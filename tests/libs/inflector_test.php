<?PHP

uses ('test', 'inflector');

class InflectorTest extends TestCase {
	var $abc;

	function ControllerTest($name) {
		$this->TestCase($name);
	}

	function setUp() {
		$this->abc = new Inflector ();
	}

   function tearDown() {
		unset($this->abc);
   }

	function testPluralizeSingularize () {
		$singulars = array(
			'search', 'switch', 'fix', 'box', 'process', 'address', 'query', 'ability', 
			'agency', 'half', 'safe', 'wife', 'basis', 'diagnosis', 'datum', 'medium', 
			'person', 'salesperson', 'man', 'woman', 'spokesman', 'child', 'page', 'robot');
		$plurals = array(
			'searches', 'switches', 'fixes', 'boxes', 'processes', 'addresses', 'queries', 'abilities', 
			'agencies', 'halves', 'saves', 'wives', 'bases', 'diagnoses', 'data', 'media', 
			'people', 'salespeople', 'men', 'women', 'spokesmen', 'children', 'pages', 'robots');

		foreach (array_combine($singulars, $plurals) as $singular => $plural) {
			$this->assertEquals($this->abc->pluralize($singular), $plural);
			$this->assertEquals($this->abc->singularize($plural), $singular);
		}
	}

	function testCamelize() {
		$this->asEq($this->abc->camelize('foo_bar_baz'), 'FooBarBaz');
	}    

	function testUnderscore () {
		$this->asEq($this->abc->underscore('FooBarBaz'), 'foo_bar_baz');
	}

	function testHumanize () {
		$this->asEq($this->abc->humanize('foo_bar_baz'), 'Foo Bar Baz');
	}

	function testTableize () {
		$this->asEq($this->abc->tableize('Bar'), 'bars');
	}

	function testClassify () {
		$this->asEq($this->abc->classify('bars'), 'Bar');
	}

	function testForeignKey () {
		$this->asEq($this->abc->foreignKey('Bar'), 'bar_id');
	}


/*
	function test () {
		$result = $this->abc->();
		$expected = '';
		$this->assertEquals($result, $expected);
	}
*/
}

?>