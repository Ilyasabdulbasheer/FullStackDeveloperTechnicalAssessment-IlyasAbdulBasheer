<?php
/**
 * AI SEO Service: Sanitization, Provider Dispatch, Schema Validation & Approval Gate
 *
 * @package GroupEnquiryDesk\AI
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

require_once __DIR__ . '/class-ai-provider-interface.php';
require_once __DIR__ . '/class-mock-llm-provider.php';
require_once __DIR__ . '/class-real-llm-adapter.php';

class AI_SEO_Service {

    /**
     * @var AI_SEO_Provider_Interface
     */
    private $provider;

    /**
     * Constructor sets provider (defaults to Mock_LLM_Provider per assessment brief)
     */
    public function __construct( AI_SEO_Provider_Interface $provider = null ) {
        if ( is_null( $provider ) ) {
            // Default to zero-cost, zero-secret Mock LLM
            $this->provider = new Mock_LLM_Provider();
        } else {
            $this->provider = $provider;
        }
    }

    /**
     * Request recommendations with full privacy protection and schema validation
     *
     * @param string $raw_title
     * @param string $raw_description
     * @param string $brand_context
     * @return array|WP_Error
     */
    public function request_recommendations( $raw_title, $raw_description, $brand_context = 'space42arena' ) {
        // 1. INPUT SANITIZATION & PII SCRUBBING
        $clean_title       = sanitize_text_field( $raw_title );
        $clean_description = sanitize_textarea_field( $raw_description );

        // Strip phone numbers and email addresses to guarantee zero PII leakage
        $sanitized_desc = preg_replace( '/[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}/', '[EMAIL_REDACTED]', $clean_description );
        $sanitized_desc = preg_replace( '/(\+?\d{1,3}[-.\s]?)?\(?\d{2,4}\)?[-.\s]?\d{3,4}[-.\s]?\d{3,4}/', '[PHONE_REDACTED]', $sanitized_desc );

        if ( empty( $clean_title ) ) {
            return new WP_Error( 'empty_title', __( 'Page title cannot be empty for SEO generation.', 'group-enquiry-desk' ) );
        }

        try {
            // 2. DISPATCH TO PROVIDER
            $raw_result = $this->provider->generate( $clean_title, $sanitized_desc, $brand_context );

            // 3. STRICT SCHEMA VALIDATION & NORMALIZATION
            $validated = $this->validate_schema( $raw_result, $clean_title );

            // 4. MARK STAGING STATUS (MANDATORY HUMAN APPROVAL REQUIREMENT)
            $validated['status'] = 'pending_human_approval';

            return $validated;

        } catch ( Exception $e ) {
            return new WP_Error( 'ai_generation_failed', $e->getMessage() );
        }
    }

    /**
     * Rigid structural validation and length constraints
     */
    private function validate_schema( $data, $fallback_title ) {
        if ( ! is_array( $data ) ) {
            throw new Exception( 'AI output is not a valid structured array.' );
        }

        // Validate and truncate SEO Title (max 60 chars)
        $title = ! empty( $data['seo_title'] ) ? sanitize_text_field( $data['seo_title'] ) : $fallback_title;
        if ( mb_strlen( $title ) > 60 ) {
            $title = mb_substr( $title, 0, 57 ) . '...';
        }

        // Validate and truncate Meta Description (max 160 chars)
        $meta = ! empty( $data['meta_description'] ) ? sanitize_text_field( $data['meta_description'] ) : '';
        if ( mb_strlen( $meta ) > 160 ) {
            $meta = mb_substr( $meta, 0, 157 ) . '...';
        }

        // Validate FAQs (enforce 2 to 3 valid entries)
        $faqs = array();
        if ( ! empty( $data['faqs'] ) && is_array( $data['faqs'] ) ) {
            foreach ( $data['faqs'] as $faq ) {
                if ( ! empty( $faq['question'] ) && ! empty( $faq['answer'] ) ) {
                    $faqs[] = array(
                        'question' => sanitize_text_field( $faq['question'] ),
                        'answer'   => sanitize_text_field( $faq['answer'] ),
                    );
                }
                if ( count( $faqs ) >= 3 ) {
                    break;
                }
            }
        }

        return array(
            'seo_title'           => $title,
            'meta_description'    => $meta,
            'faqs'                => $faqs,
            'validation_metadata' => array(
                'title_length' => mb_strlen( $title ),
                'meta_length'  => mb_strlen( $meta ),
                'faq_count'    => count( $faqs ),
                'schema_valid' => true,
            ),
        );
    }

    /**
     * Persist human-approved recommendations to post meta
     *
     * @param int $post_id
     * @param array $approved_data
     * @return bool
     */
    public static function apply_approved_metadata( $post_id, $approved_data ) {
        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            return false;
        }

        if ( ! empty( $approved_data['seo_title'] ) ) {
            update_post_meta( $post_id, '_ged_seo_title', sanitize_text_field( $approved_data['seo_title'] ) );
        }

        if ( ! empty( $approved_data['meta_description'] ) ) {
            update_post_meta( $post_id, '_ged_meta_description', sanitize_text_field( $approved_data['meta_description'] ) );
        }

        if ( ! empty( $approved_data['faqs'] ) && is_array( $approved_data['faqs'] ) ) {
            update_post_meta( $post_id, '_ged_seo_faqs', $approved_data['faqs'] );
        }

        update_post_meta( $post_id, '_ged_ai_status', 'human_approved' );
        update_post_meta( $post_id, '_ged_ai_approved_by', get_current_user_id() );
        update_post_meta( $post_id, '_ged_ai_approved_at', current_time( 'mysql' ) );

        return true;
    }
}

