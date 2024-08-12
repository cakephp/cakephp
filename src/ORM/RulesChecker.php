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
namespace Cake\ORM;

use Cake\Datasource\RuleInvoker;
use Cake\Datasource\RulesChecker as BaseRulesChecker;
use Cake\ORM\Rule\ExistsIn;
use Cake\ORM\Rule\IsUnique;
use Cake\ORM\Rule\LinkConstraint;
use Cake\ORM\Rule\ValidCount;
use Cake\Utility\Inflector;
use function Cake\I18n\__d;

/**
 * ORM flavoured rules checker.
 *
 * Adds ORM related features to the RulesChecker class.
 *
 * @see \Cake\Datasource\RulesChecker
 */
class RulesChecker extends BaseRulesChecker
{
    /**
     * Returns a callable that can be used as a rule for checking the uniqueness of a value
     * in the table.
     *
     * ### Example
     *
     * ```
     * $rules->add($rules->isUnique(['email'], 'The email should be unique'));
     * ```
     *
     * ### Options
     *
     * - `allowMultipleNulls` Allows any field to have multiple null values. Defaults to false.
     *
     * @param list<string> $fields The list of fields to check for uniqueness.
     * @param array<string, mixed>|string|null $message The error message to show in case the rule does not pass. Can
     *   also be an array of options. When an array, the 'message' key can be used to provide a message.
     * @return \Cake\Datasource\RuleInvoker
     */
    public function isUnique(array $fields, array|string|null $message = null): RuleInvoker
    {
        $options = is_array($message) ? $message : ['message' => $message];
        $message = $options['message'] ?? null;
        unset($options['message']);

        if (!$message) {
            if ($this->_useI18n) {
                $message = __d('cake', 'This value is already in use');
            } else {
                $message = 'This value is already in use';
            }
        }

        $errorField = current($fields);

        return $this->_addError(new IsUnique($fields, $options), '_isUnique', compact('errorField', 'message'));
    }

    /**
     * Returns a callable that can be used as a rule for checking that the values
     * extracted from the entity to check exist as the primary key in another table.
     *
     * This is useful for enforcing foreign key integrity checks.
     *
     * ### Example:
     *
     * ```
     * $rules->add($rules->existsIn('author_id', 'Authors', 'Invalid Author'));
     *
     * $rules->add($rules->existsIn('site_id', new SitesTable(), 'Invalid Site'));
     * ```
     *
     * Available $options are error 'message' and 'allowNullableNulls' flag.
     * 'message' sets a custom error message.
     * Set 'allowNullableNulls' to true to accept composite foreign keys where one or more nullable columns are null.
     *
     * @param list<string>|string $field The field or list of fields to check for existence by
     * primary key lookup in the other table.
     * @param \Cake\ORM\Table|\Cake\ORM\Association|string $table The table name where the fields existence will be checked.
     * @param array<string, mixed>|string|null $message The error message to show in case the rule does not pass. Can
     *   also be an array of options. When an array, the 'message' key can be used to provide a message.
     * @return \Cake\Datasource\RuleInvoker
     */
    public function existsIn(
        array|string $field,
        Table|Association|string $table,
        array|string|null $message = null
    ): RuleInvoker {
        $options = [];
        if (is_array($message)) {
            $options = $message + ['message' => null];
            $message = $options['message'];
            unset($options['message']);
        }

        if (!$message) {
            if ($this->_useI18n) {
                $message = __d('cake', 'This value does not exist');
            } else {
                $message = 'This value does not exist';
            }
        }

        $errorField = is_string($field) ? $field : current($field);

        return $this->_addError(new ExistsIn($field, $table, $options), '_existsIn', compact('errorField', 'message'));
    }

    /**
     * Validates whether links to the given association exist.
     *
     * ### Example:
     *
     * ```
     * $rules->addUpdate($rules->isLinkedTo('Articles', 'article'));
     * ```
     *
     * On a `Comments` table that has a `belongsTo Articles` association, this check would ensure that comments
     * can only be edited as long as they are associated to an existing article.
     *
     * @param \Cake\ORM\Association|string $association The association to check for links.
     * @param string|null $field The name of the association property. When supplied, this is the name used to set
     *  possible errors. When absent, the name is inferred from `$association`.
     * @param string|null $message The error message to show in case the rule does not pass.
     * @return \Cake\Datasource\RuleInvoker
     * @since 4.0.0
     */
    public function isLinkedTo(
        Association|string $association,
        ?string $field = null,
        ?string $message = null
    ): RuleInvoker {
        return $this->_addLinkConstraintRule(
            $association,
            $field,
            $message,
            LinkConstraint::STATUS_LINKED,
            '_isLinkedTo'
        );
    }

