<?php

namespace App\Http\Controllers;

use App\Models\Vehiculo;
use App\Models\User;
use App\Models\Orden;
use App\Models\Tanque;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
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