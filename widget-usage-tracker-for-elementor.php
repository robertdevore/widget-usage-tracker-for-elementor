<?php

/**
 * The plugin bootstrap file
 *
 * @link              https://robertdevore.com
 * @since             1.0.0
 * @package           Widget_Usage_Tracker_For_Elementor
 *
 * @wordpress-plugin
 *
 * Plugin Name: Widget Usage Tracker for Elementor
 * Description: Displays all registered Elementor widgets and their usage count on the site, with a modal that shows links to the content where the widgets are found.
 * Plugin URI:  https://github.com/robertdevore/widget-usage-tracker-for-elementor/
 * Version:     1.0.1
 * Author:      Robert DeVore
 * Author URI:  https://robertdevore.com/
 * License:     GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain: widget-usage-tracker-for-elementor
 * Domain Path: /languages
 * Update URI:  https://github.com/robertdevore/widget-usage-tracker-for-elementor/
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Dependency Check: Ensure Elementor or Elementor Pro is active.
 * 
 * @since  1.0.0
 * @return void
 */
function wut_check_elementor_dependency() {
    // Check if Elementor or Elementor Pro is active by verifying if their main classes exist.
    if ( ! class_exists( 'Elementor\Plugin' ) && ! class_exists( 'ElementorPro\Plugin' ) ) {

        // Include the plugin.php file to access deactivate_plugins() function.
        if ( ! function_exists( 'deactivate_plugins' ) ) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }

        // Deactivate the plugin.
        deactivate_plugins( plugin_basename( __FILE__ ) );

        // If the user can activate plugins, display an admin notice.
        if ( current_user_can( 'activate_plugins' ) ) {
            add_action( 'admin_notices', 'wut_elementor_dependency_notice' );
        }

        return;
    }
}
add_action( 'plugins_loaded', 'wut_check_elementor_dependency' );

/**
 * Admin Notice for Missing Elementor Dependency.
 * 
 * @since  1.0.0
 * @return void
 */
function wut_elementor_dependency_notice() {
    ?>
    <div class="notice notice-error is-dismissible">
        <p><?php esc_html_e( 'Widget Usage Tracker for Elementor requires Elementor or Elementor Pro to be installed and active. The plugin has been deactivated.', 'widget-usage-tracker-for-elementor' ); ?></p>
    </div>
    <?php
}

// Set the current version.
define( 'WUT_VERSION', '1.0.1' );

/**
 * Create custom tables to store widget usage data.
 * 
 * @since  1.0.0
 * @return void
 */
function wut_create_custom_tables() {
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();

    // Table for total usage counts.
    $counts_table = $wpdb->prefix . 'wut_widget_usage_counts';
    $counts_sql = "CREATE TABLE $counts_table (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        widget_name VARCHAR(255) NOT NULL,
        count BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
        PRIMARY KEY  (id),
        UNIQUE KEY widget_name (widget_name)
    ) $charset_collate;";

    // Table for per-post usage data.
    $posts_table = $wpdb->prefix . 'wut_widget_usage_posts';
    $posts_sql = "CREATE TABLE $posts_table (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        widget_name VARCHAR(255) NOT NULL,
        post_id BIGINT(20) UNSIGNED NOT NULL,
        PRIMARY KEY  (id),
        KEY widget_name (widget_name),
        KEY post_id (post_id)
    ) $charset_collate;";

    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
    dbDelta( $counts_sql );
    dbDelta( $posts_sql );
}
register_activation_hook( __FILE__, 'wut_create_custom_tables' );

/**
 * Plugin Update Checker.
 * 
 * @since  1.0.0
 */
require 'vendor/plugin-update-checker/plugin-update-checker.php';
use YahnisElsts\PluginUpdateChecker\v5\PucFactory;

$myUpdateChecker = PucFactory::buildUpdateChecker(
    'https://github.com/robertdevore/widget-usage-tracker-for-elementor/',
    __FILE__,
    'widget-usage-tracker-for-elementor'
);

