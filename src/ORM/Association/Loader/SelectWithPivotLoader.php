<?php
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         3.4.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\ORM\Association\Loader;

use InvalidArgumentException;
use RuntimeException;

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
    protected $junctionAssociationName;

    /**
     * The property name for the junction association, where its results should be nested at.
     *
     * @var string
     */
    protected $junctionProperty;

    /**
     * The junction association instance
     *
     * @var \Cake\ORM\Association\HasMany
     */
    protected $junctionAssoc;

    /**
     * Custom conditions for the junction association
     *
     * @var mixed
     */
    protected $junctionConditions;

    /**
     * {@inheritDoc}
     *
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
     * @param array $options options accepted by eagerLoader()
     * @return \Cake\ORM\Query
     * @throws \InvalidArgumentException When a key is required for associations but not selected.
     */
    protected function _buildQuery($options)
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
            $query = $queryBuilder($query);
        }

        if ($query->autoFields() === null) {
            $query->autoFields($query->clause('select') === []);
        }

        // Ensure that association conditions are applied
        // and that the required keys are in the selected columns.

        $tempName = $this->alias . '_CJoin';
        $schema = $assoc->schema();
        $joinFields = $types = [];

        foreach ($schema->typeMap() as $f => $type) {
            $key = $tempName . '__' . $f;
            $joinFields[$key] = "$name.$f";
            $types[$key] = $type;
        }

        $query
           ->where($this->junctionConditions)
            ->select($joinFields);

        $query
            ->eagerLoader()
            ->addToJoinsMap($tempName, $assoc, false, $this->junctionProperty);

        $assoc->attachTo($query, [
            'aliasPath' => $assoc->alias(),
            'includeFields' => false,
            'propertyPath' => $this->junctionProperty,
        ]);
        $query->typeMap()->addDefaults($types);

        return $query;
    }

    /**
     * Generates a string used as a table field that contains the values upon
     * which the filter should be applied
     *
     * @param array $options the options to use for getting the link field.
     * @return string
     */
    protected function _linkField($options)
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
     * @param \Cake\ORM\Query $fetchQuery The query to get results from
     * @param array $options The options passed to the eager loader
     * @return array
     * @throws \RuntimeException when the association property is not part of the results set.
     */
    protected function _buildResultMap($fetchQuery, $options)
    {
        $resultMap = [];
        $key = (array)$options['foreignKey'];
        $hydrated = $fetchQuery->hydrate();

        foreach ($fetchQuery->all() as $result) {
            if (!isset($result[$this->junctionProperty])) {
                throw new RuntimeException(sprintf(
                    '"%s" is missing from the belongsToMany results. Results cannot be created.',
                    $this->junctionProperty
                ));
            }

            $values = [];
            foreach ($key as $k) {
                $values[] = $result[$this->junctionProperty][$k];
            }
            $resultMap[implode(';', $values)][] = $result;
        }

        return $resultMap;
    }
}
