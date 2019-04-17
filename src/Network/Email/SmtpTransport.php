<?php
// @deprecated 3.5.0 Backward compatibility with 2.x, 3.0.x
class_alias('Cake\Mailer\Transport\SmtpTransport', 'Cake\Network\Email\SmtpTransport');
deprecationWarning('Use Cake\Mailer\Transport\SmtpTransport instead of Cake\Network\Email\SmtpTransport.');
