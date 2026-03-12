<?php

namespace App\View\Components\Layouts;

use Illuminate\View\Component;
use Illuminate\Support\Facades\Auth;
use App\Models\Modulo;
use App\Models\Acceso;

class Sidebar extends Component
{
    /**
     * Create a new component instance.
     *
     * @return void
     */

    public $menuItems = [];
    public $isCliente = false;
    public $pasoActual = 1;

    public function __construct()
    {
        $user = Auth::user();
        if (!$user) return;

        // Determinamos si es un cliente (Perfil 3 es Cliente)
        $this->isCliente = ($user->id_perfil == 3);

        if ($this->isCliente) {
            // Obtenemos el registro de la tabla clientes vinculado al usuario
            // Asumiendo que existe la relación en el modelo User
            $this->pasoActual = $user->cliente->registro_paso ?? 1;
            $this->menuItems = $this->getWorkflowSteps();
        } else {
            // Superadmin (1) o administradores (2)
            $this->menuItems = $this->getAdminModules($user);
        }
    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return \Illuminate\Contracts\View\View|\Closure|string
     */
    
    private function getWorkflowSteps()
    {
        // Aquí definimos los 10 pasos para el Workflow del Cliente
        return [
            ['orden' => 1, 'nombre' => 'Registro Inicial', 'icono' => 'fas fa-user-edit'],
            ['orden' => 2, 'nombre' => 'Envío de Planillas', 'icono' => 'fas fa-file-download'],
            ['orden' => 3, 'nombre' => 'Recepción de Adjuntos', 'icono' => 'fas fa-file-upload'],
            ['orden' => 4, 'nombre' => 'Revisión ImporDiesel', 'icono' => 'fas fa-search'],
            ['orden' => 5, 'nombre' => 'Carpeta Ministerio', 'icono' => 'fas fa-folder-open'],
            ['orden' => 6, 'nombre' => 'Espera Ministerio', 'icono' => 'fas fa-clock'],
            ['orden' => 7, 'nombre' => 'Fecha de Inspección', 'icono' => 'fas fa-calendar-check'],
            ['orden' => 8, 'nombre' => 'Estudio de Inspección', 'icono' => 'fas fa-microscope'],
            ['orden' => 9, 'nombre' => 'Litros Aprobados', 'icono' => 'fas fa-gas-pump'],
            ['orden' => 10, 'nombre' => 'Activación Final', 'icono' => 'fas fa-check-double'],
        ];
    }

    private function getAdminModules($user)
    {
        // Aquí va la lógica original de tu compañero para el Admin
        if ($user->id_perfil == 1) {
            return Modulo::where('id_padre', 0)->where('visible', 1)->orderBy('orden')->get();
        }
        // ... (resto de lógica de permisos)
    }

    public function render()
    {
        return view('components.layouts.sidebar');
    }
}
