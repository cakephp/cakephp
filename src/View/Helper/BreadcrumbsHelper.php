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
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         3.3.6
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\View\Helper;

use Cake\View\Helper;
use Cake\View\StringTemplateTrait;
use LogicException;

/**
 * BreadcrumbsHelper to register and display a breadcrumb trail for your views
 *
 * @property \Cake\View\Helper\UrlHelper $Url
 */
class BreadcrumbsHelper extends Helper
{
    use StringTemplateTrait;

    /**
     * Other helpers used by BreadcrumbsHelper.
     *
     * @var array
     */
    protected $helpers = ['Url'];

    /**
     * Default config for the helper.
     *
     * @var array
     */
    protected $_defaultConfig = [
        'templates' => [
            'wrapper' => '<ul{{attrs}}>{{content}}</ul>',
            'item' => '<li{{attrs}}><a href="{{url}}"{{innerAttrs}}>{{title}}</a></li>{{separator}}',
            'itemWithoutLink' => '<li{{attrs}}><span{{innerAttrs}}>{{title}}</span></li>{{separator}}',
            'separator' => '<li{{attrs}}><span{{innerAttrs}}>{{separator}}</span></li>',
        ],
    ];

    /**
     * The crumb list.
     *
     * @var array
     */
    protected $crumbs = [];

    /**
     * Add a crumb to the end of the trail.
     *
     * @param string|array $title If provided as a string, it represents the title of the crumb.
     * Alternatively, if you want to add multiple crumbs at once, you can provide an array, with each values being a
     * single crumb. Arrays are expected to be of this form:
     *
     * - *title* The title of the crumb
     * - *link* The link of the crumb. If not provided, no link will be made
     * - *options* Options of the crumb. See description of params option of this method.
     *
     * @param string|array|null $url URL of the crumb. Either a string, an array of route params to pass to
     * Url::build() or null / empty if the crumb does not have a link.
     * @param array $options Array of options. These options will be used as attributes HTML attribute the crumb will
     * be rendered in (a <li> tag by default). It accepts two special keys:
     *
     * - *innerAttrs*: An array that allows you to define attributes for the inner element of the crumb (by default, to
     *   the link)
     * - *templateVars*: Specific template vars in case you override the templates provided.
     * @return $this
     */
    public function add($title, $url = null, array $options = [])
    {
        if (is_array($title)) {
            foreach ($title as $crumb) {
                $this->crumbs[] = $crumb + ['title' => '', 'url' => null, 'options' => []];
            }

            return $this;
        }

        $this->crumbs[] = compact('title', 'url', 'options');

        return $this;
    }

    /**
     * Prepend a crumb to the start of the queue.
     *
     * @param string|array $title If provided as a string, it represents the title of the crumb.
     * Alternatively, if you want to add multiple crumbs at once, you can provide an array, with each values being a
     * single crumb. Arrays are expected to be of this form:
     *
     * - *title* The title of the crumb
     * - *link* The link of the crumb. If not provided, no link will be made
     * - *options* Options of the crumb. See description of params option of this method.
     *
     * @param string|array|null $url URL of the crumb. Either a string, an array of route params to pass to
     * Url::build() or null / empty if the crumb does not have a link.
     * @param array $options Array of options. These options will be used as attributes HTML attribute the crumb will
     * be rendered in (a <li> tag by default). It accepts two special keys:
     *
     * - *innerAttrs*: An array that allows you to define attributes for the inner element of the crumb (by default, to
     *   the link)
     * - *templateVars*: Specific template vars in case you override the templates provided.
     * @return $this
     */
    public function prepend($title, $url = null, array $options = [])
    {
        if (is_array($title)) {
            $crumbs = [];
            foreach ($title as $crumb) {
                $crumbs[] = $crumb + ['title' => '', 'url' => null, 'options' => []];
            }

            array_splice($this->crumbs, 0, 0, $crumbs);

            return $this;
        }

        array_unshift($this->crumbs, compact('title', 'url', 'options'));

        return $this;
    }

    /**
     * Insert a crumb at a specific index.
     *
     * If the index already exists, the new crumb will be inserted,
     * and the existing element will be shifted one index greater.
     * If the index is out of bounds, it will throw an exception.
     *
     * @param int $index The index to insert at.
     * @param string $title Title of the crumb.
     * @param string|array|null $url URL of the crumb. Either a string, an array of route params to pass to
     * Url::build() or null / empty if the crumb does not have a link.
     * @param array $options Array of options. These options will be used as attributes HTML attribute the crumb will
     * be rendered in (a <li> tag by default). It accepts two special keys:
     *
     * - *innerAttrs*: An array that allows you to define attributes for the inner element of the crumb (by default, to
     *   the link)
     * - *templateVars*: Specific template vars in case you override the templates provided.
     * @return $this
     * @throws \LogicException In case the index is out of bound
     */
    public function insertAt(int $index, string $title, $url = null, array $options = [])
    {
        if (!isset($this->crumbs[$index])) {
            throw new LogicException(sprintf("No crumb could be found at index '%s'", $index));
        }

        array_splice($this->crumbs, $index, 0, [compact('title', 'url', 'options')]);

        return $this;
    }

