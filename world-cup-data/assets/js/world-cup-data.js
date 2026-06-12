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
		var filterWrap = root.querySelector('[data-wcd-team-filter-wrap]');

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

		if (filterWrap) {
			filterWrap.hidden = !(tabName === 'upcoming' || tabName === 'results');
		}

		setUrlParam('tab', tabName === 'upcoming' ? '' : tabName);
	}

	function applyTeamFilter(root, team) {
		var selected = normalize(team);
		var panels = root.querySelectorAll('[data-wcd-panel]');

		if (!panels.length) {
			var activeTab = root.getAttribute('data-active-tab') || 'upcoming';
			var directCards = root.querySelectorAll('[data-wcd-match-card]');
			var directVisibleCount = 0;
			var shouldFilterDirect = activeTab === 'upcoming' || activeTab === 'results';
			var directLimitList = root.querySelector('[data-wcd-match-list]');
			var directLimit = directLimitList ? parseInt(directLimitList.getAttribute('data-wcd-limit'), 10) || 0 : 0;
			var directShown = 0;

			directCards.forEach(function (card) {
				var teams = normalize(card.getAttribute('data-teams'));
				var matchesTeam = !shouldFilterDirect || !selected || teams.indexOf(selected) !== -1;
				var withinLimit = selected || !directLimit || directShown < directLimit;
				var visible = matchesTeam && withinLimit;
				card.hidden = !visible;

				if (visible) {
					directVisibleCount += 1;
				}

				if (matchesTeam) {
					directShown += 1;
				}
			});

			var directEmpty = root.querySelector('[data-wcd-filter-empty]');

			if (directEmpty) {
				directEmpty.hidden = !shouldFilterDirect || !selected || directVisibleCount > 0 || directCards.length === 0;
			}

			setUrlParam('team', team || '');
			return;
		}

		panels.forEach(function (panel) {
			var panelName = panel.getAttribute('data-wcd-panel');
			var cards = panel.querySelectorAll('[data-wcd-match-card]');
			var visibleCount = 0;
			var shouldFilter = panelName === 'upcoming' || panelName === 'results';
			var limitList = panel.querySelector('[data-wcd-match-list]');
			var limit = limitList ? parseInt(limitList.getAttribute('data-wcd-limit'), 10) || 0 : 0;
			var shown = 0;

			cards.forEach(function (card) {
				var teams = normalize(card.getAttribute('data-teams'));
				var matchesTeam = !shouldFilter || !selected || teams.indexOf(selected) !== -1;
				var withinLimit = selected || !limit || shown < limit;
				var visible = matchesTeam && withinLimit;
				card.hidden = !visible;

				if (visible) {
					visibleCount += 1;
				}

				if (matchesTeam) {
					shown += 1;
				}
			});

			var empty = panel.querySelector('[data-wcd-filter-empty]');

			if (empty) {
				empty.hidden = !shouldFilter || !selected || visibleCount > 0 || cards.length === 0;
			}

		});

		setUrlParam('team', team || '');
	}

	function initWorldCup(root) {
		if (root.getAttribute('data-wcd-initialized') === 'true') {
			return;
		}

		root.setAttribute('data-wcd-initialized', 'true');

		var tabs = root.querySelectorAll('[data-wcd-tab]');
		var filter = root.querySelector('[data-wcd-team-filter]');
		var activeTab = root.getAttribute('data-active-tab') || 'upcoming';

		setActiveTab(root, activeTab);

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

	function getLazyConfig() {
		if (!window.wcdWorldCupData || !window.wcdWorldCupData.ajaxUrl || !window.wcdWorldCupData.nonce) {
			return null;
		}

		return window.wcdWorldCupData;
	}

	function initLazyWorldCup(root) {
		var config = getLazyConfig();
		var status = root.querySelector('[data-wcd-lazy-status]');
		var atts = {};

		if (!config) {
			if (status) {
				status.textContent = 'Could not load World Cup data. Please try again.';
			}

			root.classList.add('wcd-lazy-error');
			return;
		}

		try {
			atts = JSON.parse(root.getAttribute('data-wcd-atts') || '{}');
		} catch (error) {
			atts = {};
		}

		if (status) {
			status.textContent = config.loadingText || 'Loading World Cup data...';
		}

		var body = new URLSearchParams();
		body.append('action', 'wcd_lazy_worldcup');
		body.append('nonce', config.nonce);

		Object.keys(atts).forEach(function (key) {
			body.append('atts[' + key + ']', atts[key]);
		});

		fetch(config.ajaxUrl, {
			method: 'POST',
			credentials: 'same-origin',
			headers: {
				'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
			},
			body: body.toString()
		})
			.then(function (response) {
				if (!response.ok) {
					throw new Error('Request failed');
				}

				return response.json();
			})
			.then(function (payload) {
				if (!payload || !payload.success || !payload.data || !payload.data.html) {
					throw new Error('Invalid response');
				}

				root.insertAdjacentHTML('afterend', payload.data.html);

				var replacement = root.nextElementSibling;
				root.parentNode.removeChild(root);

				if (replacement && replacement.matches('[data-wcd-worldcup]')) {
					initWorldCup(replacement);
				}
			})
			.catch(function () {
				if (status) {
					status.textContent = config.errorText || 'Could not load World Cup data. Please try again.';
				}

				root.classList.add('wcd-lazy-error');
			});
	}

	document.addEventListener('DOMContentLoaded', function () {
		document.querySelectorAll('[data-wcd-worldcup]').forEach(initWorldCup);
		document.querySelectorAll('[data-wcd-worldcup-lazy]').forEach(initLazyWorldCup);
	});
}());
