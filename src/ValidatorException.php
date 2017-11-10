<?php
namespace LibWeb;

class ValidatorException extends \Exception {

	public function __construct( $state ) {
		parent::__construct();
	}
};