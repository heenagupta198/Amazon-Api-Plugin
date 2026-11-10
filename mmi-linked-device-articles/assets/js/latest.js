document.addEventListener('DOMContentLoaded', function () {
	if (typeof Swiper === 'undefined') {
		return;
	}

	document.querySelectorAll('.mmi-latest-right').forEach(function (wrapper) {
		var slider = wrapper.querySelector('.mmi-latest-slider');
		if (!slider) {
			return;
		}

		new Swiper(slider, {
			slidesPerView: 1,
			spaceBetween: 20,
			loop: false,
			speed: 600,
			navigation: {
				nextEl: wrapper.querySelector('.swiper-button-next'),
				prevEl: wrapper.querySelector('.swiper-button-prev')
			}
		});
	});
});
