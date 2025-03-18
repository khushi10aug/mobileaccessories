<?php defined('SYSTEM_INIT') or die('Invalid Usage.');
$printData = false;
if (!isset($tbody)) {
    $printData = true;
    $tbody = new HtmlElement('tbody', ['class' => 'listingRecordJs']);
}

$serialNo = 1;
foreach ($arrListing as $sn => $row) {
    $cls = (($serialNo % 2) == 0) ? 'even' : 'odd';
    $tr = $tbody->appendElement('tr', ['class' => $cls, 'data-row' => $serialNo]);
    $tr->setAttribute("id", $row['orderstatus_id']);
    foreach ($fields as $key => $val) {
        $tdAttr = ('action' == $key) ? ['class' => 'align-right'] : (('select_all' == $key) ? ['class' => 'col-check'] : []);        
        $td = $tr->appendElement('td', $tdAttr);
        switch ($key) {
            case 'dragdrop':
                $div = $td->appendElement('div', ['class' => 'handleJs']);
                $div->appendElement('plaintext', $tdAttr, '<svg class="svg" width="18" height="18">
                                                            <use
                                                                xlink:href="' . CONF_WEBROOT_URL . 'images/retina/sprite-actions.svg' . AttachedFile::setTimeParam(RELEASE_DATE) . '#drag">
                                                            </use>
                                                        </svg>', true);
                break;
            case 'select_all':
                $td->appendElement('plaintext', $tdAttr, '<label class="checkbox"><input class="selectItemJs" type="checkbox" name="orderstatus_ids[]" value=' . $row['orderstatus_id'] . '><i class="input-helper"></i></label>', true);
                break;
            case 'orderstatus_is_active':
                $htm = HtmlHelper::addStatusBtnHtml($canEdit, $row['orderstatus_id'], $row[$key]);
                $td->appendElement('plaintext', $tdAttr, $htm, true);
                break;
            case 'action':
                $data = [
                    'siteLangId' => $siteLangId,
                    'recordId' => $row['orderstatus_id']
                ];

                if ($canEdit) {
                    $data['editButton'] = [];
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

include (CONF_THEME_PATH . '_partial/listing/no-record-found.php');

if ($printData) {
    echo $tbody->getHtml();
}