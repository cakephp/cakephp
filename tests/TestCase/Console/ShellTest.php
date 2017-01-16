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
namespace Cake\Test\TestCase\Console;

use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use Cake\Console\Shell;
use Cake\Core\Configure;
use Cake\Core\Plugin;
use Cake\Filesystem\Folder;
use Cake\TestSuite\TestCase;
use TestApp\Shell\TestingDispatchShell;

/**
 * for testing merging vars
 */
class MergeShell extends Shell
{

    public $tasks = ['DbConfig', 'Fixture'];

    public $modelClass = 'Articles';
}

/**
 * ShellTestShell class
 */
class ShellTestShell extends Shell
{

    /**
     * name property
     *
     * @var string
     */
    public $name = 'ShellTestShell';

    /**
     * stopped property
     *
     * @var int
     */
    public $stopped;

    /**
     * testMessage property
     *
     * @var string
     */
    public $testMessage = 'all your base are belong to us';

    /**
     * stop method
     *
     * @param int $status
     * @return void
     */
    protected function _stop($status = Shell::CODE_SUCCESS)
    {
        $this->stopped = $status;
    }

    protected function _secret()
    {
    }

    //@codingStandardsIgnoreStart
    public function doSomething()
    {
    }

    protected function noAccess()
    {
    }

    public function logSomething()
    {
        $this->log($this->testMessage);
    }
    //@codingStandardsIgnoreEnd
}

/**
 * TestAppleTask class
 */
class TestAppleTask extends Shell
{
}

/**
 * TestBananaTask class
 */
class TestBananaTask extends Shell
{
}

class_alias(__NAMESPACE__ . '\TestAppleTask', 'Cake\Shell\Task\TestAppleTask');
class_alias(__NAMESPACE__ . '\TestBananaTask', 'Cake\Shell\Task\TestBananaTask');

/**
 * ShellTest class
 */
class ShellTest extends TestCase
{

    /**
     * Fixtures used in this test case
     *
     * @var array
     */
    public $fixtures = [
        'core.articles',
        'core.articles_tags',
        'core.attachments',
        'core.comments',
        'core.posts',
        'core.tags',
        'core.users'
    ];

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();

        $this->io = $this->getMockBuilder('Cake\Console\ConsoleIo')
            ->disableOriginalConstructor()
            ->getMock();
        $this->Shell = new ShellTestShell($this->io);

