<?php
// phpcs:disable PSR1.Classes.ClassDeclaration.MultipleClasses, Squiz.Classes.ClassFileName.NoMatch

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
namespace Cake\Test\TestCase\AttributeResolver\ValueObject;

use Attribute;
use Cake\AttributeResolver\Enum\AttributeTargetType;
use Cake\AttributeResolver\ValueObject\AttributeInfo;
use Cake\AttributeResolver\ValueObject\AttributeTarget;
use Cake\TestSuite\TestCase;
use InvalidArgumentException;
use RuntimeException;

/**
 * Test attribute for testing
 */
#[Attribute]
class TestAttribute
{
    public function __construct(
        public string $value = 'test',
        public int $number = 42,
    ) {
    }
}

/**
 * Another test attribute
 */
#[Attribute]
class AnotherAttribute
{
    public function __construct(public string $name)
    {
    }
}

/**
 * AttributeInfo Value Object Test
 */
class AttributeInfoTest extends TestCase
{
    /**
     * Test constructor and properties
     */
    public function testConstructor(): void
    {
        $target = new AttributeTarget(
            type: AttributeTargetType::METHOD,
            name: 'index',
            declaringClass: 'App\Controller\UsersController',
        );

        $info = new AttributeInfo(
            className: 'App\Controller\UsersController',
            attributeName: TestAttribute::class,
            arguments: ['value' => 'custom', 'number' => 100],
            filePath: '/app/src/Controller/UsersController.php',
            lineNumber: 42,
            target: $target,
            fileTime: 1234567890,
            pluginName: 'MyPlugin',
        );

        $this->assertSame('App\Controller\UsersController', $info->className);
        $this->assertSame(TestAttribute::class, $info->attributeName);
        $this->assertSame(['value' => 'custom', 'number' => 100], $info->arguments);
        $this->assertSame('/app/src/Controller/UsersController.php', $info->filePath);
        $this->assertSame(42, $info->lineNumber);
        $this->assertSame($target, $info->target);
        $this->assertSame(1234567890, $info->fileTime);
        $this->assertSame('MyPlugin', $info->pluginName);
    }

    /**
     * Test constructor with defaults
     */
    public function testConstructorDefaults(): void
    {
        $target = new AttributeTarget(
            type: AttributeTargetType::CLASS_TYPE,
            name: 'MyClass',
        );

        $info = new AttributeInfo(
            className: 'App\MyClass',
            attributeName: TestAttribute::class,
            arguments: [],
            filePath: '/app/src/MyClass.php',
            lineNumber: 10,
            target: $target,
        );

        $this->assertSame(0, $info->fileTime);
        $this->assertNull($info->pluginName);
    }

    /**
     * Test toArray serialization
     */
    public function testToArray(): void
    {
        $target = new AttributeTarget(
            type: AttributeTargetType::PROPERTY,
            name: 'title',
            declaringClass: 'App\Model\Entity\Article',
        );

        $info = new AttributeInfo(
            className: 'App\Model\Entity\Article',
            attributeName: TestAttribute::class,
            arguments: ['value' => 'test'],
            filePath: '/app/src/Model/Entity/Article.php',
            lineNumber: 25,
            target: $target,
            fileTime: 9876543210,
            pluginName: 'Blog',
        );

        $expected = [
            'className' => 'App\Model\Entity\Article',
            'attributeName' => TestAttribute::class,
            'arguments' => ['value' => 'test'],
            'filePath' => '/app/src/Model/Entity/Article.php',
            'lineNumber' => 25,
            'target' => [
                'type' => 'property',
                'name' => 'title',
                'declaringClass' => 'App\Model\Entity\Article',
                'isDeclaringClassAbstract' => false,
                'declaringClassType' => 'class',
                'methodVisibility' => null,
            ],
            'fileTime' => 9876543210,
            'pluginName' => 'Blog',
        ];

        $this->assertSame($expected, $info->toArray());
    }

