[![Total Downloads](https://img.shields.io/packagist/dt/cakephp/event.svg?style=flat-square)](https://packagist.org/packages/cakephp/event)
[![License](https://img.shields.io/badge/license-MIT-blue.svg?style=flat-square)](LICENSE.txt)

# CakePHP Event Library

This library emulates several aspects of how events are triggered and managed in popular JavaScript
libraries such as jQuery: An event object is dispatched to all listeners. The event object holds information
about the event, and provides the ability to stop event propagation at any point.
Listeners can register themselves or can delegate this task to other objects and have the chance to alter the
state and the event itself for the rest of the callbacks.

## Usage

Listeners need to be registered into a manager and events can then be triggered so that listeners can be informed
of the action.

```php
use Cake\Event\Event;
use Cake\Event\EventDispatcherTrait;

class Orders
{

	use EventDispatcherTrait;

	public function placeOrder($order)
	{
		$this->doStuff();
		$event = new Event('Orders.afterPlace', $this, [
			'order' => $order
		]);
		$this->getEventManager()->dispatch($event);
	}
}

$orders = new Orders();
$orders->getEventManager()->on(function ($event) {
	// Do something after the order was placed
	...
}, 'Orders.afterPlace');

$orders->placeOrder($order);
```

The above code allows you to easily notify the other parts of the application that an order has been created.
You can then do tasks like send email notifications, update stock, log relevant statistics and other tasks
in separate objects that focus on those concerns.

## Documentation

Please make sure you check the [official documentation](https://book.cakephp.org/3/en/core-libraries/events.html)
