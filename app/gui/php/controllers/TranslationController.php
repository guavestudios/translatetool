<?php

class TranslationController extends BaseController
{
	public static function addFolder()
	{
		self::mirror();
		translations::append(array(
			array(
				'key' => $_POST['foldername'],
				'parent_id' => $_POST['parent_id'],
				'value' => null
			)
		));
		self::redirect('key/' . translations::insertId());
	}

	public static function editFolder()
	{
		self::mirror();
		$editId = isset($_POST['edit_id']) ? (int) $_POST['edit_id'] : (int) $_POST['parent_id'];
		$newParentId = isset($_POST['parent_id']) ? (int) $_POST['parent_id'] : 0;

		$availableParents = self::getAvailableFolderParents($editId);
		$allowedParentIds = array_map(function ($parent) {
			return (int) $parent['id'];
		}, $availableParents);
		if (!in_array($newParentId, $allowedParentIds, true)) {
			$newParentId = 0;
		}

		translations::update(
			$editId,
			array(
				'key' => $_POST['foldername'],
				'parent_id' => $newParentId
			)
		);
		self::redirect('key/' . $editId);
	}

	public static function delFolder($delId)
	{
		self::recursiveDelete($delId);
		self::redirect('overview');
	}

	private static function recursiveDelete($delId)
	{
		$items = translations::get(array(), array(), "parent_id = {$delId}");
		if (!empty($items)) {
			foreach ($items as $item) {
				self::recursiveDelete($item['id']);
			}
		}
		translations::deleteById($delId);
	}

	public static function showAddFolder($parentId)
	{
		self::render('editfolder', array(
			'active' => $parentId,
			'selected_parent_id' => (int) $parentId,
			'parent_options' => self::getAvailableFolderParents()
		));
	}

	public static function showEditFolder($editId)
	{
		$folder = translations::getOne($editId);
		self::render('editfolder', array(
			'active' => $editId,
			'folder' => $folder,
			'selected_parent_id' => isset($folder['parent_id']) ? (int) $folder['parent_id'] : 0,
			'parent_options' => self::getAvailableFolderParents((int) $editId)
		));
	}

	public static function deleteKey($keyId, $active)
	{
		self::mirror();
		translations::deleteRow($keyId);
		self::redirect('key/' . $active);
	}

	public static function deleteMultiKeys()
	{
		self::mirror();
		$keyIds = isset($_POST['keyIds']) ? $_POST['keyIds'] : array();
		if (!is_array($keyIds)) {
			$keyIds = array();
		}

		$sanitizedIds = array();
		foreach ($keyIds as $id) {
			if (is_numeric($id)) {
				$sanitizedIds[] = (int) $id;
			}
		}

		$sanitizedIds = array_values(array_unique($sanitizedIds));
		if (!empty($sanitizedIds)) {
			translations::deleteRows($sanitizedIds);
		}

		header('Content-Type: application/json');
		echo json_encode(array('status' => true));
	}

	public static function key($keyId)
	{
		$keys = translations::getValues($keyId);
		self::render('overview', array('keys' => $keys, 'active' => $keyId));
	}

	public static function saveKeys($keyId)
	{
		self::mirror();
		$entries = array();
		foreach ($_POST['key'] as $k => $key) {
			$value = $_POST['value'][$k];
			$id = $_POST['id'][$k];
			$language = $_POST['language'][$k];

			$duplicate = false;
			for ($i = $k - 1; $i >= 0; $i--) {
				if ($_POST['key'][$i] == $key && $_POST['language'][$i] == $language) {
					$duplicate = true;
					break;
				}
			}
			if ($duplicate) {
				continue;
			}

			if (empty($id) and $value == '' and $key == '') {
				continue;
			}
			if (empty($id) and $value !== '' and $key !== '') {
				$entries[] = array(
					'parent_id' => $keyId,
					'key' => $key,
					'value' => $value,
					'language' => $language
				);
			}
			if (!empty($id) and $key !== '') {
				translations::update($id, array(
					'key' => $key,
					'value' => $value
				));
			}
		}
		translations::append($entries);
		ExportController::export();
		self::redirect('key/' . $keyId);
	}

