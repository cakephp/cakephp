<?php
declare(strict_types=1);

use function Cake\Core\deprecationWarning;

deprecationWarning('Since 4.1.0: `Comparison` deprecated. Use `ComparisonExpression` instead.');
class_exists('Cake\Database\Expression\ComparisonExpression');
