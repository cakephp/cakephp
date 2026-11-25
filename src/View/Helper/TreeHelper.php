<?php
declare(strict_types=1);

/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @copyright     Copyright (c) 2008, Andy Dawson
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         5.5.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\View\Helper;

use BackedEnum;
use Cake\Core\Configure;
use Cake\Database\Type\EnumLabelInterface;
use Cake\Datasource\EntityInterface;
use Cake\View\Helper;
use Closure;
use RuntimeException;
use UnitEnum;
use function Cake\Core\h;

/**
 * Helper to generate tree representations of MPTT or recursively nested data.
 *
 * @extends \Cake\View\Helper<\Cake\View\View>
 */
class TreeHelper extends Helper
{
    /**
     * Default settings
     *
     * @var array<string, mixed>
     */
    protected array $defaultConfig = [
        'alias' => 'name',
        'type' => 'ul',
        'itemType' => 'li',
        'id' => false,
        'class' => false,
        'element' => false,
        'callback' => false,
        'autoPath' => false,
        'autoPathClass' => 'active',
        'hideUnrelated' => false,
        'treePath' => [],
        'nodeKey' => 'id',
        'level' => null,
        'left' => 'lft',
        'right' => 'rght',
        'depth' => 0,
        'maxDepth' => 999,
        'firstChild' => true,
        'indent' => null,
        'indentWith' => '    ',
        'splitDepth' => false,
        'splitCount' => null,
        'totalNodes' => null,
        'fullSettings' => false,
    ];

    protected array $currentConfig = [];

    protected array $typeAttributes = [];

    protected array $typeAttributesNext = [];

    protected array $itemAttributes = [];

    /**
     * Tree generation method.
     *
     * Accepts the results of
     *     find('all', fields: ['lft', 'rght', 'whatever'], order: 'lft ASC'];
     *     children(); // if the table has Tree behavior attached
     * or find('threaded'); and generates a tree structure of the data.
     *
     * Settings (2nd parameter):
     *    'alias' => the array key to output for a simple ul (not used if element or callback is specified)
     *    'type' => type of output defaults to UL
     *    'itemType => type of item output default to LI
     *    'id' => id for top level 'type'
     *    'class' => class for top level 'type'
     *    'element' => path to an element to render to get node contents.
     *    'callback' => callback to use to get node contents. e.g. array(&$anObject, 'methodName') or 'floatingMethod'
     *    'autoPath' => [$left, $right, $classToAdd = 'active'] if set any item in the path will have the class $classToAdd added. MPTT only.
     *      You can also just pass the current entity and it will extract lft and rght properties based on the config.
     *    'hideUnrelated' => if unrelated (not children, not siblings) should be hidden, needs 'treePath', true/false,
     *      an integer (number of levels that are always shown) or a callable that marks nodes itself.
     *      Hidden nodes are skipped automatically, the nodes passed to callbacks are never modified.
     *    'treePath' => list of node keys from the root to the current node. Inserted into callback/element and
     *      used to find the branch that stays open when 'hideUnrelated' is enabled.
     *    'nodeKey' => name of the field identifying a node, matched against 'treePath'. Defaults to `id`.
     *    'level' => name of the field holding the depth of a node, same as the TreeBehavior `level` config.
     *      If set, the depth of flat rows is read from it, relative to the first row, instead of being derived
     *      from the nesting. MPTT only.
     *    'left' => name of the 'lft' field if not lft. only applies to MPTT data
     *    'right' => name of the 'rght' field if not rght. only applies to MPTT data
     *    'depth' => used internally when running recursively, can be used to override the depth in either mode.
     *    'maxDepth' => used to control the depth upto which to generate tree
     *    'firstChild' => used internally when running recursively.
     *    'splitDepth' => if multiple "parallel" types are required, instead of one big type, nominate the depth to do so here
     *        example: useful if you have 30 items to display, and you'd prefer they appeared in the source as 3 lists of 10 to be able to
     *        style/float them.
     *    'splitCount' => the number of "parallel" types. defaults to null (disabled) set the splitCount,
     *        and optionally set the splitDepth to get parallel lists. Each list of siblings is split on its own.
     *
     * @param iterable $data Data to loop over
     * @param array<string, mixed> $config Config
     * @throws \Exception
     * @return string HTML representation of the passed data
     */
    public function generate(iterable $data, array $config = []): string
    {
        return $this->generateSubTree($data, $config);
    }

