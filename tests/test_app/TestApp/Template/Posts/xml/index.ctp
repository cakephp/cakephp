<?php
$data = ['users' => ['user' => []]];
foreach ($users as $user) {
    $data['users']['user'][] = ['@' => $user['User']['username']];
}
echo \Cake\Utility\Xml::fromArray($data)->saveXml();
