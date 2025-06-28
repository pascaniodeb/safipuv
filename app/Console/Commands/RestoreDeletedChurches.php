<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Church;

class RestoreDeletedChurches extends Command
{
    protected $signature = 'churches:restore-all';
    protected $description = 'Restaura todas las iglesias eliminadas con SoftDeletes, sin disparar eventos.';

    public function handle(): int
    {
        $total = Church::onlyTrashed()->count();

        if ($total === 0) {
            $this->info('No hay iglesias eliminadas para restaurar.');
            return Command::SUCCESS;
        }

        // Restaurar sin disparar eventos
        Church::withoutEvents(function () {
            Church::onlyTrashed()->restore();
        });

        $this->info("✅ Se han restaurado $total iglesias correctamente.");

        return Command::SUCCESS;
    }
}