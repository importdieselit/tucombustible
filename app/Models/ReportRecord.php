<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReportRecord extends Model {
    protected $fillable = [
        'processed_file_id', 'report_date', 'tipo', 'cuenta', 
        'descuenta', 'monto', 'campo1', 'tipo_oper', 'orden', 'reng'
    ];
}