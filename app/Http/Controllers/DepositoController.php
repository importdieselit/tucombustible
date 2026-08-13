<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Sedes;
use App\Models\TipoCombustible;
use App\Models\Deposito;
use App\Services\DepositoService;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\Rule;
use App\Models\MovimientoCombustible;
use App\Models\Parametro;
use Illuminate\Support\Facades\Log;
use App\Models\Aforo;

class DepositoController extends Controller
{
    protected $depositoService;

    // Constructor limpio, inyectando únicamente nuestro nuevo servicio
    public function __construct(DepositoService $depositoService)
    {
        $this->depositoService = $depositoService;
    }

    public function create()
    {
        // Consultamos los maestros necesarios para los selectores del formulario
        $sedes = Sedes::all(); 
        $tiposCombustible = TipoCombustible::all();

        // Retornamos la vista ubicada en la nueva carpeta con "s"
        return view('combustibles.depositos.create', compact('sedes', 'tiposCombustible'));
    }

    public function index(Request $request)
    {
        $sedes = Sedes::orderBy('nombre')->get();
        $depositos = Deposito::with(['sedes', 'tipoCombustible','ultimaMedicion.tipoCombustible'])
                ->when($request->id_sede, function ($query, $id_sede) {
                return $query->where('id_sede', $id_sede);
            })
            ->paginate(20);

        return view('combustibles.depositos.index', compact('depositos', 'sedes'));
    }

    public function edit( int $id)
    {
        $deposito = Deposito::findOrFail($id);
        $sedes = Sedes::all();
        $tiposCombustible = TipoCombustible::all();

        return view('combustibles.depositos.edit', compact('deposito', 'sedes', 'tiposCombustible'));
    }

    /**
     * Procesa el formulario de creación de un tanque geométrico.
     */
    public function store(Request $request)
    {
        // Mensajes personalizados en español para que no salga "validation.unique"
        $messages = [
            'serial.required' => 'El campo Serial / Nombre es obligatorio.',
            'serial.unique'   => 'Este Nombre ya se encuentra registrado con otro tanque del sistema.',
            'id_sede.required' => 'Debe seleccionar una sede válida.',
            'tipo_combustible_id.required' => 'Debe seleccionar un tipo de combustible.',
            'capacidad_maxima.required' => 'La capacidad máxima es obligatoria.',
            
            // Mensajes dinámicos para la geometría física
            'diametro.required_if' => 'El diámetro (cm) es obligatorio para la forma geométrica seleccionada.',
            'longitud.required_if' => 'El largo (cm) es obligatoria para la forma geométrica seleccionada.',
            'ancho.required_if'    => 'El ancho (cm) es obligatorio para la forma geométrica seleccionada.',
            'alto.required_if'     => 'La altura (cm) es obligatorio para la forma geométrica seleccionada.',
        ];

        // Validación nativa en el controlador con las reglas exigidas
        $validator = Validator::make($request->all(), [
            'serial' => [
                'required', 'string', 'max:255',
                Rule::unique('depositos', 'serial')->where(function ($query) use ($request) {
                    // No se pueden registrar dos tanques con el mismo nombre/serial en la misma sede, pero sí en sedes diferentes
                    return $query->where('id_sede', $request->id_sede);
                })
            ],
            'id_sede' => 'required|exists:sedes,id', 
            'tipo_combustible_id' => 'required|exists:tipos_combustible,id',
            'capacidad_maxima' => 'required|numeric|min:0',
            'forma' => 'required|in:CH,CV,OH,OV,R,C,E',

            // Reglas de validación condicionales expandidas para el ecosistema completo
            'diametro' => 'required_if:forma,CH,CV,OH,C,E|nullable|numeric|min:0',
            'longitud' => 'required_if:forma,CH,R,OH,OV|nullable|numeric|min:0',
            'ancho'    => 'required_if:forma,R,OH,OV|nullable|numeric|min:0',
            'alto'     => 'required_if:forma,R,CV,OV|nullable|numeric|min:0',
            
            'producto_nombre_legacy' => 'nullable|string|max:255',
            'llena_cupo_prepagado'   => 'nullable|boolean'
        ], $messages);

        if ($validator->fails()) {
            return redirect()->back()
                             ->withErrors($validator)
                             ->withInput();
        }

        // Ejecutamos la acción a través del servicio
        $this->depositoService->registrarDeposito($validator->validated());

        Session::flash('success', '¡Tanque registrado exitosamente!');
        
        // Redirección apuntando al nuevo namespace de rutas que estás armando
        return redirect()->route('combustibles.depositos.index');
    }

