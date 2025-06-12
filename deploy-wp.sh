#!/usr/bin/env bash

# --------------------------------------------------
# WebChange Detector – WordPress.org deployment helper
# --------------------------------------------------
# 1. Configure SVN_PATH below (absolute path to your plugin SVN checkout).
# 2. Run this script from the *plugin root* directory (where webchangedetector.php resides).
#    The script will:
#       • Ask for the new version (shows current one).
#       • Update the version in plugin header & readme Stable tag.
#       • Copy plugin files (excluding development artefacts) to SVN /tags/<version> and /trunk.
#       • NOT run any SVN commands, preparing files for manual commit.
# --------------------------------------------------

set -euo pipefail

# ======= USER-CONFIGURABLE SECTION =================
# Absolute path to the local SVN checkout of the plugin repository
SVN_PATH="/Users/macmike/htdocs/wp-repository"  # Path to local SVN checkout
# ===================================================

# ---------- Helper functions -----------------------
red()   { printf "\e[31m%s\e[0m\n" "$1"; }
green() { printf "\e[32m%s\e[0m\n" "$1"; }
warn()  { printf "\e[33m%s\e[0m\n" "$1"; }

require_tool() {
  command -v "$1" >/dev/null 2>&1 || { red "Error: '$1' is not installed."; exit 1; }
}

# ---------- Pre-flight checks ----------------------
for tool in rsync sed grep; do
  require_tool "$tool"
done

# Ensure SVN directory exists for sync preparation (but we won't run SVN commands)
if [[ ! -d "$SVN_PATH" ]]; then
  red "SVN_PATH directory does not exist: $SVN_PATH"; exit 1;
fi

green "Preparing files in local SVN checkout at: $SVN_PATH"

SCRIPT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
PLUGIN_MAIN="${SCRIPT_DIR}/webchangedetector.php"
README_FILE="${SCRIPT_DIR}/readme.txt"

if [[ ! -f "$PLUGIN_MAIN" ]]; then
  red "Could not find main plugin file at $PLUGIN_MAIN"; exit 1;
fi

# ---------- Fetch current version ------------------
# Use a more portable grep/sed combo to get the version number from the plugin header
CURRENT_VERSION=$(grep -m 1 "Version:" "$PLUGIN_MAIN" | sed -e 's/.*Version:[ \t]*//')
[[ -z "$CURRENT_VERSION" ]] && { red "Could not parse current version from $PLUGIN_MAIN"; exit 1; }

echo
warn "Current plugin version: $CURRENT_VERSION"
read -rp "Enter NEW version to deploy: " NEW_VERSION
[[ -z "$NEW_VERSION" ]] && { red "No version entered. Aborting."; exit 1; }

green "Updating version strings to $NEW_VERSION ..."

# ---------- Update plugin and readme files --------------
# Use a function to handle sed's cross-platform differences for in-place editing.
# Using Basic Regular Expressions (BRE) for better portability.
run_sed_inplace() {
  local expression="$1"
  local file="$2"

  if sed --version >/dev/null 2>&1; then
    # GNU sed (Linux)
    sed -i "$expression" "$file"
  else
    # BSD/macOS sed requires a space after -i for no-backup edits
    sed -i '' "$expression" "$file"
  fi
}

# BRE expression to find and replace the plugin version line
PLUGIN_EXPRESSION="s/^\([[:space:]]*\* *Version:[[:space:]]*\).*/\1$NEW_VERSION/"
run_sed_inplace "$PLUGIN_EXPRESSION" "$PLUGIN_MAIN"

# BRE expression to find and replace the readme stable tag
if [[ -f "$README_FILE" ]]; then
  README_EXPRESSION="s/^\(Stable tag:[[:space:]]*\).*/\1$NEW_VERSION/"
  run_sed_inplace "$README_EXPRESSION" "$README_FILE"
else
  warn "readme.txt not found – skipping Stable tag update."
fi

green "Version strings updated."

# ---------- Prepare SVN directories ---------------
TAG_DIR="$SVN_PATH/tags/${NEW_VERSION}"
TRUNK_DIR="$SVN_PATH/trunk"

# Ensure clean tag directory
if [[ -d "$TAG_DIR" ]]; then
  warn "Tag $NEW_VERSION already exists – removing"
  rm -rf "$TAG_DIR"
fi
mkdir -p "$TAG_DIR"

# ---------- Rsync options --------------------------
EXCLUDES=(
  "--exclude=composer.json"
  "--exclude=composer.lock"
  "--exclude=.gitignore"
  "--exclude=.eslintrc.js"
  "--exclude=vendor"
  "--exclude=vender"
  "--exclude=lint-check.sh"
  "--exclude=deploy-wp.sh"
  "--exclude=.git"
  "--exclude=.DS_Store"
)

# ---------- Copy files to tag ----------------------
rsync -av "${EXCLUDES[@]}" "${SCRIPT_DIR}/" "$TAG_DIR/"

green "Tag directory populated: $TAG_DIR"

# ---------- Copy to trunk (clean first) ------------
rsync -av --delete "${EXCLUDES[@]}" "${SCRIPT_DIR}/" "$TRUNK_DIR/"

green "Trunk directory updated: $TRUNK_DIR"

echo
warn "Sync complete. Review changes in SmartSVN, add/commit, and push when ready." 