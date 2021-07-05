<?php


    declare(strict_types = 1);


    namespace BetterWP\Routing\FastRoute;

    use FastRoute\Dispatcher;
    use BetterWP\Routing\RoutingResult;

    trait TransformFastRoutes
    {

        public function toRoutingResult (array $route_info ) :RoutingResult {

            if ($route_info[0] !== Dispatcher::FOUND)  {

                return new RoutingResult(null, []);

            }

            $route = $route_info[1];
            $payload = $this->normalize($route_info[2]);

            return new RoutingResult($route, $payload);

        }

        private function normalize(array $captured_url_segments) :array  {

            return array_map(function ($value) {

                return rtrim($value, '/');

            }, $captured_url_segments);

        }


    }