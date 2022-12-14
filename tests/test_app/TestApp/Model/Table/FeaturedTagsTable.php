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
 * @since         4.3.7
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace TestApp\Model\Table;

use Cake\ORM\Table;
use Cake\ORM\TableRegistry;

/**
 * FeaturedTags table class
 */
class FeaturedTagsTable extends Table
{
    protected $Posts;

    public function initialize(array $config): void
    {
        // Used to reproduce https://github.com/cakephp/cakephp/issues/16373
        $this->Posts = TableRegistry::getTableLocator()->get('Posts');
    }
}
