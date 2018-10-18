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
 * @since         4.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

namespace Cake\Filesystem;

use CallbackFilterIterator;
use FilesystemIterator;
use RecursiveCallbackFilterIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RegexIterator;
use SplFileInfo;
use Traversable;

/**
 * @since 4.0.0
 * @internal
 */
class Filesystem
{
    public function find(string $path, $filter = null, ?int $flags = null): Traversable
    {
        $flags = $flags ?? FilesystemIterator::KEY_AS_PATHNAME
            | FilesystemIterator::CURRENT_AS_FILEINFO
            | FilesystemIterator::SKIP_DOTS;
        $directory = new FilesystemIterator($path, $flags);

        if ($filter === null) {
            return $directory;
        }

        if (is_string($filter)) {
            return new RegexIterator($directory, $filter);
        }

        return new CallbackFilterIterator($directory, $filter);
    }

    public function findRecursive(string $path, $filter = null, ?int $flags = null): Traversable
    {
        $flags = $flags ?? FilesystemIterator::KEY_AS_PATHNAME
            | FilesystemIterator::CURRENT_AS_FILEINFO
            | FilesystemIterator::SKIP_DOTS;
        $directory = new RecursiveDirectoryIterator($path, $flags);

        $dirFilter = new RecursiveCallbackFilterIterator(
            $directory,
            function (SplFileInfo $current) {
                if ($current->getFilename()[0] === '.' && $current->isDir()) {
                    return false;
                }

                return true;
            }
        );

        $flatten = new RecursiveIteratorIterator(
            $dirFilter,
            RecursiveIteratorIterator::CHILD_FIRST
        );

        if ($filter === null) {
            return $flatten;
        }

        if (is_string($filter)) {
            return new RegexIterator($flatten, $filter);
        }

        return new CallbackFilterIterator($flatten, $filter);
    }
}
