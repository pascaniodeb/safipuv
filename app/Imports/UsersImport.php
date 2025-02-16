<?php

namespace App\Imports;

use Spatie\Permission\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Illuminate\Contracts\Queue\ShouldQueue; // Para manejar la importación en segundo plano
use Illuminate\Support\Facades\Log;

class UsersImport implements ToModel, WithHeadingRow, ShouldQueue, WithBatchInserts, WithChunkReading
{
    public function batchSize(): int
    {
        return 10; // Inserta 100 filas por lote
    }

    public function chunkSize(): int
    {
        return 10; // Lee 100 filas a la vez
    }

    public function model(array $row)
    {
        Log::info('Procesando fila:', $row);

        // Verificar si el usuario ya existe basado en su email o username
        $user = User::where('email', $row['email'])->orWhere('username', $row['username'])->first();

        if ($user) {
            // Actualizar roles si existe
            Log::info('Usuario ya existe:', ['email' => $row['email']]);

            if (!empty($row['role_group']) && !empty($row['role_name'])) {
                $role = Role::where('name', $row['role_name'])->first();

                if ($role && !$user->hasRole($role->name)) {
                    $user->assignRole($role->name);
                    Log::info('Rol asignado al usuario existente:', ['email' => $row['email'], 'role' => $role->name]);
                }
            }

            return null; // No crear un nuevo usuario
        }

        // Validar y preparar los datos del nuevo usuario
        try {
            $user = new User([
                'name' => $row['name'],
                'lastname' => $row['lastname'],
                'username' => $row['username'],
                'email' => $row['email'],
                'profile_photo' => $row['profile_photo'] ?? null,
                'password' => Hash::make($row['password'] ?? 'password'),
                'region_id' => $row['region_id'],
                'district_id' => $row['district_id'],
                'sector_id' => $row['sector_id'],
                'nationality_id' => $row['nationality_id'],
                'active' => $row['active'],
            ]);

            $user->save();

            Log::info('Usuario creado:', ['email' => $user->email]);

            // Asignar rol al nuevo usuario
            if (!empty($row['role_group']) && !empty($row['role_name'])) {
                $role = Role::where('name', $row['role_name'])->first();

                if ($role) {
                    $user->assignRole($role->name);
                    Log::info('Rol asignado al nuevo usuario:', ['email' => $user->email, 'role' => $role->name]);
                }
            }

            return $user;

        } catch (\Exception $e) {
            Log::error('Error al crear el usuario:', ['error' => $e->getMessage(), 'fila' => $row]);
            return null;
        }
    }
}