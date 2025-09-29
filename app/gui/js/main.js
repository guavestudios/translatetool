// Highlight function using vanilla JS
function highlight(elemId) {
	if (!elemId) return;
	const elem = document.querySelector(elemId);
	if (elem) {
		elem.classList.add('highlighted');
	}
}

document.addEventListener('DOMContentLoaded', function () {
	highlight(document.location.hash);

	function syncMainWithSubInputs(mainInput, subInputs) {
		if (!mainInput || !subInputs) return;
		mainInput.addEventListener('input', function () {
			subInputs.forEach(function (subInput) {
				subInput.value = mainInput.value;
			});
		});
		// Sync on page load
		subInputs.forEach(function (subInput) {
			subInput.value = mainInput.value;
		});
	}

	// For each trans-item, sync only its own key inputs
	document.querySelectorAll('.trans-item').forEach(function (container) {
		const mainKeyInput = container.querySelector('.keyname-input-main');
		const syncInputs = container.querySelectorAll('.sync-key');
		syncMainWithSubInputs(mainKeyInput, syncInputs);
		// JS delete link logic
		const deleteLink = container.querySelector('.delete-key');
		if (deleteLink) {
			const row = container.querySelector('.lang-row input[name="id[]"]');
			if (row && row.value) {
				const active = deleteLink.getAttribute('data-active');
				deleteLink.href = `delete/${row.value}/${active}`;
				deleteLink.onclick = function () {
					const keyName = mainKeyInput ? mainKeyInput.value : '';
					return confirm(`Wirklich löschen?\nKey: ${keyName}`);
				};
			} else {
				deleteLink.style.display = 'none';
			}
		}
	});

	// Multi-delete mode logic
	const multiDeleteBtn = document.getElementById('multiDeleteModeBtn');
	const deleteSelectedBtn = document.getElementById('deleteSelectedBtn');
	let multiDeleteMode = false;

	if (multiDeleteBtn) {
		multiDeleteBtn.addEventListener('click', function () {
			multiDeleteMode = !multiDeleteMode;
			document.querySelectorAll('.multi-delete-checkbox').forEach(cb => {
				document.documentElement.classList.toggle('multi-delete-mode', multiDeleteMode);
				cb.checked = false;
			});
			deleteSelectedBtn.style.display = multiDeleteMode ? '' : 'none';
			multiDeleteBtn.textContent = multiDeleteMode ? 'Auswahlmodus beenden' : 'Multi selection';
		});

		deleteSelectedBtn.addEventListener('click', function () {
			const checked = Array.from(document.querySelectorAll('.multi-delete-checkbox:checked'));
			if (checked.length === 0) return;
			const keyNames = checked.map(cb => {
				const container = cb.closest('.trans-item');
				return container ? (container.querySelector('.keyname-input-main')?.value || '') : '';
			});
			if (window.confirm('Möchtest du die folgenden Keys wirklich löschen?\n' + keyNames.join('\n'))) {
				// Collect all first row ids for selected keys
				const ids = checked.map(cb => {
					const container = cb.closest('.trans-item');
					const row = container.querySelector('.lang-row input[name="id[]"]');
					return row ? row.value : null;
				}).filter(Boolean);
				if (ids.length > 0) {
					// Batch delete via AJAX POST
					fetch('delete/multikeys', {
						method: 'POST',
						headers: {
							'Content-Type': 'application/x-www-form-urlencoded'
						},
						body: 'keyIds[]=' + ids.join('&keyIds[]=')
					})
						.then(() => window.location.reload())
						.catch(() => window.location.reload());
				}
			}
		});
	}

	// Save all changes with Control + S
	const keyform = document.querySelector('.keyform');
	if (keyform) {
		document.addEventListener('keydown', function (evt) {
			// Save on Ctrl+S or Cmd+S
			if ((evt.key === 's' || evt.key === 'S') && (evt.ctrlKey || evt.metaKey)) {
				evt.preventDefault();
				keyform.submit();
			}
		});
	}

	// Add new key row on Enter in add-key-section
	const addKeySection = document.querySelector('.add-key-section');
	if (addKeySection) {
		addKeySection.addEventListener('keydown', function (e) {
			if (e.key === 'Enter' && e.target.tagName === 'INPUT') {
				e.preventDefault();
				const transItem = addKeySection.querySelector('.trans-item:first-of-type');
				const clone = transItem.cloneNode(true)
				function resetInputs(element) {
					const allInputs = element.querySelectorAll('input[type="text"]');
					allInputs.forEach(el => { el.value = ''; });
				}
				resetInputs(clone)
				addKeySection.append(clone)
				const mainKeyInput = clone.querySelector('.keyname-input-main');
				const syncInputs = clone.querySelectorAll('.sync-key');
				syncMainWithSubInputs(mainKeyInput, syncInputs);
				if (mainKeyInput) {
					mainKeyInput.focus();
				}
			}
		});
	}
});