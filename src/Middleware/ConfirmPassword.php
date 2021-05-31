<?php


    declare(strict_types = 1);


    namespace WPEmerge\Middleware;

    use WPEmerge\Contracts\Middleware;
    use WPEmerge\Facade\WP;
    use WPEmerge\Http\Delegate;
    use WPEmerge\Http\Psr7\Request;
    use WPEmerge\Http\ResponseFactory;
    use WPEmerge\Session\SessionStore;

    class ConfirmPassword extends Middleware
    {

        /**
         * @var SessionStore
         */
        private $session_store;
        /**
         * @var ResponseFactory
         */
        private $response_factory;

        public function __construct(SessionStore $session_store, ResponseFactory $response_factory)
        {

            $this->session_store = $session_store;
            $this->response_factory = $response_factory;
        }

        public function handle(Request $request, Delegate $next)
        {

            if ( ! $this->session_store->has('password.confirmed') ) {

                $url = WP::loginUrl( $request->getFullPath() , true );

                return $this->response_factory->redirect(301)->to($url);

            }

            return $next($request);

        }



    }