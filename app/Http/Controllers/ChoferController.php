<?php

namespace App\Http\Controllers;

use App\Models\Chofer;
use App\Models\Vehiculo;
use App\Models\Persona;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use DateTime;

class ChoferController extends BaseController
{
    /**
     * Muestra el dashboard principal con resumen de datos.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        // Datos de ejemplo para el dashboard (simulados)
        $totalChoferes = Chofer::where('cargo','CHOFER')->count();
        $choferesDisponibles = Chofer::whereNull('vehiculo_id')->count();
        $choferesEnRuta = Chofer::whereNotNull('vehiculo_id')->count();

        // Datos para la gráfica de rendimiento (simulados)
        $rendimiento = [
            '1_estrella' => 2,
            '2_estrellas' => 5,
            '3_estrellas' => 10,
            '4_estrellas' => 15,
            '5_estrellas' => 8,
        ];

        // Reporte de incidencias (simulado)
        $incidencias = [
            'Enero' => 5,
            'Febrero' => 3,
            'Marzo' => 7,
            'Abril' => 4,
            'Mayo' => 9,
            'Junio' => 6,
        ];

        return view('chofer.index', compact('totalChoferes', 'choferesDisponibles', 'choferesEnRuta', 'rendimiento', 'incidencias'));
    }

    /**
     * Muestra una lista de choferes.
     *
     * @return \Illuminate\Http\Response
     */
    

    private function parseDate($date_string, $format_in)
    {
        // Trim any whitespace
        $date_string = trim($date_string);
        
        // If the string is empty, return null immediately
        if (empty($date_string)) {
            return null;
        }

        // Use DateTime::createFromFormat to parse the string with the specified format
        $date_object = DateTime::createFromFormat($format_in, $date_string);

        // Check if the date object was successfully created
        if ($date_object !== false && $date_object->format($format_in) === $date_string) {
            // Return the date formatted for the database
            return $date_object->format('Y-m-d');
        }

        // Return null if parsing fails
        return null;
    }

    /**
     * Muestra el formulario para crear un nuevo chofer.
     *
     * @return \Illuminate\Http\Response
     */
    
    /**
     * Almacena un nuevo chofer y su información de persona en la base de datos.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string|max:255',
            'dni' => 'required|string|max:255|unique:personas',
            'dni_exp' => 'required|date',
            'telefono' => 'nullable|string|max:255',
            'licencia_numero' => 'required|string|max:255|unique:choferes',
            'licencia_vencimiento' => 'required|date',
            'documento_vialidad_numero' => 'nullable|string|max:255',
            'documento_vialidad_vencimiento' => 'nullable|date',
            'vehiculo_id' => 'nullable|exists:vehiculos,id',
        ]);

        DB::beginTransaction();
        try {
            // Asignación explícita para evitar problemas de asignación masiva
            $persona = new Persona();
            $persona->nombre = $request->nombre;
            $persona->dni = $request->dni;
            $persona->dni_exp = $request->dni_exp;
            $persona->telefono = $request->telefono;
            // Si tu formulario tiene más campos de Persona, agrégalos aquí.
            $persona->save();

            // Crear el registro en la tabla 'choferes'
            Chofer::create(array_merge($request->only('licencia_numero', 'licencia_vencimiento', 'documento_vialidad_numero', 'documento_vialidad_vencimiento', 'vehiculo_id'), ['persona_id' => $persona->id]));

            DB::commit();
            Session::flash('success', 'Chofer registrado exitosamente.');
            return Redirect::route('choferes.list');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al registrar chofer: ' . $e->getMessage());
            Session::flash('error', 'Hubo un error al procesar el registro.');
            return Redirect::back()->withInput();
        }
    }

    /**
     * Muestra el detalle de un chofer específico.
     *
     * @param  \App\Models\Chofer  $chofer
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $chofer = Chofer::findOrFail($id);
     
        // Cargar las relaciones 'persona' y 'vehiculo'
        $chofer->load('persona', 'vehiculo');

        // Datos de ejemplo para el historial y rendimiento del chofer (simulados)
        $historialViajes = [
            ['ruta' => 'Caracas - Valencia', 'fecha' => '2023-10-15', 'incidencias' => 'Ninguna'],
            ['ruta' => 'Maracay - Barquisimeto', 'fecha' => '2023-10-10', 'incidencias' => 'Retraso por tráfico'],
            ['ruta' => 'Puerto La Cruz - Cumaná', 'fecha' => '2023-10-05', 'incidencias' => 'Falla mecánica leve'],
        ];

        $graficaRendimiento = [
            'labels' => ['Enero', 'Febrero', 'Marzo', 'Abril'],
            'data' => [4.5, 4.8, 4.2, 5.0],
        ];

        return view('chofer.show', compact('chofer', 'historialViajes', 'graficaRendimiento'));
    }

    /**
     * Muestra el formulario para editar un chofer existente.
     *
     * @param  \App\Models\Chofer  $chofer
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $chofer = Chofer::findOrFail($id);
        $vehiculos = Vehiculo::all();
        $chofer->load('persona');
        return view('chofer.form', compact('chofer', 'vehiculos'));
    }
    
    /**
     * Actualiza la información de un chofer en la base de datos.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Chofer  $chofer
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $chofer = Chofer::findOrFail($id);

        $request->validate([
            'nombre' => 'required|string|max:255',
            'dni' => 'required|string|max:255|unique:personas,dni,' . $chofer->persona_id,
            'dni_exp' => 'required|date',
            'telefono' => 'nullable|string|max:255',
            'licencia_numero' => 'required|string|max:255|unique:choferes,licencia_numero,' . $chofer->id,
            'licencia_vencimiento' => 'required|date',
            'documento_vialidad_numero' => 'nullable|string|max:255',
            'documento_vialidad_vencimiento' => 'nullable|date',
            'vehiculo_id' => 'nullable|exists:vehiculos,id',
        ]);

        DB::beginTransaction();
        try {
            $chofer->persona->update($request->only('nombre', 'dni', 'dni_exp', 'telefono'));
            $chofer->update($request->only('licencia_numero', 'licencia_vencimiento', 'documento_vialidad_numero', 'documento_vialidad_vencimiento', 'vehiculo_id'));
            DB::commit();
            Session::flash('success', 'Información del chofer actualizada exitosamente.');
            return Redirect::route('choferes.list');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al actualizar chofer: ' . $e->getMessage());
            Session::flash('error', 'Hubo un error al procesar la actualización.');
            return Redirect::back()->withInput();
        }
    }

    /**
     * Elimina un chofer de la base de datos.
     *
     * @param  \App\Models\Chofer  $chofer
     * @return \Illuminate\Http\Response
     */
  
