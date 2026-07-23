#!/usr/bin/env bash
#
# wcd-release.sh - cut a WebChange Detector plugin release.
#
# Bumps the plugin version header (and, for stable releases only, the
# "Stable tag" in README.txt), commits, tags and pushes. The GitHub Actions
# workflow .github/workflows/release.yml takes over from the tag push and
# builds/publishes the distribution zip.
#
# This script NEVER touches the wordpress.org SVN checkout (wp-repo-plugin/).
#
# Usage: ./bin/wcd-release.sh <version> [--dry-run] [--yes]
#
#   <version>   e.g. 4.4.0 or 4.4.0-beta.1
#   --dry-run   validate and report only; changes nothing
#   --yes       skip the interactive confirmation
#
# Portability note: this runs on macOS (BSD userland). No `grep -P`, and no
# in-place `sed -i` (BSD requires a backup suffix, GNU forbids an empty one),
# so rewrites go through a temp file.

# No `pipefail` on purpose: `<cmd> | head -n1` makes the left side die with
# SIGPIPE, which pipefail would turn into a spurious script abort.
set -eu

PLUGIN_FILE="webchangedetector.php"
README_FILE="README.txt"
VERSION_PATTERN='^[0-9]+\.[0-9]+\.[0-9]+(-(alpha|beta|rc)\.[0-9]+)?$'
VERSION_LINE_RE='^[[:space:]]*\*[[:space:]]*Version:[[:space:]]*[^[:space:]]'
STABLE_LINE_RE='^Stable tag:[[:space:]]*[^[:space:]]'

DRY_RUN=0
ASSUME_YES=0
NEW_VERSION=""

usage() {
	cat <<'USAGE'
Usage: ./bin/wcd-release.sh <version> [--dry-run] [--yes]

  <version>   Semantic version, optionally with a pre-release suffix.
              Examples: 4.4.0, 4.4.0-beta.1, 4.4.0-rc.2
  --dry-run   Validate and report only. Changes no files, no git state.
  --yes       Skip the interactive confirmation prompt.
USAGE
}

die() {
	echo "Error: $*" >&2
	exit 1
}

# --- Parse arguments -------------------------------------------------------

while [ "$#" -gt 0 ]; do
	case "$1" in
		--dry-run)
			DRY_RUN=1
			;;
		--yes|-y)
			ASSUME_YES=1
			;;
		-h|--help)
			usage
			exit 0
			;;
		-*)
			usage >&2
			die "Unknown option: $1"
			;;
		*)
			if [ -n "$NEW_VERSION" ]; then
				usage >&2
				die "Unexpected extra argument: $1"
			fi
			NEW_VERSION="$1"
			;;
	esac
	shift
done

if [ -z "$NEW_VERSION" ]; then
	usage >&2
	exit 1
fi

# --- Preconditions ---------------------------------------------------------

[ -f "$PLUGIN_FILE" ] && [ -f "$README_FILE" ] \
	|| die "Run this script from the plugin root (where $PLUGIN_FILE lives)."

command -v git >/dev/null 2>&1 || die "git is required but not installed."
command -v php >/dev/null 2>&1 || die "php is required but not installed."

git rev-parse --is-inside-work-tree >/dev/null 2>&1 \
	|| die "Not inside a git working tree."

if [ -n "$(git status --porcelain)" ]; then
	die "Working tree is not clean. Commit or stash your changes first."
fi

BRANCH="$(git rev-parse --abbrev-ref HEAD)"
if [ "$BRANCH" != "dev" ]; then
	echo "Warning: you are on branch '$BRANCH', not 'dev'."
fi

# --- Validate the requested version ----------------------------------------

if ! printf '%s' "$NEW_VERSION" | grep -Eq "$VERSION_PATTERN"; then
	die "Invalid version '$NEW_VERSION'. Expected e.g. 4.4.0 or 4.4.0-beta.1."
fi

TAG="v${NEW_VERSION}"

if git rev-parse -q --verify "refs/tags/${TAG}" >/dev/null; then
	die "Tag ${TAG} already exists locally."
fi

if [ -n "$(git ls-remote --tags origin "refs/tags/${TAG}" 2>/dev/null)" ]; then
	die "Tag ${TAG} already exists on origin."
fi

CURRENT_VERSION="$(
	sed -E -n 's/^[[:space:]]*\*[[:space:]]*Version:[[:space:]]*([^[:space:]]+).*/\1/p' "$PLUGIN_FILE" | head -n1
)"
[ -n "$CURRENT_VERSION" ] || die "Could not read the 'Version:' header from $PLUGIN_FILE."

