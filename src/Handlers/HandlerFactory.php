<?php


	namespace WPEmerge\Handlers;

	use Closure;
	use WPEmerge\AbstractFactory;
	use WPEmerge\Contracts\Handler;
	use WPEmerge\Contracts\RouteAction;
	use WPEmerge\Http\MiddlewareResolver;

	class HandlerFactory extends AbstractFactory {




		/**
		 * @param  string|array|callable  $raw_handler
		 *
		 * @return \WPEmerge\Contracts\RouteAction
		 * @throws \Exception
		 */
		public function createUsing( $raw_handler ) : Handler {

			$handler = $this->normalizeInput( $raw_handler );

			if ( $handler[0] instanceof Closure ) {

				return new ClosureAction( $handler[0], $this->wrapClosure( $handler[0] ) );

			}

			if ( $namespaced_handler = $this->checkIsCallable( $handler ) ) {

				return new ControllerAction(
					$namespaced_handler,
					$this->wrapClass( $namespaced_handler ),
					new MiddlewareResolver($this->container)
				);

			}

			$this->fail( $handler[0], $handler[1] );

		}



	}