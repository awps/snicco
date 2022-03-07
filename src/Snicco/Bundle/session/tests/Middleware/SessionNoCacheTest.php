<?php

declare(strict_types=1);


namespace Snicco\Bundle\Session\Tests\Middleware;

use Snicco\Bundle\Session\Middleware\SessionNoCache;
use Snicco\Component\HttpRouting\Testing\MiddlewareTestCase;

final class SessionNoCacheTest extends MiddlewareTestCase
{

    /**
     * @test
     */
    public function no_cache_headers_are_added(): void
    {
        $middleware = new SessionNoCache();

        $response = $this->runMiddleware($middleware, $this->frontendRequest());

        $response->assertNextMiddlewareCalled();
        $response->psr()->assertHeader('cache-control', 'max-age=0, must-revalidate, private');
    }

}