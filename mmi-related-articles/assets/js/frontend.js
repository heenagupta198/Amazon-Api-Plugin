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

		function cardsPerView() {
			if (window.matchMedia('(max-width: 767px)').matches) {
				return 1;
			}
			if (window.matchMedia('(max-width: 1024px)').matches) {
				return 2;
			}
			return 3;
		}

		var index = 0;

		function maxIndex() {
			var cards = track.children.length;
			var per = cardsPerView();
			return Math.max(0, cards - per);
		}

		function update() {
			var card = track.querySelector('.mmi-ra-card');
			if (!card) {
				return;
			}
			var gap = 16;
			var cardWidth = card.getBoundingClientRect().width + gap;
			track.style.transform = 'translateX(' + -index * cardWidth + 'px)';
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

		window.addEventListener('resize', function () {
			index = Math.min(index, maxIndex());
			update();
		});

		update();
	});
})();
