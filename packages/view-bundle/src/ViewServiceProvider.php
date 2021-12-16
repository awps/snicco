<?php

declare(strict_types=1);

namespace Snicco\ViewBundle;

use Snicco\View\ViewEngine;
use Snicco\View\GlobalViewContext;
use Snicco\View\Contracts\ViewFactory;
use Snicco\View\ViewComposerCollection;
use Snicco\Core\Http\BaseResponseFactory;
use Snicco\Core\Contracts\ServiceProvider;
use Snicco\Core\Contracts\ResponseFactory;
use Snicco\Core\Contracts\CreatesHtmlResponse;
use Snicco\View\Implementations\PHPViewFinder;
use Snicco\View\Contracts\ViewComposerFactory;
use Snicco\View\Implementations\PHPViewFactory;
use Snicco\Core\ExceptionHandling\HtmlErrorRender;

class ViewServiceProvider extends ServiceProvider
{
    
    public function register() :void
    {
        $ds = DIRECTORY_SEPARATOR;
        $this->extendViews(
            $this->config->get('app.package_root').$ds.'resources'.$ds.'views'.$ds.'framework'
        );
        
        $this->bindGlobalContext();
        
        $this->bindViewFactoryInterface();
        
        $this->bindViewEngine();
        
        $this->bindViewComposerCollection();
        
        $this->bindResponseFactory();
        
        $this->bindCreateHtmlResponse();
        
        $this->bindHtmlErrorRenderer();
        
        $this->bindViewComposerFactory();
    }
    
    public function bootstrap() :void
    {
        //
    }
    
    private function bindViewComposerFactory() :void
    {
        $this->container->singleton(DependencyInjectionViewComposerFactory::class, function () {
            return new DependencyInjectionViewComposerFactory(
                $this->container,
                $this->config['view.composers'] ?? []
            );
        });
    }
    
    private function bindGlobalContext()
    {
        // This has to be a singleton.
        $this->container->singleton(GlobalViewContext::class, function () {
            return new GlobalViewContext();
        });
    }
    
    private function bindViewFactoryInterface() :void
    {
        $this->container->singleton(ViewFactory::class, function () {
            return $this->container[PHPViewFactory::class];
        });
    }
    
    private function bindViewEngine() :void
    {
        $this->container->singleton(ViewEngine::class, function () {
            return new ViewEngine($this->container[ViewFactory::class]);
        });
        
        $this->container->singleton(PHPViewFactory::class, function () {
            return new PHPViewFactory(
                new PHPViewFinder($this->config->get('view.paths', [])),
                $this->container->get(ViewComposerCollection::class),
            );
        });
    }
    
    private function bindViewComposerCollection() :void
    {
        $this->container->singleton(ViewComposerFactory::class, function () {
            return new DependencyInjectionViewComposerFactory(
                $this->container,
                $this->config['view.composers'] ?? []
            );
        });
        
        $this->container->singleton(ViewComposerCollection::class, function () {
            return new ViewComposerCollection(
                $this->container[DependencyInjectionViewComposerFactory::class],
                $this->container[GlobalViewContext::class]
            );
        });
    }
    
    private function bindResponseFactory()
    {
        $this->container->singleton(ViewResponseFactory::class, function () {
            return new ResponseFactoryWithViews(
                $this->container[ViewEngine::class],
                $this->container[BaseResponseFactory::class]
            );
        });
        
        $this->container->singleton(ResponseFactory::class, function () {
            return $this->container[ViewResponseFactory::class];
        });
    }
    
    private function bindCreateHtmlResponse()
    {
        $this->container->singleton(CreatesHtmlResponse::class, function () {
            return $this->container[ViewResponseFactory::class];
        });
    }
    
    private function bindHtmlErrorRenderer()
    {
        $this->container->singleton(HtmlErrorRender::class, function () {
            return new ViewBasedHtmlErrorRenderer(
                $this->container[ViewEngine::class]
            );
        });
    }
    
}