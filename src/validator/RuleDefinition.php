<?php
namespace LibWeb\validator;

use LibWeb\Validator;

class RuleDefinition {

	// A few aliases
	private static $alias = array(
		's' => 'strval',
		'i' => 'intval',
		'f' => 'floatval',
		'b' => 'boolval'
	);
	/**
	 * Get a single rule definition
	 */
	public static function getRule( $name, $args ) {
		if ( $name === 'getRule' )
			throw new \InvalidArgumentException( "'getRule' is a reserved name" );

		/// Check for alias
		if ( isset( self::$alias[$name] ) )
			$name = self::$alias[$name];

		// Try to get inline rules
		$inlineName = __CLASS__.'::'.$name;
		if ( is_callable( $inlineName ) ) {
			$inlineCheck = __CLASS__.'::'.$name.'__check';
			if ( is_callable( $inlineCheck ) )
				call_user_func_array( $inlineCheck, $args );
			return new rule\RuleInline( $inlineName, $args, 'v::'.$name );
		}

		// Try to get inline raw rules
		$rawName = __CLASS__.'::'.$name.'__raw';
		if ( is_callable( $rawName ) ) {
			$inlineCheck = __CLASS__.'::'.$name.'__rawCheck';
			if ( is_callable( $inlineCheck ) )
				call_user_func_array( $inlineCheck, $args );
			$rawSetupName = __CLASS__.'::'.$name.'__rawSetup';
			$setup = is_callable( $rawSetupName ) ? $rawSetupName : null;
			return new rule\RuleInlineRaw( $rawName, $setup, $args, 'v::'.$name );
		}

		// Try to get factory rules
		$factoryName = __CLASS__.'::'.$name.'__factory';
		if ( is_callable( $factoryName ) )
			return call_user_func_array( $factoryName, $args );

		// Could not get any rule
		throw new \InvalidArgumentException( "Invalid rule: ".$name );
	}

	/*
	  Obligatoriness validators
	 */
	/// Optional
	public static function optional__raw( $state ) {
		if ( ( $state->value === null ) || ( $state->value === '' ) ) {
			$state->value = null;
			$state->setDone();
		}
	}
	/// Required
	public static function required__raw( $state ) {
		if ( ( $state->value === null ) || ( $state->value === '' ) ) {
			$state->value = null;
			$state->addError( "Field is required" );
			$state->setDone();
		}
	}
	/// Skippable (only makes senses on objects)
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

