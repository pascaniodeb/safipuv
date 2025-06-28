<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Pastor;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class SyncPastoresAUsuarios extends Command
{
    protected $signature = 'sync:pastores-a-usuarios';
    protected $description = 'Sincroniza los pastores existentes creando o actualizando usuarios asociados';

    public function handle()
    {
        $pastores = Pastor::all();
        $creados = 0;
        $actualizados = 0;
        $duplicados = 0;

        foreach ($pastores as $pastor) {
            $username = $pastor->number_cedula;
            $email = $pastor->email;

            // Si el email ya está en uso por otro usuario (distinto username), evitar crear
            $emailDuplicado = User::where('email', $email)->where('username', '!=', $username)->exists();

            if ($emailDuplicado) {
                $this->warn("⚠️ Email duplicado para {$pastor->name} {$pastor->lastname} ({$email}), se omitió el usuario {$username}.");
                $duplicados++;
                continue;
            }

            $user = User::where('username', $username)->first();

            if (! $user) {
                // Crear nuevo usuario
                User::create([
                    'name'           => $pastor->name,
                    'lastname'       => $pastor->lastname,
                    'username'       => $username,
                    'email'          => $email,
                    'region_id'      => $pastor->region_id,
                    'district_id'    => $pastor->district_id,
                    'sector_id'      => $pastor->sector_id,
                    'nationality_id' => $pastor->nationality_id,
                    'profile_photo'  => $pastor->photo_pastor ?? null,
                    'active'         => true,
                    'password'       => Hash::make('12345678'),
                    'role_group'     => 'Sectorial',
                    'role_name'      => 'Pastor',
                ]);

                $creados++;
                $this->info("✅ Usuario creado: {$username}");
                continue;
            }

            // Verificar si hay datos desincronizados
            $needsUpdate = (
                $user->name           !== $pastor->name ||
                $user->lastname       !== $pastor->lastname ||
                $user->email          !== $email ||
                $user->region_id      !== $pastor->region_id ||
                $user->district_id    !== $pastor->district_id ||
                $user->sector_id      !== $pastor->sector_id ||
                $user->nationality_id !== $pastor->nationality_id ||
                $user->profile_photo  !== ($pastor->photo_pastor ?? null)
            );

            if ($needsUpdate) {
                $user->update([
                    'name'           => $pastor->name,
                    'lastname'       => $pastor->lastname,
                    'email'          => $email,
                    'region_id'      => $pastor->region_id,
                    'district_id'    => $pastor->district_id,
                    'sector_id'      => $pastor->sector_id,
                    'nationality_id' => $pastor->nationality_id,
                    'profile_photo'  => $pastor->photo_pastor ?? null,
                ]);

                $actualizados++;
                $this->info("🔄 Usuario actualizado: {$username}");
            }
        }

        $this->line('---');
        $this->info("Usuarios creados: $creados");
        $this->info("Usuarios actualizados: $actualizados");
        $this->info("Usuarios omitidos por email duplicado: $duplicados");
        $this->info("✅ Sincronización completa.");
    }
}