#!/usr/bin/env bash
#
# Builds the distributable plugin zip from a git ref (tag or HEAD).
#
# The layout follows the house release pattern (infomaniak-connect-openid):
# one top-level folder named after the plugin slug, containing the tracked
# runtime files (filtered by .gitattributes export-ignore) plus the two
# generated pieces git archive cannot carry: the Vite build output (build/)
# and the production autoloader (vendor/).
#
# Usage: bin/build-release.sh <tag-or-ref> [output-dir]
#
set -euo pipefail

ref="${1:?Usage: bin/build-release.sh <tag-or-ref> [output-dir]}"
dist="${2:-dist}"
slug="wp-infomaniak-ticketing"

rm -rf "${dist:?}"
mkdir -p "${dist}/${slug}"

# Tracked files only — .gitattributes export-ignore filters the dev tooling.
git archive --format=tar "${ref}" | tar -x -C "${dist}/${slug}"

# Install the production autoloader inside the dist copy so the dev vendor/
# of the working tree is never touched (the plugin ships no runtime packages,
# this only dumps the optimized autoloader). The lock file is carried in for
# a locked install, then dropped so it never ships.
cp composer.lock "${dist}/${slug}/composer.lock"
composer --working-dir="${dist}/${slug}" install --no-dev --optimize-autoloader
rm "${dist}/${slug}/composer.lock"

# Compiled front-end assets: without them the manifest lookup silently no-ops.
yarn build
cp -R build "${dist}/${slug}/build"

(cd "${dist}" && zip -qr "${slug}.zip" "${slug}")
echo "${dist}/${slug}.zip"
