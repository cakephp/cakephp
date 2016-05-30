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
     * @param string|null $message The error message to show in case the rule does not pass.
     * @return callable
     */
    public function isUnique(array $fields, $message = null)
    {
        if (!$message) {
            if ($this->_useI18n) {
                $message = __d('cake', 'This value is already in use');
            } else {
                $message = 'This value is already in use';
            }
        }

        $errorField = current($fields);
        return $this->_addError(new IsUnique($fields), '_isUnique', compact('errorField', 'message'));
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
     * Available $options are error 'message' and 'allowPartialNulls' flag.
     * 'message' sets a custom error message.
     * Set 'allowPartialNulls' to true to accept composite foreign keys where one or more nullable columns are null.'
     *
     * @param string|array $field The field or list of fields to check for existence by
     * primary key lookup in the other table.
     * @param object|string $table The table name where the fields existence will be checked.
     * @param array|string|null $options List of options or error message string to show in case the rule does not pass.
     * @return callable
     */
    public function existsIn($field, $table, $options = null)
    {
        if (is_string($options)) {
            $options = ['message' => $options];
        }

        $options = (array)$options + ['message' => null];
        $message = $options['message'];
        unset($options['message']);

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
