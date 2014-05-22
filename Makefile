VERSION=
REMOTE="origin"

# Use the version number to figure out if the release
# is a pre-release
PRERELEASE=$(shell echo $(VERSION) | grep -E 'dev|rc|alpha|beta' --quiet && echo 'true' || echo 'false')

# Github settings
UPLOAD_HOST=https://uploads.github.com
API_HOST=https://api.github.com
GITHUB_USER=

ALL: help
.PHONY: help install test need-version bump-version tag-version

help:
	@echo "CakePHP Makefile"
	@echo "================"
	@echo ""
	@echo "release"
	@echo "  Create a new release of CakePHP. Requires the VERSION and GITHUB_USER parameter."
	@echo "  Packages up a new app skeleton tarball and uploads it to github."
	@echo ""
	@echo "package"
	@echo "  Build the app package with all its dependencies."
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

guard-%:
	@if [ "$($*)" = '' ]; then \
		echo "Missing required $* variable."; \
		exit 1; \
	fi;

# Update VERSION.txt to new version.
bump-version: guard-VERSION
	@echo "Update VERSION.txt to $(VERSION)"
	# Work around bash being bad.
	mv VERSION.txt VERSION.old
	cat VERSION.old | sed s'/^[0-9]\.[0-9]\.[0-9].*/$(VERSION)/' > VERSION.txt
	rm VERSION.old
	git add VERSION.txt
	git commit -m "Update version number to $(VERSION)"

# Tag a release
tag-release: guard-VERSION bump-version
	@echo "Tagging $(VERSION)"
	git tag -s $(VERSION) -m "CakePHP $(VERSION)"
	git push $(REMOTE)
	git push $(REMOTE) --tags

# Tasks for tagging the app skeletong and
# creating a zipball of a fully built app skeleton.
.PHONY: clean tag-app build-app package

clean:
	rm -rf build
	rm -rf dist

build/app:
	@if [ ! -d build ]; \
	then \
		mkdir build; \
	fi;
	git clone git@github.com:cakephp/app.git build/app

tag-app: guard-VERSION build/app
	@echo "Tagging new version of application skeleton"
	cd build/app && git tag -s $(VERSION) -m "CakePHP App $(VERSION)"
	cd build/app && git push $(REMOTE)
	cd build/app && git push $(REMOTE) --tags

# Easier to type alias for zip balls
package: tag-app dist/cakephp-$(VERSION).zip

dist/cakephp-$(VERSION).zip: composer.phar
	@if [ ! -d dist ]; \
	then \
		mkdir dist; \
	fi;
	@echo "Installing app dependencies with composer"
	cd build/app && php ../../composer.phar install
	# Make a zipball of all the files that are not in .git dirs
	# Including .git will make zip balls huge, and the zipball is
	# intended for quick start non-git, non-cli users
	@echo "Building zipball for $(VERSION)"
	find build/app -not -path '*.git*' | zip dist/cakephp-$(VERSION).zip -@


# Tasks to publish zipballs to github.
.PHONY: publish release

publish: guard-VERSION guard-GITHUB_USER dist/cakephp-$(VERSION).zip
	@echo "Creating draft release for $(VERSION). prerelease=$(PRERELEASE)"
	curl -u $(GITHUB_USER) -p -XPOST $(API_HOST)/repos/cakephp/cakephp/releases -d '{ \
		"tag_name": "$(VERSION)", \
		"target_commitish": "3.0", \
		"name": "CakePHP $(VERSION) released", \
		"draft": true, \
		"prerelease": $(PRERELEASE) \
	}' > release.json
	# Extract id out of response json.
	php -r '$$f = file_get_contents("./release.json"); \
		$$d = json_decode($$f, true); \
		file_put_contents("./id.txt", $$d["id"]);'
	@echo "Uploading zip file to github."
	curl -u $(GITHUB_USER) -p -XPOST \
		$(UPLOAD_HOST)/repos/cakephp/cakephp/releases/`cat ./id.txt`/assets?name=cakephp-$(VERSION).zip \
		-H 'Content-Type: application/zip' \
		-d '@dist/cakephp-$(VERSION).zip'
	# Cleanup files.
	rm release.json
	rm id.txt

# Top level alias for doing a release.
release: guard-VERSION guard-GITHUB_USER package publish
