<?php
declare(strict_types=1);

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

use Cake\Core\Exception\CakeException;
use Cake\Routing\Route\EntityRoute;
use Cake\TestSuite\TestCase;
use TestApp\Model\Entity\Article;
use TestApp\Model\Enum\ArticleStatus;
use TestApp\Model\Enum\NonBacked;
use TestApp\Model\Enum\Priority;

/**
 * Test case for EntityRoute
 */
class EntityRouteTest extends TestCase
{
    /**
     * test that route keys take precedence to object properties.
     */
    public function testMatchRouteKeyPrecedence(): void
    {
        $entity = new Article([
            'category_id' => 2,
            'slug' => 'article-slug',
        ]);

        $route = new EntityRoute(
            '/articles/{category_id}/{slug}',
            [
                '_name' => 'articlesView',
            ],
        );

        $result = $route->match([
            'slug' => 'other-slug',
            '_entity' => $entity,
            '_name' => 'articlesView',
        ]);

        $this->assertSame('/articles/2/other-slug', $result);
    }

    /**
     * test that routes match their pattern.
     */
    public function testMatchEntityObject(): void
    {
        $entity = new Article([
            'category_id' => 2,
            'slug' => 'article-slug',
        ]);

        $route = new EntityRoute(
            '/articles/{category_id}/{slug}',
            [
                '_name' => 'articlesView',
            ],
        );

        $result = $route->match([
            '_entity' => $entity,
            '_name' => 'articlesView',
        ]);

        $this->assertSame('/articles/2/article-slug', $result);
    }

    /**
     * test that routes match their pattern.
     */
    public function testMatchBackedEnum(): void
    {
        $entity = new Article([
            'category_id' => 2,
            'published' => ArticleStatus::Published,
        ]);

        $route = new EntityRoute(
            '/articles/{category_id}/{published}',
            [
                '_name' => 'articlesView',
            ],
        );

        $result = $route->match([
            '_entity' => $entity,
            '_name' => 'articlesView',
        ]);

        $this->assertSame('/articles/2/Y', $result);
    }

    /**
     * test that routes match their pattern.
     */
    public function testMatchBackedIntEnum(): void
    {
        $entity = new Article([
            'category_id' => 2,
            'prio' => Priority::High,
        ]);

        $route = new EntityRoute(
            '/articles/{category_id}/{prio}',
            [
                '_name' => 'articlesView',
            ],
        );

        $result = $route->match([
            '_entity' => $entity,
            '_name' => 'articlesView',
        ]);

        $this->assertSame('/articles/2/3', $result);
    }

    /**
     * test that routes match their pattern.
     */
    public function testMatchNonBackedEnum(): void
    {
        $entity = new Article([
            'category_id' => 2,
            'level' => NonBacked::Advanced,
        ]);

        $route = new EntityRoute(
            '/articles/{category_id}/{level}',
            [
                '_name' => 'articlesView',
            ],
        );

        $result = $route->match([
            '_entity' => $entity,
            '_name' => 'articlesView',
        ]);

        $this->assertSame('/articles/2/Advanced', $result);
    }

    /**
     * test that routes match their pattern.
     */
    public function testMatchUnderscoreBetweenVar(): void
    {
        $entity = new Article([
            'category_id' => 2,
            'slug' => 'article-slug',
        ]);

        $route = new EntityRoute(
            '/articles/{category_id}_{slug}',
            [
                '_name' => 'articlesView',
            ],
        );

        $result = $route->match([
            '_entity' => $entity,
            '_name' => 'articlesView',
        ]);

        $this->assertSame('/articles/2_article-slug', $result);
    }

    /**
     * test that routes match their pattern.
     */
    public function testMatchingArray(): void
    {
        $entity = [
            'category_id' => 2,
            'slug' => 'article-slug',
        ];

        $route = new EntityRoute(
            '/articles/{category_id}/{slug}',
            [
                '_name' => 'articlesView',
                '_entity' => $entity,
            ],
        );

        $result = $route->match([
            '_entity' => $entity,
            '_name' => 'articlesView',
        ]);

        $this->assertSame('/articles/2/article-slug', $result);
    }

    /**
     * Test invalid entity option value
     */
    public function testInvalidEntityValueException(): void
    {
        $this->expectException(CakeException::class);
        $this->expectExceptionMessage('Route `/` expects the URL option `_entity` to be an array or object implementing \ArrayAccess, but `string` passed.');

        $route = new EntityRoute('/', [
            '_entity' => 'Something else',
        ]);

        $route->match([
            '_entity' => 'something-else',
        ]);
    }
}
