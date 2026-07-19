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
namespace Cake\Utility\Fs\Iterator;

use Cake\Utility\Fs\Path;
use RecursiveFilterIterator;
use RecursiveIterator;

/**
 * Filters files based on whether their path contains or matches specific patterns.
 *
 * Supports two types of patterns:
 * - String patterns: Uses substring matching (str_contains) for simple strings
 * - Regex patterns: Uses preg_match for patterns starting with /, #, or ~
 *
 * Uses OR logic: accepts if any pattern matches.
 * Always allows directories to enable recursive traversal.
 *
 * Can be used to include or exclude files based on the $negate parameter:
 * - When $negate is false (default): includes files matching patterns
 * - When $negate is true: excludes files matching patterns
 */
final class ContainsPathFilterIterator extends RecursiveFilterIterator
{
    /**
     * String patterns (substring matching, normalized)
     *
     * @var array<string>
     */
    protected array $stringPatterns;

    /**
     * Regex patterns (pattern matching, normalized)
     *
     * @var array<string>
     */
    protected array $regexPatterns;

    /**
     * @param \RecursiveIterator<string, \SplFileInfo> $iterator The iterator to filter
     * @param array<string> $patterns Path patterns to match (string or regex)
     * @param bool $negate When true, inverts the filter (excludes matching paths)
     */
    public function __construct(
        RecursiveIterator $iterator,
        array $patterns,
        protected readonly bool $negate = false,
    ) {
        parent::__construct($iterator);

        // Separate regex patterns from string patterns
        $regexPatterns = [];
        $stringPatterns = [];

        foreach ($patterns as $pattern) {
            if (preg_match('/^[\/#~]/', $pattern)) {
                $regexPatterns[] = $pattern;
            } else {
                $stringPatterns[] = $pattern;
            }
        }

        // Normalize string patterns for cross-platform compatibility
        $this->stringPatterns = array_map(fn(string $p) => Path::normalize($p), $stringPatterns);

        // Normalize regex patterns (normalize the paths they'll match against)
        $this->regexPatterns = $regexPatterns;
    }

    /**
     * @inheritDoc
     */
    public function accept(): bool
    {
        $current = $this->current();

        // Always accept directories to allow traversal
        if ($current->isDir()) {
            return true;
        }

        // If no patterns at all, accept everything (no-op)
        if ($this->stringPatterns === [] && $this->regexPatterns === []) {
            return true;
        }

        // For files, check if path matches patterns
        $path = Path::normalize($current->getPathname());
        $matches = false;

        // Check string patterns (substring matching)
        foreach ($this->stringPatterns as $pattern) {
            if (str_contains($path, $pattern)) {
                $matches = true;
                break;
            }
        }

        // Check regex patterns if no string match found
        if (!$matches) {
            foreach ($this->regexPatterns as $pattern) {
                if (preg_match($pattern, $path)) {
                    $matches = true;
                    break;
                }
            }
        }

        return $this->negate ? !$matches : $matches;
    }

    /**
     * @inheritDoc
     */
    public function getChildren(): self
    {
        /** @var \RecursiveIterator<string, \SplFileInfo> $inner */
        $inner = $this->getInnerIterator();

        // Pass all original patterns through (constructor will separate them again)
        $allPatterns = array_merge($this->stringPatterns, $this->regexPatterns);

        return new self($inner->getChildren(), $allPatterns, $this->negate);
    }
}
