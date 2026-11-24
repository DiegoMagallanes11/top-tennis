<?php

use App\Enums\Rol;
use App\Models\User;
use Database\Seeders\DemoSeeder;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;

require dirname(__DIR__, 2).'/vendor/autoload.php';
$app = require dirname(__DIR__, 2).'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
if (! app()->environment('testing') || config('database.default') !== 'sqlite') {
    throw new RuntimeException('Solo se permite en SQLite de pruebas.');
}
if (($argv[1] ?? null) === 'cliente') {
    $password = Str::random(24).'9!';
    $user = User::create(['name' => 'Cliente de prueba del navegador', 'email' => 'client-browser@example.com', 'password' => $password]);
    echo json_encode(['email' => $user->email, 'password' => $password]);
    exit(0);
}
Artisan::call('migrate', ['--force' => true]);
Artisan::call('db:seed', ['--class' => DemoSeeder::class, '--force' => true]);
$password = Str::random(24).'9!';
$admin = new User(['name' => 'Administrador de prueba', 'email' => 'admin-browser@example.com', 'password' => $password]);
$admin->rol = Rol::Admin;
$admin->email_verified_at = now();
$admin->save();
echo json_encode(['email' => $admin->email, 'password' => $password]);
