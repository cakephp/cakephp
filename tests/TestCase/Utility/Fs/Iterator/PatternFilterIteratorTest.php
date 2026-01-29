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
 * @since         5.4.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Utility\Fs\Iterator;

use Cake\TestSuite\TestCase;
use Cake\Utility\Fs\Enum\DepthOperator;
use Cake\Utility\Fs\Iterator\ContainsPathFilterIterator;
use Cake\Utility\Fs\Iterator\DepthFilterIterator;
use Cake\Utility\Fs\Iterator\FilenameFilterIterator;
use Cake\Utility\Fs\Iterator\GlobFilterIterator;
use org\bovigo\vfs\vfsStream;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/**
 * Tests for pattern-based filter iterators
 */
class PatternFilterIteratorTest extends TestCase
{
    /**
     * @var \org\bovigo\vfs\vfsStreamDirectory
     */
    protected $root;

    public function setUp(): void
    {
        parent::setUp();

        $structure = [
            'src' => [
                'Controller' => [
                    'AppController.php' => '<?php',
                    'UsersController.php' => '<?php',
                ],
                'Model' => [
                    'User.php' => '<?php',
                    'Post.php' => '<?php',
                ],
                'View' => [
                    'index.ctp' => '<h1>Hello</h1>',
                    'layout.ctp' => '<html></html>',
                ],
            ],
            'tests' => [
                'TestCase' => [
                    'UserTest.php' => '<?php',
                    'PostTest.php' => '<?php',
                ],
            ],
            'README.md' => '# Project',
            'composer.json' => '{}',
        ];

        $this->root = vfsStream::setup('root', null, $structure);
    }

    /**
     * Test FilenameFilterIterator with simple pattern
     */
    public function testFilenameFilterSimplePattern(): void
    {
        $iterator = new RecursiveDirectoryIterator(
            $this->root->url(),
            RecursiveDirectoryIterator::SKIP_DOTS,
        );
        $recursiveIterator = new RecursiveIteratorIterator($iterator);
        $filtered = new FilenameFilterIterator($recursiveIterator, ['*.php']);

        $files = [];
        foreach ($filtered as $file) {
            $files[] = $file->getFilename();
        }

        $this->assertCount(6, $files);
        $this->assertContains('AppController.php', $files);
        $this->assertContains('UserTest.php', $files);
        $this->assertNotContains('index.ctp', $files);
        $this->assertNotContains('README.md', $files);
    }

    /**
     * Test FilenameFilterIterator with multiple patterns
     */
    public function testFilenameFilterMultiplePatterns(): void
    {
        $iterator = new RecursiveDirectoryIterator(
            $this->root->url(),
            RecursiveDirectoryIterator::SKIP_DOTS,
        );
        $recursiveIterator = new RecursiveIteratorIterator($iterator);
        $filtered = new FilenameFilterIterator($recursiveIterator, ['*.md', '*.json']);

        $files = [];
        foreach ($filtered as $file) {
            $files[] = $file->getFilename();
        }

        $this->assertCount(2, $files);
        $this->assertContains('README.md', $files);
        $this->assertContains('composer.json', $files);
    }

    /**
     * Test ContainsPathFilterIterator with regex patterns
     */
    public function testContainsPathFilterRegex(): void
    {
        $iterator = new RecursiveDirectoryIterator(
            $this->root->url(),
            RecursiveDirectoryIterator::SKIP_DOTS,
        );
        $filtered = new ContainsPathFilterIterator($iterator, [
            '/Controller\.php$/',
            '/Test\.php$/',
        ]);
        $recursiveIterator = new RecursiveIteratorIterator($filtered);

        $files = [];
        foreach ($recursiveIterator as $file) {
            $files[] = $file->getFilename();
        }

        $this->assertCount(4, $files);
        $this->assertContains('AppController.php', $files);
        $this->assertContains('UsersController.php', $files);
        $this->assertContains('UserTest.php', $files);
        $this->assertContains('PostTest.php', $files);
        $this->assertNotContains('User.php', $files);
        $this->assertNotContains('Post.php', $files);
    }

