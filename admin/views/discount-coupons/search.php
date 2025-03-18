<?php defined('SYSTEM_INIT') or die('Invalid Usage.');
$printData = false;
if (!isset($tbody)) {
    $printData = true;
    $tbody = new HtmlElement('tbody', ['class' => 'listingRecordJs']);
}

$serialNo = $page == 1 ? 0 : $pageSize * ($page - 1);
foreach ($arrListing as $sn => $row) {
    $serialNo++;
    $cls = (($serialNo % 2) == 0) ? 'even' : 'odd';
    $tr = $tbody->appendElement('tr', ['class' => $cls, 'data-row' => $serialNo, 'id' => $row['coupon_id']]);

    $isExpired = ($row['coupon_end_date'] != "0000-00-00" && strtotime($row['coupon_end_date']) < strtotime(date('Y-m-d'))) ? true : false;
    foreach ($fields as $key => $val) {
        $tdAttr = ('action' == $key) ? ['class' => 'align-right'] : (('select_all' == $key) ? ['class' => 'col-check'] : []);
        $td = $tr->appendElement('td', $tdAttr);
        switch ($key) {
            case 'select_all':
                $disabled = ($isExpired) ? 'disabled' : '';
                $td->appendElement('plaintext', $tdAttr, '<label class="checkbox"><input class="selectItemJs ' . $disabled . '" type="checkbox" name="record_ids[]" ' . $disabled . ' value=' . $row['coupon_id'] . '><i class="input-helper"></i></label>', true);
                break;
            case 'listSerial':
                $td->appendElement('plaintext', $tdAttr, $serialNo);
                break;
            case 'coupon_title':
                $td->appendElement('plaintext', $tdAttr, $row[$key], true);
                break;
            case 'coupon_code':
                $code = '<div clss="d-flex">' . $row[$key] . '</div>';
                $td->appendElement('plaintext', $tdAttr, $code, true);
                break;
            case 'coupon_type':
                $statusHtm = DiscountCoupons::getTypeHtml($siteLangId, $row[$key]);
                $td->appendElement('plaintext', $tdAttr, $statusHtm, true);
                break;
            case 'coupon_discount_value':
                $discountValue = ($row['coupon_discount_in_percent'] == ApplicationConstants::PERCENTAGE) ? $row[$key] . ' %' : CommonHelper::displayMoneyFormat($row[$key]);
                $td->appendElement('plaintext', $tdAttr, $discountValue);
                break;
            case 'coupon_start_date':
                $dispDate = HtmlHelper::formatDateTime($row[$key]);
                $td->appendElement('plaintext', $tdAttr, $dispDate, true);
                break;
            case 'coupon_end_date':
                $dispDate = HtmlHelper::formatDateTime($row[$key]);
                $td->appendElement('plaintext', $tdAttr, $dispDate, true);
                break;
            case 'coupon_alive':
                $code = HtmlHelper::getStatusHtml(HtmlHelper::SUCCESS, Labels::getLabel("LBL_ACTIVE", $siteLangId));
                if ($isExpired) {
                    $code = HtmlHelper::getStatusHtml(HtmlHelper::DANGER, Labels::getLabel("LBL_EXPIRED", $siteLangId));
                }
                $td->appendElement('plaintext', $tdAttr, $code, true);
                break;
            case 'coupon_active':
                if ($isExpired) {
                    $htm = HtmlHelper::addStatusBtnHtml($canEdit, $row['coupon_id'], $row[$key], true, Labels::getLabel("LBL_EXPIRED", $siteLangId));
                } else {
                    $htm = HtmlHelper::addStatusBtnHtml($canEdit, $row['coupon_id'], $row[$key]);
                }
                $td->appendElement('plaintext', $tdAttr, $htm, true);
                break;
            case 'action':
                $data = [
                    'siteLangId' => $siteLangId,
                    'recordId' => $row['coupon_id']
                ];

                if ($canEdit) {
                    $href = UrlHelper::generateUrl('DiscountCoupons', 'links', [$row['coupon_id']]);
                    $onclick = '';
                    if ($row['coupon_type'] == DiscountCoupons::TYPE_SELLER_PACKAGE) {
                        $href = 'javascript:void(0);';
                        $onclick = 'couponLinkPlanForm(' . $row['coupon_id'] . ')';
                    }

                    $data['editButton'] = [];
                    $data['otherButtons'] = [
                        [
                            'attr' => [
                                'href' => $href,
                                'onclick' => $onclick,
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
                if ($canView) {
                    $data['otherButtons'][] = [
                        'attr' => [
                            'href' => 'javascript:void(0);',
                            'onclick' => 'couponHistory(' . $row['coupon_id'] . ')',
                            'title' => Labels::getLabel('LBL_HISTORY', $siteLangId)
                        ],
                        'label' => '<svg class="svg" width="18" height="18">
                                        <use
                                            xlink:href="' . CONF_WEBROOT_URL . 'images/retina/sprite-actions.svg' . AttachedFile::setTimeParam(RELEASE_DATE) . '#history">
                                        </use>
                                    </svg>'
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
}

include(CONF_THEME_PATH . '_partial/listing/no-record-found.php');

if ($printData) {
    echo $tbody->getHtml();
}
