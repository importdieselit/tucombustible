<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Http\Requests\ClienteRequest; // Importamos el FormRequest
use Illuminate\Http\Request; // Usamos el Request estándar para compatibilidad con BaseController
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\DB;
use App\Models\Pedido;
use App\Models\Deposito;
use App\Models\Vehiculo;

/**
 * Controlador para gestionar los clientes.
 */
class ClienteController extends BaseController
{
   

    /**
     * Almacena un nuevo cliente en la base de datos.
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\RedirectResponse
     */

    public function index()
    {

        $user = auth()->user();
        dd($user);
        $cliente = Cliente::find($user->id_cliente);
        dd($cliente);
        // 1. Indicadores de clientes
        // Obtenemos todos los clientes con parent 0.
        $sucursales = [];
        $clientesPadre= null;
        $disponibilidadData = [];
        if($user->id_perfil==3) {
            if($cliente->parent==0) {
                $sucursales = Cliente::where('parent', $user->id_cliente)
                                ->select('nombre', 'disponible', 'cupo')
                                ->get();
            } 
       }
       if($user->id_perfil<3) {
            $clientesPadre = Cliente::where('parent', 0)
                                ->select('nombre', 'disponible', 'cupo')
                                ->get();
       }
        
        // 2. Gráficas de disponibilidad de clientes.
        // Los datos para la gráfica los podemos pasar directamente del controlador a la vista.
        if($user->id_perfil<3) {
            $disponibilidadData = $clientesPadre->map(function ($cliente) {
                return [
                    'nombre' => $cliente->nombre,
                    'disponible' => $cliente->disponible,
                    'cupo' => $cliente->cupo,
                ];
            });
        } elseif($user->id_perfil==3 && $cliente->parent==0) {  
            $disponibilidadData = $sucursales->map(function ($sucursal) {
                return [
                    'nombre' => $sucursal->nombre,
                    'disponible' => $sucursal->disponible,
                    'cupo' => $sucursal->cupo,
                ];
            });
        }

        // 3. Indicadores de pedidos pendientes y en proceso.
        $pedidosPendientes = Pedido::where('estado', 'pendiente');
        
        
        if($user->id_perfil==3) {
            if($cliente->parent==0) {
                $sucursalesIds = Cliente::where('parent', $user->id_cliente)->pluck('id')->toArray();
                $pedidosPendientes = $pedidosPendientes->whereIn('id_cliente', $sucursalesIds);
            } else {
                $pedidosPendientes = $pedidosPendientes->where('id_cliente', $user->id_cliente);
            }
        }
        $pedidosPendientes = $pedidosPendientes->count();
        $pedidosEnProceso = Pedido::where('estado', 'en_proceso');
        if($user->id_perfil==3) {
            if($cliente->parent==0) {
                $sucursalesIds = Cliente::where('parent', $user->id_cliente)->pluck('id')->toArray();
                $pedidosEnProceso = $pedidosEnProceso->whereIn('id_cliente', $sucursalesIds);
            } else {
                $pedidosEnProceso = $pedidosEnProceso->where('id_cliente', $user->id_cliente);
            }
        }
        $pedidosEnProceso = $pedidosEnProceso->count();

        // 4. Niveles de los depósitos.
        $depositos = [];
        if($user->id_perfil<3) {    
            $depositos = Deposito::all();
        }

        // 5. Camiones cargados.
        // Asumimos que tienes un campo 'estado' en la tabla de vehículos o una relación
        // que te permite saber si un camión está cargado.
        // Por ejemplo, un estado 'cargado' o 'en_ruta_con_combustible'.
        if($user->id_perfil==3) {
            if($cliente->parent==0) {
                $sucursalesIds = Cliente::where('parent', $user->id_cliente)->pluck('id')->toArray();
                $camionesCargados = Vehiculo::whereIn('id_cliente', $sucursalesIds)
                                    ->count();
            } else {
                $camionesCargados = Vehiculo::where('id_cliente', $user->id_cliente)
                                    ->count();
            }
        } else {
            $camionesCargados = Vehiculo::where('estado', 'cargado')->count();
        }
    
        if($user->id_perfil==2) {
            return view('combustible.dashboard', compact(
                'clientesPadre', 
                'disponibilidadData',
                'pedidosPendientes', 
                'pedidosEnProceso', 
                'depositos', 
                'camionesCargados'
            ));
        } elseif($user->id_perfil==3) {

            return view('clientes.index', compact(
                'clientesPadre', 
                'disponibilidadData',
                'pedidosPendientes', 
                'pedidosEnProceso', 
                'sucursales'
            ));
        }
        // Pasamos todos los datos a la vista.
        return view('combustible.dashboard', compact(
            'clientesPadre', 
            'disponibilidadData',
            'pedidosPendientes', 
            'pedidosEnProceso', 
            'depositos', 
            'camionesCargados'
        ));
    }

    public function store(Request $request)
    {
        // Validamos los datos manualmente usando las reglas de ClienteRequest
        $rules = (new ClienteRequest())->rules();
        $validatedData = $request->validate($rules);

        try {
            Cliente::create($validatedData);
            Session::flash('success', 'Cliente creado exitosamente.');
            return Redirect::route('clientes.list');
        } catch (\Exception $e) {
            Log::error('Error al crear cliente: ' . $e->getMessage());
            Session::flash('error', 'Hubo un error al crear el cliente.');
            return Redirect::back()->withInput();
        }
    }

