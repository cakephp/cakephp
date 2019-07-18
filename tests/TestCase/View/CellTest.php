<?php
/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         3.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\View;

use Cake\Cache\Cache;
use Cake\Core\Plugin;
use Cake\TestSuite\TestCase;
use Cake\View\Exception\MissingCellViewException;
use Cake\View\Exception\MissingTemplateException;
use Cake\View\View;
use TestApp\Controller\CellTraitTestController;
use TestApp\View\CustomJsonView;

/**
 * CellTest class.
 *
 * For testing both View\Cell & Utility\CellTrait
 */
class CellTest extends TestCase
{

    /**
     * @var \Cake\View\View
     */
    public $View;

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        static::setAppNamespace();
        $this->loadPlugins(['TestPlugin', 'TestTheme']);
        $request = $this->getMockBuilder('Cake\Http\ServerRequest')->getMock();
        $response = $this->getMockBuilder('Cake\Http\Response')->getMock();
        $this->View = new View($request, $response);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown()
    {
        parent::tearDown();
        $this->clearPlugins();
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

        $this->assertEquals('teaser_list', $cell->viewBuilder()->getTemplate());
        $this->assertContains('<h2>Lorem ipsum</h2>', $render);
        $this->assertContains('<h2>Usectetur adipiscing eli</h2>', $render);
        $this->assertContains('<h2>Topis semper blandit eu non</h2>', $render);
        $this->assertContains('<h2>Suspendisse gravida neque</h2>', $render);

        $cell = $this->View->cell('Cello');
        $this->assertInstanceOf('TestApp\View\Cell\CelloCell', $cell);
        $this->assertEquals("Cellos\n", $cell->render());
    }

    /**
     * Tests debug output.
     *
     * @return void
     */
    public function testDebugInfo()
    {
        $cell = $this->View->cell('Articles::teaserList');
        $data = $cell->__debugInfo();
        $this->assertArrayHasKey('request', $data);
        $this->assertArrayHasKey('response', $data);
        $this->assertEquals('teaserList', $data['action']);
        $this->assertEquals([], $data['args']);
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
        $cell->viewBuilder()->setTemplate('nope');
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

        $this->assertEquals('display', $appCell->viewBuilder()->getTemplate());
        $this->assertContains('dummy', "{$appCell}");

        $pluginCell = $this->View->cell('TestPlugin.Dummy');
        $this->assertContains('dummy', "{$pluginCell}");
        $this->assertEquals('display', $pluginCell->viewBuilder()->getTemplate());
    }

