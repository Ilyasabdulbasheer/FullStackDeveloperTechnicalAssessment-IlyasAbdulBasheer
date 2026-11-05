<?php
/**
 * Standalone & Repeatable Security/Functionality Test Runner
 *
 * Can be executed via CLI: `php test-suite.php`
 *
 * Covers the 4 mandatory evaluation scenarios:
 * 1. Normal Valid Submission
 * 2. Invalid Input Handling
 * 3. Unauthorized Access Prevention (REST / Public Isolation)
 * 4. Potentially Malicious Input (XSS / SQLi payload handling)
 *
 * @package GroupEnquiryDesk
 */

// If running standalone outside of WordPress, initialize lightweight test stubs
if ( ! defined( 'ABSPATH' ) ) {
    define( 'ABSPATH', __DIR__ . '/../../' );

    // Mock storage for tests
    global $mock_posts, $mock_post_meta, $mock_current_user_caps, $mock_transients;
    $mock_posts             = array();
    $mock_post_meta        = array();
    $mock_current_user_caps = array();
    $mock_transients        = array();

    function _x( $text, $context, $domain = 'default' ) { return $text; }
    function __( $text, $domain = 'default' ) { return $text; }
    function esc_html( $text ) { return htmlspecialchars( $text, ENT_QUOTES, 'UTF-8' ); }
    function esc_attr( $text ) { return htmlspecialchars( $text, ENT_QUOTES, 'UTF-8' ); }
    function esc_url( $url ) { return $url; }
    function current_time( $type ) { return ( 'mysql' === $type ) ? date( 'Y-m-d H:i:s' ) : date( 'Y-m-d' ); }
    function is_email( $email ) { return filter_var( $email, FILTER_VALIDATE_EMAIL ) !== false; }
    function sanitize_key( $key ) { return preg_replace( '/[^a-z0-9_\-]/', '', strtolower( $key ) ); }
    function sanitize_text_field( $str ) { return strip_tags( trim( $str ) ); }
    function sanitize_textarea_field( $str ) { return strip_tags( trim( $str ) ); }
    function sanitize_email( $email ) { return filter_var( trim( $email ), FILTER_SANITIZE_EMAIL ); }
    function wp_unslash( $val ) { return stripslashes( $val ); }
    function wp_create_nonce( $action ) { return substr( md5( 'salt_' . $action ), 0, 10 ); }
    function wp_verify_nonce( $nonce, $action ) { return $nonce === substr( md5( 'salt_' . $action ), 0, 10 ); }
    function current_user_can( $cap ) {
        global $mock_current_user_caps;
        return ! empty( $mock_current_user_caps[ $cap ] );
    }
    function is_user_logged_in() {
        global $mock_current_user_caps;
        return ! empty( $mock_current_user_caps['logged_in'] );
    }
    function wp_insert_post( $args, $wp_error = false ) {
        global $mock_posts;
        $id = count( $mock_posts ) + 101;
        $args['ID'] = $id;
        $mock_posts[ $id ] = $args;
        return $id;
    }
    function update_post_meta( $post_id, $key, $val ) {
        global $mock_post_meta;
        $mock_post_meta[ $post_id ][ $key ] = $val;
        return true;
    }
    function get_post_meta( $post_id, $key, $single = true ) {
        global $mock_post_meta;
        return isset( $mock_post_meta[ $post_id ][ $key ] ) ? $mock_post_meta[ $post_id ][ $key ] : '';
    }
    function get_transient( $key ) {
        global $mock_transients;
        return isset( $mock_transients[ $key ] ) ? $mock_transients[ $key ] : false;
    }
    function set_transient( $key, $val, $exp ) {
        global $mock_transients;
        $mock_transients[ $key ] = $val;
        return true;
    }
    function delete_transient( $key ) {
        global $mock_transients;
        unset( $mock_transients[ $key ] );
        return true;
    }

    class WP_Error {
        private $code;
        private $message;
        private $data;
        public function __construct( $code = '', $message = '', $data = '' ) {
            $this->code = $code;
            $this->message = $message;
            $this->data = $data;
        }
        public function get_error_code() { return $this->code; }
        public function get_error_message() { return $this->message; }
        public function get_error_data( $key = '' ) {
            return ( is_array( $this->data ) && isset( $this->data[ $key ] ) ) ? $this->data[ $key ] : $this->data;
        }
    }
    function is_wp_error( $thing ) { return ( $thing instanceof WP_Error ); }

    // Load plugin dependencies under test
    require_once __DIR__ . '/../group-enquiry-desk/includes/class-enquiry-post-type.php';
    require_once __DIR__ . '/../group-enquiry-desk/includes/class-rate-limiter.php';
    require_once __DIR__ . '/../group-enquiry-desk/includes/class-enquiry-handler.php';
    require_once __DIR__ . '/../group-enquiry-desk/includes/class-enquiry-rest-controller.php';
}

/**
 * Terminal Color Helpers
 */
function test_pass( $msg ) {
    echo "\033[32m[PASS]\033[0m " . $msg . "\n";
}
function test_fail( $msg ) {
    echo "\033[31m[FAIL]\033[0m " . $msg . "\n";
    exit( 1 );
}

echo "========================================================\n";
echo " GROUP ENQUIRY DESK — AUTOMATED SECURITY TEST SUITE\n";
echo "========================================================\n\n";

// -----------------------------------------------------------------------------
// TEST 1: Normal Valid Submission
// -----------------------------------------------------------------------------
echo "Running Test 1: Normal Valid Submission...\n";

