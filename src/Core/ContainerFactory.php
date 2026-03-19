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
 * @since         5.3.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Core;

/**
 * Creates the configured application container implementation.
 */
class ContainerFactory
{
    /**
     * @return \Cake\Core\ContainerInterface
     */
    public static function create(): ContainerInterface
    {
        return match (Configure::read('App.container', 'league')) {
            'cake' => new CakeContainer(),
            default => new Container(),
        };
    }
}
