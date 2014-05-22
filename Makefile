
VERSION="unset"


ALL: help
.PHONY: help

help:
	@echo "CakePHP Makefile"
	@echo "================"
	@echo ""
	@echo "release"
	@echo "  Create a new release of CakePHP. Requires the VERSION parameter."
	@echo "  Packages up a new app skeleton tarball and uploads it to github."
	@echo ""
	@echo "test"
	@echo "  Run the tests for CakePHP."
	@echo ""
	@echo "bump-version"
	@echo "  Bumps the version to VERSION"


# Download composer
composer.phar:
	curl -sS https://getcomposer.org/installer | php

# Install dependencies
install: composer.phar
	php composer.phar install

test: install
	vendor/bin/phpunit

# Update VERSION.txt to new version.
bump-version:
	@if [ $(VERSION) = "unset" ]; \
	then \
		echo "You must specify a version to bump to."; \
		exit 1; \
	fi;
	# Work around bash being bad.
	mv VERSION.txt VERSION.old
	cat VERSION.old | sed s'/^[0-9]\.[0-9]\.[0-9].*/$(VERSION)/' > VERSION.txt
	rm VERSION.old