    /**
     * Insert a crumb before the first matching crumb with the specified title.
     *
     * Finds the index of the first crumb that matches the provided class,
     * and inserts the supplied callable before it.
     *
     * @param string $matchingTitle The title of the crumb you want to insert this one before.
     * @param string $title Title of the crumb.
     * @param string|array|null $url URL of the crumb. Either a string, an array of route params to pass to
     * Url::build() or null / empty if the crumb does not have a link.
     * @param array $options Array of options. These options will be used as attributes HTML attribute the crumb will
     * be rendered in (a <li> tag by default). It accepts two special keys:
     *
     * - *innerAttrs*: An array that allows you to define attributes for the inner element of the crumb (by default, to
     *   the link)
     * - *templateVars*: Specific template vars in case you override the templates provided.
     * @return $this
     * @throws \LogicException In case the matching crumb can not be found
     */
    public function insertBefore(string $matchingTitle, string $title, $url = null, array $options = [])
    {
        $key = $this->findCrumb($matchingTitle);

        if ($key === null) {
            throw new LogicException(sprintf("No crumb matching '%s' could be found.", $matchingTitle));
        }

        return $this->insertAt($key, $title, $url, $options);
    }

    /**
     * Insert a crumb after the first matching crumb with the specified title.
     *
     * Finds the index of the first crumb that matches the provided class,
     * and inserts the supplied callable before it.
     *
     * @param string $matchingTitle The title of the crumb you want to insert this one after.
     * @param string $title Title of the crumb.
     * @param string|array|null $url URL of the crumb. Either a string, an array of route params to pass to
     * Url::build() or null / empty if the crumb does not have a link.
     * @param array $options Array of options. These options will be used as attributes HTML attribute the crumb will
     * be rendered in (a <li> tag by default). It accepts two special keys:
     *
     * - *innerAttrs*: An array that allows you to define attributes for the inner element of the crumb (by default, to
     *   the link)
     * - *templateVars*: Specific template vars in case you override the templates provided.
     * @return $this
     * @throws \LogicException In case the matching crumb can not be found.
     */
    public function insertAfter(string $matchingTitle, string $title, $url = null, array $options = [])
    {
        $key = $this->findCrumb($matchingTitle);

        if ($key === null) {
            throw new LogicException(sprintf("No crumb matching '%s' could be found.", $matchingTitle));
        }

        return $this->insertAt($key + 1, $title, $url, $options);
    }

    /**
     * Returns the crumb list.
     *
     * @return array
     */
    public function getCrumbs(): array
    {
        return $this->crumbs;
    }

    /**
     * Removes all existing crumbs.
     *
     * @return $this
     */
    public function reset()
    {
        $this->crumbs = [];

        return $this;
    }

    /**
     * Renders the breadcrumbs trail.
     *
     * @param array $attributes Array of attributes applied to the `wrapper` template. Accepts the `templateVars` key to
     * allow the insertion of custom template variable in the template.
     * @param array $separator Array of attributes for the `separator` template.
     * Possible properties are :
     *
     * - *separator* The string to be displayed as a separator
     * - *templateVars* Allows the insertion of custom template variable in the template
     * - *innerAttrs* To provide attributes in case your separator is divided in two elements.
     *
     * All other properties will be converted as HTML attributes and will replace the *attrs* key in the template.
     * If you use the default for this option (empty), it will not render a separator.
     * @return string The breadcrumbs trail
     */
    public function render(array $attributes = [], array $separator = []): string
    {
        if (!$this->crumbs) {
            return '';
        }

        $crumbs = $this->crumbs;
        $crumbsCount = count($crumbs);
        $templater = $this->templater();
        $separatorString = '';

        if ($separator) {
            if (isset($separator['innerAttrs'])) {
                $separator['innerAttrs'] = $templater->formatAttributes($separator['innerAttrs']);
            }

            $separator['attrs'] = $templater->formatAttributes(
                $separator,
                ['innerAttrs', 'separator']
            );

            $separatorString = $this->formatTemplate('separator', $separator);
        }

        $crumbTrail = '';
        foreach ($crumbs as $key => $crumb) {
            $url = $crumb['url'] ? $this->Url->build($crumb['url']) : null;
            $title = $crumb['title'];
            $options = $crumb['options'];

            $optionsLink = [];
            if (isset($options['innerAttrs'])) {
                $optionsLink = $options['innerAttrs'];
                unset($options['innerAttrs']);
            }

            $template = 'item';
            $templateParams = [
                'attrs' => $templater->formatAttributes($options, ['templateVars']),
                'innerAttrs' => $templater->formatAttributes($optionsLink),
                'title' => $title,
                'url' => $url,
                'separator' => '',
                'templateVars' => $options['templateVars'] ?? [],
            ];

            if (!$url) {
                $template = 'itemWithoutLink';
            }

            if ($separatorString && $key !== $crumbsCount - 1) {
                $templateParams['separator'] = $separatorString;
            }

            $crumbTrail .= $this->formatTemplate($template, $templateParams);
        }

        $crumbTrail = $this->formatTemplate('wrapper', [
            'content' => $crumbTrail,
            'attrs' => $templater->formatAttributes($attributes, ['templateVars']),
            'templateVars' => $attributes['templateVars'] ?? [],
        ]);

        return $crumbTrail;
    }

    /**
     * Search a crumb in the current stack which title matches the one provided as argument.
     * If found, the index of the matching crumb will be returned.
     *
     * @param string $title Title to find.
     * @return int|null Index of the crumb found, or null if it can not be found.
     */
    protected function findCrumb(string $title): ?int
    {
        foreach ($this->crumbs as $key => $crumb) {
            if ($crumb['title'] === $title) {
                return $key;
            }
        }

        return null;
    }
}
