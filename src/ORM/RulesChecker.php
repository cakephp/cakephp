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

        $errorField = isset($fields['errorField']) ? $fields['errorField'] : false;

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
}
