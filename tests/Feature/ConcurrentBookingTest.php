<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ConcurrentBookingTest extends TestCase
{
    public function test_two_real_processes_cannot_book_overlapping_slots_on_one_court(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'tt-concurrent-');
        $barrier = $path.'.start';
        $processes = [];
        $pipes = [];
        try {
            config(['database.connections.concurrent' => array_replace(config('database.connections.sqlite'), ['database' => $path, 'url' => null])]);
            Artisan::call('migrate', ['--database' => 'concurrent', '--force' => true]);
            $db = DB::connection('concurrent');
            foreach ([1, 2] as $id) {
                $db->table('users')->insert(['id' => $id, 'name' => 'Cliente de prueba '.$id, 'email' => 'concurrent'.$id.'@example.com', 'password' => Hash::make('UnusedRandom123!'), 'rol' => 'cliente', 'activo' => true]);
            }
            $db->table('canchas')->insert(['id' => 1, 'nombre' => 'Cancha temporal', 'tipo_superficie' => 'Arcilla', 'estado_mantenimiento' => 'operativa']);
            $db->table('tarifas')->insert(['id' => 1, 'nombre_tarifa' => 'Temporal', 'precio' => 50]);
            foreach ([1, 2] as $id) {
                $inicio = now()->addDays(2)->setTime(10, 0)->addMinutes(($id - 1) * 15);
                $db->table('horarios')->insert(['id' => $id, 'cancha_id' => 1, 'tarifa_id' => 1, 'hora_inicio' => $inicio->format('Y-m-d H:i:s'), 'hora_fin' => $inicio->copy()->addHour()->format('Y-m-d H:i:s'), 'estado' => 'disponible']);
            }
            foreach ([1, 2] as $id) {
                $command = [PHP_BINARY, '-d', 'extension=mbstring', '-d', 'extension=pdo_sqlite', base_path('tests/support/booking-worker.php'), $path, (string) $id, (string) $id, $barrier];
                $processes[$id] = proc_open($command, [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes[$id], base_path(), null, ['bypass_shell' => true]);
                $this->assertIsResource($processes[$id]);
                fclose($pipes[$id][0]);
            }
            $deadline = microtime(true) + 15;
            while ((! file_exists($barrier.'.1') || ! file_exists($barrier.'.2')) && microtime(true) < $deadline) {
                usleep(10000);
            }
            $this->assertFileExists($barrier.'.1');
            $this->assertFileExists($barrier.'.2');
            file_put_contents($barrier, 'go');
            $results = [];
            foreach ([1, 2] as $id) {
                $output = stream_get_contents($pipes[$id][1]);
                $error = stream_get_contents($pipes[$id][2]);
                fclose($pipes[$id][1]);
                fclose($pipes[$id][2]);
                $exit = proc_close($processes[$id]);
                unset($processes[$id]);
                $this->assertSame(0, $exit, $error);
                $results[] = json_decode($output, true, 512, JSON_THROW_ON_ERROR);
            }
            $this->assertSame(1, count(array_filter($results, fn ($r) => $r['ok'])));
            $this->assertSame(1, $db->table('reservas')->count());
            $this->assertSame(1, $db->table('horarios')->where('estado', 'reservado')->count());
        } finally {
            foreach ($processes as $p) {
                if (is_resource($p)) {
                    proc_terminate($p);
                    proc_close($p);
                }
            }
            DB::purge('concurrent');
            foreach ([$path, $path.'-wal', $path.'-shm', $barrier, $barrier.'.1', $barrier.'.2'] as $file) {
                if (file_exists($file)) {
                    unlink($file);
                }
            }
        }
    }
}
