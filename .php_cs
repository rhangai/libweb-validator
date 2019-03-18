<?php

$finder = PhpCsFixer\Finder::create()
	->in( __DIR__ )
;
return PhpCsFixer\Config::create()
	->setRules([
		'@Symfony' => true,
		'indentation_type' => true,
		'blank_line_before_statement' => [],
		'braces' => [
			'allow_single_line_closure' => true,
			'position_after_functions_and_oop_constructs' => 'same',
		],
		"concat_space" => [ "spacing" => "one" ],
		'no_unneeded_curly_braces' => true,
		'no_spaces_inside_parenthesis' => true,
		'no_unneeded_control_parentheses' => true,
		'yoda_style' => false,
	])
	->setIndent("\t")
	->setLineEnding("\n")
	->setFinder($finder);