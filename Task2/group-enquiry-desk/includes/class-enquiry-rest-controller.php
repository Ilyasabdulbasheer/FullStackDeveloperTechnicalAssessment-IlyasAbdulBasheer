<?php
/**
 * Secure REST API Controller for Authorized Enquiry Access
 *
 * @package GroupEnquiryDesk
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Group_Enquiry_REST_Controller {

    const REST_NAMESPACE = 'group-enquiry/v1';
    const REST_BASE      = 'enquiries';
    const REQUIRED_CAP   = 'edit_others_posts';

    /**
     * Register REST API routes
     */
    public static function register_routes() {
        register_rest_route(
            self::REST_NAMESPACE,
            '/' . self::REST_BASE,
            array(
                array(
                    'methods'             => WP_REST_Server::READABLE,
                    'callback'            => array( __CLASS__, 'get_items' ),
                    'permission_callback' => array( __CLASS__, 'check_permission' ),
                    'args'                => self::get_collection_params(),
                ),
                'schema' => array( __CLASS__, 'get_item_schema' ),
            )
        );
    }

    /**
     * Strict Authorization Check
     * Ensures public/unauthenticated visitors can NEVER view submissions.
     *
     * @param WP_REST_Request $request
     * @return bool|WP_Error
     */
    public static function check_permission( $request ) {
        if ( ! is_user_logged_in() ) {
            return new WP_Error(
                'rest_not_logged_in',
                __( 'Authentication required to access customer enquiries.', 'group-enquiry-desk' ),
                array( 'status' => 401 )
            );
        }

        if ( ! current_user_can( self::REQUIRED_CAP ) ) {
            return new WP_Error(
                'rest_forbidden',
                __( 'Access denied: You lack sufficient administrative privileges.', 'group-enquiry-desk' ),
                array( 'status' => 403 )
            );
        }

        return true;
    }

    /**
     * Retrieve list of enquiries with server-side validation and filtering
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response|WP_Error
     */
    public static function get_items( $request ) {
        $brand_filter  = $request->get_param( 'brand' );
        $status_filter = $request->get_param( 'status' );
        $page          = (int) $request->get_param( 'page' );
        $per_page      = (int) $request->get_param( 'per_page' );

        $query_args = array(
            'post_type'      => Group_Enquiry_Post_Type::POST_TYPE,
            'paged'          => $page,
            'posts_per_page' => $per_page,
            'orderby'        => 'date',
            'order'          => 'DESC',
            'post_status'    => array(
                Group_Enquiry_Post_Type::STATUS_NEW,
                Group_Enquiry_Post_Type::STATUS_IN_PROGRESS,
                Group_Enquiry_Post_Type::STATUS_CLOSED,
            ),
        );

        if ( ! empty( $status_filter ) ) {
            $query_args['post_status'] = $status_filter;
        }

        if ( ! empty( $brand_filter ) ) {
            $query_args['meta_query'] = array(
                array(
                    'key'     => '_ged_brand',
                    'value'   => $brand_filter,
                    'compare' => '=',
                ),
            );
        }

        $query = new WP_Query( $query_args );
        $brands = Group_Enquiry_Post_Type::get_configured_brands();

        $enquiries = array();

        if ( $query->have_posts() ) {
            while ( $query->have_posts() ) {
                $query->the_post();
                $post_id     = get_the_ID();
                $brand_key   = get_post_meta( $post_id, '_ged_brand', true );
                $full_name   = get_post_meta( $post_id, '_ged_full_name', true );
                $email       = get_post_meta( $post_id, '_ged_email', true );
                $type_label  = get_post_meta( $post_id, '_ged_enquiry_type_label', true );
                $pref_date   = get_post_meta( $post_id, '_ged_preferred_date', true );
                $post_status = get_post_status( $post_id );

                $enquiries[] = array(
                    'id'             => $post_id,
                    'brand'          => $brand_key,
                    'brand_name'     => isset( $brands[ $brand_key ] ) ? $brands[ $brand_key ]['name'] : $brand_key,
                    'customer_name'  => $full_name,
                    'email'          => $email,
                    'enquiry_type'   => $type_label,
                    'preferred_date' => $pref_date ? $pref_date : null,
                    'message'        => get_the_content(),
                    'status'         => $post_status,
                    'status_label'   => Group_Enquiry_Post_Type::get_status_label( $post_status ),
                    'created_at'     => get_the_date( 'c' ),
                );
            }
            wp_reset_postdata();
        }

        $response = rest_ensure_response( $enquiries );

        // Add standard pagination headers
        $total_posts = $query->found_posts;
        $max_pages   = ceil( $total_posts / $per_page );

        $response->header( 'X-WP-Total', (int) $total_posts );
        $response->header( 'X-WP-TotalPages', (int) $max_pages );

        return $response;
    }

    /**
     * Define and validate collection parameters
     */
    public static function get_collection_params() {
        $brands = Group_Enquiry_Post_Type::get_configured_brands();

        return array(
            'page' => array(
                'description'       => 'Current page of the collection.',
                'type'              => 'integer',
                'default'           => 1,
                'sanitize_callback' => 'absint',
                'validate_callback' => 'rest_validate_request_arg',
                'minimum'           => 1,
            ),
            'per_page' => array(
                'description'       => 'Maximum number of items to be returned in result set.',
                'type'              => 'integer',
                'default'           => 20,
                'sanitize_callback' => 'absint',
                'validate_callback' => 'rest_validate_request_arg',
                'minimum'           => 1,
                'maximum'           => 100,
            ),
            'brand' => array(
                'description'       => 'Filter enquiries by specific business brand.',
                'type'              => 'string',
                'enum'              => array_keys( $brands ),
                'sanitize_callback' => 'sanitize_key',
                'validate_callback' => 'rest_validate_request_arg',
            ),
            'status' => array(
                'description'       => 'Filter enquiries by workflow status.',
                'type'              => 'string',
                'enum'              => array(
                    Group_Enquiry_Post_Type::STATUS_NEW,
                    Group_Enquiry_Post_Type::STATUS_IN_PROGRESS,
                    Group_Enquiry_Post_Type::STATUS_CLOSED,
                ),
                'sanitize_callback' => 'sanitize_key',
                'validate_callback' => 'rest_validate_request_arg',
            ),
        );
    }
}

