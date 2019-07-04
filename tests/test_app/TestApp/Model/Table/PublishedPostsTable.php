<?php
declare(strict_types=1);

/**
 * Test App Posts Model
 *
 * CakePHP : Rapid Development Framework (https://cakephp.org)
 * Copyright 2005-2012, Cake Software Foundation, Inc.
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2012, Cake Software Foundation, Inc.
 * @link          https://cakephp.org CakePHP Project
 * @since         3.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace TestApp\Model\Table;

use Cake\Database\Query;
use Cake\ORM\Table;

/**
 * Used for testing counter cache with custom finder
 */
class PublishedPostsTable extends Table
{
    /**
     * @param \Cake\Database\Query $query
     * @param array $options
     * @return \Cake\Database\Query
     */
    public function findPublished(Query $query, array $options)
    {
        return $query->where(['published' => true]);
    }
}
