<?php

/*
 * @copyright 2018 Hilmi Erdem KEREN
 * @license MIT
 */

namespace WalkwelTech\Otp;

use WalkwelTech\Otp\Http\Middleware\Otp;
use WalkwelTech\Otp\PasswordGenerators as Generators;
use Illuminate\Routing\Router;
use Illuminate\Support\ServiceProvider;

class OtpServiceProvider extends ServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = true;

    public function boot(): void
    {
        $this->publishes([$this->configPath() => config_path('otp.php')], 'walkwel-otp');
        $this->publishes([$this->migrationPath() => database_path('migrations')], 'walkwel-otp');
        $this->publishes([$this->viewPath() => resource_path('views')], 'walkwel-otp');
    }

    /**
     * Register the otp service.
     */
    public function register(): void
    {
        $service = $this->createServiceInstance();
        $this->registerDefaultPasswordGenerators($service);

        $this->app->singleton('otp', function () use ($service) {
            return $service;
        });

        /** @var Router $router */
        $router = $this->app['router'];
        $router->aliasMiddleware('otp', Otp::class);
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides(): array
    {
        return [
            'otp',
        ];
    }

    /**
     * Create a new otp service instance.
     *
     * @return OtpService
     */
    private function createServiceInstance(): OtpService
    {
        return new OtpService(
            new PasswordGeneratorManager(),
            new Encryptor(config('app.secret')),
            config('otp.password_generator', 'string'),
            6,
            Token::class
        );
    }

    /**
     * Register default password generators to the
     * given otp service instance.
     *
     * @param OtpService $service
     */
    private function registerDefaultPasswordGenerators($service): void
    {
        $service->addPasswordGenerator('string', Generators\StringPasswordGenerator::class);
        $service->addPasswordGenerator('numeric', Generators\NumericPasswordGenerator::class);
        $service->addPasswordGenerator('numeric-no-0', Generators\NumericNo0PasswordGenerator::class);
    }

    /**
     * Get the project config path.
     *
     * @return string
     */
    private function configPath()
    {
        return __DIR__.'/../config/otp.php';
    }

    /**
     * Get the migration path.
     *
     * @return string
     */
    private function migrationPath()
    {
        return __DIR__.'/../database/migrations/';
    }

    /**
     * Get the view path.
     *
     * @return string
     */
    private function viewPath()
    {
        return __DIR__.'/../views/';
    }
}
