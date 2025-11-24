# Command List: Before & After

## Before (129 lines)

```
Current Paths:

* app:  src/
* root: /var/www/html/
* core: /var/www/html/vendor/cakephp/cakephp/

Available Commands:

app:
 - generate_controller_actions
 - generate_sso_openid_config
 └─── Generate CONFIG/sso_openid_config.php
 - help
 - migrate_code
 └─── Command description here.
 - migrate_data
 └─── Command description here.
 - recursive
 └─── Command description here.
 - reset_pwd
 └─── Reset passwords for local development.
 - syntax_check
 └─── Check PHP files using PHP linter.
 - test_coverage
 └─── Generate PHPUnit test coverage report for the dashboard.

bake:
 - bake
 - bake all
 - bake behavior
 - bake cell
 - bake command
 - bake command_helper
 - bake component
 - bake controller
 - bake controller all
 - bake enum
 - bake fixture
 - bake fixture all
 - bake form
 - bake helper
 - bake mailer
 - bake middleware
 - bake model
 - bake model all
 - bake plugin
 - bake template
 - bake template all
 - bake test

cake/twig_view:
 - twig-view compile

cakephp:
 - cache clear
 └─── Clear all data in a single cache engine.
 - cache clear_all
 └─── Clear all data in all configured cache engines.
 - cache clear_group
 └─── Clear all data in a single cache group.
 - cache list
 └─── Show a list of configured caches.
 - completion
 └─── Used by shells like bash to autocomplete command name, options and arguments
 - counter_cache
 └─── Update counter cache for a model.
 - i18n
 └─── I18n commands let you generate .pot files to power translations in your application.
 - i18n extract
 └─── Extract i18n POT files from application source files.
 - i18n init
 └─── Initialize a language PO file from the POT file.
 - plugin assets copy
 └─── Copy plugin assets to app's webroot.
 - plugin assets remove
 └─── Remove plugin assets from app's webroot.
 - plugin assets symlink
 └─── Symlink (copy as fallback) plugin assets to app's webroot.
 - plugin list
 └─── Displays all currently available plugins.
 - plugin load
 └─── Command for loading plugins.
 - plugin loaded
 └─── Displays all currently loaded plugins.
 - plugin unload
 └─── Command for unloading plugins.
 - routes
 └─── Get the list of routes connected in this application.
 - routes check
 └─── Check a URL string against the routes.
 - routes generate
 └─── Check a routing array against the routes.
 - schema_cache build
 └─── Build all metadata caches for the connection.
 - schema_cache clear
 └─── Clear all metadata caches for the connection.
 - server
 └─── PHP Built-in Server for CakePHP
 - version
 └─── Show the CakePHP version.

cakephp_fixture_factories:
 - bake fixture_factory
 - fixture_factories_persist

debug_kit:
 - benchmark

ide_helper:
 - annotate all
 └─── Annotate all supported classes.
 - annotate callbacks
 └─── Annotate callback methods using callback annotation tasks.
 - annotate classes
 └─── Annotate classes using class annotation tasks.
 - annotate commands
 └─── Annotate primary model as well as used models in commands.
 - annotate components
 └─── Annotate used components inside components.
 - annotate controllers
 └─── Annotate primary model as well as used models in controller class.
 - annotate helpers
 └─── Annotate used helpers inside helpers.
 - annotate models
 └─── Annotate fields and relations in table and entity class.
 - annotate templates
 └─── Annotate helpers in view templates and elements.
 - annotate view
 └─── Annotate used helpers in AppView.
 - generate code_completion
 └─── CodeCompletion File Generator for generating better IDE auto-complete/hinting.
 - generate phpstorm
 └─── Meta File Generator for generating better IDE auto-complete/hinting in PhpStorm.
 - illuminate
 └─── PHP file modifier.

migrations:
 - bake migration
 - bake migration_diff
 - bake migration_snapshot
 - bake seed
 - migrations
 - migrations dump
 - migrations mark_migrated
 - migrations migrate
 - migrations rollback
 - migrations seed
 - migrations status

queue:
 - bake queue_task
 - queue add
 - queue info
 - queue job
 - queue run
 - queue worker

search:
 - bake filter_collection

setup:
 - bake healthcheck
 - cli_test
 └─── Test CLI env, e.g. Router for CLI usage.
 - current_config configure
 └─── Outputs configure values for given dot path.
 - current_config display
 └─── Displays runtime configuration (config and environment).
 - current_config phpinfo
 └─── Display phpinfo() for CLI.
 - current_config validate
 └─── Checks application config for CLI (DB, Cache).
 - db init
 └─── Inits DB(s) if not yet existing.
 - db reset
 └─── Resets DB by truncating all tables.
 - db wipe
 └─── Resets DB by dropping all tables.
 - db_backup create
 └─── Dumps SQL database into a backup file.
 - db_backup restore
 └─── Restores SQL database from a backup file.
 - db_data dates
 └─── Check database for invalid zero date/datetime values.
 - db_data enums
 └─── Check database for invalid enum values against PHP BackedEnum definitions.
 - db_data orphans
 └─── Check database for orphaned foreign key records.
 - db_integrity bools
 └─── Check database integrity issues regarding boolean fields.
 - db_integrity constraints
 └─── Check database integrity issues regarding nullable foreign key columns.
 - db_integrity ints
 └─── Check database integrity issues regarding int field lengths.
 - db_integrity keys
 └─── Check database integrity issues regarding missing unsigned keys.
 - db_integrity nulls
 └─── Assert proper non null fields.
 - healthcheck
 └─── Run healthcheck for the application (CLI version).
 - mail_check
 - maintenance_mode activate
 - maintenance_mode deactivate
 - maintenance_mode status
 - maintenance_mode whitelist
 - reset
 └─── Can reset local development data.
 - user create
 - user update

test_helper:
 - fixture_check
 - linter
 └─── Run custom linting tasks for code quality checks.

tools:
 - inflect

To run a command, type `bin/cake command_name [args|options]`
To get help on a specific command, type `bin/cake command_name --help`
```