    /**
     * Muestra el formulario para importar choferes desde un archivo.
     *
     * @return \Illuminate\Http\Response
     */
    public function showImportForm()
    {
        return view('chofer.import');
    }

    /**
     * Procesa la subida del archivo y realiza la importación de choferes.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function importar(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:csv,txt|max:2048', // Aseguramos que es un archivo CSV
        ]);

        $file = $request->file('file');
        $filePath = $file->getRealPath();
        
        // Abrir el archivo en modo lectura
        $csvFile = fopen($filePath, 'r');
        if (!$csvFile) {
            Session::flash('error', 'No se pudo abrir el archivo.');
            return Redirect::back();
        }

        // Variable para controlar si el encabezado ha sido leído
        $header = false;
        $importedCount = 0;
        $skippedCount = 0;

        DB::beginTransaction();
        try {
            while (($row = fgetcsv($csvFile, 1000, ';')) !== FALSE) {
                
                // Saltar la primera fila (encabezado) y filas vacías
                if (!$header) {
                    $header = true;
                    continue;
                }

                //dd($row);
                
                // Si la fila tiene menos de 7 columnas, la ignoramos.
                // if (count($row) < 6) {
                //     $skippedCount++;
                //     continue;
                // }

                // Mapear las columnas del CSV a los campos del modelo
                $nombres = trim($row[0] ?? '');
                $apellidos = trim($row[1] ?? '');
                $dni = trim($row[2] ?? '');
                $tipo_licencia = trim($row[4] ?? '');
                $licenciaVencimiento = trim($row[5] ?? '');
                $cargo = trim($row[3] ?? '');
                $docVialidadNumero = trim($row[9] ?? '');
                $docVialidadVencimiento = trim($row[8] ?? '');
                $certificado_medico = trim($row[6] ?? '');
                $certificado_medico_vencimiento = trim($row[7] ?? '');
                //dd($nombres, $apellidos, $dni, $tipo_licencia, $licenciaNumero, $docVialidadNumero, $docVialidadVencimiento);
                // Si no hay cédula, saltamos la fila
                if (empty($dni)) {
                    $skippedCount++;
                    continue;
                }

                // Verificar si el chofer ya existe para evitar duplicados
                $personaExistente = Persona::where('dni', $dni)->first();
                if ($personaExistente) {
                    $skippedCount++;
                    continue;
                }

                           // Parse the date strings using the new helper function
                $parsedLicenciaVencimiento = $this->parseDate($licenciaVencimiento, 'd/m/Y');
                $parsedDocVialidadVencimiento = $this->parseDate($docVialidadVencimiento, 'd/m/Y');
                $parsedCertificadoMedicoVencimiento = $this->parseDate($certificado_medico_vencimiento, 'd/m/Y');

                // Create the person record
                $persona = Persona::create([
                    'nombre' => $nombres . ' ' . $apellidos,
                    'dni' => $dni,
                    'dni_exp' => Carbon::now()->addYears(10), // Default value from your code
                    'telefono' => null, // Not available in the CSV
                ]);

                // Create the driver record with the parsed dates
                Chofer::create([
                    'persona_id' => $persona->id,
                    'licencia_numero' => null, // Default value from your code
                    'licencia_vencimiento' => $parsedLicenciaVencimiento,
                    'documento_vialidad_numero' => $docVialidadNumero,
                    'documento_vialidad_vencimiento' => $parsedDocVialidadVencimiento,
                    'vehiculo_id' => null, // Default value from your code
                    'tipo_licencia' => $tipo_licencia,
                    'cargo' => $cargo,
                    'certificado_medico' => $certificado_medico,
                    'certificado_medico_vencimiento' => $parsedCertificadoMedicoVencimiento,
                ]);

                $importedCount++;
            }

            fclose($csvFile);
            DB::commit();

            Session::flash('success', "Importación completada. Se importaron {$importedCount} choferes. Se saltaron {$skippedCount} filas.");
            return Redirect::route('choferes.list');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error en la importación de choferes: ' . $e->getMessage());
            Session::flash('error', 'Hubo un error en la importación. Ningún registro fue guardado.');
            return Redirect::back()->withInput();
        }
    }

}
