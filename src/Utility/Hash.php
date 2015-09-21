<?php
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @since         2.2.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Utility;

use ArrayAccess;
use InvalidArgumentException;
use RuntimeException;

/**
 * Library of array functions for manipulating and extracting data
 * from arrays or 'sets' of data.
 *
 * `Hash` provides an improved interface, more consistent and
 * predictable set of features over `Set`. While it lacks the spotty
 * support for pseudo Xpath, its more fully featured dot notation provides
 * similar features in a more consistent implementation.
 *
 * @link http://book.cakephp.org/3.0/en/core-libraries/hash.html
 */
class Hash
{

    /**
     * Get a single value specified by $path out of $data.
     * Does not support the full dot notation feature set,
     * but is faster for simple read operations.
     *
     * @param array|\ArrayAccess $data Array of data or object implementing
     *   \ArrayAccess interface to operate on.
     * @param string|array $path The path being searched for. Either a dot
     *   separated string, or an array of path segments.
     * @param mixed $default The return value when the path does not exist
     * @throws \InvalidArgumentException
     * @return mixed The value fetched from the array, or null.
     * @link http://book.cakephp.org/3.0/en/core-libraries/hash.html#Hash::get
     */
    public static function get($data, $path, $default = null)
    {
        if (!(is_array($data) || $data instanceof ArrayAccess)) {
            throw new InvalidArgumentException(
                'Invalid data type, must be an array or \ArrayAccess instance.'
            );
        }

        if (empty($data) || $path === null || $path === '') {
            return $default;
        }

        if (is_string($path) || is_numeric($path)) {
            $parts = explode('.', $path);
        } else {
            if (!is_array($path)) {
                throw new InvalidArgumentException(sprintf(
                    'Invalid Parameter %s, should be dot separated path or array.',
                    $path
                ));
            }

            $parts = $path;
        }

        switch (count($parts)) {
            case 1:
                return isset($data[$parts[0]]) ? $data[$parts[0]] : $default;
            case 2:
                return isset($data[$parts[0]][$parts[1]]) ? $data[$parts[0]][$parts[1]] : $default;
            case 3:
                return isset($data[$parts[0]][$parts[1]][$parts[2]]) ? $data[$parts[0]][$parts[1]][$parts[2]] : $default;
            default:
                foreach ($parts as $key) {
                    if ((is_array($data) || $data instanceof ArrayAccess) && isset($data[$key])) {
                        $data = $data[$key];
                    } else {
                        return $default;
                    }
                }
        }

        return $data;
    }

    /**
     * Gets the values from an array matching the $path expression.
     * The path expression is a dot separated expression, that can contain a set
     * of patterns and expressions:
     *
     * - `{n}` Matches any numeric key, or integer.
     * - `{s}` Matches any string key.
     * - `{*}` Matches any value.
     * - `Foo` Matches any key with the exact same value.
     *
     * There are a number of attribute operators:
     *
     *  - `=`, `!=` Equality.
     *  - `>`, `<`, `>=`, `<=` Value comparison.
     *  - `=/.../` Regular expression pattern match.
     *
     * Given a set of User array data, from a `$User->find('all')` call:
     *
     * - `1.User.name` Get the name of the user at index 1.
     * - `{n}.User.name` Get the name of every user in the set of users.
     * - `{n}.User[id]` Get the name of every user with an id key.
     * - `{n}.User[id>=2]` Get the name of every user with an id key greater than or equal to 2.
     * - `{n}.User[username=/^paul/]` Get User elements with username matching `^paul`.
     *
     * @param array $data The data to extract from.
     * @param string $path The path to extract.
     * @return array An array of the extracted values. Returns an empty array
     *   if there are no matches.
     * @link http://book.cakephp.org/3.0/en/core-libraries/hash.html#Hash::extract
     */
    public static function extract(array $data, $path)
    {
        if (empty($path)) {
            return $data;
        }

        // Simple paths.
        if (!preg_match('/[{\[]/', $path)) {
            return (array)static::get($data, $path);
        }

        if (strpos($path, '[') === false) {
            if (function_exists('array_column') && preg_match('|^\{n\}\.(\w+)$|', $path, $matches)) {
                return array_column($data, $matches[1]);
            }
            $tokens = explode('.', $path);
        } else {
            $tokens = Text::tokenize($path, '.', '[', ']');
        }

        $_key = '__set_item__';

        $context = [$_key => [$data]];

        foreach ($tokens as $token) {
            $next = [];

            list($token, $conditions) = self::_splitConditions($token);

            foreach ($context[$_key] as $item) {
                foreach ((array)$item as $k => $v) {
                    if (static::_matchToken($k, $token)) {
                        $next[] = $v;
                    }
                }
            }

            // Filter for attributes.
            if ($conditions) {
                $filter = [];
                foreach ($next as $item) {
                    if (is_array($item) && static::_matches($item, $conditions)) {
                        $filter[] = $item;
                    }
                }
                $next = $filter;
            }
            $context = [$_key => $next];

        }
        return $context[$_key];
    }

