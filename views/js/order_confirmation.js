(function ($) {
    $(function () {
        if($('#block-order-detail').length)
            showOrder(1, order_confirmation_id_order, order_confirmation_file);
        else
            console.log('ORDER_CONFIRMATION ERROR: Do not isset div#block-order-detail');
    });
})(jQuery);