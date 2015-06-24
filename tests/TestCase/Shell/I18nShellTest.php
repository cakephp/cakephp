<?php
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         3.0.8
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Shell;

use Cake\Cache\Cache;
use Cake\Datasource\ConnectionManager;
use Cake\Shell\I18nShell;
use Cake\TestSuite\TestCase;

/**
 * I18nShell test.
 */
class I18nShellTest extends TestCase
{

    /**
     * setup method
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        $this->io = $this->getMock('Cake\Console\ConsoleIo');
        $this->shell = new I18nShell($this->io);
    }

    /**
     * Teardown
     *
     * @return void
     */
    public function tearDown()
    {
        parent::tearDown();
    }

    /**
     * Tests that init() creates the PO files from POT files.
     *
     * @return void
     */
    public function testInit()
    {
        $localeDir = TMP . 'Locale' . DS;
        $deDir = $localeDir . 'de' . DS;
        if (!is_dir($deDir)) {
            mkdir($deDir, 0770, true);
        }
        file_put_contents($localeDir . 'default.pot', 'Testing POT file.');
        file_put_contents($localeDir . 'cake.pot', 'Testing POT file.');
        if (file_exists($deDir . 'default.po')) {
            unlink($deDir . 'default.po');
        }
        if (file_exists($deDir . 'default.po')) {
            unlink($deDir . 'cake.po');
        }

        $this->shell->io()->expects($this->at(0))
            ->method('ask')
            ->will($this->returnValue('de'));
        $this->shell->io()->expects($this->at(1))
            ->method('ask')
            ->will($this->returnValue($localeDir));

        $this->shell->params['verbose'] = true;
        $this->shell->init();

        $this->assertFileExists($deDir . 'default.po');
        $this->assertFileExists($deDir . 'cake.po');
    }
}
