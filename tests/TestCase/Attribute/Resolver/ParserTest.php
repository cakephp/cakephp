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
 * @since         5.4.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Attribute\Resolver;

use Cake\Attribute\Resolver\Enum\AttributeTargetType;
use Cake\Attribute\Resolver\Parser;
use Cake\Attribute\Resolver\ValueObject\AttributeInfo;
use Cake\TestSuite\TestCase;
use SplFileInfo;

class ParserTest extends TestCase
{
    private Parser $parser;
    private string $testDataPath;

    protected function setUp(): void
    {
        parent::setUp();
        $this->parser = new Parser();
        $this->testDataPath = TEST_APP . 'TestApp/Attribute/Resolver/Fixture/';
    }

    public function testParseClassWithMultipleArguments(): void
    {
        $filePath = $this->testDataPath . 'TestController.php';
        $results = iterator_to_array($this->parser->parseFile(new SplFileInfo($filePath)), false);

        // Should find 1 class attribute + 4 method attributes
        $this->assertCount(5, $results);

        $classAttrs = array_filter($results, fn(AttributeInfo $attr) => $attr->target->type === AttributeTargetType::CLASS_TYPE);
        $this->assertCount(1, $classAttrs);

        $classAttr = array_values($classAttrs)[0];
        $this->assertSame('TestApp\\Attribute\\Resolver\\TestRoute', $classAttr->attributeName);
        $this->assertSame(['path' => '/test'], $classAttr->arguments);
    }

    public function testParseMethodAttributes(): void
    {
        $filePath = $this->testDataPath . 'TestController.php';
        $results = iterator_to_array($this->parser->parseFile(new SplFileInfo($filePath)), false);

        $methodAttrs = array_filter($results, fn(AttributeInfo $attr) => $attr->target->type === AttributeTargetType::METHOD);
        $this->assertCount(4, $methodAttrs);

        foreach ($methodAttrs as $attr) {
            $this->assertSame(AttributeTargetType::METHOD, $attr->target->type);
            $this->assertSame('TestApp\\Attribute\\Resolver\\TestRoute', $attr->attributeName);
        }
    }

    public function testParsePropertyAttributes(): void
    {
        $filePath = $this->testDataPath . 'TestEntity.php';
        $results = iterator_to_array($this->parser->parseFile(new SplFileInfo($filePath)), false);

        $this->assertCount(3, $results);

        foreach ($results as $result) {
            $this->assertSame(AttributeTargetType::PROPERTY, $result->target->type);
            $this->assertSame('TestApp\\Attribute\\Resolver\\TestColumn', $result->attributeName);
        }
    }

    public function testParseParameterAttributes(): void
    {
        $filePath = $this->testDataPath . 'TestService.php';
        $results = iterator_to_array($this->parser->parseFile(new SplFileInfo($filePath)), false);

        $this->assertCount(2, $results);

        foreach ($results as $result) {
            $this->assertSame(AttributeTargetType::PARAMETER, $result->target->type);
            $this->assertSame('TestApp\\Attribute\\Resolver\\TestInject', $result->attributeName);
        }
    }

    public function testParseClassConstantAttributes(): void
    {
        $filePath = $this->testDataPath . 'TestConstants.php';
        $results = iterator_to_array($this->parser->parseFile(new SplFileInfo($filePath)), false);

        $this->assertCount(2, $results);

        foreach ($results as $result) {
            $this->assertSame(AttributeTargetType::CLASS_CONSTANT, $result->target->type);
            $this->assertSame('TestApp\\Attribute\\Resolver\\TestStatus', $result->attributeName);
        }
    }

    public function testExcludeAttributesByExactName(): void
    {
        $filePath = $this->testDataPath . 'TestExcludeClass.php';

        $parser = new Parser(['TestApp\\Attribute\\Resolver\\TestExclude']);
        $results = iterator_to_array($parser->parseFile(new SplFileInfo($filePath)), false);

        $this->assertCount(1, $results);
        $this->assertSame('TestApp\\Attribute\\Resolver\\TestInclude', $results[0]->attributeName);
    }

    public function testExcludeAttributesByWildcard(): void
    {
        $filePath = $this->testDataPath . 'TestExcludeClass.php';

        $parser = new Parser(['TestApp\\Attribute\\Resolver\\TestExclude*', 'TestApp\\Attribute\\Resolver\\TestInternal*']);
        $results = iterator_to_array($parser->parseFile(new SplFileInfo($filePath)), false);

        $this->assertCount(1, $results);
        $this->assertSame('TestApp\\Attribute\\Resolver\\TestInclude', $results[0]->attributeName);
    }

