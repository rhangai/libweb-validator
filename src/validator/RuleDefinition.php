<?php
namespace LibWeb\validator;

class RuleDefinition {

	private static $ruleClasses = array(
	);

	public static function getRule( $name, $args ) {
		if ( $name === 'getRule' )
			throw new \InvalidArgumentException( "'getRule' is a reserved name" );

		$inlineName = __CLASS__.'::'.$name;
		if ( is_callable( $inlineName ) )
			return new rule\RuleInline( $inlineName, $args, $name );

		$rawName = __CLASS__.'::'.$name.'__raw';
		if ( is_callable( $rawName ) ) {
			$rawSetupName = __CLASS__.'::'.$name.'__rawSetup';
			$setup = is_callable( $rawSetupName ) ? $rawSetupName : null;
			return new rule\RuleInlineRaw( $rawName, $setup, $args, $name );
		}
		
		$factoryName = __CLASS__.'::'.$name.'__factory';
		if ( is_callable( $factoryName ) )
			return call_user_func_array( $factoryName, $args );
		
		$ruleClass = @self::$ruleClasses[ $name ];
		if ( $ruleClass !== null ) {
		    return new $ruleClass( $args, $name );
		}
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

	public static function call__factory( $fn ) {
		return new rule\RuleInline( $fn, array(), 'call' );
	}
};