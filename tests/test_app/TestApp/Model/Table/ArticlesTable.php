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
     * @param array $options The options
     * @return \Cake\ORM\Query
     */
    public function findPublished($query, array $options = [])
    {
        $query = $query->where([$this->aliasField('published') => 'Y']);

        if (isset($options['title'])) {
            $query->andWhere([$this->aliasField('title') => $options['title']]);
        }

        return $query;
    }

    /**
     * Example public method
     *
     * @return void
     */
    public function doSomething()
    {
    }

    /**
     * Example Secondary public method
     *
     * @return void
     */
    public function doSomethingElse()
    {
    }

    /**
     * Example protected method
     *
     * @return void
     */
    protected function _innerMethod()
    {
    }
}
