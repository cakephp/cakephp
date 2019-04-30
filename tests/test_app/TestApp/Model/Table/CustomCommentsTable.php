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
 * @since         4.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace TestApp\Model\Table;

use Cake\Datasource\EntityInterface;
use Cake\ORM\Query;
use Cake\ORM\Table;

class CustomCommentsTable extends Table
{
    /**
     * initialize hook
     *
     * @param array $config Config data.
     * @return void
     */
    public function initialize(array $config): void
    {
        $this->setTable('comments');
        $this->setAlias('Comments');
    }

    /**
     * Finder using variadic args.
     *
     * @param Query $query Query
     * @param bool $published Whether or not a comment is published.
     * @param EntityInterface $article Article.
     * @return Query
     */
    public function findPublishedArticle(Query $query, bool $published, EntityInterface $article): Query
    {
        $query->where([
            'published' => $published ? 'Y' : 'N',
            'article_id' => $article->id,
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
