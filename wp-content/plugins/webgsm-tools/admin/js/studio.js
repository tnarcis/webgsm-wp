/**
 * WebGSM Image Studio - placeholder
 */
(function($) {
    'use strict';
    $(document).ready(function() {
        var canvasEl = document.getElementById('studio-canvas');
        if (canvasEl && typeof fabric !== 'undefined') {
            var canvas = new fabric.Canvas('studio-canvas', { width: 400, height: 400 });
        }
    });
})(jQuery);
