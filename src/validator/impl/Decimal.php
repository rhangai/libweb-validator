<?php
namespace libweb\validator\impl;

/**
 * Implements the Decimal class compatible with LibWeb
 */
class Decimal extends \RtLopez\DecimalBCMath {

	/// Convert to string
	public function __toString() {
		return $this->serializeAPI();
	}
	/// Serialize this decimal using the following formatters
	public function serializeAPI() {
		return $this->format( null, '.', '' );
	}
	
};
