<?php
get_header();

$id = get_query_var('product_id');
$response_deal_page = wp_remote_get('https://api.discountcodes.net/deal/' . sanitize_text_field($id));
$data_deal_page = json_decode(wp_remote_retrieve_body($response_deal_page));

if ($data_deal_page->status == "NOTALLOWED") {
    exit();
}
unset($data_deal_page->status);

$thesale = $data_deal_page->msg;

if (empty($thesale)) {
    include_once 'not-found-sale.php';
    get_footer();
    die();
}

$thesale = json_decode(json_encode($thesale), true);
$bottomInfo = $data_deal_page->bottominfo;
$saleImagesArr = [];

if ($thesale['morepics']) {
    $saleImagesArr1 = explode(",", $thesale['morepics']);
    foreach ($saleImagesArr1 as $imgimg) {
        $saleImagesArr[] = AMAZONPRODIMAGESURL . sanitize_text_field($imgimg);
    }
} else $saleImagesArr[] = $thesale['picture2'];

$affiliateTag = get_option('amazondeal-affiliate-' . sanitize_text_field($thesale['countryid']));


$amazonURL = 'https://' . $thesale['urlalias'];

if ($affiliateTag) {
    $amazonURL .= "?tag=$affiliateTag";
} else {
    $bottomInfo = '';
}

$primaryColorPlugin = get_option('amazondeal-primary-color');
if (empty($primaryColorPlugin)) {
    $primaryColorPlugin = '#3f3c95';
}

$secondaryColorPlugin = get_option('amazondeal-secondary-color');
if (empty($secondaryColorPlugin)) {
    $secondaryColorPlugin = '#2dc1c8';
}



$exclusiveDealBadge = "";
$exclusiveDealBorder = "";
$exclusiveDealBR = "";
if ($thesale['exclusiveDeal']) {
    $exclusiveDealBR = "<BR><BR>";
    $exclusiveDealBorder = "border: 1px solid $secondaryColorPlugin";
    $exclusiveDealBadge = '<span class="exclusive-promo" style="background-color:' . sanitize_text_field($secondaryColorPlugin) .'"><i class="fas fa-burn"></i> EXCLUSIVE DEAL</span>';
}
?>
    <div class="container deals-jv-page" style="max-width: 1200px; margin: 0 auto">
        <div class="promo-page-300">
            <h1 class="pad-lr-15"><?php echo esc_html($thesale['title']) ?></h1>
            <?php if (sizeof($saleImagesArr) > 1) { ?>
                <div class="owl-slider" style="min-height: 370px">
                    <div id="promo-images-300" class="owl-carousel">
                        <?php if ($saleImagesArr) foreach ($saleImagesArr as $imgimg) { ?>
                            <div class="item">
                                <img src="<?php echo esc_url($imgimg) ?>" class="width-min-65p" alt="<?php echo esc_attr($thesale['title']) ?>">
                            </div>
                        <?php } ?>
                    </div>
                </div>
            <?php } else { ?>
                <div class="one-image-1">
                    <img src="<?php echo esc_url($saleImagesArr[0]) ?>" class="width-min-65p" alt="<?php echo esc_attr($thesale['title']) ?>">
                </div>
            <?php } ?>

            <div class="custom_box">
                <div class="details_box" style="position: relative">
                    <?php echo esc_html($thesale['text']); ?>
                </div>
                <div class="price_box" style="position: relative">

                    <?php echo wp_kses_post($exclusiveDealBR); ?>
                    <?php echo wp_kses_post($exclusiveDealBadge); ?>


                    <div class="original_price_row">
                        <div class="row_main_text"><?php esc_html_e('Original Price', 'amzcoupons') ?></div>
                        <div class="orinial_price_text"
                             style="color: <?php echo esc_attr($primaryColorPlugin); ?>"> <?php echo CPNDEALS_currencyByVal1($thesale['CurrencyCode']); ?><?php echo esc_html($thesale['price']); ?> </div>
                    </div>
                    <div class="discount_row">
                        <div class="row_main_text"><?php esc_html_e('Discount', 'amzcoupons') ?></div>
                        <div class="discount_text" style="font-weight:700;color: <?php echo esc_attr($secondaryColorPlugin); ?>"> <?php echo esc_html($thesale['smartDiscount']); ?>%</div>
                    </div>

                    <div class="price_row">
                        <div class="row_main_text"><?php esc_html_e('Discounted Price', 'amzcoupons') ?></div>
                        <div class="price_text" style="font-weight:700;color: <?php echo esc_attr($secondaryColorPlugin); ?>"> $<?php echo number_format($thesale['afterprice'], 2); ?> </div>
                    </div>

                    <?php if ($thesale['couponecodes'] && $thesale['exclusiveDeal']) { ?>
                        <div class="price_row">
                            <div class="row_coupon_text"><?php esc_html_e('Special Coupon', 'amzcoupons') ?></div>
                            <div class="price_text" style="font-weight:700;color: <?php echo esc_attr($secondaryColorPlugin); ?>">
                                <div id="kwcontkwcpn" class="thepromokw">
                                    <textarea class="kwjs-copytextarea" disabled style="color: <?php echo esc_attr($secondaryColorPlugin); ?>"
                                      id="kwcpn"
                                      title="<?php esc_html_e('Copy Coupon', 'amzcoupons') ?>"><?php echo strip_tags($thesale['couponecodes']); ?></textarea>
                                    <span title="<?php esc_html_e('Copy Coupon', 'amzcoupons') ?>" class="kwjs-textareacopybtn"
                                          data-index-cc="kwcpn"><i class="fa fa-clipboard" aria-hidden="true"></i></span>
                                </div>
                            </div>
                        </div>
                    <?php } ?>

                    <div class="button_deal">
                        <a class="deal_button" style="background-color: <?php echo esc_attr($primaryColorPlugin); ?>" href="<?php echo esc_url($amazonURL); ?>" target="_blank">
                        <?php esc_html_e('GO TO DEAL', 'amzcoupons') ?></a>
                    </div>
                    <div class="deals-jv-page-bottominfo"><?php echo esc_html($bottomInfo); ?></div>
                </div>
            </div>
        </div>
    </div>


    <script type="text/javascript">
        <!--
        let classname4btn = document.getElementsByClassName("kwjs-textareacopybtn");
        let kwcopycpn = function () {
            let copyTextarea = document.querySelector('#' + this.getAttribute('data-index-cc'));
            let codecontainer = document.querySelector('#kwcont' + this.getAttribute('data-index-cc'));
            copyTextarea.disabled = false;
            copyTextarea.select();
            //var code = copyTextarea.value;
            try {
                let successful = document.execCommand('copy');
                //var msg = successful ? 'successful' : 'unsuccessful';
                //console.log(code+'Copying text command was ' + msg);
                //copyTextarea.backgroundColor = "green";
                console.log(successful);
                codecontainer.style.backgroundColor = "rgb(88, 195, 147)";
                copyTextarea.style.backgroundColor = "rgb(88, 195, 147)";
            } catch (err) {
                //console.log('Oops, unable to copy');
            }
            copyTextarea.disabled = true;
        };
        for (let ixclick = 0; ixclick < classname4btn.length; ixclick++) {
            classname4btn[ixclick].addEventListener('click', kwcopycpn, false);
        }
        //-->
    </script>

<?php
get_footer();
