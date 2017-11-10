<?php
namespace LibWeb\validator\rule;

use LibWeb\validator\Rule;

class RuleInlineRaw implements Rule {

	/// Construct the inline rule
	public function __construct( $fn, $setup = null, $args = array(), $name = null ) {
		$this->fn_    = $fn;
		$this->setup_ = $setup;
		$this->args_  = $args;
		$this->name_  = $name;
	}
	/// No need to setup this inline rule
	public function setup( $state ) {
		if ( $this->setup_ ) {
			$args = $this->args_;
			array_unshift( $args, $state );
			call_user_func_array( $this->setup_, $args );
		}
	}
	/// Apply the rule
	public function apply( $state ) {
		try {
			$args = $this->args_;
			array_unshift( $args, $state );
			call_user_func_array( $this->fn_, $args );
		} catch (RuleException $e) {
			$state->addError( $e );
		}
	}

	private $fn_;
	private $setup_;
	private $args_;
	private $name_;
};