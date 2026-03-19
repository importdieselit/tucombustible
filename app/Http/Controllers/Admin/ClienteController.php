<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ClienteService;
use App\Services\ClienteLubricanteService;
use App\Models\Cliente;
use App\Models\TipoCombustible;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{Redirect, Session, Log, Auth};

class ClienteController extends Controller
{
    protected ClienteService $clienteService;
    protected ClienteLubricanteService $lubricanteService;

    public function __construct(
        ClienteService $clienteService,
        ClienteLubricanteService $lubricanteService
    ) {
        $this->clienteService    = $clienteService;
        $this->lubricanteService = $lubricanteService;
    }

    // -------------------------------------------------------
    // LISTADO PRINCIPAL — CLIENTES COMBUSTIBLE
    // -------------------------------------------------------

    public function index(Request $request)
    {
        $filtros = $request->only(['search', 'status', 'tipo']);
        $data    = $this->clienteService->obtenerDashboardAdmin($filtros);

        return view('admin.cliente.index', [
            'clientes'       => $data['clientes'],
            'stats'          => $data['stats'],
            'pasos'          => $data['pasos'],
            'filtros'        => $filtros,
            'rankingMayores' => $this->clienteService->getRankingCuposMayores(),
            'rankingMenores' => $this->clienteService->getRankingCuposMenores(),
        ]);
    }

    // -------------------------------------------------------
    // EXPEDIENTE — VER DETALLE DE UN CLIENTE
    // -------------------------------------------------------

    public function show($id)
    {
        $cliente          = $this->clienteService->obtenerExpediente($id);
        $tiposCombustible = TipoCombustible::all();

        return view('admin.cliente.show', compact('cliente', 'tiposCombustible'));
    }

    // -------------------------------------------------------
    // REGISTRO DE NUEVO CLIENTE (ADMIN)
    // -------------------------------------------------------

    public function create()
    {
        $tiposCombustible = TipoCombustible::all();
        $estados          = \App\Models\Estado::orderBy('nombre')->get();

        return view('admin.cliente.create', compact('tiposCombustible', 'estados'));
    }

    public function store(Request $request)
    {
        if ($request->has('rif_tipo') && $request->has('rif_numero')) {
            $request->merge([
                'rif' => strtoupper($request->rif_tipo) . '-' . $request->rif_numero
            ]);
        }

        $request->validate([
            'nombre'              => 'required|string|max:255',
            'rif'                 => 'required|string|max:12',
            'email'               => 'required|email:rfc,dns|max:255',
            'contacto'            => 'required|string|regex:/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]+$/u|max:255',
            'telefono'            => 'nullable|digits_between:10,11',
            'estado_id'           => 'required|exists:estados,id',
            'ciudad_id'           => 'required|exists:ciudades,id',
            'direccion'           => 'nullable|string',
            'direccion_operativa' => 'required|string',
            'tipo_combustible_id' => 'required|exists:tipos_combustible,id',
            'tipo_cliente'        => 'nullable|in:padre,sucursal',
            'token_padre'         => 'nullable|string|exists:clientes,token_registro',
        ], [
            'contacto.regex'             => 'El nombre de contacto solo debe contener letras.',
            'telefono.digits_between'    => 'El teléfono debe tener entre 10 y 11 dígitos.',
            'token_padre.exists'         => 'El Token de la empresa principal no es válido.',
            'tipo_combustible_id.exists' => 'El tipo de combustible seleccionado no es válido.',
        ]);

        try {
            $this->clienteService->registrarCliente($request->all());
            Session::flash('success', 'Cliente registrado correctamente.');
            return Redirect::route('clientes.index');
        } catch (\Exception $e) {
            Log::error('Error al registrar cliente: ' . $e->getMessage());
            return Redirect::back()->withInput()->with('error', $e->getMessage());
        }
    }

    // -------------------------------------------------------
    // EDICIÓN DE DATOS DEL CLIENTE
    // -------------------------------------------------------

    public function edit($id)
    {
        $cliente = $this->clienteService->obtenerExpediente($id);
        $estados = \App\Models\Estado::orderBy('nombre')->get();

        return view('admin.cliente.edit', compact('cliente', 'estados'));
    }

