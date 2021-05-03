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

use Cake\Core\Exception\CakeException;
use CallbackFilterIterator;
use FilesystemIterator;
use Iterator;
use RecursiveCallbackFilterIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RegexIterator;
use SplFileInfo;

/**
 * This provides convenience wrappers around common filesystem queries.
 *
 * This is an internal helper class that should not be used in application code
 * as it provides no guarantee for compatibility.
 *
 * @internal
 */
class Filesystem
{
    /**
     * Directory type constant
     *
     * @var string
     */
    public const TYPE_DIR = 'dir';

    /**
     * Find files / directories (non-recursively) in given directory path.
     *
     * @param string $path Directory path.
     * @param mixed $filter If string will be used as regex for filtering using
     *   `RegexIterator`, if callable will be as callback for `CallbackFilterIterator`.
     * @param int|null $flags Flags for FilesystemIterator::__construct();
     * @return \Iterator
     */
    public function find(string $path, $filter = null, ?int $flags = null): Iterator
    {
        $flags = $flags ?? FilesystemIterator::KEY_AS_PATHNAME
            | FilesystemIterator::CURRENT_AS_FILEINFO
            | FilesystemIterator::SKIP_DOTS;
        $directory = new FilesystemIterator($path, $flags);

        if ($filter === null) {
            return $directory;
        }

        return $this->filterIterator($directory, $filter);
    }

    /**
     * Find files/ directories recursively in given directory path.
     *
     * @param string $path Directory path.
     * @param mixed $filter If string will be used as regex for filtering using
     *   `RegexIterator`, if callable will be as callback for `CallbackFilterIterator`.
     *   Hidden directories (starting with dot e.g. .git) are always skipped.
     * @param int|null $flags Flags for FilesystemIterator::__construct();
     * @return \Iterator
     */
    public function findRecursive(string $path, $filter = null, ?int $flags = null): Iterator
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

        return $this->filterIterator($flatten, $filter);
    }

    /**
     * Wrap iterator in additional filtering iterator.
     *
     * @param \Iterator $iterator Iterator
     * @param mixed $filter Regex string or callback.
     * @return \Iterator
     */
    protected function filterIterator(Iterator $iterator, $filter): Iterator
    {
        if (is_string($filter)) {
            return new RegexIterator($iterator, $filter);
        }

        return new CallbackFilterIterator($iterator, $filter);
    }

    /**
     * Dump contents to file.
     *
     * @param string $filename File path.
     * @param string $content Content to dump.
     * @return void
     * @throws \Cake\Core\Exception\CakeException When dumping fails.
     */
    public function dumpFile(string $filename, string $content): void
    {
        $dir = dirname($filename);
        if (!is_dir($dir)) {
            $this->mkdir($dir);
        }

        $exists = file_exists($filename);

        if ($this->isStream($filename)) {
            // phpcs:ignore
            $success = @file_put_contents($filename, $content);
        } else {
            // phpcs:ignore
            $success = @file_put_contents($filename, $content, LOCK_EX);
        }

        if ($success === false) {
            throw new CakeException(sprintf('Failed dumping content to file `%s`', $dir));
        }

        if (!$exists) {
            chmod($filename, 0666 & ~umask());
        }
    }

    /**
     * Create directory.
     *
     * @param string $dir Directory path.
     * @param int $mode Octal mode passed to mkdir(). Defaults to 0755.
     * @return void
     * @throws \Cake\Core\Exception\CakeException When directory creation fails.
     */
    public function mkdir(string $dir, int $mode = 0755): void
    {
        if (is_dir($dir)) {
            return;
        }

        $old = umask(0);
        // phpcs:ignore
        if (@mkdir($dir, $mode, true) === false) {
            umask($old);
            throw new CakeException(sprintf('Failed to create directory "%s"', $dir));
        }

        umask($old);
    }

    /**
     * Delete directory along with all it's contents.
     *
     * @param string $path Directory path.
     * @return bool
     * @throws \Cake\Core\Exception\CakeException If path is not a directory.
     */
    public function deleteDir(string $path): bool
    {
        if (!file_exists($path)) {
            return true;
        }

        if (!is_dir($path)) {
            throw new CakeException(sprintf('"%s" is not a directory', $path));
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );

        $result = true;
        foreach ($iterator as $fileInfo) {
            $isWindowsLink = DS === '\\' && $fileInfo->getType() === 'link';
            if ($fileInfo->getType() === self::TYPE_DIR || $isWindowsLink) {
                // phpcs:ignore
                $result = $result && @rmdir($fileInfo->getPathname());
                unset($fileInfo);
                continue;
            }

            // phpcs:ignore
            $result = $result && @unlink($fileInfo->getPathname());
            // possible inner iterators need to be unset too in order for locks on parents to be released
            unset($fileInfo);
        }

        // unsetting iterators helps releasing possible locks in certain environments,
        // which could otherwise make `rmdir()` fail
        unset($iterator);

        // phpcs:ignore
        $result = $result && @rmdir($path);

        return $result;
    }

    /**
     * Copies directory with all it's contents.
     *
     * @param string $source Source path.
     * @param string $destination Destination path.
     * @return bool
     */
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
                // phpcs:ignore
                $result = $result && @copy(
                    $fileInfo->getPathname(),
                    $destination . DIRECTORY_SEPARATOR . $fileInfo->getFilename()
                );
            }
        }

        return $result;
    }

    /**
     * Check whether the given path is a stream path.
     *
     * @param string $path Path.
     * @return bool
     */
    public function isStream(string $path): bool
    {
        return strpos($path, '://') !== false;
    }
}
