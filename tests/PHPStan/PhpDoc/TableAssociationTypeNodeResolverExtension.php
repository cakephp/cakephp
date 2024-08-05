<?php
declare(strict_types=1);

namespace Cake\PHPStan\PhpDoc;

use Cake\ORM\Association;
use Cake\ORM\Association\BelongsTo;
use Cake\ORM\Association\BelongsToMany;
use Cake\ORM\Association\HasMany;
use Cake\ORM\Association\HasOne;
use PHPStan\Analyser\NameScope;
use PHPStan\PhpDoc\TypeNodeResolver;
use PHPStan\PhpDoc\TypeNodeResolverAwareExtension;
use PHPStan\PhpDoc\TypeNodeResolverExtension;
use PHPStan\PhpDocParser\Ast\Type\IntersectionTypeNode;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use PHPStan\Type\Generic\GenericObjectType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;

/**
 * Fix intersection association phpDoc to correct generic object type, ex:
 *
 * Change `\Cake\ORM\Association\BelongsTo&\App\Model\Table\UsersTable` to `\Cake\ORM\Association\BelongsTo<\App\Model\Table\UsersTable>`
 *
 * The type `\Cake\ORM\Association\BelongsTo&\App\Model\Table\UsersTable` is considered invalid (NeverType) by PHPStan
 */
class TableAssociationTypeNodeResolverExtension implements TypeNodeResolverExtension, TypeNodeResolverAwareExtension
{
    private TypeNodeResolver $typeNodeResolver;

    /**
     * @var array<string>
     */
    protected array $associationTypes = [
        BelongsTo::class,
        BelongsToMany::class,
        HasMany::class,
        HasOne::class,
        Association::class,
    ];

    /**
     * @param \PHPStan\PhpDoc\TypeNodeResolver $typeNodeResolver
     * @return void
     */
    public function setTypeNodeResolver(TypeNodeResolver $typeNodeResolver): void
    {
        $this->typeNodeResolver = $typeNodeResolver;
    }

    /**
     * @param \PHPStan\PhpDocParser\Ast\Type\TypeNode $typeNode
     * @param \PHPStan\Analyser\NameScope $nameScope
     * @return \PHPStan\Type\Type|null
     */
    public function resolve(TypeNode $typeNode, NameScope $nameScope): ?Type
    {
        if (!$typeNode instanceof IntersectionTypeNode) {
            return null;
        }
        $types = $this->typeNodeResolver->resolveMultiple($typeNode->types, $nameScope);
        $config = [
            'association' => null,
            'table' => null,
        ];
        foreach ($types as $type) {
            if (!$type instanceof ObjectType) {
                continue;
            }
            $className = $type->getClassName();
            if ($config['association'] === null && in_array($className, $this->associationTypes)) {
                $config['association'] = $type;
            } elseif ($config['table'] === null && str_ends_with($className, 'Table')) {
                $config['table'] = $type;
            }
        }
        if ($config['table'] && $config['association']) {
            return new GenericObjectType(
                $config['association']->getClassName(),
                [$config['table']]
            );
        }

        return null;
    }
}