    public function update(Request $request, $id)
    {
        if ($request->has('rif_tipo') && $request->has('rif_numero')) {
            $request->merge([
                'rif' => strtoupper($request->rif_tipo) . '-' . $request->rif_numero
            ]);
        }

        $request->validate([
            'nombre'              => 'required|string|max:255',
            'rif'                 => 'required|string|max:15|unique:clientes,rif,' . $id,
            'email'               => 'required|email:rfc,dns|max:255',
            'contacto'            => 'required|string|regex:/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]+$/u|max:255',
            'telefono'            => 'nullable|numeric',
            'estado_id'           => 'nullable|exists:estados,id',
            'ciudad_id'           => 'nullable|exists:ciudades,id',
            'direccion'           => 'nullable|string',
            'direccion_operativa' => 'nullable|string',
        ], [
            'contacto.regex' => 'El campo Persona de Contacto solo debe contener letras.',
            'email.email'    => 'El correo electrónico debe ser una dirección válida con @.',
            'rif.unique'     => 'Este RIF ya se encuentra registrado en otro cliente.',
        ]);

        try {
            $this->clienteService->obtenerExpediente($id);

            app(\App\Repositories\ClienteRepository::class)->update($id, $request->only([
                'nombre', 'rif', 'email', 'contacto', 'telefono',
                'estado_id', 'ciudad_id', 'direccion', 'direccion_operativa',
            ]));

            Session::flash('success', 'Datos del cliente actualizados correctamente.');
            return Redirect::route('clientes.show', $id);
        } catch (\Exception $e) {
            Log::error('Error al actualizar cliente: ' . $e->getMessage());
            return Redirect::back()->withInput()->with('error', $e->getMessage());
        }
    }

    // -------------------------------------------------------
    // AVANCE DE PASOS DEL REGISTRO
    // -------------------------------------------------------

    public function avanzarPaso(Request $request, $id)
    {
        $request->validate([
            'paso' => 'required|integer|exists:registro_pasos,id',
        ]);

        try {
            $this->clienteService->avanzarPaso($id, (int) $request->paso);
            Session::flash('success', 'Etapa de registro actualizada correctamente.');
            return Redirect::back();
        } catch (\Exception $e) {
            Log::error('Error al avanzar paso: ' . $e->getMessage());
            return Redirect::back()->with('error', $e->getMessage());
        }
    }

    // -------------------------------------------------------
    // APROBACIÓN Y RECHAZO
    // -------------------------------------------------------

    public function aprobar(Request $request, $id)
    {
        $request->validate([
            'tipo_combustible_id' => 'required|exists:tipos_combustible,id',
            'litros_aprobados'    => 'required|numeric|min:1',
        ], [
            'litros_aprobados.min' => 'El cupo aprobado debe ser mayor a 0.',
        ]);

        try {
            $this->clienteService->aprobarCliente($id);
            $this->clienteService->ajustarCupo(
                $id,
                (int) $request->tipo_combustible_id,
                (float) $request->litros_aprobados
            );

            Session::flash('success', 'Cliente aprobado y cupo asignado correctamente.');
            return Redirect::back();
        } catch (\Exception $e) {
            Log::error('Error al aprobar cliente: ' . $e->getMessage());
            return Redirect::back()->with('error', $e->getMessage());
        }
    }

    public function rechazar($id)
    {
        try {
            $this->clienteService->rechazarCliente($id);
            Session::flash('success', 'Cliente marcado como rechazado.');
            return Redirect::back();
        } catch (\Exception $e) {
            Log::error('Error al rechazar cliente: ' . $e->getMessage());
            return Redirect::back()->with('error', $e->getMessage());
        }
    }

    // -------------------------------------------------------
    // INACTIVAR Y REACTIVAR
    // -------------------------------------------------------

    public function inactivar($id)
    {
        try {
            $this->clienteService->inactivarCliente($id);
            Session::flash('success', 'Cliente inactivado correctamente.');
            return Redirect::back();
        } catch (\Exception $e) {
            Log::error('Error al inactivar cliente: ' . $e->getMessage());
            return Redirect::back()->with('error', $e->getMessage());
        }
    }

    public function reactivar($id)
    {
        try {
            $this->clienteService->reactivarCliente($id);
            Session::flash('success', 'Cliente reactivado correctamente.');
            return Redirect::back();
        } catch (\Exception $e) {
            Log::error('Error al reactivar cliente: ' . $e->getMessage());
            return Redirect::back()->with('error', $e->getMessage());
        }
    }

