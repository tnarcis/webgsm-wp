(function($) {
    $(function() {
        var $form = $('.webgsm-bis-form');
        if (!$form.length || typeof webgsmBis === 'undefined') return;

        $form.on('submit', function(e) {
            e.preventDefault();
            var $f = $(this);
            var $msg = $f.find('.webgsm-bis-msg');
            var $btn = $f.find('.webgsm-bis-btn');
            var productId = $f.data('product-id') || webgsmBis.product_id;
            var email = $f.find('.webgsm-bis-email').val().trim();

            $msg.removeClass('success error loading').text('');
            $btn.prop('disabled', true);
            $msg.addClass('loading').text('Se trimite...');

            $.post(webgsmBis.ajaxurl, {
                action: 'webgsm_bis_subscribe',
                nonce: webgsmBis.nonce,
                product_id: productId,
                email: email
            })
            .done(function(res) {
                $msg.removeClass('loading');
                if (res.success) {
                    $msg.addClass('success').text(res.data && res.data.message ? res.data.message : 'Te vom anunța când produsul revine în stoc.');
                    $f[0].reset();
                } else {
                    $msg.addClass('error').text(res.data && res.data.message ? res.data.message : 'A apărut o eroare.');
                }
            })
            .fail(function() {
                $msg.removeClass('loading').addClass('error').text('Eroare de conexiune. Încearcă din nou.');
            })
            .always(function() {
                $btn.prop('disabled', false);
            });
        });
    });
})(jQuery);
