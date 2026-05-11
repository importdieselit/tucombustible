<?php

namespace App\Repositories;

use App\Models\Vehiculo;
use App\Models\Marca;
use App\Models\Modelo;
use App\Models\Cliente;
use App\Models\TipoVehiculo;
use App\Models\TipoDocumento;
use App\Models\EstatusData;
use App\Models\Viaje;
use App\Models\Orden;
use App\Models\DespachoViaje;
use App\Models\ResumenDiario;
use App\Models\MantenimientoProgramado;
use Illuminate\Support\Facades\DB;

class VehiculoRepository
{
    public function findById($id)
    {
        return Vehiculo::findOrFail($id);
    }

    public function findByPlaca($placa)
    {
        return Vehiculo::where('placa', $placa)->first();
    }

    public function create(array $data)
    {
        return Vehiculo::create($data);
    }

    public function update(Vehiculo $vehiculo, array $data)
    {
        $vehiculo->update($data);
        return $vehiculo;
    }

    public function delete($id)
    {
        $vehiculo = $this->findById($id);
        return $vehiculo->delete();
    }

    public function getAll()
    {
        return Vehiculo::all();
    }

    public function getMarcas() { return Marca::pluck('marca', 'id'); }
    public function getModelos() { return Modelo::pluck('modelo', 'id'); }
    public function getClientes() { return Cliente::pluck('nombre', 'id'); }
    public function getTiposVehiculo() { return TipoVehiculo::pluck('tipo', 'id'); }
    public function getTiposVehiculoAll() { return TipoVehiculo::all(); }
    public function getDocumentosRequeridosV() { return TipoDocumento::where('tipo', 'V')->get(); }

    public function getEstatusDataParaDashboard()
    {
        return EstatusData::whereIn('id_estatus', [1, 2, 5])->get();
    }

    public function getViajesPorVehiculo($vehiculoId)
    {
        return DespachoViaje::query()
            ->join('viajes', 'despachos_viajes.viaje_id', '=', 'viajes.id')
            ->with(['viaje.chofer.persona', 'viaje.ayudante_chofer.persona', 'cliente', 'viaje.vehiculo'])
            ->where('viajes.vehiculo_id', $vehiculoId)
            ->orderBy('viajes.fecha_salida', 'desc')
            ->select('despachos_viajes.*') 
            ->get();
    }

    public function getHistoricoEficiencia($limit = 15)
    {
        return ResumenDiario::orderBy('fecha', 'desc')->limit($limit)->get()->sortBy('fecha');
    }

    public function getFallasPorMes($desde, $hasta)
    {
        return Orden::select(
            DB::raw("DATE_FORMAT(created_at, '%Y-%m') AS mes"),
            DB::raw("COUNT(*) AS total")
        )
        ->whereBetween('created_at', [$desde, $hasta])
        ->groupBy('mes')
        ->orderBy('mes')
        ->get();
    }
}