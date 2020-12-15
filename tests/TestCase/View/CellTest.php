<?php
declare(strict_types=1);

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
use Cake\Http\Response;
use Cake\Http\ServerRequest;
use Cake\TestSuite\TestCase;
use Cake\View\Cell;
use Cake\View\Exception\MissingCellTemplateException;
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
    protected $View;

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        static::setAppNamespace();
        $this->loadPlugins(['TestPlugin', 'TestTheme']);
        $request = new ServerRequest();
        $response = new Response();
        $this->View = new View($request, $response);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown(): void
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

        $this->assertSame('teaser_list', $cell->viewBuilder()->getTemplate());
        $this->assertStringContainsString('<h2>Lorem ipsum</h2>', $render);
        $this->assertStringContainsString('<h2>Usectetur adipiscing eli</h2>', $render);
        $this->assertStringContainsString('<h2>Topis semper blandit eu non</h2>', $render);
        $this->assertStringContainsString('<h2>Suspendisse gravida neque</h2>', $render);

        $cell = $this->View->cell('Cello');
        $this->assertInstanceOf('TestApp\View\Cell\CelloCell', $cell);
        $this->assertSame("Cellos\n", $cell->render());
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
        $this->assertSame('teaserList', $data['action']);
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
            $this->assertSame(E_USER_WARNING, $errno);
            $this->assertStringContainsString('Could not render cell - Cell template file', $msg);
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
        $this->assertStringContainsString('dummy message', $render);
    }

    /**
     * Tests that cell runs default action when none is provided.
     *
     * @return void
     */
    public function testDefaultCellAction()
    {
        $appCell = $this->View->cell('Articles');

        $this->assertSame('display', $appCell->viewBuilder()->getTemplate());
        $this->assertStringContainsString('dummy', "{$appCell}");

        $pluginCell = $this->View->cell('TestPlugin.Dummy');
        $this->assertStringContainsString('dummy', "{$pluginCell}");
        $this->assertSame('display', $pluginCell->viewBuilder()->getTemplate());
    }

    /**
     * Tests that cell action setting the templatePath
     *
     * @return void
     */
    public function testSettingCellTemplatePathFromAction()
    {
        $appCell = $this->View->cell('Articles::customTemplatePath');

        $this->assertStringContainsString('Articles subdir custom_template_path template', "{$appCell}");
        $this->assertSame('custom_template_path', $appCell->viewBuilder()->getTemplate());
        $this->assertSame(Cell::TEMPLATE_FOLDER . '/Articles/Subdir', $appCell->viewBuilder()->getTemplatePath());
    }

    /**
     * Tests that cell action setting the template using the ViewBuilder renders the correct template
     *
     * @return void
     */
    public function testSettingCellTemplateFromActionViewBuilder()
    {
        $appCell = $this->View->cell('Articles::customTemplateViewBuilder');

        $this->assertStringContainsString('This is the alternate template', "{$appCell}");
        $this->assertSame('alternate_teaser_list', $appCell->viewBuilder()->getTemplate());
    }

    /**
     * Tests manual render() invocation.
     *
     * @return void
     */
    public function testCellManualRender()
    {
        /** @var \TestApp\View\Cell\ArticlesCell $cell */
        $cell = $this->View->cell('Articles::doEcho', ['msg1' => 'dummy', 'msg2' => ' message']);
        $this->assertStringContainsString('dummy message', $cell->render());

        $cell->teaserList();
        $this->assertStringContainsString('<h2>Lorem ipsum</h2>', $cell->render('teaser_list'));
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
        } catch (MissingCellTemplateException $e) {
        }

        $this->assertNotNull($e);
        $message = $e->getMessage();
        $this->assertStringContainsString(
            str_replace('/', DS, 'Cell template file `cell/Articles/foo_bar.php` could not be found.'),
            $message
        );
        $this->assertStringContainsString('The following paths', $message);
        $this->assertStringContainsString(ROOT . DS . 'templates', $message);
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
        $this->assertStringContainsString('Themed cell content.', $cell->render());
    }

    /**
     * Test that a cell can render a plugin view.
     *
     * @return void
     */
    public function testCellRenderPluginTemplate()
    {
        $cell = $this->View->cell('Articles');
        $this->assertStringContainsString(
            'TestPlugin Articles/display',
            $cell->render('TestPlugin.display')
        );

        $cell = $this->View->cell('Articles');
        $cell->viewBuilder()->setPlugin('TestPlugin');
        $this->assertStringContainsString(
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
        $this->assertStringContainsString('hello world!', "{$cell}");
    }

    /**
     * Tests that using namespaced cells works.
     *
     * @return void
     */
    public function testNamespacedCell()
    {
        $cell = $this->View->cell('Admin/Menu');
        $this->assertStringContainsString('Admin Menu Cell', $cell->render());
    }

    /**
     * Tests that using namespaced cells in plugins works
     *
     * @return void
     */
    public function testPluginNamespacedCell()
    {
        $cell = $this->View->cell('TestPlugin.Admin/Menu');
        $this->assertStringContainsString('Test Plugin Admin Menu Cell', $cell->render());
    }

    /**
     * Test that plugin cells can render other view templates.
     *
     * @return void
     */
    public function testPluginCellAlternateTemplate()
    {
        $cell = $this->View->cell('TestPlugin.Dummy::echoThis', ['msg' => 'hello world!']);
        $cell->viewBuilder()->setTemplate('../../element/translate');
        $this->assertStringContainsString('This is a translatable string', "{$cell}");
    }

    /**
     * Test that plugin cells can render other view templates.
     *
     * @return void
     */
    public function testPluginCellAlternateTemplateRenderParam()
    {
        $cell = $this->View->cell('TestPlugin.Dummy::echoThis', ['msg' => 'hello world!']);
        $result = $cell->render('../../element/translate');
        $this->assertStringContainsString('This is a translatable string', $result);
    }

    /**
     * Tests that using an nonexistent cell throws an exception.
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
        $this->assertSame(10, $cell->limit);
        $this->assertObjectNotHasAttribute('nope', $cell, 'Not a valid option');
    }

    /**
     * Test that cells get the helper configuration from the view that created them.
     *
     * @return void
     */
    public function testCellInheritsHelperConfig()
    {
        $request = new ServerRequest();
        $response = new Response();
        $helpers = ['Url', 'Form', 'Banana'];

        $view = new View($request, $response, null, ['helpers' => $helpers]);

        $cell = $view->cell('Articles');
        $this->assertSame($helpers, $cell->viewBuilder()->getHelpers());
    }

    /**
     * Test that cells the view class name of a custom view passed on.
     *
     * @return void
     */
    public function testCellInheritsCustomViewClass()
    {
        $request = new ServerRequest();
        $response = new Response();
        $view = new CustomJsonView($request, $response);
        $view->setTheme('Pretty');
        $cell = $view->cell('Articles');
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
        $request = new ServerRequest();
        $response = new Response();
        $controller = new CellTraitTestController($request, $response);
        $controller->viewBuilder()->setTheme('Pretty');
        $controller->viewBuilder()->setClassName('Json');
        $cell = $controller->cell('Articles');
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
        Cache::setConfig('default', ['className' => 'Array']);

        $cell = $this->View->cell('Articles', [], ['cache' => true]);
        $result = $cell->render();
        $expected = "dummy\n";
        $this->assertSame($expected, $result);

        $result = Cache::read('cell_test_app_view_cell_articles_cell_display_default', 'default');
        $this->assertSame($expected, $result);
        Cache::drop('default');
    }

    /**
     * Test read cached cell.
     *
     * @return void
     */
    public function testReadCachedCell()
    {
        Cache::setConfig('default', ['className' => 'Array']);
        Cache::write('cell_test_app_view_cell_articles_cell_display_default', 'from cache');

        $cell = $this->View->cell('Articles', [], ['cache' => true]);
        $result = $cell->render();
        $this->assertSame('from cache', $result);
        Cache::drop('default');
    }

    /**
     * Test cached render array config
     *
     * @return void
     */
    public function testCachedRenderArrayConfig()
    {
        Cache::setConfig('cell', ['className' => 'Array']);
        Cache::write('my_key', 'from cache', 'cell');

        $cell = $this->View->cell('Articles', [], [
            'cache' => ['key' => 'my_key', 'config' => 'cell'],
        ]);
        $result = $cell->render();
        $this->assertSame('from cache', $result);
        Cache::drop('cell');
    }

    /**
     * Test cached render when using an action changing the template used
     *
     * @return void
     */
    public function testCachedRenderSimpleCustomTemplate()
    {
        Cache::setConfig('default', ['className' => 'Array']);

        $cell = $this->View->cell('Articles::customTemplateViewBuilder', [], ['cache' => true]);
        $result = $cell->render();
        $expected = 'This is the alternate template';
        $this->assertStringContainsString($expected, $result);

        $result = Cache::read('cell_test_app_view_cell_articles_cell_customTemplateViewBuilder_default');
        $this->assertStringContainsString($expected, $result);
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
        Cache::setConfig('default', ['className' => 'Array']);
        $cell = $this->View->cell('Articles::customTemplateViewBuilder', [], ['cache' => ['key' => 'celltest']]);
        $result = $cell->render();
        $this->assertSame(1, $cell->counter);
        $cell->render();

        $this->assertSame(1, $cell->counter);
        $this->assertStringContainsString('This is the alternate template', $result);
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
        Cache::setConfig('default', ['className' => 'Array']);
        $cell = $this->View->cell('Articles::customTemplateViewBuilder', [], ['cache' => true]);
        $result = $cell->render('alternate_teaser_list');
        $result2 = $cell->render('not_the_alternate_teaser_list');
        $this->assertStringContainsString('This is the alternate template', $result);
        $this->assertStringContainsString('This is NOT the alternate template', $result2);
        Cache::delete('celltest');
        Cache::drop('default');
    }
}
