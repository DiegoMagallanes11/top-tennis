<?php

use App\Models\User;
use App\Services\GestorReservas;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

// Solo lo usa la prueba de concurrencia, con una base temporal separada.
require dirname(__DIR__, 2).'/vendor/autoload.php';
foreach (['APP_ENV' => 'testing', 'DB_CONNECTION' => 'sqlite', 'DB_DATABASE' => $argv[1], 'DB_URL' => '', 'CACHE_STORE' => 'array', 'SESSION_DRIVER' => 'array', 'MAIL_MAILER' => 'array', 'MAIL_NOTIFICATIONS_ENABLED' => 'false'] as $key => $value) {
    putenv($key.'='.$value);
    $_ENV[$key] = $value;
    $_SERVER[$key] = $value;
}
$app = require dirname(__DIR__, 2).'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
DB::statement('PRAGMA busy_timeout = 10000');
$user = User::findOrFail((int) $argv[2]);
Auth::login($user);
file_put_contents($argv[4].'.'.$argv[2], 'ready');
$deadline = microtime(true) + 15;
while (! file_exists($argv[4]) && microtime(true) < $deadline) {
    usleep(10000);
}
if (! file_exists($argv[4])) {
    fwrite(STDERR, 'Barrier timeout');
    exit(2);
}
try {
    $r = $app->make(GestorReservas::class)->crear($user, ['horario_id' => (int) $argv[3], 'metodo_pago' => 'Efectivo']);
    echo json_encode(['ok' => true, 'id' => $r->id]);
} catch (ValidationException $e) {
    echo json_encode(['ok' => false, 'conflict' => true]);
} catch (Throwable $e) {
    fwrite(STDERR, get_class($e).': '.$e->getMessage());
    exit(3);
}
