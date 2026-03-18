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
namespace Cake\Test\TestCase\AttributeResolver;

use Cake\AttributeResolver\Enum\AttributeTargetType;
use Cake\AttributeResolver\Enum\DeclaringClassType;
use Cake\AttributeResolver\Enum\MethodVisibility;
use Cake\AttributeResolver\Parser;
use Cake\AttributeResolver\ValueObject\AttributeInfo;
use Cake\TestSuite\TestCase;
use SplFileInfo;
use TestApp\Attribute\Resolver\Enum\TestPriority;
use TestApp\Attribute\Resolver\ValueObject\TestConfig;

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

        $classAttrs = array_filter($results, fn(AttributeInfo $attr) => $attr->target->type === AttributeTargetType::CLASS_);
        $this->assertCount(1, $classAttrs);

        $classAttr = array_values($classAttrs)[0];
        $this->assertSame('TestApp\\Attribute\\Resolver\\TestRoute', $classAttr->attributeName);
        $this->assertSame(['path' => '/test'], $classAttr->arguments);
    }

    public function testParseFileCapturesAbstractClassMetadata(): void
    {
        $filePath = TEST_APP . 'TestApp/Controller/AttributeRoutingBaseController.php';
        $results = iterator_to_array($this->parser->parseFile(new SplFileInfo($filePath)), false);

        $this->assertNotEmpty($results);
        foreach ($results as $result) {
            $this->assertTrue($result->target->isDeclaringClassAbstract);
            $this->assertSame(DeclaringClassType::CLASS_, $result->target->declaringClassType);
        }
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

        $visibilityByMethod = [];
        foreach ($methodAttrs as $methodAttr) {
            $visibilityByMethod[$methodAttr->target->name] = $methodAttr->target->methodVisibility;
        }
        $this->assertSame(MethodVisibility::PUBLIC, $visibilityByMethod['publicMethod']);
        $this->assertSame(MethodVisibility::PROTECTED, $visibilityByMethod['protectedMethod']);
        $this->assertSame(MethodVisibility::PRIVATE, $visibilityByMethod['privateMethod']);
        $this->assertSame(MethodVisibility::PUBLIC, $visibilityByMethod['staticMethod']);
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
            $this->assertSame(AttributeTargetType::CONSTANT, $result->target->type);
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

    public function testSkipAnonymousClasses(): void
    {
        $filePath = $this->testDataPath . 'TestAnonymousClass.php';
        $results = iterator_to_array($this->parser->parseFile(new SplFileInfo($filePath)), false);

        // Should find attributes on TestAnonymousClass and its methods
        $this->assertGreaterThan(0, count($results), 'Should find some attributes in file');
        $hasMainClass = array_any($results, fn($result) => str_contains($result->target->declaringClass ?? '', 'TestAnonymousClass'));
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

    public function testComplexArgumentsWithObjects(): void
    {
        $filePath = $this->testDataPath . 'TestComplexArguments.php';
        $results = iterator_to_array($this->parser->parseFile(new SplFileInfo($filePath)), false);

        // Should find multiple attributes with complex arguments
        $this->assertGreaterThan(0, count($results));

        // Find the method with object argument
        $methodWithObject = array_filter(
            $results,
            fn(AttributeInfo $attr) => $attr->target->name === 'methodWithComplexAttributes',
        );
        $this->assertCount(1, $methodWithObject);

        $attr = array_values($methodWithObject)[0];
        $this->assertSame('TestApp\\Attribute\\Resolver\\TestComplexArgument', $attr->attributeName);
        $this->assertArrayHasKey('value', $attr->arguments);
        $this->assertArrayHasKey('object', $attr->arguments);
        $this->assertArrayHasKey('enum', $attr->arguments);
        $this->assertArrayHasKey('constant', $attr->arguments);

        // Verify object argument is captured
        $this->assertInstanceOf(TestConfig::class, $attr->arguments['object']);
        $this->assertSame('database', $attr->arguments['object']->name);
        $this->assertSame(['host' => 'localhost', 'port' => 3306], $attr->arguments['object']->options);
    }

    public function testComplexArgumentsWithEnums(): void
    {
        $filePath = $this->testDataPath . 'TestComplexArguments.php';
        $results = iterator_to_array($this->parser->parseFile(new SplFileInfo($filePath)), false);

        // Find attribute with enum argument
        $withEnum = array_filter(
            $results,
            fn(AttributeInfo $attr) => $attr->target->name === 'methodWithComplexAttributes',
        );
        $this->assertCount(1, $withEnum);

        $attr = array_values($withEnum)[0];
        $this->assertArrayHasKey('enum', $attr->arguments);
        $this->assertInstanceOf(TestPriority::class, $attr->arguments['enum']);
        $this->assertSame(TestPriority::HIGH, $attr->arguments['enum']);
    }

    public function testComplexArgumentsWithConstants(): void
    {
        $filePath = $this->testDataPath . 'TestComplexArguments.php';
        $results = iterator_to_array($this->parser->parseFile(new SplFileInfo($filePath)), false);

        // Find attribute with constant argument
        $withConstant = array_filter(
            $results,
            fn(AttributeInfo $attr) => $attr->target->name === 'methodWithComplexAttributes',
        );
        $this->assertCount(1, $withConstant);

        $attr = array_values($withConstant)[0];
        $this->assertArrayHasKey('constant', $attr->arguments);
        $this->assertSame(30, $attr->arguments['constant']); // DEFAULT_TIMEOUT value
    }

    public function testNestedObjectArguments(): void
    {
        $filePath = $this->testDataPath . 'TestComplexArguments.php';
        $results = iterator_to_array($this->parser->parseFile(new SplFileInfo($filePath)), false);

        // Find the method with nested objects
        $nestedObjects = array_filter(
            $results,
            fn(AttributeInfo $attr) => $attr->target->name === 'nestedObjects',
        );
        $this->assertCount(1, $nestedObjects);

        $attr = array_values($nestedObjects)[0];
        $this->assertArrayHasKey('object', $attr->arguments);
        $this->assertInstanceOf(TestConfig::class, $attr->arguments['object']);

        // Verify nested object structure
        $config = $attr->arguments['object'];
        $this->assertSame('nested_config', $config->name);
        $this->assertArrayHasKey('nested', $config->options);
        $this->assertInstanceOf(TestConfig::class, $config->options['nested']);
        $this->assertSame('deep', $config->options['nested']->name);
    }

    public function testMultipleComplexAttributes(): void
    {
        $filePath = $this->testDataPath . 'TestComplexArguments.php';
        $results = iterator_to_array($this->parser->parseFile(new SplFileInfo($filePath)), false);

        // Find method with multiple attributes
        $multipleAttrs = array_filter(
            $results,
            fn(AttributeInfo $attr) => $attr->target->name === 'multipleAttributes',
        );
        $this->assertCount(3, $multipleAttrs);

        // Verify each attribute type
        $attrArray = array_values($multipleAttrs);

        // One should have constant
        $hasConstant = array_any($attrArray, fn($attr) => isset($attr->arguments['constant']));
        $this->assertTrue($hasConstant);

        // One should have enum
        $hasEnum = array_any($attrArray, fn($attr) => isset($attr->arguments['enum']));
        $this->assertTrue($hasEnum);

        // One should have object
        $hasObject = array_any($attrArray, fn($attr) => isset($attr->arguments['object']));
        $this->assertTrue($hasObject);
    }

    public function testParseInterface(): void
    {
        $filePath = $this->testDataPath . 'TestInterface.php';
        $results = iterator_to_array($this->parser->parseFile(new SplFileInfo($filePath)), false);

        // Should find interface-level and method attributes
        $this->assertGreaterThan(0, count($results));

        $interfaceAttrs = array_filter($results, fn(AttributeInfo $attr) => $attr->target->type === AttributeTargetType::CLASS_);
        $this->assertCount(1, $interfaceAttrs);
        $this->assertSame(
            DeclaringClassType::INTERFACE,
            array_values($interfaceAttrs)[0]->target->declaringClassType,
        );

        $methodAttrs = array_filter($results, fn(AttributeInfo $attr) => $attr->target->type === AttributeTargetType::METHOD);
        $this->assertCount(1, $methodAttrs);
    }

    public function testParseTrait(): void
    {
        $filePath = $this->testDataPath . 'TestTrait.php';
        $results = iterator_to_array($this->parser->parseFile(new SplFileInfo($filePath)), false);

        // Should find trait-level and method attributes
        $this->assertGreaterThan(0, count($results));

        $traitAttrs = array_filter($results, fn(AttributeInfo $attr) => $attr->target->type === AttributeTargetType::CLASS_);
        $this->assertCount(1, $traitAttrs);
        $this->assertSame(
            DeclaringClassType::TRAIT,
            array_values($traitAttrs)[0]->target->declaringClassType,
        );

        $methodAttrs = array_filter($results, fn(AttributeInfo $attr) => $attr->target->type === AttributeTargetType::METHOD);
        $this->assertCount(1, $methodAttrs);
    }

    public function testParseEnum(): void
    {
        $filePath = $this->testDataPath . 'TestEnum.php';
        $results = iterator_to_array($this->parser->parseFile(new SplFileInfo($filePath)), false);

        // Should find enum-level and case attributes
        $this->assertGreaterThan(0, count($results));

        $enumAttrs = array_filter($results, fn(AttributeInfo $attr) => $attr->target->type === AttributeTargetType::CLASS_);
        $this->assertCount(1, $enumAttrs);
        $this->assertSame(
            DeclaringClassType::ENUM,
            array_values($enumAttrs)[0]->target->declaringClassType,
        );

        // Enum cases are treated as class constants
        $caseAttrs = array_filter($results, fn(AttributeInfo $attr) => $attr->target->type === AttributeTargetType::CONSTANT);
        $this->assertCount(2, $caseAttrs);
    }

    public function testAttributeWithoutConstructor(): void
    {
        $filePath = $this->testDataPath . 'TestClassWithNoConstructorAttribute.php';
        $results = iterator_to_array($this->parser->parseFile(new SplFileInfo($filePath)), false);

        // Should handle attributes without constructors
        $this->assertCount(2, $results);

        foreach ($results as $result) {
            $this->assertSame('TestApp\\Attribute\\Resolver\\TestAttributeWithoutConstructor', $result->attributeName);
            $this->assertEmpty($result->arguments);
        }
    }

    public function testNonExistentAttributeClass(): void
    {
        // Create a temporary file with an attribute that doesn't exist
        $filePath = tempnam(sys_get_temp_dir(), 'parser_test_') . '.php';
        $content = <<<'PHP'
<?php
namespace TestApp\Temp;

use NonExistent\AttributeClass;

#[AttributeClass('test')]
class TempClass {
}
PHP;
        file_put_contents($filePath, $content);

        // Manually include the file to load the class
        include $filePath;

        $results = iterator_to_array($this->parser->parseFile(new SplFileInfo($filePath)), false);

        // Should still parse but with raw arguments (fallback)
        $this->assertCount(1, $results);
        $this->assertSame('NonExistent\\AttributeClass', $results[0]->attributeName);
        // Arguments are in positional form when constructor doesn't exist
        $this->assertEquals(['test'], array_values($results[0]->arguments));

        unlink($filePath);
    }
}
