<?php defined('SYSTEM_INIT') or die('Invalid Usage.'); ?>
<div class="js-scrollable table-wrap table-responsive">
    <?php $arr_flds = array(
        'select_all' => '',
        'product_name' => Labels::getLabel('LBL_Name', $siteLangId),
        'voldiscount_min_qty' => Labels::getLabel('LBL_Minimum_Quantity', $siteLangId),
        'voldiscount_percentage' => Labels::getLabel('LBL_Discount', $siteLangId) . ' (%)'
    );
    if ($canEdit) {
        $arr_flds['action'] = '';
    }
    if (!$canEdit || 1 > count($arrListing)) {
        unset($arr_flds['select_all']);
    }
    $tableClass = '';
    if (0 < count($arrListing)) {
        $tableClass = "table-justified";
    }
    $tbl = new HtmlElement('table', array('width' => '100%', 'class' => 'table table--hovered volDiscountList-js ' . $tableClass));
    $thead = $tbl->appendElement('thead');
    $th = $thead->appendElement('tr', array('class' => ''));

    foreach ($arr_flds as $key => $val) {
        if ('select_all' == $key) {
            $th->appendElement('th')->appendElement('plaintext', array(), '<label class="checkbox"><input title="' . $val . '" type="checkbox" onclick="selectAll($(this))" class="selectAll-js"></label>', true);
        } else {
            $th->appendElement('th', array(), $val);
        }
    }

    foreach ($arrListing as $sn => $row) {
        $tr = $tbl->appendElement('tr', array());
        $volDiscountId = $row['voldiscount_id'];
        $selProdId = $row['selprod_id'];
        foreach ($arr_flds as $key => $val) {
            $tr->setAttribute('id', 'row-' . $volDiscountId);
            $td = $tr->appendElement('td');
            switch ($key) {
                case 'select_all':
                    $td->appendElement('plaintext', array(), '<label class="checkbox"><input class="selectItem--js" type="checkbox" name="selprod_ids[' . $volDiscountId . ']" value=' . $selProdId . '></label>', true);
                    break;
                case 'product_name':
                    $str = $this->includeTemplate('_partial/product/product-info-html.php', ['product' => $row, 'siteLangId' => $siteLangId], false, true);
                    $td->appendElement('plaintext', array(), $str, true);
                    break;
                case 'voldiscount_min_qty':
                case 'voldiscount_percentage':
                    $editable = $canEdit ? 'true' : 'false';

                    $td->appendElement('div', [
                        "class" => 'click-to-edit',
                        'name' => $key,
                        'data-selprod-id' => $selProdId,
                        'data-id' => $volDiscountId,
                        'data-value' => $row[$key],
                        'data-formated-value' => $row[$key],
                        'contentEditable' => $editable,
                        'data-bs-toggle' => 'tooltip',
                        'data-placement' => 'top',
                        'onblur' => 'updateValues(this)',
                        'onfocus' => 'showOrignal(this)',
                        'title' => Labels::getLabel('LBL_CLICK_TO_EDIT', $siteLangId)
                    ], $row[$key], true);
                    break;
                case 'action':
                    $ul = $td->appendElement("ul", array("class" => "actions actions--centered"), '', true);

                    $li = $ul->appendElement('li');
                    $li->appendElement(
                        'a',
                        array(
                            'href' => 'javascript:void(0)', 'class' => '',
                            'title' => Labels::getLabel('LBL_Delete', $siteLangId), "onclick" => "deleteSellerProductVolumeDiscount(" . $volDiscountId . ")"
                        ),
                        '<i class="icn">
                    <svg class="svg" width="18" height="18">
                        <use
                            xlink:href="' . CONF_WEBROOT_URL . 'images/retina/sprite-actions.svg' . AttachedFile::setTimeParam(RELEASE_DATE) . '#delete">
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
    }

    $frm = new Form('frmVolDiscountListing', array('id' => 'frmVolDiscountListing'));
    $frm->setFormTagAttribute('class', 'form');
    echo $frm->getFormTag();
    echo $tbl->getHtml(); ?>
    </form>
</div>
<?php

if (count($arrListing) == 0) {
    $message = Labels::getLabel('LBL_No_Records_Found', $siteLangId);
    $this->includeTemplate('_partial/no-record-found.php', array('siteLangId' => $siteLangId, 'message' => $message));
}

$postedData['page'] = $page;
echo FatUtility::createHiddenFormFromData($postedData, array('name' => 'frmSearchVolumeDiscountPaging'));

$pagingArr = array('pageCount' => $pageCount, 'page' => $page, 'recordCount' => $recordCount, 'callBackJsFunc' => 'goToSearchPage', 'adminLangId' => $siteLangId);
$this->includeTemplate('_partial/pagination.php', $pagingArr, false);
