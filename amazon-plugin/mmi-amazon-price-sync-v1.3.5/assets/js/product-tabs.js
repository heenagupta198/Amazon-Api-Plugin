(function ($) {
	'use strict';

	function openPricesTab() {
		var $tab = $('li.mmi_prices_tab a, #tab-title-mmi_prices a, a[href="#tab-mmi_prices"]');
		if ($tab.length) {
			$tab.trigger('click');
			var $panel = $('#tab-mmi_prices, .mmi_prices_tab');
			if ($panel.length) {
				$('html, body').animate({ scrollTop: $panel.offset().top - 80 }, 400);
			}
			return true;
		}
		var $wrap = $('.mmi-91-prices');
		if ($wrap.length) {
			$('html, body').animate({ scrollTop: $wrap.offset().top - 80 }, 400);
			return true;
		}
		return false;
	}

	$(document).on('click', '.mmi-aps-open-prices-tab', function (e) {
		e.preventDefault();
		openPricesTab();
	});
}(jQuery));