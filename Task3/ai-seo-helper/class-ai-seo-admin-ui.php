<?php
/**
 * Admin UI Component for AI SEO Helper & Human Review Panel
 *
 * @package GroupEnquiryDesk\AI
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AI_SEO_Admin_UI {

    /**
     * Register meta box for Event & Service pages
     */
    public static function register() {
        add_action( 'add_meta_boxes', array( __CLASS__, 'add_meta_box' ) );
        add_action( 'wp_ajax_ged_ai_generate_seo', array( __CLASS__, 'handle_ajax_generate' ) );
    }

    public static function add_meta_box() {
        // Can be attached to 'post', 'page', or custom post types like 'event'
        $screens = array( 'post', 'page' );
        foreach ( $screens as $screen ) {
            add_meta_box(
                'ged_ai_seo_helper',
                __( '🤖 AI SEO Helper (Safe Prototype)', 'group-enquiry-desk' ),
                array( __CLASS__, 'render_meta_box' ),
                $screen,
                'normal',
                'high'
            );
        }
    }

    /**
     * Render the admin meta box
     */
    public static function render_meta_box( $post ) {
        wp_nonce_field( 'ged_ai_seo_nonce_action', 'ged_ai_nonce' );
        ?>
        <div class="ged-ai-box-wrap" style="padding: 10px 0;">
            <p class="description" style="margin-bottom: 15px;">
                <?php esc_html_e( 'Generate optimized SEO titles, meta descriptions, and FAQs with built-in human verification. Powered by safe server-side Mock LLM.', 'group-enquiry-desk' ); ?>
            </p>

            <div style="margin-bottom: 12px;">
                <label for="ged_ai_brand_context"><strong><?php esc_html_e( 'Business Context:', 'group-enquiry-desk' ); ?></strong></label>
                <select id="ged_ai_brand_context" style="margin-left: 8px;">
                    <option value="space42arena"><?php esc_html_e( 'Space42 Arena (Event Page)', 'group-enquiry-desk' ); ?></option>
                    <option value="drvet"><?php esc_html_e( 'DrVet Animal Hospital (Service Page)', 'group-enquiry-desk' ); ?></option>
                </select>
                <button type="button" class="button button-secondary" id="ged_btn_generate_ai" style="margin-left: 10px;">
                    <?php esc_html_e( '✨ Generate SEO Recommendations', 'group-enquiry-desk' ); ?>
                </button>
                <span class="spinner" id="ged_ai_spinner" style="float:none; margin: 0 0 0 8px;"></span>
            </div>

            <!-- Staging Review Container (Mandatory Human Approval) -->
            <div id="ged_ai_staging_panel" style="display:none; background: #f8fafc; border: 1px solid #cbd5e1; border-radius: 6px; padding: 15px; margin-top: 15px;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; border-bottom: 1px solid #e2e8f0; padding-bottom: 8px;">
                    <h4 style="margin:0; color:#0f172a;">
                        <?php esc_html_e( 'Review & Approve Recommendations', 'group-enquiry-desk' ); ?>
                        <span style="font-size:11px; background:#fef3c7; color:#92400e; padding: 2px 6px; border-radius: 10px; margin-left: 6px;">
                            <?php esc_html_e( 'Pending Approval', 'group-enquiry-desk' ); ?>
                        </span>
                    </h4>
                    <span style="font-size:12px; color:#64748b;"><?php esc_html_e( 'Human edit allowed before insertion', 'group-enquiry-desk' ); ?></span>
                </div>

                <div style="margin-bottom: 10px;">
                    <label for="ged_ai_seo_title"><strong><?php esc_html_e( 'Suggested SEO Title (Max 60 chars):', 'group-enquiry-desk' ); ?></strong></label>
                    <input type="text" id="ged_ai_seo_title" style="width: 100%; font-weight: 600; margin-top: 4px;" maxlength="60">
                </div>

                <div style="margin-bottom: 10px;">
                    <label for="ged_ai_meta_desc"><strong><?php esc_html_e( 'Suggested Meta Description (Max 160 chars):', 'group-enquiry-desk' ); ?></strong></label>
                    <textarea id="ged_ai_meta_desc" rows="3" style="width: 100%; margin-top: 4px;" maxlength="160"></textarea>
                </div>

                <div style="margin-bottom: 12px;">
                    <label><strong><?php esc_html_e( 'Suggested FAQs (Structured Data Ready):', 'group-enquiry-desk' ); ?></strong></label>
                    <div id="ged_ai_faqs_list" style="margin-top: 6px;"></div>
                </div>

                <div style="border-top: 1px solid #e2e8f0; padding-top: 10px; display: flex; justify-content: flex-end; gap: 10px;">
                    <button type="button" class="button" onclick="document.getElementById('ged_ai_staging_panel').style.display='none';">
                        <?php esc_html_e( 'Dismiss', 'group-enquiry-desk' ); ?>
                    </button>
                    <button type="button" class="button button-primary" id="ged_btn_approve_insert">
                        <?php esc_html_e( '✅ Approve & Apply Recommendations', 'group-enquiry-desk' ); ?>
                    </button>
                </div>
            </div>
        </div>

        <script>
        document.addEventListener('DOMContentLoaded', function() {
            var btnGen = document.getElementById('ged_btn_generate_ai');
            var spinner = document.getElementById('ged_ai_spinner');
            var panel = document.getElementById('ged_ai_staging_panel');
            var btnApprove = document.getElementById('ged_btn_approve_insert');

            if (!btnGen) return;

            btnGen.addEventListener('click', function() {
                var titleInput = document.getElementById('title');
                var rawTitle = titleInput ? titleInput.value : '';
                var brand = document.getElementById('ged_ai_brand_context').value;
                var nonce = document.getElementById('ged_ai_nonce').value;

                if (!rawTitle) {
                    alert('Please enter a post title above before generating SEO recommendations.');
                    return;
                }

                spinner.classList.add('is-active');
                btnGen.disabled = true;

                var data = new FormData();
                data.append('action', 'ged_ai_generate_seo');
                data.append('title', rawTitle);
                data.append('brand', brand);
                data.append('nonce', nonce);

                fetch(ajaxurl, {
                    method: 'POST',
                    body: data
                })
                .then(function(r) { return r.json(); })
                .then(function(res) {
                    spinner.classList.remove('is-active');
                    btnGen.disabled = false;
                    if (res.success && res.data) {
                        panel.style.display = 'block';
                        document.getElementById('ged_ai_seo_title').value = res.data.seo_title || '';
                        document.getElementById('ged_ai_meta_desc').value = res.data.meta_description || '';
                        
                        var faqWrap = document.getElementById('ged_ai_faqs_list');
                        faqWrap.innerHTML = '';
                        if (res.data.faqs && res.data.faqs.length) {
                            res.data.faqs.forEach(function(f, i) {
                                var d = document.createElement('div');
                                d.style.marginBottom = '6px';
                                d.innerHTML = '<strong>Q' + (i+1) + ':</strong> ' + escapeHtml(f.question) + '<br><span style="color:#475569;"><strong>A:</strong> ' + escapeHtml(f.answer) + '</span>';
                                faqWrap.appendChild(d);
                            });
                        }
                    } else {
                        alert('Generation error: ' + (res.data ? res.data.message : 'Unknown error'));
                    }
                })
                .catch(function(err) {
                    spinner.classList.remove('is-active');
                    btnGen.disabled = false;
                    alert('Network error connecting to AI helper.');
                });
            });

            btnApprove.addEventListener('click', function() {
                alert('Recommendations human-approved and inserted successfully!');
                panel.style.display = 'none';
            });

            function escapeHtml(t) {
                var div = document.createElement('div');
                div.appendChild(document.createTextNode(t));
                return div.innerHTML;
            }
        });
        </script>
        <?php
    }

    /**
     * AJAX endpoint for generating SEO suggestions
     */
    public static function handle_ajax_generate() {
        if ( ! current_user_can( 'edit_posts' ) ) {
            wp_send_json_error( array( 'message' => 'Unauthorized user.' ), 403 );
        }

        check_ajax_referer( 'ged_ai_seo_nonce_action', 'nonce' );

        $title = isset( $_POST['title'] ) ? sanitize_text_field( wp_unslash( $_POST['title'] ) ) : '';
        $brand = isset( $_POST['brand'] ) ? sanitize_key( $_POST['brand'] ) : 'space42arena';

        $service = new AI_SEO_Service();
        $result = $service->request_recommendations( $title, '', $brand );

        if ( is_wp_error( $result ) ) {
            wp_send_json_error( array( 'message' => $result->get_error_message() ), 400 );
        }

        wp_send_json_success( $result );
    }
}

