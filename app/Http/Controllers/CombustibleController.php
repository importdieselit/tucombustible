<?php

namespace App\Http\Controllers;

use App\Services\CombustibleService;
use Illuminate\Http\Request;
use Exception;

class CombustibleController extends Controller
{
    protected $combustibleService;

    public function __construct(CombustibleService $combustibleService)
    {
        $this->combustibleService = $combustibleService;
    }

    /**
     * 🆕 Muestra la pantalla principal / Dashboard del módulo de combustibles
     */
    public function index()
    {
        return view('combustibles.dashboard');
    }
}