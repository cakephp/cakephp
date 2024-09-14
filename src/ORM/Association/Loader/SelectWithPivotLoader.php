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
 * @since         3.4.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\ORM\Association\Loader;

use Cake\Database\Exception\DatabaseException;
use Cake\Database\ExpressionInterface;
use Cake\ORM\Association\HasMany;
use Cake\ORM\Query\SelectQuery;
use Closure;

/**
 * Implements the logic for loading an association using a SELECT query and a pivot table
 *
 * @internal
 */
class SelectWithPivotLoader extends SelectLoader
{
    /**
     * The name of the junction association
     *
     * @var string
     */
    protected string $junctionAssociationName;

    /**
     * The property name for the junction association, where its results should be nested at.
     *
     * @var string
     */
    protected string $junctionProperty;

    /**
     * The junction association instance
     *
     * @var \Cake\ORM\Association\HasMany
     */
    protected HasMany $junctionAssoc;

    /**
     * Custom conditions for the junction association
     *
     * @var \Cake\Database\ExpressionInterface|\Closure|array|string|null
     */
    protected ExpressionInterface|Closure|array|string|null $junctionConditions = null;

    /**
     * @inheritDoc
     */
    public function __construct(array $options)
    {
        parent::__construct($options);
        $this->junctionAssociationName = $options['junctionAssociationName'];
        $this->junctionProperty = $options['junctionProperty'];
        $this->junctionAssoc = $options['junctionAssoc'];
        $this->junctionConditions = $options['junctionConditions'];
    }

    /**
     * Auxiliary function to construct a new Query object to return all the records
     * in the target table that are associated to those specified in $options from
     * the source table.
     *
     * This is used for eager loading records on the target table based on conditions.
     *
     * @param array<string, mixed> $options options accepted by eagerLoader()
     * @return \Cake\ORM\Query\SelectQuery
     * @throws \InvalidArgumentException When a key is required for associations but not selected.
     */
    protected function _buildQuery(array $options): SelectQuery
    {
        $name = $this->junctionAssociationName;
        $assoc = $this->junctionAssoc;
        $queryBuilder = false;

        if (!empty($options['queryBuilder'])) {
            $queryBuilder = $options['queryBuilder'];
            unset($options['queryBuilder']);
        }

        $query = parent::_buildQuery($options);

        if ($queryBuilder) {
            /** @var \Cake\ORM\Query\SelectQuery $query */
            $query = $queryBuilder($query);
        }

        if ($query->isAutoFieldsEnabled() === null) {
            $query->enableAutoFields($query->clause('select') === []);
        }

        // Ensure that association conditions are applied
        // and that the required keys are in the selected columns.

        $tempName = $this->alias . '_CJoin';
        $schema = $assoc->getSchema();
        $joinFields = [];
        $types = [];

        foreach ($schema->typeMap() as $f => $type) {
            $key = $tempName . '__' . $f;
            $joinFields[$key] = "{$name}.{$f}";
            $types[$key] = $type;
        }

        $query
            ->where($this->junctionConditions)
            ->select($joinFields);

        $query
            ->getEagerLoader()
            ->addToJoinsMap($tempName, $assoc, false, $this->junctionProperty);

        $assoc->attachTo($query, [
            'aliasPath' => $assoc->getAlias(),
            'includeFields' => false,
            'propertyPath' => $this->junctionProperty,
        ]);
        $query->getTypeMap()->addDefaults($types);

        return $query;
    }

    /**
     * @inheritDoc
     */
    protected function _assertFieldsPresent(SelectQuery $fetchQuery, array $key): void
    {
        // _buildQuery() manually adds in required fields from junction table
    }

    /**
     * Generates a string used as a table field that contains the values upon
     * which the filter should be applied
     *
     * @param array<string, mixed> $options the options to use for getting the link field.
     * @return list<string>|string
     */
    protected function _linkField(array $options): array|string
    {
        $links = [];
        $name = $this->junctionAssociationName;

        foreach ((array)$options['foreignKey'] as $key) {
            $links[] = sprintf('%s.%s', $name, $key);
        }

        if (count($links) === 1) {
            return $links[0];
        }

        return $links;
    }

    /**
     * Builds an array containing the results from fetchQuery indexed by
     * the foreignKey value corresponding to this association.
     *
     * @param \Cake\ORM\Query\SelectQuery $fetchQuery The query to get results from
     * @param array<string, mixed> $options The options passed to the eager loader
     * @return array<string, mixed>
     * @throws \Cake\Database\Exception\DatabaseException when the association property is not part of the results set.
     */
    protected function _buildResultMap(SelectQuery $fetchQuery, array $options): array
    {
        $resultMap = [];
        $key = (array)$options['foreignKey'];
        $preserveKeys = $fetchQuery->getOptions()['preserveKeys'] ?? false;

        foreach ($fetchQuery->all() as $i => $result) {
            if (!isset($result[$this->junctionProperty])) {
                throw new DatabaseException(sprintf(
                    '`%s` is missing from the belongsToMany results. Results cannot be created.',
                    $this->junctionProperty
                ));
            }

            $values = [];
            foreach ($key as $k) {
                $values[] = $result[$this->junctionProperty][$k];
            }

            if ($preserveKeys) {
                $resultMap[implode(';', $values)][$i] = $result;
                continue;
            }

            $resultMap[implode(';', $values)][] = $result;
        }

        return $resultMap;
    }
}
