# PHP-FPM images

The [`publish-php-images.yml`](../../.github/workflows/publish-php-images.yml) workflow builds and publishes `ghcr.io/cakephp/cakephp:8.1` through `:8.5` when its files change on `5.x` and every Monday at 03:17 UTC. A maintainer can also use **Actions → Publish PHP-FPM images → Run workflow** to publish on demand. The workflow must be on the repository's default branch before the schedule or manual trigger can run.

Each image uses the official PHP-FPM Debian Bookworm image, which is based on `debian:bookworm-slim`, and supports `linux/amd64` and `linux/arm64`. The workflow publishes with the repository's `GITHUB_TOKEN` and requires no custom secret. After the first successful run, the package appears on the [CakePHP organization Packages page](https://github.com/orgs/cakephp/packages). New GHCR packages are private by default; package visibility can be changed in the package settings. If a GHCR package at this path already exists but is not connected to this repository, grant this repository Actions access to the package.

Installed extensions: `bcmath`, `exif`, `gd` (FreeType, JPEG, PNG, WebP), `gettext`, `gmp`, `intl`, `mysqli`, `pcntl`, `pdo_mysql`, `pdo_pgsql`, `pgsql`, `soap`, `sockets`, `xsl`, and `zip`. The official PHP-FPM base image also includes extensions such as `curl`, `mbstring`, `opcache`, `PDO`, `pdo_sqlite`, `sodium`, and XML support.

PHP 8.1 reached end of life on 31 December 2025. Its image uses the final official PHP 8.1.34 base, so weekly rebuilds can refresh Debian packages but **cannot provide new PHP 8.1 security fixes**. It is for legacy CakePHP applications; the current CakePHP `5.x` branch requires PHP 8.2 or newer.

Local build example:

```sh
docker build --build-arg PHP_IMAGE_TAG=8.5-fpm-bookworm -t cakephp-php-fpm:8.5 docker/php-fpm
docker run --rm cakephp-php-fpm:8.5 php -m
```
