<?php
declare(strict_types=1);

/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         2.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Core;

use Cake\Core\App;
use Cake\Core\Configure;
use Cake\Database\Driver\Mysql;
use Cake\TestSuite\TestCase;
use TestApp\Core\TestApp;

/**
 * AppTest class
 */
class AppTest extends TestCase
{
    /**
     * tearDown method
     */
    public function tearDown(): void
    {
        parent::tearDown();
        $this->clearPlugins();
    }

    /**
     * testClassName
     *
     * $checkCake and $existsInCake are derived from the input parameters
     *
     * @param string $class Class name
     * @param string $type Class type
     * @param string $suffix Class suffix
     * @param bool $existsInBase Whether class exists in base.
     * @param mixed $expected Expected value.
     * @dataProvider classNameProvider
     */
    public function testClassName($class, $type, $suffix = '', $existsInBase = false, $expected = false): void
    {
        static::setAppNamespace();
        $i = 0;
        TestApp::$existsInBaseCallback = function ($name, $namespace) use ($existsInBase, $class, $expected, &$i) {
            if ($i++ === 0) {
                return $existsInBase;
            }
            $checkCake = (!$existsInBase || strpos('.', $class));
            if ($checkCake) {
                return (bool)$expected;
            }

            return false;
        };
        $return = TestApp::className($class, $type, $suffix);
        $this->assertSame($expected === false ? null : $expected, $return);
    }

    public function testClassNameWithFqcn(): void
    {
        $this->assertSame(TestCase::class, App::className(TestCase::class));
        $this->assertNull(App::className('\Foo'));
    }

    /**
     * @link https://github.com/cakephp/cakephp/issues/16258
     */
    public function testClassNameWithAppNamespaceUnset(): void
    {
        Configure::delete('App.namespace');

        $result = App::className('Mysql', 'Database/Driver');
        $this->assertSame(Mysql::class, $result);
    }

    /**
     * testShortName
     *
     * @param string $class Class name
     * @param string $type Class type
     * @param string $suffix Class suffix
     * @param mixed $expected Expected value.
     * @dataProvider shortNameProvider
     */
    public function testShortName($class, $type, $suffix = '', $expected = false): void
    {
        static::setAppNamespace();

        $return = TestApp::shortName($class, $type, $suffix);
        $this->assertSame($expected, $return);
    }

    /**
     * testShortNameWithNestedAppNamespace
     */
    public function testShortNameWithNestedAppNamespace(): void
    {
        static::setAppNamespace('TestApp/Nested');

        $return = TestApp::shortName(
            'TestApp/Nested/Controller/PagesController',
            'Controller',
            'Controller'
        );
        $this->assertSame('Pages', $return);

        static::setAppNamespace();
    }

    /**
     * @link https://github.com/cakephp/cakephp/issues/15415
     */
    public function testShortNameWithAppNamespaceUnset(): void
    {
        Configure::delete('App.namespace');

        $result = App::shortName(Mysql::class, 'Database/Driver');
        $this->assertSame('Mysql', $result);
    }

