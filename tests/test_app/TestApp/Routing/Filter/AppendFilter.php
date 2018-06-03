<?php
namespace TestApp\Routing\Filter;

use Cake\Event\EventInterface;
use Cake\Routing\DispatcherFilter;

class AppendFilter extends DispatcherFilter
{
    public function afterDispatch(EventInterface $event)
    {
        $response = $event->getData('response');
        $response->body($response->body() . ' appended content');
    }
}