    /**
     * Split token conditions
     *
     * @param string $token the token being splitted.
     * @return array [token, conditions] with token splitted
     */
    protected static function _splitConditions($token)
    {
        $conditions = false;
        $position = strpos($token, '[');
        if ($position !== false) {
            $conditions = substr($token, $position);
            $token = substr($token, 0, $position);
        }

        return [$token, $conditions];
    }

    /**
     * Check a key against a token.
     *
     * @param string $key The key in the array being searched.
     * @param string $token The token being matched.
     * @return bool
     */
    protected static function _matchToken($key, $token)
    {
        switch ($token) {
            case '{n}':
                return is_numeric($key);
            case '{s}':
                return is_string($key);
            case '{*}':
                return true;
            default:
                return is_numeric($token) ? ($key == $token) : $key === $token;
        }
    }

    /**
     * Checks whether or not $data matches the attribute patterns
     *
     * @param array $data Array of data to match.
     * @param string $selector The patterns to match.
     * @return bool Fitness of expression.
     */
    protected static function _matches(array $data, $selector)
    {
        preg_match_all(
            '/(\[ (?P<attr>[^=><!]+?) (\s* (?P<op>[><!]?[=]|[><]) \s* (?P<val>(?:\/.*?\/ | [^\]]+)) )? \])/x',
            $selector,
            $conditions,
            PREG_SET_ORDER
        );

        foreach ($conditions as $cond) {
            $attr = $cond['attr'];
            $op = isset($cond['op']) ? $cond['op'] : null;
            $val = isset($cond['val']) ? $cond['val'] : null;

            // Presence test.
            if (empty($op) && empty($val) && !isset($data[$attr])) {
                return false;
            }

            // Empty attribute = fail.
            if (!(isset($data[$attr]) || array_key_exists($attr, $data))) {
                return false;
            }

            $prop = null;
            if (isset($data[$attr])) {
                $prop = $data[$attr];
            }
            $isBool = is_bool($prop);
            if ($isBool && is_numeric($val)) {
                $prop = $prop ? '1' : '0';
            } elseif ($isBool) {
                $prop = $prop ? 'true' : 'false';
            }

            // Pattern matches and other operators.
            if ($op === '=' && $val && $val[0] === '/') {
                if (!preg_match($val, $prop)) {
                    return false;
                }
            } elseif (($op === '=' && $prop != $val) ||
                ($op === '!=' && $prop == $val) ||
                ($op === '>' && $prop <= $val) ||
                ($op === '<' && $prop >= $val) ||
                ($op === '>=' && $prop < $val) ||
                ($op === '<=' && $prop > $val)
            ) {
                return false;
            }

        }
        return true;
    }

    /**
     * Insert $values into an array with the given $path. You can use
     * `{n}` and `{s}` elements to insert $data multiple times.
     *
     * @param array $data The data to insert into.
     * @param string $path The path to insert at.
     * @param array|null $values The values to insert.
     * @return array The data with $values inserted.
     * @link http://book.cakephp.org/3.0/en/core-libraries/hash.html#Hash::insert
     */
    public static function insert(array $data, $path, $values = null)
    {
        $noTokens = strpos($path, '[') === false;
        if ($noTokens && strpos($path, '.') === false) {
            $data[$path] = $values;
            return $data;
        }

        if ($noTokens) {
            $tokens = explode('.', $path);
        } else {
            $tokens = Text::tokenize($path, '.', '[', ']');
        }

        if ($noTokens && strpos($path, '{') === false) {
            return static::_simpleOp('insert', $data, $tokens, $values);
        }

        $token = array_shift($tokens);
        $nextPath = implode('.', $tokens);

        list($token, $conditions) = static::_splitConditions($token);

        foreach ($data as $k => $v) {
            if (static::_matchToken($k, $token)) {
                if (!$conditions || static::_matches($v, $conditions)) {
                    $data[$k] = $nextPath
                        ? static::insert($v, $nextPath, $values)
                        : array_merge($v, (array)$values);
                }
            }
        }
        return $data;
    }

