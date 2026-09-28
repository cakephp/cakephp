# PHP-FPM images

The [`publish-php-images.yml`](../../.github/workflows/publish-php-images.yml) workflow builds and publishes `ghcr.io/cakephp/cakephp:8.2` through `:8.5` when its files change on `5.x` and every Monday at 03:17 UTC. A maintainer can also use **Actions → Publish PHP-FPM images → Run workflow** to publish on demand. The workflow must be on the repository's default branch before the schedule or manual trigger can run.

Each image uses the official PHP-FPM Alpine variant and supports `linux/amd64` and `linux/arm64`. The workflow builds each platform on a native GitHub runner and assembles the multi-architecture tag afterward, so QEMU is unnecessary. Alpine uses musl libc, so native binaries or extensions built for glibc may need an Alpine build. The workflow publishes with the repository's `GITHUB_TOKEN` and requires no custom secret. After the first successful run, the package appears on the [CakePHP organization Packages page](https://github.com/orgs/cakephp/packages). New GHCR packages are private by default; package visibility can be changed in the package settings. If a GHCR package at this path already exists but is not connected to this repository, grant this repository Actions access to the package.

Installed extensions: `bcmath`, `exif`, `gd` (FreeType, JPEG, PNG, WebP), `gettext`, `gmp`, `intl`, `mysqli`, `pcntl`, `pdo_mysql`, `pdo_pgsql`, `pgsql`, `soap`, `sockets`, `xsl`, and `zip`. The official PHP-FPM base image also includes extensions such as `curl`, `mbstring`, `opcache`, `PDO`, `pdo_sqlite`, `sqlite3`, `sodium`, and XML support. The Dockerfile checks that both SQLite extensions are available during the build.

Local build example:

```sh
docker build --build-arg PHP_IMAGE_TAG=8.5-fpm-alpine -t cakephp-php-fpm:8.5 docker/php-fpm
docker run --rm cakephp-php-fpm:8.5 php -m
```
