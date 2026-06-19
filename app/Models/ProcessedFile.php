<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProcessedFile extends Model {
    protected $fillable = ['file_name', 'report_date', 'turno'];

    public function records() {
        return $this->hasMany(ReportRecord::class);
    }
}