    public function update(Request $request, int $id)
    {
        $messages = [
            'serial.required' => 'El campo Serial / Nombre es obligatorio.',
            'serial.unique'   => 'Este Nombre ya se encuentra registrado para la sede seleccionada.',
            'id_sede.required' => 'Debe seleccionar una sede válida.',
            'tipo_combustible_id.required' => 'Debe seleccionar un tipo de combustible.',
            'capacidad_maxima.required' => 'La capacidad máxima es obligatoria.',
            'diametro.required_if' => 'El diámetro (cm) es obligatorio.',
            'longitud.required_if' => 'El largo (cm) es obligatorio.',
            'ancho.required_if'    => 'El ancho (cm) es obligatorio.',
            'alto.required_if'     => 'La altura (cm) es obligatorio.',
        ];

        $validator = Validator::make($request->all(), [
            'serial' => [
                'required', 'string', 'max:255',
                // Buscamos duplicados en la misma sede, pero IGNORANDO el ID de este tanque
                Rule::unique('depositos', 'serial')->where(function ($query) use ($request) {
                    return $query->where('id_sede', $request->id_sede);
                })->ignore($id)
            ],
            'id_sede' => 'required|exists:sedes,id', 
            'tipo_combustible_id' => 'required|exists:tipos_combustible,id',
            'capacidad_maxima' => 'required|numeric|min:0',
            'forma' => 'required|in:CH,CV,OH,OV,R,C,E',

            'diametro' => 'required_if:forma,CH,CV,OH,C,E|nullable|numeric|min:0',
            'longitud' => 'required_if:forma,CH,R,OH,OV|nullable|numeric|min:0',
            'ancho'    => 'required_if:forma,R,OH,OV|nullable|numeric|min:0',
            'alto'     => 'required_if:forma,R,CV,OV|nullable|numeric|min:0',
            
            'producto_nombre_legacy' => 'nullable|string|max:255',
            'llena_cupo_prepagado'   => 'nullable|boolean'
        ], $messages);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        // Llamamos al método que creamos en el servicio
        $this->depositoService->actualizarDeposito($id, $validator->validated());

        Session::flash('success', '¡Tanque actualizado correctamente!');
        return redirect()->route('combustibles.depositos.index');
    }

    public function destroy(int $id)
    {
        try {
            // Solicitamos la eliminación al servicio
            $this->depositoService->eliminarDeposito($id);
            
            Session::flash('success', '¡Tanque eliminado de la infraestructura exitosamente!');
        } catch (\Exception $e) {
            // Captura errores de integridad por si tiene varillajes, auditorías o registros amarrados
            Session::flash('error', 'No se puede eliminar este tanque porque cuenta con historial operativo o registros asociados en el sistema.');
        }

        return redirect()->route('combustibles.dashboard');
    }

    public function updateLayout(Request $request)
    {
        // Validar el array entrante
        $request->validate([
            'tanques' => 'required|array',
            'tanques.*.id' => 'required|exists:depositos,id',
            'tanques.*.x' => 'required|numeric',
            'tanques.*.z' => 'required|numeric',
            'tanques.*.rotacion' => 'required|numeric|min:0|max:360',
        ]);

        // Actualizar uno a uno velozmente
        foreach ($request->tanques as $datosTanque) {
            Deposito::where('id', $datosTanque['id'])->update([
                'orden_x' => $datosTanque['x'],
                'orden_z' => $datosTanque['z'],
                'rotacion' => $datosTanque['rotacion']
            ]);
        }

        return response()->json(['success' => true]);
    }

