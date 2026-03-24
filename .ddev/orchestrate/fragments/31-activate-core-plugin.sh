#!/usr/bin/env bash

# Activate the core classic-to-gutenberg plugin.
# Must run after the project activation (30-activate-project.sh).

CORE_PLUGIN="classic-to-gutenberg"

echo "Activating core plugin: ${CORE_PLUGIN}..."

if wp plugin is-installed "$CORE_PLUGIN" --path="${WP_PATH}" 2>/dev/null; then
    if [ "${WP_MULTISITE}" = "1" ]; then
        wp plugin activate "$CORE_PLUGIN" --network --path="${WP_PATH}" 2>/dev/null || true
    else
        wp plugin activate "$CORE_PLUGIN" --path="${WP_PATH}" 2>/dev/null || true
    fi
else
    echo "Core plugin '${CORE_PLUGIN}' not found. Check 28-link-core-plugin.sh."
fi
