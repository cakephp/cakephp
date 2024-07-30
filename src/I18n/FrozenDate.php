<?php
declare(strict_types=1);

use Cake\I18n\Date;
use function Cake\Core\deprecationWarning;

deprecationWarning('5.0.0', 'Cake\I18n\FrozenDate is deprecated. Use Cake\I18n\Date instead');

class_exists(Date::class);
