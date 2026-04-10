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
namespace Cake\Test\TestCase\AttributeResolver\ValueObject;

use Cake\AttributeResolver\Enum\AttributeTargetType;
use Cake\AttributeResolver\Enum\DeclaringClassType;
use Cake\AttributeResolver\Enum\MethodVisibility;
use Cake\AttributeResolver\ValueObject\AttributeTarget;
use Cake\TestSuite\TestCase;

/**
 * AttributeTarget Value Object Test
 */
class AttributeTargetTest extends TestCase
{
    /**
     * Test constructor and properties
     */
    public function testConstructor(): void
    {
        $target = new AttributeTarget(
            type: AttributeTargetType::METHOD,
            name: 'myMethod',
            declaringClass: 'App\Controller\UsersController',
        );

        $this->assertSame(AttributeTargetType::METHOD, $target->type);
        $this->assertSame('myMethod', $target->name);
        $this->assertSame('App\Controller\UsersController', $target->declaringClass);
        $this->assertFalse($target->isDeclaringClassAbstract);
        $this->assertSame(DeclaringClassType::CLASS_, $target->declaringClassType);
        $this->assertNull($target->methodVisibility);
    }

    /**
     * Test constructor with null declaring class
     */
    public function testConstructorNullDeclaringClass(): void
    {
        $target = new AttributeTarget(
            type: AttributeTargetType::CLASS_,
            name: 'MyClass',
        );

        $this->assertSame(AttributeTargetType::CLASS_, $target->type);
        $this->assertSame('MyClass', $target->name);
        $this->assertNull($target->declaringClass);
        $this->assertFalse($target->isDeclaringClassAbstract);
        $this->assertSame(DeclaringClassType::CLASS_, $target->declaringClassType);
        $this->assertNull($target->methodVisibility);
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

        $expected = [
            'type' => 'property',
            'name' => 'title',
            'declaringClass' => 'App\Model\Entity\Article',
            'isDeclaringClassAbstract' => false,
            'declaringClassType' => 'class',
            'methodVisibility' => null,
        ];

        $this->assertSame($expected, $target->toArray());
    }

    /**
     * Test toArray with null declaring class
     */
    public function testToArrayNullDeclaringClass(): void
    {
        $target = new AttributeTarget(
            type: AttributeTargetType::CLASS_,
            name: 'TestClass',
        );

        $expected = [
            'type' => 'class',
            'name' => 'TestClass',
            'declaringClass' => null,
            'isDeclaringClassAbstract' => false,
            'declaringClassType' => 'class',
            'methodVisibility' => null,
        ];

        $this->assertSame($expected, $target->toArray());
    }

    /**
     * Test fromArray deserialization
     */
    public function testFromArray(): void
    {
        $data = [
            'type' => 'method',
            'name' => 'index',
            'declaringClass' => 'App\Controller\ArticlesController',
            'isDeclaringClassAbstract' => true,
            'declaringClassType' => 'interface',
            'methodVisibility' => 'protected',
        ];

        $target = AttributeTarget::fromArray($data);

        $this->assertSame(AttributeTargetType::METHOD, $target->type);
        $this->assertSame('index', $target->name);
        $this->assertSame('App\Controller\ArticlesController', $target->declaringClass);
        $this->assertTrue($target->isDeclaringClassAbstract);
        $this->assertSame(DeclaringClassType::INTERFACE, $target->declaringClassType);
        $this->assertSame(MethodVisibility::PROTECTED, $target->methodVisibility);
    }

    /**
     * Test fromArray with null declaring class
     */
    public function testFromArrayNullDeclaringClass(): void
    {
        $data = [
            'type' => 'parameter',
            'name' => 'userId',
            'declaringClass' => null,
            'isDeclaringClassAbstract' => false,
            'declaringClassType' => 'class',
            'methodVisibility' => null,
        ];

        $target = AttributeTarget::fromArray($data);

        $this->assertSame(AttributeTargetType::PARAMETER, $target->type);
        $this->assertSame('userId', $target->name);
        $this->assertNull($target->declaringClass);
        $this->assertFalse($target->isDeclaringClassAbstract);
        $this->assertSame(DeclaringClassType::CLASS_, $target->declaringClassType);
        $this->assertNull($target->methodVisibility);
    }

