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
     * @param \Cake\ORM\Query $query
     * @param array<string, mixed> $options
     * @return \Cake\ORM\Query
     */
    public function findByAuthor(Query $query, array $options = []): Query
    {
        if (isset($options['author_id'])) {
            $query->where(['Articles.id' => $options['author_id']]);
        }

        return $query;
    }

    /**
     * Finder that applies a formatter to test dirty associations
     *
     * @param \Cake\ORM\Query $query The query
     * @param array<string, mixed> $options The options
     */
    public function findFormatted(Query $query, array $options = []): Query
    {
        return $query->formatResults(function ($results) {
            return $results->map(function ($author) {
                $author->formatted = $author->name . '!!';

                return $author;
            });
        });
    }
}
