<?php
//////////////////////////////////////////////////////////////////////////
// + $Id:$
// +------------------------------------------------------------------+ //
// + Cake PHP : Rapid Development Framework <http://www.cakephp.org/> + //
// + Copyright: (c) 2005, CakePHP Authors/Developers                  + //
// + Author(s): Michal Tatarynowicz aka Pies <tatarynowicz@gmail.com> + //
// +            Larry E. Masters aka PhpNut <nut@phpnut.com>          + //
// +            Kamil Dzielinski aka Brego <brego.dk@gmail.com>       + //
// +------------------------------------------------------------------+ //
// + Licensed under The MIT License                                   + //
// + Redistributions of files must retain the above copyright notice. + //
// + See: http://www.opensource.org/licenses/mit-license.php          + //
//////////////////////////////////////////////////////////////////////////

/**
 * 
 * 
 * @filesource 
 * @author CakePHP Authors/Developers
 * @copyright Copyright (c) 2005, CakePHP Authors/Developers
 * @link https://trac.cakephp.org/wiki/Authors Authors/Developers
 * @package cake
 * @subpackage cake.tests.libs
 * @since CakePHP v 0.2.9
 * @version $Revision:$
 * @modifiedby $LastChangedBy$
 * @lastmodified $Date:$
 * @license http://www.opensource.org/licenses/mit-license.php The MIT License
 *
 */

/**
 * Basic defines
 */
uses('helpers/html');
/**
 * Enter description here...
 *
 * @package cake
 * @subpackage cake.tests.libs
 * @since CakePHP v .9
 *
 */
