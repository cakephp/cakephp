<?php
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         3.0.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\View;

use Cake\Cache\Cache;
use Cake\Core\Configure;
use Cake\Core\Plugin;
use Cake\TestSuite\TestCase;
use TestApp\View\CustomJsonView;

/**
 * CellTest class.
 *
 * For testing both View\Cell & Utility\CellTrait
 */
class CellTest extends TestCase
{

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        Configure::write('App.namespace', 'TestApp');
        Plugin::load(['TestPlugin', 'TestTheme']);
        $request = $this->getMockBuilder('Cake\Network\Request')->getMock();
        $response = $this->getMockBuilder('Cake\Network\Response')->getMock();
        $this->View = new \Cake\View\View($request, $response);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown()
    {
        parent::tearDown();
        Plugin::unload('TestPlugin');
        Plugin::unload('TestTheme');
        unset($this->View);
    }

    /**
     * Tests basic cell rendering.
     *
     * @return void
     */
    public function testCellRender()
    {
        $cell = $this->View->cell('Articles::teaserList');
        $render = "{$cell}";

        $this->assertEquals('teaser_list', $cell->template);
        $this->assertContains('<h2>Lorem ipsum</h2>', $render);
        $this->assertContains('<h2>Usectetur adipiscing eli</h2>', $render);
        $this->assertContains('<h2>Topis semper blandit eu non</h2>', $render);
        $this->assertContains('<h2>Suspendisse gravida neque</h2>', $render);

        $cell = $this->View->cell('Cello');
        $this->assertInstanceOf('TestApp\View\Cell\CelloCell', $cell);
        $this->assertEquals("Cellos\n", $cell->render());
    }

    /**
     * Test __toString() hitting an error when rendering views.
     *
     * @return void
     */
    public function testCellImplictRenderWithError()
    {
        $capture = function ($errno, $msg) {
            restore_error_handler();
            $this->assertEquals(E_USER_WARNING, $errno);
            $this->assertContains('Could not render cell - Cell view file', $msg);
        };
        set_error_handler($capture);

        $cell = $this->View->cell('Articles::teaserList');
        $cell->template = 'nope';
        $result = "{$cell}";
    }

    /**
     * Tests that we are able pass multiple arguments to cell methods.
     *
     * This test sets its own error handler, as PHPUnit won't convert
     * errors into exceptions when the caller is a __toString() method.
     *
     * @return void
     */
    public function testCellWithArguments()
    {
        $cell = $this->View->cell('Articles::doEcho', ['msg1' => 'dummy', 'msg2' => ' message']);
        $render = "{$cell}";
        $this->assertContains('dummy message', $render);
    }

    /**
     * Tests that cell runs default action when none is provided.
     *
     * @return void
     */
    public function testDefaultCellAction()
    {
        $appCell = $this->View->cell('Articles');

        $this->assertEquals('display', $appCell->template);
        $this->assertContains('dummy', "{$appCell}");

        $pluginCell = $this->View->cell('TestPlugin.Dummy');
        $this->assertContains('dummy', "{$pluginCell}");
        $this->assertEquals('display', $pluginCell->template);
    }

    /**
     * Tests that cell action setting the template using the property renders the correct template
     *
     * @return void
     */
    public function testSettingCellTemplateFromAction()
    {
        $appCell = $this->View->cell('Articles::customTemplate');

        $this->assertContains('This is the alternate template', "{$appCell}");
        $this->assertEquals('alternate_teaser_list', $appCell->template);
        $this->assertEquals('alternate_teaser_list', $appCell->viewBuilder()->template());
    }

    /**
     * Tests that cell action setting the templatePath
     *
     * @return void
     */
    public function testSettingCellTemplatePathFromAction()
    {
        $appCell = $this->View->cell('Articles::customTemplatePath');

        $this->assertContains('Articles subdir custom_template_path template', "{$appCell}");
        $this->assertEquals('custom_template_path', $appCell->template);
        $this->assertEquals('Cell/Articles/Subdir', $appCell->viewBuilder()->templatePath());
    }

    /**
     * Tests that cell action setting the template using the ViewBuilder renders the correct template
     *
     * @return void
     */
    public function testSettingCellTemplateFromActionViewBuilder()
    {
        $appCell = $this->View->cell('Articles::customTemplateViewBuilder');

        $this->assertContains('This is the alternate template', "{$appCell}");
        $this->assertEquals('alternate_teaser_list', $appCell->viewBuilder()->template());
    }

