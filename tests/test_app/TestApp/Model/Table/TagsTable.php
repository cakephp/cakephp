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
use Cake\Utility\Text;

/**
 * Tag table class
 */
class TagsTable extends Table
{
    public function initialize(array $config): void
    {
        $this->belongsTo('Authors');
        $this->belongsToMany('Articles');
        $this->hasMany('ArticlesTags', ['propertyName' => 'extraInfo']);
    }

    public function findSlugged(SelectQuery $query): SelectQuery
    {
        return $query->applyOptions(['preserveKeys' => true])
            ->formatResults(function ($results) {
                return $results->indexBy(function ($record) {
                    return Text::slug($record->name);
                });
            });
    }
}
