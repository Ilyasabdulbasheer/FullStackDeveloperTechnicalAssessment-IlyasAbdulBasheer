<?php
/**
 * Standalone Demonstration Script for Task 3 AI SEO Helper
 *
 * Can be executed directly from terminal: `php demo-runner.php`
 *
 * Demonstrates:
 * 1. Safe Mock LLM execution with zero external API dependencies
 * 2. Scenario 1: Space42 Arena Event Page
 * 3. Scenario 2: DrVet Animal Hospital Service Page
 * 4. PII scrubbing verification
 * 5. Server-side schema validation (character limits & structured FAQs)
 * 6. Staging state requiring human approval before publication
 *
 * @package GroupEnquiryDesk\AI
 */

if ( ! defined( 'ABSPATH' ) ) {
    define( 'ABSPATH', __DIR__ );

    function sanitize_text_field( $str ) { return strip_tags( trim( $str ) ); }
    function sanitize_textarea_field( $str ) { return strip_tags( trim( $str ) ); }
    function __( $t, $d = '' ) { return $t; }

    class WP_Error {
        private $code; private $message;
        public function __construct( $c, $m ) { $this->code = $c; $this->message = $m; }
        public function get_error_message() { return $this->message; }
    }
    function is_wp_error( $t ) { return ( $t instanceof WP_Error ); }
}

require_once __DIR__ . '/ai-seo-helper/class-ai-provider-interface.php';
require_once __DIR__ . '/ai-seo-helper/class-mock-llm-provider.php';
require_once __DIR__ . '/ai-seo-helper/class-real-llm-adapter.php';
require_once __DIR__ . '/ai-seo-helper/class-ai-seo-service.php';

echo "====================================================================\n";
echo " TASK 3: SAFE AI SEO HELPER — DEMONSTRATION RUNNER\n";
echo "====================================================================\n\n";

$service = new AI_SEO_Service(); // Uses Mock_LLM_Provider by default

// SCENARIO 1: Space42 Arena Event Page
echo "--- SCENARIO 1: Space42 Arena (Event Page) ---\n";
$input_title_1 = "Khalid Abdulrahman Live at Space42 Arena";
$input_desc_1  = "Legendary artist performing live in Abu Dhabi. Contact VIP host at vip@space42arena.com or +971 50 123 4567.";

echo "Input Title:       $input_title_1\n";
echo "Raw Description:   $input_desc_1\n";
echo "Executing AI Service (Scrubbing PII & invoking Mock LLM)...\n\n";

$result_1 = $service->request_recommendations( $input_title_1, $input_desc_1, 'space42arena' );

if ( ! is_wp_error( $result_1 ) ) {
    echo "\033[32m[SUCCESS]\033[0m Recommendations Generated & Validated:\n";
    echo "  • Proposed SEO Title (Length: " . $result_1['validation_metadata']['title_length'] . "/60 chars):\n";
    echo "    \"" . $result_1['seo_title'] . "\"\n";
    echo "  • Proposed Meta Description (Length: " . $result_1['validation_metadata']['meta_length'] . "/160 chars):\n";
    echo "    \"" . $result_1['meta_description'] . "\"\n";
    echo "  • Suggested FAQs (" . count( $result_1['faqs'] ) . " entries):\n";
    foreach ( $result_1['faqs'] as $i => $faq ) {
        echo "    [" . ( $i + 1 ) . "] Q: " . $faq['question'] . "\n";
        echo "        A: " . $faq['answer'] . "\n";
    }
    echo "  • Publication Gate Status: \033[33m" . strtoupper( $result_1['status'] ) . "\033[0m (Human must click Approve)\n\n";
} else {
    echo "\033[31m[ERROR]\033[0m " . $result_1->get_error_message() . "\n\n";
}

// SCENARIO 2: DrVet Animal Hospital Service Page
echo "--- SCENARIO 2: DrVet Animal Hospital (Service Page) ---\n";
$input_title_2 = "Veterinary Diagnostic Imaging & MRI Center";
$input_desc_2  = "State of the art 1.5T MRI and imaging in Khalifa City Abu Dhabi.";

echo "Input Title:       $input_title_2\n";
echo "Raw Description:   $input_desc_2\n";
echo "Executing AI Service...\n\n";

$result_2 = $service->request_recommendations( $input_title_2, $input_desc_2, 'drvet' );

if ( ! is_wp_error( $result_2 ) ) {
    echo "\033[32m[SUCCESS]\033[0m Recommendations Generated & Validated:\n";
    echo "  • Proposed SEO Title (Length: " . $result_2['validation_metadata']['title_length'] . "/60 chars):\n";
    echo "    \"" . $result_2['seo_title'] . "\"\n";
    echo "  • Proposed Meta Description (Length: " . $result_2['validation_metadata']['meta_length'] . "/160 chars):\n";
    echo "    \"" . $result_2['meta_description'] . "\"\n";
    echo "  • Suggested FAQs (" . count( $result_2['faqs'] ) . " entries):\n";
    foreach ( $result_2['faqs'] as $i => $faq ) {
        echo "    [" . ( $i + 1 ) . "] Q: " . $faq['question'] . "\n";
        echo "        A: " . $faq['answer'] . "\n";
    }
    echo "  • Publication Gate Status: \033[33m" . strtoupper( $result_2['status'] ) . "\033[0m (Human must click Approve)\n\n";
} else {
    echo "\033[31m[ERROR]\033[0m " . $result_2->get_error_message() . "\n\n";
}

echo "====================================================================\n";
echo " DEMONSTRATION COMPLETE: Zero external network calls. All safe.\n";
echo "====================================================================\n";

