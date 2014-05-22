
VERSION=""
REMOTE="origin"


ALL: help
.PHONY: help install test bump-version tag-version

help:
	@echo "CakePHP Makefile"
	@echo "================"
	@echo ""
	@echo "release"
	@echo "  Create a new release of CakePHP. Requires the VERSION parameter."
	@echo "  Packages up a new app skeleton tarball and uploads it to github."
	@echo ""
	@echo "publish"
	@echo "  Publish the dist/cakephp-VERSION.zip to github."
	@echo ""
	@echo "test"
	@echo "  Run the tests for CakePHP."
	@echo ""
	@echo "All other tasks are not intended to be run directly."


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
	@if [ $(VERSION) = "" ]; \
	then \
		echo "You must specify a version to bump to."; \
		exit 1; \
	fi;
	@echo "Update VERSION.txt to $(VERSION)"
	# Work around bash being bad.
	mv VERSION.txt VERSION.old
	cat VERSION.old | sed s'/^[0-9]\.[0-9]\.[0-9].*/$(VERSION)/' > VERSION.txt
	rm VERSION.old
	git add VERSION.txt
	git commit -m "Update version number to $(VERSION)"

# Tag a release
tag-release: bump-version
	@echo "Tagging $(VERSION)"
	git tag -s $(VERSION) -m "CakePHP $(VERSION)"
	git push $(REMOTE)
	git push $(REMOTE) --tags

# Tasks for tagging the app skeletong and
# creating a zipball of a fully built app skeleton.
.PHONY: clean tag-app build-app

clean:
	rm -rf build
	rm -rf dist

tag-app: composer.phar dist/cakephp-$(VERSION).zip

build/app:
	mkdir build
	git clone git@github.com/cakephp/app.git build/app

tag-app: build/app
	@if [ $(VERSION) = "" ]; \
	then \
		echo "You must specify a version to tag."; \
		exit 1; \
	fi;
	@echo "Tagging new version of application skeleton"
	cd build/app && git tag -s $(VERSION) -m "CakePHP App $(VERSION)"
	cd build/app && git push $(REMOTE)
	cd build/app && git push $(REMOTE) --tags

dist/cakephp-$(VERSION).zip: clean composer.phar tag-app
	@echo "Installing app dependencies with composer"
	cd build/app && php ../../composer.phar install
	# Make a zipball of all the files that are not in .git dirs
	# Including .git will make zip balls huge, and the zipball is
	# intended for quick start non-git, non-cli users
	@echo "Building zipball for $(VERSION)"
	cd build/app && find . -path '.git' -prune | zip dist/cakephp-$(VERSION).zip -@

# Tasks to publish zipballs to github.
.PHONY: publish

publish: dist/cakephp-$(VERSION).zip
	@echo "Publishing zipball for $(VERSION)"
