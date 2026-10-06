document.addEventListener('click', (event) => {
	if (!(event.target instanceof Element)) {
		return;
	}

	const button = event.target.closest('[data-password-toggle]');

	if (!button) {
		return;
	}

	const input = document.getElementById(button.getAttribute('aria-controls'));

	if (!(input instanceof HTMLInputElement)) {
		return;
	}

	const showPassword = input.type === 'password';

	input.type = showPassword ? 'text' : 'password';
	button.textContent = showPassword ? 'Yashirish' : 'Ko‘rsatish';
	button.setAttribute('aria-label', showPassword ? 'Parolni yashirish' : 'Parolni ko‘rsatish');
	button.setAttribute('aria-pressed', String(showPassword));
});

document.addEventListener('DOMContentLoaded', () => {
	const authorList = document.querySelector('#article-authors');
	const authorTemplate = document.querySelector('#article-author-template');
	const addAuthorButton = document.querySelector('[data-add-author]');

	if (!authorList || !(authorTemplate instanceof HTMLTemplateElement) || !addAuthorButton) {
		return;
	}

	const syncSubmitterValues = () => {
		const selectedIndex = authorList.querySelector('input[name="submitter_index"]:checked')?.value;
		for (const submitterValue of authorList.querySelectorAll('[data-submitter-value]')) {
			submitterValue.value = submitterValue.dataset.authorIndex === selectedIndex ? '1' : '0';
		}
	};

	addAuthorButton.addEventListener('click', () => {
		const index = Number(authorList.dataset.nextIndex);
		authorList.dataset.nextIndex = String(index + 1);
		authorList.insertAdjacentHTML('beforeend', authorTemplate.innerHTML.replaceAll('__INDEX__', String(index)));
	});

	authorList.addEventListener('change', (event) => {
		if (event.target instanceof HTMLInputElement && event.target.name === 'submitter_index') {
			syncSubmitterValues();
			return;
		}

		if (!(event.target instanceof HTMLSelectElement) || !event.target.matches('[data-author-user]')) {
			return;
		}

		const selected = event.target.selectedOptions[0];
		const nameInput = event.target.closest('[data-author-row]')?.querySelector('input[name$="[full_name]"]');
		if (nameInput instanceof HTMLInputElement && selected?.dataset.name) {
			nameInput.value = selected.dataset.name;
		}
	});

	authorList.addEventListener('click', (event) => {
		if (!(event.target instanceof Element)) {
			return;
		}

		const removeButton = event.target.closest('[data-remove-author]');
		if (removeButton) {
			const row = removeButton.closest('[data-author-row]');
			const wasSubmitter = row?.querySelector('input[name="submitter_index"]:checked') !== null;
			row?.remove();
			if (wasSubmitter) {
				authorList.querySelector('input[name="submitter_index"]')?.click();
			}
			syncSubmitterValues();
		}
	});
});

document.addEventListener('DOMContentLoaded', () => {
	const tier = document.querySelector('#tier');
	const warning = document.querySelector('#warning_text');

	if (!(tier instanceof HTMLSelectElement) || !(warning instanceof HTMLTextAreaElement) || !warning.dataset.tierXWarning) {
		return;
	}

	tier.addEventListener('change', () => {
		if (tier.value === 'X' && warning.value.trim() === '') {
			warning.value = warning.dataset.tierXWarning;
		}
	});
});

document.addEventListener('DOMContentLoaded', () => {
	const type = document.querySelector('#type');
	const sections = document.querySelectorAll('[data-article-family]');
	const journalName = document.querySelector('#journal_name');
	const journalId = document.querySelector('#journal_id');
	const issn = document.querySelector('#issn');
	const results = document.querySelector('#journal-search-results');
	const riskWarning = document.querySelector('#dangerous-journal-warning');

	if (!type) {
		return;
	}

	const updateFamilyFields = () => {
		const family = type.selectedOptions[0]?.dataset.family;
		for (const section of sections) {
			const visible = section.dataset.articleFamily === family;
			section.hidden = !visible;
			for (const field of section.querySelectorAll('input,select')) {
				field.disabled = !visible;
				field.required = visible && field.hasAttribute('data-required');
			}
		}
	};

	type.addEventListener('change', updateFamilyFields);
	updateFamilyFields();

	if (!(journalName instanceof HTMLInputElement) || !(journalId instanceof HTMLInputElement) || !(issn instanceof HTMLInputElement) || !(results instanceof HTMLUListElement)) {
		return;
	}

	let searchTimer;
	if (riskWarning instanceof HTMLElement) {
		riskWarning.dataset.defaultText = riskWarning.textContent;
	}
	journalName.addEventListener('input', () => {
		journalId.value = '';
		if (riskWarning) {
			riskWarning.hidden = true;
		}
		clearTimeout(searchTimer);
		const query = journalName.value.trim();
		if (query.length < 2) {
			results.hidden = true;
			results.replaceChildren();
			return;
		}

		searchTimer = setTimeout(async () => {
			try {
				const response = await fetch(`/api/jurnallar/qidiruv?q=${encodeURIComponent(query)}`, {
					headers: { Accept: 'application/json' },
				});
				if (!response.ok) {
					results.hidden = true;
					return;
				}

				const journals = await response.json();
				results.replaceChildren();
				for (const journal of journals) {
					const item = document.createElement('li');
					const button = document.createElement('button');
					button.type = 'button';
					button.className = 'block w-full px-3 py-2 text-left text-sm hover:bg-slate-100';
					button.textContent = `${journal.name} · ${journal.issn || 'ISSN yo‘q'} · ${journal.tier}`;
					button.addEventListener('click', () => {
						journalName.value = journal.name;
						journalId.value = journal.id;
						issn.value = journal.issn || '';
						results.hidden = true;
						if (riskWarning) {
							riskWarning.textContent = journal.warning_text || riskWarning.dataset.defaultText || riskWarning.textContent;
							riskWarning.hidden = journal.tier !== 'X';
						}
					});
					item.append(button);
					results.append(item);
				}
				results.hidden = journals.length === 0;
			} catch {
				results.hidden = true;
			}
		}, 250);
	});

	document.addEventListener('click', (event) => {
		if (event.target instanceof Node && !results.contains(event.target) && event.target !== journalName) {
			results.hidden = true;
		}
	});
});
