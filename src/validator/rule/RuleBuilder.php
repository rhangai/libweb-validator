<?php
namespace LibWeb\validator\rule;

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
		$this->appendRule( $rule );
		return $this;
	}
	// Add a new rule to the beginning of the chain
	public function prependRule( $rule ) {
		array_unshift( $this->chain_, $rule );
	}
	// Append a new rule to the end of the chain
	public function appendRule( $rule ) {
		$this->chain_[] = $rule;
	}
	// Chain
	private $chain_ = array();
};