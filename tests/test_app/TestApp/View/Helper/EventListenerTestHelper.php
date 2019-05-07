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
namespace TestApp\View\Helper;

use Cake\Event\EventInterface;
use Cake\View\Helper;

class EventListenerTestHelper extends Helper
{
    /**
     * Before render callback. Stub.
     *
     * @param \Cake\Event\EventInterface $event The event instance.
     * @param string $viewFile The view file being rendered.
     * @return void
     */
    public function beforeRender(EventInterface $event, $viewFile)
    {
        $this->config('options.foo', 'bar');
    }

    /**
     * Event listeners.
     *
     * @return array
     */
    public function implementedEvents(): array
    {
        return ['View.beforeRender' => 'beforeRender'];
    }
}
