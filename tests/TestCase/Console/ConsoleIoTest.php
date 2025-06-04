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
 * @since         3.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Console;

use Cake\Console\ConsoleInput;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOutput;
use Cake\Console\Exception\StopException;
use Cake\Log\Log;
use Cake\TestSuite\TestCase;
use Cake\Utility\Filesystem;
use Mockery;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\TestWith;

/**
 * ConsoleIo test.
 */
class ConsoleIoTest extends TestCase
{
    /**
     * @var \Cake\Console\ConsoleIo
     */
    protected $io;

    /**
     * @var \Cake\Console\ConsoleOutput|\Mockery\MockInterface
     */
    protected $out;

    /**
     * @var \Cake\Console\ConsoleOutput|\Mockery\MockInterface
     */
    protected $err;

    /**
     * @var \Cake\Console\ConsoleInput|\Mockery\MockInterface
     */
    protected $in;

    /**
     * setUp method
     */
    protected function setUp(): void
    {
        parent::setUp();
        static::setAppNamespace();

        $this->out = Mockery::mock(ConsoleOutput::class)->shouldIgnoreMissing();
        $this->err = Mockery::mock(ConsoleOutput::class)->shouldIgnoreMissing();
        $this->in = Mockery::mock(ConsoleInput::class);

        $this->io = new ConsoleIo($this->out, $this->err, $this->in);
    }

    /**
     * teardown method
     */
    protected function tearDown(): void
    {
        parent::tearDown();
        if (is_dir(TMP . 'shell_test')) {
            $fs = new Filesystem();
            $fs->deleteDir(TMP . 'shell_test');
        }
        Log::drop('console-logger');
    }

    /**
     * Provider for testing choice types.
     *
     * @return array
     */
    public static function choiceProvider(): array
    {
        return [
            [['y', 'n']],
            ['y,n'],
            ['y/n'],
            ['y'],
        ];
    }

    /**
     * test ask choices method
     *
     * @param array|string $choices
     */
    #[DataProvider('choiceProvider')]
    public function testAskChoices($choices): void
    {
        $this->in->shouldReceive('read')->andReturn('y')->once();

        $result = $this->io->askChoice('Just a test?', $choices);
        $this->assertSame('y', $result);
    }

    /**
     * test ask choices method
     *
     * @param array|string $choices
     */
    #[DataProvider('choiceProvider')]
    public function testAskChoicesInsensitive($choices): void
    {
        $this->in->shouldReceive('read')->andReturn('Y')->once();

        $result = $this->io->askChoice('Just a test?', $choices);
        $this->assertSame('Y', $result);
    }

    /**
     * Test ask method
     */
    public function testAsk(): void
    {
        $this->out->shouldReceive('write')
            ->with("<question>Just a test?</question>\n> ", 0)
            ->once();

        $this->in->shouldReceive('read')->andReturn('y')->once();

        $result = $this->io->ask('Just a test?');
        $this->assertSame('y', $result);
    }

    /**
     * Test ask method
     */
    public function testAskDefaultValue(): void
    {
        $this->out->shouldReceive('write')
            ->with("<question>Just a test?</question>\n[n] > ", 0)
            ->once();

        $this->in->shouldReceive('read')->andReturn('')->once();

        $result = $this->io->ask('Just a test?', 'n');
        $this->assertSame('n', $result);
    }

    /**
     * testOut method
     */
    #[TestWith(['Just a test'])]
    #[TestWith([['Just', 'a', 'test']])]
    #[TestWith([['Just', 'a', 'test'], 2])]
    #[TestWith([''])]
    public function testOut(string|array $message, int $newLines = 1): void
    {
        $this->out->shouldReceive('write')
            ->with($message, $newLines)
            ->once();

        $this->io->out($message, $newLines);
    }

    /**
     * test that verbose and quiet output levels work
     */
    #[TestWith(['Verbose', 1, ConsoleIo::VERBOSE])]
    #[TestWith(['Normal', 1, ConsoleIo::NORMAL])]
    #[TestWith(['Quiet', 1, ConsoleIo::QUIET])]
    public function testVerboseOut(string $message, int $newlines, int $level): void
    {
        $this->out->shouldReceive('write')
            ->with($message, $newlines)
            ->once();

        $this->io->level(ConsoleIo::VERBOSE);
        $this->io->out($message, $newlines, $level);
    }

    /**
     * test that verbose and quiet output levels work
     */
    #[TestWith(['verbose', 'Verbose'])]
    #[TestWith(['out', 'Out'])]
    #[TestWith(['quiet', 'Quiet'])]
    public function testVerboseOutput(string $method, string $message): void
    {
        $this->out->shouldReceive('write')
            ->with($message, 1)
            ->once();

        $this->io->level(ConsoleIo::VERBOSE);
        $this->io->{$method}($message);
    }

