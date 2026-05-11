<?php

namespace App\Services;

use App\Repositories\VehiculoRepository;
use App\Models\Vehiculo;
use App\Models\Marca;
use App\Models\Modelo;
use App\Models\VehiculoFoto;
use App\Models\TipoDocumento;
use App\Models\InventarioSuministro;
use App\Models\Viaje;
use App\Models\ResumenDiario;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Carbon;
use Maatwebsite\Excel\Facades\Excel;

class VehiculoService
{
    protected $repo;

    public function __construct(VehiculoRepository $repo)
    {
        $this->repo = $repo;
    }

    public function guardarVehiculo(array $data, $documentos = null, $fotos = null)
    {
        return DB::transaction(function () use ($data, $documentos, $fotos) {
            $data = $this->procesarMarcaModelo($data);
            
            if (!isset($data['id_cliente']) || is_null($data['id_cliente'])) { 
                $data['id_cliente'] = Auth::user()->cliente_id ?? 348; 
            }
            $data['es_flota'] = true;

            $vehiculo = $this->repo->create($data);

            if ($documentos) $this->handleDocumentos($documentos, $vehiculo);
            if ($fotos) $this->handleFotoUpload($fotos, $vehiculo);

            return $vehiculo;
        });
    }

    public function actualizarVehiculo($id, array $data, $documentos = null, $fotos = null, $fotosAEliminar = null)
    {
        return DB::transaction(function () use ($id, $data, $documentos, $fotos, $fotosAEliminar) {
            $vehiculo = $this->repo->findById($id);
            $data = $this->procesarMarcaModelo($data);
            
            $this->repo->update($vehiculo, $data);

            if ($fotosAEliminar) {
                $idsParaEliminar = explode(',', $fotosAEliminar);
                VehiculoFoto::whereIn('id', $idsParaEliminar)->where('vehiculo_id', $vehiculo->id)->delete();
            }

            if ($documentos) $this->handleDocumentos($documentos, $vehiculo);
            if ($fotos) $this->handleFotoUpload($fotos, $vehiculo);

            return $vehiculo;
        });
    }

    public function procesarImportacion($file)
    {
        $rows = Excel::toArray(null, $file)[1];
        $header = array_map('trim', array_change_key_case($rows[0], CASE_LOWER));
        $dataRows = array_slice($rows, 1);
        
        foreach ($dataRows as $row) {
            if (empty(array_filter($row))) continue;
            $rowData = array_combine($header, $row);

            $marca = Marca::firstOrCreate(['marca' => trim(strtoupper($rowData['marca']))]);
            $modelo = Modelo::firstOrCreate([
                'modelo' => trim(strtoupper($rowData['modelo'])),
                'id_marca' => $marca->id
            ]);

            $poliza = (strtoupper($rowData['poliza de seguro']) === 'PENDIENTE' || !strtotime($rowData['poliza de seguro'])) 
                ? null : Carbon::parse($rowData['poliza de seguro'])->format('Y-m-d');

            $rotc_venc = null;
            $rotc = $rowData['rotc'];
            if (strtoupper($rotc) !== 'PENDIENTE') {
                $rotcArray = explode('- AL ', $rotc);
                $rotc_venc = (!empty($rotcArray[1])) ? Carbon::createFromFormat('d/m/Y', $rotcArray[1])->format('Y-m-d') : null;
                $rotc = trim($rotcArray[0]);
            }

            $vehiculoData = [
                'flota' => $rowData['flota'],
                'placa' => $rowData['placa'],
                'marca' => $marca->id,
                'modelo' => $modelo->id,
                'poliza_fecha_out' => $poliza,
                'rotc' => $rotc,
                'rotc_venc' => $rotc_venc,
                'es_flota' => true,
                'estatus' => 1
            ];

            $existente = $this->repo->findByPlaca($rowData['placa']);
            $existente ? $this->repo->update($existente, $vehiculoData) : $this->repo->create($vehiculoData);
        }
    }

    public function prepareDashboardData($cliente_id)
    {
        $unidades_con_alerta = Vehiculo::getUnidadesConDocumentosVencidos($cliente_id)->count();
        $total_vehiculos = Vehiculo::misVehiculos()->count();
        $total_flota = Vehiculo::miFlota()->count();
        $unidades_disponibles = Vehiculo::Disponibles()->count();
        
        $historico = $this->repo->getHistoricoEficiencia();
        $tipos = $this->repo->getTiposVehiculoAll();
        $estatusList = $this->repo->getEstatusDataParaDashboard();

        $series = [];
        foreach ($estatusList as $est) {
            $dataPorTipo = [];
            foreach ($tipos as $tipo) {
                $dataPorTipo[] = Vehiculo::where('estatus', $est->id_estatus)->where('tipo', $tipo->id)->count();
            }
            $series[] = ['name' => $est->auto, 'data' => $dataPorTipo];
        }

        return [
            'unidades_con_alerta' => $unidades_con_alerta,
            'total_vehiculos' => $total_vehiculos,
            'total_flota' => $total_flota,
            'v_dis' => $unidades_disponibles,
            'chartCategorias' => $tipos->pluck('tipo')->toArray(),
            'chartSeries' => $series,
            'chartLabels' => $historico->map(fn($h) => Carbon::parse($h->fecha)->format('d/M')),
            'chartDataCierre' => $historico->pluck('disponibilidad'),
        ];
    }

    private function procesarMarcaModelo(array $data)
    {
        if (isset($data['marca']) && $data['marca'] === 'otro') {
            $nuevaMarca = Marca::create(['marca' => strtoupper($data['nueva_marca'])]);
            $data['marca'] = $nuevaMarca->id;
        }
        if (isset($data['modelo']) && $data['modelo'] === 'otro') {
            $nuevoModelo = Modelo::create(['modelo' => strtoupper($data['nuevo_modelo']), 'id_marca' => $data['marca']]);
            $data['modelo'] = $nuevoModelo->id;
        }
        return $data;
    }

    private function handleDocumentos($documentos, Vehiculo $v)
    {
        foreach ($documentos as $tipoId => $file) {
            if ($file->isValid()) {
                $tipoDoc = TipoDocumento::find($tipoId);
                $nombre = "{$tipoDoc->abreviatura}_{$v->id}.{$file->getClientOriginalExtension()}";
                $file->storeAs("public/vehiculos/{$v->id}/documentos", $nombre);
            }
        }
    }

    private function handleFotoUpload($fotos, Vehiculo $v)
    {
        foreach ($fotos as $index => $foto) {
            $ruta = $foto->store('public/vehiculos');
            VehiculoFoto::create([
                'vehiculo_id' => $v->id,
                'ruta' => str_replace('public/', 'storage/', $ruta),
                'es_principal' => ($index === 0 && $v->fotos()->count() === 0)
            ]);
        }
    }
}