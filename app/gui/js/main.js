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

	// Bulk actions mode (delete + move)
	const bulkActionsBtn = document.getElementById('bulkActionsModeBtn');
	const bulkActionsBtnText = bulkActionsBtn?.innerText;
	const bulkActionsToolbar = document.getElementById('bulkActionsToolbar');
	const deleteSelectedBtn = document.getElementById('deleteSelectedBtn');
	const moveSelectedBtn = document.getElementById('moveSelectedBtn');
	const confirmMoveBtn = document.getElementById('confirmMoveBtn');
	const bulkMoveTarget = document.getElementById('bulkMoveTarget');
	const bulkMovePanel = document.getElementById('bulkMovePanel');
	const bulkMoveDropdown = document.getElementById('bulkMoveDropdown');
	let bulkActionsMode = false;

	function getSelectedKeyItems() {
		const checked = Array.from(document.querySelectorAll('.custom-checkbox:checked'));
		const keyNames = [];
		const ids = [];
		checked.forEach(cb => {
			const container = cb.closest('.trans-item');
			if (!container) return;
			keyNames.push(container.querySelector('.keyname-input-main')?.value || '');
			const row = container.querySelector('.lang-row input[name="id[]"]');
			if (row && row.value) {
				ids.push(row.value);
			}
		});
		return { keyNames, ids };
	}

	function setMovePanelOpen(open) {
		if (!bulkMovePanel || !moveSelectedBtn) return;
		bulkMovePanel.hidden = !open;
		moveSelectedBtn.setAttribute('aria-expanded', open ? 'true' : 'false');
	}

	if (bulkActionsBtn && bulkActionsToolbar) {
		bulkActionsBtn.addEventListener('click', function () {
			bulkActionsMode = !bulkActionsMode;
			document.documentElement.classList.toggle('multi-bulk-mode', bulkActionsMode);
			document.querySelectorAll('.custom-checkbox').forEach(cb => {
				cb.checked = false;
			});
			setMovePanelOpen(false);
			bulkActionsBtn.textContent = bulkActionsMode ? 'Exit bulk actions' : bulkActionsBtnText;
		});

		if (deleteSelectedBtn) {
			deleteSelectedBtn.addEventListener('click', function () {
				setMovePanelOpen(false);
				const { keyNames, ids } = getSelectedKeyItems();
				if (ids.length === 0) return;
				if (!window.confirm('Möchtest du die folgenden Keys wirklich löschen?\n' + keyNames.join('\n'))) {
					return;
				}
				fetch('delete/multikeys', {
					method: 'POST',
					headers: {
						'Content-Type': 'application/x-www-form-urlencoded'
					},
					body: 'keyIds[]=' + ids.join('&keyIds[]=')
				})
					.then(() => window.location.reload())
					.catch(() => window.location.reload());
			});
		}

		if (moveSelectedBtn && bulkMovePanel && bulkMoveTarget && confirmMoveBtn) {
			moveSelectedBtn.addEventListener('click', function () {
				const { ids } = getSelectedKeyItems();
				if (ids.length === 0) {
					setMovePanelOpen(false);
					return;
				}
				setMovePanelOpen(bulkMovePanel.hidden);
				if (!bulkMovePanel.hidden) {
					bulkMoveTarget.focus();
				}
			});

			confirmMoveBtn.addEventListener('click', function () {
				const { keyNames, ids } = getSelectedKeyItems();
				if (ids.length === 0) {
					setMovePanelOpen(false);
					return;
				}
				const targetLabel = bulkMoveTarget.options[bulkMoveTarget.selectedIndex]?.text || '';
				if (!window.confirm(
					'Möchtest du die folgenden Keys nach "' + targetLabel + '" verschieben?\n' + keyNames.join('\n')
				)) {
					return;
				}
				const body = 'parent_id=' + encodeURIComponent(bulkMoveTarget.value)
					+ '&keyIds[]=' + ids.join('&keyIds[]=');
				fetch('move/multikeys', {
					method: 'POST',
					headers: {
						'Content-Type': 'application/x-www-form-urlencoded'
					},
					body: body
				})
					.then(async (response) => {
						let data = null;
						try {
							data = await response.json();
						} catch (e) {
							data = null;
						}
						if (response.ok && data && data.status) {
							window.location.reload();
							return;
						}
						const conflicts = data && Array.isArray(data.conflicts) ? data.conflicts : [];
						if (conflicts.length > 0) {
							window.alert(
								'Verschieben abgebrochen. Diese Keys existieren bereits im Zielordner:\n'
								+ conflicts.join('\n')
							);
							return;
						}
						window.alert('Verschieben fehlgeschlagen.');
					})
					.catch(() => {
						window.alert('Verschieben fehlgeschlagen.');
					});
			});

			document.addEventListener('click', function (event) {
				if (!bulkMoveDropdown || bulkMovePanel.hidden) return;
				if (!bulkMoveDropdown.contains(event.target)) {
					setMovePanelOpen(false);
				}
			});

			document.addEventListener('keydown', function (event) {
				if (event.key === 'Escape') {
					setMovePanelOpen(false);
				}
			});
		}
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

	// Add another blank key form on Enter in the add-key section.
	// Textareas never accept newlines, so Enter there means the same as in inputs:
	// stage the current row and start the next one (submit still via Add Key / Cmd+S).
	const addKeySection = document.querySelector('.add-key-section');
	if (addKeySection) {
		addKeySection.addEventListener('keydown', function (e) {
			if (e.key !== 'Enter') return;
			if (e.target.matches('input[type="submit"], button')) return;
			if (!e.target.matches('input, textarea')) return;

			e.preventDefault();
			const transItem = addKeySection.querySelector('.trans-item');
			const clone = transItem.cloneNode(true);

			const addKeyActions = clone.querySelector('.add-key-actions');
			if (addKeyActions) {
				addKeyActions.remove();
			}

			clone.querySelectorAll('input[type="text"], textarea').forEach(function (el) {
				el.value = '';
			});

			transItem.parentNode.insertBefore(clone, transItem);

			const mainKeyInput = clone.querySelector('.keyname-input-main');
			const syncInputs = clone.querySelectorAll('.sync-key');
			syncMainWithSubInputs(mainKeyInput, syncInputs);
			if (mainKeyInput) {
				mainKeyInput.focus();
			}
		});
	}

	const collapsibles = document.querySelectorAll('.collapsible');
	collapsibles.forEach(function (collapsible) {
		const header = collapsible.querySelector('.collapsible-header');
		const content = collapsible.querySelector('.collapsible-content');
		if (header && content) {
			header.addEventListener('click', function () {
				collapsible.classList.toggle('open');
			});
		}
	});
});