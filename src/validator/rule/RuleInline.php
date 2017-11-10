<?php
namespace LibWeb\validator\rule;

use LibWeb\validator\Rule;

class RuleInline implements Rule {

	/// Construct the inline rule
	public function __construct( $fn, $name = null ) {
		$this->fn_ = $fn;
	}
	/// No need to setup this inline rule
	public function setup( $state ) {}
	/// Apply the rule
	public function apply( $state ) {
		try {
			$result = call_user_func( $this->fn_, $state->value );
		} catch ( \Exception $e ) {
			$result = $e;
		}
		if ( ( $result === null ) || ( $result === true ) )
			return;
		if ( ( $result === false ) || ( $result instanceof \Exception ) ) {
			$state->addError( "Could not validate ".($this->name_ ?: ""), $result );
			return;
		}
		if ( $result instanceof RuleInlineValue )
			$result = $result->getValue();
		$state->value = $result;
	}

	private $fn_;
	private $name_;
};