<?php
declare(strict_types=1);

/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @since         3.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Console\Exception;

/**
 * Used when a shell cannot be found.
 */
class MissingShellException extends ConsoleException
{
    /**
     * @var string
     */
    protected $_messageTemplate = 'Shell class for "%s" could not be found.'
        . ' If you are trying to use a plugin shell, that was loaded via $this->addPlugin(),'
        . ' you may need to update bin/cake.php to match https://github.com/cakephp/app/tree/master/bin/cake.php';
}
