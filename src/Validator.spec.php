<?php
namespace LibWeb;

use LibWeb\Validator as v;
use PHPUnit\Framework\TestCase;

class TestValidator extends TestCase {

	/**
	 * Test string
	 */
	public function testString() {
		$this->assertGenericValidator( array( "s", "strval" ), [
			'valid' => [
				['10', 10],
				['my text', 'my text'],
				['trim', '    trim      '],
			],
			'invalid' => [
				true, false,
				[],
				new \stdClass,
				new \DateTime,
			]
		] );
	}
	/**
	 * Test Int
	 */
	public function testInt() {
		$this->assertGenericValidator( array( "i", "intval" ), [
			'valid' => [
				[10, 10],
				[77, '77'],
				[10000, '10000'],
			],
			'invalid' => [
				true, false,
				[],
				new \stdClass,
				new \DateTime,
				'10.0',
				'oi',
				10.2,
			]
		] );
	}
	/**
	 * Test Float validations
	 */
	public function testFloat() {
		$this->assertGenericValidator( array( "f", "floatval" ), [
			'valid' => [
				[10.0, 10.0],
				[77.0, '77.0'],
				[10000, '10000'],
			],
			'invalid' => [
				true, false,
				[],
				new \stdClass,
				new \DateTime,
				'10.x',
				'oi',
			]
		] );
	}
	/**
	 * Boolean validations
	 */
	public function testBool() {
		$this->assertGenericValidator( array( "b", "boolval" ), [
			'valid' => [
				[true, 'true'],
				[true, true],
				[true, '1'],
				[false, 'false'],
				[false, false],
				[false, 0],
			],
			'invalid' => [
				[],
				new \stdClass,
				new \DateTime,
				'oi',
			]
		] );
	}
	/**
	 * InstanceOf validations
	 */
	public function testInstanceOf() {
		$date = new class extends \DateTime {};
		$this->assertGenericValidator( [ 'instanceOf' => v::instanceOf('\\DateTime') ], [
			'valid' => [
				[ new \DateTime ],
				[ $date ],
			],
			'invalid' => [
				[],
				new \stdClass,
				'oi',
			]
		] );
	}
	/**
	 * Obj validations
	 */
	public function testObj() {
		$data = (object) array(
			"name" => "  Fake Name   ",
			"age"  => '10',
			"isOk" => 'true',
		);
		$expectedData =  (object) array(
			"name" => "Fake Name",
			"age"  => 10,
			"isOk" => true,
		);
		$validated = v::validate( $data, array(
			"name" => v::s(),
			"age"  => v::i(),
			'isOk' => v::b(),
		) );
		$this->assertEquals( $expectedData, $validated );
	}
	/**
	 * InstanceOf Array Of
	 */
	public function testArrayOf() {
		$this->assertGenericValidator([ 'arrayOf' =>  v::arrayOf( v::s() ) ], [
			'valid' => [
				[ [ '10', '20', '30' ], [ 10, 20, 30 ] ],
				[ [], [] ],
				[ [ 'oi' ], [ 'oi' ] ],
			],
			'invalid' => [
				[ new \stdClass ],
				new \stdClass,
				'oi',
			]
		], false  );
		$this->assertGenericValidator([ 'arrayOf' =>  v::arrayOf( v::i() ) ], [
			'valid' => [
				[ [ 10, 20, 30 ], [ '10', 20, 30 ] ],
			],
		], false  );
	}

	/**
	 * Assert a generic validator
	 */
	public function assertGenericValidator( $rules, $data, $strict = true ) {
		$data = ( object ) $data;
		foreach ( $rules as $name => $rule ) {
			if ( is_int( $name ) && is_string( $rule ) ) {
				$name = $rule;
				$rule = call_user_func( array( v::class, $name ) );
			}
			if ( @$data->valid ) {
				foreach ( $data->valid as $validValue ) {
					if ( count($validValue) === 1 ) {
						$expected  = $testValue = $validValue[0];
					} else if ( count($validValue) === 2 ) { 
						$expected  = $validValue[0];
						$testValue = $validValue[1];
					}
					$this->assertValidate( $rule, $testValue, $expected, $name, $strict );
				}
			}
			if ( @$data->invalid ) {
				foreach ( $data->invalid as $invalidValue )
					$this->assertValidateError( $rule, $invalidValue );
			}
		}
	}
	/// Assert a validation error
	public function assertValidateError( $rule, $value, $message = "Was not suposed to validate." ) {
		try {
			$validated = v::validate( $value, $rule );
		} catch ( ValidatorException $e ) {
			$this->assertTrue( true );
			return;
		}
		$this->fail( $message." Passed ".gettype( $value ).': '.print_r( $value, true ) );
	}
	/// Assert a validation
	public function assertValidate( $rule, $value, $expected, $ruleName = null, $strict = true ) {
		$validated = v::validate( $value, $rule );
		if ( $strict )
			$this->assertSame( $expected, $validated, "Invalid validation".( $ruleName ? (" for ".$ruleName) : "" ) );
		else
			$this->assertEquals( $expected, $validated, "Invalid validation".( $ruleName ? (" for ".$ruleName) : "" ) );
	}

};
