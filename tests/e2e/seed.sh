#!/usr/bin/env bash
#
# Test content for the end-to-end suite, so tests that need it run instead
# of skipping on a fresh install: a submenu in the primary menu, and a post
# with a featured image, a category and a tag. Runs inside the wordpress
# container as the Apache user, after local/bin/install.sh:
#
#   docker compose -f docker-compose.test.yml exec -T -u www-data wordpress bash -s < tests/e2e/seed.sh
#
# Idempotent. Never run it against a real site: it adds content.

set -euo pipefail

if [ "$(wp option get whalepress_e2e_seeded 2>/dev/null || true)" = "1" ]; then
	echo "End-to-end test content is already seeded."
	exit 0
fi

# A child item under the first top-level primary menu item that links to a
# page, for the submenu disclosure tests.
menu=$(wp menu list --fields=term_id,locations --format=csv | awk -F, '$2 ~ /primary/ { print $1; exit }')
if [ -z "$menu" ]; then
	echo "No menu is assigned to the primary location; run local/bin/install.sh first." >&2
	exit 1
fi
parent=$(wp menu item list "$menu" --fields=db_id,type,menu_item_parent --format=csv | awk -F, '$2 == "post_type" && $3 == 0 { print $1; exit }')
team=$(wp post create --post_type=page --post_status=publish --post_title='Team' --porcelain)
wp menu item add-post "$menu" "$team" --parent-id="$parent"

# The newest post, so it leads the front page's post list, with a featured
# image (the theme's screenshot, which every image ships), a category and a tag.
post=$(wp post create \
	--post_status=publish \
	--post_author="$(wp user list --role=administrator --field=ID --number=1)" \
	--post_title='A post with a featured image' \
	--post_content='<!-- wp:paragraph --><p>Seeded for the end-to-end tests.</p><!-- /wp:paragraph -->' \
	--post_category="$(wp term create category 'Testing' --porcelain)" \
	--tags_input='Seeded' \
	--porcelain)

screenshot="$(wp eval 'echo get_stylesheet_directory();')/screenshot.png"
wp media import "$screenshot" --post_id="$post" --featured_image --alt='The theme screenshot' --porcelain

wp option update whalepress_e2e_seeded 1
echo "Seeded end-to-end test content."
