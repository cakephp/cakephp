<?PHP

uses ('test', 'controller');

class ControllerTest extends TestCase {
	var $abc;

	// constructor of the test suite
	function ControllerTest($name) {
		$this->TestCase($name);
	}

	// called before the test functions will be executed
	// this function is defined in PHPUnit_TestCase and overwritten
	// here
	function setUp() {
		$this->abc = new Controller ();
		$this->abc->base = '/ease';

		$data = array('foo'=>'foo_value', 'foobar'=>'foobar_value', 'tofu'=>'1');
		$params = array('controller'=>'Test', 'action'=>'test_action', 'data'=>$data);
		$this->abc->params = $params;
		$this->abc->data = $data;

		$this->abc->action = $this->abc->params['action'];
		$this->abc->passed_args = null;			
	 }

    // called after the test functions are executed
    // this function is defined in PHPUnit_TestCase and overwritten
    // here
    function tearDown() {
        unset($this->abc);
    }

	
	function testUrlFor () {
		$result = $this->abc->urlFor ('/foo/bar');
		$expected = '/ease/foo/bar';
		$this->assertEquals($result, $expected);

		$result = $this->abc->urlFor ('baz');
		$expected = '/ease/test/baz';
		$this->assertEquals($result, $expected);

		$result = $this->abc->urlFor ();
		$expected = '/ease/test/test_action';
		$this->assertEquals($result, $expected);
	}

	function testParseHtmlOptions () {
		$result = $this->abc->parseHtmlOptions(null);
		$expected = null;
		$this->assertEquals($result, $expected);

		$result = $this->abc->parseHtmlOptions(array());
		$expected = null;
		$this->assertEquals($result, $expected);

		$result = $this->abc->parseHtmlOptions (array('class'=>'foo'));
		$expected = ' class="foo"';
		$this->assertEquals($result, $expected);

		$result = $this->abc->parseHtmlOptions (array('class'=>'foo', 'id'=>'bar'), '', ' ');
		$expected = 'class="foo" id="bar" ';
		$this->assertEquals($result, $expected);
	}

	function testLinkTo() {
		$result = $this->abc->linkTo ('Testing ó³¼', '/test/ok', array('style'=>'color:Red'), 'Sure?');
		$expected = '<a href="/ease/test/ok" style="color:Red" onClick="return confirm(\'Sure?\')">Testing ó³¼</a>';
		$this->assertEquals($result, $expected);

		$result = $this->abc->linkTo ('Ok', 'ok');
		$expected = '<a href="/ease/test/ok">Ok</a>';
		$this->assertEquals($result, $expected);
	}

	function testLinkOut () {
		$result = $this->abc->linkOut ('Sputnik.pl', 'http://www.sputnik.pl/', array('style'=>'color:Red'));
		$expected = '<a href="http://www.sputnik.pl/" style="color:Red">Sputnik.pl</a>';
		$this->assertEquals($result, $expected);

		$result = $this->abc->linkOut ('http://sputnik.pl');
		$expected = '<a href="http://sputnik.pl">http://sputnik.pl</a>';
		$this->assertEquals($result, $expected);
	}

	function testFormTag () {
		$result = $this->abc->formTag();
		$expected = '<form action="/ease/test/test_action" method="post">';
		$this->assertEquals($result, $expected);

		$result = $this->abc->formTag('foo', 'get');
		$expected = '<form action="/ease/test/foo" method="get">';
		$this->assertEquals($result, $expected);

		$result = $this->abc->formTag('/bar/baz', 'file');
		$expected = '<form action="/ease/bar/baz" method="post" enctype="multipart/form-data">';
		$this->assertEquals($result, $expected);
	}

	function testSubmitTag () {
		$result = $this->abc->submitTag();
		$expected = '<input type="submit" value="Submit" />';
		$this->assertEquals($result, $expected);

		$result = $this->abc->submitTag('Foo', array('class'=>'Bar'));
		$expected = '<input type="submit" class="Bar" value="Foo" />';
		$this->assertEquals($result, $expected);
	}

	function testInputTag () {
		$result = $this->abc->inputTag('foo');
		$expected = '<input name="data[foo]" size="20" value="foo_value" />';
		$this->assertEquals($result, $expected);

		$result = $this->abc->inputTag('bar', 20, array('class'=>'Foobar'));
		$expected = '<input name="data[bar]" class="Foobar" size="20" value="" />';
		$this->assertEquals($result, $expected);
	}

	function testPasswordTag () {
		$result = $this->abc->passwordTag('foo');
		$expected = '<input type="password" name="data[foo]" size="20" value="foo_value" />';
		$this->assertEquals($result, $expected);

		$result = $this->abc->passwordTag('foo', 33, array('class'=>'Bar'));
		$expected = '<input type="password" name="data[foo]" class="Bar" size="33" value="foo_value" />';
		$this->assertEquals($result, $expected);
	}

	function testHiddenTag () {
		$result = $this->abc->hiddenTag('foo');
		$expected = '<input type="hidden" name="data[foo]" value="foo_value" />';
		$this->assertEquals($result, $expected);

		$result = $this->abc->hiddenTag('bar', 'baz');
		$expected = '<input type="hidden" name="data[bar]" value="baz" />';
		$this->assertEquals($result, $expected);

		$result = $this->abc->hiddenTag('foobar', null, array('class'=>'Bar'));
		$expected = '<input type="hidden" name="data[foobar]" class="Bar" value="foobar_value" />';
		$this->assertEquals($result, $expected);
	}

