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
 * @since         5.3.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Utility;

use BadMethodCallException;
use Closure;

trait MacroableInstanceTrait
{
    /**
     * The registered string macros.
     *
     * @var array<string, object|callable>
     */
    protected static array $macros = [];

    /**
     * Register a custom macro.
     *
     * @param string               $name
     * @param callable|object $macro Closure or invokable object/callable
     * @return void
     */
    public static function macro(string $name, object|callable $macro): void
    {
        static::$macros[$name] = $macro;
    }

    /**
     * Determine if a macro is registered.
     *
     * @param string  $name
     * @return bool
     */
    public static function hasMacro(string $name): bool
    {
        return isset(static::$macros[$name]);
    }

    /**
     * Flush the existing macros.
     *
     * @return void
     */
    public static function flushMacros(): void
    {
        static::$macros = [];
    }

    /**
     * Dynamically handle calls to the class (object context).
     *
     * @param string  $method
     * @param array   $parameters
     * @return mixed
     * @throws \BadMethodCallException
     */
    public function __call(string $method, array $parameters): mixed
    {
        if (! static::hasMacro($method)) {
            throw new BadMethodCallException(sprintf(
                'Method %s::%s does not exist.',
                static::class,
                $method,
            ));
        }

        $macro = static::$macros[$method];

        if ($macro instanceof Closure) {
            $macro = $macro->bindTo($this, static::class);
        }

        if (!is_callable($macro)) {
            throw new BadMethodCallException(sprintf(
                'Macro for method `%s()` is not callable.',
                $method,
            ));
        }

        return $macro(...$parameters);
    }
}
