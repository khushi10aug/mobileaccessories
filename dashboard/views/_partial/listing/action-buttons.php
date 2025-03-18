<?php defined('SYSTEM_INIT') or die('Invalid Usage');

$canEdit = isset($canEdit) ? $canEdit : false;

$ul = new HtmlElement("ul", array());
if (isset($htmlContent) && !empty($htmlContent)) {
    $ul->appendElement('li', [], $htmlContent, true);
}

$msg = isset($msg) ? $msg : '';
if (isset($statusButtons) && true === $statusButtons && $canEdit) {
    $li = $ul->appendElement('li', ['title' => Labels::getLabel('BTN_MARK_AS_ACTIVE', $siteLangId), 'data-bs-toggle' => 'tooltip', 'data-placement' => 'top']);

    $li->appendElement(
        'a',
        [
            'href' => 'javascript:void(0)',
            'class' => 'btn btn-outline-gray btn-icon formActionBtn-js disabled',
            'onclick' => "toggleBulkStatues(1, '" . $msg . "')"
        ],
        '<svg class="svg btn-icon-start" width="18" height="18">
            <use
                xlink:href="' . CONF_WEBROOT_URL . 'images/retina/sprite-actions.svg' . AttachedFile::setTimeParam(RELEASE_DATE) . '#active">
            </use>
        </svg><span class="btn-txt">' . Labels::getLabel('BTN_ACTIVATE', $siteLangId) . '</span>',
        true
    );

    $li = $ul->appendElement('li', ['title' => Labels::getLabel('BTN_MARK_AS_IN-ACTIVE', $siteLangId), 'data-bs-toggle' => 'tooltip', 'data-placement' => 'top']);
    $li->appendElement(
        'a',
        [
            'href' => 'javascript:void(0)',
            'class' => 'btn btn-outline-gray btn-icon formActionBtn-js disabled',
            'onclick' => "toggleBulkStatues(0, '" . $msg . "')"
        ],
        '<svg class="svg btn-icon-start" width="18" height="18">
            <use
                xlink:href="' . CONF_WEBROOT_URL . 'images/retina/sprite-actions.svg' . AttachedFile::setTimeParam(RELEASE_DATE) . '#in-active">
            </use>
        </svg><span class="btn-txt">' . Labels::getLabel('BTN_DEACTIVATE', $siteLangId) . '</span>',
        true
    );
}

if (isset($deleteButton) && true === $deleteButton && $canEdit) {
    $li = $ul->appendElement('li', ['title' => Labels::getLabel('BTN_DELETE_RECORDS', $siteLangId), 'data-bs-toggle' => 'tooltip', 'data-placement' => 'top']);
    $li->appendElement(
        'a',
        [
            'href' => 'javascript:void(0)',
            'class' => 'btn btn-outline-gray btn-icon formActionBtn-js disabled',
            'onclick' => "deleteSelected()"
        ],
        '<svg class="svg btn-icon-start" width="18" height="18">
            <use
                xlink:href="' . CONF_WEBROOT_URL . 'images/retina/sprite-actions.svg' . AttachedFile::setTimeParam(RELEASE_DATE) . '#delete">
            </use>
        </svg><span class="btn-txt">' . Labels::getLabel('BTN_DELETE', $siteLangId) . '</span>',
        true
    );
}

if (isset($listTopButtons) && is_array($listTopButtons)) {
    foreach ($listTopButtons as $attr) {
        $liAttr = [];
        if (isset($attr['attr']['title'])) {
            $liAttr = ['title' => $attr['attr']['title'], 'data-bs-toggle' => 'tooltip', 'data-placement' => 'top'];
            unset($attr['attr']['title']);
        }
        $li = $ul->appendElement('li', $liAttr);
        $li->appendElement('a', $attr['attr'], (string) html_entity_decode($attr['label'], ENT_QUOTES, 'utf-8'), true);
    }
}

if (!empty($columnButtons)) {
    $li = $ul->appendElement('li', ['class' => 'dropdown custom-drag-drop']);
    $li->appendElement(
        'a',
        [
            'href' => 'javascript:void(0)',
            'class' => 'btn btn-icon btn-link',
            'title' => Labels::getLabel('LBL_COLUMNS', $siteLangId),
            'data-bs-toggle' => 'dropdown',
            'aria-expanded' => false
        ],
        '<svg class="svg" width="18" height="18">
            <use
                xlink:href="' . CONF_WEBROOT_URL . 'images/retina/sprite-actions.svg' . AttachedFile::setTimeParam(RELEASE_DATE) . '#columns">
            </use>
        </svg>' . Labels::getLabel('LBL_COLUMNS', $siteLangId),
        true
    );

    $li->appendElement('div', ['class' => 'dropdown-menu dropdown-menu-right dropdown-menu-anim dropdown-menu-fit dropdown-menu-anim scroll scroll-y'], $columnButtons, true);
}
if (!empty($htmlContent) || !empty($statusButtons) || !empty($deleteButton) || !empty($listTopButtons) || !empty($columnButtons)) {
    echo '<div class="card-toolbar">';
    echo $ul->getHtml();
    echo '</div>';
}