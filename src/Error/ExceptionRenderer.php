<?php
declare(strict_types=1);

class_alias(
    'Cake\Error\Renderer\HtmlExceptionRenderer',
    'Cake\Error\ExceptionRenderer'
);
deprecationWarning(
    'Use Cake\Error\Renderer\SapiBasedExceptionRenderer instead of Cake\Error\ExceptionRenderer ' .
    'to preserve automatic exception rendering based on cli/web. Or use Cake\Error\Renderer\HtmlExceptionRenderer ' .
    'to have exceptions rendered as HTML.',
    0
);