// Set the branch that contains the stable release.
$myUpdateChecker->setBranch( 'main' );

// Include WP_List_Table if it doesn't already exist.
if ( ! class_exists( 'WP_List_Table' ) ) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * Elementor_Widget_Usage_List_Table Class.
 *
 * Creates a WP_List_Table to display Elementor widgets and their usage details.
 */
class Elementor_Widget_Usage_List_Table extends WP_List_Table {

    private $widget_data = [];

    /**
     * Constructor for Elementor_Widget_Usage_List_Table.
     * 
     * @since 1.0.0
     */
    public function __construct() {
        parent::__construct(
            [
                'singular' => esc_html__( 'Widget', 'widget-usage-tracker-for-elementor' ),
                'plural'   => esc_html__( 'Widgets', 'widget-usage-tracker-for-elementor' ),
                'ajax'     => false,
            ]
        );
    }

    /**
     * Prepares the table items.
     * 
     * @since  1.0.0
     * @return void
     */
    public function prepare_items() {
        $this->widget_data = $this->get_widget_data();

        $columns = $this->get_columns();

        $this->_column_headers = [ $columns, [], $this->get_sortable_columns() ];
        $this->items           = $this->widget_data;
    }

    /**
     * Defines the table columns.
     * 
     * @since  1.0.0
     * @return array
     */
    public function get_columns() {
        return [
            'name'    => esc_html__( 'Widget Type', 'widget-usage-tracker-for-elementor' ),
            'count'   => esc_html__( 'Usage Count', 'widget-usage-tracker-for-elementor' ),
            'details' => esc_html__( 'Details', 'widget-usage-tracker-for-elementor' ),
        ];
    }

    /**
     * Renders the column data for each row.
     *
     * @param array  $item        The current item.
     * @param string $column_name The name of the column.
     * 
     * @since  1.0.0
     * @return mixed
     */
    public function column_default( $item, $column_name ) {
        switch ( $column_name ) {
            case 'name':
                return esc_html( $item['widget_name'] );
            case 'count':
                return esc_html( $item['count'] );
            case 'details':
                return sprintf(
                    '<a href="#" class="view-details" data-widget-name="%s">%s</a>',
                    esc_attr( $item['widget_name'] ),
                    esc_html__( 'View Details', 'widget-usage-tracker-for-elementor' )
                );
            default:
                return print_r( $item, true );
        }
    }

    /**
     * Retrieves data for all registered Elementor widgets with usage count greater than zero.
     *
     * @since 1.0.0
     * @return array|mixed|object
     */
    private function get_widget_data() {
        if ( ! did_action( 'elementor/loaded' ) ) {
            return [];
        }

        global $wpdb;
        $counts_table = $wpdb->prefix . 'wut_widget_usage_counts';

        // Fetch all widgets with count > 0.
        $results = $wpdb->get_results(
            "SELECT widget_name, count FROM $counts_table WHERE count > 0",
            ARRAY_A
        );

        return $results ? $results : [];
    }
}

/**
 * Adds a top-level admin menu page for Widget Usage Tracker.
 * 
 * @since  1.0.0
 * @return void
 */
function wut_add_top_level_menu() {
    add_menu_page(
        esc_html__( 'Widget Usage Tracker', 'widget-usage-tracker-for-elementor' ),
        esc_html__( 'Widget Tracker', 'widget-usage-tracker-for-elementor' ),
        'manage_options',
        'widget-finder',
        'wut_display_widget_finder_page',
        'dashicons-search',
        999
    );
}
add_action( 'admin_menu', 'wut_add_top_level_menu' );

/**
 * Displays the Widget Usage Tracker admin page content.
 * 
 * @since  1.0.0
 * @return void
 */