    /**
     * Test fromArray deserialization
     */
    public function testFromArray(): void
    {
        $data = [
            'className' => 'App\Controller\ArticlesController',
            'attributeName' => TestAttribute::class,
            'arguments' => ['value' => 'index'],
            'filePath' => '/app/src/Controller/ArticlesController.php',
            'lineNumber' => 50,
            'target' => [
                'type' => 'method',
                'name' => 'index',
                'declaringClass' => 'App\Controller\ArticlesController',
            ],
            'fileTime' => 1111111111,
            'pluginName' => 'Admin',
        ];

        $info = AttributeInfo::fromArray($data);

        $this->assertSame('App\Controller\ArticlesController', $info->className);
        $this->assertSame(TestAttribute::class, $info->attributeName);
        $this->assertSame(['value' => 'index'], $info->arguments);
        $this->assertSame('/app/src/Controller/ArticlesController.php', $info->filePath);
        $this->assertSame(50, $info->lineNumber);
        $this->assertSame(AttributeTargetType::METHOD, $info->target->type);
        $this->assertSame('index', $info->target->name);
        $this->assertSame(1111111111, $info->fileTime);
        $this->assertSame('Admin', $info->pluginName);
    }

    /**
     * Test round-trip serialization
     */
    public function testRoundTripSerialization(): void
    {
        $target = new AttributeTarget(
            type: AttributeTargetType::PARAMETER,
            name: 'userId',
            declaringClass: 'App\Controller\UsersController',
        );

        $original = new AttributeInfo(
            className: 'App\Controller\UsersController',
            attributeName: TestAttribute::class,
            arguments: ['value' => 'param', 'number' => 99],
            filePath: '/app/src/Controller/UsersController.php',
            lineNumber: 100,
            target: $target,
            fileTime: 3333333333,
            pluginName: 'Users',
        );

        $array = $original->toArray();
        $restored = AttributeInfo::fromArray($array);

        $this->assertEquals($original, $restored);
    }

    /**
     * Test getInstance creates attribute instance
     */
    public function testGetInstance(): void
    {
        $target = new AttributeTarget(
            type: AttributeTargetType::CLASS_TYPE,
            name: 'TestClass',
        );

        $info = new AttributeInfo(
            className: 'TestClass',
            attributeName: TestAttribute::class,
            arguments: ['value' => 'custom', 'number' => 77],
            filePath: '/test.php',
            lineNumber: 1,
            target: $target,
        );

        $instance = $info->getInstance();

        $this->assertInstanceOf(TestAttribute::class, $instance);
        $this->assertSame('custom', $instance->value);
        $this->assertSame(77, $instance->number);
    }

    /**
     * Test getInstance with expected class validation
     */
    public function testGetInstanceWithExpectedClass(): void
    {
        $target = new AttributeTarget(
            type: AttributeTargetType::CLASS_TYPE,
            name: 'TestClass',
        );

        $info = new AttributeInfo(
            className: 'TestClass',
            attributeName: TestAttribute::class,
            arguments: [],
            filePath: '/test.php',
            lineNumber: 1,
            target: $target,
        );

        $instance = $info->getInstance(TestAttribute::class);

        $this->assertInstanceOf(TestAttribute::class, $instance);
    }

    /**
     * Test getInstance throws exception for non-existent class
     */
    public function testGetInstanceNonExistentClass(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Attribute class "NonExistent\Class" does not exist');

        $target = new AttributeTarget(
            type: AttributeTargetType::CLASS_TYPE,
            name: 'TestClass',
        );

        $info = new AttributeInfo(
            className: 'TestClass',
            attributeName: 'NonExistent\Class',
            arguments: [],
            filePath: '/test.php',
            lineNumber: 1,
            target: $target,
        );

        $info->getInstance();
    }

