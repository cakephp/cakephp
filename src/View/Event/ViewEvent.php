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
 * @since         3.1
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\View\Event;

use Cake\Event\Event;
use Cake\View\View;

/**
 * {@inheritdoc}
 */
class ViewEvent extends Event
{

    /**
     * {@inheritdoc}
     *
     * @param string $name Name of the event
     * @param \Cake\View\View $view the view that this event applies to
     * @param array|null $data any value you wish to be transported with this event to it can be read by listeners
     */
    public function __construct($name, View $view, $data = null)
    {
        if (strpos($name, '.') === false) {
            $name = 'View.' . $name;
        }

        parent::__construct($name, $view, $data);
    }

    /**
     * Returns the view this event applies to
     *
     * @return \Cake\View\View
     */
    public function view()
    {
        return $this->subject();
    }
}
