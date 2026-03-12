<?php

namespace App\Services;

use App\Repositories\ClienteRepository;
use Illuminate\Support\Facades\{DB, Hash, Storage};
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
                'token_registro'      => Str::random(40),
            ]);

            $this->repository->crearUsuario([
                'name'                 => strtoupper($data['contacto']),
                'email'                => $data['email'],
                'password'             => Hash::make($rifLimpio), 
                'id_perfil'            => 3,
                'cliente_id'           => $cliente->id,
                'status_usuario'       => 'en_registro',
                'must_change_password' => 1
            ]);

            if (!empty($data['litros_diesel']) && $data['litros_diesel'] > 0) {
                $this->repository->registrarCupo([
                    'cliente_id'          => $cliente->id,
                    'tipo_combustible_id' => 1,
                    'litros_solicitados'  => $data['litros_diesel'],
                    'litros_aprobados'    => 0,
                ]);
            }

            if (!empty($data['litros_mgo']) && $data['litros_mgo'] > 0) {
                $this->repository->registrarCupo([
                    'cliente_id'          => $cliente->id,
                    'tipo_combustible_id' => 2,
                    'litros_solicitados'  => $data['litros_mgo'],
                    'litros_aprobados'    => 0,
                ]);
            }

            return $cliente;
        });
    }

    public function subirDocumentoExpediente($clienteId, $file, $tipoDocumento)
    {
        return DB::transaction(function () use ($clienteId, $file, $tipoDocumento) {
            $extension = $file->getClientOriginalExtension();
            $fileName = "{$tipoDocumento}_" . time() . "." . $extension;
            $ruta = $file->storeAs("expedientes/{$clienteId}", $fileName, 'public');

            // Solo guardamos el documento. NO avanzamos el paso aquí.
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
            // Contamos documentos distintos
            $conteo = \App\Models\Documento::where('cliente_id', $clienteId)
                        ->distinct('nombre_documento')
                        ->count();

            // Validamos que estén los 12
            if ($conteo < 12) {
                throw new \Exception("Expediente incompleto. Debe cargar los 12 documentos obligatorios antes de enviar a revisión.");
            }

            // AHORA SÍ: El paso cambia a 3 SOLO cuando el cliente decide enviar
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