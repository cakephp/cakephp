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
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         3.0.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\I18n;

use Aura\Intl\Package;

/**
 * Wraps multiple message loaders calling them one after another until
 * one of them returns a non-empty package.
 *
 */
class ChainMessagesLoader
{

    /**
     * The list of callables to execute one after another for loading messages
     *
     * @var array
     */
    protected $_loaders = [];

    /**
     * Receives a list of callable functions or objects that will be executed
     * one after another until one of them returns a non-empty translations package
     *
     * @param array $loaders List of callables to execute
     */
    public function __construct(array $loaders)
    {
        $this->_loaders = $loaders;
    }

    /**
     * Executes this object returning the translations package as configured in
     * the chain.
     *
     * @return \Aura\Intl\Package
     * @throws \RuntimeException if any of the loaders in the chain is not a valid callable
     */
    public function __invoke()
    {
        foreach ($this->_loaders as $k => $loader) {
            if (!is_callable($loader)) {
                throw new \RuntimeException(sprintf(
                    'Loader "%s" in the chain is not a valid callable',
                    $k
                ));
            }

            $package = $loader();
            if (!$package) {
                continue;
            }

            if (!($package instanceof Package)) {
                throw new \RuntimeException(sprintf(
                    'Loader "%s" in the chain did not return a valid Package object',
                    $k
                ));
            }

            if (count($package->getMessages())) {
                return $package;
            }
        }
        return new Package;
    }
}
