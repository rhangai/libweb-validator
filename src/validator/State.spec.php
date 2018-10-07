<?php
namespace libweb\validator;

use PHPUnit\Framework\TestCase;

class TestState extends TestCase {

	public function testCreate() {
		$value = new \stdClass;
		$state = new State( $value );
		
		$this->assertSame( $value, $state->value );
	}

	public function testParents() {
		$levels = 10;
		$states = array( new State( null ) );
		for ( $i = 0; $i < $levels; ++$i ) {
			$states[] = new State( null, $i, $states[ $i ] );
		}
		for ( $i = 0; $i < $levels; ++$i ) {
			$this->assertSame( $states[ $i ], $states[ $i + 1 ]->getParent() );
		}
		
		$key = array();
		for ( $i = 0; $i < $levels; ++$i ) {
			$key[] = $i;
			$this->assertEquals( $key, $states[ $i + 1 ]->getKey() );
		}
		
	}


	public function testValidatorGet() {
		$value = array(
			0 => 0,
			"test" => uniqid(),
		);
		$state = new State( (object) $value );

		foreach ( $value as $key => $v ) {
			$this->assertEquals( $v, $state->validatorGet( $key ) );
		}
	}

};