    /**
     * classNameProvider
     *
     * Return test permutations for testClassName method. Format:
     *  className
     *  type
     *  suffix
     *  existsInBase (Base meaning App or plugin namespace)
     *  expected return value
     *
     * @return array
     */
    public function classNameProvider(): array
    {
        return [
            ['Does', 'Not', 'Exist'],

            ['Exists', 'In', 'App', true, 'TestApp\In\ExistsApp'],
            ['Also/Exists', 'In', 'App', true, 'TestApp\In\Also\ExistsApp'],
            ['Also', 'Exists/In', 'App', true, 'TestApp\Exists\In\AlsoApp'],
            ['Also', 'Exists/In/Subfolder', 'App', true, 'TestApp\Exists\In\Subfolder\AlsoApp'],
            ['No', 'Suffix', '', true, 'TestApp\Suffix\No'],

            ['MyPlugin.Exists', 'In', 'Suffix', true, 'MyPlugin\In\ExistsSuffix'],
            ['MyPlugin.Also/Exists', 'In', 'Suffix', true, 'MyPlugin\In\Also\ExistsSuffix'],
            ['MyPlugin.Also', 'Exists/In', 'Suffix', true, 'MyPlugin\Exists\In\AlsoSuffix'],
            ['MyPlugin.Also', 'Exists/In/Subfolder', 'Suffix', true, 'MyPlugin\Exists\In\Subfolder\AlsoSuffix'],
            ['MyPlugin.No', 'Suffix', '', true, 'MyPlugin\Suffix\No'],

            ['Vend/MPlugin.Exists', 'In', 'Suffix', true, 'Vend\MPlugin\In\ExistsSuffix'],
            ['Vend/MPlugin.Also/Exists', 'In', 'Suffix', true, 'Vend\MPlugin\In\Also\ExistsSuffix'],
            ['Vend/MPlugin.Also', 'Exists/In', 'Suffix', true, 'Vend\MPlugin\Exists\In\AlsoSuffix'],
            ['Vend/MPlugin.Also', 'Exists/In/Subfolder', 'Suffix', true, 'Vend\MPlugin\Exists\In\Subfolder\AlsoSuffix'],
            ['Vend/MPlugin.No', 'Suffix', '', true, 'Vend\MPlugin\Suffix\No'],

            ['Exists', 'In', 'Cake', false, 'Cake\In\ExistsCake'],
            ['Also/Exists', 'In', 'Cake', false, 'Cake\In\Also\ExistsCake'],
            ['Also', 'Exists/In', 'Cake', false, 'Cake\Exists\In\AlsoCake'],
            ['Also', 'Exists/In/Subfolder', 'Cake', false, 'Cake\Exists\In\Subfolder\AlsoCake'],
            ['No', 'Suffix', '', false, 'Cake\Suffix\No'],

            // Realistic examples returning nothing
            ['App', 'Core', 'Suffix'],
            ['Auth', 'Controller/Component'],
            ['Unknown', 'Controller', 'Controller'],

            // Real examples returning class names
            ['App', 'Core', '', false, 'Cake\Core\App'],
            ['Auth', 'Controller/Component', 'Component', false, 'Cake\Controller\Component\AuthComponent'],
            ['File', 'Cache/Engine', 'Engine', false, 'Cake\Cache\Engine\FileEngine'],
            ['Command', 'Shell/Task', 'Task', false, 'Cake\Shell\Task\CommandTask'],
            ['Upgrade/Locations', 'Shell/Task', 'Task', false, 'Cake\Shell\Task\Upgrade\LocationsTask'],
            ['Pages', 'Controller', 'Controller', true, 'TestApp\Controller\PagesController'],
        ];
    }

    /**
     * pluginSplitNameProvider
     *
     * Return test permutations for testClassName method. Format:
     *  className
     *  type
     *  suffix
     *  expected return value
     *
     * @return array
     */
    public function shortNameProvider(): array
    {
        return [
            ['TestApp\In\ExistsApp', 'In', 'App', 'Exists'],
            ['TestApp\In\Also\ExistsApp', 'In', 'App', 'Also/Exists'],
            ['TestApp\Exists\In\AlsoApp', 'Exists/In', 'App', 'Also'],
            ['TestApp\Exists\In\Subfolder\AlsoApp', 'Exists/In/Subfolder', 'App', 'Also'],
            ['TestApp\Suffix\No', 'Suffix', '', 'No'],

            ['MyPlugin\In\ExistsSuffix', 'In', 'Suffix', 'MyPlugin.Exists'],
            ['MyPlugin\In\Also\ExistsSuffix', 'In', 'Suffix', 'MyPlugin.Also/Exists'],
            ['MyPlugin\Exists\In\AlsoSuffix', 'Exists/In', 'Suffix', 'MyPlugin.Also'],
            ['MyPlugin\Exists\In\Subfolder\AlsoSuffix', 'Exists/In/Subfolder', 'Suffix', 'MyPlugin.Also'],
            ['MyPlugin\Suffix\No', 'Suffix', '', 'MyPlugin.No'],

            ['Vend\MPlugin\In\ExistsSuffix', 'In', 'Suffix', 'Vend/MPlugin.Exists'],
            ['Vend\MPlugin\In\Also\ExistsSuffix', 'In', 'Suffix', 'Vend/MPlugin.Also/Exists'],
            ['Vend\MPlugin\Exists\In\AlsoSuffix', 'Exists/In', 'Suffix', 'Vend/MPlugin.Also'],
            ['Vend\MPlugin\Exists\In\Subfolder\AlsoSuffix', 'Exists/In/Subfolder', 'Suffix', 'Vend/MPlugin.Also'],
            ['Vend\MPlugin\Suffix\No', 'Suffix', '', 'Vend/MPlugin.No'],

            ['Cake\In\ExistsCake', 'In', 'Cake', 'Exists'],
            ['Cake\In\Also\ExistsCake', 'In', 'Cake', 'Also/Exists'],
            ['Cake\Exists\In\AlsoCake', 'Exists/In', 'Cake', 'Also'],
            ['Cake\Exists\In\Subfolder\AlsoCake', 'Exists/In/Subfolder', 'Cake', 'Also'],
            ['Cake\Suffix\No', 'Suffix', '', 'No'],

            ['Muffin\Webservice\Webservice\EndpointWebservice', 'Webservice', 'Webservice', 'Muffin/Webservice.Endpoint'],

            // Real examples returning class names
            ['Cake\Core\App', 'Core', '', 'App'],
            ['Cake\Controller\Component\AuthComponent', 'Controller/Component', 'Component', 'Auth'],
            ['Cake\Cache\Engine\FileEngine', 'Cache/Engine', 'Engine', 'File'],
            ['Cake\Shell\Task\CommandTask', 'Shell/Task', 'Task', 'Command'],
            ['Cake\Shell\Task\Upgrade\LocationsTask', 'Shell/Task', 'Task', 'Upgrade/Locations'],
            ['TestApp\Controller\PagesController', 'Controller', 'Controller', 'Pages'],
        ];
    }

