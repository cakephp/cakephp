# The following env variables need to be set:
# - VERSION
# - GITHUB_USER
# - GITHUB_TOKEN (optional if you have two factor authentication in github)

# Use the version number to figure out if the release
# is a pre-release
PRERELEASE=$(shell echo $(VERSION) | grep -E 'dev|rc|alpha|beta' --quiet && echo 'true' || echo 'false')
COMPONENTS= filesystem log utility cache datasource core collection event validation database i18n ORM
CURRENT_BRANCH=$(shell git branch | grep '*' | tr -d '* ')

# Github settings
UPLOAD_HOST=https://uploads.github.com
API_HOST=https://api.github.com
OWNER=cakephp
REMOTE=origin

ifdef GITHUB_TOKEN
	AUTH=-H 'Authorization: token $(GITHUB_TOKEN)'
else
	AUTH=-u $(GITHUB_USER) -p$(GITHUB_PASS)
endif

DASH_VERSION=$(shell echo $(VERSION) | sed -e s/\\./-/g)

# Used when building packages for older 3.x packages.
# The build scripts clone cakephp/app, and this var selects the
# correct tag in that repo.
# For 3.1.x use 3.1.2
# For 3.0.x use 3.0.5
APP_VERSION:=master

ALL: help
.PHONY: help install test need-version bump-version tag-version

help:
	@echo "CakePHP Makefile"
	@echo "================"
	@echo ""
	@echo "release"
	@echo "  Create a new release of CakePHP. Requires the VERSION and GITHUB_USER, or GITHUB_TOKEN parameter."
	@echo "  Packages up a new app skeleton tarball and uploads it to github."
	@echo ""
	@echo "package"
	@echo "  Build the app package with all its dependencies."
	@echo ""
	@echo "publish"
	@echo "  Publish the dist/cakephp-VERSION.zip to github."
	@echo ""
	@echo "components"
	@echo "  Split each of the public namespaces into separate repos and push the to Github."
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
.PHONY: clean package

clean:
	rm -rf build
	rm -rf dist

build:
	mkdir -p build

build/app: build
	git clone git@github.com:$(OWNER)/app.git build/app/
	cd build/app && git checkout $(APP_VERSION)

build/cakephp: build
	git checkout $(VERSION)
	git checkout-index -a -f --prefix=build/cakephp/
	git checkout -

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
package: dist/cakephp-$(DASH_VERSION).zip



# Tasks to publish zipballs to Github.
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

# Tasks for publishing separate repositories out of each CakePHP namespace

components: $(foreach component, $(COMPONENTS), component-$(component))
components-tag: $(foreach component, $(COMPONENTS), tag-component-$(component))

component-%:
	git checkout $(CURRENT_BRANCH) > /dev/null
	- (git remote add $* git@github.com:$(OWNER)/$*.git -f 2> /dev/null)
	- (git branch -D $* 2> /dev/null)
	git checkout -b $*
	git filter-branch --prune-empty --subdirectory-filter src/$(shell php -r "echo ucfirst('$*');") -f $*
	git push -f $* $*:$(CURRENT_BRANCH)
	git checkout $(CURRENT_BRANCH) > /dev/null

tag-component-%: component-% guard-VERSION guard-GITHUB_USER
	@echo "Creating tag for the $* component"
	git checkout $*
	curl $(AUTH) -XPOST $(API_HOST)/repos/$(OWNER)/$*/git/refs -d '{ \
		"ref": "refs\/tags\/$(VERSION)", \
		"sha": "$(shell git rev-parse $*)" \
	}'
	git checkout $(CURRENT_BRANCH) > /dev/null
	git branch -D $*
	git remote rm $*

# Top level alias for doing a release.
release: guard-VERSION guard-GITHUB_USER tag-release components-tag package publish