     public function actualizar(Request $request, Deposito $deposito)
    {
        $validator = Validator::make($request->all(), [
            'nombre' => 'required|string|max:255',
            'capacidad_litros' => 'required|numeric|min:0',
            //'nivel_actual_cm' => 'required|numeric|min:0',
            'nivel_actual_litros' => 'required|numeric|min:0|lte:capacidad_litros',
            'ubicacion' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return redirect()->route('depositos.index')
                             ->withErrors($validator)
                             ->withInput()
                             ->with('error', 'Error al actualizar el depósito. Revisa los datos ingresados.');
        }

        $deposito->update($validator->validated());

        Session::flash('success', 'Depósito actualizado exitosamente.');
        return redirect()->route('depositos.index');
    }

    public function ajuste(Request $request)
    {
        $deposito=Deposito::find($request->id);
        $variacion=$deposito->nivel_actual_litros - $request->nivel_actual_litros;
        $deposito->nivel_cm = $request->nivel_cm;
        $deposito->nivel_actual_litros= $request->nivel_actual_litros;
        $deposito->save();

        // 3. Crear el registro del movimiento
            $movimiento = new MovimientoCombustible();
            $movimiento->created_at = date('Y-m-d H:i '); // Asignar la fecha del formulario
            $movimiento->tipo_movimiento = 'ajuste';
            $movimiento->deposito_id = $request->deposito_id;
            $movimiento->cantidad_litros = abs($variacion);
            $movimiento->observaciones = $request->observacion;
            $movimiento->cant_inicial =$deposito->nivel_actual_litros;
            $movimiento->cant_final= $request->nivel_actual_litros;
            $movimiento->save();


        Session::flash('success', 'Depósito actualizado exitosamente.');
        return redirect()->route('depositos.index');
    }

    public function ajusteResguardo(Request $request)
    {
        $resguardo=Parametro::where('nombre','resguardo')->first();
        $resguardo->valor=$request->nuevo_resguardo;
        $resguardo->save();

        return response()->json([
            'message' => 'Resguardo ajustado con éxito.',
            'nuevo_resguardo' => round($resguardo->valor, 2)    
        ]);
    }

    public function ajusteDinamic(Request $request)
    {
        $deposito=Deposito::find($request->id);
        $total=0;
        $total00=0;


        $parteEntera = floor($request->nuevo_nivel);

        // 2. Obtener la parte decimal (siempre un valor entre 0 y 1).
        $parteDecimal = $request->nuevo_nivel - $parteEntera;
        $valorRedondeado = 0.0;

        // 3. Aplicar la lógica solicitada.
        // Usamos una pequeña tolerancia (epsilon) para evitar problemas de coma flotante, 
        // aunque para 0.5 no suele ser tan crítico.
        if ($parteDecimal >= 0.5) { 
            // Si el decimal es > 0.5, ajustamos a 0.5.
            $valorRedondeado = $parteEntera + 0.5;
        } else {
            // Si el decimal es <= 0.5, ajustamos a 0.0 (la parte entera).
            $valorRedondeado = $parteEntera;
        }

        // Es importante devolver un float, aunque se vea como entero, para futuras comparaciones.
        $nuevoNivel = (float)number_format($valorRedondeado, 1, '.', '');
        
        $litrosActual=Aforo::where('profundidad_cm', $nuevoNivel)->where('deposito_id', $request->id)->first();
       
        if($litrosActual){
            $variacion=$deposito->nivel_actual_litros - $litrosActual->litros;
            
            $deposito->nivel_actual_litros= $litrosActual->litros;
            $deposito->nivel_cm= $request->nuevo_nivel;
            $deposito->save();

        // 3. Crear el registro del movimiento
            $movimiento = new MovimientoCombustible();
            $movimiento->created_at = date('Y-m-d H:i '); // Asignar la fecha del formulario
            $movimiento->tipo_movimiento = 'ajuste';
            $movimiento->deposito_id = $request->id;
            $movimiento->cantidad_litros = $variacion;
            $movimiento->observaciones = $request->observacion;
            $movimiento->save();

            $mensaje = "✅ Nivel Tanque {$deposito->serial}:\n"
                 . "Nivel Actual: {$request->nuevo_nivel} Cm\n"
                 . "Disponibles: {$deposito->nivel_actual_litros} Ltrs\n"
                 . "Variacion: {$variacion} Ltrs\n"
                 . "Observacion: {$request->observacion}\n";

        // 1. Notificación a Telegram (Ejemplo de Alerta General)
        try {
            // El servicio TelegramNotificationService debe tener un método como sendNotification
           // $this->telegramService->sendMessage($mensaje);
           $total= Deposito::whereNotIn('serial',['00'])->sum('nivel_actual_litros');
           $total00= Deposito::where('serial','00')->pluck('nivel_actual_litros');

        } catch (\Exception $e) {
            Log::error("Error enviando notificación a Telegram: " . $e->getMessage());
        }



        return response()->json([
            'message' => 'Nivel ajustado con éxito.',
            'nuevo_nivel' => round($deposito->nivel_actual_litros, 2),
            'capacidad' => $deposito->capacidad_litros,
            'total' => $total,
            'total00' => $total00
        ]);
    }
    return response()->json([
            'message' => 'Nivel no ajustado.',
            'nuevo_nivel' => round($deposito->nivel_actual_litros, 2),
            'capacidad' => $deposito->capacidad_litros,
            
        ]);
       
    }

}