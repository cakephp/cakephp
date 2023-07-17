<?php
declare(strict_types=1);

use function Cake\Core\deprecationWarning;

/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @since         4.2.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
deprecationWarning(
    'Since 4.2.0: Cake\Core\Exception\Exception is deprecated.' .
    'Use Cake\Core\Exception\CakeException instead.'
);
class_exists('Cake\Core\Exception\CakeException');
