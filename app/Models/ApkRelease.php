<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ApkRelease extends Model {
    public static $table = 'apk_releases';
    public static $primaryKey = 'id';
    protected $fillable = ['version_code', 'version_name', 'file_path'];
}
