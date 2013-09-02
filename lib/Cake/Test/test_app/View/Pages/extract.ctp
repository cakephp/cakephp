<?php
$count = 10;
$messages = array('count' => 10);

// Plural
echo __n('You have %d new message.', 'You have %d new messages.', $count);
echo __n('You deleted %d message.', 'You deleted %d messages.', $messages['count']);

// Domain Plural
echo __dn('domain', 'You have %d new message (domain).', 'You have %d new messages (domain).', '10');
echo __dn('domain', 'You deleted %d message (domain).', 'You deleted %d messages (domain).', $messages['count']);

// Duplicated Message
echo __('Editing this Page');
echo __('You have %d new message.');

// Contains quotes
echo __('double "quoted"');
echo __("single 'quoted'");

// Multiline
__('Hot features!'
	. "\n - No Configuration:"
		. ' Set-up the database and let the magic begin'
	. "\n - Extremely Simple:"
		. ' Just look at the name...It\'s Cake'
	. "\n - Active, Friendly Community:"
		. ' Join us #cakephp on IRC. We\'d love to help you get started');

// Category
echo __c('You have a new message (category: LC_TIME).', 5);
