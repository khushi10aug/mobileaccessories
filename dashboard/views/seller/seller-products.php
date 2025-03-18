<?php defined('SYSTEM_INIT') or die('Invalid Usage.');
$currency_id = FatApp::getConfig('CONF_CURRENCY', FatUtility::VAR_INT, 1);
$selectedCurrency = CommonHelper::getCurrencyId();
$arr_flds = array(
    'select_all' => '',
    'listserial' => Labels::getLabel('LBL_#', $siteLangId),
    'name' => Labels::getLabel('LBL_Name', $siteLangId),
    'selprod_price' => Labels::getLabel('LBL_Price', $siteLangId),
    'selprod_stock' => Labels::getLabel('LBL_Quantity', $siteLangId),
    'selprod_available_from' => Labels::getLabel('LBL_Available_From', $siteLangId),
    'selprod_active' => Labels::getLabel('LBL_Status', $siteLangId),
    'action' => ''
);

if (1 > count($arrListing) || !$canEdit) {
    unset($arr_flds['select_all'], $arr_flds['selprod_active'], $arr_flds['action']);
}

$tableClass = (0 < count($arrListing)) ? "table-justified" : ''; ?>

<div class="js-scrollable table-wrap table-responsive">
    <?php
    $tbl = new HtmlElement('table', array('width' => '100%', 'class' => 'table ' . $tableClass));
    $th = $tbl->appendElement('thead')->appendElement('tr', array('class' => ''));
    foreach ($arr_flds as $key => $val) {
        if ('select_all' == $key) {
            if (count($arrListing) > 0) {
                $th->appendElement('th')->appendElement('plaintext', array(), '<label class="checkbox"><input type="checkbox" onclick="selectAll( $(this) )" title="' . $val . '" class="selectAll-js"></label>', true);
            }
        } else {
            $e = $th->appendElement('th', array(), $val);
        }
    }
    $page = isset($page) ? $page : 1;
    $recordCount = isset($recordCount) ? $recordCount : 1;
    $sr_no = ($page >= 1 && !$product_id) ? $recordCount - (($page - 1) * $pageSize) : count($arrListing);

    foreach ($arrListing as $sn => $row) {
        $tr = $tbl->appendElement('tr', array('class' => ($row['selprod_active'] != applicationConstants::ACTIVE) ? '' : ''));

        foreach ($arr_flds as $key => $val) {
            $td = $tr->appendElement('td');
            switch ($key) {
                case 'select_all':
                    $td->appendElement('plaintext', array(), '<label class="checkbox"><input class="selectItem--js" type="checkbox" name="selprod_ids[]" value=' . $row['selprod_id'] . '></label>', true);
                    break;
                case 'listserial':
                    $td->appendElement('plaintext', array(), $sr_no, true);
                    break;
                case 'name':
                    $str = $this->includeTemplate('_partial/product/product-info-html.php', ['product' => $row, 'siteLangId' => $siteLangId], false, true);
                    $td->appendElement('plaintext', array(), $str, true);
                    break;
                case 'selprod_price':
                    $defaultCurrencyValue = '';
                    $price = '<span class="form-text text-muted"><i>' . Labels::getLabel('LBL_N/A', $siteLangId) . '</i></span>';
                    if (false === SellerProduct::isPriceHidden($row['selprod_hide_price'], $row['shop_rfq_enabled'])) {
                        if ($currency_id != $selectedCurrency) {
                            $defaultCurrencyValue = '<br><span class="form-text text-muted" data-bs-toggle="tooltip" title="' . Labels::getLabel('LBL_SYSTEM_DEFAULT_CURRENCY.') . '">(' . CommonHelper::displayMoneyFormat($row[$key], true, true) . ')</span>';
                        }
                        $price = '<span>' . CommonHelper::displayMoneyFormat($row[$key]) . '</span>' . $defaultCurrencyValue;
                    }
                    $td->appendElement('plaintext', array(), $price, true);
                    break;
                case 'selprod_stock':
                    $td->appendElement('plaintext', array(), $row[$key], true);
                    if ($row['selprod_track_inventory'] && ($row['selprod_stock']  <= $row['selprod_threshold_stock_level'])) {
                        $td->appendElement('plaintext', array(), '<i class="fa fa-info-circle" data-bs-toggle="tooltip" data-placement="right" title="' . Labels::getLabel('MSG_Product_stock_qty_below_or_equal_to_threshold_level', $siteLangId) . '"></i>', true);
                    }
                    break;
                case 'selprod_available_from':
                    $td->appendElement('plaintext', array(), FatDate::format($row[$key], false), true);
                    break;
                case 'selprod_active':
                    $attributes = "";
                    if (applicationConstants::ACTIVE == $row['selprod_active']) {
                        $attributes = 'checked';
                    }
                    $attributes .= ' onclick="toggleSellerProductStatus(event,this)"';
                    $str = HtmlHelper::configureSwitchForCheckboxStatic('', $row['selprod_id'], $attributes);
                    $td->appendElement('plaintext', array(), $str, true);
                    break;
                case 'action':
                    $ul = $td->appendElement("ul", array("class" => "actions"), '', true);
                    $li = $ul->appendElement("li");
                    $li->appendElement(
                        'a',
                        array('href' => UrlHelper::generateUrl('seller', 'sellerProductForm', array($row['selprod_product_id'], $row['selprod_id'])), 'title' => Labels::getLabel('LBL_Edit', $siteLangId)),
                        '<i class="icn">
                        <svg class="svg" width="18" height="18">
                            <use
                                xlink:href="' . CONF_WEBROOT_URL . 'images/retina/sprite-actions.svg' . AttachedFile::setTimeParam(RELEASE_DATE) . '#edit">
                            </use>
                        </svg>
                    </i>',
                        true
                    );

                    $li = $ul->appendElement("li");
                    $li->appendElement(
                        'a',
                        array('href' => 'javascript:void(0)', 'title' => Labels::getLabel('LBL_Delete', $siteLangId), "onclick" => "sellerProductDelete(" . $row['selprod_id'] . ")"),
                        '<i class="icn">
                        <svg class="svg" width="18" height="18">
                            <use
                                xlink:href="' . CONF_WEBROOT_URL . 'images/retina/sprite-actions.svg' . AttachedFile::setTimeParam(RELEASE_DATE) . '#delete">
                            </use>
                        </svg>
                    </i>',
                        true
                    );
                    //$productOptions = Product::getProductOptions($row['selprod_product_id'], $siteLangId);
                    $available = Product::availableForAddToStore($row['selprod_product_id'], $userParentId);
                    if ($available) {
                        $li = $ul->appendElement("li");
                        $li->appendElement(
                            'a',
                            array('href' => 'javascript:void(0)', 'title' => Labels::getLabel('LBL_Clone', $siteLangId), "onclick" => "sellerProductCloneForm(" . $row['selprod_product_id'] . "," . $row['selprod_id'] . ")"),
                            '<i class="icn">
                            <svg class="svg" width="18" height="18">
                                <use
                                    xlink:href="' . CONF_WEBROOT_URL . 'images/retina/sprite-actions.svg' . AttachedFile::setTimeParam(RELEASE_DATE) . '#copy">
                                </use>
                            </svg>
                        </i>',
                            true
                        );
                    }

                    $li = $ul->appendElement("li");
                    $li->appendElement(
                        'a',
                        array('href' => 'javascript:void(0)', 'title' => Labels::getLabel('LBL_PRODUCT_MISSING_INFO', $siteLangId), "onclick" => "productMissingInfo(" . $row['selprod_id'] . ")"),
                        '<i class="icn">
                            <svg class="svg" width="18" height="18">
                                <use
                                    xlink:href="' . CONF_WEBROOT_URL . 'images/retina/sprite-actions.svg' . AttachedFile::setTimeParam(RELEASE_DATE) . '#warning">
                                </use>
                            </svg>
                        </i>',
                        true
                    );

                    break;
                default:
                    $td->appendElement('plaintext', array(), $row[$key], true);
                    break;
            }
        }

        $sr_no--;
    }
    if (count($arrListing) == 0) {
        echo $tbl->getHtml();
        $message = Labels::getLabel('LBL_No_Records_Found', $siteLangId);
        $this->includeTemplate('_partial/no-record-found.php', array('siteLangId' => $siteLangId, 'message' => $message));
    } else {
        $frm = new Form('frmSellerProductsListing', array('id' => 'frmSellerProductsListing'));
        $frm->setFormTagAttribute('class', 'form actionButtons-js');
        $frm->setFormTagAttribute('onsubmit', 'formAction(this, reloadList ); return(false);');
        $frm->setFormTagAttribute('action', UrlHelper::generateUrl('Seller', 'toggleBulkStatuses'));
        $frm->addHiddenField('', 'status');

        echo $frm->getFormTag();
        echo $frm->getFieldHtml('status');
        echo $tbl->getHtml(); ?>
        </form>
    <?php } ?>
</div>

<?php if (!$product_id) {
    $postedData['page'] = $page;
    echo FatUtility::createHiddenFormFromData($postedData, array('name' => 'frmSellerProductSearchPaging'));
    $pagingArr = array('pageCount' => $pageCount, 'page' => $page, 'recordCount' => $recordCount, 'callBackJsFunc' => 'goToSellerProductSearchPage');
    $this->includeTemplate('_partial/pagination.php', $pagingArr, false);
}