    /**
     * @param iterable $data
     * @param array<string, mixed> $config
     * @param \Cake\Datasource\EntityInterface|iterable|array|null $parent
     * @param array<int, array<string, mixed>> $visibility Visibility of the nodes in `$data`, see `calculateVisibility()`
     * @throws \Exception
     * @return string
     */
    protected function generateSubTree(
        iterable $data,
        array $config,
        EntityInterface|iterable|null $parent = null,
        array $visibility = [],
    ): string {
        $dataArray = iterator_to_array($data, false);
        if ($dataArray === []) {
            return '';
        }

        $this->currentConfig = $config + $this->getConfig();
        if ($this->currentConfig['autoPath'] && is_object($this->currentConfig['autoPath'])) {
            $autoPathEntity = $this->currentConfig['autoPath'];
            $propertyLeft = $this->currentConfig['left'];
            $propertyRight = $this->currentConfig['right'];
            $this->currentConfig['autoPath'] = [$autoPathEntity->$propertyLeft, $autoPathEntity->$propertyRight];
        }

        if ($this->currentConfig['autoPath'] && !isset($this->currentConfig['autoPath'][2])) {
            $this->currentConfig['autoPath'][2] = $this->currentConfig['autoPathClass'];
        }
        $alias = $this->currentConfig['alias'];
        $type = $this->currentConfig['type'];
        $itemType = $this->currentConfig['itemType'];
        $id = $this->currentConfig['id'];
        $class = $this->currentConfig['class'];
        $element = $this->currentConfig['element'];
        $callback = $this->currentConfig['callback'];
        $autoPath = $this->currentConfig['autoPath'];
        $hideUnrelated = $this->currentConfig['hideUnrelated'];
        $treePath = $this->currentConfig['treePath'];
        $nodeKey = $this->currentConfig['nodeKey'];
        $left = $this->currentConfig['left'];
        $right = $this->currentConfig['right'];
        $depth = $this->currentConfig['depth'];
        $maxDepth = $this->currentConfig['maxDepth'];
        $indent = $this->currentConfig['indent'];
        $indentWith = $this->currentConfig['indentWith'];
        if ($indent === null && Configure::read('debug')) {
            $indent = true;
        }

        if ($parent === null) {
            $this->itemAttributes = [];
            $this->typeAttributes = [];
            $this->typeAttributesNext = [];
        }
        if ($depth == 0) {
            if ($class) {
                $this->addTypeAttribute('class', $class, null, 'previous');
            }
            if ($id) {
                $this->addTypeAttribute('id', $id, null, 'previous');
            }
        }
        $return = '';
        $this->currentConfig['totalNodes'] = count($dataArray);
        $keys = array_keys($dataArray);
        $isFlat = $this->isFlat($dataArray);
        $levelField = $this->currentConfig['level'];
        $baseLevel = $isFlat && $levelField ? (int)($dataArray[0][$levelField] ?? 0) : 0;

        if ($hideUnrelated === true || is_numeric($hideUnrelated)) {
            if ($parent === null) {
                $visibility = $this->calculateVisibility($dataArray, $isFlat);
            }
        } elseif ($hideUnrelated && is_callable($hideUnrelated)) {
            $hideUnrelated($dataArray, $treePath);
        }

        $splitCount = (int)$this->currentConfig['splitCount'];
        $splitDepth = $this->currentConfig['splitDepth'];
        $groupSize = static function (int $level, int $count) use ($splitCount, $splitDepth): int {
            if ($splitCount < 1 || $count < 1 || ($splitDepth && $splitDepth != $level)) {
                return 0;
            }

            return (int)ceil($count / $splitCount);
        };

        // Number of visible items in each list of siblings, keyed by parent index (-1 for the root list).
        $groupCounts = [];
        if ($splitCount > 0) {
            if ($isFlat) {
                foreach ($this->flatParents($dataArray)['parents'] as $i => $parentIndex) {
                    if (empty($visibility[$i]['hide'])) {
                        $groupCounts[$parentIndex] = ($groupCounts[$parentIndex] ?? 0) + 1;
                    }
                }
            } else {
                $groupCounts[-1] = count($dataArray) - count(array_filter(array_column($visibility, 'hide')));
            }
        }

        // Each entry describes a list of siblings. The first one is the list being generated, the others
        // belong to the flat rows that are still open. Hidden or skipped rows are tracked with `rendered`
        // set to false so that their descendants are skipped as well.
        /** @var list<array{right: mixed, rendered: bool, opened: bool, count: int, size: int}> $stack */
        $stack = [[
            'right' => null,
            'rendered' => true,
            'opened' => false,
            'count' => 0,
            'size' => $groupSize($depth, $groupCounts[-1] ?? 0),
        ]];
        $skipped = ['rendered' => false, 'opened' => false, 'count' => 0, 'size' => 0];

        $whiteSpace = '';
        foreach ($dataArray as $i => $result) {
            $row = $result;

            // Close open items as appropriate
            // phpcs:ignore Squiz.PHP.DisallowSizeFunctionsInLoops.Found
            while (count($stack) > 1 && ($stack[count($stack) - 1]['right'] < $row[$right])) {
                $closed = array_pop($stack);
                if (!$closed['rendered']) {
                    continue;
                }
                if ($closed['opened']) {
                    if ($indent) {
                        $whiteSpace = str_repeat((string)$indentWith, count($stack) - 1);
                        $return .= "\r\n" . $whiteSpace . $indentWith;
                    }
                    if ($type) {
                        $return .= '</' . $type . '>';
                    }
                }
                if ($itemType) {
                    $return .= '</' . $itemType . '>';
                }
            }
            $top = count($stack) - 1;
            $rowDepth = $depth + $top;
            if ($isFlat && $levelField && isset($row[$levelField])) {
                $rowDepth = $depth + (int)$row[$levelField] - $baseLevel;
            }

            $hasChildren = false;
            $firstChild = false;
            $lastChild = false;
            $hasVisibleChildren = false;
            $hasNestedRows = false;
            $numberOfDirectChildren = null;
            $numberOfTotalChildren = null;

            if (isset($result['children'])) {
                if ($result['children'] && $rowDepth < $maxDepth) {
                    $hasChildren = true;
                    $hasVisibleChildren = true;
                    $numberOfDirectChildren = count($result['children']);
                }

                $firstChild = $i === $keys[0];
                $lastChild = $i === $keys[count($keys) - 1];
            } elseif (isset($row[$left])) {
                $hasNestedRows = isset($dataArray[$i + 1]) && $dataArray[$i + 1][$right] < $row[$right];
                if ($row[$left] != ($row[$right] - 1) && $rowDepth < $maxDepth) {
                    $hasChildren = true;
                    $numberOfTotalChildren = ($row[$right] - $row[$left] - 1) / 2;
                    $hasVisibleChildren = $hasNestedRows;
                }
                if (!isset($dataArray[$i - 1]) || ($dataArray[$i - 1][$left] == $row[$left] - 1)) {
                    $firstChild = true;
                }
                if (!isset($dataArray[$i + 1]) || ($top > 0 && $stack[$top]['right'] == $row[$right] + 1)) {
                    $lastChild = true;
                }
            } else {
                throw new RuntimeException('Invalid Tree Structure: ' . print_r($result, true));
            }

            // Skip rows hidden by `hideUnrelated` and the descendants of skipped rows
            if (!$stack[$top]['rendered'] || !empty($visibility[$i]['hide'])) {
                if ($hasNestedRows) {
                    $stack[] = ['right' => $row[$right]] + $skipped;
                }
                continue;
            }

            $activePathElement = null;
            if ($autoPath) {
                if ($result[$left] <= $autoPath[0] && $result[$right] >= $autoPath[1]) {
                    $activePathElement = true;
                } elseif (isset($autoPath[3]) && $result[$left] == $autoPath[0]) {
                    $activePathElement = $autoPath[3];
                } else {
                    $activePathElement = false;
                }
            }

            $onTreePath = $hideUnrelated
                && $rowDepth === 0
                && !empty($treePath[0])
                && ($row[$nodeKey] ?? null) == $treePath[0];

            $elementData = [
                'data' => $result,
                'parent' => $parent,
                'depth' => $rowDepth,
                'hasChildren' => $hasChildren,
                'numberOfDirectChildren' => $numberOfDirectChildren,
                'numberOfTotalChildren' => $numberOfTotalChildren,
                'firstChild' => $firstChild,
                'lastChild' => $lastChild,
                'hasVisibleChildren' => $hasVisibleChildren,
                'activePathElement' => $activePathElement,
                'isSibling' => $rowDepth == 0 && !$activePathElement && !$onTreePath,
            ];

            $collapse = $hideUnrelated && $elementData['isSibling'];

            $this->currentConfig = $elementData + $this->currentConfig;
            if ($this->currentConfig['fullSettings']) {
                $elementData = $this->currentConfig;
            }

            // Main Content
            if ($element) {
                $content = $this->View->element($element, $elementData);
            } elseif ($callback) {
                [$content] = array_map($callback, [$elementData]);
            } else {
                $content = $row[$alias];
            }

            if (in_array($content, [null, false, ''], true)) {
                $this->itemAttributes = [];
                if ($hasNestedRows) {
                    $stack[] = ['right' => $row[$right]] + $skipped;
                }
                continue;
            }

            if (!is_string($content)) {
                if ($content instanceof EnumLabelInterface) {
                    $content = $content->label();
                } elseif ($content instanceof BackedEnum) {
                    $content = $content->value;
                } elseif ($content instanceof UnitEnum) {
                    $content = $content->name;
                } elseif ($content instanceof Closure) {
                    $content = $content();
                }

                $content = (string)$content;
            }

            $whiteSpace = str_repeat((string)$indentWith, $rowDepth);
            if ($indent && strpos($content, "\r\n", 1)) {
                $content = str_replace("\r\n", "\n" . $whiteSpace . $indentWith, $content);
            }

            // Prefix
            if (!$stack[$top]['opened']) {
                $stack[$top]['opened'] = true;
                if ($indent) {
                    $return .= "\r\n" . $whiteSpace;
                }
                if ($type) {
                    $typeAttributes = $this->attributes($type, ['data' => $elementData]);
                    $return .= '<' . $type . $typeAttributes . '>';
                }
            } elseif ($type && $stack[$top]['size'] > 0 && $stack[$top]['count'] % $stack[$top]['size'] === 0) {
                // The previous item is already closed at this point, so the lists stay balanced
                $return .= '</' . $type . '><' . $type . '>';
            }
            $stack[$top]['count']++;
            if ($indent) {
                $return .= "\r\n" . $whiteSpace . $indentWith;
            }
            if ($itemType) {
                $itemAttributes = $this->attributes($itemType, $elementData, true, true);
                $return .= '<' . $itemType . $itemAttributes . '>';
            }
            $return .= $element || $callback ? $content : h($content);

            // Suffix
            if ($hasVisibleChildren && $numberOfDirectChildren && !$collapse) {
                $childConfig = ['depth' => $rowDepth + 1] + $config;
                $return .= $this->generateSubTree(
                    $result['children'],
                    $childConfig,
                    $result,
                    $visibility[$i]['children'] ?? [],
                );
                if ($itemType) {
                    if ($indent) {
                        $return .= $whiteSpace . $indentWith;
                    }
                    $return .= '</' . $itemType . '>';
                }
            } elseif ($hasNestedRows) {
                if ($collapse || $rowDepth >= $maxDepth) {
                    if ($itemType) {
                        $return .= '</' . $itemType . '>';
                    }
                    $stack[] = ['right' => $row[$right]] + $skipped;
                } else {
                    $stack[] = [
                        'right' => $row[$right],
                        'rendered' => true,
                        'opened' => false,
                        'count' => 0,
                        'size' => $groupSize($rowDepth + 1, $groupCounts[$i] ?? 0),
                    ];
                }
            } elseif ($itemType) {
                $return .= '</' . $itemType . '>';
            }
        }

        // Cleanup
        // phpcs:ignore Squiz.PHP.DisallowSizeFunctionsInLoops.Found
        while (count($stack) > 1) {
            $closed = array_pop($stack);
            if (!$closed['rendered']) {
                continue;
            }
            if ($closed['opened']) {
                if ($indent) {
                    $whiteSpace = str_repeat((string)$indentWith, count($stack) - 1);
                    $return .= "\r\n" . $whiteSpace . $indentWith;
                }
                if ($type) {
                    $return .= '</' . $type . '>';
                }
            }
            if ($itemType) {
                $return .= '</' . $itemType . '>';
            }
        }

        if ($return && $indent) {
            $return .= "\r\n";
        }
        if ($return && $type) {
            if ($indent) {
                $return .= $whiteSpace;
            }
            $return .= '</' . $type . '>';
            if ($indent) {
                $return .= "\r\n";
            }
        }

        return $return;
    }

