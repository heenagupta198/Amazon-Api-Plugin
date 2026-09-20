(function () {
	'use strict';

	function closeAllMenus(except) {
		document.querySelectorAll('.mmi-kebab-menu').forEach(function (menu) {
			if (except && menu === except) {
				return;
			}
			menu.hidden = true;
			var btn = menu.parentElement && menu.parentElement.querySelector('.mmi-kebab-btn');
			if (btn) {
				btn.setAttribute('aria-expanded', 'false');
			}
		});
	}

	document.addEventListener('click', function (event) {
		var btn = event.target.closest('.mmi-kebab-btn');
		if (btn) {
			event.preventDefault();
			var menu = btn.parentElement && btn.parentElement.querySelector('.mmi-kebab-menu');
			if (!menu) {
				return;
			}
			var open = !menu.hidden;
			closeAllMenus();
			menu.hidden = open;
			btn.setAttribute('aria-expanded', open ? 'false' : 'true');
			return;
		}

		if (!event.target.closest('.mmi-image-toolbar')) {
			closeAllMenus();
		}
	});

	document.addEventListener('keydown', function (event) {
		if (event.key === 'Escape') {
			closeAllMenus();
		}
	});
})();
