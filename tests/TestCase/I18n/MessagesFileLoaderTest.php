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
     * Set Up
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        $this->localePaths = Configure::read('App.paths.locales');
    }

    /**
     * Tear down method
     *
     * @return void
     */
    public function tearDown()
    {
        parent::tearDown();
        Configure::write('App.paths.locales', $this->localePaths);
    }

    /**
     * test reading file from custom locale folder
     *
     * @return void
     */
    public function testCustomLocalePath()
    {
        $loader = new MessagesFileLoader('default', 'en');
        $package = $loader();
        $messages = $package->getMessages();
        $this->assertEquals('Po (translated)', $messages['Plural Rule 1']['_context']['']);

        Configure::write('App.paths.locales', [TEST_APP . 'custom_locale' . DS]);
        $loader = new MessagesFileLoader('default', 'en');
        $package = $loader();
        $messages = $package->getMessages();
        $this->assertEquals('Po (translated) from custom folder', $messages['Plural Rule 1']['_context']['']);
    }

    /**
     * Test reading MO files
     * @return void
     */
    public function testLoadingMoFiles()
    {
        $loader = new MessagesFileLoader('empty', 'es', 'mo');
        $package = $loader();
        $this->assertNotFalse($package);

        $loader = new MessagesFileLoader('missing', 'es', 'mo');
        $package = $loader();
        $this->assertFalse($package);
    }
}
