(function () {
    'use strict';

    var box = document.getElementById('webgsm-montaj-box');
    var addMontaj = document.getElementById('webgsm_add_montaj');
    var microCb = document.getElementById('webgsm_microsoldering');
    var totalEl = document.getElementById('webgsm-montaj-total');
    if (!box && !addMontaj) return;

    var montaj = box ? (parseFloat(box.getAttribute('data-montaj') || '0') || 0) : 0;
    var microDefault = box ? (parseFloat(box.getAttribute('data-micro') || '0') || 0) : 0;

    function partPriceFromDom() {
        var priceEl = document.querySelector('.summary .price .woocommerce-Price-amount, .summary .price ins .woocommerce-Price-amount');
        if (!priceEl) return 0;
        var n = parseFloat(String(priceEl.textContent).replace(/[^\d.,]/g, '').replace(',', '.'));
        return isNaN(n) ? 0 : n;
    }

    function updateTotal() {
        if (!totalEl) return;
        var part = partPriceFromDom();
        var micro = microCb && microCb.checked ? microDefault : 0;
        var labor = addMontaj && addMontaj.checked ? montaj : 0;
        var total = part + labor + micro;
        totalEl.textContent = total.toFixed(2).replace('.', ',') + ' lei';
    }

    if (addMontaj) addMontaj.addEventListener('change', updateTotal);
    if (microCb) microCb.addEventListener('change', updateTotal);

    var form = addMontaj ? addMontaj.closest('form.cart') : null;
    if (form) {
        form.addEventListener('submit', function () {
            if (addMontaj && !addMontaj.checked && microCb) {
                microCb.checked = false;
            }
        });
    }
})();
