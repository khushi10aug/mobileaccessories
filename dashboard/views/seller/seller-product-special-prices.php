<?php defined('SYSTEM_INIT') or die('Invalid Usage.'); ?>
<div class="tabs tabs--small   tabs--scroll clearfix">
    <?php require_once('sellerCatalogProductTop.php'); ?>
</div>
<div class="card">
    <?php if (count($arrListing) > 0) { ?>
        <div class="card-head">
            <div class="card-head-label">
                <h5 class="card-title"><?php echo Labels::getLabel('LBL_Special_price', $siteLangId); ?>
                </h5>
            </div>
            <div class="action">
                <a class="btn btn-brand btn-sm" href="javascript:void(0);" onclick='sellerProductSpecialPriceForm(<?php echo $selprod_id; ?>, 0);'>
                    <?php echo Labels::getLabel('LBL_Add_New_Special_Price', $siteLangId); ?>
                </a>
                <!-- <a class="btn btn-outline-gray btn-sm" href="<?php echo UrlHelper::generateUrl('Seller', 'specialPrice', array($selprod_id)); ?>">
                    <?php echo Labels::getLabel('LBL_MANAGE_SPECIAL_PRICES', $siteLangId) ?>
                </a> -->
            </div>
        </div>
    <?php } ?>
    <div class="card-body">
        <div class="row">
            <div class="<?php echo (count($arrListing) > 0) ? 'col-md-8' : 'col-md-12'; ?>">
                <div class="form__subcontent js-scrollable table-wrap">
                    <?php
                    $arr_flds = array(
                        'listserial' => Labels::getLabel('LBL_#', $siteLangId),
                        'splprice_price' => Labels::getLabel('LBL_Special_Price', $siteLangId),
                        'splprice_start_date' => Labels::getLabel('LBL_Start_Date', $siteLangId),
                        'splprice_end_date' => Labels::getLabel('LBL_End_Date', $siteLangId),
                        'action' => '',
                    );
                    $tableClass = '';
                    if (0 < count($arrListing)) {
                        $tableClass = "table-justified";
                    }
                    $tbl = new HtmlElement('table', array('width' => '100%', 'class' => 'table ' . $tableClass));
                    $th = $tbl->appendElement('thead')->appendElement('tr', array('class' => ''));
                    foreach ($arr_flds as $val) {
                        $e = $th->appendElement('th', array(), $val);
                    }

                    $sr_no = 0;
                    foreach ($arrListing as $sn => $row) {
                        $sr_no++;
                        $tr = $tbl->appendElement('tr', array());
                        foreach ($arr_flds as $key => $val) {
                            $td = $tr->appendElement('td');
                            switch ($key) {
                                case 'listserial':
                                    $td->appendElement('plaintext', array(), $sr_no, true);
                                    break;
                                case 'splprice_price':
                                    $td->appendElement('plaintext', array(), CommonHelper::displayMoneyFormat($row[$key], true, true), true);
                                    break;
                                case 'splprice_start_date':
                                    $td->appendElement('plaintext', array(), FatDate::format($row[$key]), true);
                                    break;
                                case 'splprice_end_date':
                                    $td->appendElement('plaintext', array(), FatDate::format($row[$key]), true);
                                    break;
                                case 'action':
                                    $ul = $td->appendElement("ul", array("class" => "actions"), '', true);
                                    $li = $ul->appendElement("li");
                                    $li->appendElement(
                                        'a',
                                        array('href' => 'javascript:void(0)', 'class' => '', 'title' => Labels::getLabel('LBL_Edit', $siteLangId), "onclick" => "sellerProductSpecialPriceForm(" . $selprod_id . ", " . $row['splprice_id'] . ")"),
                                        '<svg class="svg" width="18" height="18">
                                        <use
                                            xlink:href="' . CONF_WEBROOT_URL . 'images/retina/sprite-actions.svg' . AttachedFile::setTimeParam(RELEASE_DATE) . '#edit">
                                        </use>
                                    </svg>',
                                        true
                                    );
                                    $li = $ul->appendElement("li");
                                    $li->appendElement(
                                        'a',
                                        array('href' => 'javascript:void(0)', 'class' => '', 'title' => Labels::getLabel('LBL_Delete', $siteLangId), "onclick" => "deleteSellerProductSpecialPrice(" . $row['splprice_id'] . ")"),
                                        '<svg class="svg" width="18" height="18">
                                        <use
                                            xlink:href="' . CONF_WEBROOT_URL . 'images/retina/sprite-actions.svg' . AttachedFile::setTimeParam(RELEASE_DATE) . '#delete">
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
                    }

                    if (count($arrListing) == 0) {
                        $message = Labels::getLabel('LBL_No_any_special_prices_on_this_product', $siteLangId);
                        $linkArr = [
                            [
                                'href' => 'javascript:void(0);',
                                'label' => Labels::getLabel('LBL_Add_New_Special_Price', $siteLangId),
                                'onclick' => 'sellerProductSpecialPriceForm(' . $selprod_id . ', 0);',
                            ]
                        ];
                        $this->includeTemplate('_partial/no-record-found.php', array('siteLangId' => $siteLangId, 'linkArr' => $linkArr, 'message' => $message));
                    } else {
                        echo $tbl->getHtml();
                    } ?>
                </div>
            </div>
        </div>
    </div>
</div>