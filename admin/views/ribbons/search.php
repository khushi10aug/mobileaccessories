<?php defined('SYSTEM_INIT') or die('Invalid Usage.');
$printData = false;
if (!isset($tbody)) {
    $printData = true;
    $tbody = new HtmlElement('tbody', ['class' => 'listingRecordJs']);
}

$serialNo = ($page - 1) * $pageSize + 1;
foreach ($arrListing as $sn => $row) {
    $cls = (($serialNo % 2) == 0) ? 'even' : 'odd';
    $tr = $tbody->appendElement('tr', ['class' => $cls, 'data-row' => $serialNo, 'id' => $row[Badge::DB_TBL_PREFIX . 'id']]);
    foreach ($fields as $key => $val) {
        $tdAttr = ('action' == $key) ? ['class' => 'align-right'] : (('select_all' == $key) ? ['class' => 'col-check'] : []);
        $td = $tr->appendElement('td', $tdAttr);
        switch ($key) {
            case 'select_all':
                $td->appendElement('plaintext', $tdAttr, '<label class="checkbox"><input class="selectItemJs" type="checkbox" name="badge_ids[]" value=' . $row[Badge::DB_TBL_PREFIX . 'id'] . '><i class="input-helper"></i></label>', true);
                break;
            case 'listSerial':
                $td->appendElement('plaintext', $tdAttr, $serialNo, true);
                break;
            case Badge::DB_TBL_PREFIX . 'name':
                $td->appendElement('plaintext', $tdAttr, $row[$key], true);
                break;
            case Badge::DB_TBL_PREFIX . 'shape_type':
                $ribbon = $this->includeTemplate('_partial/ribbon-ui.php', ['ribbRow' => $row], false, true);
                $html = '<div class="badge-wrap">' . $ribbon . '</div>';
                $td->appendElement('plaintext', [], $html, true);
                break;

            case 'badge_added_on':
                $td->appendElement('plaintext', array(), HtmlHelper::formatDateTime($row[$key], true, true, FatApp::getConfig('CONF_TIMEZONE', FatUtility::VAR_STRING, date_default_timezone_get())), true);
                break;
            case Badge::DB_TBL_PREFIX . 'active':
                $htm = HtmlHelper::addStatusBtnHtml($canEdit, $row[Badge::DB_TBL_PREFIX . 'id'], $row[$key]);
                $td->appendElement('plaintext', $tdAttr, $htm, true);
                break;
            case 'action':
                $data = [
                    'siteLangId' => $siteLangId,
                    'recordId' => $row[Badge::DB_TBL_PREFIX . 'id']
                ];

                if ($canEdit) {
                    $data['editButton'] = [
                        'onclick' => 'editRecord(' . $row[Badge::DB_TBL_PREFIX . 'id'] . ', true)'
                    ];
                    $data['deleteButton'] = [];
                    $data['otherButtons'] = [
                        [
                            'attr' => [
                                'href' => UrlHelper::generateUrl('BadgeLinkConditions', 'list', [$row[Badge::DB_TBL_PREFIX . 'id']]),
                                'title' => Labels::getLabel('LBL_LINKS', $siteLangId)
                            ],
                            'label' => '<svg class="svg" width="18" height="18">
                                            <use
                                                xlink:href="' . CONF_WEBROOT_URL . 'images/retina/sprite-actions.svg' . AttachedFile::setTimeParam(RELEASE_DATE) . '#linking">
                                            </use>
                                        </svg>'
                        ]
                    ];
                }
                $actionItems = $this->includeTemplate('_partial/listing/listing-action-buttons.php', $data, false, true);
                $td->appendElement('plaintext', $tdAttr, $actionItems, true);
                break;
            default:
                $td->appendElement('plaintext', [], $row[$key], true);
                break;
        }
    }
    $serialNo++;
}

include(CONF_THEME_PATH . '_partial/listing/no-record-found.php');

if ($printData) {
    echo $tbody->getHtml();
}
