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
 * @since         1.2.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\View;

use Cake\Cache\Cache;
use Cake\Controller\Controller;
use Cake\Core\App;
use Cake\Core\Configure;
use Cake\Core\Plugin;
use Cake\Event\Event;
use Cake\Event\EventListenerInterface;
use Cake\Network\Request;
use Cake\Routing\Router;
use Cake\TestSuite\TestCase;
use Cake\View\Helper;
use Cake\View\View;
use TestApp\View\AppView;

/**
 * ViewPostsController class
 */
class ViewPostsController extends Controller
{

    /**
     * name property
     *
     * @var string
     */
    public $name = 'Posts';

    /**
     * index method
     *
     * @return void
     */
    public function index()
    {
        $this->set([
            'testData' => 'Some test data',
            'test2' => 'more data',
            'test3' => 'even more data',
        ]);
    }

    /**
     * nocache_tags_with_element method
     *
     * @return void
     */
    public function nocache_multiple_element()
    {
        $this->set('foo', 'this is foo var');
        $this->set('bar', 'this is bar var');
    }
}

/**
 * ThemePostsController class
 */
class ThemePostsController extends Controller
{

    /**
     * index method
     *
     * @return void
     */
    public function index()
    {
        $this->set('testData', 'Some test data');
        $test2 = 'more data';
        $test3 = 'even more data';
        $this->set(compact('test2', 'test3'));
    }
}

/**
 * TestView class
 */
class TestView extends AppView
{

    public function initialize()
    {
        $this->loadHelper('Html', ['mykey' => 'myval']);
    }

    /**
     * getViewFileName method
     *
     * @param string $name Controller action to find template filename for
     * @return string Template filename
     */
    public function getViewFileName($name = null)
    {
        return $this->_getViewFileName($name);
    }

    /**
     * getLayoutFileName method
     *
     * @param string $name The name of the layout to find.
     * @return string Filename for layout file (.ctp).
     */
    public function getLayoutFileName($name = null)
    {
        return $this->_getLayoutFileName($name);
    }

    /**
     * paths method
     *
     * @param string $plugin Optional plugin name to scan for view files.
     * @param bool $cached Set to true to force a refresh of view paths.
     * @return array paths
     */
    public function paths($plugin = null, $cached = true)
    {
        return $this->_paths($plugin, $cached);
    }

    /**
     * Setter for extension.
     *
     * @param string $ext The extension
     * @return void
     */
    public function ext($ext)
    {
        $this->_ext = $ext;
    }
}

/**
 * TestBeforeAfterHelper class
 */
class TestBeforeAfterHelper extends Helper
{

    /**
     * property property
     *
     * @var string
     */
    public $property = '';

    /**
     * beforeLayout method
     *
     * @param string $viewFile View file name.
     * @return void
     */
    public function beforeLayout($viewFile)
    {
        $this->property = 'Valuation';
    }

    /**
     * afterLayout method
     *
     * @param string $layoutFile Layout file name.
     * @return void
     */
    public function afterLayout($layoutFile)
    {
        $this->_View->append('content', 'modified in the afterlife');
    }
}

/**
 * TestObjectWithToString
 *
 * An object with the magic method __toString() for testing with view blocks.
 */
class TestObjectWithToString
{

    /**
     * Return string value.
     *
     * @return string
     */
    public function __toString()
    {
        return "I'm ObjectWithToString";
    }
}

/**
 * TestObjectWithoutToString
 *
 * An object without the magic method __toString() for testing with view blocks.
 */
class TestObjectWithoutToString
{
}

/**
 * TestViewEventListenerInterface
 *
 * An event listener to test cakePHP events
 */
class TestViewEventListenerInterface implements EventListenerInterface
{

    /**
     * type of view before rendering has occurred
     *
     * @var string
     */
    public $beforeRenderViewType;

    /**
     * type of view after rendering has occurred
     *
     * @var string
     */
    public $afterRenderViewType;

    /**
     * implementedEvents method
     *
     * @return array
     */
    public function implementedEvents()
    {
        return [
            'View.beforeRender' => 'beforeRender',
            'View.afterRender' => 'afterRender'
        ];
    }

    /**
     * beforeRender method
     *
     * @param \Cake\Event\Event $event the event being sent
     * @return void
     */
    public function beforeRender(Event $event)
    {
        $this->beforeRenderViewType = $event->subject()->getCurrentType();
    }

