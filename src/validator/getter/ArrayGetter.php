<?php
namespace LibWeb\validator\getter;

class ArrayGetter {

	public function __construct( $array ) {
		$this->array_ = (array) $array;
	}
	
	public function validatorGet( $key ) {
		return @$this->array_[ $key ];
	}

	private $array_;
}