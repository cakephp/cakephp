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
 * Author table class
 */
class AuthorsTable extends Table
{
    /**
     * @param array<string, mixed> $config
     * @return void
     */
    public function initialize(array $config): void
    {
        $this->hasMany('articles');
    }

    /**
     * @param \Cake\ORM\Query\SelectQuery $query
     * @param int|null $authorId
     * @return \Cake\ORM\Query\SelectQuery
     */
    public function findByAuthor(SelectQuery $query, ?int $authorId = null): SelectQuery
    {
        if ($authorId !== null) {
            $query->where(['Articles.id' => $authorId]);
        }

        return $query;
    }

    /**
     * Finder that applies a formatter to test dirty associations
     *
     * @param \Cake\ORM\Query\SelectQuery $query The query
     * @param array<string, mixed> $options The options
     * @return \Cake\ORM\Query\SelectQuery
     */
    public function findFormatted(SelectQuery $query, array $options = []): SelectQuery
    {
        return $query->formatResults(function ($results) {
            return $results->map(function ($author) {
                $author->formatted = $author->name . '!!';

                return $author;
            });
        });
    }

    /**
     * Finder that accepts an option via a typed parameter.
     *
     * @param \Cake\ORM\Query\SelectQuery $query The query
     * @param int $id Author ID
     * @return \Cake\ORM\Query\SelectQuery
     */
    public function findWithIdArgument(SelectQuery $query, int $id): SelectQuery
    {
        return $query->where(['id' => $id]);
    }
}
