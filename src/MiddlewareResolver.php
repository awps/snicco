<?php


	namespace WPEmerge;

	use Contracts\ContainerAdapter;

	use WPEmerge\Traits\HasControllerMiddleware;

	class MiddlewareResolver {

		/**
		 * @var \Contracts\ContainerAdapter
		 */
		private $container;

		public function __construct( ContainerAdapter $container ) {

			$this->container = $container;
		}

		public function resolveFor( array $callable ) {

			[ $class, $method ] = $callable;

			if ( ! method_exists( $class, 'getMiddleware' ) ) {
				return [];
			}

			/** @var HasControllerMiddleware $controller_instance */
			$controller_instance = $this->container->make( $class );

			// Dont resolve this controller again when we hit the route.
			$this->container->instance( $class, $controller_instance );

			return $controller_instance->getMiddleware( $method );


		}

	}