    /**
     * Tests manual render() invocation.
     *
     * @return void
     */
    public function testCellManualRender()
    {
        $cell = $this->View->cell('Articles::doEcho', ['msg1' => 'dummy', 'msg2' => ' message']);
        $this->assertContains('dummy message', $cell->render());

        $cell->teaserList();
        $this->assertContains('<h2>Lorem ipsum</h2>', $cell->render('teaser_list'));
    }

    /**
     * Tests manual render() invocation with error
     *
     * @expectedException \Cake\View\Exception\MissingCellViewException
     * @return void
     */
    public function testCellManualRenderError()
    {
        $cell = $this->View->cell('Articles');
        $cell->render('derp');
    }

    /**
     * Test rendering a cell with a theme.
     *
     * @return void
     */
    public function testCellRenderThemed()
    {
        $this->View->theme = 'TestTheme';
        $cell = $this->View->cell('Articles', ['msg' => 'hello world!']);

        $this->assertEquals($this->View->theme, $cell->viewBuilder()->theme());
        $this->assertContains('Themed cell content.', $cell->render());
        $this->assertEquals($cell->View->theme, $cell->viewBuilder()->theme());
    }

    /**
     * Test that a cell can render a plugin view.
     *
     * @return void
     */
    public function testCellRenderPluginTemplate()
    {
        $cell = $this->View->cell('Articles');
        $this->assertContains(
            'TestPlugin Articles/display',
            $cell->render('TestPlugin.display')
        );

        $cell = $this->View->cell('Articles');
        $cell->plugin = 'TestPlugin';
        $this->assertContains(
            'TestPlugin Articles/display',
            $cell->render('display')
        );
    }

    /**
     * Tests that using plugin's cells works.
     *
     * @return void
     */
    public function testPluginCell()
    {
        $cell = $this->View->cell('TestPlugin.Dummy::echoThis', ['msg' => 'hello world!']);
        $this->assertContains('hello world!', "{$cell}");
    }

    /**
     * Tests that using namespaced cells works.
     *
     * @return void
     */
    public function testNamespacedCell()
    {
        $cell = $this->View->cell('Admin/Menu');
        $this->assertContains('Admin Menu Cell', $cell->render());
    }

    /**
     * Tests that using namespaced cells in plugins works
     *
     * @return void
     */
    public function testPluginNamespacedCell()
    {
        $cell = $this->View->cell('TestPlugin.Admin/Menu');
        $this->assertContains('Test Plugin Admin Menu Cell', $cell->render());
    }

    /**
     * Test that plugin cells can render other view templates.
     *
     * @return void
     */
    public function testPluginCellAlternateTemplate()
    {
        $cell = $this->View->cell('TestPlugin.Dummy::echoThis', ['msg' => 'hello world!']);
        $cell->template = '../../Element/translate';
        $this->assertContains('This is a translatable string', "{$cell}");
    }

    /**
     * Test that plugin cells can render other view templates.
     *
     * @return void
     */
    public function testPluginCellAlternateTemplateRenderParam()
    {
        $cell = $this->View->cell('TestPlugin.Dummy::echoThis', ['msg' => 'hello world!']);
        $result = $cell->render('../../Element/translate');
        $this->assertContains('This is a translatable string', $result);
    }

    /**
     * Tests that using an unexisting cell throws an exception.
     *
     * @expectedException \Cake\View\Exception\MissingCellException
     * @return void
     */
    public function testUnexistingCell()
    {
        $cell = $this->View->cell('TestPlugin.Void::echoThis', ['arg1' => 'v1']);
        $cell = $this->View->cell('Void::echoThis', ['arg1' => 'v1', 'arg2' => 'v2']);
    }

    /**
     * Tests missing method errors
     *
     * @expectedException \BadMethodCallException
     * @expectedExceptionMessage Class TestApp\View\Cell\ArticlesCell does not have a "nope" method.
     * @return void
     */
    public function testCellMissingMethod()
    {
        $cell = $this->View->cell('Articles::nope');
        $cell->render();
    }

    /**
     * Test that cell options are passed on.
     *
     * @return void
     */
    public function testCellOptions()
    {
        $cell = $this->View->cell('Articles', [], ['limit' => 10, 'nope' => 'nope']);
        $this->assertEquals(10, $cell->limit);
        $this->assertFalse(property_exists('nope', $cell), 'Not a valid option');
    }

    /**
     * Test that cells get the helper configuration from the view that created them.
     *
     * @return void
     */
    public function testCellInheritsHelperConfig()
    {
        $this->View->helpers = ['Url', 'Form', 'Banana'];
        $cell = $this->View->cell('Articles');
        $this->assertSame($this->View->helpers, $cell->helpers);
    }

