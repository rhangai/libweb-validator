<?php
namespace LibWeb\validator;

class State {

	const FLAG_SKIP = 0x01;


	/// Value variable
	public $value;
	/**
	 * Construct the state
	 */
	public function __construct( $value, $key = null, $parent = null ) {
		$this->value    = $value;
		$this->initial_ = $value;
		$this->key_     = self::mergeKeys( $key, $parent );
		$this->parent_  = $parent;
	}
	/// Get the key
	public function getKey() { return $this->key_; }
	/// Get the parent
	public function getParent() { return $this->parent_; }
	/// Get the initial value for the state
	public function getInitial() { return $this->initial_; }
	/// Mark the state as finished
	public function setDone( $value = true ) { $this->done_ = !!$value; }
	/// Check if state has finished
	public function isDone() { return $this->done_; }
	/**
	 * Merge keys for state
	 */
	private static function mergeKeys( $key, $parent ) {
		if ( !$parent )
			return $key !== null ? array( $key ) : array();
		$keys = $parent->getKey();
		if ( $key !== null )
			$keys[] = $key;
		return $keys;
	}
	/**
	 * Get validator sub-data
	 */
	public function validatorGet( $key ) {
		if ( $this->getter_ === null )
			$this->getter_ = self::createGetterFor( $this->initial_ );
		if ( $this->getter_ === false )
			throw new \LogicException( "Cannot get any sub key on this state." );
		return $this->getter_->validatorGet( $key );
	}
	/**
	 * Set the rule used to validate a single key
	 */
	public function setCurrentRuleFor( $key, $rule ) {
		$this->subrules_[ $key ] = $rule;
	}
	/**
	 * Set the flags
	 */
	public function setFlag( $flags, $set = false ) {
		$this->flags_ = ($set ? $flags : ($this->flags_ | $flags));
	}
	/**
	 * Set the flags
	 */
	public function testFlag( $flag ) {
		return ( $this->flags_ & $flag ) === $flag;
	}
	/**
	 * Create a dependency on the given key
	 */
	public function dependsOn( $key ) {
		if ( is_array( $key ) ) {
			foreach( $key as $k )
				$this->dependencies_[] = $k;
		} else 
			$this->dependencies_[] = $key;
	}
	/**
	 * Get the dependencies on the state
	 */
	public function getDependencies() {
		return $this->dependencies_;
	}
	/**
	 * Add a new error on the given state
	 */
	public function addError( $message, $exception = null ) {
		if ( $message instanceof \Exception ) {
			$exception = $message;
			$message   = "";
		}

		if ( $exception instanceof RuleException ) {
			$message = ( $message ? $message." - " : "" ) . $exception->getMessage();
		} else if ( !$exception instanceof \Exception ) {
			$exception = null;
		}
	    $this->errors_[] = (object) array(
			"key"       => $this->getKey(),
			"message"   => $message,
			"exception" => $exception,
		);
	}
	/**
	 * Merge the errors from another state
	 */
	public function mergeErrorsFrom( $state ) {
		$this->errors_ = array_merge( $this->errors_, $state->getErrors() );
	}
	/**
	 * Get the errors
	 */
	public function getErrors() {
		return $this->errors_;
	}
	/**
	 * Create a getter for subdata to validate
	 */
	private static function createGetterFor( $value ) {
		if ( is_object( $value ) ) {
			if ( ( $value instanceof \stdClass ) || ( $value instanceof \ArrayAccess ) )
				return new getter\ArrayGetter( $value );
			else if ( is_callable( array( $value, 'validatorGet' ) ) )
				return $value;
		} else if ( is_array( $value ) ) {
			return new getter\ArrayGetter( $value );
		}
		return new getter\NullGetter();
	}

	private $initial_;
	private $key_;
	private $parent_;
	private $getter_;
	private $done_;
	private $flags_ = 0;
	private $subrules_ = array();
	private $errors_ = array();
	private $dependencies_ = array();
};