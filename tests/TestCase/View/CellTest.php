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

use BadMethodCallException;
use Cake\Cache\Cache;
use Cake\Http\Response;
use Cake\Http\ServerRequest;
use Cake\TestSuite\TestCase;
use Cake\View\Cell;
use Cake\View\Exception\MissingCellException;
use Cake\View\Exception\MissingCellTemplateException;
use Cake\View\Exception\MissingTemplateException;
use Cake\View\View;
use TestApp\Controller\CellTraitTestController;
use TestApp\View\Cell\CelloCell;
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
     */
    public function setUp(): void
    {
        parent::setUp();
        static::setAppNamespace();
        $this->clearPlugins();
        $this->loadPlugins(['TestPlugin', 'TestTheme']);
        $request = new ServerRequest();
        $response = new Response();
        $this->View = new View($request, $response);
    }

    /**
     * tearDown method
     */
    public function tearDown(): void
    {
        parent::tearDown();
        unset($this->View);
    }

    /**
     * Tests basic cell rendering.
     */
    public function testCellRender(): void
    {
        $cell = $this->View->cell('Articles::teaserList');
        $render = "{$cell}";

        $this->assertSame('teaser_list', $cell->viewBuilder()->getTemplate());
        $this->assertStringContainsString('<h2>Lorem ipsum</h2>', $render);
        $this->assertStringContainsString('<h2>Usectetur adipiscing eli</h2>', $render);
        $this->assertStringContainsString('<h2>Topis semper blandit eu non</h2>', $render);
        $this->assertStringContainsString('<h2>Suspendisse gravida neque</h2>', $render);

        $cell = $this->View->cell('Cello');
        $this->assertInstanceOf(CelloCell::class, $cell);
        $this->assertSame("Cellos\n", $cell->render());
    }

    /**
     * Tests debug output.
     */
    public function testDebugInfo(): void
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
     */
    public function testCellImplictRenderWithError(): void
    {
        $capture = function ($errno, $msg): void {
            restore_error_handler();
            $this->assertSame(E_USER_WARNING, $errno);
            $this->assertStringContainsString('Could not render cell - Cell template file', $msg);
        };
        set_error_handler($capture);

        $cell = $this->View->cell('Articles::teaserList');
        $cell->viewBuilder()->setTemplate('nope');
        (string)$cell;
    }

    /**
     * Tests that we are able pass multiple arguments to cell methods.
     *
     * This test sets its own error handler, as PHPUnit won't convert
     * errors into exceptions when the caller is a __toString() method.
     */
    public function testCellWithArguments(): void
    {
        $cell = $this->View->cell('Articles::doEcho', ['dummy', ' message']);
        $render = "{$cell}";
        $this->assertStringContainsString('dummy message', $render);
    }

    public function testCellWithNamedArguments(): void
    {
        $cell = $this->View->cell('Articles::doEcho', ['msg1' => 'dummy', 'msg2' => ' message']);
        $render = "{$cell}";
        $this->assertStringContainsString('dummy message', $render);

        $cell = $this->View->cell('Articles::doEcho', ['msg2' => ' dummy', 'msg1' => 'message']);
        $render = "{$cell}";
        $this->assertStringContainsString('message dummy', $render);
    }

    /**
     * Tests that cell runs default action when none is provided.
     */
    public function testDefaultCellAction(): void
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
     */
    public function testSettingCellTemplatePathFromAction(): void
    {
        $appCell = $this->View->cell('Articles::customTemplatePath');

        $this->assertStringContainsString('Articles subdir custom_template_path template', "{$appCell}");
        $this->assertSame('custom_template_path', $appCell->viewBuilder()->getTemplate());
        $this->assertSame(Cell::TEMPLATE_FOLDER . '/Articles/Subdir', $appCell->viewBuilder()->getTemplatePath());
    }

    /**
     * Tests that cell action setting the template using the ViewBuilder renders the correct template
     */
    public function testSettingCellTemplateFromActionViewBuilder(): void
    {
        $appCell = $this->View->cell('Articles::customTemplateViewBuilder');

        $this->assertStringContainsString('This is the alternate template', "{$appCell}");
        $this->assertSame('alternate_teaser_list', $appCell->viewBuilder()->getTemplate());
    }

    /**
     * Tests manual render() invocation.
     */
    public function testCellManualRender(): void
    {
        /** @var \TestApp\View\Cell\ArticlesCell $cell */
        $cell = $this->View->cell('Articles::doEcho', ['msg1' => 'dummy', 'msg2' => ' message']);
        $this->assertStringContainsString('dummy message', $cell->render());

        $cell->teaserList();
        $this->assertStringContainsString('<h2>Lorem ipsum</h2>', $cell->render('teaser_list'));
    }

    /**
     * Tests manual render() invocation with error
     */
    public function testCellManualRenderError(): void
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
     */
    public function testCellRenderThemed(): void
    {
        $this->View->setTheme('TestTheme');
        $cell = $this->View->cell('Articles');

        $this->assertEquals($this->View->getTheme(), $cell->viewBuilder()->getTheme());
        $this->assertStringContainsString('Themed cell content.', $cell->render());
    }

    /**
     * Test that a cell can render a plugin view.
     */
    public function testCellRenderPluginTemplate(): void
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
     */
    public function testPluginCell(): void
    {
        $cell = $this->View->cell('TestPlugin.Dummy::echoThis', ['msg' => 'hello world!']);
        $this->assertStringContainsString('hello world!', "{$cell}");
    }

    /**
     * Tests that using namespaced cells works.
     */
    public function testNamespacedCell(): void
    {
        $cell = $this->View->cell('Admin/Menu');
        $this->assertStringContainsString('Admin Menu Cell', $cell->render());
    }

    /**
     * Tests that using namespaced cells in plugins works
     */
    public function testPluginNamespacedCell(): void
    {
        $cell = $this->View->cell('TestPlugin.Admin/Menu');
        $this->assertStringContainsString('Test Plugin Admin Menu Cell', $cell->render());
    }

    /**
     * Test that plugin cells can render other view templates.
     */
    public function testPluginCellAlternateTemplate(): void
    {
        $cell = $this->View->cell('TestPlugin.Dummy::echoThis', ['msg' => 'hello world!']);
        $cell->viewBuilder()->setTemplate('../../element/translate');
        $this->assertStringContainsString('This is a translatable string', "{$cell}");
    }

    /**
     * Test that plugin cells can render other view templates.
     */
    public function testPluginCellAlternateTemplateRenderParam(): void
    {
        $cell = $this->View->cell('TestPlugin.Dummy::echoThis', ['msg' => 'hello world!']);
        $result = $cell->render('../../element/translate');
        $this->assertStringContainsString('This is a translatable string', $result);
    }

    /**
     * Tests that using an nonexistent cell throws an exception.
     */
    public function testNonExistentCell(): void
    {
        $this->expectException(MissingCellException::class);
        $this->View->cell('Void::echoThis', ['arg1' => 'v1', 'arg2' => 'v2']);
    }

    /**
     * Tests missing method errors
     */
    public function testCellMissingMethod(): void
    {
        $this->expectException(BadMethodCallException::class);
        $this->expectExceptionMessage('Class `TestApp\View\Cell\ArticlesCell` does not have a `nope` method.');
        $cell = $this->View->cell('Articles::nope');
        $cell->render();
    }

    /**
     * Test that cell options are passed on.
     */
    public function testCellOptions(): void
    {
        /** @var \TestApp\View\Cell\ArticlesCell $cell */
        $cell = $this->View->cell('Articles', [], ['limit' => 10, 'nope' => 'nope']);
        $this->assertSame(10, $cell->limit);
        $this->assertTrue(!isset($cell->nope), 'Not a valid option');
    }

    /**
     * Test that cells get the helper configuration from the view that created them.
     */
    public function testCellInheritsHelperConfig(): void
    {
        $request = new ServerRequest();
        $response = new Response();
        $helpers = ['Url', 'Form', 'Banana'];

        $view = new View($request, $response, null, ['helpers' => $helpers]);

        $cell = $view->cell('Articles');
        $expected = array_combine($helpers, [[], [], []]);
        $this->assertSame($expected, $cell->viewBuilder()->getHelpers());
    }

    /**
     * Test that cells the view class name of a custom view passed on.
     */
    public function testCellInheritsCustomViewClass(): void
    {
        $request = new ServerRequest();
        $response = new Response();
        $view = new CustomJsonView($request, $response);
        $view->setTheme('Pretty');
        $cell = $view->cell('Articles');
        $this->assertSame(CustomJsonView::class, $cell->viewBuilder()->getClassName());
        $this->assertSame('Pretty', $cell->viewBuilder()->getTheme());
    }

    /**
     * Test that cells the view class name of a controller passed on.
     */
    public function testCellInheritsController(): void
    {
        $request = new ServerRequest();
        $controller = new CellTraitTestController($request);
        $controller->viewBuilder()->setTheme('Pretty');
        $controller->viewBuilder()->setClassName('Json');
        $cell = $controller->cell('Articles');
        $this->assertSame('Json', $cell->viewBuilder()->getClassName());
        $this->assertSame('Pretty', $cell->viewBuilder()->getTheme());
    }

    /**
     * Test cached render.
     */
    public function testCachedRenderSimple(): void
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
     */
    public function testReadCachedCell(): void
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
     */
    public function testCachedRenderArrayConfig(): void
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
     */
    public function testCachedRenderSimpleCustomTemplate(): void
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
     */
    public function testCachedRenderSimpleCustomTemplateViewBuilder(): void
    {
        Cache::setConfig('default', ['className' => 'Array']);
        /** @var \TestApp\View\Cell\ArticlesCell $cell */
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
     */
    public function testACachedViewCellReRendersWhenGivenADifferentTemplate(): void
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

    /**
     * Tests events are dispatched correctly
     */
    public function testCellRenderDispatchesEvents(): void
    {
        $args = ['msg1' => 'dummy', 'msg2' => ' message'];
        /** @var \TestApp\View\Cell\ArticlesCell $cell */
        $cell = $this->View->cell('Articles::doEcho', $args);
        $beforeEventIsCalled = false;
        $afterEventIsCalled = false;
        $manager = $this->View->getEventManager();
        $manager->on('Cell.beforeAction', function ($event, $eventCell, $action, $eventArgs) use ($cell, $args, &$beforeEventIsCalled): void {
            $this->assertSame($eventCell, $cell);
            $this->assertEquals('doEcho', $action);
            $this->assertEquals($args, $eventArgs);
            $beforeEventIsCalled = true;
        });
        $manager->on('Cell.afterAction', function ($event, $eventCell, $action, $eventArgs) use ($cell, $args, &$afterEventIsCalled): void {
            $this->assertSame($eventCell, $cell);
            $this->assertEquals('doEcho', $action);
            $this->assertEquals($args, $eventArgs);
            $afterEventIsCalled = true;
        });
        $cell->render();
        $this->assertTrue($beforeEventIsCalled);
        $this->assertTrue($afterEventIsCalled);
    }
}
