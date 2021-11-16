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
namespace Cake\Database\Exception;

use Cake\Core\Exception\CakeException;

/**
 * Class MissingExtensionException
 */
class MissingExtensionException extends CakeException
{
    /**
     * @inheritDoc
     */
    protected $_messageTemplate = 'Could not use driver `%s` for connection `%s` due to missing PHP extension.';
}
