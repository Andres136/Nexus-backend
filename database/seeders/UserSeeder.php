<?php

namespace Database\Seeders;

use App\Models\Departamentos;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $departamento = Departamentos::first();
        User::create([
            'name'=> 'Elver',
            'email'=> 'tecnologiasetasplast@gmail.com',
            'telefono'=> '3026676033',
            'password'=> Hash::make('Setas@+10'),
            'departamento_id'=> $departamento->id,
            'role_id'=> 1,
            'estado_id'=> 1,

        ]);
    }
}
