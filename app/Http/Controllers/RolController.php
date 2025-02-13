<?php

namespace App\Http\Controllers;

use App\Models\Roles;
use Illuminate\Http\Request;

class RolController extends Controller
{
    //

    public function index()
    {
        //
        $roles = Roles::all();
        return response()->json($roles);

    }
}
