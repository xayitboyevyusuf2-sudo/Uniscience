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

	addAuthorButton.addEventListener('click', () => {
		const index = Number(authorList.dataset.nextIndex);
		authorList.dataset.nextIndex = String(index + 1);
		authorList.insertAdjacentHTML('beforeend', authorTemplate.innerHTML.replaceAll('__INDEX__', String(index)));
	});

	authorList.addEventListener('change', (event) => {
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
			removeButton.closest('[data-author-row]')?.remove();
		}
	});
});