class HtmlHelperTest extends UnitTestCase
{
/**
 * Enter description here...
 *
 * @var unknown_type
 */
	var $html;


/**
 * constructor of the test suite
 *
 */
	function ControllerTest()
	{
		$this->UnitTestCase('Html helper test');
	}

/**
 * Enter description here...
 *
 */
	function setUp()
	{
		$this->html = new HtmlHelper();
		
		$this->html->base = '/ease';

		$data = array('foo'=>'foo_value', 'foobar'=>'foobar_value', 'tofu'=>'1');
		$params = array('controller'=>'Test', 'action'=>'test_action', 'data'=>$data);
		$here = '/cake/test';
		$this->html->params = $params;
		$this->html->data = $data;
		$this->html->here = $here;

		$this->html->action = $this->html->params['action'];
		$this->html->passed_args = null;
	}

/**
 * Enter description here...
 *
 */
	function tearDown()
	{
		unset($this->html);
	}

/**
 * Enter description here...
 *
 */
	function testTrim()
	{
		$expected = 'Long ...';
		$result   = $this->html->trim('Long string', 5, '...');
		$this->assertEqual($expected, $result);
	}
	
/**
 * Enter description here...
 *
 */
	function testUrlFor()
	{
		$result   = $this->html->urlFor('/foo/bar');
		$expected = '/ease/foo/bar';
		$this->assertEqual($result, $expected);

		$result   = $this->html->urlFor('baz');
		$expected = '/ease/test/baz';
		$this->assertEqual($result, $expected);

		$result   = $this->html->urlFor();
		$expected = '/cake/test';
		$this->assertEqual($result, $expected);
	}

/**
 * Enter description here...
 *
 */
	function testParseHtmlOptions()
	{
		$result   = $this->html->parseHtmlOptions(null);
		$expected = null;
		$this->assertEqual($result, $expected);

		$result   = $this->html->parseHtmlOptions(array());
		$expected = null;
		$this->assertEqual($result, $expected);

		$result   = $this->html->parseHtmlOptions(array('class'=>'foo'));
		$expected = ' class="foo"';
		$this->assertEqual($result, $expected);

		$result   = $this->html->parseHtmlOptions(array('class'=>'foo', 'id'=>'bar'), array('class'));
		$expected = ' id="bar"';
		$this->assertEqual($result, $expected);

		$result   = $this->html->parseHtmlOptions(array('class'=>'foo', 'id'=>'bar'), null, '', ' ');
		$expected = 'class="foo" id="bar" ';
		$this->assertEqual($result, $expected);
	}

/**
 * Enter description here...
 *
 */
	function testLinkTo()
	{
		$result   = $this->html->linkTo('Testing �', '/test/ok', array('style'=>'color:Red'), 'Sure?');
		$expected = '<a href="/ease/test/ok" style="color:Red" onClick="return confirm(\'Sure?\')">Testing �</a>';
		$this->assertEqual($result, $expected);

		$result   = $this->html->linkTo('Ok', 'ok');
		$expected = '<a href="/ease/test/ok">Ok</a>';
		$this->assertEqual($result, $expected);
	}

/**
 * Enter description here...
 *
 */
	function testLinkOut()
	{
		$result   = $this->html->linkOut('Sputnik.pl', 'http://www.sputnik.pl/', array('style'=>'color:Red'));
		$expected = '<a href="http://www.sputnik.pl/" style="color:Red">Sputnik.pl</a>';
		$this->assertEqual($result, $expected);

		$result   = $this->html->linkOut('http://sputnik.pl');
		$expected = '<a href="http://sputnik.pl">http://sputnik.pl</a>';
		$this->assertEqual($result, $expected);
	}

/**
 * Enter description here...
 *
 */
	function testFormTag()
	{
		$result   = $this->html->formTag();
		$expected = "<form action=\"{$this->html->here}\" method=\"post\">";
		$this->assertEqual($result, $expected);

		$result   = $this->html->formTag('foo', 'get');
		$expected = '<form action="/ease/test/foo" method="get">';
		$this->assertEqual($result, $expected);

		$result   = $this->html->formTag('/bar/baz', 'file');
		$expected = '<form action="/ease/bar/baz" method="post" enctype="multipart/form-data">';
		$this->assertEqual($result, $expected);
	}

/**
 * Enter description here...
 *
 */
	function testSubmitTag()
	{
		$result   = $this->html->submitTag();
		$expected = '<input type="submit" value="Submit" />';
		$this->assertEqual($result, $expected);

		$result   = $this->html->submitTag('Foo', array('class'=>'Bar'));
		$expected = '<input type="submit" class="Bar" value="Foo" />';
		$this->assertEqual($result, $expected);
	}

/**
 * Enter description here...
 *
 */
	function testInputTag()
	{
		$result   = $this->html->inputTag('foo');
		$expected = '<input name="data[foo]" size="20" value="foo_value" />';
		$this->assertEqual($result, $expected);

		$result   = $this->html->inputTag('bar', 20, array('class'=>'Foobar'));
		$expected = '<input name="data[bar]" class="Foobar" size="20" value="" />';
		$this->assertEqual($result, $expected);
	}

/**
 * Enter description here...
 *
 */
	function testPasswordTag()
	{
		$result   = $this->html->passwordTag('foo');
		$expected = '<input type="password" name="data[foo]" size="20" value="foo_value" />';
		$this->assertEqual($result, $expected);

		$result   = $this->html->passwordTag('foo', 33, array('class'=>'Bar'));
		$expected = '<input type="password" name="data[foo]" class="Bar" size="33" value="foo_value" />';
		$this->assertEqual($result, $expected);
	}

/**
 * Enter description here...
 *
 */
	function testHiddenTag()
	{
		$result   = $this->html->hiddenTag('foo');
		$expected = '<input type="hidden" name="data[foo]" value="foo_value" />';
		$this->assertEqual($result, $expected);

		$result   = $this->html->hiddenTag('bar', 'baz');
		$expected = '<input type="hidden" name="data[bar]" value="baz" />';
		$this->assertEqual($result, $expected);

		$result   = $this->html->hiddenTag('foobar', null, array('class'=>'Bar'));
		$expected = '<input type="hidden" name="data[foobar]" class="Bar" value="foobar_value" />';
		$this->assertEqual($result, $expected);
	}

/**
 * Enter description here...
 *
 */
	function testFileTag()
	{
		$result   = $this->html->fileTag('bar', array('class'=>'Foo', 'disabled'=>'1'));
		$expected = '<input type="file" name="bar" class="Foo" disabled="1" />';
		$this->assertEqual($result, $expected);
	}

/**
 * Enter description here...
 *
 */
	function testAreaTag()
	{
		$result   = $this->html->areaTag('foo');
		$expected = '<textarea name="data[foo]" cols="60" rows="10">foo_value</textarea>';
		$this->assertEqual($result, $expected);

		$result   = $this->html->areaTag('foo', 33, 33, array('class'=>'Bar'));
		$expected = '<textarea name="data[foo]" class="Bar" cols="33" rows="33">foo_value</textarea>';
		$this->assertEqual($result, $expected);
	}

/**
 * Enter description here...
 *
 */
	function testCheckboxTag()
	{
		$result   = $this->html->checkboxTag('bar');
		$expected = '<label for="tag_bar"><input type="checkbox" name="data[bar]" id="tag_bar" />Bar</label>';
		$this->assertEqual($result, $expected);

		$result   = $this->html->checkboxTag('tofu', 'ToFu title', array('class'=>'Baz'));
		$expected = '<label for="tag_tofu"><input type="checkbox" name="data[tofu]" id="tag_tofu" class="Baz" checked="checked" />ToFu title</label>';
		$this->assertEqual($result, $expected);
	}

/**
 * Enter description here...
 *
 */
	function testRadioTags()
	{
		$result   = $this->html->radioTags('foo', array('foo'=>'Foo', 'bar'=>'Bar'), '---', array('class'=>'Foo'));
		$expected = '<label for="tag_foo_foo"><input type="radio" name="data[foo]" id="tag_foo_foo" class="Foo" value="foo" />Foo</label>---<label for="tag_foo_bar"><input type="radio" name="data[foo]" id="tag_foo_bar" class="Foo" value="bar" />Bar</label>';
		$this->assertEqual($result, $expected);

		$result   = $this->html->radioTags('bar', array());
		$expected = null;
		$this->assertEqual($result, $expected);
	}

/**
 * Enter description here...
 *
 */
	function testSelectTag()
	{
		$result   = $this->html->selectTag('tofu', array('m'=>'male', 'f'=>'female'), 'm', array('class'=>'Outer'), array('class'=>'Inner', 'id'=>'FooID'));
		$expected = '<select name="data[tofu]" class="Outer">'."\n".'<option value="" class="Inner" id="FooID"></option>'."\n".'<option value="m" class="Inner" id="FooID" selected="selected">male</option>'."\n".'<option value="f" class="Inner" id="FooID">female</option>'."\n".'</select>';
		$this->assertEqual($result, $expected);

		$result   = $this->html->selectTag('tofu', array());
		$expected = null;
		$this->assertEqual($result, $expected);
	}

/**
 * Enter description here...
 *
 */
	function testTableHeaders()
	{
		$result   = $this->html->tableHeaders(array('One', 'Two', 'Three'));
		$expected = '<tr><th>One</th> <th>Two</th> <th>Three</th></tr>';
		$this->assertEqual($result, $expected);

		$result   = $this->html->tableHeaders(array('Foo Bar', 'Baz'), array('class'=>'Eco'), array('align'=>'left'));
		$expected = '<tr class="Eco"><th align="left">Foo Bar</th> <th align="left">Baz</th></tr>';
		$this->assertEqual($result, $expected);
	}

/**
 * Enter description here...
 *
 */
	function testTableCells()
	{
		$result   = $this->html->tableCells(array('Foo', 'Bar'));
		$expected = '<tr><td>Foo</td> <td>Bar</td></tr>';
		$this->assertEqual($result, $expected);

		$result   = $this->html->tableCells(array(array('Foo','Bar'),array('Baz','Echo'),array('Nul','Pio')), array('class'=>'Mini'), array('align'=>'left'));
		$expected = join("\n", array('<tr class="Mini"><td>Foo</td> <td>Bar</td></tr>', '<tr align="left"><td>Baz</td> <td>Echo</td></tr>', '<tr class="Mini"><td>Nul</td> <td>Pio</td></tr>'));
		$this->assertEqual($result, $expected);
	}

/**
 * Enter description here...
 *
 */
	function testImageTag()
	{
		$result   = $this->html->imageTag('foo.gif');
		$expected = '<img src="/ease'.IMAGES_URL.'foo.gif" alt="" />';
		$this->assertEqual($result, $expected);

		$result   = $this->html->imageTag('bar/baz.gif', 'Foobar', array('class'=>'Zet'));
		$expected = '<img src="/ease'.IMAGES_URL.'bar/baz.gif" alt="Foobar" class="Zet" />';
		$this->assertEqual($result, $expected);
	}

/**
 * Enter description here...
 *
 */
	function testTagValue()
	{
		$result   = $this->html->tagValue('foo');
		$expected = 'foo_value';
		$this->assertEqual($result, $expected);

		$result   = $this->html->tagValue('bar');
		$expected = false;
		$this->assertEqual($result, $expected);
	}

	/**
	 * Enter description here...
	 *
	 */
	function testGetCrumbs()
	{
		$this->html->addCrumb('Foo', '/bar/foo');
		$result   = $this->html->getCrumbs();
		$expected = '<a href="/ease">START</a>&raquo;<a href="/ease/bar/foo">Foo</a>';
		$this->assertEqual($result, $expected);
	}

}

?>