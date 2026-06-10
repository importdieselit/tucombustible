<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Pago;
use App\Models\User;
use App\Models\Cliente;
use App\Models\Pedido;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Override;

class PagoController extends BaseController
{
    public function getAditionalData()
    {
        $usuarios = User::all();
        $clientes = Cliente::all();
        $pedidos = Pedido::all();

        return response()->json([
            'usuarios' => $usuarios,
            'clientes' => $clientes,
            'pedidos' => $pedidos,
        ]);
    }

    public function index()
    {
        $pagos = Pago::all();
        $clientes = Cliente::all();

        return view('pago.index',compact('pagos', 'clientes'));
    }

    public function filter(Request $request)
    {
        $query = Pago::query();

        if ($request->has('id_usuario')) {
            $query->where('id_usuario', $request->input('id_usuario'));
        }

        if ($request->has('id_cliente')) {
            $query->where('id_cliente', $request->input('id_cliente'));
        }

        if ($request->has('id_pedido')) {
            $query->where('id_pedido', $request->input('id_pedido'));
        }

        if ($request->has('fecha_pago')) {
            $query->whereDate('fecha_pago', $request->input('fecha_pago'));
        }

        $pagos = $query->get();

        return response()->json($pagos);
    }

    public function getDetailsForView($item)
    {
        $cliente = Cliente::find($item->id_cliente);
        $pedido = Pedido::find($item->id_pedido);

        return [
            'cliente' => $cliente,
            'pedido' => $pedido,
        ];
    }


    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'id_usuario' => 'required|exists:users,id',
            'id_cliente' => 'required|exists:clientes,id',
            'id_pedido' => 'nullable|exists:pedidos,id',
            'persona_contacto' => 'required|string|max:255',
            'telefono_contacto' => 'required|string|max:255',
            'litros' => 'required|numeric',
            'referencia' => 'required|string|max:255',
            'fecha_pago' => 'required|date',
            'fecha_solicitud' => 'required|date',
        ]);

        $cliente = Cliente::find($validatedData['id_cliente']);
         if(is_null($cliente->telefono)){
            $cliente->telefono = $validatedData['telefono_contacto'];
            $cliente->contacto = $validatedData['persona_contacto'];
            $cliente->save();
         }

        $pago = Pago::create($validatedData);
        $pago->save();

         

        $pedido = Pedido::find($validatedData['id_pedido']);
        if ($pedido) {
            // $pedido->estado = 'pendiente';
            // $pedido->save();
        }else{
           
            $pedido = Pedido::create([
                'cliente_id' => $validatedData['id_cliente'],
                'fecha_solicitud' => now(),
                'user_id' => $validatedData['id_usuario'],
                'cantidad_solicitada' => $validatedData['litros'],
                'estado' => 'pendiente',
                'direccion_despacho' => $cliente->direccion_operativa ?? $cliente->direccion
            ]);
            $pago->id_pedido = $pedido->id;
            $pago->save();
        }


        return response()->json($pago, 201);    
    }
    
}
