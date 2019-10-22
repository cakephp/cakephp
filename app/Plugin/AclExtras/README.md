# Acl Extras

Acl Extras provides a console app that helps you manage DbAcl records more easily.  Its main feature and purpose is to make generating Aco nodes for all your controllers and actions easier.  It also includes some helper methods for verifying and recovering corrupted trees.

## Installation

Clone the repo or download a tarball and install it into `app/Plugin/AclExtras` or in any of your pluginPaths.

Then activate the plugin in your app/Config/bootstrap.php file as shown below:

    CakePlugin::load('AclExtras');

## Usage

You can find a list of commands by running `Console/cake AclExtras.AclExtras -h` from your command line.

### Setting up the contorller

You'll need to configure AuthComponent to use the Actions authorization method.  
In your `beforeFilter` add the following:

    $this->Auth->authorize = 'actions';
    $this->Auth->actionPath = 'controllers/';

## Issues 

If you find an issue in the code or want to suggest something, please use the tickets at http://github.com/markstory/acl_extras/issues

## License

Acl Extras is licensed under the MIT license.