    /**
     * Perform a simple insert/remove operation.
     *
     * @param string $op The operation to do.
     * @param array $data The data to operate on.
     * @param array $path The path to work on.
     * @param mixed $values The values to insert when doing inserts.
     * @return array data.
     */
    protected static function _simpleOp($op, $data, $path, $values = null)
    {
        $_list =& $data;

        $count = count($path);
        $last = $count - 1;
        foreach ($path as $i => $key) {
            if ((is_numeric($key) && (int)($key) > 0 || $key === '0') &&
                strpos($key, '0') !== 0
            ) {
                $key = (int)$key;
            }
            if ($op === 'insert') {
                if ($i === $last) {
                    $_list[$key] = $values;
                    return $data;
                }
                if (!isset($_list[$key])) {
                    $_list[$key] = [];
                }
                $_list =& $_list[$key];
                if (!is_array($_list)) {
                    $_list = [];
                }
            } elseif ($op === 'remove') {
                if ($i === $last) {
                    unset($_list[$key]);
                    return $data;
                }
                if (!isset($_list[$key])) {
                    return $data;
                }
                $_list =& $_list[$key];
            }
        }
    }

    /**
     * Remove data matching $path from the $data array.
     * You can use `{n}` and `{s}` to remove multiple elements
     * from $data.
     *
     * @param array $data The data to operate on
     * @param string $path A path expression to use to remove.
     * @return array The modified array.
     * @link http://book.cakephp.org/3.0/en/core--libraries/hash.html#Hash::remove
     */
    public static function remove(array $data, $path)
    {
        $noTokens = strpos($path, '[') === false;
        $noExpansion = strpos($path, '{') === false;

        if ($noExpansion && $noTokens && strpos($path, '.') === false) {
            unset($data[$path]);
            return $data;
        }

        $tokens = $noTokens ? explode('.', $path) : Text::tokenize($path, '.', '[', ']');

        if ($noExpansion && $noTokens) {
            return static::_simpleOp('remove', $data, $tokens);
        }

        $token = array_shift($tokens);
        $nextPath = implode('.', $tokens);

        list($token, $conditions) = self::_splitConditions($token);

        foreach ($data as $k => $v) {
            $match = static::_matchToken($k, $token);
            if ($match && is_array($v)) {
                if ($conditions) {
                    if (static::_matches($v, $conditions)) {
                        if ($nextPath) {
                            $data[$k] = static::remove($v, $nextPath);
                        } else {
                            unset($data[$k]);
                        }
                    }
                } else {
                    $data[$k] = static::remove($v, $nextPath);
                }
                if (empty($data[$k])) {
                    unset($data[$k]);
                }
            } elseif ($match && empty($nextPath)) {
                unset($data[$k]);
            }
        }
        return $data;
    }

    /**
     * Creates an associative array using `$keyPath` as the path to build its keys, and optionally
     * `$valuePath` as path to get the values. If `$valuePath` is not specified, all values will be initialized
     * to null (useful for Hash::merge). You can optionally group the values by what is obtained when
     * following the path specified in `$groupPath`.
     *
     * @param array $data Array from where to extract keys and values
     * @param string $keyPath A dot-separated string.
     * @param string|null $valuePath A dot-separated string.
     * @param string|null $groupPath A dot-separated string.
     * @return array Combined array
     * @link http://book.cakephp.org/3.0/en/core-libraries/hash.html#Hash::combine
     * @throws \RuntimeException  When keys and values count is unequal.
     */
    public static function combine(array $data, $keyPath, $valuePath = null, $groupPath = null)
    {
        if (empty($data)) {
            return [];
        }

        if (is_array($keyPath)) {
            $format = array_shift($keyPath);
            $keys = static::format($data, $keyPath, $format);
        } else {
            $keys = static::extract($data, $keyPath);
        }
        if (empty($keys)) {
            return [];
        }

        if (!empty($valuePath) && is_array($valuePath)) {
            $format = array_shift($valuePath);
            $vals = static::format($data, $valuePath, $format);
        } elseif (!empty($valuePath)) {
            $vals = static::extract($data, $valuePath);
        }
        if (empty($vals)) {
            $vals = array_fill(0, count($keys), null);
        }

        if (count($keys) !== count($vals)) {
            throw new RuntimeException(
                'Hash::combine() needs an equal number of keys + values.'
            );
        }

        if ($groupPath !== null) {
            $group = static::extract($data, $groupPath);
            if (!empty($group)) {
                $c = count($keys);
                for ($i = 0; $i < $c; $i++) {
                    if (!isset($group[$i])) {
                        $group[$i] = 0;
                    }
                    if (!isset($out[$group[$i]])) {
                        $out[$group[$i]] = [];
                    }
                    $out[$group[$i]][$keys[$i]] = $vals[$i];
                }
                return $out;
            }
        }
        if (empty($vals)) {
            return [];
        }
        return array_combine($keys, $vals);
    }

