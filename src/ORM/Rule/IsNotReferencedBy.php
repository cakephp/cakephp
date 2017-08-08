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
     * The list of fields to check
     *
     * @var array
     */
    protected $_fields;

    /**
     * The repository where the field will be looked for
     *
     * @var array
     */
    protected $_repository;

    /**
     * Options for the constructor
     *
     * @var array
     */
    protected $_options = [];

    /**
     * Constructor.
     *
     * @param null|string|array $fields The field or fields to check.
     * @param string $repository The association name for the repository which will be checked.
     * @param array $options The additional options for this rule.
     */
    public function __construct($fields, $repository, array $options = [])
    {
        $this->_options = $options;
        $this->_fields = (array)$fields;
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
        if (is_string($options))
        $assoc = $options['repository']->association($this->_repository);

        if (!$assoc) {
            throw new RuntimeException(sprintf(
                "IsNotReferencedBy rule is invalid. '%s' is not associated with '%s'.",
                $options['repository'],
                $this->_repository
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
