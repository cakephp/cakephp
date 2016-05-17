<?php
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @since         3.0.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace TestApp\Model\Table;

use Cake\Core\Exception\Exception;
use Cake\ORM\Query;
use Cake\ORM\Table;

/**
 * AuthUser class
 *
 */
class AuthUsersTable extends Table
{

    /**
     * Custom finder
     *
     * @param \Cake\ORM\Query $query The query to find with
     * @param array $options The options to find with
     * @return \Cake\ORM\Query The query builder
     */
    public function findAuth(Query $query, array $options)
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
     * @param \Cake\ORM\Query $query The query to find with
     * @param array $options The options to find with
     * @return \Cake\ORM\Query The query builder
     */
    public function findUsername(Query $query, array $options)
    {
        if (empty($options['username'])) {
            throw new Exception(__('Username not defined'));
        }

        $query = $this->find()
            ->where(['username' => $options['username']])
            ->select(['id', 'username', 'password']);

        return $query;
    }
}
