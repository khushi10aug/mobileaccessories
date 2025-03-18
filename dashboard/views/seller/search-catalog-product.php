<?php defined('SYSTEM_INIT') or die('Invalid Usage.'); ?>
<div class="card-table">
    <div class="js-scrollable table-wrap table-responsive">
        <?php
        $arr_flds = array(
            'listserial' => Labels::getLabel('LBL_#', $siteLangId),
            'product_identifier' => Labels::getLabel('LBL_Product', $siteLangId),
            'product_model' => Labels::getLabel('LBL_Model', $siteLangId),
            'product_active' => Labels::getLabel('LBL_Status', $siteLangId),
            'product_approved' => Labels::getLabel('LBL_Admin_Approval', $siteLangId)
        );

        if (0 < FatApp::getConfig('CONF_WITHOUT_PROD_VARIANTS', FatUtility::VAR_INT, 0)) {
            $arr_flds = array_merge(['select_all' => Labels::getLabel('LBL_SELECT_ALL', $siteLangId)], $arr_flds);
        }

        $width = array(
            'select_all' => '5%',
            'listserial' => '5%',
            'product_identifier' => '35%',
            'product_model' => '10%',
            'product_active' => '10%',
            'product_approved' => '15%',
            'action' => '20%'
        );
        $isCustom = $postedData['type'] ?? 0;
        if ($canEdit && $canEditShipProfile && 1 > $isCustom) {
            $arr_flds['product_shipped_by'] = Labels::getLabel('LBL_SHIPPED_BY_ME', $siteLangId);
            $width['product_shipped_by'] = '15%';
            $width['product_identifier'] = '25%';
        }
        $tableClass = '';
        if (0 < count($arrListing)) {
            $tableClass = "table-justified";
        }
        $arr_flds['action'] = '';

        $disableSelectAll = empty($arrListing) ? 'disabled="disabled"' : '';

        $tbl = new HtmlElement('table', array('width' => '100%', 'class' => 'table listingTableJs ' . $tableClass));
        $th = $tbl->appendElement('thead', ['class' => 'tableHeadJs'])->appendElement('tr', array('class' => ''));
        foreach ($arr_flds as $key => $val) {
            switch ($key) {
                case 'select_all':
                    $th->appendElement('th', [], '<label class="checkbox"><input title="' . $val . '" data-bs-toggle="tooltip" type="checkbox" ' . $disableSelectAll . ' onclick="selectAll(this)" class="selectAllJs"><i class="input-helper"></i></label>', true);
                    break;
                default:
                    $th->appendElement('th', array('width' => $width[$key]), $val);
                    break;
            }
        }

        $sr_no = ($page > 1) ? $recordCount - (($page - 1) * $pageSize) : $recordCount;

        $tbody = $tbl->appendElement('tbody', ['class' => 'listingRecordJs']);
        foreach ($arrListing as $sn => $row) {
            $tdAttr = ('action' == $key) ? ['class' => 'align-right'] : [];
            $tr = $tbody->appendElement('tr', array('class' => ''));
            foreach ($arr_flds as $key => $val) {
                $td = $tr->appendElement('td');
                switch ($key) {
                    case 'select_all':
                        $td->appendElement('plaintext', $tdAttr, '<label class="checkbox"><input class="selectItemJs" type="checkbox" name="record_ids[]" value=' . $row['product_id'] . '><i class="input-helper"></i></label>', true);
                        break;
                    case 'listserial':
                        $td->appendElement('plaintext', $tdAttr, $sr_no, true);
                        break;
                    case 'product_identifier':
                        $uploadedTime = AttachedFile::setTimeParam($row['product_updated_on']);
                        $html = '<div class="product-profile">
                            <figure class="product-profile__pic"><img ' . HtmlHelper::getImgDimParm(ImageDimension::TYPE_PRODUCTS, ImageDimension::VIEW_MINI) . ' src="' . UrlHelper::getCachedUrl(UrlHelper::generateFileUrl('image', 'product', array($row['product_id'], ImageDimension::VIEW_MINI, 0, 0, $siteLangId), CONF_WEBROOT_FRONTEND) . $uploadedTime, CONF_IMG_CACHE_TIME, '.jpg') . '" title="' . $row['product_name'] . '" alt="' . $row['product_name'] . '"></figure>
                                <div class="product-profile__description">
                                    <div class="product-profile__title">' . $row['product_name'] . '</div>
                                    <div class="product-profile__sub_title"> (' . $row[$key] . ') </div>
                                </div>
                            </div>';
                        $td->appendElement('plaintext', $tdAttr, $html, true);
                        break;
                    case 'attrgrp_name':
                        $td->appendElement('plaintext', $tdAttr, CommonHelper::displayNotApplicable($siteLangId, $row[$key]), true);
                        break;
                    case 'product_approved':
                        $td->appendElement('span', array('class' => 'badge badge-inline ' . $approveUnApproveClassArr[$row[$key]]), $approveUnApproveArr[$row[$key]] . '<br>', true);
                        break;
                    case 'product_active':
                        if (0 < FatApp::getConfig('CONF_WITHOUT_PROD_VARIANTS', FatUtility::VAR_INT, 0)) {
                            $statusAct = ($canEdit) ? 'updateStatus(event, this, ' . $row['product_id'] . ', ' . ((int) !$row[$key]) . ')' : 'return false;';
                            $statusClass = ($canEdit) ? '' : 'disabled';
                            $checked = applicationConstants::ACTIVE == $row[$key] ? 'checked' : '';

                            $htm = '<span class="switch switch-sm switch-icon">
                            <label>
                                <input type="checkbox" data-old-status="' . $row[$key] . '" value="' . $row['product_id'] . '" ' . $checked . ' onclick="' . $statusAct . '" ' . $statusClass . '>
                                <span class="input-helper"></span>
                            </label>
                        </span>';
                            $td->appendElement('plaintext', $tdAttr, $htm, true);
                        } else {
                            $td->appendElement('span', array('class' => 'badge badge-inline ' . $activeInactiveClassArr[$row[$key]]), $activeInactiveArr[$row[$key]] . '<br>', true);
                        }
                        break;
                    case 'product_model':
                        $lbl = !empty($row[$key]) ? $row[$key] : Labels::getLabel('LBL_N/A', $siteLangId);
                        $td->appendElement('plaintext', $tdAttr, $lbl, true);
                        break;
                    case 'product_shipped_by':
                        $str = Labels::getLabel('LBL_N/A', $siteLangId);
                        if (!$row['product_seller_id'] && !in_array($row['product_type'], [Product::PRODUCT_TYPE_DIGITAL, Product::PRODUCT_TYPE_SERVICE])) {
                            $attributes = ($row['psbs_user_id']) ? "checked" : "";
                            $statucAct = (!$row['psbs_user_id']) ? 'setShippedBySeller(' . $row['product_id'] . ')' : 'setShippedByAdmin(' . $row['product_id'] . ')';
                            $attributes .= ' onclick="' . $statucAct . '"';
                            $str = HtmlHelper::configureSwitchForCheckboxStatic('', $row['product_id'], $attributes);
                        } else if (Product::PRODUCT_TYPE_SERVICE == $row['product_type']) {
                            $str = '<span class="badge badge-inline badge-success">' . Labels::getLabel('LBL_ME', $siteLangId) . '</span>';
                        }

                        $td->appendElement('plaintext', $tdAttr, $str, true);
                        break;
                    case 'action':
                        $canAddToStore = true;
                        if ($row['product_approved'] == applicationConstants::NO) {
                            $canAddToStore = false;
                        }
                        $available = Product::availableForAddToStore($row['product_id'], $userParentId);
                        $ul = $td->appendElement("ul", array('class' => 'actions'), '', true);
                        if ($canEdit) {
                            $hasInventory = Product::hasInventory($row['product_id'], UserAuthentication::getLoggedUserId());
                            if ($hasInventory && !FatApp::getConfig('CONF_WITHOUT_PROD_VARIANTS', FatUtility::VAR_INT, 0)) {
                                $li = $ul->appendElement("li");
                                $li->appendElement(
                                    'a',
                                    array('href' => 'javascript:void(0)', 'onclick' => 'sellerProducts(' . $row['product_id'] . ')', 'class' => '', 'title' => Labels::getLabel('LBL_View_Inventories', $siteLangId), true),
                                    '<i class="icn">
                                <svg class="svg" width="18" height="18">
                                    <use
                                        xlink:href="' . CONF_WEBROOT_URL . 'images/retina/sprite-actions.svg' . AttachedFile::setTimeParam(RELEASE_DATE) . '#inventories">
                                    </use>
                                </svg>
                            </i>',
                                    true
                                );
                            }

                            if ($available && 1 > FatApp::getConfig('CONF_WITHOUT_PROD_VARIANTS', FatUtility::VAR_INT, 0)) {
                                $li = $ul->appendElement("li");
                                $li->appendElement(
                                    'a',
                                    array('href' => 'javascript:void(0)', 'class' => ($canAddToStore) ? 'icn-highlighted' : 'icn-highlighted disabled', 'onclick' => 'checkIfAvailableForInventory(' . $row['product_id'] . ')', 'title' => Labels::getLabel('LBL_Add_To_Store', $siteLangId), true),
                                    '<i class="icn">
                                    <svg class="svg" width="18" height="18">
                                        <use
                                            xlink:href="' . CONF_WEBROOT_URL . 'images/retina/sprite-actions.svg' . AttachedFile::setTimeParam(RELEASE_DATE) . '#add">
                                        </use>
                                    </svg>
                                </i>',
                                    true
                                );
                            }

                            if ($row['product_added_by_admin_id'] && 1 > $row['product_seller_id'] && 1 > $row['product_attachements_with_inventory'] && $row['product_type'] == Product::PRODUCT_TYPE_DIGITAL) {
                                $li = $ul->appendElement("li");
                                $li->appendElement(
                                    'a',
                                    array('href' => 'javascript:void(0)', 'onclick' => 'fileLinkForm(' . $row['product_id'] . ')', 'class' => '', 'title' => Labels::getLabel('LBL_LINK_OR_FILES', $siteLangId), true),
                                    '<i class="icn">
                                        <svg class="svg" width="18" height="18">
                                            <use
                                                xlink:href="' . CONF_WEBROOT_URL . 'images/retina/sprite-actions.svg' . AttachedFile::setTimeParam(RELEASE_DATE) . '#icon-download">
                                            </use>
                                        </svg>
                                    </i>',
                                    true
                                );
                            }

                            if (0 != $row['product_seller_id']) {
                                $li = $ul->appendElement("li");
                                $li->appendElement('a', array('class' => '', 'title' => Labels::getLabel('LBL_Edit', $siteLangId), "href" => UrlHelper::generateUrl('products', 'form', array($row['product_id']))), '<i class="icn">
                                    <svg class="svg" width="18" height="18">
                                        <use
                                            xlink:href="' . CONF_WEBROOT_URL . 'images/retina/sprite-actions.svg' . AttachedFile::setTimeParam(RELEASE_DATE) . '#edit">
                                        </use>
                                    </svg>
                                </i>', true);
                            }

                            if ($canEditShipProfile && $row['product_added_by_admin_id'] && $row['psbs_user_id'] && $row['product_type'] == Product::PRODUCT_TYPE_PHYSICAL) {
                                $li = $ul->appendElement("li");
                                $li->appendElement("a", array('title' => Labels::getLabel('LBL_Edit_Shipping', $siteLangId), 'onclick' => 'sellerShippingForm(' . $row['product_id'] . ')', 'href' => 'javascript:void(0)'), '<i class="icn">
                                <svg class="svg" width="18" height="18">
                                    <use
                                        xlink:href="' . CONF_WEBROOT_URL . 'images/retina/sprite-actions.svg' . AttachedFile::setTimeParam(RELEASE_DATE) . '#shipping">
                                    </use>
                                </svg>
                            </i>', true);
                            }
                        }

                        $li = $ul->appendElement("li");
                        $li->appendElement(
                            'a',
                            array('href' => 'javascript:void(0)', 'onclick' => 'catalogInfo(' . $row['product_id'] . ')', 'class' => '', 'title' => Labels::getLabel('LBL_PRODUCT_INFO', $siteLangId), true),
                            '<i class="icn">
                                <svg class="svg" width="18" height="18">
                                    <use
                                        xlink:href="' . CONF_WEBROOT_URL . 'images/retina/sprite-actions.svg' . AttachedFile::setTimeParam(RELEASE_DATE) . '#view">
                                    </use>
                                </svg>
                            </i>',
                            true
                        );

                        if (0 != $row['product_seller_id']) {
                            $li = $ul->appendElement("li");
                            $li->appendElement(
                                'a',
                                array('href' => 'javascript:void(0)', 'onclick' => 'deleteCatalog(' . $row['product_id'] . ')', 'class' => '', 'title' => Labels::getLabel('LBL_DELETE_PRODUCT', $siteLangId), true),
                                '<i class="icn">
                                <svg class="svg" width="18" height="18">
                                    <use
                                        xlink:href="' . CONF_WEBROOT_URL . 'images/retina/sprite-actions.svg' . AttachedFile::setTimeParam(RELEASE_DATE) . '#delete">
                                    </use>
                                </svg>
                            </i>',
                                true
                            );
                        }

                        if (FatApp::getConfig('CONF_WITHOUT_PROD_VARIANTS', FatUtility::VAR_INT, 0)) {
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
                        }
                        break;
                    default:
                        $td->appendElement('plaintext', $tdAttr, $row[$key], true);
                        break;
                }
            }

            $sr_no--;
        }

        if (0 < FatApp::getConfig('CONF_WITHOUT_PROD_VARIANTS', FatUtility::VAR_INT, 0)) {
            $attr = [
                'class' => 'actionButtonsJs',
                'onsubmit' => 'formAction(this); return(false);',
                'action' => UrlHelper::generateUrl(LibHelper::getControllerName(), 'toggleBulkStatusesForCatalogs'),
            ];
            $frm = new Form('listingForm', $attr);
            echo $frm->getFormTag();
            $frm->addHiddenField('', 'status');
            echo $frm->getFieldHtml('status');
            echo $tbl->getHtml();
            echo '</form>';
        } else {
            echo $tbl->getHtml();
        }

        if (count($arrListing) == 0) {
            $message = Labels::getLabel('LBL_Searched_product_is_not_found_in_catalog', $siteLangId);
            $linkArr = array();
            if (User::canAddCustomProductAvailableToAllSellers() && 1 > $isCustom) {
                $linkArr = array(
                    0 => array(
                        'href' => UrlHelper::generateUrl('CustomProducts', 'form'),
                        'label' => Labels::getLabel('LBL_REQUEST_NEW_PRODUCT', $siteLangId),
                    )
                );
            } else {
                $linkArr = array(
                    0 => array(
                        'href' => UrlHelper::generateUrl('Products', 'form'),
                        'label' => Labels::getLabel('LBL_ADD_NEW_PRODUCT', $siteLangId),
                    )
                );
            }
            $this->includeTemplate('_partial/no-record-found.php', array('siteLangId' => $siteLangId, 'linkArr' => $linkArr, 'message' => $message));
        }

        if (!isset($postedData['type']) || '' == $postedData['type']) {
            $postedData['type'] = -1;
        } ?>
    </div>
</div>
<?php $postedData['page'] = $page;
echo FatUtility::createHiddenFormFromData($postedData, array('name' => 'frmCatalogProductSearchPaging'));

$pagingArr = array('pageCount' => $pageCount, 'page' => $page, 'callBackJsFunc' => 'goToCatalogProductSearchPage');
$this->includeTemplate('_partial/pagination.php', $pagingArr, false);
