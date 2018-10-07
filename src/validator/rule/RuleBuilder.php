<?php
namespace libweb\validator\rule;

use LibWeb\Validator;
use LibWeb\validator\Rule;
use LibWeb\validator\RuleDefinition;

class RuleBuilder implements Rule {

	// Apply the rules
	public function setup( $state ) {
		foreach ( $this->chain_ as $rule ) {
			$rule->setup( $state );
		}
	}
	// Apply the rules
	public function apply( $state ) {
		foreach ( $this->chain_ as $rule ) {
		    $rule->apply( $state );
			if ( $state->isDone() )
				break;
		}
	}
	// Apply the rules
	public function __call( $method, $args ) {
		$rule = RuleDefinition::getRule( $method, $args );
		return $this->appendRule( $rule );
	}
	// Add a new rule to the beginning of the chain
	public function prependRule( $rule ) {
		if ( !$rule instanceof Rule )
			throw new \InvalidArgumentException( "Rule is not an instance of Rule" );
		else if ( $rule instanceof RuleBuilder )
			$this->chain_ = array_merge( $rule->chain_, $this->chain_ );
		else
			array_unshift( $this->chain_, $rule );
		return $this;
	}
	// Append a new rule to the end of the chain
	public function appendRule( $rule ) {
		if ( !$rule instanceof Rule )
			throw new \InvalidArgumentException( "Rule is not an instance of Rule" );
		else if ( $rule instanceof RuleBuilder )
			$this->chain_ = array_merge( $this->chain_, $rule->chain_ );
		else
			$this->chain_[] = $rule;
		return $this;
	}
	// Chain
	private $chain_ = array();
};