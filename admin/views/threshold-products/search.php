<?php
defined('SYSTEM_INIT') or die('Invalid Usage.');
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
    $tr = $tbody->appendElement('tr', ['class' => $cls, 'data-row' => $serialNo, 'id' => $row['selprod_id']]);
    foreach ($fields as $key => $val) {
        $tdAttr = ('action' == $key) ? ['class' => 'align-right'] : [];
        $td = $tr->appendElement('td', $tdAttr);
        switch ($key) {
            case 'listSerial':
                $td->appendElement('plaintext', $tdAttr, $serialNo);
                break;
            case 'product_name':
                $str = $this->includeTemplate('_partial/product/product-info-card.php', ['selProdId' => $row['selprod_id'], 'siteLangId' => $siteLangId, 'sellerName' => $row['credential_username']], false, true);
                $td->appendElement('plaintext', array(), $str, true);
                break;
            case 'earch_sent_on':
                $val = !empty($row[$key]) ? $row[$key] : Labels::getLabel('LBL_N/A', $siteLangId);
                $td->appendElement('plaintext', array(), $val);
                break;
            case 'action':
                $data = [
                    'siteLangId' => $siteLangId,
                    'recordId' => $row['selprod_id']
                ];

                $data['otherButtons'] = [
                    [
                        'attr' => [
                            'href' => 'javascript:void(0)',
                            'onclick' => 'sendMail(' . $row['selprod_user_id'] . ',' . $row['selprod_id'] . ')',
                            'title' => Labels::getLabel('LBL_EMAIL_TO_SELLER', $siteLangId)
                        ],
                        'label' => '<i class="icn">
                                            <svg class="svg" width="18" height="18">
                                                <use xlink:href="' . CONF_WEBROOT_URL . 'images/retina/sprite-actions.svg' . AttachedFile::setTimeParam(RELEASE_DATE) . '#send-email">
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
    $serialNo++;
}

include (CONF_THEME_PATH . '_partial/listing/no-record-found.php');

if ($printData) {
    echo $tbody->getHtml();
}