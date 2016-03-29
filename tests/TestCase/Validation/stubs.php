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
 * @since         3.2.5
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Validation {

    /**
     * Use namespace injection to overwrite is_uploaded_file()
     * during tests.
     *
     * @param string $filename The file to check.
     * @return bool Whether or not the file exists.
     */
    function is_uploaded_file($filename)
    {
        return file_exists($filename);
    }
}
