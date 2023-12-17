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
namespace Cake\Test\TestCase\I18n;

use Cake\Core\Configure;
use Cake\I18n\MessagesFileLoader;
use Cake\TestSuite\TestCase;

/**
 * MessagesFileLoaderTest class
 */
class MessagesFileLoaderTest extends TestCase
{
    /**
     * test reading file from custom locale folder
     */
    public function testCustomLocalePath(): void
    {
        $loader = new MessagesFileLoader('default', 'en');
        $package = $loader();
        $messages = $package->getMessages();
        $this->assertSame('Po (translated)', $messages['Plural Rule 1']['_context']['']);

        Configure::write('App.paths.locales', [TEST_APP . 'custom_locale' . DS]);
        $loader = new MessagesFileLoader('default', 'en');
        $package = $loader();
        $messages = $package->getMessages();
        $this->assertSame('Po (translated) from custom folder', $messages['Plural Rule 1']['_context']['']);
    }

    /**
     * Test reading MO files
     */
    public function testLoadingMoFiles(): void
    {
        $loader = new MessagesFileLoader('empty', 'es', 'mo');
        $package = $loader();
        $this->assertNotFalse($package);

        $loader = new MessagesFileLoader('missing', 'es', 'mo');
        $package = $loader();
        $this->assertFalse($package);
    }

    /**
     * Testing MessagesFileLoader::translationsFilder array sequence
     */
    public function testTranslationFoldersSequence(): void
    {
        $this->loadPlugins([
            'TestPluginTwo' => [],
        ]);
        $loader = new MessagesFileLoader('test_plugin_two', 'en');

        $expected = [
            ROOT . DS . 'tests' . DS . 'test_app' . DS . 'resources' . DS . 'locales' . DS . 'en_' . DS,
            ROOT . DS . 'tests' . DS . 'test_app' . DS . 'resources' . DS . 'locales' . DS . 'en' . DS,
            ROOT . DS . 'tests' . DS . 'test_app' . DS . 'Plugin' . DS . 'TestPluginTwo' . DS . 'resources' . DS . 'locales' . DS . 'en_' . DS,
            ROOT . DS . 'tests' . DS . 'test_app' . DS . 'Plugin' . DS . 'TestPluginTwo' . DS . 'resources' . DS . 'locales' . DS . 'en' . DS,
        ];
        $result = $loader->translationsFolders();
        $this->assertEquals($expected, $result);
    }

    /**
     * Testing plugin override from app
     */
    public function testAppOverridesPlugin(): void
    {
        $this->loadPlugins([
            'TestPlugin' => [],
            'TestPluginTwo' => [],
        ]);

        $loader = new MessagesFileLoader('test_plugin', 'en');
        $package = $loader();
        $messages = $package->getMessages();
        $this->assertSame('Plural Rule 1 (from plugin)', $messages['Plural Rule 1']['_context']['']);

        $loader = new MessagesFileLoader('test_plugin_two', 'en');
        $package = $loader();
        $messages = $package->getMessages();
        $this->assertSame('Test Message (from app)', $messages['Test Message']['_context']['']);
    }
}
