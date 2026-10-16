document.addEventListener('DOMContentLoaded', function () {
	if (typeof Swiper === 'undefined') {
		return;
	}

	document.querySelectorAll('.related-news-swiper').forEach(function (el) {
		var section = el.closest('.related-news-slider');
		if (!section) {
			return;
		}

		new Swiper(el, {
			slidesPerView: 2,
			spaceBetween: 16,
			loop: false,
			speed: 500,
			navigation: {
				nextEl: section.querySelector('.swiper-button-next'),
				prevEl: section.querySelector('.swiper-button-prev')
			},
			breakpoints: {
				640: { slidesPerView: 3 },
				1024: { slidesPerView: 4 },
				1280: { slidesPerView: 5 }
			}
		});
	});
});
