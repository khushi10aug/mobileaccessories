<?php defined('SYSTEM_INIT') or die('Invalid Usage.');
$printData = false;
$page = $page ?? 0;
$pageSize = $pageSize ?? 0;
if (!isset($tbody)) {
    $printData = true;
    $tbody = new HtmlElement('tbody', ['class' => 'listingRecordJs']);
}

$serialNo = ($page - 1) * $pageSize + 1;
foreach ($arrListing as $sn => $row) {
    $cls = (($serialNo % 2) == 0) ? 'even' : 'odd';
    $tr = $tbody->appendElement('tr', ['class' => $cls, 'data-row' => $serialNo, 'id' => $row['option_id']]);

    foreach ($fields as $key => $val) {
        $tdAttr = ('action' == $key) ? ['class' => 'align-right'] : (('select_all' == $key) ? ['class' => 'col-check'] : []);
        $td = $tr->appendElement('td', $tdAttr);
        switch ($key) {
            case 'select_all':
                $td->appendElement('plaintext', $tdAttr, '<label class="checkbox"><input class="selectItemJs" type="checkbox" name="option_ids[]" value=' . $row['option_id'] . '><i class="input-helper"></i></label>', true);
                break;
            case 'listSerial':
                $td->appendElement('plaintext', $tdAttr, $serialNo);
                break;
            case 'option_name':
                if ($row['option_name'] != $row['option_identifier']) {
                    $td->appendElement('plaintext', $tdAttr, $row[$key], true);
                    $td->appendElement('br', $tdAttr);
                    $td->appendElement('plaintext', $tdAttr, '(' . $row['option_identifier'] . ')', true);
                } else {
                    $td->appendElement('plaintext', $tdAttr, $row[$key], true);
                }
                break;
            case 'option_is_separate_images':
            case 'option_display_in_filter':
            case 'option_is_color':
                $td->appendElement('plaintext', $tdAttr, applicationConstants::getYesNoArr($siteLangId)[$row[$key]], true);
                break;
            case 'user_name':
                if ($row['user_name'] != '') {
                    $td->appendElement('plaintext', $tdAttr, $row['user_name'], true);
                } else {
                    $td->appendElement('plaintext', $tdAttr, Labels::getLabel('LBL_Admin'), true);
                }
                break;
            case 'action':
                $data = [
                    'siteLangId' => $siteLangId,
                    'recordId' => $row['option_id']
                ];

                if ($canEdit) {
                    $data['editButton'] = [];
                    $data['deleteButton'] = [];
                    $data['otherButtons'] = [
                        [
                            'attr' => [
                                'href' => UrlHelper::generateUrl('OptionValues', 'list', array($row[Option::DB_TBL_PREFIX . 'id'])),
                                'title' => Labels::getLabel('LBL_OPTION_VALUES', $siteLangId)
                            ],
                            'label' => '<svg class="svg" width="18" height="18">
                                            <use
                                                xlink:href="' . CONF_WEBROOT_URL . 'images/retina/sprite-actions.svg' . AttachedFile::setTimeParam(RELEASE_DATE) . '#list">
                                            </use>
                                        </svg>'
                        ]
                    ];
                }
                $actionItems = $this->includeTemplate('_partial/listing/listing-action-buttons.php', $data, false, true);
                $td->appendElement('plaintext', $tdAttr, $actionItems, true);
                break;
            default:
                $td->appendElement('plaintext', $tdAttr, $row[$key], true);
                break;
        }
    }
    $serialNo++;
}

include(CONF_THEME_PATH . '_partial/listing/no-record-found.php');

if ($printData) {
    echo $tbody->getHtml();
}
