<?php

use Illuminate\Support\Carbon;

describe('Idempotency Prune Command', function() {

    beforeEach(function() {
        $this->config = $this->app->make(\Truschery\Idem\Config\IdempotencyConfig::class);
        Carbon::setTestNow('2026-05-25');

        \Illuminate\Support\Facades\DB::table($this->config->databaseTable)->insert([
            'key' => 'expired-key',
            'response' => null,
            'hash' => null,
            'expires_at' => Carbon::now()->subSeconds($this->config->databaseTtl)->timestamp,
            'status' => \Truschery\Idem\Enums\Status::COMPLETED->value
        ]);

        \Illuminate\Support\Facades\DB::table($this->config->databaseTable)->insert([
            'key' => 'valid-key',
            'response' => null,
            'hash' => null,
            'expires_at' => Carbon::now()->addSeconds($this->config->databaseTtl + 1)->timestamp,
            'status' => \Truschery\Idem\Enums\Status::PROCESSING->value
        ]);
    });

    it('can successfully prune expired idempotency records', function() {

        $this->artisan(\Truschery\Idem\Console\IdempotencyPruneCommand::class)
        ->assertSuccessful();

        $this->assertDatabaseHas($this->config->databaseTable, [
            'key' => 'valid-key',
        ]);

        $this->assertDatabaseMissing($this->config->databaseTable, [
            'key' => 'expired-key',
        ]);
    });

});