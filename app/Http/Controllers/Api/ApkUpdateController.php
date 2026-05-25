<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ApkRelease;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;

class ApkUpdateController extends Controller {
    public function getLatestVersion(): JsonResponse {
        // Obtenemos el registro con el versionCode más alto
        $latest = ApkRelease::orderBy('version_code', 'desc')->first();

        if (!$latest) {
            return response()->json(['latest_version' => 0], 200);
        }

        return response()->json([
            'latest_version' => $latest->version_code,
            'version_name'   => $latest->version_name,
            'apk_url'        => asset('storage/' . $latest->file_path), // URL directa de descarga
        ], 200);
    }
}