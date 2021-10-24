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
 * @since         3.2.5
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Validation;

/**
 * Use namespace injection to overwrite is_uploaded_file()
 * during tests.
 *
 * @param string $filename The file to check.
 * @return bool Whether the file exists.
 */
function is_uploaded_file($filename): bool
{
    return file_exists($filename);
}
