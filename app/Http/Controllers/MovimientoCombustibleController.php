<?php

namespace App\Http\Controllers;

use App\Models\MovimientoCombustible;
use App\Models\Deposito;
use App\Models\Proveedor;
use App\Models\Cliente;
use App\Models\Vehiculo;
use App\Models\Pedido;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;  
use App\Traits\GenerateAlerts;
use Illuminate\Support\Facades\Auth;

/**
 * Controlador para gestionar los movimientos de combustible (recarga y despacho).
 */
class MovimientoCombustibleController extends Controller
{
    use GenerateAlerts;
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
        $userId = auth()->id(); // Obtener el ID del usuario autenticado
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
        $userId = auth()->id(); // Obtener el ID del usuario autenticado

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
// Generar alertas si es necesario
            if ($deposito->nivel_actual_litros / $deposito->capacidad_litros < 0.1) {
                $this->createAlert([
                    'id_usuario' => $userId, // ID del usuario responsable de la orden.
                    'id_rel' => $deposito->id, // ID de la item.
                    'observacion' => 'El nivel del depósito "' . $deposito->nombre . '" es crítico: ' . $deposito->nivel_actual_litros . ' L restantes.',
                    'accion' => route('deposito.show', $deposito->id) , // Ruta para ver la orden.
                    'dias' => 0,
                ]);
               
            } elseif ($deposito->nivel_actual_litros / $deposito->capacidad_litros < 0.25) {
               $this->createAlert([
                    'id_usuario' => $userId, // ID del usuario responsable de la orden.
                    'id_rel' => $deposito->id, // ID de la item.
                    'observacion' => 'El nivel del depósito "' . $deposito->nombre . '" es bajo: ' . $deposito->nivel_actual_litros . ' L restantes.',
                    'accion' => route('deposito.show', $deposito->id) , // Ruta para ver la orden.
                    'dias' => 0,
                ]);
                
            }   

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

     /**
     * Muestra el panel de pedidos pendientes para aprobación y rechazo.
     *
     * @return \Illuminate\View\View
     */
    public function pedidos()
    {
        $pedidos = Pedido::with(['cliente'])
            ->whereIn('estado', ['pendiente'])
            ->orderBy('fecha_solicitud', 'desc')    
            ->get();

        return view('combustible.pedidos', compact('pedidos'));
    }

    /**
     * Procesa la aprobación de un pedido.
     *
     * @param int $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function aprobar(Request $request, $id)
    {
        try {
            $pedido = Pedido::findOrFail($id);
            $pedido->estado = 'aprobado';
            $pedido->cantidad_aprobada = $request->input('cantidad_aprobada');
            $pedido->observaciones_admin = $request->input('observaciones_admin', $pedido->observaciones_admin);
            $pedido->fecha_aprobacion = Carbon::now();
            $pedido->save();

            Session::flash('success', 'Pedido de combustible aprobado exitosamente.');
        } catch (\Exception $e) {
            Session::flash('error', 'Error al aprobar el pedido: ' . $e->getMessage());
        }

        return redirect()->route('combustible.pedidos');
    }

    /**
     * Procesa el rechazo de un pedido.
     *
     * @param int $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function rechazar(Request $request, $id)
    {
        try {
            $pedido = Pedido::findOrFail($id);
            $pedido->estado = 'rechazado';
            $pedido->observaciones_admin = $request->input('observaciones_admin', 'Rechazado por el administrador.');
            $pedido->save();

            Session::flash('success', 'Pedido de combustible rechazado exitosamente.');
        } catch (\Exception $e) {
            Session::flash('error', 'Error al rechazar el pedido: ' . $e->getMessage());
        }

        return redirect()->route('combustible.pedidos');
    }

    /**
     * Muestra el panel de pedidos aprobados listos para despacho.
     *
     * @return \Illuminate\View\View
     */
    public function despachos()
    {
        // Recuperamos los pedidos con estado 'aprobado'
        $pedidos = Pedido::with('cliente')
            ->where('estado', 'aprobado')
            ->orderBy('fecha_aprobacion', 'desc')
            ->get();
        
        // Asumimos que existen los modelos Vehiculo y Deposito
           $vehiculos = Vehiculo::where('estatus', 1)
            ->where('permiso_intt', '!=', 'S/P')
            ->whereNotNull('permiso_intt')
            ->get();
        $depositos = Deposito::all();

        return view('combustible.aprobados', compact('pedidos', 'vehiculos', 'depositos'));
    }

