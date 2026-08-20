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
 * @extends \Cake\View\Helper<\Cake\View\View>
 */
class BreadcrumbsHelper extends Helper
{
    use StringTemplateTrait;

    /**
     * Other helpers used by BreadcrumbsHelper.
     *
     * @var array<int|string, string|array<string, mixed>>
     */
    protected array $helpers = ['Url'];

    /**
     * Default config for the helper.
     *
     * @var array<string, mixed>
     */
    protected array $defaultConfig = [
        'templates' => [
            'wrapper' => '<ul{{attrs}}>{{content}}</ul>',
            'item' => '<li{{attrs}}><a href="{{url}}"{{innerAttrs}}>{{content}}</a></li>{{separator}}',
            'itemWithoutLink' => '<li{{attrs}}><span{{innerAttrs}}>{{content}}</span></li>{{separator}}',
            'separator' => '<li{{attrs}}><span{{innerAttrs}}>{{separator}}</span></li>',
        ],
    ];

    /**
     * The crumb list.
     *
     * @var array
     */
    protected array $crumbs = [];

    /**
     * Add a crumb to the end of the trail.
     *
     * @param string $content Represents the content of the crumb.
     * @param array|string|null $url URL of the crumb. Either a string, an array of route params to pass to
     * Url::build() or null / empty if the crumb does not have a link.
     * @param array<string, mixed> $options Array of options. These options will be used as HTML attributes the crumb will
     * be rendered in (a <li> tag by default). It accepts two special keys:
     * - *innerAttrs*: An array that allows you to define attributes for the inner element of the crumb (by default, to
     *   the link)
     * - *templateVars*: Specific template vars in case you override the templates provided.
     * @return $this
     */
    public function add(string $content, array|string|null $url = null, array $options = []): static
    {
        $this->crumbs[] = compact('content', 'url', 'options');

        return $this;
    }

    /**
     * Add multiple crumbs to the end of the trail.
     *
     * @param array<array{content?: string, url?: array|string|null, options?: array<string, mixed>}> $crumbs Array of crumbs to add.
     * Arrays are expected to be of this form:
     * - *content* The content of the crumb
     * - *link* The link of the crumb. If not provided, no link will be made
     * - *options* Options of the crumb. See description of params option of this method.
     * @param array<string, mixed> $options Shared options for all crumbs. These options will be used as defaults
     * for each crumb, with individual crumb options taking precedence. These options will be used as attributes
     * HTML attribute the crumb will be rendered in (a <li> tag by default). It accepts two special keys:
     *
     * - *innerAttrs*: An array that allows you to define attributes for the inner element of the crumb (by default, to
     *   the link)
     * - *templateVars*: Specific template vars in case you override the templates provided.
     * @return $this
     */
    public function addMany(array $crumbs, array $options = []): static
    {
        foreach ($crumbs as $crumb) {
            $crumb += ['content' => '', 'url' => null, 'options' => []];
            $crumb['options'] += $options;
            $this->crumbs[] = $crumb;
        }

        return $this;
    }

    /**
     * Prepend a crumb to the start of the queue.
     *
     * @param string $content Represents the content of the crumb.
     * @param array|string|null $url URL of the crumb. Either a string, an array of route params to pass to
     * Url::build() or null / empty if the crumb does not have a link.
     * @param array<string, mixed> $options Array of options. These options will be used as HTML attributes the crumb will
     * be rendered in (a <li> tag by default). It accepts two special keys:
     *
     * - *innerAttrs*: An array that allows you to define attributes for the inner element of the crumb (by default, to
     *   the link)
     * - *templateVars*: Specific template vars in case you override the templates provided.
     * @return $this
     */
    public function prepend(string $content, array|string|null $url = null, array $options = []): static
    {
        array_unshift($this->crumbs, compact('content', 'url', 'options'));

        return $this;
    }

    /**
     * Prepend multiple crumbs to the start of the queue.
     *
     * @param array<array{content?: string, url?: array|string|null, options?: array<string, mixed>}> $crumbs Array of crumbs to prepend.
     * Arrays are expected to be of this form:
     * - *content* The content of the crumb
     * - *link* The link of the crumb. If not provided, no link will be made
     * - *options* Options of the crumb. See description of params option of this method.
     * @param array<string, mixed> $options Shared options for all crumbs. These options will be used as defaults
     * for each crumb, with individual crumb options taking precedence. These options will be used as attributes
     * HTML attribute the crumb will be rendered in (a <li> tag by default). It accepts two special keys:
     *
     * - *innerAttrs*: An array that allows you to define attributes for the inner element of the crumb (by default, to
     *   the link)
     * - *templateVars*: Specific template vars in case you override the templates provided.
     * @return $this
     */
    public function prependMany(array $crumbs, array $options = []): static
    {
        $prepend = [];
        foreach ($crumbs as $crumb) {
            $crumb += ['content' => '', 'url' => null, 'options' => []];
            $crumb['options'] += $options;
            $prepend[] = $crumb;
        }

        array_splice($this->crumbs, 0, 0, $prepend);

        return $this;
    }

