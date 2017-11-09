<?php
namespace LibWeb\validator;

use PHPUnit\Framework\TestCase;

class TestState extends TestCase {

	public function testCreate() {
		$value = new \stdClass;
		$state = new State( $value );
		
		$this->assertEquals( $value, $state->value );
	}

	public function testParent() {
		$value = new \stdClass;
		$state = new State( $value );
		
		$this->assertEquals( $value, $state->value );
	}

};
