<?php
declare(strict_types=1);

use Rector\Caching\ValueObject\Storage\FileCacheStorage;
use Rector\Config\RectorConfig;
use Rector\Set\ValueObject\SetList;

$cacheDir = getenv('RECTOR_CACHE_DIR') ?: sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'rector';

return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/config',
        __DIR__ . '/contrib',
        __DIR__ . '/src',
        __DIR__ . '/tests',
    ])

    ->withCache(
        cacheClass: FileCacheStorage::class,
        cacheDirectory: $cacheDir,
    )

    ->withPhpSets()
    ->withAttributesSets()

    ->withSets([
        SetList::CODE_QUALITY,
        SetList::CODING_STYLE,
        SetList::DEAD_CODE,
        SetList::EARLY_RETURN,
        SetList::INSTANCEOF,
        SetList::TYPE_DECLARATION,
    ])

    ->withSkip([
        __DIR__ . '/tests/test_app/templates',
        __DIR__ . '/tests/test_app/Plugin/TestPlugin/templates',

        \Rector\CodeQuality\Rector\Catch_\ThrowWithPreviousExceptionRector::class,
        \Rector\CodeQuality\Rector\ClassMethod\ExplicitReturnNullRector::class,
        \Rector\CodeQuality\Rector\ClassMethod\OptionalParametersAfterRequiredRector::class,
        \Rector\CodeQuality\Rector\Class_\CompleteDynamicPropertiesRector::class,
        \Rector\CodeQuality\Rector\Concat\JoinStringConcatRector::class,
        \Rector\CodeQuality\Rector\Foreach_\ForeachToInArrayRector::class,
        \Rector\CodeQuality\Rector\Foreach_\UnusedForeachValueToArrayKeysRector::class,
        \Rector\CodeQuality\Rector\FuncCall\CompactToVariablesRector::class,
        \Rector\CodeQuality\Rector\FuncCall\SimplifyRegexPatternRector::class,
        \Rector\CodeQuality\Rector\FunctionLike\SimplifyUselessVariableRector::class,
        \Rector\CodeQuality\Rector\Identical\FlipTypeControlToUseExclusiveTypeRector::class,
        \Rector\CodeQuality\Rector\If_\ConsecutiveNullCompareReturnsToNullCoalesceQueueRector::class,
        \Rector\CodeQuality\Rector\If_\ExplicitBoolCompareRector::class,
        \Rector\CodeQuality\Rector\If_\SimplifyIfElseToTernaryRector::class,
        \Rector\CodeQuality\Rector\If_\SimplifyIfReturnBoolRector::class,
        \Rector\CodeQuality\Rector\Include_\AbsolutizeRequireAndIncludePathRector::class,
        \Rector\CodeQuality\Rector\Isset_\IssetOnPropertyObjectToPropertyExistsRector::class,
        \Rector\CodingStyle\Rector\Catch_\CatchExceptionNameMatchingTypeRector::class,
        \Rector\CodingStyle\Rector\Catch_\CatchExceptionNameMatchingTypeRector::class,
        \Rector\CodingStyle\Rector\ClassMethod\MakeInheritedMethodVisibilitySameAsParentRector::class,
        \Rector\CodingStyle\Rector\ClassMethod\NewlineBeforeNewAssignSetRector::class,
        \Rector\CodingStyle\Rector\Encapsed\EncapsedStringsToSprintfRector::class,
        \Rector\CodingStyle\Rector\FuncCall\StrictArraySearchRector::class,
        \Rector\CodingStyle\Rector\FuncCall\VersionCompareFuncCallToConstantRector::class,
        \Rector\CodingStyle\Rector\If_\NullableCompareToNullRector::class,
        \Rector\CodingStyle\Rector\Stmt\NewlineAfterStatementRector::class,
        \Rector\CodingStyle\Rector\String_\UseClassKeywordForClassNameResolutionRector::class,
        \Rector\DeadCode\Rector\Assign\RemoveDoubleAssignRector::class,
        \Rector\DeadCode\Rector\Assign\RemoveUnusedVariableAssignRector::class,
        \Rector\DeadCode\Rector\Cast\RecastingRemovalRector::class,
        \Rector\DeadCode\Rector\ClassMethod\RemoveEmptyClassMethodRector::class,
        \Rector\DeadCode\Rector\ClassMethod\RemoveNullTagValueNodeRector::class,
        \Rector\DeadCode\Rector\ClassMethod\RemoveUnusedConstructorParamRector::class,
        \Rector\DeadCode\Rector\ClassMethod\RemoveUnusedPrivateMethodRector::class,
        \Rector\DeadCode\Rector\ClassMethod\RemoveUselessParamTagRector::class,
        \Rector\DeadCode\Rector\ClassMethod\RemoveUselessReturnTagRector::class,
        \Rector\DeadCode\Rector\ConstFetch\RemovePhpVersionIdCheckRector::class,
        \Rector\DeadCode\Rector\Expression\RemoveDeadStmtRector::class,
        \Rector\DeadCode\Rector\For_\RemoveDeadIfForeachForRector::class,
        \Rector\DeadCode\Rector\For_\RemoveDeadLoopRector::class,
        \Rector\DeadCode\Rector\If_\RemoveAlwaysTrueIfConditionRector::class,
        \Rector\DeadCode\Rector\If_\RemoveDeadInstanceOfRector::class,
        \Rector\DeadCode\Rector\If_\UnwrapFutureCompatibleIfPhpVersionRector::class,
        \Rector\DeadCode\Rector\Node\RemoveNonExistingVarAnnotationRector::class,
        \Rector\DeadCode\Rector\Property\RemoveUselessVarTagRector::class,
        \Rector\EarlyReturn\Rector\If_\ChangeOrIfContinueToMultiContinueRector::class,
        \Rector\EarlyReturn\Rector\Return_\ReturnBinaryOrToEarlyReturnRector::class,
        \Rector\Php56\Rector\FuncCall\PowToExpRector::class,
        \Rector\Php73\Rector\FuncCall\ArrayKeyFirstLastRector::class,
        \Rector\Php73\Rector\FuncCall\SetCookieRector::class,
        \Rector\Php73\Rector\FuncCall\StringifyStrNeedlesRector::class,
        \Rector\Php73\Rector\String_\SensitiveHereNowDocRector::class,
        \Rector\Php74\Rector\Closure\ClosureToArrowFunctionRector::class,
        \Rector\Php80\Rector\Class_\ClassPropertyAssignToConstructorPromotionRector::class,
        \Rector\Php81\Rector\FuncCall\NullToStrictStringFuncCallArgRector::class,
        \Rector\Php81\Rector\Property\ReadOnlyPropertyRector::class,
        \Rector\Strict\Rector\Empty_\DisallowedEmptyRuleFixerRector::class,
        \Rector\TypeDeclaration\Rector\ArrowFunction\AddArrowFunctionReturnTypeRector::class,
        \Rector\TypeDeclaration\Rector\BooleanAnd\BinaryOpNullableToInstanceofRector::class,
        \Rector\TypeDeclaration\Rector\ClassMethod\AddParamTypeBasedOnPHPUnitDataProviderRector::class,
        \Rector\TypeDeclaration\Rector\ClassMethod\AddVoidReturnTypeWhereNoReturnRector::class,
        \Rector\TypeDeclaration\Rector\ClassMethod\BoolReturnTypeFromBooleanConstReturnsRector::class,
        \Rector\TypeDeclaration\Rector\ClassMethod\ParamTypeByMethodCallTypeRector::class,
        \Rector\TypeDeclaration\Rector\ClassMethod\ReturnNeverTypeRector::class,
        \Rector\TypeDeclaration\Rector\ClassMethod\ReturnTypeFromMockObjectRector::class,
        \Rector\TypeDeclaration\Rector\ClassMethod\ReturnTypeFromStrictFluentReturnRector::class,
        \Rector\TypeDeclaration\Rector\ClassMethod\ReturnTypeFromStrictTypedCallRector::class,
        \Rector\TypeDeclaration\Rector\ClassMethod\ReturnUnionTypeRector::class,
        \Rector\TypeDeclaration\Rector\ClassMethod\StrictArrayParamDimFetchRector::class,
        \Rector\TypeDeclaration\Rector\ClassMethod\StrictStringParamConcatRector::class,
        \Rector\TypeDeclaration\Rector\Class_\TypedPropertyFromCreateMockAssignRector::class,
        \Rector\TypeDeclaration\Rector\Closure\AddClosureNeverReturnTypeRector::class,
        \Rector\TypeDeclaration\Rector\Closure\ClosureReturnTypeRector::class,
        \Rector\TypeDeclaration\Rector\Property\TypedPropertyFromAssignsRector::class,
        \Rector\TypeDeclaration\Rector\Property\TypedPropertyFromStrictConstructorRector::class,
        \Rector\TypeDeclaration\Rector\Property\TypedPropertyFromStrictSetUpRector::class,
        \Rector\TypeDeclaration\Rector\While_\WhileNullableToInstanceofRector::class,

        // Manual - only appliable for part of the code
        \Rector\CodeQuality\Rector\Equal\UseIdenticalOverEqualWithSameTypeRector::class,
        \Rector\DeadCode\Rector\Expression\RemoveDeadStmtRector::class,
        \Rector\Php55\Rector\String_\StringClassNameToClassConstantRector::class,
        \Rector\Php73\Rector\FuncCall\ArrayKeyFirstLastRector::class,
        \Rector\Php80\Rector\FuncCall\ClassOnObjectRector::class,
        \Rector\CodeQuality\Rector\Ternary\SwitchNegatedTernaryRector::class,
    ]);