	/*
	  Type validators
	 */
	// Any validator
	public static function any( $value ) {
		return true;
	}
	// String value
	public static function strval( $value, $trim = true ) {
		if ( is_array( $value ) )
			throw new RuleException( "Array cannot be converted to string." );
		else if ( is_object( $value ) ) {
			if ( !method_exists( $value, '__toString' ) )
				throw RuleException::createWithValue( "Object cannot be converted to string.", $value );
			$value = (string) $value;
		} else if ( $value === true || $value === false )
			throw RuleException::createWithValue( "Boolean cannot be converted to string.", $value );
		if ( $trim !== false )
			return trim( $value );
		else
			return (string) $value;
	}
	// Int value
	public static function intval( $value ) {
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
	public static function floatval( $value, $decimal = null, $thousands = null, $asString = false ) {
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
			if ( $asString ) {
				$value = ( $decimal === '.' ) ? $value : ( $split[0].'.'.$split[1] );
				return $isNegative ? ('-'.$value) : $value;
			} else {
				$value = ( $decimal === '.' ) ? floatval( $value ) : floatval( $split[0].'.'.$split[1] );
				return $isNegative ? -$value : $value;
			}
		} else
			throw RuleException::createWithValue( "Value must be a float.", $value );
	}
	// Boolean value
	public static function boolval( $value ) {
		if ( ($value === '0' ) || ( $value === 0 ) || ( $value === false ) || ($value === 'false') )
		    return new rule\RuleInlineValue( false );
		else if ( ( $value === true ) || ( $value === 'true' ) || ( $value == '1' ) )
		    return new rule\RuleInlineValue( true );
		else
			throw RuleException::createWithValue( "Value must be a boolean.", $value );
	}
	// Decimal value
	public static function decimal__check( $digits, $decimal, $decimalSeparator = null, $thousandsSeparator = null ) {
		if ( !is_int( $digits ) || ( $digits <= 1 ) )
			throw new \InvalidArgumentException( "Digis must be a positive integral > 1. $digits given" );
		if ( !is_int( $decimal ) || ( $decimal < 0 ) )
			throw new \InvalidArgumentException( "Decimal precision must be a positive integral or 0. $decimal given" );
		if ( $decimal > $digits )
			throw new \InvalidArgumentException( "Decimal precision must be less than digits. ($digits, $decimal) given" );
		if ( !class_exists( '\\RtLopez\\Decimal' ) )
			throw new \LogicException( "You must install rtlopez/decimal for decimal validator to work" );
	}
	public static function decimal( $value, $digits, $decimal, $decimalSeparator = null, $thousandsSeparator = null ) {
		if ( $value instanceof \RtLopez\Decimal )
			$value = $value->format( null, '.', '' );
		$value = self::floatval( $value, $decimalSeparator, $thousandsSeparator, true );
		try {
			$value = new impl\Decimal( $value, $decimal );
		} catch ( \RtLopez\ConversionException $e ) {
			throw RuleException::createWithValue( $e->getMessage(), $value );
		}

		$integralDigits = $digits - $decimal;
		$max = \RtLopez\Decimal::create( '10', $decimal )->pow( $integralDigits );
		$min = $max->mul( -1 );
		if ( $value->ge( $max ) || $value->le( $min ) ) {
			throw new RuleException( "Value out of range. Must be between ".$min->format(null, '.', '')." and ".$max->format( null, '.', '' ) );
		}
		
		
		return $value;
	}

	/// Check for type
	public static function instanceOf( $value, $type ) {
		if ( !$value instanceof $type )
			throw RuleException::createWithValue( "Value must be of type $type.", $value );
	}
	/// Convert to an object
	public static function obj__factory( $definition ) {
		return new rule\RuleObject( $definition );
	}	
	/// Tests every element on the array against the validator
	public static function arrayOf__raw( $state, $rule ) {
		$value = &$state->value;
		if ( !is_array( $value ) ) {
			$state->addError( RuleException::createWithValue( "Value must be an array.", $value ) );
			return;
		}
		
		$rule = Validator::normalizeRule( $rule );
		foreach ( $value as $key => &$v ) {
			$child = new State( $v, $key, $state );
			Validator::validateState( $child, $rule );
			$state->mergeErrorsFrom( $child );
			$v = $child->value;
		}
	}


	/*==============================
	   Numeric rules
	 ==============================*/
	public static function range( $value, $min, $max ) {
		if ( is_int( $value ) ) {
			if ( ( $value < $min ) || ( $value > $max ) )
				throw RuleException::createWithValue( "Value must be between [$min, $max].", $value );
		} else if ( is_float( $value ) ) {
			if ( ( $value < $min ) || ( $value > $max ) )
				throw RuleException::createWithValue( "Value must be between [$min, $max].", $value );
		} else
			throw RuleException::createWithValue( "Cannot validate range [$min, $max].", $value );
	}


