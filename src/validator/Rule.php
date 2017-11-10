<?php
namespace LibWeb\validator;

/**
 * Basic interface for a rule
 */
interface Rule {
	/// Setup the state
	public function setup( $state );
	/// Apply itself on a state
	public function apply( $state );
};