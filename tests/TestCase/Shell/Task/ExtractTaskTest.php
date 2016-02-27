<?php
/**
 * CakePHP :  Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP Project
 * @since         1.2.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Shell\Task;

use Cake\Core\Configure;
use Cake\Core\Plugin;
use Cake\Filesystem\Folder;
use Cake\TestSuite\TestCase;

/**
 * ExtractTaskTest class
 *
 */
class ExtractTaskTest extends TestCase
{

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        $this->io = $this->getMock('Cake\Console\ConsoleIo', [], [], '', false);
        $progress = $this->getMock('Cake\Shell\Helper\ProgressHelper', [], [$this->io]);
        $this->io->method('helper')
            ->will($this->returnValue($progress));

        $this->Task = $this->getMock(
            'Cake\Shell\Task\ExtractTask',
            ['in', 'out', 'err', '_stop'],
            [$this->io]
        );
        $this->path = TMP . 'tests/extract_task_test';
        new Folder($this->path . DS . 'locale', true);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown()
    {
        parent::tearDown();
        unset($this->Task);

        $Folder = new Folder($this->path);
        $Folder->delete();
        Plugin::unload();
    }

    /**
     * testExecute method
     *
     * @return void
     */
    public function testExecute()
    {
        $this->Task->params['paths'] = TEST_APP . 'TestApp' . DS . 'Template' . DS . 'Pages';
        $this->Task->params['output'] = $this->path . DS;
        $this->Task->params['extract-core'] = 'no';
        $this->Task->params['merge'] = 'no';

        $this->Task->expects($this->never())->method('err');
        $this->Task->expects($this->any())->method('in')
            ->will($this->returnValue('y'));
        $this->Task->expects($this->never())->method('_stop');

        $this->Task->main();
        $this->assertTrue(file_exists($this->path . DS . 'default.pot'));
        $result = file_get_contents($this->path . DS . 'default.pot');

        $this->assertFalse(file_exists($this->path . DS . 'cake.pot'));

        // extract.ctp
        $pattern = '/\#: Template[\/\\\\]Pages[\/\\\\]extract\.ctp:\d+;\d+\n';
        $pattern .= 'msgid "You have %d new message."\nmsgid_plural "You have %d new messages."/';
        $this->assertRegExp($pattern, $result);

        $pattern = '/msgid "You have %d new message."\nmsgstr ""/';
        $this->assertNotRegExp($pattern, $result, 'No duplicate msgid');

        $pattern = '/\#: Template[\/\\\\]Pages[\/\\\\]extract\.ctp:\d+\n';
        $pattern .= 'msgid "You deleted %d message."\nmsgid_plural "You deleted %d messages."/';
        $this->assertRegExp($pattern, $result);

        $pattern = '/\#: Template[\/\\\\]Pages[\/\\\\]extract\.ctp:\d+\nmsgid "';
        $pattern .= 'Hot features!';
        $pattern .= '\\\n - No Configuration: Set-up the database and let the magic begin';
        $pattern .= '\\\n - Extremely Simple: Just look at the name...It\'s Cake';
        $pattern .= '\\\n - Active, Friendly Community: Join us #cakephp on IRC. We\'d love to help you get started';
        $pattern .= '"\nmsgstr ""/';
        $this->assertRegExp($pattern, $result);

        $this->assertContains('msgid "double \\"quoted\\""', $result, 'Strings with quotes not handled correctly');
        $this->assertContains("msgid \"single 'quoted'\"", $result, 'Strings with quotes not handled correctly');

        $pattern = '/\#: Template[\/\\\\]Pages[\/\\\\]extract\.ctp:\d+\n';
        $pattern .= 'msgctxt "mail"\n';
        $pattern .= 'msgid "letter"/';
        $this->assertRegExp($pattern, $result);

        $pattern = '/\#: Template[\/\\\\]Pages[\/\\\\]extract\.ctp:\d+\n';
        $pattern .= 'msgctxt "alphabet"\n';
        $pattern .= 'msgid "letter"/';
        $this->assertRegExp($pattern, $result);

        // extract.ctp - reading the domain.pot
        $result = file_get_contents($this->path . DS . 'domain.pot');

        $pattern = '/msgid "You have %d new message."\nmsgid_plural "You have %d new messages."/';
        $this->assertNotRegExp($pattern, $result);
        $pattern = '/msgid "You deleted %d message."\nmsgid_plural "You deleted %d messages."/';
        $this->assertNotRegExp($pattern, $result);

        $pattern = '/msgid "You have %d new message \(domain\)."\nmsgid_plural "You have %d new messages \(domain\)."/';
        $this->assertRegExp($pattern, $result);
        $pattern = '/msgid "You deleted %d message \(domain\)."\nmsgid_plural "You deleted %d messages \(domain\)."/';
        $this->assertRegExp($pattern, $result);
    }

