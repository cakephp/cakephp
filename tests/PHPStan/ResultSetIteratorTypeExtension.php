<?php
declare(strict_types=1);

namespace Cake\PHPStan;

use Cake\Datasource\ResultSetInterface;
use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Type\DynamicMethodReturnTypeExtension;
use PHPStan\Type\Generic\GenericObjectType;
use PHPStan\Type\Type;

/**
 * Helps PHPStan understand that iterating over a ResultSet<Entity> yields Entity objects
 */
class ResultSetIteratorTypeExtension implements DynamicMethodReturnTypeExtension
{
    public function getClass(): string
    {
        return ResultSetInterface::class;
    }

    public function isMethodSupported(MethodReflection $methodReflection): bool
    {
        return in_array($methodReflection->getName(), ['current', 'first', 'last']);
    }

    public function getTypeFromMethodCall(
        MethodReflection $methodReflection,
        MethodCall $methodCall,
        Scope $scope,
    ): ?Type {
        $calledOnType = $scope->getType($methodCall->var);

        if (!$calledOnType instanceof GenericObjectType) {
            return null;
        }

        $genericTypes = $calledOnType->getTypes();
        if (count($genericTypes) > 0) {
            // Return the generic type parameter (the entity type)
            return $genericTypes[0];
        }

        return null;
    }
}
