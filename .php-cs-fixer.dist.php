<?php

use PhpCsFixer\Config;
use PhpCsFixer\Finder;

$finder = Finder::create()
    ->in(__DIR__ . '/src/')
    ->in(__DIR__ . '/tests/')
;
$rules = [
    '@PSR12' => true,
    '@PHP82Migration' => true,
    'array_syntax' => [
        'syntax' => 'short',
    ],
    'logical_operators' => true,
    'native_constant_invocation' => [
        'fix_built_in' => true,
    ],
    'native_function_invocation' => [
        'include' => ['@all'],
    ],
    'strict_comparison' => true,
    'strict_param' => true,
    'whitespace_after_comma_in_array' => true,
];

return (new Config())
    ->setRules($rules)
    ->setFinder($finder)
    ->setUsingCache(false)
    ->setRiskyAllowed(true)
;
