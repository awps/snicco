<?php

declare(strict_types=1);

namespace Snicco\Core\Http;

use RuntimeException;
use Snicco\Core\Routing\Pipeline;
use RKA\Middleware\IpAddress;
use Snicco\Core\Contracts\Redirector;
use Snicco\Core\Routing\UrlGenerator;
use Snicco\Core\Contracts\ServiceProvider;
use Snicco\Core\Contracts\ResponseFactory;
use Snicco\Core\Controllers\ViewController;
use Snicco\Core\Controllers\RedirectController;
use Snicco\Core\Controllers\FallBackController;
use Snicco\EventDispatcher\Contracts\Dispatcher;
use Psr\Http\Message\StreamFactoryInterface as Psr17StreamFactory;
use Psr\Http\Message\ResponseFactoryInterface as Psr17ResponseFactory;

class HttpServiceProvider extends ServiceProvider
{
    
    public function register() :void
    {
        $this->bindConfig();
        $this->bindResponseEmitter();
        $this->bindKernel();
        $this->bindRedirector();
        $this->bindResponsePostProcessor();
        $this->bindIpAddressMiddleware();
        $this->bindMethodField();
        $this->bindResponseFactory();
        $this->bindResponsePreparation();
        $this->bindCoreControllers();
    }
    
    public function bootstrap() :void
    {
        //
    }
    
    private function bindRedirector()
    {
        $this->container->singleton(Redirector::class, function () {
            return new StatelessRedirector(
                $this->container[UrlGenerator::class],
                $this->container[Psr17ResponseFactory::class]
            );
        });
    }
    
    private function bindConfig()
    {
        if ( ! class_exists(IpAddress::class)) {
            return;
        }
        
        $this->config->extend('proxies.check', false);
        $this->config->extend('proxies.trust', []);
        $this->config->extend('proxies.headers', []);
    }
    
    private function bindIpAddressMiddleware()
    {
        $this->container->singleton(IpAddress::class, function () {
            $check = $this->config->get('proxies.check');
            $proxies = $this->config->get('proxies.trust');
            $headers = $this->config->get('proxies.headers');
            
            if ($check && empty($proxies)) {
                throw new RuntimeException('You have to configure trusted proxies.');
            }
            if ($check && empty($headers)) {
                throw new RuntimeException(
                    'You have to configure headers to extract the remote ip.'
                );
            }
            
            return new IpAddress($check, $proxies, 'ip_address', $headers);
        });
    }
    
    private function bindResponsePostProcessor()
    {
        $this->container->singleton(ResponsePostProcessor::class, function () {
            return new ResponsePostProcessor(
                $this->container[Dispatcher::class],
                $this->app->isRunningUnitTest()
            );
        });
    }
    
    private function bindMethodField()
    {
        $this->container->singleton(MethodField::class, fn() => new MethodField(
            $this->appKey()
        ));
    }
    
    private function bindKernel()
    {
        $this->container->singleton(HttpKernel::class, function () {
            return new HttpKernel(
                $this->container[Pipeline::class],
                $this->container[ResponseEmitter::class],
                $this->container[Dispatcher::class],
            );
        });
    }
    
    private function bindResponseEmitter()
    {
        $this->container->singleton(ResponseEmitter::class, function () {
            return new ResponseEmitter(
                $this->container[ResponsePreparation::class]
            );
        });
    }
    
    private function bindResponseFactory()
    {
        $this->container->singleton(BaseResponseFactory::class, function () {
            return new BaseResponseFactory(
                $this->container[Psr17ResponseFactory::class],
                $this->container[Psr17StreamFactory::class],
                $this->container[Redirector::class]
            );
        });
        $this->container->singleton(ResponseFactory::class, function () {
            return $this->container[BaseResponseFactory::class];
        });
    }
    
    private function bindResponsePreparation()
    {
        $this->container->singleton(ResponsePreparation::class, function () {
            return new ResponsePreparation($this->container[Psr17StreamFactory::class]);
        });
    }
    
    private function bindCoreControllers()
    {
        $this->container->singleton(RedirectController::class, function () {
            return new RedirectController();
        });
        $this->container->singleton(ViewController::class, function () {
            return new ViewController();
        });
        $this->container->singleton(FallBackController::class, function () {
            return new FallBackController();
        });
    }
    
}