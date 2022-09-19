<?php
declare(strict_types=1);

/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @since         3.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace TestApp\Model\Table;

use Cake\Core\Exception\CakeException;
use Cake\ORM\Query\SelectQuery;
use Cake\ORM\Table;

/**
 * AuthUser class
 */
class AuthUsersTable extends Table
{
    /**
     * Custom finder
     *
     * @param \Cake\ORM\Query\SelectQuery $query The query to find with
     * @param array<string, mixed> $options The options to find with
     * @return \Cake\ORM\Query\SelectQuery The query builder
     */
    public function findAuth(SelectQuery $query, array $options): SelectQuery
    {
        $query->select(['id', 'username', 'password']);
        if (!empty($options['return_created'])) {
            $query->select(['created']);
        }

        return $query;
    }

    /**
     * Custom finder
     *
     * @param \Cake\ORM\Query\SelectQuery $query The query to find with
     * @param array<string, mixed> $options The options to find with
     * @return \Cake\ORM\Query\SelectQuery The query builder
     */
    public function findUsername(SelectQuery $query, array $options): SelectQuery
    {
        if (empty($options['username'])) {
            throw new CakeException(__('Username not defined'));
        }

        $query = $this->find()
            ->where(['username' => $options['username']])
            ->select(['id', 'username', 'password']);

        return $query;
    }
}