function wut_display_widget_finder_page() {
    if ( ! class_exists( 'Elementor_Widget_Usage_List_Table' ) ) {
        return;
    }

    // Create an instance of the list table.
    $list_table = new Elementor_Widget_Usage_List_Table();
    $list_table->prepare_items();
    ?>
    <div class="wrap">
        <h1>
            <?php esc_html_e( 'Widget Usage Tracker for Elementor', 'widget-usage-tracker-for-elementor' ); ?>
            <?php
                echo ' <a id="wut-support" class="button" style="margin-left: 10px;" href="https://robertdevore.com/contact/" target="_blank"><span class="dashicons dashicons-format-chat"></span> <span class="wut-support-text">' . esc_html__( 'Support', 'widget-usage-tracker-for-elementor' ) . '</span></a>';
            ?>
        </h1>
        <form method="post">
            <?php $list_table->display(); ?>
        </form>
    </div>

    <div id="usage-details-modal" style="display:none;">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h2><?php esc_html_e( 'Widget Usage Details', 'widget-usage-tracker-for-elementor' ); ?></h2>
            <p id="usage-header"></p>
            <ul id="usage-details-list"></ul>
        </div>
    </div>
    <?php
}

/**
 * Enqueue JavaScript and CSS for the modal and AJAX functionality.
 * 
 * @since 1.0.0
 * @param string $hook The current admin page.
 */
function wut_enqueue_admin_scripts( $hook ) {
    // Check if we're on the Widget Usage Tracker admin page.
    if ( 'toplevel_page_widget-finder' !== $hook ) {
        return;
    }

    // Enqueue the CSS file.
    wp_enqueue_style( 'widget-usage-tracker-css', plugin_dir_url( __FILE__ ) . 'assets/css/wut-styles.css', [], WUT_VERSION );

    // Enqueue the JavaScript file.
    wp_enqueue_script( 'widget-usage-tracker-js', plugin_dir_url( __FILE__ ) . 'assets/js/wut-scripts.js', [ 'jquery' ], WUT_VERSION, true );

    // Localize the script with AJAX URL and nonce.
    wp_localize_script( 'widget-usage-tracker-js', 'WidgetUsageTracker', [
        'ajax_url' => admin_url( 'admin-ajax.php' ),
        'nonce'    => wp_create_nonce( 'widget_usage_tracker_nonce' ),
    ]);
}
add_action( 'admin_enqueue_scripts', 'wut_enqueue_admin_scripts' );

/**
 * AJAX callback to get pages where the widget is used.
 *
 * @since 1.0.0
 */
function wut_ajax_get_widget_usage_details() {
    // Check the nonce for security.
    check_ajax_referer( 'widget_usage_tracker_nonce', 'nonce' );

    // Sanitize the widget name received from the AJAX request.
    $widget_name = sanitize_text_field( $_POST['widget_name'] );

    global $wpdb;
    $posts_table = $wpdb->prefix . 'wut_widget_usage_posts';

    // Fetch post IDs where the widget is used.
    $post_ids = $wpdb->get_col(
        $wpdb->prepare(
            "SELECT post_id FROM $posts_table WHERE widget_name = %s",
            $widget_name
        )
    );

    // Initialize pages array.
    $pages = [];

    if ( ! empty( $post_ids ) ) {
        // Define batch size.
        $batch_size = 100; // Adjust as needed.

        // Total number of posts.
        $total_posts = count( $post_ids );

        // Calculate total number of batches.
        $total_batches = ceil( $total_posts / $batch_size );

        for ( $batch = 0; $batch < $total_batches; $batch++ ) {
            // Slice the post_ids array for the current batch.
            $batch_posts = array_slice( $post_ids, $batch * $batch_size, $batch_size );

            // Fetch posts in bulk.
            $posts = get_posts( [
                'post__in'       => $batch_posts,
                'post_type'      => 'any',
                'post_status'    => 'publish',
                'numberposts'    => -1,
                'fields'         => 'ids',
            ] );

            foreach ( $posts as $post_id ) {
                $pages[] = [
                    'title' => get_the_title( $post_id ),
                    'url'   => get_permalink( $post_id ),
                ];
            }

            // Optional: Clear memory.
            unset( $batch_posts, $posts );
        }
    }

    // Log the number of unique pages sent.
    error_log( 'Widget Usage Tracker: Sending ' . count( $pages ) . ' unique pages to frontend for widget "' . $widget_name . '"' );

    wp_send_json_success( $pages );
}
add_action( 'wp_ajax_get_widget_usage_details', 'wut_ajax_get_widget_usage_details' );