    /**
     * Procesa el despacho de un pedido, actualizando el saldo del cliente.
     *
     * @param int $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function despachar(Request $request, $id)
    {
        // 1. Validar los datos de la solicitud
        $request->validate([
            'vehiculo_id' => 'required|exists:vehiculos,id',
            'deposito_id' => 'required|exists:depositos,id',
        ]);
        
        $userId = Auth::id(); // Usar el Facade Auth para mayor claridad

        try {
            DB::beginTransaction();

            // 2. Obtener todos los modelos necesarios
            $pedido = Pedido::with('cliente')->findOrFail($id);
            $cliente = $pedido->cliente;
            $cantidadDespachar = $pedido->cantidad_aprobada; 
            $vehiculo = Vehiculo::findOrFail($request->input('vehiculo_id'));
            $deposito = Deposito::findOrFail($request->input('deposito_id'));
            
            // 3. Realizar las verificaciones de negocio
            if ($cliente->disponible < $cantidadDespachar) {
                Session::flash('error', 'El cliente no tiene suficiente combustible disponible para este despacho.');
                DB::rollBack();
                return redirect()->back();
            }

            if ($deposito->nivel_actual_litros < $cantidadDespachar) {
                Session::flash('error', 'No hay suficiente combustible en el depósito para completar este despacho.');
                DB::rollBack();
                return redirect()->back();
            }

            // 4. Actualizar los modelos en memoria
            // Actualizar el estado del pedido a 'en_proceso'
            $pedido->estado = 'en_proceso';
            $pedido->vehiculo_id = $vehiculo->id;
            $pedido->deposito_id = $deposito->id;
            $pedido->fecha_completado = Carbon::now();

            // Actualizar el estatus del vehículo
            $vehiculo->estatus = 2;

            // Actualizar el saldo del cliente
            $cliente->disponible -= $cantidadDespachar;

            // Actualizar el nivel actual del depósito
            $deposito->nivel_actual_litros -= $cantidadDespachar;

            // 5. Crear el registro del movimiento de despacho en memoria
            $movimiento = new MovimientoCombustible();
            $movimiento->created_at = Carbon::now();
            $movimiento->tipo_movimiento = 'salida';
            $movimiento->deposito_id = $deposito->id; // Corregido: usar $deposito->id
            $movimiento->cliente_id = $pedido->cliente_id;
            $movimiento->cisterna_id = $vehiculo->id;
            $movimiento->cantidad_litros = $cantidadDespachar;
            $movimiento->observaciones = 'Despacho de pedido ID: ' . $pedido->id;

            // 6. Guardar todos los modelos y el movimiento de manera atómica
            $pedido->save();
            $vehiculo->save();
            $cliente->save();
            $deposito->save();
            $movimiento->save();

            // 7. Generar alertas si es necesario
            if ($deposito->nivel_actual_litros / $deposito->capacidad_litros < 0.1) {
                $this->createAlert([
                    'id_usuario' => $userId,
                    'id_rel' => $deposito->id,
                    'observacion' => 'El nivel del depósito "' . $deposito->nombre . '" es crítico: ' . $deposito->nivel_actual_litros . ' L restantes.',
                    'accion' => route('deposito.show', $deposito->id),
                    'dias' => 0,
                ]);
            } elseif ($deposito->nivel_actual_litros / $deposito->capacidad_litros < 0.25) {
                $this->createAlert([
                    'id_usuario' => $userId,
                    'id_rel' => $deposito->id,
                    'observacion' => 'El nivel del depósito "' . $deposito->nombre . '" es bajo: ' . $deposito->nivel_actual_litros . ' L restantes.',
                    'accion' => route('deposito.show', $deposito->id),
                    'dias' => 0,
                ]);
            }

            DB::commit();
            Session::flash('success', 'Despacho realizado y saldo del cliente actualizado exitosamente.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error al despachar el combustible: " . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            Session::flash('error', 'Error al despachar el combustible. Por favor, revisa los logs de la aplicación.');
        }

        return redirect()->route('combustible.aprobados');
    }
}
