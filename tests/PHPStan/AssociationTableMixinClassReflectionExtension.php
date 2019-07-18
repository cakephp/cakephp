<?php declare(strict_types = 1);

namespace Cake\PHPStan;

use Cake\ORM\Association;
use Cake\ORM\Table;
use PHPStan\Broker\Broker;
use PHPStan\Reflection\BrokerAwareClassReflectionExtension;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\MethodsClassReflectionExtension;
use PHPStan\Reflection\PropertiesClassReflectionExtension;
use PHPStan\Reflection\PropertyReflection;

class AssociationTableMixinClassReflectionExtension implements PropertiesClassReflectionExtension, MethodsClassReflectionExtension, BrokerAwareClassReflectionExtension
{
    /**
     * @var \PHPStan\Broker\Broker
     */
    private $broker;

    /**
     * @param Broker $broker Class reflection broker
     * @return void
     */
    public function setBroker(Broker $broker): void
    {
        $this->broker = $broker;
    }

    /**
     * @return ClassReflection
     */
    protected function getTableReflection(): ClassReflection
    {
        return $this->broker->getClass(Table::class);
    }

    /**
     * @param ClassReflection $classReflection Class reflection
     * @param string $methodName Method name
     * @return bool
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
     * @return MethodReflection
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
     * @return bool
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
     * @return PropertyReflection
     */
    public function getProperty(ClassReflection $classReflection, string $propertyName): PropertyReflection
    {
        return $this->getTableReflection()->getNativeProperty($propertyName);
    }
}
