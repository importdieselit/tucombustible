<?php

namespace App\Repositories;

use App\Models\Cliente;
use App\Models\ClienteCupo;
use App\Models\ChoferCliente;
use App\Models\PlacaVehiculo;
use App\Models\RegistroPaso;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ClienteRepository
{
    // -------------------------------------------------------
    // CONSULTAS GENERALES
    // -------------------------------------------------------

    public function find($id)
    {
        return Cliente::with([
            'registroPaso',
            'cupos.tipoCombustible',
            'placas',
            'choferes',
            'estado',
            'ciudad',
            'sucursales.registroPaso',
        ])->findOrFail($id);
    }

    public function getClientesCombustible(array $filtros)
    {
        $query = Cliente::with(['registroPaso','padre']);

        if (!empty($filtros['search'])) {
            $query->where(function ($q) use ($filtros) {
                $q->where('nombre', 'like', "%{$filtros['search']}%")
                  ->orWhere('rif', 'like', "%{$filtros['search']}%");
            });
        }

        if (isset($filtros['status']) && $filtros['status'] !== '') {
            $query->where('status', $filtros['status']);
        }

        if (!empty($filtros['tipo'])) {
            if ($filtros['tipo'] === 'padre') {
            $query->where('parent', 0);
        } elseif ($filtros['tipo'] === 'sucursal') {
            $query->where('parent', '>', 0);
        }
        }

        return $query->orderBy('updated_at', 'desc')->paginate(15);
    }

    public function getStatsGlobales(): array
    {
        return [
            'total_clientes'    => Cliente::count(),
            'en_registro'       => Cliente::enRegistro()->count(),
            'aprobados'         => Cliente::aprobados()->count(),
            'rechazados'        => Cliente::rechazados()->count(),
            'inactivos'         => Cliente::inactivos()->count(),
        ];
    }

    public function getPasos()
    {
        return RegistroPaso::activos()->get();
    }

    // -------------------------------------------------------
    // CREACIÓN
    // -------------------------------------------------------

    public function create(array $data): Cliente
    {
        return Cliente::create($data);
    }

    public function crearUsuario(array $data): User
    {
        return User::create($data);
    }

    public function registrarCupo(array $data): ClienteCupo
    {
        return ClienteCupo::create($data);
    }

    // -------------------------------------------------------
    // ACTUALIZACIÓN DE DATOS
    // -------------------------------------------------------

    public function update($id, array $data): Cliente
    {
        $cliente = Cliente::findOrFail($id);
        $cliente->update($data);
        return $cliente;
    }

    // -------------------------------------------------------
    // FLUJO DE REGISTRO (PASOS)
    // -------------------------------------------------------

    public function avanzarPaso($clienteId, int $nuevoPaso): Cliente
    {
        return DB::transaction(function () use ($clienteId, $nuevoPaso) {
            $cliente = Cliente::findOrFail($clienteId);
            $cliente->registro_paso = $nuevoPaso;
            $cliente->save();
            return $cliente;
        });
    }

    // -------------------------------------------------------
    // GESTIÓN DE STATUS
    // -------------------------------------------------------

    public function aprobar($clienteId): Cliente
    {
        return DB::transaction(function () use ($clienteId) {
            $cliente = Cliente::findOrFail($clienteId);
            $cliente->status         = Cliente::STATUS_APROBADO;
            $cliente->registro_paso  = 5;
            $cliente->fecha_aprobacion = now();
            $cliente->save();
            return $cliente;
        });
    }

    public function rechazar($clienteId): Cliente
    {
        return DB::transaction(function () use ($clienteId) {
            $cliente = Cliente::findOrFail($clienteId);
            $cliente->status        = Cliente::STATUS_RECHAZADO;
            $cliente->registro_paso = 5;
            $cliente->save();
            return $cliente;
        });
    }

    public function inactivar($clienteId): Cliente
    {
        return DB::transaction(function () use ($clienteId) {
            $cliente = Cliente::findOrFail($clienteId);
            $cliente->status = Cliente::STATUS_INACTIVO;
            $cliente->save();

            // Si es Padre, inactiva todas sus sucursales en cascada
            if ($cliente->es_padre) {
                Cliente::where('parent', $clienteId)
                    ->update(['status' => Cliente::STATUS_INACTIVO]);
            }

            return $cliente;
        });
    }

    public function reactivar($clienteId): Cliente
    {
        $cliente = Cliente::findOrFail($clienteId);
        $cliente->status = Cliente::STATUS_APROBADO;
        $cliente->save();
        return $cliente;
    }

    // -------------------------------------------------------
    // GESTIÓN DE CUPOS
    // -------------------------------------------------------

    public function ajustarCupo($clienteId, int $tipoCombustibleId, float $litros): ClienteCupo
    {
        return DB::transaction(function () use ($clienteId, $tipoCombustibleId, $litros) {
            // Actualiza solo el cupo del tipo de combustible indicado
            // Si no existe, lo crea (caso de primer registro de cupo)
            $cupo = ClienteCupo::updateOrCreate(
                [
                    'cliente_id'          => $clienteId,
                    'tipo_combustible_id' => $tipoCombustibleId,
                ],
                [
                    'litros_aprobados'   => $litros,
                    'litros_solicitados' => $litros,
                ]
            );

            // Sincroniza el campo cupo en la tabla clientes (referencia rápida)
            Cliente::where('id', $clienteId)->update(['cupo' => $litros, 'disponible' => $litros]);

            return $cupo;
        });
    }

    // -------------------------------------------------------
    // GESTIÓN DE PLACAS
    // -------------------------------------------------------

    public function registrarPlaca($clienteId, string $placa): PlacaVehiculo
    {
        return PlacaVehiculo::updateOrCreate(
            ['cliente_id' => $clienteId, 'placa' => strtoupper(str_replace(' ', '', $placa))],
            ['activo' => 1, 'updated_at' => now()]
        );
    }

    public function inactivarPlaca($placaId): PlacaVehiculo
    {
        $placa = PlacaVehiculo::findOrFail($placaId);
        $placa->activo = 0;
        $placa->save();
        return $placa;
    }

    public function getPlacas($clienteId)
    {
        return PlacaVehiculo::where('cliente_id', $clienteId)->activas()->get();
    }

    // -------------------------------------------------------
    // GESTIÓN DE CHOFERES
    // -------------------------------------------------------

    public function registrarChofer($clienteId, string $nombreCompleto, string $cedula): ChoferCliente
    {
        return ChoferCliente::updateOrCreate(
            ['cliente_id' => $clienteId, 'cedula' => $cedula],
            ['nombre_completo' => strtoupper($nombreCompleto), 'activo' => 1, 'updated_at' => now()]
        );
    }

    public function inactivarChofer($choferId): ChoferCliente
    {
        $chofer = ChoferCliente::findOrFail($choferId);
        $chofer->activo = 0;
        $chofer->save();
        return $chofer;
    }

    public function getChoferes($clienteId)
    {
        return ChoferCliente::where('cliente_id', $clienteId)->activos()->get();
    }

    // -------------------------------------------------------
    // DOCUMENTOS (Lógica interna — no expuesta en vistas)
    // -------------------------------------------------------

    public function guardarDocumento(array $data)
    {
        return \App\Models\Documento::updateOrCreate(
            [
                'cliente_id'       => $data['cliente_id'],
                'nombre_documento' => $data['tipo_documento'],
            ],
            [
                'requisito_id'     => $data['requisito_id'] ?? 0,
                'tipo_anexo'       => $data['tipo_anexo'],
                'nombre_documento' => $data['tipo_documento'],
                'ruta'             => $data['ruta'],
                'validado'         => 0,
            ]
        );
    }

    // -------------------------------------------------------
    // SUCURSALES
    // -------------------------------------------------------

    public function getSucursales($parentId)
    {
        return Cliente::with(['registroPaso', 'cupos.tipoCombustible'])
            ->where('parent', $parentId)
            ->get();
    }

    public function existeClienteConTipoCombustible(string $rif, int $tipoCombustibleId): bool
    {
        return Cliente::where('rif', strtoupper($rif))
            ->whereHas('cupos', function ($q) use ($tipoCombustibleId) {
                $q->where('tipo_combustible_id', $tipoCombustibleId);
            })
            ->exists();
    }
}