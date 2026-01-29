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
namespace Cake\Utility\Fs;

use AppendIterator;
use Cake\Utility\Fs\Enum\DepthOperator;
use Cake\Utility\Fs\Enum\FinderMode;
use Cake\Utility\Fs\Iterator\CallbackFilterIterator;
use Cake\Utility\Fs\Iterator\ContainsPathFilterIterator;
use Cake\Utility\Fs\Iterator\DepthFilterIterator;
use Cake\Utility\Fs\Iterator\ExcludeDirectoryFilterIterator;
use Cake\Utility\Fs\Iterator\FilenameFilterIterator;
use Cake\Utility\Fs\Iterator\FileTypeFilterIterator;
use Cake\Utility\Fs\Iterator\GlobFilterIterator;
use Cake\Utility\Fs\Iterator\HiddenFileFilterIterator;
use Closure;
use FilesystemIterator;
use Iterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/**
 * Finder provides a fluent interface for finding files and directories.
 *
 * Example usage:
 * ```php
 * // Find files
 * $finder = new Finder();
 * $files = $finder
 *     ->in('src')
 *     ->name('*.php')
 *     ->exclude('vendor')
 *     ->files();
 *
 * foreach ($files as $file) {
 *     echo $file->getPathname();
 * }
 *
 * // Find directories
 * $directories = (new Finder())
 *     ->in('src')
 *     ->exclude('vendor')
 *     ->directories();
 *
 * // Find both files and directories
 * $all = (new Finder())
 *     ->in('src')
 *     ->all();
 * ```
 */
class Finder
{
    /**
     * Base paths to search in
     *
     * @var array<string>
     */
    protected array $paths = [];

    /**
     * Name patterns to match
     *
     * @var array<string>
     */
    protected array $names = [];

    /**
     * Name patterns to exclude
     *
     * @var array<string>
     */
    protected array $notNames = [];

    /**
     * Directories to exclude
     *
     * @var array<string>
     */
    protected array $exclude = [];

    /**
     * Path patterns to include
     *
     * @var array<string>
     */
    protected array $pathPatterns = [];

    /**
     * Path patterns to exclude
     *
     * @var array<string>
     */
    protected array $notPathPatterns = [];

    /**
     * Glob patterns for full path matching
     *
     * @var array<string>
     */
    protected array $globPatterns = [];

    /**
     * Depth conditions
     *
     * @var array<array{0: \Cake\Utility\Fs\Enum\DepthOperator, 1: int}>
     */
    protected array $depths = [];

    /**
     * Whether to ignore hidden files
     *
     * @var bool
     */
    protected bool $ignoreHiddenFiles = true;

    /**
     * Whether to search recursively
     *
     * @var bool
     */
    protected bool $recursive = true;

    /**
     * The iteration mode (files, directories, or all)
     *
     * @var \Cake\Utility\Fs\Enum\FinderMode|null
     */
    protected ?FinderMode $mode = null;

    /**
     * Custom filter callbacks
     *
     * @var array<\Closure(\SplFileInfo, string): bool>
     */
    protected array $filters = [];

    /**
     * Add a path to search in.
     *
     * @param string $path The directory path
     * @return $this
     */
    public function in(string $path)
    {
        $this->paths[] = $path;

        return $this;
    }

    /**
     * Add a name pattern to match.
     *
     * @param string $pattern Glob pattern (e.g., '*.php')
     * @return $this
     */
    public function name(string $pattern)
    {
        $this->names[] = $pattern;

        return $this;
    }

    /**
     * Add a name pattern that must not be matched.
     *
     * @param string $pattern Glob pattern to exclude (e.g., '*.rb', '*Test.php')
     * @return $this
     */
    public function notName(string $pattern)
    {
        $this->notNames[] = $pattern;

        return $this;
    }

    /**
     * Exclude a directory from the search.
     *
     * @param string $directory Directory name to exclude
     * @return $this
     */
    public function exclude(string $directory)
    {
        $this->exclude[] = $directory;

        return $this;
    }

    /**
     * Add a path pattern that must be matched.
     *
     * @param string $pattern Path pattern (e.g., 'Controller')
     * @return $this
     */
    public function path(string $pattern)
    {
        $this->pathPatterns[] = $pattern;

        return $this;
    }

    /**
     * Add a path pattern that must not be matched.
     *
     * @param string $pattern Path pattern to exclude
     * @return $this
     */
    public function notPath(string $pattern)
    {
        $this->notPathPatterns[] = $pattern;

        return $this;
    }

    /**
     * Add a glob pattern for full path matching.
     *
     * Supports wildcards like `src/**\/*.php` for recursive matching.
     *
     * @param string $pattern Glob pattern (e.g., 'src/**\/*.php', 'tests/**\/*Test.php')
     * @return $this
     */
    public function pattern(string $pattern)
    {
        $this->globPatterns[] = $pattern;

        return $this;
    }