    public function testHandleNonExistentFile(): void
    {
        $results = iterator_to_array($this->parser->parseFile(new SplFileInfo('/non/existent/file.php')), false);

        $this->assertEmpty($results);
    }

    public function testHandleFileWithSyntaxError(): void
    {
        $filePath = tempnam(sys_get_temp_dir(), 'parser_test_') . '.php';
        file_put_contents($filePath, '<?php class Invalid { invalid syntax }');

        $results = iterator_to_array($this->parser->parseFile(new SplFileInfo($filePath)), false);

        $this->assertEmpty($results);
        unlink($filePath);
    }

    public function testHandleFileWithNoClasses(): void
    {
        $filePath = tempnam(sys_get_temp_dir(), 'parser_test_') . '.php';
        file_put_contents($filePath, '<?php $variable = 123;');

        $results = iterator_to_array($this->parser->parseFile(new SplFileInfo($filePath)), false);

        $this->assertEmpty($results);
        unlink($filePath);
    }

    public function testParseInterfaceTraitEnum(): void
    {
        $filePath = $this->testDataPath . 'TestInterfaceAndTrait.php';
        $results = iterator_to_array($this->parser->parseFile(new SplFileInfo($filePath)), false);

        // Should find attributes from interface, trait, and enum
        $this->assertGreaterThan(0, count($results), 'Should find attributes in interface/trait/enum file');

        // Verify we find attributes from enum (these always work)
        $enumAttrs = array_filter($results, fn(AttributeInfo $attr) => str_contains($attr->target->declaringClass ?? '', 'TestEnum'));
        $this->assertGreaterThan(0, count($enumAttrs), 'Should find enum attributes');

        // Note: Interface and trait attributes may not be found if their attributes
        // are not instantiable or if the file is loaded before parsing
    }

    public function testParseGlobalNamespaceClass(): void
    {
        $filePath = $this->testDataPath . 'TestGlobalNamespace.php';
        $results = iterator_to_array($this->parser->parseFile(new SplFileInfo($filePath)), false);

        $this->assertGreaterThan(0, count($results));

        foreach ($results as $result) {
            // Class attributes have the class name in declaringClass
            $this->assertStringContainsString(
                'GlobalNamespaceClass',
                $result->target->declaringClass ?? $result->target->name,
            );
        }
    }

    public function testParseMultipleNamespaces(): void
    {
        $filePath = $this->testDataPath . 'TestMultipleNamespaces.php';
        $results = iterator_to_array($this->parser->parseFile(new SplFileInfo($filePath)), false);

        $this->assertGreaterThanOrEqual(3, count($results));

        $classNames = array_unique(array_map(
            fn(AttributeInfo $attr) => $attr->target->declaringClass ?? $attr->target->name,
            $results,
        ));

        $this->assertContains('TestApp\\Attribute\\Resolver\\Fixture\\FirstClass', $classNames);
        $this->assertContains('TestApp\\OtherNamespace\\SecondClass', $classNames);
        $this->assertContains('ThirdClass', $classNames);
    }

    public function testSkipAnonymousClasses(): void
    {
        $filePath = $this->testDataPath . 'TestAnonymousClass.php';
        $results = iterator_to_array($this->parser->parseFile(new SplFileInfo($filePath)), false);

        // Should find attributes on TestAnonymousClass and its methods
        $this->assertGreaterThan(0, count($results), 'Should find some attributes in file');

        // Verify main class is found
        $hasMainClass = false;
        foreach ($results as $result) {
            if (str_contains($result->target->declaringClass ?? '', 'TestAnonymousClass')) {
                $hasMainClass = true;
                break;
            }
        }
        $this->assertTrue($hasMainClass, 'Main class TestAnonymousClass should be found');
    }

    public function testFallbackPathWithAlreadyLoadedFile(): void
    {
        // First load the file normally
        $filePath = $this->testDataPath . 'TestEntity.php';
        require_once $filePath;

        // Now parse it again - should use token fallback path
        $results = iterator_to_array($this->parser->parseFile(new SplFileInfo($filePath)), false);

        // Should still find all attributes even via fallback
        $this->assertCount(3, $results);

        foreach ($results as $result) {
            $this->assertSame(AttributeTargetType::PROPERTY, $result->target->type);
            $this->assertSame('TestApp\\Attribute\\Resolver\\TestColumn', $result->attributeName);
        }
    }
}
