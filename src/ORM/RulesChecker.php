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
 * @since         3.0.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\ORM;

use Cake\Datasource\RulesChecker as BaseRulesChecker;
use Cake\ORM\Rule\ExistsIn;
use Cake\ORM\Rule\IsUnique;
use Cake\ORM\Rule\LinkConstraint;
use Cake\ORM\Rule\ValidCount;

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
     * ### Example:
     *
     * ```
     * $rules->add($rules->isUnique(['email'], 'The email should be unique'));
     * ```
     *
     * @param array $fields The list of fields to check for uniqueness.
     * @param string|array|null $message The error message to show in case the rule does not pass. Can
     *   also be an array of options. When an array, the 'message' key can be used to provide a message.
     * @return callable
     */
    public function isUnique(array $fields, $message = null)
    {
        $options = [];
        if (is_array($message)) {
            $options = $message + ['message' => null];
            $message = $options['message'];
            unset($options['message']);
        }
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
     * @param string|array $field The field or list of fields to check for existence by
     * primary key lookup in the other table.
     * @param object|string $table The table name where the fields existence will be checked.
     * @param string|null $message The error message to show in case the rule does not pass.
     * @return callable
     */
    public function existsIn($field, $table, $message = null)
    {
        if (!$message) {
            if ($this->_useI18n) {
                $message = __d('cake', 'This value does not exist');
            } else {
                $message = 'This value does not exist';
            }
        }

        $errorField = is_string($field) ? $field : current($field);
        return $this->_addError(new ExistsIn($field, $table), '_existsIn', compact('errorField', 'message'));
    }

    /**
     * Validates whether links to the given association exist.
     *
     * ### Example:
     *
     * ```
     * $rules->update($rules->isLinkedTo('Articles', 'article'));
     * ```
     *
     * On a `Comments` table that has a `belongsTo Articles` association, this check would ensure that comments
     * can only be edited as long as they are associated to an existing article.
     *
     * @param \Cake\ORM\Association|\Cake\ORM\Table|string $association The association to check for links.
     * @param string|null $field The name of the association property. When supplied, this is the name used to set
     *  possible errors. When absent, no error message will be set.
     * @param string|null $message The error message to show in case the rule does not pass.
     * @return callable
     */
    public function isLinkedTo($association, $field = null, $message = null)
    {
        return $this->_addLinkConstraintRule(
            $association,
            $field,
            $message,
            LinkConstraint::LINK_STATUS_LINKED,
            '_isLinkedTo'
        );
    }

    /**
     * Validates whether links to the given association do not exist.
     *
     * ### Example:
     *
     * ```
     * $rules->delete($rules->isNotLinkedTo('Comments', 'comments'));
     * ```
     *
     * On a `Articles` table that has a `hasMany Comments` association, this check would ensure that articles
     * can only be deleted when no associated comments exist.
     *
     * @param \Cake\ORM\Association|\Cake\ORM\Table|string $association The association to check for links.
     * @param string|null $field The name of the association property. When supplied, this is the name used to set
     *  possible errors. When absent, no error message will be set.
     * @param string|null $message The error message to show in case the rule does not pass.
     * @return callable
     */
    public function isNotLinkedTo($association, $field = null, $message = null)
    {
        return $this->_addLinkConstraintRule(
            $association,
            $field,
            $message,
            LinkConstraint::LINK_STATUS_NOT_LINKED,
            '_isNotLinkedTo'
        );
    }

    /**
     * Adds a link constraint rule.
     *
     * @see \Cake\ORM\RulesChecker::isLinkedTo()
     * @see \Cake\ORM\RulesChecker::isNotLinkedTo()
     * @see \Cake\ORM\Rule\LinkConstraint::LINK_STATUS_LINKED
     * @see \Cake\ORM\Rule\LinkConstraint::LINK_STATUS_NOT_LINKED
     *
     * @param \Cake\ORM\Association|\Cake\ORM\Table|string $association The association to check for links.
     * @param string|null $field The name of the association property. When supplied, this is the name used to set
     *  possible errors. When absent, no error message will be set.
     * @param string|null $message The error message to show in case the rule does not pass.
     * @param string $linkStatus The ink status required for the check to pass.
     * @param string $ruleName The alias/name of the rule.
     * @return callable
     */
    protected function _addLinkConstraintRule($association, $field, $message, $linkStatus, $ruleName)
    {
        if ($association instanceof Table) {
            $association = $association->registryAlias();
        }

        if (!$message) {
            if ($this->_useI18n) {
                $message = __d('cake', 'Cannot modify row: a constraint for the `%s` association fails');
            } else {
                $message = 'Cannot modify row: a constraint for the `%s` association fails';
            }
        }

        $message = sprintf($message, is_string($association) ? $association : $association->registryAlias());

        $errorField = $field;
        $rule = new LinkConstraint(
            $association,
            $linkStatus,
            LinkConstraint::ERROR_MODE_RETURN_VALUE
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
     * @return callable
     */
    public function validCount($field, $count = 0, $operator = '>', $message = null)
    {
        if (!$message) {
            if ($this->_useI18n) {
                $message = __d('cake', 'The count does not match {0}{1}', [$operator, $count]);
            } else {
                $message = sprintf('The count does not match %s%d', $operator, $count);
            }
        }

        $errorField = $field;
        return $this->_addError(new ValidCount($field, $count, $operator), '_validCount', compact('count', 'operator', 'errorField', 'message'));
    }
}
