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
 * @since         4.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace TestApp\Model\Behavior;

use Cake\Datasource\EntityInterface;
use Cake\ORM\Behavior;
use Cake\ORM\Query;

class CustomFinderBehavior extends Behavior
{
    /**
     * Finder using variadic args.
     *
     * @param Query $query Query
     * @param bool $published Whether or not a comment is published.
     * @return Query
     */
    public function findPublished(Query $query, bool $published): Query
    {
        $query->where([
            'published' => $published ? 'Y' : 'N',
        ]);

        return $query;
    }

    /**
     * Finder using variadic args.
     *
     * @param Query $query Query
     * @param EntityInterface $user User entity.
     * @return Query
     */
    public function findUser(Query $query, EntityInterface $user): Query
    {
        $query->where([
            'user_id' => $user->id,
        ]);

        return $query;
    }
}
