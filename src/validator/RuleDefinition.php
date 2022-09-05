<?php
namespace libweb\validator;

use libweb\Validator;

class RuleDefinition {

	// A few aliases
	private static $alias = array(
		's' => 'strval',
		'i' => 'intval',
		'f' => 'floatval',
		'b' => 'boolval'
	);
	// Custom rules
	private static $customRules = array();
	private static $customRuleDefinitionClass = [];
	// Add custom rule
	public static function addCustomRule( $name, $definition ) {
		$definition = (object) $definition;
		self::$customRules[ $name ] = $definition;
	}
	// Add custom rule definition class
	public static function addCustomRuleDefinitionClass( $definitionClass ) {
		if ( !in_array( $definitionClass, self::$customRuleDefinitionClass ) )
			self::$customRuleDefinitionClass[] = $definitionClass;
	}
	/**
	 * Get a single rule definition
	 */
	public static function getRule( $name, $args ) {
		if ( $name === 'getRule' )
			throw new \InvalidArgumentException( "'getRule' is a reserved name" );

		// Custom rules
		if ( isset( self::$customRules[ $name ] ) ) {
			$customRule = self::$customRules[ $name ];
			if ( $customRule->type === 'inline' ) {
				if ( $customRule->rule instanceof Rule )
					return $customRule->rule;
				return new rule\RuleInline( $customRule->rule, $args, 'v::'.$name );
			} else if ( $customRule->type === 'raw' ) 
				return new rule\RuleInlineRaw( $customRule->rule, $customRule->setup, $args, 'v::'.$name );
			else if ( $customRule->type === 'factory' ) 
				return call_user_func_array( $customRule->rule, $args );
			else
				throw new \LogicException( "Should not get here" );
		}

		$rule = null;
		foreach ( self::$customRuleDefinitionClass as $class ) {
			$rule = self::getRuleFromClass( $class, $name, $args );
			if ( $rule )
				return $rule;
		}

		$rule = self::getRuleFromClass( __CLASS__, $name, $args );
		if ( $rule )
			return $rule;

		// Could not get any rule
		throw new \InvalidArgumentException( "Invalid rule: ".$name );
	}
	/// Get a rule from a class according to the definition
	private static function getRuleFromClass( $class, $name, $args ) {
		/// Check for alias
		if ( isset( $class::$alias[$name] ) )
			$name = $class::$alias[$name];

		// Try to get inline rules
		$inlineName = $class.'::'.$name;
		if ( is_callable( $inlineName ) ) {
			$inlineCheck = $class.'::'.$name.'__check';
			if ( is_callable( $inlineCheck ) )
				call_user_func_array( $inlineCheck, $args );
			return new rule\RuleInline( $inlineName, $args, 'v::'.$name );
		}

		// Try to get inline raw rules
		$rawName = $class.'::'.$name.'__raw';
		if ( is_callable( $rawName ) ) {
			$inlineCheck = $class.'::'.$name.'__rawCheck';
			if ( is_callable( $inlineCheck ) )
				call_user_func_array( $inlineCheck, $args );
			$rawSetupName = $class.'::'.$name.'__rawSetup';
			$setup = is_callable( $rawSetupName ) ? $rawSetupName : null;
			return new rule\RuleInlineRaw( $rawName, $setup, $args, 'v::'.$name );
		}

		// Try to get factory rules
		$factoryName = $class.'::'.$name.'__factory';
		if ( is_callable( $factoryName ) )
			return call_user_func_array( $factoryName, $args );

		return null;
	}

	// =============== Obligatoriness ================
	/** Optional validator (Bypass if null or '') */
	public static function optional__raw( $state ) {
		if ( ( $state->value === null ) || ( $state->value === '' ) ) {
			$state->value = null;
			$state->setDone();
		}
	}
	/** Required validator (Fails if null or '') */
	public static function required__raw( $state ) {
		if ( ( $state->value === null ) || ( $state->value === '' ) ) {
			$state->value = null;
			$state->addError( "Field is required" );
			$state->setDone();
		}
	}
	/** Skippable validator (Bypass if null or '' and does not set the property) */
	public static function skippable__raw( $state ) {
		if ( ( $state->value === null ) || ( $state->value === '' ) ) {
			$state->value = null;
			$state->setFlag( State::FLAG_SKIP );
			$state->setDone();
		}
	}
	/** Optional validator If a condition is met */
	public static function optionalIf__raw( $state, $isOptional ) {
		if ( $isOptional )
			return self::optional__raw( $state );
		return self::required__raw( $state );
	}
	/** Required validator only if a condition is met */
	public static function requiredIf__raw( $state, $isRequired ) {
		return self::optionalIf__raw( $state, !$isRequired );
	}
	/** Skippable validator If a condition is met */
	public static function skippableIf__raw( $state, $isSkippable ) {
		if ( $isSkippable )
			return self::skippable__raw( $state );
	}

