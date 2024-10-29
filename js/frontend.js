jQuery("#country").change(function () {

    var countryid = jQuery("#country option:selected").val();

    jQuery.ajax({
        url: amazon_deals_jv_ajax_url,
        type: "GET",
        dataType: 'html',
        data: {
            action: 'show_category_data_frontend',
            countryid: countryid,
        },
        beforeSend: function (response) {
            jQuery(".category_frontend").html(response);
        },
        success: function (response) {
            jQuery(".category_frontend").html(response);

        },
        error: function (data) {

        }
    });
});

jQuery(document).ready(function () {

    if (jQuery("#slider-range").length) {
        var discountslider = jQuery("#slider-range");
        discountslider.slider({
            range: true,
            min: 30,
            max: 100,
            values: [get_discmin, get_discmax],
            slide: function (event, ui) {
                jQuery("#front_discount").val(ui.values[0] + "% - " + ui.values[1] + "%")
            }
        });
        jQuery("#front_discount").val("" + discountslider.slider("values", 0) + "% - " + discountslider.slider("values", 1) + "%");
    }


    jQuery('#deals-search-form').submit(function (event) {
        event.preventDefault();
        jQuery("#pglim1").val(1);
        jQuery("#mind1").val(discountslider.slider("values", 0) * 1);
        jQuery("#maxd1").val(discountslider.slider("values", 1) * 1);


        jQuery(this).unbind('submit').submit();
        return true; // return false to cancel form action
    });

});


// $.fn.andSelf = function () {
//     return this.addBack.apply(this, arguments);
// }

jQuery(document).ready(function () {
    jQuery("#promo-images-300").owlCarousel({
        autoplay: true,
        rewind: true,
        margin: 20,
        responsiveClass: true,
        autoplayTimeout: 7000,
        smartSpeed: 800,
        nav: true,
        responsive: {
            0: {
                items: 1
            },

            600: {
                items: 2
            },

            1024: {
                items: 3
            },

            1366: {
                items: 3
            }
        }
    });
});