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
    protected array $_defaultConfig = [
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
     *    'hideUnrelated' => if unrelated (not children, not siblings) should be hidden, needs 'treePath', true/false or array/string for callback
     *    'treePath' => treePath to insert into callback/element
     *    'left' => name of the 'lft' field if not lft. only applies to MPTT data
     *    'right' => name of the 'rght' field if not rght. only applies to MPTT data
     *    'depth' => used internally when running recursively, can be used to override the depth in either mode.
     *    'maxDepth' => used to control the depth upto which to generate tree
     *    'firstChild' => used internally when running recursively.
     *    'splitDepth' => if multiple "parallel" types are required, instead of one big type, nominate the depth to do so here
     *        example: useful if you have 30 items to display, and you'd prefer they appeared in the source as 3 lists of 10 to be able to
     *        style/float them.
     *    'splitCount' => the number of "parallel" types. defaults to null (disabled) set the splitCount,
     *        and optionally set the splitDepth to get parallel lists
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
     * @throws \Exception
     * @return string
     */
    protected function generateSubTree(
        iterable $data,
        array $config,
        EntityInterface|iterable|null $parent = null,
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
        $stack = [];
        if ($depth == 0) {
            if ($class) {
                $this->addTypeAttribute('class', $class, null, 'previous');
            }
            if ($id) {
                $this->addTypeAttribute('id', $id, null, 'previous');
            }
        }
        $return = '';
        $addType = true;
        $this->currentConfig['totalNodes'] = count($dataArray);
        $keys = array_keys($dataArray);

        if ($hideUnrelated === true || is_numeric($hideUnrelated)) {
            $this->markUnrelatedAsHidden($dataArray, $treePath);
        } elseif ($hideUnrelated && is_callable($hideUnrelated)) {
            $hideUnrelated($dataArray, $treePath);
        }

        $whiteSpace = '';
        foreach ($dataArray as $i => $result) {
            $row = $result;

            // Close open items as appropriate
            // phpcs:ignore Squiz.PHP.DisallowSizeFunctionsInLoops.Found
            while ($stack && ($stack[count($stack) - 1] < $row[$right])) {
                array_pop($stack);
                if ($indent) {
                    $whiteSpace = str_repeat((string)$indentWith, count($stack));
                    $return .= "\r\n" . $whiteSpace . $indentWith;
                }
                if ($type) {
                    $return .= '</' . $type . '>';
                }
                if ($itemType) {
                    $return .= '</' . $itemType . '>';
                }
            }
            $hasChildren = false;
            $firstChild = false;
            $lastChild = false;
            $hasVisibleChildren = false;
            $numberOfDirectChildren = null;
            $numberOfTotalChildren = null;

            if (isset($result['children'])) {
                if ($result['children'] && $depth < $maxDepth) {
                    $hasChildren = true;
                    $hasVisibleChildren = true;
                    $numberOfDirectChildren = count($result['children']);
                }

                $firstChild = $i === $keys[0];
                $lastChild = $i === $keys[count($keys) - 1];
            } elseif (isset($row[$left])) {
                if ($row[$left] != ($row[$right] - 1) && $depth < $maxDepth) {
                    $hasChildren = true;
                    $numberOfTotalChildren = ($row[$right] - $row[$left] - 1) / 2;
                    if (isset($dataArray[$i + 1]) && $dataArray[$i + 1][$right] < $row[$right]) {
                        $hasVisibleChildren = true;
                    }
                }
                if (!isset($dataArray[$i - 1]) || ($dataArray[$i - 1][$left] == $row[$left] - 1)) {
                    $firstChild = true;
                }
                if (!isset($dataArray[$i + 1]) || ($stack && $stack[count($stack) - 1] == $row[$right] + 1)) {
                    $lastChild = true;
                }
            } else {
                throw new RuntimeException('Invalid Tree Structure: ' . print_r($result, true));
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

            $depth = $depth ?: count($stack);

            $elementData = [
                'data' => $result,
                'parent' => $parent,
                'depth' => $depth,
                'hasChildren' => $hasChildren,
                'numberOfDirectChildren' => $numberOfDirectChildren,
                'numberOfTotalChildren' => $numberOfTotalChildren,
                'firstChild' => $firstChild,
                'lastChild' => $lastChild,
                'hasVisibleChildren' => $hasVisibleChildren,
                'activePathElement' => $activePathElement,
                'isSibling' => $depth == 0 && !$activePathElement,
            ];

            if ($elementData['isSibling'] && $hideUnrelated) {
                $result['children'] = [];
            }

            $this->currentConfig = $elementData + $this->currentConfig;
            if ($this->currentConfig['fullSettings']) {
                $elementData = $this->currentConfig;
            }

            // Main Content
            if ($element) {
                $content = $this->_View->element($element, $elementData);
            } elseif ($callback) {
                [$content] = array_map($callback, [$elementData]);
            } else {
                $content = $row[$alias];
            }

            if (in_array($content, [null, false, ''], true)) {
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

            $whiteSpace = str_repeat((string)$indentWith, $depth);
            if ($indent && strpos($content, "\r\n", 1)) {
                $content = str_replace("\r\n", "\n" . $whiteSpace . $indentWith, $content);
            }

            // Prefix
            if ($addType) {
                if ($indent) {
                    $return .= "\r\n" . $whiteSpace;
                }
                if ($type) {
                    $typeAttributes = $this->attributes($type, ['data' => $elementData]);
                    $return .= '<' . $type . $typeAttributes . '>';
                }
            }
            if ($indent) {
                $return .= "\r\n" . $whiteSpace . $indentWith;
            }
            if ($itemType) {
                $itemAttributes = $this->attributes($itemType, $elementData, true, true);
                $return .= '<' . $itemType . $itemAttributes . '>';
            }
            $return .= $element || $callback ? $content : h($content);

            // Suffix
            $addType = false;
            if ($hasVisibleChildren) {
                if ($numberOfDirectChildren) {
                    $config['depth'] = $depth + 1;
                    $children = $result['children'];

                    $return .= $this->suffix();
                    $return .= $this->generateSubTree($children, $config, $result);
                    if ($itemType) {
                        if ($indent) {
                            $return .= $whiteSpace . $indentWith;
                        }
                        $return .= '</' . $itemType . '>';
                    }
                } elseif ($numberOfTotalChildren) {
                    $addType = true;
                    $stack[] = $row[$right];
                }
            } else {
                if ($itemType) {
                    $return .= '</' . $itemType . '>';
                }
                $return .= $this->suffix();
            }
        }

        // Cleanup
        while ($stack) {
            array_pop($stack);
            if ($indent) {
                $whiteSpace = str_repeat((string)$indentWith, count($stack));
                $return .= "\r\n" . $whiteSpace . $indentWith;
            }
            if ($type) {
                $return .= '</' . $type . '>';
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
        } elseif (!(isset($this->itemAttributes[$id]) && in_array($key, $this->itemAttributes[$id]))) {
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
        } elseif (!(isset($this->{$var}[$id]) && in_array($key, $this->{$var}[$id]))) {
            $this->{$var}[$id][] = $key;
        }
    }

    /**
     * Suffix method.
     *
     * Used to close and reopen a ul/ol to allow easier listings
     *
     * @param bool $reset Reset
     * @return string
     */
    protected function suffix(bool $reset = false): string
    {
        static $splitCounter = 0;

        if ($reset) {
            $splitCounter = 0;
        }
        $splitCount = $this->currentConfig['splitCount'];
        $splitDepth = $this->currentConfig['splitDepth'];
        $totalNodes = $this->currentConfig['totalNodes'];
        $depth = $this->currentConfig['depth'];
        $type = $this->currentConfig['type'];
        $lastChild = $this->currentConfig['lastChild'];
        $numberOfDirectChildren = $this->currentConfig['numberOfDirectChildren'];
        $numberOfTotalChildren = $this->currentConfig['numberOfTotalChildren'];
        if ($splitDepth || $splitCount) {
            if (!$splitDepth) {
                $splitCount = $totalNodes / $splitCount;
                $rounded = (int)$splitCount;
                if ($rounded < $splitCount) {
                    $splitCount = $rounded + 1;
                }
            } elseif ($depth == $splitDepth - 1) {
                $total = $numberOfDirectChildren ?: $numberOfTotalChildren;
                if ($total) {
                    $splitCounter = 0;
                    $splitCount = $total / $splitCount;
                    $rounded = (int)$splitCount;
                    if ($rounded < $splitCount) {
                        $splitCount = $rounded + 1;
                    }
                }
            }
            if (!$splitDepth || $depth == $splitDepth) {
                $splitCounter++;
                if ($type && ($splitCounter % $splitCount) === 0 && !$lastChild) {
                    unset($this->currentConfig['callback']);

                    return '</' . $type . '><' . $type . '>';
                }
            }
        }

        return '';
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
     * Mark unrelated records as hidden using `'hide' => 1`.
     * In the callback or element you can then return early in this case.
     *
     * @param array $tree Tree
     * @param array $path Tree path
     * @param int $level Level
     * @throws \Exception
     * @return void
     */
    protected function markUnrelatedAsHidden(array &$tree, array $path, int $level = 0): void
    {
        $hideUnrelated = $this->currentConfig['hideUnrelated'];
        $siblingIsActive = false;
        foreach ($tree as &$subTree) {
            if (is_object($subTree) && method_exists($subTree, 'toArray')) {
                $subTree = $subTree->toArray();
            }
            if (!isset($subTree['children'])) {
                throw new RuntimeException(
                    'Only works with threaded (nested children) results: '
                        . print_r($subTree, true),
                );
            }

            if (!empty($path[$level]) && $subTree['id'] == $path[$level]) {
                $subTree['show'] = 1;
                $siblingIsActive = true;
            }
            if (!empty($subTree['show'])) {
                foreach ($subTree['children'] as &$v) {
                    $v['parent_show'] = 1;
                }
            }
            if (is_numeric($hideUnrelated) && $hideUnrelated > $level) {
                $siblingIsActive = true;
            }
        }
        foreach ($tree as &$subTree) {
            if ($level && !$siblingIsActive && !isset($subTree['parent_show'])) {
                $subTree['hide'] = 1;
            }
            $this->markUnrelatedAsHidden($subTree['children'], $path, $level + 1);
        }
    }
}
