<?php defined('SYSTEM_INIT') or die('Invalid Usage.');
$printData = false;
if (!isset($tbody)) {
    $printData = true;
    $tbody = new HtmlElement('tbody', ['class' => 'listingRecordJs']);
}

foreach ($arrListing as $sn => $row) {
    $serialNo = $sn + 1;
    $cls = (($serialNo % 2) == 0) ? 'even' : 'odd';
    $tr = $tbody->appendElement('tr', ['class' => $cls, 'data-row' => $serialNo]);
    $tr->setAttribute("id", $row['currency_id']);

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
                $td->appendElement('plaintext', $tdAttr, '<label class="checkbox"><input class="selectItemJs" type="checkbox" name="currency_ids[]" value=' . $row['currency_id'] . '><i class="input-helper"></i></label>', true);
                break;
            case 'currency_name':
                $default = HtmlHelper::getStatusHtml(HtmlHelper::INFO, Labels::getLabel('LBL_DEFAULT', $siteLangId));
                $name = $row[$key];
                $name .= ($row['currency_id'] == FatApp::getConfig('CONF_CURRENCY', FatUtility::VAR_INT, 0) ? ' ' . $default : '');
                $td->appendElement('plaintext', $tdAttr, $name, true);
                break;
            case 'currency_symbol_left':
                $td->appendElement('plaintext', $tdAttr, CommonHelper::displayNotApplicable($siteLangId, $row[$key]), true);
                break;
            case 'currency_symbol_right':
                $td->appendElement('plaintext', $tdAttr, CommonHelper::displayNotApplicable($siteLangId, $row[$key]), true);
                break;
            case 'currency_active':
                $statusAct = ($canEdit) ? 'updateStatus(event, this, ' . $row['currency_id'] . ', ' . ((int) !$row[$key]) . ')' : 'return false;';
                $statusClass = ($canEdit) ? '' : 'disabled';
                if ($row['currency_id'] == FatApp::getConfig('CONF_CURRENCY', FatUtility::VAR_INT, 0)) {
                    $statusClass = 'disabled';
                }
                $checked = applicationConstants::ACTIVE == $row[$key] ? 'checked' : '';

                $htm = '<span class="switch switch-sm switch-icon">
                                    <label>
                                        <input type="checkbox" data-old-status="' . $row[$key] . '" value="' . $row['currency_id'] . '" ' . $checked . ' onclick="' . $statusAct . '" ' . $statusClass . '>
                                        <span class="input-helper"></span>
                                    </label>
                                </span>';
                $td->appendElement('plaintext', $tdAttr, $htm, true);
                break;
            case 'currency_code':
                $td->appendElement('plaintext', $tdAttr, $row[$key], true);
                break;
            case 'action':
                $data = [
                    'siteLangId' => $siteLangId,
                    'recordId' => $row['currency_id']
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

include(CONF_THEME_PATH . '_partial/listing/no-record-found.php');


if ($printData) {
    echo $tbody->getHtml();
}
