<?php
declare(strict_types=1);

namespace Cake\Test\TestCase\Console;

use Cake\Console\CommandScanner;
use Cake\Core\App;
use Cake\Core\Plugin;
use Cake\TestSuite\TestCase;
use Mockery;

/**
 * Test case for the CommandScanner
 */
class CommandScannerTest extends TestCase
{
    /**
     * Test scanning commands from the core.
     */
    public function testScanCore(): void
    {
        $commandScanner = Mockery::mock(CommandScanner::class . '[scanDir]');
        $commandScanner
            ->shouldReceive('scanDir')
            ->once()
            ->with(
                CAKE . 'Command' . DIRECTORY_SEPARATOR,
                'Cake\\Command\\',
                '',
                ['command_list'],
            )
            ->andReturn([]);

        $commandScanner->scanCore();
    }

    /**
     * Test scanning commands from the app.
     */
    public function testScanApp(): void
    {
        $commandScanner = Mockery::mock(CommandScanner::class . '[scanDir]');
        $commandScanner
            ->shouldReceive('scanDir')
            ->once()
            ->with(
                App::classPath('Command')[0],
                'App\\Command\\',
                '',
            )
            ->andReturn([]);

        $commandScanner->scanApp();
    }

    /**
     * Test scanning commands from a plugin.
     */
    public function testScanPlugin(): void
    {
        $this->loadPlugins(['Company/TestPluginThree']);

        $expected = [
            [
                'file' => Plugin::classPath('Company/TestPluginThree') . 'Command/CompanyCommand.php',
                'fullName' => 'company/test_plugin_three.company',
                'name' => 'company',
                'class' => 'Company\TestPluginThree\Command\CompanyCommand',
            ],
        ];
        $commandScanner = new CommandScanner();
        $result = $commandScanner->scanPlugin('Company/TestPluginThree');
        $this->assertSame($expected, $result);
    }

    /**
     * Test scanning commands from a no existing plugin.
     */
    public function testScanPluginNoExistingPlugin(): void
    {
        $commandScanner = new CommandScanner();
        $this->assertEmpty($commandScanner->scanPlugin('NonExistentPlugin'));
    }
}
