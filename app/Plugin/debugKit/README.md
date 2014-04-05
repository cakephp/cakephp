# CakePHP DebugKit [![Build Status](https://secure.travis-ci.org/cakephp/debug_kit.png?branch=master)](http://travis-ci.org/cakephp/debug_kit)

DebugKit provides a debugging toolbar and enhanced debugging tools for CakePHP applications.

## Requirements

The master branch has the following requirements:

* CakePHP 2.2.0 or greater.
* PHP 5.3.0 or greater.

## Installation

* Clone/Copy the files in this directory into `app/Plugin/DebugKit`

This can be done with the git submodule command
```sh
git submodule add https://github.com/cakephp/debug_kit.git app/Plugin/DebugKit
```

* Ensure the plugin is loaded in `app/Config/bootstrap.php` by calling `CakePlugin::load('DebugKit');`
* Include the toolbar component in your `app/Controller/AppController.php`:
```php
class AppController extends Controller {
         public $components = array('DebugKit.Toolbar');
}
```
* Set `Configure::write('debug', 1);` in `app/Config/core.php`.
* Make sure to remove the 'sql_dump' element from your layout (usually
  `app/View/Layouts/default.ctp` if you want to experience the awesome that is
  the debug kit SQL log.

### Using Composer

Ensure `require` is present in `composer.json`. This will install the plugin into `Plugin/DebugKit`:

```json
{
    "require": {
        "cakephp/debug_kit": "2.2.*"
    }
}
```

## Reporting issues

If you have a problem with DebugKit please open an issue on [GitHub](https://github.com/cakephp/debug_kit/issues).

## Contributing

If you'd like to contribute to DebugKit, check out the
[roadmap](https://github.com/cakephp/debug_kit/wiki/roadmap) for any
planned features. You can [fork](https://help.github.com/articles/fork-a-repo)
the project, add features, and send [pull
requests](https://help.github.com/articles/using-pull-requests) or open
[issues](https://github.com/cakephp/debug_kit/issues).

## Versions

DebugKit has several releases, each compatible with different releases of
CakePHP. Use the appropriate version by downloading a tag, or checking out the
correct branch.

* `1.0, 1.1, 1.2` are compatible with CakePHP 1.2.x. These releases of DebugKit
  will not work with CakePHP 1.3. You can also use the `1.2-branch` for the mos
  recent updates and bugfixes.
* `1.3.0` is compatible with CakePHP 1.3.x only. It will not work with CakePHP
  1.2. You can also use the `1.3` branch to get the most recent updates and
  bugfixes.
* `2.0.0` is compatible with CakePHP 2.0.x only. It will not work with previous
  CakePHP versions.
* `2.2.0` is compatible with CakePHP 2.2.0 and greater. It will not work with
  older versions of CakePHP as this release uses new API's available in 2.2.
  You can also use the `master` branch to get the most recent updates.
* `2.2.x` are compatible with CakePHP 2.2.0 and greater. It is a necessary
  upgrade for people using CakePHP 2.4 as the naming conventions around loggers
  changed in that release.

# Documentation

## Toolbar Panels

The DebugKit Toolbar is comprised of several panels, which are shown by clicking the
CakePHP icon in the upper right-hand corner of your browser after DebugKit has been
installed and loaded. Each panel is comprised of a panel class and view element.
Typically a panel handles the collection and display of a single type of information
such as Logs or Request information. You can choose to panels from the toolbar or add
your own custom panels.

### Built-in Panels

There are several built-in panels. They are

 * **History** Allows access to previous request information, useful when
   debugging actions with redirects.
 * **Request** Displays information about the current request, GET, POST, Cake
   Parameters, Current Route information and Cookies if the `CookieComponent`
   is in you controller's components.
 * **Session** Display the information currently in the Session.
 * **Timer** Display any timers that were set during the request see
   `DebugKitDebugger` for more information. Also displays
   memory use at component callbacks as well as peak memory used.
 * **Sql Logs** Displays sql logs for each database connection.
 * **Log** Display any entries made to the log files this request.
 * **Variables** Display View variables set in controller.
 * **Environment** Display environment variables related to PHP + CakePHP.

## Configuration

The toolbar has a few configuration settings. Settings are passed in the component declaration like normal component configuration.

```php
public $components = array(
    'DebugKit.Toolbar' => array(/* array of settings */)
);
```	

### Configuring Panels

You can customize the toolbar to show your custom panels or hide any built-in panel when adding it toolbar to your components.
```php
public $components = array('DebugKit.Toolbar' => array(
    'panels' => array('customPanel', 'timer'=>false)
    )
);
```

Would display your custom panel and all built-in panels except the 'Timer' panel.

#### Controlling Panels

Using the panels key you can specify which panels you want to load, as well as the order in which you want the panels loaded.
```php
public $components = array(
        'DebugKit.Toolbar' => array('panels' => array('myCustomPanel', 'timer' => false))
);
```
	
Would add your custom panel `myCustomPanel` to the toolbar and exclude the default `Timer` panel. In addition to choosing which panels you want, you can pass options into the `__construct` of the panels. For example the built-in `History` panel uses the `history` key to set the number of historical requests to track.
```php
public $components = array(
        'DebugKit.Toolbar' => array('history' => 10)
);
```

Would load the `History` panel and set its history level to 10. The `panels` key is not passed to the Panel constructor.

#### forceEnable

The `forceEnable` setting is new in DebugKit 1.1. It allows you to force the toolbar to display regardless of the value of `Configure::read('debug');`. This is useful when profiling an application with debug kit as you can enable the toolbar even when running the application in production mode.

#### autoRun

autoRun is a new configuration setting for DebugKit 1.2. It allows you to control whether or not the toolbar is displayed automatically or whether you would like to use a query string parameter to enable it. Set this configuration key to false to use query string parameter toggling of the toolbar.
```php
var $components = array(
    'DebugKit.Toolbar' => array('autoRun' => false)
);
```

When visiting a page you can add `?debug=true` to the url and the toolbar will be visible. Otherwise it will stay hidden and not execute.

## Developing your own panels

You can create your own custom panels for DebugKit to help in debugging your applications.

### Panel Classes

Panel Classes simply need to be placed in`Panel` directory inside a `Lib` path. The filename should match the classname, so the class `MyCustomPanel` would be expected to have a filename of `app/Lib/Panel/MyCustomPanel.php`.
```php

App::uses('DebugPanel', 'DebugKit.Lib');

/**
 * My Custom Panel
 */
class MyCustomPanel extends DebugPanel {
        ...
}
```
See also the example `Test/test_app/Plugin/DebugkitTestPlugin/Lib/Panel/PluginTestPanel.php`.

Notice that custom panels are required to subclass the `DebugPanel` class. Panels can define the
`css` and `javascript` properties to include additional CSS or javascript on the page. Both
properties should be an array.
```php
class MyCustomPanel extends DebugPanel {
        public $javascript = array(
                '/my_plugin/js/custom_panel.js'
        );
}
```

### Callbacks

Panel objects have 2 callbacks, that allow them to hook into and introspect on the current request.
```php
startup(Controller $controller)
```

Each panel's `startup()` method is called during component `startup()` process. `$controller` is a reference to the current controller object.
```php
beforeRender(Controller $controller)
```

Much like `startup()` `beforeRender()` is called during the Component beforeRender() process. Again `$controller` is a reference to the current controller. Normally at this point you could do additional introspection on the controller. The return of a panels `beforeRender()` is automatically passed to the View by the Toolbar Component. Therefore, under normal use you do not need to explicitly set variables to the controller.

#### Example of beforeRender callback
```php
/**
 * beforeRender callback - grabs request params
 *
 * @return array
 */
 public function beforeRender(Controller $controller) {
     return $controller->params;
 }
```

This would return cake's internal params array. The return of a panel's `beforeRender()` is available in you Panel element as `$content`

### Panel Elements 

Each Panel is expected to have a view element that renders the content from the panel. The element name must be the underscored inflection of the class name. For example `SessionPanel` has an element named `session_panel.ctp`, and sqllogPanel has an element named `sqllog_panel.ctp`. These elements should be located in the root of your `View/Elements` directory.

#### Custom titles and elements

Panels should pick up their title and element name by convention. However, if you need to choose a custom element name or title, there are properties to allow that configuration.

- `$title` - Set a custom title for use in the toolbar. This title will be used as the panels button.
- `$elementName` - Set a custom element name to be used to render the panel.

### Panels as Cake Plugins

Panels provided by [Cake Plugins](http://book.cakephp.org/2.0/en/plugins.html) work almost entirely the same as other plugins, with one minor difference:  You must set `var $plugin` to be the name of the plugin directory, so that the panel's Elements can be located at render time.
```php
class MyCustomPanel extends DebugPanel {
    public $plugin = 'MyPlugin';
        ...
}
```

To use a plugin panel, use the common CakePHP dot notation for plugins. 
```php
public $components = array('DebugKit.Toolbar' => array(
    'panels' => array('MyPlugin.MyCustom')
));
```
The above would load all the default panels as well as the custom panel from `MyPlugin`. 

## Cache Engine

By default DebugKit uses File as engine for internal cache, but if you want to use another cache engine you can customize it simply adding an cache key inside the components config array.
```php
public $components = array('DebugKit.Toolbar' => array(
        'cache' => array('engine' => 'Memcache', 'servers' => array('127.0.0.1:11211'))
        )
);
```

You can use any cache engine supported by CakePHP, the same you set in both core.php and bootstrap.php files with Cache::config() method.

## Viewing the toolbar for ajax requests

When doing Ajax requests, you will not be able to see an HTML version of the toolbar. However, if you have a browser extension that supports FirePHP, you can view
the toolbar in your browser:

- [FirePHP 4 chrome](https://chrome.google.com/webstore/detail/firephp4chrome/gpgbmonepdpnacijbbdijfbecmgoojma)
- [FirePHP for chrome](https://chrome.google.com/webstore/detail/firephp-for-chrome/goajlbdffkligccnfgibeilhdnnpaead)
- [FirePHP for firefox](https://addons.mozilla.org/en-US/firefox/addon/firephp/)

Once you have installed the correct extension, you should see the toolbar data output on each ajax request.
