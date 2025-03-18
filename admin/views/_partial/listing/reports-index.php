<?php defined('SYSTEM_INIT') or die('Invalid Usage.');
$actionItemsData = array_merge($actionItemsData, ['otherButtons' =>  [[
    'attr' => [
        'href' => 'javascript:void(0)',
        'class' => 'btn btn-icon btn-link',
        'onclick' => 'exportForm()',
        'title' => Labels::getLabel('LBL_Export', $siteLangId)
    ],
    'label' => '<svg class="svg" width="18" height="18">
    <use xlink:href="' . CONF_WEBROOT_URL . 'images/retina/sprite-actions.svg' . AttachedFile::setTimeParam(RELEASE_DATE) . '#export">
    </use>
</svg>' . Labels::getLabel('LBL_Export', $siteLangId)
]]]);
require_once(CONF_THEME_PATH . '_partial/listing/index.php');
