<?php

declare(strict_types=1);

namespace SunnyPHP;

use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use SunnyPHP\Aop\AspectManager;
use SunnyPHP\Auth\AuthManager;
use SunnyPHP\Auth\DatabaseUserProvider;
use SunnyPHP\Auth\UserProvider;
use SunnyPHP\Cache\CacheManager;
use SunnyPHP\Config\Repository;
use SunnyPHP\Container\Container;
use SunnyPHP\Database\Connection;
use SunnyPHP\Database\DatabaseManager;
use SunnyPHP\Database\Model;
use SunnyPHP\Exception\Handler;
use SunnyPHP\Facade\Facade;
use SunnyPHP\Http\Kernel;
use SunnyPHP\Http\Request;
use SunnyPHP\Http\ResponseEmitter;
use SunnyPHP\Logging\Logger;
use SunnyPHP\Queue\QueueManager;
use SunnyPHP\Routing\Router;
use SunnyPHP\Session\SessionManager;
use SunnyPHP\Validation\Factory as ValidatorFactory;

final class Application
{
    public const string VERSION = '0.2.0';

    public private(set) Container $container;

    public function __construct(
        public private(set) string $basePath,
    ) {
        $this->basePath = rtrim($this->basePath, '/\\');
        $this->container = new Container();
        $this->registerBaseBindings();
    }

    public static function create(string $basePath): self
    {
        return new self($basePath);
    }

    public function container(): Container
    {
        return $this->container;
    }

    public function make(string $id, array $parameters = []): mixed
    {
        return $this->container->make($id, $parameters);
    }

    public function path(string $path = ''): string
    {
        if ($path === '') {
            return $this->basePath;
        }

        return $this->basePath
            . DIRECTORY_SEPARATOR
            . ltrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path), '/\\');
    }

    public function boot(): self
    {
        $this->loadConfig();
        $this->registerLogger();
        Facade::setFacadeApplication($this);
        $this->registerInfrastructure();
        $this->loadRoutes();

        $timezone = (string) $this->container->get(Repository::class)->get('app.timezone', 'UTC');
        date_default_timezone_set($timezone);

        return $this;
    }

    public function run(): void
    {
        $kernel = $this->container->make(Kernel::class);
        $response = $kernel->handle(Request::capture());
        $this->container->make(ResponseEmitter::class)->emit($response);
    }

    private function registerBaseBindings(): void
    {
        $this->container->instance(self::class, $this);
        $this->container->instance(Container::class, $this->container);
        $this->container->instance(ContainerInterface::class, $this->container);
        $this->container->singleton(Handler::class, Handler::class);
        $this->container->singleton(Kernel::class, Kernel::class);
        $this->container->singleton(ResponseEmitter::class, ResponseEmitter::class);
    }

    private function loadConfig(): void
    {
        $items = [];
        $configDir = $this->path('config');

        if (is_dir($configDir)) {
            foreach (glob($configDir . DIRECTORY_SEPARATOR . '*.php') ?: [] as $file) {
                $items[basename($file, '.php')] = require $file;
            }
        }

        $this->container->instance(Repository::class, new Repository($items));
    }

    private function registerLogger(): void
    {
        $logger = new Logger($this->path('storage/logs/sunny.log'));
        $this->container->instance(Logger::class, $logger);
        $this->container->instance(LoggerInterface::class, $logger);
    }

    private function registerInfrastructure(): void
    {
        $this->container->singleton(DatabaseManager::class, DatabaseManager::class);
        $this->container->singleton(Connection::class, fn (Container $c): Connection => $c->make(DatabaseManager::class)->connection());
        $this->container->singleton(CacheManager::class, CacheManager::class);
        $this->container->singleton(SessionManager::class, SessionManager::class);
        $this->container->singleton(ValidatorFactory::class, ValidatorFactory::class);
        $this->container->singleton(UserProvider::class, DatabaseUserProvider::class);
        $this->container->singleton(AuthManager::class, AuthManager::class);
        $this->container->singleton(QueueManager::class, QueueManager::class);
        $this->container->singleton(AspectManager::class, AspectManager::class);

        Model::useDatabase($this->container->make(DatabaseManager::class));
        $this->container->enableAop($this->container->make(AspectManager::class));
    }

    private function loadRoutes(): void
    {
        $router = new Router();
        $this->container->instance(Router::class, $router);

        $file = $this->path('routes/web.php');
        if (is_file($file)) {
            (static function (string $file, Router $router): void {
                require $file;
            })($file, $router);
        }
    }
}
