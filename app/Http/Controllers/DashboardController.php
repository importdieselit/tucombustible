<?php

namespace App\Http\Controllers;

use App\Models\Vehiculo;
use App\Models\User;
use App\Models\Orden;
use App\Models\Tanque;
use App\Models\Cliente;
use App\Models\Pedido;
use App\Models\MovimientoCombustible;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
class DashboardController extends Controller
{
    public function index()
    {

        $userId=Auth::id();
        $user=User::find($userId);
        // Redirigir a la vista de cliente si el perfil es 3
            if ($user->id_perfil == 3) {
                    // 1. Indicadores de clientes
                $clientesPadre = Cliente::where('parent', 0)
                                        ->select('nombre', 'disponible', 'cupo')
                                        ->get();
                
                // 2. Gráficas de disponibilidad de clientes.
                $disponibilidadData = $clientesPadre->map(function ($cliente) {
                    return [
                        'nombre' => $cliente->nombre,
                        'disponible' => $cliente->disponible,
                        'cupo' => $cliente->cupo,
                    ];
                });

                // 3. Indicadores de pedidos pendientes y en proceso.
                $pedidosPendientes = Pedido::where('estatus', 'Pendiente')->count();
                $pedidosAprobados = Pedido::where('estatus', 'Aprobado')->count();

                // 4. Indicadores de volumen de combustible despachado
                $movimientosHoy = MovimientoCombustible::whereDate('created_at', now())->sum('cantidad_litros');
                $movimientosMes = MovimientoCombustible::whereMonth('created_at', now()->month)->sum('cantidad_litros');


                return redirect()->route('clientes.dashboard');
            }

         
        $totalVehiculos = Vehiculo::count();
        $totalUsuarios = User::count();
        $totalOrdenesAbiertas = Orden::where('estatus', 'Abierta')->count(); // Asumiendo que 'estatus' tiene este valor
        $totalTanques = Tanque::count();

        // Puedes añadir más información, como las últimas 5 órdenes
        $ultimasOrdenes = Orden::orderBy('id', 'desc')->take(5)->get();

        return view('dashboard', compact(
            'totalVehiculos',
            'totalUsuarios',
            'totalOrdenesAbiertas',
            'totalTanques',
            'ultimasOrdenes'
        ));
    }
}