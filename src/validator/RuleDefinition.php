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

	/*
	  
	 */
	public static function optional__raw( $state ) {
		if ( ( $state->value === null ) || ( $state->value === '' ) ) {
			$state->value = null;
			$state->setDone();
		}
	}	
	public static function required__raw( $state ) {
		if ( ( $state->value === null ) || ( $state->value === '' ) ) {
			$state->value = null;
			$state->addError( "Field is required" );
			$state->setDone();
		}
	}	
	public static function skippable__raw( $state ) {
		if ( ( $state->value === null ) || ( $state->value === '' ) ) {
			$state->value = null;
			$state->setFlag( State::FLAG_SKIP );
			$state->setDone();
		}
	}


	/// Add a rule dependency
	public static function dependsOn__raw( $state ) {
	}
	public static function dependsOn__rawSetup( $state, $fields ) {
		$state->dependsOn( $fields );
	}

	/// call
	public static function call__factory( $fn ) {
		return new rule\RuleInline( $fn, array(), 'call' );
	}

	/// obj
	public static function obj__factory( $definition ) {
		return new rule\RuleObject( $definition );
	}

	
	/*
	  Type validators
	 */
	// Any validator
	public static function any( $value ) {
		return true;
	}
	// String value
	public static function strval( $value, $trim = true ) {
		return self::s( $value, $trim );
	}
	public static function s( $value, $trim = true ) {
		if ( is_object( $value ) ) {
			if ( !method_exists( $value, '__toString' ) )
				throw RuleException::createWithValue( "Object cannot be converted to string.", $value );
			$value = (string) $value;
		}
		if ( $trim !== false )
			return trim( $value );
		else
			return strval( $value );
	}
	// Int value
	public static function intval( $value ) {
		return self::i( $value );
	}
	public static function i( $value ) {
		$error = false;
		if ( is_int( $value ) ) {
		    return true;
		} else if ( is_string( $value ) ) {
			if ( !ctype_digit( $value ) )
				throw RuleException::createWithValue( "Value must be an int.", $value );
			return intval( trim( $value ), 10 );
		} else
			throw RuleException::createWithValue( "Value must be an int.", $value );
	}

	// Float value
	public static function floatval( $value, $decimal = null, $thousands = null ) {
		return self::f( $value, $decimal, $thousands );
	}
	public static function f( $value, $decimal = null, $thousands = null ) {
		$error = false;
		if ( $decimal === null )
			$decimal   = '.';

		if ( is_int( $value ) ) {
		    return true;
		} else if ( is_float( $value ) ) {
		    return true;
		} else if ( is_string( $value ) ) {
			$value = trim( $value );
			$isNegative = ( @$value[0] === '-' );
			if ( $isNegative )
				$value = substr( $value, 1 );
			if ( $thousands )
				$value = str_replace( $thousands, "", $value );
			if ( ctype_digit( $value ) ) {
				$value = intval( $value, 10 );
				return $isNegative ? -$value : $value;
			}
			$split = explode( $decimal, $value );
			if ( ( count($split) != 2 ) || ( !ctype_digit( $split[0] ) ) || ( !ctype_digit( $split[1] ) ) )
				throw RuleException::createWithValue( "Value must be a float.", $value );
			$value = ( $decimal === '.' ) ? floatval( $value ) : floatval( $split[0].'.'.$split[1] );
			return $isNegative ? -$value : $value;
		} else
			throw RuleException::createWithValue( "Value must be a float.", $value );
	}
	// Boolean value
	public static function boolval( $value ) {
		return self::b( $value );
	}
	public static function b( $value ) {
		if ( !$value || ($value === 'false') )
		    return new rule\InlineRuleValue( false );
		else if ( ( $value === true ) || ( $value === 'true' ) || ( $value == '1' ) )
		    return new rule\InlineRuleValue( true );
		else
			throw RuleException::createWithValue( "Value must be a boolean.", $value );

	}



};