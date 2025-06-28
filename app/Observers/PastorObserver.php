<?php

namespace App\Observers;

use App\Models\Pastor;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class PastorObserver
{
    public function created(Pastor $pastor)
    {
        // Crear el usuario automáticamente
        User::create([
            'name'           => $pastor->name,
            'lastname'       => $pastor->lastname,
            'username'       => $pastor->number_cedula,
            'email'          => $pastor->email,
            'region_id'      => $pastor->region_id,
            'district_id'    => $pastor->district_id,
            'sector_id'      => $pastor->sector_id,
            'nationality_id' => $pastor->nationality_id,
            'profile_photo'  => $pastor->photo_pastor ?? null,
            'active'         => true,
            'password'       => Hash::make('12345678'), // Contraseña por defecto
            'role_group'     => 'Sectorial',
            'role_name'      => 'Pastor',
        ]);
    }


    public function updated(Pastor $pastor)
    {
        $user = User::where('username', $pastor->number_cedula)->first();

        if ($user) {
            $user->update([
                'name'           => $pastor->name,
                'lastname'       => $pastor->lastname,
                'email'          => $pastor->email,
                'region_id'      => $pastor->region_id,
                'district_id'    => $pastor->district_id,
                'sector_id'      => $pastor->sector_id,
                'nationality_id' => $pastor->nationality_id,
                'profile_photo'  => $pastor->photo_pastor ?? null,
            ]);
        }
    }

    public function deleted(Pastor $pastor)
    {
        $user = User::where('username', $pastor->number_cedula)->first();

        if ($user) {
            $user->delete(); // soft-delete si usas SoftDeletes
        }
    }

    public function restored(Pastor $pastor)
    {
        $user = User::withTrashed()->where('username', $pastor->number_cedula)->first();

        if ($user && $user->trashed()) {
            $user->restore();
        }
    }
}