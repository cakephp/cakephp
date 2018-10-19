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

use Cake\Core\Exception\Exception;
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

    public function dumpFile(string $filename, string $content): void
    {
        $dir = dirname($filename);
        if (!is_dir($dir)) {
            $this->mkdir($dir);
        }

        $exits = file_exists($filename);

        if ($this->isStream($filename)) {
            file_put_contents($filename, $content);
        } else {
            file_put_contents($filename, $content, LOCK_EX);
        }

        if (!$exits) {
            chmod($filename, 0666 & ~umask());
        }
    }

    public function mkdir(string $dir, int $mode = 0755): void
    {
        if (is_dir($dir)) {
            return;
        }

        $old = umask(0);
        // @codingStandardsIgnoreLine
        if (@mkdir($dir, $mode, true) === false) {
            umask($old);
            throw new Exception(sprintf('Failed to create directory "%s"', $dir));
        }

        umask($old);
    }

    public function deleteDir(string $path): bool
    {
        if (!file_exists($path)) {
            return true;
        }

        if (!is_dir($path)) {
            throw new Exception(sprintf('"%s" is not a directory', $path));
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );

        $result = true;
        foreach ($iterator as $fileInfo) {
            switch ($fileInfo->getType()) {
                case 'dir':
                    // @codingStandardsIgnoreLine
                    $result = $result && @rmdir($fileInfo->getPathname());
                    break;
                case 'link':
                    // @codingStandardsIgnoreLine
                    $result = $result && @unlink($fileInfo->getPathname());
                    break;
                default:
                    // @codingStandardsIgnoreLine
                    $result = $result && @unlink($fileInfo->getPathname());
            }
        }

        // @codingStandardsIgnoreLine
        $result = $result && @rmdir($path);

        return $result;
    }

    public function copyDir(string $source, string $destination): bool
    {
        $destination = (new SplFileInfo($destination))->getPathname();

        if (!is_dir($destination)) {
            $this->mkdir($destination);
        }

        $iterator = new FilesystemIterator($source);

        $result = true;
        foreach ($iterator as $fileInfo) {
            if ($fileInfo->isDir()) {
                $result = $result && $this->copyDir(
                    $fileInfo->getPathname(),
                    $destination . DIRECTORY_SEPARATOR . $fileInfo->getFilename()
                );
            } else {
                // @codingStandardsIgnoreLine
                $result = $result && @copy(
                    $fileInfo->getPathname(),
                    $destination . DIRECTORY_SEPARATOR . $fileInfo->getFilename()
                );
            }
        }

        return $result;
    }

    public function isStream(string $path): bool
    {
        return strpos($path, '://') !== false;
    }
}