    /**
     * testExecute with merging on method
     *
     * @return void
     */
    public function testExecuteMerge()
    {
        $this->Task->params['paths'] = TEST_APP . 'TestApp' . DS . 'Template' . DS . 'Pages';
        $this->Task->params['output'] = $this->path . DS;
        $this->Task->params['extract-core'] = 'no';
        $this->Task->params['merge'] = 'yes';

        $this->Task->expects($this->never())->method('err');
        $this->Task->expects($this->any())->method('in')
            ->will($this->returnValue('y'));
        $this->Task->expects($this->never())->method('_stop');

        $this->Task->main();
        $this->assertFileExists($this->path . DS . 'default.pot');
        $this->assertFileNotExists($this->path . DS . 'cake.pot');
        $this->assertFileNotExists($this->path . DS . 'domain.pot');
    }

    /**
     * test exclusions
     *
     * @return void
     */
    public function testExtractWithExclude()
    {
        $this->Task->interactive = false;

        $this->Task->params['paths'] = TEST_APP . 'TestApp/Template';
        $this->Task->params['output'] = $this->path . DS;
        $this->Task->params['exclude'] = 'Pages,Layout';
        $this->Task->params['extract-core'] = 'no';

        $this->Task->expects($this->any())->method('in')
            ->will($this->returnValue('y'));

        $this->Task->main();
        $this->assertTrue(file_exists($this->path . DS . 'default.pot'));
        $result = file_get_contents($this->path . DS . 'default.pot');

        $pattern = '/\#: .*extract\.ctp:\d+\n/';
        $this->assertNotRegExp($pattern, $result);

        $pattern = '/\#: .*default\.ctp:\d+\n/';
        $this->assertNotRegExp($pattern, $result);
    }
    /**
     * testExtractWithoutLocations method
     *
     * @return void
     */
    public function testExtractWithoutLocations()
    {
        $this->Task->params['paths'] = TEST_APP . 'TestApp/Template';
        $this->Task->params['output'] = $this->path . DS;
        $this->Task->params['exclude'] = 'Pages,Layout';
        $this->Task->params['extract-core'] = 'no';
        $this->Task->params['no-location'] = true;

        $this->Task->expects($this->never())->method('err');
        $this->Task->expects($this->any())->method('in')
            ->will($this->returnValue('y'));

        $this->Task->main();
        $this->assertTrue(file_exists($this->path . DS . 'default.pot'));

        $result = file_get_contents($this->path . DS . 'default.pot');

        $pattern = '/\n\#: .*\n/';
        $this->assertNotRegExp($pattern, $result);
    }

    /**
     * test extract can read more than one path.
     *
     * @return void
     */
    public function testExtractMultiplePaths()
    {
        $this->Task->interactive = false;

        $this->Task->params['paths'] =
            TEST_APP . 'TestApp/Template/Pages,' .
            TEST_APP . 'TestApp/Template/Posts';

        $this->Task->params['output'] = $this->path . DS;
        $this->Task->params['extract-core'] = 'no';
        $this->Task->expects($this->never())->method('err');
        $this->Task->expects($this->never())->method('_stop');
        $this->Task->main();

        $result = file_get_contents($this->path . DS . 'default.pot');

        $pattern = '/msgid "Add User"/';
        $this->assertRegExp($pattern, $result);
    }

