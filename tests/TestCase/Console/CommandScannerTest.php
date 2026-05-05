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
        $dir = ROOT . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'Command' . DIRECTORY_SEPARATOR;

        $expected = [
            [
                'file' => $dir . 'CacheClearCommand.php',
                'fullName' => 'cache clear',
                'name' => 'cache clear',
                'class' => 'Cake\Command\CacheClearCommand',
            ],
            [
                'file' => $dir . 'CacheClearGroupCommand.php',
                'fullName' => 'cache clear_group',
                'name' => 'cache clear_group',
                'class' => 'Cake\Command\CacheClearGroupCommand',
            ],
            [
                'file' => $dir . 'CacheClearallCommand.php',
                'fullName' => 'cache clear_all',
                'name' => 'cache clear_all',
                'class' => 'Cake\Command\CacheClearallCommand',
            ],
            [
                'file' => $dir . 'CacheListCommand.php',
                'fullName' => 'cache list',
                'name' => 'cache list',
                'class' => 'Cake\Command\CacheListCommand',
            ],
            [
                'file' => $dir . 'CompletionCommand.php',
                'fullName' => 'completion',
                'name' => 'completion',
                'class' => 'Cake\Command\CompletionCommand',
            ],
            [
                'file' => $dir . 'CounterCacheCommand.php',
                'fullName' => 'counter_cache',
                'name' => 'counter_cache',
                'class' => 'Cake\Command\CounterCacheCommand',
            ],
            [
                'file' => $dir . 'I18nCommand.php',
                'fullName' => 'i18n',
                'name' => 'i18n',
                'class' => 'Cake\Command\I18nCommand',
            ],
            [
                'file' => $dir . 'I18nExtractCommand.php',
                'fullName' => 'i18n extract',
                'name' => 'i18n extract',
                'class' => 'Cake\Command\I18nExtractCommand',
            ],
            [
                'file' => $dir . 'I18nInitCommand.php',
                'fullName' => 'i18n init',
                'name' => 'i18n init',
                'class' => 'Cake\Command\I18nInitCommand',
            ],
            [
                'file' => $dir . 'PluginAssetsCopyCommand.php',
                'fullName' => 'plugin assets copy',
                'name' => 'plugin assets copy',
                'class' => 'Cake\Command\PluginAssetsCopyCommand',
            ],
            [
                'file' => $dir . 'PluginAssetsRemoveCommand.php',
                'fullName' => 'plugin assets remove',
                'name' => 'plugin assets remove',
                'class' => 'Cake\Command\PluginAssetsRemoveCommand',
            ],
            [
                'file' => $dir . 'PluginAssetsSymlinkCommand.php',
                'fullName' => 'plugin assets symlink',
                'name' => 'plugin assets symlink',
                'class' => 'Cake\Command\PluginAssetsSymlinkCommand',
            ],
            [
                'file' => $dir . 'PluginListCommand.php',
                'fullName' => 'plugin list',
                'name' => 'plugin list',
                'class' => 'Cake\Command\PluginListCommand',
            ],
            [
                'file' => $dir . 'PluginLoadCommand.php',
                'fullName' => 'plugin load',
                'name' => 'plugin load',
                'class' => 'Cake\Command\PluginLoadCommand',
            ],
            [
                'file' => $dir . 'PluginLoadedCommand.php',
                'fullName' => 'plugin loaded',
                'name' => 'plugin loaded',
                'class' => 'Cake\Command\PluginLoadedCommand',
            ],
            [
                'file' => $dir . 'PluginUnloadCommand.php',
                'fullName' => 'plugin unload',
                'name' => 'plugin unload',
                'class' => 'Cake\Command\PluginUnloadCommand',
            ],
            [
                'file' => $dir . 'RoutesCheckCommand.php',
                'fullName' => 'routes check',
                'name' => 'routes check',
                'class' => 'Cake\Command\RoutesCheckCommand',
            ],
            [
                'file' => $dir . 'RoutesCommand.php',
                'fullName' => 'routes',
                'name' => 'routes',
                'class' => 'Cake\Command\RoutesCommand',
            ],
            [
                'file' => $dir . 'RoutesGenerateCommand.php',
                'fullName' => 'routes generate',
                'name' => 'routes generate',
                'class' => 'Cake\Command\RoutesGenerateCommand',
            ],
            [
                'file' => $dir . 'SchemacacheBuildCommand.php',
                'fullName' => 'schema_cache build',
                'name' => 'schema_cache build',
                'class' => 'Cake\Command\SchemacacheBuildCommand',
            ],
            [
                'file' => $dir . 'SchemacacheClearCommand.php',
                'fullName' => 'schema_cache clear',
                'name' => 'schema_cache clear',
                'class' => 'Cake\Command\SchemacacheClearCommand',
            ],
            [
                'file' => $dir . 'ServerCommand.php',
                'fullName' => 'server',
                'name' => 'server',
                'class' => 'Cake\Command\ServerCommand',
            ],
            [
                'file' => $dir . 'VersionCommand.php',
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
