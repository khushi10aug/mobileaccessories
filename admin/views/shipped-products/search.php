<?php defined('SYSTEM_INIT') or die('Invalid Usage.');
$printData = false;
if (!isset($tbody)) {
    $printData = true;
    $tbody = new HtmlElement('tbody', ['class' => 'listingRecordJs']);
}

$serialNo = ($page - 1) * $pageSize + 1;

foreach ($arrListing as $sn => $row) {
    $cls = (($serialNo % 2) == 0) ? 'even' : 'odd';
    $tr = $tbody->appendElement('tr', ['class' => $cls, 'data-row' => $serialNo, 'id' => $row['shippro_product_id']]);
    foreach ($fields as $key => $val) {
        $tdAttr = ('action' == $key) ? ['class' => 'align-right'] : [];
        $td = $tr->appendElement('td', $tdAttr);
        switch ($key) {
            case 'listSerial':
                $td->appendElement('plaintext', $tdAttr, $serialNo);
                break;
            case 'total_seller_ship':
                if ($row[$key] > 0) {
                    $td->appendElement('a', array('href' => 'javascript:void(0)', 'class'=>"link-text", 'onclick' => "viewSellerShip(" . $row['shippro_product_id'] . ")"), $row[$key], true);
                } else {
                    $td->appendElement('plaintext', array(), $row[$key], true);
                }
                break;
            case 'total_admin_seller_ship':
                if ($row[$key] > 0) {
                    $td->appendElement('a', array('href' => 'javascript:void(0)', 'class'=>"link-text", 'onclick' => "viewAdminSellerShip(" . $row['shippro_product_id'] . ")"), $row[$key], true);
                } else {
                    $td->appendElement('plaintext', array(), $row[$key], true);
                }
                break;
            case 'action':
                $data = [
                    'siteLangId' => $siteLangId,
                    'recordId' => $row['shippro_product_id']
                ];

                if ($canEdit) {
                    $data['editButton'] = false;
                }
                $data['otherButtons'] = [
                    [
                        'attr' => [
                            'href' => 'javascript:void(0)',
                            'onclick' => 'editRecord(' . $row['shippro_product_id'] . ',' . $row['shippro_shipprofile_id'] . ')',
                            'title' => Labels::getLabel('LBL_Update_Shipping_Profile', $siteLangId)
                        ],
                        'label' => '<i class="icn">
                                        <svg class="svg" width="18" height="18">
                                            <use
                                                xlink:href="' . CONF_WEBROOT_URL . 'images/retina/sprite-actions.svg' . AttachedFile::setTimeParam(RELEASE_DATE) . '#form">
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

include(CONF_THEME_PATH . '_partial/listing/no-record-found.php');

if ($printData) {
    echo $tbody->getHtml();
}
