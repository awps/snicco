<?php


	namespace Tests\stubs\Controllers\Admin;

	use Tests\stubs\Middleware\FooMiddleware;
	use Tests\stubs\TestResponse;
	use WPEmerge\Contracts\HasControllerMiddlewareInterface;
	use WPEmerge\Middleware\HasControllerMiddlewareTrait;
	use WPEmerge\Requests\Request;

	class AdminControllerWithMiddleware implements HasControllerMiddlewareInterface {

		use HasControllerMiddlewareTrait;

		/**
		 * @var AdminControllerDependency
		 */
		private $dependency;

		public function __construct( AdminControllerDependency $dependency ) {


			if ( isset( $GLOBALS['controller_constructor_count'] )) {


				$count = $GLOBALS['controller_constructor_count'];

				$GLOBALS['controller_constructor_count'] = $count+1;

			}



			$this->middleware(FooMiddleware::class);

			$this->dependency = $dependency;

		}

		public function handle( Request $request) {

			$request->body .= 'admin_controller' . $this->dependency->add_to_response;

			return new TestResponse($request);

		}


	}

	class AdminControllerDependency  {

		public $add_to_response = '_dependency';

	}