$brand = 'space42arena';
$valid_payload = array(
    'ged_brand'          => $brand,
    'ged_nonce'          => wp_create_nonce( 'group_enquiry_action_' . $brand ),
    'ged_full_name'      => 'Sarah Jenkins',
    'ged_email'          => 'sarah.jenkins@example.com',
    'ged_enquiry_type'   => 'venue_hire',
    'ged_preferred_date' => '2026-12-15',
    'ged_message'        => 'Interested in hiring Main Hall for tech conference.',
    'ged_website_hp'     => '', // Honeypot empty
);

$result_1 = Group_Enquiry_Handler::process_submission( $valid_payload );

if ( ! is_wp_error( $result_1 ) && is_int( $result_1 ) && $result_1 > 0 ) {
    $stored_brand = get_post_meta( $result_1, '_ged_brand', true );
    $stored_email = get_post_meta( $result_1, '_ged_email', true );
    if ( 'space42arena' === $stored_brand && 'sarah.jenkins@example.com' === $stored_email ) {
        test_pass( "Test 1 Passed: Valid enquiry successfully created (ID: $result_1) with correct metadata." );
    } else {
        test_fail( "Test 1 Failed: Metadata mismatch on persisted enquiry." );
    }
} else {
    test_fail( "Test 1 Failed: Could not process valid submission: " . ( is_wp_error( $result_1 ) ? $result_1->get_error_message() : 'Unknown error' ) );
}

echo "\n";

// -----------------------------------------------------------------------------
// TEST 2: Invalid Input Handling
// -----------------------------------------------------------------------------
echo "Running Test 2: Invalid Input Validation...\n";

$brand = 'drvet';
$invalid_payload = array(
    'ged_brand'          => $brand,
    'ged_nonce'          => wp_create_nonce( 'group_enquiry_action_' . $brand ),
    'ged_full_name'      => 'A', // Too short (min 2 chars)
    'ged_email'          => 'not-an-email', // Malformed email
    'ged_enquiry_type'   => 'invalid_type_does_not_exist',
    'ged_message'        => 'hi', // Too short
    'ged_website_hp'     => '',
);

$result_2 = Group_Enquiry_Handler::process_submission( $invalid_payload );

if ( is_wp_error( $result_2 ) ) {
    test_pass( "Test 2 Passed: Invalid input rejected with error code '" . $result_2->get_error_code() . "': " . $result_2->get_error_message() );
} else {
    test_fail( "Test 2 Failed: System accepted malformed input when it should have returned WP_Error." );
}

echo "\n";

// -----------------------------------------------------------------------------
// TEST 3: Unauthorized Access Prevention (REST / Public Boundary)
// -----------------------------------------------------------------------------
echo "Running Test 3: Unauthorized Access Prevention...\n";

// Scenario 3A: Anonymous public visitor attempting to read enquiries
global $mock_current_user_caps;
$mock_current_user_caps = array(); // Not logged in

$auth_check_anon = Group_Enquiry_REST_Controller::check_permission( null );
if ( is_wp_error( $auth_check_anon ) && 401 === $auth_check_anon->get_error_data( 'status' ) ) {
    test_pass( "Test 3A Passed: Anonymous public visitor denied with HTTP 401 Unauthorized." );
} else {
    test_fail( "Test 3A Failed: Anonymous user was not blocked." );
}

// Scenario 3B: Logged in Subscriber (insufficient capabilities)
$mock_current_user_caps = array(
    'logged_in'  => true,
    'read'       => true,
    'edit_posts' => false, // No administrative capability
);

$auth_check_sub = Group_Enquiry_REST_Controller::check_permission( null );
if ( is_wp_error( $auth_check_sub ) && 403 === $auth_check_sub->get_error_data( 'status' ) ) {
    test_pass( "Test 3B Passed: Low-privilege subscriber denied with HTTP 403 Forbidden." );
} else {
    test_fail( "Test 3B Failed: Subscriber was granted access without 'edit_others_posts'." );
}

echo "\n";

// -----------------------------------------------------------------------------
// TEST 4: Potentially Malicious Input Handling (XSS & Injection Protection)
// -----------------------------------------------------------------------------
echo "Running Test 4: Potentially Malicious Input Handling...\n";

$brand = 'ipstudios';
$malicious_payload = array(
    'ged_brand'          => $brand,
    'ged_nonce'          => wp_create_nonce( 'group_enquiry_action_' . $brand ),
    'ged_full_name'      => "<script>alert('XSS')</script>Alex Smith",
    'ged_email'          => 'alex.smith@example.com',
    'ged_enquiry_type'   => 'content_production',
    'ged_message'        => "<img src=x onerror=alert('hack')> Let's produce a TV series; DROP TABLE wp_users; --",
    'ged_website_hp'     => '',
);

$result_4 = Group_Enquiry_Handler::process_submission( $malicious_payload );

if ( ! is_wp_error( $result_4 ) && is_int( $result_4 ) ) {
    $cleaned_name = get_post_meta( $result_4, '_ged_full_name', true );
    // Verify script tags were completely sanitized
    if ( false === strpos( $cleaned_name, '<script>' ) && 'Alex Smith' === $cleaned_name ) {
        test_pass( "Test 4 Passed: XSS injection vectors neutralized. Stored name sanitized to: '$cleaned_name'." );
    } else {
        test_fail( "Test 4 Failed: Raw script tag found in stored postmeta: '$cleaned_name'." );
    }
} else {
    test_fail( "Test 4 Failed: Could not process submission." );
}

echo "\n========================================================\n";
echo " ALL 4 SECURITY & FUNCTIONALITY TESTS PASSED (100%)\n";
echo "========================================================\n";

