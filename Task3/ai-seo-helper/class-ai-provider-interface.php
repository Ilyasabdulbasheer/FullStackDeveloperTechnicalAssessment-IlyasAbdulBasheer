<?php
/**
 * Abstract AI LLM Provider Interface
 *
 * @package GroupEnquiryDesk\AI
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

interface AI_SEO_Provider_Interface {

    /**
     * Generate structured SEO metadata and FAQs from page context
     *
     * @param string $page_title Raw page title
     * @param string $description Content/summary of the page
     * @param string $brand_context e.g. 'space42arena' or 'drvet'
     * @return array Array with keys: 'seo_title', 'meta_description', 'faqs'
     * @throws Exception If generation fails
     */
    public function generate( $page_title, $description, $brand_context );
}