    /**
     * Returns a formatted series of values extracted from `$data`, using
     * `$format` as the format and `$paths` as the values to extract.
     *
     * Usage:
     *
     * ```
     * $result = Hash::format($users, ['{n}.User.id', '{n}.User.name'], '%s : %s');
     * ```
     *
     * The `$format` string can use any format options that `vsprintf()` and `sprintf()` do.
     *
     * @param array $data Source array from which to extract the data
     * @param array $paths An array containing one or more Hash::extract()-style key paths
     * @param string $format Format string into which values will be inserted, see sprintf()
     * @return void|array An array of strings extracted from `$path` and formatted with `$format`
     * @link http://book.cakephp.org/3.0/en/core-libraries/hash.html#Hash::format
     * @see sprintf()
     * @see Hash::extract()
     */
    public static function format(array $data, array $paths, $format)
    {
        $extracted = [];
        $count = count($paths);

        if (!$count) {
            return;
        }

        for ($i = 0; $i < $count; $i++) {
            $extracted[] = static::extract($data, $paths[$i]);
        }
        $out = [];
        $data = $extracted;
        $count = count($data[0]);

        $countTwo = count($data);
        for ($j = 0; $j < $count; $j++) {
            $args = [];
            for ($i = 0; $i < $countTwo; $i++) {
                if (array_key_exists($j, $data[$i])) {
                    $args[] = $data[$i][$j];
                }
            }
            $out[] = vsprintf($format, $args);
        }
        return $out;
    }

    /**
     * Determines if one array contains the exact keys and values of another.
     *
     * @param array $data The data to search through.
     * @param array $needle The values to file in $data
     * @return bool true if $data contains $needle, false otherwise
     * @link http://book.cakephp.org/3.0/en/core-libraries/hash.html#Hash::contains
     */
    public static function contains(array $data, array $needle)
    {
        if (empty($data) || empty($needle)) {
            return false;
        }
        $stack = [];

        while (!empty($needle)) {
            $key = key($needle);
            $val = $needle[$key];
            unset($needle[$key]);

            if (array_key_exists($key, $data) && is_array($val)) {
                $next = $data[$key];
                unset($data[$key]);

                if (!empty($val)) {
                    $stack[] = [$val, $next];
                }
            } elseif (!array_key_exists($key, $data) || $data[$key] != $val) {
                return false;
            }

            if (empty($needle) && !empty($stack)) {
                list($needle, $data) = array_pop($stack);
            }
        }
        return true;
    }

    /**
     * Test whether or not a given path exists in $data.
     * This method uses the same path syntax as Hash::extract()
     *
     * Checking for paths that could target more than one element will
     * make sure that at least one matching element exists.
     *
     * @param array $data The data to check.
     * @param string $path The path to check for.
     * @return bool Existence of path.
     * @see Hash::extract()
     * @link http://book.cakephp.org/3.0/en/core-libraries/hash.html#Hash::check
     */
    public static function check(array $data, $path)
    {
        $results = static::extract($data, $path);
        if (!is_array($results)) {
            return false;
        }
        return count($results) > 0;
    }

