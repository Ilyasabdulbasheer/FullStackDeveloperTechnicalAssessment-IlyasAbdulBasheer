<?php
/**
 * Plugin Name: Group Enquiry Desk
 * Plugin URI: https://github.com/Ilyasabdulbasheer/FullStackDeveloperTechnicalAssessment-IlyasAbdulBasheer
 * Description: Secure multi-brand customer enquiry desk for WordPress. Features scoped frontend forms, CSRF/rate-limit defense, admin management, and an authenticated REST API.
 * Version: 1.0.0
 * Author: Ilyas Abdul Basheer
 * Text Domain: group-enquiry-desk
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * License: GPLv2 or later
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Direct access denied.
}

define( 'GROUP_ENQUIRY_VERSION', '1.0.0' );
define( 'GROUP_ENQUIRY_FILE', __FILE__ );
define( 'GROUP_ENQUIRY_DIR', plugin_dir_path( __FILE__ ) );
define( 'GROUP_ENQUIRY_URL', plugin_dir_url( __FILE__ ) );

/**
 * Main Group Enquiry Desk Plugin Class
 */
final class Group_Enquiry_Desk {

    /**
     * Singleton instance
     *
     * @var Group_Enquiry_Desk|null
     */
    private static $instance = null;

    /**
     * Retrieve single instance
     */
    public static function instance() {
        if ( is_null( self::$instance ) ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        $this->load_dependencies();
        $this->init_hooks();
    }

    /**
     * Include required core classes
     */
    private function load_dependencies() {
        require_once GROUP_ENQUIRY_DIR . 'includes/class-enquiry-post-type.php';
        require_once GROUP_ENQUIRY_DIR . 'includes/class-rate-limiter.php';
        require_once GROUP_ENQUIRY_DIR . 'includes/class-enquiry-form.php';
        require_once GROUP_ENQUIRY_DIR . 'includes/class-enquiry-handler.php';
        require_once GROUP_ENQUIRY_DIR . 'includes/class-enquiry-admin.php';
        require_once GROUP_ENQUIRY_DIR . 'includes/class-enquiry-rest-controller.php';
    }

    /**
     * Initialize WordPress hooks
     */
    private function init_hooks() {
        // Data model initialization
        add_action( 'init', array( 'Group_Enquiry_Post_Type', 'register' ) );

        // Frontend assets & shortcodes
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_frontend_assets' ) );
        add_shortcode( 'group_enquiry_form', array( 'Group_Enquiry_Form', 'render_shortcode' ) );

        // Form submission handling (AJAX and standard POST)
        add_action( 'wp_ajax_group_enquiry_submit', array( 'Group_Enquiry_Handler', 'handle_ajax_submission' ) );
        add_action( 'wp_ajax_nopriv_group_enquiry_submit', array( 'Group_Enquiry_Handler', 'handle_ajax_submission' ) );
        add_action( 'admin_post_group_enquiry_submit', array( 'Group_Enquiry_Handler', 'handle_post_submission' ) );
        add_action( 'admin_post_nopriv_group_enquiry_submit', array( 'Group_Enquiry_Handler', 'handle_post_submission' ) );

        // Admin interface
        if ( is_admin() ) {
            add_action( 'admin_menu', array( 'Group_Enquiry_Admin', 'register_menu' ) );
            add_action( 'admin_enqueue_scripts', array( 'Group_Enquiry_Admin', 'enqueue_admin_assets' ) );
            add_action( 'admin_post_group_enquiry_update_status', array( 'Group_Enquiry_Admin', 'handle_status_update' ) );
        }

        // REST API
        add_action( 'rest_api_init', array( 'Group_Enquiry_REST_Controller', 'register_routes' ) );
    }

    /**
     * Enqueue public scripts and styles
     */
    public function enqueue_frontend_assets() {
        wp_register_style(
            'group-enquiry-frontend',
            GROUP_ENQUIRY_URL . 'assets/css/frontend.css',
            array(),
            GROUP_ENQUIRY_VERSION
        );

        wp_register_script(
            'group-enquiry-frontend',
            GROUP_ENQUIRY_URL . 'assets/js/frontend.js',
            array(),
            GROUP_ENQUIRY_VERSION,
            true
        );

        wp_localize_script(
            'group-enquiry-frontend',
            'groupEnquiryConfig',
            array(
                'ajaxUrl' => admin_url( 'admin-ajax.php' ),
            )
        );
    }

    /**
     * Plugin activation hook
     */
    public static function activate() {
        require_once GROUP_ENQUIRY_DIR . 'includes/class-enquiry-post-type.php';
        Group_Enquiry_Post_Type::register();
        flush_rewrite_rules();
    }

    /**
     * Plugin deactivation hook
     */
    public static function deactivate() {
        flush_rewrite_rules();
    }
}

register_activation_hook( GROUP_ENQUIRY_FILE, array( 'Group_Enquiry_Desk', 'activate' ) );
register_deactivation_hook( GROUP_ENQUIRY_FILE, array( 'Group_Enquiry_Desk', 'deactivate' ) );

// Launch plugin
Group_Enquiry_Desk::instance();