    /**
     * Test DepthFilterIterator with EQUAL operator
     */
    public function testDepthFilterEqual(): void
    {
        $iterator = new RecursiveDirectoryIterator(
            $this->root->url(),
            RecursiveDirectoryIterator::SKIP_DOTS,
        );
        $recursiveIterator = new RecursiveIteratorIterator($iterator);
        $filtered = new DepthFilterIterator($recursiveIterator, DepthOperator::EQUAL, 0);

        $files = [];
        foreach ($filtered as $file) {
            $files[] = $file->getFilename();
        }

        // Only root level items (2 files)
        $this->assertCount(2, $files);
        $this->assertContains('README.md', $files);
        $this->assertContains('composer.json', $files);
    }

    /**
     * Test DepthFilterIterator with GREATER_THAN operator
     */
    public function testDepthFilterGreaterThan(): void
    {
        $iterator = new RecursiveDirectoryIterator(
            $this->root->url(),
            RecursiveDirectoryIterator::SKIP_DOTS,
        );
        $recursiveIterator = new RecursiveIteratorIterator($iterator);
        $filtered = new DepthFilterIterator($recursiveIterator, DepthOperator::GREATER_THAN, 1);

        $files = [];
        foreach ($filtered as $file) {
            if ($file->isFile()) {
                $files[] = $file->getFilename();
            }
        }

        // Files at depth > 1 (inside Controller, Model, View, TestCase directories)
        $this->assertCount(8, $files);
        $this->assertContains('AppController.php', $files);
        $this->assertContains('UserTest.php', $files);
    }

    /**
     * Test DepthFilterIterator with LESS_THAN_OR_EQUAL operator
     */
    public function testDepthFilterLessThanOrEqual(): void
    {
        $iterator = new RecursiveDirectoryIterator(
            $this->root->url(),
            RecursiveDirectoryIterator::SKIP_DOTS,
        );
        $recursiveIterator = new RecursiveIteratorIterator($iterator);
        $filtered = new DepthFilterIterator($recursiveIterator, DepthOperator::LESS_THAN_OR_EQUAL, 0);

        $files = [];
        foreach ($filtered as $file) {
            if ($file->isFile()) {
                $files[] = $file->getFilename();
            }
        }

        // Only files at depth 0
        $this->assertCount(2, $files);
        $this->assertContains('README.md', $files);
        $this->assertContains('composer.json', $files);
    }

    /**
     * Test DepthFilterIterator with NOT_EQUAL operator
     */
    public function testDepthFilterNotEqual(): void
    {
        $iterator = new RecursiveDirectoryIterator(
            $this->root->url(),
            RecursiveDirectoryIterator::SKIP_DOTS,
        );
        $recursiveIterator = new RecursiveIteratorIterator($iterator);
        $filtered = new DepthFilterIterator($recursiveIterator, DepthOperator::NOT_EQUAL, 0);

        $files = [];
        foreach ($filtered as $file) {
            if ($file->isFile()) {
                $files[] = $file->getFilename();
            }
        }

        // All files except depth 0 (excludes README.md and composer.json, only 8 files at depth 2)
        $this->assertCount(8, $files);
        $this->assertContains('AppController.php', $files);
        $this->assertContains('UserTest.php', $files);
        $this->assertNotContains('README.md', $files);
        $this->assertNotContains('composer.json', $files);
    }

    /**
     * Test DepthFilterIterator with LESS_THAN operator
     */
    public function testDepthFilterLessThan(): void
    {
        $iterator = new RecursiveDirectoryIterator(
            $this->root->url(),
            RecursiveDirectoryIterator::SKIP_DOTS,
        );
        $recursiveIterator = new RecursiveIteratorIterator($iterator);
        $filtered = new DepthFilterIterator($recursiveIterator, DepthOperator::LESS_THAN, 1);

        $files = [];
        foreach ($filtered as $file) {
            if ($file->isFile()) {
                $files[] = $file->getFilename();
            }
        }

        // Only files at depth < 1 (depth 0)
        $this->assertCount(2, $files);
        $this->assertContains('README.md', $files);
        $this->assertContains('composer.json', $files);
    }

