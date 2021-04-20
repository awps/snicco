<?php


	namespace Tests\stubs\Controllers\Ajax;

	use Tests\stubs\TestResponse;
	use WPEmerge\Requests\Request;

	class AjaxController {

		public function handle( Request $request) {

			$request->body = 'ajax_controller';

			return new TestResponse($request);


		}

		public function assertNoView ( Request $request , string $no_view ) {

			$request->body  = $no_view;

			return new TestResponse($request);

		}

	}