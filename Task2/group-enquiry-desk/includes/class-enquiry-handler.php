<?php
/**
 * Form Submission Processing & Security Validation Handler
 *
 * @package GroupEnquiryDesk
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Group_Enquiry_Handler {

    /**
     * Handle AJAX submission
     */
    public static function handle_ajax_submission() {
        $result = self::process_submission( $_POST );

        if ( is_wp_error( $result ) ) {
            wp_send_json_error(
                array(
                    'message' => $result->get_error_message(),
                    'code'    => $result->get_error_code(),
                ),
                $result->get_error_data( 'status' ) ? (int) $result->get_error_data( 'status' ) : 400
            );
        }

        wp_send_json_success(
            array(
                'message'    => __( 'Thank you! Your enquiry has been received securely.', 'group-enquiry-desk' ),
                'enquiry_id' => $result,
            ),
            201
        );
    }

    /**
     * Handle standard non-JS HTTP POST submission fallback
     */
    public static function handle_post_submission() {
        $result = self::process_submission( $_POST );

        $referer = wp_get_referer();
        if ( ! $referer ) {
            $referer = home_url();
        }

        if ( is_wp_error( $result ) ) {
            $redirect_url = add_query_arg(
                array(
                    'ged_status' => 'error',
                    'ged_msg'    => rawurlencode( $result->get_error_message() ),
                ),
                $referer
            );
        } else {
            $redirect_url = add_query_arg(
                array(
                    'ged_status' => 'success',
                ),
                $referer
            );
        }

        wp_safe_redirect( $redirect_url );
        exit;
    }

    /**
     * Centralized processing logic with comprehensive security checks
     *
     * @param array $data Raw input array
     * @return int|WP_Error Post ID on success or WP_Error on failure
     */
    public static function process_submission( $data ) {
        // 1. IP RATE LIMIT CHECK
        if ( Group_Enquiry_Rate_Limiter::is_rate_limited() ) {
            return new WP_Error(
                'rate_limit_exceeded',
                __( 'Too many enquiries submitted from your connection. Please wait 10 minutes before trying again.', 'group-enquiry-desk' ),
                array( 'status' => 429 )
            );
        }

        // 2. HONEYPOT ANTI-SPAM TRAP
        if ( ! empty( $data['ged_website_hp'] ) ) {
            // Silently reject bot submissions
            return new WP_Error(
                'spam_detected',
                __( 'Spam submission detected.', 'group-enquiry-desk' ),
                array( 'status' => 400 )
            );
        }

        // 3. BRAND VALIDATION (Configured context check)
        $raw_brand = isset( $data['ged_brand'] ) ? sanitize_key( $data['ged_brand'] ) : '';
        $configured_brands = Group_Enquiry_Post_Type::get_configured_brands();

        if ( empty( $raw_brand ) || ! array_key_exists( $raw_brand, $configured_brands ) ) {
            return new WP_Error(
                'invalid_brand',
                __( 'Invalid or unconfigured business brand.', 'group-enquiry-desk' ),
                array( 'status' => 400 )
            );
        }

        // 4. CSRF NONCE VERIFICATION
        $nonce = isset( $data['ged_nonce'] ) ? $data['ged_nonce'] : '';
        if ( ! wp_verify_nonce( $nonce, 'group_enquiry_action_' . $raw_brand ) ) {
            return new WP_Error(
                'invalid_nonce',
                __( 'Security token expired or invalid. Please refresh the page and try again.', 'group-enquiry-desk' ),
                array( 'status' => 403 )
            );
        }

        // 5. INPUT SANITIZATION
        $full_name = isset( $data['ged_full_name'] ) ? sanitize_text_field( wp_unslash( $data['ged_full_name'] ) ) : '';
        $email     = isset( $data['ged_email'] ) ? sanitize_email( wp_unslash( $data['ged_email'] ) ) : '';
        $type_key  = isset( $data['ged_enquiry_type'] ) ? sanitize_key( $data['ged_enquiry_type'] ) : '';
        $message   = isset( $data['ged_message'] ) ? sanitize_textarea_field( wp_unslash( $data['ged_message'] ) ) : '';
        $date      = isset( $data['ged_preferred_date'] ) ? sanitize_text_field( wp_unslash( $data['ged_preferred_date'] ) ) : '';

        // 6. VALIDATION RULES
        if ( empty( $full_name ) || mb_strlen( $full_name ) < 2 ) {
            return new WP_Error( 'missing_name', __( 'Please provide your full name (minimum 2 characters).', 'group-enquiry-desk' ) );
        }

        if ( empty( $email ) || ! is_email( $email ) ) {
            return new WP_Error( 'invalid_email', __( 'Please provide a valid email address.', 'group-enquiry-desk' ) );
        }

        $allowed_types = $configured_brands[ $raw_brand ]['enquiry_types'];
        if ( empty( $type_key ) || ! array_key_exists( $type_key, $allowed_types ) ) {
            return new WP_Error( 'invalid_enquiry_type', __( 'Please select a valid enquiry type for this brand.', 'group-enquiry-desk' ) );
        }

        if ( empty( $message ) || mb_strlen( $message ) < 5 ) {
            return new WP_Error( 'missing_message', __( 'Please include an enquiry message (minimum 5 characters).', 'group-enquiry-desk' ) );
        }

        if ( ! empty( $date ) && ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date ) ) {
            return new WP_Error( 'invalid_date', __( 'Preferred date must follow the YYYY-MM-DD format.', 'group-enquiry-desk' ) );
        }

        // 7. RECORD SUBMISSION ATTEMPT IN RATE LIMITER
        Group_Enquiry_Rate_Limiter::record_attempt();

        // 8. INSERT AS INTERNAL CUSTOM POST TYPE
        $post_title = sprintf(
            '[%s] %s (%s)',
            $configured_brands[ $raw_brand ]['name'],
            $full_name,
            current_time( 'Y-m-d H:i' )
        );

        $post_data = array(
            'post_title'   => $post_title,
            'post_content' => $message,
            'post_status'  => Group_Enquiry_Post_Type::STATUS_NEW,
            'post_type'    => Group_Enquiry_Post_Type::POST_TYPE,
            'post_date'    => current_time( 'mysql' ),
        );

        $enquiry_id = wp_insert_post( $post_data, true );

        if ( is_wp_error( $enquiry_id ) ) {
            return $enquiry_id;
        }

        // 9. PERSIST STRUCTURED META
        update_post_meta( $enquiry_id, '_ged_brand', $raw_brand );
        update_post_meta( $enquiry_id, '_ged_full_name', $full_name );
        update_post_meta( $enquiry_id, '_ged_email', $email );
        update_post_meta( $enquiry_id, '_ged_enquiry_type', $type_key );
        update_post_meta( $enquiry_id, '_ged_enquiry_type_label', $allowed_types[ $type_key ] );
        update_post_meta( $enquiry_id, '_ged_preferred_date', $date );
        update_post_meta( $enquiry_id, '_ged_ip_address', Group_Enquiry_Rate_Limiter::get_client_ip() );
        update_post_meta( $enquiry_id, '_ged_created_at', current_time( 'mysql' ) );

        return $enquiry_id;
    }
}

