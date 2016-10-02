<?php
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         3.3.6
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
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
     * Other helpers used by BreadcrumbsHelper
     *
     * @var array
     */
    public $helpers = ['Url'];

    /**
     * Default config for the helper.
     *
     * @var array
     */
    protected $_defaultConfig = [
        'templates' => [
            'wrapper' => '<ul{{attrs}}>{{content}}</ul>',
            'item' => '<li{{attrs}}><a href="{{link}}"{{innerAttrs}}>{{title}}</a></li>',
            'itemWithoutLink' => '<li{{attrs}}><span{{innerAttrs}}>{{title}}</span></li>',
            'separator' => '<li{{attrs}}><span{{innerAttrs}}>{{separator}}</span></li>'
        ]
    ];

    /**
     * The crumbs list.
     *
     * @var array
     */
    protected $crumbs = [];

    /**
     * Add a crumb to the trail.
     *
     * @param string $title Title of the crumb
     * @param string|array|null $link Link of the crumb. Either a string, an array of route params to pass to
     * Url::build() or null / empty if the crumb does not have a link
     * @param array $options Array of options
     * @return $this
     */
    public function add($title, $link = null, array $options = [])
    {
        $this->crumbs[] = ['title' => $title, 'link' => $link, 'options' => $options];

        return $this;
    }

    /**
     * Prepend a crumb to the start of the queue.
     *
     * @param string $title Title of the crumb
     * @param string|array|null $link Link of the crumb. Either a string, an array of route params to pass to
     * Url::build() or null / empty if the crumb does not have a link
     * @param array $options Array of options
     * @return $this
     */
    public function prepend($title, $link = null, array $options = [])
    {
        array_unshift($this->crumbs, ['title' => $title, 'link' => $link, 'options' => $options]);

        return $this;
    }

    /**
     * Insert a crumb at a specific index.
     *
     * If the index already exists, the new crumb will be inserted,
     * and the existing element will be shifted one index greater.
     *
     * @param int $index The index to insert at.
     * @param string $title Title of the crumb
     * @param string|array|null $link Link of the crumb. Either a string, an array of route params to pass to
     * Url::build() or null / empty if the crumb does not have a link
     * @param array $options Array of options
     * @return $this
     */
    public function insertAt($index, $title, $link = null, array $options = [])
    {
        array_splice($this->crumbs, $index, 0, [['title' => $title, 'link' => $link, 'options' => $options]]);

        return $this;
    }

    /**
     * Insert a crumb before the first matching crumb with the specified title.
     *
     * Finds the index of the first middleware that matches the provided class,
     * and inserts the supplied callable before it.
     *
     * @param string $matchingTitle The title of the crumb you want to insert this one before
     * @param string $title Title of the crumb
     * @param string|array|null $link Link of the crumb. Either a string, an array of route params to pass to
     * Url::build() or null / empty if the crumb does not have a link
     * @param array $options Array of options
     * @return $this
     */
    public function insertBefore($matchingTitle, $title, $link = null, array $options = [])
    {
        $found = false;
        foreach ($this->crumbs as $key => $crumb) {
            if ($crumb['title'] === $matchingTitle) {
                $found = true;
                break;
            }
        }

        if ($found) {
            return $this->insertAt($key, $title, $link, $options);
        }
        throw new LogicException(sprintf("No crumb matching '%s' could be found.", $matchingTitle));
    }

    /**
     * Insert a crumb after the first matching crumb with the specified title.
     *
     * Finds the index of the first middleware that matches the provided class,
     * and inserts the supplied callable before it.
     *
     * @param string $matchingTitle The title of the crumb you want to insert this one after
     * @param string $title Title of the crumb
     * @param string|array|null $link Link of the crumb. Either a string, an array of route params to pass to
     * Url::build() or null / empty if the crumb does not have a link
     * @param array $options Array of options
     * @return $this
     */
    public function insertAfter($matchingTitle, $title, $link = null, array $options = [])
    {
        $found = false;
        foreach ($this->crumbs as $key => $crumb) {
            if ($crumb['title'] === $matchingTitle) {
                $found = true;
                break;
            }
        }

        if ($found) {
            return $this->insertAt($key + 1, $title, $link, $options);
        }
        throw new LogicException(sprintf("No crumb matching '%s' could be found.", $matchingTitle));
    }

    /**
     * Returns the crumbs list
     *
     * @return array
     */
    public function getCrumbs()
    {
       return $this->crumbs;
    }

    /**
     * Renders the breadcrumbs trail
     *
     * @param array $attributes Array of attributes applied to the wrapper element
     * @param array $separator Array of attributes for the `separator` template
     * @return string The breadcrumbs trail
     */
    public function render(array $attributes = [], array $separator = [])
    {
        $crumbs = $this->crumbs;
        $crumbsCount = count($crumbs);
        $templater = $this->templater();

        if (!empty($separator)) {
            $separatorParams = [];
            if (isset($separator['innerAttrs'])) {
                $separatorParams['innerAttrs'] = $templater->formatAttributes($separator['innerAttrs']);
                unset($separator['innerAttrs']);
            }

            if (isset($separator['separator'])) {
                $separatorParams['separator'] = $separator['separator'];
                unset($separator['separator']);
            }

            $separatorParams['attrs'] = $templater->formatAttributes($separator);
        }

        $crumbTrail = '';
        foreach ($crumbs as $key => $crumb) {
            $link = $this->prepareLink($crumb['link']);
            $title = $crumb['title'];
            $options = $crumb['options'];

            $optionsLink = [];
            if (isset($options['innerAttrs'])) {
                $optionsLink = $options['innerAttrs'];
                unset($options['innerAttrs']);
            }

            $template = 'item';
            $templateParams = [
                'attrs' => $templater->formatAttributes($options),
                'innerAttrs' => $templater->formatAttributes($optionsLink),
                'title' => $title,
                'link' => $link,
            ];

            if (empty($link)) {
                $template = 'itemWithoutLink';
            }

            $crumbTrail .= $this->formatTemplate($template, $templateParams);

            if (isset($separatorParams) && $key !== ($crumbsCount-1)) {
                $crumbTrail .= $this->formatTemplate('separator', $separatorParams);
            }
        }

        $crumbTrail = $this->formatTemplate('wrapper', [
            'content' => $crumbTrail,
            'attrs' => $templater->formatAttributes($attributes)
        ]);

        return $crumbTrail;
    }

    /**
     * Prepare the URL for a specific `link` param of a crumb
     *
     * @param array|string|null $link If array, an array of Router url params
     * If string, will be used as is
     * If empty, will consider that there is no link
     *
     * @return null|string The URL of a crumb
     */
    protected function prepareLink($link)
    {
        if (is_string($link)) {
            return $link;
        }

        if (is_array($link)) {
            return $this->Url->build($link);
        }

        return null;
    }
}
