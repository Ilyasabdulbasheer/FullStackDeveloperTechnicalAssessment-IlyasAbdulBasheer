<?php
/**
 * Production-Ready Real LLM Provider Adapter (OpenAI / Gemini)
 *
 * Demonstrates clean architecture for connecting external AI models
 * while strictly maintaining server-side secret isolation and zero PII leakage.
 *
 * @package GroupEnquiryDesk\AI
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

require_once __DIR__ . '/class-ai-provider-interface.php';

class Real_LLM_Adapter implements AI_SEO_Provider_Interface {

    private $api_key;
    private $api_endpoint;
    private $model;

    /**
     * Constructor reads secret strictly from server environment
     */
    public function __construct( $api_key = null, $endpoint = 'https://api.openai.com/v1/chat/completions', $model = 'gpt-4o-mini' ) {
        // Retrieve secret strictly from server environment or wp-config constant
        // NEVER expose this to frontend JS or database options
        $this->api_key      = $api_key ? $api_key : ( defined( 'AI_API_KEY' ) ? AI_API_KEY : getenv( 'AI_API_KEY' ) );
        $this->api_endpoint = $endpoint;
        $this->model        = $model;
    }

    /**
     * Dispatch structured generation request to external LLM
     */
    public function generate( $page_title, $description, $brand_context ) {
        if ( empty( $this->api_key ) ) {
            throw new Exception( 'Real LLM Adapter requires a valid AI_API_KEY server environment variable.' );
        }

        $system_prompt = "You are a professional technical SEO specialist for commercial venues and healthcare facilities in Abu Dhabi.
You must return ONLY a valid JSON object matching this schema:
{
  \"seo_title\": \"string (max 60 chars)\",
  \"meta_description\": \"string (max 160 chars)\",
  \"faqs\": [
    {\"question\": \"string\", \"answer\": \"string\"}
  ]
}
CONSTRAINTS:
1. Do not invent ticket prices or backstage privileges not present in the text.
2. Do not invent medical claims or guarantee treatment cures.
3. Keep titles geo-targeted to Abu Dhabi / UAE.";

        $user_content = sprintf(
            "Brand Context: %s\nPage Title: %s\nPage Content: %s",
            $brand_context,
            $page_title,
            $description
        );

        $payload = array(
            'model'           => $this->model,
            'response_format' => array( 'type' => 'json_object' ),
            'temperature'     => 0.3,
            'messages'        => array(
                array( 'role' => 'system', 'content' => $system_prompt ),
                array( 'role' => 'user', 'content' => $user_content ),
            ),
        );

        $response = wp_remote_post( $this->api_endpoint, array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $this->api_key,
                'Content-Type'  => 'application/json',
            ),
            'body'    => wp_json_encode( $payload ),
            'timeout' => 15,
        ) );

        if ( is_wp_error( $response ) ) {
            throw new Exception( 'External LLM network error: ' . $response->get_error_message() );
        }

        $status_code = wp_remote_retrieve_response_code( $response );
        $body        = wp_remote_retrieve_body( $response );

        if ( 200 !== $status_code ) {
            throw new Exception( sprintf( 'LLM API returned HTTP %d: %s', $status_code, substr( $body, 0, 100 ) ) );
        }

        $decoded = json_decode( $body, true );
        if ( empty( $decoded['choices'][0]['message']['content'] ) ) {
            throw new Exception( 'Malformed payload received from LLM provider.' );
        }

        $parsed_content = json_decode( $decoded['choices'][0]['message']['content'], true );
        if ( ! is_array( $parsed_content ) ) {
            throw new Exception( 'LLM response content failed JSON parsing.' );
        }

        return $parsed_content;
    }
}

