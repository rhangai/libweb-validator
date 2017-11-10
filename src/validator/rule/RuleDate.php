<?php
namespace LibWeb\validator\rule;

use LibWeb\validator\Rule;
use LibWeb\validator\RuleException;

/// Internal date time object
class ValidatorDateTime extends \DateTime {
	// Set the output format for this rule
	public function setOutputFormat( $format ) {
		$this->out_ = $format;
	}
	// Convert the datetime to string
	public function __toString() {
		$format = $this->out_ ?: "Y-m-d H:i:s";
		return $this->format( $format );
	}
	private $out_;
}

/**
   Convert an object to a DateTime
 */
class RuleDate implements Rule {

	// Construct the rule
	public function __construct( $format = null, $out = null ) {
		if ( $format === null )
		    $format = array( "Y-m-d H:i:s", "Y-m-d" );
		$this->format_ = $format;
		$this->out_    = $out;
	}
	// No need to setup
	public function setup( $state ) {}
	// Try to convert to a date
	public function apply( $state ) {
		$value = $state->value;
		if ( is_string( $value ) ) {
			$value = self::tryParse( $value, $this->format_ );
			if ( $value === false ) {
				$state->addError( RuleException::createWithValue( "Invalid date object.", $state->value ) );
				return;
			}
		}
		if ( !( $value instanceof \DateTime ) ) {
			$state->addError( RuleException::createWithValue( "Invalid date object.", $value ) );
			return;
		}
		$newtime = new ValidatorDateTime;
		$newtime->setTimestamp( $value->getTimestamp() );
		$newtime->setOutputFormat( $this->out_ );
		$state->value = $newtime;
	}

	// Try parse a format, or an array of formats
	private static function tryParse( $value, $format ) {
		if ( is_array( $format ) ) {
			foreach ( $format as $item ) {
				$result = self::tryParse( $value, $item );
				if ( $result !== false )
					return $result;
			}
			return false;
		} else if ( !$format )
			return false;
		
		$result = \DateTime::createFromFormat( '!'.$format, $value );
		if ( $result === false )
			return false;
		if ( $result->format( $format ) !== $value )
			return false;
		return $result;
	}
	
	private $format_;
	private $out_;
};
