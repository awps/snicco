<?php

declare(strict_types=1);

namespace Snicco\Component\Kernel;

use Generator;
use InvalidArgumentException;
use LogicException;
use Psr\Container\ContainerInterface;
use RuntimeException;
use Snicco\Component\Kernel\Configuration\ConfigCache;
use Snicco\Component\Kernel\Configuration\ConfigLoader;
use Snicco\Component\Kernel\Configuration\NullCache;
use Snicco\Component\Kernel\Configuration\ReadOnlyConfig;
use Snicco\Component\Kernel\Configuration\WritableConfig;
use Snicco\Component\Kernel\ValueObject\Directories;
use Snicco\Component\Kernel\ValueObject\Environment;

use function array_merge;
use function get_class;
use function implode;
use function sprintf;

/**
 * @psalm-suppress PropertyNotSetInConstructor
 */
final class Kernel
{

    private DIContainer $container;
    private Environment $env;
    private Directories $dirs;
    private ReadOnlyConfig $read_only_config;
    private ConfigCache $config_cache;

    private bool $booted = false;

    /**
     * @var array<string,Bundle>
     */
    private array $bundles = [];

    /**
     * @var Bootstrapper[]
     */
    private array $bootstrappers = [];

    private bool $loaded_from_cache = true;

    public function __construct(
        DIContainer $container,
        Environment $env,
        Directories $dirs,
        ConfigCache $config_cache = null
    ) {
        $this->container = $container;
        $this->env = $env;
        $this->dirs = $dirs;
        $this->config_cache = $config_cache ?: new NullCache();
        $this->container[ContainerInterface::class] = $this->container;
    }

    public function boot(): void
    {
        if ($this->booted) {
            throw new LogicException('The kernel cant be booted twice.');
        }

        $cached_config = $this->config_cache->get($this->configCacheFile(), function () {
            return $this->loadAllConfigFilesFromDisk();
        });

        $this->read_only_config = ReadOnlyConfig::fromArray($cached_config);

        if ($this->loaded_from_cache) {
            $this->setBundlesAndBootstrappers(
                $this->read_only_config->getArray('bundles'),
                $this->read_only_config->getListOfStrings('app.bootstrappers')
            );
        }

        $this->registerBundles();

        $this->container->lock();

        $this->bootBundles();

        $this->booted = true;
    }

    public function env(): Environment
    {
        return $this->env;
    }

    public function container(): DIContainer
    {
        return $this->container;
    }

    public function directories(): Directories
    {
        return $this->dirs;
    }

    public function config(): ReadOnlyConfig
    {
        if (!isset($this->read_only_config)) {
            throw new LogicException(
                'The applications config can only be accessed after bootstrapping.'
            );
        }
        return $this->read_only_config;
    }

    public function usesBundle(string $alias): bool
    {
        return isset($this->bundles[$alias]);
    }

    private function loadAllConfigFilesFromDisk(): array
    {
        $config_dir = $this->dirs->configDir();

        $loaded_config = (new ConfigLoader())($this->dirs->configDir());
        $writable_config = WritableConfig::fromArray($loaded_config);

        if (!$writable_config->has('app')) {
            throw new InvalidArgumentException(
                "The [app.php] config file was not found in the config dir [$config_dir]."
            );
        }
        if (!$writable_config->has('app.bootstrappers')) {
            $writable_config->set('app.bootstrappers', []);
        }
        if (!$writable_config->has('bundles')) {
            $writable_config->set('bundles', []);
        }

        $this->setBundlesAndBootstrappers(
            $writable_config->getArray('bundles'),
            $writable_config->getListOfStrings('app.bootstrappers')
        );

        foreach ($this->bundles as $bundle) {
            $bundle->configure($writable_config, $this);
        }

        foreach ($this->bootstrappers as $bootstrapper) {
            $bootstrapper->configure($writable_config, $this);
        }

        $this->loaded_from_cache = false;

        return $writable_config->toArray();
    }

    private function registerBundles(): void
    {
        foreach ($this->bundles as $bundle) {
            $bundle->register($this);
        }
        foreach ($this->bootstrappers as $bootstrapper) {
            $bootstrapper->register($this);
        }
    }

    private function bootBundles(): void
    {
        foreach ($this->bundles as $bundle) {
            $bundle->bootstrap($this);
        }
        foreach ($this->bootstrappers as $bootstrapper) {
            $bootstrapper->bootstrap($this);
        }
    }

    private function configCacheFile(): string
    {
        return $this->dirs->cacheDir() . '/' . $this->env->asString() . '.config.php';
    }

    /**
     * @param array{all?: class-string<Bundle>[], prod?: class-string<Bundle>[], testing?: class-string<Bundle>[], dev?: class-string<Bundle>[]} $bundles
     * @psalm-return Generator<Bundle>
     */
    private function bundlesInCurrentEnv(array $bundles): Generator
    {
        $env = $bundles[$this->env->asString()] ?? [];
        $all = $bundles[Environment::ALL] ?? [];

        foreach (array_merge($all, $env) as $bundle) {
            yield new $bundle();
        }
    }

    private function addBundle(Bundle $bundle): void
    {
        if (!$bundle->shouldRun($this->env)) {
            return;
        }

        $alias = $bundle->alias();

        if (isset($this->bundles[$alias])) {
            throw new RuntimeException(
                sprintf(
                    "2 bundles in your application share the same alias [$alias].\nAffected [%s]",
                    implode(',', [get_class($this->bundles[$alias]), get_class($bundle)])
                )
            );
        }
        $this->bundles[$alias] = $bundle;
    }

    /**
     * @param class-string<Bootstrapper>[] $bootstrappers
     * @psalm-return Generator<Bootstrapper>
     */
    private function bootstrappersInCurrentEnv(array $bootstrappers): Generator
    {
        foreach ($bootstrappers as $class) {
            $bootstrapper = new $class();
            if ($bootstrapper->shouldRun($this->env)) {
                yield $bootstrapper;
            }
        }
    }

    private function addBootstrapper(Bootstrapper $bootstrapper): void
    {
        $this->bootstrappers[] = $bootstrapper;
    }

    private function setBundlesAndBootstrappers(array $bundles, array $bootstrappers): void
    {
        /** @var array{all?: class-string<Bundle>[], prod?: class-string<Bundle>[], testing?: class-string<Bundle>[], dev?: class-string<Bundle>[]} $bundles */
        foreach ($this->bundlesInCurrentEnv($bundles) as $bundle) {
            $this->addBundle($bundle);
        }

        /** @var class-string<Bootstrapper>[] $bootstrappers */
        foreach ($this->bootstrappersInCurrentEnv($bootstrappers) as $bootstrapper) {
            $this->addBootstrapper($bootstrapper);
        }
    }

}