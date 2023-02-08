<?php
declare(strict_types=1);

/**
 * MergeShell file
 *
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         3.0.8
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

namespace TestApp\Shell;

use Cake\Console\Shell;

/**
 * for testing merging vars
 */
class MergeShell extends Shell
{
    /**
     * @var array
     */
    public $tasks = ['DbConfig', 'Fixture'];

    /**
     * @var string
     */
    protected $modelClass = 'Articles';

    /**
     * @var \TestApp\Model\Table\ArticlesTable
     */
    public $Articles;
}
