<?php

namespace Cake\ORM\Rule;

use Cake\Datasource\EntityInterface;
use Cake\ORM\Association;
use RuntimeException;

/**
 * Checks that given entity is not referenced by any row in the given table
 */
class IsNotReferencedBy
{
    /**
     * The repository that will be checked
     *
     * @var array
     */
    protected $_repository;

    /**
     * Constructor.
     *
     * @param string $repository The association name for the repository which will be checked.
     */
    public function __construct($repository)
    {
        $this->_repository = $repository;
    }

    /**
     * Performs the reference check
     *
     * @param \Cake\Datasource\EntityInterface $entity The entity to check against
     * @param array $options Options passed to the check,
     * where the `repository` key is required.
     * @throws \RuntimeException When the rule refers to an undefined association.
     * @return bool
     */
    public function __invoke(EntityInterface $entity, array $options)
    {
        $assoc = $options['repository']->association($this->_repository);

        if (!$assoc) {
            throw new RuntimeException(sprintf(
                "IsNotReferencedBy rule is invalid. '%s' is not associated with '%s'.",
                $this->_repository,
                get_class($options['repository'])
            ));
        }

        $pkFieldNames = (array)$assoc->getBindingKey();
        $fkFieldNames = (array)$assoc->getForeignKey();
        $target = $assoc->getTarget();

        $conditions = [];
        foreach ($pkFieldNames as $key => $field) {
            $conditions[$field] = $entity->{$fkFieldNames[$key]};
        }

        return ! $target->exists($conditions);
    }
}
