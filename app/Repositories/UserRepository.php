<?php

namespace App\Repositories;

use App\Models\User;
use Illuminate\Support\Facades\DB;

class UserRepository
{
    public function getFiltrados(array $filtros, int $clienteId = 0)
    {
        $query = User::with(['perfil', 'cliente']);

        // Si no es admin global, filtramos por su empresa
        if ($clienteId !== 0) {
            $query->where('cliente_id', $clienteId);
        }

        if (!empty($filtros['search'])) {
            $search = $filtros['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        return $query->orderBy('name', 'asc')->paginate(20);
    }

    public function find($id): User
    {
        return User::with(['perfil', 'cliente', 'persona'])->findOrFail($id);
    }

    public function create(array $data): User
    {
        return User::create($data);
    }

    public function update($id, array $data): User
    {
        $user = User::findOrFail($id);
        $user->update($data);
        return $user;
    }

    public function getStatsPorPerfil(int $clienteId = 0)
    {
        $query = DB::table('users')
            ->join('perfiles', 'users.id_perfil', '=', 'perfiles.id')
            ->select('perfiles.nombre as perfil', DB::raw('COUNT(*) as total'));

        if ($clienteId !== 0) {
            $query->where('users.cliente_id', $clienteId);
        }

        return $query->groupBy('perfiles.nombre')
                     ->orderBy('total', 'desc')
                     ->get();
    }

    public function countAll(): int
    {
        return User::count();
    }
}