/**
 * Schedule a cron event upon plugin activation and trigger it immediately.
 *
 * @since  1.0.0
 * @return void
 */
function wut_schedule_cron_event() {
    if ( ! wp_next_scheduled( 'wut_update_widget_usage_counts' ) ) {
        // Schedule the event to run hourly.
        wp_schedule_event( time(), 'hourly', 'wut_update_widget_usage_counts' );
    }

    // Trigger the cron job immediately.
    if ( function_exists( 'wut_update_widget_usage_counts' ) ) {
        do_action( 'wut_update_widget_usage_counts' );
    }
}
register_activation_hook( __FILE__, 'wut_schedule_cron_event' );

/**
 * Clear scheduled cron event upon plugin deactivation.
 *
 * @since  1.0.0
 * @return void
 */
function wut_clear_cron_event() {
    $timestamp = wp_next_scheduled( 'wut_update_widget_usage_counts' );
    if ( $timestamp ) {
        wp_unschedule_event( $timestamp, 'wut_update_widget_usage_counts' );
    }
}
register_deactivation_hook( __FILE__, 'wut_clear_cron_event' );

/**
 * Cron callback to update widget usage counts and per-post usage in custom tables.
 *
 * @since  1.0.0
 * @return void
 */
function wut_update_widget_usage_counts() {
    global $wpdb;
    $counts_table = $wpdb->prefix . 'wut_widget_usage_counts';
    $posts_table  = $wpdb->prefix . 'wut_widget_usage_posts';

    if ( ! did_action( 'elementor/loaded' ) ) {
        return;
    }

    $widgets_manager = \Elementor\Plugin::instance()->widgets_manager;
    $widgets         = $widgets_manager->get_widget_types();

    foreach ( $widgets as $widget_name => $widget_instance ) {
        // Calculate widget usage count and get post_ids.
        $usage_data = wut_calculate_widget_usage( $widget_instance->get_name() );

        // Update the counts table.
        $wpdb->replace(
            $counts_table,
            [
                'widget_name' => $widget_instance->get_name(),
                'count'       => $usage_data['count'],
            ],
            [
                '%s',
                '%d',
            ]
        );

        // Debugging: Log the usage data.
        error_log( 'Widget Usage Tracker: Widget "' . $widget_name . '" has count ' . $usage_data['count'] . ' and post_ids: ' . implode( ', ', $usage_data['post_ids'] ) );

        // Update the posts table.
        if ( ! empty( $usage_data['post_ids'] ) ) {
            // First, delete existing entries for this widget.
            $wpdb->delete(
                $posts_table,
                [ 'widget_name' => $widget_name ],
                [ '%s' ]
            );

            // Prepare data for bulk insertion.
            $values = [];
            foreach ( $usage_data['post_ids'] as $post_id ) {
                $values[] = $wpdb->prepare( "(%s, %d)", $widget_instance->get_name(), $post_id );
            }

            if ( ! empty( $values ) ) {
                $sql = "INSERT INTO $posts_table (widget_name, post_id) VALUES " . implode( ', ', $values );
                $wpdb->query( $sql );

                // Debugging: Log the SQL query.
                error_log( 'Widget Usage Tracker: Executed SQL - ' . $sql );
            }
        }
    }
}
add_action( 'wut_update_widget_usage_counts', 'wut_update_widget_usage_counts' );

/**
 * Calculates the total usage of a specific Elementor widget across all published posts.
 * Returns both the count and the list of post_ids where the widget is used.
 *
 * @param string $widget_name The name of the widget to count.
 * 
 * @since  1.0.0
 * @return array ['count' => int, 'post_ids' => array]
 */
