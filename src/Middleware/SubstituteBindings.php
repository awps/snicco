<?php


	namespace WPEmerge\Middleware;

	use Contracts\ContainerAdapter;
	use WPEmerge\Contracts\Middleware;
	use WPEmerge\Helpers\ImplicitRouteBindings;
	use WPEmerge\Requests\Request;

	class SubstituteBindings implements Middleware {

		/**
		 * @var \Contracts\ContainerAdapter
		 */
		private $container;

		/**
		 * SubstituteBindings constructor.
		 *
		 * @param  \Contracts\ContainerAdapter  $container
		 */
		public function __construct( ContainerAdapter $container ) {

			$this->container = $container;

		}

		public function handle ( Request $request, \Closure $next ) {

			// ImplicitRouteBindings::resolveForRoute($this->container, $request->route());

			return $next($request);

		}


	}