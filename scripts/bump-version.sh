#!/usr/bin/env bash
#
# Bumps the package version by creating a new annotated git tag.
# This package has no `version` field in composer.json — Composer/Packagist
# resolve the version from git tags, so tagging *is* the version bump.
#
# Usage:
#   scripts/bump-version.sh [major|minor|patch] [-m "message"] [--push]
#
# Defaults to a patch bump. Without --push, the tag is created locally only;
# run `git push origin <branch> --follow-tags` yourself when ready.

set -euo pipefail

BUMP="patch"
MESSAGE=""
PUSH=false

while [[ $# -gt 0 ]]; do
    case "$1" in
        major|minor|patch)
            BUMP="$1"
            shift
            ;;
        -m)
            MESSAGE="$2"
            shift 2
            ;;
        --push)
            PUSH=true
            shift
            ;;
        *)
            echo "Unknown argument: $1" >&2
            echo "Usage: $0 [major|minor|patch] [-m \"message\"] [--push]" >&2
            exit 1
            ;;
    esac
done

if [[ -n "$(git status --porcelain)" ]]; then
    echo "Error: working tree is not clean. Commit or stash changes first." >&2
    exit 1
fi

git fetch --tags --quiet

LATEST_TAG=$(git tag -l --sort=-v:refname | head -1)
LATEST_TAG=${LATEST_TAG:-0.0.0}

if [[ ! "$LATEST_TAG" =~ ^([0-9]+)\.([0-9]+)\.([0-9]+)$ ]]; then
    echo "Error: latest tag '$LATEST_TAG' is not in MAJOR.MINOR.PATCH format." >&2
    exit 1
fi

MAJOR="${BASH_REMATCH[1]}"
MINOR="${BASH_REMATCH[2]}"
PATCH="${BASH_REMATCH[3]}"

case "$BUMP" in
    major)
        MAJOR=$((MAJOR + 1))
        MINOR=0
        PATCH=0
        ;;
    minor)
        MINOR=$((MINOR + 1))
        PATCH=0
        ;;
    patch)
        PATCH=$((PATCH + 1))
        ;;
esac

NEW_TAG="${MAJOR}.${MINOR}.${PATCH}"

if git rev-parse "$NEW_TAG" >/dev/null 2>&1; then
    echo "Error: tag $NEW_TAG already exists." >&2
    exit 1
fi

if [[ -z "$MESSAGE" ]]; then
    MESSAGE="$(git log -1 --format=%s)"
fi

echo "Bumping $LATEST_TAG -> $NEW_TAG ($BUMP)"
git tag -a "$NEW_TAG" -m "$NEW_TAG: $MESSAGE"
echo "Created tag $NEW_TAG"

if [[ "$PUSH" == true ]]; then
    CURRENT_BRANCH=$(git rev-parse --abbrev-ref HEAD)
    git push origin "$CURRENT_BRANCH" --follow-tags
    echo "Pushed $CURRENT_BRANCH and tag $NEW_TAG to origin"
else
    echo "Tag created locally. Push with:"
    echo "  git push origin $(git rev-parse --abbrev-ref HEAD) --follow-tags"
fi
