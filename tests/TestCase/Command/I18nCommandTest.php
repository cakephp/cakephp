<?php
declare(strict_types=1);

/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         3.0.8
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Command;

use Cake\Console\TestSuite\ConsoleIntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * I18nCommand test.
 */
class I18nCommandTest extends TestCase
{
    use ConsoleIntegrationTestTrait;

    /**
     * @var string
     */
    protected $localeDir;

    /**
     * setup method
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->localeDir = TMP . 'Locale' . DS;
        $this->useCommandRunner();
        $this->setAppNamespace();
    }

    /**
     * Teardown
     */
    public function tearDown(): void
    {
        parent::tearDown();

        $deDir = $this->localeDir . 'de_DE' . DS;

        if (file_exists($this->localeDir . 'default.pot')) {
            unlink($this->localeDir . 'default.pot');
            unlink($this->localeDir . 'cake.pot');
        }
        if (file_exists($deDir . 'default.po')) {
            unlink($deDir . 'default.po');
            unlink($deDir . 'cake.po');
        }
    }

    /**
     * Tests that init() creates the PO files from POT files.
     */
    public function testInit(): void
    {
        $deDir = $this->localeDir . 'de_DE' . DS;
        if (!is_dir($deDir)) {
            mkdir($deDir, 0770, true);
        }
        file_put_contents($this->localeDir . 'default.pot', 'Testing POT file.');
        file_put_contents($this->localeDir . 'cake.pot', 'Testing POT file.');
        if (file_exists($deDir . 'default.po')) {
            unlink($deDir . 'default.po');
        }
        if (file_exists($deDir . 'default.po')) {
            unlink($deDir . 'cake.po');
        }

        $this->exec('i18n init --verbose', [
            'de_DE',
            $this->localeDir,
        ]);

        $this->assertExitSuccess();
        $this->assertOutputContains('Generated 2 PO files');
        $this->assertFileExists($deDir . 'default.po');
        $this->assertFileExists($deDir . 'cake.po');
    }

    /**
     * Test that the option parser is shaped right.
     */
    public function testGetOptionParser(): void
    {
        $this->exec('i18n -h');

        $this->assertExitSuccess();
        $this->assertOutputContains('cake i18n');
    }

    /**
     * Tests main interactive mode
     */
    public function testInteractiveQuit(): void
    {
        $this->exec('i18n', ['q']);
        $this->assertExitSuccess();
    }

    /**
     * Tests main interactive mode
     */
    public function testInteractiveHelp(): void
    {
        $this->exec('i18n', ['h', 'q']);
        $this->assertExitSuccess();
        $this->assertOutputContains('cake i18n');
    }

    /**
     * Tests main interactive mode
     */
    public function testInteractiveInit(): void
    {
        $this->exec('i18n', [
            'i',
            'x',
        ]);
        $this->assertExitError();
        $this->assertErrorContains('Invalid language code');
    }
}