    /**
     * AddItemAttribute function
     *
     * Called to modify the attributes of the next <item> to be processed
     * Note that the content of a 'node' is processed before generating its wrapping <item> tag
     *
     * @param string $id Id
     * @param string $key Key
     * @param mixed $value Value
     * @return void
     */
    public function addItemAttribute(string $id = '', string $key = '', mixed $value = null): void
    {
        if ($value !== null) {
            $this->itemAttributes[$id][$key] = $value;
        } elseif (!(isset($this->itemAttributes[$id]) && in_array($key, $this->itemAttributes[$id], true))) {
            $this->itemAttributes[$id][] = $key;
        }
    }

    /**
     * AddTypeAttribute function
     *
     * Called to modify the attributes of the next <type> to be processed
     * Note that the content of a 'node' is processed before generating its wrapping <type> tag (if appropriate)
     * An 'interesting' case is that of a first child with children. To generate the output
     * <ul> (1)
     *      <li>XYZ (3)
     *              <ul> (2)
     *                      <li>ABC...
     *                      ...
     *              </ul>
     *              ...
     * The processing order is indicated by the numbers in brackets.
     * attributes are allways applied to the next type (2) to be generated
     * to set properties of the holding type - pass 'previous' for the 4th param
     * i.e.
     * // Hide children (2)
     * $tree->addTypeAttribute('style', 'display', 'hidden');
     * // give top level type (1) a class
     * $tree->addTypeAttribute('class', 'hasHiddenGrandChildren', null, 'previous');
     *
     * @param string $id ID
     * @param string $key Key
     * @param mixed|null $value Value
     * @param string $previousOrNext Previous or next
     * @return void
     */
    public function addTypeAttribute(
        string $id = '',
        string $key = '',
        mixed $value = null,
        string $previousOrNext = 'next',
    ): void {
        $var = 'typeAttributes';
        $firstChild = $this->currentConfig['firstChild'] ?? true;
        if ($previousOrNext === 'next' && $firstChild) {
            $var = 'typeAttributesNext';
        }
        if ($value !== null) {
            $this->{$var}[$id][$key] = $value;
        } elseif (!(isset($this->{$var}[$id]) && in_array($key, $this->{$var}[$id], true))) {
            $this->{$var}[$id][] = $key;
        }
    }