    /**
     * test that verbose and quiet output levels work
     */
    public function testQuietOutput(): void
    {
        $this->out->shouldReceive('write')
            ->with('Quiet', 1)
            ->twice();

        $this->io->level(ConsoleIo::QUIET);

        $this->io->out('Verbose', 1, ConsoleIo::VERBOSE);
        $this->io->out('Normal', 1, ConsoleIo::NORMAL);
        $this->io->out('Quiet', 1, ConsoleIo::QUIET);
        $this->io->verbose('Verbose');
        $this->io->quiet('Quiet');
    }

    /**
     * testErr method
     */
    #[TestWith(['Just a test'])]
    #[TestWith([['Just', 'a', 'test']])]
    #[TestWith([['Just', 'a', 'test'], 2])]
    #[TestWith([''])]
    public function testErr(string|array $message, int $newLines = 1): void
    {
        $this->err->shouldReceive('write')
            ->with($message, $newLines)
            ->once();

        $this->io->err($message, $newLines);
    }

    /**
     * Tests abort() wrapper.
     */
    public function testAbort(): void
    {
        $this->expectException(StopException::class);
        $this->expectExceptionMessage('Some error');
        $this->expectExceptionCode(1);

        $this->err->shouldReceive('write')
            ->with('<error>Some error</error>', 1)
            ->once();

        $this->expectException(StopException::class);
        $this->expectExceptionCode(1);
        $this->expectExceptionMessage('Some error');

        $this->io->abort('Some error');
    }

    /**
     * Tests abort() wrapper.
     */
    public function testAbortCustomCode(): void
    {
        $this->expectException(StopException::class);
        $this->expectExceptionMessage('Some error');
        $this->expectExceptionCode(99);

        $this->err->shouldReceive('write')
            ->with('<error>Some error</error>', 1)
            ->once();

        $this->expectException(StopException::class);
        $this->expectExceptionCode(99);
        $this->expectExceptionMessage('Some error');

        $this->io->abort('Some error', 99);
    }

    /**
     * testNl
     */
    public function testNl(): void
    {
        $newLine = "\n";
        if (DS === '\\') {
            $newLine = "\r\n";
        }
        $this->assertSame($this->io->nl(), $newLine);
        $this->assertSame($this->io->nl(2), $newLine . $newLine);
        $this->assertSame($this->io->nl(1), $newLine);
    }

    /**
     * testHr
     */
    #[TestWith([0])]
    #[TestWith([2])]
    public function testHr(int $newlines): void
    {
        $bar = str_repeat('-', 79);

        $this->out->shouldReceive('write')->with('', $newlines)->once();
        $this->out->shouldReceive('write')->with($bar, 1)->once();
        $this->out->shouldReceive('write')->with('', $newlines)->once();

        $this->io->hr($newlines);
    }

    /**
     * Test overwriting.
     */
    public function testOverwrite(): void
    {
        $number = strlen('Some text I want to overwrite');

        $this->out->shouldReceive('write')
            ->with('Some <info>text</info> I want to overwrite', 0)
            ->andReturn($number)
            ->once();

        $this->out->shouldReceive('write')
            ->with(str_repeat("\x08", $number), 0)
            ->andReturn(9)
            ->once();

        $this->out->shouldReceive('write')
            ->with('Less text', 0)
            ->andReturn(9)
            ->once();

        $this->out->shouldReceive('write')
            ->with(str_repeat(' ', $number - 9), 0)
            ->andReturn(1)
            ->once();

        $this->out->shouldReceive('write')
            ->with(PHP_EOL, 0)
            ->andReturn(0)
            ->once();

        $this->io->out('Some <info>text</info> I want to overwrite', 0);
        $this->io->overwrite('Less text');
    }

    /**
     * Test overwriting content with shorter content
     */
    public function testOverwriteWithShorterContent(): void
    {
        $length = strlen('12345');

        $this->out->shouldReceive('write')
            ->with('12345', 1)
            ->andReturn($length)
            ->once();

        // Backspaces
        $this->out->shouldReceive('write')
            ->with(str_repeat("\x08", $length), 0)
            ->andReturn($length)
            ->once();

        $this->out->shouldReceive('write')
            ->with('123', 0)
            ->andReturn(3)
            ->once();

        // 2 spaces output to pad up to 5 bytes
        $this->out->shouldReceive('write')
            ->with(str_repeat(' ', $length - 3), 0)
            ->andReturn($length - 3)
            ->once();

        // Backspaces
        $this->out->shouldReceive('write')
            ->with(str_repeat("\x08", $length), 0)
            ->andReturn($length)
            ->once();

        $this->out->shouldReceive('write')
            ->with('12', 0)
            ->andReturn(2)
            ->once();

        $this->out->shouldReceive('write')
            ->with(str_repeat(' ', $length - 2), 0)
            ->andReturn($length - 2)
            ->once();

        $this->io->out('12345');
        $this->io->overwrite('123', 0);
        $this->io->overwrite('12', 0);
    }

