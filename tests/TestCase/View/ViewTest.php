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
 * @since         1.2.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\View;

use Cake\Cache\Cache;
use Cake\Controller\Controller;
use Cake\Core\App;
use Cake\Core\Configure;
use Cake\Core\Plugin;
use Cake\Event\EventInterface;
use Cake\Http\Response;
use Cake\Http\ServerRequest;
use Cake\TestSuite\TestCase;
use Cake\View\View;
use RuntimeException;
use TestApp\Controller\ThemePostsController;
use TestApp\Controller\ViewPostsController;
use TestApp\View\Helper\TestBeforeAfterHelper;
use TestApp\View\Object\TestObjectWithoutToString;
use TestApp\View\Object\TestObjectWithToString;
use TestApp\View\TestView;
use TestApp\View\TestViewEventListenerInterface;

/**
 * ViewTest class
 */
class ViewTest extends TestCase
{
    /**
     * Fixtures used in this test.
     *
     * @var array
     */
    protected $fixtures = ['core.Posts', 'core.Users'];

    /**
     * @var \Cake\View\View
     */
    protected $View;

    /**
     * @var \TestApp\Controller\ViewPostsController
     */
    protected $PostsController;

    /**
     * @var \TestApp\Controller\ThemePostsController
     */
    protected $ThemePostsController;

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();

        $request = new ServerRequest();
        $this->Controller = new Controller($request);
        $this->PostsController = new ViewPostsController($request);
        $this->PostsController->index();
        $this->View = $this->PostsController->createView();
        $this->View->setTemplatePath('Posts');

        $themeRequest = new ServerRequest(['url' => 'posts/index']);
        $this->ThemeController = new Controller($themeRequest);
        $this->ThemePostsController = new ThemePostsController($themeRequest);
        $this->ThemePostsController->index();
        $this->ThemeView = $this->ThemePostsController->createView();
        $this->ThemeView->setTemplatePath('Posts');

