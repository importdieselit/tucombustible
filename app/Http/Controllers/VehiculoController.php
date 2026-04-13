<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\VehiculoService;
use App\Repositories\VehiculoRepository;
use App\Http\Requests\VehiculoStoreRequest;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class VehiculoController extends BaseController
{
    protected $service;
    protected $repo;

    public function __construct(VehiculoService $service, VehiculoRepository $repo)
    {
        parent::__construct(); 
        $this->service = $service;
        $this->repo = $repo;
    }

    public function index()
    {
        $vehiculos = $this->repo->getAll();
        return view('vehiculo.index', compact('vehiculos'));
    }

    public function filter(Request $request)
    {
        return $this->list(\App\Models\Vehiculo::query()); 
    }

    protected function getAdditionalData()
    {
        return $this->service->prepareDashboardData(Auth::user()->cliente_id);
    }

    public function create()
    {
        $marcas = $this->repo->getMarcas();
        $modelos = $this->repo->getModelos();
        $clientes = $this->repo->getClientes();
        $tiposVehiculo = $this->repo->getTiposVehiculo();
        $documentosRequeridos = $this->repo->getDocumentosRequeridosV();
        
        return view('vehiculo.create', compact('marcas', 'modelos', 'clientes', 'tiposVehiculo','documentosRequeridos'));
    }

    public function store(Request $request)
    {
        app(VehiculoStoreRequest::class); 
        try {
            $this->service->guardarVehiculo($request->all(), $request->file('documentos'), $request->file('fotos'));
            Session::flash('success', 'Vehículo creado exitosamente.');
        } catch (\Exception $e) {
            Log::error('Error Store Vehiculo: ' . $e->getMessage());
            Session::flash('error', 'Error al crear el registro.');
        }
        return Redirect::route('vehiculos.list');
    }

    public function edit($id)
    {
        $item = $this->repo->findById($id);
        $marcas = $this->repo->getMarcas();
        $modelos = $this->repo->getModelos();
        $clientes = $this->repo->getClientes();
        $tiposVehiculo = $this->repo->getTiposVehiculo();
        $documentosRequeridos = $this->repo->getDocumentosRequeridosV();

        return view('vehiculo.edit', compact('item', 'marcas', 'modelos', 'clientes', 'tiposVehiculo','documentosRequeridos'));
    }

    public function updateV(Request $request)
    {
        try {
            $this->service->actualizarVehiculo($request->id, $request->all(), $request->file('documentos'), $request->file('fotos'), $request->input('fotos_a_eliminar'));
            Session::flash('success', '¡Vehículo actualizado!');
            return Redirect::route('vehiculos.index');
        } catch (\Exception $e) {
            Log::error("Error Update Vehiculo: " . $e->getMessage());
            return Redirect::back()->with('error', 'Error al actualizar.');
        }
    }

    public function importSave(Request $request)
    {
        $request->validate(['file' => 'required|mimes:xlsx,xls,csv']);
        try {
            $this->service->procesarImportacion($request->file('file'));
            Session::flash('success', 'Importación completada.');
        } catch (\Exception $e) {
            Session::flash('error', 'Error en importación: ' . $e->getMessage());
        }
        return Redirect::back();
    }
}