---

## After (54 lines)

```
Available Commands:

app:
 - generate_controller_actions
 - generate_sso_openid_config
 - help
 - migrate_code
 - migrate_data
 - recursive
 - reset_pwd
 - syntax_check
 - test_coverage

bake:
 - bake [all|behavior|cell|command|command_helper|component|controller|enum|fixture|form|helper|mailer|middleware|model|plugin|template|test]
 - bake controller [all]
 - bake fixture [all]
 - bake model [all]
 - bake template [all]

cake/twig_view:
 - twig-view [compile]

cakephp:
 - cache [clear|clear_all|clear_group|list]
 - completion
 - counter_cache
 - i18n [extract|init]
 - plugin [assets copy|assets remove|assets symlink|list|load|loaded|unload]
 - routes [check|generate]
 - schema_cache [build|clear]
 - server
 - version

cakephp_fixture_factories:
 - bake [fixture_factory]
 - fixture_factories_persist

debug_kit:
 - benchmark

ide_helper:
 - annotate [all|callbacks|classes|commands|components|controllers|helpers|models|templates|view]
 - generate [code_completion|phpstorm]
 - illuminate

migrations:
 - bake [migration|migration_diff|migration_snapshot|seed]
 - migrations [dump|mark_migrated|migrate|rollback|seed|status]

queue:
 - bake [queue_task]
 - queue [add|info|job|run|worker]

search:
 - bake [filter_collection]

setup:
 - bake [healthcheck]
 - cli_test
 - current_config [configure|display|phpinfo|validate]
 - db [init|reset|wipe]
 - db_backup [create|restore]
 - db_data [dates|enums|orphans]
 - db_integrity [bools|constraints|ints|keys|nulls]
 - healthcheck
 - mail_check
 - maintenance_mode [activate|deactivate|status|whitelist]
 - reset
 - user [create|update]

test_helper:
 - fixture_check
 - linter

tools:
 - inflect

To run a command, type `bin/cake command_name [args|options]`
To get help on a specific command, type `bin/cake command_name --help`
To see command descriptions, use `bin/cake --help --verbose`
```

---

## Summary

| Metric | Before | After | Reduction |
|--------|--------|-------|-----------|
| Lines  | ~129   | ~54   | **58%**   |
| Paths section | Shown | Hidden | - |
| Descriptions | Shown | Hidden | - |
| Subcommands | Expanded | Grouped | - |

Use `bin/cake --help --verbose` to see the full output with descriptions.