    /**
     * Validates whether links to the given association do not exist.
     *
     * ### Example:
     *
     * ```
     * $rules->addDelete($rules->isNotLinkedTo('Comments', 'comments'));
     * ```
     *
     * On a `Articles` table that has a `hasMany Comments` association, this check would ensure that articles
     * can only be deleted when no associated comments exist.
     *
     * @param \Cake\ORM\Association|string $association The association to check for links.
     * @param string|null $field The name of the association property. When supplied, this is the name used to set
     *  possible errors. When absent, the name is inferred from `$association`.
     * @param string|null $message The error message to show in case the rule does not pass.
     * @return \Cake\Datasource\RuleInvoker
     * @since 4.0.0
     */
    public function isNotLinkedTo(
        Association|string $association,
        ?string $field = null,
        ?string $message = null
    ): RuleInvoker {
        return $this->_addLinkConstraintRule(
            $association,
            $field,
            $message,
            LinkConstraint::STATUS_NOT_LINKED,
            '_isNotLinkedTo'
        );
    }

    /**
     * Adds a link constraint rule.
     *
     * @param \Cake\ORM\Association|string $association The association to check for links.
     * @param string|null $errorField The name of the property to use for setting possible errors. When absent,
     *   the name is inferred from `$association`.
     * @param string|null $message The error message to show in case the rule does not pass.
     * @param string $linkStatus The ink status required for the check to pass.
     * @param string $ruleName The alias/name of the rule.
     * @return \Cake\Datasource\RuleInvoker
     * @throws \InvalidArgumentException In case the `$association` argument is of an invalid type.
     * @since 4.0.0
     * @see \Cake\ORM\RulesChecker::isLinkedTo()
     * @see \Cake\ORM\RulesChecker::isNotLinkedTo()
     * @see \Cake\ORM\Rule\LinkConstraint::STATUS_LINKED
     * @see \Cake\ORM\Rule\LinkConstraint::STATUS_NOT_LINKED
     */
    protected function _addLinkConstraintRule(
        Association|string $association,
        ?string $errorField,
        ?string $message,
        string $linkStatus,
        string $ruleName
    ): RuleInvoker {
        if ($association instanceof Association) {
            $associationAlias = $association->getName();
            $errorField ??= $association->getProperty();
        } else {
            $associationAlias = $association;

            if ($errorField === null) {
                $repository = $this->_options['repository'] ?? null;
                if ($repository instanceof Table) {
                    $association = $repository->getAssociation($association);
                    $errorField = $association->getProperty();
                } else {
                    $errorField = Inflector::underscore($association);
                }
            }
        }

        if (!$message) {
            if ($this->_useI18n) {
                $message = __d(
                    'cake',
                    'Cannot modify row: a constraint for the `{0}` association fails.',
                    $associationAlias
                );
            } else {
                $message = sprintf(
                    'Cannot modify row: a constraint for the `%s` association fails.',
                    $associationAlias
                );
            }
        }

        $rule = new LinkConstraint(
            $association,
            $linkStatus
        );

        return $this->_addError($rule, $ruleName, compact('errorField', 'message'));
    }

    /**
     * Validates the count of associated records.
     *
     * @param string $field The field to check the count on.
     * @param int $count The expected count.
     * @param string $operator The operator for the count comparison.
     * @param string|null $message The error message to show in case the rule does not pass.
     * @return \Cake\Datasource\RuleInvoker
     */
    public function validCount(
        string $field,
        int $count = 0,
        string $operator = '>',
        ?string $message = null
    ): RuleInvoker {
        if (!$message) {
            if ($this->_useI18n) {
                $message = __d('cake', 'The count does not match {0}{1}', [$operator, $count]);
            } else {
                $message = sprintf('The count does not match %s%d', $operator, $count);
            }
        }

        $errorField = $field;

        return $this->_addError(
            new ValidCount($field),
            '_validCount',
            compact('count', 'operator', 'errorField', 'message')
        );
    }
}
