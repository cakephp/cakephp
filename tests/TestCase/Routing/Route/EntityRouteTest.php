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
     * test that route keys take precedence to object properties.
     *
     * @return void
     */
    public function testMatchRouteKeyPrecedence()
    {
        $entity = new Article([
            'category_id' => 2,
            'slug' => 'article-slug'
        ]);

        $route = $route = new EntityRoute(
            '/articles/:category_id/:slug',
            [
                '_name' => 'articlesView',
            ]
        );

        $result = $route->match([
            'slug' => 'other-slug',
            '_entity' => $entity,
            '_name' => 'articlesView'
        ]);

        $this->assertEquals('/articles/2/other-slug', $result);
    }

    /**
     * test that routes match their pattern.
     *
     * @return void
     */
    public function testMatchEntityObject()
    {
        $entity = new Article([
            'category_id' => 2,
            'slug' => 'article-slug'
        ]);

        $route = $route = new EntityRoute(
            '/articles/:category_id/:slug',
            [
                '_name' => 'articlesView',
            ]
        );

        $result = $route->match([
            '_entity' => $entity,
            '_name' => 'articlesView'
        ]);

        $this->assertEquals('/articles/2/article-slug', $result);
    }

    /**
     * test that routes match their pattern.
     *
     * @return void
     */
    public function testMatchUnderscoreBetweenVar()
    {
        $entity = new Article([
            'category_id' => 2,
            'slug' => 'article-slug'
        ]);

        $route = $route = new EntityRoute(
            '/articles/:category_id_:slug',
            [
                '_name' => 'articlesView',
            ]
        );

        $result = $route->match([
            '_entity' => $entity,
            '_name' => 'articlesView'
        ]);

        $this->assertEquals('/articles/2_article-slug', $result);
    }

    /**
     * test that routes match their pattern.
     *
     * @return void
     */
    public function testMatchingArray()
    {
        $entity = [
            'category_id' => 2,
            'slug' => 'article-slug'
        ];

        $route = new EntityRoute(
            '/articles/:category_id/:slug',
            [
                '_name' => 'articlesView',
                '_entity' => $entity
            ]
        );

        $result = $route->match([
            '_entity' => $entity,
            '_name' => 'articlesView'
        ]);

        $this->assertEquals('/articles/2/article-slug', $result);
    }

    /**
     * Test invalid entity option value
     *
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Route `/` expects the URL option `_entity` to be an array or object implementing \ArrayAccess, but `string` passed.
     */
    public function testInvalidEntityValueException()
    {
        $route = new EntityRoute('/', [
            '_entity' => 'Something else'
        ]);

        $route->match([
            '_entity' => 'something-else',
        ]);
    }
}
