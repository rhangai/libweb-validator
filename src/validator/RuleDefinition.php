<?php
namespace LibWeb\validator;

class RuleDefinition {

	/**
	 * Get a single rule definition
	 */
	public static function getRule( $name, $args ) {
		if ( $name === 'getRule' )
			throw new \InvalidArgumentException( "'getRule' is a reserved name" );

		// Try to get inline rules
		$inlineName = __CLASS__.'::'.$name;
		if ( is_callable( $inlineName ) )
			return new rule\RuleInline( $inlineName, $args, $name );

		// Try to get inline raw rules
		$rawName = __CLASS__.'::'.$name.'__raw';
		if ( is_callable( $rawName ) ) {
			$rawSetupName = __CLASS__.'::'.$name.'__rawSetup';
			$setup = is_callable( $rawSetupName ) ? $rawSetupName : null;
			return new rule\RuleInlineRaw( $rawName, $setup, $args, $name );
		}

		// Try to get factory rules
		$factoryName = __CLASS__.'::'.$name.'__factory';
		if ( is_callable( $factoryName ) )
			return call_user_func_array( $factoryName, $args );

		// Could not get any rule
		throw new \InvalidArgumentException( "Invalid rule: ".$name );
	}

	public static function s( $value, $trim = true ) {
		$value = (string) $value;
		if ( $trim )
			$value = trim( $value );
		return $value;
	}
	
	public static function optional__raw( $state ) {
		if ( ( $state->value === null ) || ( $state->value === '' ) ) {
			$state->value = null;
			$state->setDone( true );
		}
	}
	
	public static function dependsOn__raw( $state ) {
	}
	public static function dependsOn__rawSetup( $state, $fields ) {
		if ( is_string( $fields ) )
			$state->addDependency( $fields );
		else if ( is_array( $fields ) ) {
			foreach ( $fields as $field )
				$state->addDependency( $field );
		}
	}

	public static function call__factory( $fn ) {
		return new rule\RuleInline( $fn, array(), 'call' );
	}
	
	public static function obj__factory( $definition ) {
		return new rule\RuleObject( $definition );
	}
};