	function testFileTag () {
		$result = $this->abc->fileTag('bar', array('class'=>'Foo', 'disabled'=>'1'));
		$expected = '<input type="file" name="bar" class="Foo" disabled="1" />';
		$this->assertEquals($result, $expected);
	}

	function testAreaTag () {
		$result = $this->abc->areaTag('foo');
		$expected = '<textarea name="data[foo]" cols="60" rows="10">foo_value</textarea>';
		$this->assertEquals($result, $expected);

		$result = $this->abc->areaTag('foo', 33, 33, array('class'=>'Bar'));
		$expected = '<textarea name="data[foo]" class="Bar" cols="33" rows="33">foo_value</textarea>';
		$this->assertEquals($result, $expected);
	}

	function testCheckboxTag () {
		$result = $this->abc->checkboxTag('bar');
		$expected = '<label for="tag_bar"><input type="checkbox" name="data[bar]" id="tag_bar" />Bar</label>';
		$this->assertEquals($result, $expected);

		$result = $this->abc->checkboxTag('tofu', 'ToFu title', array('class'=>'Baz'));
		$expected = '<label for="tag_tofu"><input type="checkbox" name="data[tofu]" id="tag_tofu" class="Baz" checked="checked" />ToFu title</label>';
		$this->assertEquals($result, $expected);
	}

	function testRadioTags () {
		$result = $this->abc->radioTags('foo', array('foo'=>'Foo', 'bar'=>'Bar'), '---', array('class'=>'Foo'));
		$expected = '<label for="tag_foo_foo"><input type="radio" name="data[foo]" id="tag_foo_foo" class="Foo" value="foo" />Foo</label>---<label for="tag_foo_bar"><input type="radio" name="data[foo]" id="tag_foo_bar" class="Foo" value="bar" />Bar</label>';
		$this->assertEquals($result, $expected);

		$result = $this->abc->radioTags('bar', array());
		$expected = null;
		$this->assertEquals($result, $expected);
	}

	function testSelectTag () {
		$result = $this->abc->selectTag('tofu', array('m'=>'male', 'f'=>'female'), array('class'=>'Outer'), array('class'=>'Inner', 'id'=>'FooID'));
		$expected = '<select name="data[tofu]" class="Outer">'."\n".'<option value="" class="Inner" id="FooID"></option>'."\n".'<option value="m" class="Inner" id="FooID">male</option>'."\n".'<option value="f" class="Inner" id="FooID">female</option>'."\n".'</select>';
		$this->assertEquals($result, $expected);

		$result = $this->abc->selectTag('tofu', array());
		$expected = null;
		$this->assertEquals($result, $expected);
	}

	function testTableHeaders () {
		$result = $this->abc->tableHeaders(array('One', 'Two', 'Three'));
		$expected = '<tr><th>One</th> <th>Two</th> <th>Three</th></tr>';
		$this->assertEquals($result, $expected);

		$result = $this->abc->tableHeaders(array('Foo Bar', 'Baz'), array('class'=>'Eco'), array('align'=>'left'));
		$expected = '<tr class="Eco"><th align="left">Foo Bar</th> <th align="left">Baz</th></tr>';
		$this->assertEquals($result, $expected);
	}

	function testTableCells () {
		$result = $this->abc->tableCells(array('Foo', 'Bar'));
		$expected = '<tr><td>Foo</td> <td>Bar</td></tr>';
		$this->assertEquals($result, $expected);

		$result = $this->abc->tableCells(array(array('Foo','Bar'),array('Baz','Echo'),array('Nul','Pio')), array('class'=>'Mini'), array('align'=>'left'));
		$expected = join("\n", array('<tr class="Mini"><td>Foo</td> <td>Bar</td></tr>', '<tr align="left"><td>Baz</td> <td>Echo</td></tr>', '<tr class="Mini"><td>Nul</td> <td>Pio</td></tr>'));
		$this->assertEquals($result, $expected);
	}

	function testImageTag () {
		$result = $this->abc->imageTag('foo.gif');
		$expected = '<img src="/ease/images/foo.gif" alt="" />';
		$this->assertEquals($result, $expected);

		$result = $this->abc->imageTag('bar/baz.gif', 'Foobar', array('class'=>'Zet'));
		$expected = '<img src="/ease/images/bar/baz.gif" alt="Foobar" class="Zet" />';
		$this->assertEquals($result, $expected);
	}

	function testTagValue () {
		$result = $this->abc->tagValue('foo');
		$expected = 'foo_value';
		$this->assertEquals($result, $expected);

		$result = $this->abc->tagValue('bar');
		$expected = false;
		$this->assertEquals($result, $expected);
	}

	function testGetCrumbs () {
		$this->abc->addCrumb('Foo', '/bar/foo');
		$result = $this->abc->getCrumbs();
		$expected = '<a href="/ease">START</a> &raquo; <a href="/ease/bar/foo">Foo</a>';
		$this->assertEquals($result, $expected);
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