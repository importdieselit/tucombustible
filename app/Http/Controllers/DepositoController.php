<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Deposito;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Session;
use App\Http\Controllers\BaseController;
use App\Models\Aforo;
use Illuminate\Support\Facades\Log;
use App\Models\MovimientoCombustible;
use App\Services\TelegramNotificationService;

class DepositoController extends BaseController
{

    protected $fcmService;
    protected $telegramService;

    public function __construct(
    //    FcmNotificationService $fcmService, 
        TelegramNotificationService $telegramService
    ) {
      //  $this->fcmService = $fcmService;
        $this->telegramService = $telegramService;
    }

     public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [

            'capacidad_litros' => 'required|numeric|min:0',
            'nivel_actual_litros' => 'required|numeric|min:0|lte:capacidad_litros',
            'ubicacion' => 'nullable|string|max:255',
            'serial' => 'required|string|max:255|unique:depositos,serial',
            'producto' => 'required|string|max:255'

        ]);

        if ($validator->fails()) {
            return redirect()->route('depositos.index')
                             ->withErrors($validator)
                             ->withInput()
                             ->with('error', 'Error al crear el depósito. Revisa los datos ingresados.');
        }

        Deposito::create($validator->validated());

        Session::flash('success', 'Depósito creado exitosamente.');
        return redirect()->route('depositos.list');
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

     public function ajusteDinamic(Request $request)
    {
        $deposito=Deposito::find($request->id);

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
        } catch (\Exception $e) {
            Log::error("Error enviando notificación a Telegram: " . $e->getMessage());
        }



        return response()->json([
            'message' => 'Nivel ajustado con éxito.',
            'nuevo_nivel' => round($deposito->nivel_actual_litros, 2),
            'capacidad' => $deposito->capacidad_litros
        ]);
    }
    return response()->json([
            'message' => 'Nivel no ajustado.',
            'nuevo_nivel' => round($deposito->nivel_actual_litros, 2),
            'capacidad' => $deposito->capacidad_litros
        ]);
       
    }

}
