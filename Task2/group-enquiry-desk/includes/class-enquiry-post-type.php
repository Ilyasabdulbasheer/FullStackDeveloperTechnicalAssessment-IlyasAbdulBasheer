<?php
/**
 * Custom Post Type & Data Model Definition
 *
 * @package GroupEnquiryDesk
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Group_Enquiry_Post_Type {

    const POST_TYPE = 'group_enquiry';

    // Status Constants
    const STATUS_NEW         = 'enquiry_new';
    const STATUS_IN_PROGRESS = 'enquiry_in_progress';
    const STATUS_CLOSED      = 'enquiry_closed';

    /**
     * Register CPT and custom post statuses
     */
    public static function register() {
        self::register_post_statuses();
        self::register_post_type();
    }

    /**
     * Register Custom Post Type with strict internal visibility
     */
    private static function register_post_type() {
        $labels = array(
            'name'               => _x( 'Enquiries', 'post type general name', 'group-enquiry-desk' ),
            'singular_name'      => _x( 'Enquiry', 'post type singular name', 'group-enquiry-desk' ),
            'menu_name'          => _x( 'Group Enquiries', 'admin menu', 'group-enquiry-desk' ),
            'all_items'          => __( 'All Enquiries', 'group-enquiry-desk' ),
            'view_item'          => __( 'View Enquiry', 'group-enquiry-desk' ),
            'search_items'       => __( 'Search Enquiries', 'group-enquiry-desk' ),
            'not_found'          => __( 'No enquiries found', 'group-enquiry-desk' ),
            'not_found_in_trash' => __( 'No enquiries found in Trash', 'group-enquiry-desk' ),
        );

        $args = array(
            'labels'             => $labels,
            'description'        => __( 'Internal storage for multi-brand customer enquiries', 'group-enquiry-desk' ),
            // CRITICAL SECURITY SETTINGS:
            // Public users must NEVER be able to query or view enquiries on the frontend
            'public'             => false,
            'publicly_queryable' => false,
            'show_ui'            => false, // Handled by our dedicated Group_Enquiry_Admin controller
            'show_in_menu'       => false,
            'query_var'          => false,
            'rewrite'            => false,
            'capability_type'    => 'post',
            'has_archive'        => false,
            'hierarchical'       => false,
            'supports'           => array( 'title' ),
            'show_in_rest'       => false, // Handled by our secure Group_Enquiry_REST_Controller
        );

        register_post_type( self::POST_TYPE, $args );
    }

    /**
     * Register workflow statuses
     */
    private static function register_post_statuses() {
        register_post_status( self::STATUS_NEW, array(
            'label'                     => _x( 'New', 'enquiry status', 'group-enquiry-desk' ),
            'public'                    => false,
            'exclude_from_search'       => true,
            'show_in_admin_all_list'    => true,
            'show_in_admin_status_list' => true,
            'label_count'               => _n_noop( 'New <span class="count">(%s)</span>', 'New <span class="count">(%s)</span>', 'group-enquiry-desk' ),
        ) );

        register_post_status( self::STATUS_IN_PROGRESS, array(
            'label'                     => _x( 'In Progress', 'enquiry status', 'group-enquiry-desk' ),
            'public'                    => false,
            'exclude_from_search'       => true,
            'show_in_admin_all_list'    => true,
            'show_in_admin_status_list' => true,
            'label_count'               => _n_noop( 'In Progress <span class="count">(%s)</span>', 'In Progress <span class="count">(%s)</span>', 'group-enquiry-desk' ),
        ) );

        register_post_status( self::STATUS_CLOSED, array(
            'label'                     => _x( 'Closed', 'enquiry status', 'group-enquiry-desk' ),
            'public'                    => false,
            'exclude_from_search'       => true,
            'show_in_admin_all_list'    => true,
            'show_in_admin_status_list' => true,
            'label_count'               => _n_noop( 'Closed <span class="count">(%s)</span>', 'Closed <span class="count">(%s)</span>', 'group-enquiry-desk' ),
        ) );
    }

    /**
     * Return configured business brands and their permissible enquiry types
     * Matches assessment requirements exactly.
     *
     * @return array
     */
    public static function get_configured_brands() {
        return array(
            'ipstudios' => array(
                'name'          => 'IP Studios',
                'domain'        => 'ipstudios.com',
                'enquiry_types' => array(
                    'content_production' => 'Content production',
                    'live_production'    => 'Live production',
                    'digital_media'      => 'Digital media / Broadcast',
                ),
            ),
            'drvet' => array(
                'name'          => 'DrVet Animal Hospital',
                'domain'        => 'drvet.ae',
                'enquiry_types' => array(
                    'general_service'  => 'General service enquiry (no medical records)',
                    'preventive_care'  => 'Preventative care & wellness',
                    'clinic_tour'      => 'Facility inquiry / General information',
                ),
            ),
            'space42arena' => array(
                'name'          => 'Space42 Arena',
                'domain'        => 'space42arena.com',
                'enquiry_types' => array(
                    'venue_hire'        => 'Venue hire',
                    'event_information' => 'Event information',
                    'corporate_gala'    => 'Corporate booking / Exhibition',
                ),
            ),
            'jazzbar' => array(
                'name'          => 'Jazzbar',
                'domain'        => 'jazzbar.ae',
                'enquiry_types' => array(
                    'private_event' => 'Private event enquiry',
                    'vip_table'     => 'VIP group reservation enquiry',
                    'catering_hire' => 'Exclusive venue buyout',
                ),
            ),
            'menalead' => array(
                'name'          => 'MENA Lead',
                'domain'        => 'menalead.com',
                'enquiry_types' => array(
                    'project_enquiry'      => 'Project enquiry',
                    'business_partnership' => 'Business partnership',
                    'consultancy_tender'   => 'Consultancy & architectural tender',
                ),
            ),
        );
    }

    /**
     * Map internal status key to clean human readable label
     */
    public static function get_status_label( $status ) {
        switch ( $status ) {
            case self::STATUS_IN_PROGRESS:
                return __( 'In Progress', 'group-enquiry-desk' );
            case self::STATUS_CLOSED:
                return __( 'Closed', 'group-enquiry-desk' );
            case self::STATUS_NEW:
            default:
                return __( 'New', 'group-enquiry-desk' );
        }
    }
}