    /**
     * Recursively filters a data set.
     *
     * @param array $data Either an array to filter, or value when in callback
     * @param callable $callback A function to filter the data with. Defaults to
     *   `static::_filter()` Which strips out all non-zero empty values.
     * @return array Filtered array
     * @link http://book.cakephp.org/3.0/en/core-libraries/hash.html#Hash::filter
     */
    public static function filter(array $data, $callback = ['self', '_filter'])
    {
        foreach ($data as $k => $v) {
            if (is_array($v)) {
                $data[$k] = static::filter($v, $callback);
            }
        }
        return array_filter($data, $callback);
    }

    /**
     * Callback function for filtering.
     *
     * @param array $var Array to filter.
     * @return bool
     */
    protected static function _filter($var)
    {
        return $var === 0 || $var === '0' || !empty($var);
    }

    /**
     * Collapses a multi-dimensional array into a single dimension, using a delimited array path for
     * each array element's key, i.e. [['Foo' => ['Bar' => 'Far']]] becomes
     * ['0.Foo.Bar' => 'Far'].)
     *
     * @param array $data Array to flatten
     * @param string $separator String used to separate array key elements in a path, defaults to '.'
     * @return array
     * @link http://book.cakephp.org/3.0/en/core-libraries/hash.html#Hash::flatten
     */
    public static function flatten(array $data, $separator = '.')
    {
        $result = [];
        $stack = [];
        $path = null;

        reset($data);
        while (!empty($data)) {
            $key = key($data);
            $element = $data[$key];
            unset($data[$key]);

            if (is_array($element) && !empty($element)) {
                if (!empty($data)) {
                    $stack[] = [$data, $path];
                }
                $data = $element;
                reset($data);
                $path .= $key . $separator;
            } else {
                $result[$path . $key] = $element;
            }

            if (empty($data) && !empty($stack)) {
                list($data, $path) = array_pop($stack);
                reset($data);
            }
        }
        return $result;
    }

    /**
     * Expands a flat array to a nested array.
     *
     * For example, unflattens an array that was collapsed with `Hash::flatten()`
     * into a multi-dimensional array. So, `['0.Foo.Bar' => 'Far']` becomes
     * `[['Foo' => ['Bar' => 'Far']]]`.
     *
     * @param array $data Flattened array
     * @param string $separator The delimiter used
     * @return array
     * @link http://book.cakephp.org/3.0/en/core-libraries/hash.html#Hash::expand
     */
    public static function expand(array $data, $separator = '.')
    {
        $result = [];
        foreach ($data as $flat => $value) {
            $keys = explode($separator, $flat);
            $keys = array_reverse($keys);
            $child = [
                $keys[0] => $value
            ];
            array_shift($keys);
            foreach ($keys as $k) {
                $child = [
                    $k => $child
                ];
            }

            $stack = [[$child, &$result]];
            static::_merge($stack, $result);
        }
        return $result;
    }

    /**
     * This function can be thought of as a hybrid between PHP's `array_merge` and `array_merge_recursive`.
     *
     * The difference between this method and the built-in ones, is that if an array key contains another array, then
     * Hash::merge() will behave in a recursive fashion (unlike `array_merge`). But it will not act recursively for
     * keys that contain scalar values (unlike `array_merge_recursive`).
     *
     * Note: This function will work with an unlimited amount of arguments and typecasts non-array parameters into arrays.
     *
     * @param array $data Array to be merged
     * @param mixed $merge Array to merge with. The argument and all trailing arguments will be array cast when merged
     * @return array Merged array
     * @link http://book.cakephp.org/3.0/en/core-libraries/hash.html#Hash::merge
     */
    public static function merge(array $data, $merge)
    {
        $args = array_slice(func_get_args(), 1);
        $return = $data;

        foreach ($args as &$curArg) {
            $stack[] = [(array)$curArg, &$return];
        }
        unset($curArg);
        static::_merge($stack, $return);
        return $return;
    }

    /**
     * Merge helper function to reduce duplicated code between merge() and expand().
     *
     * @param array $stack The stack of operations to work with.
     * @param array $return The return value to operate on.
     * @return void
     */
    protected static function _merge($stack, &$return)
    {
        while (!empty($stack)) {
            foreach ($stack as $curKey => &$curMerge) {
                foreach ($curMerge[0] as $key => &$val) {
                    if (!empty($curMerge[1][$key]) && (array)$curMerge[1][$key] === $curMerge[1][$key] && (array)$val === $val) {
                        $stack[] = [&$val, &$curMerge[1][$key]];
                    } elseif ((int)$key === $key && isset($curMerge[1][$key])) {
                        $curMerge[1][] = $val;
                    } else {
                        $curMerge[1][$key] = $val;
                    }
                }
                unset($stack[$curKey]);
            }
            unset($curMerge);
        }
    }

