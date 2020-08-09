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
 * @copyright     Copyright (c) 2017 Aura for PHP
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         4.2.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\I18n;

use Cake\I18n\Exception\FormatterNotMappedException;

/**
 * A ServiceLocator implementation for loading and retaining formatter objects.
 *
 * @internal
 */
class FormatterLocator
{
    /**
     * A registry to retain formatter objects.
     *
     * @var array
     */
    protected $registry = [];

    /**
     * Tracks whether or not a registry entry has been converted from a
     * callable to a formatter object.
     *
     * @var array
     */
    protected $converted = [];

    /**
     * Constructor.
     *
     * @param array $registry An array of key-value pairs where the key is the
     * formatter name the value is a callable that returns a formatter object.
     */
    public function __construct(array $registry = [])
    {
        foreach ($registry as $name => $spec) {
            $this->set($name, $spec);
        }
    }

    /**
     * Sets a formatter into the registry by name.
     *
     * @param string $name The formatter name.
     *
     * @param callable $spec A callable that returns a formatter object.
     * @return void
     */
    public function set($name, $spec)
    {
        $this->registry[$name] = $spec;
        $this->converted[$name] = false;
    }

    /**
     * Gets a formatter from the registry by name.
     *
     * @param string $name The formatter to retrieve.
     * @return \Cake\I18n\FormatterInterface A formatter object.
     * @throws \Cake\I18n\Exception\FormatterNotMappedException
     */
    public function get($name)
    {
        if (! isset($this->registry[$name])) {
            throw new FormatterNotMappedException($name);
        }

        if (! $this->converted[$name]) {
            $func = $this->registry[$name];
            $this->registry[$name] = $func();
            $this->converted[$name] = true;
        }

        return $this->registry[$name];
    }
}
