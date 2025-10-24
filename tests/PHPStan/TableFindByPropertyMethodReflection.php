<?php
declare(strict_types=1);

namespace Cake\PHPStan;

use Cake\ORM\Query\SelectQuery;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\FunctionVariantWithPhpDocs;
use PHPStan\Reflection\MethodReflection;
use PHPStan\TrinaryLogic;
use PHPStan\Type\Generic\TemplateTypeMap;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;

class TableFindByPropertyMethodReflection implements MethodReflection
{
    /**
     * @var string
     */
    private string $name;

    /**
     * @var \PHPStan\Reflection\ClassReflection
     */
    private ClassReflection $declaringClass;

    public function __construct(string $name, ClassReflection $declaringClass)
    {
        $this->name = $name;
        $this->declaringClass = $declaringClass;
    }

    public function getDeclaringClass(): ClassReflection
    {
        return $this->declaringClass;
    }

    public function getPrototype(): MethodReflection
    {
        return $this;
    }

    public function isStatic(): bool
    {
        return false;
    }

    /**
     * @return \PHPStan\Reflection\ParameterReflection[]
     */
    public function getParameters(): array
    {
        return [];
    }

    public function isVariadic(): bool
    {
        return true;
    }

    public function isPrivate(): bool
    {
        return false;
    }

    public function isPublic(): bool
    {
        return true;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getReturnType(): Type
    {
        return new ObjectType(SelectQuery::class);
    }

    public function getDocComment(): ?string
    {
        return null;
    }

    public function getVariants(): array
    {
        return [
            new FunctionVariantWithPhpDocs(
                TemplateTypeMap::createEmpty(),
                TemplateTypeMap::createEmpty(),
                [],
                true,
                $this->getReturnType(),
                $this->getReturnType(),
                $this->getReturnType(),
            ),
        ];
    }

    public function isDeprecated(): TrinaryLogic
    {
        return TrinaryLogic::createNo();
    }

    public function getDeprecatedDescription(): ?string
    {
        return null;
    }

    public function isFinal(): TrinaryLogic
    {
        return TrinaryLogic::createNo();
    }

    public function isInternal(): TrinaryLogic
    {
        return TrinaryLogic::createNo();
    }

    public function getThrowType(): ?Type
    {
        return null;
    }

    public function hasSideEffects(): TrinaryLogic
    {
        return TrinaryLogic::createNo();
    }
}
