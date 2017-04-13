(function () {
	var lastScrollTop = 0,
		topbar = document.querySelector('.topbar');

	window.addEventListener('scroll', function () {
		var scrollTop = window.pageYOffset;

		if (scrollTop >= 200 && lastScrollTop < 200) {
			topbar.classList.add('scrolled');
		} else if (scrollTop < 200 && lastScrollTop >= 200) {
			topbar.classList.remove('scrolled');
		}

		lastScrollTop = scrollTop;
	});
})();

(function () {
	var inputs = document.querySelectorAll('.input input,.input textarea'),
		i;

	function checkValue() {
		if (this.value) {
			this.parentElement.classList.add('filled');
		} else {
			this.parentElement.classList.remove('filled');
		}
	}

	for (i = 0; i < inputs.length; i++) {
		inputs[i].addEventListener('focus', function (e) {
			this.parentElement.classList.add('focus');
		});
		inputs[i].addEventListener('blur', function (e) {
			this.parentElement.classList.remove('focus');
		});
		inputs[i].addEventListener('keyup', checkValue);
		checkValue.call(inputs[i]);
	}
})();

(function () {
	var searchButton = document.querySelector('.social.buttons .search'),
		searchBar = document.querySelector('.searchbar'),
		searchBarVisible = false;

	if (searchButton && searchBar) {
		searchButton.addEventListener('click', function (e) {
			e.preventDefault();
			e.stopPropagation();
			searchBar.classList.add('visible');
			searchBar.querySelector('input').focus();
			searchBarVisible = true;
		});
		window.addEventListener('click', function (e) {
			var el = e.target;
			if (searchBarVisible) {
				while (el && el != searchBar) { el = el.parentElement; }
				if (!el) {
					searchBar.classList.remove('visible');
					searchBarVisible = false;
				}
			}
		});
	}
})();