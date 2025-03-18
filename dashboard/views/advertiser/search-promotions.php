<?php
$arr_flds = array(
    'listserial' => Labels::getLabel('LBL_#', $siteLangId),
    'promotion_name' => Labels::getLabel('LBL_PROMOTION_NAME', $siteLangId),
    'promotion_budget' => Labels::getLabel('LBL_Budget', $siteLangId),
    'promotion_duration' => Labels::getLabel('LBL_Duration', $siteLangId),
    'promotion_type' => Labels::getLabel('LBL_Type', $siteLangId),
    'promotion_date' => Labels::getLabel('LBL_SCHEDULED', $siteLangId),
    'promotion_end_date' => Labels::getLabel('LBL_PROMOTION', $siteLangId),
    'promotion_approved' => Labels::getLabel('LBL_Approved', $siteLangId),
    'promotion_active' => Labels::getLabel('LBL_Status', $siteLangId),
    'action' => '',
);
$tableClass = '';
if (0 < count($arrListing)) {
    $tableClass = "table-justified";
}
$tbl = new HtmlElement(
    'table',
    array('width' => '100%', 'class' => 'table ' . $tableClass, 'id' => 'promotions')
);

$th = $tbl->appendElement('thead')->appendElement('tr');
foreach ($arr_flds as $key => $val) {
    $th->appendElement('th', array(), $val);
}

$sr_no = ($page > 1) ? $recordCount - (($page - 1) * $pageSize) : $recordCount;
foreach ($arrListing as $sn => $row) {
    $tr = $tbl->appendElement('tr');
    $tr->setAttribute("id", $row['promotion_id']);

    foreach ($arr_flds as $key => $val) {
        $td = $tr->appendElement('td');
        switch ($key) {
            case 'listserial':
                $td->appendElement('plaintext', array(), $sr_no);
                break;
            case 'promotion_name':
                $td->appendElement('plaintext', array(), $row[$key], true);
                break;
            case 'promotion_budget':
                $td->appendElement('plaintext', array(), CommonHelper::displayMoneyFormat($row[$key]));
                break;
            case 'promotion_duration':
                $td->appendElement('plaintext', array(), $promotionBudgetDurationArr[$row[$key]], true);
                break;
            case 'promotion_type':
                $td->appendElement('plaintext', array(), $typeArr[$row[$key]], true);
                break;
            case 'promotion_approved':
                $td->appendElement('span', array('class' => 'badge badge-inline ' . $arrYesNoClassArr[$row[$key]]), $arrYesNo[$row[$key]], true);
                break;
            case 'promotion_active':
                $attributes = (applicationConstants::ACTIVE == $row['promotion_active']) ? "checked" : "";
                $attributes .= ' onclick="togglePromotionStatus(event,this)"';
                $str = HtmlHelper::configureSwitchForCheckboxStatic('', $row['promotion_id'], $attributes);
                $td->appendElement('plaintext', array(), $str, true);
                break;
            case 'promotion_end_date':
                $txt = '';
                if ($row[$key] < date("Y-m-d")) {
                    $txt = Labels::getLabel('LBL_Expired', $siteLangId);
                } else {
                    if ($row['promotion_start_date'] <= date("Y-m-d") && $row['promotion_end_date'] >= date("Y-m-d")  && $row['promotion_start_time'] <= date('H:i') && $row['promotion_end_time'] >= date('H:i')) {
                        $txt = Labels::getLabel('LBL_RUNNING', $siteLangId);
                        if (!$isPpcBalanceSufficent) {
                            $txt = Labels::getLabel('LBL_LOW_BALANCE', $siteLangId);
                        }
                    } else {
                        $txt = Labels::getLabel('LBL_SCHEDULED', $siteLangId);
                    }
                }
                $td->appendElement('plaintext', array(), $txt, true);
                break;
            case 'promotion_date':
                $str = '<span class="text-nowrap">' . Labels::getLabel('LBL_Start', $siteLangId) . ' : ' . FatDate::format($row['promotion_start_date']). '</span><br>';
                $str .= '<span class="text-nowrap">' . Labels::getLabel('LBL_End', $siteLangId) . ' : ' . FatDate::format($row['promotion_end_date']) . '</span><br>';
                $str .= '<span class="text-nowrap">' . Labels::getLabel('LBL_Promotion_Interval', $siteLangId) . ' : ' . date("H:i", strtotime($row['promotion_start_time'])) . ' - ' . date("H:i", strtotime($row['promotion_end_time'])) . '</span>';

                $td->appendElement('plaintext', array(), $str, true);
                break;
            case 'action':
                $ul = $td->appendElement("ul", array("class" => "actions"));
                if ($canEdit) {
                    $li = $ul->appendElement("li");
                    $li->appendElement(
                        'a',
                        array(
                            'href' => 'javascript:void(0)',
                            'class' => 'button small green', 'title' => Labels::getLabel('LBL_Edit', $siteLangId),
                            "onclick" => "promotionForm(" . $row['promotion_id'] . ")"
                        ),
                        '<svg class="svg" width="18" height="18">
                            <use
                                xlink:href="' . CONF_WEBROOT_URL . 'images/retina/sprite-actions.svg' . AttachedFile::setTimeParam(RELEASE_DATE) . '#edit">
                            </use>
                        </svg>',
                        true
                    );
                }

                $li = $ul->appendElement("li");
                $li->appendElement(
                    'a',
                    array(
                        'href' => UrlHelper::generateUrl('advertiser', 'analytics', array($row['promotion_id'])),
                        'class' => 'button small green', 'title' => Labels::getLabel('LBL_Analytics', $siteLangId)
                    ),
                    '<svg class="svg" width="18" height="18">
                    <use
                        xlink:href="' . CONF_WEBROOT_URL . 'images/retina/sprite-actions.svg' . AttachedFile::setTimeParam(RELEASE_DATE) . '#view">
                    </use>
                </svg>',
                    true
                );
                break;
            default:
                $td->appendElement('plaintext', array(), $row[$key], true);
                break;
        }
    }
    $sr_no--;
} ?>

<div class="js-scrollable table-wrap table-responsive">
    <?php
    echo $tbl->getHtml();
    if (count($arrListing) == 0) {
        $message = Labels::getLabel('LBL_No_Records_Found', $siteLangId);
        $this->includeTemplate('_partial/no-record-found.php', array('siteLangId' => $siteLangId, 'message' => $message));
    }
    $postedData['page'] = $page;
    echo FatUtility::createHiddenFormFromData($postedData, array(
        'name' => 'frmPromotionSearchPaging'
    ));
    $pagingArr = array('pageCount' => $pageCount, 'page' => $page, 'recordCount' => $recordCount);
    $this->includeTemplate('_partial/pagination.php', $pagingArr, false); ?>
</div>