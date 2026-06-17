<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddColumnTurnoToProcessedFilesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
       Schema::table('processed_files', function (Blueprint $table) {
            $table->string('turno', 20)->after('report_date'); // 'Matutino' o 'Vespertino'
            $table->unique(['report_date', 'turno'], 'unique_report_date_turno');
        });

        Schema::table('report_records', function (Blueprint $table) {
            $table->string('turno', 20)->after('report_date'); // Facilita las consultas directas
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('processed_files', function (Blueprint $table) {
            $table->dropIndex('unique_report_date_turno');
            $table->dropColumn('turno');
        });
        Schema::table('report_records', function (Blueprint $table) {
            $table->dropColumn('turno');
        });
    
    }
}
