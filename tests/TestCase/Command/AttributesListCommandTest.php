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
 * @since         6.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Command;

use Cake\Attribute\Resolver;
use Cake\Console\CommandInterface;
use Cake\Console\TestSuite\ConsoleIntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * AttributesListCommandTest class
 */
class AttributesListCommandTest extends TestCase
{
    use ConsoleIntegrationTestTrait;

    /**
     * setUp method
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->setAppNamespace();

        Resolver::setConfig('default', [
            'paths' => [
                'Attribute/Resolver/Fixture/*.php',
            ],
            'basePath' => APP,
            'excludePaths' => [],
            'excludeAttributes' => [],
            'artifact' => null,
        ]);
    }

    /**
     * tearDown method
     */
    protected function tearDown(): void
    {
        parent::tearDown();
        Resolver::drop('default');
    }

    /**
     * Test defaultName
     */
    public function testDefaultName(): void
    {
        $this->exec('attributes list --help');
        $this->assertExitCode(CommandInterface::CODE_SUCCESS);
        $this->assertOutputContains('attributes list');
    }

    /**
     * Test getDescription
     */
    public function testGetDescription(): void
    {
        $this->exec('attributes list --help');
        $this->assertExitCode(CommandInterface::CODE_SUCCESS);
        $this->assertOutputContains('List');
    }

    /**
     * Test help output
     */
    public function testHelp(): void
    {
        $this->exec('attributes list --help');
        $this->assertExitCode(CommandInterface::CODE_SUCCESS);
        $this->assertOutputContains('attributes list');
        $this->assertOutputContains('attribute');
        $this->assertOutputContains('class');
    }

    /**
     * Test basic execution
     */
    public function testBasicExecution(): void
    {
        $this->exec('attributes list');
        $this->assertExitCode(CommandInterface::CODE_SUCCESS);
    }

    /**
     * Test outputs attribute count
     */
    public function testOutputsAttributeCount(): void
    {
        $this->exec('attributes list');
        $this->assertOutputContains('Found');
        $this->assertOutputContains('attributes');
    }

    /**
     * Test outputs table headers
     */
    public function testOutputsTableHeaders(): void
    {
        $this->exec('attributes list');
        $this->assertOutputContains('Attribute');
        $this->assertOutputContains('Class');
        $this->assertOutputContains('Plugin');
        $this->assertOutputContains('Type');
        $this->assertOutputContains('Target');
    }

    /**
     * Test lists all attributes
     */
    public function testListsAllAttributes(): void
    {
        $this->exec('attributes list');
        $this->assertExitCode(CommandInterface::CODE_SUCCESS);
        $this->assertOutputContains('TestRoute');
    }

    /**
     * Test returns success code
     */
    public function testReturnsSuccessCode(): void
    {
        $this->exec('attributes list');
        $this->assertExitCode(CommandInterface::CODE_SUCCESS);
    }

    /**
     * Test empty results shows warning
     */
    public function testEmptyResultsShowsWarning(): void
    {
        $emptyDir = TMP . 'empty_list_test_' . uniqid() . DS;
        mkdir($emptyDir, 0777, true);

        // Drop first to ensure fresh config
        Resolver::drop('emptylist');

        Resolver::setConfig('emptylist', [
            'paths' => ['*.php'],
            'basePath' => $emptyDir,
            'artifact' => null,
        ]);

        $this->exec('attributes list --config emptylist');
        // When empty, warning should be shown (it's in stderr, not stdout)
        $this->assertErrorContains('No attributes found');

        Resolver::drop('emptylist');
        rmdir($emptyDir);
    }

    /**
     * Test empty results returns success
     */
    public function testEmptyResultsReturnsSuccess(): void
    {
        $emptyDir = TMP . 'empty_list_test_' . uniqid() . DS;
        mkdir($emptyDir, 0777, true);

        // Drop first to ensure fresh config
        Resolver::drop('emptylist2');

        Resolver::setConfig('emptylist2', [
            'paths' => ['*.php'],
            'basePath' => $emptyDir,
            'artifact' => null,
        ]);

        $this->exec('attributes list --config emptylist2');
        $this->assertExitCode(CommandInterface::CODE_SUCCESS);

        Resolver::drop('emptylist2');
        rmdir($emptyDir);
    }