    /**
     * afterRender method
     *
     * @param \Cake\Event\Event $event the event being sent
     * @return void
     */
    public function afterRender(Event $event)
    {
        $this->afterRenderViewType = $event->subject()->getCurrentType();
    }
}

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
    public $fixtures = ['core.posts', 'core.users'];

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();

        $request = new Request();
        $this->Controller = new Controller($request);
        $this->PostsController = new ViewPostsController($request);
        $this->PostsController->index();
        $this->View = $this->PostsController->createView();
        $this->View->viewPath = 'Posts';

        $themeRequest = new Request('posts/index');
        $this->ThemeController = new Controller($themeRequest);
        $this->ThemePostsController = new ThemePostsController($themeRequest);
        $this->ThemePostsController->index();
        $this->ThemeView = $this->ThemePostsController->createView();
        $this->ThemeView->viewPath = 'Posts';

        Plugin::load(['TestPlugin', 'PluginJs', 'TestTheme', 'Company/TestPluginThree']);
        Configure::write('debug', true);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown()
    {
        parent::tearDown();
        Plugin::unload();
        unset($this->View);
        unset($this->PostsController);
        unset($this->Controller);
        unset($this->ThemeView);
        unset($this->ThemePostsController);
        unset($this->ThemeController);
    }

    /**
     * Test getViewFileName method
     *
     * @return void
     */
    public function testGetTemplate()
    {
        $request = $this->getMockBuilder('Cake\Network\Request')->getMock();
        $response = $this->getMockBuilder('Cake\Network\Response')->getMock();

        $viewOptions = [
            'plugin' => null,
            'name' => 'Pages',
            'viewPath' => 'Pages'
        ];
        $request->action = 'display';
        $request->params['pass'] = ['home'];

        $ThemeView = new TestView(null, null, null, $viewOptions);
        $ThemeView->theme = 'TestTheme';
        $expected = TEST_APP . 'TestApp' . DS . 'Template' . DS . 'Pages' . DS . 'home.ctp';
        $result = $ThemeView->getViewFileName('home');
        $this->assertPathEquals($expected, $result);

        $expected = Plugin::path('TestTheme') . 'src' . DS . 'Template' . DS . 'Posts' . DS . 'index.ctp';
        $result = $ThemeView->getViewFileName('/Posts/index');
        $this->assertPathEquals($expected, $result);

        $expected = Plugin::path('TestTheme') . 'src' . DS . 'Template' . DS . 'Layout' . DS . 'default.ctp';
        $result = $ThemeView->getLayoutFileName();
        $this->assertPathEquals($expected, $result);

        $ThemeView->layoutPath = 'rss';
        $expected = TEST_APP . 'TestApp' . DS . 'Template' . DS . 'Layout' . DS . 'rss' . DS . 'default.ctp';
        $result = $ThemeView->getLayoutFileName();
        $this->assertPathEquals($expected, $result);

        $ThemeView->layoutPath = 'Email' . DS . 'html';
        $expected = TEST_APP . 'TestApp' . DS . 'Template' . DS . 'Layout' . DS . 'Email' . DS . 'html' . DS . 'default.ctp';
        $result = $ThemeView->getLayoutFileName();
        $this->assertPathEquals($expected, $result);

        $ThemeView = new TestView(null, null, null, $viewOptions);

        $ThemeView->theme = 'Company/TestPluginThree';
        $expected = Plugin::path('Company/TestPluginThree') . 'src' . DS . 'Template' . DS . 'Layout' . DS . 'default.ctp';
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
        $viewOptions = ['plugin' => 'TestPlugin',
            'name' => 'TestPlugin',
            'viewPath' => 'Tests',
            'view' => 'index'
        ];

        $View = new TestView(null, null, null, $viewOptions);

        $expected = Plugin::path('TestPlugin') . 'src' . DS . 'Template' . DS . 'Tests' . DS . 'index.ctp';
        $result = $View->getViewFileName('index');
        $this->assertEquals($expected, $result);

        $expected = Plugin::path('TestPlugin') . 'src' . DS . 'Template' . DS . 'Layout' . DS . 'default.ctp';
        $result = $View->getLayoutFileName();
        $this->assertEquals($expected, $result);
    }

    /**
     * Test that plugin files with absolute file paths are scoped
     * to the plugin and do now allow any file path.
     *
     * @expectedException Cake\View\Exception\MissingTemplateException
     * @return void
     */
    public function testPluginGetTemplateAbsoluteFail()
    {
        $request = $this->getMockBuilder('Cake\Network\Request')->getMock();
        $response = $this->getMockBuilder('Cake\Network\Response')->getMock();

        $viewOptions = [
            'plugin' => null,
            'name' => 'Pages',
            'viewPath' => 'Pages'
        ];
        $request->action = 'display';
        $request->params['pass'] = ['home'];

        $view = new TestView(null, null, null, $viewOptions);
        $expected = TEST_APP . 'Plugin' . DS . 'Company' . DS . 'TestPluginThree' . DS . 'src' . DS . 'Template' . DS . 'Pages' . DS . 'index.ctp';
        $result = $view->getViewFileName('Company/TestPluginThree./Pages/index');
        $this->assertPathEquals($expected, $result);

        $view->getViewFileName('Company/TestPluginThree./etc/passwd');
    }

    /**
     * Test getViewFileName method on plugin
     *
     * @return void
     */
    public function testPluginThemedGetTemplate()
    {
        $viewOptions = ['plugin' => 'TestPlugin',
            'name' => 'TestPlugin',
            'viewPath' => 'Tests',
            'view' => 'index',
            'theme' => 'TestTheme'
        ];

        $ThemeView = new TestView(null, null, null, $viewOptions);
        $themePath = Plugin::path('TestTheme') . 'src' . DS . 'Template' . DS;

        $expected = $themePath . 'Plugin' . DS . 'TestPlugin' . DS . 'Tests' . DS . 'index.ctp';
        $result = $ThemeView->getViewFileName('index');
        $this->assertPathEquals($expected, $result);

        $expected = $themePath . 'Plugin' . DS . 'TestPlugin' . DS . 'Layout' . DS . 'plugin_default.ctp';
        $result = $ThemeView->getLayoutFileName('plugin_default');
        $this->assertPathEquals($expected, $result);

        $expected = $themePath . 'Layout' . DS . 'default.ctp';
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
        $viewOptions = ['plugin' => 'TestPlugin',
            'name' => 'TestPlugin',
            'viewPath' => 'Tests',
            'view' => 'index'
        ];

        $View = new TestView(null, null, null, $viewOptions);
        $paths = $View->paths();
        $expected = array_merge(App::path('Template'), App::core('Template'));
        $this->assertEquals($expected, $paths);

        $paths = $View->paths('TestPlugin');
        $pluginPath = Plugin::path('TestPlugin');
        $expected = [
            TEST_APP . 'TestApp' . DS . 'Template' . DS . 'Plugin' . DS . 'TestPlugin' . DS,
            $pluginPath . 'src' . DS . 'Template' . DS,
            TEST_APP . 'TestApp' . DS . 'Template' . DS,
            CAKE . 'Template' . DS,
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
        $viewOptions = ['plugin' => 'TestPlugin',
            'name' => 'TestPlugin',
            'viewPath' => 'Tests',
            'view' => 'index',
            'theme' => 'TestTheme'
        ];

        $View = new TestView(null, null, null, $viewOptions);
        $paths = $View->paths('TestPlugin');
        $pluginPath = Plugin::path('TestPlugin');
        $themePath = Plugin::path('TestTheme');
        $expected = [
            $themePath . 'src' . DS . 'Template' . DS . 'Plugin' . DS . 'TestPlugin' . DS,
            $themePath . 'src' . DS . 'Template' . DS,
            TEST_APP . 'TestApp' . DS . 'Template' . DS . 'Plugin' . DS . 'TestPlugin' . DS,
            $pluginPath . 'src' . DS . 'Template' . DS,
            TEST_APP . 'TestApp' . DS . 'Template' . DS,
            CAKE . 'Template' . DS,
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
        $viewOptions = ['plugin' => 'TestPlugin',
            'name' => 'TestPlugin',
            'viewPath' => 'Tests',
            'view' => 'index',
            'theme' => 'TestTheme'
        ];

        $paths = Configure::read('App.paths.templates');
        $paths[] = Plugin::classPath('TestPlugin') . 'Template' . DS;
        Configure::write('App.paths.templates', $paths);

        $View = new TestView(null, null, null, $viewOptions);
        $paths = $View->paths('TestPlugin');
        $pluginPath = Plugin::path('TestPlugin');
        $themePath = Plugin::path('TestTheme');
        $expected = [
            $themePath . 'src' . DS . 'Template' . DS . 'Plugin' . DS . 'TestPlugin' . DS,
            $themePath . 'src' . DS . 'Template' . DS,
            TEST_APP . 'TestApp' . DS . 'Template' . DS . 'Plugin' . DS . 'TestPlugin' . DS,
            $pluginPath . 'src' . DS . 'Template' . DS . 'Plugin' . DS . 'TestPlugin' . DS,
            $pluginPath . 'src' . DS . 'Template' . DS,
            TEST_APP . 'TestApp' . DS . 'Template' . DS,
            TEST_APP . 'Plugin' . DS . 'TestPlugin' . DS . 'src' . DS . 'Template' . DS,
            CAKE . 'Template' . DS,
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
        $viewOptions = ['plugin' => 'TestPlugin',
            'name' => 'TestPlugin',
            'viewPath' => 'Tests',
            'view' => 'index'
        ];

        $View = new TestView(null, null, null, $viewOptions);

        $pluginPath = Plugin::path('TestPlugin');
        $expected = TEST_APP . 'Plugin' . DS . 'TestPlugin' . DS . 'src' . DS .
            'Template' . DS . 'Tests' . DS . 'index.ctp';
        $result = $View->getViewFileName('index');
        $this->assertPathEquals($expected, $result);

        $expected = $pluginPath . 'src' . DS . 'Template' . DS . 'Layout' . DS . 'default.ctp';
        $result = $View->getLayoutFileName();
        $this->assertPathEquals($expected, $result);
    }

    /**
     * Test getViewFileName method
     *
     * @return void
     */
    public function testGetViewFileNames()
    {
        $viewOptions = [
            'plugin' => null,
            'name' => 'Pages',
            'viewPath' => 'Pages'
        ];
        $request = $this->getMockBuilder('Cake\Network\Request')->getMock();
        $response = $this->getMockBuilder('Cake\Network\Response')->getMock();

        $View = new TestView(null, null, null, $viewOptions);

        $expected = TEST_APP . 'TestApp' . DS . 'Template' . DS . 'Pages' . DS . 'home.ctp';
        $result = $View->getViewFileName('home');
        $this->assertPathEquals($expected, $result);

        $expected = TEST_APP . 'TestApp' . DS . 'Template' . DS . 'Posts' . DS . 'index.ctp';
        $result = $View->getViewFileName('/Posts/index');
        $this->assertPathEquals($expected, $result);

        $expected = TEST_APP . 'TestApp' . DS . 'Template' . DS . 'Posts' . DS . 'index.ctp';
        $result = $View->getViewFileName('../Posts/index');
        $this->assertPathEquals($expected, $result);

        $expected = TEST_APP . 'TestApp' . DS . 'Template' . DS . 'Pages' . DS . 'page.home.ctp';
        $result = $View->getViewFileName('page.home');
        $this->assertPathEquals($expected, $result, 'Should not ruin files with dots.');

        Plugin::load('TestPlugin');
        $expected = TEST_APP . 'TestApp' . DS . 'Template' . DS . 'Pages' . DS . 'home.ctp';
        $result = $View->getViewFileName('TestPlugin.home');
        $this->assertPathEquals($expected, $result, 'Plugin is missing the view, cascade to app.');

        $View->templatePath('Tests');
        $expected = TEST_APP . 'Plugin' . DS . 'TestPlugin' . DS . 'src' . DS .
            'Template' . DS . 'Tests' . DS . 'index.ctp';
        $result = $View->getViewFileName('TestPlugin.index');
        $this->assertPathEquals($expected, $result);
    }

    /**
     * Test that getViewFileName() protects against malicious directory traversal.
     *
     * @expectedException \InvalidArgumentException
     * @return void
     */
    public function testGetViewFileNameDirectoryTraversal()
    {
        $viewOptions = [
            'plugin' => null,
            'name' => 'Pages',
            'viewPath' => 'Pages',
        ];
        $request = $this->getMockBuilder('Cake\Network\Request')->getMock();
        $response = $this->getMockBuilder('Cake\Network\Response')->getMock();

        $view = new TestView(null, null, null, $viewOptions);
        $view->ext('.php');
        $view->getViewFileName('../../../../bootstrap');
    }

    /**
     * Test getting layout filenames
     *
     * @return void
     */
    public function testGetLayoutFileName()
    {
        $viewOptions = ['plugin' => null,
            'name' => 'Pages',
            'viewPath' => 'Pages',
            'action' => 'display'
        ];

        $View = new TestView(null, null, null, $viewOptions);

        $expected = TEST_APP . 'TestApp' . DS . 'Template' . DS . 'Layout' . DS . 'default.ctp';
        $result = $View->getLayoutFileName();
        $this->assertPathEquals($expected, $result);

        $View->layoutPath = 'rss';
        $expected = TEST_APP . 'TestApp' . DS . 'Template' . DS . 'Layout' . DS . 'rss' . DS . 'default.ctp';
        $result = $View->getLayoutFileName();
        $this->assertPathEquals($expected, $result);

        $View->layoutPath = 'Email' . DS . 'html';
        $expected = TEST_APP . 'TestApp' . DS . 'Template' . DS . 'Layout' . DS . 'Email' . DS . 'html' . DS . 'default.ctp';
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
        $viewOptions = ['plugin' => null,
            'name' => 'Pages',
            'viewPath' => 'Pages',
            'action' => 'display'
        ];

        $View = new TestView(null, null, null, $viewOptions);
        Plugin::load('TestPlugin');

        $expected = TEST_APP . 'Plugin' . DS . 'TestPlugin' . DS . 'src' . DS .
            'Template' . DS . 'Layout' . DS . 'default.ctp';
        $result = $View->getLayoutFileName('TestPlugin.default');
        $this->assertPathEquals($expected, $result);

        $View->plugin = 'TestPlugin';
        $expected = TEST_APP . 'Plugin' . DS . 'TestPlugin' . DS . 'src' . DS .
            'Template' . DS . 'Layout' . DS . 'default.ctp';
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
        $View->request->params['prefix'] = 'foo_prefix';
        $expected = TEST_APP . 'TestApp' . DS . 'Template' . DS .
            'FooPrefix' . DS . 'Layout' . DS . 'default.ctp';
        $result = $View->getLayoutFileName();
        $this->assertPathEquals($expected, $result);

        $View->request->params['prefix'] = 'FooPrefix';
        $result = $View->getLayoutFileName();
        $this->assertPathEquals($expected, $result);

        // Nested prefix layout
        $View->request->params['prefix'] = 'foo_prefix/bar_prefix';
        $expected = TEST_APP . 'TestApp' . DS . 'Template' . DS .
            'FooPrefix' . DS . 'BarPrefix' . DS . 'Layout' . DS . 'default.ctp';
        $result = $View->getLayoutFileName();
        $this->assertPathEquals($expected, $result);

        $View->request->params['prefix'] = 'foo_prefix/bar_prefix';
        $expected = TEST_APP . 'TestApp' . DS . 'Template' . DS .
            'FooPrefix' . DS . 'Layout' . DS . 'nested_prefix_cascade.ctp';
        $result = $View->getLayoutFileName('nested_prefix_cascade');
        $this->assertPathEquals($expected, $result);

        // Fallback to app's layout
        $View->request->params['prefix'] = 'Admin';
        $expected = TEST_APP . 'TestApp' . DS . 'Template' . DS .
            'Layout' . DS . 'default.ctp';
        $result = $View->getLayoutFileName();
        $this->assertPathEquals($expected, $result);
    }

    /**
     * Test that getLayoutFileName() protects against malicious directory traversal.
     *
     * @expectedException \InvalidArgumentException
     * @return void
     */
    public function testGetLayoutFileNameDirectoryTraversal()
    {
        $viewOptions = [
            'plugin' => null,
            'name' => 'Pages',
            'viewPath' => 'Pages',
        ];
        $request = $this->getMockBuilder('Cake\Network\Request')->getMock();
        $response = $this->getMockBuilder('Cake\Network\Response')->getMock();

        $view = new TestView(null, null, null, $viewOptions);
        $view->ext('.php');
        $view->getLayoutFileName('../../../../bootstrap');
    }

    /**
     * Test for missing views
     *
     * @expectedException \Cake\View\Exception\MissingTemplateException
     * @return void
     */
    public function testMissingTemplate()
    {
        $viewOptions = ['plugin' => null,
            'name' => 'Pages',
            'viewPath' => 'Pages'
        ];
        $request = $this->getMockBuilder('Cake\Network\Request')->getMock();
        $response = $this->getMockBuilder('Cake\Network\Response')->getMock();

        $View = new TestView($request, $response, null, $viewOptions);
        $View->getViewFileName('does_not_exist');
    }

    /**
     * Test for missing layouts
     *
     * @expectedException \Cake\View\Exception\MissingLayoutException
     * @return void
     */
    public function testMissingLayout()
    {
        $viewOptions = ['plugin' => null,
            'name' => 'Pages',
            'viewPath' => 'Pages',
            'layout' => 'whatever'
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
        $this->assertEquals(['testData' => 'Some test data', 'test2' => 'more data', 'test3' => 'even more data'], $this->View->viewVars);
    }

    /**
     * Test generation of UUIDs method
     *
     * @return void
     */
    public function testUUIDGeneration()
    {
        Router::connect('/:controller', ['action' => 'index']);
        $result = $this->View->uuid('form', ['controller' => 'posts', 'action' => 'index']);
        $this->assertEquals('form5988016017', $result);

        $result = $this->View->uuid('form', ['controller' => 'posts', 'action' => 'index']);
        $this->assertEquals('formc3dc6be854', $result);

        $result = $this->View->uuid('form', ['controller' => 'posts', 'action' => 'index']);
        $this->assertEquals('form28f92cc87f', $result);
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

        $result = $this->View->elementExists('non_existent_element');
        $this->assertFalse($result);

        $result = $this->View->elementExists('TestPlugin.element');
        $this->assertFalse($result);

        $this->View->plugin = 'TestPlugin';
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
        $this->assertEquals('this is the test element', $result);

        $result = $this->View->element('TestPlugin.plugin_element');
        $this->assertEquals("Element in the TestPlugin\n", $result);

        $this->View->plugin = 'TestPlugin';
        $result = $this->View->element('plugin_element');
        $this->assertEquals("Element in the TestPlugin\n", $result);

        $result = $this->View->element('plugin_element', [], ['plugin' => false]);
        $this->assertEquals("Plugin element overridden in app\n", $result);
    }

    /**
     * Test element method with a prefix
     *
     * @return void
     */
    public function testPrefixElement()
    {
        $this->View->request->params['prefix'] = 'Admin';
        $result = $this->View->element('prefix_element');
        $this->assertEquals('this is a prefixed test element', $result);

        $result = $this->View->element('TestPlugin.plugin_element');
        $this->assertEquals('this is the plugin prefixed element using params[plugin]', $result);

        $this->View->plugin = 'TestPlugin';
        $result = $this->View->element('test_plugin_element');
        $this->assertEquals('this is the test set using View::$plugin plugin prefixed element', $result);

        $this->View->request->params['prefix'] = 'FooPrefix/BarPrefix';
        $result = $this->View->element('prefix_element');
        $this->assertEquals('this is a nested prefixed test element', $result);

        $this->View->request->params['prefix'] = 'FooPrefix/BarPrefix';
        $result = $this->View->element('prefix_element_in_parent');
        $this->assertEquals('this is a nested prefixed test element in first level element', $result);
    }

    /**
     * Test elementInexistent method
     *
     * @expectedException \Cake\View\Exception\MissingElementException
     * @return void
     */
    public function testElementInexistent()
    {
        $this->View->element('non_existent_element');
    }

    /**
     * Test elementInexistent3 method
     *
     * @expectedException \Cake\View\Exception\MissingElementException
     * @return void
     */
    public function testElementInexistent3()
    {
        $this->View->element('test_plugin.plugin_element');
    }

    /**
     * Test that elements can have callbacks
     *
     * @return void
     */
    public function testElementCallbacks()
    {
        $count = 0;
        $callback = function ($event, $file) use (&$count) {
            $count++;
        };
        $events = $this->View->eventManager();
        $events->attach($callback, 'View.beforeRender');
        $events->attach($callback, 'View.afterRender');

        $this->View->element('test_element', [], ['callbacks' => true]);
        $this->assertEquals(2, $count);
    }

    /**
     * Test that additional element viewVars don't get overwritten with helpers.
     *
     * @return void
     */
    public function testElementParamsDontOverwriteHelpers()
    {
        $Controller = new ViewPostsController();
        $Controller->helpers = ['Form'];

        $View = $Controller->createView();
        $result = $View->element('type_check', ['form' => 'string'], ['callbacks' => true]);
        $this->assertEquals('string', $result);

        $View->set('form', 'string');
        $result = $View->element('type_check', [], ['callbacks' => true]);
        $this->assertEquals('string', $result);
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
        $this->assertEquals('this is the test element', $result);
    }

    /**
     * Test elementCache method
     *
     * @return void
     */
    public function testElementCache()
    {
        Cache::drop('test_view');
        Cache::config('test_view', [
            'engine' => 'File',
            'duration' => '+1 day',
            'path' => CACHE . 'views/',
            'prefix' => ''
        ]);
        Cache::clear(false, 'test_view');

        $View = $this->PostsController->createView();
        $View->elementCache = 'test_view';

        $result = $View->element('test_element', [], ['cache' => true]);
        $expected = 'this is the test element';
        $this->assertEquals($expected, $result);

        $result = Cache::read('element__test_element_cache_callbacks', 'test_view');
        $this->assertEquals($expected, $result);

        $result = $View->element('test_element', ['param' => 'one', 'foo' => 'two'], ['cache' => true]);
        $this->assertEquals($expected, $result);

        $result = Cache::read('element__test_element_cache_callbacks_param_foo', 'test_view');
        $this->assertEquals($expected, $result);

        $View->element('test_element', [
            'param' => 'one',
            'foo' => 'two'
        ], [
            'cache' => ['key' => 'custom_key']
        ]);
        $result = Cache::read('element_custom_key', 'test_view');
        $this->assertEquals($expected, $result);

        $View->elementCache = 'default';
        $View->element('test_element', [
            'param' => 'one',
            'foo' => 'two'
        ], [
            'cache' => ['config' => 'test_view'],
        ]);
        $result = Cache::read('element__test_element_cache_callbacks_param_foo', 'test_view');
        $this->assertEquals($expected, $result);

        Cache::clear(true, 'test_view');
        Cache::drop('test_view');
    }

    /**
     * Test element events
     *
     * @return void
     */
    public function testViewEvent()
    {
        $View = $this->PostsController->createView();
        $View->templatePath($this->PostsController->name);
        $View->autoLayout = false;
        $listener = new TestViewEventListenerInterface();

        $View->eventManager()->attach($listener);

        $View->render('index');
        $this->assertEquals(View::TYPE_VIEW, $listener->beforeRenderViewType);
        $this->assertEquals(View::TYPE_VIEW, $listener->afterRenderViewType);

        $this->assertEquals($View->getCurrentType(), View::TYPE_VIEW);
        $View->element('test_element', [], ['callbacks' => true]);
        $this->assertEquals($View->getCurrentType(), View::TYPE_VIEW);

        $this->assertEquals(View::TYPE_ELEMENT, $listener->beforeRenderViewType);
        $this->assertEquals(View::TYPE_ELEMENT, $listener->afterRenderViewType);
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

        $config = $View->Html->config();
        $this->assertEquals('bar', $config['foo']);
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
            $this->assertContains('The "Html" alias has already been loaded', $e->getMessage());
        }
    }

    /**
     * Test loadHelpers method
     *
     * @return void
     */
    public function testLoadHelpers()
    {
        $View = new View();

        $View->helpers = ['Html' => ['foo' => 'bar'], 'Form' => ['foo' => 'baz']];
        $View->loadHelpers();

        $this->assertInstanceOf('Cake\View\Helper\HtmlHelper', $View->Html, 'Object type is wrong.');
        $this->assertInstanceOf('Cake\View\Helper\FormHelper', $View->Form, 'Object type is wrong.');

        $config = $View->Html->config();
        $this->assertEquals('bar', $config['foo']);

        $config = $View->Form->config();
        $this->assertEquals('baz', $config['foo']);
    }

    /**
     * Test lazy loading helpers
     *
     * @return void
     */
    public function testLazyLoadHelpers()
    {
        $View = new View();

        $View->helpers = [];
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
        $config = $View->Html->config();
        $this->assertEquals('myval', $config['mykey']);
    }

    /**
     * Test the correct triggering of helper callbacks
     *
     * @return void
     */
    public function testHelperCallbackTriggering()
    {
        $View = $this->PostsController->createView();
        $View->templatePath($this->PostsController->name);

        $manager = $this->getMockBuilder('Cake\Event\EventManager')->getMock();
        $View->eventManager($manager);

        $manager->expects($this->at(0))->method('dispatch')
            ->with(
                $this->logicalAnd(
                    $this->isInstanceOf('Cake\Event\Event'),
                    $this->attributeEqualTo('_name', 'View.beforeRender'),
                    $this->attributeEqualTo('_subject', $View)
                )
            );

        $manager->expects($this->at(1))->method('dispatch')
            ->with(
                $this->logicalAnd(
                    $this->isInstanceOf('Cake\Event\Event'),
                    $this->attributeEqualTo('_name', 'View.beforeRenderFile'),
                    $this->attributeEqualTo('_subject', $View)
                )
            );

        $manager->expects($this->at(2))->method('dispatch')
            ->with(
                $this->logicalAnd(
                    $this->isInstanceOf('Cake\Event\Event'),
                    $this->attributeEqualTo('_name', 'View.afterRenderFile'),
                    $this->attributeEqualTo('_subject', $View)
                )
            );

        $manager->expects($this->at(3))->method('dispatch')
            ->with(
                $this->logicalAnd(
                    $this->isInstanceOf('Cake\Event\Event'),
                    $this->attributeEqualTo('_name', 'View.afterRender'),
                    $this->attributeEqualTo('_subject', $View)
                )
            );

        $manager->expects($this->at(4))->method('dispatch')
            ->with(
                $this->logicalAnd(
                    $this->isInstanceOf('Cake\Event\Event'),
                    $this->attributeEqualTo('_name', 'View.beforeLayout'),
                    $this->attributeEqualTo('_subject', $View)
                )
            );

        $manager->expects($this->at(5))->method('dispatch')
            ->with(
                $this->logicalAnd(
                    $this->isInstanceOf('Cake\Event\Event'),
                    $this->attributeEqualTo('_name', 'View.beforeRenderFile'),
                    $this->attributeEqualTo('_subject', $View)
                )
            );

        $manager->expects($this->at(6))->method('dispatch')
            ->with(
                $this->logicalAnd(
                    $this->isInstanceOf('Cake\Event\Event'),
                    $this->attributeEqualTo('_name', 'View.afterRenderFile'),
                    $this->attributeEqualTo('_subject', $View)
                )
            );

        $manager->expects($this->at(7))->method('dispatch')
            ->with(
                $this->logicalAnd(
                    $this->isInstanceOf('Cake\Event\Event'),
                    $this->attributeEqualTo('_name', 'View.afterLayout'),
                    $this->attributeEqualTo('_subject', $View)
                )
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
        $this->PostsController->helpers = [
            'TestBeforeAfter' => ['className' => __NAMESPACE__ . '\TestBeforeAfterHelper'],
            'Html'
        ];
        $View = $this->PostsController->createView();
        $View->templatePath($this->PostsController->name);
        $View->render('index');
        $this->assertEquals('Valuation', $View->helpers()->TestBeforeAfter->property);
    }

    /**
     * Test afterLayout method
     *
     * @return void
     */
    public function testAfterLayout()
    {
        $this->PostsController->helpers = [
            'TestBeforeAfter' => ['className' => __NAMESPACE__ . '\TestBeforeAfterHelper'],
            'Html'
        ];
        $this->PostsController->set('variable', 'values');

        $View = $this->PostsController->createView();
        $View->templatePath($this->PostsController->name);

        $content = 'This is my view output';
        $result = $View->renderLayout($content, 'default');
        $this->assertRegExp('/modified in the afterlife/', $result);
        $this->assertRegExp('/This is my view output/', $result);
    }

    /**
     * Test renderLoadHelper method
     *
     * @return void
     */
    public function testRenderLoadHelper()
    {
        $this->PostsController->helpers = ['Form', 'Number'];
        $View = $this->PostsController->createView('Cake\Test\TestCase\View\TestView');
        $View->templatePath($this->PostsController->name);

        $result = $View->render('index', false);
        $this->assertEquals('posts index', $result);

        $attached = $View->helpers()->loaded();
        // HtmlHelper is loaded in TestView::initialize()
        $this->assertEquals(['Html', 'Form', 'Number'], $attached);

        $this->PostsController->helpers = ['Html', 'Form', 'Number', 'TestPlugin.PluggedHelper'];
        $View = $this->PostsController->createView('Cake\Test\TestCase\View\TestView');
        $View->templatePath($this->PostsController->name);

        $result = $View->render('index', false);
        $this->assertEquals('posts index', $result);

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
        $View = $this->PostsController->createView('Cake\Test\TestCase\View\TestView');
        $View->templatePath($this->PostsController->name);
        $result = $View->render('index');

        $this->assertRegExp("/<meta charset=\"utf-8\"\/>\s*<title>/", $result);
        $this->assertRegExp("/<div id=\"content\">\s*posts index\s*<\/div>/", $result);
        $this->assertRegExp("/<div id=\"content\">\s*posts index\s*<\/div>/", $result);

        $View = $this->PostsController->createView('Cake\Test\TestCase\View\TestView');
        $result = $View->render(false, 'ajax2');

        $this->assertRegExp('/Ajax\!/', $result);

        $this->assertNull($View->render(false, 'ajax2'));

        $this->PostsController->helpers = ['Html'];
        $this->PostsController->request->params['action'] = 'index';
        Configure::write('Cache.check', true);

        $View = $this->PostsController->createView('Cake\Test\TestCase\View\TestView');
        $View->templatePath($this->PostsController->name);
        $result = $View->render('index');

        $this->assertRegExp("/<meta charset=\"utf-8\"\/>\s*<title>/", $result);
        $this->assertRegExp("/<div id=\"content\">\s*posts index\s*<\/div>/", $result);
    }

    /**
     * Test that View::$view works
     *
     * @return void
     */
    public function testRenderUsingViewProperty()
    {
        $View = $this->PostsController->createView('Cake\Test\TestCase\View\TestView');
        $View->templatePath($this->PostsController->name);
        $View->template('cache_form');

        $this->assertEquals('cache_form', $View->view);
        $result = $View->render();
        $this->assertRegExp('/Add User/', $result);
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

        $View = $this->PostsController->createView('Cake\Test\TestCase\View\TestView');
        $View->set(compact('error', 'message'));
        $View->viewPath = 'Error';

        $result = $View->render('pdo_error', 'error');
        $this->assertRegExp('/this is sql string/', $result);
        $this->assertRegExp('/it works/', $result);
    }

    /**
     * Test render()ing a file in a subdir from a custom viewPath
     * in a plugin.
     *
     * @return void
     */
    public function testGetViewFileNameSubdirWithPluginAndViewPath()
    {
        $this->PostsController->plugin = 'TestPlugin';
        $this->PostsController->name = 'Posts';
        $View = $this->PostsController->createView('Cake\Test\TestCase\View\TestView');
        $View->templatePath('Element');
        $pluginPath = TEST_APP . 'Plugin' . DS . 'TestPlugin' . DS;
        $result = $View->getViewFileName('sub_dir/sub_element');
        $expected = $pluginPath . 'src' . DS . 'Template' . DS . 'Element' . DS . 'sub_dir' . DS . 'sub_element.ctp';
        $this->assertPathEquals($expected, $result);
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
        $Controller->helpers = ['Html'];
        $Controller->set('html', 'I am some test html');
        $View = $Controller->createView();
        $View->templatePath($Controller->name);
        $result = $View->render('helper_overwrite', false);

        $this->assertRegExp('/I am some test html/', $result);
        $this->assertRegExp('/Test link/', $result);
    }

    /**
     * Test getViewFileName method
     *
     * @return void
     */
    public function testViewFileName()
    {
        $View = $this->PostsController->createView('Cake\Test\TestCase\View\TestView');
        $View->templatePath('Posts');

        $result = $View->getViewFileName('index');
        $this->assertRegExp('/Posts(\/|\\\)index.ctp/', $result);

        $result = $View->getViewFileName('TestPlugin.index');
        $this->assertRegExp('/Posts(\/|\\\)index.ctp/', $result);

        $result = $View->getViewFileName('/Pages/home');
        $this->assertRegExp('/Pages(\/|\\\)home.ctp/', $result);

        $result = $View->getViewFileName('../Element/test_element');
        $this->assertRegExp('/Element(\/|\\\)test_element.ctp/', $result);

        $expected = TEST_APP . 'TestApp' . DS . 'Template' . DS . 'Posts' . DS . 'index.ctp';
        $result = $View->getViewFileName('../Posts/index');
        $this->assertPathEquals($expected, $result);
    }

    /**
     * Test creating a block with capturing output.
     *
     * @return void
     */
    public function testBlockCaptureOverwrite()
    {
        $this->View->start('test');
        echo 'Block content';
        $this->View->end();

        $this->View->start('test');
        echo 'New content';
        $this->View->end();

        $result = $this->View->fetch('test');
        $this->assertEquals('New content', $result);
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
        $this->assertEquals('Block contentNew content', $result);
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
        $this->assertEquals('Block content', $result);
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

        $this->View->append('test');
        echo 'appended';
        $this->View->end();

        $result = $this->View->fetch('test');
        $this->assertEquals('Content appended', $result);
    }

    /**
     * Test setting a block's content.
     *
     * @return void
     */
    public function testBlockSet()
    {
        $this->View->assign('test', 'Block content');
        $result = $this->View->fetch('test');
        $this->assertEquals('Block content', $result);
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

        $this->View->reset('test');
        $result = $this->View->fetch('test', 'This should not be returned');
        $this->assertSame('', $result);
    }

    /**
     * Test checking a block's existance.
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
     * This should produce a "Object of class TestObjectWithoutToString could not be converted to string" error
     * which gets thrown as a PHPUnit_Framework_Error Exception by PHPUnit.
     *
     * @expectedException \PHPUnit_Framework_Error
     * @return void
     */
    public function testBlockSetObjectWithoutToString()
    {
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
        $this->assertEquals('1.23456789', $result);
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
     * This should produce a "Object of class TestObjectWithoutToString could not be converted to string" error
     * which gets thrown as a PHPUnit_Framework_Error Exception by PHPUnit.
     *
     * @expectedException \PHPUnit_Framework_Error
     * @return void
     */
    public function testBlockAppendObjectWithoutToString()
    {
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
        $this->View->prepend('test', $value);

        $result = $this->View->fetch('test');
        $this->assertEquals($value . 'Block', $result);
    }

    /**
     * Test prepending an object without __toString magic method to a block with prepend.
     *
     * This should produce a "Object of class TestObjectWithoutToString could not be converted to string" error
     * which gets thrown as a PHPUnit_Framework_Error Exception by PHPUnit.
     *
     * @expectedException \PHPUnit_Framework_Error
     * @return void
     */
    public function testBlockPrependObjectWithoutToString()
    {
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
        $this->View->append('test', 'Unknown');
        $result = $this->View->fetch('test');
        $this->assertEquals('Unknown', $result);
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
        $this->assertEquals('Unknown', $result);
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

        $this->assertEquals('In first In first', $this->View->fetch('first'));
        $this->assertEquals('In second', $this->View->fetch('second'));
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
        } catch (\Cake\Core\Exception\Exception $e) {
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
            $this->assertContains('The "no_close" block was left open', $e->getMessage());
        }
    }

    /**
     * Test nested extended views.
     *
     * @return void
     */
    public function testExtendNested()
    {
        $this->View->layout = false;
        $content = $this->View->render('nested_extends');
        $expected = <<<TEXT
This is the second parent.
This is the first parent.
This is the first template.
Sidebar Content.
TEXT;
        $this->assertEquals($expected, $content);
    }

    /**
     * Make sure that extending the current view with itself causes an exception
     *
     * @return void
     */
    public function testExtendSelf()
    {
        try {
            $this->View->layout = false;
            $this->View->render('extend_self');
            $this->fail('No exception');
        } catch (\LogicException $e) {
            ob_end_clean();
            $this->assertContains('cannot have views extend themselves', $e->getMessage());
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
            $this->View->layout = false;
            $this->View->render('extend_loop');
        } catch (\LogicException $e) {
            ob_end_clean();
            $this->assertContains('cannot have views extend in a loop', $e->getMessage());
        }
    }

    /**
     * Test extend() in an element and a view.
     *
     * @return void
     */
    public function testExtendElement()
    {
        $this->View->layout = false;
        $content = $this->View->render('extend_element');
        $expected = <<<TEXT
Parent View.
View content.
Parent Element.
Element content.

TEXT;
        $this->assertEquals($expected, $content);
    }

    /**
     * Test extend() in an element and a view.
     *
     * @return void
     */
    public function testExtendPrefixElement()
    {
        $this->View->request->params['prefix'] = 'Admin';
        $this->View->layout = false;
        $content = $this->View->render('extend_element');
        $expected = <<<TEXT
Parent View.
View content.
Parent Element.
Prefix Element content.

TEXT;
        $this->assertEquals($expected, $content);
    }

    /**
     * Extending an element which doesn't exist should throw a missing view exception
     *
     * @return void
     */
    public function testExtendMissingElement()
    {
        try {
            $this->View->layout = false;
            $this->View->render('extend_missing_element');
            $this->fail('No exception');
        } catch (\LogicException $e) {
            ob_end_clean();
            ob_end_clean();
            $this->assertContains('element', $e->getMessage());
        }
    }

    /**
     * Test extend() preceeded by an element()
     *
     * @return void
     */
    public function testExtendWithElementBeforeExtend()
    {
        $this->View->layout = false;
        $result = $this->View->render('extend_with_element');
        $expected = <<<TEXT
Parent View.
this is the test elementThe view

TEXT;
        $this->assertEquals($expected, $result);
    }

    /**
     * Test extend() preceeded by an element()
     *
     * @return void
     */
    public function testExtendWithPrefixElementBeforeExtend()
    {
        $this->View->request->params['prefix'] = 'Admin';
        $this->View->layout = false;
        $result = $this->View->render('extend_with_element');
        $expected = <<<TEXT
Parent View.
this is the test prefix elementThe view

TEXT;
        $this->assertEquals($expected, $result);
    }

    /**
     * Test memory leaks that existed in _paths at one point.
     *
     * @return void
     */
    public function testMemoryLeakInPaths()
    {
        $this->skipIf(env('CODECOVERAGE') == 1, 'Running coverage this causes this tests to fail sometimes.');
        $this->ThemeController->plugin = null;
        $this->ThemeController->name = 'Posts';

        $View = $this->ThemeController->createView();
        $View->templatePath('Posts');
        $View->layout = 'whatever';
        $View->theme = 'TestTheme';
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
        $this->assertEquals($default, $result);

        $expected = 'My Title';
        $this->View->assign('title', $expected);
        $result = $this->View->fetch('title', $default);
        $this->assertEquals($expected, $result);
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
        $this->assertEquals($default, $result);

        $expected = 'Back to the Future';
        $this->View->set('title', $expected);
        $result = $this->View->get('title', $default);
        $this->assertEquals($expected, $result);
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
     * Test magic getter and setter for removed properties.
     *
     * @return void
     */
    public function testMagicGetterSetter()
    {
        $View = $this->View;

        $View->view = 'myview';
        $this->assertEquals('myview', $View->template());
        $this->assertEquals('myview', $View->view);

        $View->viewPath = 'mypath';
        $this->assertEquals('mypath', $View->templatePath());
        $this->assertEquals('mypath', $View->templatePath);
    }
}
