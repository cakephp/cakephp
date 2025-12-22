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
 * @since         3.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\ORM\Rule;

use Cake\Database\Exception\DatabaseException;
use Cake\Datasource\EntityInterface;
use Cake\ORM\Association;
use Cake\ORM\Table;

/**
 * Checks that the value provided in a field exists as the primary key of another
 * table.
 */
class ExistsIn
{
    /**
     * The list of fields to check
     *
     * @var array<string>
     */
    protected array $fields;

    /**
     * The repository where the field will be looked for
     *
     * @var \Cake\ORM\Table|\Cake\ORM\Association|string
     */
    protected Table|Association|string $repository;

    /**
     * Options for the constructor
     *
     * @var array<string, mixed>
     */
    protected array $options = [];

    /**
     * Constructor.
     *
     * Available option for $options is 'allowNullableNulls' flag.
     * Set to true to accept composite foreign keys where one or more nullable columns are null.
     *
     * @param array<string>|string $fields The field or fields to check existence as primary key.
     * @param \Cake\ORM\Table|\Cake\ORM\Association|string $repository The repository where the
     * field will be looked for, or the association name for the repository.
     * @param array<string, mixed> $options The options that modify the rule's behavior.
     *     Options 'allowNullableNulls' will make the rule pass if given foreign keys are set to `null`.
     *     Notice: allowNullableNulls cannot pass by database columns set to `NOT NULL`.
     */
    public function __construct(array|string $fields, Table|Association|string $repository, array $options = [])
    {
        $options += ['allowNullableNulls' => false];
        $this->options = $options;

        $this->fields = (array)$fields;
        $this->repository = $repository;
    }

    /**
     * Performs the existence check
     *
     * @param \Cake\Datasource\EntityInterface $entity The entity from where to extract the fields
     * @param array<string, mixed> $options Options passed to the check,
     * where the `repository` key is required.
     * @throws \Cake\Database\Exception\DatabaseException When the rule refers to an undefined association.
     * @return bool
     */
    public function __invoke(EntityInterface $entity, array $options): bool
    {
        if (is_string($this->repository)) {
            /** @var \Cake\ORM\Table $table */
            $table = $options['repository'];

            if (!$table->hasAssociation($this->repository)) {
                throw new DatabaseException(sprintf(
                    'ExistsIn rule for `%s` is invalid. `%s` is not associated with `%s`.',
                    implode(', ', $this->fields),
                    $this->repository,
                    $options['repository']::class,
                ));
            }
            $repository = $table->getAssociation($this->repository);
            $this->repository = $repository;
        }

        $fields = $this->fields;
        $source = $this->repository;
        $target = $this->repository;
        if ($target instanceof Association) {
            $bindingKey = (array)$target->getBindingKey();
            $realTarget = $target->getTarget();
        } else {
            $bindingKey = (array)$target->getPrimaryKey();
            $realTarget = $target;
        }

        if (!empty($options['sourceTable']) && $realTarget === $options['sourceTable']) {
            return true;
        }

        if (!empty($options['repository'])) {
            /** @var \Cake\ORM\Table $source */
            $source = $options['repository'];
        } else {
            $source = $this->repository;
        }
        if ($source instanceof Association) {
            $source = $source->getSource();
        }

        if (!$entity->extract($this->fields, true)) {
            return true;
        }

        if ($this->fieldsAreNull($entity, $source)) {
            return true;
        }

        if ($this->options['allowNullableNulls']) {
            $schema = $source->getSchema();
            foreach ($fields as $i => $field) {
                if ($schema->hasColumn($field) && $schema->isNullable($field) && $entity->get($field) === null) {
                    unset($bindingKey[$i], $fields[$i]);
                }
            }
        }

        $primary = array_map(
            fn(string $key) => $target->aliasField($key) . ' IS',
            $bindingKey,
        );
        $conditions = array_combine(
            $primary,
            $entity->extract($fields),
        );

        return $target->exists($conditions);
    }

    /**
     * Checks whether the given entity fields are nullable and null.
     *
     * @param \Cake\Datasource\EntityInterface $entity The entity to check.
     * @param \Cake\ORM\Table $source The table to use schema from.
     * @return bool
     */
    protected function fieldsAreNull(EntityInterface $entity, Table $source): bool
    {
        $nulls = 0;
        $schema = $source->getSchema();
        foreach ($this->fields as $field) {
            if ($schema->hasColumn($field) && $schema->isNullable($field) && $entity->get($field) === null) {
                $nulls++;
            }
        }

        return $nulls === count($this->fields);
    }
}
