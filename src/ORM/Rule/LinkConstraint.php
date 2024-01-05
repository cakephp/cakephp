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
 * @since         4.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\ORM\Rule;

use Cake\Database\Exception\DatabaseException;
use Cake\Datasource\EntityInterface;
use Cake\ORM\Association;
use Cake\ORM\Table;
use InvalidArgumentException;

/**
 * Checks whether links to a given association exist / do not exist.
 */
class LinkConstraint
{
    /**
     * Status that requires a link to be present.
     *
     * @var string
     */
    public const STATUS_LINKED = 'linked';

    /**
     * Status that requires a link to not be present.
     *
     * @var string
     */
    public const STATUS_NOT_LINKED = 'notLinked';

    /**
     * The association that should be checked.
     *
     * @var \Cake\ORM\Association|string
     */
    protected Association|string $_association;

    /**
     * The link status that is required to be present in order for the check to succeed.
     *
     * @var string
     */
    protected string $_requiredLinkState;

    /**
     * Constructor.
     *
     * @param \Cake\ORM\Association|string $association The alias of the association that should be checked.
     * @param string $requiredLinkStatus The link status that is required to be present in order for the check to
     *  succeed.
     */
    public function __construct(Association|string $association, string $requiredLinkStatus)
    {
        if (!in_array($requiredLinkStatus, [static::STATUS_LINKED, static::STATUS_NOT_LINKED], true)) {
            throw new InvalidArgumentException(
                'Argument 2 is expected to match one of the `\Cake\ORM\Rule\LinkConstraint::STATUS_*` constants.'
            );
        }

        $this->_association = $association;
        $this->_requiredLinkState = $requiredLinkStatus;
    }

    /**
     * Callable handler.
     *
     * Performs the actual link check.
     *
     * @param \Cake\Datasource\EntityInterface $entity The entity involved in the operation.
     * @param array<string, mixed> $options Options passed from the rules checker.
     * @return bool Whether the check was successful.
     */
    public function __invoke(EntityInterface $entity, array $options): bool
    {
        $table = $options['repository'] ?? null;
        if (!($table instanceof Table)) {
            throw new InvalidArgumentException(
                'Argument 2 is expected to have a `repository` key that holds an instance of `\Cake\ORM\Table`.'
            );
        }

        $association = $this->_association;
        if (!$association instanceof Association) {
            $association = $table->getAssociation($association);
        }

        $count = $this->_countLinks($association, $entity);

        if (
            (
                $this->_requiredLinkState === static::STATUS_LINKED &&
                $count < 1
            ) ||
            (
                $this->_requiredLinkState === static::STATUS_NOT_LINKED &&
                $count !== 0
            )
        ) {
            return false;
        }

        return true;
    }

    /**
     * Alias fields.
     *
     * @param list<string> $fields The fields that should be aliased.
     * @param \Cake\ORM\Table $source The object to use for aliasing.
     * @return array<string> The aliased fields
     */
    protected function _aliasFields(array $fields, Table $source): array
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
    protected function _buildConditions(array $fields, array $values): array
    {
        if (count($fields) !== count($values)) {
            throw new InvalidArgumentException(sprintf(
                'The number of fields is expected to match the number of values, got %d field(s) and %d value(s).',
                count($fields),
                count($values)
            ));
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
    protected function _countLinks(Association $association, EntityInterface $entity): int
    {
        $source = $association->getSource();

        $primaryKey = (array)$source->getPrimaryKey();
        if (!$entity->has($primaryKey)) {
            throw new DatabaseException(sprintf(
                'LinkConstraint rule on `%s` requires all primary key values for building the counting ' .
                'conditions, expected values for `(%s)`, got `(%s)`.',
                $source->getAlias(),
                implode(', ', $primaryKey),
                implode(', ', $entity->extract($primaryKey))
            ));
        }

        $aliasedPrimaryKey = $this->_aliasFields($primaryKey, $source);
        $conditions = $this->_buildConditions(
            $aliasedPrimaryKey,
            $entity->extract($primaryKey)
        );

        return $source
            ->find()
            ->matching($association->getName())
            ->where($conditions)
            ->count();
    }
}
