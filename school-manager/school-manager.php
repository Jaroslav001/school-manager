<?php

/**
 * Plugin Name: School Manager
 * Plugin URI:  https://example.com
 * Description: Creates a School CPT, allows Admins and Managers to assign schools to users, and restricts access based on assignments. Also customizes the admin menu icon and color for the Schools menu and adds Manager and Lector admin menu items.
 * Version:     1.0.3
 * Author:      Your Name
 * Author URI:  https://example.com
 * License:     GPL2
 * Text Domain: school-manager
 */

if (! defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class School_Manager
{
    public function __construct()
    {
        // Register roles and CPT
        add_action('init', [$this, 'setup_roles'], 20);
        add_action('init', [$this, 'register_school_cpt']);

        // Add admin menu entries for Manager and Lector
        add_action('admin_menu', [$this, 'register_user_menus']);

        // Control user-edit capabilities for Managers
        add_filter('map_meta_cap', [$this, 'filter_map_meta_cap'], 10, 4);

        // User profile metabox for Assigned Schools
        add_action('show_user_profile',   [$this, 'render_user_meta_box']);
        add_action('edit_user_profile',   [$this, 'render_user_meta_box']);
        add_action('personal_options_update', [$this, 'save_user_meta']);
        add_action('edit_user_profile_update', [$this, 'save_user_meta']);

        // Restrict front-end school page access
        add_action('template_redirect', [$this, 'restrict_school_page_access']);

        // Block non-admin users from wp-admin
        add_action('admin_init', [$this, 'block_non_admin_admin_access'], 1);

        // Customize admin menu icon and color
        add_action('admin_head', [$this, 'admin_icon_style']);
    }

    /**
     * Setup custom roles Manager and Lector.
     */
    public function setup_roles()
    {
        if (! get_role('manager')) {
            add_role('manager', 'Manager', ['read' => true, 'edit_users' => true, 'list_users' => true]);
        }
        if (! get_role('lector')) {
            add_role('lector', 'Lector', ['read' => true]);
        }
    }

    /**
     * Register the School CPT.
     */
    public function register_school_cpt()
    {
        register_post_type('school', [
            'label'           => __('Schools', 'school-manager'),
            'public'          => true,
            'has_archive'     => true,
            'rewrite'         => ['slug' => 'schools', 'with_front' => false],
            'show_in_menu'    => true,
            'menu_icon'       => 'dashicons-welcome-learn-more',
            'supports'        => ['title', 'editor', 'thumbnail'],
            'capability_type' => 'post',
            'show_in_rest'    => true,
        ]);
        // Enable Elementor support if available
        if (function_exists('add_post_type_support')) {
            add_post_type_support('school', 'elementor');
        }
    }

    /**
     * Register Manager and Lector menu pages.
     */
    public function register_user_menus()
    {
        // Managers menu
        add_menu_page(
            __('Managers', 'school-manager'),
            __('Managers', 'school-manager'),
            'list_users',
            'users.php?role=manager',
            '',
            'dashicons-businessperson',
            6
        );
        // Lectors menu
        add_menu_page(
            __('Lectors', 'school-manager'),
            __('Lectors', 'school-manager'),
            'list_users',
            'users.php?role=lector',
            '',
            'dashicons-id',
            7
        );
    }

    /**
     * Limit Managers from editing non-Lector profiles.
     */
    public function filter_map_meta_cap($caps, $cap, $user_id, $args)
    {
        if (in_array($cap, ['edit_user', 'edit_users', 'list_users', 'promote_user'], true)) {
            $target = intval($args[0] ?? 0);
            if (user_can($user_id, 'manager') && $target && ! user_can($target, 'lector')) {
                $caps[] = 'do_not_allow';
            }
        }
        return $caps;
    }

    /**
     * Render Assigned Schools metabox on user profiles.
     */
    public function render_user_meta_box($user)
    {
        if (! current_user_can('administrator') && ! (current_user_can('manager') && in_array('lector', (array) $user->roles, true))) {
            return;
        }
        $args = ['post_type' => 'school', 'posts_per_page' => -1];
        if (current_user_can('manager')) {
            $mine = get_user_meta(get_current_user_id(), 'assigned_schools', true) ?: [];
            $args['post__in'] = array_map('intval', $mine);
        }
        $schools  = get_posts($args);
        $assigned = get_user_meta($user->ID, 'assigned_schools', true) ?: [];
        echo '<h2>' . esc_html__('Assigned Schools', 'school-manager') . '</h2>';
        echo '<table class="form-table"><tr><th></th><td>';
        foreach ($schools as $s) {
            $checked = in_array($s->ID, (array) $assigned, true) ? 'checked' : '';
            printf('<label><input type="checkbox" name="assigned_schools[]" value="%d" %s> %s</label><br>', $s->ID, $checked, esc_html(get_the_title($s)));
        }
        echo '</td></tr></table>';
    }

    /**
     * Save Assigned Schools when profile is updated.
     */
    public function save_user_meta($user_id)
    {
        if (! current_user_can('administrator') && ! (current_user_can('manager') && user_can($user_id, 'lector'))) {
            return;
        }
        $schools = array_map('intval', (array) ($_POST['assigned_schools'] ?? []));
        update_user_meta($user_id, 'assigned_schools', $schools);
    }

    /**
     * Restrict front-end access to School posts.
     */
    public function restrict_school_page_access()
    {
        if (is_singular('school') && ! current_user_can('administrator')) {
            $assigned = get_user_meta(get_current_user_id(), 'assigned_schools', true) ?: [];
            $assigned = array_map('intval', (array) $assigned);
            if (! in_array(get_queried_object_id(), $assigned, true)) {
                status_header(403);
                get_header();
                echo '<div class="elementor-message elementor-message-danger" style="margin:2em auto;max-width:600px;">';
                echo esc_html__('You do not have access to this school.', 'school-manager');
                echo '</div>';
                get_footer();
                exit;
            }
        }
    }

    /**
     * Redirect non-admin users away from wp-admin.
     */
    public function block_non_admin_admin_access()
    {
        if (is_admin() && ! current_user_can('administrator') && ! (defined('DOING_AJAX') && DOING_AJAX)) {
            wp_safe_redirect(home_url());
            exit;
        }
    }

    /**
     * Output custom admin CSS to recolor the Schools menu icon.
     */
    public function admin_icon_style()
    {
?>
        <style>
            #menu-post-school .wp-menu-image:before {
                color: #1e73be;
            }
        </style>
<?php
    }
}

// Initialize the plugin
new School_Manager();
