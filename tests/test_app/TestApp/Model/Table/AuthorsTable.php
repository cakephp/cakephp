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

use Cake\ORM\Query;
use Cake\ORM\Table;

/**
 * Author table class
 */
class AuthorsTable extends Table
{

    public function initialize(array $config)
    {
        $this->hasMany('articles');
    }

    public function findByAuthor(Query $query, array $options = [])
    {
        if (isset($options['author_id'])) {
            $query->where(['Articles.id' => $options['author_id']]);
        }

        return $query;
    }
}