    /**
     * test classPath() with a plugin.
     */
    public function testClassPathWithPlugins(): void
    {
        $basepath = TEST_APP . 'Plugin' . DS;
        $this->loadPlugins(['TestPlugin', 'Company/TestPluginThree']);

        $result = App::classPath('Controller', 'TestPlugin');
        $this->assertPathEquals($basepath . 'TestPlugin' . DS . 'src' . DS . 'Controller' . DS, $result[0]);

        $result = App::classPath('Controller', 'Company/TestPluginThree');
        $expected = $basepath . 'Company' . DS . 'TestPluginThree' . DS . 'src' . DS . 'Controller' . DS;
        $this->assertPathEquals($expected, $result[0]);
    }

    /**
     * test path() with a plugin.
     *
     * @deprecated
     */
    public function testPathWithPlugins(): void
    {
        $basepath = TEST_APP . 'Plugin' . DS;
        $this->loadPlugins(['TestPlugin', 'Company/TestPluginThree']);

        $result = App::path('locales', 'TestPlugin');
        $this->assertPathEquals($basepath . 'TestPlugin' . DS . 'resources' . DS . 'locales' . DS, $result[0]);

        $result = App::path('locales', 'Company/TestPluginThree');
        $expected = $basepath . 'Company' . DS . 'TestPluginThree' . DS . 'resources' . DS . 'locales' . DS;
        $this->assertPathEquals($expected, $result[0]);

        $this->deprecated(function () use ($basepath): void {
            $result = App::path('Controller', 'TestPlugin');
            $this->assertPathEquals($basepath . 'TestPlugin' . DS . 'src' . DS . 'Controller' . DS, $result[0]);

            $result = App::path('Controller', 'Company/TestPluginThree');
            $expected = $basepath . 'Company' . DS . 'TestPluginThree' . DS . 'src' . DS . 'Controller' . DS;
            $this->assertPathEquals($expected, $result[0]);
        });
    }

    /**
     * testCore method
     */
    public function testCore(): void
    {
        $model = App::core('Model');
        $this->assertEquals([CAKE . 'Model' . DS], $model);

        $view = App::core('View');
        $this->assertEquals([CAKE . 'View' . DS], $view);

        $controller = App::core('Controller');
        $this->assertEquals([CAKE . 'Controller' . DS], $controller);

        $component = App::core('Controller/Component');
        $this->assertEquals([CAKE . 'Controller' . DS . 'Component' . DS], str_replace('/', DS, $component));

        $auth = App::core('Controller/Component/Auth');
        $this->assertEquals([CAKE . 'Controller' . DS . 'Component' . DS . 'Auth' . DS], str_replace('/', DS, $auth));

        $datasource = App::core('Model/Datasource');
        $this->assertEquals([CAKE . 'Model' . DS . 'Datasource' . DS], str_replace('/', DS, $datasource));
    }
}
