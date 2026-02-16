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

use AttributeResolver\AttributeResolver;
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

        AttributeResolver::setConfig('default', [
            'paths' => [
                'Attribute/Resolver/Fixture/*.php',
            ],
            'basePath' => APP,
            'excludePaths' => [],
            'excludeAttributes' => [],
        ]);
    }

    /**
     * tearDown method
     */
    protected function tearDown(): void
    {
        parent::tearDown();
        AttributeResolver::drop('default');
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
        AttributeResolver::drop('emptylist');

        AttributeResolver::setConfig('emptylist', [
            'paths' => ['*.php'],
            'basePath' => $emptyDir,
        ]);

        $this->exec('attributes list --config emptylist');
        // When empty, warning should be shown (it's in stderr, not stdout)
        $this->assertErrorContains('No attributes found');

        AttributeResolver::drop('emptylist');
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
        AttributeResolver::drop('emptylist2');

        AttributeResolver::setConfig('emptylist2', [
            'paths' => ['*.php'],
            'basePath' => $emptyDir,
        ]);

        $this->exec('attributes list --config emptylist2');
        $this->assertExitCode(CommandInterface::CODE_SUCCESS);

        AttributeResolver::drop('emptylist2');
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
        AttributeResolver::setConfig('custom', [
            'paths' => ['Attribute/Resolver/Fixture/*.php'],
            'basePath' => APP,
        ]);

        $this->exec('attributes list --config custom');
        $this->assertExitCode(CommandInterface::CODE_SUCCESS);

        AttributeResolver::drop('custom');
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

    /**
     * Test format option appears in help
     */
    public function testFormatOptionInHelp(): void
    {
        $this->exec('attributes list --help');
        $this->assertExitCode(CommandInterface::CODE_SUCCESS);
        $this->assertOutputContains('format');
        $this->assertOutputContains('json');
    }

    /**
     * Test default format is text
     */
    public function testDefaultFormatIsText(): void
    {
        $this->exec('attributes list');
        $this->assertExitCode(CommandInterface::CODE_SUCCESS);
        // Text format should show table headers
        $this->assertOutputContains('Attribute');
        $this->assertOutputContains('Class');
        $this->assertOutputContains('Plugin');
    }

    /**
     * Test JSON format output
     */
    public function testJsonFormatOutput(): void
    {
        $this->exec('attributes list --format json');
        $this->assertExitCode(CommandInterface::CODE_SUCCESS);

        $output = $this->out->output();
        $decoded = json_decode($output, true);

        $this->assertIsArray($decoded);
        $this->assertNotEmpty($decoded);
    }

    /**
     * Test JSON format output structure
     */
    public function testJsonFormatOutputStructure(): void
    {
        $this->exec('attributes list --format json');
        $this->assertExitCode(CommandInterface::CODE_SUCCESS);

        $output = $this->out->output();
        $decoded = json_decode($output, true);

        $this->assertIsArray($decoded);

        // Check first item has required fields
        if ($decoded !== []) {
            $first = $decoded[0];
            $this->assertArrayHasKey('className', $first);
            $this->assertArrayHasKey('attributeName', $first);
            $this->assertArrayHasKey('arguments', $first);
            $this->assertArrayHasKey('filePath', $first);
            $this->assertArrayHasKey('lineNumber', $first);
            $this->assertArrayHasKey('target', $first);
            $this->assertArrayHasKey('fileTime', $first);
            $this->assertArrayHasKey('pluginName', $first);

            // Check target structure
            $this->assertArrayHasKey('type', $first['target']);
            $this->assertArrayHasKey('name', $first['target']);
            $this->assertArrayHasKey('declaringClass', $first['target']);
        }
    }

    /**
     * Test JSON format with short option
     */
    public function testJsonFormatWithShortOption(): void
    {
        $this->exec('attributes list -f json');
        $this->assertExitCode(CommandInterface::CODE_SUCCESS);

        $output = $this->out->output();
        $decoded = json_decode($output, true);

        $this->assertIsArray($decoded);
    }

    /**
     * Test JSON format with filter options
     */
    public function testJsonFormatWithFilters(): void
    {
        $this->exec('attributes list --format json --attribute TestRoute');
        $this->assertExitCode(CommandInterface::CODE_SUCCESS);

        $output = $this->out->output();
        $decoded = json_decode($output, true);

        $this->assertIsArray($decoded);
        // All items should have TestRoute in attribute name
        foreach ($decoded as $item) {
            $this->assertStringContainsString('TestRoute', $item['attributeName']);
        }
    }

    /**
     * Test JSON format does not include text headers
     */
    public function testJsonFormatNoTextHeaders(): void
    {
        $this->exec('attributes list --format json');
        $this->assertExitCode(CommandInterface::CODE_SUCCESS);

        // Should not contain text format headers
        $this->assertOutputNotContains('Found');
        $this->assertOutputNotContains('attributes:');
    }

    /**
     * Test JSON format with empty results
     */
    public function testJsonFormatEmptyResults(): void
    {
        $emptyDir = TMP . 'empty_json_test_' . uniqid() . DS;
        mkdir($emptyDir, 0777, true);

        AttributeResolver::drop('emptyjson');
        AttributeResolver::setConfig('emptyjson', [
            'paths' => ['*.php'],
            'basePath' => $emptyDir,
        ]);

        $this->exec('attributes list --config emptyjson --format json');
        $this->assertExitCode(CommandInterface::CODE_SUCCESS);

        $output = $this->out->output();
        $decoded = json_decode($output, true);

        $this->assertIsArray($decoded);
        $this->assertEmpty($decoded);

        AttributeResolver::drop('emptyjson');
        rmdir($emptyDir);
    }

    /**
     * Test JSON output is valid JSON
     */
    public function testJsonFormatValidJson(): void
    {
        $this->exec('attributes list --format json');
        $this->assertExitCode(CommandInterface::CODE_SUCCESS);

        $output = $this->out->output();

        // Should be valid JSON
        json_decode($output);
        $this->assertSame(JSON_ERROR_NONE, json_last_error());
    }

    /**
     * Test invalid format option
     */
    public function testInvalidFormatOption(): void
    {
        $this->exec('attributes list --format invalid');
        $this->assertExitCode(CommandInterface::CODE_ERROR);
        $this->assertErrorContains('format');
    }
}
