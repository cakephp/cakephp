<?php
$this->start('non closing block');
throw new \Exception('Exception with open buffers');