    /**
     * Test DepthFilterIterator with GREATER_THAN_OR_EQUAL operator
     */
    public function testDepthFilterGreaterThanOrEqual(): void
    {
        $iterator = new RecursiveDirectoryIterator(
            $this->root->url(),
            RecursiveDirectoryIterator::SKIP_DOTS,
        );
        $recursiveIterator = new RecursiveIteratorIterator($iterator);
        $filtered = new DepthFilterIterator($recursiveIterator, DepthOperator::GREATER_THAN_OR_EQUAL, 2);

        $files = [];
        foreach ($filtered as $file) {
            if ($file->isFile()) {
                $files[] = $file->getFilename();
            }
        }

        // Files at depth >= 2 (inside Controller, Model, View, TestCase directories)
        $this->assertCount(8, $files);
        $this->assertContains('AppController.php', $files);
        $this->assertContains('UsersController.php', $files);
        $this->assertContains('User.php', $files);
        $this->assertContains('Post.php', $files);
    }

    /**
     * Test FilenameFilterIterator with negate parameter (single pattern)
     */
    public function testFilenameFilterNegatedSinglePattern(): void
    {
        $iterator = new RecursiveDirectoryIterator(
            $this->root->url(),
            RecursiveDirectoryIterator::SKIP_DOTS,
        );
        $recursiveIterator = new RecursiveIteratorIterator($iterator);
        $filtered = new FilenameFilterIterator($recursiveIterator, ['*Test.php'], negate: true);

        $files = [];
        foreach ($filtered as $file) {
            $files[] = $file->getFilename();
        }

        // Should exclude UserTest.php and PostTest.php
        $this->assertNotContains('UserTest.php', $files);
        $this->assertNotContains('PostTest.php', $files);
        $this->assertContains('AppController.php', $files);
        $this->assertContains('User.php', $files);
    }

    /**
     * Test FilenameFilterIterator with negate parameter (multiple patterns)
     */
    public function testFilenameFilterNegatedMultiplePatterns(): void
    {
        $iterator = new RecursiveDirectoryIterator(
            $this->root->url(),
            RecursiveDirectoryIterator::SKIP_DOTS,
        );
        $recursiveIterator = new RecursiveIteratorIterator($iterator);
        $filtered = new FilenameFilterIterator($recursiveIterator, ['*.md', '*.json'], negate: true);

        $files = [];
        foreach ($filtered as $file) {
            $files[] = $file->getFilename();
        }

        $this->assertNotContains('README.md', $files);
        $this->assertNotContains('composer.json', $files);
        $this->assertContains('AppController.php', $files);
    }

    /**
     * Test ContainsPathFilterIterator with negate parameter (single pattern)
     */
    public function testContainsPathFilterNegatedSinglePattern(): void
    {
        $iterator = new RecursiveDirectoryIterator(
            $this->root->url(),
            RecursiveDirectoryIterator::SKIP_DOTS,
        );
        $filtered = new ContainsPathFilterIterator($iterator, ['Controller'], negate: true);

        $recursiveIterator = new RecursiveIteratorIterator($filtered);
        $files = [];
        foreach ($recursiveIterator as $file) {
            if ($file->isFile()) {
                $files[] = $file->getFilename();
            }
        }

        // Should exclude files with 'Controller' in path
        $this->assertNotContains('AppController.php', $files);
        $this->assertNotContains('UsersController.php', $files);
        $this->assertContains('User.php', $files);
        $this->assertContains('UserTest.php', $files);
    }

    /**
     * Test ContainsPathFilterIterator with negate allows directory traversal
     */
    public function testContainsPathFilterNegatedAllowsDirectoryTraversal(): void
    {
        $iterator = new RecursiveDirectoryIterator(
            $this->root->url(),
            RecursiveDirectoryIterator::SKIP_DOTS,
        );
        $filtered = new ContainsPathFilterIterator($iterator, ['Controller'], negate: true);

        $recursiveIterator = new RecursiveIteratorIterator($filtered);
        $files = [];
        foreach ($recursiveIterator as $file) {
            if ($file->isFile()) {
                $files[] = $file->getFilename();
            }
        }

        // Should still find files in other directories even though traversing through src
        $this->assertGreaterThan(0, $files);
        $this->assertContains('User.php', $files);
    }

