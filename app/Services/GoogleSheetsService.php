<?php

namespace App\Services;

use Google\Client;
use Google\Service\Sheets;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;


class GoogleSheetService
{
    
    protected $sheets;

    public function __construct()
    {
        $client = new Client();
        // Ruta al archivo JSON de credenciales de tu Service Account
        $client->setAuthConfig(storage_path('app/google/google-service-account.json'));
        $client->addScope(Sheets::SPREADSHEETS_READONLY);

        $this->sheets = new Sheets($client);
    }

    /**
     * Obtiene las filas de un Sheet específico
     */
    public function getSheetData(string $spreadsheetId, string $range)
    {
        try {
            $response = $this->sheets->spreadsheets_values->get($spreadsheetId, $range);
            return $response->getValues();
        } catch (\Exception $e) {
            Log::error("Error consultando Google Sheets: " . $e->getMessage());
            return null;
        }
    }
}