    /**
     * Test filter by attribute name
     */
    public function testFilterByAttributeName(): void
    {
        $this->exec('attributes list --attribute TestRoute');
        $this->assertExitCode(CommandInterface::CODE_SUCCESS);
        $this->assertOutputContains('TestRoute');
    }

    /**
     * Test filter by attribute short option
     */
    public function testFilterByAttributeShortOption(): void
    {
        $this->exec('attributes list -a TestRoute');
        $this->assertExitCode(CommandInterface::CODE_SUCCESS);
        $this->assertOutputContains('TestRoute');
    }

    /**
     * Test filter by attribute partial match
     */
    public function testFilterByAttributePartialMatch(): void
    {
        $this->exec('attributes list --attribute Route');
        $this->assertExitCode(CommandInterface::CODE_SUCCESS);
        $this->assertOutputContains('TestRoute');
    }

    /**
     * Test filter by class name
     */
    public function testFilterByClassName(): void
    {
        $this->exec('attributes list --class TestController');
        $this->assertExitCode(CommandInterface::CODE_SUCCESS);
        $this->assertOutputContains('TestController');
    }

    /**
     * Test filter by class short option
     */
    public function testFilterByClassShortOption(): void
    {
        $this->exec('attributes list -c TestController');
        $this->assertExitCode(CommandInterface::CODE_SUCCESS);
        $this->assertOutputContains('TestController');
    }

    /**
     * Test filter by class partial match
     */
    public function testFilterByClassPartialMatch(): void
    {
        $this->exec('attributes list --class Controller');
        $this->assertExitCode(CommandInterface::CODE_SUCCESS);
        $this->assertOutputContains('TestController');
    }

    /**
     * Test filter by namespace
     */
    public function testFilterByNamespace(): void
    {
        $this->exec('attributes list --namespace TestApp');
        $this->assertExitCode(CommandInterface::CODE_SUCCESS);
    }

    /**
     * Test filter by namespace with wildcard
     */
    public function testFilterByNamespaceWithWildcard(): void
    {
        $this->exec('attributes list --namespace "TestApp\\\\Attribute\\\\*"');
        $this->assertExitCode(CommandInterface::CODE_SUCCESS);
    }

    /**
     * Test filter by namespace short option
     */
    public function testFilterByNamespaceShortOption(): void
    {
        $this->exec('attributes list -n TestApp');
        $this->assertExitCode(CommandInterface::CODE_SUCCESS);
    }

    /**
     * Test filter by type
     */
    public function testFilterByType(): void
    {
        $this->exec('attributes list --type method');
        $this->assertExitCode(CommandInterface::CODE_SUCCESS);
        $this->assertOutputContains('method');
    }

    /**
     * Test filter by type short option
     */
    public function testFilterByTypeShortOption(): void
    {
        $this->exec('attributes list -t method');
        $this->assertExitCode(CommandInterface::CODE_SUCCESS);
        $this->assertOutputContains('method');
    }

    /**
     * Test filter by all type values
     */
    public function testFilterByTypeAllValues(): void
    {
        $types = ['class', 'method', 'property', 'parameter', 'constant'];

        foreach ($types as $type) {
            $this->exec('attributes list --type ' . $type);
            $this->assertExitCode(CommandInterface::CODE_SUCCESS);
        }
    }

    /**
     * Test filter by invalid type
     */
    public function testFilterByTypeInvalid(): void
    {
        $this->exec('attributes list --type invalid_type');
        $this->assertExitCode(CommandInterface::CODE_SUCCESS);
        $this->assertErrorContains('No attributes found');
    }

    /**
     * Test filter by plugin
     */
    public function testFilterByPlugin(): void
    {
        $this->exec('attributes list --plugin TestPlugin');
        $this->assertExitCode(CommandInterface::CODE_SUCCESS);
    }

    /**
     * Test filter by plugin short option
     */
    public function testFilterByPluginShortOption(): void
    {
        $this->exec('attributes list -p TestPlugin');
        $this->assertExitCode(CommandInterface::CODE_SUCCESS);
    }

    /**
     * Test multiple filters
     */
    public function testMultipleFilters(): void
    {
        $this->exec('attributes list --type method --class TestController');
        $this->assertExitCode(CommandInterface::CODE_SUCCESS);
    }

    /**
     * Test config option
     */
    public function testConfigOption(): void
    {
        Resolver::setConfig('custom', [
            'paths' => ['Attribute/Resolver/Fixture/*.php'],
            'basePath' => APP,
            'artifact' => null,
        ]);

        $this->exec('attributes list --config custom');
        $this->assertExitCode(CommandInterface::CODE_SUCCESS);

        Resolver::drop('custom');
    }

