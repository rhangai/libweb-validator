<?php
namespace libweb\validator\rule;

use libweb\Validator;
use libweb\validator\Rule;

class RuleInline implements Rule {

	/// Construct the inline rule
	public function __construct( $fn, $args = array(), $name = null ) {
		$this->fn_ = $fn;
		$this->args_ = $args;
		$this->name_ = $name;
	}
	/// No need to setup this inline rule
	public function setup( $state ) {}
	/// Apply the rule
	public function apply( $state ) {
		try {
			$args = $this->args_;
			array_unshift( $args, $state->value );
			$result = call_user_func_array( $this->fn_, $args );
		} catch ( \Exception $e ) {
			$result = $e;
		}
		if ( ( $result === null ) || ( $result === true ) )
			return;
		if ( ( $result === false ) || ( $result instanceof \Exception ) ) {
			$state->addError( "Could not validate ".($this->name_ ?: ""), $result );
			return;
		}
		if ( $result instanceof Rule ) {
			Validator::validateState( $state, $result );
			return;
		}
		if ( $result instanceof RuleInlineValue )
			$result = $result->getValue();
		$state->value = $result;
	}

	private $fn_;
	private $args_;
	private $name_;
};