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
 * @since         3.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\I18n;

use Cake\Core\Exception\CakeException;

/**
 * Wraps multiple message loaders calling them one after another until
 * one of them returns a non-empty package.
 */
class ChainMessagesLoader
{
    /**
     * The list of callables to execute one after another for loading messages
     *
     * @var array<callable>
     */
    protected array $_loaders = [];

    /**
     * Receives a list of callable functions or objects that will be executed
     * one after another until one of them returns a non-empty translations package
     *
     * @param array<callable> $loaders List of callables to execute
     */
    public function __construct(array $loaders)
    {
        $this->_loaders = $loaders;
    }

    /**
     * Executes this object returning the translations package as configured in
     * the chain.
     *
     * @return \Cake\I18n\Package
     * @throws \Cake\Core\Exception\CakeException if any of the loaders in the chain is not a valid callable
     */
    public function __invoke(): Package
    {
        foreach ($this->_loaders as $k => $loader) {
            if (!is_callable($loader)) {
                throw new CakeException(sprintf(
                    'Loader `%s` in the chain is not a valid callable.',
                    $k
                ));
            }

            $package = $loader();
            if (!$package) {
                continue;
            }

            if (!($package instanceof Package)) {
                throw new CakeException(sprintf(
                    'Loader `%s` in the chain did not return a valid Package object.',
                    $k
                ));
            }

            return $package;
        }

        return new Package();
    }
}
