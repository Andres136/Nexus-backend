<?php

namespace App\Http\Controllers\Crm;

use App\Http\Controllers\Controller;
use App\Models\Crm\Sede;
use Illuminate\Http\Request;

class SedeController extends Controller
{
    public function index()
{
    $sedes = Sede::select('id', 'nombre')->orderBy('nombre')->get();
    return response()->json($sedes);
}
}
