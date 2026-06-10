<?php

namespace App\Services;

use Google\Client;
use Google\Service\Sheets;
use Illuminate\Support\Facades\Log;

class GoogleSheetsService
{
    protected $sheets;
    protected $spreadsheetId;

    public function __construct()
    {
        $this->spreadsheetId = config('services.google.spreadsheet_id');
        
        $client = new Client();
        $client->setAuthConfig(config('services.google.credentials'));
        $client->addScope(Sheets::SPREADSHEETS_READONLY);

        $this->sheets = new Sheets($client);
    }

    /**
     * Recupera las filas de un rango específico de la hoja de cálculo activa.
     */
    public function getRange(string $range): ?array
    {
        try {
            $response = $this->sheets->spreadsheets_values->get($this->spreadsheetId, $range);
            return $response->getValues();
        } catch (\Exception $e) {
            Log::error("Error en GoogleSheetsService@getRange: " . $e->getMessage());
            return null;
        }
    }
}