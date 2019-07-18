<?php
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Console;

use InvalidArgumentException;

/**
 * This is a factory for creating Command and Shell instances.
 *
 * This factory can be replaced or extended if you need to customize building
 * your command and shell objects.
 */
class CommandFactory implements CommandFactoryInterface
{

    /**
     * {@inheritDoc}
     */
    public function create($className)
    {
        $command = new $className();
        if (!($command instanceof Command) && !($command instanceof Shell)) {
            $valid = implode('` or `', [Shell::class, Command::class]);
            $message = sprintf('Class `%s` must be an instance of `%s`.', $className, $valid);
            throw new InvalidArgumentException($message);
        }

        return $command;
    }
}
