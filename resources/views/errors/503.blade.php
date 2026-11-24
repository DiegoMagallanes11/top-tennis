@extends('errors.layout')
@section('titulo', 'Actualización en curso')
@section('contenido')
    <div class="code">503</div>
    <h1 class="titulo">Actualización en curso</h1>
    <p class="mensaje">Estamos actualizando el sistema de reservas. Intenta de nuevo cuando el club termine la actualización. Ninguna reserva se confirmó desde esta pantalla.</p>
    <a href="{{ url('/') }}" class="btn">Ir al inicio</a>
@endsection
