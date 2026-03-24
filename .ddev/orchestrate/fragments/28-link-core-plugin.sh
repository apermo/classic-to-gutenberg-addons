#!/usr/bin/env bash

# Symlink the core classic-to-gutenberg plugin from vendor/ into
# wp-content/plugins/ so WordPress can discover it alongside the
# addons plugin.

CORE_PLUGIN="classic-to-gutenberg"
CORE_SOURCE="${DOCROOT}/../../vendor/apermo/${CORE_PLUGIN}"
LINK_TARGET="${WP_PATH}/wp-content/plugins/${CORE_PLUGIN}"

if [ ! -d "$CORE_SOURCE" ]; then
    echo "Core plugin not found at ${CORE_SOURCE}. Run composer install first."
    return 1
fi

if [ -L "$LINK_TARGET" ]; then
    echo "Core plugin symlink already exists: ${LINK_TARGET}"
    return 0
fi

if [ -d "$LINK_TARGET" ]; then
    echo "Core plugin directory already exists: ${LINK_TARGET}. Skipping."
    return 0
fi

echo "Linking core plugin: ${CORE_PLUGIN}"
ln -sf "$(realpath "$CORE_SOURCE")" "$LINK_TARGET"
echo "Core plugin linked."