	/*==============================
	  
	  String rules

	 =================================*/
	public static function regex( $value, $pattern ) {
		$value = self::strval( $value, false );
	    $match = preg_match( $pattern, $value );
		if ( !$match )
			throw RuleException::createWithValue( "Value must match Regex '".$pattern."'.", $value );
		return $value;
	}
	public static function len( $value, $min, $max = null ) {
		$len = null;
		if ( is_array( $value ) || ( $value instanceof \Countable ) )
			$len = count( $value );
		else if ( is_string( $value ) )
			$len = strlen( $value );
		if ( $len === null )
			throw RuleException::createWithValue( "Cannot get length.", $value );
		if ( $max === null )
			$max = $min;
		if ( ( $len > $max ) && ( $max > 0 ) )
			throw new RuleException( "Maximun lenght must be ".$max );
		else if ( $len < $min )
			throw new RuleException( "Minimun lenght must be ".$min );
		return true;
	}
	public static function minlen( $value, $min ) {
		return self::len( $value, $min, INF );
	}
	public static function str_replace( $value, $search, $replace ) {
		$value = self::strval( $value, false );
		return str_replace( $search, $replace, $value );
	}
	public static function preg_replace( $value, $search, $replace ) {
		$value = self::strval( $value, false );
		if ( is_string( $replace ) )
			return preg_replace( $search, $replace, $value );
		else if ( is_callable( $replace ) )
			return preg_replace_callback( $search, $replace, $value );
		else
			throw new \InvalidArgumentException( "Parameter to replace must be a callback, or a string" );
	}
	public static function blacklist( $value, $chars ) {
		$value = self::strval( $value, false );

		$out = array();
		for ( $i = 0, $len = strlen( $value ); $i < $len; ++$i ) {
			$c = $value[ $i ];
			if ( strpos( $chars, $c ) === false )
				$out[] = $c;
		}
		return implode( "", $out );
	}
	public static function whitelist( $value, $chars ) {
		$value = self::strval( $value, false );

		$out = array();
		for ( $i = 0, $len = strlen( $value ); $i < $len; ++$i ) {
			$c = $value[ $i ];
			if ( strpos( $chars, $c ) !== false )
				$out[] = $c;
		}
		return implode( "", $out );
	}
	

	/*
	  Locale validators
	 */
	/// Brazilian CPF validator
	public static function cpf( $cpf ) {
		$cpf = preg_replace('/[^0-9]/', '', (string) $cpf);

		// Valida tamanho
		if (strlen($cpf) != 11)
			throw new RuleException( "CPF must be length 11" );
		$all_equals = true;
		for ( $i = 1; $i<11; ++$i ) {
			if ( $cpf[$i] !== $cpf[$i-1] ) {
				$all_equals = false;
				break;
			}
		}
		if ( $all_equals )
			throw new RuleException( "CPF must have different digits" );
		// Calcula e confere primeiro dígito verificador
		for ($i = 0, $j = 10, $soma = 0; $i < 9; $i++, $j--)
			$soma += $cpf{$i} * $j;
		$resto = $soma % 11;
		if ($cpf{9} != ($resto < 2 ? 0 : 11 - $resto))
			throw new RuleException( "Invalid CPF" );
		// Calcula e confere segundo dígito verificador
		for ($i = 0, $j = 11, $soma = 0; $i < 10; $i++, $j--)
			$soma += $cpf{$i} * $j;
		$resto = $soma % 11;
		if ( $cpf{10} != ($resto < 2 ? 0 : 11 - $resto) )
			throw new RuleException( "Invalid CPF" );
		return $cpf;
	}
	// Brazilian CNPJ validator
	public static function cnpj( $cnpj ) {
		$cnpj = preg_replace('/[^0-9]/', '', (string) $cnpj);
		// Valida tamanho
		if (strlen($cnpj) != 14)
			throw new RuleException( "CNPJ must have length 14" );
		$all_equals = true;
		for ( $i = 1; $i<14; ++$i ) {
			if ( $cnpj[$i] !== $cnpj[$i-1] ) {
				$all_equals = false;
				break;
			}
		}
		if ( $all_equals )
			throw new RuleException( "Invalid CNPJ" );
		// Valida primeiro dígito verificador
		for ($i = 0, $j = 5, $soma = 0; $i < 12; $i++)
		{
			$soma += $cnpj{$i} * $j;
			$j = ($j == 2) ? 9 : $j - 1;
		}
		$resto = $soma % 11;
		if ($cnpj{12} != ($resto < 2 ? 0 : 11 - $resto))
			throw new RuleException( "Invalid CNPJ" );
		// Valida segundo dígito verificador
		for ($i = 0, $j = 6, $soma = 0; $i < 13; $i++)
		{
			$soma += $cnpj{$i} * $j;
			$j = ($j == 2) ? 9 : $j - 1;
		}
		$resto = $soma % 11;
		if ( $cnpj{13} != ($resto < 2 ? 0 : 11 - $resto) )
			throw new RuleException( "Invalid CNPJ" );
		return $cnpj;
	}


};