<?php

namespace App\Imports;

use App\Models\Cliente;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ClientesImport implements ToModel, WithHeadingRow
{
    /**
     * @var array Un array para almacenar el ID del cliente 'parent' para cada código CIIU.
     */
    private $ciiuParents = [];

    /**
     * @param array $row
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function model(array $row)
    {
        dd($row);
        // Limpiamos los datos de la fila, asegurando que los campos existan y no sean nulos.
        $ciiu = (int) ($row['ciiu'] ?? 0);
        $rif = (string) ($row['rif'] ?? '');
        $nombre = (string) ($row['cliente'] ?? '');
        $cupo = (float) ($row['cupo'] ?? 0.0);
        $sector = (string) ($row['sector'] ?? '');
        $disponible = (float) ($row['cantidad'] ?? 0.0);

        // Validación básica.
        if (empty($ciiu) || empty($rif)) {
            Log::warning("Fila omitida por datos de CIIU o RIF faltantes.", $row);
            return null;
        }

        // Lógica para determinar el periodo basada en las columnas del Excel.
        $periodo = 'Otro'; // Valor por defecto
        if (isset($row['diario']) && strtoupper($row['diario']) === 'SI') {
            $periodo = 'D';
        } elseif (isset($row['semanal']) && strtoupper($row['semanal']) === 'SI') {
            $periodo = 'S';
        } elseif (isset($row['mensual']) && strtoupper($row['mensual']) === 'SI') {
            $periodo = 'M';
        }
        $existingParent = Cliente::where('ciiu', $ciiu)->where('parent', 0)->first();
            if ($existingParent) {
                if(strtoupper($nombre) == strtoupper($existingParent->nombre)){
                    return null; // Si el nombre coincide, omitimos la fila.
                }
                $parent = $existingParent->id; 
            } else {
                $parent = 0; 
            }
        
        
        // Crear una nueva instancia del modelo Cliente.
        $cliente = new Cliente([
            'nombre' => $nombre,
            'contacto' => $row['responsable'] ?? null,
            'dni' => $row['dni'] ?? null,
            'telefono' => null, 
            'email' => null, 
            'rif' => $rif,
            'direccion' => $row['direccion'] ?? null,
            'disponible' => $disponible, 
            'cupo' => $cupo,
            'ciiu' => $ciiu,
            'parent' => $parent,
            'periodo' => $periodo,
            'sector' => $sector,
        ]);
        
        // Si el cliente que estamos creando es el 'parent' (parent=0), 
        // lo guardamos en nuestro array de seguimiento para futuras filas.
        if ($parent === 0 && !isset($this->ciiuParents[$ciiu])) {
            $cliente->save();
            $this->ciiuParents[$ciiu] = $cliente->id;
        }

        return $cliente;
    }
}
