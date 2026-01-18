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
 * @since         5.2.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Command;

use Cake\Attribute\Resolver;
use Cake\Console\CommandInterface;
use Cake\Console\TestSuite\ConsoleIntegrationTestTrait;
use Cake\TestSuite\TestCase;
use Cake\Utility\Filesystem;

/**
 * AttributesResolveCommandTest class
 */
class AttributesResolveCommandTest extends TestCase
{
    use ConsoleIntegrationTestTrait;

    protected string $artifactPath;

    /**
     * setUp method
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->setAppNamespace();

        $this->artifactPath = TMP . 'tests' . DS . 'attributes' . DS;

        Resolver::setConfig('default', [
            'paths' => [
                'Attribute/Resolver/Fixture/*.php',
            ],
            'basePath' => APP,
            'excludePaths' => [],
            'excludeAttributes' => [],
            'artifact' => $this->artifactPath . 'default.php',
            'validateFiles' => false,
        ]);
    }

    /**
     * tearDown method
     */
    protected function tearDown(): void
    {
        parent::tearDown();
        Resolver::drop('default');

        $fs = new Filesystem();
        if (is_dir($this->artifactPath)) {
            $fs->deleteDir($this->artifactPath);
        }
    }

    /**
     * Test defaultName
     */
    public function testDefaultName(): void
    {
        $this->exec('attributes resolve --help');
        $this->assertExitCode(CommandInterface::CODE_SUCCESS);
        $this->assertOutputContains('attributes resolve');
    }

    /**
     * Test getDescription
     */
    public function testGetDescription(): void
    {
        $this->exec('attributes resolve --help');
        $this->assertExitCode(CommandInterface::CODE_SUCCESS);
        $this->assertOutputContains('Resolve');
    }

    /**
     * Test help output
     */
    public function testHelp(): void
    {
        $this->exec('attributes resolve --help');
        $this->assertExitCode(CommandInterface::CODE_SUCCESS);
        $this->assertOutputContains('attributes resolve');
        $this->assertOutputContains('no-clear');
        $this->assertOutputContains('clear-only');
    }

    /**
     * Test basic execution
     */
    public function testBasicExecution(): void
    {
        $this->exec('attributes resolve');
        $this->assertExitCode(CommandInterface::CODE_SUCCESS);
    }

    /**
     * Test outputs resolve message
     */
    public function testOutputsResolveMessage(): void
    {
        $this->exec('attributes resolve');
        $this->assertOutputContains('Resolving attributes');
    }

    /**
     * Test outputs clear message
     */
    public function testOutputsClearMessage(): void
    {
        $this->exec('attributes resolve');
        $this->assertOutputContains('Clearing');
    }

    /**
     * Test outputs success with count
     */
    public function testOutputsSuccessWithCount(): void
    {
        $this->exec('attributes resolve');
        $this->assertOutputContains('Resolved');
        $this->assertOutputContains('attributes');
    }

    /**
     * Test outputs elapsed time
     */
    public function testOutputsElapsedTime(): void
    {
        $this->exec('attributes resolve');
        $this->assertOutputRegExp('/\d+\.?\d*s/');
    }

    /**
     * Test returns success code
     */
    public function testReturnsSuccessCode(): void
    {
        $this->exec('attributes resolve');
        $this->assertExitCode(CommandInterface::CODE_SUCCESS);
    }

    /**
     * Test creates artifact file
     */
    public function testCreatesArtifactFile(): void
    {
        $this->exec('attributes resolve');
        $this->assertDirectoryExists($this->artifactPath);
        $files = glob($this->artifactPath . '*.php');
        $this->assertNotEmpty($files);
    }

    /**
     * Test artifact contains attributes
     */
    public function testArtifactContainsAttributes(): void
    {
        $this->exec('attributes resolve');

        $files = glob($this->artifactPath . '*.php');
        $this->assertNotEmpty($files);

        $content = file_get_contents($files[0]);
        $this->assertStringContainsString('return', $content);
    }

    /**
     * Test no-clear option
     */
    public function testNoClearOption(): void
    {
        $this->exec('attributes resolve --no-clear');
        $this->assertOutputNotContains('Clearing');
    }

