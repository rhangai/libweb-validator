<?php
namespace libweb\validator\rule;
class RuleInlineValue {
	public function __construct( $value ) {
		$this->value_ = $value;
	}
	public function getValue() { return $this->value_; }
	private $value_;
};