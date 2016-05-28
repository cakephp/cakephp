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
 * @since         3.3.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\ORM\Rule;

use Cake\Datasource\EntityInterface;
use Cake\ORM\Association;
use Cake\ORM\Exception\LinkConstraintViolationException;
use Cake\ORM\Query;
use Cake\ORM\Table;

class LinkConstraint
{
    /**
     * The error more that throws exceptions on failure.
     *
     * @var string
     */
    const ERROR_MODE_EXCEPTIONS = 'errorModeExceptions';

    /**
     * The error more that returns a boolean on failure.
     *
     * @var string
     */
    const ERROR_MODE_RETURN_VALUE = 'errorModeReturnValue';

    /**
     * Link status that requires a link to be present.
     *
     * @var string
     */
    const LINK_STATUS_LINKED = 'linkStatusLinked';

    /**
     * Link status that requires a link to not be present.
     *
     * @var string
     */
    const LINK_STATUS_NOT_LINKED = 'linkStatusNotLinked';

    /**
     * The association that should be checked.
     *
     * @var \Cake\ORM\Association|string
     */
    protected $_association;

    /**
     * The error mode to use when the check fails.
     *
     * @var string
     */
    protected $_errorMode;

    /**
     * The link status that is required to be present in order for the check to succeed.
     *
     * @var string
     */
    protected $_requiredLinkState;

    /**
     * Constructor.
     *
     * @param \Cake\ORM\Association|string $association The alias of the association that should be checked.
     * @param string $requiredLinkStatus The link status that is required to be present in order for the check to
     *  succeed.
     * @param string $errorMode The error mode to use, defaults to `LinkConstraint::ERROR_MODE_EXCEPTIONS`.
     */
    public function __construct($association, $requiredLinkStatus, $errorMode = null)
    {
        if ((!is_string($association) && !$association instanceof Association) || empty($association)) {
            throw new \InvalidArgumentException(
                'Argument 1 is expected to be either an instance of `Cake\ORM\Association`, or a non-empty string.'
            );
        }

        if (!is_string($requiredLinkStatus) ||
            !in_array($requiredLinkStatus, [static::LINK_STATUS_LINKED, static::LINK_STATUS_NOT_LINKED], true)
        ) {
            throw new \InvalidArgumentException(
                'Argument 2 is expected to match one of the `\Cake\ORM\Rule\LinkConstraint::LINK_STATUS_*` constants.'
            );
        }

        if ($errorMode === null) {
            $errorMode = static::ERROR_MODE_EXCEPTIONS;
        }
        if (!is_string($errorMode) ||
            !in_array($errorMode, [static::ERROR_MODE_EXCEPTIONS, static::ERROR_MODE_RETURN_VALUE], true)
        ) {
            throw new \InvalidArgumentException(
                'Argument 3 is expected to match one of the `\Cake\ORM\Rule\LinkConstraint::ERROR_MODE_*` constants.'
            );
        }

        $this->_association = $association;
        $this->_errorMode = $errorMode;
        $this->_requiredLinkState = $requiredLinkStatus;
    }

    /**
     * Callable handler.
     *
     * Performs the actual link check.
     *
     * @param \Cake\Datasource\EntityInterface $entity The entity involved in the operation.
     * @param array $options Options passed from the rules checker.
     * @return bool Whether the check was successful.
     */
    public function __invoke(EntityInterface $entity, array $options)
    {
        if (!isset($options['repository']) || !$options['repository'] instanceof Table) {
            throw new \InvalidArgumentException(
                'Argument 2 is expected to have a `repository` key set that holds an instance of `\Cake\ORM\Table`.'
            );
        }

        /* @var $table \Cake\ORM\Table */
        $table = $options['repository'];

        $association = $this->_association;
        if (!$association instanceof Association) {
            $association = $table->association($association);
            if (!$association) {
                throw new \RuntimeException(
                    sprintf(
                        'The association `%s` could not be found on the repository `%s`.',
                        $this->_association,
                        $table->registryAlias()
                    )
                );
            }
        }

        $count = $this->_countLinks($association, $entity);

        if (($this->_requiredLinkState === static::LINK_STATUS_LINKED && $count < 1) ||
            ($this->_requiredLinkState === static::LINK_STATUS_NOT_LINKED && $count !== 0)
        ) {
            if ($this->_errorMode === static::ERROR_MODE_EXCEPTIONS) {
                throw new LinkConstraintViolationException([
                    'repository' => $table->registryAlias(),
                    'association' => $association->registryAlias()
                ]);
            }
            return false;
        }

        return true;
    }

    /**
     * Alias fields.
     *
     * @param array $fields The fields that should be aliased.
     * @param \Cake\ORM\Association|\Cake\ORM\Table $source The object to use for aliasing.
     * @return array The aliased fields
     */
    protected function _aliasFields($fields, $source)
    {
        foreach ($fields as $key => $value) {
            $fields[$key] = $source->aliasField($value);
        }
        return $fields;
    }

    /**
     * Build conditions.
     *
     * @param array $fields The condition fields.
     * @param array $values The condition values.
     * @return array A conditions array combined from the passed fields and values.
     */
    protected function _buildConditions($fields, $values)
    {
        if (count($fields) !== count($values)) {
            throw new \InvalidArgumentException('The number of fields is expected to match the number of values.');
        }
        return array_combine($fields, $values);
    }

    /**
     * Count links.
     *
     * @param \Cake\ORM\Association $association The association for which to count links.
     * @param \Cake\Datasource\EntityInterface $entity The entity involved in the operation.
     * @return int The number of links.
     */
    protected function _countLinks($association, $entity)
    {
        $source = $association->source();

        $primaryKey = $source->primaryKey();
        if (!$entity->has($primaryKey)) {
            throw new \InvalidArgumentException('All primary key values are required.');
        }

        $sourceAlias = $source->registryAlias();
        $sourceAssociation = $association->target()->association($sourceAlias);

        if ($sourceAssociation instanceof Association) {
            $conditions = $this->_buildConditions(
                $this->_aliasFields((array)$primaryKey, $sourceAssociation),
                $entity->extract((array)$primaryKey)
            );

            return $association
                ->find()
                ->matching(
                    $sourceAssociation->registryAlias(),
                    function (Query $query) use ($conditions) {
                        return $query->where($conditions);
                    }
                )
                ->count();
        }

        $conditions = $this->_buildConditions(
            $this->_aliasFields((array)$primaryKey, $source),
            $entity->extract((array)$primaryKey)
        );

        $entity = $source
            ->find()
            ->contain($association->registryAlias())
            ->where($conditions)
            ->hydrate(false)
            ->bufferResults(false)
            ->firstOrFail();

        $property = $association->property();
        if (!isset($entity[$property]) || empty($entity[$property])) {
            return 0;
        }
        if (in_array($association->type(), [Association::MANY_TO_MANY, Association::ONE_TO_MANY], true)) {
            return count($entity[$property]);
        }
        return 1;
    }
}
