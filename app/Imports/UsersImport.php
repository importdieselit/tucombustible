<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use App\Models\Cliente;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class UsersImport implements ToModel, WithHeadingRow
{
    /**
     * El nombre y la firma del comando.
     *
     * @var string
     */
    protected $signature = 'import:users {file}';

    /**
     * La descripción del comando de la consola.
     *
     * @var string
     */
    protected $description = 'Importa usuarios y clientes desde un archivo CSV.';

    /**
     * Ejecuta el comando.
     */
    public function handle()
    {
        $filePath = $this->argument('file');

        if (!file_exists($filePath)) {
            $this->error("El archivo no existe en la ruta: {$filePath}");
            return 1;
        }

        $this->info("Iniciando la importación desde {$filePath}...");

        $file = fopen($filePath, 'r');
        $header = fgetcsv($file); // Leer la cabecera

        // Mapear los nombres de las columnas a índices
        $rifDistIndex = array_search('rif', $header);
        $trifDistIndex = array_search('trif', $header);
        $nombreEmpresaIndex = array_search('nombre', $header);
        $ciAutorizadoIndex = array_search('ci', $header);
        $tciAutorizadoIndex = array_search('tci', $header);
        $nombreAutorizadoIndex = array_search('responsable', $header);

        // Validar que las columnas necesarias existan
        if ($rifDistIndex === false || $nombreEmpresaIndex === false || $ciAutorizadoIndex === false || $nombreAutorizadoIndex === false) {
            $this->error('El archivo CSV no tiene las columnas requeridas.');
            fclose($file);
            return 1;
        }

        $lineCount = 0;
        $importedCount = 0;
        $this->withProgressBar(iterator_to_array($this->readCsv($file, $header)), function ($row) use (&$lineCount, &$importedCount, $trifDistIndex,$rifDistIndex, $nombreEmpresaIndex, $tciAutorizadoIndex, $ciAutorizadoIndex, $nombreAutorizadoIndex) {
            $lineCount++;

            // Mapear los datos de la fila
            $rifCliente = trim($row[$trifDistIndex].$row[$rifDistIndex]);
            $nombreCliente = strtoupper(trim($row[$nombreEmpresaIndex]));
            $ciAutorizado = trim($row[$ciAutorizadoIndex]);
            $nombreAutorizado = trim($row[$nombreAutorizadoIndex]);

            // Si falta algún dato crucial, saltar la fila
            if (empty($rifCliente) || empty($nombreAutorizado) || empty($ciAutorizado)) {
                $this->warn("Saltando la línea {$lineCount} debido a datos incompletos.");
                return;
            }

            DB::beginTransaction();
            try {
                // 1. Buscar o crear el cliente (usuario padre)
                $clienteP = Cliente::where('rif', $rifCliente)
                ->where('parent', 0)
                ->first();

                // 2. Buscar si ya existe el usuario padre en la tabla `users`
                $parentUser = User::where('id_cliente', $clienteP->id)
                                  ->where('parent', 0)
                                  ->first();

                // Si no existe, crearlo.
                if (!$parentUser) {
                    $parentUser = User::create([
                        'name' => $nombreCliente,
                        'email' => $rifCliente. '@tucombustible.com',
                        'rif' => $rifCliente,
                        'ci' => $rifCliente, // Usamos el RIF como CI para el padre
                        'password' => bcrypt(Str::random(10)), // Generar una contraseña aleatoria
                        'id_cliente' => $clienteP->id,
                        'parent' => 0,
                    ]);
                    $this->info("Creado usuario padre para el RIF: {$rifCliente}");
                }
                $cliente = Cliente::where('rif', $rifCliente)
                                  ->where('nombre', $nombreCliente)
                                  ->first();

                // 3. Crear el usuario autorizado, asociándolo al padre
                User::firstOrCreate(
                    ['ci' => $ciAutorizado],
                    [
                        'name' => $nombreAutorizado,
                        'email' => $ciAutorizado . '@tucombustible.com', // Generar un email único
                        'rif' => $rifCliente,
                        'password' => bcrypt(123456789),
                        'id_cliente' => $cliente->id,
                        'parent' => $parentUser->id,
                    ]
                );

                DB::commit();
                $importedCount++;

            } catch (\Exception $e) {
                DB::rollBack();
                $this->error("Error al procesar la línea {$lineCount}: " . $e->getMessage());
            }
        });

        $this->info("Importación completada. Se procesaron {$lineCount} líneas y se importaron {$importedCount} usuarios.");

        fclose($file);
        return 0;
    }

    /**
     * Un generador para leer el archivo CSV.
     */
    protected function readCsv($file, $header)
    {
        while (($row = fgetcsv($file)) !== false) {
            if (count($row) === count($header)) {
                yield $row;
            } else {
                // Manejar filas con un número incorrecto de columnas, si es necesario.
                $this->warn("Línea con formato incorrecto. Ignorando.");
            }
        }
    }
}