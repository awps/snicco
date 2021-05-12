<?php


    declare(strict_types = 1);


    namespace Tests\integration\Routing;

    use Tests\RequestTesting;
    use Tests\TestCase;
    use WPEmerge\Exceptions\RouteLogicException;
    use WPEmerge\Facade\WP;

    class AjaxRoutesTest extends TestCase
    {

        use SetUpRouter;
        use RequestTesting;

        protected function afterSetUp()
        {

            WP::shouldReceive('isAdmin')->andReturnTrue();
            WP::shouldReceive('isAdminAjax')->andReturnTrue();

        }

        /** @test */
        public function ajax_routes_can_be_matched_by_passing_the_action_as_the_route_parameter()
        {

            $this->router->group(['prefix' => 'wp-admin/admin-ajax.php'], function () {

                $this->router->post('foo_action')->handle(function () {

                    return 'FOO_ACTION';

                });

            });

            $ajax_request = $this->ajaxRequest('foo_action');

            $response = $this->router->runRoute($ajax_request);
            $this->assertSame('FOO_ACTION', $response);

        }

        /** @test */
        public function ajax_routes_with_the_wrong_action_dont_match()
        {

            $this->router->group(['prefix' => 'wp-admin/admin-ajax.php'], function () {

                $this->router->post('foo_action')->handle(function () {

                    return 'FOO_ACTION';

                });

            });

            $ajax_request = $this->ajaxRequest('bar_action');

            $response = $this->router->runRoute($ajax_request);
            $this->assertSame(null, $response);

        }

        /** @test */
        public function ajax_routes_can_be_matched_if_the_action_parameter_is_in_the_query()
        {

            $this->router->group(['prefix' => 'wp-admin/admin-ajax.php'], function () {

                $this->router->get('foo_action')->handle(function () {

                    return 'FOO_ACTION';

                });

            });

            $ajax_request = $this->ajaxRequest('foo_action', 'GET');
            $ajax_request->request->remove('action');
            $ajax_request->query->set('action', 'foo_action');

            $response = $this->router->runRoute($ajax_request);
            $this->assertSame('FOO_ACTION', $response);

        }

        /** @test */
        public function if_the_action_is_not_correct_but_the_url_is_the_route_will_not_match()
        {

            $this->router->group(['prefix' => 'wp-admin/admin-ajax.php'], function () {

                $this->router->get('foo_action')->handle(function () {

                    return 'FOO_ACTION';

                });

            });

            $ajax_request = $this->ajaxRequest('foo_action', 'GET', 'admin-ajax.php/foo_action');
            $ajax_request->request->set('action', '');

            $response = $this->router->runRoute($ajax_request);
            $this->assertSame(null, $response, 'This route should not have run.');

        }

        /** @test */
        public function the_action_is_passed_to_the_route_handler()
        {

            $this->router->group(['prefix' => 'wp-admin/admin-ajax.php'], function () {

                $this->router->post('bar_action')->handle(function ($request, $action) {

                    return strtoupper($action);

                });

            });

            $ajax_request = $this->ajaxRequest('bar_action');

            $response = $this->router->runRoute($ajax_request);
            $this->assertSame('BAR_ACTION', $response);

        }

        /** @test */
        public function the_action_is_passed_to_the_route_handler_for_get_requests()
        {

            $this->router->group(['prefix' => 'wp-admin/admin-ajax.php'], function () {

                $this->router->get('bar_action')->handle(function ($request, $action) {

                    return strtoupper($action);

                });

            });

            $ajax_request = $this->ajaxRequest('bar_action', 'GET');
            $ajax_request->request->remove('action');
            $ajax_request->query->set('action', 'bar_action');

            $response = $this->router->runRoute($ajax_request);
            $this->assertSame('BAR_ACTION', $response);

        }

        /** @test */
        public function ajax_routes_can_be_reversed()
        {

            $this->router->group([
                'prefix' => 'wp-admin/admin-ajax.php', 'name' => 'ajax',
            ], function () {

                $this->router->post('foo_action')->handle(function () {

                    //

                })->name('foo');

            });

            $expected = $this->ajaxUrl();

            $this->assertSame($expected, $this->router->getRouteUrl('ajax.foo'));

        }

        /** @test */
        public function ajax_routes_can_be_reversed_for_get_request_with_the_action_in_the_query_string()
        {

            WP::shouldReceive('addQueryArg')
              ->with('action', 'foo_action', $this->ajaxUrl())
              ->andReturn($this->ajaxUrl().'?action=foo_action');

            $this->router->group([
                'prefix' => 'wp-admin/admin-ajax.php', 'name' => 'ajax',
            ], function () {

                $this->router->get('foo_action')->handle(function () {

                    //

                })->name('foo');

            });

            $expected = $this->ajaxUrl().'?action=foo_action';

            $this->assertSame($expected, $this->router->getRouteUrl('ajax.foo', ['method' => 'GET']));

        }

        /** @test */
        public function an_exception_gets_thrown_when_the_route_doesnt_support_get_requests()
        {
            $this->expectException('Route: ajax.foo does not respond to GET requests.');
            $this->expectException(RouteLogicException::class);

            $this->router->group([
                'prefix' => 'wp-admin/admin-ajax.php', 'name' => 'ajax',
            ], function () {

                $this->router->post('foo_action')->handle(function () {

                    //

                })->name('foo');

            });

            $expected = $this->ajaxUrl().'?action=foo_action';

            $this->assertSame($expected, $this->router->getRouteUrl('ajax.foo', ['method' => 'GET']));

        }

    }