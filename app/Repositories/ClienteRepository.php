<?php

namespace App\Repositories;

use App\Models\Cliente;
use App\Models\ClienteCupo;
use App\Models\ChoferCliente;
use App\Models\PlacaVehiculo;
use App\Models\RegistroPaso;
use App\Models\User;
use App\Models\GascoCupoMensual;
use App\Models\Documento;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

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
            // Cargamos el cupo de las sucursales para el modal
            'sucursales.registroPaso',
            'sucursales.cuposGasco' => function($q) {
                $q->where('mes', now()->month)->where('anio', now()->year);
            },
            // Cargamos el cupo del cliente actual
            'cuposGasco' => function($q) {
                $q->where('mes', now()->month)->where('anio', now()->year);
            }
        ])->findOrFail($id);
    }

    public function getClientesCombustible(array $filtros)
    {
        $ahora = Carbon::now();

        // 1. Iniciamos la query con las relaciones básicas
        $query = Cliente::with(['registroPaso', 'padre', 'cupos']);

        // 2. Agregamos el cálculo de SIAVCOM (Suma de todos sus cupos de combustible)
        $query->withSum('cupos as cupo_siavcom', 'litros_aprobados');

        // 3. Agregamos el Cupo GASCO del mes actual mediante un subquery
        // Esto permite que 'cupo_gasco' esté disponible directamente en cada objeto $c de la tabla
        $query->addSelect(['cupo_gasco' => GascoCupoMensual::select('litros_autorizados')
            ->whereColumn('cliente_id', 'clientes.id')
            ->where('mes', $ahora->month)
            ->where('anio', $ahora->year)
            ->limit(1)
        ]);

        // --- FILTROS EXISTENTES ---
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

        return $query->orderBy('updated_at', 'desc')->paginate(20);
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

    public function obtenerClientesActivos()
    {
        // Usamos el scope que ya definiste en el modelo Cliente
        return \App\Models\Cliente::aprobados()->orderBy('nombre', 'asc')->get();
    }

    // -------------------------------------------------------
    // GESTIÓN DE CUPOS
    // -------------------------------------------------------

    public function ajustarCupo($clienteId, $tipoCombustibleId, $litros): ClienteCupo
    {

        return DB::transaction(function () use ($clienteId, $tipoCombustibleId, $litros) {
            $cliente = Cliente::findOrFail($clienteId);

            // 1. (Opcional) Limpiar el campo viejo en la tabla clientes
            $cliente->update(['cupo' => 0]); 

            // 2. Usar updateOrCreate para que si es Padre (no tiene registro) lo cree
            // y si es Sucursal (ya tiene registro) lo actualice.
            $cupo = ClienteCupo::updateOrCreate(
                [
                    'cliente_id'          => $cliente->id, 
                    'tipo_combustible_id' => $tipoCombustibleId
                ],
                [
                    'litros_aprobados'   => $litros,
                    'litros_solicitados' => $litros,
                    'updated_at'         => now()
                ]
            );

            // 3. Actualizar el disponible del cliente
            $cliente->update(['disponible' => $litros]);

            // IMPORTANTE: Retornar el objeto $cupo para que coincida con el Return Type
            return $cupo;
        });
    }

    // -------------------------------------------------------
    // GESTIÓN DE PLACAS
    // -------------------------------------------------------

    public function registrarPlaca($clienteId, string $placa): PlacaVehiculo
    {
        $placaLimpia = strtoupper(str_replace(' ', '', $placa));

        return PlacaVehiculo::updateOrCreate(
            // El primer array es el criterio de búsqueda único global
            ['placa' => $placaLimpia], 
            // El segundo array contiene lo que se va a insertar o actualizar si se encuentra
            [
                'cliente_id' => $clienteId, 
                'activo' => 1, 
                'updated_at' => now()
            ]
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
            // Criterio de búsqueda único global
            ['cedula' => $cedula], 
            // Datos a actualizar o insertar
            [
                'cliente_id' => $clienteId,
                'nombre_completo' => strtoupper($nombreCompleto), 
                'activo' => 1, 
                'updated_at' => now()
            ]
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
        return Documento::updateOrCreate(
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
        $mes = now()->month;
        $anio = now()->year;

        return Cliente::with(['registroPaso'])
            ->with(['cuposGasco' => function($q) use ($mes, $anio) {
                $q->where('mes', $mes)->where('anio', $anio);
            }])
            ->where('parent', $parentId)
            ->get()
            ->map(function($sucursal) {
                // Creamos un atributo dinámico para facilitar el acceso en el Blade
                $sucursal->cupo_gasco_actual = $sucursal->cuposGasco->first()->litros_autorizados ?? 0;
                return $sucursal;
            });
    }

    public function existeClienteConTipoCombustible(string $rif, int $tipoCombustibleId): bool
    {
        return Cliente::where('rif', strtoupper($rif))
            ->whereHas('cupos', function ($q) use ($tipoCombustibleId) {
                $q->where('tipo_combustible_id', $tipoCombustibleId);
            })
            ->exists();
    }

    public function getTopCuposMayores(int $limit = 5)
    {
        return Cliente::whereHas('despachos', function($q) {
                $q->whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year);
            })
            ->withCount(['despachos' => function($q) {
                $q->whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year);
            }])
            ->orderByDesc('despachos_count') // Mayor a menor
            ->limit($limit)
            ->get();
    }

    public function getTopCuposMenores(int $limit = 5)
    {
        return Cliente::whereHas('despachos', function($q) {
                $q->whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year);
            })
            ->withCount(['despachos' => function($q) {
                $q->whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year);
            }])
            ->orderBy('despachos_count') // Menor a mayor
            ->limit($limit)
            ->get();
    }
}