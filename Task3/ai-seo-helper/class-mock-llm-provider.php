<?php
/**
 * Default Mock LLM Provider
 *
 * Provides realistic, deterministic, offline SEO recommendations
 * with zero external API dependencies or costs.
 *
 * @package GroupEnquiryDesk\AI
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

require_once __DIR__ . '/class-ai-provider-interface.php';

class Mock_LLM_Provider implements AI_SEO_Provider_Interface {

    /**
     * Generate deterministic structured SEO metadata
     */
    public function generate( $page_title, $description, $brand_context ) {
        // Normalize context
        $brand = strtolower( trim( $brand_context ) );

        if ( 'space42arena' === $brand || false !== stripos( $page_title, 'Arena' ) || false !== stripos( $page_title, 'Concert' ) ) {
            return $this->generate_arena_metadata( $page_title, $description );
        }

        if ( 'drvet' === $brand || false !== stripos( $page_title, 'Vet' ) || false !== stripos( $page_title, 'Hospital' ) ) {
            return $this->generate_vet_metadata( $page_title, $description );
        }

        return $this->generate_generic_metadata( $page_title, $description );
    }

    private function generate_arena_metadata( $page_title, $description ) {
        $clean_title = trim( str_ireplace( 'at Space42 Arena', '', $page_title ) );
        $seo_title   = sprintf( '%s Live in Abu Dhabi | Space42 Arena', substr( $clean_title, 0, 35 ) );

        $meta_description = sprintf(
            'Experience %s live at Space42 Arena, Al Raha Beach, Abu Dhabi. Book tickets for an unforgettable evening at the premier indoor arena.',
            substr( $clean_title, 0, 30 )
        );

        $faqs = array(
            array(
                'question' => sprintf( 'Where can I purchase tickets for %s at Space42 Arena?', $clean_title ),
                'answer'   => 'Official tickets can be reserved via the Space42 Arena ticketing portal or authorized ticketing partners.',
            ),
            array(
                'question' => 'Where is Space42 Arena located and what parking is available?',
                'answer'   => 'Space42 Arena is located on Al Raha Beach, Abu Dhabi, offering ample on-site visitor parking accessible from the E10 highway.',
            ),
            array(
                'question' => 'What is the bag policy for events at Space42 Arena?',
                'answer'   => 'For security and fast entry, bags larger than A4 size are not permitted inside the arena. Security bag checks are conducted at all entry gates.',
            ),
        );

        return array(
            'seo_title'        => $seo_title,
            'meta_description' => $meta_description,
            'faqs'             => $faqs,
        );
    }

    private function generate_vet_metadata( $page_title, $description ) {
        $clean_title = trim( $page_title );
        $seo_title   = sprintf( '%s in Abu Dhabi | DrVet Animal Hospital', substr( $clean_title, 0, 30 ) );

        $meta_description = sprintf(
            'Specialized %s at DrVet Animal Hospital in Khalifa City, Abu Dhabi. Compassionate veterinary care and 24/7 emergency support.',
            strtolower( substr( $clean_title, 0, 30 ) )
        );

        $faqs = array(
            array(
                'question' => sprintf( 'How do I book an appointment for %s?', strtolower( $clean_title ) ),
                'answer'   => 'Routine appointments can be requested online through our enquiry desk or by calling our clinic team at +971 2 412 6555.',
            ),
            array(
                'question' => 'What should I do if my pet has an after-hours emergency?',
                'answer'   => 'For critical veterinary emergencies after 9:00 PM, please call our 24/7 emergency hotline immediately at +971 50 509 7215.',
            ),
            array(
                'question' => 'Where is DrVet Animal Hospital located in Abu Dhabi?',
                'answer'   => 'Our veterinary hospital is located at 13 Al Maseer St, Khalifa City (SE-6), Abu Dhabi, UAE.',
            ),
        );

        return array(
            'seo_title'        => $seo_title,
            'meta_description' => $meta_description,
            'faqs'             => $faqs,
        );
    }

    private function generate_generic_metadata( $page_title, $description ) {
        return array(
            'seo_title'        => sprintf( '%s | Official Services', substr( $page_title, 0, 40 ) ),
            'meta_description' => substr( strip_tags( $description ), 0, 150 ),
            'faqs'             => array(
                array(
                    'question' => 'How can I get in touch regarding this service?',
                    'answer'   => 'Please submit an enquiry through our official contact form to speak with our client services team.',
                ),
                array(
                    'question' => 'Where are your services provided?',
                    'answer'   => 'Our operations span Abu Dhabi, Dubai, and the broader GCC region.',
                ),
            ),
        );
    }
}

