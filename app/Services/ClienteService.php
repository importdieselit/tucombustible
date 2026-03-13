<?php

namespace App\Services;

use App\Repositories\ClienteRepository;
use App\Models\Cliente;
use Illuminate\Support\Facades\{DB, Hash, Storage, Log};
use Illuminate\Support\Str;

class ClienteService
{
    protected $repository;

    public function __construct(ClienteRepository $repository)
    {
        $this->repository = $repository;
    }

    public function registrarCliente(array $data)
    {
        return DB::transaction(function () use ($data) {
            $rifOficial = strtoupper($data['rif']);
            $rifLimpio = str_replace(['-', ' '], '', $rifOficial);
            
            $servicios = [];
            if (!empty($data['litros_diesel']) && $data['litros_diesel'] > 0) $servicios[] = 'Diesel';
            if (!empty($data['litros_mgo']) && $data['litros_mgo'] > 0) $servicios[] = 'MGO';
            $tipoServicioTexto = implode(' y ', $servicios);

            // Determinar el parent_id basándose en el token_registro proporcionado
            $parentId = 0;
            if (isset($data['tipo_cliente']) && $data['tipo_cliente'] === 'sucursal' && !empty($data['token_padre'])) {
                // Buscamos al padre usando la columna token_registro
                $padre = Cliente::where('token_registro', strtoupper($data['token_padre']))->first();
                
                if (!$padre) {
                    throw new \Exception("El código de empresa principal (Token) no es válido o no existe.");
                }
                $parentId = $padre->id;
            }

            // Crear el cliente con el parent_id (0 si es padre, ID del padre si es sucursal)
            $cliente = $this->repository->create([
                'nombre'              => strtoupper($data['nombre']), 
                'rif'                 => $rifOficial,
                'contacto'            => strtoupper($data['contacto']),
                'email'               => $data['email'],
                'telefono'            => $data['telefono'],
                'estado_id'           => $data['estado_id'],
                'ciudad_id'           => $data['ciudad_id'],
                'direccion_operativa' => $data['direccion_operativa'],
                'tipo_servicio'       => $tipoServicioTexto,
                'registro_paso'       => 1,
                'status'              => 0,
                'parent'              => $parentId,
                'token_registro'      => strtoupper(Str::random(10)), // Genera su propio token para futuras sucursales propias
            ]);

            // Crear usuario asociado
            $this->repository->crearUsuario([
                'name'                 => strtoupper($data['contacto']),
                'email'                => $data['email'],
                'password'             => Hash::make($rifLimpio), 
                'id_perfil'            => 3,
                'cliente_id'           => $cliente->id,
                'status_usuario'       => 'en_registro',
                'must_change_password' => 1
            ]);

            return $cliente;
        });
    }

    public function registrarActivosAprobados($clienteId, array $placas, array $choferes)
    {
        return DB::transaction(function () use ($clienteId, $placas, $choferes) {
            foreach ($placas as $placa) {
                if (!empty($placa)) {
                    DB::table('placas_vehiculos')->updateOrInsert(
                        ['cliente_id' => $clienteId, 'placa' => strtoupper(str_replace(' ', '', $placa))],
                        ['activo' => 1, 'updated_at' => now()]
                    );
                }
            }

            foreach ($choferes as $chofer) {
                if (!empty($chofer['cedula']) && !empty($chofer['nombre'])) {
                    DB::table('choferes_clientes')->updateOrInsert(
                        ['cliente_id' => $clienteId, 'cedula' => $chofer['cedula']],
                        ['nombre_completo' => strtoupper($chofer['nombre']), 'activo' => 1, 'updated_at' => now()]
                    );
                }
            }
        });
    }

    public function subirDocumentoExpediente($clienteId, $file, $tipoDocumento)
    {
        return DB::transaction(function () use ($clienteId, $file, $tipoDocumento) {
            $extension = $file->getClientOriginalExtension();
            $fileName = "{$tipoDocumento}_" . time() . "." . $extension;
            $ruta = $file->storeAs("expedientes/{$clienteId}", $fileName, 'public');

            return $this->repository->guardarDocumento([
                'cliente_id'       => $clienteId,
                'tipo_documento'   => $tipoDocumento,
                'tipo_anexo'       => $extension,
                'ruta'             => $ruta,
                'nombre_documento' => $tipoDocumento
            ]);
        });
    }

    public function enviarExpedienteARevision($clienteId)
    {
        return DB::transaction(function () use ($clienteId) {
            $conteo = \App\Models\Documento::where('cliente_id', $clienteId)
                        ->distinct('nombre_documento')
                        ->count();

            if ($conteo < 12) {
                throw new \Exception("Expediente incompleto. Debe cargar los 12 documentos obligatorios antes de enviar a revisión.");
            }

            return $this->repository->avanzarPaso($clienteId, 3);
        });
    }

    public function obtenerExpediente($id) { return $this->repository->find($id); }

    public function obtenerDashboardAdmin(array $filtros)
    {
        return [
            'clientes' => $this->repository->getClientesEnRegistro($filtros),
            'stats'    => $this->repository->getStatsGlobales(),
        ];
    }

    public function avanzarPaso($id, $paso, $extra = [])
    {
        return DB::transaction(function () use ($id, $paso, $extra) {
            return $this->repository->avanzarPaso($id, $paso, $extra);
        });
    }

    public function cambiarEstatus($id) { return $this->repository->toggleStatus($id); }
}