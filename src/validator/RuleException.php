<?php
namespace LibWeb\validator;

class RuleException extends \Exception {
	/**
	 * Create a new exception for a rule with a value
	 */
	public static function createWithValue( $message, $value ) {
		if ( is_object( $value ) )
			$message .= " Passed Object(".get_class( $value ).")";
		else if ( is_array( $value ) )
			$message .= " Passed Array";
		else if ( is_array( $value ) )
			$message .= " Passed ".gettype($value).": ".json_encode( value );
		return new static( $message );
	}

};