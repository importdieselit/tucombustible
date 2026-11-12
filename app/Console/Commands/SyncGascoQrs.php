<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Models\Estado;
use App\Models\Ciudad;
use Illuminate\Support\Str;
use Carbon\Carbon;

class SyncGascoQrs extends Command
{
    protected $signature = 'gasco:sync';
    protected $description = 'Simula inicio de sesión en Gasco, obtiene el token JWT y extrae los códigos de sede para los QRs';

    public function handle()
    {
        $this->info('Iniciando infiltración en Sistema Gasco...');

        // --------------------------------------------------------
        // FASE 1: AUTENTICACIÓN
        // --------------------------------------------------------
        $urlLogin = 'https://gasco.minhidrocarburos.gob.ve/api/Auth'; 
        
        $this->info('1. Autenticando...');
        $loginResponse = Http::post($urlLogin, [
            'username' => 'lmartinez@impordiesel.com.ve',
            'password' => 'iYSYG5yL7DKKmh3709o-Zw',
            'captcha'  => true
        ]);

        if (!$loginResponse->successful()) {
            $this->error('Falló el login. Credenciales inválidas o el servidor rechazó la conexión.');
            return Command::FAILURE;
        }

        // Extraemos el token según la estructura JSON que descubriste
        $token = $loginResponse->json('data.authData.token'); 

        if (!$token) {
            $this->error('Login exitoso, pero no se encontró el Token JWT en la respuesta.');
            return Command::FAILURE;
        }

        $this->info('Token JWT obtenido con éxito.');

        // --------------------------------------------------------
        // FASE 2: EXTRACCIÓN MASIVA DE DATOS
        // --------------------------------------------------------
        $emailParam = urlencode('lmartinez@impordiesel.com.ve');
        $period = Carbon::now()->format('m-Y');
        
        // Forzamos pageSize=100 para traerlos todos sin iterar
        $urlBase = 'https://gasco.minhidrocarburos.gob.ve/api/DistributorPublic';
        $urlDataNormal  = "{$urlBase}/AssigmentsBranches?email={$emailParam}&period={$period}&pageSize=100&pageNumber=1";
        $urlDataSpecial = "{$urlBase}/AssigmentsBranchesSpecial?email={$emailParam}&period={$period}&pageSize=100&pageNumber=1";

        $this->info('2. Descargando clientes de asignación normal...');
        $respNormal = Http::withToken($token)
            ->withHeaders(['accept' => 'application/json'])
            ->get($urlDataNormal);

        $registrosNormales = $respNormal->successful() 
            ? ($respNormal->json('data.records') ?? []) 
            : [];

        $this->info('   -> Obtenidos: ' . count($registrosNormales) . ' registros.');

        $this->info('3. Descargando clientes de asignación especial...');
        $respSpecial = Http::withToken($token)
            ->withHeaders(['accept' => 'application/json'])
            ->get($urlDataSpecial);

        $registrosEspeciales = $respSpecial->successful() 
            ? ($respSpecial->json('data.records') ?? $respSpecial->json('records') ?? []) 
            : [];

        $this->info('   -> Obtenidos: ' . count($registrosEspeciales) . ' registros especiales.');

        $registros = collect($registrosNormales)->unique('id')->values()->all();
        $registrosEsp = collect($registrosEspeciales)->unique('id')->values()->all();

        if (empty($registros) && empty($registrosEsp)) {
            $this->warn('No se encontraron registros para este periodo.');
            return Command::SUCCESS;
        }

        // Traemos todos los campos que realmente se evalúan y heredan
        $clientesLocales = DB::table('clientes')
            ->select('id', 'nombre', 'alias', 'rif', 'qr_gasco', 'contacto', 'email', 'telefono', 'cupo', 'parent', 'token_registro')
            ->get();
        
        $this->info('Se descargarán detalles de ' . (count($registros) + count($registrosEsp)) . ' clientes.');
        $this->newLine();

        // --------------------------------------------------------
        // FASE 3: EXTRACCIÓN DETALLADA Y CRUCE INTERACTIVO
        // --------------------------------------------------------
        $actualizados = 0;
        $creados = 0;

        if (!empty($registros)) {
            $this->info('--- PROCESANDO ASIGNACIONES NORMALES ---');
            $this->procesarRegistros($registros, 'AssigmentsBrancheDetail', $token, $clientesLocales, $actualizados, $creados);
        }

        if (!empty($registrosEsp)) {
            $this->info('--- PROCESANDO ASIGNACIONES ESPECIALES ---');
            $this->procesarRegistros($registrosEsp, 'AssigmentsBranchesSpecialDetail', $token, $clientesLocales, $actualizados, $creados);
        }

        $this->info("¡Proceso finalizado! {$actualizados} actualizados | {$creados} nuevos creados.");
        return Command::SUCCESS;
    }

