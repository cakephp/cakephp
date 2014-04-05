<?php
$data = array('users' => array('user' => array()));
foreach ($users as $user) {
	$data['users']['user'][] = array('@' => $user['User']['username']);
}
echo Xml::fromArray($data)->saveXml();
