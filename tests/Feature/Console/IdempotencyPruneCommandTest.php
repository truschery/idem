<?php

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Truschery\Idem\Config\IdempotencyConfig;
use Truschery\Idem\Console\IdempotencyPruneCommand;
use Truschery\Idem\Enums\Status;

describe('Idempotency Prune Command', function () {

    beforeEach(function () {
        $this->config = $this->app->make(IdempotencyConfig::class);
        Carbon::setTestNow('2026-05-25');

        DB::table($this->config->databaseTable)->insert([
            'key' => 'expired-key',
            'response' => null,
            'hash' => null,
            'expires_at' => Carbon::now()->subSeconds($this->config->databaseTtl)->timestamp,
            'status' => Status::COMPLETED->value,
        ]);

        DB::table($this->config->databaseTable)->insert([
            'key' => 'valid-key',
            'response' => null,
            'hash' => null,
            'expires_at' => Carbon::now()->addSeconds($this->config->databaseTtl + 1)->timestamp,
            'status' => Status::PROCESSING->value,
        ]);
    });

    it('can successfully prune expired idempotency records', function () {

        $this->artisan(IdempotencyPruneCommand::class)
            ->assertSuccessful();

        $this->assertDatabaseHas($this->config->databaseTable, [
            'key' => 'valid-key',
        ]);

        $this->assertDatabaseMissing($this->config->databaseTable, [
            'key' => 'expired-key',
        ]);
    });

});
