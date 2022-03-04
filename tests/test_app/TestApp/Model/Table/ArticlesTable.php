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

use Cake\ORM\Query;
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
     * @param \Cake\ORM\Query $query The query
     * @param array<string, mixed> $options The options
     */
    public function findPublished($query, array $options = []): Query
    {
        $query = $query->where([$this->aliasField('published') => 'Y']);

        if (isset($options['title'])) {
            $query->andWhere([$this->aliasField('title') => $options['title']]);
        }

        return $query;
    }

    /**
     * Find articles and eager load authors.
     *
     * @param \Cake\ORM\Query $query The query
     * @param array<string, mixed> $options The options
     */
    public function findWithAuthors($query, array $options = []): Query
    {
        return $query->contain('Authors');
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
