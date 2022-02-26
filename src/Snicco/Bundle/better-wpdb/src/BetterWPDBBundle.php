<?php

declare(strict_types=1);


namespace Snicco\Bundle\BetterWPDB;

use Snicco\Component\BetterWPDB\BetterWPDB;
use Snicco\Component\BetterWPDB\QueryLogger;
use Snicco\Component\Kernel\Bundle;
use Snicco\Component\Kernel\Configuration\WritableConfig;
use Snicco\Component\Kernel\Kernel;
use Snicco\Component\Kernel\ValueObject\Environment;

final class BetterWPDBBundle implements Bundle
{

    public const ALIAS = 'sniccowp/better-wpdb-bundle';

    public function shouldRun(Environment $env): bool
    {
        return true;
    }

    public function configure(WritableConfig $config, Kernel $kernel): void
    {
        //
    }

    public function register(Kernel $kernel): void
    {
        $kernel->container()->singleton(BetterWPDB::class, function () use ($kernel) {
            return BetterWPDB::fromWpdb($kernel->container()[QueryLogger::class] ?? null);
        });
    }

    public function bootstrap(Kernel $kernel): void
    {
        //
    }

    public function alias(): string
    {
        return self::ALIAS;
    }
}