    /**
     * Test round-trip serialization
     */
    public function testRoundTripSerialization(): void
    {
        $original = new AttributeTarget(
            type: AttributeTargetType::METHOD,
            name: 'save',
            declaringClass: 'App\Model\Table\ArticlesTable',
        );

        $array = $original->toArray();
        $restored = AttributeTarget::fromArray($array);

        $this->assertEquals($original, $restored);
        $this->assertSame($original->type, $restored->type);
        $this->assertSame($original->name, $restored->name);
        $this->assertSame($original->declaringClass, $restored->declaringClass);
    }

    /**
     * Test serialize/unserialize round-trip via PHP serialize()
     */
    public function testPhpSerializeRoundTrip(): void
    {
        $original = new AttributeTarget(
            type: AttributeTargetType::PROPERTY,
            name: 'email',
            declaringClass: 'App\Model\Entity\User',
        );

        $serialized = serialize($original);
        $restored = unserialize($serialized);

        $this->assertInstanceOf(AttributeTarget::class, $restored);
        $this->assertSame($original->type, $restored->type);
        $this->assertSame($original->name, $restored->name);
        $this->assertSame($original->declaringClass, $restored->declaringClass);
    }

    /**
     * Test serialize/unserialize with null declaringClass
     */
    public function testPhpSerializeWithNullDeclaringClass(): void
    {
        $original = new AttributeTarget(
            type: AttributeTargetType::CLASS_,
            name: 'MyClass',
        );

        $serialized = serialize($original);
        $restored = unserialize($serialized);

        $this->assertInstanceOf(AttributeTarget::class, $restored);
        $this->assertSame(AttributeTargetType::CLASS_, $restored->type);
        $this->assertSame('MyClass', $restored->name);
        $this->assertNull($restored->declaringClass);
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

        $json = json_encode($target);
        $decoded = json_decode($json, true);

        $this->assertSame($target->toArray(), $decoded);
    }

    /**
     * Test concrete class target is instantiable.
     */
    public function testIsInstantiableDeclaringTypeTrue(): void
    {
        $target = new AttributeTarget(
            type: AttributeTargetType::CLASS_,
            name: 'UsersController',
            declaringClass: 'App\Controller\UsersController',
            isDeclaringClassAbstract: false,
            declaringClassType: DeclaringClassType::CLASS_,
        );

        $this->assertTrue($target->isInstantiableDeclaringType());
    }

    /**
     * Test non-concrete declaring types are not instantiable.
     */
    public function testIsInstantiableDeclaringTypeFalseForAbstractOrNonClass(): void
    {
        $abstractTarget = new AttributeTarget(
            type: AttributeTargetType::CLASS_,
            name: 'BaseController',
            declaringClass: 'App\Controller\BaseController',
            isDeclaringClassAbstract: true,
            declaringClassType: DeclaringClassType::CLASS_,
        );
        $interfaceTarget = new AttributeTarget(
            type: AttributeTargetType::CLASS_,
            name: 'Contract',
            declaringClass: 'App\Controller\Contract',
            isDeclaringClassAbstract: false,
            declaringClassType: DeclaringClassType::INTERFACE,
        );

        $this->assertFalse($abstractTarget->isInstantiableDeclaringType());
        $this->assertFalse($interfaceTarget->isInstantiableDeclaringType());
    }

    /**
     * Test public method target helper.
     */
    public function testIsPublicMethodTarget(): void
    {
        $publicMethodTarget = new AttributeTarget(
            type: AttributeTargetType::METHOD,
            name: 'index',
            declaringClass: 'App\Controller\UsersController',
            methodVisibility: MethodVisibility::PUBLIC,
        );
        $protectedMethodTarget = new AttributeTarget(
            type: AttributeTargetType::METHOD,
            name: 'index',
            declaringClass: 'App\Controller\UsersController',
            methodVisibility: MethodVisibility::PROTECTED,
        );

        $this->assertTrue($publicMethodTarget->isPublicMethodTarget());
        $this->assertFalse($protectedMethodTarget->isPublicMethodTarget());
    }
}
