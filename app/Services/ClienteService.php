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
        return DB::transaction(function () use ($data) {

            $rifOficial = strtoupper($data['rif']);
            $rifLimpio  = str_replace(['-', ' '], '', $rifOficial);

            $parentId = 0;
            if (isset($data['tipo_cliente']) && $data['tipo_cliente'] === 'sucursal' && !empty($data['token_padre'])) {
                $padre = Cliente::where('token_registro', strtoupper($data['token_padre']))->first();

                if (!$padre) {
                    throw new \Exception('El código de empresa principal (Token) no es válido o no existe.');
                }

                if ($padre->status !== Cliente::STATUS_APROBADO) {
                    throw new \Exception('La empresa principal no está activa. No es posible registrar una sucursal.');
                }

                $parentId = $padre->id;
            }

            $cliente = $this->repository->create([
                'nombre'              => strtoupper($data['nombre']),
                'rif'                 => $rifOficial,
                'contacto'            => strtoupper($data['contacto'] ?? ''),
                'telefono'            => $data['telefono'] ?? null,
                'contacto_alt'        => !empty($data['contacto_alt']) ? strtoupper($data['contacto_alt']) : null,
                'telefono_alt'        => $data['telefono_alt'] ?? null,
                'email'               => $data['email'],
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

            $this->repository->crearUsuario([
                'name'           => strtoupper($data['contacto'] ?? $data['nombre']),
                'email'          => $data['email'],
                'password'       => Hash::make($rifLimpio),
                'id_perfil'      => 3,
                'cliente_id'     => $cliente->id,
                'status_usuario' => 'en_registro',
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

        if ($cliente->status !== Cliente::STATUS_APROBADO) {
            throw new \Exception('Solo se puede ajustar el cupo de clientes aprobados.');
        }

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
    // RANKINGS DE CUPO
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