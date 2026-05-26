<?php

namespace Truschery\Idem\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Truschery\Idem\Config\IdempotencyConfig;

class IdempotencyPruneCommand extends Command
{
    protected $signature = 'idempotency:prune';

    protected $description = 'Prune expired idempotency records from the database';

    public function handle(IdempotencyConfig $config): int
    {
        DB::table($config->databaseTable)
            ->where('expires_at', '<=', Carbon::now()->timestamp)
            ->delete();

        return self::SUCCESS;
    }
}
