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

use Cake\ORM\Query\SelectQuery;
use Cake\ORM\Table;

/**
 * Article table class
 */
class ArticlesTable extends Table
{
    public function initialize(array $config): void
    {
        $this->belongsTo('Authors');
        $this->belongsToMany('Tags');
        $this->hasMany('ArticlesTags');
    }

    /**
     * Find published
     *
     * @param \Cake\ORM\Query\SelectQuery $query The query
     * @param array<string, mixed> $options The options
     */
    public function findPublished($query, ?string $title = null): SelectQuery
    {
        $query = $query->where([$this->aliasField('published') => 'Y']);

        if ($title !== null) {
            $query->andWhere([$this->aliasField('title') => $title]);
        }

        return $query;
    }

    /**
     * Find articles and eager load authors.
     *
     * @param \Cake\ORM\Query\SelectQuery $query The query
     * @param array<string, mixed> $options The options
     */
    public function findWithAuthors($query, array $options = []): SelectQuery
    {
        return $query->contain('Authors');
    }

    /**
     * Finder for testing named parameter compatibility
     */
    public function findTitled(SelectQuery $query, array $options): SelectQuery
    {
        if (!empty($options['title'])) {
            $query->where(['Articles.title' => $options['title']]);
        }

        return $query;
    }

    /**
     * Example public method
     */
    public function doSomething(): void
    {
    }

    /**
     * Example Secondary public method
     */
    public function doSomethingElse(): void
    {
    }

    /**
     * Example protected method
     */
    protected function _innerMethod(): void
    {
    }
}
