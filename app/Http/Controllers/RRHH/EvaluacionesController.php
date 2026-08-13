<?php

namespace App\Http\Controllers\RRHH;

use App\Http\Controllers\Controller;
use App\Services\GoogleSheetsService;
use Illuminate\Http\Request;
use App\Models\RrhhEvaluacionForm;
use App\Models\User;
use App\Repositories\EvaluacionesRepository;
use Google\Client;
use Google\Service\Sheets;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class EvaluacionesController extends Controller
{

    protected $sheetService;
    protected $repository;

    public function __construct(GoogleSheetsService $sheetService, EvaluacionesRepository $repository)
    {
        $this->sheetService = $sheetService;
        $this->repository = $repository;
    }

   

    public function generarReporte()
    {
        {
            try {
                // 1. Inicializar y autenticar el cliente de la API de Google
                $client = new Client();
                $client->setAuthConfig(config('services.google.credentials'));
                $client->addScope(Sheets::SPREADSHEETS_READONLY);

                $sheets = new Sheets($client);
                $spreadsheetId = config('services.google.spreadsheet_id');

                // 2. Obtener la lista real de todas las pestañas (cargos) del documento
                $metaData = $sheets->spreadsheets->get($spreadsheetId);
                $sheetsList = $metaData->getSheets();

                // Colección final donde agruparemos a todo el personal de todas las hojas
                $reporteConsolidado = collect();

                // 3. Recorrer cada una de las pestañas dinámicamente

                foreach ($sheetsList as $sheet) {
                    $nombrePestana = $sheet->getProperties()->getTitle();
                    $range = "'{$nombrePestana}'!A1:O100"; // IMPORTANTE: Cambiamos A2 por A1 para poder leer la cabecera (las preguntas)
                    
                    $response = $sheets->spreadsheets_values->get($spreadsheetId, $range);
                    $rawRows = $response->getValues();

                    if ($rawRows && count($rawRows) > 1) {
                        
                        // Extraemos la fila 0 que contiene las cabeceras (las preguntas del formulario)
                        $preguntas = $rawRows[0]; 

                        foreach ($sheetsList as $sheet) {
                            $nombrePestana = $sheet->getProperties()->getTitle();
                            $sheetId = $sheet->getProperties()->getSheetId(); 
                            
                            $range = "'{$nombrePestana}'!A1:O100";
                            
                            $response = $sheets->spreadsheets_values->get($spreadsheetId, $range);
                            $rawRows = $response->getValues();

                            if ($rawRows && count($rawRows) > 1) {
                                $preguntas = $rawRows[0]; 

                                // Usamos $index => $row para saber en qué fila exacta estamos
                                foreach ($rawRows as $index => $row) {
                                    if ($index === 0 || empty($row) || !isset($row[3])) {
                                        continue;
                                    }
                                    if (str_contains(strtolower($row[3]), 'nombre y apellido')) {
                                        continue;
                                    }

                                    $respuestasNegativas = [];
                                    
                                    for ($i = 5; $i < count($row); $i++) {
                                        $respuestaText = $row[$i] ?? '';
                                        $respuestaLower = strtolower(trim($respuestaText));
                                        
                                        $palabrasNegativas = ['no', 'nunca', 'casi nunca', 'rara vez', 'falso', 'en desacuerdo'];

                                        if (in_array($respuestaLower, $palabrasNegativas) || $respuestaLower === '0' || $respuestaLower === '1') {
                                            $textoPregunta = $preguntas[$i] ?? 'Criterio no definido';
                                            $textoLimpio = preg_replace('/^[\d\.\-\s¿]+/', '', $textoPregunta);
                                            $textoLimpio = str_replace(['?', '"'], '', $textoLimpio);
                                            
                                            $indicadorDebilidad = \Illuminate\Support\Str::limit(ucfirst(trim($textoLimpio)), 45, '...');

                                            $respuestasNegativas[] = [
                                                'indicador' => $indicadorDebilidad,
                                                'respuesta' => $respuestaText
                                            ];
                                        }
                                    }

                                    // NUEVO: Calculamos la fila real de Excel (índice 0 es Fila 1, índice 1 es Fila 2, etc.)
                                    $filaExcel = $index + 1;
                                    
                                    // NUEVO: Construimos el link directo a la celda específica de ese empleado
                                    $linkEdicion = "https://docs.google.com/spreadsheets/d/{$spreadsheetId}/edit#gid={$sheetId}&range=A{$filaExcel}";

                                    $reporteConsolidado->push([
                                        'fecha_evaluacion' => $row[2] ?? 'N/A',
                                        'nombre'           => trim($row[3]),
                                        'cargo'            => trim($row[4] ?? $nombrePestana),
                                        'puntuacion'       => $row[1] ?? 'N/A',
                                        'marca_temporal'   => $row[0] ?? 'N/A',
                                        'negativas'        => $respuestasNegativas,
                                        'link_edicion'     => $linkEdicion // Pasamos el link a la vista
                                    ]);
                                }
                            }
                        }
                    }
                }


                // 4. Ordenar los resultados alfabéticamente por nombre para una presentación ejecutiva
                $reporteResultados = $reporteConsolidado->sortBy('nombre')->values();

                // 5. Retornar la vista tradicional con toda la data unificada
                return view('rrhh.evaluaciones.resultados_sheets', compact('reporteResultados'));

            } catch (\Exception $e) {
                // Registro estricto en logs con formato JSON para fácil lectura en producción
                Log::error("Falla crítica al recorrer Google Sheets: " . $e->getMessage());
                return abort(500, 'Error interno al procesar las hojas de evaluación.');
            }
        }
    }

   public function create()
    {
        $usuario = User::find(auth()->user()->id);
        $personalData = $usuario->personalData();


        if (!$personalData) {
            return redirect()->back()->with('error', 'No se encontró información de personal para el usuario.');
        }

        $formulario = $personalData->getEvaluacionForm();
        if (!$formulario) {
            return redirect()->back()->with('error', 'No hay un formulario de evaluación activo para tu cargo.');
        }


        return view('rrhh.evaluaciones.index', compact('formulario'));
    }

    public function store()
    {
        $usuario = User::find(auth()->user()->id);
        $personalData = $usuario->personalData();

        if (!$personalData) {
            return redirect()->back()->with('error', 'No se encontró información de personal para el usuario.');
        }

        $formulario = $personalData->getEvaluacionForm();
        if (!$formulario) {
            return redirect()->back()->with('error', 'No hay un formulario de evaluación activo para tu cargo.');
        }
        return redirect()->back()->with('success', 'Formulario de evaluación enviado correctamente.');
    }

        public function edit($id)
        {
            $formulario = RrhhEvaluacionForm::findOrFail($id);
            return view('rrhh.evaluaciones.edit', compact('formulario'));
        }

        public function update()
        {
            $id = request()->input('id');
            $formulario = RrhhEvaluacionForm::findOrFail($id);

            $formulario->nombre = request()->input('nombre');
            $formulario->google_form_url = request()->input('google_form_url');
            $formulario->activo = request()->input('activo');

            $formulario->save();   
        }
}