    /**
     * Test no-clear preserves existing artifacts
     */
    public function testNoClearPreservesExistingArtifacts(): void
    {
        // First resolve to create artifact
        $this->exec('attributes resolve');
        $files = glob($this->artifactPath . '*.php');
        $this->assertNotEmpty($files);
        $originalTime = filemtime($files[0]);

        // Wait a moment to ensure time difference
        sleep(1);

        // Resolve again with --no-clear
        $this->exec('attributes resolve --no-clear');
        $newFiles = glob($this->artifactPath . '*.php');
        $newTime = filemtime($newFiles[0]);

        // File should have been updated (resolve still writes)
        $this->assertGreaterThanOrEqual($originalTime, $newTime);
    }

    /**
     * Test clear-only option
     */
    public function testClearOnlyOption(): void
    {
        // Create artifact first
        $this->exec('attributes resolve');
        $this->assertDirectoryExists($this->artifactPath);

        // Clear only
        $this->exec('attributes resolve --clear-only');
        $this->assertOutputContains('Clearing');
        $this->assertOutputNotContains('Resolving');
    }

    /**
     * Test clear-only no resolve message
     */
    public function testClearOnlyNoResolveMessage(): void
    {
        $this->exec('attributes resolve --clear-only');
        $this->assertOutputNotContains('Resolved');
    }

    /**
     * Test clear-only removes artifacts
     */
    public function testClearOnlyRemovesArtifacts(): void
    {
        // Create artifact
        $this->exec('attributes resolve');
        $files = glob($this->artifactPath . '*.php');
        $this->assertNotEmpty($files);

        // Clear only
        $this->exec('attributes resolve --clear-only');

        // Artifacts should be removed
        $files = glob($this->artifactPath . '*.php');
        $this->assertEmpty($files);
    }

    /**
     * Test warning when artifacts disabled
     */
    public function testWarningWhenArtifactsDisabled(): void
    {
        Resolver::setConfig('disabled', [
            'paths' => ['Attribute/Resolver/Fixture/*.php'],
            'basePath' => APP,
            'artifact' => null,
        ]);

        $this->exec('attributes resolve --config disabled');
        $this->assertErrorContains('disabled');

        Resolver::drop('disabled');
    }

    /**
     * Test uses default config
     */
    public function testUsesDefaultConfig(): void
    {
        $this->exec('attributes resolve');
        $this->assertExitCode(CommandInterface::CODE_SUCCESS);
        $this->assertDirectoryExists($this->artifactPath);
    }

    /**
     * Test config option
     */
    public function testConfigOption(): void
    {
        $customPath = TMP . 'tests' . DS . 'custom_attributes' . DS;

        Resolver::setConfig('custom', [
            'paths' => ['Attribute/Resolver/Fixture/*.php'],
            'basePath' => APP,
            'artifact' => $customPath . 'custom.php',
        ]);

        $this->exec('attributes resolve --config custom');
        $this->assertExitCode(CommandInterface::CODE_SUCCESS);
        $this->assertDirectoryExists($customPath);

        Resolver::drop('custom');
        $fs = new Filesystem();
        if (is_dir($customPath)) {
            $fs->deleteDir($customPath);
        }
    }

    /**
     * Test invalid config shows error
     */
    public function testInvalidConfigShowsError(): void
    {
        $this->exec('attributes resolve --config nonexistent');
        $this->assertExitCode(CommandInterface::CODE_ERROR);
        $this->assertErrorContains('Configuration');
    }

    /**
     * Test empty results shows zero count
     */
    public function testEmptyResultsShowsZeroCount(): void
    {
        $emptyDir = TMP . 'empty_test_' . uniqid() . DS;
        mkdir($emptyDir, 0777, true);

        Resolver::setConfig('empty', [
            'paths' => ['*.php'],
            'basePath' => $emptyDir,
            'artifact' => $emptyDir . 'empty.php',
        ]);

        $this->exec('attributes resolve --config empty');
        $this->assertOutputContains('Resolved 0 attributes');

        Resolver::drop('empty');
        $fs = new Filesystem();
        $fs->deleteDir($emptyDir);
    }

    /**
     * Test option parser has all options
     */
    public function testOptionParserHasAllOptions(): void
    {
        $this->exec('attributes resolve --help');
        $this->assertOutputContains('no-clear');
        $this->assertOutputContains('clear-only');
        $this->assertOutputContains('config');
    }
}
