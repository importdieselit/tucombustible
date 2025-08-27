<?php

namespace App\Http\Controllers;

use App\Models\MovimientoCombustible;
use App\Models\Deposito;
use App\Models\Proveedor;
use App\Models\Cliente;
use App\Models\Vehiculo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * Controlador para gestionar los movimientos de combustible (recarga y despacho).
 */
class MovimientoCombustibleController extends Controller
{
    /**
     * Muestra el formulario para registrar una recarga de combustible.
     * @return \Illuminate\View\View
     */
    public function createRecarga()
    {
        // Se obtienen todos los depósitos y proveedores para los dropdowns del formulario.
        $depositos = Deposito::all();
        $proveedores = Proveedor::all();
        $hoy = now()->format('Y-m-d\TH:i'); // Obtiene la fecha actual en formato YYYY-MM-DD
        
        return view('combustible.recarga', compact('depositos', 'proveedores', 'hoy'));
    }

    /**
     * Almacena una nueva recarga de combustible en la base de datos.
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function storeRecarga(Request $request)
    {
        // 1. Validación de los datos
        $request->validate([
            'fecha' => 'required|date',
            'deposito_id' => 'required|exists:depositos,id',
            'proveedor_id' => 'required|exists:proveedores,id',
            'cantidad_litros' => 'required|numeric|min:1',
            'observaciones' => 'nullable|string'
        ]);

        try {
            // 2. Buscar el depósito para actualizar el nivel
            $deposito = Deposito::findOrFail($request->deposito_id);
            
            // Verificación para no exceder la capacidad
            $nuevo_nivel = $deposito->nivel_actual_litros + $request->cantidad_litros;
            if ($nuevo_nivel > $deposito->capacidad_litros) {
                Session::flash('error', 'La cantidad de recarga excede la capacidad del depósito. Nivel actual: ' . $deposito->nivel_actual_litros . ' L. Capacidad: ' . $deposito->capacidad_litros . ' L.');
                return Redirect::back()->withInput();
            }

            // 3. Crear el registro del movimiento
            $movimiento = new MovimientoCombustible();
            $movimiento->created_at = $request->fecha; // Asignar la fecha del formulario
            $movimiento->tipo_movimiento = 'entrada';
            $movimiento->deposito_id = $request->deposito_id;
            $movimiento->proveedor_id = $request->proveedor_id;
            $movimiento->cantidad_litros = $request->cantidad_litros;
            $movimiento->observaciones = $request->observaciones;
            $movimiento->save();
            
            // 4. Actualizar el nivel actual del depósito
            $deposito->nivel_actual_litros = $nuevo_nivel;
            $deposito->save();

            Session::flash('success', 'Recarga de combustible registrada exitosamente.');
            return Redirect::route('combustible.recarga');

        } catch (ModelNotFoundException $e) {
            Session::flash('error', 'Depósito o proveedor no encontrado.');
            Log::error('Error al registrar recarga: ' . $e->getMessage());
            return Redirect::back()->withInput();
        } catch (\Exception $e) {
            Session::flash('error', 'Hubo un error al procesar la recarga.');
            Log::error('Error al registrar recarga: ' . $e->getMessage());
            return Redirect::back()->withInput();
        }
    }
    
    /**
     * Muestra el formulario para registrar un despacho de combustible.
     * @return \Illuminate\View\View
     */
    public function createDespacho()
    {
        // Se obtienen todos los depósitos, clientes y vehículos para los dropdowns.
        $depositos = Deposito::all();
        $clientes = Cliente::all();
        $vehiculos = Vehiculo::all();
         // Obtener los vehículos tipo cisterna (asumiendo que tipo = 2)
        $cisternas = Vehiculo::where('tipo', 2)->get();
        $hoy = now()->format('Y-m-d\TH:i'); // Obtiene la fecha y hora actual en formato YYYY-MM-DD HH:MM:SS
        
        return view('combustible.despacho', compact('depositos', 'clientes', 'vehiculos','cisternas', 'hoy'));
    }

    /**
     * Almacena un nuevo despacho de combustible en la base de datos.
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function storeDespacho(Request $request)
    {
        $request->validate([
            'fecha' => 'required|date',
            'deposito_id' => 'required|exists:depositos,id',
            'cliente_id' => 'nullable|exists:clientes,id', // Opcional si el despacho es a un vehículo interno
            'vehiculo_id' => 'nullable|exists:vehiculos,id',
            'cantidad_litros' => 'required|numeric|min:1',
            'observaciones' => 'nullable|string'
        ]);

        try {
            $deposito = Deposito::findOrFail($request->deposito_id);

            // Verificación de que hay suficiente combustible
            if ($deposito->nivel_actual_litros < $request->cantidad_litros) {
                Session::flash('error', 'No hay suficiente combustible en el depósito. Nivel actual: ' . $deposito->nivel_actual_litros . ' L.');
                return Redirect::back()->withInput();
            }

            // Crear el registro del movimiento
            $movimiento = new MovimientoCombustible();
            $movimiento->created_at = $request->fecha;
            $movimiento->tipo_movimiento = 'salida';
            $movimiento->deposito_id = $request->deposito_id;
            $movimiento->cliente_id = $request->cliente_id;
            $movimiento->vehiculo_id = $request->vehiculo_id;
            $movimiento->cantidad_litros = $request->cantidad_litros;
            $movimiento->observaciones = $request->observaciones;
            $movimiento->save();

            // Actualizar el nivel actual del depósito
            $deposito->nivel_actual_litros -= $request->cantidad_litros;
            $deposito->save();

            Session::flash('success', 'Despacho de combustible registrado exitosamente.');
            return Redirect::route('combustible.despacho');

        } catch (ModelNotFoundException $e) {
            Session::flash('error', 'Depósito, cliente o vehículo no encontrado.');
            Log::error('Error al registrar despacho: ' . $e->getMessage());
            return Redirect::back()->withInput();
        } catch (\Exception $e) {
            Session::flash('error', 'Hubo un error al procesar el despacho.');
            Log::error('Error al registrar despacho: ' . $e->getMessage());
            return Redirect::back()->withInput();
        }
    }
     public function list()
    {
        // Obtiene todos los movimientos, ordenados por fecha de creación (más recientes primero)
        // y con las relaciones de depósito, cliente, etc. precargadas.
        $movimientos = MovimientoCombustible::with(['deposito', 'cliente', 'proveedor', 'cisterna', 'vehiculo'])
                                            ->orderBy('created_at', 'desc')
                                            ->get();

        return view('combustible.list', ['movimientos' => $movimientos]);
    }
}
