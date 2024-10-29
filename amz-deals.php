<?php
/*
Plugin Name: Coupons page for Amazon affiliates
Plugin URI:  https://AmzCoupons.com/
Description: WordPress Plugin for Amazon Affiliates will help you monetize your website with a customized deals page.
Text Domain: amzcoupons
Version: 1.5
*/

if (!defined('ABSPATH')) exit; // Exit if accessed directly

define('WP_AMAZON_DEAL_FORM_URL', plugin_dir_url(__FILE__));
define('WP_AMAZON_DEAL_FORM_DIR', plugin_dir_path(__FILE__));
define('AMAZONPRODIMAGESURL', "https://images-na.ssl-images-amazon.com/images/I/");


if (!class_exists('amz_deals')) :

    class amz_deals
    {
        private object $deal;

        public function setDeal($deal)
        {
            $this->deal = $deal;
        }

        public function __construct()
        {
            $this->init();
        }

        public function init()
        {
            if (!session_id()) {
                @session_start();
            }

            add_action('admin_menu', array($this, 'amazon_reponder_admin_menu'), 99);

            add_action('wp_ajax_show_category_data', array($this, 'show_category_data'));
            add_action('wp_ajax_nopriv_show_category_data', array($this, 'show_category_data'));

            add_action('init', array($this, 'country_gloabal_array'));

            add_shortcode('amazon-deal-list', array($this, 'show_data_on_frontend'));

            add_action('wp_ajax_show_category_data_frontend', array($this, 'show_category_data_frontend'));
            add_action('wp_ajax_nopriv_show_category_data_frontend', array($this, 'show_category_data_frontend'));

            add_action('wp_enqueue_scripts', array($this, 'amazon_form_scripts'));

            add_action('wp_head', array($this, 'ajax_form_scripts'));


            add_action('init', array($this, 'custom_rewrite_basic'));
            add_filter('query_vars', array($this, 'prefix_register_query_var'));
            add_filter('template_include', array($this, 'prefix_url_rewrite_templates'), 100);

            add_action('wp_head', array($this, 'add_meta_tags'), 2);
            add_filter('pre_get_document_title', array($this, 'title_callback'), 50);

        }

        public function amazon_form_scripts()
        {
            wp_enqueue_script('jquery');
            wp_enqueue_script('jquery-ui-core');
            wp_enqueue_script('jquery-ui-slider');

            wp_enqueue_script('amazondeal_front_js', WP_AMAZON_DEAL_FORM_URL . 'js/frontend.min.js', array(), '1.2', true);
            wp_enqueue_script('amazondeal_oo_js', WP_AMAZON_DEAL_FORM_URL . 'js/oo.min.js', array(), '1.2', true);
            wp_enqueue_style('amz-deals-mixed', WP_AMAZON_DEAL_FORM_URL . 'css/mixed.css');
            wp_localize_script('public_main_script', 'amazon-deals', array('url' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('ajaxnonce')));

        }

        public function add_meta_tags()
        {
            if (get_query_var('post_type') == "cpndealpage-oo") {
//                $id = get_query_var('product_id');
//                $response_deal_page = wp_remote_get(' https://api.discountcodes.net/deal/' . $id);
//                $data_deal_page = json_decode(wp_remote_retrieve_body($response_deal_page));
//                if ($data_deal_page->status == "NOTALLOWED") {
//                    exit();
//                }
//                unset($data_deal_page->status);
//                $thesale = $data_deal_page->msg;
                //echo '<meta name="description" content="' . strip_tags($thesale->text) . '" />' . "\n";
                if (isset($this->deal->text)) {
                    echo '<meta name="description" content="' . strip_tags($this->shorter($this->deal->text, 150)) . '" />' . "\n";
                }

            }
        }


        public function title_callback($title)
        {
            if (get_query_var('post_type') == "cpndealpage-oo") {
                $id = get_query_var('product_id');
                $response_deal_page = wp_remote_get(' https://api.discountcodes.net/deal/' . $id);
                $data_deal_page = json_decode(wp_remote_retrieve_body($response_deal_page));
                //print_r($data_deal_page);
                //exit();
                if ($data_deal_page->status == "NOTALLOWED") {
                    exit();
                }
                $thesale = $data_deal_page->msg;
                if (!$thesale) {
                    $this->setDeal($data_deal_page);
                    return esc_html(__('NOT FOUND', 'amzcoupons'));
                } else {
                    $this->setDeal($thesale);
                }
                return $this->shorter($this->deal->title, 200);
            }
            return $title;
        }


        public function amazon_reponder_admin_menu()
        {
            add_menu_page(__('AMZ Coupons', 'amzcoupons'), __('AMZ Coupons', 'amzcoupons'), 'manage_options', 'amzcoupons', array($this, 'amazon_settings_page'), 'dashicons-star-filled');
        }


        public function ajax_form_scripts()
        {
            ?>
            <script type="text/javascript">
                const amazon_deals_jv_ajax_url = '<?php echo admin_url("admin-ajax.php"); ?>';
                //const ajax_nonce = '<?php echo wp_create_nonce("secure_nonce_name"); ?>';
            </script>
            <?php
        }


        public function amazon_settings_page()
        {


            global $AMAZONDEALS_COUNTRIE_ARR;

            $is_updated = false;

            if (isset($_POST['submited']) && $_POST['submited'] != '') {

                //echo "<pre>"; print_r(wp_kses($_POST['countries']));exit();
                update_option('amazondeal-countrie', CPNDEALS_sanitizeArray($_POST['countries']));

                if (array_key_exists("22category", $_POST)) {
                    update_option('amazondeal-countrie-category22', CPNDEALS_sanitizeArray($_POST['22category']));
                }
                if (array_key_exists("22country-select-all", $_POST)) {
                    update_option('amazondeal-countrie-category22', sanitize_text_field($_POST['22country-select-all']));
                }

                if (array_key_exists("23category", $_POST)) {
                    update_option('amazondeal-countrie-category23', CPNDEALS_sanitizeArray($_POST['23category']));
                }
                if (array_key_exists("23country-select-all", $_POST)) {
                    update_option('amazondeal-countrie-category23', sanitize_text_field($_POST['23country-select-all']));
                }

                if (array_key_exists("24category", $_POST)) {
                    update_option('amazondeal-countrie-category24', CPNDEALS_sanitizeArray($_POST['24category']));
                }
                if (array_key_exists("24country-select-all", $_POST)) {
                    update_option('amazondeal-countrie-category24', sanitize_text_field($_POST['24country-select-all']));
                }

                if (array_key_exists("25category", $_POST)) {
                    update_option('amazondeal-countrie-category25', CPNDEALS_sanitizeArray($_POST['25category']));
                }
                if (array_key_exists("25country-select-all", $_POST)) {
                    update_option('amazondeal-countrie-category25', sanitize_text_field($_POST['25country-select-all']));
                }

                if (array_key_exists("26category", $_POST)) {
                    update_option('amazondeal-countrie-category26', CPNDEALS_sanitizeArray($_POST['26category']));
                }
                if (array_key_exists("26country-select-all", $_POST)) {
                    update_option('amazondeal-countrie-category26', sanitize_text_field($_POST['26country-select-all']));
                }

                if (array_key_exists("27category", $_POST)) {
                    update_option('amazondeal-countrie-category27', CPNDEALS_sanitizeArray($_POST['27category']));
                }
                if (array_key_exists("27country-select-all", $_POST)) {
                    update_option('amazondeal-countrie-category27', sanitize_text_field($_POST['27country-select-all']));
                }

                if (array_key_exists("28category", $_POST)) {
                    update_option('amazondeal-countrie-category28', CPNDEALS_sanitizeArray($_POST['28category']));
                }
                if (array_key_exists("28country-select-all", $_POST)) {
                    update_option('amazondeal-countrie-category28', sanitize_text_field($_POST['28country-select-all']));
                }

                if (array_key_exists("29category", $_POST)) {
                    update_option('amazondeal-countrie-category29', CPNDEALS_sanitizeArray($_POST['29category']));
                }
                if (array_key_exists("29country-select-all", $_POST)) {
                    update_option('amazondeal-countrie-category29', sanitize_text_field($_POST['29country-select-all']));
                }

                if (array_key_exists("30category", $_POST)) {
                    update_option('amazondeal-countrie-category30', CPNDEALS_sanitizeArray($_POST['30category']));
                }
                if (array_key_exists("30country-select-all", $_POST)) {
                    update_option('amazondeal-countrie-category30', sanitize_text_field($_POST['30country-select-all']));
                }

                if (array_key_exists("31category", $_POST)) {
                    update_option('amazondeal-countrie-category31', CPNDEALS_sanitizeArray($_POST['31category']));
                }
                if (array_key_exists("31country-select-all", $_POST)) {
                    update_option('amazondeal-countrie-category31', sanitize_text_field($_POST['31country-select-all']));
                }


                update_option('amazondeal-affiliate-22', sanitize_text_field($_POST['affiliate22']));
                update_option('amazondeal-affiliate-23', sanitize_text_field($_POST['affiliate23']));
                update_option('amazondeal-affiliate-24', sanitize_text_field($_POST['affiliate24']));
                update_option('amazondeal-affiliate-25', sanitize_text_field($_POST['affiliate25']));
                update_option('amazondeal-affiliate-26', sanitize_text_field($_POST['affiliate26']));
                update_option('amazondeal-affiliate-27', sanitize_text_field($_POST['affiliate27']));
                update_option('amazondeal-affiliate-28', sanitize_text_field($_POST['affiliate28']));
                update_option('amazondeal-affiliate-29', sanitize_text_field($_POST['affiliate29']));
                update_option('amazondeal-affiliate-30', sanitize_text_field($_POST['affiliate30']));
                update_option('amazondeal-affiliate-31', sanitize_text_field($_POST['affiliate31']));

                update_option('amazondeal-discount', sanitize_text_field($_POST['discount']));

                update_option('amazondeal-primary-color', sanitize_text_field($_POST['primary-color']));
                update_option('amazondeal-secondary-color', sanitize_text_field($_POST['secondary-color']));
                update_option('amazondeal-deals-page', sanitize_text_field($_POST['amazondeal-deals-page']));
                update_option('amazondeal-deals-defaultsorting', sanitize_text_field($_POST['amazondeal-deals-defaultsorting']));


                $is_updated = true;
            }

            ?>
            <style>
                .category td {
                    display: inline-block;
                    width: 20%;
                }

                .text-capitalize {
                    text-transform: uppercase;
                }
            </style>
            <div class="wrap">
                <h1><?php esc_html_e('Amazon Deals Settings', 'amzcoupons') ?></h1>

                <?php if ($is_updated) { ?>
                    <div class="updated settings-error notice is-dismissible" style="margin: 0 0 20px; max-width: 845px;">
                        <p><strong><?php esc_html_e('Settings saved successfully.', 'amzcoupons') ?></strong></p>
                        <button class="notice-dismiss" type="button">
                            <span class="screen-reader-text"><?php esc_html_e('Dismiss this notice.', 'amzcoupons') ?></span>
                        </button>
                    </div>
                <?php } ?>

                <?php

                $response = wp_remote_get('https://api.discountcodes.net/conw/');
                $body = json_decode(wp_remote_retrieve_body($response));
                //echo "<BR>===========" . $body->status;
                if ($body->status == "NOTALLOWED") {
                    echo esc_attr($body->msg);
                } else {
                    if ($body->status == "OKI") {
                        ?>

                        <form method="post">

                            <table class="form-table">

                                <tr>
                                    <th scope="row"><label> <?php esc_html_e('Countries', 'amzcoupons') ?> </label></th>

                                    <td>
                                        <select title="<?php esc_attr_e('Countries', 'amzcoupons') ?>"
                                                name="countries[]" multiple id="countries" style="height: 210px;width: 210px;">
                                            <?php
                                            $con = get_option('amazondeal-countrie');
                                            foreach ($AMAZONDEALS_COUNTRIE_ARR as $key => $value) { ?>
                                                <option value="<?php echo esc_attr($key); ?>" <?php echo(!empty($con) && in_array($key, $con) ? ' selected="selected"' : '') ?>><?php echo esc_html($value['title']); ?></option>
                                            <?php } ?>
                                        </select>

                                    </td>
                                </tr>

                                <tr class="category">
                                    <th><label><?php esc_html_e('Category', 'amzcoupons') ?></label></th>

                                    <?php
                                    $conid = array();
                                    $conid[22] = get_option('amazondeal-countrie-category22');
                                    $conid[23] = get_option('amazondeal-countrie-category23');
                                    $conid[24] = get_option('amazondeal-countrie-category24');
                                    $conid[25] = get_option('amazondeal-countrie-category25');
                                    $conid[26] = get_option('amazondeal-countrie-category26');
                                    $conid[27] = get_option('amazondeal-countrie-category27');
                                    $conid[28] = get_option('amazondeal-countrie-category28');
                                    $conid[29] = get_option('amazondeal-countrie-category29');
                                    $conid[30] = get_option('amazondeal-countrie-category30');
                                    $conid[31] = get_option('amazondeal-countrie-category31');

                                    foreach ($conid as $key => $country_id) {

                                        if (is_array($con) && in_array($key, $con)) {

                                            $response = wp_remote_get('https://api.discountcodes.net/categories-by-country/' . sanitize_text_field($key) . '/');
                                            $body = json_decode(wp_remote_retrieve_body($response));

                                            unset($body->status);
                                            ?>

                                            <td>
                                                <label><b><?php
                                                        if (array_key_exists($key, $AMAZONDEALS_COUNTRIE_ARR)) {
                                                            echo esc_attr($AMAZONDEALS_COUNTRIE_ARR[$key]['title']);
                                                        } ?>
                                                    </b></label><br><br>
                                                <input type="checkbox" name="<?php echo esc_attr($key) ?>country-select-all" class="chk_boxes<?php echo esc_attr($key); ?>" label="check all"
                                                       title="<?php esc_attr_e('Select ALL Categories', 'amzcoupons'); ?>"
                                                       value="on" <?php echo($conid[$key] == 'on' ? 'checked' : ''); ?>> <?php esc_html_e('ALL Categories', 'amzcoupons') ?> <br>
                                                <br>


                                                <select name="<?php echo esc_attr($key); ?>category[]" multiple
                                                        title="<?php esc_attr_e('Category', 'amzcoupons'); ?>"
                                                        id="Category" class="country-cat<?php echo esc_attr($key); ?>">
                                                    <?php
                                                    foreach ($body->msg as $value1) { ?>
                                                        <option value="<?php echo esc_attr($value1->id); ?>" <?php if (is_array($country_id) && in_array($value1->id, $country_id)) echo "selected"; ?>><?php echo esc_html($value1->title); ?></option>
                                                        <?php
                                                    }
                                                    ?>
                                                </select>
                                            </td>
                                            <script>
                                                jQuery(document).ready(function () {
                                                    var defult_checked = jQuery('.chk_boxes<?php echo esc_attr($key); ?>').is(':checked');
                                                    if (defult_checked == true) {
                                                        jQuery('.country-cat<?php echo esc_attr($key); ?> option').prop('selected', true);
                                                        jQuery('.country-cat<?php echo esc_attr($key); ?> option').prop('disabled', true);

                                                    }
                                                    jQuery('.country-cat<?php echo esc_attr($key); ?>').prop('required', true);
                                                    jQuery('.chk_boxes<?php echo esc_attr($key); ?>').click(function () {
                                                        var checked = jQuery(this).is(':checked');

                                                        if (checked == true) {
                                                            jQuery('.country-cat<?php echo esc_attr($key); ?>').prop('required', false);
                                                            jQuery('.country-cat<?php echo esc_attr($key); ?> option').prop('selected', true);
                                                            jQuery('.country-cat<?php echo esc_attr($key); ?> option').prop('disabled', true);
                                                        } else {
                                                            jQuery('.country-cat<?php echo esc_attr($key); ?>').prop('required', true);
                                                            jQuery('.country-cat<?php echo esc_attr($key); ?> option').prop('selected', false);
                                                            jQuery('.country-cat<?php echo esc_attr($key); ?> option').prop('disabled', false);
                                                        }
                                                    });
                                                });

                                            </script>
                                            <?php

                                        }
                                    } ?>

                                </tr>

                                <tr>
                                    <th><label for="amazondeal-deals-page"><?php esc_html_e('Deals Main Page', 'amzcoupons'); ?></label></th>
                                    <td>
                                        <input type="text" id="amazondeal-deals-page" name="amazondeal-deals-page" class="amazondeal-deals-page" style="width: 380px"
                                               value="<?php echo esc_attr(get_option('amazondeal-deals-page')); ?>">
                                    </td>
                                </tr>


                                <tr>
                                    <th><label for="discount"><?php esc_html_e('Min Discount (%)', 'amzcoupons'); ?></label></th>
                                    <td>
                                        <input type="number" id="discount" name="discount" min="30" max="100" class="discount" value="<?php echo esc_attr(get_option('amazondeal-discount')) ?: 30; ?>">
                                    </td>
                                </tr>

                                <tr>
                                    <th><label for="primary-color"><?php esc_html_e('Primary Color #', 'amzcoupons'); ?></label></th>
                                    <td>
                                        <input type="text" id="primary-color" name="primary-color" class="primary-color" value="<?php echo esc_attr(get_option('amazondeal-primary-color')); ?>">
                                    </td>
                                </tr>

                                <tr>
                                    <th><label for="secondary-color"><?php esc_html_e('Secondary Color #', 'amzcoupons'); ?></label></th>
                                    <td>
                                        <input type="text" id="secondary-color" name="secondary-color" class="secondary-color"
                                               value="<?php echo esc_attr(get_option('amazondeal-secondary-color')); ?>">
                                    </td>
                                </tr>

                                <tr>
                                    <th><label for="secondary-color"><?php esc_html_e('Default sorting', 'amzcoupons'); ?></label></th>
                                    <td>
                                        <?php
                                        $savedDefaultSorting = esc_attr(get_option('amazondeal-deals-defaultsorting'));
                                        ?>
                                        <select class="text-capitalize" name="amazondeal-deals-defaultsorting" title="Select default sorting">
                                            <option value="2" <?php if ($savedDefaultSorting == 2) echo "selected"; ?> >HIGHEST DISCOUNT FIRST</option>
                                            <option value="5" <?php if ($savedDefaultSorting == 5) echo "selected"; ?> >RECENTLY UPDATED</option>
                                            <option value="3" <?php if ($savedDefaultSorting == 3) echo "selected"; ?> >RECENTLY ADDED</option>
                                            <option value="1" <?php if ($savedDefaultSorting == 1) echo "selected"; ?> >LOWEST PRICE FIRST</option>
                                            <option value="4" <?php if ($savedDefaultSorting == 4) echo "selected"; ?>>ORIGINAL PRICE - HIGHEST FIRST</option>
                                        </select>
                                    </td>
                                </tr>

                                <tr>
                                    <th><h3><?php esc_html_e('Affiliate Tags', 'amzcoupons'); ?></h3></th>
                                </tr>

                                <?php

                                foreach ($AMAZONDEALS_COUNTRIE_ARR as $key => $value) { ?>
                                    <tr>
                                        <th scope="row">
                                            <label for="affiliate<?php echo esc_attr($key); ?>">
                                                <?php echo esc_html($value['title']); ?>
                                            </label>
                                        </th>
                                        <td>
                                            <input type="text" id="affiliate<?php echo esc_attr($key); ?>" name="affiliate<?php echo esc_attr($key); ?>"
                                                   value="<?php echo esc_attr(get_option('amazondeal-affiliate-' . $key)); ?>">
                                        </td>
                                    </tr>

                                <?php } ?>

                                <script>

                                    var ids = [];
                                    jQuery("#countries").change(function () {

                                        jQuery('#countries :selected').each(function () {

                                            if (jQuery.inArray(jQuery(this).val(), ids) === -1) {
                                                ids.push(jQuery(this).val());
                                            }

                                        });

                                        for (var i = 0; i < ids.length; i++) {
                                            if (jQuery(this).val().indexOf(ids[i]) < 0) {
                                                ids.splice(i, 1);
                                                --i;
                                            }
                                        }


                                        jQuery.ajax({
                                            url: ajaxurl,
                                            type: "GET",
                                            dataType: 'html',
                                            data: {
                                                action: 'show_category_data',
                                                ids: ids,
                                            },
                                            beforeSend: function (response) {
                                                jQuery(".category").html(response);
                                            },
                                            success: function (response) {
                                                jQuery(".category").html(response);

                                            },
                                            error: function (data) {

                                            }
                                        });

                                    });

                                </script>


                            </table>

                            <p class="submit"><input type="submit" name="submited" id="submited" class="button button-primary" value="<?php esc_attr_e('Save Changes', 'amzcoupons'); ?>"></p>

                        </form>

                        <?php
                    }
                }
                ?>

            </div>
            <?php
        }

        public function show_category_data()
        {

            global $wpdb;

            global $AMAZONDEALS_COUNTRIE_ARR;

            $country_ids = $_GET['ids'];

            ?>

            <th scope="row"><label> <?php esc_html_e('Category', 'amzcoupons') ?> </label></th>

            <?php

            $conid = array();
            $conid[22] = get_option('amazondeal-countrie-category22');
            $conid[23] = get_option('amazondeal-countrie-category23');
            $conid[24] = get_option('amazondeal-countrie-category24');
            $conid[25] = get_option('amazondeal-countrie-category25');
            $conid[26] = get_option('amazondeal-countrie-category26');
            $conid[27] = get_option('amazondeal-countrie-category27');
            $conid[28] = get_option('amazondeal-countrie-category28');
            $conid[29] = get_option('amazondeal-countrie-category29');
            $conid[30] = get_option('amazondeal-countrie-category30');
            $conid[31] = get_option('amazondeal-countrie-category31');

            foreach ($country_ids as $country_id) {

                $response = wp_remote_get('https://api.discountcodes.net/categories-by-country/' . sanitize_text_field($country_id) . '/');
                $body = json_decode(wp_remote_retrieve_body($response));

                unset($body->status);
                ?>

                <td>
                    <label><b><?php
                            if (array_key_exists($country_id, $AMAZONDEALS_COUNTRIE_ARR)) {
                                echo esc_attr($AMAZONDEALS_COUNTRIE_ARR[$country_id]['title']);
                            } ?>
                        </b></label><br><br>
                    <input type="checkbox" name="<?php echo esc_attr($country_id) ?>country-select-all" class="chk_boxes<?php echo esc_attr($country_id); ?>" label="check all"
                           value="on" <?php echo($conid[$country_id] == 'on' ? 'checked' : ''); ?>> <?php esc_html_e('ALL Categories', 'amzcoupons') ?> <br>
                    <br>
                    <select name="<?php echo esc_attr($country_id); ?>category[]" multiple id="Category" class="country-cat<?php echo esc_attr($country_id); ?>">
                        <?php
                        foreach ($body->msg as $value1) { ?>
                            <option value="<?php echo esc_attr($value1->id); ?>"<?php echo(!empty($conid) && in_array($value1->id, $conid[$country_id]) ? ' selected="selected"' : '') ?>><?php echo esc_html($value1->title); ?></option>
                            <?php
                        }
                        ?>
                    </select>
                </td>
                <script>
                    jQuery(document).ready(function () {
                        var defult_checked = jQuery('.chk_boxes<?php echo esc_attr($country_id); ?>').is(':checked');
                        if (defult_checked == true) {
                            jQuery('.country-cat<?php echo esc_attr($country_id); ?> option').prop('selected', true);
                            jQuery('.country-cat<?php echo esc_attr($country_id); ?> option').prop('disabled', true);

                        }
                        jQuery('.country-cat<?php echo esc_attr($country_id); ?>').prop('required', true);
                        jQuery('.chk_boxes<?php echo esc_attr($country_id); ?>').click(function () {
                            var checked = jQuery(this).is(':checked');

                            if (checked == true) {
                                jQuery('.country-cat<?php echo esc_attr($country_id); ?>').prop('required', false);
                                jQuery('.country-cat<?php echo esc_attr($country_id); ?> option').prop('selected', true);
                                jQuery('.country-cat<?php echo esc_attr($country_id); ?> option').prop('disabled', true);
                            } else {
                                jQuery('.country-cat<?php echo esc_attr($country_id); ?>').prop('required', true);
                                jQuery('.country-cat<?php echo esc_attr($country_id); ?> option').prop('selected', false);
                                jQuery('.country-cat<?php echo esc_attr($country_id); ?> option').prop('disabled', false);
                            }
                        });
                    });

                </script>
                <?php
            }
            echo die();
        }


        /*
         * SHOW THE DEALS
         */
        public function show_data_on_frontend($atts)
        {
            global $wpdb;
            global $AMAZONDEALS_COUNTRIE_ARR;

            $primaryColorPlugin = get_option('amazondeal-primary-color');
            if (empty($primaryColorPlugin)) {
                $primaryColorPlugin = '#3f3c95';
            }

            $secondaryColorPlugin = get_option('amazondeal-secondary-color');
            if (empty($secondaryColorPlugin)) {
                $secondaryColorPlugin = '#2dc1c8';
            }


            if (!isset($_GET['pageid']) && !isset($_GET['action'])) {
                //print_r($_GET);
                //echo get_option('amazondeal-deals-page') . '?pageid=1';
                wp_redirect(get_option('amazondeal-deals-page') . '?pageid=1');
            }

            $content = "";

            $a = shortcode_atts(array(
                'filter' => 'on',
            ), $atts);

            $_GET['pageid'] = isset($_GET['pageid']) ? (int)$_GET['pageid'] : 1;
            $_GET['mind'] = isset($_GET['mind']) ? (int)$_GET['mind'] : 30;
            $_GET['maxd'] = isset($_GET['maxd']) ? (int)$_GET['maxd'] : 100;


            $contry_id_get = get_option('amazondeal-countrie');
            $country_string = implode(',', $contry_id_get);

            $countryIDGet = $_GET['country'] = isset($_GET['country']) ? sanitize_text_field($_GET['country']) : $country_string;
            $category_string = 0;

            // ONLY ONE COUNTRY SELECTED
            if (is_numeric($_GET['country']) && $_GET['country'] > 0) {

                $cat_id_get = get_option('amazondeal-countrie-category' . sanitize_text_field($_GET['country']));

//                var_dump($cat_id_get);
//                exit();

                if ($cat_id_get != 'on') {
                    $category_string = implode(',', $cat_id_get);
                } else {
                    // Get all categories for the country
//                    $response = wp_remote_get('https://api.discountcodes.net/categories-by-country/' . sanitize_text_field($countryIDGet) . '/');
//                    $body = json_decode(wp_remote_retrieve_body($response));
//                    $categoriesArr = [];
//                    foreach ($body->msg as $value1) {
//                        $categoriesArr[] = $value1->id;
//                    }
//                    $category_string = implode(',', $categoriesArr);
                }

                //echo $category_string;
            } // MULTIPLE COUNTRIES SELECTED
            else {
                if ($contry_id_get) {
                    $categoriesArr = [];
                    foreach ($contry_id_get as $cid) {
                        $cat_id_get1 = get_option('amazondeal-countrie-category' . sanitize_text_field($cid));
                        if ($cat_id_get1 == 'on') {
                            $response = wp_remote_get('https://api.discountcodes.net/categories-by-country/' . sanitize_text_field($cid) . '/');
                            $body = json_decode(wp_remote_retrieve_body($response));
                            foreach ($body->msg as $value1) {
                                $categoriesArr[] = $value1->id;
                            }
                        } else {
                            foreach ($cat_id_get1 as $cat1) {
                                $categoriesArr[] = $cat1;
                            }
                        }
                        //echo("<BR>");

                    }
                    //print_r($categoriesArr);
                }
                $category_string = implode(',', $categoriesArr);
            }
            $get_category = isset($_GET['front_category']) ? sanitize_text_field($_GET['front_category']) : $category_string;

            //echo "<BR>" . $category_string;
            //echo "<BR>" . $_GET['front_category']; echo "<pre>";print_r($get_pagenum);exit();
	        $savedDefaultSorting = esc_attr(get_option('amazondeal-deals-defaultsorting'));
            $_GET['lp'] = isset($_GET['lp']) ? (int)sanitize_text_field($_GET['lp']) : $savedDefaultSorting;

            $get_pagenum = sanitize_text_field($_GET['pageid']);
            $get_country = sanitize_text_field($_GET['country']);
            //$get_category = $_GET['front_category'];
            $get_mind = sanitize_text_field($_GET['mind']);
            $get_maxd = sanitize_text_field($_GET['maxd']);
            $get_lpriceon = sanitize_text_field($_GET['lp']);


            $get_skw = filter_input(INPUT_GET, 'skw', FILTER_SANITIZE_SPECIAL_CHARS);
            $get_skw = trim(mb_strtolower($get_skw, 'UTF-8'));

            ob_start();


            ?>

            <style>
                .pagination-small ul > li > a,
                .pagination-small ul > li > span {
                    background-color: <?php echo esc_attr($primaryColorPlugin); ?>;
                }
            </style>


            <div class="amazon_form" id="amazon_form">
                <?php
                if ($a['filter'] != 'off') {
                    include_once WP_AMAZON_DEAL_FORM_DIR . '/partials/search_filter.php';
                }
                ?>

                <div class="product-list">
                    <?php

                    // echo home_url();

                    $apiString = '?pgl=' . sanitize_text_field($get_pagenum) . '&country=' . sanitize_text_field($get_country) . '&min_d=' . sanitize_text_field($get_mind) . '&max_d=' . sanitize_text_field($get_maxd);
                    if ($get_category) {
                        $apiString .= '&catid=' . sanitize_text_field($get_category);
                    }
                    if ($get_lpriceon) {
                        $apiString .= '&lp=' . sanitize_text_field($get_lpriceon);
                    }
                    if ($get_skw) {
                        $apiString .= '&skw=' . sanitize_text_field($get_skw);
                    }

                    //echo esc_attr($apiString);

                    $response_search_form = wp_remote_get('https://api.discountcodes.net/deals-list/' . ($apiString));
                    $data_search_form = json_decode(wp_remote_retrieve_body($response_search_form));

                    if ($data_search_form->status == "NOTALLOWED") {
                        exit();
                    }

                    unset($data_search_form->status);
                    $bottomInfo = $data_search_form->bottominfo;

                    if ($data_search_form->msg->items) {
                        echo '<ul class="amazondeals-jv">';
                        foreach ($data_search_form->msg->items as $sale1) {
                            $this->amazondeal_show1item($sale1);
                        }
                        echo "</ul>";
                    }


                    $total_results = (int)$data_search_form->msg->total;
                    $perpage_limit = (int)$data_search_form->msg->perpage;

                    global $wp;
                    $urlwithvars = home_url($wp->request) . '/' . '?pageid=' . sanitize_text_field($get_pagenum);

                    if ($get_country) {
                        $urlwithvars .= '&country=' . sanitize_text_field($get_country);
                    }
                    if ($get_category) {
                        $urlwithvars .= '&front_category=' . sanitize_text_field($get_category);
                    }
                    if ($get_lpriceon) {
                        $urlwithvars .= '&lp=' . sanitize_text_field($get_lpriceon);
                    }
                    if ($get_mind) {
                        $urlwithvars .= '&mind=' . sanitize_text_field($get_mind);
                    }
                    if ($get_maxd) {
                        $urlwithvars .= '&maxd=' . sanitize_text_field($get_maxd);
                    }
                    if ($get_skw) {
                        $urlwithvars .= '&skw=' . sanitize_text_field($get_skw);
                    }


                    //echo $this->search_pagination($count, $new_pageid);
                    echo $this->_seacrh_pagination($get_pagenum, $total_results, $perpage_limit, 1, $urlwithvars, 'pageid');
                    ?>

                    <div class="deals-result-page-bottominfo"><?php echo esc_html($bottomInfo); ?></div>

                </div>

            </div>

            <script>

                var get_discmin = <?php echo sanitize_text_field($get_mind) ?: 30; ?>;
                var get_discmax = <?php echo sanitize_text_field($get_maxd) ?: 100; ?>;


                jQuery(function () {
                    if (jQuery("#slider-range").length) {
                        var discountslider = jQuery("#slider-range");
                        discountslider.slider({
                            range: true, min: 30, max: 100, values: [get_discmin, get_discmax], slide: function (event, ui) {
                                jQuery("#amount").val(ui.values[0] + "% - " + ui.values[1] + "%")
                            }
                        });
                        jQuery("#amount").val("%" + discountslider.slider("values", 0) + " - %" + discountslider.slider("values", 1))
                    }
                });

            </script>


            <?php

            $content = ob_get_contents();
            ob_clean();

            return $content;

        }

        public function show_category_data_frontend()
        {
            global $wpdb;
            global $AMAZONDEALS_COUNTRIE_ARR;

            $conid[22] = get_option('amazondeal-countrie-category22');
            $conid[23] = get_option('amazondeal-countrie-category23');
            $conid[24] = get_option('amazondeal-countrie-category24');
            $conid[25] = get_option('amazondeal-countrie-category25');
            $conid[26] = get_option('amazondeal-countrie-category26');
            $conid[27] = get_option('amazondeal-countrie-category27');
            $conid[28] = get_option('amazondeal-countrie-category28');
            $conid[29] = get_option('amazondeal-countrie-category29');
            $conid[30] = get_option('amazondeal-countrie-category30');
            $conid[31] = get_option('amazondeal-countrie-category31');

            $country_id = $country_ids = sanitize_text_field($_GET['countryid']);

            $response = wp_remote_get('https://api.discountcodes.net/categories-by-country/' . sanitize_text_field($country_id) . '/');
            $body = json_decode(wp_remote_retrieve_body($response));

            unset($body->status);

            $activeCategories = [];
            $activeCategoriesIDS = [];

            //echo 'CONID' . $conid[$country_ids] . '<BR>';

            if (is_array($conid[$country_ids])) {
                if ($body->msg) foreach ($body->msg as $value1) {
                    if (in_array($value1->id, $conid[$country_ids])) {
                        $activeCategories[] = json_decode(json_encode($value1), true);
                        $activeCategoriesIDS[] = $value1->id;
                    }
                }
            }

            if ($conid[$country_ids] == 'on') {
                if ($body->msg) foreach ($body->msg as $value1) {
                    $activeCategories[] = json_decode(json_encode($value1), true);
                    $activeCategoriesIDS[] = $value1->id;
                }
            }

            if ($activeCategoriesIDS) {
                $activeCategoriesStr = implode(",", $activeCategoriesIDS);
            }


            ?>
            <div class="select-option">
                <select name="front_category" id="front_category" class="front_country_cat amazon-deals-jw-select">
                    <option value="<?php echo esc_attr($activeCategoriesStr); ?>"><?php esc_html_e('Select Category', 'amzcoupons') ?></option>
                    <?php
                    foreach ($activeCategories as $activeCategory) {
                        ?>
                        <option value="<?php echo esc_attr($activeCategory['id']); ?>">
                            <?php echo esc_html($activeCategory['title']); ?>
                        </option>
                        <?php
                    }
                    ?>
                </select>
            </div>
            <?php
            echo die();
        }

        public function amazondeal_show1item($sale1)
        {

            $primaryColorPlugin = get_option('amazondeal-primary-color');
            if (empty($primaryColorPlugin)) {
                $primaryColorPlugin = '#3f3c95';
            }

            $secondaryColorPlugin = get_option('amazondeal-secondary-color');
            if (empty($secondaryColorPlugin)) {
                $secondaryColorPlugin = '#2dc1c8';
            }


            $saleurl = site_url() . '/cpndealpage-oo/' . sanitize_text_field($sale1->id) . '/';

            $exclusiveDealBadge = "";
            $exclusiveDealBorder = "";
            if ($sale1->exclusiveDeal) {
                $exclusiveDealBorder = "border: 1px solid $secondaryColorPlugin";
                //$exclusiveDealBadge = '<span class="exclusive-promo" style="background-color:' . esc_attr($secondaryColorPlugin) .'"><i class="fas fa-burn"></i>' . esc_html_e('EXCLUSIVE DEAL', 'amzcoupons') . '</span>';
                $exclusiveDealBadge = '<span class="exclusive-promo" style="background-color:' . sanitize_text_field($secondaryColorPlugin) . '"><i class="fas fa-burn"></i> EXCLUSIVE DEAL</span>';

                //print_r($exclusiveDealBadge);
            }
            ?>

            <li class="text-center pos-rel saleli col-lg-3 col-md-4 col-sm-6" id="spromo<?php echo esc_attr($sale1->id); ?>">

                <div class="feature boxed cast-light " style="<?php echo esc_attr($exclusiveDealBorder); ?>">

                    <?php echo wp_kses_post($exclusiveDealBadge); ?>


                    <div class="boxed imgcont">
                        <a href="<?php echo esc_url($saleurl); ?>" target="_blank">
                            <img alt="<?php echo esc_attr($sale1->title); ?>" class="image-small inline-block mb24" style="max-height:200px;"
                                 src="<?php echo esc_url($sale1->picture2); ?>"
                                 data="<?php echo esc_url($sale1->picture3); ?>">
                        </a>
                    </div>
                    <h4 class="height-min-60 promo-title-item">
                        <a href="<?php echo esc_url($saleurl); ?>" target="_blank" title="<?php echo esc_attr($sale1->title); ?>">
                            <?php echo $this->shorter($sale1->title, 38) ?>
                        </a>
                    </h4>
                    <div class="clearfix"></div>
                    <div class="pull-left price"> <?php echo CPNDEALS_currencyByVal1($sale1->CurrencyCode) ?><?php echo number_format($sale1->afterprice, 2); ?></div>
                    <div class="discnt-pull-right">
                        <span class="discnt smart"
                              style="background-color: <?php echo esc_attr($secondaryColorPlugin); ?>"><strong><?php echo esc_html($sale1->smartDiscount); ?></strong> <?php esc_html_e('% off', 'amzcoupons') ?></span>
                    </div>
                    <div class="clearfix"></div>
                </div>
                <div class="center-block no-float" style="background-color: <?php echo esc_attr($primaryColorPlugin); ?>">
                    <a class="btn btn-filled width-100" style="text-decoration: none;" href="<?php echo esc_url($saleurl); ?>" target="_blank"><?php esc_html_e('GET COUPON', 'amzcoupons') ?></a>
                </div>
            </li>
            <?php
        }

        public function buildLinkQuery($args = array(), $values = array())
        {
            if (!empty($values)) $args = array_merge($args, $values);
            if (!empty($args)) $link = '&' . rawurldecode(http_build_query(array_filter($args)));
            return $link;
        }

        public function shorter($text, $chars_limit)
        {
            $text = trim(strip_tags($text));
            if (mb_strlen($text, "utf-8") > $chars_limit) {
                $new_text = mb_substr($text, 0, $chars_limit, "utf-8");
                $new_text = trim($new_text);
                return $new_text . "...";
            } // If not just return the text as is
            else {
                return $text;
            }
        }

        public function custom_rewrite_basic()
        {
            add_rewrite_rule('^cpndealpage-oo/([0-9]+)', 'index.php?post_type=cpndealpage-oo&product_id=$matches[1]', 'top');
        }

        public function prefix_register_query_var($vars)
        {
            $vars[] = 'product_id';

            return $vars;
        }

        public function prefix_url_rewrite_templates($template)
        {
            if (get_query_var('post_type') == "cpndealpage-oo" && get_query_var('product_id')) {
                $file = WP_AMAZON_DEAL_FORM_DIR . 'partials/template-deal-page.php';
                if (!empty($file)) {
                    return $file;
                }
            }
            return $template;
        }

        public function _seacrh_pagination($page = 0, $totalitems = 0, $limit = 15, $adjacents = 1, $targetpage = "", $pagestring = "page")
        {
            global $items_per_page;

            $margin = '';
            $padding = '';

            //defaults
            if (!$adjacents) $adjacents = 1;
            if (!$limit) $limit = $items_per_page;
            if (!$page || $page == 0) $page = (!empty($_GET[$pagestring])) ? intval($_GET[$pagestring]) : 1;
            // if(empty($targetpage)) $targetpage = "/";
            if (empty($pagestring)) $pagestring = "pglim";

            //other vars
            $prev = $page - 1;                                    //previous page is page - 1
            $next = $page + 1;                                    //next page is page + 1
            $lastpage = ceil($totalitems / $limit);                //lastpage is = total items / items per page, rounded up.
            $lpm1 = $lastpage - 1;                                //last page minus 1

            /*
                Now we apply our rules and draw the pagination object.
                We're actually saving the code to a variable in case we want to draw it more than once.
            */
            $pagination = "";
            if ($lastpage > 1) {
                $pagination .= "<div class=\"pagination pagination-small\"";
                if ($margin || $padding) {
                    $pagination .= " style=\"";
                    if ($margin)
                        $pagination .= "margin: $margin;";
                    if ($padding)
                        $pagination .= "padding: $padding;";
                    $pagination .= "\"";
                }
                $pagination .= "><ul>";

                //previous button
                if ($page > 1)
                    $pagination .= "<li class=\"page-prev\"><a rel=\"prev\" href=\"" . $this->url_query($targetpage, array($pagestring => $prev)) . "\">«</a></li>";//« prev
                else
                    $pagination .= "<li class=\"page-prev disabled\"><a href=\"javascript:void(0)\">«</a></li>";//  « prev

                //pages
                if ($lastpage < 7 + ($adjacents * 2))    //not enough pages to bother breaking it up
                {
                    for ($counter = 1; $counter <= $lastpage; $counter++) {
                        $relnextprev = "next";
                        if ($counter < $page) $relnextprev = "prev";
                        if ($counter == $page)
                            $pagination .= "<li class=\"active\"><a href=\"javascript:void(0)\">$counter</a></li>";
                        else
                            $pagination .= "<li><a rel='" . $relnextprev . "' href=\"" . $this->url_query($targetpage, array($pagestring => $counter)) . "\">$counter</a></li>";
                    }
                } elseif ($lastpage >= 7 + ($adjacents * 2))    //enough pages to hide some
                {
                    //close to beginning; only hide later pages
                    if ($page < 1 + ($adjacents * 3)) {
                        for ($counter = 1; $counter < 4 + ($adjacents * 2); $counter++) {
                            $relnextprev = "next";
                            if ($counter < $page) $relnextprev = "prev";
                            if ($counter == $page)
                                $pagination .= "<li class=\"current\"><a href=\"javascript:void(0)\">$counter</a></li>";
                            else
                                $pagination .= "<li><a rel='" . $relnextprev . "'  href=\"" . $this->url_query($targetpage, array($pagestring => $counter)) . "\">$counter</a></li>";
                        }
                        $pagination .= "<li class=\"elipses\"><a href=\"javascript:void(0)\">...</a></li>";
                        $pagination .= "<li><a href=\"" . $this->url_query($targetpage, array($pagestring => $lpm1)) . "\">$lpm1</a></li>";
                        $pagination .= "<li><a href=\"" . $this->url_query($targetpage, array($pagestring => $lastpage)) . "\">$lastpage</a></li>";
                    } //in middle; hide some front and some back
                    elseif ($lastpage - ($adjacents * 2) > $page && $page > ($adjacents * 2)) {
                        $pagination .= "<li><a href=\"" . $this->url_query($targetpage, array($pagestring => '1')) . "\">1</a></li>";
                        $pagination .= "<li><a href=\"" . $this->url_query($targetpage, array($pagestring => '2')) . "\">2</a></li>";
                        $pagination .= "<li class=\"elipses\"><a href=\"javascript:void(0)\">...</a></li>";
                        for ($counter = $page - $adjacents; $counter <= $page + $adjacents; $counter++) {
                            if ($counter == $page)
                                $pagination .= "<li class=\"current\"><a href=\"javascript:void(0)\">$counter</a></li>";
                            else
                                $pagination .= "<li><a href=\"" . $this->url_query($targetpage, array($pagestring => $counter)) . "\">$counter</a></li>";
                        }
                        $pagination .= "<li><a href=\"javascript:void(0)\">...</a></li>";
                        $pagination .= "<li><a href=\"" . $this->url_query($targetpage, array($pagestring => $lpm1)) . "\">$lpm1</a></li>";
                        $pagination .= "<li><a href=\"" . $this->url_query($targetpage, array($pagestring => $lastpage)) . "\">$lastpage</a></li>";
                    } //close to end; only hide early pages
                    else {
                        $pagination .= "<li><a href=\"" . $this->url_query($targetpage, array($pagestring => '1')) . "\">1</a></li>";
                        $pagination .= "<li><a href=\"" . $this->url_query($targetpage, array($pagestring => '2')) . "\">2</a></li>";
                        $pagination .= "<li class=\"elipses\"><a href=\"javascript:void(0)\">...</a></li>";
                        for ($counter = $lastpage - (1 + ($adjacents * 3)); $counter <= $lastpage; $counter++) {
                            if ($counter == $page)
                                $pagination .= "<li class=\"current\"><a href=\"javascript:void(0)\">$counter</a></li>";
                            else
                                $pagination .= "<li><a href=\"" . $this->url_query($targetpage, array($pagestring => $counter)) . "\">$counter</a></li>";
                        }
                    }
                }

                //next button
                if ($page < $counter - 1)
                    $pagination .= "<li class=\"page-next\"><a rel=\"next\" href=\"" . $this->url_query($targetpage, array($pagestring => $next)) . "\">»</a></li>";//next »
                else
                    $pagination .= "<li class=\"page-next disabled\"><a href=\"javascript:void(0)\">»</a></li>";//next »
                $pagination .= "</ul></div>\n";
            }

            return $pagination;

        }

        public function url_query($url = '', $query_parts = array(), $clear_query = false)
        {
            $url = str_replace('\\', '/', $url);


            $url = (empty($url)) ? getHostInfo() . getRequestUri() : $url;

            if (empty($query_parts)) return $url;
            $url_parts = parse_url($url);
            //if (empty($url_parts['query'])) return $url;
            $url_query = $url_parts['query'];

            parse_str($url_query, $data);
            foreach ($query_parts as $key => $value) {
                if (array_key_exists($key, $data)) {
                    if (empty($value)) unset($data[$key]);
                    else $data[$key] = $value;
                } else {
                    $data[$key] = $value;
                }
            }
            $query = http_build_query($data);


            $result = '';
            if (!empty($url_parts['scheme'])) $result .= $url_parts['scheme'] . '://';
            if (!empty($url_parts['user'])) $result .= $url_parts['user'];
            if (!empty($url_parts['pass'])) $result .= ':' . $url_parts['pass'];
            if (!empty($url_parts['user']) || !empty($url_parts['pass'])) $result .= '@';
            if (!empty($url_parts['host'])) $result .= $url_parts['host'];
            if (!empty($url_parts['port'])) $result .= ':' . $url_parts['port'];
            //if(ISLOCAL) $result .= '/';
            if (!empty($url_parts['path'])) $result .= $url_parts['path'];
            if (!empty($query) && !$clear_query) $result .= '?' . $query;
            if (!empty($url_parts['fragment'])) $result .= '#' . $url_parts['fragment'];


            return stripslashes(rawurldecode($result));
        }


        /*
 * Countries Array Global
 */
        public function country_gloabal_array()
        {

            global $AMAZONDEALS_COUNTRIE_ARR;
            $AMAZONDEALS_COUNTRIE_ARR = array();

            $AMAZONDEALS_COUNTRIE_ARR[22] = array
            (
                "title" => "United States",
                "ccode" => "United States",
                "cdomain" => "com",
                "langcfgsfx" => "eng",
                "title1" => "amazon.com",
                "urlalias" => "us",
                "serviceUrl" => "https://mws.amazonservices.com",
                "marketplace_id" => "ATVPDKIKX0DER",
                "CurrencyCode" => "USD",
            );

            $AMAZONDEALS_COUNTRIE_ARR[23] = array
            (
                "title" => "United Kingdom",
                "ccode" => "United Kingdom",
                "cdomain" => "co.uk",
                "langcfgsfx" => "eng",
                "title1" => "amazon.co.uk",
                "urlalias" => "uk",
                "serviceUrl" => "https://mws-eu.amazonservices.com",
                "marketplace_id" => "A1F83G8C2ARO7P",
                "CurrencyCode" => "GBP",
            );

            $AMAZONDEALS_COUNTRIE_ARR[28] = array
            (
                "title" => "Brazil",
                "ccode" => "Brazil",
                "cdomain" => "com.br",
                "langcfgsfx" => "eng",
                "title1" => "amazon.com.br",
                "urlalias" => "br",
                "serviceUrl" => "https://mws.amazonservices.com",
                "marketplace_id" => "A2Q3Y263D00KWC",
                "CurrencyCode" => "",
            );

            $AMAZONDEALS_COUNTRIE_ARR[24] = array
            (
                "title" => "Canada",
                "ccode" => "Canada",
                "cdomain" => "ca",
                "langcfgsfx" => "eng",
                "title1" => "amazon.ca",
                "urlalias" => "ca",
                "serviceUrl" => "https://mws.amazonservices.com",
                "marketplace_id" => "A2EUQ1WTGCTBG2",
                "CurrencyCode" => "CAD",
            );

            $AMAZONDEALS_COUNTRIE_ARR[26] = array
            (
                "title" => "France",
                "ccode" => "France",
                "cdomain" => "fr",
                "langcfgsfx" => "fre",
                "title1" => "amazon.fr",
                "urlalias" => "fr",
                "serviceUrl" => "https://mws-eu.amazonservices.com",
                "marketplace_id" => "A13V1IB3VIYZZH",
                "CurrencyCode" => "EUR",
            );

            $AMAZONDEALS_COUNTRIE_ARR[25] = array
            (
                "title" => "Germany",
                "ccode" => "Germany",
                "cdomain" => "de",
                "langcfgsfx" => "ger",
                "title1" => "amazon.de",
                "urlalias" => "de",
                "serviceUrl" => "https://mws-eu.amazonservices.com",
                "marketplace_id" => "A1PA6795UKMFR9",
                "CurrencyCode" => "EUR",
            );

            $AMAZONDEALS_COUNTRIE_ARR[27] = array
            (
                "title" => "Italy",
                "ccode" => "Italy",
                "cdomain" => "it",
                "langcfgsfx" => "ita",
                "title1" => "amazon.it",
                "urlalias" => "it",
                "serviceUrl" => "https://mws-eu.amazonservices.com",
                "marketplace_id" => "APJ6JRA9NG5V4",
                "CurrencyCode" => "EUR",
            );

            $AMAZONDEALS_COUNTRIE_ARR[31] = array
            (
                "title" => "Japan",
                "ccode" => "Japan",
                "cdomain" => "co.jp",
                "langcfgsfx" => "eng",
                "title1" => "amazon.co.jp",
                "urlalias" => "jp",
                "serviceUrl" => "https://mws.amazonservices.jp",
                "marketplace_id" => "A1VC38T7YXB528",
                "CurrencyCode" => "JPY",
            );

            $AMAZONDEALS_COUNTRIE_ARR[29] = array
            (
                "title" => "Mexico",
                "ccode" => "Mexico",
                "cdomain" => "com.mx",
                "langcfgsfx" => "eng",
                "title1" => "amazon.com.mx",
                "urlalias" => "mx",
                "serviceUrl" => "https://mws.amazonservices.com",
                "marketplace_id" => "A1AM78C64UM0Y8",
                "CurrencyCode" => "",
            );

            $AMAZONDEALS_COUNTRIE_ARR[30] = array
            (
                "title" => "Spain",
                "ccode" => "Spain",
                "cdomain" => "es",
                "langcfgsfx" => "spa",
                "title1" => "amazon.es",
                "urlalias" => "es",
                "serviceUrl" => "https://mws-eu.amazonservices.com",
                "marketplace_id" => "A1RKKUPIHCS9HS",
                "CurrencyCode" => "EUR",
            );
        }

    }

