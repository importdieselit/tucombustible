<?php

namespace App\Repositories;

use App\Models\{User, Cliente, ClienteCupo, Documento};
use Illuminate\Support\Facades\DB;

class ClienteRepository
{
    public function find($id)
    {
        return Cliente::with(['user', 'documentos'])->findOrFail($id);
    }

    public function countByPaso($operador, $paso = null)
    {
        if ($paso === null) {
            return Cliente::where('registro_paso', $operador)->count();
        }
        return Cliente::where('registro_paso', $operador, $paso)->count();
    }

    public function getClientesEnRegistro($filtros)
    {
        $query = Cliente::query();

        if (!empty($filtros['search'])) {
            $query->where(function($q) use ($filtros) {
                $q->where('nombre', 'like', "%{$filtros['search']}%")
                  ->orWhere('rif', 'like', "%{$filtros['search']}%");
            });
        }

        if (!empty($filtros['status_filtro'])) {
            if ($filtros['status_filtro'] == 'activos') {
                $query->where('registro_paso', 10);
            } elseif ($filtros['status_filtro'] == 'proceso') {
                $query->where('registro_paso', '<', 10);
            }
        }

        return $query->orderBy('updated_at', 'desc')->paginate(15);
    }

    public function getStatsGlobales()
    {
        return [
            'total_clientes'      => Cliente::count(),
            'total_en_registro'   => $this->countByPaso('<', 10),
            'en_espera_revision'  => $this->countByPaso(3),
            'activos'             => $this->countByPaso(10),
        ];
    }

    public function avanzarPaso($clienteId, $nuevoPaso, array $extra = [])
    {
        return DB::transaction(function () use ($clienteId, $nuevoPaso, $extra) {
            $cliente = Cliente::findOrFail($clienteId);

            if ($nuevoPaso == 10) {
                $cliente->status = 1;
            }

            // SOLO actualizamos columnas que existen en la tabla 'clientes' según el DDL
            if (isset($extra['cupo'])) {
                $cliente->cupo = $extra['cupo'];
                $cliente->disponible = $extra['disponible'] ?? $extra['cupo'];
                
                $tipoId = $extra['tipo_combustible_id'] ?? 1;

                // El tipo de combustible se maneja EXCLUSIVAMENTE en la tabla cliente_cupos
                ClienteCupo::where('cliente_id', $clienteId)->delete();

                ClienteCupo::create([
                    'cliente_id'          => $clienteId,
                    'tipo_combustible_id' => $tipoId,
                    'litros_aprobados'    => $extra['cupo'],
                    'litros_solicitados'  => $extra['cupo'],
                    'disponible'          => $extra['disponible'] ?? $extra['cupo']
                ]);
            }

            $cliente->registro_paso = $nuevoPaso;
            $cliente->save();

            return $cliente;
        });
    }

    public function guardarDocumento(array $data)
    {
        $mapaRequisitos = [
            'planilla_solicitud'        => 1,
            'declaracion_jurada'        => 2,
            'carta_ministerio'          => 3,
            'registro_mercantil'        => 4,
            'acta_constitutiva'         => 5,
            'rif_legalizado'            => 6,
            'dni_contacto'              => 7,
            'rif_contacto'              => 8,
            'islr'                      => 9,
            'permiso_bomberos'          => 10,
            'maquinaria_tanques'        => 11,
            'croquis_ubicacion'         => 12,
        ];

        return Documento::updateOrCreate(
            [
                'cliente_id'       => $data['cliente_id'],
                'nombre_documento' => $data['tipo_documento'] 
            ],
            [
                'requisito_id'     => $mapaRequisitos[$data['tipo_documento']] ?? 0,
                'tipo_anexo'       => $data['tipo_anexo'],
                'nombre_documento' => $data['tipo_documento'], 
                'ruta'             => $data['ruta'],
                'validado'         => 0
            ]
        );
    }

    public function create(array $data) { return Cliente::create($data); }
    public function crearUsuario(array $data) { return User::create($data); }
    public function registrarCupo(array $data) { return ClienteCupo::create($data); }

    public function toggleStatus($id)
    {
        $cliente = Cliente::findOrFail($id);
        $cliente->status = !$cliente->status;
        $cliente->save();
        return $cliente;
    }

    public function getSucursales($parentId)
    {
        return Cliente::where('parent', $parentId)->get();
    }
}