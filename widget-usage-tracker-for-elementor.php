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
 * Version:     1.0.0
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

// Plugin Update Checker.
require 'vendor/plugin-update-checker/plugin-update-checker.php';
use YahnisElsts\PluginUpdateChecker\v5\PucFactory;

$myUpdateChecker = PucFactory::buildUpdateChecker(
	'https://github.com/robertdevore/widget-usage-tracker-for-elementor/',
	__FILE__,
	'widget-usage-tracker-for-elementor'
);

// Set the branch that contains the stable release.
$myUpdateChecker->setBranch( 'main' );

// Set the current version.
define( 'WUT_VERSION', '1.0.0' );

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
     * @since  1.0.0
     * @return void
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
     * @return array Column headers for the table.
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
     * @return string The content for the column.
     */
    public function column_default( $item, $column_name ) {
        switch ( $column_name ) {
            case 'name':
                return esc_html( $item[ $column_name ] );
            case 'count':
                return esc_html( $item[ $column_name ] );
            case 'details':
                return sprintf(
                    '<a href="#" class="view-details" data-widget-name="%s">%s</a>',
                    esc_attr( $item['name'] ),
                    esc_html__( 'View Details', 'widget-usage-tracker-for-elementor' )
                );
            default:
                return print_r( $item, true );
        }
    }

    /**
     * Retrieves data for all registered Elementor widgets with usage count greater than zero.
     *
     * @since  1.0.0
     * @return array List of widgets with usage details.
     */
    private function get_widget_data() {
        if ( ! did_action( 'elementor/loaded' ) ) {
            return [];
        }

        $widgets_manager = \Elementor\Plugin::instance()->widgets_manager;
        $widgets         = $widgets_manager->get_widget_types();

        $widget_data = [];

        // Loop through the registered widgets.
        foreach ( $widgets as $widget_name => $widget_instance ) {
            $count = $this->count_widget_usage( $widget_instance->get_name() );
            if ( $count > 0 ) {
                $widget_data[] = [
                    'name'  => $widget_instance->get_name(),
                    'count' => $count,
                ];
            }
        }

        return $widget_data;
    }

    /**
     * Counts the total usage of a specific Elementor widget across all published posts.
     *
     * @param string $widget_name The name of the widget to count.
     * 
     * @since  1.0.0
     * @return int The total number of times the widget is used.
     */
    private function count_widget_usage( $widget_name ) {
        global $wpdb;

        // Fetch all published post IDs that have Elementor data.
        $post_ids = $wpdb->get_col(
            $wpdb->prepare(
                "SELECT p.ID FROM {$wpdb->prefix}posts p
                 JOIN {$wpdb->prefix}postmeta pm ON p.ID = pm.post_id
                 WHERE pm.meta_key = '_elementor_data' 
                 AND p.post_status = 'publish'"
            )
        );

        $total_count = 0;

        foreach ( $post_ids as $post_id ) {
            $elementor_data = get_post_meta( $post_id, '_elementor_data', true );

            if ( ! empty( $elementor_data ) ) {
                $data = json_decode( $elementor_data, true );

                if ( is_array( $data ) ) {
                    foreach ( $data as $widget ) {
                        if ( isset( $widget['widgetType'] ) && $widget['widgetType'] === $widget_name ) {
                            $total_count++;
                        }

                        // Recursively check for nested widgets (e.g., within sections or columns).
                        if ( isset( $widget['elements'] ) && is_array( $widget['elements'] ) ) {
                            $total_count += $this->count_nested_widgets( $widget['elements'], $widget_name );
                        }
                    }
                }
            }
        }

        return $total_count;
    }

    /**
     * Recursively counts widget instances in nested elements.
     *
     * @param array  $elements    The nested elements to search.
     * @param string $widget_name The name of the widget to count.
     *
     * @return int The number of times the widget is found in nested elements.
     */
    private function count_nested_widgets( $elements, $widget_name ) {
        $count = 0;

        foreach ( $elements as $element ) {
            if ( isset( $element['widgetType'] ) && $element['widgetType'] === $widget_name ) {
                $count++;
            }

            if ( isset( $element['elements'] ) && is_array( $element['elements'] ) ) {
                $count += $this->count_nested_widgets( $element['elements'], $widget_name );
            }
        }

        return $count;
    }
}

/**
 * Adds a menu item for the settings page.
 * 
 * @since  1.0.0
 * @return void
 */
