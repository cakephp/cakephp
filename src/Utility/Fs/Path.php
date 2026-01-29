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

/**
 * Path utility class for cross-platform path manipulation.
 *
 * Provides static methods for normalizing, joining, and working with filesystem paths
 * in a platform-independent way.
 */
class Path
{
    /**
     * Normalizes a path by converting directory separators to forward slashes
     * and removing redundant separators.
     *
     * @param string $path The path to normalize
     * @param bool $trailing Whether to preserve trailing slashes
     * @return string The normalized path
     */
    public static function normalize(string $path, bool $trailing = false): string
    {
        // Convert all directory separators to forward slashes
        $path = str_replace('\\', '/', $path);

        // Remove duplicate slashes
        $normalized = preg_replace('#/+#', '/', $path);
        if ($normalized === null) {
            $normalized = $path;
        }

        // Handle trailing slash
        if (!$trailing) {
            $normalized = rtrim($normalized, '/');
        } elseif (!str_ends_with($normalized, '/') && $normalized !== '') {
            $normalized .= '/';
        }

        return $normalized;
    }

    /**
     * Makes a path relative to a base path.
     *
     * @param string $path The absolute path to make relative
     * @param string $from The base path to make relative from
     * @return string The relative path
     */
    public static function makeRelative(string $path, string $from): string
    {
        $path = static::normalize($path);
        $from = static::normalize($from);

        // Split paths into segments
        $pathParts = explode('/', trim($path, '/'));
        $fromParts = explode('/', trim($from, '/'));

        // Find common base
        $commonLength = 0;
        $maxLength = min(count($pathParts), count($fromParts));

        for ($i = 0; $i < $maxLength; $i++) {
            if ($pathParts[$i] === $fromParts[$i]) {
                $commonLength++;
            } else {
                break;
            }
        }

        // Build relative path
        $relativeParts = [];

        // Add .. for each directory we need to go up
        $upCount = count($fromParts) - $commonLength;
        for ($i = 0; $i < $upCount; $i++) {
            $relativeParts[] = '..';
        }

        // Add remaining path segments
        $relativeParts = array_merge(
            $relativeParts,
            array_slice($pathParts, $commonLength),
        );

        return implode('/', $relativeParts);
    }

    /**
     * Joins path segments together using the correct directory separator.
     *
     * @param string ...$segments Path segments to join
     * @return string The joined path
     */
    public static function join(string ...$segments): string
    {
        if ($segments === []) {
            return '';
        }

        $result = str_replace('\\', '/', array_shift($segments));

        foreach ($segments as $segment) {
            $segment = str_replace('\\', '/', $segment);
            $result = rtrim($result, '/') . '/' . ltrim($segment, '/');
        }

        return $result;
    }

    /**
     * Checks if a path matches a glob pattern.
     *
     * @param string $pattern The glob pattern
     * @param string $path The path to check
     * @return bool True if the path matches the pattern
     */
    public static function matches(string $pattern, string $path): bool
    {
        $pattern = static::normalize($pattern);
        $path = static::normalize($path);

        // Convert glob pattern to regex
        $regex = '#^' . preg_quote($pattern, '#') . '$#';

        // Replace glob wildcards
        $regex = str_replace(
            ['\*\*', '\*', '\?'],
            ['.*', '[^/]*', '[^/]'],
            $regex,
        );

        return (bool)preg_match($regex, $path);
    }
}
