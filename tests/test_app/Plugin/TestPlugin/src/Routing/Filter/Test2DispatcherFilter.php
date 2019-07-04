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
 * @since         2.2.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace TestPlugin\Routing\Filter;

use Cake\Event\EventInterface;
use Cake\Routing\DispatcherFilter;

/**
 * Test2DispatcherFilter
 */
class Test2DispatcherFilter extends DispatcherFilter
{
    public function beforeDispatch(EventInterface $event)
    {
        $event->data('response')->statusCode(500);
        $event->stopPropagation();

        return $event->data('response');
    }

    public function afterDispatch(EventInterface $event)
    {
        $event->data('response')->statusCode(200);
    }
}