function elementor_widget_usage_tracker_menu() {
    add_menu_page(
        esc_html__( 'Widget Usage Tracker for Elementor', 'widget-usage-tracker-for-elementor' ),
        esc_html__( 'Widget Usage', 'widget-usage-tracker-for-elementor' ),
        'manage_options',
        'widget-usage-tracker-for-elementor',
        'elementor_widget_usage_tracker_page',
        'dashicons-search'
    );
}
add_action( 'admin_menu', 'elementor_widget_usage_tracker_menu' );

/**
 * Displays the settings page content.
 * 
 * @since  1.0.0
 * @return void
 */
function elementor_widget_usage_tracker_page() {
    if ( ! class_exists( 'Elementor_Widget_Usage_List_Table' ) ) {
        return;
    }

    $table = new Elementor_Widget_Usage_List_Table();
    $table->prepare_items();
    ?>
    <div class="wrap">
        <h1>
            <?php esc_html_e( 'Widget Usage Tracker for Elementor', 'widget-usage-tracker-for-elementor' ); ?>
            <?php
                echo ' <button id="wut-support" class="button" style="margin-left: 10px;"><span class="dashicons dashicons-format-chat"></span> ' . esc_html__( 'Support', 'widget-usage-tracker-for-elementor' ) . '</button>';
            ?>
        </h1>
        <form method="post">
            <?php $table->display(); ?>
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
 * Enqueue JavaScript for the modal and AJAX functionality.
 * 
 * @since  1.0.0
 * @return void
 */
function elementor_widget_usage_tracker_scripts() {
    wp_enqueue_style( 'widget-usage-tracker-css', plugin_dir_url( __FILE__ ) . 'assets/css/wut-styles.css', [], WUT_VERSION );
    // Enqueue the script.
    wp_enqueue_script( 'widget-usage-tracker-js', plugin_dir_url( __FILE__ ) . 'assets/js/wut-scripts.js', [ 'jquery' ], WUT_VERSION, true );
    // Localize the script.
    wp_localize_script( 'widget-usage-tracker-js', 'WidgetUsageTracker', [
        'ajax_url' => admin_url( 'admin-ajax.php' ),
        'nonce'    => wp_create_nonce( 'widget_usage_tracker_nonce' ),
    ]);
}
add_action( 'admin_enqueue_scripts', 'elementor_widget_usage_tracker_scripts' );

/**
 * AJAX callback to get pages where the widget is used.
 * 
 * @since  1.0.0
 * @return void
 */
function elementor_widget_usage_tracker_ajax() {
    // Check the nonce for security.
    check_ajax_referer( 'widget_usage_tracker_nonce', 'nonce' );

    // Sanitize the widget name received from the AJAX request.
    $widget_name = sanitize_text_field( $_POST['widget_name'] );

    global $wpdb;

    // Fetch unique post IDs where the widget is used in published posts.
    $results = $wpdb->get_col(
        $wpdb->prepare(
            "SELECT DISTINCT pm.post_id FROM {$wpdb->prefix}postmeta pm
             JOIN {$wpdb->prefix}posts p ON pm.post_id = p.ID
             WHERE pm.meta_key = '_elementor_data' 
             AND pm.meta_value LIKE %s
             AND p.post_status = 'publish'",
            '%' . $wpdb->esc_like( $widget_name ) . '%'
        )
    );

    // Debugging: Log the number of unique post IDs found.
    error_log( 'Widget Usage Tracker: Found ' . count( $results ) . ' unique post IDs for widget "' . $widget_name . '"' );

    $pages = [];

    // Iterate through the unique post_ids.
    foreach ( $results as $post_id ) {
        // Ensure post_id is an integer.
        $post_id = intval( $post_id );
        if ( $post_id <= 0 ) {
            continue;
        }

        $page = get_post( $post_id );
        if ( $page ) {
            // Ensure the post is published.
            if ( 'publish' !== $page->post_status ) {
                continue;
            }

            $pages[] = [
                'title' => get_the_title( $page ),
                'url'   => get_permalink( $page ),
            ];

            // Debugging: Log each page added.
            error_log( 'Widget Usage Tracker: Added post "' . $page->post_title . '" (ID: ' . $post_id . ')' );
        }
    }

    // Reindex the array to ensure it's a numerically indexed array.
    $pages = array_values( $pages );

    // Debugging: Log the final pages array.
    error_log( 'Widget Usage Tracker: Sending ' . count( $pages ) . ' unique pages to frontend.' );

    wp_send_json_success( $pages );
}
add_action( 'wp_ajax_get_widget_usage_details', 'elementor_widget_usage_tracker_ajax' );
