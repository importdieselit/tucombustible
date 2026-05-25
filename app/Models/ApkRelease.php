<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ApkRelease extends Model {    
    protected $table = 'apk_releases';
    protected $primaryKey = 'id';
    protected $fillable = ['version_code', 'version_name', 'file_path'];
}
