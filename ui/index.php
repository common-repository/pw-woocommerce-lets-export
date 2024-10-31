<?php
    if ( !defined( 'ABSPATH' ) ) { exit; }

    global $pw_lets_export;

    $exports = get_posts( array(
        'posts_per_page' => -1,
        'post_type' => 'pw_lets_export',
        'post_status' => 'publish'
    ) );
?>
<script>
    // For script.js
    var pwleAdminUrl = '<?php echo admin_url( 'admin.php?page=pw-lets-export' ); ?>';
</script>

<div class="wrap">
    <div class="pwle-header">
        <div class="pwle-title-container">
            <div class="pwle-title">PW WooCommerce Let's Export! </div>
            <div class="pwle-credits">by <a href="https://www.pimwick.com" target="_blank" class="pwle-link">Pimwick</a></div>
            <div class="pwle-version">v<?php echo $version; ?></div>
        </div>
    </div>
</div>
<div id="pwle-main-content" class="<?php echo ( isset( $pwle_export ) || count( $exports ) == 0 ) ? 'pwle-hidden' : ''; ?>">
    <a href="#" onClick="pwleWizardLoadStep(1); return false;" class="button button-primary" style="margin-bottom: 16px;">Create a new export</a>
    <?php
        if ( count( $exports ) > 0 ) {
            ?>
            <table class="pwle-table">
                <tr>
                    <th>Name</th>
                    <th>Created</th>
                    <th>&nbsp;</th>
                </tr>
                <?php
                    foreach( $exports as $export ) {
                        $title = $export->post_title;

                        $edit_url = admin_url( 'admin.php?page=pw-lets-export&export_id=' . $export->ID );

                        ?>
                        <tr data-export-id="<?php echo $export->ID; ?>">
                            <td>
                                <a href="<?php echo $edit_url; ?>" class="pwle-link" style="font-weight: 600;"><?php echo $title; ?></a>
                            </td>
                            <td>
                                <?php echo $pw_lets_export->format_date( $export->post_date ); ?>
                            </td>
                            <td>
                                <?php
                                    foreach ( $pw_lets_export->output_classes as $output_class ) {
                                        ?>
                                        <a href="#" class="pwle-export-button pwle-quick-export-button pwle-link" data-export_type="<?php echo $output_class->name; ?>" title="<?php echo esc_attr( $output_class->title ); ?>">
                                            <div style="background-color: <?php echo $output_class->color; ?>;">
                                                <i class="fa <?php echo $output_class->icon; ?>" aria-hidden="true"></i>
                                            </div>
                                        </a>
                                        <?php
                                    }
                                ?>
                                <a href="#" class="pwle-link pwle-delete-link"><i class="fa fa-trash-o"></i></a>
                            </td>
                        </tr>
                        <?php
                    }
                ?>
            </table>
            <script>
                jQuery(function() {
                    jQuery('#pwle-main-content').css('display', 'block');
                });
            </script>
            <?php
        }

        if ( isset( $pwle_export ) || count( $exports ) == 0 ) {
            ?>
            <script>
                jQuery(function() {
                    pwleWizardLoadStep(1);
                });
            </script>
            <?php
        }
    ?>
    <div style="margin-top: 5.0em;">
        We love making WordPress plugins and we love your feedback!<br>
        <a href="https://wordpress.org/support/plugin/pw-woocommerce-lets-export/reviews/" target="_blank">Leave a review on WordPress.org</a>
    </div>
</div>
<form id="pwle-form" method="POST">
    <input type="hidden" name="export_id" value="<?php echo isset( $pwle_export ) ? $pwle_export->ID : ''; ?>">
    <?php
        require( 'wizard/all_steps.php' );
    ?>
</form>
<div id="pwle-screen-export-table-container" style="margin-top: 2.0em;"></div>
<?php
