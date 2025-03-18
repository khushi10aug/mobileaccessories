<?php
defined('SYSTEM_INIT') or die('Invalid Usage');

$div = new HtmlElement('div', array('class' => 'd-flex'));
if (isset($htmlContent) && $htmlContent != '') {
    $div->appendElement('div', ["class" => 'dropdown custom-drag-drop me-2'], $htmlContent, true);
}
$btnGrp = $div->appendElement('div', array("class" => "btn-group"));
$msg = isset($msg) ? $msg : '';
if (isset($statusButtons) && true === $statusButtons) {
    $div->appendElement('a', array('href' => 'javascript:void(0)', 'class' => 'btn btn-outline-brand btn-sm formActionBtn-js formActions-css', 'title' => Labels::getLabel('LBL_Publish', $siteLangId), "onclick" => "toggleBulkStatues(1, '" . $msg . "')"), '<svg class="svg" width="20" height="20">
    <use xlink:href="' . CONF_WEBROOT_URL . 'images/retina/sprite.svg' . AttachedFile::setTimeParam(RELEASE_DATE) . '#eye">
    </use>
    </svg>', true);

    $div->appendElement('a', array('href' => 'javascript:void(0)', 'class' => 'btn btn-outline-brand btn-sm formActionBtn-js formActions-css', 'title' => Labels::getLabel('LBL_Unpublish', $siteLangId), "onclick" => "toggleBulkStatues(0, '" . $msg . "')"), '<svg class="svg" width="20" height="20">
    <use xlink:href="' . CONF_WEBROOT_URL . 'images/retina/sprite.svg' . AttachedFile::setTimeParam(RELEASE_DATE) . '#eye-slash">
    </use>
    </svg>', true);
}

if (isset($deleteButton) && true === $deleteButton) {
    $div->appendElement('a', array('href' => 'javascript:void(0)', 'class' => 'btn btn-outline-brand btn-sm formActionBtn-js formActions-css', 'title' => Labels::getLabel('LBL_Delete', $siteLangId), "onclick" => "deleteSelected()"), '<svg class="svg" width="20" height="20">
    <use xlink:href="' . CONF_WEBROOT_URL . 'images/retina/sprite.svg' . AttachedFile::setTimeParam(RELEASE_DATE) . '#trash">
    </use>
    </svg>', true);
}

if (isset($otherButtons) && is_array($otherButtons)) {
    foreach ($otherButtons as $attr) {
        $class = isset($attr['attr']['class']) ? $attr['attr']['class'] : '';
        $attr['attr']['class'] = 'btn btn-outline-brand btn-sm ' . $class;
        $btnGrp->appendElement('a', str_replace('&#039;', "'", $attr['attr']), (string) $attr['label'], true);
    }
}
echo $div->getHtml();
?>
<script>
    $(function() {
        $('.dropdown-menu').on('click', function(e) {
            e.stopPropagation();
        });
    });
</script>