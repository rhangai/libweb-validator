<?php
namespace LibWeb;

/**
 *
 */
class ValidatorException extends \Exception {
	/**
	 * Validator exception
	 */
	public function __construct( $state, $serializable = false ) {
		$serializable = !!$serializable;
		
		$this->state        = $state;
		$this->errors       = $state->getErrors();
		$this->serializable = $serializable;

		$msg = "Invalid fields: \n";
		foreach ( $this->errors as $field ) {
			if ( $field->key ) {
				$msg .= "    ".implode(".", $field->key)." => ".( $field->message === true ? "Error" : $field->message )."\n";
			} else {
				$msg .= "    ".( $field->message === true ? "Error" : $field->message )."\n";
			}
		};
		parent::__construct( $msg );

		$stack = $this->getTrace();
		$base = __DIR__;
		for ( $i = 0, $len = count($stack); $i<$len; ++$i ) {
			$item = $stack[ $i ];
			if ( @$item["file"] === null || @$item["line"] === null )
				continue;

				
			$file = $item[ "file" ];
			if ( substr( $file, 0, strlen( $base ) ) === $base )
				continue;
			$this->stack = $item;
			break;
		}

	}

	public function serializeFile() {
		if ( $this->stack === null )
			return array( "file" => parent::getFile(), "line" => parent::getLine() );
		return $this->stack;
	}

	private $errors;
	private $fields;
	private $serializable;
	private $stack;
};