    private function procesarRegistros(array $registros, string $endpointDetalle, string $token, $clientesLocales, int &$actualizados, int &$creados)
    {
        foreach ($registros as $registro) {
            $idRegistro = $registro['id'];
            $branchCode = $registro['branchCode'];
            $assignedLiters = $registro['assignedLiters'] ?? 0;
            $businessName = $registro['businessName'];

            $qrBranchCode = str_replace('-', ':', $branchCode); 
            $contenidoQr = "GASCO:{$qrBranchCode}:{$businessName}";

            $urlDetail = "https://gasco.minhidrocarburos.gob.ve/api/DistributorPublic/{$endpointDetalle}?code={$idRegistro}";
            
            $detailResponse = Http::withToken($token)->withHeaders([
                'Accept' => 'application/json',
                'User-Agent' => 'Mozilla/5.0 (Windows)',
            ])->get($urlDetail);

            if (!$detailResponse->successful()) {
                $this->error("Falló la descarga del detalle para la sede: {$branchCode}");
                continue;
            }

            $detalle = $detailResponse->json('data');
            $rifGasco = $detalle['rif'];
            $branchName = $detalle['branchName'];

            $this->line("Procesando: <fg=cyan>{$businessName} - {$branchName} (RIF: {$rifGasco})</>");

            // Lógica de Matching
            $rifGascoLimpio = $this->limpiarRif($rifGasco);
            $sedeGascoLimpia = $this->normalizarTexto($branchName);

            // Filtramos en la colección los que tengan el mismo RIF
            $candidatos = $clientesLocales->filter(function ($cliente) use ($rifGascoLimpio) {
                return $this->limpiarRif($cliente->rif) === $rifGascoLimpio;
            });

            $candidatoSeleccionado = null;
            $crearNuevo = false;

            if ($candidatos->isEmpty()) {
                $this->warn("   ⚠️ RIF NO encontrado en BD. Se preparará como NUEVO CLIENTE (Padre).");
                $crearNuevo = true;
            } else {
                // Buscamos si hay un match perfecto
                $matchPerfecto = null;
                foreach ($candidatos as $candidato) {
                    if (isset($candidato->qr_gasco) && $candidato->qr_gasco == $contenidoQr) {
                        $matchPerfecto = $candidato; 
                        break;
                    }

                    $nombreLocalLimpio = $this->normalizarTexto($candidato->nombre);
                    $aliasLocalLimpio = $this->normalizarTexto($candidato->alias);

                    if (str_contains($nombreLocalLimpio, $sedeGascoLimpia) || str_contains($aliasLocalLimpio, $sedeGascoLimpia)) {
                        $matchPerfecto = $candidato;
                        break;
                    }
                }

                if ($matchPerfecto) {
                    $candidatoSeleccionado = $matchPerfecto;
                    $this->info("   ✅ Match Automático: {$candidatoSeleccionado->nombre}");
                } else {
                    // No es un match perfecto, armamos un menú con todos los clientes locales que comparten ese RIF
                    $this->warn("   ⚠️ Nombres diferentes para el RIF {$rifGasco}.");
                    $this->line("   <fg=yellow>Sede en Gasco:</> {$branchName}");
                    
                    $opcionesMenu = [
                        'nueva'  => 'Crear como NUEVA sucursal',
                        'saltar' => 'Saltar cliente (Ignorar por ahora)'
                    ];
                    
                    $mapaOpciones = [];
                    $i = 1;
                    foreach ($candidatos as $cand) {
                        $label = "Vincular a: {$cand->nombre}" . ($cand->alias ? " (Alias: {$cand->alias})" : "");
                        $opcionesMenu["vincular_$i"] = $label;
                        $mapaOpciones["vincular_$i"] = $cand;
                        $i++;
                    }

                    $seleccion = $this->choice("   ¿Qué deseas hacer?", array_values($opcionesMenu), 0);

                    if ($seleccion === $opcionesMenu['nueva']) {
                        $crearNuevo = true;
                    } elseif ($seleccion === $opcionesMenu['saltar']) {
                        $this->line("   ❌ Omitido por el usuario.");
                    } else {
                        // Encontrar cuál candidato local fue seleccionado para vincular
                        foreach ($opcionesMenu as $key => $label) {
                            if ($label === $seleccion && isset($mapaOpciones[$key])) {
                                $candidatoSeleccionado = $mapaOpciones[$key];
                                $this->info("   ✔️ Vinculado manualmente a: {$candidatoSeleccionado->nombre}");
                                break;
                            }
                        }
                    }
                }
            }

            // Si el usuario decidió saltar el cliente, no consultamos Estado/Ciudad en vano
            if (!$candidatoSeleccionado && !$crearNuevo) {
                $this->newLine();
                usleep(250000);
                continue;
            }

            $estado = Estado::firstOrCreate(
                ['nombre' => $detalle['state'] ?: 'Distrito Capital']
            );

            $ciudad = Ciudad::firstOrCreate(
                ['nombre' => $detalle['municipality'] ?: 'Caracas', 'estado_id' => $estado->id]
            );

            // Consolidar dirección
            $direccionPartes = [
                $detalle['state'] ?? null, $detalle['municipality'] ?? null, $detalle['parish'] ?? null,
                $detalle['locationWay'] ?? null, $detalle['locationDetail'] ?? null, $detalle['locationNumber'] ?? null,
                $detalle['locationFloor'] ?? null, $detalle['locationOffice'] ?? null, $detalle['reference'] ?? null
            ];
            $direccionCompleta = implode(', ', array_filter(array_map('trim', $direccionPartes)));

            if ($candidatoSeleccionado) {
                $nuevoContacto = $detalle['responsibleName'] ?? $candidatoSeleccionado->contacto;
                $nuevoEmail = $detalle['responsibleEmail'] ?? $candidatoSeleccionado->email;
                $nuevoTelefono = $detalle['responsiblePhone'] ?? $candidatoSeleccionado->telefono;

                if (
                    $candidatoSeleccionado->cupo != $assignedLiters ||
                    $candidatoSeleccionado->contacto !== $nuevoContacto ||
                    $candidatoSeleccionado->email !== $nuevoEmail ||
                    $candidatoSeleccionado->telefono !== $nuevoTelefono ||
                    $candidatoSeleccionado->qr_gasco !== $contenidoQr
                ) {
                    // ACTUALIZAR EXISTENTE
                    DB::table('clientes')->where('id', $candidatoSeleccionado->id)->update([
                        'qr_gasco' => $contenidoQr,
                        'cupo' => $assignedLiters,
                        'estado_id' => $estado->id ?? null,
                        'ciudad_id' => $ciudad->id ?? null,
                        'direccion' => $direccionCompleta,
                        'direccion_operativa' => $direccionCompleta,
                        'contacto' => $nuevoContacto,
                        'email' => $nuevoEmail,
                        'telefono' => $nuevoTelefono,
                        'updated_at' => Carbon::now(),
                    ]);
                    $actualizados++;
                }
            } elseif ($crearNuevo) {
                // PREPARAR NUEVO REGISTRO (PADRE O HIJO)
                $parentId = 0;
                $aliasPadre = null;
                $tokenRegistro = null;

                if ($candidatos->isNotEmpty()) {
                    // Es sucursal: Buscamos al padre (el que tiene parent=0) o al primer hermano
                    $padre = $candidatos->firstWhere('parent', 0) ?? $candidatos->first();
                    if ($padre) {
                        $parentId = $padre->id;
                        $aliasPadre = $padre->alias ?? $padre->nombre;
                        $tokenRegistro = $padre->token_registro ?? null;
                    }
                }

                $sucursalSinRedundancia = $branchName;
                if (!empty($aliasPadre)) {
                    // Borramos la palabra del alias padre del nombre de la sede (ignorando mayus/minus)
                    $sucursalSinRedundancia = trim(str_ireplace($aliasPadre, '', $branchName));
                }
                
                // Si por alguna razón borrar el alias dejó la cadena vacía, volvemos al original
                if (empty($sucursalSinRedundancia)) {
                    $sucursalSinRedundancia = $branchName;
                }

                $nombreFinal = $aliasPadre ? $businessName . ' - ' . $sucursalSinRedundancia : $businessName;
                $rifFinal = str_replace('-', '', $rifGasco);
                $contactoFinal = $detalle['responsibleName'] ?? 'SIN CONTACTO';
                $emailFinal = $detalle['responsibleEmail'] ?? 'correo@cambiarcorreo.com';
                $telefonoFinal = $detalle['responsiblePhone'] ?? '04125555555';
                $tipoFinal = strtoupper($detalle['fuelType'] ?? 'DIESEL');

                // Mostramos los datos organizados antes de pedir confirmación
                $this->newLine();
                $this->warn("   📋 DATOS DEL NUEVO REGISTRO A CREAR:");
                $this->table(
                    ['Campo', 'Valor a Guardar'],
                    [
                        ['Jerarquía', $parentId === 0 ? 'CLIENTE PADRE (Principal)' : "SUCURSAL HIJA (Parent ID: {$parentId})"],
                        ['Nombre', $nombreFinal],
                        ['Alias', $branchName],
                        ['RIF', $rifFinal],
                        ['Cupo / Tipo', "{$assignedLiters} Lts ({$tipoFinal})"],
                        ['Estado / Ciudad', "{$estado->nombre} / {$ciudad->nombre}"],
                        ['Dirección', $direccionCompleta],
                        ['Contacto', $contactoFinal],
                        ['Email / Teléfono', "{$emailFinal} | {$telefonoFinal}"],
                        ['QR Gasco', $contenidoQr],
                        ['Token Registro', $tokenRegistro ?? 'NULL (Se asignará después)'],
                    ]
                );

                if ($this->confirm('   ¿Deseas crear este nuevo registro en la base de datos?', true)) {
                    $nuevoId = DB::table('clientes')->insertGetId([
                        'nombre' => $nombreFinal,
                        'alias' => $branchName,
                        'rif' => $rifFinal, 
                        'qr_gasco' => $contenidoQr,
                        'estado_id' => $estado->id ?? null,
                        'ciudad_id' => $ciudad->id ?? null,
                        'direccion' => $direccionCompleta,
                        'direccion_operativa' => $direccionCompleta,
                        'cupo' => $assignedLiters,
                        'prepagado' => 0,
                        'contacto' => $contactoFinal,
                        'email' => $emailFinal,
                        'telefono' => $telefonoFinal,
                        'parent' => $parentId,
                        'token_registro' => $tokenRegistro,
                        'status' => 2,
                        'registro_paso' => 5,
                        'tipo' => $tipoFinal,
                        'created_at' => Carbon::now(),
                        'updated_at' => Carbon::now(),
                    ]);

                    // Lo inyectamos en la colección en memoria para que si la próxima sede es de esta misma empresa,
                    // la reconozca como candidato y no rompa la estructura
                    $clientesLocales->push((object)[
                        'id' => $nuevoId,
                        'nombre' => $nombreFinal,
                        'alias' => $branchName,
                        'rif' => $rifFinal,
                        'parent' => $parentId,
                        'token_registro' => $tokenRegistro,
                        'qr_gasco' => $contenidoQr,
                        'cupo' => $assignedLiters,
                        'contacto' => $contactoFinal,
                        'email' => $emailFinal,
                        'telefono' => $telefonoFinal,
                    ]);

                    $this->info("   ✨ Creado en BD con ID: {$nuevoId} (Parent: {$parentId})");
                    $creados++;
                } else {
                    $this->line("   ❌ Creación cancelada por el usuario.");
                }
            }

            $this->newLine();
            usleep(250000); 
        }
    }

    private function limpiarRif($rif)
    {
        return preg_replace('/[^A-Za-z0-9]/', '', strtoupper($rif));
    }

    private function normalizarTexto($texto)
    {
        if (empty($texto)) return '';
        $texto = mb_strtoupper($texto, 'UTF-8');
        $texto = str_replace(['Á','É','Í','Ó','Ú','Ä','Ë','Ï','Ö','Ü'], ['A','E','I','O','U','A','E','I','O','U'], $texto);
        $texto = preg_replace('/[^A-Z0-9\s]/', ' ', $texto);
        $palabrasBasura = [' CA ', ' SA ', ' C A ', ' S A ', ' SUCURSAL ', ' SEDE ', ' TIENDA ', ' FARMATODO ', ' FORUM '];
        $texto = ' ' . $texto . ' ';
        $texto = str_replace($palabrasBasura, ' ', $texto);
        return trim(preg_replace('/\s+/', ' ', $texto));
    }
}