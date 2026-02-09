<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;

class IseedAllTables extends Command
{
    protected $signature = 'iseed:all';
    protected $description = 'Genera seeders para todas las tablas de la base de datos de forma automática';

    public function handle()
    {
        $dbName = env('DB_DATABASE');
        
        $tables = DB::select('SHOW TABLES');
        $key = "Tables_in_{$dbName}";

        $tableNames = collect($tables)->map(function ($table) use ($key) {
            return $table->$key;
        })->reject(function ($name) {
            return $name === 'migrations';
        })->implode(',');

        $this->info("Procesando tablas: {$tableNames}");

        Artisan::call("iseed {$tableNames} --force");

        $this->info("¡Proceso completado! Seeders generados en database/seeders");
    }
}