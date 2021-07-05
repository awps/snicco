<?php


    declare(strict_types = 1);

    use Tests\stubs\TestApp;
    use BetterWP\Routing\Router;

    TestApp::route()->prefix('auth-confirm')->name('auth.confirm.test')->group( function (Router $router)  {

        $router->get('foo', function () {

            return 'Access to foo granted';

        })->middleware('auth.confirmed');

    });