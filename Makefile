# The following env variables need to be set:
# - VERSION
# - GITHUB_USER
# - GITHUB_TOKEN (optional if you have two factor authentication in github)

# Use the version number to figure out if the release
# is a pre-release
PRERELEASE=$(shell echo $(VERSION) | grep -E 'dev|rc|alpha|beta' --quiet && echo 'true' || echo 'false')

# Github settings
UPLOAD_HOST=https://uploads.github.com
API_HOST=https://api.github.com
OWNER='cakephp'
REMOTE="origin"

ifdef GITHUB_TOKEN
	AUTH=-H 'Authorization: token $(GITHUB_TOKEN)'
else
	AUTH=-u $(GITHUB_USER) -p$(GITHUB_PASS)
endif

DASH_VERSION=$(shell echo $(VERSION) | sed -e s/\\./-/g)

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


test: install
	vendor/bin/phpunit


# Utility target for checking required parameters
guard-%:
	@if [ "$($*)" = '' ]; then \
		echo "Missing required $* variable."; \
		exit 1; \
	fi;


# Download composer
composer.phar:
	curl -sS https://getcomposer.org/installer | php

# Install dependencies
install: composer.phar
	php composer.phar install



# Version bumping & tagging for CakePHP itself
# Update VERSION.txt to new version.
bump-version: guard-VERSION
	@echo "Update VERSION.txt to $(VERSION)"
	# Work around sed being bad.
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



# Tasks for tagging the app skeleton and
# creating a zipball of a fully built app skeleton.
.PHONY: clean tag-app build-app package

clean:
	rm -rf build
	rm -rf dist

build:
	mkdir -p build

build/app: build
	git clone git@github.com:$(OWNER)/app.git build/app/

build/cakephp: build
	git checkout-index -a -f --prefix=build/cakephp/

tag-app: guard-VERSION build/app
	@echo "Tagging new version of application skeleton"
	cd build/app && git tag -s $(VERSION) -m "CakePHP App $(VERSION)"
	cd build/app && git push $(REMOTE)
	cd build/app && git push $(REMOTE) --tags

dist/cakephp-$(DASH_VERSION).zip: build/app build/cakephp composer.phar
	mkdir -p dist
	@echo "Installing app dependencies with composer"
	# Install deps with composer
	cd build/app && php ../../composer.phar install
	# Copy the current cakephp libs up so we don't have to wait
	# for packagist to refresh.
	rm -rf build/app/vendor/cakephp/cakephp
	cp -r build/cakephp build/app/vendor/cakephp/cakephp
	# Make a zipball of all the files that are not in .git dirs
	# Including .git will make zip balls huge, and the zipball is
	# intended for quick start non-git, non-cli users
	@echo "Building zipball for $(VERSION)"
	cd build/app && find . -not -path '*.git*' | zip ../../dist/cakephp-$(DASH_VERSION).zip -@

# Easier to type alias for zip balls
package: tag-app dist/cakephp-$(DASH_VERSION).zip



# Tasks to publish zipballs to github.
.PHONY: publish release

publish: guard-VERSION guard-GITHUB_USER dist/cakephp-$(DASH_VERSION).zip
	@echo "Creating draft release for $(VERSION). prerelease=$(PRERELEASE)"
	curl $(AUTH) -XPOST $(API_HOST)/repos/$(OWNER)/cakephp/releases -d '{ \
		"tag_name": "$(VERSION)", \
		"name": "CakePHP $(VERSION) released", \
		"draft": true, \
		"prerelease": $(PRERELEASE) \
	}' > release.json
	# Extract id out of response json.
	php -r '$$f = file_get_contents("./release.json"); \
		$$d = json_decode($$f, true); \
		file_put_contents("./id.txt", $$d["id"]);'
	@echo "Uploading zip file to github."
	curl $(AUTH) -XPOST \
		$(UPLOAD_HOST)/repos/$(OWNER)/cakephp/releases/`cat ./id.txt`/assets?name=cakephp-$(DASH_VERSION).zip \
		-H "Accept: application/vnd.github.manifold-preview" \
		-H 'Content-Type: application/zip' \
		--data-binary '@dist/cakephp-$(DASH_VERSION).zip'
	# Cleanup files.
	rm release.json
	rm id.txt

# Top level alias for doing a release.
release: guard-VERSION guard-GITHUB_USER tag-release package publish
