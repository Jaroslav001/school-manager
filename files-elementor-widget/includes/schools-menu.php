<?php
// includes/schools-menu.php
if (! defined('ABSPATH')) {
    exit;
}

add_filter('wp_nav_menu_objects', 'fev_inject_user_schools', 20, 2);
function fev_inject_user_schools($items, $args)
{
    if (! is_user_logged_in()) {
        return $items;
    }

    $parent_id = 0;

    // 1) Find the “Schools” parent item and mark it has-children
    foreach ($items as $item) {
        if (trim($item->title) === 'Schools' && (int) $item->menu_item_parent === 0) {
            $parent_id = $item->ID;
            if (! in_array('menu-item-has-children', $item->classes, true)) {
                $item->classes[] = 'menu-item-has-children';
            }
        }
    }

    // 2) If no “Schools” item, bail
    if (! $parent_id) {
        return $items;
    }

    // 3) Determine the highest existing menu_order under “Schools”
    $max_order = 0;
    foreach ($items as $item) {
        if ((int) $item->menu_item_parent === $parent_id) {
            $max_order = max($max_order, (int) $item->menu_order);
        }
    }

    // 4) Fetch assigned school IDs from user meta
    $assigned_ids = get_user_meta(get_current_user_id(), 'assigned_schools', true);
    if (! is_array($assigned_ids) || empty($assigned_ids)) {
        return $items;
    }

    // 5) Load the actual WP_Post objects
    $schools = array_map('get_post', array_map('intval', $assigned_ids));
    $schools = array_filter($schools); // remove false/null if any ID invalid

    // 6) Sort by creation date (oldest first). For newest first, swap $b/$a.
    usort($schools, function ($a, $b) {
        return strcmp($a->post_date, $b->post_date);
    });

    // 7) Build the new items list, injecting sorted CPTs right after the parent
    $new_items = [];
    foreach ($items as $item) {
        $new_items[] = $item;

        if ($item->ID === $parent_id) {
            foreach ($schools as $school) {
                $max_order++;

                $clone = clone $item;
                $clone->ID               = $parent_id * 1000 + $school->ID;
                $clone->db_id            = 0;
                $clone->title            = get_the_title($school);
                $clone->url              = get_permalink($school);
                $clone->menu_item_parent = $parent_id;
                $clone->menu_order       = $max_order;
                $clone->classes          = ['menu-item', 'menu-item-school'];

                $new_items[] = $clone;
            }
        }
    }

    return $new_items;
}
