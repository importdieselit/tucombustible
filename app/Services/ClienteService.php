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

    public function subirDocumentoExpediente($clienteId, $file, $tipoDocumento)
    {
        return DB::transaction(function () use ($clienteId, $file, $tipoDocumento) {
            $cliente = $this->repository->find($clienteId);
            $rifCarpeta = str_replace(['-', ' '], '', $cliente->rif);

            $documentosValidos = [
                'rif_legalizado', 'documento_constitutivo', 'copia_representante_legal',
                'lista_equipos_tanques', 'croquis_ubicacion', 'constancia_bomberos'
            ];

            if (!in_array($tipoDocumento, $documentosValidos)) {
                throw new \Exception("Tipo de documento inválido.");
            }

            $fileName = "{$tipoDocumento}_" . time() . "." . $file->getClientOriginalExtension();
            $ruta = $file->storeAs("expedientes/{$rifCarpeta}", $fileName, 'public');

            $this->repository->guardarDocumento([
                'cliente_id'     => $clienteId,
                'tipo_documento' => $tipoDocumento,
                'ruta_archivo'   => $ruta
            ]);

            return ['success' => true, 'ruta' => $ruta];
        });
    }

    public function enviarExpedienteARevision($clienteId)
    {
        // Usamos el repositorio para contar, no el modelo directamente
        $conteoDocs = $this->repository->contarDocumentos($clienteId);
        
        if ($conteoDocs < 6) {
            throw new \Exception("Debe cargar los 6 documentos obligatorios.");
        }

        return $this->repository->avanzarPaso($clienteId, 3);
    }

    public function registrarCliente(array $data)
    {
        return DB::transaction(function () use ($data) {
            $parentId = 0;
            $rifLimpio = strtoupper(trim($data['rif']));

            if (isset($data['tipo_cliente']) && $data['tipo_cliente'] === 'sucursal' && !empty($data['token_padre'])) {
                $padre = $this->repository->findByToken($data['token_padre']);
                if (!$padre) throw new \Exception("Token de asociación inválido.");
                $parentId = $padre->id;
            }

            $user = $this->repository->crearUsuario([
                'name'                 => strtoupper($data['contacto']),
                'email'                => $data['email'],
                'password'             => Hash::make($rifLimpio),
                'id_perfil'            => 3,
                'status_usuario'       => 'en_registro',
                'must_change_password' => 1
            ]);

            $cliente = $this->repository->create([
                'user_id'             => $user->id,
                'nombre'              => strtoupper($data['razon_social']),
                'rif'                 => $rifLimpio,
                'contacto'            => strtoupper($data['contacto']),
                'telefono'            => $data['telefono'],
                'email'               => $data['email'],
                'estado_id'           => $data['estado_id'],
                'direccion_operativa' => strtoupper($data['direccion_operativa']),
                'parent'              => $parentId,
                'registro_paso'       => 1,
                'status'              => 0,
                'tipo_solicitud'      => $data['tipo_solicitud'],
                'tipo_servicio'       => $data['tipo_servicio'],
                'token_registro'      => ($parentId == 0) ? Str::upper(Str::random(10)) : null,
            ]);

            if (!empty($data['litros_solicitados'])) {
                $combustibleId = (strtolower($data['tipo_servicio']) === 'diesel') ? 1 : 2;
                $this->repository->registrarCupo([
                    'cliente_id' => $cliente->id,
                    'tipo_combustible_id' => $combustibleId,
                    'litros_solicitados' => $data['litros_solicitados'],
                    'litros_aprobados' => 0,
                ]);
            }

            return $cliente;
        });
    }

    public function obtenerDashboardAdmin(array $filtros)
    {
        return [
            'clientes' => $this->repository->getClientesEnRegistro($filtros),
            'stats'    => $this->repository->getStatsGlobales(),
        ];
    }

    public function cambiarEstatus($id)
    {
        return $this->repository->toggleStatus($id);
    }

    public function avanzarPaso($id, $paso, $extra = []) { return $this->repository->avanzarPaso($id, $paso, $extra); }
    public function obtenerExpediente($id) { return $this->repository->find($id); }
}