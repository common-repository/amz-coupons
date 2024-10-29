<?php
$GET['skw'] = filter_input(INPUT_GET, 'skw', FILTER_SANITIZE_SPECIAL_CHARS);
$GET['skw'] = trim(mb_strtolower($GET['skw'], 'UTF-8'));
?>

<form action="" method="GET" name="amazon_form" id="deals-search-form">
    <div class="form-controller">

        <div class="row">
            <div class="col-md-4">
                <div class="select-option">
                    <input type="hidden" name="pageid" id="pglim1" value="<?php echo esc_attr($get_pagenum); ?>">
                    <input type="hidden" value="<?php echo esc_attr($get_mind); ?>" name="mind" id="mind1">
                    <input type="hidden" value="<?php echo esc_attr($get_maxd); ?>" name="maxd" id="maxd1">

                    <select name="country" id="country" class="amazon-deals-jw-select" title="<?php esc_attr_e('Country', 'amzcoupons') ?>">
                        <option value="<?php echo esc_attr($country_string); ?>"><?php esc_html_e('Select Country', 'amzcoupons') ?></option>
                        <?php
                        $con = get_option('amazondeal-countrie');

                        foreach ($con as $value) { ?>

                            <option value="<?php echo esc_attr($value); ?>"
                                <?php echo(!empty($con) && isset($_GET['country']) && ($_GET['country'] == $value) ? ' selected="selected"' : '') ?>>
                                <?php echo esc_html($AMAZONDEALS_COUNTRIE_ARR[$value]['title']); ?>
                            </option>

                        <?php } ?>
                    </select>
                </div>
            </div>
            <div class="col-md-4 category_frontend">
                <div class="select-option">
                    <?php
                    if (isset($_GET['country'])) {

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

                        $response = wp_remote_get('https://api.discountcodes.net/categories-by-country/' . sanitize_text_field($_GET['country']) . '/');

                        $body = json_decode(wp_remote_retrieve_body($response));

                        unset($body->status);
                    }

                    ?>

                    <select name="front_category" id="front_category" class="front_country_cat amazon-deals-jw-select"
                            title="<?php esc_attr_e('All Categories', 'amzcoupons') ?>">
                        <option value="<?php echo esc_attr($category_string); ?>"><?php esc_html_e('All Categories', 'amzcoupons') ?></option>
                        <?php if (isset($_GET['front_category']) || isset($_GET['country'])) {

                            foreach ($body->msg as $value1) {

                                $fCountry = sanitize_text_field($_GET['country']);
                                if (is_array($conid[$_GET['country']])) {
                                    $foundCC = in_array($value1->id, $conid[$_GET['country']]);
                                } else {
                                    $foundCC = $value1->id == $conid[$_GET['country']];
                                }

                                if ($foundCC || $conid[$_GET['country']] == 'on') { ?>

                                    <option value="<?php echo esc_attr($value1->id); ?>"
                                        <?php
                                        if (isset($_GET['front_category'])) {
                                            if (($value1->id == $_GET['front_category'])) echo "selected";
                                        }
                                        ?>
                                    >
                                        <?php echo esc_html($value1->title); ?>
                                    </option>

                                <?php }
                            }
                        } ?>
                    </select>
                </div>
            </div>
            <div class="col-md-4">
                <?php
                $savedDefaultSorting = esc_attr(get_option('amazondeal-deals-defaultsorting'));
                $selectedSorting = $savedDefaultSorting;
                if (!empty($_GET['lp'])) {
                    $selectedSorting = (int)sanitize_text_field($_GET['lp']);
                }
                ?>
                <div class="select-option">
                    <select class="filtercat custom-select amazon-deals-jw-select"
                            name="lp" id="selectsortby"
                            data-style="btn-inverse" title="<?php esc_attr_e('Select Sorting', 'amzcoupons') ?>">
                        <option value="2" <?php echo ($selectedSorting == 2) ? ' selected="selected"' : ''; ?>
                                class="text-capitalize" selected=""><?php esc_html_e('HIGHEST DISCOUNT FIRST', 'amzcoupons') ?>
                        </option>
                        <option value="5" <?php echo ($selectedSorting == 5) ? ' selected="selected"' : ''; ?>
                                class="text-capitalize"><?php esc_html_e('RECENTLY UPDATED', 'amzcoupons') ?>
                        </option>
                        <option value="3" <?php echo ($selectedSorting == 3) ? ' selected="selected"' : ''; ?>
                                class="text-capitalize"><?php esc_html_e('RECENTLY ADDED', 'amzcoupons') ?>
                        </option>
                        <option value="1" <?php echo ($selectedSorting == 1) ? ' selected="selected"' : ''; ?>
                                class="text-capitalize"><?php esc_html_e('LOWEST PRICE FIRST', 'amzcoupons') ?>
                        </option>
                        <option value="4" <?php echo ($selectedSorting == 4) ? ' selected="selected"' : ''; ?>
                                class="text-capitalize"><?php esc_html_e('ORIGINAL PRICE - HIGHEST FIRST', 'amzcoupons') ?>
                        </option>
                    </select>
                </div>

            </div>
            <div class="col-md-4">

                <div class="row flex_data discount-filter">
                    <div class="col-md-6 mb-0">
                        <label for="front_discount"><?php esc_html_e('Discount', 'amzcoupons') ?></label>
                    </div>
                    <div class="col-md-6 mb-0">
                        <input type="text" id="front_discount" class="rangeinput" readonly style="color: <?php echo esc_attr($secondaryColorPlugin); ?>">
                    </div>
                </div>

                <div id="slider-range"></div>
            </div>
        </div>
        <div class="row">
            <div class="col-md-kwsearch">
                <input type="text" id="skw" name="skw" class="filtertext" title="<?php esc_attr_e('Search by Keyword...', 'amzcoupons') ?>"
                       placeholder="<?php esc_attr_e('Search by Keyword...', 'amzcoupons') ?>"
                       value="<?php if (!empty($GET['skw'])) echo esc_attr($GET['skw']); ?> ">

            </div>
            <div class="col-md-btnfilter">

                <input type="submit" id="search" value="<?php esc_attr_e('Search', 'amzcoupons') ?>" class="float-right"
                       style="border: 0; background-color: <?php echo esc_attr($primaryColorPlugin) ?>">
                <!--                <input type="submit" name="submit" id="search" value="Search" class="float-right" style="border: 0">-->
            </div>
        </div>
    </div>
</form>
