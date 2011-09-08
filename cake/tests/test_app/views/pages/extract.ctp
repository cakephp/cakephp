<?php
$count = 10;
$messages = array('count' => 10);

// Plural
__n('You have %d new message.', 'You have %d new messages.', $count);
__n('You deleted %d message.', 'You deleted %d messages.', $messages['count']);

// Domain Plural
__dn('domain', 'You have %d new message (domain).', 'You have %d new messages (domain).', '10');
__dn('domain', 'You deleted %d message (domain).', 'You deleted %d messages (domain).', $messages['count']);

// Duplicated Message
__('Editing this Page');

// Multiline with comments
__('Hot features!'
  . "\n - No Configuration:"				// Comments will be stripped
		. ' Set-up the database and let the magic begin'
	. "\n - Extremely Simple:"				// Comments will be stripped
		. ' Just look at the name...It\'s Cake'
	. "\n - Active, Friendly Community:"	// Comments will be stripped
		. ' Join us #cakephp on IRC. We\'d love to help you get started');

// This throws an error and is not parsed
__('Found ' . $count . ' new messages');