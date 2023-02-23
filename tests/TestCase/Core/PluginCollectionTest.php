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
 * @since         3.6.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Core;

use Cake\Core\BasePlugin;
use Cake\Core\Configure;
use Cake\Core\Exception\CakeException;
use Cake\Core\Exception\MissingPluginException;
use Cake\Core\PluginCollection;
use Cake\Core\PluginInterface;
use Cake\TestSuite\TestCase;
use Company\TestPluginThree\TestPluginThreePlugin;
use InvalidArgumentException;
use Named\NamedPlugin;
use TestPlugin\Plugin as TestPlugin;

/**
 * PluginCollection Test
 */
class PluginCollectionTest extends TestCase
{
    public function testConstructor(): void
    {
        $plugins = new PluginCollection([new TestPlugin()]);

        $this->assertCount(1, $plugins);
        $this->assertTrue($plugins->has('TestPlugin'));
    }

    public function testAdd(): void
    {
        $plugins = new PluginCollection();
        $this->assertCount(0, $plugins);

        $plugins->add(new TestPlugin());
        $this->assertCount(1, $plugins);
    }

    public function testAddOperations(): void
    {
        $plugins = new PluginCollection();
        $plugins->add(new TestPlugin());

        $this->assertFalse($plugins->has('Nope'));
        $this->assertSame($plugins, $plugins->remove('Nope'));

        $this->assertTrue($plugins->has('TestPlugin'));
        $this->assertSame($plugins, $plugins->remove('TestPlugin'));
        $this->assertCount(0, $plugins);
        $this->assertFalse($plugins->has('TestPlugin'));
    }

    public function testAddVendoredPlugin(): void
    {
        $plugins = new PluginCollection();
        $plugins->add(new TestPluginThreePlugin());

        $this->assertTrue($plugins->has('Company/TestPluginThree'));
        $this->assertFalse($plugins->has('TestPluginThree'));
        $this->assertFalse($plugins->has('Company'));
        $this->assertFalse($plugins->has('TestPlugin'));
    }

    public function testHas(): void
    {
        $plugins = new PluginCollection();
        $this->assertFalse($plugins->has('TestPlugin'));

        $plugins->add(new TestPlugin());
        $this->assertTrue($plugins->has('TestPlugin'));
        $this->assertFalse($plugins->has('Plugin'));
    }

    public function testGet(): void
    {
        $plugins = new PluginCollection();
        $plugin = new TestPlugin();
        $plugins->add($plugin);

        $this->assertSame($plugin, $plugins->get('TestPlugin'));
    }

    public function testGetAutoload(): void
    {
        $plugins = new PluginCollection();
        $plugin = $plugins->get('Named');
        $this->assertInstanceOf(NamedPlugin::class, $plugin);
    }

    public function testGetInvalid(): void
    {
        $this->expectException(MissingPluginException::class);

        $plugins = new PluginCollection();
        $plugins->get('Invalid');
    }

    public function testCreate(): void
    {
        $plugins = new PluginCollection();

        $plugin = $plugins->create('Named');
        $this->assertInstanceOf(NamedPlugin::class, $plugin);

        $plugin = $plugins->create('Named', ['name' => 'Granpa']);
        $this->assertInstanceOf(NamedPlugin::class, $plugin);
        $this->assertSame('Granpa', $plugin->getName());

        $plugin = $plugins->create(NamedPlugin::class);
        $this->assertInstanceOf(NamedPlugin::class, $plugin);

        $plugin = $plugins->create('Company/TestPluginThree');
        $this->assertInstanceOf(TestPluginThreePlugin::class, $plugin);

        $plugin = $plugins->create('TestTheme');
        $this->assertInstanceOf(BasePlugin::class, $plugin);
        $this->assertSame('TestTheme', $plugin->getName());
    }

    public function testCreateException(): void
    {
        $this->expectException(CakeException::class);
        $this->expectExceptionMessage('Cannot create a plugin with empty name');

        $plugins = new PluginCollection();
        $plugins->create('');
    }

    public function testIterator(): void
    {
        $data = [
            new TestPlugin(),
            new TestPluginThreePlugin(),
        ];
        $plugins = new PluginCollection($data);
        $out = [];
        foreach ($plugins as $key => $plugin) {
            $this->assertInstanceOf(PluginInterface::class, $plugin);
            $out[] = $plugin;
        }
        $this->assertSame($data, $out);
    }

    public function testWith(): void
    {
        $plugins = new PluginCollection();
        $plugin = new TestPlugin();
        $plugin->disable('routes');

        $pluginThree = new TestPluginThreePlugin();

        $plugins->add($plugin);
        $plugins->add($pluginThree);

        $out = [];
        foreach ($plugins->with('routes') as $p) {
            $out[] = $p;
        }
        $this->assertCount(1, $out);
        $this->assertSame($pluginThree, $out[0]);
    }

    /**
     * Test that looping over the plugin collection during
     * a with loop doesn't lose iteration state.
     *
     * This situation can happen when a plugin like bake
     * needs to discover things inside other plugins.
     */
    public function testWithInnerIteration(): void
    {
        $plugins = new PluginCollection();
        $plugin = new TestPlugin();
        $pluginThree = new TestPluginThreePlugin();

        $plugins->add($plugin);
        $plugins->add($pluginThree);

        $out = [];
        foreach ($plugins->with('routes') as $p) {
            foreach ($plugins as $i) {
                // Do nothing, we just need to enumerate the collection
            }
            $out[] = $p;
        }
        $this->assertCount(2, $out);
        $this->assertSame($plugin, $out[0]);
        $this->assertSame($pluginThree, $out[1]);
    }

    public function testWithInvalidHook(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $plugins = new PluginCollection();
        foreach ($plugins->with('bad') as $p) {
        }
    }

    public function testFindPathNoConfigureData(): void
    {
        Configure::write('plugins', []);
        $plugins = new PluginCollection();
        $path = $plugins->findPath('TestPlugin');

        $this->assertSame(TEST_APP . 'Plugin' . DS . 'TestPlugin' . DS, $path);
    }

    public function testFindPathLoadsConfigureData(): void
    {
        $configPath = ROOT . DS . 'cakephp-plugins.php';
        $this->skipIf(file_exists($configPath), 'cakephp-plugins.php exists, skipping overwrite');
        $file = <<<PHP
<?php
declare(strict_types=1);
return [
    'plugins' => [
        'TestPlugin' => '/config/path'
    ]
];
PHP;
        file_put_contents($configPath, $file);

        $plugins = new PluginCollection();
        Configure::delete('plugins');
        $path = $plugins->findPath('TestPlugin');
        unlink($configPath);

        $this->assertSame('/config/path', $path);
    }

    public function testFindPathConfigureData(): void
    {
        Configure::write('plugins', ['TestPlugin' => '/some/path']);
        $plugins = new PluginCollection();
        $path = $plugins->findPath('TestPlugin');

        $this->assertSame('/some/path', $path);
    }

    public function testFindPathMissingPlugin(): void
    {
        Configure::write('plugins', []);
        $plugins = new PluginCollection();

        $this->expectException(MissingPluginException::class);
        $plugins->findPath('InvalidPlugin');
    }
}
