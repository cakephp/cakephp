#!/usr/bin/env bash
set -euo pipefail

if [[ $# -ne 3 ]]; then
  echo "Usage: $0 IMAGE_NAME PHP_VERSION DIGEST_DIR" >&2
  exit 2
fi

image_name=$1
php_version=$2
digest_dir=$3

shopt -s nullglob
digest_files=("$digest_dir"/*)
if [[ ${#digest_files[@]} -ne 2 ]]; then
  echo "Expected two image digests in $digest_dir; found ${#digest_files[@]}" >&2
  exit 1
fi

sources=()
for file in "${digest_files[@]}"; do
  digest=${file##*/}
  if [[ ! -f $file || ! $digest =~ ^[a-f0-9]{64}$ ]]; then
    echo "Invalid image digest file: $file" >&2
    exit 1
  fi
  sources+=("$image_name@sha256:$digest")
done

docker buildx imagetools create -t "$image_name:$php_version" "${sources[@]}"
docker buildx imagetools inspect "$image_name:$php_version"
