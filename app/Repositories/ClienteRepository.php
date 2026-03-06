<?php

namespace App\Repositories;

use App\Models\User;
use App\Models\Cliente;
use App\Models\ClienteCupo;
use App\Models\CaptacionDocumento;
use App\Models\PlacaVehiculo;
use App\Models\ChoferCliente;

class ClienteRepository
{
    public function create(array $data) { return Cliente::create($data); }

    public function crearUsuario(array $data) { return User::create($data); }

    public function registrarCupo(array $data) { return ClienteCupo::create($data); }

    public function findByToken($token) { return Cliente::where('token_registro', $token)->first(); }

    public function find($id) { return Cliente::with(['sucursales', 'user', 'documentos'])->findOrFail($id); }

    public function findByRif($rif) { return Cliente::where('rif', $rif)->first(); }

    public function guardarDocumento(array $data)
    {
        return CaptacionDocumento::updateOrCreate(
            ['cliente_id' => $data['cliente_id'], 'tipo_documento' => $data['tipo_documento']],
            ['ruta_archivo' => $data['ruta_archivo'], 'status' => 'pendiente']
        );
    }

    /**
     * Cuenta cuántos documentos tiene un cliente para validar el paso 2
     */
    public function contarDocumentos($clienteId)
    {
        return CaptacionDocumento::where('cliente_id', $clienteId)->count();
    }

    public function getClientesEnRegistro($filtros)
    {
        $query = Cliente::query()->with('user')->where('registro_paso', '<', 10);

        if (!empty($filtros['search'])) {
            $query->where(function($q) use ($filtros) {
                $q->where('nombre', 'like', "%{$filtros['search']}%")
                  ->orWhere('rif', 'like', "%{$filtros['search']}%");
            });
        }

        return $query->orderBy('updated_at', 'desc')->paginate(20);
    }

    public function getStatsGlobales()
    {
        return [
            'total'             => Cliente::count(),
            'activos'           => Cliente::where('registro_paso', 10)->where('status', 1)->count(),
            'inactivos'         => Cliente::where('registro_paso', 10)->where('status', 0)->count(),
            'total_en_registro' => Cliente::where('registro_paso', '<', 10)->count(),
            'en_espera_revision'=> Cliente::where('registro_paso', 3)->count(),
        ];
    }

    public function avanzarPaso($clienteId, $nuevoPaso, array $datosExtra = [])
    {
        $cliente = Cliente::findOrFail($clienteId);

        if (isset($datosExtra['fecha_inspeccion'])) {
            $cliente->fecha_inspeccion = $datosExtra['fecha_inspeccion'];
            $cliente->inspector = $datosExtra['inspector'] ?? null;
        }

        if ($nuevoPaso == 10) {
            $cliente->status = 1;
            if ($cliente->user) {
                $cliente->user->update(['status_usuario' => 'activo']);
            }
        }

        $cliente->registro_paso = $nuevoPaso;
        $cliente->save();
        return $cliente;
    }

    public function update($id, array $data)
    {
        $cliente = Cliente::findOrFail($id);
        $cliente->update($data);
        return $cliente;
    }

    public function toggleStatus($id)
    {
        $cliente = Cliente::findOrFail($id);
        $cliente->status = ($cliente->status == 1) ? 0 : 1;
        $cliente->save();
        return $cliente;
    }
}