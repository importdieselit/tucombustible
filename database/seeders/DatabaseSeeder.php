<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

class DatabaseSeeder extends Seeder
{
    /**
     *
     * @return void
     */
    public function run()
    {
        $files = File::files(database_path('seeders')); 

        foreach ($files as $file) {
            $filename = $file->getFilenameWithoutExtension();

            if ($filename !== 'DatabaseSeeder') {
                
                $className = "Database\\Seeders\\{$filename}";

                if (class_exists($className)) {
                    $this->command->info("Sembrando data de: {$filename}");
                    $this->call($className);
                }
            }
        }     
        $this->command->getOutput()->success('¡Sincronización de data completada con éxito!');
    }
}