<?php
namespace libweb\validator\getter;

/**
 * Always get a null value
 */
class NullGetter implements Getter {
	public function validatorGet( $key ) {
		return null;
	}
}