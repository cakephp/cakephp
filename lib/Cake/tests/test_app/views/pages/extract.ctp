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