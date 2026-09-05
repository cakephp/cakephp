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
 * @since         5.4.2
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\TestSuite;

/**
 * Materializes a directory tree under a real temporary directory so tests
 * can exercise real iterators against a known fixture layout.
 */
final class FsFixture
{
    /**
     * Create a root directory containing `$structure` and return its
     * absolute path. Any pre-existing root with the same name is removed.
     *
     * @param string $rootDirName Name of the root directory.
     * @param array $structure Nested arrays create directories; string values
     *   are written as file contents.
     * @return string Absolute path to the new root directory.
     */
    public static function setup(string $rootDirName, array $structure = []): string
    {
        $path = self::baseDir() . DS . $rootDirName;
        self::remove($path);
        mkdir($path, 0777, true);
        self::create($structure, $path);

        return $path;
    }

    /**
     * Build the absolute path for a subpath of a previously created root.
     *
     * @param string $name Root name and optional relative path, e.g. `root/src`.
     * @return string
     */
    public static function path(string $name): string
    {
        return self::baseDir() . DS . str_replace('/', DS, $name);
    }

    /**
     * Add files and directories to an existing root.
     *
     * @param array $structure Nested arrays create directories; string values
     *   are written as file contents.
     * @param string $parent Absolute path of the directory to create in.
     * @return void
     */
    public static function create(array $structure, string $parent): void
    {
        foreach ($structure as $name => $content) {
            $path = $parent . DS . $name;
if (is_array($content)) {
    if (!is_dir($path)) {
        mkdir($path, 0777, true);
    }
    self::create($content, $path);
} else {
    file_put_contents($path, (string)$content);
}
        }
    }

    /**
     * Remove the per-process root and all its contents.
     *
     * @return void
     */
    public static function tearDown(): void
    {
        self::remove(self::baseDir());
    }

    /**
     * @return string
     */
    private static function baseDir(): string
    {
        return sys_get_temp_dir() . DS . 'cakephp-fs-' . getmypid();
    }

    /**
     * Recursively delete a path if it exists.
     *
     * @param string $path
     * @return void
     */
    private static function remove(string $path): void
    {
        if (!is_dir($path)) {
            return;
        }
        foreach (scandir($path) as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            $itemPath = $path . DS . $item;
            if (is_dir($itemPath) && !is_link($itemPath)) {
                self::remove($itemPath);
            } else {
                unlink($itemPath);
            }
        }
        rmdir($path);
    }
}
