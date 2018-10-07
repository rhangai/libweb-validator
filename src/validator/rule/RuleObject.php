<?php
namespace libweb\validator\rule;

use LibWeb\Validator;
use LibWeb\validator\Rule;
use LibWeb\validator\State;
use MJS\TopSort\Implementations\StringSort;

class RuleObject implements Rule {


	public function __construct( $rules ) {
		$normalized = array();
		foreach ( $rules as $key => $rule ) {
			$rule = self::normalizeRule( $key, $rule );
			$normalized[ $key ] = $rule;
		}
		$this->rules_ = $normalized;
	}
	/**
	 * Setup the rule and create every child state
	 */
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
	 * Apply the rule
	 */
	public function apply( $state ) {
		if ( !is_object( $state->value ) && !is_array( $state->value ) ) {
			$state->addError( "Object or Array required" );
			return;
		}
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
			$state->setCurrentRuleFor( $key, $rule, $child->value );
			if ( $child->testFlag( State::FLAG_SKIP ) )
				continue;
			$value[$key] = $child->value;			
		}
		$state->value = (object) $value;
	}

	private static function normalizeRule( &$key, $rule ) {
		$rule = Validator::normalizeRule( $rule );
		$len  = strlen( $key );
		if ( $len <= 0 )
			return $rule;
		if ( $key[$len - 1] === '?' ) {
			if ( $len >= 2 && $key[ $len - 2 ] === '?' ) {
				$key = substr( $key, 0, $len - 2 );
				return Validator::skippable()->appendRule( $rule );
			}
			$key = substr( $key, 0, $len - 1 );
			return Validator::optional()->appendRule( $rule );
		}
		return Validator::required()->appendRule( $rule );
	}

	private $childStates_;
	private $rules_;
};