    /**
     * Attributes function.
     *
     * Logic to apply styles to tags.
     *
     * @param string $rType rType
     * @param array $elementData Element data
     * @param bool $clear Clear
     * @param bool $isItem is this an item or type call
     * @return string
     */
    protected function attributes(
        string $rType,
        array $elementData = [],
        bool $clear = true,
        bool $isItem = false,
    ): string {
        $type = $this->currentConfig['type'];
        $itemType = $this->currentConfig['itemType'];
        $autoPath = $this->currentConfig['autoPath'];
        if ($rType === $type && !$isItem) {
            $attributes = $this->typeAttributes;
            if ($clear) {
                $this->typeAttributes = $this->typeAttributesNext;
                $this->typeAttributesNext = [];
            }
        } else {
            $attributes = $this->itemAttributes;
            $this->itemAttributes = [];
            if ($clear) {
                $this->itemAttributes = [];
            }
        }
        if ($rType === $itemType && !empty($elementData['activePathElement'])) {
            $attributes['class'][] = $elementData['activePathElement'] === true
                ? $autoPath[2]
                : $elementData['activePathElement'];
        }
        if (!$attributes) {
            return '';
        }
        foreach ($attributes as $type => $values) {
            foreach ($values as $key => $val) {
                if (is_array($val)) {
                    $parts = [];
                    foreach ($val as $vKey => $v) {
                        $parts[] = $vKey . ':' . $v;
                    }
                    $val = implode(';', $parts);
                    $attributes[$type][$key] = $val;
                }
                if (is_string($key)) {
                    $attributes[$type][$key] = $key . ':' . $val . ';';
                }
            }
            $attributes[$type] = $type . '="' . h(implode(' ', $attributes[$type])) . '"';
        }

        return ' ' . implode(' ', $attributes);
    }

