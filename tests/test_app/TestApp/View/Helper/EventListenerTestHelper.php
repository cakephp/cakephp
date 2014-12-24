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
 * @since         3.0.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace TestApp\View\Helper;

use Cake\Event\Event;
use Cake\View\Helper;

class EventListenerTestHelper extends Helper
{

    /**
     * Before render callback. Stub.
     *
     * @param \Cake\Event\Event $event The event instance.
     * @param string $viewFile The view file being rendered.
     * @return void
     */
    public function beforeRender(Event $event, $viewFile)
    {
        $this->config('options.foo', 'bar');
    }

    /**
     * Event listeners.
     *
     * @return array
     */
    public function implementedEvents()
    {
        return ['View.beforeRender' => 'beforeRender'];
    }
}