function wut_calculate_widget_usage( $widget_name ) {
    global $wpdb;

    // Initialize count and post_ids.
    $total_count = 0;
    $post_ids    = [];

    // Define batch size.
    $batch_size = apply_filters( 'wut_calculate_widget_usage_batch_size', 50 );

    // Get total number of posts to process.
    $total_posts = $wpdb->get_var(
        "SELECT COUNT(p.ID) FROM {$wpdb->prefix}posts p
         JOIN {$wpdb->prefix}postmeta pm ON p.ID = pm.post_id
         WHERE pm.meta_key = '_elementor_data' 
         AND p.post_status = 'publish'"
    );

    // Calculate total number of batches.
    $total_batches = ceil( $total_posts / $batch_size );

    for ( $batch = 0; $batch < $total_batches; $batch++ ) {
        // Fetch a batch of post IDs.
        $post_ids_batch = $wpdb->get_col(
            $wpdb->prepare(
                "SELECT p.ID FROM {$wpdb->prefix}posts p
                 JOIN {$wpdb->prefix}postmeta pm ON p.ID = pm.post_id
                 WHERE pm.meta_key = '_elementor_data' 
                 AND p.post_status = 'publish'
                 LIMIT %d OFFSET %d",
                $batch_size,
                $batch * $batch_size
            )
        );

        foreach ( $post_ids_batch as $post_id ) {
            $elementor_data = get_post_meta( $post_id, '_elementor_data', true );

            if ( ! empty( $elementor_data ) ) {
                $data = json_decode( $elementor_data, true );

                if ( is_array( $data ) ) {
                    foreach ( $data as $widget ) {
                        if ( isset( $widget['widgetType'] ) && $widget['widgetType'] === $widget_name ) {
                            $total_count++;
                            $post_ids[] = $post_id;
                        }

                        // Recursively check for nested widgets (e.g., within sections or columns).
                        if ( isset( $widget['elements'] ) && is_array( $widget['elements'] ) ) {
                            $nested = wut_count_nested_widgets( $widget['elements'], $widget_name, $post_id );
                            $total_count += $nested['count'];
                            $post_ids = array_merge( $post_ids, $nested['post_ids'] );
                        }
                    }
                }
            }
        }

        // Optional: Clear memory.
        unset( $post_ids_batch );
    }

    // Remove duplicate post_ids.
    $post_ids = array_unique( $post_ids );

    // Debugging: Log the collected post IDs
    error_log( 'Widget Usage Tracker: Widget "' . $widget_name . '" used in posts: ' . implode( ', ', $post_ids ) );

    return [
        'count'    => $total_count,
        'post_ids' => $post_ids,
    ];
}

/**
 * Recursively counts widget instances in nested elements and collects post_ids.
 *
 * @param array  $elements    The nested elements to search.
 * @param string $widget_name The name of the widget to count.
 * @param int    $post_id     The ID of the post being processed.
 *
 * @return array ['count' => int, 'post_ids' => array]
 */
function wut_count_nested_widgets( $elements, $widget_name, $post_id ) {
    $count    = 0;
    $post_ids = [];

    foreach ( $elements as $element ) {
        if ( isset( $element['widgetType'] ) && $element['widgetType'] === $widget_name ) {
            $count++;
            $post_ids[] = $post_id; // Record the post ID when the widget is found
        }

        if ( isset( $element['elements'] ) && is_array( $element['elements'] ) ) {
            $nested = wut_count_nested_widgets( $element['elements'], $widget_name, $post_id );
            $count += $nested['count'];
            $post_ids = array_merge( $post_ids, $nested['post_ids'] );
        }
    }

    return [
        'count'    => $count,
        'post_ids' => $post_ids,
    ];
}

/**
 * Hook into post save to update widget usage counts and post associations.
 *
 * @param int $post_id The ID of the post being saved.
 * 
 * @since 1.0.0
 */