    /**
     * Whether the nodes are a flat MPTT result instead of nested `children`.
     *
     * @param array $nodes Nodes
     * @return bool
     */
    protected function isFlat(array $nodes): bool
    {
        $first = $nodes[0] ?? null;

        return $first !== null && !isset($first['children']) && isset($first[$this->currentConfig['left']]);
    }

    /**
     * Find the parent (`-1` for roots) and level of each row of a flat MPTT result.
     *
     * @param array $nodes Nodes
     * @return array{parents: array<int, int>, levels: array<int, int>}
     */
    protected function flatParents(array $nodes): array
    {
        $right = $this->currentConfig['right'];
        $levelField = $this->currentConfig['level'];
        $baseLevel = $levelField ? (int)($nodes[0][$levelField] ?? 0) : 0;
        $parents = [];
        $levels = [];
        $ancestors = [];
        foreach ($nodes as $i => $node) {
            // phpcs:ignore Squiz.PHP.DisallowSizeFunctionsInLoops.Found
            while ($ancestors && $ancestors[count($ancestors) - 1][1] < $node[$right]) {
                array_pop($ancestors);
            }
            $parents[$i] = $ancestors ? $ancestors[count($ancestors) - 1][0] : -1;
            $levels[$i] = $levelField && isset($node[$levelField])
                ? (int)$node[$levelField] - $baseLevel
                : count($ancestors);
            if (isset($nodes[$i + 1]) && $nodes[$i + 1][$right] < $node[$right]) {
                $ancestors[] = [$i, $node[$right]];
            }
        }

        return ['parents' => $parents, 'levels' => $levels];
    }