    // -------------------------------------------------------
    // AJUSTE DE CUPO
    // -------------------------------------------------------

    public function ajustarCupo(Request $request, $id)
    {
        $request->validate([
            'tipo_combustible_id' => 'required|exists:tipos_combustible,id',
            'litros_aprobados'    => 'required|numeric|min:1',
        ]);

        try {
            $this->clienteService->ajustarCupo(
                $id,
                (int) $request->tipo_combustible_id,
                (float) $request->litros_aprobados
            );

            Session::flash('success', 'Cupo ajustado correctamente.');
            return Redirect::back();
        } catch (\Exception $e) {
            Log::error('Error al ajustar cupo: ' . $e->getMessage());
            return Redirect::back()->with('error', $e->getMessage());
        }
    }

    // -------------------------------------------------------
    // REGISTRO DE PLACAS Y CHOFERES (Solo Admin)
    // -------------------------------------------------------

    public function registrarPlaca(Request $request, $id)
    {
        $request->validate([
            'placa' => 'required|string|max:8',
        ]);

        try {
            $this->clienteService->registrarPlaca($id, $request->placa);
            Session::flash('success', 'Placa registrada correctamente.');
            return Redirect::back();
        } catch (\Exception $e) {
            Log::error('Error al registrar placa: ' . $e->getMessage());
            return Redirect::back()->with('error', $e->getMessage());
        }
    }

    public function inactivarPlaca($placaId)
    {
        try {
            $this->clienteService->inactivarPlaca($placaId);
            Session::flash('success', 'Placa inactivada correctamente.');
            return Redirect::back();
        } catch (\Exception $e) {
            return Redirect::back()->with('error', $e->getMessage());
        }
    }

    public function registrarChofer(Request $request, $id)
    {
        $request->validate([
            'nombre_completo' => 'required|string|max:255',
            'cedula'          => 'required|string|max:15',
        ]);

        try {
            $this->clienteService->registrarChofer($id, $request->nombre_completo, $request->cedula);
            Session::flash('success', 'Chofer registrado correctamente.');
            return Redirect::back();
        } catch (\Exception $e) {
            Log::error('Error al registrar chofer: ' . $e->getMessage());
            return Redirect::back()->with('error', $e->getMessage());
        }
    }

    public function inactivarChofer($choferId)
    {
        try {
            $this->clienteService->inactivarChofer($choferId);
            Session::flash('success', 'Chofer inactivado correctamente.');
            return Redirect::back();
        } catch (\Exception $e) {
            return Redirect::back()->with('error', $e->getMessage());
        }
    }

    // -------------------------------------------------------
    // CLIENTES LUBRICANTES
    // -------------------------------------------------------

    public function indexLubricantes()
    {
        $clientes = $this->lubricanteService->obtenerTodos();
        return view('admin.cliente.lubricantes.index', compact('clientes'));
    }

    public function storeLubricante(Request $request)
    {
        if ($request->has('rif_tipo') && $request->has('rif_numero')) {
            $request->merge([
                'rif' => strtoupper($request->rif_tipo) . '-' . $request->rif_numero
            ]);
        }

        $request->validate([
            'razon_social' => 'required|string|max:255',
            'rif'          => 'required|string|max:20|unique:clientes_lubricantes,rif',
            'email'        => 'required|email|max:255',
        ], [
            'razon_social.required' => 'La razón social es obligatoria.',
            'rif.required'          => 'El RIF es obligatorio.',
            'rif.unique'            => 'Ya existe un cliente lubricante registrado con este RIF.',
            'email.required'        => 'El correo electrónico es obligatorio.',
            'email.email'           => 'El correo electrónico debe ser válido.',
        ]);

        try {
            $this->lubricanteService->registrar($request->all());
            Session::flash('success', 'Cliente lubricante registrado correctamente.');
            return Redirect::back();
        } catch (\Exception $e) {
            Log::error('Error al registrar cliente lubricante: ' . $e->getMessage());
            return Redirect::back()->withInput()->with('error', $e->getMessage());
        }
    }

    public function destroyLubricante($id)
    {
        try {
            $this->lubricanteService->eliminar($id);
            Session::flash('success', 'Cliente lubricante eliminado correctamente.');
            return Redirect::back();
        } catch (\Exception $e) {
            return Redirect::back()->with('error', $e->getMessage());
        }
    }
}