    /**
     * Test plugin column shows dash
     */
    public function testPluginColumnShowsDash(): void
    {
        $this->exec('attributes list');
        $this->assertOutputContains('-');
    }

    /**
     * Test option parser has all options
     */
    public function testOptionParserHasAllOptions(): void
    {
        $this->exec('attributes list --help');
        $this->assertOutputContains('attribute');
        $this->assertOutputContains('class');
        $this->assertOutputContains('namespace');
        $this->assertOutputContains('type');
        $this->assertOutputContains('plugin');
        $this->assertOutputContains('config');
    }

    /**
     * Test table output formats correctly
     */
    public function testTableOutputFormatsCorrectly(): void
    {
        $this->exec('attributes list');
        $this->assertExitCode(CommandInterface::CODE_SUCCESS);
        // Table should have pipe separators
        $this->assertOutputContains('|');
    }

    /**
     * Test attribute column shows FQDN
     */
    public function testAttributeColumnShowsFqdn(): void
    {
        $this->exec('attributes list --attribute TestRoute');
        $this->assertExitCode(CommandInterface::CODE_SUCCESS);
        $this->assertOutputContains('TestApp\Attribute\Resolver\TestRoute');
    }

    /**
     * Test class column shows FQDN
     */
    public function testClassColumnShowsFqdn(): void
    {
        $this->exec('attributes list --verbose --class TestController');
        $this->assertExitCode(CommandInterface::CODE_SUCCESS);
        $this->assertOutputContains('TestApp\Attribute\Resolver\Fixture\TestController');
    }

    /**
     * Test target column shows FQDN for class targets
     */
    public function testTargetColumnShowsFqdnForClassTargets(): void
    {
        $this->exec('attributes list --verbose --type class --class TestController');
        $this->assertExitCode(CommandInterface::CODE_SUCCESS);
        $this->assertOutputContains('TestApp\Attribute\Resolver\Fixture\TestController');
    }

    /**
     * Test target column shows simple name for method targets
     */
    public function testTargetColumnShowsSimpleNameForMethods(): void
    {
        $this->exec('attributes list --type method --class TestController');
        $this->assertExitCode(CommandInterface::CODE_SUCCESS);
        $this->assertOutputContains('publicMethod');
        $this->assertOutputNotContains('TestController::publicMethod');
    }

    /**
     * Test truncation is applied by default
     */
    public function testTruncationAppliedByDefault(): void
    {
        $this->exec('attributes list');
        $this->assertExitCode(CommandInterface::CODE_SUCCESS);
        // Long names should show truncation indicator
        $this->assertOutputContains('...');
    }

    /**
     * Test verbose option disables truncation
     */
    public function testVerboseOptionDisablesTruncation(): void
    {
        $this->exec('attributes list --verbose --class TestAnonymousClass');
        $this->assertExitCode(CommandInterface::CODE_SUCCESS);
        // With verbose, full class name should appear (no truncation for normal names)
        $this->assertOutputContains('TestApp\Attribute\Resolver\Fixture\TestAnonymousClass');
    }

    /**
     * Test verbose option shows full names
     */
    public function testVerboseOptionShowsFullNames(): void
    {
        $this->exec('attributes list --verbose --attribute TestRoute --type class');
        $this->assertExitCode(CommandInterface::CODE_SUCCESS);
        // Should contain full namespace paths without truncation
        $this->assertOutputContains('TestApp\Attribute\Resolver\TestRoute');
    }

    /**
     * Test truncateLeft works for long anonymous class names
     */
    public function testTruncationWorksForAnonymousClasses(): void
    {
        $this->exec('attributes list');
        $this->assertExitCode(CommandInterface::CODE_SUCCESS);
        // Anonymous classes should be truncated from the left
        // The output should contain the file reference but truncated
        if (str_contains($this->out->output(), 'class@anonymous')) {
            $this->assertOutputContains('...');
        }
    }

    /**
     * Test verbose option help text
     */
    public function testVerboseOptionHelpText(): void
    {
        $this->exec('attributes list --help');
        $this->assertExitCode(CommandInterface::CODE_SUCCESS);
        $this->assertOutputContains('verbose');
        $this->assertOutputContains('truncation');
    }
}
