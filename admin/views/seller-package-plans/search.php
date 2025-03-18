<?php defined('SYSTEM_INIT') or die('Invalid Usage.');
$printData = false;
if (!isset($tbody)) {
    $printData = true;
    $tbody = new HtmlElement('tbody', ['class' => 'listingRecordJs']);
}

$serialNo = ($page - 1) * $pageSize + 1;

foreach ($arrListing as $sn => $row) {
    $cls = (($serialNo % 2) == 0) ? 'even' : 'odd';
    $tr = $tbody->appendElement('tr', ['class' => $cls, 'data-row' => $serialNo]);
    if ($row['spplan_active'] != applicationConstants::ACTIVE) {
        $tr->setAttribute("class", " nodrag nodrop");
    }

    if ($row['spplan_active'] == applicationConstants::ACTIVE) {
        $tr->setAttribute("id", $row['spplan_id']);
    }

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
                $td->appendElement('plaintext', $tdAttr, '<label class="checkbox"><input class="selectItemJs" type="checkbox" name="record_ids[]" value=' . $row['spplan_id'] . '><i class="input-helper"></i></label>', true);
                break;
            case 'listSerial':
                $td->appendElement('plaintext', $tdAttr, $serialNo);
                break;
            case 'spplan_price':
                $td->appendElement('plaintext', array(), CommonHelper::displayMoneyFormat($row[$key]));
                break;
            case 'spplan_interval':
                $td->appendElement('plaintext', array(), SellerPackagePlans::getPlanPeriod($row), true);
                break;
            case 'spplan_active':
                $statusAct = ($canEdit) ? 'updateStatus(event, this, ' . $row['spplan_id'] . ', ' . ((int) !$row[$key]) . ')' : 'return false;';
                $statusClass = ($canEdit) ? '' : 'disabled';
                $checked = applicationConstants::ACTIVE == $row[$key] ? 'checked' : '';

                $htm = '<span class="switch switch-sm switch-icon">
                                    <label>
                                        <input type="checkbox" data-old-status="' . $row[$key] . '" value="' . $row['spplan_id'] . '" ' . $checked . ' onclick="' . $statusAct . '" ' . $statusClass . '>
                                        <span class="input-helper"></span>
                                    </label>
                                </span>';
                $td->appendElement('plaintext', $tdAttr, $htm, true);
                break;
            case 'action':
                $data = [
                    'siteLangId' => $siteLangId,
                    'recordId' => $row['spplan_id']
                ];

                if ($canEdit) {
                    $data['editButton'] = [
                        'onclick' => 'editPlanRecord(' . $row['spplan_spackage_id'] . ',' . $row['spplan_id'] . ')'
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