    /**
     * Add a custom filter callback.
     *
     * The callback receives the SplFileInfo object and relative path,
     * and should return true to include the file.
     *
     * Example:
     * ```php
     * $finder->filter(fn(\SplFileInfo $file) => $file->getSize() > 1024);
     * ```
     *
     * @param \Closure(\SplFileInfo, string): bool $callback Filter callback
     * @return $this
     */
    public function filter(Closure $callback)
    {
        $this->filters[] = $callback;

        return $this;
    }

    /**
     * Add a depth condition.
     *
     * @param int $level The depth level (0 = top-level directory)
     * @param \Cake\Utility\Fs\Enum\DepthOperator $operator The comparison operator (default: EQUAL)
     * @return $this
     */
    public function depth(int $level, DepthOperator $operator = DepthOperator::EQUAL)
    {
        $this->depths[] = [$operator, $level];

        return $this;
    }

    /**
     * Set whether to ignore hidden files and directories.
     *
     * @param bool $ignore Whether to ignore hidden files
     * @return $this
     */
    public function ignoreHiddenFiles(bool $ignore = true)
    {
        $this->ignoreHiddenFiles = $ignore;

        return $this;
    }

    /** Set whether to search recursively into subdirectories.
     *
     * @param bool $recursive Whether to search recursively (default: true)
     * @return $this
     */
    public function recursive(bool $recursive = true)
    {
        $this->recursive = $recursive;

        return $this;
    }

    /**
     * Get files matching the criteria.
     *
     * @return \Iterator<\SplFileInfo>
     */
    public function files(): Iterator
    {
        $this->mode = FinderMode::FILES;

        return $this->iterate();
    }

    /**
     * Get directories matching the criteria.
     *
     * @return \Iterator<\SplFileInfo>
     */
    public function directories(): Iterator
    {
        $this->mode = FinderMode::DIRECTORIES;

        return $this->iterate();
    }

    /**
     * Get both files and directories matching the criteria.
     *
     * @return \Iterator<\SplFileInfo>
     */
    public function all(): Iterator
    {
        $this->mode = FinderMode::ALL;

        return $this->iterate();
    }

    /**
     * Iterate over items matching the criteria.
     *
     * @return \Iterator<\SplFileInfo>
     */
    protected function iterate(): Iterator
    {
        // Combine results from all paths
        if (count($this->paths) === 1) {
            return $this->buildIterator($this->paths[0]);
        }

        // Multiple paths - use AppendIterator
        $append = new AppendIterator();
        foreach ($this->paths as $path) {
            $append->append($this->buildIterator($path));
        }

        return $append;
    }

    /**
     * Build an iterator chain with all configured filters.
     *
     * @param string $path The directory path
     * @return \Iterator<\SplFileInfo>
     */
    protected function buildIterator(string $path): Iterator
    {
        $flags = FilesystemIterator::KEY_AS_PATHNAME
            | FilesystemIterator::CURRENT_AS_FILEINFO
            | FilesystemIterator::SKIP_DOTS;

        $directory = new RecursiveDirectoryIterator($path, $flags);

        // Apply hidden file filtering
        if ($this->ignoreHiddenFiles) {
            $directory = new HiddenFileFilterIterator($directory);
        }

        // Apply directory exclusions
        if ($this->exclude !== []) {
            $directory = new ExcludeDirectoryFilterIterator($directory, $this->exclude);
        }

        // Apply path pattern exclusions during recursion
        if ($this->notPathPatterns !== []) {
            $directory = new ContainsPathFilterIterator($directory, $this->notPathPatterns, negate: true);
        }

        // Apply path pattern inclusions during recursion (non-regex patterns only)
        if ($this->pathPatterns !== []) {
            $directory = new ContainsPathFilterIterator($directory, $this->pathPatterns);
        }

        // Use SELF_FIRST when looking for directories to include them in iteration
        // Use LEAVES_ONLY when looking for files only for optimization
        $iteratorMode = $this->mode === FinderMode::FILES
            ? RecursiveIteratorIterator::LEAVES_ONLY
            : RecursiveIteratorIterator::SELF_FIRST;

        $iterator = new RecursiveIteratorIterator($directory, $iteratorMode);

        // Apply file type filtering
        if ($this->mode !== null && $this->mode !== FinderMode::ALL) {
            $iterator = new FileTypeFilterIterator($iterator, $this->mode);
        }

        // Apply filename filtering
        if ($this->names !== []) {
            $iterator = new FilenameFilterIterator($iterator, $this->names);
        }
        if ($this->notNames !== []) {
            $iterator = new FilenameFilterIterator($iterator, $this->notNames, negate: true);
        }

        // Apply depth filtering (handles non-recursive mode when recursive=false)
        if (!$this->recursive) {
            $iterator = new DepthFilterIterator($iterator, DepthOperator::EQUAL, 0);
        }
        foreach ($this->depths as [$operator, $level]) {
            $iterator = new DepthFilterIterator($iterator, $operator, $level);
        }

        // Apply glob pattern filtering
        if ($this->globPatterns !== []) {
            $iterator = new GlobFilterIterator($iterator, $this->globPatterns, $path);
        }

        // Apply custom filters
        foreach ($this->filters as $callback) {
            $iterator = new CallbackFilterIterator($iterator, $callback, $path);
        }

        return $iterator;
    }
}