        $this->loadPlugins(['TestPlugin', 'PluginJs', 'TestTheme', 'Company/TestPluginThree']);
        Configure::write('debug', true);
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
        unset($this->PostsController);
        unset($this->Controller);
        unset($this->ThemeView);
        unset($this->ThemePostsController);
        unset($this->ThemeController);
    }

    /**
     * Test getTemplateFileName method
     *
     * @return void
     */
    public function testGetTemplate()
    {
        $viewOptions = [
            'plugin' => null,
            'name' => 'Pages',
            'templatePath' => 'Pages',
        ];

        $ThemeView = new TestView(null, null, null, $viewOptions);
        $ThemeView->setTheme('TestTheme');
        $expected = TEST_APP . 'templates' . DS . 'Pages' . DS . 'home.php';
        $result = $ThemeView->getTemplateFileName('home');
        $this->assertPathEquals($expected, $result);

        $expected = Plugin::path('TestTheme') . 'templates' . DS . 'Posts' . DS . 'index.php';
        $result = $ThemeView->getTemplateFileName('/Posts/index');
        $this->assertPathEquals($expected, $result);

        $expected = Plugin::path('TestTheme') . 'templates' . DS . 'layout' . DS . 'default.php';
        $result = $ThemeView->getLayoutFileName();
        $this->assertPathEquals($expected, $result);

        $ThemeView->setLayoutPath('rss');
        $expected = TEST_APP . 'templates' . DS . 'layout' . DS . 'rss' . DS . 'default.php';
        $result = $ThemeView->getLayoutFileName();
        $this->assertPathEquals($expected, $result);

        $ThemeView->setLayoutPath('email' . DS . 'html');
        $expected = TEST_APP . 'templates' . DS . 'layout' . DS . 'email' . DS . 'html' . DS . 'default.php';
        $result = $ThemeView->getLayoutFileName();
        $this->assertPathEquals($expected, $result);

        $ThemeView = new TestView(null, null, null, $viewOptions);

        $ThemeView->setTheme('Company/TestPluginThree');
        $expected = Plugin::path('Company/TestPluginThree') . 'templates' . DS . 'layout' . DS . 'default.php';
        $result = $ThemeView->getLayoutFileName();
        $this->assertPathEquals($expected, $result);
    }

    /**
     * Test getLayoutFileName method on plugin
     *
     * @return void
     */
    public function testPluginGetTemplate()
    {
        $viewOptions = [
            'plugin' => 'TestPlugin',
            'name' => 'TestPlugin',
            'templatePath' => 'Tests',
            'template' => 'index',
        ];

        $View = new TestView(null, null, null, $viewOptions);

        $expected = Plugin::path('TestPlugin') . 'templates' . DS . 'Tests' . DS . 'index.php';
        $result = $View->getTemplateFileName('index');
        $this->assertSame($expected, $result);

        $expected = Plugin::path('TestPlugin') . 'templates' . DS . 'layout' . DS . 'default.php';
        $result = $View->getLayoutFileName();
        $this->assertSame($expected, $result);
    }

    /**
     * Test that plugin files with absolute file paths are scoped
     * to the plugin and do now allow any file path.
     *
     * @return void
     */
    public function testPluginGetTemplateAbsoluteFail()
    {
        $this->expectException(\Cake\View\Exception\MissingTemplateException::class);
        $viewOptions = [
            'plugin' => null,
            'name' => 'Pages',
            'viewPath' => 'Pages',
        ];

        $view = new TestView(null, null, null, $viewOptions);
        $expected = TEST_APP . 'Plugin' . DS . 'Company' . DS . 'TestPluginThree' . DS . 'templates' . DS . 'Pages' . DS . 'index.php';
        $result = $view->getTemplateFileName('Company/TestPluginThree./Pages/index');
        $this->assertPathEquals($expected, $result);

        $view->getTemplateFileName('Company/TestPluginThree./etc/passwd');
    }

    /**
     * Test getTemplateFileName method on plugin
     *
     * @return void
     */
    public function testPluginThemedGetTemplate()
    {
        $viewOptions = [
            'plugin' => 'TestPlugin',
            'name' => 'TestPlugin',
            'templatePath' => 'Tests',
            'template' => 'index',
            'theme' => 'TestTheme',
        ];

        $ThemeView = new TestView(null, null, null, $viewOptions);
        $themePath = Plugin::path('TestTheme') . 'templates' . DS;

        $expected = $themePath . 'plugin' . DS . 'TestPlugin' . DS . 'Tests' . DS . 'index.php';
        $result = $ThemeView->getTemplateFileName('index');
        $this->assertPathEquals($expected, $result);

        $expected = $themePath . 'plugin' . DS . 'TestPlugin' . DS . 'layout' . DS . 'plugin_default.php';
        $result = $ThemeView->getLayoutFileName('plugin_default');
        $this->assertPathEquals($expected, $result);

        $expected = $themePath . 'layout' . DS . 'default.php';
        $result = $ThemeView->getLayoutFileName('default');
        $this->assertPathEquals($expected, $result);
    }

    /**
     * Test that plugin/$plugin_name is only appended to the paths it should be.
     *
     * @return void
     */
    public function testPathPluginGeneration()
    {
        $viewOptions = [
            'plugin' => 'TestPlugin',
            'name' => 'TestPlugin',
            'viewPath' => 'Tests',
            'view' => 'index',
        ];

        $View = new TestView(null, null, null, $viewOptions);
        $paths = $View->paths();
        $expected = array_merge(App::path('templates'), App::core('templates'));
        $this->assertEquals($expected, $paths);

        $paths = $View->paths('TestPlugin');
        $pluginPath = Plugin::path('TestPlugin');
        $expected = [
            TEST_APP . 'templates' . DS . 'plugin' . DS . 'TestPlugin' . DS,
            $pluginPath . 'templates' . DS,
            TEST_APP . 'templates' . DS,
            CORE_PATH . 'templates' . DS,
        ];
        $this->assertPathEquals($expected, $paths);
    }

    /**
     * Test that themed plugin paths are generated correctly.
     *
     * @return void
     */
    public function testPathThemedPluginGeneration()
    {
        $viewOptions = [
            'plugin' => 'TestPlugin',
            'name' => 'TestPlugin',
            'viewPath' => 'Tests',
            'view' => 'index',
            'theme' => 'TestTheme',
        ];

        $View = new TestView(null, null, null, $viewOptions);
        $paths = $View->paths('TestPlugin');
        $pluginPath = Plugin::path('TestPlugin');
        $themePath = Plugin::path('TestTheme');
        $expected = [
            $themePath . 'templates' . DS . 'plugin' . DS . 'TestPlugin' . DS,
            $themePath . 'templates' . DS,
            TEST_APP . 'templates' . DS . 'plugin' . DS . 'TestPlugin' . DS,
            $pluginPath . 'templates' . DS,
            TEST_APP . 'templates' . DS,
            CORE_PATH . 'templates' . DS,
        ];
        $this->assertPathEquals($expected, $paths);
    }

    /**
     * Test that multiple paths can be used in App.paths.templates.
     *
     * @return void
     */
    public function testMultipleAppPaths()
    {
        $viewOptions = [
            'plugin' => 'TestPlugin',
            'name' => 'TestPlugin',
            'viewPath' => 'Tests',
            'view' => 'index',
            'theme' => 'TestTheme',
        ];

        $paths = Configure::read('App.paths.templates');
        $paths[] = Plugin::path('TestPlugin') . 'templates' . DS;
        Configure::write('App.paths.templates', $paths);

        $View = new TestView(null, null, null, $viewOptions);
        $paths = $View->paths('TestPlugin');
        $pluginPath = Plugin::path('TestPlugin');
        $themePath = Plugin::path('TestTheme');
        $expected = [
            $themePath . 'templates' . DS . 'plugin' . DS . 'TestPlugin' . DS,
            $themePath . 'templates' . DS,
            TEST_APP . 'templates' . DS . 'plugin' . DS . 'TestPlugin' . DS,
            $pluginPath . 'templates' . DS . 'plugin' . DS . 'TestPlugin' . DS,
            $pluginPath . 'templates' . DS,
            TEST_APP . 'templates' . DS,
            TEST_APP . 'Plugin' . DS . 'TestPlugin' . DS . 'templates' . DS,
            CORE_PATH . 'templates' . DS,
        ];
        $this->assertPathEquals($expected, $paths);
    }

    /**
     * Test that CamelCase'd plugins still find their view files.
     *
     * @return void
     */
    public function testCamelCasePluginGetTemplate()
    {
        $viewOptions = [
            'plugin' => 'TestPlugin',
            'name' => 'TestPlugin',
            'templatePath' => 'Tests',
            'template' => 'index',
        ];

        $View = new TestView(null, null, null, $viewOptions);

        $pluginPath = Plugin::path('TestPlugin');
        $expected = TEST_APP . 'Plugin' . DS . 'TestPlugin' . DS . 'templates' . DS .
            'Tests' . DS . 'index.php';
        $result = $View->getTemplateFileName('index');
        $this->assertPathEquals($expected, $result);

        $expected = $pluginPath . 'templates' . DS . 'layout' . DS . 'default.php';
        $result = $View->getLayoutFileName();
        $this->assertPathEquals($expected, $result);
    }

    /**
     * Test getTemplateFileName method
     *
     * @return void
     */
    public function testGetViewFileNames()
    {
        $viewOptions = [
            'plugin' => null,
            'name' => 'Pages',
            'templatePath' => 'Pages',
        ];
        $View = new TestView(null, null, null, $viewOptions);

        $expected = TEST_APP . 'templates' . DS . 'Pages' . DS . 'home.php';
        $result = $View->getTemplateFileName('home');
        $this->assertPathEquals($expected, $result);

        $expected = TEST_APP . 'templates' . DS . 'Posts' . DS . 'index.php';
        $result = $View->getTemplateFileName('/Posts/index');
        $this->assertPathEquals($expected, $result);

        $expected = TEST_APP . 'templates' . DS . 'Posts' . DS . 'index.php';
        $result = $View->getTemplateFileName('../Posts/index');
        $this->assertPathEquals($expected, $result);

        $expected = TEST_APP . 'templates' . DS . 'Pages' . DS . 'page.home.php';
        $result = $View->getTemplateFileName('page.home');
        $this->assertPathEquals($expected, $result, 'Should not ruin files with dots.');

        $expected = TEST_APP . 'templates' . DS . 'Pages' . DS . 'home.php';
        $result = $View->getTemplateFileName('TestPlugin.home');
        $this->assertPathEquals($expected, $result, 'Plugin is missing the view, cascade to app.');

        $View->setTemplatePath('Tests');
        $expected = TEST_APP . 'Plugin' . DS . 'TestPlugin' . DS . 'templates' . DS .
            'Tests' . DS . 'index.php';
        $result = $View->getTemplateFileName('TestPlugin.index');
        $this->assertPathEquals($expected, $result);
    }

    /**
     * Test that getTemplateFileName() protects against malicious directory traversal.
     *
     * @return void
     */
    public function testGetViewFileNameDirectoryTraversal()
    {
        $this->expectException(\InvalidArgumentException::class);
        $viewOptions = [
            'plugin' => null,
            'name' => 'Pages',
            'templatePath' => 'Pages',
        ];

        $view = new TestView(null, null, null, $viewOptions);
        $view->ext('.php');
        $view->getTemplateFileName('../../../bootstrap');
    }

    /**
     * Test getTemplateFileName doesn't re-apply existing subdirectories
     *
     * @return void
     */
    public function testGetViewFileNameSubDir()
    {
        $viewOptions = [
            'plugin' => null,
            'name' => 'Posts',
            'templatePath' => 'Posts/json',
            'layoutPath' => 'json',
        ];
        $view = new TestView(null, null, null, $viewOptions);

        $expected = TEST_APP . 'templates' . DS . 'Posts' . DS . 'json' . DS . 'index.php';
        $result = $view->getTemplateFileName('index');
        $this->assertPathEquals($expected, $result);

        $view->setSubDir('json');
        $result = $view->getTemplateFileName('index');
        $expected = TEST_APP . 'templates' . DS . 'Posts' . DS . 'json' . DS . 'index.php';
        $this->assertPathEquals($expected, $result);
    }

    /**
     * Test getTemplateFileName applies subdirectories on equal length names
     *
     * @return void
     */
    public function testGetViewFileNameSubDirLength()
    {
        $viewOptions = [
            'plugin' => null,
            'name' => 'Jobs',
            'templatePath' => 'Jobs',
            'layoutPath' => 'json',
        ];
        $view = new TestView(null, null, null, $viewOptions);

        $view->setSubDir('json');
        $result = $view->getTemplateFileName('index');
        $expected = TEST_APP . 'templates' . DS . 'Jobs' . DS . 'json' . DS . 'index.php';
        $this->assertPathEquals($expected, $result);
    }

    /**
     * Test getting layout filenames
     *
     * @return void
     */
    public function testGetLayoutFileName()
    {
        $viewOptions = [
            'plugin' => null,
            'name' => 'Pages',
            'viewPath' => 'Pages',
            'action' => 'display',
        ];

        $View = new TestView(null, null, null, $viewOptions);

        $expected = TEST_APP . 'templates' . DS . 'layout' . DS . 'default.php';
        $result = $View->getLayoutFileName();
        $this->assertPathEquals($expected, $result);

        $View->setLayoutPath('rss');
        $expected = TEST_APP . 'templates' . DS . 'layout' . DS . 'rss' . DS . 'default.php';
        $result = $View->getLayoutFileName();
        $this->assertPathEquals($expected, $result);

        $View->setLayoutPath('email' . DS . 'html');
        $expected = TEST_APP . 'templates' . DS . 'layout' . DS . 'email' . DS . 'html' . DS . 'default.php';
        $result = $View->getLayoutFileName();
        $this->assertPathEquals($expected, $result);
    }

    /**
     * Test getting layout filenames for plugins.
     *
     * @return void
     */
    public function testGetLayoutFileNamePlugin()
    {
        $viewOptions = [
            'plugin' => null,
            'name' => 'Pages',
            'viewPath' => 'Pages',
            'action' => 'display',
        ];

        $View = new TestView(null, null, null, $viewOptions);

        $expected = TEST_APP . 'Plugin' . DS . 'TestPlugin' . DS . 'templates' . DS .
            'layout' . DS . 'default.php';
        $result = $View->getLayoutFileName('TestPlugin.default');
        $this->assertPathEquals($expected, $result);

        $View->setRequest($View->getRequest()->withParam('plugin', 'TestPlugin'));
        $expected = TEST_APP . 'Plugin' . DS . 'TestPlugin' . DS . 'templates' . DS .
            'layout' . DS . 'default.php';
        $result = $View->getLayoutFileName('default');
        $this->assertPathEquals($expected, $result);
    }

    /**
     * Test getting layout filenames for prefix
     *
     * @return void
     */
    public function testGetLayoutFileNamePrefix()
    {
        $View = new TestView();

        // Prefix specific layout
        $View->setRequest($View->getRequest()->withParam('prefix', 'foo_prefix'));
        $expected = TEST_APP . 'templates' . DS .
            'FooPrefix' . DS . 'layout' . DS . 'default.php';
        $result = $View->getLayoutFileName();
        $this->assertPathEquals($expected, $result);

        $View->setRequest($View->getRequest()->withParam('prefix', 'FooPrefix'));
        $result = $View->getLayoutFileName();
        $this->assertPathEquals($expected, $result);

        // Nested prefix layout
        $View->setRequest($View->getRequest()->withParam('prefix', 'foo_prefix/bar_prefix'));
        $expected = TEST_APP . 'templates' . DS .
            'FooPrefix' . DS . 'BarPrefix' . DS . 'layout' . DS . 'default.php';
        $result = $View->getLayoutFileName();
        $this->assertPathEquals($expected, $result);

        $expected = TEST_APP . 'templates' . DS .
            'FooPrefix' . DS . 'layout' . DS . 'nested_prefix_cascade.php';
        $result = $View->getLayoutFileName('nested_prefix_cascade');
        $this->assertPathEquals($expected, $result);

        // Fallback to app's layout
        $View->setRequest($View->getRequest()->withParam('prefix', 'Admin'));
        $expected = TEST_APP . 'templates' . DS .
            'layout' . DS . 'default.php';
        $result = $View->getLayoutFileName();
        $this->assertPathEquals($expected, $result);
    }

    /**
     * Test that getLayoutFileName() protects against malicious directory traversal.
     *
     * @return void
     */
    public function testGetLayoutFileNameDirectoryTraversal()
    {
        $this->expectException(\InvalidArgumentException::class);
        $viewOptions = [
            'plugin' => null,
            'name' => 'Pages',
            'viewPath' => 'Pages',
        ];

        $view = new TestView(null, null, null, $viewOptions);
        $view->ext('.php');
        $view->getLayoutFileName('../../../bootstrap');
    }

    /**
     * Test for missing views
     *
     * @return void
     */
    public function testMissingTemplate()
    {
        $this->expectException(\Cake\View\Exception\MissingTemplateException::class);
        $this->expectExceptionMessage('Template file `does_not_exist.php` could not be found');
        $this->expectExceptionMessage('The following paths were searched');
        $this->expectExceptionMessage('- `' . ROOT . DS . 'templates' . DS . 'does_not_exist.php`');
        $viewOptions = [
            'plugin' => null,
            'name' => 'Pages',
            'viewPath' => 'Pages',
        ];
        $request = new ServerRequest();
        $response = new Response();

        $View = new TestView($request, $response, null, $viewOptions);
        $View->getTemplateFileName('does_not_exist');
    }

    /**
     * Test for missing layouts
     *
     * @return void
     */
    public function testMissingLayout()
    {
        $this->expectException(\Cake\View\Exception\MissingLayoutException::class);
        $this->expectExceptionMessage('Layout file `whatever.php` could not be found');
        $this->expectExceptionMessage('The following paths were searched');
        $this->expectExceptionMessage('- `' . ROOT . DS . 'templates' . DS . 'layout' . DS . 'whatever.php`');
        $viewOptions = [
            'plugin' => null,
            'name' => 'Pages',
            'viewPath' => 'Pages',
            'layout' => 'whatever',
        ];
        $View = new TestView(null, null, null, $viewOptions);
        $View->getLayoutFileName();
    }

    /**
     * Test viewVars method
     *
     * @return void
     */
    public function testViewVars()
    {
        $this->assertEquals(['testData', 'test2', 'test3'], $this->View->getVars());
    }

    /**
     * Test elementExists method
     *
     * @return void
     */
    public function testElementExists()
    {
        $result = $this->View->elementExists('test_element');
        $this->assertTrue($result);

        $result = $this->View->elementExists('TestPlugin.plugin_element');
        $this->assertTrue($result);

        $result = $this->View->elementExists('nonexistent_element');
        $this->assertFalse($result);

        $result = $this->View->elementExists('TestPlugin.element');
        $this->assertFalse($result);

        $this->View->setRequest($this->View->getRequest()->withParam('plugin', 'TestPlugin'));
        $result = $this->View->elementExists('plugin_element');
        $this->assertTrue($result);
    }

    /**
     * Test element method
     *
     * @return void
     */
    public function testElement()
    {
        $result = $this->View->element('test_element');
        $this->assertSame('this is the test element', $result);

        $result = $this->View->element('TestPlugin.plugin_element');
        $this->assertSame("Element in the TestPlugin\n", $result);

        $this->View->setRequest($this->View->getRequest()->withParam('plugin', 'TestPlugin'));
        $result = $this->View->element('plugin_element');
        $this->assertSame("Element in the TestPlugin\n", $result);

        $result = $this->View->element('plugin_element', [], ['plugin' => false]);
        $this->assertSame("Plugin element overridden in app\n", $result);
    }

    /**
     * Test element method with a prefix
     *
     * @return void
     */
    public function testPrefixElement()
    {
        $this->View->setRequest($this->View->getRequest()->withParam('prefix', 'Admin'));
        $result = $this->View->element('prefix_element');
        $this->assertSame('this is a prefixed test element', $result);

        $result = $this->View->element('TestPlugin.plugin_element');
        $this->assertSame('this is the plugin prefixed element using params[plugin]', $result);

        $this->View->setRequest($this->View->getRequest()->withParam('plugin', 'TestPlugin'));
        $result = $this->View->element('test_plugin_element');
        $this->assertSame('this is the test set using View::$plugin plugin prefixed element', $result);

        $this->View->setRequest($this->View->getRequest()->withParam('prefix', 'FooPrefix/BarPrefix'));
        $result = $this->View->element('prefix_element');
        $this->assertSame('this is a nested prefixed test element', $result);

        $result = $this->View->element('prefix_element_in_parent');
        $this->assertSame('this is a nested prefixed test element in first level element', $result);
    }

    /**
     * Test loading missing view element
     *
     * @return void
     */
    public function testElementMissing()
    {
        $this->expectException(\Cake\View\Exception\MissingElementException::class);
        $this->expectExceptionMessage('Element file `nonexistent_element.php` could not be found');

        $this->View->element('nonexistent_element');
    }

    /**
     * Test loading nonexistent plugin view element
     *
     * @return void
     */
    public function testElementMissingPluginElement()
    {
        $this->expectException(\Cake\View\Exception\MissingElementException::class);
        $this->expectExceptionMessage('Element file `TestPlugin.nope.php` could not be found');
        $this->expectExceptionMessage(implode(DS, ['test_app', 'templates', 'plugin', 'TestPlugin', 'element', 'nope.php']));
        $this->expectExceptionMessage(implode(DS, ['test_app', 'Plugin', 'TestPlugin', 'templates', 'element', 'nope.php']));

        $this->View->element('TestPlugin.nope');
    }

    /**
     * Test that elements can have callbacks
     *
     * @return void
     */
    public function testElementCallbacks()
    {
        $count = 0;
        $callback = function (EventInterface $event, $file) use (&$count) {
            $count++;
        };
        $events = $this->View->getEventManager();
        $events->on('View.beforeRender', $callback);
        $events->on('View.afterRender', $callback);

        $this->View->element('test_element', [], ['callbacks' => true]);
        $this->assertSame(2, $count);
    }

    /**
     * Test that additional element viewVars don't get overwritten with helpers.
     *
     * @return void
     */
    public function testElementParamsDontOverwriteHelpers()
    {
        $Controller = new ViewPostsController();

        $View = $Controller->createView();
        $result = $View->element('type_check', ['form' => 'string'], ['callbacks' => true]);
        $this->assertSame('string', $result);

        $View->set('form', 'string');
        $result = $View->element('type_check', [], ['callbacks' => true]);
        $this->assertSame('string', $result);
    }

    /**
     * Test elementCacheHelperNoCache method
     *
     * @return void
     */
    public function testElementCacheHelperNoCache()
    {
        $Controller = new ViewPostsController();
        $View = $Controller->createView();
        $result = $View->element('test_element', ['ram' => 'val', 'test' => ['foo', 'bar']]);
        $this->assertSame('this is the test element', $result);
    }

    /**
     * Test elementCache method
     *
     * @return void
     */
    public function testElementCache()
    {
        Cache::drop('test_view');
        Cache::setConfig('test_view', [
            'engine' => 'File',
            'duration' => '+1 day',
            'path' => CACHE . 'views/',
            'prefix' => '',
        ]);
        Cache::clear('test_view');

        $View = $this->PostsController->createView();
        $View->setElementCache('test_view');

        $result = $View->element('test_element', [], ['cache' => true]);
        $expected = 'this is the test element';
        $this->assertSame($expected, $result);

        $result = Cache::read('element__test_element', 'test_view');
        $this->assertSame($expected, $result);

        $result = $View->element('test_element', ['param' => 'one', 'foo' => 'two'], ['cache' => true]);
        $this->assertSame($expected, $result);

        $result = Cache::read('element__test_element_param_foo', 'test_view');
        $this->assertSame($expected, $result);

        $View->element('test_element', [
            'param' => 'one',
            'foo' => 'two',
        ], [
            'cache' => ['key' => 'custom_key'],
        ]);
        $result = Cache::read('element_custom_key', 'test_view');
        $this->assertSame($expected, $result);

        $View->setElementCache('default');
        $View->element('test_element', [
            'param' => 'one',
            'foo' => 'two',
        ], [
            'cache' => ['config' => 'test_view'],
        ]);
        $result = Cache::read('element__test_element_param_foo', 'test_view');
        $this->assertSame($expected, $result);

        Cache::clear('test_view');
        Cache::drop('test_view');
    }

    /**
     * Test elementCache method with namespaces and subfolder
     *
     * @return void
     */
    public function testElementCacheSubfolder()
    {
        Cache::drop('test_view');
        Cache::setConfig('test_view', [
            'engine' => 'File',
            'duration' => '+1 day',
            'path' => CACHE . 'views/',
            'prefix' => '',
        ]);
        Cache::clear('test_view');

        $View = $this->PostsController->createView();
        $View->setElementCache('test_view');

        $result = $View->element('subfolder/test_element', [], ['cache' => true]);
        $expected = 'this is the test element in subfolder';
        $this->assertSame($expected, trim($result));

        $result = Cache::read('element__subfolder_test_element', 'test_view');
        $this->assertSame($expected, trim($result));
    }

    /**
     * Test element events
     *
     * @return void
     */
    public function testViewEvent()
    {
        $View = $this->PostsController->createView();
        $View->setTemplatePath($this->PostsController->getName());
        $View->enableAutoLayout(false);
        $listener = new TestViewEventListenerInterface();

        $View->getEventManager()->on($listener);

        $View->render('index');
        $this->assertSame(View::TYPE_TEMPLATE, $listener->beforeRenderViewType);
        $this->assertSame(View::TYPE_TEMPLATE, $listener->afterRenderViewType);

        $this->assertSame($View->getCurrentType(), View::TYPE_TEMPLATE);
        $View->element('test_element', [], ['callbacks' => true]);
        $this->assertSame($View->getCurrentType(), View::TYPE_TEMPLATE);

        $this->assertSame(View::TYPE_ELEMENT, $listener->beforeRenderViewType);
        $this->assertSame(View::TYPE_ELEMENT, $listener->afterRenderViewType);
    }

    /**
     * Test loading helper using loadHelper().
     *
     * @return void
     */
    public function testLoadHelper()
    {
        $View = new View();

        $View->loadHelper('Html', ['foo' => 'bar']);
        $this->assertInstanceOf('Cake\View\Helper\HtmlHelper', $View->Html);

        $config = $View->Html->getConfig();
        $this->assertSame('bar', $config['foo']);
    }

    /**
     * Test loading helper when duplicate.
     *
     * @return void
     */
    public function testLoadHelperDuplicate()
    {
        $View = new View();

        $this->assertNotEmpty($View->loadHelper('Html', ['foo' => 'bar']));
        try {
            $View->loadHelper('Html', ['test' => 'value']);
            $this->fail('No exception');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString('The "Html" alias has already been loaded', $e->getMessage());
        }
    }

    /**
     * Test loadHelpers method
     *
     * @return void
     */
    public function testLoadHelpers()
    {
        $View = new View(null, null, null, [
            'helpers' => ['Html' => ['foo' => 'bar'], 'Form' => ['foo' => 'baz']],
        ]);

        $result = $View->loadHelpers();
        $this->assertSame($View, $result);

        $this->assertInstanceOf('Cake\View\Helper\HtmlHelper', $View->Html, 'Object type is wrong.');
        $this->assertInstanceOf('Cake\View\Helper\FormHelper', $View->Form, 'Object type is wrong.');

        $config = $View->Html->getConfig();
        $this->assertSame('bar', $config['foo']);

        $config = $View->Form->getConfig();
        $this->assertSame('baz', $config['foo']);
    }

    /**
     * Test lazy loading helpers
     *
     * @return void
     */
    public function testLazyLoadHelpers()
    {
        $View = new View();

        $this->assertInstanceOf('Cake\View\Helper\HtmlHelper', $View->Html, 'Object type is wrong.');
        $this->assertInstanceOf('Cake\View\Helper\FormHelper', $View->Form, 'Object type is wrong.');
    }

    /**
     * Test manipulating class properties in initialize()
     *
     * @return void
     */
    public function testInitialize()
    {
        $View = new TestView();
        $config = $View->Html->getConfig();
        $this->assertSame('myval', $config['mykey']);
    }

    /**
     * Test the correct triggering of helper callbacks
     *
     * @return void
     */
    public function testHelperCallbackTriggering()
    {
        $View = $this->PostsController->createView();
        $View->setTemplatePath($this->PostsController->getName());

        $manager = $this->getMockBuilder('Cake\Event\EventManager')->getMock();
        $View->setEventManager($manager);

        $manager->expects($this->exactly(8))
            ->method('dispatch')
            ->withConsecutive(
                [$this->callback(function (EventInterface $event) {
                    return $event->getName() === 'View.beforeRender';
                })],
                [$this->callback(function (EventInterface $event) {
                    return $event->getName() === 'View.beforeRenderFile';
                })],
                [$this->callback(function (EventInterface $event) {
                    return $event->getName() === 'View.afterRenderFile';
                })],
                [$this->callback(function (EventInterface $event) {
                    return $event->getName() === 'View.afterRender';
                })],
                [$this->callback(function (EventInterface $event) {
                    return $event->getName() === 'View.beforeLayout';
                })],
                [$this->callback(function (EventInterface $event) {
                    return $event->getName() === 'View.beforeRenderFile';
                })],
                [$this->callback(function (EventInterface $event) {
                    return $event->getName() === 'View.afterRenderFile';
                })],
                [$this->callback(function (EventInterface $event) {
                    return $event->getName() === 'View.afterLayout';
                })]
            );

        $View->render('index');
    }

    /**
     * Test beforeLayout method
     *
     * @return void
     */
    public function testBeforeLayout()
    {
        $this->PostsController->viewBuilder()->setHelpers([
            'TestBeforeAfter' => ['className' => TestBeforeAfterHelper::class],
            'Html',
        ]);
        $View = $this->PostsController->createView();
        $View->setTemplatePath($this->PostsController->getName());
        $View->render('index');
        $this->assertSame('Valuation', $View->helpers()->TestBeforeAfter->property);
    }

    /**
     * Test afterLayout method
     *
     * @return void
     */
    public function testAfterLayout()
    {
        $this->PostsController->viewBuilder()->setHelpers([
            'TestBeforeAfter' => ['className' => TestBeforeAfterHelper::class],
            'Html',
        ]);
        $this->PostsController->set('variable', 'values');

        $View = $this->PostsController->createView();
        $View->setTemplatePath($this->PostsController->getName());

        $content = 'This is my view output';
        $result = $View->renderLayout($content, 'default');
        $this->assertMatchesRegularExpression('/modified in the afterlife/', $result);
        $this->assertMatchesRegularExpression('/This is my view output/', $result);
    }

    /**
     * Test renderLoadHelper method
     *
     * @return void
     */
    public function testRenderLoadHelper()
    {
        $this->PostsController->viewBuilder()->setHelpers(['Form', 'Number']);
        $View = $this->PostsController->createView(TestView::class);
        $View->setTemplatePath($this->PostsController->getName());

        $result = $View->render('index', false);
        $this->assertSame('posts index', $result);

        $attached = $View->helpers()->loaded();
        // HtmlHelper is loaded in TestView::initialize()
        $this->assertEquals(['Html', 'Form', 'Number'], $attached);

        $this->PostsController->viewBuilder()->setHelpers(
            ['Html', 'Form', 'Number', 'TestPlugin.PluggedHelper']
        );
        $View = $this->PostsController->createView(TestView::class);
        $View->setTemplatePath($this->PostsController->getName());

        $result = $View->render('index', false);
        $this->assertSame('posts index', $result);

        $attached = $View->helpers()->loaded();
        $expected = ['Html', 'Form', 'Number', 'PluggedHelper'];
        $this->assertEquals($expected, $attached, 'Attached helpers are wrong.');
    }

    /**
     * Test render method
     *
     * @return void
     */
    public function testRender()
    {
        $View = $this->PostsController->createView(TestView::class);
        $View->setTemplatePath($this->PostsController->getName());
        $result = $View->render('index');

        $this->assertMatchesRegularExpression("/<meta charset=\"utf-8\"\/>\s*<title>/", $result);
        $this->assertMatchesRegularExpression("/<div id=\"content\">\s*posts index\s*<\/div>/", $result);
        $this->assertMatchesRegularExpression("/<div id=\"content\">\s*posts index\s*<\/div>/", $result);

        $this->PostsController->viewBuilder()->setHelpers(['Html']);
        $this->PostsController->setRequest(
            $this->PostsController->getRequest()->withParam('action', 'index')
        );
        Configure::write('Cache.check', true);

        $View = $this->PostsController->createView(TestView::class);
        $View->setTemplatePath($this->PostsController->getName());
        $result = $View->render('index');

        $this->assertMatchesRegularExpression("/<meta charset=\"utf-8\"\/>\s*<title>/", $result);
        $this->assertMatchesRegularExpression("/<div id=\"content\">\s*posts index\s*<\/div>/", $result);
    }

    /**
     * Test that View::$view works
     *
     * @return void
     */
    public function testRenderUsingViewProperty()
    {
        $View = $this->PostsController->createView(TestView::class);
        $View->setTemplatePath($this->PostsController->getName());
        $View->setTemplate('cache_form');

        $this->assertSame('cache_form', $View->getTemplate());
        $result = $View->render();
        $this->assertMatchesRegularExpression('/Add User/', $result);
    }

    /**
     * Test that layout set from view file takes precedence over layout set
     * as argument to render().
     *
     * @return void
     */
    public function testRenderUsingLayoutArgument()
    {
        $error = new \PDOException();
        $error->queryString = 'this is sql string';
        $message = 'it works';
        $trace = $error->getTrace();

        $View = $this->PostsController->createView(TestView::class);
        $View->set(compact('error', 'message', 'trace'));
        $View->setTemplatePath('Error');

        $result = $View->render('pdo_error', 'error');
        $this->assertMatchesRegularExpression('/this is sql string/', $result);
        $this->assertMatchesRegularExpression('/it works/', $result);
    }

    /**
     * Test renderLayout()
     *
     * @return void
     */
    public function testRenderLayout()
    {
        $View = $this->PostsController->createView(TestView::class);
        $result = $View->renderLayout('', 'ajax2');

        $this->assertMatchesRegularExpression('/Ajax\!/', $result);
    }

    /**
     * Test render()ing a file in a subdir from a custom viewPath
     * in a plugin.
     *
     * @return void
     */
    public function testGetTemplateFileNameSubdirWithPluginAndViewPath()
    {
        $this->PostsController->setPlugin('TestPlugin');
        $this->PostsController->setName('Posts');
        /** @var \TestApp\View\TestView $View */
        $View = $this->PostsController->createView(TestView::class);
        $View->setTemplatePath('element');

        $pluginPath = TEST_APP . 'Plugin' . DS . 'TestPlugin' . DS;
        $result = $View->getTemplateFileName('sub_dir/sub_element');
        $expected = $pluginPath . 'templates' . DS . 'element' . DS . 'sub_dir' . DS . 'sub_element.php';
        $this->assertPathEquals($expected, $result);
    }

    public function testGetTemplateException()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Template name not provided');
        $view = new View();
        $view->render();
    }

    /**
     * Test that view vars can replace the local helper variables
     * and not overwrite the $this->Helper references
     *
     * @return void
     */
    public function testViewVarOverwritingLocalHelperVar()
    {
        $Controller = new ViewPostsController();
        $Controller->set('html', 'I am some test html');
        $View = $Controller->createView();
        $View->setTemplatePath($Controller->getName());
        $result = $View->render('helper_overwrite', false);

        $this->assertMatchesRegularExpression('/I am some test html/', $result);
        $this->assertMatchesRegularExpression('/Test link/', $result);
    }

    /**
     * Test getTemplateFileName method
     *
     * @return void
     */
    public function testViewFileName()
    {
        /** @var \TestApp\View\TestView $View */
        $View = $this->PostsController->createView(TestView::class);
        $View->setTemplatePath('Posts');

        $result = $View->getTemplateFileName('index');
        $this->assertMatchesRegularExpression('/Posts(\/|\\\)index.php/', $result);

        $result = $View->getTemplateFileName('TestPlugin.index');
        $this->assertMatchesRegularExpression('/Posts(\/|\\\)index.php/', $result);

        $result = $View->getTemplateFileName('/Pages/home');
        $this->assertMatchesRegularExpression('/Pages(\/|\\\)home.php/', $result);

        $result = $View->getTemplateFileName('../element/test_element');
        $this->assertMatchesRegularExpression('/element(\/|\\\)test_element.php/', $result);

        $expected = TEST_APP . 'templates' . DS . 'Posts' . DS . 'index.php';
        $result = $View->getTemplateFileName('../Posts/index');
        $this->assertPathEquals($expected, $result);
    }

    /**
     * Test creating a block with capturing output.
     *
     * @return void
     */
    public function testBlockCaptureOverwrite()
    {
        $result = $this->View->start('test');
        $this->assertSame($this->View, $result);

        echo 'Block content';
        $result = $this->View->end();
        $this->assertSame($this->View, $result);

        $this->View->start('test');
        echo 'New content';
        $this->View->end();

        $result = $this->View->fetch('test');
        $this->assertSame('New content', $result);
    }

    /**
     * Test that blocks can be fetched inside a block with the same name
     *
     * @return void
     */
    public function testBlockExtend()
    {
        $this->View->start('test');
        echo 'Block content';
        $this->View->end();

        $this->View->start('test');
        echo $this->View->fetch('test');
        echo 'New content';
        $this->View->end();

        $result = $this->View->fetch('test');
        $this->assertSame('Block contentNew content', $result);
    }

    /**
     * Test creating a block with capturing output.
     *
     * @return void
     */
    public function testBlockCapture()
    {
        $this->View->start('test');
        echo 'Block content';
        $this->View->end();

        $result = $this->View->fetch('test');
        $this->assertSame('Block content', $result);
    }

    /**
     * Test appending to a block with capturing output.
     *
     * @return void
     */
    public function testBlockAppendCapture()
    {
        $this->View->start('test');
        echo 'Content ';
        $this->View->end();

        $result = $this->View->append('test');
        $this->assertSame($this->View, $result);

        echo 'appended';
        $this->View->end();

        $result = $this->View->fetch('test');
        $this->assertSame('Content appended', $result);
    }

    /**
     * Test setting a block's content.
     *
     * @return void
     */
    public function testBlockSet()
    {
        $result = $this->View->assign('test', 'Block content');
        $this->assertSame($this->View, $result);

        $result = $this->View->fetch('test');
        $this->assertSame('Block content', $result);
    }

    /**
     * Test resetting a block's content.
     *
     * @return void
     */
    public function testBlockReset()
    {
        $this->View->assign('test', '');
        $result = $this->View->fetch('test', 'This should not be returned');
        $this->assertSame('', $result);
    }

    /**
     * Test resetting a block's content with reset.
     *
     * @return void
     */
    public function testBlockResetFunc()
    {
        $this->View->assign('test', 'Block content');
        $result = $this->View->fetch('test', 'This should not be returned');
        $this->assertSame('Block content', $result);

        $result = $this->View->reset('test');
        $this->assertSame($this->View, $result);

        $result = $this->View->fetch('test', 'This should not be returned');
        $this->assertSame('', $result);
    }

    /**
     * Test checking a block's existence.
     *
     * @return void
     */
    public function testBlockExist()
    {
        $this->assertFalse($this->View->exists('test'));
        $this->View->assign('test', 'Block content');
        $this->assertTrue($this->View->exists('test'));
    }

    /**
     * Test setting a block's content to null
     *
     * @return void
     */
    public function testBlockSetNull()
    {
        $this->View->assign('testWithNull', null);
        $result = $this->View->fetch('testWithNull');
        $this->assertSame('', $result);
    }

    /**
     * Test setting a block's content to an object with __toString magic method
     *
     * @return void
     */
    public function testBlockSetObjectWithToString()
    {
        $objectWithToString = new TestObjectWithToString();
        $this->View->assign('testWithObjectWithToString', $objectWithToString);
        $result = $this->View->fetch('testWithObjectWithToString');
        $this->assertSame("I'm ObjectWithToString", $result);
    }

    /**
     * Test setting a block's content to an object without __toString magic method
     *
     * @return void
     */
    public function testBlockSetObjectWithoutToString()
    {
        $this->checkException(
            'Object of class ' . TestObjectWithoutToString::class . ' could not be converted to string'
        );

        $objectWithToString = new TestObjectWithoutToString();
        $this->View->assign('testWithObjectWithoutToString', $objectWithToString);
    }

    /**
     * Test setting a block's content to a decimal
     *
     * @return void
     */
    public function testBlockSetDecimal()
    {
        $this->View->assign('testWithDecimal', 1.23456789);
        $result = $this->View->fetch('testWithDecimal');
        $this->assertSame('1.23456789', $result);
    }

    /**
     * Data provider for block related tests.
     *
     * @return array
     */
    public static function blockValueProvider()
    {
        return [
            'string' => ['A string value'],
            'decimal' => [1.23456],
            'object with __toString' => [new TestObjectWithToString()],
        ];
    }

    /**
     * Test appending to a block with append.
     *
     * @param mixed $value Value
     * @return void
     * @dataProvider blockValueProvider
     */
    public function testBlockAppend($value)
    {
        $this->View->assign('testBlock', 'Block');
        $this->View->append('testBlock', $value);

        $result = $this->View->fetch('testBlock');
        $this->assertSame('Block' . $value, $result);
    }

    /**
     * Test appending an object without __toString magic method to a block with append.
     *
     * @return void
     */
    public function testBlockAppendObjectWithoutToString()
    {
        $this->checkException(
            'Object of class ' . TestObjectWithoutToString::class . ' could not be converted to string'
        );

        $object = new TestObjectWithoutToString();
        $this->View->assign('testBlock', 'Block ');
        $this->View->append('testBlock', $object);
    }

    /**
     * Test prepending to a block with prepend.
     *
     * @param mixed $value Value
     * @return void
     * @dataProvider blockValueProvider
     */
    public function testBlockPrepend($value)
    {
        $this->View->assign('test', 'Block');
        $result = $this->View->prepend('test', $value);
        $this->assertSame($this->View, $result);

        $result = $this->View->fetch('test');
        $this->assertSame($value . 'Block', $result);
    }

    /**
     * Test prepending an object without __toString magic method to a block with prepend.
     *
     * @return void
     */
    public function testBlockPrependObjectWithoutToString()
    {
        $this->checkException(
            'Object of class ' . TestObjectWithoutToString::class . ' could not be converted to string'
        );

        $object = new TestObjectWithoutToString();
        $this->View->assign('test', 'Block ');
        $this->View->prepend('test', $object);
    }

    /**
     * You should be able to append to undefined blocks.
     *
     * @return void
     */
    public function testBlockAppendUndefined()
    {
        $result = $this->View->append('test', 'Unknown');
        $this->assertSame($this->View, $result);

        $result = $this->View->fetch('test');
        $this->assertSame('Unknown', $result);
    }

    /**
     * You should be able to prepend to undefined blocks.
     *
     * @return void
     */
    public function testBlockPrependUndefined()
    {
        $this->View->prepend('test', 'Unknown');
        $result = $this->View->fetch('test');
        $this->assertSame('Unknown', $result);
    }

    /**
     * Test getting block names
     *
     * @return void
     */
    public function testBlocks()
    {
        $this->View->append('test', 'one');
        $this->View->assign('test1', 'one');

        $this->assertEquals(['test', 'test1'], $this->View->blocks());
    }

    /**
     * Test that blocks can be nested.
     *
     * @return void
     */
    public function testNestedBlocks()
    {
        $this->View->start('first');
        echo 'In first ';
        $this->View->start('second');
        echo 'In second';
        $this->View->end();
        echo 'In first';
        $this->View->end();

        $this->assertSame('In first In first', $this->View->fetch('first'));
        $this->assertSame('In second', $this->View->fetch('second'));
    }

    /**
     * Test that starting the same block twice throws an exception
     *
     * @return void
     */
    public function testStartBlocksTwice()
    {
        try {
            $this->View->start('first');
            $this->View->start('first');
            $this->fail('No exception');
        } catch (\Cake\Core\Exception\CakeException $e) {
            ob_end_clean();
            $this->assertTrue(true);
        }
    }

    /**
     * Test that an exception gets thrown when you leave a block open at the end
     * of a view.
     *
     * @return void
     */
    public function testExceptionOnOpenBlock()
    {
        try {
            $this->View->render('open_block');
            $this->fail('No exception');
        } catch (\LogicException $e) {
            ob_end_clean();
            $this->assertStringContainsString('The "no_close" block was left open', $e->getMessage());
        }
    }

    /**
     * Test nested extended views.
     *
     * @return void
     */
    public function testExtendNested()
    {
        $content = $this->View->render('nested_extends', false);
        $expected = <<<TEXT
This is the second parent.
This is the first parent.
This is the first template.
Sidebar Content.
TEXT;
        $this->assertSame($expected, $content);
    }

    /**
     * Make sure that extending the current view with itself causes an exception
     *
     * @return void
     */
    public function testExtendSelf()
    {
        try {
            $this->View->render('extend_self', false);
            $this->fail('No exception');
        } catch (\LogicException $e) {
            $this->assertStringContainsString('cannot have templates extend themselves', $e->getMessage());
        }
    }

    /**
     * Make sure that extending in a loop causes an exception
     *
     * @return void
     */
    public function testExtendLoop()
    {
        try {
            $this->View->render('extend_loop', false);
            $this->fail('No exception');
        } catch (\LogicException $e) {
            $this->assertStringContainsString('cannot have templates extend in a loop', $e->getMessage());
        }
    }

    /**
     * Test extend() in an element and a view.
     *
     * @return void
     */
    public function testExtendElement()
    {
        $content = $this->View->render('extend_element', false);
        $expected = <<<TEXT
Parent View.
View content.
Parent Element.
Element content.

TEXT;
        $this->assertSame($expected, $content);
    }

    /**
     * Test extend() in an element and a view.
     *
     * @return void
     */
    public function testExtendPrefixElement()
    {
        $this->View->setRequest($this->View->getRequest()->withParam('prefix', 'Admin'));
        $content = $this->View->render('extend_element', false);
        $expected = <<<TEXT
Parent View.
View content.
Parent Element.
Prefix Element content.

TEXT;
        $this->assertSame($expected, $content);
    }

    /**
     * Extending an element which doesn't exist should throw a missing view exception
     *
     * @return void
     */
    public function testExtendMissingElement()
    {
        try {
            $this->View->render('extend_missing_element', false);
            $this->fail('No exception');
        } catch (\LogicException $e) {
            $this->assertStringContainsString('element', $e->getMessage());
        }
    }

    /**
     * Test extend() preceded by an element()
     *
     * @return void
     */
    public function testExtendWithElementBeforeExtend()
    {
        $result = $this->View->render('extend_with_element', false);
        $expected = <<<TEXT
Parent View.
this is the test elementThe view

TEXT;
        $this->assertSame($expected, $result);
    }

    /**
     * Test extend() preceded by an element()
     *
     * @return void
     */
    public function testExtendWithPrefixElementBeforeExtend()
    {
        $this->View->setRequest($this->View->getRequest()->withParam('prefix', 'Admin'));
        $this->View->disableAutoLayout();
        $result = $this->View->render('extend_with_element');
        $expected = <<<TEXT
Parent View.
this is the test prefix elementThe view

TEXT;
        $this->assertSame($expected, $result);
    }

    /**
     * Tests that the buffers that are opened when evaluating a template
     * are being closed in case an exception happens.
     *
     * @return void
     */
    public function testBuffersOpenedDuringTemplateEvaluationAreBeingClosed()
    {
        $bufferLevel = ob_get_level();

        $e = null;
        try {
            $this->View->element('exception_with_open_buffers');
        } catch (\Exception $e) {
        }

        $this->assertNotNull($e);
        $this->assertSame('Exception with open buffers', $e->getMessage());
        $this->assertSame($bufferLevel, ob_get_level());
    }

    /**
     * Tests that the buffers that are opened during block caching are
     * being closed in case an exception happens.
     *
     * @return void
     */
    public function testBuffersOpenedDuringBlockCachingAreBeingClosed()
    {
        Cache::drop('test_view');
        Cache::setConfig('test_view', [
            'engine' => 'File',
            'duration' => '+1 day',
            'path' => CACHE . 'views/',
            'prefix' => '',
        ]);
        Cache::clear('test_view');

        $bufferLevel = ob_get_level();

        $e = null;
        try {
            $this->View->cache(function () {
                ob_start();

                throw new \Exception('Exception with open buffers');
            }, [
                'key' => __FUNCTION__,
                'config' => 'test_view',
            ]);
        } catch (\Exception $e) {
        }

        Cache::clear('test_view');
        Cache::drop('test_view');

        $this->assertNotNull($e);
        $this->assertSame('Exception with open buffers', $e->getMessage());
        $this->assertSame($bufferLevel, ob_get_level());
    }

    /**
     * Test memory leaks that existed in _paths at one point.
     *
     * @return void
     */
    public function testMemoryLeakInPaths()
    {
        $this->skipIf((bool)env('CODECOVERAGE'), 'Running coverage this causes this tests to fail sometimes.');
        $this->ThemeController->setName('Posts');

        $View = $this->ThemeController->createView();
        $View->setTemplatePath('Posts');
        $View->setLayout('whatever');
        $View->setTheme('TestTheme');
        $View->element('test_element');

        $start = memory_get_usage();
        for ($i = 0; $i < 10; $i++) {
            $View->element('test_element');
        }
        $end = memory_get_usage();
        $this->assertLessThanOrEqual($start + 15000, $end);
    }

    /**
     * Tests that a view block uses default value when not assigned and uses assigned value when it is
     *
     * @return void
     */
    public function testBlockDefaultValue()
    {
        $default = 'Default';
        $result = $this->View->fetch('title', $default);
        $this->assertSame($default, $result);

        $expected = 'My Title';
        $this->View->assign('title', $expected);
        $result = $this->View->fetch('title', $default);
        $this->assertSame($expected, $result);
    }

    /**
     * Tests that a view variable uses default value when not assigned and uses assigned value when it is
     *
     * @return void
     */
    public function testViewVarDefaultValue()
    {
        $default = 'Default';
        $result = $this->View->get('title', $default);
        $this->assertSame($default, $result);

        $expected = 'Back to the Future';
        $this->View->set('title', $expected);
        $result = $this->View->get('title', $default);
        $this->assertSame($expected, $result);
    }

    /**
     * Test the helpers() method.
     *
     * @return void
     */
    public function testHelpers()
    {
        $this->assertInstanceOf('Cake\View\HelperRegistry', $this->View->helpers());

        $result = $this->View->helpers();
        $this->assertSame($result, $this->View->helpers());
    }

    /**
     * Test getTemplatePath() and setTemplatePath().
     *
     * @return void
     */
    public function testGetSetTemplatePath()
    {
        $result = $this->View->setTemplatePath('foo');
        $this->assertSame($this->View, $result);

        $templatePath = $this->View->getTemplatePath();
        $this->assertSame($templatePath, 'foo');
    }

    /**
     * Test getLayoutPath() and setLayoutPath().
     *
     * @return void
     */
    public function testGetSetLayoutPath()
    {
        $result = $this->View->setLayoutPath('foo');
        $this->assertSame($this->View, $result);

        $layoutPath = $this->View->getLayoutPath();
        $this->assertSame($layoutPath, 'foo');
    }

    /**
     * Test isAutoLayoutEnabled() and enableAutoLayout().
     *
     * @return void
     */
    public function testAutoLayout()
    {
        $result = $this->View->enableAutoLayout(false);
        $this->assertSame($this->View, $result);

        $autoLayout = $this->View->isAutoLayoutEnabled();
        $this->assertSame($autoLayout, false);

        $this->View->enableAutoLayout();
        $autoLayout = $this->View->isAutoLayoutEnabled();
        $this->assertSame($autoLayout, true);
    }

    /**
     * testDisableAutoLayout
     *
     * @return void
     */
    public function testDisableAutoLayout()
    {
        $this->assertTrue($this->View->isAutoLayoutEnabled());

        $result = $this->View->disableAutoLayout();
        $this->assertSame($this->View, $result);

        $autoLayout = $this->View->isAutoLayoutEnabled();
        $this->assertFalse($this->View->isAutoLayoutEnabled());
    }

    /**
     * Test getTheme() and setTheme().
     *
     * @return void
     */
    public function testGetSetTheme()
    {
        $result = $this->View->setTheme('foo');
        $this->assertSame($this->View, $result);

        $theme = $this->View->getTheme();
        $this->assertSame($theme, 'foo');
    }

    /**
     * Test getTemplate() and setTemplate().
     *
     * @return void
     */
    public function testGetSetTemplate()
    {
        $result = $this->View->setTemplate('foo');
        $this->assertSame($this->View, $result);

        $template = $this->View->getTemplate();
        $this->assertSame($template, 'foo');
    }

    /**
     * Test setLayout() and getLayout().
     *
     * @return void
     */
    public function testGetSetLayout()
    {
        $result = $this->View->setLayout('foo');
        $this->assertSame($this->View, $result);

        $layout = $this->View->getLayout();
        $this->assertSame($layout, 'foo');
    }

    /**
     * Test getName() and getPlugin().
     *
     * @return void
     */
    public function testGetNamePlugin()
    {
        $this->assertSame('Posts', $this->View->getName());
        $this->assertNull($this->View->getPlugin());

        $this->assertSame($this->View, $this->View->setPlugin('TestPlugin'));
        $this->assertSame('TestPlugin', $this->View->getPlugin());
    }

    protected function checkException($message)
    {
        if (version_compare(PHP_VERSION, '7.4', '>=')) {
            $this->expectException(\Error::class);
        } else {
            $this->expectException(\PHPUnit\Framework\Error\Error::class);
        }
        $this->expectExceptionMessage($message);
    }
}
