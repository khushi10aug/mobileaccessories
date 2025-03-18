<?php defined('SYSTEM_INIT') or die('Invalid Usage.'); ?>
<div class="js-scrollable table-wrap table-responsive">
    <?php $arr_flds = array(
        'select_all' => '',
        'product_name' => Labels::getLabel('LBL_Name', $siteLangId),
        'selprod_price' => Labels::getLabel('LBL_Original_Price', $siteLangId),
        'splprice_price' => Labels::getLabel('LBL_Special_Price', $siteLangId),
        'splprice_start_date' => Labels::getLabel('LBL_Start_Date', $siteLangId),
        'splprice_end_date' => Labels::getLabel('LBL_End_Date', $siteLangId),
    );
    if ($canEdit) {
        $arr_flds['action']    = '';
    }
    if (!$canEdit || 1 > count($arrListing)) {
        unset($arr_flds['select_all']);
    }
    $tableClass = '';
    if (0 < count($arrListing)) {
        $tableClass = "table-justified";
    }
    $tbl = new HtmlElement('table', array('width' => '100%', 'class' => 'table splPriceList-js ' . $tableClass));
    $th = $tbl->appendElement('thead')->appendElement('tr', array('class' => ''));
    foreach ($arr_flds as $column => $lblTitle) {
        if ('select_all' == $column) {
            $th->appendElement('th')->appendElement('plaintext', array(), '<label class="checkbox"><input title="' . $lblTitle . '" type="checkbox" onclick="selectAll($(this))" class="selectAll-js"></label>', true);
        } else {
            $th->appendElement('th', array(), $lblTitle);
        }
    }

    foreach ($arrListing as $sn => $row) {
        $tr = $tbl->appendElement('tr', array());
        $splPriceId = $row['splprice_id'];
        $selProdId = $row['selprod_id'];
        $editListingFrm = new Form('editListingFrm-' . $splPriceId, array('id' => 'editListingFrm-' . $splPriceId));
        foreach ($arr_flds as $column => $val) {
            $tr->setAttribute('id', 'row-' . $splPriceId);
            $td = $tr->appendElement('td');
            switch ($column) {
                case 'select_all':
                    $td->appendElement('plaintext', array(), '<label class="checkbox"><input class="selectItem--js" type="checkbox" name="selprod_ids[' . $splPriceId . ']" value=' . $selProdId . '></label>', true);
                    break;
                case 'product_name':
                    $str = $this->includeTemplate('_partial/product/product-info-html.php', ['product' => $row, 'siteLangId' => $siteLangId], false, true);
                    $td->appendElement('plaintext', array(), $str, true);
                    break;
                case 'selprod_price':
                    $price = CommonHelper::displayMoneyFormat($row[$column], true, true);
                    $td->appendElement('plaintext', array(), $price, true);
                    break;
                case 'splprice_start_date':
                case 'splprice_end_date':
                    $date = date('Y-m-d', strtotime($row[$column]));
                    $fldAttr = array(
                        'placeholder' => $val,
                        'readonly' => 'readonly',
                        'class' => 'field--calender inputDateJs d-none',
                        'name' => $column,
                        'data-selprod-id' => $selProdId,
                        'data-price' => $row['selprod_price'],
                        'data-id' => $splPriceId,
                        'data-value' => $date,
                        'data-formated-value' => $date,
                    );

                    $attr = ['class' => 'dateJs contenteditable click-to-edit', 'data-bs-toggle' => 'tooltip', 'data-placement' => 'top', 'title' => Labels::getLabel('LBL_CLICK_TO_EDIT', $siteLangId)];
                    $td->appendElement('div', $attr, $date, true);
                    $editListingFrm->addDateField($val, $column, $date, $fldAttr);
                    $td->appendElement('plaintext', array(), $editListingFrm->getFieldHtml($column), true);
                    break;
                case 'splprice_price':
                    $editable = $canEdit ? 'true' : 'false';
                    $div = $td->appendElement('div', ['class' => 'edit-price']);
                    $splPrice = CommonHelper::displayMoneyFormat($row[$column], true, true);

                    $div->appendElement('div', [
                        "class" => 'click-to-edit',
                        'name' => $column,
                        'data-selprod-id' => $selProdId,
                        'data-price' => $row['selprod_price'],
                        'data-id' => $splPriceId,
                        'data-value' => $row[$column],
                        'data-formated-value' => $splPrice,
                        'contentEditable' => $editable,
                        'data-bs-toggle' => 'tooltip',
                        'data-placement' => 'top',
                        'onblur' => 'updateValues(this)',
                        'onfocus' => 'showOrignal(this)',
                        'title' => Labels::getLabel('LBL_CLICK_TO_EDIT', $siteLangId)
                    ], $splPrice, true);
                    if ($row['selprod_price'] > $row[$column]) {
                        $discountPrice = $row['selprod_price'] - $row[$column];
                        $discountPercentage = round(($discountPrice / $row['selprod_price']) * 100, 2);
                        $discountPercentage = $discountPercentage . "% " . Labels::getLabel('LBL_off', $siteLangId);
                        $div->appendElement('div', array("class" => 'percentValJs badge badge-success ms-1'), $discountPercentage, true);
                    }
                    break;
                case 'action':
                    $ul = $td->appendElement("ul", array("class" => "actions actions--centered"), '', true);

                    $li = $ul->appendElement('li');
                    $li->appendElement(
                        'a',
                        array(
                            'href' => 'javascript:void(0)', 'class' => '',
                            'title' => Labels::getLabel('LBL_Delete', $siteLangId), "onclick" => "deleteSellerProductSpecialPrice(" . $splPriceId . ")"
                        ),
                        '<i class="icn">
                                            <svg class="svg" width="18" height="18">
                                                <use xlink:href="' . CONF_WEBROOT_URL . 'images/retina/sprite-actions.svg' . AttachedFile::setTimeParam(RELEASE_DATE) . '#delete">
                                                </use>
                                            </svg>
                                        </i>',
                        true
                    );
                    break;
                default:
                    $td->appendElement('plaintext', array(), $row[$column], true);
                    break;
            }
        }
    }

    $frm = new Form('frmSplPriceListing', array('id' => 'frmSplPriceListing'));
    $frm->setFormTagAttribute('class', 'form');

    echo $frm->getFormTag();
    echo $tbl->getHtml();
    ?>
    </form>
</div>
<?php
if (count($arrListing) == 0) {
    $message = Labels::getLabel('LBL_No_Records_Found', $siteLangId);
    $this->includeTemplate('_partial/no-record-found.php', array('siteLangId' => $siteLangId, 'message' => $message));
} ?>
<?php $postedData['page'] = $page;
echo FatUtility::createHiddenFormFromData($postedData, array('name' => 'frmSearchSpecialPricePaging'));

$pagingArr = array('pageCount' => $pageCount, 'page' => $page, 'recordCount' => $recordCount, 'callBackJsFunc' => 'goToSearchPage', 'adminLangId' => $siteLangId);
$this->includeTemplate('_partial/pagination.php', $pagingArr, false);
