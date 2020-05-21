<?php
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
namespace Cake\Datasource\Exception;

use Cake\Core\Exception\Exception;

/**
 * Exception class to be thrown when a datasource configuration is not found
 */
class MissingDatasourceConfigException extends Exception
{
    /**
     * @inheritDoc
     */
    protected $_messageTemplate = 'The datasource configuration "%s" was not found.';
}
