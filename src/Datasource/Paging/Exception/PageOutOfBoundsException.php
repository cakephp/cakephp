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
 * @since         3.5.0
 * @license       https://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Datasource\Paging\Exception;

use Cake\Core\Exception\CakeException;

/**
 * Exception raised when requested page number does not exist.
 */
class PageOutOfBoundsException extends CakeException
{
    /**
     * @inheritDoc
     */
    protected $_messageTemplate = 'Page number %s could not be found.';
}

// phpcs:disable
class_exists('Cake\Datasource\Exception\PageOutOfBoundsException');
// phpcs:enable
