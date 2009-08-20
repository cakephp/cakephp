<?php
$count = 10;
$message = array('count' => 10);

// Plural
__n('You have %d new message.', 'You have %d new messages.', $count);
__n('You deleted %d message.', 'You deleted %d messages.', $messages['count']);

// Domain Plural
__dn('domain', 'You have %d new message (domain).', 'You have %d new messages (domain).', '10');
__dn('domain', 'You deleted %d message (domain).', 'You deleted %d messages (domain).', $messages['count']);