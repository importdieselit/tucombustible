<?php

namespace App\Http\Livewire\Clientes;

use Livewire\Component;
use App\Models\Estado;
use App\Models\Ciudad;
use App\Services\ClienteService;

class RegistroInicial extends Component
{
    public $razon_social, $rif, $email, $estado_id, $ciudad_id;
    public $ciudades = [];

    // Livewire permite inyección de dependencias en el método registrar
    public function registrar(ClienteService $clienteService)
    {
        $data = $this->validate([
            'razon_social' => 'required|min:5',
            'rif' => 'required|unique:clientes,rif',
            'email' => 'required|email|unique:clientes,email',
            'estado_id' => 'required',
            'ciudad_id' => 'required',
        ]);

        $clienteService->registrarProspecto($data);

        session()->flash('message', 'Paso 1 completado con éxito.');
        // Redirección...
    }

    public function updatedEstadoId($value)
    {
        $this->ciudades = Ciudad::where('estado_id', $value)->get();
        $this->ciudad_id = null;
    }

    public function render()
    {
        return view('livewire.clientes.registro-inicial', [
            'estados' => Estado::all()
        ]);
    }
}
