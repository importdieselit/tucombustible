<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IaTool extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'is_active',
        'required_roles',
        'api_endpoint',
        'http_method',
        'parameters_schema',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'required_roles' => 'array',
        'parameters_schema' => 'array',
    ];
}