<?php

uses('inflector');

class InflectorTest extends UnitTestCase
{
	var $inflector;

	function InflectorTest()
	{
		$this->UnitTestCase('Inflector test');
	}

	function setUp()
	{
		$this->inflector = new Inflector();
	}

	function tearDown()
	{
		unset($this->inflector);
	}

	function testPluralizeSingularize()
	{
		$singulars = array(
		'search', 'switch', 'fix', 'box', 'process', 'address', 'query', 'ability',
		'agency', 'half', 'safe', 'wife', 'basis', 'diagnosis', 'datum', 'medium',
		'person', 'salesperson', 'man', 'woman', 'spokesman', 'child', 'page', 'robot');
		$plurals = array(
		'searches', 'switches', 'fixes', 'boxes', 'processes', 'addresses', 'queries', 'abilities',
		'agencies', 'halves', 'saves', 'wives', 'bases', 'diagnoses', 'data', 'media',
		'people', 'salespeople', 'men', 'women', 'spokesmen', 'children', 'pages', 'robots');

		foreach (array_combine($singulars, $plurals) as $singular => $plural)
		{
			$this->assertEqual($this->inflector->pluralize($singular), $plural);
			$this->assertEqual($this->inflector->singularize($plural), $singular);
		}
	}

	function testCamelize()
	{
		$this->assertEqual($this->inflector->camelize('foo_bar_baz'), 'FooBarBaz');
	}

	function testUnderscore()
	{
		$this->assertEqual($this->inflector->underscore('FooBarBaz'), 'foo_bar_baz');
	}

	function testHumanize()
	{
		$this->assertEqual($this->inflector->humanize('foo_bar_baz'), 'Foo Bar Baz');
	}

	function testTableize()
	{
		$this->assertEqual($this->inflector->tableize('Bar'), 'bars');
	}

	function testClassify()
	{
		$this->assertEqual($this->inflector->classify('bars'), 'Bar');
	}

	function testForeignKey()
	{
		$this->assertEqual($this->inflector->foreignKey('Bar'), 'bar_id');
	}
}

?>