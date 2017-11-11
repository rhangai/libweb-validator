<?php

require_once dirname(__DIR__).'/vendor/autoload.php';

/**
 * Builds the reference
 */
class ReferenceBuilder {

	private $methods_ = array();
	private $aliases_ = array();

	// Add a new method alias
	public function addAlias( $method, $alias ) {
		if ( isset( $this->aliases_[ $method ] ) )
			$this->aliases_[ $method ][] = $alias;
		else
			$this->aliases_[ $method ] = array( $alias );
	}

	// Add a new Reflection Method
	public function addMethod( $method ) {
		if ( !$method instanceof ReflectionMethod )
			throw new \InvalidArgumentException( "Must be a reflection" );

		$name = self::parseName( $method->name, $type );
		if ( $name === false )
			return;

		$anchor = self::getAnchorName( $name );

		$parameters = $method->getParameters();
		if ( $type === 'inline' || $type === 'raw' )
			array_shift( $parameters );

		
		$this->methods_[ $name ] = (object) array(
			"reflection" => $method,
			"name"   => $name,
			'anchor' => $anchor,
			"params" => self::parseParameters( $parameters ),
			"docs"   => self::parseDocComment( $method->getDocComment() ),
		);
	}

	// Refresh every method defined
	public function refreshMethods() {
		$cache = array();

		foreach ( $this->methods_ as $method ) {
			if ( !@$method->reflection )
				continue;
			$file = $method->reflection->getFileName();
			if ( !isset( $cache[ $file ] ) ) {
				$fileLines = file( $file );
				if ( $fileLines === false )
					continue;
				$cache[$file] = $fileLines;
			} else {
				$fileLines = $cache[ $file ];
			}


			$i = $method->reflection->getStartLine();
			while ( $i >= 0 ) {
				$line = $fileLines[ $i ];
			    if ( !preg_match( '/^\s*\/\/\s*======*(.*?)=+/', $line, $matches ) ) {
					--$i;
					continue;
				}

				$method->category = trim( $matches[1] );
				break;
			}

			// Try to parse the comment
			if ( !$method->docs ) {
				$i = $method->reflection->getStartLine();
				if ( isset( $fileLines[ $i - 2 ] ) ) {
					$line = $fileLines[ $i - 2 ];
					if ( preg_match( '/^\s*\/\/\/\s*(.*)$/', $line, $matches ) ) {
						$method->docs = trim( $matches[1] );
					}
				}
			}

			
		}
		
		
	}
	/**
	 * Try to rewrite the given file
	 */
	public function rewrite( $file ) {
		$fp = fopen( $file, "r+" );
		
		$valid = false;
		while ( true ) {
			$pos = ftell( $fp );
			$buffer = fgets( $fp );
			if ( $buffer === false )
				break;
			if (strpos($buffer, '<a name="api"></a>') !== false) {
				$valid = true;
				break; 
			}      
		}
		
		if ( !$valid )
			fseek( $fp, 0, SEEK_END );
		else
			fseek( $fp, $pos, SEEK_SET );

		$this->appendStream( $fp );
		fclose( $fp );
	}
	/**
	 * Write the reference to a single stream
	 */
	public function appendStream( $stream ) {
		// Sort by name
		$methodList = $this->methods_;
		uasort( $methodList, function( $a, $b ) {
			return strcmp( $a->name, $b->name );
		});
		
		/// Group by category
		$methodsByCategory = array();
		foreach ( $methodList as $method )  {
			$category = @$method->category ?: '';
			if ( !isset( $methodsByCategory[ $category ] ) )
				$methodsByCategory[ $category ] = array();
			$methodsByCategory[ $category ][] = $method;
		}
		
		/// Write header
		fwrite( $stream, '<a name="api"></a>'."\n" );
		fwrite( $stream, "API Reference\n" );
		fwrite( $stream, "======================\n" );
		fwrite( $stream, "\n" );
		
		// Write summary
		fwrite( $stream, "Summary\n" );
		fwrite( $stream, "----------------\n" );
		foreach ( $methodsByCategory as $category => $categoryMethodList ) {
			fwrite( $stream, "- ".$category."\n" );
			foreach ( $categoryMethodList as $method ) {
				fwrite( $stream, "  - [`".$method->name.$method->params."`](#".$method->anchor.")\n" );
				$aliases = @$this->aliases_[ $method->name ];
				if ( $aliases ) {
					foreach( $aliases as $alias )
						fwrite( $stream, "  - [`".$alias.$method->params."`](#".$method->anchor.")\n" );
				}
			}
		}

		// Write methods
		fwrite( $stream, "\n" );
		fwrite( $stream, "Methods\n" );
		fwrite( $stream, "----------------\n" );
		foreach ( $methodList as $method )  {
			fwrite( $stream, "\n" );
			fwrite( $stream, '- <a name="'.$method->anchor.'"></a> `'.$method->name.$method->params.'`'."\n" );
			fwrite( $stream, "\n" );
			fwrite( $stream, "  ".$method->docs."\n" );
			
		}
	}
	/**
	 * Get an anchor name for the given method name
	 */
	private static function getAnchorName( $name ) {
	    $name = preg_replace_callback( '/([a-z])([A-Z])/', function( $matches ) {
			return $matches[1].'-'.mb_strtolower( $matches[2] );
		}, $name );
		$name = mb_strtolower($name);
	    $name = preg_replace( '/[^a-z]/', '-', $name );
		return 'api-'.$name;
	}
	/**
	 * Parse a method name to extract a validator name
	 */
	private static function parseName( $name, &$type = null ) {
		$type = null;
		if ( $name === 'getRule' )
			return false;
		else if ( substr( $name, -5 ) === '__raw' ) {
			$type = 'raw';
			return substr( $name, 0, -5 );
		} else if ( substr( $name, -9 ) === '__factory' ) {
			$type = 'factory';
			return substr( $name, 0, -9 );
		} else if ( strpos( $name, '__' ) !== false )
			return false;
		$type = 'inline';
		return $name;
	}
	/**
	 * Parse the parameters list to extract the parameter string  
	 */
	private static function parseParameters( $reflectionParameterList ) {
		$params = array();
		foreach ( $reflectionParameterList as $reflectionParameter ) {
			$param = array(
				"name" => $reflectionParameter->getName(),
			);
			try {
				$param["defaultValue"] = $reflectionParameter->getDefaultValue();
				$param["hasDefaultValue"] = true;
			} catch ( \Exception $e ) {
				$param["hasDefaultValue"] = false;

			}
			$params[] = (object) $param;
		}

		$paramStr = array();
		foreach ( $params as $param ) {
			$p = '$'.$param->name;
			if ( $param->hasDefaultValue )
				$p .= ' = '.json_encode($param->defaultValue);
			$paramStr[] = $p;
		}
		return '('.implode( ', ', $paramStr ). ')';
	}

	/// Parse the doc comment
	private static function parseDocComment( $comment ) {
		if ( !$comment )
			return $comment;
		$len = strlen( $comment );
		return trim( substr( $comment, 3, $len - 5 ) );
	}
}

/**
   
   Read the definitions and write to README.md
   
 */
function main() {
	$builder = new ReferenceBuilder();

	$reflection = new ReflectionClass( '\\LibWeb\\validator\\RuleDefinition' );

	// Add methods
	foreach ( $reflection->getMethods() as $method ) {
		$builder->addMethod( $method );
	}
	$builder->refreshMethods();

	// Add aliases
	$propertyAlias = $reflection->getProperty( 'alias' );
	$propertyAlias->setAccessible( true );
	foreach ( $propertyAlias->getValue() as $alias => $method ) {
		$builder->addAlias( $method, $alias );
	}

	// Rewrite file
	$builder->rewrite( dirname( __DIR__ ).'/README.md' );
}

main();