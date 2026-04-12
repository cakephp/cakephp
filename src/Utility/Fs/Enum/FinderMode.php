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
 * @since         5.4.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Utility\Fs\Enum;

/**
 * Enum for Finder iteration modes
 *
 * Defines what type of filesystem items to iterate over.
 */
enum FinderMode
{
    /**
     * Iterate only files
     */
    case FILES;

    /**
     * Iterate only directories
     */
    case DIRECTORIES;

    /**
     * Iterate both files and directories
     */
    case ALL;
}
