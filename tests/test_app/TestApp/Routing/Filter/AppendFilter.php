<?php
namespace TestApp\Routing\Filter;

use Cake\Event\Event;
use Cake\Routing\DispatcherFilter;

class AppendFilter extends DispatcherFilter
{
    public function afterDispatch(Event $event)
    {
        $response = $event->data('response');
        $response->body($response->body() . ' appended content');
    }
}
