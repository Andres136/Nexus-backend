@extends('layouts.email')

@section('content')
    <h1>Hola, {{ $nombre }}</h1>

    <p>Se te ha asignado una nueva tarea:</p>
    <ul>
        <li><strong>Título:</strong> {{ $tarea->nombre }}</li>
        <li><strong>Numero:</strong> {{ $tarea->id }}</li>
        <li><strong>Fecha límite:</strong> {{ \Carbon\Carbon::parse($tarea->fecha_fin)->format('Y-m-d') }}</li>
    </ul>

    <h3>📝 Descripción</h3>
    <p>{{ $tarea->descripcion }}</p>

   
@endsection
