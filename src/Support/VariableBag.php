<?php


	declare( strict_types = 1 );


	namespace BetterWP\Support;

	use Illuminate\Config\Repository;

	class VariableBag extends Repository {


		public function add(array $globals) {

			$this->set($globals);

		}


	}