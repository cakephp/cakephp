<?php
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
 * @since         3.6.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Routing\Route;

use Cake\Routing\Route\EntityRoute;
use Cake\TestSuite\TestCase;
use TestApp\Model\Entity\Article;

/**
 * Test case for EntityRoute
 */
class EntityRouteTest extends TestCase
{
    /**
     * test that routes match their pattern.
     *
     * @return void
     */
    public function testMatchBasic()
    {
        $entity = new Article([
            'category_id' => 2,
            'slug' => 'article-slug'
        ]);

        $route = $route = new EntityRoute(
            '/articles/:category_id/:slug',
            [
                '_name' => 'articlesView',
                '_entity' => $entity,
                'controller' => 'articles',
                'action' => 'view'
            ]
        );

        $result = $route->match([
            '_entity' => $entity,
            '_name' => 'articlesView',
            'controller' => 'articles',
            'action' => 'view'
        ]);

        $this->assertEquals('/articles/2/article-slug', $result);
    }

    /**
     * Test invalid entity option value
     *
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Route `/` expects the URL option `_entity` to be `Cake\Datasource\EntityInterface`, but `string` passed.
     */
    public function testInvalidEntityValueException()
    {
        $route = $route = new EntityRoute('/',
            [
                '_name' => 'articlesView',
                '_entity' => 'Something else',
            ]
        );

        $route->match([
            '_entity' => 'something-else',
            '_name' => 'articlesView',
        ]);
    }
}
