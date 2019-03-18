<?php

namespace libweb\validator\rule;

use libweb\Validator;
use libweb\validator\Rule;
use libweb\validator\RuleDefinition;

/**
 * @method static validator\rule\RuleBuilder any()                                                                                               Validates anything
 * @method static validator\rule\RuleBuilder boolval()                                                                                           Requires a boolean value
 * @method static validator\rule\RuleBuilder b()                                                                                                 Requires a boolean value
 * @method static validator\rule\RuleBuilder strval(boolean $trim)                                                                               Requires a string value with trimming
 * @method static validator\rule\RuleBuilder s(boolean $trim)                                                                                    Requires a string value with trimming
 * @method static validator\rule\RuleBuilder intval()                                                                                            Requires an integer value
 * @method static validator\rule\RuleBuilder i()                                                                                                 Requires an integer value
 * @method static validator\rule\RuleBuilder floatval(string $decimalSeparator, string $thousandsSeparator)                                      Requires a floating point value
 * @method static validator\rule\RuleBuilder f(string $decimalSeparator, string $thousandsSeparator)                                             Requires a floating point value
 * @method static validator\rule\RuleBuilder decimal(int $digits, int $decimalDigits, string|null $decimalSeparator, string $thousandsSeparator) Requires a decimal value
 * @method static validator\rule\RuleBuilder d(int $digits, int $decimalDigits, string $decimalSeparator, string $thousandsSeparator)            Requires a decimal value
 * @method static validator\rule\RuleBuilder instanceOf(string $class)                                                                           Requires an instance of a given object
 * @method static validator\rule\RuleBuilder uploadedFile()                                                                                      Requires an uploaded object. @see \\Psr\\Http\\Message\\UploadedFileInterface
 * @method static validator\rule\RuleBuilder obj($validatorDefinition)                                                                           Requires an object and uses the validator on it
 * @method static validator\rule\RuleBuilder date($format, $out)                                                                                 Requires a date string using the $format or a DateTime
 * @method static validator\rule\RuleBuilder arrayOf($validatorDefinition)                                                                       Requires an array of the given item
 * @method static validator\rule\RuleBuilder call($fn)                                                                                           Calls the function using the value as first parameter.
 * @method static validator\rule\RuleBuilder invoke($fn)                                                                                         Invoke the function on the object.
 * @method static validator\rule\RuleBuilder invokeArray($fn)                                                                                    Invoke the function on the object using the array as the parameters.
 * @method static validator\rule\RuleBuilder set($array)                                                                                         Requires the value to be inside the set of values
 * @method static validator\rule\RuleBuilder map($map)                                                                                           Requires the value to be a key of the map, and return its value
 * @method static validator\rule\RuleBuilder regex($pattern)                                                                                     Requires a $pattern regex
 * @method static validator\rule\RuleBuilder len($length)                                                                                        Requires the object to be of an exact length
 * @method static validator\rule\RuleBuilder minlen($length)                                                                                     Requires the object to be of a minimum length
 * @method static validator\rule\RuleBuilder maxlen($length)                                                                                     Requires the object to be of a maximum length
 * @method static validator\rule\RuleBuilder str_replace($search, $replace)                                                                      Replaces the given string
 * @method static validator\rule\RuleBuilder preg_replace($search, $replaceOrFunction)                                                           Replaces the given string
 * @method static validator\rule\RuleBuilder blacklist($chars)                                                                                   Do not allow the given chars and returns a string without it
 * @method static validator\rule\RuleBuilder cpf()                                                                                               Validates against a brazilian CPF
 * @method static validator\rule\RuleBuilder cnpj()                                                                                              Validates against a brazilian CNPJ
 */
class RuleBuilder implements Rule {
	// Apply the rules
	public function setup($state) {
		foreach ($this->chain_ as $rule) {
			$rule->setup($state);
		}
	}

	// Apply the rules
	public function apply($state) {
		foreach ($this->chain_ as $rule) {
			$rule->apply($state);
			if ($state->isDone()) {
				break;
			}
		}
	}

	// Apply the rules
	public function __call($method, $args) {
		$rule = RuleDefinition::getRule($method, $args);
		return $this->appendRule($rule);
	}

	// Add a new rule to the beginning of the chain
	public function prependRule($rule) {
		if (!$rule instanceof Rule) {
			throw new \InvalidArgumentException('Rule is not an instance of Rule');
		} elseif ($rule instanceof RuleBuilder) {
			$this->chain_ = array_merge($rule->chain_, $this->chain_);
		} else {
			array_unshift($this->chain_, $rule);
		}
		return $this;
	}

	// Append a new rule to the end of the chain
	public function appendRule($rule) {
		if (!$rule instanceof Rule) {
			throw new \InvalidArgumentException('Rule is not an instance of Rule');
		} elseif ($rule instanceof RuleBuilder) {
			$this->chain_ = array_merge($this->chain_, $rule->chain_);
		} else {
			$this->chain_[] = $rule;
		}
		return $this;
	}

	// Chain
	private $chain_ = array();
}