	public static function overview()
	{
		self::render('overview', array('active' => 0));
	}

	public static function search()
	{
		$searchString = $_POST['search'];
		$results = translations::searchForKey($searchString);
		self::render('search', array('results' => $results, 'active' => 0));
	}

	public static function poll()
	{
		header('Content-Type: application/json');
		echo json_encode(array('status' => true));
	}

	public static function nottranslated()
	{
		$langs = config::get('languages');
		$dbData = translations::get(array(), array('key'));
		$keymap = array();
		self::fillKeytree($dbData, $keymap);

		$data = array();
		foreach ($dbData as $entry) {
			if ($entry["parent_id"] == 0) {
				continue;
			}
			if ($entry["value"] == null && $entry["language"] == null) {
				continue;
			}
			$data[$entry["key"]][$entry['language']] = $entry;
		}

		$missing = array();
		foreach ($data as $entrylang) {
			$missingLanguages = array();
			foreach ($langs as $lang) {
				if (empty($entrylang[$lang]) || $entrylang[$lang]['value'] === '') {
					$missingLanguages[] = $lang;
				}
			}

			if (!empty($missingLanguages)) {
				$entry = reset($entrylang);
				$missing[] = array(
					'key' => $entry["key"],
					'folder_id' => $entry['parent_id'],
					'folder_name' => $keymap[$entry['parent_id']],
					'row_id' => $entry['id'],
					'languages' => $missingLanguages
				);
			}
		}

		self::render('nottranslated', array(
			'keys' => $missing
		));
	}

	private static function fillKeytree(&$dbArr, &$output)
	{
		foreach ($dbArr as &$entry) {
			if (empty($output[$entry["id"]])) {
				$key = $entry["key"];
				$pid = $entry["parent_id"];
				while ($pid != 0) {
					foreach ($dbArr as &$search) {
						if ($search["id"] == $pid) {
							$key = $search["key"] . '.' . $key;
							$pid = $search["parent_id"];
						}
					}
				}
				$output[$entry["id"]] = $key;
			}
		}
	}

	private static function getAvailableFolderParents($excludeFolderId = null)
	{
		$where = "value IS NULL AND language IS NULL";
		$folders = translations::get(array('id', 'key', 'parent_id'), array('key'), $where);
		$childrenByParent = array();
		foreach ($folders as $folder) {
			$parentId = (int) $folder['parent_id'];
			if (!isset($childrenByParent[$parentId])) {
				$childrenByParent[$parentId] = array();
			}
			$childrenByParent[$parentId][] = $folder;
		}

		$excludedIds = array();
		if ($excludeFolderId !== null) {
			$excludeFolderId = (int) $excludeFolderId;
			$excludedIds[] = $excludeFolderId;
			$excludedIds = array_merge($excludedIds, self::getDescendantFolderIds($excludeFolderId, $childrenByParent));
		}

		$options = array(
			array('id' => 0, 'label' => 'Root')
		);
		self::appendFolderParentOptions($options, $childrenByParent, 0, '', $excludedIds);
		return $options;
	}

	private static function appendFolderParentOptions(&$options, $childrenByParent, $parentId, $prefix, $excludedIds)
	{
		if (!isset($childrenByParent[$parentId])) {
			return;
		}

		foreach ($childrenByParent[$parentId] as $folder) {
			$currentId = (int) $folder['id'];
			if (in_array($currentId, $excludedIds, true)) {
				continue;
			}
			$label = ($prefix === '') ? $folder['key'] : $prefix . '.' . $folder['key'];
			$options[] = array('id' => $currentId, 'label' => $label);
			self::appendFolderParentOptions($options, $childrenByParent, $currentId, $label, $excludedIds);
		}
	}

	private static function getDescendantFolderIds($folderId, $childrenByParent)
	{
		$descendants = array();
		if (!isset($childrenByParent[$folderId])) {
			return $descendants;
		}

		foreach ($childrenByParent[$folderId] as $child) {
			$childId = (int) $child['id'];
			$descendants[] = $childId;
			$descendants = array_merge($descendants, self::getDescendantFolderIds($childId, $childrenByParent));
		}
		return $descendants;
	}
}