    /**
     * Test overwriting content with longer content
     */
    public function testOverwriteWithLongerContent(): void
    {
        $this->out->shouldReceive('write')
            ->with('1', 1)
            ->andReturn(1)
            ->once();

        // Backspaces
        $this->out->shouldReceive('write')
            ->with(str_repeat("\x08", 1), 0)
            ->andReturn(1)
            ->once();

        $this->out->shouldReceive('write')
            ->with('123', 0)
            ->andReturn(3)
            ->once();

        // Backspaces
        $this->out->shouldReceive('write')
            ->with(str_repeat("\x08", 3), 0)
            ->andReturn(3)
            ->once();

        $this->out->shouldReceive('write')
            ->with('12345', 0)
            ->andReturn(5)
            ->once();

        $this->io->out('1');
        $this->io->overwrite('123', 0);
        $this->io->overwrite('12345', 0);
    }

    /**
     * Tests that setLoggers works properly
     */
    public function testSetLoggers(): void
    {
        Log::drop('stdout');
        Log::drop('stderr');
        $this->io->setLoggers(true);
        $this->assertNotEmpty(Log::engine('stdout'));
        $this->assertNotEmpty(Log::engine('stderr'));

        $this->io->setLoggers(false);
        $this->assertNull(Log::engine('stdout'));
        $this->assertNull(Log::engine('stderr'));
    }

    /**
     * Tests that setLoggers does not add loggers if the
     * application already has a console logger. This
     * lets developers opt-out of the default behavior
     * by configuring something equivalent.
     */
    public function testSetLoggersWithCustom(): void
    {
        Log::drop('stdout');
        Log::drop('stderr');
        Log::setConfig('console-logger', [
            'className' => 'Console',
            'stream' => $this->out,
            'types' => ['error', 'warning'],
        ]);
        $this->io->setLoggers(true);
        $this->assertEmpty(Log::engine('stdout'));
        $this->assertEmpty(Log::engine('stderr'));
        $this->assertNotEmpty(Log::engine('console-logger'));

        $this->io->setLoggers(false);
        $this->assertNull(Log::engine('stdout'));
        $this->assertNull(Log::engine('stderr'));
        $this->assertNotEmpty(Log::engine('console-logger'));
    }

    /**
     * Tests that setLoggers works properly with quiet
     */
    public function testSetLoggersQuiet(): void
    {
        Log::drop('stdout');
        Log::drop('stderr');
        $this->io->setLoggers(ConsoleIo::QUIET);
        $this->assertEmpty(Log::engine('stdout'));
        $this->assertNotEmpty(Log::engine('stderr'));
    }

    /**
     * Tests that setLoggers works properly with verbose
     */
    public function testSetLoggersVerbose(): void
    {
        Log::drop('stdout');
        Log::drop('stderr');
        $this->io->setLoggers(ConsoleIo::VERBOSE);

        $this->assertNotEmpty(Log::engine('stderr'));
        /** @var \Cake\Log\Log $engine */
        $engine = Log::engine('stdout');
        $this->assertEquals(['notice', 'info', 'debug'], $engine->getConfig('levels'));
    }

    /**
     * Ensure that setStyle() just proxies to stdout.
     */
    public function testSetStyle(): void
    {
        $this->out->shouldReceive('setStyle')
            ->with('name', ['props'])
            ->once();

        $this->io->setStyle('name', ['props']);
    }

    /**
     * Ensure that getStyle() just proxies to stdout.
     */
    public function testGetStyle(): void
    {
        $this->out->shouldReceive('getStyle')
            ->with('name')
            ->once();

        $this->io->getStyle('name');
    }

    /**
     * Ensure that styles() just proxies to stdout.
     */
    public function testStyles(): void
    {
        $this->out->shouldReceive('styles')->once();

        $this->io->styles();
    }

    /**
     * Test the helper method.
     */
    public function testHelper(): void
    {
        $this->out->shouldReceive('write')
            ->with('It works!well ish', 1)
            ->once();

        $helper = $this->io->helper('simple');
        $helper->output(['well', 'ish']);
    }

    /**
     * test out helper methods
     */
    #[TestWith(['info'])]
    #[TestWith(['success'])]
    #[TestWith(['comment'])]
    public function testOutHelpers(string $method): void
    {
        $this->out->shouldReceive('write')
            ->with("<{$method}>Just a test</{$method}>", 1)
            ->once();

        $this->out->shouldReceive('write')
            ->with(["<{$method}>Just</{$method}>", "<{$method}>a test</{$method}>"], 1)
            ->once();

        $this->io->{$method}('Just a test');
        $this->io->{$method}(['Just', 'a test']);
    }