# Use PHP's own comparator so the ordering matches what WordPress will do.
if ! php -r 'exit(version_compare($argv[1], $argv[2], ">") ? 0 : 1);' "$NEW_VERSION" "$CURRENT_VERSION"; then
	die "New version ${NEW_VERSION} is not greater than the current version ${CURRENT_VERSION}."
fi

# A pre-release suffix means "Stable tag" must keep pointing at the last stable.
IS_STABLE=1
case "$NEW_VERSION" in
	*-*) IS_STABLE=0 ;;
esac

CURRENT_STABLE_TAG="$(
	sed -E -n 's/^Stable tag:[[:space:]]*([^[:space:]]+).*/\1/p' "$README_FILE" | head -n1
)"

echo
echo "Release plan"
echo "  Branch:          ${BRANCH}"
echo "  Current version: ${CURRENT_VERSION}"
echo "  New version:     ${NEW_VERSION}"
echo "  Tag:             ${TAG}"
if [ "$IS_STABLE" -eq 1 ]; then
	echo "  Stable tag:      ${CURRENT_STABLE_TAG} -> ${NEW_VERSION} (stable release)"
else
	echo "  Stable tag:      ${CURRENT_STABLE_TAG} (unchanged: pre-release)"
fi
echo

if [ "$DRY_RUN" -eq 1 ]; then
	echo "Dry run: nothing was changed."
	exit 0
fi

# --- Rewrite the version headers -------------------------------------------

replace_first_match() {
	# $1 = file, $2 = ERE identifying the line, $3 = sed -E substitution for that line.
	# The substitution is addressed by line number so only the FIRST match is
	# rewritten, even if a similar line appears later in the file.
	local file="$1" line_re="$2" expression="$3" line tmp
	line="$(grep -n -E "$line_re" "$file" | head -n1 | cut -d: -f1)"
	[ -n "$line" ] || die "No line matching /${line_re}/ in ${file}."
	# A template is mandatory for BSD mktemp, so pass one explicitly.
	tmp="$(mktemp "${file}.XXXXXX")"
	# Expand $tmp now, on purpose: a failing sed/cp must not leave the temp file
	# behind in the plugin root (where the dist build would pick it up).
	# shellcheck disable=SC2064
	trap "rm -f '$tmp'" EXIT
	sed -E "${line}${expression}" "$file" >"$tmp"
	# cp (not mv) so the original file permissions are preserved.
	cp "$tmp" "$file"
	rm -f "$tmp"
	trap - EXIT
}

replace_first_match "$PLUGIN_FILE" "$VERSION_LINE_RE" \
	"s|^([[:space:]]*\*[[:space:]]*Version:[[:space:]]*)[^[:space:]]+|\1${NEW_VERSION}|"

if [ "$IS_STABLE" -eq 1 ]; then
	replace_first_match "$README_FILE" "$STABLE_LINE_RE" \
		"s|^(Stable tag:[[:space:]]*)[^[:space:]]+|\1${NEW_VERSION}|"
fi

# Guard against a silently failed rewrite.
WRITTEN_VERSION="$(
	sed -E -n 's/^[[:space:]]*\*[[:space:]]*Version:[[:space:]]*([^[:space:]]+).*/\1/p' "$PLUGIN_FILE" | head -n1
)"
if [ "$WRITTEN_VERSION" != "$NEW_VERSION" ]; then
	git checkout -- "$PLUGIN_FILE" "$README_FILE"
	die "Version rewrite failed (header still reads '${WRITTEN_VERSION}'). Changes reverted."
fi

echo "--- git diff ---"
git --no-pager diff
echo

# --- Confirm ---------------------------------------------------------------

if [ "$ASSUME_YES" -ne 1 ]; then
	printf 'Commit, tag %s and push to origin/%s? [y/N] ' "$TAG" "$BRANCH"
	# Tolerate EOF (no tty): treat it as "no" instead of aborting via set -e
	# with the version rewrite still on disk.
	ANSWER=""
	read -r ANSWER || true
	case "$ANSWER" in
		y|Y|yes|YES) ;;
		*)
			git checkout -- "$PLUGIN_FILE" "$README_FILE"
			echo "Aborted. Changes reverted."
			exit 1
			;;
	esac
fi

# --- Commit, tag, push -----------------------------------------------------