    /**
     * Checks to see if all the values in the array are numeric
     *
     * @param array $data The array to check.
     * @return bool true if values are numeric, false otherwise
     * @link http://book.cakephp.org/3.0/en/core-libraries/hash.html#Hash::numeric
     */
    public static function numeric(array $data)
    {
        if (empty($data)) {
            return false;
        }
        return $data === array_filter($data, 'is_numeric');
    }

    /**
     * Counts the dimensions of an array.
     * Only considers the dimension of the first element in the array.
     *
     * If you have an un-even or heterogenous array, consider using Hash::maxDimensions()
     * to get the dimensions of the array.
     *
     * @param array $data Array to count dimensions on
     * @return int The number of dimensions in $data
     * @link http://book.cakephp.org/3.0/en/core-libraries/hash.html#Hash::dimensions
     */
    public static function dimensions(array $data)
    {
        if (empty($data)) {
            return 0;
        }
        reset($data);
        $depth = 1;
        while ($elem = array_shift($data)) {
            if (is_array($elem)) {
                $depth += 1;
                $data = $elem;
            } else {
                break;
            }
        }
        return $depth;
    }

    /**
     * Counts the dimensions of *all* array elements. Useful for finding the maximum
     * number of dimensions in a mixed array.
     *
     * @param array $data Array to count dimensions on
     * @return int The maximum number of dimensions in $data
     * @link http://book.cakephp.org/3.0/en/core-libraries/hash.html#Hash::maxDimensions
     */
    public static function maxDimensions(array $data)
    {
        $depth = [];
        if (is_array($data) && reset($data) !== false) {
            foreach ($data as $value) {
                if (is_array($value)) {
                    $depth[] = static::maxDimensions($value) + 1;
                } else {
                    $depth[] = 1;
                }
            }
        }
        return empty($depth) ? 0 : max($depth);
    }

    /**
     * Map a callback across all elements in a set.
     * Can be provided a path to only modify slices of the set.
     *
     * @param array $data The data to map over, and extract data out of.
     * @param string $path The path to extract for mapping over.
     * @param callable $function The function to call on each extracted value.
     * @return array An array of the modified values.
     * @link http://book.cakephp.org/3.0/en/core-libraries/hash.html#Hash::map
     */
    public static function map(array $data, $path, $function)
    {
        $values = (array)static::extract($data, $path);
        return array_map($function, $values);
    }

    /**
     * Reduce a set of extracted values using `$function`.
     *
     * @param array $data The data to reduce.
     * @param string $path The path to extract from $data.
     * @param callable $function The function to call on each extracted value.
     * @return mixed The reduced value.
     * @link http://book.cakephp.org/3.0/en/core-libraries/hash.html#Hash::reduce
     */
    public static function reduce(array $data, $path, $function)
    {
        $values = (array)static::extract($data, $path);
        return array_reduce($values, $function);
    }

    /**
     * Apply a callback to a set of extracted values using `$function`.
     * The function will get the extracted values as the first argument.
     *
     * ### Example
     *
     * You can easily count the results of an extract using apply().
     * For example to count the comments on an Article:
     *
     * ```
     * $count = Hash::apply($data, 'Article.Comment.{n}', 'count');
     * ```
     *
     * You could also use a function like `array_sum` to sum the results.
     *
     * ```
     * $total = Hash::apply($data, '{n}.Item.price', 'array_sum');
     * ```
     *
     * @param array $data The data to reduce.
     * @param string $path The path to extract from $data.
     * @param callable $function The function to call on each extracted value.
     * @return mixed The results of the applied method.
     */
    public static function apply(array $data, $path, $function)
    {
        $values = (array)static::extract($data, $path);
        return call_user_func($function, $values);
    }