    /**
     * Calculate which unrelated nodes are hidden when `hideUnrelated` is enabled.
     *
     * This leaves the nodes untouched, the result is only used to skip nodes while rendering.
     * It is keyed like `$nodes` and, for threaded data, nested through `children`.
     *
     * @param array $nodes Nodes
     * @param bool $flat Whether the nodes are a flat MPTT result
     * @return array<int, array{show: bool, hide: bool, children?: array}>
     */
    protected function calculateVisibility(array $nodes, bool $flat): array
    {
        if ($flat) {
            return $this->flatVisibility($nodes);
        }

        return $this->threadedVisibility($nodes, 0, false);
    }

    /**
     * @param array $nodes Nodes
     * @param int $level Level
     * @param bool $parentShown Whether the parent is part of the tree path
     * @return array<int, array{show: bool, hide: bool, children: array}>
     */
    protected function threadedVisibility(array $nodes, int $level, bool $parentShown): array
    {
        $hideUnrelated = $this->currentConfig['hideUnrelated'];
        $path = $this->currentConfig['treePath'];
        $nodeKey = $this->currentConfig['nodeKey'];

        $siblingIsActive = is_numeric($hideUnrelated) && $hideUnrelated > $level;
        $shown = [];
        foreach ($nodes as $i => $node) {
            if (!isset($node['children'])) {
                throw new RuntimeException('Invalid Tree Structure: ' . print_r($node, true));
            }
            $shown[$i] = !empty($path[$level]) && ($node[$nodeKey] ?? null) == $path[$level];
            $siblingIsActive = $siblingIsActive || $shown[$i];
        }

        $visibility = [];
        foreach ($nodes as $i => $node) {
            $hide = $level > 0 && !$siblingIsActive && !$parentShown;
            $visibility[$i] = [
                'show' => $shown[$i],
                'hide' => $hide,
                'children' => $hide ? [] : $this->threadedVisibility($node['children'], $level + 1, $shown[$i]),
            ];
        }

        return $visibility;
    }

    /**
     * @param array $nodes Nodes
     * @return array<int, array{show: bool, hide: bool}>
     */
    protected function flatVisibility(array $nodes): array
    {
        $hideUnrelated = $this->currentConfig['hideUnrelated'];
        $path = $this->currentConfig['treePath'];
        $nodeKey = $this->currentConfig['nodeKey'];
        ['parents' => $parents, 'levels' => $levels] = $this->flatParents($nodes);

        $shown = [];
        $activeParents = [];
        foreach ($nodes as $i => $node) {
            $shown[$i] = !empty($path[$levels[$i]]) && ($node[$nodeKey] ?? null) == $path[$levels[$i]];
            if ($shown[$i]) {
                $activeParents[$parents[$i]] = true;
            }
        }

        $visibility = [];
        foreach ($nodes as $i => $node) {
            $level = $levels[$i];
            $siblingIsActive = (is_numeric($hideUnrelated) && $hideUnrelated > $level)
                || isset($activeParents[$parents[$i]]);
            $parentShown = $parents[$i] >= 0 && $shown[$parents[$i]];
            $visibility[$i] = [
                'show' => $shown[$i],
                'hide' => $level > 0 && !$siblingIsActive && !$parentShown,
            ];
        }

        return $visibility;
    }
}