endif;

$amazon_deals = new amz_deals();

function CPNDEALS_activate_dealplugin()
{


    $args = array(
        'label' => __('Deal Page', 'amzcoupons'),
        'public' => true,
        'show_ui' => true,
        'capability_type' => 'post',
        'hierarchical' => false,
        'rewrite' => array(
            'slug' => 'cpndealpage-oo',
            'with_front' => false
        ),
        'query_var' => true,
        'supports' => array(
            'title',
            'editor',
            'excerpt',
            'trackbacks',
            'custom-fields',
            'revisions',
            'thumbnail',
            'author',
            'page-attributes'
        )
    );

    register_post_type('cpndealpage-oo', $args);

}

add_action('init', 'CPNDEALS_activate_dealplugin');

function CPNDEALS_dealFlushRewrites()
{
    CPNDEALS_activate_dealplugin();
    flush_rewrite_rules();
}

register_activation_hook(__FILE__, 'CPNDEALS_dealFlushRewrites');

register_uninstall_hook(__FILE__, 'CPNDEALS_dealPluginUninstall');

function CPNDEALS_dealPluginUninstall()
{
    unregister_post_type('cpndealpage-oo');
}


function CPNDEALS_currencyByVal1($val)
{
    $currency_symbols = array(
        'AED' => '&#1583;.&#1573;', // ?
        'AFN' => '&#65;&#102;',
        'ALL' => '&#76;&#101;&#107;',
        'AMD' => '',
        'ANG' => '&#402;',
        'AOA' => '&#75;&#122;', // ?
        'ARS' => '&#36;',
        'AUD' => '&#36;',
        'AWG' => '&#402;',
        'AZN' => '&#1084;&#1072;&#1085;',
        'BAM' => '&#75;&#77;',
        'BBD' => '&#36;',
        'BDT' => '&#2547;', // ?
        'BGN' => '&#1083;&#1074;',
        'BHD' => '.&#1583;.&#1576;', // ?
        'BIF' => '&#70;&#66;&#117;', // ?
        'BMD' => '&#36;',
        'BND' => '&#36;',
        'BOB' => '&#36;&#98;',
        'BRL' => '&#82;&#36;',
        'BSD' => '&#36;',
        'BTN' => '&#78;&#117;&#46;', // ?
        'BWP' => '&#80;',
        'BYR' => '&#112;&#46;',
        'BZD' => '&#66;&#90;&#36;',
        'CAD' => '&#36;',
        'CDF' => '&#70;&#67;',
        'CHF' => '&#67;&#72;&#70;',
        'CLF' => '', // ?
        'CLP' => '&#36;',
        'CNY' => '&#165;',
        'COP' => '&#36;',
        'CRC' => '&#8353;',
        'CUP' => '&#8396;',
        'CVE' => '&#36;', // ?
        'CZK' => '&#75;&#269;',
        'DJF' => '&#70;&#100;&#106;', // ?
        'DKK' => '&#107;&#114;',
        'DOP' => '&#82;&#68;&#36;',
        'DZD' => '&#1583;&#1580;', // ?
        'EGP' => '&#163;',
        'ETB' => '&#66;&#114;',
        'EUR' => '&#8364;',
        'FJD' => '&#36;',
        'FKP' => '&#163;',
        'GBP' => '&#163;',
        'GEL' => '&#4314;', // ?
        'GHS' => '&#162;',
        'GIP' => '&#163;',
        'GMD' => '&#68;', // ?
        'GNF' => '&#70;&#71;', // ?
        'GTQ' => '&#81;',
        'GYD' => '&#36;',
        'HKD' => '&#36;',
        'HNL' => '&#76;',
        'HRK' => '&#107;&#110;',
        'HTG' => '&#71;', // ?
        'HUF' => '&#70;&#116;',
        'IDR' => '&#82;&#112;',
        'ILS' => '&#8362;',
        'INR' => '&#8377;',
        'IQD' => '&#1593;.&#1583;', // ?
        'IRR' => '&#65020;',
        'ISK' => '&#107;&#114;',
        'JEP' => '&#163;',
        'JMD' => '&#74;&#36;',
        'JOD' => '&#74;&#68;', // ?
        'JPY' => '&#165;',
        'KES' => '&#75;&#83;&#104;', // ?
        'KGS' => '&#1083;&#1074;',
        'KHR' => '&#6107;',
        'KMF' => '&#67;&#70;', // ?
        'KPW' => '&#8361;',
        'KRW' => '&#8361;',
        'KWD' => '&#1583;.&#1603;', // ?
        'KYD' => '&#36;',
        'KZT' => '&#1083;&#1074;',
        'LAK' => '&#8365;',
        'LBP' => '&#163;',
        'LKR' => '&#8360;',
        'LRD' => '&#36;',
        'LSL' => '&#76;', // ?
        'LTL' => '&#76;&#116;',
        'LVL' => '&#76;&#115;',
        'LYD' => '&#1604;.&#1583;', // ?
        'MAD' => '&#1583;.&#1605;.', //?
        'MDL' => '&#76;',
        'MGA' => '&#65;&#114;', // ?
        'MKD' => '&#1076;&#1077;&#1085;',
        'MMK' => '&#75;',
        'MNT' => '&#8366;',
        'MOP' => '&#77;&#79;&#80;&#36;', // ?
        'MRO' => '&#85;&#77;', // ?
        'MUR' => '&#8360;', // ?
        'MVR' => '.&#1923;', // ?
        'MWK' => '&#77;&#75;',
        'MXN' => '&#36;',
        'MYR' => '&#82;&#77;',
        'MZN' => '&#77;&#84;',
        'NAD' => '&#36;',
        'NGN' => '&#8358;',
        'NIO' => '&#67;&#36;',
        'NOK' => '&#107;&#114;',
        'NPR' => '&#8360;',
        'NZD' => '&#36;',
        'OMR' => '&#65020;',
        'PAB' => '&#66;&#47;&#46;',
        'PEN' => '&#83;&#47;&#46;',
        'PGK' => '&#75;', // ?
        'PHP' => '&#8369;',
        'PKR' => '&#8360;',
        'PLN' => '&#122;&#322;',
        'PYG' => '&#71;&#115;',
        'QAR' => '&#65020;',
        'RON' => '&#108;&#101;&#105;',
        'RSD' => '&#1044;&#1080;&#1085;&#46;',
        'RUB' => '&#1088;&#1091;&#1073;',
        'RWF' => '&#1585;.&#1587;',
        'SAR' => '&#65020;',
        'SBD' => '&#36;',
        'SCR' => '&#8360;',
        'SDG' => '&#163;', // ?
        'SEK' => '&#107;&#114;',
        'SGD' => '&#36;',
        'SHP' => '&#163;',
        'SLL' => '&#76;&#101;', // ?
        'SOS' => '&#83;',
        'SRD' => '&#36;',
        'STD' => '&#68;&#98;', // ?
        'SVC' => '&#36;',
        'SYP' => '&#163;',
        'SZL' => '&#76;', // ?
        'THB' => '&#3647;',
        'TJS' => '&#84;&#74;&#83;', // ? TJS (guess)
        'TMT' => '&#109;',
        'TND' => '&#1583;.&#1578;',
        'TOP' => '&#84;&#36;',
        'TRY' => '&#8356;', // New Turkey Lira (old symbol used)
        'TTD' => '&#36;',
        'TWD' => '&#78;&#84;&#36;',
        'TZS' => '',
        'UAH' => '&#8372;',
        'UGX' => '&#85;&#83;&#104;',
        'USD' => '&#36;',
        'UYU' => '&#36;&#85;',
        'UZS' => '&#1083;&#1074;',
        'VEF' => '&#66;&#115;',
        'VND' => '&#8363;',
        'VUV' => '&#86;&#84;',
        'WST' => '&#87;&#83;&#36;',
        'XAF' => '&#70;&#67;&#70;&#65;',
        'XCD' => '&#36;',
        'XDR' => '',
        'XOF' => '',
        'XPF' => '&#70;',
        'YER' => '&#65020;',
        'ZAR' => '&#82;',
        'ZMK' => '&#90;&#75;', // ?
        'ZWL' => '&#90;&#36;',
    );

    return $currency_symbols[$val];

}


function CPNDEALS_sanitizeArray($array)
{
    foreach ($array as $value) {
        if (!is_array($value)) {
            $value = sanitize_text_field($value);
        } else {
            CPNDEALS_sanitizeArray($value);
        }
    }
    return $array;
}
