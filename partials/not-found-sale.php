<div style="margin: 30px auto; text-align: center">
    <h4><?php esc_html_e('DEAL NOT FOUND', 'amzcoupons') ?></h4>
    <span><?php esc_html_e('redirecting to deals page...', 'amzcoupons') ?></span>
</div>
<?php
$dealsHomePage = get_option('amazondeal-deals-page');
if (!$dealsHomePage) {
    $dealsHomePage = home_url();
}
?>
<script>
    setTimeout(function () {
        window.location.href = '<?php echo $dealsHomePage; ?>';
    }, 3000);
</script>