    /**
     * Tests that it is possible to exclude plugin paths by enabling the param option for the ExtractTask
     *
     * @return void
     */
    public function testExtractExcludePlugins()
    {
        Configure::write('App.namespace', 'TestApp');
        $this->Task = $this->getMock(
            'Cake\Shell\Task\ExtractTask',
            ['_isExtractingApp', 'in', 'out', 'err', 'clear', '_stop'],
            [$this->io]
        );
        $this->Task->expects($this->exactly(1))
            ->method('_isExtractingApp')
            ->will($this->returnValue(true));

        $this->Task->params['paths'] = TEST_APP . 'TestApp/';
        $this->Task->params['output'] = $this->path . DS;
        $this->Task->params['exclude-plugins'] = true;

        $this->Task->main();
        $result = file_get_contents($this->path . DS . 'default.pot');
        $this->assertNotRegExp('#TestPlugin#', $result);
    }

    /**
     * Test that is possible to extract messages from a single plugin
     *
     * @return void
     */
    public function testExtractPlugin()
    {
        Configure::write('App.namespace', 'TestApp');

        $this->Task = $this->getMock(
            'Cake\Shell\Task\ExtractTask',
            ['_isExtractingApp', 'in', 'out', 'err', 'clear', '_stop'],
            [$this->io]
        );

        $this->Task->params['output'] = $this->path . DS;
        $this->Task->params['plugin'] = 'TestPlugin';

        $this->Task->main();
        $result = file_get_contents($this->path . DS . 'default.pot');
        $this->assertNotRegExp('#Pages#', $result);
        $this->assertRegExp('/translate\.ctp:\d+/', $result);
        $this->assertContains('This is a translatable string', $result);
    }

    /**
     * Test that is possible to extract messages from a vendored plugin.
     *
     * @return void
     */
    public function testExtractVendoredPlugin()
    {
        Configure::write('App.namespace', 'TestApp');

        $this->Task = $this->getMock(
            'Cake\Shell\Task\ExtractTask',
            ['_isExtractingApp', 'in', 'out', 'err', 'clear', '_stop'],
            [$this->io]
        );

        $this->Task->params['output'] = $this->path . DS;
        $this->Task->params['plugin'] = 'Company/TestPluginThree';

        $this->Task->main();
        $result = file_get_contents($this->path . DS . 'test_plugin_three.pot');
        $this->assertNotRegExp('#Pages#', $result);
        $this->assertRegExp('/default\.ctp:\d+/', $result);
        $this->assertContains('A vendor message', $result);
    }

    /**
     *  Test that the extract shell overwrites existing files with the overwrite parameter
     *
     * @return void
     */
    public function testExtractOverwrite()
    {
        Configure::write('App.namespace', 'TestApp');
        $this->Task->interactive = false;

        $this->Task->params['paths'] = TEST_APP . 'TestApp/';
        $this->Task->params['output'] = $this->path . DS;
        $this->Task->params['extract-core'] = 'no';
        $this->Task->params['overwrite'] = true;

        file_put_contents($this->path . DS . 'default.pot', 'will be overwritten');
        $this->assertTrue(file_exists($this->path . DS . 'default.pot'));
        $original = file_get_contents($this->path . DS . 'default.pot');

        $this->Task->main();
        $result = file_get_contents($this->path . DS . 'default.pot');
        $this->assertNotEquals($original, $result);
    }

    /**
     *  Test that the extract shell scans the core libs
     *
     * @return void
     */
    public function testExtractCore()
    {
        Configure::write('App.namespace', 'TestApp');
        $this->Task->interactive = false;

        $this->Task->params['paths'] = TEST_APP . 'TestApp/';
        $this->Task->params['output'] = $this->path . DS;
        $this->Task->params['extract-core'] = 'yes';

        $this->Task->main();
        $this->assertTrue(file_exists($this->path . DS . 'cake.pot'));
        $result = file_get_contents($this->path . DS . 'cake.pot');

        $pattern = '/#: Console\/Templates\//';
        $this->assertNotRegExp($pattern, $result);

        $pattern = '/#: Test\//';
        $this->assertNotRegExp($pattern, $result);
    }
}
