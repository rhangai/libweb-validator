<?php
namespace LibWeb\validator;

class RuleException extends \Exception {
	/**
	 * Create a new exception for a rule with a value
	 */
	public static function createWithValue( $message, $value ) {
		$message .= " Passed ".gettype( $value ).": ".$value;
		return new static( $message );
	}

};