# Recovery instructions for a half-finished release. Which state we are in
# decides what is safe to advise, so the state is always passed in explicitly:
#
#   commit-failed  headers rewritten and staged, no commit, no tag, nothing pushed
#   tag-failed     release commit exists, tag missing, nothing pushed
#   not-pushed     release commit and local tag exist, origin has neither
#   pushed         `git push` succeeded, so origin HAS the commit; only the tag
#                  is missing there (or the verification query itself failed)
#
# Re-running this script for the same version never works once the headers are
# rewritten: it aborts on the existing local tag, and deleting that tag makes it
# abort on the version guard instead, because the header already holds the new
# version. So every state spells out the manual way forward.
#
# Only states where the release commit is provably NOT on origin may suggest
# rewinding the branch. After a successful push, `git reset --hard` would leave
# the branch behind origin and make the next push a non-fast-forward.
recovery_hint() {
	case "$1" in
		commit-failed)
			cat <<HINT

Recovery
--------
The version headers were rewritten and staged, but the release commit was NOT
created. Nothing was tagged and nothing was pushed. Undo the rewrite with:

  git reset HEAD -- ${PLUGIN_FILE} ${README_FILE}
  git checkout -- ${PLUGIN_FILE} ${README_FILE}

Then fix the cause (a pre-commit hook, for example) and run this script again.
HINT
			;;
		tag-failed)
			cat <<HINT

Recovery
--------
The release commit exists on ${BRANCH}, but the tag ${TAG} was NOT created and
nothing was pushed. Do NOT re-run this script for ${NEW_VERSION}. Finish the
release by hand:

  git tag -a ${TAG} -m "Release ${TAG}"
  git push origin ${BRANCH} --follow-tags

To abandon this release instead (only while the release commit is still the
last commit on ${BRANCH} and was not pushed):

  git reset --hard HEAD~1
HINT
			;;
		not-pushed)
			cat <<HINT

Recovery
--------
The release commit and the local tag ${TAG} exist, but origin does not have
them. Do NOT re-run this script for ${NEW_VERSION}. Push the existing state by
hand instead:

  git push origin ${BRANCH}
  git push origin refs/tags/${TAG}

To abandon this release instead (only while the release commit is still the
last commit on ${BRANCH} and was not pushed):

  git tag -d ${TAG}
  git reset --hard HEAD~1
HINT
			;;
		pushed)
			cat <<HINT

Recovery
--------
The release commit IS on origin/${BRANCH} (the push succeeded); only the tag
${TAG} is missing there, or could not be confirmed. Do NOT re-run this script
for ${NEW_VERSION}. Push the tag by hand (a no-op if it is already there):

  git push origin refs/tags/${TAG}
HINT
			;;
		*)
			die "Internal error: recovery_hint called with unknown state '$1'."
			;;
	esac
}

git add "$PLUGIN_FILE" "$README_FILE"

if ! git commit -m "Release ${TAG}"; then
	recovery_hint commit-failed
	die "Could not create the release commit for ${TAG}."
fi

# Annotated (-a), not lightweight: `git push --follow-tags` transfers annotated
# tags only, and release.yml is triggered by the tag push.
if ! git tag -a "$TAG" -m "Release ${TAG}"; then
	recovery_hint tag-failed
	die "Could not create the annotated tag ${TAG}."
fi

if ! git push origin "$BRANCH" --follow-tags; then
	recovery_hint not-pushed
	die "Push to origin/${BRANCH} failed."
fi

# --- Verify the tag actually landed on origin ------------------------------
#
# The tag push is what triggers release.yml. A branch push that silently leaves
# the tag behind would look like success while no release is ever built, so
# confirm the ref exists on the remote before reporting success.
#
# `git ls-remote` exits 0 whether or not the ref matches, so emptiness (not the
# exit status) is the test for "tag not on origin". Keep stderr out of the
# captured output: a warning on a successful query must never be mistaken for a
# matching ref. It is reported separately so an auth/network failure is not
# mislabelled as a missing tag. Either way the check fails closed.

REMOTE_TAG_ERR_FILE="$(mktemp "${TMPDIR:-/tmp}/wcd-release-lsremote.XXXXXX")"
REMOTE_TAG="$(git ls-remote --tags origin "refs/tags/${TAG}" 2>"$REMOTE_TAG_ERR_FILE" || true)"
REMOTE_TAG_ERR="$(cat "$REMOTE_TAG_ERR_FILE")"
rm -f "$REMOTE_TAG_ERR_FILE"

if [ -z "$REMOTE_TAG" ]; then
	recovery_hint pushed
	if [ -n "$REMOTE_TAG_ERR" ]; then
		die "Could not confirm tag ${TAG} on origin (git said: ${REMOTE_TAG_ERR}). If the tag is missing, GitHub Actions will NOT build this release."
	fi
	die "Tag ${TAG} did not reach origin. GitHub Actions will NOT build this release."
fi

echo
echo "Pushed ${TAG} (verified on origin). GitHub Actions is now building and publishing the release zip:"
echo "  https://github.com/WebChangeDetector/webchangedetector/actions"
