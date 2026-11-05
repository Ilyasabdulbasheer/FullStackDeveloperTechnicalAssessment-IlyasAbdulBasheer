<?php
/**
 * Frontend Public Enquiry Form Generator
 *
 * @package GroupEnquiryDesk
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Group_Enquiry_Form {

    /**
     * Render the shortcode form
     * Usage: [group_enquiry_form brand="space42arena"]
     *
     * @param array $atts Shortcode attributes
     * @return string HTML form markup
     */
    public static function render_shortcode( $atts ) {
        // Enqueue registered frontend assets
        wp_enqueue_style( 'group-enquiry-frontend' );
        wp_enqueue_script( 'group-enquiry-frontend' );

        $brands = Group_Enquiry_Post_Type::get_configured_brands();

        $parsed_atts = shortcode_atts(
            array(
                'brand' => 'ipstudios',
            ),
            $atts,
            'group_enquiry_form'
        );

        $selected_brand_key = sanitize_key( $parsed_atts['brand'] );

        // Fallback if an unconfigured brand is supplied in the shortcode
        if ( ! array_key_exists( $selected_brand_key, $brands ) ) {
            $selected_brand_key = 'ipstudios';
        }

        $brand_info    = $brands[ $selected_brand_key ];
        $enquiry_types = $brand_info['enquiry_types'];

        // Capture output buffer
        ob_start();
        ?>
        <div class="ged-form-wrapper" id="ged-enquiry-form-container">
            <div class="ged-header">
                <span class="ged-badge"><?php echo esc_html( $brand_info['name'] ); ?></span>
                <h3 class="ged-title"><?php esc_html_e( 'Submit an Enquiry', 'group-enquiry-desk' ); ?></h3>
                <p class="ged-subtitle">
                    <?php
                    /* translators: %s: Brand name */
                    printf( esc_html__( 'Contact the team at %s directly. We will get back to you promptly.', 'group-enquiry-desk' ), esc_html( $brand_info['name'] ) );
                    ?>
                </p>
            </div>

            <!-- Status message container for AJAX or redirected responses -->
            <div class="ged-alerts" role="alert" aria-live="polite"></div>

            <form class="ged-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
                <!-- Action hook for non-JS standard POST fallback -->
                <input type="hidden" name="action" value="group_enquiry_submit">

                <!-- 
                  SECURITY REQUIREMENT:
                  Brand is locked by page configuration (shortcode attribute), 
                  signed cryptographically in the nonce, and strictly validated on server.
                -->
                <input type="hidden" name="ged_brand" value="<?php echo esc_attr( $selected_brand_key ); ?>">
                <?php wp_nonce_field( 'group_enquiry_action_' . $selected_brand_key, 'ged_nonce' ); ?>

                <!-- ANTI-SPAM HONEYPOT TRAP: Real users cannot see this field; bots will fill it in -->
                <div class="ged-hp-field" aria-hidden="true" style="position:absolute; left:-9999px; top:-9999px;">
                    <label for="ged_website_hp_<?php echo esc_attr( $selected_brand_key ); ?>">Leave this empty</label>
                    <input type="text" id="ged_website_hp_<?php echo esc_attr( $selected_brand_key ); ?>" name="ged_website_hp" tabindex="-1" autocomplete="off">
                </div>

                <!-- Full Name -->
                <div class="ged-form-group">
                    <label for="ged_full_name"><?php esc_html_e( 'Full Name', 'group-enquiry-desk' ); ?> <span class="ged-required">*</span></label>
                    <input type="text" id="ged_full_name" name="ged_full_name" class="ged-input" required maxlength="120" placeholder="e.g. Jane Doe">
                </div>

                <!-- Email Address -->
                <div class="ged-form-group">
                    <label for="ged_email"><?php esc_html_e( 'Email Address', 'group-enquiry-desk' ); ?> <span class="ged-required">*</span></label>
                    <input type="email" id="ged_email" name="ged_email" class="ged-input" required maxlength="120" placeholder="e.g. jane@example.com">
                </div>

                <!-- Brand-Specific Enquiry Type -->
                <div class="ged-form-group">
                    <label for="ged_enquiry_type"><?php esc_html_e( 'Enquiry Type', 'group-enquiry-desk' ); ?> <span class="ged-required">*</span></label>
                    <select id="ged_enquiry_type" name="ged_enquiry_type" class="ged-select" required>
                        <option value=""><?php esc_html_e( '— Please select an option —', 'group-enquiry-desk' ); ?></option>
                        <?php foreach ( $enquiry_types as $type_key => $type_label ) : ?>
                            <option value="<?php echo esc_attr( $type_key ); ?>">
                                <?php echo esc_html( $type_label ); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Optional Preferred Date -->
                <div class="ged-form-group">
                    <label for="ged_preferred_date">
                        <?php esc_html_e( 'Preferred Date', 'group-enquiry-desk' ); ?>
                        <span class="ged-optional"><?php esc_html_e( '(Optional)', 'group-enquiry-desk' ); ?></span>
                    </label>
                    <input type="date" id="ged_preferred_date" name="ged_preferred_date" class="ged-input" min="<?php echo esc_attr( current_time( 'Y-m-d' ) ); ?>">
                </div>

                <!-- Message -->
                <div class="ged-form-group">
                    <label for="ged_message"><?php esc_html_e( 'Message', 'group-enquiry-desk' ); ?> <span class="ged-required">*</span></label>
                    <textarea id="ged_message" name="ged_message" class="ged-textarea" rows="4" required maxlength="3000" placeholder="<?php esc_attr_e( 'Please describe your request...', 'group-enquiry-desk' ); ?>"></textarea>
                </div>

                <!-- DrVet Specific Medical Disclaimer (Privacy Requirement) -->
                <?php if ( 'drvet' === $selected_brand_key ) : ?>
                    <div class="ged-disclaimer ged-disclaimer-drvet">
                        <small>
                            <strong><?php esc_html_e( 'Notice:', 'group-enquiry-desk' ); ?></strong>
                            <?php esc_html_e( 'This form is for general hospital and scheduling enquiries only. For urgent emergencies or clinical medical records, please call our 24/7 hotline directly at +971 50 509 7215.', 'group-enquiry-desk' ); ?>
                        </small>
                    </div>
                <?php endif; ?>

                <!-- Submit Button -->
                <div class="ged-form-actions">
                    <button type="submit" class="ged-submit-btn">
                        <span class="ged-btn-text"><?php esc_html_e( 'Submit Enquiry', 'group-enquiry-desk' ); ?></span>
                        <span class="ged-spinner" style="display:none;" aria-hidden="true">⏳</span>
                    </button>
                </div>
            </form>
        </div>
        <?php
        return ob_get_clean();
    }
}