function wut_update_widget_usage_on_save( $post_id ) {
    // Avoid recursion and unnecessary processing.
    if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
        return;
    }

    // Verify if Elementor data exists.
    $elementor_data = get_post_meta( $post_id, '_elementor_data', true );
    if ( empty( $elementor_data ) ) {
        return;
    }

    $data = json_decode( $elementor_data, true );
    if ( ! is_array( $data ) ) {
        return;
    }

    // Get all widgets in the post.
    $widgets = [];
    foreach ( $data as $widget ) {
        if ( isset( $widget['widgetType'] ) ) {
            $widgets[] = $widget['widgetType'];

            // Handle nested widgets.
            if ( isset( $widget['elements'] ) && is_array( $widget['elements'] ) ) {
                $nested = wut_count_nested_widgets( $widget['elements'], $widget['widgetType'], $post_id );
                $widgets = array_merge( $widgets, $nested['post_ids'] );
            }
        }
    }

    // Count widgets.
    $widget_counts = array_count_values( $widgets );

    // Update custom tables.
    global $wpdb;
    $counts_table = $wpdb->prefix . 'wut_widget_usage_counts';
    $posts_table  = $wpdb->prefix . 'wut_widget_usage_posts';

    foreach ( $widget_counts as $widget_name => $count ) {
        // Update the total count.
        $wpdb->replace(
            $counts_table,
            [
                'widget_name' => $widget_name,
                'count'       => wut_get_total_usage_count( $widget_name ),
            ],
            [
                '%s',
                '%d',
            ]
        );

        // Delete existing associations for this widget and post.
        $wpdb->delete(
            $posts_table,
            [
                'widget_name' => $widget_name,
                'post_id'     => $post_id,
            ],
            [
                '%s',
                '%d',
            ]
        );

        // Insert the new association.
        $wpdb->insert(
            $posts_table,
            [
                'widget_name' => $widget_name,
                'post_id'     => $post_id,
            ],
            [
                '%s',
                '%d',
            ]
        );
    }
}
add_action( 'save_post', 'wut_update_widget_usage_on_save' );

/**
 * Recursively retrieves widget types from nested Elementor elements.
 *
 * @param array $elements The nested elements to search.
 * @return array The list of widget types found within the elements.
 *
 * @since 1.0.0
 */
function wut_get_nested_widgets( $elements ) {
    $widgets = [];

    foreach ( $elements as $element ) {
        // Check if the element is a widget and has a 'widgetType'.
        if ( isset( $element['widgetType'] ) ) {
            $widgets[] = $element['widgetType'];
        }

        // If the element has nested elements, recursively retrieve their widget types.
        if ( isset( $element['elements'] ) && is_array( $element['elements'] ) ) {
            $widgets = array_merge( $widgets, wut_get_nested_widgets( $element['elements'] ) );
        }
    }

    return $widgets;
}

/**
 * Retrieves the total usage count for a widget by querying the posts table.
 *
 * @param string $widget_name The name of the widget.
 * 
 * @since  1.0.0
 * @return int The total usage count.
 */
function wut_get_total_usage_count( $widget_name ) {
    global $wpdb;
    $posts_table = $wpdb->prefix . 'wut_widget_usage_posts';

    $count = $wpdb->get_var(
        $wpdb->prepare(
            "SELECT COUNT(*) FROM $posts_table WHERE widget_name = %s",
            $widget_name
        )
    );

    return $count ? (int) $count : 0;
}

/**
 * Uninstall callback to clean up plugin data.
 *
 * This function will be called when the plugin is uninstalled.
 *
 * @since  1.0.0
 * @return void
 */
function wut_uninstall() {
    global $wpdb;
    
    // Define the table names with the correct prefix.
    $counts_table = $wpdb->prefix . 'wut_widget_usage_counts';
    $posts_table  = $wpdb->prefix . 'wut_widget_usage_posts';
    
    // Delete the custom tables.
    $wpdb->query( "DROP TABLE IF EXISTS `$counts_table`;" );
    $wpdb->query( "DROP TABLE IF EXISTS `$posts_table`;" );
    
    // Clear the scheduled cron event.
    wp_clear_scheduled_hook( 'wut_update_widget_usage_counts' );
}
register_uninstall_hook( __FILE__, 'wut_uninstall' );
