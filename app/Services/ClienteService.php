<?php

namespace App\Services;

use App\Models\Cliente;
use App\Repositories\ClienteRepository;
use Illuminate\Support\Facades\{DB, Hash, Log, Storage};
use Illuminate\Support\Str;

class ClienteService
{
    protected ClienteRepository $repository;

    public function __construct(ClienteRepository $repository)
    {
        $this->repository = $repository;
    }

    // -------------------------------------------------------
    // REGISTRO DE CLIENTES
    // -------------------------------------------------------

    public function registrarCliente(array $data): Cliente
    {
        // Validar unicidad rif + tipo_combustible
        $tipoCombustibleId = $data['tipo_combustible_id'] ?? null;

        if (!$tipoCombustibleId) {
            throw new \Exception('Debe seleccionar el tipo de combustible.');
        }

        if ($this->repository->existeClienteConTipoCombustible($data['rif'], $tipoCombustibleId)) {
            throw new \Exception(
                'Ya existe un cliente registrado con este RIF para el tipo de combustible seleccionado. ' .
                'Si desea registrar un cupo de otro combustible, debe crear un nuevo registro.'
            );
        }

        return DB::transaction(function () use ($data, $tipoCombustibleId) {

            $rifOficial = strtoupper($data['rif']);
            $rifLimpio  = str_replace(['-', ' '], '', $rifOficial);

            // Determinar si es Sucursal y resolver el parent_id
            $parentId = 0;
            if (isset($data['tipo_cliente']) && $data['tipo_cliente'] === 'sucursal' && !empty($data['token_padre'])) {
                $padre = Cliente::where('token_registro', strtoupper($data['token_padre']))->first();

                if (!$padre) {
                    throw new \Exception('El código de empresa principal (Token) no es válido o no existe.');
                }

                // Solo se puede registrar una sucursal bajo un Cliente Padre activo (aprobado)
                if ($padre->status !== Cliente::STATUS_APROBADO) {
                    throw new \Exception('La empresa principal no está activa. No es posible registrar una sucursal.');
                }

                $parentId = $padre->id;
            }

            $cliente = $this->repository->create([
                'nombre'              => strtoupper($data['nombre']),
                'rif'                 => $rifOficial,
                'contacto'            => strtoupper($data['contacto'] ?? ''),
                'email'               => $data['email'],
                'telefono'            => $data['telefono'] ?? null,
                'estado_id'           => $data['estado_id'] ?? null,
                'ciudad_id'           => $data['ciudad_id'] ?? null,
                'direccion'           => strtoupper($data['direccion'] ?? ''),
                'direccion_operativa' => strtoupper($data['direccion_operativa'] ?? ''),
                'ciiu'                => $data['ciiu'] ?? null,
                'sector'              => $data['sector'] ?? null,
                'registro_paso'       => 1,
                'status'              => Cliente::STATUS_EN_REGISTRO,
                'parent'              => $parentId,
                'token_registro'      => strtoupper(Str::random(10)),
            ]);

            // Crear usuario asociado — contraseña inicial es el RIF sin guiones
            $this->repository->crearUsuario([
                'name'                 => strtoupper($data['contacto'] ?? $data['nombre']),
                'email'                => $data['email'],
                'password'             => Hash::make($rifLimpio),
                'id_perfil'            => 3,
                'cliente_id'           => $cliente->id,
                'status_usuario'       => 'en_registro'
            ]);
            
            $this->repository->registrarCupo([
            'cliente_id'          => $cliente->id,
            'tipo_combustible_id' => $tipoCombustibleId,
            'litros_solicitados'  => $data['litros_solicitados'] ?? 0,
            'litros_aprobados'    => 0, // Se aprueba después cuando el Admin aprueba al cliente
            ]);

            return $cliente;
        });
    }

    // -------------------------------------------------------
    // CONSULTAS
    // -------------------------------------------------------

    public function obtenerExpediente($id): Cliente
    {
        return $this->repository->find($id);
    }

    public function obtenerDashboardAdmin(array $filtros): array
    {
        return [
            'clientes' => $this->repository->getClientesCombustible($filtros),
            'stats'    => $this->repository->getStatsGlobales(),
            'pasos'    => $this->repository->getPasos(),
        ];
    }

    // -------------------------------------------------------
    // FLUJO DE REGISTRO (PASOS)
    // -------------------------------------------------------

    public function avanzarPaso($clienteId, int $nuevoPaso): Cliente
    {
        // Validar que el paso existe y está activo
        $pasoValido = $this->repository->getPasos()->firstWhere('id', $nuevoPaso);

        if (!$pasoValido) {
            throw new \Exception('El paso indicado no existe o no está activo.');
        }

        return $this->repository->avanzarPaso($clienteId, $nuevoPaso);
    }

    // -------------------------------------------------------
    // GESTIÓN DE STATUS
    // -------------------------------------------------------

