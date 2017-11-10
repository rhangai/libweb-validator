<?php
namespace LibWeb\validator;

class RuleException extends \Exception {
	/**
	 * Create a new exception for a rule with a value
	 */
	public static function createWithValue( $message, $value ) {
		if ( is_object( $value ) )
			$message .= " Passed object of class ".get_class( $value );
		else
			$message .= " Passed ".gettype( $value ).": ".$value;
		return new static( $message );
	}

};