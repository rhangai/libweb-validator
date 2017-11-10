<?php
namespace LibWeb\validator\rule;

use LibWeb\validator\Rule;
use LibWeb\validator\State;
use MJS\TopSort\Implementations\StringSort;

class RuleObject implements Rule {

	public function __construct( $rules ) {
		$this->rules_ = $rules;
	}

	public function setup( $state ) {
		$childStates = array();
		foreach ( $this->rules_ as $key => $rule ) {
			$child = new State( $state->validatorGet( $key ), $key, $state );
			$rule->setup( $child );
			$childStates[$key] = $child;
		}
		$this->childStates_ = $childStates;
	}

	/**
	 * 
	 */
	public function apply( $state ) {
		// Order the keys to apply the validation according to the dependency
		$sorter = new StringSort;
		foreach ( $this->childStates_ as $key => $child )
			$sorter->add( $key, $child->getDependencies() );
		$keys = $sorter->sort();

		$value = array();
		foreach ( $keys as $key ) {
			$rule  = $this->rules_[ $key ];
			$child = $this->childStates_[ $key ];
			$rule->apply( $child );
			if ( $child->getErrors() ) {
				$state->mergeErrorsFrom( $child );
				continue;
			}
			$value[$key] = $child->value;			
		}
		$state->value = (object) $value;
	}

	private $childStates_;
	private $rules_;
};