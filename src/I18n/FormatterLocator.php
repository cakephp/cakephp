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

use Cake\I18n\Exception\I18nException;

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
     * Tracks whether a registry entry has been converted from a
     * FQCN to a formatter object.
     *
     * @var array<bool>
     */
    protected $converted = [];

    /**
     * Constructor.
     *
     * @param array $registry An array of key-value pairs where the key is the
     * formatter name the value is a FQCN for the formatter.
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
     * @param string $className A FQCN for a formatter.
     * @return void
     */
    public function set(string $name, string $className): void
    {
        $this->registry[$name] = $className;
        $this->converted[$name] = false;
    }

    /**
     * Gets a formatter from the registry by name.
     *
     * @param string $name The formatter to retrieve.
     * @return \Cake\I18n\FormatterInterface A formatter object.
     * @throws \Cake\I18n\Exception\I18nException
     */
    public function get(string $name): FormatterInterface
    {
        if (!isset($this->registry[$name])) {
            throw new I18nException("Formatter named `{$name}` has not been registered");
        }

        if (!$this->converted[$name]) {
            $this->registry[$name] = new $this->registry[$name]();
            $this->converted[$name] = true;
        }

        return $this->registry[$name];
    }
}
