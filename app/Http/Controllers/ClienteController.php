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

    public function dashboard()
    {
        $user = auth()->user();

        if ($user->status_usuario === 'prospecto') {
            return redirect()->route('captacion.completar');
        }

        $cliente = Cliente::find($user->cliente_id);
    
        // 0. Inicialización de variables para evitar errores en compact
        $sucursalesIds = [];
        $idsAsociados = [$user->cliente_id];
        $sucursales = [];
        $clientesPadre = null;
        $disponibilidadData = [];
        $pedidosDashboard = [];
        $depositos = [];

        // 1. Identificar jerarquía (Si es Padre, incluir Sucursales)
        if ($cliente && $cliente->parent == 0) {
            $sucursalesIds = Cliente::where('parent', $user->cliente_id)->pluck('id')->toArray();
            $idsAsociados = array_merge([$user->cliente_id], $sucursalesIds);
        }

        // 2. Lógica de Clientes y Disponibilidad (Gráficas)
        if ($user->id_perfil < 3) {
            // Administradores/Logística
            $clientesPadre = Cliente::where('parent', 0)
                ->select('nombre', 'disponible', 'cupo', 'direccion', 'id')
                ->with('sucursales:id,nombre,direccion,disponible,cupo,parent')
                ->get();

            $disponibilidadData = $clientesPadre->map(function ($c) {
                return [
                    'nombre' => $c->nombre,
                    'disponible' => $c->disponible,
                    'direccion' => $c->direccion,
                    'cupo' => $c->cupo,
                    'sucursales' => $c->sucursales->map(fn($s) => [
                        'id' => $s->id,
                        'nombre' => $s->nombre,
                        'direccion' => $s->direccion,
                        'disponible' => $s->disponible,
                        'cupo' => $s->cupo,
                    ]),
                ];
            });
            $depositos = Deposito::all();
        } else {
            // Perfil Cliente (Perfil 3)
            if ($cliente->parent == 0) {
                $sucursales = Cliente::whereIn('id', $sucursalesIds)
                    ->select('nombre', 'disponible', 'cupo', 'direccion', 'id')
                    ->get();
            
                $disponibilidadData = $sucursales->map(fn($s) => [
                    'id' => $s->id,
                    'nombre' => $s->nombre,
                    'disponible' => $s->disponible,
                    'direccion' => $s->direccion,
                    'cupo' => $s->cupo,
                ]);
            }
        }

        // 3. Indicadores de Pedidos (Conteo de Status correcto)
        $queryBase = Pedido::query();
        if ($user->id_perfil == 3) {
            $queryBase->whereIn('cliente_id', $idsAsociados);
        }

        // Conteos independientes
        $pedidosPendientes = (clone $queryBase)->where('estado', 'pendiente')->count();
        $pedidosEnProceso  = (clone $queryBase)->where('estado', 'en_proceso')->count();

        // 4. Listado del Dashboard (Pedidos activos para la tabla)
        $pedidosCollection = (clone $queryBase)
            ->whereNotIn('estado', ['entregado', 'cancelado'])
            ->with('cliente')
            ->get();

        $pedidosDashboard = $pedidosCollection->map(function ($pedido) {
            return [
                'id' => $pedido->id,
                'cantidad' => number_format($pedido->cantidad_solicitada, 2, ',', '.') . ' L',
                'cliente' => $pedido->cliente->nombre ?? 'N/A',
                'estado' => $pedido->estado,
                'observacion' => $pedido->observaciones,
                'fecha' => $pedido->fecha_solicitud ? $pedido->fecha_solicitud->format('d/m/Y H:i') : 'N/A',
                'tipo' => 'pedido',
            ];
        })->toArray();

        // 5. Camiones cargados
        $queryVehiculos = Vehiculo::query();
        if ($user->id_perfil == 3) {
            $queryVehiculos->whereIn('id_cliente', $idsAsociados);
        } else {
            $queryVehiculos->where('estado', 'cargado');
        }
        $camionesCargados = $queryVehiculos->count();

        // 6. Retorno de vistas según perfil
        $data = compact(
            'clientesPadre', 
            'disponibilidadData',
            'pedidosPendientes', 
            'pedidosEnProceso', 
            'depositos', 
            'camionesCargados',
            'pedidosDashboard',
            'sucursales',
            'cliente'
        );

        if ($user->id_perfil == 3) {
            return view('cliente.index', $data);
        }

        // Default para Admin/Logística (Perfil 1 y 2)
        return view('combustible.dashboard', $data);
        }

    public function store(Request $request)
    {
        // Valida los datos con las reglas del ClienteRequest
        // Esto detendrá cualquier intento de crear un cliente con campos NULL obligatorios
        $rules = (new ClienteRequest())->rules();
        $validatedData = $request->validate($rules);

        try {
            // Lógica de la Matriz Concatenada para el RIF
            // Unimos el tipo (J, G, V, E) con el número (ej: J-123456789)
            $rif_concatenado = $request->rif_tipo . '-' . $request->rif_num;
            $validatedData['rif'] = $rif_concatenado;
            Cliente::create($validatedData);

            Session::flash('success', '¡Cliente creado exitosamente!');
        
            return Redirect::route('clientes.index');

        } catch (\Exception $e) {

            Log::error('Error al crear cliente: ' . $e->getMessage(), [
                'input' => $request->except(['_token']),
                'line' => $e->getLine()
            ]);

            Session::flash('error', 'No se pudo crear el cliente. Por favor, verifique los datos e intente nuevamente.');
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
        // Validamos los datos con las reglas del Request (el ID ayuda a validarlas)
        $rules = (new ClienteRequest())->rules($id); // Pasamos el ID para la validación de unicidad
        $validatedData = $request->validate($rules);

        try {
            $cliente = Cliente::findOrFail($id);
            $validateData['rif'] = $request->rif_tipo . '-' . $request->rif_num;
            $cliente->update($validatedData);

            Session::flash('success', 'Cliente actualizado exitosamente.');
            return Redirect::route('clientes.index');

        } catch (ModelNotFoundException $e) {
            Session::flash('error', 'Cliente no encontrado.');
            return Redirect::back();

        } catch (\Exception $e) {
            Log::error('Error al actualizar cliente: ' . $e->getMessage());
            Session::flash('error', 'Hubo un error al actualizar el cliente.');
            return Redirect::back()->withInput();
        }
    }

    public function edit ($id)
    {
        $cliente = Cliente::findOrFail($id);
        return view('cliente.edit', compact('cliente'));
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

    public function storeAlVuelo(Request $request)
    {
        $request->validate(['nombre' => 'required|string|max:255']);

        $cliente = Cliente::create([
            'nombre' => $request->nombre,
            'rif' => 'PENDIENTE', // Marcadores para datos faltantes
            'direccion' => 'PENDIENTE',
            'disponible' => 0,
            'cupo' => 0,
            'ciiu' => 0,
            'parent' => 0,
            'periodo' => 'M'
        ]);

        return response()->json(['message' => 'Cliente registrado', 'cliente' => $cliente], 201);
    }
}