	// =============== Meta ================
	/** Add a rule dependency. (Only works for objects) */
	public static function dependsOn__raw( $state ) {
	}
	public static function dependsOn__rawSetup( $state, $fields ) {
		$state->dependsOn( $fields );
	}

	// =============== Type validators ================
	/// Validate all objects
	public static function any( $value ) {
		return true;
	}
	/// Convert the object to a string value
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
	/// Convert the value to an int and fails if cannot be safely convertible
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
	/// Convert the value to a float
	public static function floatval( $value, $decimal = null, $thousands = null, $asString = false ) {
		$error = false;
		if ( $decimal === null )
			$decimal   = '.';

		if ( is_int( $value ) ) {
		    return $value;
		} else if ( is_float( $value ) ) {
		    return $value;
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
	/// Convert the value to a boolean
	public static function boolval( $value ) {
		if ( ($value === '0' ) || ( $value === 0 ) || ( $value === false ) || ($value === 'false') )
		    return new rule\RuleInlineValue( false );
		else if ( ( $value === true ) || ( $value === 'true' ) || ( $value == '1' ) )
		    return new rule\RuleInlineValue( true );
		else
			throw RuleException::createWithValue( "Value must be a boolean.", $value );
	}
	// Check for decimal
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
	/// Convert the value to a decimal with $digits and $decimal (needs rtlopes\Decimal)
	public static function decimal( $value, $digits, $decimal, $decimalSeparator = null, $thousandsSeparator = null ) {
		if ( $value instanceof \RtLopez\Decimal ) {
			$value = new impl\Decimal( $value, $decimal );
		} else {
			$value = self::floatval( $value, $decimalSeparator, $thousandsSeparator, true );
			try {
				$value = new impl\Decimal( $value, $decimal );
			} catch ( \RtLopez\ConversionException $e ) {
				throw RuleException::createWithValue( $e->getMessage(), $value );
			}
		}
		
		$integralDigits = $digits - $decimal;
		$max = \RtLopez\Decimal::create( '10', $decimal )->pow( $integralDigits );
		$min = $max->mul( -1 );
		if ( $value->ge( $max ) || $value->le( $min ) ) {
			throw new RuleException( "Value out of range. Must be between ".$min->format(null, '.', '')." and ".$max->format( null, '.', '' ) );
		}
		
		
		return $value;
	}
	/// Check if the object is an instance of the given type
	public static function instanceOf( $value, $type ) {
		if ( !$value instanceof $type )
			throw RuleException::createWithValue( "Value must be of type $type.", $value );
	}
	/// Check if the object is an instance of an uploaded file
	public static function uploadedFile( $value ) {
		return self::instanceOf( $value, "\\Psr\\Http\\Message\\UploadedFileInterface" );
	}
	/// Convert the value to an object and validate its fields
	public static function obj__factory( $definition ) {
		return new rule\RuleObject( $definition );
	}	
	/// Convert the value to a date using the format (or keep if alredy a \DateTime)
	public static function date__factory( $format = null, $out = null ) {
		return new rule\RuleDate( $format, $out );
	}	
	/// Validate every element on the array against the $rule
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

	
	// ============== Mixed ================
	/// Call the function $fn to validate the value
	public static function call__factory( $fn ) {
		return new rule\RuleInline( $fn, array(), 'call' );
	}
	/// Invoke the function $fn on the object
	public static function invoke( $value, $fn ) {
		$args = array_slice( func_get_args(), 2 );
		return self::invokeArray( $value, $fn, $args );
	}
	/// Invoke the function $fn on the object using array as args
	public static function invokeArray( $value, $fn, $args = array() ) {
		return call_user_func_array( array( $value, $fn ), $args );
	}
	/// Check if value is in set
	public static function set( $value, $items ) {
		if ( !in_array( $value, $items ) )
			throw RuleException::createWithValue( "Value must be in ".json_encode( $items ), $value );
	}
	/// Map a value to another
	public static function map( $value, $map ) {
		if ( !array_key_exists( $value, $map  ) )
			throw RuleException::createWithValue( "Value must be one of ".json_encode( array_keys( $map ) ), $value );
		return new rule\RuleInlineValue( $map[ $value ] );
	}
	/// Check if member will validate just as the other fields
	public static function sameAs__raw( $state, $field ) {
		$fieldValue = null;
		$parent = $state->getParent();
		$rule   = $parent->getCurrentRuleFor( $field, $fieldValue );
		if ( !$rule )
			return;
		Validator::validateState( $state, $rule );
		if ( $state->getErrors() )
			return;
		if ( $state->value != $fieldValue )
			$state->addError( RuleException::createWithValue( "Value must match the field '$field'", $state->value ) );
	}
	public static function sameAs__rawSetup( $state, $field ) {
		$parent = $state->getParent();
		if ( !$parent )
			throw new \LogicException( "Must use sameAs rule on an object" );
		$state->dependsOn( $field );
	}
	// ================ Numeric rules =======================
	/// Check if object is between two numbers (inside range)
	public static function between__factory( $min, $max ) {
		if ( class_exists( '\\RtLopez\\Decimal' ) )
			return new rule\RuleInline( __CLASS__.'::between__implDecimal', [ $min, $max ], 'between' );
		else
			return new rule\RuleInline( __CLASS__.'::between__implDefault', [ $min, $max ], 'between' );
	}
	public static function between__implDefault( $value, $min, $max ) {
		if ( is_int( $value ) || is_float( $value ) ) {
			if ( ( $value < $min ) || ( $value > $max ) )
				throw RuleException::createWithValue( "Value must be between [$min, $max].", $value );
		} else
			throw RuleException::createWithValue( "Cannot validate range [$min, $max] because value is not a number.", $value );
	}
	public static function between__implDecimal( $value, $min, $max ) {
	    if ( is_int( $value ) || is_float( $value ) || ( $value instanceof \RtLopez\Decimal ) ) {
			if ( self::between__implLessThan( $value, $min ) || self::between__implLessThan( $max, $value ) )
				throw RuleException::createWithValue( "Value must be between [$min, $max].", $value );
		} else
			throw RuleException::createWithValue( "Cannot validate range [$min, $max].", $value );
	}
    public static function between__implLessThan( $a, $b ) {
		if ( ( $a === INF ) || ( $b === -INF ) )
			return false;
		else if ( ( $a === -INF ) || ( $b === INF ) )
			return true;
		else if ( $a instanceof \RtLopez\Decimal )
			return $a->lt( $b );
		else if ( $b instanceof \RtLopez\Decimal ) {
			return $b->ge( $a );
		}
		return $a < $b;
	}


	// ================ String rules ====================
	/// Validate the value against the $pattern
	public static function regex( $value, $pattern ) {
		$value = self::strval( $value, false );
	    $match = preg_match( $pattern, $value );
		if ( !$match )
			throw RuleException::createWithValue( "Value must match Regex '".$pattern."'.", $value );
		return $value;
	}
	/// Check for string or array length
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
	/// Check if string has at least $min length
	public static function minlen( $value, $min ) {
		return self::len( $value, $min, INF );
	}
	/// Check if string has at most $max length
	public static function maxlen( $value, $max ) {
		return self::len( $value, 0, $max );
	}
	/// Replace the characters on the string
	public static function str_replace( $value, $search, $replace ) {
		$value = self::strval( $value, false );
		return str_replace( $search, $replace, $value );
	}
	/// Replace the $search pattern on the string using $replace (Callback or string)
	public static function preg_replace( $value, $search, $replace ) {
		$value = self::strval( $value, false );
		if ( is_string( $replace ) )
			return preg_replace( $search, $replace, $value );
		else if ( is_callable( $replace ) )
			return preg_replace_callback( $search, $replace, $value );
		else
			throw new \InvalidArgumentException( "Parameter to replace must be a callback, or a string" );
	}
	/// Remove any char found in $chars from the string
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
	/// Remove any char NOT found in $chars from the string
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

	// ================ Locale rules =======================
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
			$soma += $cpf[$i] * $j;
		$resto = $soma % 11;
		if ($cpf[9] != ($resto < 2 ? 0 : 11 - $resto))
			throw new RuleException( "Invalid CPF" );
		// Calcula e confere segundo dígito verificador
		for ($i = 0, $j = 11, $soma = 0; $i < 10; $i++, $j--)
			$soma += $cpf[$i] * $j;
		$resto = $soma % 11;
		if ( $cpf[10] != ($resto < 2 ? 0 : 11 - $resto) )
			throw new RuleException( "Invalid CPF" );
		return $cpf;
	}
	/// Brazilian CNPJ validator
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
			$soma += $cnpj[$i] * $j;
			$j = ($j == 2) ? 9 : $j - 1;
		}
		$resto = $soma % 11;
		if ($cnpj[12] != ($resto < 2 ? 0 : 11 - $resto))
			throw new RuleException( "Invalid CNPJ" );
		// Valida segundo dígito verificador
		for ($i = 0, $j = 6, $soma = 0; $i < 13; $i++)
		{
			$soma += $cnpj[$i] * $j;
			$j = ($j == 2) ? 9 : $j - 1;
		}
		$resto = $soma % 11;
		if ( $cnpj[13] != ($resto < 2 ? 0 : 11 - $resto) )
			throw new RuleException( "Invalid CNPJ" );
		return $cnpj;
	}


};