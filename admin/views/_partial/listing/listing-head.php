<?php defined('SYSTEM_INIT') or die('Invalid Usage');
$actionItemsData['columnButtons'] = '';

if (isset($actionItemsData['formColumns']) && !empty($actionItemsData['formColumns'])) {
    $actionItemsData['columnButtons'] = '<ul class="list-checkbox list-drag-drop ui-sortable" id="sortable">';
    foreach ($actionItemsData['formColumns'] as $key => $label) {
        $disabled = '';
        $checked = '';
        if (in_array($key, $actionItemsData['defaultColumns'])) {
            $disabled = 'disabled';
            $checked = 'checked="checked"';
        }
        $actionItemsData['columnButtons'] .= '<li>
            <svg class="svg handleJs" width="18" height="18">
                <use
                    xlink:href="' . CONF_WEBROOT_URL . 'images/retina/sprite-actions.svg' . AttachedFile::setTimeParam(RELEASE_DATE) . '#drag">
                </use>
            </svg>
            <label class="checkbox ' . $disabled . '">
                <input class="filterColumnJs" type="checkbox" name="listingFld" value="' . $key . '" ' . $checked . $disabled . ' onclick=reloadList()>
                ' . $label . ' <span></span></label>
        </li>';
    }
    $actionItemsData['columnButtons'] .= ' </ul>';
}

$this->includeTemplate('_partial/listing/action-buttons.php', $actionItemsData, false);