    /**
     * Test getInstance throws exception when expected class doesn't match
     */
    public function testGetInstanceWrongExpectedClass(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/is not an instance of/');

        $target = new AttributeTarget(
            type: AttributeTargetType::CLASS_TYPE,
            name: 'TestClass',
        );

        $info = new AttributeInfo(
            className: 'TestClass',
            attributeName: TestAttribute::class,
            arguments: [],
            filePath: '/test.php',
            lineNumber: 1,
            target: $target,
        );

        $info->getInstance(AnotherAttribute::class);
    }

    /**
     * Test isInstanceOf method
     */
    public function testIsInstanceOf(): void
    {
        $target = new AttributeTarget(
            type: AttributeTargetType::CLASS_TYPE,
            name: 'TestClass',
        );

        $info = new AttributeInfo(
            className: 'TestClass',
            attributeName: TestAttribute::class,
            arguments: [],
            filePath: '/test.php',
            lineNumber: 1,
            target: $target,
        );

        $this->assertTrue($info->isInstanceOf(TestAttribute::class));
        $this->assertFalse($info->isInstanceOf(AnotherAttribute::class));
    }

    /**
     * Test serialize/unserialize round-trip via PHP serialize()
     */
    public function testPhpSerializeRoundTrip(): void
    {
        $target = new AttributeTarget(
            type: AttributeTargetType::METHOD,
            name: 'view',
            declaringClass: 'App\Controller\ArticlesController',
        );

        $original = new AttributeInfo(
            className: 'App\Controller\ArticlesController',
            attributeName: TestAttribute::class,
            arguments: ['value' => 'test', 'number' => 50],
            filePath: '/app/src/Controller/ArticlesController.php',
            lineNumber: 100,
            target: $target,
            fileTime: 1111111111,
            pluginName: 'Articles',
        );

        $serialized = serialize($original);
        $restored = unserialize($serialized);

        $this->assertInstanceOf(AttributeInfo::class, $restored);
        $this->assertSame($original->className, $restored->className);
        $this->assertSame($original->attributeName, $restored->attributeName);
        $this->assertSame($original->arguments, $restored->arguments);
        $this->assertSame($original->filePath, $restored->filePath);
        $this->assertSame($original->lineNumber, $restored->lineNumber);
        $this->assertSame($original->fileTime, $restored->fileTime);
        $this->assertSame($original->pluginName, $restored->pluginName);

        // Check nested AttributeTarget was also serialized correctly
        $this->assertInstanceOf(AttributeTarget::class, $restored->target);
        $this->assertSame($original->target->type, $restored->target->type);
        $this->assertSame($original->target->name, $restored->target->name);
        $this->assertSame($original->target->declaringClass, $restored->target->declaringClass);
    }

    /**
     * Test serialize/unserialize with null pluginName
     */
    public function testPhpSerializeWithNullPluginName(): void
    {
        $target = new AttributeTarget(
            type: AttributeTargetType::CLASS_TYPE,
            name: 'MyClass',
        );

        $original = new AttributeInfo(
            className: 'App\MyClass',
            attributeName: TestAttribute::class,
            arguments: [],
            filePath: '/app/src/MyClass.php',
            lineNumber: 5,
            target: $target,
            fileTime: 0,
        );

        $serialized = serialize($original);
        $restored = unserialize($serialized);

        $this->assertInstanceOf(AttributeInfo::class, $restored);
        $this->assertNull($restored->pluginName);
        $this->assertSame(0, $restored->fileTime);
    }

    /**
     * Test json_encode integration
     */
    public function testJsonEncode(): void
    {
        $target = new AttributeTarget(
            type: AttributeTargetType::METHOD,
            name: 'index',
            declaringClass: 'App\Controller\UsersController',
        );

        $info = new AttributeInfo(
            className: 'App\Controller\UsersController',
            attributeName: TestAttribute::class,
            arguments: ['value' => 'test', 'number' => 42],
            filePath: '/app/src/Controller/UsersController.php',
            lineNumber: 50,
            target: $target,
            fileTime: 1234567890,
            pluginName: 'MyPlugin',
        );

        $json = json_encode($info);
        $decoded = json_decode($json, true);

        $this->assertSame($info->toArray(), $decoded);
    }
}
