(function () {
	'use strict';

	document.querySelectorAll('[data-mmi-ra-carousel]').forEach(function (root) {
		var track = root.querySelector('.mmi-ra-track');
		var viewport = root.querySelector('.mmi-ra-track-viewport');
		var prev = root.querySelector('.mmi-ra-prev');
		var next = root.querySelector('.mmi-ra-next');
		if (!track || !viewport) {
			return;
		}

		var index = 0;
		var gap = 24;

		function cardsPerView() {
			if (window.matchMedia('(max-width: 767px)').matches) {
				return 1;
			}
			if (window.matchMedia('(max-width: 1018px)').matches) {
				return 2;
			}
			return 3;
		}

		function cardStepWidth() {
			var card = track.querySelector('.mmi-ra-module');
			if (!card) {
				return 0;
			}
			return card.getBoundingClientRect().width + gap;
		}

		function maxIndex() {
			var cards = track.children.length;
			var per = cardsPerView();
			return Math.max(0, cards - per);
		}

		function update() {
			index = Math.min(index, maxIndex());
			var step = cardStepWidth();
			if (step > 0) {
				track.style.transform = 'translateX(' + -index * step + 'px)';
			}
			if (prev) {
				prev.disabled = index <= 0;
			}
			if (next) {
				next.disabled = index >= maxIndex();
			}
		}

		function step(dir) {
			index = Math.min(Math.max(0, index + dir), maxIndex());
			update();
		}

		if (prev) {
			prev.addEventListener('click', function () {
				step(-1);
			});
		}
		if (next) {
			next.addEventListener('click', function () {
				step(1);
			});
		}

		var startX = 0;
		viewport.addEventListener(
			'touchstart',
			function (e) {
				startX = e.touches[0].clientX;
			},
			{ passive: true }
		);
		viewport.addEventListener(
			'touchend',
			function (e) {
				var dx = e.changedTouches[0].clientX - startX;
				if (Math.abs(dx) < 40) {
					return;
				}
				step(dx < 0 ? 1 : -1);
			},
			{ passive: true }
		);

		window.addEventListener('resize', update);
		update();
	});
})();