    /**
     * Tests that cell action setting the template using the property renders the correct template
     *
     * @return void
     */
    public function testSettingCellTemplateFromAction()
    {
        $this->deprecated(function () {
            $appCell = $this->View->cell('Articles::customTemplate');

            $this->assertContains('This is the alternate template', "{$appCell}");
            $this->assertEquals('alternate_teaser_list', $appCell->viewBuilder()->getTemplate());
            $this->assertEquals('alternate_teaser_list', $appCell->template);
        });
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
        $this->assertEquals('custom_template_path', $appCell->viewBuilder()->getTemplate());
        $this->assertEquals('Cell/Articles/Subdir', $appCell->viewBuilder()->getTemplatePath());
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
        $this->assertEquals('alternate_teaser_list', $appCell->viewBuilder()->getTemplate());
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
     * @return void
     */
    public function testCellManualRenderError()
    {
        $cell = $this->View->cell('Articles');

        $e = null;
        try {
            $cell->render('fooBar');
        } catch (MissingCellViewException $e) {
        }

        $this->assertNotNull($e);
        $this->assertEquals('Cell view file "foo_bar.ctp" is missing.', $e->getMessage());
        $this->assertInstanceOf(MissingTemplateException::class, $e->getPrevious());
    }

    /**
     * Test rendering a cell with a theme.
     *
     * @return void
     */
    public function testCellRenderThemed()
    {
        $this->View->setTheme('TestTheme');
        $cell = $this->View->cell('Articles', ['msg' => 'hello world!']);

        $this->assertEquals($this->View->getTheme(), $cell->viewBuilder()->getTheme());
        $this->assertContains('Themed cell content.', $cell->render());
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
        $cell->viewBuilder()->setPlugin('TestPlugin');
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
        $cell->viewBuilder()->setTemplate('../../Element/translate');
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
     * Tests that using an non-existent cell throws an exception.
     *
     * @return void
     */
    public function testNonExistentCell()
    {
        $this->expectException(\Cake\View\Exception\MissingCellException::class);
        $cell = $this->View->cell('TestPlugin.Void::echoThis', ['arg1' => 'v1']);
        $cell = $this->View->cell('Void::echoThis', ['arg1' => 'v1', 'arg2' => 'v2']);
    }

    /**
     * Tests missing method errors
     *
     * @return void
     */
    public function testCellMissingMethod()
    {
        $this->expectException(\BadMethodCallException::class);
        $this->expectExceptionMessage('Class TestApp\View\Cell\ArticlesCell does not have a "nope" method.');
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
        $this->assertObjectNotHasAttribute('nope', $cell, 'Not a valid option');
    }

    /**
     * Test that cells get the helper configuration from the view that created them.
     *
     * @return void
     */
    public function testCellInheritsHelperConfig()
    {
        $request = $this->getMockBuilder('Cake\Http\ServerRequest')->getMock();
        $response = $this->getMockBuilder('Cake\Http\Response')->getMock();
        $helpers = ['Url', 'Form', 'Banana'];

        $view = new View($request, $response, null, ['helpers' => $helpers]);

        $cell = $view->cell('Articles');
        $this->assertSame($helpers, $cell->viewBuilder()->getHelpers());

        $this->deprecated(function () use ($cell, $helpers) {
            $this->assertSame($helpers, $cell->helpers);
        });
    }

    /**
     * Test that cells the view class name of a custom view passed on.
     *
     * @return void
     */
    public function testCellInheritsCustomViewClass()
    {
        $request = $this->getMockBuilder('Cake\Http\ServerRequest')->getMock();
        $response = $this->getMockBuilder('Cake\Http\Response')->getMock();
        $view = new CustomJsonView($request, $response);
        $view->setTheme('Pretty');
        $cell = $view->cell('Articles');
        $this->assertSame('TestApp\View\CustomJsonView', $cell->viewClass);
        $this->assertSame('TestApp\View\CustomJsonView', $cell->viewBuilder()->getClassName());
        $this->assertSame('Pretty', $cell->viewBuilder()->getTheme());
    }

    /**
     * Test that cells the view class name of a controller passed on.
     *
     * @return void
     */
    public function testCellInheritsController()
    {
        $request = $this->getMockBuilder('Cake\Http\ServerRequest')->getMock();
        $response = $this->getMockBuilder('Cake\Http\Response')->getMock();
        $controller = new CellTraitTestController($request, $response);
        $controller->viewBuilder()->setTheme('Pretty');
        $controller->viewClass = 'Json';
        $cell = $controller->cell('Articles');
        $this->assertSame('Json', $cell->viewClass);
        $this->assertSame('Json', $cell->viewBuilder()->getClassName());
        $this->assertSame('Pretty', $cell->viewBuilder()->getTheme());
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
            ->with('cell_test_app_view_cell_articles_cell_display_default', "dummy\n")
            ->will($this->returnValue(true));
        Cache::setConfig('default', $mock);

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
        Cache::setConfig('default', $mock);

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
            ->with('my_key', "dummy\n")
            ->will($this->returnValue(true));
        Cache::setConfig('cell', $mock);

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
            ->with('cell_test_app_view_cell_articles_cell_customTemplate_default', "<h1>This is the alternate template</h1>\n")
            ->will($this->returnValue(true));
        Cache::setConfig('default', $mock);

        $this->deprecated(function () {
            $cell = $this->View->cell('Articles::customTemplate', [], ['cache' => true]);
            $result = $cell->render();
            $this->assertContains('This is the alternate template', $result);
        });

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
        Cache::setConfig('default', [
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
        Cache::setConfig('default', [
            'className' => 'File',
            'path' => CACHE,
        ]);
        $cell = $this->View->cell('Articles::customTemplateViewBuilder', [], ['cache' => true]);
        $result = $cell->render('alternate_teaser_list');
        $result2 = $cell->render('not_the_alternate_teaser_list');
        $this->assertContains('This is the alternate template', $result);
        $this->assertContains('This is NOT the alternate template', $result2);
        Cache::delete('celltest');
        Cache::drop('default');
    }
}
