<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use RuntimeException;

abstract class TestCase extends BaseTestCase
{
    /**
     * Guard against a stale bootstrap/cache/config.php overriding phpunit.xml.
     *
     * A cached config pins DB_CONNECTION at build time and wins over phpunit's
     * <env> entries, which points RefreshDatabase at the real MySQL database.
     *
     * The framework calls refreshApplication() before setUpTraits(), so this is
     * the last point before RefreshDatabase runs `migrate:fresh`. Checking from
     * setUp() alone is worthless: parent::setUp() has dropped every table by the
     * time it returns, so it reports the loss rather than preventing it. The
     * RefreshDatabase trait declares its own beforeRefreshingDatabase(), and a
     * trait method beats an inherited one, so that hook cannot be used here.
     *
     * Throws rather than fail() so the run aborts instead of continuing.
     */
    protected function refreshApplication()
    {
        parent::refreshApplication();

        $this->assertUsesDisposableDatabase();
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->assertUsesDisposableDatabase();
    }

    private function assertUsesDisposableDatabase(): void
    {
        $connection = config('database.default');

        if ($connection === 'sqlite' && config("database.connections.{$connection}.database") === ':memory:') {
            return;
        }

        throw new RuntimeException(
            "Refusing to run tests against the [{$connection}] connection"
            .' ('.config("database.connections.{$connection}.database").'). '
            .'phpunit.xml requires sqlite/:memory: — a cached config is overriding it. '
            .'Run `php artisan optimize:clear` first. If you are running from a copy '
            .'of the project, note that a symlinked vendor/ resolves PSR-4 to the '
            .'original path and boots the original config.'
        );
    }
}
