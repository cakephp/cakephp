<?php
declare(strict_types=1);

use Cake\Console\Helper\BannerHelper;
use function Cake\Core\deprecationWarning;

deprecationWarning('5.4.0', 'Cake\Command\Helper\BannerHelper is deprecated. Use Cake\Console\Helper\BannerHelper instead.');

class_exists(BannerHelper::class);