    /**
     * Actualiza un cliente existente en la base de datos.
     * @param \Illuminate\Http\Request $request
     * @param int $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, $id)
    {
        // Validamos los datos manualmente usando las reglas de ClienteRequest
        $rules = (new ClienteRequest())->rules($id); // Pasamos el ID para la validación de unicidad
        $validatedData = $request->validate($rules);

        try {
            $cliente = Cliente::findOrFail($id);
            $cliente->update($validatedData);
            Session::flash('success', 'Cliente actualizado exitosamente.');
            return Redirect::route('clientes.list');
        } catch (ModelNotFoundException $e) {
            Session::flash('error', 'Cliente no encontrado.');
            return Redirect::back();
        } catch (\Exception $e) {
            Log::error('Error al actualizar cliente: ' . $e->getMessage());
            Session::flash('error', 'Hubo un error al actualizar el cliente.');
            return Redirect::back()->withInput();
        }
    }

    /**
     * Elimina un cliente de la base de datos.
     * @param int $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy($id)
    {
        try {
            $cliente = Cliente::findOrFail($id);
            $cliente->delete();
            Session::flash('success', 'Cliente eliminado exitosamente.');
            return Redirect::route('clientes.index');
        } catch (ModelNotFoundException $e) {
            Session::flash('error', 'Cliente no encontrado.');
            return Redirect::back();
        } catch (\Exception $e) {
            Log::error('Error al eliminar cliente: ' . $e->getMessage());
            Session::flash('error', 'Hubo un error al eliminar el cliente.');
            return Redirect::back();
        }

    }

    public function import()
    {
        return view('cliente.import');
    }

    /**
     * Procesa el archivo subido e importa los clientes.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function handleImport(Request $request)
    {
        // 1. Validar que se haya subido un archivo.
        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv',
        ]);

        try {
            DB::beginTransaction();

            // 2. Obtener la primera hoja del archivo Excel como una colección.
            // Esto lee todo el archivo de una vez.
            $coleccion = Excel::toCollection(null, $request->file('file'))->first();
            if ($coleccion->isEmpty()) {
                throw new \Exception("El archivo está vacío o la hoja de datos no se encontró.");
            }

            // 3. Omitir la primera fila (encabezados) para empezar con los datos.
            $filas = $coleccion->skip(1);
            
            // Un array para rastrear los clientes 'padre' ya creados en esta importación.
            $ciiuParents = [];

            // 4. Recorrer cada fila de la colección y aplicar la lógica.
            foreach ($filas as $fila) {
                // Si la fila está vacía, la saltamos.
                if ($fila->filter()->isEmpty()) {
                    continue;
                }

                // Limpiamos y mapeamos los datos de la fila según tu lógica y la estructura del Excel.
                // Usamos índices numéricos ya que toCollection no usa los encabezados de columna.
                $ciiu = (int) ($fila[13] ?? 0);
                $rif = (string) ($fila[11] ?? '');
                $nombre = (string) ($fila[1] ?? '');
                $cupo = (float) ($fila[2] ?? 0.0);
                $sector = (string) ($fila[7] ?? '');
                $disponible = (float) ($fila[6] ?? 0.0);

                // Validación básica de tu código.
                if (empty($ciiu) || empty($rif)) {
                    Log::warning("Fila omitida por datos de CIIU o RIF faltantes.", ['fila' => $fila]);
                    continue;
                }
                
                // Lógica para determinar el periodo.
                $periodo = 'M';
                if (isset($fila[3]) && strtoupper($fila[3]) === 'SI') {
                    $periodo = 'D';
                } elseif (isset($fila[4]) && strtoupper($fila[4]) === 'SI') {
                    $periodo = 'S';
                }
                // Si la fila 5 (mensual) es 'SI', el periodo ya es 'M', no hace falta un elseif.

                // Lógica para encontrar o asignar el padre.
                $parent = 0;
                    // Si no, buscamos en la base de datos un cliente con parent=0.
                    $existingParent = Cliente::where('ciiu', $ciiu)->where('parent', 0)->first();
                    if ($existingParent) {
                        if (strtoupper($nombre) == strtoupper($existingParent->nombre)) {
                            Log::warning("Fila omitida: coincide con el nombre del cliente padre.", ['fila' => $fila]);
                            continue;
                        }       // Si ya existe, lo usamos como el padre.
                        $parent = $existingParent->id;
                    }
            
                

                $clientePadre = Cliente::create([
                        'nombre' => $nombre,
                        'contacto' => $fila[9] ?? null,
                        'dni' => $fila[10] ?? null,
                        'rif' => $rif,
                        'direccion' => $fila[12] ?? null,
                        'disponible' => $disponible,
                        'cupo' => $cupo,
                        'ciiu' => $ciiu,
                        'parent' => $parent,
                        'periodo' => $periodo,
                        'sector' => $sector,
                    ]);
                
            }

            DB::commit();

            Session::flash('success', '¡Clientes importados exitosamente!');
        } catch (\Exception $e) {
            DB::rollBack();
            Session::flash('error', 'Hubo un error al importar los clientes: ' . $e->getMessage());
        }

        return Redirect::back();
    }
}