    /**
     * Test ContainsPathFilterIterator with single pattern
     */
    public function testContainsPathFilterSinglePattern(): void
    {
        $iterator = new RecursiveDirectoryIterator(
            $this->root->url(),
            RecursiveDirectoryIterator::SKIP_DOTS,
        );
        $filtered = new ContainsPathFilterIterator($iterator, ['Model']);
        $recursiveIterator = new RecursiveIteratorIterator($filtered);

        $files = [];
        foreach ($recursiveIterator as $file) {
            $files[] = $file->getFilename();
        }

        // Should only include files with 'Model' in path
        $this->assertCount(2, $files);
        $this->assertContains('User.php', $files);
        $this->assertContains('Post.php', $files);
    }

    /**
     * Test ContainsPathFilterIterator with multiple patterns (OR logic)
     */
    public function testContainsPathFilterMultiplePatterns(): void
    {
        $iterator = new RecursiveDirectoryIterator(
            $this->root->url(),
            RecursiveDirectoryIterator::SKIP_DOTS,
        );
        $filtered = new ContainsPathFilterIterator($iterator, ['Controller', 'TestCase']);
        $recursiveIterator = new RecursiveIteratorIterator($filtered);

        $files = [];
        foreach ($recursiveIterator as $file) {
            $files[] = $file->getFilename();
        }

        // Should include files with either 'Controller' or 'TestCase' in path
        $this->assertContains('AppController.php', $files);
        $this->assertContains('UsersController.php', $files);
        $this->assertContains('UserTest.php', $files);
        $this->assertContains('PostTest.php', $files);
        $this->assertNotContains('User.php', $files); // In Model, not Controller/TestCase
    }

    /**
     * Test ContainsPathFilterIterator with mixed string and regex patterns
     */
    public function testContainsPathFilterMixedPatterns(): void
    {
        $iterator = new RecursiveDirectoryIterator(
            $this->root->url(),
            RecursiveDirectoryIterator::SKIP_DOTS,
        );
        // Mix string pattern 'TestCase' with regex pattern matching Controller files
        $filtered = new ContainsPathFilterIterator($iterator, ['TestCase', '/Controller\.php$/']);
        $recursiveIterator = new RecursiveIteratorIterator($filtered);

        $files = [];
        foreach ($recursiveIterator as $file) {
            $files[] = $file->getFilename();
        }

        // Should include files matching either pattern
        $this->assertContains('AppController.php', $files);
        $this->assertContains('UsersController.php', $files);
        $this->assertContains('UserTest.php', $files);
        $this->assertContains('PostTest.php', $files);
        $this->assertNotContains('User.php', $files);
        $this->assertNotContains('Post.php', $files);
    }

    /**
     * Test GlobFilterIterator with simple pattern
     */
    public function testGlobFilterSimplePattern(): void
    {
        $iterator = new RecursiveDirectoryIterator(
            $this->root->url(),
            RecursiveDirectoryIterator::SKIP_DOTS,
        );
        $recursiveIterator = new RecursiveIteratorIterator($iterator);
        $filtered = new GlobFilterIterator($recursiveIterator, ['src/**/*.php'], $this->root->url());

        $files = [];
        foreach ($filtered as $file) {
            $files[] = $file->getFilename();
        }

        // Should match all PHP files under src/
        $this->assertContains('AppController.php', $files);
        $this->assertContains('User.php', $files);
        $this->assertNotContains('UserTest.php', $files); // In tests/, not src/
    }

    /**
     * Test GlobFilterIterator with multiple patterns
     */
    public function testGlobFilterMultiplePatterns(): void
    {
        $iterator = new RecursiveDirectoryIterator(
            $this->root->url(),
            RecursiveDirectoryIterator::SKIP_DOTS,
        );
        $recursiveIterator = new RecursiveIteratorIterator($iterator);
        $filtered = new GlobFilterIterator(
            $recursiveIterator,
            ['**/*Test.php', '*.md'],
            $this->root->url(),
        );

        $files = [];
        foreach ($filtered as $file) {
            $files[] = $file->getFilename();
        }

        // Should match test files and markdown files
        $this->assertContains('UserTest.php', $files);
        $this->assertContains('PostTest.php', $files);
        $this->assertContains('README.md', $files);
        $this->assertNotContains('User.php', $files);
    }
}
