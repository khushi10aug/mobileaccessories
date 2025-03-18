<?php defined('SYSTEM_INIT') or die('Invalid Usage.');
$printData = false;
if (!isset($tbody)) {
    $printData = true;
    $tbody = new HtmlElement('tbody', ['class' => 'listingRecordJs']);
}

$tableId = "faqCategoryJs";

$serialNo = $page == 1 ? 0 : $pageSize * ($page - 1);
foreach ($arrListing as $sn => $row) {
    $serialNo++;
    $cls = (($serialNo % 2) == 0) ? 'even' : 'odd';
    $tr = $tbody->appendElement('tr', ['class' => $cls, 'data-row' => $serialNo, 'id' => $row['faqcat_id']]);

    if ($row['faqcat_active'] == applicationConstants::ACTIVE) {
        $tr->setAttribute("id", $row['faqcat_id']);
    }

    if ($row['faqcat_active'] != applicationConstants::ACTIVE) {
        $tr->setAttribute("class", "nodrag nodrop");
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
                $td->appendElement('plaintext', $tdAttr, '<label class="checkbox"><input class="selectItemJs" type="checkbox" name="faqcat_ids[]" value=' . $row['faqcat_id'] . '><i class="input-helper"></i></label>', true);
                break;
            case 'listSerial':
                $td->appendElement('plaintext', $tdAttr, $serialNo);
                break;
            case 'faqcat_name':
                $td->appendElement('plaintext', $tdAttr, $row[$key], true);
                break;
            case 'faqcat_active':
                $statusAct = ($canEdit) ? 'updateStatus(event, this, ' . $row['faqcat_id'] . ', ' . ((int) !$row[$key]) . ')' : 'return false;';
                $statusClass = ($canEdit) ? '' : 'disabled';
                $checked = applicationConstants::ACTIVE == $row[$key] ? 'checked' : '';

                $htm = '<span class="switch switch-sm switch-icon">
                    <label>
                        <input type="checkbox" data-old-status="' . $row[$key] . '" value="' . $row['faqcat_id'] . '" ' . $checked . ' onclick="' . $statusAct . '" ' . $statusClass . '>
                        <span class="input-helper"></span>
                    </label>
                </span>';
                $td->appendElement('plaintext', $tdAttr, $htm, true);


                break;
            case 'action':

                $data = [
                    'siteLangId' => $siteLangId,
                    'recordId' => $row['faqcat_id']
                ];

                if ($canEdit) {
                    $data['editButton'] = [];
                    $data['deleteButton'] = [];
                }
                $data['otherButtons'] = [
                    [
                        'attr' => [
                            'href' =>  'javascript:void(0);',
                            'onclick' => 'redirectToList('.$row['faqcat_id'].')' ,
                            'title' => Labels::getLabel('LBL_FAQ_Listing', $siteLangId),
                        ],
                        'label' => '<i class="icn">
                            <svg class="svg" width="18" height="18">
                                <use xlink:href="' . CONF_WEBROOT_URL . 'images/retina/sprite-actions.svg' . AttachedFile::setTimeParam(RELEASE_DATE) . '#list">
                                </use>
                            </svg>
                        </i>'
                    ]
                ];
                $actionItems = $this->includeTemplate('_partial/listing/listing-action-buttons.php', $data, false, true);
                $td->appendElement('plaintext', $tdAttr, $actionItems, true);
                
                break;
            default:
                $td->appendElement('plaintext', $tdAttr, $row[$key], true);
                break;
        }
    }
}
include (CONF_THEME_PATH . '_partial/listing/no-record-found.php');

if ($printData) {
    echo $tbody->getHtml();
}
?>