        if (is_dir(TMP . 'shell_test')) {
            $Folder = new Folder(TMP . 'shell_test');
            $Folder->delete();
        }
    }

    /**
     * testConstruct method
     *
     * @return void
     */
    public function testConstruct()
    {
        $this->assertEquals('ShellTestShell', $this->Shell->name);
        $this->assertInstanceOf('Cake\Console\ConsoleIo', $this->Shell->io());
    }

    /**
     * testInitialize method
     *
     * @return void
     */
    public function testInitialize()
    {
        Configure::write('App.namespace', 'TestApp');

        Plugin::load('TestPlugin');
        $this->Shell->tasks = ['DbConfig' => ['one', 'two']];
        $this->Shell->plugin = 'TestPlugin';
        $this->Shell->modelClass = 'TestPlugin.TestPluginComments';
        $this->Shell->initialize();
        $this->Shell->loadModel();

        $this->assertTrue(isset($this->Shell->TestPluginComments));
        $this->assertInstanceOf(
            'TestPlugin\Model\Table\TestPluginCommentsTable',
            $this->Shell->TestPluginComments
        );
    }

    /**
     * test LoadModel method
     *
     * @return void
     */
    public function testLoadModel()
    {
        Configure::write('App.namespace', 'TestApp');

        $Shell = new MergeShell();
        $this->assertInstanceOf(
            'TestApp\Model\Table\ArticlesTable',
            $Shell->Articles
        );
        $this->assertEquals('Articles', $Shell->modelClass);

        Plugin::load('TestPlugin');
        $result = $this->Shell->loadModel('TestPlugin.TestPluginComments');
        $this->assertInstanceOf(
            'TestPlugin\Model\Table\TestPluginCommentsTable',
            $result
        );
        $this->assertInstanceOf(
            'TestPlugin\Model\Table\TestPluginCommentsTable',
            $this->Shell->TestPluginComments
        );
    }

    /**
     * testIn method
     *
     * @return void
     */
    public function testIn()
    {
        $this->io->expects($this->at(0))
            ->method('askChoice')
            ->with('Just a test?', ['y', 'n'], 'n')
            ->will($this->returnValue('n'));

        $this->io->expects($this->at(1))
            ->method('ask')
            ->with('Just a test?', 'n')
            ->will($this->returnValue('n'));

        $result = $this->Shell->in('Just a test?', ['y', 'n'], 'n');
        $this->assertEquals('n', $result);

        $result = $this->Shell->in('Just a test?', null, 'n');
        $this->assertEquals('n', $result);
    }

    /**
     * Test in() when not interactive.
     *
     * @return void
     */
    public function testInNonInteractive()
    {
        $this->io->expects($this->never())
            ->method('askChoice');
        $this->io->expects($this->never())
            ->method('ask');

        $this->Shell->interactive = false;

        $result = $this->Shell->in('Just a test?', 'y/n', 'n');
        $this->assertEquals('n', $result);
    }

    /**
     * testVerbose method
     *
     * @return void
     */
    public function testVerbose()
    {
        $this->io->expects($this->once())
            ->method('verbose')
            ->with('Just a test', 1);

        $this->Shell->verbose('Just a test');
    }

    /**
     * testQuiet method
     *
     * @return void
     */
    public function testQuiet()
    {
        $this->io->expects($this->once())
            ->method('quiet')
            ->with('Just a test', 1);

        $this->Shell->quiet('Just a test');
    }

    /**
     * testOut method
     *
     * @return void
     */
    public function testOut()
    {
        $this->io->expects($this->once())
            ->method('out')
            ->with('Just a test', 1);

        $this->Shell->out('Just a test');
    }

    /**
     * testErr method
     *
     * @return void
     */
    public function testErr()
    {
        $this->io->expects($this->once())
            ->method('err')
            ->with('<error>Just a test</error>', 1);

        $this->Shell->err('Just a test');
    }

    /**
     * testInfo method
     *
     * @return void
     */
    public function testInfo()
    {
        $this->io->expects($this->once())
            ->method('out')
            ->with('<info>Just a test</info>', 1);

        $this->Shell->info('Just a test');
    }

    /**
     * testWarn method
     *
     * @return void
     */
    public function testWarn()
    {
        $this->io->expects($this->once())
            ->method('err')
            ->with('<warning>Just a test</warning>', 1);

        $this->Shell->warn('Just a test');
    }

    /**
     * testSuccess method
     *
     * @return void
     */
    public function testSuccess()
    {
        $this->io->expects($this->once())
            ->method('out')
            ->with('<success>Just a test</success>', 1);

        $this->Shell->success('Just a test');
    }

    /**
     * testNl
     *
     * @return void
     */
    public function testNl()
    {
        $this->io->expects($this->once())
            ->method('nl')
            ->with(2);

        $this->Shell->nl(2);
    }

    /**
     * testHr
     *
     * @return void
     */
    public function testHr()
    {
        $this->io->expects($this->once())
            ->method('hr')
            ->with(2);

        $this->Shell->hr(2);
    }

    /**
     * testError
     *
     * @return void
     */
    public function testError()
    {
        $this->io->expects($this->at(0))
            ->method('err')
            ->with('<error>Error:</error> Foo Not Found');

        $this->io->expects($this->at(1))
            ->method('err')
            ->with("Searched all...");

        $this->Shell->error('Foo Not Found', 'Searched all...');
        $this->assertSame($this->Shell->stopped, 1);
    }

    /**
     * testLoadTasks method
     *
     * @return void
     */
    public function testLoadTasks()
    {
        $this->assertTrue($this->Shell->loadTasks());

        $this->Shell->tasks = null;
        $this->assertTrue($this->Shell->loadTasks());

        $this->Shell->tasks = false;
        $this->assertTrue($this->Shell->loadTasks());

        $this->Shell->tasks = true;
        $this->assertTrue($this->Shell->loadTasks());

        $this->Shell->tasks = [];
        $this->assertTrue($this->Shell->loadTasks());

        $this->Shell->tasks = ['TestApple'];
        $this->assertTrue($this->Shell->loadTasks());
        $this->assertInstanceOf('Cake\Shell\Task\TestAppleTask', $this->Shell->TestApple);

        $this->Shell->tasks = 'TestBanana';
        $this->assertTrue($this->Shell->loadTasks());
        $this->assertInstanceOf('Cake\Shell\Task\TestAppleTask', $this->Shell->TestApple);
        $this->assertInstanceOf('Cake\Shell\Task\TestBananaTask', $this->Shell->TestBanana);

        unset($this->Shell->ShellTestApple, $this->Shell->TestBanana);

        $this->Shell->tasks = ['TestApple', 'TestBanana'];
        $this->assertTrue($this->Shell->loadTasks());
        $this->assertInstanceOf('Cake\Shell\Task\TestAppleTask', $this->Shell->TestApple);
        $this->assertInstanceOf('Cake\Shell\Task\TestBananaTask', $this->Shell->TestBanana);
    }

    /**
     * test that __get() makes args and params references
     *
     * @return void
     */
    public function testMagicGetArgAndParamReferences()
    {
        $this->Shell->tasks = ['TestApple'];
        $this->Shell->args = ['one'];
        $this->Shell->params = ['help' => false];
        $this->Shell->loadTasks();
        $result = $this->Shell->TestApple;

        $this->Shell->args = ['one', 'two'];

        $this->assertSame($this->Shell->args, $result->args);
        $this->assertSame($this->Shell->params, $result->params);
    }

    /**
     * testShortPath method
     *
     * @return void
     */
    public function testShortPath()
    {
        $path = $expected = DS . 'tmp/ab/cd';
        $this->assertPathEquals($expected, $this->Shell->shortPath($path));

        $path = $expected = DS . 'tmp/ab/cd/';
        $this->assertPathEquals($expected, $this->Shell->shortPath($path));

        $path = $expected = DS . 'tmp/ab/index.php';
        $this->assertPathEquals($expected, $this->Shell->shortPath($path));

        $path = DS . 'tmp/ab/' . DS . 'cd';
        $expected = DS . 'tmp/ab/cd';
        $this->assertPathEquals($expected, $this->Shell->shortPath($path));

        $path = 'tmp/ab';
        $expected = 'tmp/ab';
        $this->assertPathEquals($expected, $this->Shell->shortPath($path));

        $path = 'tmp/ab';
        $expected = 'tmp/ab';
        $this->assertPathEquals($expected, $this->Shell->shortPath($path));

        $path = APP;
        $result = $this->Shell->shortPath($path);
        $this->assertNotContains(ROOT, $result, 'Short paths should not contain ROOT');
    }

    /**
     * testCreateFile method
     *
     * @return void
     */
    public function testCreateFileNonInteractive()
    {
        $eol = PHP_EOL;
        $path = TMP . 'shell_test';
        $file = $path . DS . 'file1.php';

        new Folder($path, true);

        $contents = "<?php{$eol}echo 'test';${eol}\$te = 'st';{$eol}";
        $result = $this->Shell->createFile($file, $contents);
        $this->assertTrue($result);
        $this->assertTrue(file_exists($file));
        $this->assertEquals(file_get_contents($file), $contents);
    }

    /**
     * Test that files are not changed with a 'n' reply.
     *
     * @return void
     */
    public function testCreateFileNoReply()
    {
        $eol = PHP_EOL;
        $path = TMP . 'shell_test';
        $file = $path . DS . 'file1.php';

        new Folder($path, true);

        $this->io->expects($this->once())
            ->method('askChoice')
            ->will($this->returnValue('n'));

        touch($file);
        $this->assertTrue(file_exists($file));

        $contents = "My content";
        $result = $this->Shell->createFile($file, $contents);
        $this->assertTrue(file_exists($file));
        $this->assertTextEquals('', file_get_contents($file));
        $this->assertFalse($result, 'Did not create file.');
    }

    /**
     * Test that files are changed with a 'y' reply.
     *
     * @return void
     */
    public function testCreateFileOverwrite()
    {
        $eol = PHP_EOL;
        $path = TMP . 'shell_test';
        $file = $path . DS . 'file1.php';

        new Folder($path, true);

        $this->io->expects($this->once())
            ->method('askChoice')
            ->will($this->returnValue('y'));

        touch($file);
        $this->assertTrue(file_exists($file));

        $contents = "My content";
        $result = $this->Shell->createFile($file, $contents);
        $this->assertTrue(file_exists($file));
        $this->assertTextEquals($contents, file_get_contents($file));
        $this->assertTrue($result, 'Did create file.');
    }

    /**
     * Test that there is no user prompt in non-interactive mode while file already exists.
     *
     * @return void
     */
    public function testCreateFileOverwriteNonInteractive()
    {
        $path = TMP . 'shell_test';
        $file = $path . DS . 'file1.php';

        new Folder($path, true);

        touch($file);
        $this->assertTrue(file_exists($file));

        $this->io->expects($this->never())->method('askChoice');

        $this->Shell->interactive = false;
        $result = $this->Shell->createFile($file, 'My content');
        $this->assertTrue($result);
        $this->assertEquals(file_get_contents($file), 'My content');
    }

    /**
     * Test that all files are changed with a 'a' reply.
     *
     * @return void
     */
    public function testCreateFileOverwriteAll()
    {
        $eol = PHP_EOL;
        $path = TMP . 'shell_test';
        $files = [
            $path . DS . 'file1.php' => 'My first content',
            $path . DS . 'file2.php' => 'My second content',
            $path . DS . 'file3.php' => 'My third content'
        ];

        new Folder($path, true);

        $this->io->expects($this->once())
            ->method('askChoice')
            ->will($this->returnValue('a'));

        foreach ($files as $file => $contents) {
            touch($file);
            $this->assertTrue(file_exists($file));

            $result = $this->Shell->createFile($file, $contents);
            $this->assertTrue(file_exists($file));
            $this->assertTextEquals($contents, file_get_contents($file));
            $this->assertTrue($result, 'Did create file.');
        }
    }

    /**
     * Test that you can't create files that aren't writable.
     *
     * @return void
     */
    public function testCreateFileNoPermissions()
    {
        $this->skipIf(DS === '\\', 'Cant perform operations using permissions on windows.');

        $path = TMP . 'shell_test';
        $file = $path . DS . 'no_perms';

        if (!is_dir($path)) {
            mkdir($path);
        }
        chmod($path, 0444);

        $this->Shell->createFile($file, 'testing');
        $this->assertFalse(file_exists($file));

        chmod($path, 0744);
        rmdir($path);
    }

    /**
     * test hasTask method
     *
     * @return void
     */
    public function testHasTask()
    {
        $this->Shell->tasks = ['Extract', 'DbConfig'];
        $this->Shell->loadTasks();

        $this->assertTrue($this->Shell->hasTask('extract'));
        $this->assertTrue($this->Shell->hasTask('Extract'));
        $this->assertFalse($this->Shell->hasTask('random'));

        $this->assertTrue($this->Shell->hasTask('db_config'));
        $this->assertTrue($this->Shell->hasTask('DbConfig'));
    }

    /**
     * test the hasMethod
     *
     * @return void
     */
    public function testHasMethod()
    {
        $this->assertTrue($this->Shell->hasMethod('doSomething'));
        $this->assertFalse($this->Shell->hasMethod('hr'), 'hr is callable');
        $this->assertFalse($this->Shell->hasMethod('_secret'), '_secret is callable');
        $this->assertFalse($this->Shell->hasMethod('no_access'), 'no_access is callable');
    }

    /**
     * test run command calling main.
     *
     * @return void
     */
    public function testRunCommandMain()
    {
        $io = $this->getMockBuilder('Cake\Console\ConsoleIo')->getMock();
        $shell = $this->getMockBuilder('Cake\Console\Shell')
            ->setMethods(['main', 'startup'])
            ->setConstructorArgs([$io])
            ->getMock();

        $shell->expects($this->once())->method('startup');
        $shell->expects($this->once())->method('main')
            ->with('cakes')
            ->will($this->returnValue(true));
        $result = $shell->runCommand(['cakes', '--verbose']);
        $this->assertTrue($result);
        $this->assertEquals('main', $shell->command);
    }

    /**
     * test run command calling a real method with no subcommands defined.
     *
     * @return void
     */
    public function testRunCommandWithMethod()
    {
        $io = $this->getMockBuilder('Cake\Console\ConsoleIo')->getMock();
        $shell = $this->getMockBuilder('Cake\Console\Shell')
            ->setMethods(['hitMe', 'startup'])
            ->setConstructorArgs([$io])
            ->getMock();

        $shell->expects($this->once())->method('startup');
        $shell->expects($this->once())->method('hitMe')
            ->with('cakes')
            ->will($this->returnValue(true));
        $result = $shell->runCommand(['hit_me', 'cakes', '--verbose'], true);
        $this->assertTrue($result);
        $this->assertEquals('hit_me', $shell->command);
    }

    /**
     * test that a command called with an extra parameter passed merges the extra parameters
     * to the shell's one
     * Also tests that if an extra `requested` parameter prevents the welcome message from
     * being displayed
     *
     * @return void
     */
    public function testRunCommandWithExtra()
    {
        $Parser = $this->getMockBuilder('Cake\Console\ConsoleOptionParser')
            ->setMethods(['help'])
            ->setConstructorArgs(['knife'])
            ->getMock();
        $io = $this->getMockBuilder('Cake\Console\ConsoleIo')->getMock();
        $Shell = $this->getMockBuilder('Cake\Console\Shell')
            ->setMethods(['getOptionParser', 'slice', '_welcome', 'param'])
            ->setConstructorArgs([$io])
            ->getMock();
        $Parser->addSubCommand('slice');
        $Shell->expects($this->once())
            ->method('getOptionParser')
            ->will($this->returnValue($Parser));
        $Shell->expects($this->once())
            ->method('slice')
            ->with('cakes');
        $Shell->expects($this->never())->method('_welcome');
        $Shell->expects($this->once())->method('param')
            ->with('requested')
            ->will($this->returnValue(true));
        $Shell->runCommand(['slice', 'cakes'], false, ['requested' => true]);
    }

    /**
     * Test the dispatchShell() arguments parser
     *
     * @return void
     */
    public function testDispatchShellArgsParser()
    {
        $Shell = new Shell();

        $expected = [['schema', 'create', 'DbAcl'], []];
        // Shell::dispatchShell('schema create DbAcl');
        $result = $Shell->parseDispatchArguments(['schema create DbAcl']);
        $this->assertEquals($expected, $result);

        // Shell::dispatchShell('schema', 'create', 'DbAcl');
        $result = $Shell->parseDispatchArguments(['schema', 'create', 'DbAcl']);
        $this->assertEquals($expected, $result);

        // Shell::dispatchShell(['command' => 'schema create DbAcl']);
        $result = $Shell->parseDispatchArguments([[
            'command' => 'schema create DbAcl'
        ]]);
        $this->assertEquals($expected, $result);

        // Shell::dispatchShell(['command' => ['schema', 'create', 'DbAcl']]);
        $result = $Shell->parseDispatchArguments([[
            'command' => ['schema', 'create', 'DbAcl']
        ]]);
        $this->assertEquals($expected, $result);

        $expected[1] = ['param' => 'value'];
        // Shell::dispatchShell(['command' => 'schema create DbAcl', 'extra' => ['param' => 'value']]);
        $result = $Shell->parseDispatchArguments([[
            'command' => 'schema create DbAcl',
            'extra' => ['param' => 'value']
        ]]);
        $this->assertEquals($expected, $result);

        // Shell::dispatchShell(['command' => ['schema', 'create', 'DbAcl'], 'extra' => ['param' => 'value']]);
        $result = $Shell->parseDispatchArguments([[
            'command' => ['schema', 'create', 'DbAcl'],
            'extra' => ['param' => 'value']
        ]]);
        $this->assertEquals($expected, $result);
    }

    /**
     * test calling a shell that dispatch another one
     *
     * @return void
     */
    public function testDispatchShell()
    {
        $Shell = new TestingDispatchShell();
        ob_start();
        $Shell->runCommand(['test_task'], true);
        $result = ob_get_clean();

        $expected = <<<TEXT
<info>Welcome to CakePHP Console</info>
I am a test task, I dispatch another Shell
I am a dispatched Shell

TEXT;
        $this->assertEquals($expected, $result);

        ob_start();
        $Shell->runCommand(['test_task_dispatch_array'], true);
        $result = ob_get_clean();
        $this->assertEquals($expected, $result);

        ob_start();
        $Shell->runCommand(['test_task_dispatch_command_string'], true);
        $result = ob_get_clean();
        $this->assertEquals($expected, $result);

        ob_start();
        $Shell->runCommand(['test_task_dispatch_command_array'], true);
        $result = ob_get_clean();
        $this->assertEquals($expected, $result);

        $expected = <<<TEXT
<info>Welcome to CakePHP Console</info>
I am a test task, I dispatch another Shell
I am a dispatched Shell. My param `foo` has the value `bar`

TEXT;

        ob_start();
        $Shell->runCommand(['test_task_dispatch_with_param'], true);
        $result = ob_get_clean();
        $this->assertEquals($expected, $result);

        $expected = <<<TEXT
<info>Welcome to CakePHP Console</info>
I am a test task, I dispatch another Shell
I am a dispatched Shell. My param `foo` has the value `bar`
My param `fooz` has the value `baz`

TEXT;
        ob_start();
        $Shell->runCommand(['test_task_dispatch_with_multiple_params'], true);
        $result = ob_get_clean();
        $this->assertEquals($expected, $result);

        $expected = <<<TEXT
<info>Welcome to CakePHP Console</info>
I am a test task, I dispatch another Shell
<info>Welcome to CakePHP Console</info>
I am a dispatched Shell

TEXT;
        ob_start();
        $Shell->runCommand(['test_task_dispatch_with_requested_off'], true);
        $result = ob_get_clean();
        $this->assertEquals($expected, $result);
    }

    /**
     * Test that runCommand() doesn't call public methods when the second arg is false.
     *
     * @return void
     */
    public function testRunCommandAutoMethodOff()
    {
        $io = $this->getMockBuilder('Cake\Console\ConsoleIo')->getMock();
        $shell = $this->getMockBuilder('Cake\Console\Shell')
            ->setMethods(['hit_me', 'startup'])
            ->setConstructorArgs([$io])
            ->getMock();

        $shell->expects($this->never())->method('startup');
        $shell->expects($this->never())->method('hit_me');

        $result = $shell->runCommand(['hit_me', 'baseball'], false);
        $this->assertFalse($result);

        $result = $shell->runCommand(['hit_me', 'baseball']);
        $this->assertFalse($result, 'Default value of runCommand() should be false');
    }

    /**
     * test run command calling a real method with mismatching subcommands defined.
     *
     * @return void
     */
    public function testRunCommandWithMethodNotInSubcommands()
    {
        $parser = $this->getMockBuilder('Cake\Console\ConsoleOptionParser')
            ->setMethods(['help'])
            ->setConstructorArgs(['knife'])
            ->getMock();
        $io = $this->getMockBuilder('Cake\Console\ConsoleIo')->getMock();
        $shell = $this->getMockBuilder('Cake\Console\Shell')
            ->setMethods(['getOptionParser', 'roll', 'startup'])
            ->setConstructorArgs([$io])
            ->getMock();

        $parser->addSubCommand('slice');

        $shell->expects($this->any())
            ->method('getOptionParser')
            ->will($this->returnValue($parser));

        $parser->expects($this->once())
            ->method('help');

        $shell->expects($this->never())->method('startup');
        $shell->expects($this->never())->method('roll');

        $result = $shell->runCommand(['roll', 'cakes', '--verbose']);
        $this->assertFalse($result);
    }

    /**
     * test run command calling a real method with subcommands defined.
     *
     * @return void
     */
    public function testRunCommandWithMethodInSubcommands()
    {
        $parser = $this->getMockBuilder('Cake\Console\ConsoleOptionParser')
            ->setMethods(['help'])
            ->setConstructorArgs(['knife'])
            ->getMock();
        $io = $this->getMockBuilder('Cake\Console\ConsoleIo')->getMock();
        $shell = $this->getMockBuilder('Cake\Console\Shell')
            ->setMethods(['getOptionParser', 'slice', 'startup'])
            ->setConstructorArgs([$io])
            ->getMock();

        $parser->addSubCommand('slice');

        $shell->expects($this->any())
            ->method('getOptionParser')
            ->will($this->returnValue($parser));

        $shell->expects($this->once())->method('startup');
        $shell->expects($this->once())
            ->method('slice')
            ->with('cakes');

        $shell->runCommand(['slice', 'cakes', '--verbose']);
    }

    /**
     * test run command calling a missing method with subcommands defined.
     *
     * @return void
     */
    public function testRunCommandWithMissingMethodInSubcommands()
    {
        $parser = $this->getMockBuilder('Cake\Console\ConsoleOptionParser')
            ->setMethods(['help'])
            ->setConstructorArgs(['knife'])
            ->getMock();
        $parser->addSubCommand('slice');

        $io = $this->getMockBuilder('Cake\Console\ConsoleIo')->getMock();
        $shell = $this->getMockBuilder('Cake\Console\Shell')
            ->setMethods(['getOptionParser', 'startup'])
            ->setConstructorArgs([$io])
            ->getMock();
        $shell->expects($this->any())
            ->method('getOptionParser')
            ->will($this->returnValue($parser));

        $shell->expects($this->never())
            ->method('startup');

        $parser->expects($this->once())
            ->method('help');

        $shell->runCommand(['slice', 'cakes', '--verbose']);
    }

    /**
     * test run command causing exception on Shell method.
     *
     * @return void
     */
    public function testRunCommandBaseClassMethod()
    {
        $shell = $this->getMockBuilder('Cake\Console\Shell')
            ->setMethods(['startup', 'getOptionParser', 'out', 'hr'])
            ->disableOriginalConstructor()
            ->getMock();

        $shell->io($this->getMockBuilder('Cake\Console\ConsoleIo')->getMock());
        $parser = $this->getMockBuilder('Cake\Console\ConsoleOptionParser')
            ->disableOriginalConstructor()
            ->getMock();

        $parser->expects($this->once())->method('help');
        $shell->expects($this->once())->method('getOptionParser')
            ->will($this->returnValue($parser));
        $shell->expects($this->never())->method('hr');
        $shell->expects($this->once())->method('out');

        $shell->runCommand(['hr']);
    }

    /**
     * test run command causing exception on Shell method.
     *
     * @return void
     */
    public function testRunCommandMissingMethod()
    {
        $shell = $this->getMockBuilder('Cake\Console\Shell')
            ->setMethods(['startup', 'getOptionParser', 'out', 'hr'])
            ->disableOriginalConstructor()
            ->getMock();
        $shell->io($this->getMockBuilder('Cake\Console\ConsoleIo')->getMock());
        $parser = $this->getMockBuilder('Cake\Console\ConsoleOptionParser')
            ->disableOriginalConstructor()
            ->getMock();

        $parser->expects($this->once())->method('help');
        $shell->expects($this->once())->method('getOptionParser')
            ->will($this->returnValue($parser));
        $shell->expects($this->once())->method('out');

        $result = $shell->runCommand(['idontexist']);
        $this->assertFalse($result);
    }

    /**
     * test that a --help causes help to show.
     *
     * @return void
     */
    public function testRunCommandTriggeringHelp()
    {
        $parser = $this->getMockBuilder('Cake\Console\ConsoleOptionParser')
            ->disableOriginalConstructor()
            ->getMock();
        $parser->expects($this->once())->method('parse')
            ->with(['--help'])
            ->will($this->returnValue([['help' => true], []]));
        $parser->expects($this->once())->method('help');

        $shell = $this->getMockBuilder('Cake\Console\Shell')
            ->setMethods(['getOptionParser', 'out', 'startup', '_welcome'])
            ->disableOriginalConstructor()
            ->getMock();
        $shell->io($this->getMockBuilder('Cake\Console\ConsoleIo')->getMock());
        $shell->expects($this->once())->method('getOptionParser')
            ->will($this->returnValue($parser));
        $shell->expects($this->once())->method('out');

        $shell->runCommand(['--help']);
    }

    /**
     * test that runCommand will not call runCommand on tasks that are not subcommands.
     *
     * @return void
     */
    public function testRunCommandNotCallUnexposedTask()
    {
        $shell = $this->getMockBuilder('Cake\Console\Shell')
            ->setMethods(['startup', 'hasTask', 'out'])
            ->disableOriginalConstructor()
            ->getMock();
        $shell->io($this->getMockBuilder('Cake\Console\ConsoleIo')->getMock());
        $task = $this->getMockBuilder('Cake\Console\Shell')
            ->setMethods(['runCommand'])
            ->disableOriginalConstructor()
            ->getMock();

        $task->expects($this->never())
            ->method('runCommand');

        $shell->expects($this->any())
            ->method('hasTask')
            ->will($this->returnValue(true));
        $shell->expects($this->never())->method('startup');
        $shell->expects($this->once())->method('out');
        $shell->RunCommand = $task;

        $result = $shell->runCommand(['run_command', 'one']);
        $this->assertFalse($result);
    }

    /**
     * test that runCommand will call runCommand on the task.
     *
     * @return void
     */
    public function testRunCommandHittingTaskInSubcommand()
    {
        $parser = new ConsoleOptionParser('knife');
        $parser->addSubcommand('slice');
        $io = $this->getMockBuilder('Cake\Console\ConsoleIo')->getMock();

        $shell = $this->getMockBuilder('Cake\Console\Shell')
            ->setMethods(['hasTask', 'startup', 'getOptionParser'])
            ->disableOriginalConstructor()
            ->getMock();
        $shell->io($io);
        $task = $this->getMockBuilder('Cake\Console\Shell')
            ->setMethods(['main', 'runCommand'])
            ->disableOriginalConstructor()
            ->getMock();
        $task->io($io);
        $task->expects($this->once())
            ->method('runCommand')
            ->with(['one'], false, ['requested' => true]);

        $shell->expects($this->once())->method('getOptionParser')
            ->will($this->returnValue($parser));

        $shell->expects($this->once())->method('startup');
        $shell->expects($this->any())
            ->method('hasTask')
            ->will($this->returnValue(true));

        $shell->Slice = $task;
        $shell->runCommand(['slice', 'one']);
    }

    /**
     * test that runCommand will invoke a task
     *
     * @return void
     */
    public function testRunCommandInvokeTask()
    {
        $parser = new ConsoleOptionParser('knife');
        $parser->addSubcommand('slice');
        $io = $this->getMockBuilder('Cake\Console\ConsoleIo')->getMock();

        $shell = $this->getMockBuilder('Cake\Console\Shell')
            ->setMethods(['hasTask', 'getOptionParser'])
            ->setConstructorArgs([$io])
            ->getMock();
        $task = $this->getMockBuilder('Cake\Console\Shell')
            ->setMethods(['main', '_welcome'])
            ->setConstructorArgs([$io])
            ->getMock();

        $shell->expects($this->once())
            ->method('getOptionParser')
            ->will($this->returnValue($parser));

        $shell->expects($this->any())
            ->method('hasTask')
            ->will($this->returnValue(true));

        $task->expects($this->never())
            ->method('_welcome');

        // One welcome message output.
        $io->expects($this->at(2))
            ->method('out')
            ->with($this->stringContains('Welcome to CakePHP'));

        $shell->Slice = $task;
        $shell->runCommand(['slice', 'one']);
        $this->assertTrue($task->params['requested'], 'Task is requested, no welcome.');
    }

    /**
     * test run command missing parameters
     *
     * @return void
     */
    public function testRunCommandMainMissingArgument()
    {
        $io = $this->getMockBuilder('Cake\Console\ConsoleIo')->getMock();
        $shell = $this->getMockBuilder('Cake\Console\Shell')
            ->setMethods(['main', 'startup', 'getOptionParser'])
            ->setConstructorArgs([$io])
            ->getMock();

        $parser = new ConsoleOptionParser('test');
        $parser->addArgument('filename', [
            'required' => true,
            'help' => 'a file',
        ]);
        $shell->expects($this->once())
            ->method('getOptionParser')
            ->will($this->returnValue($parser));
        $shell->expects($this->never())->method('main');

        $io->expects($this->once())
            ->method('err')
            ->with('<error>Error: Missing required arguments. filename is required.</error>');
        $result = $shell->runCommand([]);
        $this->assertFalse($result, 'Shell should fail');
    }

    /**
     * test wrapBlock wrapping text.
     *
     * @return void
     */
    public function testWrapText()
    {
        $text = 'This is the song that never ends. This is the song that never ends. This is the song that never ends.';
        $result = $this->Shell->wrapText($text, ['width' => 33]);
        $expected = <<<TEXT
This is the song that never ends.
This is the song that never ends.
This is the song that never ends.
TEXT;
        $this->assertTextEquals($expected, $result, 'Text not wrapped.');

        $result = $this->Shell->wrapText($text, ['indent' => '  ', 'width' => 33]);
        $expected = <<<TEXT
  This is the song that never ends.
  This is the song that never ends.
  This is the song that never ends.
TEXT;
        $this->assertTextEquals($expected, $result, 'Text not wrapped.');
    }

    /**
     * Testing camel cased naming of tasks
     *
     * @return void
     */
    public function testShellNaming()
    {
        $this->Shell->tasks = ['TestApple'];
        $this->Shell->loadTasks();
        $expected = 'TestApple';
        $this->assertEquals($expected, $this->Shell->TestApple->name);
    }

    /**
     * Test reading params
     *
     * @dataProvider paramReadingDataProvider
     */
    public function testParamReading($toRead, $expected)
    {
        $this->Shell->params = [
            'key' => 'value',
            'help' => false,
            'emptykey' => '',
            'truthy' => true
        ];
        $this->assertSame($expected, $this->Shell->param($toRead));
    }

    /**
     * Data provider for testing reading values with Shell::param()
     *
     * @return array
     */
    public function paramReadingDataProvider()
    {
        return [
            [
                'key',
                'value',
            ],
            [
                'help',
                false,
            ],
            [
                'emptykey',
                '',
            ],
            [
                'truthy',
                true,
            ],
            [
                'does_not_exist',
                null,
            ]
        ];
    }

    /**
     * Test that option parsers are created with the correct name/command.
     *
     * @return void
     */
    public function testGetOptionParser()
    {
        $this->Shell->name = 'test';
        $this->Shell->plugin = 'plugin';
        $parser = $this->Shell->getOptionParser();

        $this->assertEquals('plugin.test', $parser->command());
    }

    /**
     * Test file and console and logging quiet output
     *
     * @return void
     */
    public function testQuietLog()
    {
        $io = $this->getMockBuilder('Cake\Console\ConsoleIo')
            ->disableOriginalConstructor()
            ->getMock();
        $io->expects($this->once())
            ->method('level')
            ->with(Shell::QUIET);
        $io->expects($this->at(0))
            ->method('setLoggers')
            ->with(true);
        $io->expects($this->at(2))
            ->method('setLoggers')
            ->with(ConsoleIo::QUIET);

        $this->Shell = $this->getMockBuilder(__NAMESPACE__ . '\ShellTestShell')
            ->setMethods(['welcome'])
            ->setConstructorArgs([$io])
            ->getMock();
        $this->Shell->runCommand(['foo', '--quiet']);
    }

    /**
     * Tests __debugInfo
     *
     * @return void
     */
    public function testDebugInfo()
    {
        $expected = [
            'name' => 'ShellTestShell',
            'plugin' => null,
            'command' => null,
            'tasks' => [],
            'params' => [],
            'args' => [],
            'interactive' => true
        ];
        $result = $this->Shell->__debugInfo();
        $this->assertEquals($expected, $result);
    }
}