    /**
     * Insert a crumb at a specific index.
     *
     * If the index already exists, the new crumb will be inserted,
     * before the existing element, shifting the existing element one index
     * greater than before.
     *
     * If the index is out of bounds, an exception will be thrown.
     *
     * @param int $index The index to insert at.
     * @param string $content Content of the crumb.
     * @param array|string|null $url URL of the crumb. Either a string, an array of route params to pass to
     * Url::build() or null / empty if the crumb does not have a link.
     * @param array<string, mixed> $options Array of options. These options will be used as HTML attributes the crumb will
     * be rendered in (a <li> tag by default). It accepts two special keys:
     *
     * - *innerAttrs*: An array that allows you to define attributes for the inner element of the crumb (by default, to
     *   the link)
     * - *templateVars*: Specific template vars in case you override the templates provided.
     * @return $this
     * @throws \LogicException In case the index is out of bound
     */
    public function insertAt(int $index, string $content, array|string|null $url = null, array $options = []): static
    {
        if (!isset($this->crumbs[$index]) && $index !== count($this->crumbs)) {
            throw new LogicException(sprintf('No crumb could be found at index `%s`.', $index));
        }

        array_splice($this->crumbs, $index, 0, [compact('content', 'url', 'options')]);

        return $this;
    }

    /**
     * Insert a crumb before the first matching crumb with the specified content.
     *
     * Finds the index of the first crumb that matches the provided class,
     * and inserts the supplied callable before it.
     *
     * @param string $matchingContent The content of the crumb you want to insert this one before.
     * @param string $content Content of the crumb.
     * @param array|string|null $url URL of the crumb. Either a string, an array of route params to pass to
     * Url::build() or null / empty if the crumb does not have a link.
     * @param array<string, mixed> $options Array of options. These options will be used as HTML attributes the crumb will
     * be rendered in (a <li> tag by default). It accepts two special keys:
     *
     * - *innerAttrs*: An array that allows you to define attributes for the inner element of the crumb (by default, to
     *   the link)
     * - *templateVars*: Specific template vars in case you override the templates provided.
     * @return $this
     * @throws \LogicException In case the matching crumb can not be found
     */
    public function insertBefore(
        string $matchingContent,
        string $content,
        array|string|null $url = null,
        array $options = [],
    ): static {
        $key = $this->findCrumb($matchingContent);

        if ($key === null) {
            throw new LogicException(sprintf('No crumb matching `%s` could be found.', $matchingContent));
        }

        return $this->insertAt($key, $content, $url, $options);
    }

    /**
     * Insert a crumb after the first matching crumb with the specified content.
     *
     * Finds the index of the first crumb that matches the provided class,
     * and inserts the supplied callable before it.
     *
     * @param string $matchingContent The content of the crumb you want to insert this one after.
     * @param string $content Content of the crumb.
     * @param array|string|null $url URL of the crumb. Either a string, an array of route params to pass to
     * Url::build() or null / empty if the crumb does not have a link.
     * @param array<string, mixed> $options Array of options. These options will be used as HTML attributes the crumb will
     * be rendered in (a <li> tag by default). It accepts two special keys:
     *
     * - *innerAttrs*: An array that allows you to define attributes for the inner element of the crumb (by default, to
     *   the link)
     * - *templateVars*: Specific template vars in case you override the templates provided.
     * @return $this
     * @throws \LogicException In case the matching crumb can not be found.
     */
    public function insertAfter(
        string $matchingContent,
        string $content,
        array|string|null $url = null,
        array $options = [],
    ): static {
        $key = $this->findCrumb($matchingContent);

        if ($key === null) {
            throw new LogicException(sprintf('No crumb matching `%s` could be found.', $matchingContent));
        }

        return $this->insertAt($key + 1, $content, $url, $options);
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
    public function reset(): static
    {
        $this->crumbs = [];

        return $this;
    }

    /**
     * Renders the breadcrumbs trail.
     *
     * @param array<string, mixed> $attributes Array of attributes applied to the `wrapper` template. Accepts the `templateVars` key to
     * allow the insertion of custom template variable in the template.
     * @param array<string, mixed> $separator Array of attributes for the `separator` template.
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
                ['innerAttrs', 'separator'],
            );

            $separatorString = $this->formatTemplate('separator', $separator);
        }

        $crumbTrail = '';
        foreach ($crumbs as $key => $crumb) {
            $url = $crumb['url'] ? $this->Url->build($crumb['url']) : null;
            $content = $crumb['content'];
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
                'content' => $content,
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

        return $this->formatTemplate('wrapper', [
            'content' => $crumbTrail,
            'attrs' => $templater->formatAttributes($attributes, ['templateVars']),
            'templateVars' => $attributes['templateVars'] ?? [],
        ]);
    }

    /**
     * Search a crumb in the current stack which content matches the one provided as argument.
     * If found, the index of the matching crumb will be returned.
     *
     * @param string $content Content to find.
     * @return int|null Index of the crumb found, or null if it can not be found.
     */
    protected function findCrumb(string $content): ?int
    {
        foreach ($this->crumbs as $key => $crumb) {
            if ($crumb['content'] === $content) {
                return $key;
            }
        }

        return null;
    }
}
