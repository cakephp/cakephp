<?php
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
    /**
     * Number of times finder method is executed.
     *
     * @var int
     */
    public $finderCount = 0;

    public function initialize(array $config)
    {
        $this->belongsTo('Authors');
        $this->belongsToMany('Tags');
        $this->hasMany('ArticlesTags');
    }

    /**
     * Find published
     *
     * @param \Cake\ORM\Query $query The query
     * @return \Cake\ORM\Query
     */
    public function findPublished($query)
    {
        $this->finderCount++;
        return $query->where(['published' => 'Y']);
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
