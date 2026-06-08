(function () {
	'use strict';

	function normalize(value) {
		return (value || '').toString().trim().toLowerCase();
	}

	function setUrlParam(key, value) {
		if (!window.history || !window.history.replaceState) {
			return;
		}

		var url = new URL(window.location.href);

		if (value) {
			url.searchParams.set(key, value);
		} else {
			url.searchParams.delete(key);
		}

		window.history.replaceState({}, '', url.toString());
	}

	function setActiveTab(root, tabName) {
		var buttons = root.querySelectorAll('[data-wcd-tab]');
		var panels = root.querySelectorAll('[data-wcd-panel]');

		buttons.forEach(function (button) {
			var isActive = button.getAttribute('data-wcd-tab') === tabName;
			button.classList.toggle('is-active', isActive);
			button.setAttribute('aria-selected', isActive ? 'true' : 'false');
			button.setAttribute('tabindex', isActive ? '0' : '-1');
		});

		panels.forEach(function (panel) {
			var isActive = panel.getAttribute('data-wcd-panel') === tabName;
			panel.classList.toggle('is-active', isActive);
			panel.hidden = !isActive;
		});

		root.setAttribute('data-active-tab', tabName);
		setUrlParam('tab', tabName === 'upcoming' ? '' : tabName);
	}

	function applyTeamFilter(root, team) {
		var selected = normalize(team);
		var panels = root.querySelectorAll('[data-wcd-panel]');

		panels.forEach(function (panel) {
			var cards = panel.querySelectorAll('[data-wcd-match-card]');
			var visibleCount = 0;

			cards.forEach(function (card) {
				var teams = normalize(card.getAttribute('data-teams'));
				var visible = !selected || teams.indexOf(selected) !== -1;
				card.hidden = !visible;

				if (visible) {
					visibleCount += 1;
				}
			});

			var empty = panel.querySelector('[data-wcd-filter-empty]');

			if (empty) {
				empty.hidden = !selected || visibleCount > 0 || cards.length === 0;
			}
		});

		setUrlParam('team', team || '');
	}

	function initWorldCup(root) {
		var tabs = root.querySelectorAll('[data-wcd-tab]');
		var filter = root.querySelector('[data-wcd-team-filter]');

		tabs.forEach(function (tab) {
			tab.addEventListener('click', function () {
				setActiveTab(root, tab.getAttribute('data-wcd-tab'));
			});

			tab.addEventListener('keydown', function (event) {
				var current = Array.prototype.indexOf.call(tabs, tab);
				var next = null;

				if (event.key === 'ArrowRight') {
					next = tabs[(current + 1) % tabs.length];
				}

				if (event.key === 'ArrowLeft') {
					next = tabs[(current - 1 + tabs.length) % tabs.length];
				}

				if (next) {
					event.preventDefault();
					next.focus();
					setActiveTab(root, next.getAttribute('data-wcd-tab'));
				}
			});
		});

		if (filter) {
			filter.addEventListener('change', function () {
				applyTeamFilter(root, filter.value);
			});

			applyTeamFilter(root, filter.value);
		}
	}

	document.addEventListener('DOMContentLoaded', function () {
		document.querySelectorAll('[data-wcd-worldcup]').forEach(initWorldCup);
	});
}());