    public function aprobarCliente($clienteId): Cliente
    {
        $cliente = Cliente::findOrFail($clienteId);

        if ($cliente->status === Cliente::STATUS_APROBADO) {
            throw new \Exception('El cliente ya se encuentra aprobado.');
        }

        return $this->repository->aprobar($clienteId);
    }

    public function rechazarCliente($clienteId): Cliente
    {
        $cliente = Cliente::findOrFail($clienteId);

        if ($cliente->status === Cliente::STATUS_RECHAZADO) {
            throw new \Exception('El cliente ya se encuentra rechazado.');
        }

        return $this->repository->rechazar($clienteId);
    }

    public function inactivarCliente($clienteId): Cliente
    {
        $cliente = Cliente::findOrFail($clienteId);

        if ($cliente->status === Cliente::STATUS_INACTIVO) {
            throw new \Exception('El cliente ya se encuentra inactivo.');
        }

        // Solo se pueden inactivar clientes aprobados
        if ($cliente->status !== Cliente::STATUS_APROBADO) {
            throw new \Exception('Solo se pueden inactivar clientes aprobados.');
        }

        return $this->repository->inactivar($clienteId);
    }

    public function reactivarCliente($clienteId): Cliente
    {
        $cliente = Cliente::findOrFail($clienteId);

        if ($cliente->status !== Cliente::STATUS_INACTIVO) {
            throw new \Exception('Solo se pueden reactivar clientes inactivos.');
        }

        return $this->repository->reactivar($clienteId);
    }

    // -------------------------------------------------------
    // GESTIÓN DE CUPOS
    // -------------------------------------------------------

    public function ajustarCupo($clienteId, int $tipoCombustibleId, float $litros)
    {
        $cliente = Cliente::findOrFail($clienteId);

        // Solo se ajusta el cupo de clientes aprobados
        if ($cliente->status !== Cliente::STATUS_APROBADO) {
            throw new \Exception('Solo se puede ajustar el cupo de clientes aprobados.');
        }

        // Verificar que el tipo de combustible coincida con el cupo existente
        // Si el cliente ya tiene cupo de un tipo, no puede agregar otro — debe crear un registro nuevo
        $cupoExistente = $cliente->cupos()
            ->where('tipo_combustible_id', '!=', $tipoCombustibleId)
            ->first();

        if ($cupoExistente) {
            throw new \Exception(
                'El cliente ya tiene un cupo asignado de otro tipo de combustible. ' .
                'Para registrar un tipo diferente debe crear un nuevo registro de cliente.'
            );
        }

        return $this->repository->ajustarCupo($clienteId, $tipoCombustibleId, $litros);
    }

    // -------------------------------------------------------
    // GESTIÓN DE PLACAS Y CHOFERES (Solo Admin)
    // -------------------------------------------------------

    public function registrarPlaca($clienteId, string $placa)
    {
        $cliente = Cliente::findOrFail($clienteId);

        if ($cliente->status !== Cliente::STATUS_APROBADO) {
            throw new \Exception('Solo se pueden registrar placas en clientes aprobados.');
        }

        return $this->repository->registrarPlaca($clienteId, $placa);
    }

    public function inactivarPlaca($placaId)
    {
        return $this->repository->inactivarPlaca($placaId);
    }

    public function registrarChofer($clienteId, string $nombreCompleto, string $cedula)
    {
        $cliente = Cliente::findOrFail($clienteId);

        if ($cliente->status !== Cliente::STATUS_APROBADO) {
            throw new \Exception('Solo se pueden registrar choferes en clientes aprobados.');
        }

        return $this->repository->registrarChofer($clienteId, $nombreCompleto, $cedula);
    }

    public function inactivarChofer($choferId)
    {
        return $this->repository->inactivarChofer($choferId);
    }

    // -------------------------------------------------------
    // DOCUMENTOS (Lógica interna — no expuesta en vistas)
    // -------------------------------------------------------

    public function subirDocumento($clienteId, $file, string $tipoDocumento)
    {
        return DB::transaction(function () use ($clienteId, $file, $tipoDocumento) {
            $extension = $file->getClientOriginalExtension();
            $fileName  = "{$tipoDocumento}_" . time() . ".{$extension}";
            $ruta      = $file->storeAs("expedientes/{$clienteId}", $fileName, 'public');

            return $this->repository->guardarDocumento([
                'cliente_id'     => $clienteId,
                'tipo_documento' => $tipoDocumento,
                'tipo_anexo'     => $extension,
                'ruta'           => $ruta,
            ]);
        });
    }

    // -------------------------------------------------------
    // RANKINGS DE CUPO (agregar al final de ClienteService)
    // -------------------------------------------------------

    public function getRankingCuposMayores(int $limit = 5)
    {
        return $this->repository->getTopCuposMayores($limit);
    }

    public function getRankingCuposMenores(int $limit = 5)
    {
        return $this->repository->getTopCuposMenores($limit);
    }
}