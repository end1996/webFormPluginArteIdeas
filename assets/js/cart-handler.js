jQuery(document).ready(function($) {
    $(document).on('change', 'form.woocommerce-cart-form input.qty', function() {
        var $cartForm = $(this).closest('form.woocommerce-cart-form');
        $cartForm.find('button[name="update_cart"]').prop('disabled', false).trigger('click');
    });
});