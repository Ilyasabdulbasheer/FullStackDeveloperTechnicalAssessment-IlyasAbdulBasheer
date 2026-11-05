<?php
/**
 * Admin Management View & Status Controller
 *
 * @package GroupEnquiryDesk
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Group_Enquiry_Admin {

    const REQUIRED_CAPABILITY = 'edit_others_posts';

    /**
     * Register Admin Menu Page
     */
    public static function register_menu() {
        add_menu_page(
            __( 'Group Enquiries', 'group-enquiry-desk' ),
            __( 'Group Enquiries', 'group-enquiry-desk' ),
            self::REQUIRED_CAPABILITY,
            'group-enquiries',
            array( __CLASS__, 'render_admin_page' ),
            'dashicons-email-alt',
            26
        );
    }

    /**
     * Enqueue Admin Styles
     */
    public static function enqueue_admin_assets( $hook ) {
        if ( 'toplevel_page_group-enquiries' !== $hook ) {
            return;
        }

        wp_enqueue_style(
            'group-enquiry-admin',
            GROUP_ENQUIRY_URL . 'assets/css/admin.css',
            array(),
            GROUP_ENQUIRY_VERSION
        );
    }

    /**
     * Handle Admin Status Update Action
     */
    public static function handle_status_update() {
        if ( ! current_user_can( self::REQUIRED_CAPABILITY ) ) {
            wp_die( esc_html__( 'Unauthorized: You do not have permission to manage enquiries.', 'group-enquiry-desk' ), 403 );
        }

        check_admin_referer( 'ged_admin_status_update', 'ged_status_nonce' );

        $post_id    = isset( $_POST['enquiry_id'] ) ? (int) $_POST['enquiry_id'] : 0;
        $new_status = isset( $_POST['new_status'] ) ? sanitize_key( $_POST['new_status'] ) : '';

        $valid_statuses = array(
            Group_Enquiry_Post_Type::STATUS_NEW,
            Group_Enquiry_Post_Type::STATUS_IN_PROGRESS,
            Group_Enquiry_Post_Type::STATUS_CLOSED,
        );

        if ( $post_id > 0 && in_array( $new_status, $valid_statuses, true ) ) {
            wp_update_post( array(
                'ID'          => $post_id,
                'post_status' => $new_status,
            ) );
        }

        $redirect_url = add_query_arg(
            array(
                'page'        => 'group-enquiries',
                'ged_updated' => '1',
            ),
            admin_url( 'admin.php' )
        );

        wp_safe_redirect( $redirect_url );
        exit;
    }

    /**
     * Render Admin Dashboard Page
     */
    public static function render_admin_page() {
        if ( ! current_user_can( self::REQUIRED_CAPABILITY ) ) {
            wp_die( esc_html__( 'Access Denied: Insufficient permissions to view customer enquiries.', 'group-enquiry-desk' ), 403 );
        }

        $brands = Group_Enquiry_Post_Type::get_configured_brands();

        // Filter parameters
        $filter_brand  = isset( $_GET['filter_brand'] ) ? sanitize_key( $_GET['filter_brand'] ) : '';
        $filter_status = isset( $_GET['filter_status'] ) ? sanitize_key( $_GET['filter_status'] ) : '';

        // Query arguments
        $query_args = array(
            'post_type'      => Group_Enquiry_Post_Type::POST_TYPE,
            'posts_per_page' => 50,
            'orderby'        => 'date',
            'order'          => 'DESC',
            'post_status'    => array(
                Group_Enquiry_Post_Type::STATUS_NEW,
                Group_Enquiry_Post_Type::STATUS_IN_PROGRESS,
                Group_Enquiry_Post_Type::STATUS_CLOSED,
            ),
        );

        if ( ! empty( $filter_status ) ) {
            $query_args['post_status'] = $filter_status;
        }

        if ( ! empty( $filter_brand ) && array_key_exists( $filter_brand, $brands ) ) {
            $query_args['meta_query'] = array(
                array(
                    'key'     => '_ged_brand',
                    'value'   => $filter_brand,
                    'compare' => '=',
                ),
            );
        }

        $enquiry_query = new WP_Query( $query_args );
        ?>
        <div class="wrap ged-admin-wrap">
            <h1 class="wp-heading-inline"><?php esc_html_e( 'Group Enquiry Desk — Multi-Brand Management', 'group-enquiry-desk' ); ?></h1>
            <p class="description">
                <?php esc_html_e( 'Secure cross-brand inbox for IP Studios, DrVet, Space42 Arena, Jazzbar, and MENA Lead.', 'group-enquiry-desk' ); ?>
            </p>

            <?php if ( isset( $_GET['ged_updated'] ) ) : ?>
                <div class="notice notice-success is-dismissible">
                    <p><?php esc_html_e( 'Enquiry status updated successfully.', 'group-enquiry-desk' ); ?></p>
                </div>
            <?php endif; ?>

            <!-- Filtering Bar -->
            <div class="ged-filter-bar tablenav top">
                <form method="get" action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>">
                    <input type="hidden" name="page" value="group-enquiries">

                    <label for="filter_brand" class="screen-reader-text"><?php esc_html_e( 'Filter by Brand', 'group-enquiry-desk' ); ?></label>
                    <select name="filter_brand" id="filter_brand">
                        <option value=""><?php esc_html_e( 'All Brands (5 Businesses)', 'group-enquiry-desk' ); ?></option>
                        <?php foreach ( $brands as $key => $brand ) : ?>
                            <option value="<?php echo esc_attr( $key ); ?>" <?php selected( $filter_brand, $key ); ?>>
                                <?php echo esc_html( $brand['name'] ); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <label for="filter_status" class="screen-reader-text"><?php esc_html_e( 'Filter by Status', 'group-enquiry-desk' ); ?></label>
                    <select name="filter_status" id="filter_status">
                        <option value=""><?php esc_html_e( 'All Statuses', 'group-enquiry-desk' ); ?></option>
                        <option value="<?php echo esc_attr( Group_Enquiry_Post_Type::STATUS_NEW ); ?>" <?php selected( $filter_status, Group_Enquiry_Post_Type::STATUS_NEW ); ?>>
                            <?php esc_html_e( 'New', 'group-enquiry-desk' ); ?>
                        </option>
                        <option value="<?php echo esc_attr( Group_Enquiry_Post_Type::STATUS_IN_PROGRESS ); ?>" <?php selected( $filter_status, Group_Enquiry_Post_Type::STATUS_IN_PROGRESS ); ?>>
                            <?php esc_html_e( 'In Progress', 'group-enquiry-desk' ); ?>
                        </option>
                        <option value="<?php echo esc_attr( Group_Enquiry_Post_Type::STATUS_CLOSED ); ?>" <?php selected( $filter_status, Group_Enquiry_Post_Type::STATUS_CLOSED ); ?>>
                            <?php esc_html_e( 'Closed', 'group-enquiry-desk' ); ?>
                        </option>
                    </select>

                    <button type="submit" class="button"><?php esc_html_e( 'Apply Filter', 'group-enquiry-desk' ); ?></button>

                    <?php if ( ! empty( $filter_brand ) || ! empty( $filter_status ) ) : ?>
                        <a href="<?php echo esc_url( admin_url( 'admin.php?page=group-enquiries' ) ); ?>" class="button button-link">
                            <?php esc_html_e( 'Reset Filters', 'group-enquiry-desk' ); ?>
                        </a>
                    <?php endif; ?>
                </form>
            </div>

            <!-- Enquiries Table -->
            <table class="wp-list-table widefat fixed striped table-view-list">
                <thead>
                    <tr>
                        <th scope="col" style="width: 70px;"><?php esc_html_e( 'ID', 'group-enquiry-desk' ); ?></th>
                        <th scope="col" style="width: 140px;"><?php esc_html_e( 'Brand', 'group-enquiry-desk' ); ?></th>
                        <th scope="col" style="width: 180px;"><?php esc_html_e( 'Customer', 'group-enquiry-desk' ); ?></th>
                        <th scope="col" style="width: 180px;"><?php esc_html_e( 'Enquiry Type', 'group-enquiry-desk' ); ?></th>
                        <th scope="col"><?php esc_html_e( 'Message', 'group-enquiry-desk' ); ?></th>
                        <th scope="col" style="width: 110px;"><?php esc_html_e( 'Pref. Date', 'group-enquiry-desk' ); ?></th>
                        <th scope="col" style="width: 130px;"><?php esc_html_e( 'Created', 'group-enquiry-desk' ); ?></th>
                        <th scope="col" style="width: 110px;"><?php esc_html_e( 'Status', 'group-enquiry-desk' ); ?></th>
                        <th scope="col" style="width: 160px;"><?php esc_html_e( 'Action', 'group-enquiry-desk' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ( $enquiry_query->have_posts() ) : ?>
                        <?php while ( $enquiry_query->have_posts() ) : $enquiry_query->the_post();
                            $post_id     = get_the_ID();
                            $brand_key   = get_post_meta( $post_id, '_ged_brand', true );
                            $full_name   = get_post_meta( $post_id, '_ged_full_name', true );
                            $email       = get_post_meta( $post_id, '_ged_email', true );
                            $type_label  = get_post_meta( $post_id, '_ged_enquiry_type_label', true );
                            $pref_date   = get_post_meta( $post_id, '_ged_preferred_date', true );
                            $post_status = get_post_status( $post_id );
                            $brand_name  = isset( $brands[ $brand_key ] ) ? $brands[ $brand_key ]['name'] : $brand_key;
                        ?>
                            <tr>
                                <td>#<?php echo esc_html( $post_id ); ?></td>
                                <td>
                                    <span class="ged-brand-badge ged-brand-<?php echo esc_attr( $brand_key ); ?>">
                                        <?php echo esc_html( $brand_name ); ?>
                                    </span>
                                </td>
                                <td>
                                    <strong><?php echo esc_html( $full_name ); ?></strong><br>
                                    <a href="<?php echo esc_url( 'mailto:' . $email ); ?>">
                                        <?php echo esc_html( $email ); ?>
                                    </a>
                                </td>
                                <td><?php echo esc_html( $type_label ? $type_label : '—' ); ?></td>
                                <td>
                                    <div class="ged-message-preview">
                                        <?php echo esc_html( wp_trim_words( get_the_content(), 25, '...' ) ); ?>
                                    </div>
                                </td>
                                <td><?php echo esc_html( $pref_date ? $pref_date : '—' ); ?></td>
                                <td><?php echo esc_html( get_the_date( 'Y-m-d H:i' ) ); ?></td>
                                <td>
                                    <span class="ged-status-badge ged-status-<?php echo esc_attr( $post_status ); ?>">
                                        <?php echo esc_html( Group_Enquiry_Post_Type::get_status_label( $post_status ) ); ?>
                                    </span>
                                </td>
                                <td>
                                    <!-- Quick Status Update Form with Nonce -->
                                    <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="ged-quick-status-form">
                                        <input type="hidden" name="action" value="group_enquiry_update_status">
                                        <input type="hidden" name="enquiry_id" value="<?php echo esc_attr( $post_id ); ?>">
                                        <?php wp_nonce_field( 'ged_admin_status_update', 'ged_status_nonce' ); ?>
                                        
                                        <select name="new_status" onchange="this.form.submit()" class="ged-status-select">
                                            <option value="<?php echo esc_attr( Group_Enquiry_Post_Type::STATUS_NEW ); ?>" <?php selected( $post_status, Group_Enquiry_Post_Type::STATUS_NEW ); ?>>
                                                <?php esc_html_e( 'Set: New', 'group-enquiry-desk' ); ?>
                                            </option>
                                            <option value="<?php echo esc_attr( Group_Enquiry_Post_Type::STATUS_IN_PROGRESS ); ?>" <?php selected( $post_status, Group_Enquiry_Post_Type::STATUS_IN_PROGRESS ); ?>>
                                                <?php esc_html_e( 'Set: In Progress', 'group-enquiry-desk' ); ?>
                                            </option>
                                            <option value="<?php echo esc_attr( Group_Enquiry_Post_Type::STATUS_CLOSED ); ?>" <?php selected( $post_status, Group_Enquiry_Post_Type::STATUS_CLOSED ); ?>>
                                                <?php esc_html_e( 'Set: Closed', 'group-enquiry-desk' ); ?>
                                            </option>
                                        </select>
                                    </form>
                                </td>
                            </tr>
                        <?php endwhile; wp_reset_postdata(); ?>
                    <?php else : ?>
                        <tr>
                            <td colspan="9" style="text-align:center; padding: 24px;">
                                <em><?php esc_html_e( 'No enquiries found matching your filter criteria.', 'group-enquiry-desk' ); ?></em>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php
    }
}

