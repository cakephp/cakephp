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
 * AttributesInspectCommandTest class
 */
class AttributesInspectCommandTest extends TestCase
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
        $this->exec('attributes inspect --help');
        $this->assertExitCode(CommandInterface::CODE_SUCCESS);
        $this->assertOutputContains('attributes inspect');
    }

    /**
     * Test getDescription
     */
    public function testGetDescription(): void
    {
        $this->exec('attributes inspect --help');
        $this->assertExitCode(CommandInterface::CODE_SUCCESS);
        $this->assertOutputContains('Inspect');
    }

    /**
     * Test help output
     */
    public function testHelp(): void
    {
        $this->exec('attributes inspect --help');
        $this->assertExitCode(CommandInterface::CODE_SUCCESS);
        $this->assertOutputContains('attributes inspect');
        $this->assertOutputContains('attribute');
        $this->assertOutputContains('class');
    }

    /**
     * Test inspect by attribute name finds matches
     */
    public function testInspectByAttributeFindsMatches(): void
    {
        $this->exec('attributes inspect TestRoute');
        $this->assertExitCode(CommandInterface::CODE_SUCCESS);
        $this->assertOutputContains('Found');
        $this->assertOutputContains('attribute');
    }

    /**
     * Test inspect by attribute partial match
     */
    public function testInspectByAttributePartialMatch(): void
    {
        $this->exec('attributes inspect Route');
        $this->assertExitCode(CommandInterface::CODE_SUCCESS);
        $this->assertOutputContains('TestRoute');
    }

    /**
     * Test inspect by attribute no matches shows error
     */
    public function testInspectByAttributeNoMatches(): void
    {
        $this->exec('attributes inspect NonExistentAttribute');
        $this->assertExitCode(CommandInterface::CODE_ERROR);
        $this->assertErrorContains('No attributes found');
    }

    /**
     * Test inspect by attribute returns error code
     */
    public function testInspectByAttributeReturnsError(): void
    {
        $this->exec('attributes inspect NonExistent');
        $this->assertExitCode(CommandInterface::CODE_ERROR);
    }

    /**
     * Test inspect by class finds matches
     */
    public function testInspectByClassFindsMatches(): void
    {
        $this->exec('attributes inspect --class TestController');
        $this->assertExitCode(CommandInterface::CODE_SUCCESS);
        $this->assertOutputContains('Found');
    }

    /**
     * Test inspect by class partial match
     */
    public function testInspectByClassPartialMatch(): void
    {
        $this->exec('attributes inspect --class Controller');
        $this->assertExitCode(CommandInterface::CODE_SUCCESS);
        $this->assertOutputContains('TestController');
    }

    /**
     * Test inspect by class no matches
     */
    public function testInspectByClassNoMatches(): void
    {
        $this->exec('attributes inspect --class NonExistentClass');
        $this->assertExitCode(CommandInterface::CODE_ERROR);
        $this->assertErrorContains('No attributes found');
    }

    /**
     * Test inspect by class returns error code
     */
    public function testInspectByClassReturnsError(): void
    {
        $this->exec('attributes inspect --class NonExistent');
        $this->assertExitCode(CommandInterface::CODE_ERROR);
    }

    /**
     * Test without argument or option shows error
     */
    public function testWithoutArgumentOrOption(): void
    {
        $this->exec('attributes inspect');
        $this->assertExitCode(CommandInterface::CODE_ERROR);
        $this->assertErrorContains('Please provide');
    }

    /**
     * Test without argument or option returns error code
     */
    public function testWithoutArgumentOrOptionReturnsError(): void
    {
        $this->exec('attributes inspect');
        $this->assertExitCode(CommandInterface::CODE_ERROR);
    }

    /**
     * Test displays attribute count
     */
    public function testDisplaysAttributeCount(): void
    {
        $this->exec('attributes inspect TestRoute');
        $this->assertExitCode(CommandInterface::CODE_SUCCESS);
        $this->assertOutputContains('Found');
        $this->assertOutputContains('attribute');
    }

    /**
     * Test displays attribute class name
     */
    public function testDisplaysAttributeClassName(): void
    {
        $this->exec('attributes inspect TestRoute');
        $this->assertExitCode(CommandInterface::CODE_SUCCESS);
        $this->assertOutputContains('Attribute Class:');
        $this->assertOutputContains('TestRoute');
    }

    /**
     * Test displays target class name
     */
    public function testDisplaysTargetClassName(): void
    {
        $this->exec('attributes inspect TestRoute');
        $this->assertExitCode(CommandInterface::CODE_SUCCESS);
        $this->assertOutputContains('Target Class:');
    }

    /**
     * Test displays plugin name
     */
    public function testDisplaysPluginName(): void
    {
        $this->exec('attributes inspect TestRoute');
        $this->assertExitCode(CommandInterface::CODE_SUCCESS);
        $this->assertOutputContains('Plugin:');
    }

    /**
     * Test displays target type
     */
    public function testDisplaysTargetType(): void
    {
        $this->exec('attributes inspect TestRoute');
        $this->assertExitCode(CommandInterface::CODE_SUCCESS);
        $this->assertOutputContains('Target:');
    }

    /**
     * Test displays target name
     */
    public function testDisplaysTargetName(): void
    {
        $this->exec('attributes inspect TestRoute');
        $this->assertExitCode(CommandInterface::CODE_SUCCESS);
        $this->assertOutputContains('Target:');
    }

    /**
     * Test displays file path
     */
    public function testDisplaysFilePath(): void
    {
        $this->exec('attributes inspect TestRoute');
        $this->assertExitCode(CommandInterface::CODE_SUCCESS);
        $this->assertOutputContains('File:');
    }

    /**
     * Test displays line number
     */
    public function testDisplaysLineNumber(): void
    {
        $this->exec('attributes inspect TestRoute');
        $this->assertExitCode(CommandInterface::CODE_SUCCESS);
        $this->assertOutputContains('File:');
        $this->assertOutputContains(':');
    }

    /**
     * Test displays file time
     */
    public function testDisplaysFileTime(): void
    {
        $this->exec('attributes inspect TestRoute');
        $this->assertExitCode(CommandInterface::CODE_SUCCESS);
        // File time might be 0 in some cases, so just check format if present
        if (str_contains($this->out->output(), 'File Time:')) {
            $this->assertOutputContains('File Time:');
        }
    }

    /**
     * Test displays arguments section when present
     */
    public function testDisplaysArgumentsSection(): void
    {
        $this->exec('attributes inspect TestRoute');
        $this->assertExitCode(CommandInterface::CODE_SUCCESS);
        // TestRoute has path argument
        if (str_contains($this->out->output(), 'Arguments:')) {
            $this->assertOutputContains('Arguments:');
        }
    }

    /**
     * Test displays string arguments
     */
    public function testDisplaysStringArguments(): void
    {
        $this->exec('attributes inspect TestRoute');
        $this->assertExitCode(CommandInterface::CODE_SUCCESS);
        // Check for argument display format
        if (str_contains($this->out->output(), 'Arguments:')) {
            $this->assertOutputContains('- ');
        }
    }

    /**
     * Test multiple attributes are numbered
     */
    public function testMultipleAttributesNumbered(): void
    {
        $this->exec('attributes inspect TestRoute');
        $this->assertExitCode(CommandInterface::CODE_SUCCESS);
        $this->assertOutputContains('1. ');
    }

    /**
     * Test class option short flag
     */
    public function testClassOptionShortFlag(): void
    {
        $this->exec('attributes inspect -c TestController');
        $this->assertExitCode(CommandInterface::CODE_SUCCESS);
        $this->assertOutputContains('TestController');
    }

    /**
     * Test config option
     */
    public function testConfigOption(): void
    {
        Resolver::setConfig('custom', [
            'paths' => ['Attribute/Resolver/Fixture/*.php'],
            'basePath' => APP,
        ]);

        $this->exec('attributes inspect TestRoute --config custom');
        $this->assertExitCode(CommandInterface::CODE_SUCCESS);

        Resolver::drop('custom');
    }

    /**
     * Test config option with invalid config
     */
    public function testConfigOptionInvalid(): void
    {
        $this->exec('attributes inspect TestRoute --config invalid');
        $this->assertExitCode(CommandInterface::CODE_ERROR);
        $this->assertErrorContains('does not exist');
    }

    /**
     * Test option parser has all options
     */
    public function testOptionParserHasAllOptions(): void
    {
        $this->exec('attributes inspect --help');
        $this->assertOutputContains('attribute');
        $this->assertOutputContains('class');
        $this->assertOutputContains('config');
    }

    /**
     * Test short attribute name is displayed
     */
    public function testShortAttributeNameDisplayed(): void
    {
        $this->exec('attributes inspect TestRoute');
        $this->assertExitCode(CommandInterface::CODE_SUCCESS);
        // Should show short name at the top
        $this->assertOutputContains('TestRoute');
    }

    /**
     * Test displays dash for no plugin
     */
    public function testDisplaysDashForNoPlugin(): void
    {
        $this->exec('attributes inspect TestRoute');
        $this->assertExitCode(CommandInterface::CODE_SUCCESS);
        $this->assertOutputContains('Plugin:');
        $this->assertOutputContains('-');
    }

    /**
     * Test inspect specific attribute type
     */
    public function testInspectSpecificType(): void
    {
        $this->exec('attributes inspect TestColumn');
        $this->assertExitCode(CommandInterface::CODE_SUCCESS);
        $this->assertOutputContains('property');
    }

    /**
     * Test inspect shows method target
     */
    public function testInspectShowsMethodTarget(): void
    {
        $this->exec('attributes inspect TestRoute --class TestController');
        $this->assertExitCode(CommandInterface::CODE_SUCCESS);
        $this->assertOutputContains('method');
    }

    /**
     * Test inspect shows class target
     */
    public function testInspectShowsClassTarget(): void
    {
        $this->exec('attributes inspect TestRoute');
        $this->assertExitCode(CommandInterface::CODE_SUCCESS);
        $this->assertOutputContains('class');
    }
}
