<?php
declare(strict_types=1);

/**
 * Test App SluggedPosts Model
 *
 * CakePHP : Rapid Development Framework (https://cakephp.org)
 * Copyright 2005-2020, Cake Software Foundation, Inc.
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2020, Cake Software Foundation, Inc.
 * @link          https://cakephp.org CakePHP Project
 * @since         4.1.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace TestApp\Model\Table;

use Cake\ORM\Table;

class SluggedPostsTable extends Table
{
    public function initialize(array $config): void
    {
        $this->setTable('posts');
        $this->addBehavior('Sluggable');
    }
}