    /**
     * Test that cells the view class name of a custom view passed on.
     *
     * @return void
     */
    public function testCellInheritsCustomViewClass()
    {
        $request = $this->getMockBuilder('Cake\Network\Request')->getMock();
        $response = $this->getMockBuilder('Cake\Network\Response')->getMock();
        $view = new CustomJsonView($request, $response);
        $cell = $view->cell('Articles');
        $this->assertSame('TestApp\View\CustomJsonView', $cell->viewClass);
    }

    /**
     * Test cached render.
     *
     * @return void
     */
    public function testCachedRenderSimple()
    {
        $mock = $this->getMockBuilder('Cake\Cache\CacheEngine')->getMock();
        $mock->method('init')
            ->will($this->returnValue(true));
        $mock->method('read')
            ->will($this->returnValue(false));
        $mock->expects($this->once())
            ->method('write')
            ->with('cell_test_app_view_cell_articles_cell_display_default', "dummy\n");
        Cache::config('default', $mock);

        $cell = $this->View->cell('Articles', [], ['cache' => true]);
        $result = $cell->render();
        $this->assertEquals("dummy\n", $result);
        Cache::drop('default');
    }

    /**
     * Test read cached cell.
     *
     * @return void
     */
    public function testReadCachedCell()
    {
        $mock = $this->getMockBuilder('Cake\Cache\CacheEngine')->getMock();
        $mock->method('init')
            ->will($this->returnValue(true));
        $mock->method('read')
            ->will($this->returnValue("dummy\n"));
        $mock->expects($this->never())
            ->method('write');
        Cache::config('default', $mock);

        $cell = $this->View->cell('Articles', [], ['cache' => true]);
        $result = $cell->render();
        $this->assertEquals("dummy\n", $result);
        Cache::drop('default');
    }

    /**
     * Test cached render array config
     *
     * @return void
     */
    public function testCachedRenderArrayConfig()
    {
        $mock = $this->getMockBuilder('Cake\Cache\CacheEngine')->getMock();
        $mock->method('init')
            ->will($this->returnValue(true));
        $mock->method('read')
            ->will($this->returnValue(false));
        $mock->expects($this->once())
            ->method('write')
            ->with('my_key', "dummy\n");
        Cache::config('cell', $mock);

        $cell = $this->View->cell('Articles', [], [
            'cache' => ['key' => 'my_key', 'config' => 'cell']
        ]);
        $result = $cell->render();
        $this->assertEquals("dummy\n", $result);
        Cache::drop('cell');
    }

    /**
     * Test cached render when using an action changing the template used
     *
     * @return void
     */
    public function testCachedRenderSimpleCustomTemplate()
    {
        $mock = $this->getMockBuilder('Cake\Cache\CacheEngine')->getMock();
        $mock->method('init')
            ->will($this->returnValue(true));
        $mock->method('read')
            ->will($this->returnValue(false));
        $mock->expects($this->once())
            ->method('write')
            ->with('cell_test_app_view_cell_articles_cell_customTemplate_default', "<h1>This is the alternate template</h1>\n");
        Cache::config('default', $mock);

        $cell = $this->View->cell('Articles::customTemplate', [], ['cache' => true]);
        $result = $cell->render();
        $this->assertContains('This is the alternate template', $result);

        Cache::drop('default');
    }

    /**
     * Test that when the cell cache is enabled, the cell action is only invoke the first
     * time the cell is rendered
     *
     * @return void
     */
    public function testCachedRenderSimpleCustomTemplateViewBuilder()
    {
        Cache::config('default', [
            'className' => 'File',
            'path' => CACHE,
        ]);
        $cell = $this->View->cell('Articles::customTemplateViewBuilder', [], ['cache' => ['key' => 'celltest']]);
        $result = $cell->render();
        $this->assertEquals(1, $cell->counter);
        $cell->render();
        $this->assertEquals(1, $cell->counter);
        $this->assertContains('This is the alternate template', $result);
        Cache::delete('celltest');
        Cache::drop('default');
    }

    /**
     * Test that when the cell cache is enabled, the cell action is only invoke the first
     * time the cell is rendered
     *
     * @return void
     */
    public function testACachedViewCellReRendersWhenGivenADifferentTemplate()
    {
        Cache::config('default', [
            'className' => 'File',
            'path' => CACHE,
        ]);
        $cell = $this->View->cell('Articles::customTemplateViewBuilder', [], ['cache' => true]);
        $result = $cell->render("alternate_teaser_list");
        $result2 = $cell->render("not_the_alternate_teaser_list");
        $this->assertContains('This is the alternate template', $result);
        $this->assertContains('This is NOT the alternate template', $result2);
        Cache::delete('celltest');
        Cache::drop('default');
    }
}
