<?php
namespace LibWeb\validator\getter;

/**
 * Get the keys on an array (or array like object)
 */
class ArrayGetter implements Getter {

	/// Construct the getter by passing the array
	public function __construct( $array ) {
		$this->array_ = (array) $array;
	}
	/// Get a key on the array
	public function validatorGet( $key ) {
		return @$this->array_[ $key ];
	}

	private $array_; ///< The array
}