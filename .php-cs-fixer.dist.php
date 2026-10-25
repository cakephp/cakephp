<?php
declare(strict_types=1);

use PhpCsFixer\Config;
use PhpCsFixer\Finder;

$finder = (new Finder())
    ->in('config')
    ->in('src')
    ->in('tests')
    ->exclude('templates');

return (new Config())
    ->setRiskyAllowed(false)
    ->setRules([
        '@PER-CS3x0' => true,
        'blank_line_after_opening_tag' => false,
        'blank_lines_before_namespace' => ['max_line_breaks' => 2, 'min_line_breaks' => 1],
        'fully_qualified_strict_types' => ['import_symbols' => true, 'phpdoc_tags' => []],
        'linebreak_after_opening_tag' => true,
        'no_unused_imports' => true,
        'blank_line_between_import_groups' => false,
        'cast_spaces' => ['space' => 'none'],
        'single_line_empty_body' => false,
        'yoda_style' => ['equal' => false, 'identical' => false, 'less_and_greater' => false],
    ])
    ->setFinder($finder);
