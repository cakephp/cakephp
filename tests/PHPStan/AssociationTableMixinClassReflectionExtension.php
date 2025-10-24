<?php
declare(strict_types=1);

namespace Cake\PHPStan;

use Cake\ORM\Association;
use Cake\ORM\Table;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\MethodsClassReflectionExtension;
use PHPStan\Reflection\PropertiesClassReflectionExtension;
use PHPStan\Reflection\PropertyReflection;
use PHPStan\Reflection\ReflectionProvider;

class AssociationTableMixinClassReflectionExtension implements PropertiesClassReflectionExtension, MethodsClassReflectionExtension
{
    /**
     * @var \PHPStan\Reflection\ReflectionProvider
     */
    private ReflectionProvider $reflectionProvider;

    public function __construct(
        ReflectionProvider $reflectionProvider,
    ) {
        $this->reflectionProvider = $reflectionProvider;
    }

    protected function getTableReflection(): ClassReflection
    {
        return $this->reflectionProvider->getClass(Table::class);
    }

    /**
     * @param ClassReflection $classReflection Class reflection
     * @param string $methodName Method name
     */
    public function hasMethod(ClassReflection $classReflection, string $methodName): bool
    {
        // magic findBy* method
        if ($classReflection->isSubclassOf(Table::class) && preg_match('/^find(?:\w+)?By/', $methodName) > 0) {
            return true;
        }

        if (!$classReflection->isSubclassOf(Association::class)) {
            return false;
        }

        return $this->getTableReflection()->hasMethod($methodName);
    }

    /**
     * @param ClassReflection $classReflection Class reflection
     * @param string $methodName Method name
     */
    public function getMethod(ClassReflection $classReflection, string $methodName): MethodReflection
    {
        // magic findBy* method
        if ($classReflection->isSubclassOf(Table::class) && preg_match('/^find(?:\w+)?By/', $methodName) > 0) {
            return new TableFindByPropertyMethodReflection($methodName, $classReflection);
        }

        return $this->getTableReflection()->getNativeMethod($methodName);
    }

    /**
     * @param ClassReflection $classReflection Class reflection
     * @param string $propertyName Method name
     */
    public function hasProperty(ClassReflection $classReflection, string $propertyName): bool
    {
        if (!$classReflection->isSubclassOf(Association::class)) {
            return false;
        }

        return $this->getTableReflection()->hasProperty($propertyName);
    }

    /**
     * @param ClassReflection $classReflection Class reflection
     * @param string $propertyName Method name
     */
    public function getProperty(ClassReflection $classReflection, string $propertyName): PropertyReflection
    {
        return $this->getTableReflection()->getNativeProperty($propertyName);
    }
}
