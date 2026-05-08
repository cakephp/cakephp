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
        $commandDir = ROOT . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'Command' . DIRECTORY_SEPARATOR;

        $expected = [
            [
                'file' => $commandDir . 'CacheClearCommand.php',
                'fullName' => 'cache clear',
                'name' => 'cache clear',
                'class' => 'Cake\Command\CacheClearCommand',
            ],
            [
                'file' => $commandDir . 'CacheClearGroupCommand.php',
                'fullName' => 'cache clear_group',
                'name' => 'cache clear_group',
                'class' => 'Cake\Command\CacheClearGroupCommand',
            ],
            [
                'file' => $commandDir . 'CacheClearallCommand.php',
                'fullName' => 'cache clear_all',
                'name' => 'cache clear_all',
                'class' => 'Cake\Command\CacheClearallCommand',
            ],
            [
                'file' => $commandDir . 'CacheListCommand.php',
                'fullName' => 'cache list',
                'name' => 'cache list',
                'class' => 'Cake\Command\CacheListCommand',
            ],
            [
                'file' => $commandDir . 'CompletionCommand.php',
                'fullName' => 'completion',
                'name' => 'completion',
                'class' => 'Cake\Command\CompletionCommand',
            ],
            [
                'file' => $commandDir . 'CounterCacheCommand.php',
                'fullName' => 'counter_cache',
                'name' => 'counter_cache',
                'class' => 'Cake\Command\CounterCacheCommand',
            ],
            [
                'file' => $commandDir . 'I18nCommand.php',
                'fullName' => 'i18n',
                'name' => 'i18n',
                'class' => 'Cake\Command\I18nCommand',
            ],
            [
                'file' => $commandDir . 'I18nExtractCommand.php',
                'fullName' => 'i18n extract',
                'name' => 'i18n extract',
                'class' => 'Cake\Command\I18nExtractCommand',
            ],
            [
                'file' => $commandDir . 'I18nInitCommand.php',
                'fullName' => 'i18n init',
                'name' => 'i18n init',
                'class' => 'Cake\Command\I18nInitCommand',
            ],
            [
                'file' => $commandDir . 'PluginAssetsCopyCommand.php',
                'fullName' => 'plugin assets copy',
                'name' => 'plugin assets copy',
                'class' => 'Cake\Command\PluginAssetsCopyCommand',
            ],
            [
                'file' => $commandDir . 'PluginAssetsRemoveCommand.php',
                'fullName' => 'plugin assets remove',
                'name' => 'plugin assets remove',
                'class' => 'Cake\Command\PluginAssetsRemoveCommand',
            ],
            [
                'file' => $commandDir . 'PluginAssetsSymlinkCommand.php',
                'fullName' => 'plugin assets symlink',
                'name' => 'plugin assets symlink',
                'class' => 'Cake\Command\PluginAssetsSymlinkCommand',
            ],
            [
                'file' => $commandDir . 'PluginListCommand.php',
                'fullName' => 'plugin list',
                'name' => 'plugin list',
                'class' => 'Cake\Command\PluginListCommand',
            ],
            [
                'file' => $commandDir . 'PluginLoadCommand.php',
                'fullName' => 'plugin load',
                'name' => 'plugin load',
                'class' => 'Cake\Command\PluginLoadCommand',
            ],
            [
                'file' => $commandDir . 'PluginLoadedCommand.php',
                'fullName' => 'plugin loaded',
                'name' => 'plugin loaded',
                'class' => 'Cake\Command\PluginLoadedCommand',
            ],
            [
                'file' => $commandDir . 'PluginUnloadCommand.php',
                'fullName' => 'plugin unload',
                'name' => 'plugin unload',
                'class' => 'Cake\Command\PluginUnloadCommand',
            ],
            [
                'file' => $commandDir . 'RoutesCheckCommand.php',
                'fullName' => 'routes check',
                'name' => 'routes check',
                'class' => 'Cake\Command\RoutesCheckCommand',
            ],
            [
                'file' => $commandDir . 'RoutesCommand.php',
                'fullName' => 'routes',
                'name' => 'routes',
                'class' => 'Cake\Command\RoutesCommand',
            ],
            [
                'file' => $commandDir . 'RoutesGenerateCommand.php',
                'fullName' => 'routes generate',
                'name' => 'routes generate',
                'class' => 'Cake\Command\RoutesGenerateCommand',
            ],
            [
                'file' => $commandDir . 'SchemacacheBuildCommand.php',
                'fullName' => 'schema_cache build',
                'name' => 'schema_cache build',
                'class' => 'Cake\Command\SchemacacheBuildCommand',
            ],
            [
                'file' => $commandDir . 'SchemacacheClearCommand.php',
                'fullName' => 'schema_cache clear',
                'name' => 'schema_cache clear',
                'class' => 'Cake\Command\SchemacacheClearCommand',
            ],
            [
                'file' => $commandDir . 'ServerCommand.php',
                'fullName' => 'server',
                'name' => 'server',
                'class' => 'Cake\Command\ServerCommand',
            ],
            [
                'file' => $commandDir . 'VersionCommand.php',
                'fullName' => 'version',
                'name' => 'version',
                'class' => 'Cake\Command\VersionCommand',
            ],
        ];
        $commandScanner = new CommandScanner();
        $result = $commandScanner->scanCore();
        $this->assertSame($expected, $result);
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
                'file' => Plugin::classPath('Company/TestPluginThree') . 'Command' . DIRECTORY_SEPARATOR . 'CompanyCommand.php',
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
