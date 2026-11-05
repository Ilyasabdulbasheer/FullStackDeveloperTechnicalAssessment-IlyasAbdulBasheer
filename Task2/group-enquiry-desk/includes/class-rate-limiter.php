<?php
/**
 * Transient-Based IP Rate Limiter & Anti-Spam Guard
 *
 * @package GroupEnquiryDesk
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Group_Enquiry_Rate_Limiter {

    /**
     * Max submissions allowed within the time window
     */
    const MAX_ATTEMPTS = 5;

    /**
     * Time window in seconds (10 minutes)
     */
    const TIME_WINDOW = 600;

    /**
     * Check if client IP is currently rate-limited
     *
     * @param string|null $ip_override For testing purposes
     * @return bool True if rate limited (blocked), false if allowed
     */
    public static function is_rate_limited( $ip_override = null ) {
        $ip = $ip_override ? $ip_override : self::get_client_ip();
        $key = self::get_transient_key( $ip );

        $attempts = (int) get_transient( $key );

        return ( $attempts >= self::MAX_ATTEMPTS );
    }

    /**
     * Record a submission attempt for client IP
     *
     * @param string|null $ip_override For testing purposes
     * @return int Current attempt count
     */
    public static function record_attempt( $ip_override = null ) {
        $ip = $ip_override ? $ip_override : self::get_client_ip();
        $key = self::get_transient_key( $ip );

        $attempts = (int) get_transient( $key );
        $attempts++;

        set_transient( $key, $attempts, self::TIME_WINDOW );

        return $attempts;
    }

    /**
     * Reset rate limit for an IP (used in testing)
     */
    public static function reset_attempts( $ip_override = null ) {
        $ip = $ip_override ? $ip_override : self::get_client_ip();
        delete_transient( self::get_transient_key( $ip ) );
    }

    /**
     * Safely resolve client IP address
     *
     * @return string
     */
    public static function get_client_ip() {
        $ip = '127.0.0.1';

        if ( ! empty( $_SERVER['HTTP_CLIENT_IP'] ) ) {
            $ip = sanitize_text_field( wp_unslash( $_SERVER['HTTP_CLIENT_IP'] ) );
        } elseif ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
            // If comma-separated list of proxies, take the first valid IP
            $forwarded = explode( ',', sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) );
            $ip = trim( $forwarded[0] );
        } elseif ( ! empty( $_SERVER['REMOTE_ADDR'] ) ) {
            $ip = sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) );
        }

        // Validate IP format
        if ( ! filter_var( $ip, FILTER_VALIDATE_IP ) ) {
            $ip = '127.0.0.1';
        }

        return $ip;
    }

    /**
     * Generate MD5 hashed transient key to ensure DB index safety
     */
    private static function get_transient_key( $ip ) {
        return 'ged_rate_' . md5( $ip );
    }
}

