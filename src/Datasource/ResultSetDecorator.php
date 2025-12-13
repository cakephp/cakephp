<?php
declare(strict_types=1);

/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         3.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Datasource;

use Cake\Collection\Collection;
use Cake\Core\Configure;

/**
 * Generic ResultSet decorator. This will make any traversable object appear to
 * be a database result
 *
 * @template TKey
 * @template TValue
 * @extends \Cake\Collection\Collection<TKey, TValue>
 * @implements \Cake\Datasource\ResultSetInterface<TKey, TValue>
 */
class ResultSetDecorator extends Collection implements ResultSetInterface
{
    /**
     * @inheritDoc
     */
    public function __debugInfo(): array
    {
        $parentInfo = parent::__debugInfo();
        $limit = Configure::read('App.ResultSetDebugLimit', 10);

        return array_merge($parentInfo, ['items' => $this->take($limit)->toArray()]);
    }
}