    /**
     * Sorts an array by any value, determined by a Set-compatible path
     *
     * ### Sort directions
     *
     * - `asc` Sort ascending.
     * - `desc` Sort descending.
     *
     * ### Sort types
     *
     * - `regular` For regular sorting (don't change types)
     * - `numeric` Compare values numerically
     * - `string` Compare values as strings
     * - `natural` Compare items as strings using "natural ordering" in a human friendly way.
     *   Will sort foo10 below foo2 as an example.
     *
     * To do case insensitive sorting, pass the type as an array as follows:
     *
     * ```
     * Hash::sort($data, 'some.attribute', 'asc', ['type' => 'regular', 'ignoreCase' => true]);
     * ```
     *
     * When using the array form, `type` defaults to 'regular'. The `ignoreCase` option
     * defaults to `false`.
     *
     * @param array $data An array of data to sort
     * @param string $path A Set-compatible path to the array value
     * @param string $dir See directions above. Defaults to 'asc'.
     * @param array|string $type See direction types above. Defaults to 'regular'.
     * @return array Sorted array of data
     * @link http://book.cakephp.org/3.0/en/core-libraries/hash.html#Hash::sort
     */
    public static function sort(array $data, $path, $dir = 'asc', $type = 'regular')
    {
        if (empty($data)) {
            return [];
        }
        $originalKeys = array_keys($data);
        $numeric = is_numeric(implode('', $originalKeys));
        if ($numeric) {
            $data = array_values($data);
        }
        $sortValues = static::extract($data, $path);
        $sortCount = count($sortValues);
        $dataCount = count($data);

        // Make sortValues match the data length, as some keys could be missing
        // the sorted value path.
        if ($sortCount < $dataCount) {
            $sortValues = array_pad($sortValues, $dataCount, null);
        }
        $result = static::_squash($sortValues);
        $keys = static::extract($result, '{n}.id');
        $values = static::extract($result, '{n}.value');

        $dir = strtolower($dir);
        $ignoreCase = false;

        // $type can be overloaded for case insensitive sort
        if (is_array($type)) {
            $type += ['ignoreCase' => false, 'type' => 'regular'];
            $ignoreCase = $type['ignoreCase'];
            $type = $type['type'];
        }
        $type = strtolower($type);

        if ($dir === 'asc') {
            $dir = SORT_ASC;
        } else {
            $dir = SORT_DESC;
        }
        if ($type === 'numeric') {
            $type = SORT_NUMERIC;
        } elseif ($type === 'string') {
            $type = SORT_STRING;
        } elseif ($type === 'natural') {
            $type = SORT_NATURAL;
        } else {
            $type = SORT_REGULAR;
        }
        if ($ignoreCase) {
            $values = array_map('mb_strtolower', $values);
        }
        array_multisort($values, $dir, $type, $keys, $dir, $type);
        $sorted = [];
        $keys = array_unique($keys);

        foreach ($keys as $k) {
            if ($numeric) {
                $sorted[] = $data[$k];
                continue;
            }
            if (isset($originalKeys[$k])) {
                $sorted[$originalKeys[$k]] = $data[$originalKeys[$k]];
            } else {
                $sorted[$k] = $data[$k];
            }
        }
        return $sorted;
    }

    /**
     * Helper method for sort()
     * Squashes an array to a single hash so it can be sorted.
     *
     * @param array $data The data to squash.
     * @param string|null $key The key for the data.
     * @return array
     */
    protected static function _squash(array $data, $key = null)
    {
        $stack = [];
        foreach ($data as $k => $r) {
            $id = $k;
            if ($key !== null) {
                $id = $key;
            }
            if (is_array($r) && !empty($r)) {
                $stack = array_merge($stack, static::_squash($r, $id));
            } else {
                $stack[] = ['id' => $id, 'value' => $r];
            }
        }
        return $stack;
    }

    /**
     * Computes the difference between two complex arrays.
     * This method differs from the built-in array_diff() in that it will preserve keys
     * and work on multi-dimensional arrays.
     *
     * @param array $data First value
     * @param array $compare Second value
     * @return array Returns the key => value pairs that are not common in $data and $compare
     *    The expression for this function is ($data - $compare) + ($compare - ($data - $compare))
     * @link http://book.cakephp.org/3.0/en/core-libraries/hash.html#Hash::diff
     */
    public static function diff(array $data, array $compare)
    {
        if (empty($data)) {
            return (array)$compare;
        }
        if (empty($compare)) {
            return (array)$data;
        }
        $intersection = array_intersect_key($data, $compare);
        while (($key = key($intersection)) !== null) {
            if ($data[$key] == $compare[$key]) {
                unset($data[$key]);
                unset($compare[$key]);
            }
            next($intersection);
        }
        return $data + $compare;
    }

