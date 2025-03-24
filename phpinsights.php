<?php

declare(strict_types=1);

use NunoMaduro\PhpInsights\Domain\Insights\CyclomaticComplexityIsHigh;
use NunoMaduro\PhpInsights\Domain\Insights\ForbiddenNormalClasses;
use NunoMaduro\PhpInsights\Domain\Insights\ForbiddenTraits;
use PHP_CodeSniffer\Standards\Generic\Sniffs\Files\LineLengthSniff;
use PHP_CodeSniffer\Standards\Generic\Sniffs\Formatting\SpaceAfterNotSniff;
use PhpCsFixer\Fixer\ClassNotation\OrderedClassElementsFixer;
use PhpCsFixer\Fixer\FunctionNotation\FunctionDeclarationFixer;
use PhpCsFixer\Fixer\ReturnNotation\ReturnAssignmentFixer;
use SlevomatCodingStandard\Sniffs\Arrays\TrailingArrayCommaSniff;
use SlevomatCodingStandard\Sniffs\Classes\SuperfluousExceptionNamingSniff;
use SlevomatCodingStandard\Sniffs\Classes\SuperfluousInterfaceNamingSniff;
use SlevomatCodingStandard\Sniffs\Commenting\DocCommentSpacingSniff;
use SlevomatCodingStandard\Sniffs\ControlStructures\DisallowEmptySniff;
use SlevomatCodingStandard\Sniffs\TypeHints\DisallowArrayTypeHintSyntaxSniff;
use SlevomatCodingStandard\Sniffs\TypeHints\DisallowMixedTypeHintSniff;
use SlevomatCodingStandard\Sniffs\TypeHints\ParameterTypeHintSniff;
use SlevomatCodingStandard\Sniffs\TypeHints\PropertyTypeHintSniff;
use SlevomatCodingStandard\Sniffs\TypeHints\ReturnTypeHintSniff;

return [
    'preset' => 'default',
    'remove' => [
        DisallowArrayTypeHintSyntaxSniff::class,
        DisallowEmptySniff::class,
        DisallowMixedTypeHintSniff::class,
        DocCommentSpacingSniff::class,
        ForbiddenNormalClasses::class,
        ForbiddenTraits::class,
        FunctionDeclarationFixer::class,
        OrderedClassElementsFixer::class,
        ParameterTypeHintSniff::class,
        PropertyTypeHintSniff::class,
        ReturnAssignmentFixer::class,
        ReturnTypeHintSniff::class,
        SpaceAfterNotSniff::class,
        SuperfluousExceptionNamingSniff::class,
        SuperfluousInterfaceNamingSniff::class,
        TrailingArrayCommaSniff::class
    ],
    'config' => [
        LineLengthSniff::class => [
            'lineLimit' => 120,
            'absoluteLineLimit' => 120,
            'ignoreComments' => true
        ],
        CyclomaticComplexityIsHigh::class => [
            'maxComplexity' => 11
        ]
    ],
    'requirements' => [
        'min-quality' => 100,
        'min-complexity' => 40,
        'min-architecture' => 100,
        'min-style' => 100
    ],
    'exclude' => [
        '.idea/'
    ]
];
