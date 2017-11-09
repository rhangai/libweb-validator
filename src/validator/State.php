<?php
namespace LibWeb\validator;

class State {

	public $value;

	public function __construct( $value, $key = null, $parent = null ) {
		$this->value    = $value;
		$this->initial_ = $value;
		$this->key_     = self::mergeKeys( $key, $parent );
		$this->parent_  = $parent;
	}

	public function getKey() { return $this->key_; }

	public function getParent() { return $this->parent_; }

	private static function mergeKeys( $key, $parent ) {
		if ( !$parent )
			return $key ? array( $key ) : array();

		$keys = $parent->getKey();
		if ( $key )
			$keys[] = $key;
		return $keys;
	}
	
	private $initial_;
	private $key_;
	private $parent_;
};