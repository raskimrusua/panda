<?php

use Illuminate\Support\Facades\Cache;

beforeEach(function () {
    Cache::flush();
});

it('reloads all crop content files', function () {
    $this->artisan('crops:content:reload')
        ->expectsOutputToContain('Loading all crop content files')
        ->expectsOutputToContain('tomato (Tomato)')
        ->assertExitCode(0);
});

it('reloads a single crop by slug', function () {
    $this->artisan('crops:content:reload', ['--slug' => 'tomato'])
        ->expectsOutputToContain('Reloading single crop: tomato')
        ->assertExitCode(0);
});

it('returns FAILURE when single-slug crop does not exist', function () {
    $this->artisan('crops:content:reload', ['--slug' => 'nonexistent-xyz'])
        ->assertExitCode(1);
});

it('flushes cache before reload', function () {
    Cache::put('panda:content:crop:stale', ['old' => 'data'], 3600);
    Cache::put('panda:content:crops:all', ['stale-list'], 3600);

    $this->artisan('crops:content:reload')->assertExitCode(0);

    // The all-crops key was repopulated with current state
    expect(Cache::get('panda:content:crops:all'))->not->toBe(['stale-list']);
});
