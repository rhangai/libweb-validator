<?php
namespace libweb\validator\impl;

/**
 * Implements the Decimal class compatible with libweb
 */
class Decimal extends \RtLopez\DecimalBCMath implements \JsonSerializable {

	/// Convert to string
	public function __toString() {
		return $this->format( null, '.', '' );
	}
	public function serializeAPI() {
		return $this->__toString();
	}
	public function jsonSerialize() {
		return $this->__toString();
	}
};