    /**
     * test err helper methods
     */
    #[TestWith(['warning'])]
    #[TestWith(['error'])]
    public function testErrHelpers(string $method): void
    {
        $this->err->shouldReceive('write')
            ->with("<{$method}>Just a test</{$method}>", 1)
            ->once();

        $this->err->shouldReceive('write')
            ->with(["<{$method}>Just</{$method}>", "<{$method}>a test</{$method}>"], 1)
            ->once();

        $this->io->{$method}('Just a test');
        $this->io->{$method}(['Just', 'a test']);
    }

    /**
     * Test that createFile
     */
    public function testCreateFileSuccess(): void
    {
        $this->err->shouldNotReceive('write');

        $path = TMP . 'shell_test';
        mkdir($path);

        $file = $path . DS . 'file1.php';
        $contents = 'some content';
        $result = $this->io->createFile($file, $contents);

        $this->assertTrue($result);
        $this->assertFileExists($file);
        $this->assertStringEqualsFile($file, $contents);
    }

    public function testCreateFileEmptySuccess(): void
    {
        $this->err->shouldNotReceive('write');

        $path = TMP . 'shell_test';
        mkdir($path);

        $file = $path . DS . 'file_empty.php';
        $contents = '';
        $result = $this->io->createFile($file, $contents);

        $this->assertTrue($result);
        $this->assertFileExists($file);
        $this->assertStringEqualsFile($file, $contents);
    }

    public function testCreateFileDirectoryCreation(): void
    {
        $this->err->shouldNotReceive('write');

        $directory = TMP . 'shell_test';
        $this->assertFileDoesNotExist($directory, 'Directory should not exist before createFile');

        $path = $directory . DS . 'create.txt';
        $contents = 'some content';
        $result = $this->io->createFile($path, $contents);

        $this->assertTrue($result, 'File should create');
        $this->assertFileExists($path);
        $this->assertStringEqualsFile($path, $contents);
    }

    /**
     * Test that createFile with permissions error.
     */
    public function testCreateFilePermissionsError(): void
    {
        $this->skipIf(DS === '\\', 'Cant perform operations using permissions on windows.');

        $path = TMP . 'shell_test';
        $file = $path . DS . 'no_perms';

        if (!is_dir($path)) {
            mkdir($path);
        }
        chmod($path, 0444);

        $this->io->createFile($file, 'testing');
        $this->assertFileDoesNotExist($file);

        chmod($path, 0744);
        rmdir($path);
    }

    /**
     * Test that `q` raises an error.
     */
    public function testCreateFileOverwriteQuit(): void
    {
        $path = TMP . 'shell_test';
        mkdir($path);

        $file = $path . DS . 'file1.php';
        touch($file);

        $this->expectException(StopException::class);

        $this->in->shouldReceive('read')->andReturn('q')->once();

        $this->io->createFile($file, 'some content');
    }

    /**
     * Test that `n` raises an error.
     */
    public function testCreateFileOverwriteNo(): void
    {
        $path = TMP . 'shell_test';
        mkdir($path);

        $file = $path . DS . 'file1.php';
        file_put_contents($file, 'original');
        touch($file);

        $this->in->shouldReceive('read')
            ->andReturn('n')
            ->once();

        $contents = 'new content';
        $result = $this->io->createFile($file, $contents);

        $this->assertFalse($result);
        $this->assertFileExists($file);
        $this->assertStringEqualsFile($file, 'original');
    }

    /**
     * Test the forceOverwrite parameter
     */
    public function testCreateFileOverwriteParam(): void
    {
        $path = TMP . 'shell_test';
        mkdir($path);

        $file = $path . DS . 'file1.php';
        file_put_contents($file, 'original');
        touch($file);

        $contents = 'new content';
        $result = $this->io->createFile($file, $contents, true);

        $this->assertTrue($result);
        $this->assertFileExists($file);
        $this->assertStringEqualsFile($file, $contents);
    }

    /**
     * Test the `a` response
     */
    public function testCreateFileOverwriteAll(): void
    {
        $path = TMP . 'shell_test';
        mkdir($path);

        $file = $path . DS . 'file1.php';
        file_put_contents($file, 'original');
        touch($file);

        $this->in->shouldReceive('read')
            ->andReturn('a')
            ->once();

        $this->io->createFile($file, 'new content');
        $this->assertStringEqualsFile($file, 'new content');

        $this->io->createFile($file, 'newer content');
        $this->assertStringEqualsFile($file, 'newer content');

        $this->io->createFile($file, 'newest content', false);
        $this->assertStringEqualsFile(
            $file,
            'newest content',
            'overwrite state replaces parameter',
        );
    }
}
