<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class BackupClub extends Command
{
    protected $signature = 'club:backup';

    protected $description = 'Guarda una copia privada de la base configurada; no modifica ni elimina registros.';

    public function handle(): int
    {
        $dir = storage_path('app/private/backups');
        if (! is_dir($dir)) {
            mkdir($dir, 0700, true);
        }
        $name = $dir.'/club-'.now()->format('Ymd-His').'-'.bin2hex(random_bytes(3));
        $driver = DB::getDriverName();
        if ($driver === 'sqlite') {
            DB::statement('VACUUM INTO '.DB::getPdo()->quote($name.'.sqlite'));
            $this->info('Copia SQLite guardada en '.$name.'.sqlite');

            return self::SUCCESS;
        }
        if ($driver !== 'mysql') {
            $this->error('Este respaldo admite MySQL y SQLite. Usa la herramienta de tu proveedor para otro motor.');

            return self::FAILURE;
        }
        $handle = fopen($name.'.sql', 'xb');
        try {
            fwrite($handle, "-- Copia privada Top Tennis. Restaurar solo en una base vacía.\nSET FOREIGN_KEY_CHECKS=0;\n");
            DB::statement('SET TRANSACTION ISOLATION LEVEL REPEATABLE READ');
            DB::transaction(function () use ($handle) {
                foreach (DB::select('SHOW TABLES') as $tableRow) {
                    $table = array_values((array) $tableRow)[0];
                    if (! preg_match('/^[A-Za-z0-9_]+$/', $table)) {
                        throw new \RuntimeException('Nombre de tabla no admitido');
                    }
                    $create = array_values((array) DB::selectOne('SHOW CREATE TABLE `'.$table.'`'))[1];
                    fwrite($handle, $create.";\n");
                    foreach (DB::table($table)->cursor() as $row) {
                        $values = (array) $row;
                        $columns = implode(',', array_map(fn ($c) => '`'.$c.'`', array_keys($values)));
                        $quoted = implode(',', array_map(fn ($v) => $v === null ? 'NULL' : DB::getPdo()->quote((string) $v), array_values($values)));
                        fwrite($handle, 'INSERT INTO `'.$table.'` ('.$columns.') VALUES ('.$quoted.");\n");
                    }
                }
            });
            fwrite($handle, "SET FOREIGN_KEY_CHECKS=1;\n");
            fclose($handle);
            chmod($name.'.sql', 0600);
            $this->info('Copia MySQL guardada en '.$name.'.sql');

            return self::SUCCESS;
        } catch (\Throwable $e) {
            fclose($handle);
            $this->error('La copia no se completó. No uses el archivo parcial para restaurar.');
            report($e);

            return self::FAILURE;
        }
    }
}