    /**
     * Merges the difference between $data and $compare onto $data.
     *
     * @param array $data The data to append onto.
     * @param array $compare The data to compare and append onto.
     * @return array The merged array.
     * @link http://book.cakephp.org/3.0/en/core-libraries/hash.html#Hash::mergeDiff
     */
    public static function mergeDiff(array $data, array $compare)
    {
        if (empty($data) && !empty($compare)) {
            return $compare;
        }
        if (empty($compare)) {
            return $data;
        }
        foreach ($compare as $key => $value) {
            if (!array_key_exists($key, $data)) {
                $data[$key] = $value;
            } elseif (is_array($value)) {
                $data[$key] = static::mergeDiff($data[$key], $compare[$key]);
            }
        }
        return $data;
    }

    /**
     * Normalizes an array, and converts it to a standard format.
     *
     * @param array $data List to normalize
     * @param bool $assoc If true, $data will be converted to an associative array.
     * @return array
     * @link http://book.cakephp.org/3.0/en/core-libraries/hash.html#Hash::normalize
     */
    public static function normalize(array $data, $assoc = true)
    {
        $keys = array_keys($data);
        $count = count($keys);
        $numeric = true;

        if (!$assoc) {
            for ($i = 0; $i < $count; $i++) {
                if (!is_int($keys[$i])) {
                    $numeric = false;
                    break;
                }
            }
        }
        if (!$numeric || $assoc) {
            $newList = [];
            for ($i = 0; $i < $count; $i++) {
                if (is_int($keys[$i])) {
                    $newList[$data[$keys[$i]]] = null;
                } else {
                    $newList[$keys[$i]] = $data[$keys[$i]];
                }
            }
            $data = $newList;
        }
        return $data;
    }

    /**
     * Takes in a flat array and returns a nested array
     *
     * ### Options:
     *
     * - `children` The key name to use in the resultset for children.
     * - `idPath` The path to a key that identifies each entry. Should be
     *   compatible with Hash::extract(). Defaults to `{n}.$alias.id`
     * - `parentPath` The path to a key that identifies the parent of each entry.
     *   Should be compatible with Hash::extract(). Defaults to `{n}.$alias.parent_id`
     * - `root` The id of the desired top-most result.
     *
     * @param array $data The data to nest.
     * @param array $options Options are:
     * @return array of results, nested
     * @see Hash::extract()
     * @throws \InvalidArgumentException When providing invalid data.
     * @link http://book.cakephp.org/3.0/en/core-libraries/hash.html#Hash::nest
     */
    public static function nest(array $data, array $options = [])
    {
        if (!$data) {
            return $data;
        }

        $alias = key(current($data));
        $options += [
            'idPath' => "{n}.$alias.id",
            'parentPath' => "{n}.$alias.parent_id",
            'children' => 'children',
            'root' => null
        ];

        $return = $idMap = [];
        $ids = static::extract($data, $options['idPath']);

        $idKeys = explode('.', $options['idPath']);
        array_shift($idKeys);

        $parentKeys = explode('.', $options['parentPath']);
        array_shift($parentKeys);

        foreach ($data as $result) {
            $result[$options['children']] = [];

            $id = static::get($result, $idKeys);
            $parentId = static::get($result, $parentKeys);

            if (isset($idMap[$id][$options['children']])) {
                $idMap[$id] = array_merge($result, (array)$idMap[$id]);
            } else {
                $idMap[$id] = array_merge($result, [$options['children'] => []]);
            }
            if (!$parentId || !in_array($parentId, $ids)) {
                $return[] =& $idMap[$id];
            } else {
                $idMap[$parentId][$options['children']][] =& $idMap[$id];
            }
        }

        if (!$return) {
            throw new InvalidArgumentException('Invalid data array to nest.');
        }

        if ($options['root']) {
            $root = $options['root'];
        } else {
            $root = static::get($return[0], $parentKeys);
        }

        foreach ($return as $i => $result) {
            $id = static::get($result, $idKeys);
            $parentId = static::get($result, $parentKeys);
            if ($id !== $root && $parentId != $root) {
                unset($return[$i]);
            }
        }
        return array_values($return);
    }
}
