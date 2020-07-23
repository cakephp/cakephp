<?php
declare(strict_types=1);

/**
 * CakePHP :  Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP Project
 * @since         1.2.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Console;

use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use Cake\Console\Exception\StopException;
use Cake\Console\Shell;
use Cake\Filesystem\Filesystem;
use Cake\TestSuite\TestCase;
use RuntimeException;
use TestApp\Shell\MergeShell;
use TestApp\Shell\ShellTestShell;
use TestApp\Shell\Task\TestAppleTask;
use TestApp\Shell\Task\TestBananaTask;
use TestApp\Shell\TestingDispatchShell;
use TestPlugin\Model\Table\TestPluginCommentsTable;

class_alias(TestAppleTask::class, 'Cake\Shell\Task\TestAppleTask');
class_alias(TestBananaTask::class, 'Cake\Shell\Task\TestBananaTask');

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
    protected $fixtures = [
        'core.Articles',
        'core.ArticlesTags',
        'core.Attachments',
        'core.Comments',
        'core.Posts',
        'core.Tags',
        'core.Users',
    ];

    /**
     * @var \Cake\Console\Shell
     */
    protected $Shell;

    /**
     * @var \Cake\Console\ConsoleIo|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $io;

    /**
     * @var \Cake\Filesystem\Filesystem
     */
    protected $fs;

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->io = $this->getMockBuilder('Cake\Console\ConsoleIo')
            ->disableOriginalConstructor()
            ->getMock();
        $this->Shell = new ShellTestShell($this->io, $this->getTableLocator());

        $this->fs = new Filesystem();

        if (is_dir(TMP . 'shell_test')) {
            $this->fs->deleteDir(TMP . 'shell_test');
        }
    }

    /**
     * testConstruct method
     *
     * @return void
     */
    public function testConstruct()
    {
        $this->assertSame('ShellTestShell', $this->Shell->name);
        $this->assertInstanceOf(ConsoleIo::class, $this->Shell->getIo());
    }

    /**
     * testInitialize method
     *
     * @return void
     */
    public function testInitialize()
    {
        static::setAppNamespace();

        $this->loadPlugins(['TestPlugin']);
        $this->Shell->tasks = ['Sample' => ['one', 'two']];
        $this->Shell->plugin = 'TestPlugin';
        $this->Shell->initialize();
        // TestApp\Shell\ShellTestShell has $modelClass property set to 'TestPlugin.TestPluginComments'
        $this->Shell->loadModel();

        $this->assertTrue(isset($this->Shell->TestPluginComments));
        $this->assertInstanceOf(
            TestPluginCommentsTable::class,
            $this->Shell->TestPluginComments
        );
        $this->clearPlugins();
    }

    /**
     * test LoadModel method
     *
     * @return void
     */
    public function testLoadModel()
    {
        static::setAppNamespace();

        $Shell = new MergeShell();
        $this->assertInstanceOf(
            'TestApp\Model\Table\ArticlesTable',
            $Shell->Articles
        );
        $this->assertSame('Articles', $Shell->modelClass);

        $this->loadPlugins(['TestPlugin']);
        $result = $this->Shell->loadModel('TestPlugin.TestPluginComments');
        $this->assertInstanceOf(
            TestPluginCommentsTable::class,
            $result
        );
        $this->assertInstanceOf(
            TestPluginCommentsTable::class,
            $this->Shell->TestPluginComments
        );
        $this->clearPlugins();
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
        $this->assertSame('n', $result);

        $result = $this->Shell->in('Just a test?', null, 'n');
        $this->assertSame('n', $result);
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
        $this->assertSame('n', $result);
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
            ->method('error')
            ->with('Just a test', 1);

        $this->Shell->err('Just a test');
    }

    /**
     * testErr method with array
     *
     * @return void
     */
    public function testErrArray()
    {
        $this->io->expects($this->once())
            ->method('error')
            ->with(['Just', 'a', 'test'], 1);

        $this->Shell->err(['Just', 'a', 'test']);
    }

    /**
     * testInfo method
     *
     * @return void
     */
    public function testInfo()
    {
        $this->io->expects($this->once())
            ->method('info')
            ->with('Just a test', 1);

        $this->Shell->info('Just a test');
    }

    /**
     * testInfo method with array
     *
     * @return void
     */
    public function testInfoArray()
    {
        $this->io->expects($this->once())
            ->method('info')
            ->with(['Just', 'a', 'test'], 1);

        $this->Shell->info(['Just', 'a', 'test']);
    }

    /**
     * testWarn method
     *
     * @return void
     */
    public function testWarn()
    {
        $this->io->expects($this->once())
            ->method('warning')
            ->with('Just a test', 1);

        $this->Shell->warn('Just a test');
    }

    /**
     * testWarn method with array
     *
     * @return void
     */
    public function testWarnArray()
    {
        $this->io->expects($this->once())
            ->method('warning')
            ->with(['Just', 'a', 'test'], 1);

        $this->Shell->warn(['Just', 'a', 'test']);
    }

    /**
     * testSuccess method
     *
     * @return void
     */
    public function testSuccess()
    {
        $this->io->expects($this->once())
            ->method('success')
            ->with('Just a test', 1);

        $this->Shell->success('Just a test');
    }

    /**
     * testSuccess method with array
     *
     * @return void
     */
    public function testSuccessArray()
    {
        $this->io->expects($this->once())
            ->method('success')
            ->with(['Just', 'a', 'test'], 1);

        $this->Shell->success(['Just', 'a', 'test']);
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
     * testAbort
     *
     * @return void
     */
    public function testAbort()
    {
        $this->expectException(StopException::class);
        $this->expectExceptionMessage('Foo Not Found');
        $this->expectExceptionCode(1);

        $this->io->expects($this->at(0))
            ->method('err')
            ->with('<error>Foo Not Found</error>');

        $this->Shell->abort('Foo Not Found');
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
        $this->assertStringNotContainsString(ROOT, $result, 'Short paths should not contain ROOT');
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

        $this->fs->mkdir($path);

        $contents = "<?php{$eol}echo 'test';${eol}\$te = 'st';{$eol}";

        $this->Shell->interactive = false;
        $result = $this->Shell->createFile($file, $contents);
        $this->assertTrue($result);
        $this->assertFileExists($file);
        $this->assertStringEqualsFile($file, $contents);
    }

    /**
     * Test that while in non interactive mode it will not overwrite files by default.
     *
     * @return void
     */
    public function testCreateFileNonInteractiveFileExists()
    {
        $eol = PHP_EOL;
        $path = TMP . 'shell_test';
        $file = $path . DS . 'file1.php';
        if (!is_dir($path)) {
            mkdir($path, 0770, true);
        }
        touch($file);
        $this->assertFileExists($file);

        $this->fs->mkdir($path);

        $contents = "<?php{$eol}echo 'test';${eol}\$te = 'st';{$eol}";
        $this->Shell->interactive = false;
        $result = $this->Shell->createFile($file, $contents);
        $this->assertFalse($result);
    }

    /**
     * Test that files are not changed with a 'n' reply.
     *
     * @return void
     */
    public function testCreateFileNoReply()
    {
        $path = TMP . 'shell_test';
        $file = $path . DS . 'file1.php';

        $this->fs->mkdir($path);

        $this->io->expects($this->once())
            ->method('askChoice')
            ->will($this->returnValue('n'));

        touch($file);
        $this->assertFileExists($file);

        $contents = 'My content';
        $result = $this->Shell->createFile($file, $contents);
        $this->assertFileExists($file);
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
        $path = TMP . 'shell_test';
        $file = $path . DS . 'file1.php';

        $this->fs->mkdir($path);

        $this->io->expects($this->once())
            ->method('askChoice')
            ->will($this->returnValue('y'));

        touch($file);
        $this->assertFileExists($file);

        $contents = 'My content';
        $result = $this->Shell->createFile($file, $contents);
        $this->assertFileExists($file);
        $this->assertTextEquals($contents, file_get_contents($file));
        $this->assertTrue($result, 'Did create file.');
    }

    /**
     * Test that there is no user prompt in non-interactive mode while file already exists
     * and if force mode is explicitly enabled.
     *
     * @return void
     */
    public function testCreateFileOverwriteNonInteractive()
    {
        $path = TMP . 'shell_test';
        $file = $path . DS . 'file1.php';

        $this->fs->mkdir($path);

        touch($file);
        $this->assertFileExists($file);

        $this->io->expects($this->never())->method('askChoice');

        $this->Shell->params['force'] = true;
        $this->Shell->interactive = false;
        $result = $this->Shell->createFile($file, 'My content');
        $this->assertTrue($result);
        $this->assertStringEqualsFile($file, 'My content');
    }

    /**
     * Test that all files are changed with a 'a' reply.
     *
     * @return void
     */
    public function testCreateFileOverwriteAll()
    {
        $path = TMP . 'shell_test';
        $files = [
            $path . DS . 'file1.php' => 'My first content',
            $path . DS . 'file2.php' => 'My second content',
            $path . DS . 'file3.php' => 'My third content',
        ];

        $this->fs->mkdir($path);

        $this->io->expects($this->once())
            ->method('askChoice')
            ->will($this->returnValue('a'));

        foreach ($files as $file => $contents) {
            touch($file);
            $this->assertFileExists($file);

            $result = $this->Shell->createFile($file, $contents);
            $this->assertFileExists($file);
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
        $this->assertFileNotExists($file);

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
        $this->setAppNamespace();
        $this->Shell->tasks = ['Sample', 'TestApple'];
        $this->Shell->loadTasks();

        $this->assertTrue($this->Shell->hasTask('sample'));
        $this->assertTrue($this->Shell->hasTask('Sample'));
        $this->assertFalse($this->Shell->hasTask('random'));

        $this->assertTrue($this->Shell->hasTask('testApple'));
        $this->assertTrue($this->Shell->hasTask('TestApple'));
    }

    /**
     * test task loading exception
     *
     * @return void
     */
    public function testMissingTaskException()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Task `DoesNotExist` not found. Maybe you made a typo or a plugin is missing or not loaded?');

        $this->Shell->tasks = ['DoesNotExist'];
        $this->Shell->loadTasks();
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
        /** @var \Cake\Console\Shell|\PHPUnit\Framework\MockObject\MockObject $shell */
        $shell = $this->getMockBuilder('Cake\Console\Shell')
            ->onlyMethods(['startup'])
            ->addMethods(['main'])
            ->setConstructorArgs([$io])
            ->getMock();

        $shell->expects($this->once())->method('startup');
        $shell->expects($this->once())->method('main')
            ->with('cakes')
            ->will($this->returnValue(true));
        $result = $shell->runCommand(['cakes', '--verbose']);
        $this->assertTrue($result);
        $this->assertSame('main', $shell->command);
    }

    /**
     * test run command calling a real method with no subcommands defined.
     *
     * @return void
     */
    public function testRunCommandWithMethod()
    {
        $io = $this->getMockBuilder('Cake\Console\ConsoleIo')->getMock();
        /** @var \Cake\Console\Shell|\PHPUnit\Framework\MockObject\MockObject $shell */
        $shell = $this->getMockBuilder('Cake\Console\Shell')
            ->onlyMethods(['startup'])
            ->addMethods(['hitMe'])
            ->setConstructorArgs([$io])
            ->getMock();

        $shell->expects($this->once())->method('startup');
        $shell->expects($this->once())->method('hitMe')
            ->with('cakes')
            ->will($this->returnValue(true));
        $result = $shell->runCommand(['hit_me', 'cakes', '--verbose'], true);
        $this->assertTrue($result);
        $this->assertSame('hit_me', $shell->command);
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
            ->onlyMethods(['help'])
            ->setConstructorArgs(['knife'])
            ->getMock();
        $io = $this->getMockBuilder('Cake\Console\ConsoleIo')->getMock();
        $Shell = $this->getMockBuilder('Cake\Console\Shell')
            ->onlyMethods(['getOptionParser', '_welcome', 'param'])
            ->addMethods(['slice'])
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
            'command' => 'schema create DbAcl',
        ]]);
        $this->assertEquals($expected, $result);

        // Shell::dispatchShell(['command' => ['schema', 'create', 'DbAcl']]);
        $result = $Shell->parseDispatchArguments([[
            'command' => ['schema', 'create', 'DbAcl'],
        ]]);
        $this->assertEquals($expected, $result);

        $expected[1] = ['param' => 'value'];
        // Shell::dispatchShell(['command' => 'schema create DbAcl', 'extra' => ['param' => 'value']]);
        $result = $Shell->parseDispatchArguments([[
            'command' => 'schema create DbAcl',
            'extra' => ['param' => 'value'],
        ]]);
        $this->assertEquals($expected, $result);

        // Shell::dispatchShell(['command' => ['schema', 'create', 'DbAcl'], 'extra' => ['param' => 'value']]);
        $result = $Shell->parseDispatchArguments([[
            'command' => ['schema', 'create', 'DbAcl'],
            'extra' => ['param' => 'value'],
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
        /** @var \Cake\Console\Shell|\PHPUnit\Framework\MockObject\MockObject $shell */
        $shell = $this->getMockBuilder('Cake\Console\Shell')
            ->onlyMethods(['startup'])
            ->addMethods(['hit_me'])
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
            ->onlyMethods(['help'])
            ->setConstructorArgs(['knife'])
            ->getMock();
        $io = $this->getMockBuilder('Cake\Console\ConsoleIo')->getMock();
        $shell = $this->getMockBuilder('Cake\Console\Shell')
            ->onlyMethods(['getOptionParser', 'startup'])
            ->addMethods(['roll'])
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
            ->onlyMethods(['help'])
            ->setConstructorArgs(['knife'])
            ->getMock();
        $io = $this->getMockBuilder('Cake\Console\ConsoleIo')->getMock();
        $shell = $this->getMockBuilder('Cake\Console\Shell')
            ->onlyMethods(['getOptionParser', 'startup'])
            ->addMethods(['slice'])
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
        /** @var \Cake\Console\ConsoleOptionParser|\PHPUnit\Framework\MockObject\MockObject $parser */
        $parser = $this->getMockBuilder('Cake\Console\ConsoleOptionParser')
            ->onlyMethods(['help'])
            ->setConstructorArgs(['knife'])
            ->getMock();
        $parser->addSubCommand('slice');

        $io = $this->getMockBuilder('Cake\Console\ConsoleIo')->getMock();
        /** @var \Cake\Console\Shell|\PHPUnit\Framework\MockObject\MockObject $shell */
        $shell = $this->getMockBuilder('Cake\Console\Shell')
            ->onlyMethods(['getOptionParser', 'startup'])
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
        /** @var \Cake\Console\Shell|\PHPUnit\Framework\MockObject\MockObject $shell */
        $shell = $this->getMockBuilder('Cake\Console\Shell')
            ->onlyMethods(['startup', 'getOptionParser', 'hr'])
            ->disableOriginalConstructor()
            ->getMock();
        $shell->setIo(
            $this->getMockBuilder('Cake\Console\ConsoleIo')
            ->onlyMethods(['err'])
            ->getMock()
        );
        $parser = $this->getMockBuilder('Cake\Console\ConsoleOptionParser')
            ->disableOriginalConstructor()
            ->getMock();
        $parser->expects($this->once())->method('help');
        $parser->method('parse')
            ->will($this->returnValue([[], []]));

        $shell->expects($this->once())->method('getOptionParser')
            ->will($this->returnValue($parser));
        $shell->expects($this->never())->method('hr');
        $shell->_io->expects($this->exactly(2))->method('err');

        $shell->runCommand(['hr']);
    }

    /**
     * test run command causing exception on Shell method.
     *
     * @return void
     */
    public function testRunCommandMissingMethod()
    {
        /** @var \Cake\Console\Shell|\PHPUnit\Framework\MockObject\MockObject $shell */
        $shell = $this->getMockBuilder('Cake\Console\Shell')
            ->onlyMethods(['startup', 'getOptionParser', 'hr'])
            ->disableOriginalConstructor()
            ->getMock();
        $shell->setIo(
            $this->getMockBuilder('Cake\Console\ConsoleIo')
            ->onlyMethods(['err'])
            ->getMock()
        );
        $parser = $this->getMockBuilder('Cake\Console\ConsoleOptionParser')
            ->disableOriginalConstructor()
            ->getMock();
        $parser->expects($this->once())->method('help');
        $parser->method('parse')
            ->will($this->returnValue([[], []]));

        $shell->expects($this->once())->method('getOptionParser')
            ->will($this->returnValue($parser));
        $shell->_io->expects($this->exactly(2))->method('err');

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
            ->onlyMethods(['getOptionParser', 'out', 'startup', '_welcome'])
            ->disableOriginalConstructor()
            ->getMock();
        $shell->setIo($this->getMockBuilder('Cake\Console\ConsoleIo')->getMock());
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
        /** @var \Cake\Console\Shell|\PHPUnit\Framework\MockObject\MockObject $shell */
        $shell = $this->getMockBuilder('Cake\Console\Shell')
            ->onlyMethods(['startup', 'hasTask'])
            ->disableOriginalConstructor()
            ->getMock();
        $shell->setIo(
            $this->getMockBuilder('Cake\Console\ConsoleIo')
            ->onlyMethods(['err'])
            ->getMock()
        );
        $task = $this->getMockBuilder('Cake\Console\Shell')
            ->onlyMethods(['runCommand'])
            ->disableOriginalConstructor()
            ->getMock();

        $task->expects($this->never())
            ->method('runCommand');

        $shell->expects($this->any())
            ->method('hasTask')
            ->will($this->returnValue(true));
        $shell->expects($this->never())->method('startup');
        $shell->_io->expects($this->exactly(2))->method('err');
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
            ->onlyMethods(['hasTask', 'startup', 'getOptionParser'])
            ->disableOriginalConstructor()
            ->getMock();
        $shell->setIo($io);
        $task = $this->getMockBuilder('Cake\Console\Shell')
            ->onlyMethods(['runCommand'])
            ->addMethods(['main'])
            ->disableOriginalConstructor()
            ->getMock();
        $task->setIo($io);
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
            ->onlyMethods(['hasTask', 'getOptionParser'])
            ->setConstructorArgs([$io])
            ->getMock();
        $task = $this->getMockBuilder('Cake\Console\Shell')
            ->onlyMethods(['_welcome'])
            ->addMethods(['main'])
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
            ->onlyMethods(['startup', 'getOptionParser'])
            ->addMethods(['main'])
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
            ->method('error')
            ->with('Error: Missing required argument. The `filename` argument is required.');
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
            'truthy' => true,
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
            ],
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

        $this->assertSame('plugin.test', $parser->getCommand());
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

        $this->Shell = $this->getMockBuilder(ShellTestShell::class)
            ->addMethods(['welcome'])
            ->setConstructorArgs([$io])
            ->getMock();
        $this->Shell->runCommand(['foo', '--quiet']);
    }

    /**
     * Test getIo() and setIo() methods
     *
     * @return void
     */
    public function testGetSetIo()
    {
        $this->Shell->setIo($this->io);
        $this->assertSame($this->Shell->getIo(), $this->io);
    }

    /**
     * Test setRootName filters into the option parser help text.
     *
     * @return void
     */
    public function testSetRootNamePropagatesToHelpText()
    {
        $this->assertSame($this->Shell, $this->Shell->setRootName('tool'), 'is chainable');
        $this->assertStringContainsString('tool shell_test_shell [-h]', $this->Shell->getOptionParser()->help());
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
            'interactive' => true,
        ];
        $result = $this->Shell->__debugInfo();
        $this->assertEquals($expected, $result);
    }
}
