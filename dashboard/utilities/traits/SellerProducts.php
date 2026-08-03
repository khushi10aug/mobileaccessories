<?php

trait SellerProducts
{
    private $selProdRecordId = 0;

    protected function getSellerProductSearchForm($product_id = 0)
    {
        $frm = new Form('frmSearch');
        $frm->addHiddenField('', 'badge_id');
        $frm->addHiddenField('', 'ribbon_id');
        $frm->addHiddenField('', 'product_id', $product_id);
        $frm->addHiddenField('', 'total_record_count');
        $frm->addHiddenField('', 'page', 1);
        $frm->addTextBox(Labels::getLabel('BTN_SEARCH_BY', $this->siteLangId), 'keyword', '', array('id' => 'keyword'));

        HtmlHelper::addSearchButton($frm);
        return $frm;
    }

    public function products($product_id = 0)
    {
        if (FatApp::getConfig('CONF_WITHOUT_PROD_VARIANTS', FatUtility::VAR_INT, 0)) {
            FatApp::redirectUser(UrlHelper::generateUrl('Seller', 'catalog'));
        }

        $this->userPrivilege->canViewProducts(UserAuthentication::getLoggedUserId());
        $this->includeDateTimeFiles();
        if (!$this->isShopActive($this->userParentId)) {
            FatApp::redirectUser(UrlHelper::generateUrl('Seller', 'shop'));
        }

        $product_id = FatUtility::int($product_id);
        $this->set('canEdit', $this->userPrivilege->canEditProducts(UserAuthentication::getLoggedUserId(), true));
        $this->set('frmSearch', $this->getSellerProductSearchForm($product_id));
        $this->set('product_id', $product_id);

        $srch = new ProductSearch($this->siteLangId, null, null, false, false);
        $srch->joinProductShippedBySeller($this->userParentId);
        $srch->joinTable(AttributeGroup::DB_TBL, 'LEFT OUTER JOIN', 'product_attrgrp_id = attrgrp_id', 'attrgrp');
        $srch->joinTable(UpcCode::DB_TBL, 'LEFT OUTER JOIN', 'upc_product_id = product_id', 'upc');
        $srch->addDirectCondition(
            '((CASE
                    WHEN product_seller_id = 0 THEN product_active = 1
                    WHEN product_seller_id > 0 THEN product_active IN (1, 0)
                    END ) )'
        );
        $srch->addCondition('product_deleted', '=', 'mysql_func_' . applicationConstants::NO, 'AND', true);
        $cnd = $srch->addCondition('product_seller_id', '=', 'mysql_func_0', 'AND', true);
        $cnd->attachCondition('product_added_by_admin_id', '=', applicationConstants::YES, 'AND');
        $srch->addGroupBy('product_id');
        $rs = $srch->getResultSet();
        $adminCatalogs = $srch->recordCount();
        $this->set('adminCatalogs', $adminCatalogs);
        $this->set('statusButtons', true);
        $this->set('deleteButton', true);
        $this->set('keywordPlaceholder', Labels::getLabel('LBL_SEARCH_BY_PRODUCT_NAME', $this->siteLangId));
        $this->_template->addJs(['js/select2.js']);
        $this->_template->addCss(['css/select2.min.css']);
        $this->_template->render(true, true);
    }

    public function sellerProducts($product_id = 0)
    {
        $this->userPrivilege->canViewProducts(UserAuthentication::getLoggedUserId());
        $product_id = FatUtility::int($product_id);
        if (0 < $product_id) {
            $row = Product::getAttributesById($product_id, array('product_id'));
            if (!$row) {
                FatUtility::dieWithError(Labels::getLabel('MSG_INVALID_REQUEST', $this->siteLangId));
            }
        }
        $keyword = FatApp::getPostedData('keyword', FatUtility::VAR_STRING, '');
        $userId = $this->userParentId;

        $srch = SellerProduct::searchSellerProducts($this->siteLangId, $userId, $keyword);
        $srch->joinTable(Shop::DB_TBL, 'INNER JOIN', 'shop_user_id = selprod_user_id', 'shop');

        $pageSize = FatApp::getConfig('CONF_PAGE_SIZE');
        $page = FatApp::getPostedData('page', FatUtility::VAR_INT, 1);
        $page = ($page <= 0) ? 1 : $page;
        $post = FatApp::getPostedData();

        $srch->addGroupBy('selprod_id');
        $this->setRecordCount(clone $srch, $pageSize, $page, $post, true);
        $srch->doNotCalculateRecords();
        $srch->addOrder('selprod_added_on', 'DESC');
        $srch->addOrder('selprod_id', 'DESC');
        $srch->addOrder('product_name');
        $srch->addMultipleFields(
            array(
                'selprod_id',
                'selprod_user_id',
                'selprod_price',
                'selprod_stock',
                'selprod_track_inventory',
                'selprod_cart_type',
                'selprod_hide_price',
                'selprod_threshold_stock_level',
                'selprod_product_id',
                'product_identifier',
                'selprod_active',
                'selprod_available_from',
                'IFNULL(product_name, product_identifier) as product_name',
                'COALESCE(selprod_title, product_name, product_identifier) as selprod_title',
                'product_updated_on', 'shop_rfq_enabled'
            )
        );
        $srch->setPageNumber($page);
        $srch->setPageSize($pageSize);
        if ($product_id) {
            $srch->addCondition('selprod_product_id', '=', 'mysql_func_' . $product_id, 'AND', true);
        }

        $srch->addOrder('selprod_id', 'DESC');
        $arrListing = FatApp::getDb()->fetchAll($srch->getResultSet());
        if (count($arrListing)) {
            foreach ($arrListing as &$arr) {
                $arr['options'] = SellerProduct::getSellerProductOptions($arr['selprod_id'], true, $this->siteLangId);
            }
        }

        $this->set("arrListing", $arrListing);
        $this->set('product_id', $product_id);
        $this->set('activeInactiveArr', applicationConstants::getActiveInactiveArr($this->siteLangId));
        $this->set('canEdit', $this->userPrivilege->canEditProducts(UserAuthentication::getLoggedUserId(), true));
        $this->set('postedData', $post);
        $this->set('userParentId', $userId);
        $this->_template->render(false, false);
    }

    public function sellerProductForm($product_id, $selprod_id = 0)
    {
        $this->userPrivilege->canEditProducts(UserAuthentication::getLoggedUserId());

        if (0 == $selprod_id && !Product::availableForAddToStore($product_id, $this->userParentId)) {
            FatApp::redirectUser(UrlHelper::generateUrl('Seller', 'products'));
        }

        if (!UserPrivilege::isUserHasValidSubsription($this->userParentId)) {
            Message::addErrorMessage(Labels::getLabel("ERR_Please_buy_subscription", $this->siteLangId));
            FatApp::redirectUser(UrlHelper::generateUrl('Seller', 'Packages'));
        }

        if (0 == $selprod_id && FatApp::getConfig('CONF_ENABLE_SELLER_SUBSCRIPTION_MODULE', FatUtility::VAR_INT, 0) && SellerProduct::getActiveCount($this->userParentId) >= SellerPackages::getAllowedLimit($this->userParentId, $this->siteLangId, 'ossubs_inventory_allowed')) {
            Message::addErrorMessage(Labels::getLabel("ERR_You_have_crossed_your_package_limit.", $this->siteLangId));
            FatApp::redirectUser(UrlHelper::generateUrl('Seller', 'Packages'));
        }

        $selprod_id = FatUtility::int($selprod_id);
        $product_id = FatUtility::int($product_id);

        if (!$product_id) {
            FatApp::redirectUser($_SESSION['referer_page_url']);
        }

        $userId = $this->userParentId;
        $userObj = new User($userId);
        $vendorReturnAddress = $userObj->getUserReturnAddress($this->siteLangId);

        if (!$vendorReturnAddress) {
            Message::addErrorMessage(Labels::getLabel('ERR_PLEASE_ADD_RETURN_ADDRESS', $this->siteLangId));
            FatApp::redirectUser(UrlHelper::generateUrl('seller', 'shop', array(User::RETURN_ADDRESS_ACCOUNT_TAB)));
        }
        $languages = Language::getAllNames();
        $userObj = new User($userId);

        foreach ($languages as $langId => $langName) {
            $srch = new SearchBase(User::DB_TBL_USR_RETURN_ADDR_LANG);
            $srch->addCondition('uralang_user_id', '=', 'mysql_func_' . $userId, 'AND', true);
            $srch->addCondition('uralang_lang_id', '=', 'mysql_func_' . $langId, 'AND', true);
            $srch->doNotCalculateRecords();
            $srch->setPageSize(1);
            $rs = $srch->getResultSet();
            $vendorReturnAddress = FatApp::getDb()->fetch($rs);
            if (!$vendorReturnAddress) {
                Message::addErrorMessage(Labels::getLabel('ERR_PLEASE_ADD_RETURN_ADDRESS_BEFORE_ADDING/updating_product', $this->siteLangId));
                FatApp::redirectUser(UrlHelper::generateUrl('seller', 'shop', array(User::RETURN_ADDRESS_ACCOUNT_TAB, $langId)));
            }
        }

        $productRow = Product::getProductDataById($this->siteLangId, $product_id, array('product_type'));

        if (!$productRow) {
            Message::addErrorMessage(Labels::getLabel('ERR_INVALID_REQUEST', $this->siteLangId));
            FatApp::redirectUser($_SESSION['referer_page_url']);
        }

        if (!UserPrivilege::canSellerAddProductInCatalog($product_id, $userId)) {
            Message::addErrorMessage(Labels::getLabel("ERR_Invalid_Request", $this->siteLangId));
            FatApp::redirectUser(UrlHelper::generateUrl('Seller', 'Products'));
        }

        $canAttachDigitalDownload = 0;

        $ddpObj = new DigitalDownloadPrivilages();

        if (
            $productRow['product_type'] == Product::PRODUCT_TYPE_DIGITAL && (true == $ddpObj->canEdit($product_id, Product::CATALOG_TYPE_PRIMARY, 0, $this->siteLangId, true))
        ) {
            $canAttachDigitalDownload = 1;
        }

        /* $this->_template->addJs(array('js/jquery.datetimepicker.js'), false); */
        $this->_template->addJs(['js/select2.js', 'js/cropper.js', 'js/cropper-main.js']);
        $this->_template->addCss(['css/select2.min.css']);
        $this->set('product_type', $productRow['product_type']);
        $this->set('product_id', $product_id);
        $this->set('selprod_id', $selprod_id);
        $this->set('canAttachDigitalDownload', $canAttachDigitalDownload);
        $this->set('language', Language::getAllNames());
        $this->_template->render(true, true);
    }

    public function sellerProductGeneralForm($product_id, $selprod_id = 0)
    {
        $selprod_id = FatUtility::int($selprod_id);
        $product_id = FatUtility::int($product_id);
        if (!$product_id) {
            LibHelper::exitWithError(Labels::getLabel('ERR_INVALID_REQUEST', $this->siteLangId));
        }

        if (0 == $selprod_id && FatApp::getConfig('CONF_ENABLE_SELLER_SUBSCRIPTION_MODULE', FatUtility::VAR_INT, 0) && SellerProduct::getActiveCount($this->userParentId) >= SellerPackages::getAllowedLimit($this->userParentId, $this->siteLangId, 'ossubs_inventory_allowed')) {
            LibHelper::exitWithError(Labels::getLabel('ERR_YOU_HAVE_CROSSED_YOUR_PACKAGE_LIMIT', $this->siteLangId));
        }

        if (!UserPrivilege::isUserHasValidSubsription($this->userParentId)) {
            LibHelper::exitWithError(Labels::getLabel('ERR_PLEASE_BUY_SUBSCRIPTION', $this->siteLangId));
        }

        if ($selprod_id == 0 && !UserPrivilege::canSellerAddProductInCatalog($product_id, $this->userParentId)) {
            LibHelper::exitWithError(Labels::getLabel('LBL_PLEASE_UPGRADE_YOUR_PACKAGE_TO_ADD_NEW_PRODUCTS', $this->siteLangId), false);
        }

        $productRow = Product::getProductDataById($this->siteLangId, $product_id, array('IFNULL(product_name, product_identifier) as product_name', 'product_active', 'product_seller_id', 'product_added_by_admin_id', 'product_cod_enabled', 'product_type', 'product_approved', 'product_min_selling_price', 'product_fulfillment_type'));

        if (!$productRow) {
            LibHelper::exitWithError(Labels::getLabel('ERR_INVALID_REQUEST', $this->siteLangId));
        }

        if ($productRow['product_active'] != applicationConstants::ACTIVE) {
            LibHelper::exitWithError(Labels::getLabel('ERR_CATALOG_IS_NO_MORE_ACTIVE', $this->siteLangId), false);
        }

        if ($productRow['product_approved'] != applicationConstants::YES) {
            LibHelper::exitWithError(Labels::getLabel('ERR_CATALOG_IS_NOT_YET_APPROVED', $this->siteLangId), false);
        }

        if (($productRow['product_seller_id'] != $this->userParentId) && $productRow['product_added_by_admin_id'] == 0) {
            LibHelper::exitWithError(Labels::getLabel('ERR_INVALID_REQUEST', $this->siteLangId), false);
        }

        // $productLangRow = Product::getProductDataById($this->siteLangId, $product_id, array('product_identifier'));

        $productOptions = Product::getProductOptions($product_id, $this->siteLangId, true);

        $availableOptionsCount = 1;
        array_walk($productOptions, function ($val) use (&$availableOptionsCount) {
            $availableOptionsCount *= count($val['optionValues']);
        });

        $frmSellerProduct = $this->getSellerProductForm($product_id, $selprod_id, 'SELLER_PRODUCT', $availableOptionsCount);

        $sellerProductRow = ['selprod_fulfillment_type' => $productRow['product_fulfillment_type']];
        if ($selprod_id) {
            $sellerProductRow = SellerProduct::getAttributesById($selprod_id, null, true, true);
            if (!$sellerProductRow) {
                LibHelper::exitWithError(Labels::getLabel('ERR_INVALID_REQUEST', $this->siteLangId), false);
            }

            if ($sellerProductRow['selprod_user_id'] != $this->userParentId) {
                LibHelper::exitWithError(Labels::getLabel('ERR_INVALID_REQUEST', $this->siteLangId), false);
            }
            // $urlRewriteData = UrlRewrite::getAttributesById($sellerProductRow['selprod_urlrewrite_id']);
            $urlSrch = UrlRewrite::getSearchObject();
            $urlSrch->doNotCalculateRecords();
            $urlSrch->setPageSize(1);
            $urlSrch->addFld('urlrewrite_custom');
            $urlSrch->addCondition('urlrewrite_original', '=', 'products/view/' . $selprod_id);
            $rs = $urlSrch->getResultSet();
            $urlRow = FatApp::getDb()->fetch($rs);
            $sellerProductRow['selprod_url_keyword'] = '';
            if ($urlRow) {
                $data['urlrewrite_custom'] = $urlRow['urlrewrite_custom'];
                $customUrl = explode("/", $urlRow['urlrewrite_custom']);
                $sellerProductRow['selprod_url_keyword'] = $customUrl[0];
            }
            $sellerProductRow['selprod_cod_enabled'] = (0 < $productRow['product_cod_enabled'] ? $sellerProductRow['selprod_cod_enabled'] : 0);
        } else {
            $sellerProductRow['selprod_available_from'] = date('Y-m-d');
            $sellerProductRow['selprod_cod_enabled'] = $productRow['product_cod_enabled'];
            // $sellerProductRow['selprod_url_keyword'] = strtolower(CommonHelper::createSlug($productLangRow['product_identifier']));
        }

        $productWarranty = Product::getAttributesById($product_id, 'product_warranty', true);
        $sellerProductRow['product_warranty'] = FatUtility::int($productWarranty);

        $returnAge = isset($sellerProductRow['selprod_return_age']) ? FatUtility::int($sellerProductRow['selprod_return_age']) : '';
        $cancellationAge = isset($sellerProductRow['selprod_cancellation_age']) ? FatUtility::int($sellerProductRow['selprod_cancellation_age']) : '';

        if ('' === $returnAge || '' === $cancellationAge) {
            $sellerProductRow['use_shop_policy'] = 1;
        }

        if ($selprod_id > 0) {
            $languages = Language::getAllNames();
            foreach ($languages as $langId => $langName) {
                $langData = SellerProduct::getAttributesByLangId($langId, $selprod_id);
                $sellerProductRow['selprod_title' . $langId] = '';
                $sellerProductRow['selprod_comments' . $langId] = '';
                if (!empty($langData)) {
                    $sellerProductRow['selprod_title' . $langId] = $langData['selprod_title'];
                    $sellerProductRow['selprod_comments' . $langId] = $langData['selprod_comments'];
                }
            }
        } else {
            $sellerProductRow['selprod_title' . $this->siteLangId] = $productRow['product_name'];
        }

        $frmSellerProduct->fill($sellerProductRow);
        $shippedBySeller = 0;
        if (Product::isProductShippedBySeller($product_id, $productRow['product_seller_id'], $this->userParentId)) {
            $shippedBySeller = 1;
        }

        $availableOptions = array();
        if (SellerProduct::INVENTORY_RESTRICT_LIMIT >= $availableOptionsCount) {
            $optionCombinations = CommonHelper::combinationOfElementsOfArr($productOptions, 'optionValues', '_');
            foreach ($optionCombinations as $optionKey => $optionValue) {
                /* Check if product already added for this option [ */
                $selProdCode = $product_id . '_' . $optionKey;
                $selProdAvailable = Product::isSellProdAvailableForUser($selProdCode, $this->siteLangId, $this->userParentId);
                if (!empty($selProdAvailable) && !$selProdAvailable['selprod_deleted']) {
                    continue;
                }
                $availableOptions[$optionKey] = $optionValue;
                /* ] */
            }
        }
        $optionValues = array();
        if (isset($sellerProductRow['selprodoption_optionvalue_id'])) {
            foreach ($sellerProductRow['selprodoption_optionvalue_id'] as $opId => $op) {
                $optionValue = new OptionValue($op[$opId]);
                $option = $optionValue->getOptionValue($opId);
                if (isset($option['optionvalue_name' . $this->siteLangId]) && $option['optionvalue_name' . $this->siteLangId] != '') {
                    $optionValues[] = $option['optionvalue_name' . $this->siteLangId];
                }
            }
        }

        $editOptionKey = '0';
        if ($selprod_id > 0 && !empty($sellerProductRow['selprod_code'])) {
            $codePrefix = $product_id . '_';
            if (0 === strpos($sellerProductRow['selprod_code'], $codePrefix)) {
                $editOptionKey = substr($sellerProductRow['selprod_code'], strlen($codePrefix));
                if ('' === $editOptionKey) {
                    $editOptionKey = '0';
                }
            }
        }

        //$shipBySeller = SellerProduct::prodShipByseller($product_id);
        $shipBySeller = Product::isProductShippedBySeller($product_id, $productRow['product_seller_id'], UserAuthentication::getLoggedUserId());

        $inventoryForm = $this->inventoryOptionsForm();

        $this->set('inventoryForm', $inventoryForm);
        $this->set('shipBySeller', $shipBySeller);
        $this->set('optionValues', $optionValues);
        $this->set('availableOptions', $availableOptions);
        $this->set('productOptions', $productOptions);
        $this->set('editOptionKey', $editOptionKey);
        /* $this->_template->addJs(array('js/jquery.datetimepicker.js'), false); */
        $this->set('customActiveTab', 'GENERAL');
        $this->set('frmSellerProduct', $frmSellerProduct);
        $this->set('product_id', $product_id);
        $this->set('selprod_id', $selprod_id);
        $this->set('product_type', $productRow['product_type']);
        $this->set('shippedBySeller', $shippedBySeller);
        $this->set('productMinSellingPrice', $productRow['product_min_selling_price']);
        $this->set('language', Language::getAllNames());
        $this->set('activeTab', 'GENERAL');

        $autoCompleteTemp = (SellerProduct::INVENTORY_RESTRICT_LIMIT < $availableOptionsCount ? '-autocomplete' : '');

        $this->_template->render(false, false, 'seller/seller-product-general-form' . $autoCompleteTemp . '.php');
    }

    public function getOptions($productId)
    {
        $needle = FatApp::getPostedData('keyword', FatUtility::VAR_STRING, '');
        if (empty($needle)) {
            die(json_encode(['options' => []]));
        }

        $selectedOptions = FatApp::getPostedData('selectedOptions', FatUtility::VAR_STRING, '');
        if (!empty($selectedOptions) && true === LibHelper::isJson($selectedOptions)) {
            $selectedOptions = json_decode($selectedOptions, true);
        }

        $keywordData = [];
        if (!empty($needle) && false !== strpos($needle, '|')) {
            $keywordData = explode('|', $needle);
        } else if (!empty($needle) && false !== strpos($needle, '_')) {
            $keywordData = explode('_', $needle);
        }

        if (!empty($keywordData)) {
            $needle = $keywordData[0];
        }

        Product::setSearchOptionName(trim($needle));
        $productOptions = Product::getProductOptions($productId, $this->siteLangId, true);
        $invOptionsArr = CommonHelper::combinationOfElementsOfArr($productOptions, 'optionValues', '_');

        $availableOptions = array();
        foreach ($invOptionsArr as $optionKey => $optionValue) {
            $selProdCode = $productId . '_' . $optionKey;
            $selProdAvailable = Product::isSellProdAvailableForUser($selProdCode, $this->siteLangId, $this->userParentId);
            if (
                (!empty($selProdAvailable) && !$selProdAvailable['selprod_deleted']) ||
                (is_array($selectedOptions) && in_array($optionKey, $selectedOptions))
            ) {
                continue;
            }

            $haystack = strtolower($optionValue);

            if (!empty($keywordData)) {
                $keywordData = array_map('trim', $keywordData);
                $needle = implode('_', $keywordData);
            };
            if (!empty($needle) && false === strpos($haystack, trim(strtolower($needle)))) {
                continue;
            }

            $availableOptions[] = array(
                'id' => $optionKey,
                'name' => str_replace("_", " | ", $optionValue)
            );
        }

        die(json_encode(['options' => $availableOptions]));
    }

    public function addInvOption()
    {
        $this->setUpMultipleSellerProducts(1);
        $post = FatApp::getPostedData();
        $this->set('selprod_id', $this->selProdRecordId);
        $this->set('product_id', FatUtility::int($post['product_id'] ?? 0));
        $this->set('optionData', $post);
        $json['html'] = $this->_template->render(false, false, 'seller/add-inv-option.php', true);
        FatUtility::dieJsonSuccess($json);
    }

    /**
     * Modal form to attach images for a specific option combination on seller inventory form.
     */
    public function optionImageForm($productId, $optionKey = '0')
    {
        $productId = FatUtility::int($productId);
        $optionKey = trim((string) $optionKey);
        if ('' === $optionKey) {
            $optionKey = '0';
        }
        $this->validateSellerProductImageAccess($productId, $optionKey);

        $frm = new Form('optionImageFrm');
        $frm->addHiddenField('', 'record_id', $productId);
        $frm->addHiddenField('', 'option_id', $optionKey);
        $frm->addHiddenField('', 'file_type', AttachedFile::FILETYPE_PRODUCT_IMAGE);
        $languagesAssocArr = Language::getAllNames();
        if (count($languagesAssocArr) > 1) {
            $frm->addSelectBox(
                Labels::getLabel('FRM_LANGUAGE', $this->siteLangId),
                'lang_id',
                array(0 => Labels::getLabel('LBL_All_Languages', $this->siteLangId)) + $languagesAssocArr,
                '',
                array(),
                ''
            );
        } else {
            $frm->addHiddenField('', 'lang_id', array_key_first($languagesAssocArr));
        }
        $fld = $frm->addFileUpload(Labels::getLabel('FRM_UPLOAD', $this->siteLangId), 'prod_image');
        $imgDimension = ImageDimension::getProductImageData(ImageDimension::VIEW_ORIGINAL);
        $frm->addHiddenField('', 'min_width', $imgDimension[ImageDimension::WIDTH]);
        $frm->addHiddenField('', 'min_height', $imgDimension[ImageDimension::HEIGHT]);
        $frm->addHtml('', 'images', '');

        $optionLabel = Labels::getLabel('LBL_FOR_ALL_OPTIONS', $this->siteLangId);
        if ('0' !== $optionKey) {
            $optionCombinations = Product::getSeparateImageOptions($productId, $this->siteLangId);
            if (isset($optionCombinations[$optionKey])) {
                $optionLabel = $optionCombinations[$optionKey];
            } elseif (ctype_digit($optionKey) && isset($optionCombinations[(int) $optionKey])) {
                $optionLabel = $optionCombinations[(int) $optionKey];
            } else {
                $optionLabel = str_replace('_', ' | ', $optionKey);
            }
        }

        $this->set('frm', $frm);
        $this->set('product_id', $productId);
        $this->set('option_id', $optionKey);
        $this->set('optionLabel', $optionLabel);
        $this->set('html', $this->_template->render(false, false, 'seller/option-image-form.php', true));
        $this->_template->render(false, false, 'json-success.php', true, false);
    }

    public function optionImages($productId, $optionKey = '0', $langId = 0)
    {
        $productId = FatUtility::int($productId);
        $optionKey = trim((string) $optionKey);
        if ('' === $optionKey) {
            $optionKey = '0';
        }
        $this->validateSellerProductImageAccess($productId, $optionKey);

        $languages = Language::getAllNames();
        if (count($languages) <= 1) {
            $langId = array_key_first($languages);
        } else {
            $langId = FatUtility::int($langId);
        }

        $optionSubId = Product::encodeImageOptionSubId($optionKey);
        $images = AttachedFile::getMultipleAttachments(
            AttachedFile::FILETYPE_PRODUCT_IMAGE,
            $productId,
            $optionSubId,
            $langId,
            (count($languages) <= 1) ? true : false,
            0,
            0,
            true
        );

        $this->set('images', $images ?: []);
        $this->set('product_id', $productId);
        $this->set('option_id', $optionKey);
        $this->set('html', $this->_template->render(false, false, 'seller/option-images.php', true));
        $this->_template->render(false, false, 'json-success.php', true, false);
    }

    public function uploadOptionMedia()
    {
        $post = FatApp::getPostedData();
        if (empty($post)) {
            LibHelper::exitWithError(Labels::getLabel('ERR_INVALID_REQUEST_OR_FILE_NOT_SUPPORTED', $this->siteLangId), true);
        }
        if (!is_uploaded_file($_FILES['cropped_image']['tmp_name'] ?? '')) {
            LibHelper::exitWithError(Labels::getLabel('ERR_PLEASE_SELECT_A_FILE', $this->siteLangId), true);
        }

        $productId = FatUtility::int($post['record_id'] ?? 0);
        $optionKey = isset($post['option_id']) ? trim((string) $post['option_id']) : '0';
        if ('' === $optionKey) {
            $optionKey = '0';
        }
        $this->validateSellerProductImageAccess($productId, $optionKey);

        $languages = Language::getAllNames();
        if (count($languages) > 1) {
            $langId = FatUtility::int($post['lang_id'] ?? 0);
        } else {
            $langId = array_key_first($languages);
        }

        $optionSubId = Product::encodeImageOptionSubId($optionKey);
        $fileHandlerObj = new AttachedFile();
        if (!$fileHandlerObj->saveImage(
            $_FILES['cropped_image']['tmp_name'],
            AttachedFile::FILETYPE_PRODUCT_IMAGE,
            $productId,
            $optionSubId,
            $_FILES['cropped_image']['name'],
            -1,
            false,
            $langId
        )) {
            LibHelper::exitWithError($fileHandlerObj->getError(), true);
        }

        FatApp::getDb()->updateFromArray(
            'tbl_products',
            ['product_img_updated_on' => date('Y-m-d H:i:s')],
            ['smt' => 'product_id = ?', 'vals' => [$productId]]
        );

        $this->set('lang_id', $langId);
        $this->set('option_id', $optionKey);
        $this->set('product_id', $productId);
        $this->set('msg', Labels::getLabel('MSG_FILE_UPLOADED_SUCCESSFULLY', $this->siteLangId));
        $this->_template->render(false, false, 'json-success.php');
    }

    public function deleteOptionImage($productId, $imageId)
    {
        $productId = FatUtility::int($productId);
        $imageId = FatUtility::int($imageId);
        if (1 > $productId || 1 > $imageId) {
            LibHelper::exitWithError(Labels::getLabel('ERR_INVALID_REQUEST', $this->siteLangId), true);
        }

        $fileHandlerObj = new AttachedFile();
        $data = $fileHandlerObj::getAttributesById($imageId, ['afile_lang_id', 'afile_record_subid', 'afile_record_id', 'afile_type']);
        if (false == $data || (int) $data['afile_record_id'] !== $productId || (int) $data['afile_type'] !== AttachedFile::FILETYPE_PRODUCT_IMAGE) {
            LibHelper::exitWithError(Labels::getLabel('ERR_INVALID_REQUEST', $this->siteLangId), true);
        }

        $this->validateSellerProductImageAccess($productId);

        if (!$fileHandlerObj->deleteFile(AttachedFile::FILETYPE_PRODUCT_IMAGE, $productId, $imageId)) {
            LibHelper::exitWithError($fileHandlerObj->getError(), true);
        }

        $this->set('optionId', $data['afile_record_subid']);
        $this->set('langId', $data['afile_lang_id']);
        $this->set('msg', Labels::getLabel('LBL_Image_removed_successfully.', $this->siteLangId));
        $this->_template->render(false, false, 'json-success.php');
    }

    private function validateSellerProductImageAccess($productId, $optionKey = null)
    {
        $productId = FatUtility::int($productId);
        if (1 > $productId) {
            LibHelper::exitWithError(Labels::getLabel('ERR_INVALID_REQUEST', $this->siteLangId), true);
        }

        $productRow = Product::getAttributesById($productId, ['product_seller_id', 'product_added_by_admin_id', 'product_active', 'product_approved']);
        if (!$productRow) {
            LibHelper::exitWithError(Labels::getLabel('ERR_INVALID_REQUEST', $this->siteLangId), true);
        }

        $ownsProduct = ((int) $productRow['product_seller_id'] === (int) $this->userParentId);
        $isAdminCatalog = ((int) $productRow['product_added_by_admin_id'] > 0);
        if (!$ownsProduct && !$isAdminCatalog) {
            LibHelper::exitWithError(Labels::getLabel('ERR_INVALID_REQUEST', $this->siteLangId), true);
        }

        if (!$ownsProduct && !UserPrivilege::canSellerAddProductInCatalog($productId, $this->userParentId)) {
            /* Allow if seller already has inventory for this product */
            $srch = SellerProduct::getSearchObject();
            $srch->doNotCalculateRecords();
            $srch->setPageSize(1);
            $srch->addCondition('selprod_product_id', '=', $productId);
            $srch->addCondition('selprod_user_id', '=', $this->userParentId);
            $srch->addCondition('selprod_deleted', '=', applicationConstants::NO);
            if (!FatApp::getDb()->fetch($srch->getResultSet())) {
                LibHelper::exitWithError(Labels::getLabel('ERR_INVALID_REQUEST', $this->siteLangId), true);
            }
        }

        if (null !== $optionKey && '0' !== (string) $optionKey) {
            $optionValues = Product::getSeparateImageOptions($productId, $this->siteLangId);
            $optionKeyExists = array_key_exists($optionKey, $optionValues)
                || (ctype_digit((string) $optionKey) && array_key_exists((int) $optionKey, $optionValues));
            if (!$optionKeyExists) {
                LibHelper::exitWithError(Labels::getLabel('ERR_INVALID_REQUEST', $this->siteLangId), true);
            }
        }
    }

    private function inventoryOptionsForm()
    {
        $frm = new Form('frmInventoryOptionsForm');
        $frm->addSelectBox(Labels::getLabel('FRM_VARIANT/OPTION', $this->siteLangId), 'option_autocomplete', [], '', array('class' => 'optionname--js', 'placeholder' => Labels::getLabel('FRM_SELECT_OPTION', $this->siteLangId)));

        $fld = $frm->addFloatField(Labels::getLabel('FRM_COST_PRICE', $this->siteLangId) . CommonHelper::concatCurrencySymbolWithAmtLbl(), 'inv_option_cost');
        $fld->requirements()->setPositive();

        $fld = $frm->addFloatField(Labels::getLabel('FRM_SELLING_PRICE', $this->siteLangId) . CommonHelper::concatCurrencySymbolWithAmtLbl(), 'inv_option_sell_price');
        $fld->requirements()->setPositive();

        $frm->addRequiredField(Labels::getLabel('FRM_QUANTITY', $this->siteLangId), 'inv_option_stock');
        $frm->addRequiredField(Labels::getLabel('FRM_SKU', $this->siteLangId), 'inv_option_sku');

        $frm->addHiddenField('', 'inv_option_name');
        $frm->addHiddenField('', 'inv_option_id');
        $frm->addHiddenField('', 'inv_option_selprod_id');
        $frm->addButton('', 'btn_attach_image', Labels::getLabel('LBL_IMAGES', $this->siteLangId));
        $frm->addSubmitButton('', 'btn_submit', Labels::getLabel('BTN_ADD', $this->siteLangId));
        $frm->addButton('', 'btn_clear', Labels::getLabel('BTN_CLEAR', $this->siteLangId));
        return $frm;
    }

    public function validatePostedData($post)
    {
        $selprod_id = FatUtility::int($post['selprod_id']);
        $selprod_product_id = FatUtility::int($post['selprod_product_id']);

        if (!UserPrivilege::isUserHasValidSubsription($this->userParentId)) {
            Message::addErrorMessage(Labels::getLabel("ERR_Please_buy_subscription", $this->siteLangId));
            FatUtility::dieWithError(Message::getHtml());
        }

        if (!$selprod_product_id) {
            Message::addErrorMessage(Labels::getLabel('ERR_INVALID_REQUEST', $this->siteLangId));
            FatUtility::dieWithError(Message::getHtml());
        }
        $productRow = Product::getAttributesById($selprod_product_id, array('product_id', 'product_active', 'product_seller_id', 'product_added_by_admin_id', 'product_type'));
        if (!$productRow) {
            Message::addErrorMessage(Labels::getLabel('ERR_INVALID_REQUEST', $this->siteLangId));
            FatUtility::dieWithError(Message::getHtml());
        }
        if (($productRow['product_seller_id'] != $this->userParentId) && $productRow['product_added_by_admin_id'] == 0) {
            Message::addErrorMessage(Labels::getLabel('ERR_INVALID_REQUEST', $this->siteLangId));
            FatUtility::dieWithError(Message::getHtml());
        }

        if ($productRow['product_type'] == Product::PRODUCT_TYPE_DIGITAL && $post['selprod_max_download_times'] == 0) {
            Message::addErrorMessage(Labels::getLabel('ERR_DOWNLOAD_TIMES_MUST_BE_-1_OR_GREATER_THAN_ZERO', $this->siteLangId));
            FatUtility::dieWithError(Message::getHtml());
        }

        if ($productRow['product_type'] == Product::PRODUCT_TYPE_DIGITAL && $post['selprod_download_validity_in_days'] == 0) {
            Message::addErrorMessage(Labels::getLabel('ERR_DOWNLOAD_VALIDITY_MUST_BE_-1_OR_GREATER_THAN_ZERO', $this->siteLangId));
            FatUtility::dieWithError(Message::getHtml());
        }

        $selProdCode = $productRow['product_id'] . '_';
        $post['selprod_code'] = $selProdCode;

        /* Validate product belongs to current logged seller[ */
        if ($selprod_id) {
            $sellerProductRow = SellerProduct::getAttributesById($selprod_id, array('selprod_user_id'));
            if ($sellerProductRow['selprod_user_id'] != $this->userParentId) {
                Message::addErrorMessage(Labels::getLabel('ERR_INVALID_ACCESS', $this->siteLangId));
                FatUtility::dieWithError(Message::getHtml());
            }
        }
        /* ] */

        if ((isset($post['selprod_url_keyword']) && !empty($post['selprod_url_keyword'])) || $selprod_id) {
            $post['selprod_url_keyword'] = strtolower(CommonHelper::createSlug($post['selprod_url_keyword']));
        }

        if (isset($post['selprod_track_inventory']) && $post['selprod_track_inventory'] == Product::INVENTORY_NOT_TRACK) {
            $post['selprod_threshold_stock_level'] = 0;
        }

        if (!$selprod_id) {
            $post['selprod_user_id'] = $this->userParentId;
            $post['selprod_added_on'] = date("Y-m-d H:i:s");
        }
        return $post;
    }

    public function setUpSellerProduct()
    {
        $this->userPrivilege->canEditProducts(UserAuthentication::getLoggedUserId());
        $post = $this->validatePostedData(FatApp::getPostedData());
        $selprod_id = FatUtility::int($post['selprod_id']);

        $selprod_url_keyword = '';
        if ($selprod_id > 0) {
            unset($post['selprod_code']);
            $selprod_url_keyword = SellerProduct::getAttributesById($selprod_id, 'selprod_url_keyword');
            $srch = new SearchBase(SellerProductSpecialPrice::DB_TBL);
            $srch->addCondition('splprice_selprod_id', '=', 'mysql_func_' . $selprod_id, 'AND', true);
            $srch->addCondition('splprice_price', '>=', $post['selprod_price']);
            $srch->addCondition('splprice_end_date', '>=', date('Y-m-d H:i:s'));
            $srch->addFld('splprice_price');
            $srch->addOrder('splprice_price', 'DESC');
            $srch->doNotCalculateRecords();
            $srch->setPageSize(1);
            $db = FatApp::getDb();
            $rs = $srch->getResultSet();
            $result = $db->fetch($rs);
            if (is_array($result) && !empty($result)) {
                $price = CommonHelper::displayMoneyFormat($result['splprice_price']);
                $msg = Labels::getLabel('MSG_SELLING_PRICE_MUST_BE_GREATER_THAN_SPECIAL_PRICE_{SPECIAL-PRICE}', $this->siteLangId);
                $msg = CommonHelper::replaceStringData($msg, ['{SPECIAL-PRICE}' => $price]);
                Message::addErrorMessage($msg);
                FatUtility::dieWithError(Message::getHtml());
            }
        }

        $post['selprod_subtract_stock'] = FatApp::getPostedData('selprod_subtract_stock', FatUtility::VAR_INT, 0);
        $post['selprod_track_inventory'] = FatApp::getPostedData('selprod_track_inventory', FatUtility::VAR_INT, 0);
        $post['selprod_hide_price'] = (SellerProduct::CART_TYPE_RFQ_ONLY != FatApp::getPostedData('selprod_cart_type', FatUtility::VAR_INT, 0) ? 0 : FatApp::getPostedData('selprod_hide_price', FatUtility::VAR_INT, 0));

        $selprodStock = FatApp::getPostedData('selprod_stock', FatUtility::VAR_INT, 1);
        $post['selprod_stock'] = (0 < $selprodStock) ? $selprodStock : 1;

        $minOrderQty = FatApp::getPostedData('selprod_min_order_qty', FatUtility::VAR_INT, 1);
        $post['selprod_min_order_qty'] = 0 < $minOrderQty ? $minOrderQty : 1;

        // $siteDefaultLangId = FatApp::getConfig('conf_default_site_lang', FatUtility::VAR_INT, 1);
        $siteDefaultLangId = $this->siteLangId;

        $keywordSlug = '';
        $productId = SellerProduct::getAttributesById($selprod_id, 'selprod_product_id', false);
        if (empty($post['selprod_title' . $siteDefaultLangId])) {
            $productLangRow = Product::getProductDataById($this->siteLangId, $productId, array('product_identifier', 'product_name'));
            $keywordSlug = $productLangRow['product_name'] ?? $productLangRow['product_identifier'];
        }
        $keywordSlug =  $post['selprod_title' . $siteDefaultLangId] ?? $keywordSlug;

        if ($selprod_url_keyword == '') {
            $shopData = Shop::getAttributesByUserId($this->userParentId, ['COALESCE(shop_name,shop_identifier) as shop_name'], false, $this->userParentId);
            if (0 < $selprod_id) {
                $options = SellerProduct::getSellerProductOptions($selprod_id, true, $this->siteLangId);
                foreach ($options as $optionValue) {
                    $keywordSlug .= '-' . $optionValue['optionvalue_name'];
                }
            }

            $keywordSlug = $keywordSlug . '-' . $shopData['shop_name'];
            $post['selprod_url_keyword'] = strtolower(CommonHelper::createSlug($keywordSlug));
        }
        $post['selprod_active'] = FatApp::getPostedData('selprod_active', FatUtility::VAR_INT, 0);
        $data_to_be_save = $post;
        $sellerProdObj = new SellerProduct($selprod_id);
        $sellerProdObj->assignValues($data_to_be_save);
        if (!$sellerProdObj->save()) {
            Message::addErrorMessage(Labels::getLabel($sellerProdObj->getError(), $this->siteLangId));
            FatUtility::dieWithError(Message::getHtml());
        }

        $selprod_id = $sellerProdObj->getMainTableRecordId();

        $selProdSpecificsObj = new SellerProductSpecifics($selprod_id);
        $useShopPolicy = FatApp::getPostedData('use_shop_policy', FatUtility::VAR_INT, 0);
        if (0 < $useShopPolicy) {
            if (!$selProdSpecificsObj->deleteRecord()) {
                FatUtility::dieJsonError($selProdSpecificsObj->getError());
            }
        } else {
            $post['sps_selprod_id'] = $selprod_id;
            $selProdSpecificsObj->assignValues($post);
            $data = $selProdSpecificsObj->getFlds();
            if (!$selProdSpecificsObj->addNew(array(), $data)) {
                FatUtility::dieJsonError($selProdSpecificsObj->getError());
            }
        }

        $sellerProdObj->rewriteUrlProduct($post['selprod_url_keyword']);
        $sellerProdObj->rewriteUrlReviews($post['selprod_url_keyword']);
        $sellerProdObj->rewriteUrlMoreSellers($post['selprod_url_keyword']);

        /* Add Meta data automatically[ */
        if (0 == FatApp::getPostedData('selprod_id', Fatutility::VAR_INT, 0)) {
            if (!$sellerProdObj->saveMetaData()) {
                Message::addErrorMessage($sellerProdObj->getError());
                FatUtility::dieWithError(Message::getHtml());
            }
        }
        /* ] */



        /* Update seller product language data[ */
        $languages = Language::getAllNames();
        foreach ($languages as $langId => $langName) {
            if (!empty($post['selprod_title' . $langId])) {
                $selProdData = array(
                    'selprodlang_selprod_id' => $selprod_id,
                    'selprodlang_lang_id' => $langId,
                    'selprod_title' => $post['selprod_title' . $langId],
                    'selprod_comments' => $post['selprod_comments' . $langId],
                );

                if (!$sellerProdObj->updateLangData($langId, $selProdData)) {
                    Message::addErrorMessage(Labels::getLabel($sellerProdObj->getError(), $this->siteLangId));
                    FatUtility::dieWithError(Message::getHtml());
                }
            }
        }

        $autoUpdateOtherLangsData = FatApp::getPostedData('auto_update_other_langs_data', FatUtility::VAR_INT, 0);
        if (0 < $autoUpdateOtherLangsData) {
            $updateLangDataobj = new TranslateLangData(SellerProduct::DB_TBL_LANG);
            if (false === $updateLangDataobj->updateTranslatedData($selprod_id)) {
                LibHelper::exitWithError($updateLangDataobj->getError(), true);
            }
        }
        /* ] */

        $productId = SellerProduct::getAttributesById($selprod_id, 'selprod_product_id', false);
        CalculativeDataRecord::updateThresholdSelprodRequestCount();
        Product::updateMinPrices($productId);
        $this->set('selprod_id', $selprod_id);
        $this->set('product_id', $productId);
        $this->set('msg', Labels::getLabel('MSG_PRODUCT_SETUP_SUCCESSFUL', $this->siteLangId));
        $this->_template->render(false, false, 'json-success.php');
    }

    private function deleteSelProdWithoutOptions($productId, $userId)
    {
        $productId = FatUtility::int($productId);
        $userId = FatUtility::int($userId);
        if (!$productId || !$userId) {
            FatUtility::dieJsonError(Labels::getLabel('ERR_INVALID_REQUEST', CommonHelper::getLangId()));
        }
        $srch = SellerProduct::getSearchObject();
        $srch->joinTable(SellerProduct::DB_TBL_SELLER_PROD_OPTIONS, 'LEFT JOIN', 'selprod_id = selprodoption_selprod_id', 'tspo');
        $srch->addCondition('selprod_product_id', '=', 'mysql_func_' . $productId, 'AND', true);
        $srch->addCondition('selprod_user_id', '=', 'mysql_func_' . $userId, 'AND', true);
        $srch->addCondition('selprod_deleted', '=', 'mysql_func_' . applicationConstants::NO, 'AND', true);
        $srch->addMultipleFields(array('selprod_id', 'selprodoption_optionvalue_id'));
        $srch->doNotCalculateRecords();
        $srch->setPageSize(1);
        $rs = $srch->getResultSet();
        $row = FatApp::getDb()->fetch($rs);
        if (empty($row)) {
            return true;
        }
        if (empty($row['selprodoption_optionvalue_id'])) {
            $this->deleteSellerProduct($row['selprod_id']);
        }
    }

    private function addOption(int $selprod_id, array $data_to_be_save, string $optionValue)
    {
        $post = $this->validatePostedData(FatApp::getPostedData());
        $useShopPolicy = FatApp::getPostedData('use_shop_policy', FatUtility::VAR_INT, 0);
        $sellerProdObj = new SellerProduct($selprod_id);
        $sellerProdObj->assignValues($data_to_be_save);
        if (!$sellerProdObj->save()) {
            $msg = $sellerProdObj->getError();
            if (false !== strpos(strtolower($msg), 'duplicate')) {
                $msg = Labels::getLabel('MSG_DUPLICATE_RECORD', $this->siteLangId);
            }
            FatUtility::dieJsonError($msg);
        }

        $this->selProdRecordId = $sellerProdObj->getMainTableRecordId();

        /* save options data, if any [ */
        $options = array();
        $optionValues = explode("_", $optionValue);
        foreach ($optionValues as $optionValueId) {
            $optionId = OptionValue::getAttributesById($optionValueId, 'optionvalue_option_id', false);
            $options[$optionId] = $optionValueId;
        }
        asort($options);
        if (!$sellerProdObj->addUpdateSellerProductOptions($this->selProdRecordId, $options)) {
            FatUtility::dieJsonError($sellerProdObj->getError());
        }
        /* ] */

        $selProdSpecificsObj = new SellerProductSpecifics($this->selProdRecordId);
        if (0 < $useShopPolicy) {
            if (!$selProdSpecificsObj->deleteRecord()) {
                FatUtility::dieJsonError($selProdSpecificsObj->getError());
            }
        } else {
            $post['sps_selprod_id'] = $this->selProdRecordId;
            $selProdSpecificsObj->assignValues($post);
            $data = $selProdSpecificsObj->getFlds();
            if (!$selProdSpecificsObj->addNew(array(), $data)) {
                FatUtility::dieJsonError($selProdSpecificsObj->getError());
            }
        }
        $post['selprod_url_keyword'] = $post['selprod_url_keyword'] ?? $data_to_be_save['selprod_url_keyword'];

        $sellerProdObj->rewriteUrlProduct($post['selprod_url_keyword']);
        $sellerProdObj->rewriteUrlReviews($post['selprod_url_keyword']);
        $sellerProdObj->rewriteUrlMoreSellers($post['selprod_url_keyword']);

        /* Add Meta data automatically[ */
        if (!$sellerProdObj->saveMetaData()) {
            FatUtility::dieJsonError($sellerProdObj->getError());
        }
        /* ] */

        /* Update seller product language data[ */
        $languages = Language::getAllNames();
        foreach ($languages as $langId => $langName) {
            if (empty($post['selprod_title' . $langId])) {
                continue;
            }
            $selProdData = array(
                'selprodlang_selprod_id' => $this->selProdRecordId,
                'selprodlang_lang_id' => $langId,
                'selprod_title' => $post['selprod_title' . $langId],
                'selprod_comments' => isset($post['selprod_comments' . $langId]) ? $post['selprod_comments' . $langId] : '',
            );

            if (!$sellerProdObj->updateLangData($langId, $selProdData)) {
                FatUtility::dieJsonError($sellerProdObj->getError());
            }
        }
        /* ] */
    }

    public function viewProdOptions(int $product_id)
    {
        if (1 > $product_id) {
            FatUtility::dieJsonError(Labels::getLabel('ERR_INVALID_REQUEST', $this->siteLangId));
        }

        $productOptions = Product::getProductOptions($product_id, $this->siteLangId, true);
        if (empty($productOptions)) {
            FatUtility::dieJsonError(Labels::getLabel('ERR_NO_RECORD_FOUND', $this->siteLangId));
        }

        $this->set('productOptions', $productOptions);
        $json['html'] = $this->_template->render(false, false, 'seller/prod-options.php', true);
        FatUtility::dieJsonSuccess($json);
    }

    public function setUpMultipleSellerProducts(int $return = 0)
    {
        $this->userPrivilege->canEditProducts(UserAuthentication::getLoggedUserId());
        $post = $this->validatePostedData(FatApp::getPostedData());

        $productOptions = Product::getProductOptions($post['selprod_product_id'], $this->siteLangId, true);
        if (empty($productOptions)) {
            FatUtility::dieJsonError(Labels::getLabel('ERR_INVALID_REQUEST', $this->siteLangId));
        }

        $availableOptionsCount = 1;
        array_walk($productOptions, function ($val) use (&$availableOptionsCount) {
            $availableOptionsCount *= count($val['optionValues']);
        });

        $productId = FatApp::getPostedData('selprod_product_id', FatUtility::VAR_INT, 0);
        $data_to_be_save = $post;
        $this->deleteSelProdWithoutOptions($productId, $this->userParentId);
        $selprod_id = FatApp::getPostedData('selprod_id', FatUtility::VAR_INT, 0);
        $errorMsg = '';
        $prodAllowedLimit = -1;
        if (FatApp::getConfig('CONF_ENABLE_SELLER_SUBSCRIPTION_MODULE', FatUtility::VAR_INT, 0)) {
            $prodAllowedLimit = SellerPackages::getAllowedLimit($this->userParentId, $this->siteLangId, 'ossubs_inventory_allowed');
        }
        $prodInfo = Product::getAttributesById($productId, ['product_min_selling_price', 'product_type']);
        $minSellingPrice = $prodInfo['product_min_selling_price'] ?? 0;
        $productCount = SellerProduct::getActiveCount($this->userParentId);

        $keywordSlug = '';
        if (empty($post['selprod_title' . $this->siteLangId])) {
            $productLangRow = Product::getProductDataById($this->siteLangId, $productId, array('product_identifier', 'product_name'));
            $keywordSlug = $productLangRow['product_name'] ?? $productLangRow['product_identifier'];
        }

        $keywordSlug =  $post['selprod_title' . $this->siteLangId] ?? $keywordSlug;

        $shopData = Shop::getAttributesByUserId($this->userParentId, ['COALESCE(shop_name,shop_identifier) as shop_name'], false, $this->userParentId);

        if (SellerProduct::INVENTORY_RESTRICT_LIMIT >= $availableOptionsCount) {
            $optionCombinations = CommonHelper::combinationOfElementsOfArr($productOptions, 'optionValues', '_');
            foreach ($optionCombinations as $optionKey => $optionValue) {
                /* Check if product already added for this option [ */
                $selProdCode = $post['selprod_code'] . $optionKey;
                $post['selprod_stock' . $optionKey] = $post['selprod_stock' . $optionKey] ?? 1;

                $selProdAvailable = Product::isSellProdAvailableForUser($selProdCode, $this->siteLangId, $this->userParentId);
                if (!empty($selProdAvailable)) {
                    if (!$selProdAvailable['selprod_deleted']) {
                        /* $error = true;
                          Message::addErrorMessage($optionValue . ' ' . Labels::getLabel('MSG_ALREADY_ADDED', $this->siteLangId)); */
                        continue;
                    }
                    $data_to_be_save['selprod_deleted'] = applicationConstants::NO;
                }

                if (!isset($post['selprod_cost' . $optionKey]) || !isset($post['selprod_price' . $optionKey])) {
                    continue;
                }

                $sellingPrice = $post['selprod_price' . $optionKey];
                if ($minSellingPrice > $sellingPrice) {
                    $errorMsg = Labels::getLabel('MSG_FOR_{VARIANT}_VARIANT_YOU_CANNOT_ADD_LESS_THEN_MINIMUM_SELLING_PRICE_{MINIMUM-SELLING-PRICE}', $this->siteLangId);
                    $errorMsg = CommonHelper::replaceStringData($errorMsg, ['{VARIANT}' => $optionValue, '{MINIMUM-SELLING-PRICE}' => $minSellingPrice]);
                    continue;
                }
                if (SellerProduct::MAX_RANGE_OF_AVAILBLE_QTY < $post['selprod_stock' . $optionKey]) {
                    $post['selprod_stock' . $optionKey] = SellerProduct::MAX_RANGE_OF_AVAILBLE_QTY;
                }

                $productCount++;
                if (-1 != $prodAllowedLimit && $prodAllowedLimit < $productCount) {
                    $data_to_be_save['selprod_active'] = applicationConstants::INACTIVE;
                }
                $data_to_be_save['selprod_code'] = $selProdCode;
                $data_to_be_save['selprod_cost'] = $post['selprod_cost' . $optionKey];
                $data_to_be_save['selprod_price'] = $sellingPrice;
                if ($prodInfo['product_type'] == Product::PRODUCT_TYPE_SERVICE) {
                    $data_to_be_save['selprod_stock'] = 1;
                    $data_to_be_save['selprod_min_order_qty'] = 1;
                    $data_to_be_save['selprod_subtract_stock'] = 0;
                    $data_to_be_save['selprod_track_inventory'] = 0;
                    $data_to_be_save['selprod_threshold_stock_level'] = 0;
                    $data_to_be_save['selprod_condition'] = 1;
                } else {
                    $data_to_be_save['selprod_stock'] = $post['selprod_stock' . $optionKey];
                }
                $data_to_be_save['selprod_sku'] = $post['selprod_sku' . $optionKey] ?? '';

                $selProdKeywordSlug = $keywordSlug . '-' . $optionValue . '-' . $shopData['shop_name'];
                $data_to_be_save['selprod_url_keyword'] = strtolower(CommonHelper::createSlug($selProdKeywordSlug));
                $this->addOption($selprod_id, $data_to_be_save, $optionKey);
            }
        } else {
            $optionValue = FatApp::getPostedData('inv_option_id', FatUtility::VAR_STRING, '');
            if (!empty($optionValue) && false === Product::validateProductOptionValue($post['selprod_product_id'], $optionValue)) {
                FatUtility::dieJsonError(Labels::getLabel('ERR_INVALID_REQUEST', $this->siteLangId));
            }

            if (1 > $selprod_id && SellerProduct::INVENTORY_RESTRICT_LIMIT < $availableOptionsCount) {
                $selprod_id = FatApp::getPostedData('inv_option_selprod_id', FatUtility::VAR_INT, 0);
            }

            $selProdCode = $post['selprod_code'] . $optionValue;
            $data_to_be_save['selprod_deleted'] = applicationConstants::NO;

            if (!isset($post['selprod_cost' . $optionValue]) || !isset($post['selprod_price' . $optionValue]) || !isset($post['selprod_stock' . $optionValue])) {
                FatUtility::dieJsonError(Labels::getLabel('ERR_INVALID_REQUEST', $this->siteLangId));
            }

            if (SellerProduct::MAX_RANGE_OF_AVAILBLE_QTY < $post['selprod_stock' . $optionValue]) {
                $post['selprod_stock' . $optionValue] = SellerProduct::MAX_RANGE_OF_AVAILBLE_QTY;
            }

            if (-1 != $prodAllowedLimit && $prodAllowedLimit < $productCount) {
                $data_to_be_save['selprod_active'] = applicationConstants::INACTIVE;
            }

            $data_to_be_save['selprod_code'] = $selProdCode;
            $data_to_be_save['selprod_cost'] = $post['selprod_cost' . $optionValue];
            $data_to_be_save['selprod_price'] = $post['selprod_price' . $optionValue];
            if ($prodInfo['product_type'] == Product::PRODUCT_TYPE_SERVICE) {
                $data_to_be_save['selprod_stock'] = 1;
                $data_to_be_save['selprod_min_order_qty'] = 1;
                $data_to_be_save['selprod_subtract_stock'] = 0;
                $data_to_be_save['selprod_track_inventory'] = 0;
                $data_to_be_save['selprod_threshold_stock_level'] = 0;
                $data_to_be_save['selprod_condition'] = 1;
            } else {
                $data_to_be_save['selprod_stock'] = $post['selprod_stock' . $optionValue];
            }
            $data_to_be_save['selprod_sku'] = $post['selprod_sku' . $optionValue];
            $selProdKeywordSlug = $keywordSlug . '-' . $optionValue . '-' . $shopData['shop_name'];
            $data_to_be_save['selprod_url_keyword'] = strtolower(CommonHelper::createSlug($selProdKeywordSlug));
            $this->addOption($selprod_id, $data_to_be_save, $optionValue);
        }

        Product::updateMinPrices($productId);

        /* Return if called in other function; */
        if (0 < $return) {
            return;
        }

        if (!empty($errorMsg)) {
            FatUtility::dieJsonError($errorMsg);
        }
        $this->set('product_id', $productId);
        $this->set('msg', Labels::getLabel('MSG_PRODUCT_SETUP_SUCCESSFUL', $this->siteLangId));
        $this->_template->render(false, false, 'json-success.php');
    }

    public function checkSellProdAvailableForUser()
    {
        $post = FatApp::getPostedData();
        $selprod_id = Fatutility::int($post['selprod_id']);
        $selprod_product_id = Fatutility::int($post['selprod_product_id']);

        $productRow = Product::getAttributesById($selprod_product_id, array('product_id', 'product_active', 'product_seller_id', 'product_added_by_admin_id'));
        if (!$productRow) {
            FatUtility::dieJsonError(Labels::getLabel('ERR_INVALID_REQUEST', $this->siteLangId));
        }
        if (($productRow['product_seller_id'] != $this->userParentId) && $productRow['product_added_by_admin_id'] == 0) {
            Message::addErrorMessage(Labels::getLabel('ERR_INVALID_REQUEST', $this->siteLangId));
            FatApp::redirectUser($_SESSION['referer_page_url']);
        }

        $options = array();
        if (isset($post['selprodoption_optionvalue_id']) && count($post['selprodoption_optionvalue_id'])) {
            $options = $post['selprodoption_optionvalue_id'];
            unset($post['selprodoption_optionvalue_id']);
        }
        asort($options);
        $selProdCode = $productRow['product_id'] . '_' . implode('_', $options);

        $selProdAvailable = Product::isSellProdAvailableForUser($selProdCode, $this->siteLangId, $this->userParentId, $selprod_id);

        if (!empty($selProdAvailable) && !$selProdAvailable['selprod_deleted']) {
            FatUtility::dieJsonError(Labels::getLabel("ERR_INVENTORY_FOR_THIS_OPTION_HAVE_BEEN_ADDED", $this->siteLangId));
        }
        FatUtility::dieJsonSuccess(Labels::getLabel('LBL_SUCCESS'));
    }

    private function getSellerProductLangForm($formLangId, $selprod_id = 0)
    {
        $formLangId = FatUtility::int($formLangId);

        $frm = new Form('frmSellerProductLang');
        /* $frm->addSelectBox(Labels::getLabel('FRM_LANGUAGE', $formLangId), 'lang_id', Language::getAllNames(), $formLangId, array(), ''); */
        $frm->addRequiredField(Labels::getLabel('FRM_PRODUCT_DISPLAY_TITLE', $formLangId), 'selprod_title');
        $frm->addTextArea(Labels::getLabel('FRM_ANY_EXTRA_COMMENT_FOR_BUYER', $formLangId), 'selprod_comments');
        $frm->addHiddenField('', 'lang_id', $formLangId);
        $frm->addHiddenField('', 'selprod_product_id');
        $frm->addHiddenField('', 'selprod_id', $selprod_id);

        $siteLangId = FatApp::getConfig('conf_default_site_lang', FatUtility::VAR_INT, 1);
        $translatorSubscriptionKey = FatApp::getConfig('CONF_TRANSLATOR_SUBSCRIPTION_KEY', FatUtility::VAR_STRING, '');

        if (!empty($translatorSubscriptionKey) && $formLangId == $siteLangId) {
            $frm->addCheckBox(Labels::getLabel('FRM_UPDATE_OTHER_LANGUAGES_DATA', $formLangId), 'auto_update_other_langs_data', 1, array(), false, 0);
        }

        $fld1 = $frm->addSubmitButton('', 'btn_submit', Labels::getLabel('BTN_SAVE_CHANGES', $formLangId));
        $fld2 = $frm->addButton('', 'btn_cancel', Labels::getLabel('BTN_CANCEL', $formLangId), array('onclick' => 'cancelForm(this)'));
        $fld1->attachField($fld2);
        return $frm;
    }

    public function sellerProductLangForm($langId, $selprod_id, $autoFillLangData = 0)
    {
        $langId = FatUtility::int($langId);
        $selprod_id = FatUtility::int($selprod_id);

        if ($langId == 0 || $selprod_id == 0) {
            Message::addErrorMessage(Labels::getLabel('ERR_INVALID_REQUEST', $this->siteLangId));
            FatApp::redirectUser($_SESSION['referer_page_url']);
        }

        $sellerProductRow = SellerProduct::getAttributesById($selprod_id);
        if (!$sellerProductRow) {
            Message::addErrorMessage(Labels::getLabel('ERR_INVALID_REQUEST', $this->siteLangId));
            FatApp::redirectUser($_SESSION['referer_page_url']);
        }

        if ($sellerProductRow['selprod_user_id'] != $this->userParentId) {
            Message::addErrorMessage(Labels::getLabel('ERR_INVALID_ACCESS', $this->siteLangId));
            FatApp::redirectUser($_SESSION['referer_page_url']);
        }

        $frmSellerProdLangFrm = $this->getSellerProductLangForm($langId, $selprod_id);
        if (0 < $autoFillLangData) {
            $updateLangDataobj = new TranslateLangData(SellerProduct::DB_TBL_LANG);
            $translatedData = $updateLangDataobj->getTranslatedData($selprod_id, $langId);
            if (false === $translatedData) {
                Message::addErrorMessage($updateLangDataobj->getError());
                FatUtility::dieWithError(Message::getHtml());
            }
            $langData = current($translatedData);
        } else {
            $langData = SellerProduct::getAttributesByLangId($langId, $selprod_id);
        }
        $langData['selprod_product_id'] = $sellerProductRow['selprod_product_id'];

        $productRow = Product::getAttributesById($sellerProductRow['selprod_product_id'], array('product_type'));
        /* $langData['selprod_title'] = array_key_exists('selprod_title', $langData) ? $langData['selprod_title'] : SellerProduct::getProductDisplayTitle($selprod_id, $langId); */
        if ($langData) {
            $frmSellerProdLangFrm->fill($langData);
        }
        $this->set('customActiveTab', '');
        $this->set('frmSellerProdLangFrm', $frmSellerProdLangFrm);
        $this->set('product_id', $sellerProductRow['selprod_product_id']);
        $this->set('selprod_id', $selprod_id);
        $this->set('formLangId', $langId);
        $this->set('product_type', $productRow['product_type']);
        $this->set('formLayout', Language::getLayoutDirection($langId));
        $this->set('language', Language::getAllNames());
        $this->set('activeTab', 'GENERAL');
        $this->_template->render(false, false);
    }

    public function setUpSellerProductLang()
    {
        $this->userPrivilege->canEditProducts(UserAuthentication::getLoggedUserId());
        $post = FatApp::getPostedData();
        $selprod_id = Fatutility::int($post['selprod_id']);
        $lang_id = Fatutility::int($post['lang_id']);
        $selprod_product_id = Fatutility::int($post['selprod_product_id']);

        if ($selprod_id == 0 || $selprod_product_id == 0 || $lang_id == 0) {
            Message::addErrorMessage(Labels::getLabel('ERR_INVALID_REQUEST', $this->siteLangId));
            FatApp::redirectUser($_SESSION['referer_page_url']);
        }

        $frm = $this->getSellerProductLangForm($lang_id, $selprod_id);
        $post = $frm->getFormDataFromArray($post);

        if (false === $post) {
            Message::addErrorMessage(current($frm->getValidationErrors()));
            FatApp::redirectUser($_SESSION['referer_page_url']);
        }

        $sellerProductRow = SellerProduct::getAttributesById($selprod_id, array('selprod_user_id'));
        if ($sellerProductRow['selprod_user_id'] != $this->userParentId) {
            Message::addErrorMessage(Labels::getLabel('ERR_INVALID_ACCESS', $this->siteLangId));
            FatApp::redirectUser($_SESSION['referer_page_url']);
        }

        $data = array(
            'selprodlang_selprod_id' => $selprod_id,
            'selprodlang_lang_id' => $lang_id,
            'selprod_title' => $post['selprod_title'],
            /* 'selprod_warranty' => $post['selprod_warranty'],
              'selprod_return_policy' => $post['selprod_return_policy'], */
            'selprod_comments' => $post['selprod_comments'],
        );

        $obj = new SellerProduct($selprod_id);
        if (!$obj->updateLangData($lang_id, $data)) {
            FatUtility::dieJsonError(Labels::getLabel($obj->getError(), $this->siteLangId));
        }

        $autoUpdateOtherLangsData = FatApp::getPostedData('auto_update_other_langs_data', FatUtility::VAR_INT, 0);
        if (0 < $autoUpdateOtherLangsData) {
            $updateLangDataobj = new TranslateLangData(SellerProduct::DB_TBL_LANG);
            if (false === $updateLangDataobj->updateTranslatedData($selprod_id)) {
                Message::addErrorMessage($updateLangDataobj->getError());
                FatUtility::dieWithError(Message::getHtml());
            }
        }

        $newTabLangId = 0;
        if ($selprod_id > 0) {
            $languages = Language::getAllNames();
            foreach ($languages as $langId => $langName) {
                if ($langId > $lang_id) {
                    $newTabLangId = $langId;
                    break;
                }
                /* if (!$row = SellerProduct::getAttributesByLangId($langId, $selprod_id)) {
                  $newTabLangId = $langId;
                  break;
                  } */
            }
        }

        $this->set('selprod_id', $selprod_id);
        $this->set('product_id', $selprod_product_id);
        $this->set('langId', $newTabLangId);
        $this->set('msg', Labels::getLabel('MSG_Setup_Successful', $this->siteLangId));
        $this->_template->render(false, false, 'json-success.php');
    }

    public function productTaxRates($selprod_id)
    {
        $selprod_id = Fatutility::int($selprod_id);
        $sellerProductRow = SellerProduct::getAttributesById($selprod_id);
        if ($sellerProductRow['selprod_user_id'] != $this->userParentId) {
            Message::addErrorMessage(Labels::getLabel('ERR_INVALID_ACCESS', $this->siteLangId));
            FatApp::redirectUser($_SESSION['referer_page_url']);
        }

        $taxRates[] = $this->getTaxRates($sellerProductRow['selprod_product_id'], $this->userParentId);

        $this->set('arrListing', $taxRates);
        $this->set('activeTab', 'TAX');
        $this->set('userId', $this->userParentId);
        $this->set('selprod_id', $sellerProductRow['selprod_id']);
        $this->set('product_id', $sellerProductRow['selprod_product_id']);

        $this->_template->render(false, false);
    }

    private function getTaxRates($productId, $userId)
    {
        $productId = Fatutility::int($productId);
        $userId = Fatutility::int($userId);

        $taxRates = array();
        $taxObj = Tax::getTaxCatObjByProductId($productId, $this->siteLangId);
        $taxObj->addMultipleFields(array('IFNULL(taxcat_name,taxcat_identifier) as taxcat_name', 'ptt_seller_user_id', 'ptt_taxcat_id', 'ptt_product_id'));
        $taxObj->doNotCalculateRecords();

        $cnd = $taxObj->addCondition('ptt_seller_user_id', '=', 'mysql_func_0', 'AND', true);
        $cnd->attachCondition('ptt_seller_user_id', '=', $userId, 'OR');

        $taxObj->setPageSize(1);
        $taxObj->addOrder('ptt_seller_user_id', 'DESC');

        $rs = $taxObj->getResultSet();
        $taxRates = FatApp::getDb()->fetch($rs);

        return $taxRates ? $taxRates : array();
    }

    private function changeTaxCategoryForm($langId)
    {
        $frm = new Form('frmTaxRate');
        $frm->addHiddenField('', 'selprod_id');
        $taxCatArr = Tax::getSaleTaxCatArr($langId);

        $frm->addSelectBox(Labels::getLabel('FRM_TAX_CATEGORY', $langId), 'ptt_taxcat_id', $taxCatArr, '', array(), Labels::getLabel('FRM_SELECT', $langId))->requirements()->setRequired(true);

        $frm->addSubmitButton('', 'btn_submit', Labels::getLabel('BTN_SAVE_CHANGES', $langId));
        return $frm;
    }

    public function changeTaxCategory($selprod_id)
    {
        $selprod_id = FatUtility::int($selprod_id);
        $sellerProductRow = SellerProduct::getAttributesById($selprod_id);

        if ($sellerProductRow['selprod_user_id'] != $this->userParentId) {
            Message::addErrorMessage(Labels::getLabel('ERR_INVALID_ACCESS', $this->siteLangId));
            FatApp::redirectUser($_SESSION['referer_page_url']);
        }

        /* $srch = Tax::getSearchObject($this->siteLangId);
          $srch->addMultipleFields(array('taxcat_id','IFNULL(taxcat_name,taxcat_identifier) as taxcat_name'));
          $rs =  $srch->getResultSet();
          if($rs){
          $records = FatApp::getDb()->fetchAll($rs,'taxcat_id');
          }
          var_dump($records); */
        $taxRates = $this->getTaxRates($sellerProductRow['selprod_product_id'], $this->userParentId);
        $frm = $this->changeTaxCategoryForm($this->siteLangId);

        $frm->fill($taxRates + array('selprod_id' => $sellerProductRow['selprod_id']));

        $this->set('frm', $frm);
        $this->set('userId', $this->userParentId);
        $this->set('selprod_id', $sellerProductRow['selprod_id']);
        $this->set('product_id', $sellerProductRow['selprod_product_id']);
        $this->_template->render(false, false);
    }

    public function setUpTaxCategory()
    {
        $this->userPrivilege->canEditTaxCategory(UserAuthentication::getLoggedUserId());
        $post = FatApp::getPostedData();
        $selprod_id = FatUtility::int($post['selprod_id']);
        if (!$selprod_id) {
            FatUtility::dieJsonError(Labels::getLabel('ERR_INVALID_REQUEST', $this->siteLangId));
        }

        $sellerProductRow = SellerProduct::getAttributesById($selprod_id);
        if ($sellerProductRow['selprod_user_id'] != $this->userParentId) {
            FatUtility::dieJsonError(Labels::getLabel('ERR_INVALID_ACCESS', $this->siteLangId));
        }

        $data = array(
            'ptt_product_id' => $sellerProductRow['selprod_product_id'],
            'ptt_taxcat_id' => $post['ptt_taxcat_id'],
            'ptt_seller_user_id' => $this->userParentId
        );
        /* CommonHelper::printArray($data); die; */
        $obj = new Tax();
        if (!$obj->addUpdateProductTaxCat($data)) {
            FatUtility::dieJsonError($obj->getError());
        }

        $this->set('selprod_id', $selprod_id);
        $this->set('msg', Labels::getLabel('MSG_SETUP_SUCCESSFULLY', $this->siteLangId));
        $this->_template->render(false, false, 'json-success.php');
    }

    public function resetTaxRates($selprod_id)
    {
        $selprod_id = FatUtility::int($selprod_id);
        $sellerProductRow = SellerProduct::getAttributesById($selprod_id);

        if ($sellerProductRow['selprod_user_id'] != $this->userParentId) {
            FatUtility::dieJsonError(Labels::getLabel('ERR_INVALID_ACCESS', $this->siteLangId));
        }

        if (!FatApp::getDb()->deleteRecords(Tax::DB_TBL_PRODUCT_TO_TAX, array('smt' => 'ptt_product_id = ? and ptt_seller_user_id = ?', 'vals' => array($sellerProductRow['selprod_product_id'], $this->userParentId)))) {
            FatUtility::dieJsonError(FatApp::getDb()->getError());
        }

        $this->set('selprod_id', $selprod_id);
        $this->set('msg', Labels::getLabel('MSG_Reset_successfully', $this->siteLangId));
        $this->_template->render(false, false, 'json-success.php');
    }

    private function getSellerProductSpecialPriceForm()
    {
        $frm = new Form('frmSellerProductSpecialPrice');
        $fld = $frm->addFloatField(Labels::getLabel('FRM_SPECIAL_PRICE', $this->siteLangId) . CommonHelper::concatCurrencySymbolWithAmtLbl(), 'splprice_price');
        $fld->requirements()->setPositive();
        $fld = $frm->addDateField(Labels::getLabel('FRM_PRICE_START_DATE', $this->siteLangId), 'splprice_start_date', '', array('readonly' => 'readonly'));
        $fld->requirements()->setRequired();

        $fld = $frm->addDateField(Labels::getLabel('FRM_PRICE_END_DATE', $this->siteLangId), 'splprice_end_date', '', array('readonly' => 'readonly'));
        $fld->requirements()->setRequired();
        $fld->requirements()->setCompareWith('splprice_start_date', 'ge', Labels::getLabel('FRM_PRICE_START_DATE', $this->siteLangId));

        $frm->addHiddenField('', 'splprice_selprod_id');
        $frm->addHiddenField('', 'splprice_id');

        /* $str = "<span id='special-price-discounted-string'>".Labels::getLabel("FRM_[SAVE_NN_(XX%_Off)]", $this->siteLangId)."</span>";
          $frm->addHtml( '', 'discountHtmlHeading', Labels::getLabel('FRM_OPTIONAL_DISCOUNT_FIELDS', $this->siteLangId)." ". Labels::getLabel("FRM_BELOW_STRING_WILL_APPEAR_AS:", $this->siteLangId) .'<br/>'.$str );
          $fld = $frm->addTextBox( Labels::getLabel( 'FRM_SAVE' ,$this->siteLangId), 'splprice_display_list_price' );
          $fld->requirements()->setFloat();
          $fld->addFieldTagAttribute( 'onChange', 'updateDiscountString()');
          $fld = $frm->addTextBox( Labels::getLabel( 'FRM_AMOUNT' ,$this->siteLangId), 'splprice_display_dis_val' );
          $fld->requirements()->setFloat();
          $fld->addFieldTagAttribute( 'onChange', 'updateDiscountString()');
          $fld = $frm->addSelectBox( Labels::getLabel('FRM_DISCOUNT_TYPE', $this->siteLangId), 'splprice_display_dis_type', applicationConstants::getPercentageFlatArr($this->siteLangId), '', array() );
          $fld->addFieldTagAttribute( 'onChange', 'updateDiscountString()');
         */
        $fld1 = $frm->addSubmitButton('', 'btn_submit', Labels::getLabel('BTN_SAVE_CHANGES', $this->siteLangId));
        $fld2 = $frm->addButton('', 'btn_cancel', Labels::getLabel('BTN_CANCEL', $this->siteLangId), array('onclick' => 'javascript:$("#sellerProductsForm").html(\'\')'));
        $fld1->attachField($fld2);
        return $frm;
    }

    public function sellerProductSpecialPrices($selprod_id)
    {
        $selprod_id = FatUtility::int($selprod_id);
        $sellerProductRow = SellerProduct::getAttributesById($selprod_id);
        $productRow = Product::getAttributesById($sellerProductRow['selprod_product_id'], array('product_type'));

        if ($sellerProductRow['selprod_user_id'] != $this->userParentId) {
            Message::addErrorMessage(Labels::getLabel('ERR_INVALID_ACCESS', $this->siteLangId));
            FatApp::redirectUser($_SESSION['referer_page_url']);
        }


        $arrListing = SellerProduct::getSellerProductSpecialPrices($selprod_id);
        $this->set('arrListing', $arrListing);
        $this->set('selprod_id', $sellerProductRow['selprod_id']);
        $this->set('product_id', $sellerProductRow['selprod_product_id']);
        $this->set('siteLangId', $this->siteLangId);
        $this->set('product_type', $productRow['product_type']);
        $this->set('activeTab', 'SPECIAL_PRICE');
        $this->_template->render(false, false);
    }

    public function sellerProductSpecialPriceForm($selprod_id, $splprice_id = 0)
    {
        $selprod_id = FatUtility::int($selprod_id);
        $splprice_id = FatUtility::int($splprice_id);
        if (!$selprod_id) {
            Message::addErrorMessage(Labels::getLabel('ERR_INVALID_REQUEST', $this->siteLangId));
            FatApp::redirectUser($_SESSION['referer_page_url']);
        }
        $sellerProductRow = SellerProduct::getAttributesById($selprod_id);
        if ($sellerProductRow['selprod_user_id'] != $this->userParentId) {
            Message::addErrorMessage(Labels::getLabel('ERR_INVALID_ACCESS', $this->siteLangId));
            FatApp::redirectUser($_SESSION['referer_page_url']);
        }

        $frmSellerProductSpecialPrice = $this->getSellerProductSpecialPriceForm();
        $specialPriceRow = array();
        if ($splprice_id) {
            $tblRecord = new TableRecord(SellerProduct::DB_TBL_SELLER_PROD_SPCL_PRICE);
            if (!$tblRecord->loadFromDb(array('smt' => 'splprice_id = ?', 'vals' => array($splprice_id)))) {
                Message::addErrorMessage(Labels::getLabel('ERR_INVALID_REQUEST', $this->siteLangId));
                FatApp::redirectUser($_SESSION['referer_page_url']);
            }
            $specialPriceRow = $tblRecord->getFlds();
        }

        $specialPriceRow['splprice_selprod_id'] = $selprod_id;
        $frmSellerProductSpecialPrice->fill($specialPriceRow);

        $this->set('frmSellerProductSpecialPrice', $frmSellerProductSpecialPrice);
        $this->set('selprod_id', $selprod_id);
        $this->set('product_id', $sellerProductRow['selprod_product_id']);
        $this->set('siteLangId', $this->siteLangId);
        $this->set('activeTab', 'SPECIAL_PRICE');
        $this->_template->render(false, false);
    }

    public function setUpSellerProductSpecialPrice()
    {
        $this->userPrivilege->canEditSpecialPrice(UserAuthentication::getLoggedUserId());
        $post = FatApp::getPostedData();
        $selprod_id = FatUtility::int($post['splprice_selprod_id']);
        $splprice_id = FatUtility::int($post['splprice_id']);

        if (!$selprod_id) {
            FatUtility::dieJsonError(Labels::getLabel('ERR_INVALID_REQUEST', $this->siteLangId));
        }
        $prodSrch = new ProductSearch($this->siteLangId);
        $prodSrch->joinSellerProducts();
        $prodSrch->addCondition('selprod_id', '=', 'mysql_func_' . $selprod_id, 'AND', true);
        $prodSrch->addMultipleFields(array('product_min_selling_price', 'selprod_price', 'selprod_user_id'));
        $prodSrch->setPageSize(1);
        $rs = $prodSrch->getResultSet();
        $product = FatApp::getDb()->fetch($rs);

        /* if ($post['splprice_price'] < $product['product_min_selling_price'] || $post['splprice_price'] >= $product['selprod_price']) {
          $str = Labels::getLabel('MSG_Price_must_between_min_selling_price_{minsellingprice}_and_selling_price_{sellingprice}', $this->siteLangId);
          $minSellingPrice = CommonHelper::displayMoneyFormat($product['product_min_selling_price'], false, true, true);
          $sellingPrice = CommonHelper::displayMoneyFormat($product['selprod_price'], false, true, true);

          $message = CommonHelper::replaceStringData($str, array('{minsellingprice}' => $minSellingPrice, '{sellingprice}' => $sellingPrice));
          FatUtility::dieJsonError($message);
          } */

        if ($product['selprod_user_id'] != $this->userParentId) {
            FatUtility::dieJsonError(Labels::getLabel('ERR_INVALID_ACCESS', $this->siteLangId));
        }

        $frm = $this->getSellerProductSpecialPriceForm();
        $post = $frm->getFormDataFromArray(FatApp::getPostedData());

        if (false === $post) {
            FatUtility::dieJsonError(current($frm->getValidationErrors()));
        }

        /* Check if same date already exists [ */
        $tblRecord = new TableRecord(SellerProduct::DB_TBL_SELLER_PROD_SPCL_PRICE);
        if ($tblRecord->loadFromDb(array('smt' => '(splprice_selprod_id = ?) AND ((splprice_start_date between ? AND ?) OR (splprice_end_date between ? AND ?) )', 'vals' => array($selprod_id, $post['splprice_start_date'], $post['splprice_end_date'], $post['splprice_start_date'], $post['splprice_end_date'])))) {
            $specialPriceRow = $tblRecord->getFlds();
            if ($specialPriceRow['splprice_id'] != $post['splprice_id']) {
                FatUtility::dieJsonError(Labels::getLabel('ERR_SPECIAL_PRICE_FOR_THIS_DATE_ALREADY_ADDED', $this->siteLangId));
            }
        }
        /* ] */

        $data_to_save = array(
            'splprice_id' => $splprice_id,
            'splprice_selprod_id' => $selprod_id,
            'splprice_start_date' => $post['splprice_start_date'],
            'splprice_end_date' => $post['splprice_end_date'],
            'splprice_price' => $post['splprice_price'],
            /* 'splprice_display_dis_type' =>    $post['splprice_display_dis_type'],
                  'splprice_display_dis_val' =>    $post['splprice_display_dis_val'],
                  'splprice_display_list_price' =>$post['splprice_display_list_price'], */
        );
        $sellerProdObj = new SellerProduct();
        if (!$sellerProdObj->addUpdateSellerProductSpecialPrice($data_to_save)) {
            FatUtility::dieJsonError(Labels::getLabel($sellerProdObj->getError(), $this->siteLangId));
        }
        $productId = SellerProduct::getAttributesById($selprod_id, 'selprod_product_id', false);
        Product::updateMinPrices($productId);
        $this->set('msg', Labels::getLabel('MSG_SPECIAL_PRICE_SETUP_SUCCESSFUL', $this->siteLangId));
        $this->_template->render(false, false, 'json-success.php');
    }

    public function deleteSellerProductSpecialPrice()
    {
        $this->userPrivilege->canEditSpecialPrice(UserAuthentication::getLoggedUserId());
        $splPriceId = FatApp::getPostedData('splprice_id', FatUtility::VAR_INT, 0);
        if (1 > $splPriceId) {
            Message::addErrorMessage(Labels::getLabel('ERR_INVALID_REQUEST', $this->siteLangId));
            FatApp::redirectUser($_SESSION['referer_page_url']);
        }
        $specialPriceRow = SellerProduct::getSellerProductSpecialPriceById($splPriceId);
        $this->removeSpecialPrice($splPriceId, $specialPriceRow);
        $productId = SellerProduct::getAttributesById($specialPriceRow['selprod_id'], 'selprod_product_id', false);
        Product::updateMinPrices($productId);
        $this->set('selprod_id', $specialPriceRow['selprod_id']);
        $this->set('msg', Labels::getLabel('MSG_SPECIAL_PRICE_RECORD_DELETED', $this->siteLangId));
        $this->_template->render(false, false, 'json-success.php');
    }

    public function removeSpecialPriceArr()
    {
        $splpriceIdArr = FatApp::getPostedData('selprod_ids');
        $splpriceIds = FatUtility::int($splpriceIdArr);
        foreach ($splpriceIds as $splPriceId => $selProdId) {
            $specialPriceRow = SellerProduct::getSellerProductSpecialPriceById($splPriceId);
            $this->removeSpecialPrice($splPriceId, $specialPriceRow);
        }
        Product::updateMinPrices();
        $this->set('selprod_id', $specialPriceRow['selprod_id']);
        $this->set('msg', Labels::getLabel('MSG_SPECIAL_PRICE_RECORD_DELETED', $this->siteLangId));
        $this->_template->render(false, false, 'json-success.php');
    }

    private function removeSpecialPrice($splPriceId, $specialPriceRow)
    {
        if ($specialPriceRow['selprod_user_id'] != $this->userParentId) {
            FatUtility::dieWithError(Labels::getLabel('MSG_INVALID_ACCESS', $this->siteLangId));
        }

        $sellerProdObj = new SellerProduct($specialPriceRow['selprod_id']);
        if (!$sellerProdObj->deleteSellerProductSpecialPrice($splPriceId, $specialPriceRow['selprod_id'])) {
            FatUtility::dieWithError(Labels::getLabel($sellerProdObj->getError(), $this->siteLangId));
        }
        return true;
    }

    /* Seller Product Volume Discount [ */

    public function sellerProductVolumeDiscounts($selprod_id)
    {
        $selprod_id = FatUtility::int($selprod_id);
        $sellerProductRow = SellerProduct::getAttributesById($selprod_id, array('selprod_user_id', 'selprod_id', 'selprod_product_id'));

        if ($sellerProductRow['selprod_user_id'] != $this->userParentId) {
            FatUtility::dieWithError(Labels::getLabel('MSG_INVALID_ACCESS', $this->siteLangId));
        }

        $productRow = Product::getAttributesById($sellerProductRow['selprod_product_id'], array('product_type'));

        $srch = new SellerProductVolumeDiscountSearch();
        $srch->doNotCalculateRecords();
        $srch->doNotLimitRecords();
        $srch->addCondition('voldiscount_selprod_id', '=', 'mysql_func_' . $selprod_id, 'AND', true);
        $rs = $srch->getResultSet();

        $arrListing = FatApp::getDb()->fetchAll($rs);
        $this->set('arrListing', $arrListing);
        $this->set('selprod_id', $sellerProductRow['selprod_id']);
        $this->set('product_id', $sellerProductRow['selprod_product_id']);
        $this->set('product_type', $productRow['product_type']);
        $this->set('activeTab', 'VOLUME_DISCOUNT');

        $productLangRow = Product::getAttributesByLangId($this->siteLangId, $sellerProductRow['selprod_product_id'], array('product_name'));
        $this->set('productCatalogName', $productLangRow['product_name']);

        $this->_template->render(false, false);
    }

    public function sellerProductVolumeDiscountForm($selprod_id, $voldiscount_id)
    {
        $selprod_id = FatUtility::int($selprod_id);
        $voldiscount_id = FatUtility::int($voldiscount_id);
        if ($selprod_id <= 0) {
            FatUtility::dieWithError(Labels::getLabel('MSG_INVALID_REQUEST', $this->siteLangId));
        }
        $sellerProductRow = SellerProduct::getAttributesById($selprod_id, array('selprod_id', 'selprod_user_id', 'selprod_product_id'));
        if ($sellerProductRow['selprod_user_id'] != $this->userParentId || $selprod_id != $sellerProductRow['selprod_id']) {
            FatUtility::dieWithError(Labels::getLabel('MSG_INVALID_ACCESS', $this->siteLangId));
        }

        $frmSellerProductVolDiscount = $this->getSellerProductVolumeDiscountForm($this->siteLangId);
        $volumeDiscountRow = array();
        if ($voldiscount_id) {
            $volumeDiscountRow = SellerProductVolumeDiscount::getAttributesById($voldiscount_id);
            if (!$volumeDiscountRow) {
                FatUtility::dieWithError(Labels::getLabel('MSG_INVALID_REQUEST', $this->siteLangId));
            }
        }
        $volumeDiscountRow['voldiscount_selprod_id'] = $sellerProductRow['selprod_id'];
        $frmSellerProductVolDiscount->fill($volumeDiscountRow);
        $this->set('frmSellerProductVolDiscount', $frmSellerProductVolDiscount);
        $this->set('selprod_id', $sellerProductRow['selprod_id']);
        $this->set('product_id', $sellerProductRow['selprod_product_id']);
        $this->set('activeTab', 'VOLUME_DISCOUNT');
        $this->_template->render(false, false);
    }

    public function setUpSellerProductVolumeDiscount()
    {
        $this->userPrivilege->canEditVolumeDiscount(UserAuthentication::getLoggedUserId());
        $selprod_id = FatApp::getPostedData('voldiscount_selprod_id', FatUtility::VAR_INT, 0);
        if (!$selprod_id) {
            Message::addErrorMessage(Labels::getLabel('ERR_INVALID_REQUEST', $this->siteLangId));
            FatUtility::dieWithError(Message::getHtml());
        }
        $voldiscount_id = FatApp::getPostedData('voldiscount_id', FatUtility::VAR_INT, 0);

        $frm = $this->getSellerProductVolumeDiscountForm($this->siteLangId);
        $post = $frm->getFormDataFromArray(FatApp::getPostedData());
        if (false === $post) {
            Message::addErrorMessage(current($frm->getValidationErrors()), $this->siteLangId);
            FatUtility::dieWithError(Message::getHtml());
        }

        $this->updateSelProdVolDiscount($selprod_id, $voldiscount_id, $post['voldiscount_min_qty'], $post['voldiscount_percentage']);

        $this->set('msg', Labels::getLabel('MSG_VOLUME_DISCOUNT_SETUP_SUCCESSFUL', $this->siteLangId));
        $this->_template->render(false, false, 'json-success.php');
    }

    private function updateSelProdVolDiscount($selprod_id, $voldiscount_id, $minQty, $perc)
    {
        $sellerProductRow = SellerProduct::getAttributesById($selprod_id, array('selprod_user_id', 'selprod_stock', 'selprod_min_order_qty'), false);

        if ($minQty > $sellerProductRow['selprod_stock']) {
            FatUtility::dieJsonError(Labels::getLabel('ERR_QUANTITY_CANNOT_BE_MORE_THAN_THE_STOCK_OF_THE_PRODUCT', $this->siteLangId));
        }

        if ($minQty < $sellerProductRow['selprod_min_order_qty']) {
            FatUtility::dieJsonError(Labels::getLabel('ERR_QUANTITY_CANNOT_BE_LESS_THAN_THE_MINIMUM_ORDER_QUANTITY', $this->siteLangId) . ': ' . $sellerProductRow['selprod_min_order_qty']);
        }

        if ($perc > 100 || 1 > $perc) {
            FatUtility::dieJsonError(Labels::getLabel('ERR_INVALID_PERCENTAGE', $this->siteLangId));
        }

        if ($sellerProductRow['selprod_user_id'] != $this->userParentId) {
            FatUtility::dieJsonError(Labels::getLabel('ERR_INVALID_REQUEST', $this->siteLangId));
        }

        /* Check if volume discount for same quantity already exists [ */
        $tblRecord = new TableRecord(SellerProductVolumeDiscount::DB_TBL);
        if ($tblRecord->loadFromDb(array('smt' => 'voldiscount_selprod_id = ? AND voldiscount_min_qty = ?', 'vals' => array($selprod_id, $minQty)))) {
            $volDiscountRow = $tblRecord->getFlds();
            if ($volDiscountRow['voldiscount_id'] != $voldiscount_id) {
                FatUtility::dieJsonError(Labels::getLabel('ERR_VOLUME_DISCOUNT_FOR_THIS_QUANTITY_ALREADY_ADDED', $this->siteLangId));
            }
        }
        /* ] */

        $data_to_save = array(
            'voldiscount_selprod_id' => $selprod_id,
            'voldiscount_min_qty' => $minQty,
            'voldiscount_percentage' => $perc
        );

        if (0 < $voldiscount_id) {
            $data_to_save['voldiscount_id'] = $voldiscount_id;
        }

        // Return Volume Discount ID if $return(Second Param) is true else it will return bool value.
        $voldiscount_id = SellerProductVolumeDiscount::updateData($data_to_save, true);
        if (1 > $voldiscount_id) {
            FatUtility::dieJsonError(Labels::getLabel('ERR_UNABLE_TO_SAVE_THIS_RECORD', $this->siteLangId));
        }
        return $voldiscount_id;
    }

    public function deleteSellerProductVolumeDiscount()
    {
        $this->userPrivilege->canEditVolumeDiscount(UserAuthentication::getLoggedUserId());
        $post = FatApp::getPostedData();
        $voldiscount_id = FatApp::getPostedData('voldiscount_id', FatUtility::VAR_INT, 0);
        if (!$voldiscount_id) {
            Message::addErrorMessage(Labels::getLabel('ERR_INVALID_REQUEST', $this->siteLangId));
            FatApp::redirectUser($_SESSION['referer_page_url']);
        }

        $volumeDiscountRow = SellerProductVolumeDiscount::getAttributesById($voldiscount_id);
        $sellerProductRow = SellerProduct::getAttributesById($volumeDiscountRow['voldiscount_selprod_id'], array('selprod_user_id'), false);
        if (!$volumeDiscountRow || !$sellerProductRow || $sellerProductRow['selprod_user_id'] != $this->userParentId) {
            Message::addErrorMessage(Labels::getLabel('ERR_INVALID_REQUEST', $this->siteLangId));
            FatApp::redirectUser($_SESSION['referer_page_url']);
        }

        $this->deleteVolumeDiscount($voldiscount_id, $volumeDiscountRow['voldiscount_selprod_id']);

        $this->set('selprod_id', $volumeDiscountRow['voldiscount_selprod_id']);
        $this->set('msg', Labels::getLabel('MSG_VOLUME_DISCOUNT_RECORD_DELETED', $this->siteLangId));
        $this->_template->render(false, false, 'json-success.php');
    }

    public function deleteVolumeDiscountArr()
    {
        $this->userPrivilege->canEditVolumeDiscount(UserAuthentication::getLoggedUserId());
        $splpriceIdArr = FatApp::getPostedData('selprod_ids');
        $splpriceIds = FatUtility::int($splpriceIdArr);
        foreach ($splpriceIds as $voldiscount_id => $selProdId) {
            $volumeDiscountRow = SellerProductVolumeDiscount::getAttributesById($voldiscount_id);
            $sellerProductRow = SellerProduct::getAttributesById($volumeDiscountRow['voldiscount_selprod_id'], array('selprod_user_id'), false);
            if (!$volumeDiscountRow || !$sellerProductRow || $sellerProductRow['selprod_user_id'] != $this->userParentId) {
                Message::addErrorMessage(Labels::getLabel('ERR_INVALID_REQUEST', $this->siteLangId));
                FatUtility::dieWithError(Message::getHtml());
            }

            $this->deleteVolumeDiscount($voldiscount_id, $volumeDiscountRow['voldiscount_selprod_id']);
        }
        $this->set('msg', Labels::getLabel('MSG_VOLUME_DISCOUNT_RECORD_DELETED', $this->siteLangId));
        $this->_template->render(false, false, 'json-success.php');
    }

    private function deleteVolumeDiscount($volumeDiscountId, $volumeDiscountSelprodId)
    {
        $db = FatApp::getDb();
        if (!$db->deleteRecords(SellerProductVolumeDiscount::DB_TBL, array('smt' => 'voldiscount_id = ? AND voldiscount_selprod_id = ?', 'vals' => array($volumeDiscountId, $volumeDiscountSelprodId)))) {
            Message::addErrorMessage(Labels::getLabel("LBL_" . $db->getError(), $this->siteLangId));
            FatApp::redirectUser($_SESSION['referer_page_url']);
        }
        return true;
    }

    private function getSellerProductVolumeDiscountForm($langId)
    {
        $frm = new Form('frmSellerProductSpecialPrice');

        $frm->addHiddenField('', 'voldiscount_selprod_id', 0);
        $frm->addHiddenField('', 'voldiscount_id', 0);
        $qtyFld = $frm->addIntegerField(Labels::getLabel("FRM_MINIMUM_QUANTITY", $langId), 'voldiscount_min_qty');
        $qtyFld->requirements()->setPositive();
        $discountFld = $frm->addFloatField(Labels::getLabel("FRM_DISCOUNT_IN_(%)", $this->siteLangId), "voldiscount_percentage");
        $discountFld->requirements()->setPositive();
        $discountFld->requirements()->setRange(1, 100);
        $fld1 = $frm->addSubmitButton('', 'btn_submit', Labels::getLabel('BTN_SAVE_CHANGES', $langId));
        $fld2 = $frm->addButton('', 'btn_cancel', Labels::getLabel('BTN_CANCEL', $langId), array('onclick' => 'javascript:$("#sellerProductsForm").html(\'\')'));
        $fld1->attachField($fld2);
        return $frm;
    }

    /*    ]    */

    /* Seller Product Seo [ */

    public function productSeo()
    {
        $this->userPrivilege->canViewMetaTags(UserAuthentication::getLoggedUserId());
        $this->set('frmSearch', $this->getSellerProductSearchForm());
        $this->set('keywordPlaceholder', Labels::getLabel('LBL_SEARCH_BY_PRODUCT_NAME', $this->siteLangId));
        $this->_template->render(true, true);
    }

    public function searchSeoProducts()
    {
        $userId = $this->userParentId;
        $page = FatApp::getPostedData('page', FatUtility::VAR_INT, 1);
        $keyword = FatApp::getPostedData('keyword', FatUtility::VAR_STRING, '');
        $srch = SellerProduct::searchSellerProducts($this->siteLangId, $userId, $keyword);
        $pageSize = FatApp::getConfig('CONF_PAGE_SIZE');
        $post = FatApp::getPostedData();
        $page = (empty($post['page']) || $post['page'] <= 0) ? 1 : $post['page'];
        $page = (empty($page) || $page <= 0) ? 1 : $page;
        $page = FatUtility::int($page);
        $this->setRecordCount(clone $srch, $pageSize, $page, $post);
        $srch->doNotCalculateRecords();
        $srch->addOrder('selprod_active', 'DESC');
        $srch->addOrder('selprod_added_on', 'DESC');
        $srch->addOrder('selprod_id', 'DESC');
        $srch->addOrder('product_name', 'ASC');
        $srch->addMultipleFields(['selprod_id', 'IFNULL(selprod_title, IFNULL(product_name, product_identifier)) as selprod_title']);
        $srch->setPageNumber($page);
        $srch->setPageSize($pageSize);
        $this->set("arrListing", FatApp::getDb()->fetchAll($srch->getResultSet()));
        $this->set('canEditMetaTag', $this->userPrivilege->canEditMetaTags(UserAuthentication::getLoggedUserId(), true));
        $this->set('postedData', $post);
        $this->set('pageSize', FatApp::getConfig('CONF_PAGE_SIZE', FatUtility::VAR_INT, 10));
        $this->_template->render(false, false);
    }

    public function productSeoLangForm($selprodId, $langId, $autoFillLangData = 0)
    {
        $selprodId = FatUtility::int($selprodId);
        $langId = FatUtility::int($langId);

        $sellerProductRow = SellerProduct::getAttributesById($selprodId);
        if ($sellerProductRow['selprod_user_id'] != $this->userParentId) {
            Message::addErrorMessage(Labels::getLabel('ERR_INVALID_ACCESS', $this->siteLangId));
            FatApp::redirectUser($_SESSION['referer_page_url']);
        }

        $metaType = MetaTag::META_GROUP_PRODUCT_DETAIL;
        $this->set('metaType', $metaType);

        $prodMetaData = Product::getProductMetaData($selprodId);

        $metaId = 0;

        if (!empty($prodMetaData)) {
            $metaId = $prodMetaData['meta_id'];
        }

        if (0 < $autoFillLangData) {
            $updateLangDataobj = new TranslateLangData(MetaTag::DB_TBL_LANG);
            $translatedData = $updateLangDataobj->getTranslatedData($metaId, $langId, CommonHelper::getDefaultFormLangId());
            if (false === $translatedData) {
                LibHelper::exitWithError($updateLangDataobj->getError(), true);
            }
            $metaData = current($translatedData);
        } else {
            $metaData = MetaTag::getAttributesByLangId($langId, $metaId);
        }

        $prodSeoLangFrm = $this->getSeoLangForm($metaId, $langId, $selprodId, MetaTag::META_GROUP_PRODUCT_DETAIL);
        $prodSeoLangFrm->fill($metaData);

        $this->set('languages', Language::getAllNames());
        $this->set('productSeoLangForm', $prodSeoLangFrm);
        $this->set('formLayout', Language::getLayoutDirection($langId));
        $this->set('selprodId', $selprodId);
        $this->set('selprod_lang_id', $langId);

        $this->_template->render(false, false);
    }

    private function getSeoLangForm($metaId = 0, $lang_id = 0, $recordId = 0, $metaType = 'default')
    {
        $frm = new Form('frmMetaTagLang');

        $frm->addHiddenField('', 'meta_id', $metaId);
        $frm->addHiddenField('', 'meta_type', $metaType);
        $frm->addHiddenField('', 'meta_record_id', $recordId);

        $languages = Language::getAllNames();
        if (count($languages) > 1) {
            $frm->addSelectBox(Labels::getLabel('FRM_LANGUAGE', $this->siteLangId), 'lang_id', $languages, $lang_id, array(), '');
        } else {
            $lang_id = array_key_first($languages);
            $frm->addHiddenField('', 'lang_id', $lang_id);
        }

        $frm->addRequiredField(Labels::getLabel("FRM_META_TITLE", $this->siteLangId), 'meta_title');
        $frm->addTextarea(Labels::getLabel("FRM_META_KEYWORDS", $this->siteLangId), 'meta_keywords');
        $frm->addTextarea(Labels::getLabel("FRM_META_DESCRIPTION", $this->siteLangId), 'meta_description');
        $fld = $frm->addTextarea(Labels::getLabel("FRM_OTHER_META_TAGS", $this->siteLangId), 'meta_other_meta_tags');
        $fld->htmlAfterField = '<small class="form-text text-muted">' . Labels::getLabel('FRM_FOR_EXAMPLE:', $this->siteLangId) . ' ' . htmlspecialchars('<meta name="copyright" content="text">') . '</small>';
        $siteLangId = FatApp::getConfig('conf_default_site_lang', FatUtility::VAR_INT, 1);
        $translatorSubscriptionKey = FatApp::getConfig('CONF_TRANSLATOR_SUBSCRIPTION_KEY', FatUtility::VAR_STRING, '');

        $languages = Language::getAllNames();
        if (!empty($translatorSubscriptionKey) && $lang_id == $siteLangId && count($languages) > 1) {
            $frm->addCheckBox(Labels::getLabel('FRM_UPDATE_OTHER_LANGUAGES_DATA', $this->siteLangId), 'auto_update_other_langs_data', 1, array(), false, 0);
        }
        $frm->addButton('', 'btn_exit', Labels::getLabel("BTN_SAVE_&_EXIT", $this->siteLangId));
        $frm->addButton('', 'btn_next', Labels::getLabel("BTN_SAVE_&_NEXT", $this->siteLangId));
        return $frm;
    }

    public function setupProdMetaLang()
    {
        $this->userPrivilege->canEditMetaTags(UserAuthentication::getLoggedUserId());
        $post = FatApp::getPostedData();

        $languages = Language::getAllNames();
        if (count($languages) > 1) {
            $lang_id = $post['lang_id'];
        } else {
            $lang_id = array_key_first($languages);
            $post['lang_id'] = $lang_id;
        }



        if ($lang_id == 0) {
            Message::addErrorMessage(Labels::getLabel("ERR_INVALID_ACCESS", $this->siteLangId));
            FatApp::redirectUser($_SESSION['referer_page_url']);
        }
        $metaId = FatUtility::int($post['meta_id']);
        $metaRecordId = FatUtility::int($post['meta_record_id']);

        if (!UserPrivilege::canEditMetaTag($metaId, $metaRecordId)) {
            FatUtility::dieJsonError(Labels::getLabel("ERR_INVALID_ACCESS", $this->siteLangId));
        }
        if (false === $post) {
            FatUtility::dieJsonError(Labels::getLabel("ERR_INVALID_ACCESS", $this->siteLangId));
        }

        if (!$post['meta_other_meta_tags'] == '' && $post['meta_other_meta_tags'] == strip_tags($post['meta_other_meta_tags'])) {
            FatUtility::dieJsonError(Labels::getLabel('ERR_INVALID_OTHER_META_TAG', $this->siteLangId));
        }

        $tabsArr = MetaTag::getTabsArr();
        $metaType = MetaTag::META_GROUP_PRODUCT_DETAIL;

        if ($metaType == '' || !isset($tabsArr[$metaType])) {
            FatUtility::dieJsonError(Labels::getLabel("ERR_INVALID_ACCESS", $this->siteLangId));
        }

        $post['meta_controller'] = $tabsArr[$metaType]['controller'];
        $post['meta_action'] = $tabsArr[$metaType]['action'];
        if ($metaId == 0) {
            $post['meta_subrecord_id'] = 0;
        }
        $record = new MetaTag($metaId);
        $record->assignValues($post);
        if (!$record->save()) {
            FatUtility::dieJsonError($record->getError());
        }

        $metaId = $record->getMainTableRecordId();
        $frm = $this->getSeoLangForm($metaId, $lang_id, $metaRecordId, MetaTag::META_GROUP_PRODUCT_DETAIL);
        $post = $frm->getFormDataFromArray(FatApp::getPostedData());

        $data = array(
            'metalang_lang_id' => $lang_id,
            'metalang_meta_id' => $metaId,
            'meta_title' => strip_tags($post['meta_title']),
            'meta_keywords' => strip_tags($post['meta_keywords']),
            'meta_description' => strip_tags($post['meta_description']),
            'meta_other_meta_tags' => $post['meta_other_meta_tags'],
        );

        $metaObj = new MetaTag($metaId);

        if (!$metaObj->updateLangData($lang_id, $data)) {
            FatUtility::dieJsonError($metaObj->getError());
        }

        $autoUpdateOtherLangsData = FatApp::getPostedData('auto_update_other_langs_data', FatUtility::VAR_INT, 0);
        if (0 < $autoUpdateOtherLangsData) {
            $updateLangDataobj = new TranslateLangData(MetaTag::DB_TBL_LANG);
            if (false === $updateLangDataobj->updateTranslatedData($metaId)) {
                Message::addErrorMessage($updateLangDataobj->getError());
                FatUtility::dieWithError(Message::getHtml());
            }
        }
        $languages = Language::getAllNames();

        $newTabLangId = $lang_id;
        $keys = array_keys($languages);
        $index = array_search($lang_id, $keys);
        if (count($languages) > $index + 1) {
            $newTabLangId = $keys[$index + 1];
        }

        $this->set('msg', Labels::getLabel("MSG_Setup_Successful", $this->siteLangId));
        $this->set('metaRecordId', $metaRecordId);
        $this->set('langId', $newTabLangId);
        $this->_template->render(false, false, 'json-success.php');
    }

    /*  --- ] Seller Product Seo  --- -   */

    /* Seller Product URL Rewriting [ */

    public function productUrlRewriting()
    {
        $this->userPrivilege->canViewUrlRewriting(UserAuthentication::getLoggedUserId());
        $this->set('frmSearch', $this->getSellerProductSearchForm());
        $this->set('keywordPlaceholder', Labels::getLabel('LBL_SEARCH_BY_PRODUCT_NAME', $this->siteLangId));
        $this->_template->render(true, true);
    }

    public function productUrlForm($selprodId)
    {
        $this->userPrivilege->canViewUrlRewriting(UserAuthentication::getLoggedUserId());
        $selprodId = FatUtility::int($selprodId);

        $sellerProductRow = SellerProduct::getAttributesById($selprodId);
        if ($sellerProductRow['selprod_user_id'] != $this->userParentId) {
            Message::addErrorMessage(Labels::getLabel('ERR_INVALID_ACCESS', $this->siteLangId));
            FatUtility::dieWithError(Message::getHtml());
        }
        $frm = $this->getUrlRewriteForm();

        $tabsArr = MetaTag::getTabsArr();
        $metaType = MetaTag::META_GROUP_PRODUCT_DETAIL;

        if ($metaType == '' || !isset($tabsArr[$metaType])) {
            Message::addErrorMessage(Labels::getLabel("ERR_INVALID_ACCESS", $this->siteLangId));
            FatUtility::dieWithError(Message::getHtml());
        }
        $url = $tabsArr[$metaType]['controller'] . '/' . $tabsArr[$metaType]['action'] . '/' . $selprodId;
        $url = trim($url, '/\\');

        if (0 < $selprodId) {
            $srch = UrlRewrite::getSearchObject();
            $srch->joinTable(UrlRewrite::DB_TBL, 'LEFT OUTER JOIN', 'temp.urlrewrite_original = ur.urlrewrite_original', 'temp');
            $srch->addCondition('ur.urlrewrite_original', '=', $url);
            $rs = $srch->getResultSet();
            $data = [
                'selprod_id' => $selprodId
            ];
            while ($row = FatApp::getDb()->fetch($rs)) {
                $data['urlrewrite_original'] = $row['urlrewrite_original'];
                $data['urlrewrite_custom'][$row['urlrewrite_lang_id']] = $row['urlrewrite_custom'];
            }

            if (empty($data)) {
                FatUtility::dieWithError($this->str_invalid_request);
            }
            $frm->fill($data);
        }
        $this->set('frm', $frm);
        $this->set('selprodId', $selprodId);
        $this->_template->render(false, false);
    }

    public function searchUrlRewritingProducts()
    {
        $userId = $this->userParentId;
        $page = FatApp::getPostedData('page', FatUtility::VAR_INT, 1);
        $keyword = FatApp::getPostedData('keyword', FatUtility::VAR_STRING, '');
        $pageSize = FatApp::getConfig('CONF_PAGE_SIZE');
        $post = FatApp::getPostedData();
        $page = (empty($post['page']) || $post['page'] <= 0) ? 1 : $post['page'];
        $page = (empty($page) || $page <= 0) ? 1 : $page;
        $page = FatUtility::int($page);

        $srch = SellerProduct::searchSellerProducts($this->siteLangId, $userId, $keyword);
        $this->setRecordCount(clone $srch, $pageSize, $page, $post);
        $srch->doNotCalculateRecords();
        $srch->addOrder('selprod_active', 'DESC');
        $srch->addOrder('selprod_added_on', 'DESC');
        $srch->addOrder('selprod_id', 'DESC');
        $srch->addOrder('product_name', 'ASC');
        $srch->setPageNumber($page);
        $srch->setPageSize($pageSize);
        $srch->addMultipleFields(['selprod_id', 'IFNULL(selprod_title, IFNULL(product_name, product_identifier)) as selprod_title']);
        $arrListing = FatApp::getDb()->fetchAll($srch->getResultSet());
        foreach ($arrListing as $key => $sellerProduct) {
            // $urlRewriteData = UrlRewrite::getAttributesById($sellerProduct['selprod_id']);
            $urlSrch = UrlRewrite::getSearchObject();
            $urlSrch->doNotCalculateRecords();
            $urlSrch->setPageSize(1);
            $urlSrch->addMultipleFields(array('urlrewrite_id', 'urlrewrite_custom'));
            $urlSrch->addCondition('urlrewrite_original', '=', 'products/view/' . $sellerProduct['selprod_id']);
            $rs = $urlSrch->getResultSet();
            $urlRow = FatApp::getDb()->fetch($rs);
            if ($urlRow) {
                $arrListing[$key]['urlrewrite_id'] = $urlRow['urlrewrite_id'];
                $arrListing[$key]['urlrewrite_custom'] = $urlRow['urlrewrite_custom'];
            }
        }
        $this->set('canEditUrlRewrite', $this->userPrivilege->canEditUrlRewriting(UserAuthentication::getLoggedUserId(), true));
        $this->set("arrListing", $arrListing);
        $this->set('postedData', $post);
        $this->_template->render(false, false);
    }

    public function setupCustomUrl()
    {
        $this->userPrivilege->canEditUrlRewriting(UserAuthentication::getLoggedUserId());
        $frm = $this->getUrlRewriteForm();
        $post = $frm->getFormDataFromArray(FatApp::getPostedData());

        if (false === $post) {
            FatUtility::dieJsonError(current($frm->getValidationErrors()));
        }

        $selprodId = $post['selprod_id'];

        if (!UserPrivilege::canEditSellerProduct($this->userParentId, $selprodId)) {
            FatUtility::dieJsonError(Labels::getLabel("ERR_INVALID_ACCESS", $this->siteLangId));
        }

        $tabsArr = MetaTag::getTabsArr();
        $metaType = MetaTag::META_GROUP_PRODUCT_DETAIL;

        if ($metaType == '' || !isset($tabsArr[$metaType])) {
            Message::addErrorMessage(Labels::getLabel("ERR_INVALID_ACCESS", $this->siteLangId));
            FatUtility::dieWithError(Message::getHtml());
        }
        $url = $tabsArr[$metaType]['controller'] . '/' . $tabsArr[$metaType]['action'] . '/' . $selprodId;
        $originalUrl = trim(strtolower($url), '/\\');

        $srch = UrlRewrite::getSearchObject();
        $srch->joinTable(UrlRewrite::DB_TBL, 'LEFT OUTER JOIN', 'temp.urlrewrite_original = ur.urlrewrite_original', 'temp');
        $srch->addCondition('ur.urlrewrite_original', '=', $originalUrl);
        $srch->addMultipleFields(array('temp.*'));
        $srch->doNotCalculateRecords();
        $rs = $srch->getResultSet();
        $row = FatApp::getDb()->fetchAll($rs, 'urlrewrite_lang_id');

        $langArr = Language::getAllNames();
        foreach ($langArr as $langId => $langName) {
            if (!FatApp::getConfig('CONF_LANG_SPECIFIC_URL', FatUtility::VAR_INT, 0) && $langId != FatApp::getConfig('CONF_DEFAULT_SITE_LANG', FatUtility::VAR_INT, 1)) {
                continue;
            }

            $recordId = 0;
            if (array_key_exists($langId, $row)) {
                $recordId = $row[$langId]['urlrewrite_id'];
            }
            $url = $post['urlrewrite_custom'][$langId];

            $srch = UrlRewrite::getSearchObject();
            $srch->addCondition('ur.urlrewrite_custom', '=', $url);
            $srch->addCondition('ur.urlrewrite_id', '!=', 'mysql_func_' . $recordId, 'AND', true);
            $srch->addMultipleFields(['ur.urlrewrite_id']);
            $srch->doNotCalculateRecords();
            $srch->setPageSize(1);
            $rs = $srch->getResultSet();
            if (FatApp::getDb()->fetch($rs)) {
                FatUtility::dieJsonError(Labels::getLabel('ERR_DUPLICATE_CUSTOM_URL', $this->siteLangId));
            }

            $data = [
                'urlrewrite_original' => $originalUrl,
                'urlrewrite_lang_id' => $langId,
                'urlrewrite_custom' => CommonHelper::seoUrl($url)
            ];
            $record = new UrlRewrite($recordId);
            $record->assignValues($data);

            if (!$record->save()) {
                FatUtility::dieJsonError($record->getError());
            }
        }

        $this->set('msg', Labels::getLabel("MSG_Setup_Successful", $this->siteLangId));
        $this->_template->render(false, false, 'json-success.php');
    }

    private function getUrlRewriteForm()
    {
        $frm = new Form('frmUrlRewrite');
        $frm->addHiddenField('', 'selprod_id');
        $frm->addRequiredField(Labels::getLabel('FRM_ORIGINAL_URL', $this->siteLangId), 'urlrewrite_original');
        $langArr = Language::getAllNames();
        foreach ($langArr as $langId => $langName) {
            if (!FatApp::getConfig('CONF_LANG_SPECIFIC_URL', FatUtility::VAR_INT, 0) && $langId != FatApp::getConfig('CONF_DEFAULT_SITE_LANG', FatUtility::VAR_INT, 1)) {
                continue;
            }

            $fieldName = Labels::getLabel('FRM_CUSTOM_URL', $this->siteLangId);
            if (FatApp::getConfig('CONF_LANG_SPECIFIC_URL', FatUtility::VAR_INT, 0)) {
                $fieldName .= '(' . $langName . ')';
            }
            $frm->addRequiredField($fieldName, 'urlrewrite_custom[' . $langId . ']');
        }
        $fld = $frm->addHTML('', '', '');
        //$fld = $frm->addRequiredField(Labels::getLabel('FRM_CUSTOM_URL', $this->siteLangId), 'urlrewrite_custom');
        $fld->htmlAfterField = '<span class="form-text text-muted">' . Labels::getLabel('FRM_EXAMPLE:_Custom_URL_Example', $this->siteLangId) . '</span>';
        $frm->addSubmitButton('', 'btn_submit', Labels::getLabel('BTN_SAVE_CHANGES', $this->siteLangId));
        return $frm;
    }

    /*  --- ] Seller Product URL Rewriting  ----   */

    /*  ---- Seller Product Links  ----- [ */

    public function sellerProductLinkFrm($selProd_id)
    {
        $post = FatApp::getPostedData();
        $selprod_id = FatUtility::int($selProd_id);
        if (!UserPrivilege::canEditSellerProduct($this->userParentId, $selprod_id)) {
            FatUtility::dieJsonError(Labels::getLabel("ERR_INVALID_ACCESS", $this->siteLangId));
        }
        $sellProdObj = new SellerProduct();
        $sellerProductRow = SellerProduct::getAttributesById($selprod_id);
        $productRow = Product::getAttributesById($sellerProductRow['selprod_product_id'], array('product_type'));

        $upsellProds = $sellProdObj->getUpsellProducts($selprod_id, $this->siteLangId);
        $relatedProds = $sellProdObj->getRelatedProducts($this->siteLangId, $selprod_id);
        $sellerproductLinkFrm = $this->getLinksFrm();
        $data['selprod_id'] = $selProd_id;
        $sellerproductLinkFrm->fill($data);
        $this->set('sellerproductLinkFrm', $sellerproductLinkFrm);
        $this->set('upsellProducts', $upsellProds);
        $this->set('relatedProducts', $relatedProds);
        $this->set('selprod_id', $selProd_id);
        $this->set('product_id', $sellerProductRow[SellerProduct::DB_TBL_PREFIX . 'product_id']);
        $this->set('activeTab', 'LINKS');
        $this->set('product_type', $productRow['product_type']);
        $this->_template->render(false, false);
    }

    private function getLinksFrm()
    {
        $frm = new Form('frmLinks', array('id' => 'frmLinks'));

        $fld1 = $frm->addTextBox(Labels::getLabel('FRM_BUY_TOGETHER_PRODUCTS', $this->siteLangId), 'products_buy_together');
        $fld1->htmlAfterField = '<div class="row"><div class="col-md-12"><ul class="list-vertical" id="buy-together-products"></ul></div></div>';

        $fld1 = $frm->addTextBox(Labels::getLabel('FRM_RELATED_PRODUCTS', $this->siteLangId), 'products_related');
        $fld1->htmlAfterField = '<div class="row"><div class="col-md-12"><ul class="list-vertical" id="related-products"></ul></div></div>';

        $frm->addHiddenField('', 'selprod_id');
        $frm->addSubmitButton('', 'btn_submit', Labels::getLabel("BTN_SAVE_CHANGES", $this->siteLangId));
        return $frm;
    }

    public function autoCompleteProducts()
    {
        $pagesize = 20;
        $post = FatApp::getPostedData();
        $page = FatApp::getPostedData('page', FatUtility::VAR_INT, 1);
        if ($page < 2) {
            $page = 1;
        }
        $srch = SellerProduct::getSearchObject($this->siteLangId);
        $srch->joinTable(Product::DB_TBL, 'INNER JOIN', 'p.product_id = sp.selprod_product_id', 'p');
        $srch->joinTable(Product::DB_TBL_LANG, 'LEFT OUTER JOIN', 'p.product_id = p_l.productlang_product_id AND p_l.productlang_lang_id = ' . $this->siteLangId, 'p_l');

        if (FatApp::getConfig("CONF_PRODUCT_BRAND_MANDATORY", FatUtility::VAR_INT, 1)) {
            $srch->joinTable(Brand::DB_TBL, 'INNER JOIN', 'tb.brand_id = product_brand_id and tb.brand_active = ' . applicationConstants::YES . ' and tb.brand_deleted = ' . applicationConstants::NO, 'tb');
        } else {
            $srch->joinTable(Brand::DB_TBL, 'LEFT OUTER JOIN', 'tb.brand_id = product_brand_id', 'tb');
            $srch->addDirectCondition("(case WHEN brand_id > 0 THEN (tb.brand_active = " . applicationConstants::YES . " AND tb.brand_deleted = " . applicationConstants::NO . ") else TRUE end)");
        }

        $srch->addOrder('product_name');
        $srch->addCondition('product_deleted', '=', applicationConstants::NO);
        $srch->addCondition('selprod_deleted', '=', applicationConstants::NO);
        $srch->addCondition('selprod_cart_type', '!=', SellerProduct::CART_TYPE_RFQ_ONLY);
        if (isset($post['volumeDiscount']) && $post['volumeDiscount']) {
            $srch->addCondition(Product::DB_TBL_PREFIX . 'type', '!=', Product::PRODUCT_TYPE_SERVICE);
        }
        if (!empty($post['keyword'])) {
            $cnd = $srch->addCondition('product_name', 'LIKE', '%' . $post['keyword'] . '%');
            $cnd->attachCondition('selprod_title', 'LIKE', '%' . $post['keyword'] . '%', 'OR');
            $cnd->attachCondition('product_identifier', 'LIKE', '%' . $post['keyword'] . '%', 'OR');
        }

        $srch->addCondition('selprod_user_id', '=', 'mysql_func_' . $this->userParentId, 'AND', true);
        if (isset($post['selprod_id'])) {
            $srch->addCondition('selprod_id', '!=', $post['selprod_id']);
        }
        // if (isset($post['selected_products'])) {
        //     $srch->addCondition('selprod_id', 'NOT IN', array_values($post['selected_products']));
        // }

        $excludeRecords = FatApp::getPostedData('excludeRecords', FatUtility::VAR_INT);
        if (!empty($excludeRecords) && is_array($excludeRecords)) {
            $srch->addCondition('selprod_id', 'NOT IN', $excludeRecords);
        }

        $srch->addMultipleFields(
            array(
                'selprod_id as id',
                'IFNULL(selprod_title, product_name) as product_name',
                'product_identifier',
                'selprod_price'
            )
        );
        $srch->setPageSize($pagesize);
        $srch->setPageNumber($page);
        $srch->addOrder('selprod_active', 'DESC');
        $db = FatApp::getDb();
        $rs = $srch->getResultSet();
        $products = $db->fetchAll($rs, 'id');
        $pageCount = $srch->pages();

        $json = array();
        foreach ($products as $key => $option) {
            $options = SellerProduct::getSellerProductOptions($key, true, $this->siteLangId);
            $variantsStr = '';
            array_walk($options, function ($item, $key) use (&$variantsStr) {
                $variantsStr .= ' | ' . $item['option_name'] . ' : ' . $item['optionvalue_name'];
            });

            $json[] = array(
                'id' => $key,
                'text' => strip_tags(html_entity_decode($option['product_name'], ENT_QUOTES, 'UTF-8')) . $variantsStr,
                'product_identifier' => strip_tags(html_entity_decode($option['product_identifier'], ENT_QUOTES, 'UTF-8')),
                'price' => $option['selprod_price']
            );
        }
        die(json_encode(['pageCount' => $pageCount, 'results' => $json]));
    }

    public function setupSellerProductLinks()
    {
        $this->userPrivilege->canEditProducts(UserAuthentication::getLoggedUserId());
        $post = FatApp::getPostedData();
        $selprod_id = FatUtility::int($post['selprod_id']);
        if (!UserPrivilege::canEditSellerProduct($this->userParentId, $selprod_id)) {
            FatUtility::dieJsonError(Labels::getLabel("ERR_INVALID_ACCESS", $this->siteLangId));
        }
        $upsellProducts = (isset($post['product_upsell'])) ? $post['product_upsell'] : array();
        $relatedProducts = (isset($post['product_related'])) ? $post['product_related'] : array();
        unset($post['selprod_id']);

        if ($selprod_id <= 0) {
            Message::addErrorMessage(Labels::getLabel('ERR_INVALID_REQUEST', $this->siteLangId));
            FatApp::redirectUser($_SESSION['referer_page_url']);
        }

        $sellerProdObj = new sellerProduct();
        /* saving of product Upsell Product[ */
        if (!$sellerProdObj->addUpdateSellerUpsellProducts($selprod_id, $upsellProducts)) {
            Message::addErrorMessage($sellerProdObj->getError());
            FatApp::redirectUser($_SESSION['referer_page_url']);
        }
        /* ] */
        /* saving of Related Products[ */


        if (!$sellerProdObj->addUpdateSellerRelatedProdcts($selprod_id, $relatedProducts)) {
            Message::addErrorMessage($sellerProdObj->getError());
            FatApp::redirectUser($_SESSION['referer_page_url']);
        }
        /* ] */

        $this->set('msg', Labels::getLabel('MSG_RECORD_UPDATED_SUCCESSFULLY', $this->siteLangId));
        $this->_template->render(false, false, 'json-success.php');
    }

    /*  - ---  ] Seller Product Links  ----- */

    public function linkPoliciesForm($product_id, $selprod_id, $ppoint_type)
    {
        $product_id = FatUtility::int($product_id);
        $ppoint_type = FatUtility::int($ppoint_type);
        $selprod_id = FatUtility::int($selprod_id);
        if ($product_id <= 0 || $selprod_id <= 0 || $ppoint_type <= 0) {
            FatUtility::dieWithError(Labels::getLabel('MSG_INVALID_REQUEST', $this->siteLangId));
        }
        $productRow = Product::getAttributesById($product_id, array('product_type'));
        $frm = $this->getLinkPoliciesForm($selprod_id, $ppoint_type);
        $data = array('selprod_id' => $selprod_id);
        $frm->fill($data);
        $this->set('product_id', $product_id);
        $this->set('selprod_id', $selprod_id);
        $this->set('frm', $frm);
        $this->set('language', Language::getAllNames());
        $this->set('activeTab', 'GENERAL');
        $this->set('product_type', $productRow['product_type']);
        $this->set('ppoint_type', $ppoint_type);
        $this->_template->render(false, false);
    }

    /* public function searchPoliciesToLink()
    {
        $selprod_id = FatApp::getPostedData('selprod_id', FatUtility::VAR_INT, 0);
        $ppoint_type = FatApp::getPostedData('ppoint_type', FatUtility::VAR_INT, 0);
        $searchForm = $this->getLinkPoliciesForm($selprod_id, $ppoint_type);
        $data = FatApp::getPostedData();
        $page = (empty($data['page']) || $data['page'] <= 0) ? 1 : $data['page'];
        $pagesize = FatApp::getConfig('CONF_PAGE_SIZE', FatUtility::VAR_INT, 10);
        $post = $searchForm->getFormDataFromArray($data);
        $srch = PolicyPoint::getSearchObject($this->siteLangId);
        $srch->joinTable('tbl_seller_product_policies', 'left outer join', 'spp.sppolicy_ppoint_id = pp.ppoint_id and spp.sppolicy_selprod_id=' . $selprod_id, 'spp');
        $srch->addCondition('pp.ppoint_type', '=', $ppoint_type);
        $srch->addMultipleFields(array('*', 'ifnull(sppolicy_selprod_id,0) selProdId'));
        $srch->setPageNumber($page);
        $srch->setPageSize($pagesize);
        $srch->addOrder('selProdId', 'desc');
        $records = FatApp::getDb()->fetchAll($srch->getResultSet(), 'ppoint_id');
        $this->set("selprod_id", $selprod_id);
        $this->set("arrListing", $records);
        $this->set('pageCount', $srch->pages());
        $this->set('recordCount', $srch->recordCount());
        $this->set('page', $page);
        $this->set('pageSize', $pagesize);
        $this->set('postedData', $post);
        $this->_template->render(false, false, 'seller/search-policies-to-link.php', false, false);
    } */

    public function getSpecialPriceDiscountString()
    {
        $post = FatApp::getPostedData();
        $str = Labels::getLabel("LBL_[Save_nn_(XX%_Off)]", $this->siteLangId);
        $str = str_replace(array("nn", "Nn", "NN", "nN"), CommonHelper::displayMoneyFormat($post['splprice_display_list_price']), $str);
        if ($post['splprice_display_dis_type'] == applicationConstants::PERCENTAGE) {
            $str = str_replace(array("XX", "xx", "Xx", "xX"), $post['splprice_display_dis_val'], $str);
        } elseif ($post['splprice_display_dis_type'] == applicationConstants::FLAT) {
            $str = str_replace(array("XX%", "xx%", "Xx%", "xX%"), CommonHelper::displayMoneyFormat($post['splprice_display_dis_val']), $str);
        } else {
            $str = str_replace(array("XX%", "xx%", "Xx%", "xX%"), CommonHelper::displayMoneyFormat($post['splprice_display_dis_val']), $str);
        }
        echo $str;
    }

    private function getLinkPoliciesForm($selprod_id, $ppoint_type)
    {
        $frm = new Form('frmLinkWarrantyPolicies');
        $frm->addHiddenField('', 'selprod_id', $selprod_id);
        $frm->addHiddenField('', 'ppoint_type', $ppoint_type);
        $frm->addHiddenField('', 'page');
        return $frm;
    }

    public function addPolicyPoint()
    {
        $post = FatApp::getPostedData();
        if (empty($post['selprod_id']) || empty($post['ppoint_id'])) {
            Message::addErrorMessage($this->str_invalid_request);
            FatUtility::dieWithError(Message::getHtml());
        }
        $selprod_id = FatUtility::int($post['selprod_id']);
        $ppoint_id = FatUtility::int($post['ppoint_id']);
        $dataToSave = array('sppolicy_ppoint_id' => $ppoint_id, 'sppolicy_selprod_id' => $selprod_id);
        $obj = new SellerProduct();
        if (!$obj->addPolicyPointToSelProd($dataToSave)) {
            Message::addErrorMessage($obj->getError());
            FatUtility::dieWithError(Message::getHtml());
        }
        FatUtility::dieJsonSuccess(Labels::getLabel("MSG_Policy_Added_Successfully", $this->siteLangId));
    }

    public function removePolicyPoint()
    {
        $post = FatApp::getPostedData();
        if (empty($post['selprod_id']) || empty($post['ppoint_id'])) {
            Message::addErrorMessage($this->str_invalid_request);
            FatUtility::dieWithError(Message::getHtml());
        }
        $selprod_id = FatUtility::int($post['selprod_id']);
        $ppoint_id = FatUtility::int($post['ppoint_id']);
        $whereCond = array('smt' => 'sppolicy_ppoint_id = ? and sppolicy_selprod_id = ?', 'vals' => array($ppoint_id, $selprod_id));
        $db = FatApp::getDb();
        if (!$db->deleteRecords(SellerProduct::DB_TBL_SELLER_PROD_POLICY, $whereCond)) {
            Message::addErrorMessage($db->getError());
            FatUtility::dieWithError(Message::getHtml());
        }
        FatUtility::dieJsonSuccess(Labels::getLabel("LBL_Policy_Removed_Successfully", $this->siteLangId));
    }

    public function deleteBulkSellerProducts()
    {
        $this->userPrivilege->canEditProducts(UserAuthentication::getLoggedUserId());
        $selprodId_arr = FatUtility::int(FatApp::getPostedData('selprod_ids'));
        if (empty($selprodId_arr)) {
            FatUtility::dieWithError(
                Labels::getLabel('MSG_INVALID_REQUEST', $this->siteLangId)
            );
        }
        foreach ($selprodId_arr as $selprod_id) {
            $this->deleteSellerProduct($selprod_id);
        }
        FatUtility::dieJsonSuccess(
            Labels::getLabel('MSG_RECORD_DELETED_SUCCESSFULLY', $this->siteLangId)
        );
    }

    public function sellerProductDelete()
    {
        $selprod_id = FatApp::getPostedData('id', FatUtility::VAR_INT, 0);

        $this->deleteSellerProduct($selprod_id);

        FatUtility::dieJsonSuccess(
            Labels::getLabel('MSG_RECORD_DELETED_SUCCESSFULLY', $this->siteLangId)
        );
    }

    private function deleteSellerProduct($selprod_id)
    {
        $this->userPrivilege->canEditProducts(UserAuthentication::getLoggedUserId());
        $selprod_id = FatUtility::int($selprod_id);
        if (1 > $selprod_id) {
            FatUtility::dieJsonError(Labels::getLabel('ERR_INVALID_REQUEST_ID', $this->siteLangId));
        }

        $selprodObj = new SellerProduct($selprod_id);
        if (!$selprodObj->deleteSellerProduct($selprod_id)) {
            FatUtility::dieJsonError(Labels::getLabel('ERR_INVALID_REQUEST_ID', $this->siteLangId));
        }
    }

    public function sellerProductCloneForm($product_id, $selprod_id)
    {
        if (!UserPrivilege::isUserHasValidSubsription($this->userParentId)) {
            Message::addErrorMessage(Labels::getLabel("ERR_Please_buy_subscription", $this->siteLangId));
            FatUtility::dieWithError(Message::getHtml());
        }
        if (FatApp::getConfig('CONF_ENABLE_SELLER_SUBSCRIPTION_MODULE', FatUtility::VAR_INT, 0) && SellerProduct::getActiveCount($this->userParentId) >= SellerPackages::getAllowedLimit($this->userParentId, $this->siteLangId, 'ossubs_inventory_allowed')) {
            Message::addErrorMessage(Labels::getLabel("ERR_You_have_crossed_your_package_limit", $this->siteLangId));
            FatUtility::dieWithError(Message::getHtml());
        }
        $selprod_id = FatUtility::int($selprod_id);
        $product_id = FatUtility::int($product_id);
        $userId = $this->userParentId;

        $sellerProductRow = SellerProduct::getAttributesById($selprod_id, array('selprod_user_id', 'selprod_id', 'selprod_product_id', 'selprod_url_keyword', 'selprod_cost', 'selprod_price', 'selprod_stock', 'selprod_return_age', 'selprod_cancellation_age'), false, true);

        if ($sellerProductRow['selprod_user_id'] != $this->userParentId) {
            Message::addErrorMessage(Labels::getLabel('ERR_INVALID_ACCESS', $this->siteLangId));
            FatUtility::dieWithError(Message::getHtml());
        }
        $sellerProductRow['selprod_available_from'] = date('Y-m-d');
        $frm = $this->getSellerProductCloneForm($product_id, $selprod_id);

        $returnAge = isset($sellerProductRow['selprod_return_age']) ? FatUtility::int($sellerProductRow['selprod_return_age']) : '';
        $cancellationAge = isset($sellerProductRow['selprod_cancellation_age']) ? FatUtility::int($sellerProductRow['selprod_cancellation_age']) : '';

        if ('' === $returnAge || '' === $cancellationAge) {
            $sellerProductRow['use_shop_policy'] = 1;
        }
        $frm->fill($sellerProductRow);

        $this->set('frm', $frm);
        $this->set('userId', $this->userParentId);
        $this->set('selprod_id', $sellerProductRow['selprod_id']);
        $this->set('product_id', $sellerProductRow['selprod_product_id']);
        $this->_template->render(false, false);
    }

    public function getSellerProductCloneForm($product_id, $selprod_id)
    {
        $frm = new Form('frmSellerProduct');
        $productData = Product::getAttributesById($product_id, array('product_identifier', 'product_min_selling_price', 'product_type'));

        $productOptions = Product::getProductOptions($product_id, $this->siteLangId, true);
        if ($productOptions) {
            foreach ($productOptions as $option) {
                $option_name = ($option['option_name'] != '') ? $option['option_name'] : $option['option_identifier'];
                $fld = $frm->addSelectBox($option_name, 'selprodoption_optionvalue_id[' . $option['option_id'] . ']', $option['optionValues'], '', array('class' => 'selprodoption_optionvalue_id'), Labels::getLabel('LBL_Select', $this->siteLangId));
                $fld->requirements()->setRequired();
            }
        }
        $frm->addTextBox(Labels::getLabel('FRM_URL_KEYWORD', $this->siteLangId), 'selprod_url_keyword')->requirements()->setRequired();

        $costPrice = $frm->addFloatField(Labels::getLabel('FRM_COST_PRICE', $this->siteLangId) . ' [' . CommonHelper::getCurrencySymbol(true) . ']', 'selprod_cost');
        $costPrice->requirements()->setPositive();

        $fld = $frm->addFloatField(Labels::getLabel('FRM_PRICE', $this->siteLangId) . ' [' . CommonHelper::getCurrencySymbol(true) . ']', 'selprod_price');
        if (isset($productData['product_min_selling_price'])) {
            $fld->requirements()->setRange($productData['product_min_selling_price'], 9999999999);
            $fld->requirements()->setCustomErrorMessage(Labels::getLabel('FRM_MINIMUM_SELLING_PRICE_FOR_THIS_PRODUCT_IS', $this->siteLangId) . ' ' . CommonHelper::displayMoneyFormat($productData['product_min_selling_price'], true, true));
        }

        if (0 < FatApp::getConfig('CONF_RFQ_MODULE', FatUtility::VAR_INT, 0) && 1 > FatApp::getConfig('CONF_HIDE_PRICES', FatUtility::VAR_INT, 0)) {
            $shopRfqEnabled = Shop::getAttributesByUserId($this->userParentId, 'shop_rfq_enabled', false);
            if (0 < $shopRfqEnabled) {
                $cartTypeFld = $frm->addSelectBox(Labels::getLabel('FRM_CART_TYPE', $this->siteLangId), 'selprod_cart_type', SellerProduct::getCartType(), SellerProduct::CART_TYPE_BOTH, array('class' => 'fieldsVisibilityJs onlyShowHideJs'), '');
                $cartTypeFld->requirements()->setRequired();
                $frm->addCheckBox(Labels::getLabel("FRM_HIDE_PRICE", $this->siteLangId), 'selprod_hide_price', 1, array(), false, 0);

                $hidePriceReqFld = new FormFieldRequirement('selprod_hide_price', Labels::getLabel('FRM_HIDE_PRICE', $this->siteLangId));
                $hidePriceReqFld->setRequired(true);
                $hidePriceReqFld->setPositive();

                $hidePriceUnReqFld = new FormFieldRequirement('selprod_hide_price', Labels::getLabel('FRM_HIDE_PRICE', $this->siteLangId));
                $hidePriceUnReqFld->setRequired(false);
                $hidePriceUnReqFld->setPositive();

                $cartTypeFld->requirements()->addOnChangerequirementUpdate(SellerProduct::CART_TYPE_RFQ_ONLY, 'eq', 'selprod_hide_price', $hidePriceReqFld);
                $cartTypeFld->requirements()->addOnChangerequirementUpdate(SellerProduct::CART_TYPE_RFQ_ONLY, 'ne', 'selprod_hide_price', $hidePriceUnReqFld);
            }
        }

        if ($productData['product_type'] != Product::PRODUCT_TYPE_SERVICE) {
            $fld = $frm->addIntegerField(Labels::getLabel('FRM_QUANTITY', $this->siteLangId), 'selprod_stock');
            $fld->requirements()->setRange(1, SellerProduct::MAX_RANGE_OF_AVAILBLE_QTY);
        }

        $frm->addDateField(Labels::getLabel('FRM_DATE_AVAILABLE', $this->siteLangId), 'selprod_available_from', '', array('readonly' => 'readonly'))->requirements()->setRequired();


        if (Product::PRODUCT_TYPE_PHYSICAL == $productData['product_type']) {
            $useShopPolicy = $frm->addCheckBox(Labels::getLabel('FRM_USE_SHOP_RETURN_AND_CANCELLATION_AGE_POLICY', $this->siteLangId), 'use_shop_policy', 1, ['id' => 'use_shop_policy'], false, 0);
            $fld = $frm->addIntegerField(Labels::getLabel('FRM_ORDER_RETURN_AGE', $this->siteLangId), 'selprod_return_age');
            $fld->htmlAfterField = '<span class="form-text text-muted">' . Labels::getLabel('FRM_WARRANTY_IN_DAYS', $this->siteLangId) . ' </span>';

            $orderReturnAgeReqFld = new FormFieldRequirement('selprod_return_age', Labels::getLabel('FRM_ORDER_RETURN_AGE', $this->siteLangId));
            $orderReturnAgeReqFld->setRequired(true);
            $orderReturnAgeReqFld->setPositive();

            $orderReturnAgeUnReqFld = new FormFieldRequirement('selprod_return_age', Labels::getLabel('FRM_ORDER_RETURN_AGE', $this->siteLangId));
            $orderReturnAgeUnReqFld->setRequired(false);
            $orderReturnAgeUnReqFld->setPositive();

            $fld = $frm->addIntegerField(Labels::getLabel('FRM_ORDER_CANCELLATION_AGE', $this->siteLangId), 'selprod_cancellation_age');
            $fld->htmlAfterField = '<span class="form-text text-muted">' . Labels::getLabel('FRM_WARRANTY_IN_DAYS', $this->siteLangId) . ' </span>';

            $orderCancellationAgeReqFld = new FormFieldRequirement('selprod_cancellation_age', Labels::getLabel('FRM_ORDER_CANCELLATION_AGE', $this->siteLangId));
            $orderCancellationAgeReqFld->setRequired(true);
            $orderCancellationAgeReqFld->setPositive();

            $orderCancellationAgeUnReqFld = new FormFieldRequirement('selprod_cancellation_age', Labels::getLabel('FRM_ORDER_CANCELLATION_AGE', $this->siteLangId));
            $orderCancellationAgeUnReqFld->setRequired(false);
            $orderCancellationAgeUnReqFld->setPositive();

            $useShopPolicy->requirements()->addOnChangerequirementUpdate(Shop::USE_SHOP_POLICY, 'eq', 'selprod_return_age', $orderReturnAgeUnReqFld);
            $useShopPolicy->requirements()->addOnChangerequirementUpdate(Shop::USE_SHOP_POLICY, 'ne', 'selprod_return_age', $orderReturnAgeReqFld);

            $useShopPolicy->requirements()->addOnChangerequirementUpdate(Shop::USE_SHOP_POLICY, 'eq', 'selprod_cancellation_age', $orderCancellationAgeUnReqFld);
            $useShopPolicy->requirements()->addOnChangerequirementUpdate(Shop::USE_SHOP_POLICY, 'ne', 'selprod_cancellation_age', $orderCancellationAgeReqFld);
        }

        $frm->addHiddenField('', 'selprod_product_id', $product_id);
        $frm->addHiddenField('', 'selprod_id', $selprod_id);
        return $frm;
    }

    public function setUpSellerProductClone()
    {
        $this->userPrivilege->canEditProducts(UserAuthentication::getLoggedUserId());
        $post = FatApp::getPostedData();
        $useShopPolicy = FatApp::getPostedData('use_shop_policy', FatUtility::VAR_INT, 0);
        $selprod_id = Fatutility::int($post['selprod_id']);

        $selprod_product_id = Fatutility::int($post['selprod_product_id']);

        if (!UserPrivilege::isUserHasValidSubsription($this->userParentId)) {
            FatUtility::dieJsonError(Labels::getLabel("ERR_PLEASE_BUY_SUBSCRIPTION", $this->siteLangId));
        }
        if (!$selprod_product_id) {
            FatUtility::dieJsonError($this->str_invalid_request);
        }

        $productRow = Product::getAttributesById($selprod_product_id, array('product_id', 'product_active', 'product_seller_id', 'product_added_by_admin_id'));
        if (!$productRow) {
            FatUtility::dieJsonError($this->str_invalid_request);
        }
        if (($productRow['product_seller_id'] != $this->userParentId) && $productRow['product_added_by_admin_id'] == 0) {
            FatUtility::dieJsonError($this->str_invalid_request);
        }
        $frm = $this->getSellerProductCloneForm($selprod_product_id, $selprod_id);
        $post['use_shop_policy'] = $useShopPolicy;
        $post = $frm->getFormDataFromArray($post);
        if (false === $post) {
            FatUtility::dieJsonError(current($frm->getValidationErrors()));
        }
        /* Validate product belongs to current logged seller[ */
        if ($selprod_id) {
            $sellerProductRow = SellerProduct::getAttributesById($selprod_id, null, true, true);
            if ($sellerProductRow['selprod_user_id'] != $this->userParentId) {
                Message::addErrorMessage(Labels::getLabel('ERR_INVALID_ACCESS', $this->siteLangId));
                FatUtility::dieWithError(Message::getHtml());
            }
        }
        /* ] */
        $post['selprod_url_keyword'] = strtolower(CommonHelper::createSlug($post['selprod_url_keyword']));

        $options = array();
        if (isset($post['selprodoption_optionvalue_id']) && count($post['selprodoption_optionvalue_id'])) {
            $options = $post['selprodoption_optionvalue_id'];
            unset($post['selprodoption_optionvalue_id']);
        }
        asort($options);
        $sellerProdObj = new SellerProduct();
        $selProdCode = $productRow['product_id'] . '_' . implode('_', $options);
        $sellerProductRow['selprod_code'] = $selProdCode;

        $selProdAvailable = Product::isSellProdAvailableForUser($selProdCode, $this->siteLangId, $this->userParentId, 0);

        unset($sellerProductRow['selprod_id']);
        $data_to_be_save = $sellerProductRow;
        $data_to_be_save['selprod_price'] = $post['selprod_price'];
        $data_to_be_save['selprod_stock'] = (isset($post['selprod_stock']) && $post['selprod_stock'] > 0) ? $post['selprod_stock'] : 1;
        $data_to_be_save['selprod_available_from'] = $post['selprod_available_from'];
        $data_to_be_save['selprod_cart_type'] = $post['selprod_cart_type'] ?? 0;
        $data_to_be_save['selprod_hide_price'] = (SellerProduct::CART_TYPE_RFQ_ONLY != $post['selprod_cart_type'] ? 0 : FatApp::getPostedData('selprod_hide_price', FatUtility::VAR_INT, 0));

        if (!empty($selProdAvailable)) {
            if (!$selProdAvailable['selprod_deleted']) {
                FatUtility::dieWithError(Labels::getLabel("LBL_Inventory_for_this_option_have_been_added", $this->siteLangId));
            }
            $sellerProdObj = new SellerProduct($selProdAvailable['selprod_id']);
            $data_to_be_save['selprod_deleted'] = applicationConstants::NO;
            $sellerProdObj->assignValues($data_to_be_save);
            if (!$sellerProdObj->save()) {
                FatUtility::dieJsonError($sellerProdObj->getError());
            }
            $this->set('msg', Labels::getLabel('MSG_PRODUCT_WAS_DELETED._REACTIVATE_THE_SAME', $this->siteLangId));
            $this->_template->render(false, false, 'json-success.php');
        } else {
            $data_to_be_save['selprod_user_id'] = $this->userParentId;
            $data_to_be_save['selprod_added_on'] = date("Y-m-d H:i:s");
            $sellerProdObj->assignValues($data_to_be_save);

            if (!$sellerProdObj->save()) {
                FatUtility::dieJsonError($sellerProdObj->getError());
            }
        }

        $selprod_id = $sellerProdObj->getMainTableRecordId();

        if (!empty($selprod_id)) {
            $selProdSpecificsObj = new SellerProductSpecifics($selprod_id);
            if (0 < $useShopPolicy) {
                if (!$selProdSpecificsObj->deleteRecord()) {
                    FatUtility::dieJsonError($selProdSpecificsObj->getError());
                }
            } else {
                $post['sps_selprod_id'] = $selprod_id;
                $selProdSpecificsObj->assignValues($post);
                $selProdSepc = $selProdSpecificsObj->getFlds();
                if (!$selProdSpecificsObj->addNew(array(), $selProdSepc)) {
                    FatUtility::dieJsonError($selProdSpecificsObj->getError());
                }
            }
        }

        $sellerProdObj->rewriteUrlProduct($post['selprod_url_keyword']);
        $sellerProdObj->rewriteUrlReviews($post['selprod_url_keyword']);
        $sellerProdObj->rewriteUrlMoreSellers($post['selprod_url_keyword']);

        /* save options data, if any[ */
        if ($selprod_id) {
            if (!$sellerProdObj->addUpdateSellerProductOptions($selprod_id, $options)) {
                FatUtility::dieJsonError($sellerProdObj->getError());
            }
        }
        /* ] */

        $languages = Language::getAllNames();

        /* Clone seller product Lang Data and SEO data automatically[ */

        $metaData = array();

        $tabsArr = MetaTag::getTabsArr();
        $metaType = MetaTag::META_GROUP_PRODUCT_DETAIL;

        if ($metaType == '' || !isset($tabsArr[$metaType])) {
            FatUtility::dieJsonError(Labels::getLabel("ERR_INVALID_ACCESS", $this->siteLangId));
        }

        $metaData['meta_controller'] = $tabsArr[$metaType]['controller'];
        $metaData['meta_action'] = $tabsArr[$metaType]['action'];
        $metaData['meta_record_id'] = $selprod_id;
        $metaIdentifier = SellerProduct::getProductDisplayTitle($selprod_id, FatApp::getConfig('CONF_DEFAULT_SITE_LANG', FatUtility::VAR_INT, 1));
        $meta = new MetaTag();

        /* $count = 1;
          while ($metaRow = MetaTag::getAttributesByIdentifier($metaIdentifier, array('meta_identifier'))) {
          $metaIdentifier = $metaRow['meta_identifier']."-".$count;
          $count++;
          }
          $metaData['meta_identifier'] = $metaIdentifier; */
        $meta->assignValues($metaData);

        if (!$meta->save()) {
            FatUtility::dieJsonError($meta->getError());
        }
        $metaId = $meta->getMainTableRecordId();

        foreach ($languages as $langId => $langName) {
            $langData = SellerProduct::getAttributesByLangId($langId, $post['selprod_id']);
            $langData = array(
                'selprodlang_selprod_id' => $selprod_id,
                'selprod_title' => SellerProduct::getProductDisplayTitle($selprod_id, $langId)
            );
            if (!$sellerProdObj->updateLangData($langId, $langData)) {
                FatUtility::dieJsonError($sellerProdObj->getError());
            }

            $selProdMeta = array(
                'metalang_lang_id' => $langId,
                'metalang_meta_id' => $metaId,
                'meta_title' => SellerProduct::getProductDisplayTitle($selprod_id, $langId),
            );

            $metaObj = new MetaTag($metaId);

            if (!$metaObj->updateLangData($langId, $selProdMeta)) {
                FatUtility::dieJsonError($metaObj->getError());
            }
        }

        /* ] */

        /* Search policies to link [ */
        $srch = PolicyPoint::getSearchObject($this->siteLangId);
        $srch->joinTable('tbl_seller_product_policies', 'left outer join', 'spp.sppolicy_ppoint_id = pp.ppoint_id and spp.sppolicy_selprod_id=' . $post['selprod_id'], 'spp');
        $srch->addMultipleFields(array('*', 'ifnull(sppolicy_selprod_id,0) selProdId'));
        $srch->addCondition('sppolicy_selprod_id', '=', $post['selprod_id']);
        $srch->doNotCalculateRecords();
        $policies = FatApp::getDb()->fetchAll($srch->getResultSet(), 'ppoint_id');
        foreach ($policies as $linkData) {
            $dataToSave = array('sppolicy_selprod_id' => $selprod_id, 'sppolicy_ppoint_id' => $linkData['sppolicy_ppoint_id']);
            if (!$sellerProdObj->addPolicyPointToSelProd($dataToSave)) {
                FatUtility::dieJsonError($sellerProdObj->getError());
            }
        }
        /* ] */

        $this->set('msg', Labels::getLabel('MSG_PRODUCT_SETUP_SUCCESSFUL', $this->siteLangId));
        $this->_template->render(false, false, 'json-success.php');
    }

    public function toggleBulkStatuses()
    {
        $status = FatApp::getPostedData('status', FatUtility::VAR_INT, -1);
        $selprodIdsArr = FatUtility::int(FatApp::getPostedData('selprod_ids'));
        if (empty($selprodIdsArr) || -1 == $status) {
            FatUtility::dieWithError(
                Labels::getLabel('MSG_INVALID_REQUEST', $this->siteLangId)
            );
        }

        $invAllowedLimit = SellerPackages::getAllowedLimit($this->userParentId, $this->siteLangId, 'ossubs_inventory_allowed');
        $msg = '';
        foreach ($selprodIdsArr as $selprod_id) {
            if (1 > $selprod_id) {
                continue;
            }

            $productCount = SellerProduct::getActiveCount($this->userParentId);
            if (FatApp::getConfig('CONF_ENABLE_SELLER_SUBSCRIPTION_MODULE', FatUtility::VAR_INT, 0) && applicationConstants::ACTIVE == $status && -1 != $invAllowedLimit && $invAllowedLimit <= $productCount) {
                $msg = Labels::getLabel('ERR_UNABLE_To_ACTIVATE_SOME_OF_THE_PRODUCTS._AS_YOU_HAVE_CROSSED_YOUR_PACKAGE_LIMIT.', $this->siteLangId);
                break;
            }

            $this->updateSellerProductStatus($selprod_id, $status);
        }
        $msg = !empty($msg) ? $msg : Labels::getLabel('MSG_Status_changed_Successfully', $this->siteLangId);
        $this->set('msg', $msg);
        $this->_template->render(false, false, 'json-success.php');
    }

    public function changeProductStatus()
    {
        $this->userPrivilege->canEditProducts(UserAuthentication::getLoggedUserId());
        $selprodId = FatApp::getPostedData('selprodId', FatUtility::VAR_INT, 0);

        $sellerProductData = SellerProduct::getAttributesById($selprodId, array('selprod_active'));

        if (!$sellerProductData) {
            LibHelper::exitWithError(Labels::getLabel('ERR_INVALID_REQUEST', $this->siteLangId), true);
        }

        $status = ($sellerProductData['selprod_active'] == applicationConstants::ACTIVE) ? applicationConstants::INACTIVE : applicationConstants::ACTIVE;
        if ($status == applicationConstants::ACTIVE && FatApp::getConfig('CONF_ENABLE_SELLER_SUBSCRIPTION_MODULE', FatUtility::VAR_INT, 0) && SellerProduct::getActiveCount($this->userParentId) >= SellerPackages::getAllowedLimit($this->userParentId, $this->siteLangId, 'ossubs_inventory_allowed')) {
            LibHelper::exitWithError(Labels::getLabel('ERR_YOU_HAVE_CROSSED_YOUR_PACKAGE_LIMIT', $this->siteLangId), true);
        }

        $this->updateSellerProductStatus($selprodId, $status);

        $this->set('msg', Labels::getLabel('MSG_Status_changed_Successfully', $this->siteLangId));
        $this->_template->render(false, false, 'json-success.php');
    }

    private function updateSellerProductStatus($selprodId, $status)
    {
        $status = FatUtility::int($status);
        $selprodId = FatUtility::int($selprodId);
        if (1 > $selprodId || -1 == $status) {
            FatUtility::dieWithError(
                Labels::getLabel('MSG_INVALID_REQUEST', $this->siteLangId)
            );
        }

        $sellerProdObj = new SellerProduct($selprodId);
        if (!$sellerProdObj->changeStatus($status)) {
            Message::addErrorMessage($sellerProdObj->getError());
            FatUtility::dieWithError(Message::getHtml());
        }
        $productId = SellerProduct::getAttributesById($selprodId, 'selprod_product_id', false);
        Product::updateMinPrices($productId);
    }

    public function addVolumeDiscountForm()
    {
        $this->set('frm', SellerProduct::volumeDiscountForm($this->siteLangId));
        $this->set('includeTabs', false);
        $this->set('formTitle', Labels::getLabel('LBL_BIND_VOLUME_DISCOUNT', $this->siteLangId));
        $this->set('html', $this->_template->render(false, false, NULL, true));
        $this->_template->render(false, false, 'json-success.php', true, false);
    }

    public function volumeDiscount($selProd_id = 0)
    {
        $this->userPrivilege->canViewVolumeDiscount(UserAuthentication::getLoggedUserId());
        if (!UserPrivilege::isUserHasValidSubsription($this->userParentId)) {
            Message::addInfo(Labels::getLabel("MSG_Please_buy_subscription", $this->siteLangId));
            FatApp::redirectUser(UrlHelper::generateUrl('Seller', 'Packages'));
        }
        $selProd_id = FatUtility::int($selProd_id);
        if (0 < $selProd_id || 0 > $selProd_id) {
            $selProd_id = SellerProduct::getAttributesByID($selProd_id, 'selprod_id', false);
            if (empty($selProd_id)) {
                Message::addErrorMessage(Labels::getLabel('ERR_INVALID_REQUEST', $this->siteLangId));
                FatApp::redirectUser(UrlHelper::generateUrl('Seller', 'volumeDiscount'));
            }
        }

        $srchFrm = $this->getVolumeDiscountSearchForm();
        $selProdIdsArr = FatApp::getPostedData('selprod_ids', FatUtility::VAR_INT, 0);

        $dataToEdit = array();
        if (!empty($selProdIdsArr) || 0 < $selProd_id) {
            $selProdIdsArr = (0 < $selProd_id) ? array($selProd_id) : $selProdIdsArr;
            $productsTitle = SellerProduct::getProductDisplayTitle($selProdIdsArr, $this->siteLangId);
            foreach ($selProdIdsArr as $selProdId) {
                $dataToEdit[] = array(
                    'product_name' => html_entity_decode($productsTitle[$selProdId], ENT_QUOTES, 'UTF-8'),
                    'voldiscount_selprod_id' => $selProdId
                );
            }
        } else {
            $post = $srchFrm->getFormDataFromArray(FatApp::getPostedData());
            if (false === $post) {
                FatUtility::dieJsonError(current($srchFrm->getValidationErrors()));
            } else {
                unset($post['btn_submit'], $post['btn_clear']);
                $srchFrm->fill($post);
            }
        }
        if (0 < $selProd_id) {
            $srchFrm->addHiddenField('', 'selprod_id', $selProd_id);
            $srchFrm->fill(array('keyword' => $productsTitle[$selProdId]));
        }
        $this->set("canEdit", $this->userPrivilege->canEditVolumeDiscount(UserAuthentication::getLoggedUserId(), true));
        $this->set("dataToEdit", $dataToEdit);
        $this->set("frmSearch", $srchFrm);
        $this->set("selProd_id", $selProd_id);

        $this->set("keywordPlaceholder", Labels::getLabel('LBL_SEARCH_BY_PRODUCT_NAME', $this->siteLangId));
        $this->set('deleteButton', true);
        $this->_template->addJs(array('js/select2.js'));
        $this->_template->addCss(array('css/select2.min.css'));
        $this->_template->render();
    }

    public function searchVolumeDiscountProducts()
    {
        $this->userPrivilege->canViewVolumeDiscount(UserAuthentication::getLoggedUserId());
        $userId = $this->userParentId;
        $page = FatApp::getPostedData('page', FatUtility::VAR_INT, 1);
        $selProdId = FatApp::getPostedData('selprod_id', FatUtility::VAR_INT, 0);
        $keyword = FatApp::getPostedData('keyword', FatUtility::VAR_STRING, '');
        $pagesize = FatApp::getConfig('CONF_PAGE_SIZE', FatUtility::VAR_INT, 10);
        $srch = SellerProduct::searchVolumeDiscountProducts($this->siteLangId, $selProdId, $keyword, $userId);
        $post = FatApp::getPostedData();
        $this->setRecordCount(clone $srch, $pagesize, $page, $post);
        $srch->doNotCalculateRecords();
        $srch->addMultipleFields(
            [
                'selprod_id',
                'credential_username',
                'voldiscount_min_qty',
                'voldiscount_percentage',
                'IFNULL(product_name, product_identifier) as product_name',
                'selprod_title',
                'voldiscount_id',
                'product_updated_on',
                'selprod_product_id',
                'user_id',
                'user_updated_on',
                'credential_email',
                'user_name'
            ]
        );
        $srch->addOrder('voldiscount_id', 'DESC');
        $srch->setPageSize($pagesize);
        $srch->setPageNumber($page);
        $arrListing = FatApp::getDb()->fetchAll($srch->getResultSet());
        if (count($arrListing)) {
            foreach ($arrListing as &$arr) {
                $arr['options'] = SellerProduct::getSellerProductOptions($arr['selprod_id'], true, $this->siteLangId);
            }
        }
        $this->set("arrListing", $arrListing);
        $this->set('canEdit', $this->userPrivilege->canEditVolumeDiscount(UserAuthentication::getLoggedUserId(), true));
        $this->set('postedData', $post);
        $this->_template->render(false, false);
    }

    private function getVolumeDiscountSearchForm()
    {
        $frm = new Form('frmSearch', array('id' => 'frmSearch'));
        $frm->setRequiredStarWith('caption');
        $frm->addHiddenField('', 'total_record_count');
        $frm->addTextBox(Labels::getLabel('BTN_KEYWORD', $this->siteLangId), 'keyword');
        HtmlHelper::addSearchButton($frm);
        return $frm;
    }

    public function updateVolumeDiscountRow()
    {
        $this->userPrivilege->canEditVolumeDiscount(UserAuthentication::getLoggedUserId());
        $data = FatApp::getPostedData();

        if (empty($data)) {
            FatUtility::dieJsonError(Labels::getLabel('ERR_INVALID_REQUEST', $this->siteLangId));
        }

        $selprod_id = FatUtility::int($data['voldiscount_selprod_id']);
        if (1 > $selprod_id) {
            FatUtility::dieJsonError(Labels::getLabel('ERR_INVALID_REQUEST', $this->siteLangId));
        }

        $qty = FatApp::getPostedData('voldiscount_min_qty', FatUtility::VAR_INT, 0);
        if (2 > $qty) {
            FatUtility::dieJsonError(Labels::getLabel('ERR_MINIMUM_QUANTITY_SHOULD_BE_GREATER_THAN_1', $this->siteLangId));
        }

        $volDiscountId = $this->updateSelProdVolDiscount($selprod_id, 0, $data['voldiscount_min_qty'], $data['voldiscount_percentage']);
        if (!$volDiscountId) {
            FatUtility::dieJsonError(Labels::getLabel('ERR_INVALID_RESPONSE', $this->siteLangId));
        }

        // last Param of getProductDisplayTitle function used to get title in html form.
        $productName = SellerProduct::getProductDisplayTitle($data['voldiscount_selprod_id'], $this->siteLangId, true);

        $data['product_name'] = $productName;
        $this->set('post', $data);
        $this->set('volDiscountId', $volDiscountId);
        $json = array(
            'status' => true,
            'msg' => Labels::getLabel('LBL_Volume_Discount_Setup_Successful', $this->siteLangId),
            'data' => $this->_template->render(false, false, 'seller/update-volume-discount-row.php', true)
        );
        FatUtility::dieJsonSuccess($json);
    }

    public function updateVolumeDiscountColValue()
    {
        $this->userPrivilege->canEditVolumeDiscount(UserAuthentication::getLoggedUserId());
        $volDiscountId = FatApp::getPostedData('voldiscount_id', FatUtility::VAR_INT, 0);
        if (1 > $volDiscountId) {
            FatUtility::dieJsonError(Labels::getLabel('ERR_INVALID_REQUEST', $this->siteLangId));
        }
        $attribute = FatApp::getPostedData('attribute', FatUtility::VAR_STRING, '');
        $columns = array('voldiscount_min_qty', 'voldiscount_percentage');
        if (!in_array($attribute, $columns)) {
            FatUtility::dieJsonError(Labels::getLabel('ERR_INVALID_REQUEST', $this->siteLangId));
        }

        $value = FatApp::getPostedData('value');
        if ('voldiscount_min_qty' == $attribute && 2 > $value) {
            FatUtility::dieJsonError(Labels::getLabel('ERR_MINIMUM_QUANTITY_SHOULD_BE_GREATER_THAN_1', $this->siteLangId));
        }

        $otherColumns = array_values(array_diff($columns, [$attribute]));
        $otherColumnsValue = SellerProductVolumeDiscount::getAttributesById($volDiscountId, $otherColumns);
        if (empty($otherColumnsValue)) {
            FatUtility::dieJsonError(Labels::getLabel('ERR_INVALID_REQUEST', $this->siteLangId));
        }
        $selProdId = FatApp::getPostedData('selProdId', FatUtility::VAR_INT, 0);

        $dataToUpdate = array(
            'voldiscount_id' => $volDiscountId,
            'voldiscount_selprod_id' => $selProdId,
            $attribute => $value
        );
        $dataToUpdate += $otherColumnsValue;

        $volDiscountId = $this->updateSelProdVolDiscount($selProdId, $volDiscountId, $dataToUpdate['voldiscount_min_qty'], $dataToUpdate['voldiscount_percentage']);
        if (!$volDiscountId) {
            FatUtility::dieJsonError(Labels::getLabel('ERR_INVALID_RESPONSE', $this->siteLangId));
        }

        $json = array(
            'status' => true,
            'msg' => Labels::getLabel('MSG_Success', $this->siteLangId),
            'data' => array('value' => CommonHelper::numberFormat($value))
        );
        FatUtility::dieJsonSuccess($json);
    }

    public function getRelatedProductsList($selprod_id)
    {
        $selprod_id = FatUtility::int($selprod_id);
        $srch = SellerProduct::searchRelatedProducts($this->siteLangId);
        $srch->addCondition('selprod_user_id', '=', 'mysql_func_' . $this->userParentId, 'AND', true);
        $srch->addCondition(SellerProduct::DB_TBL_RELATED_PRODUCTS_PREFIX . 'sellerproduct_id', '=', $selprod_id);
        $srch->addOrder('selprod_id', 'DESC');
        $srch->doNotCalculateRecords();
        $rs = $srch->getResultSet();
        $relatedProds = FatApp::getDb()->fetchAll($rs);
        $json = array(
            'selprodId' => $selprod_id,
            'relatedProducts' => $relatedProds
        );
        FatUtility::dieJsonSuccess($json);
    }

    private function getRelatedProductsForm()
    {
        $frm = new Form('frmRelatedSellerProduct');
        $prodName = $frm->addSelectBox(Labels::getLabel('FRM_PRODUCT', $this->siteLangId), 'selprod_id', []);
        $prodName->requirements()->setRequired();
        $frm->addSelectBox(Labels::getLabel('FRM_RELATED_PRODUCTS', $this->siteLangId), 'products_related[]', [], '');
        return $frm;
    }

    public function relatedProductsForm()
    {
        $this->set("frm", $this->getRelatedProductsForm());
        $this->set('includeTabs', false);
        $this->set('html', $this->_template->render(false, false, NULL, true));
        $this->_template->render(false, false, 'json-success.php', true, false);
    }

    public function relatedProducts($selProd_id = 0)
    {
        $this->userPrivilege->canViewRelatedProducts(UserAuthentication::getLoggedUserId());
        if (!UserPrivilege::isUserHasValidSubsription($this->userParentId)) {
            Message::addInfo(Labels::getLabel("MSG_Please_buy_subscription", $this->siteLangId));
            FatApp::redirectUser(UrlHelper::generateUrl('Seller', 'Packages'));
        }
        $selProd_id = FatUtility::int($selProd_id);
        if (0 < $selProd_id || 0 > $selProd_id) {
            $selProd_id = SellerProduct::getAttributesByID($selProd_id, 'selprod_id', false);
            if (empty($selProd_id)) {
                Message::addErrorMessage(Labels::getLabel('ERR_INVALID_REQUEST', $this->siteLangId));
                FatApp::redirectUser(UrlHelper::generateUrl('Seller', 'volumeDiscount'));
            }
        }

        $srchFrm = $this->getVolumeDiscountSearchForm();
        $selProdIdsArr = FatApp::getPostedData('selprod_ids', FatUtility::VAR_INT, 0);

        $dataToEdit = array();
        if (!empty($selProdIdsArr) || 0 < $selProd_id) {
            $selProdIdsArr = (0 < $selProd_id) ? array($selProd_id) : $selProdIdsArr;
            $productsTitle = SellerProduct::getProductDisplayTitle($selProdIdsArr, $this->siteLangId);
            foreach ($selProdIdsArr as $selProdId) {
                $dataToEdit[] = array(
                    'product_name' => html_entity_decode($productsTitle[$selProdId], ENT_QUOTES, 'UTF-8'),
                    'voldiscount_selprod_id' => $selProdId
                );
            }
        } else {
            $post = $srchFrm->getFormDataFromArray(FatApp::getPostedData());
            if (false === $post) {
                FatUtility::dieJsonError(current($srchFrm->getValidationErrors()));
            } else {
                unset($post['btn_submit'], $post['btn_clear']);
                $srchFrm->fill($post);
            }
        }
        if (0 < $selProd_id) {
            $srchFrm->addHiddenField('', 'selprod_id', $selProd_id);
            $srchFrm->fill(array('keyword' => $productsTitle[$selProdId]));
        }
        $this->set("canEdit", $this->userPrivilege->canEditRelatedProducts(UserAuthentication::getLoggedUserId(), true));
        $this->set("dataToEdit", $dataToEdit);
        $this->set("frmSearch", $srchFrm);
        $this->set("selProd_id", $selProd_id);
        $this->set("keywordPlaceholder", Labels::getLabel('LBL_SEARCH_BY_PRODUCT_NAME', $this->siteLangId));
        $this->_template->addJs(array('js/select2.js'));
        $this->_template->addCss(array('css/select2.min.css'));
        $this->_template->render();
    }

    public function searchRelatedProducts()
    {
        $this->userPrivilege->canViewRelatedProducts(UserAuthentication::getLoggedUserId());
        $userId = $this->userParentId;
        $page = FatApp::getPostedData('page', FatUtility::VAR_INT, 1);
        $selProdId = FatApp::getPostedData('selprod_id', FatUtility::VAR_INT, 0);
        $keyword = FatApp::getPostedData('keyword', FatUtility::VAR_STRING, '');
        $pagesize = FatApp::getConfig('CONF_PAGE_SIZE', FatUtility::VAR_INT, 10);
        $post = FatApp::getPostedData();

        $srch = new SearchBase(SellerProduct::DB_TBL_RELATED_PRODUCTS);

        $srch->joinTable(SellerProduct::DB_TBL, 'INNER JOIN', SellerProduct::DB_TBL_PREFIX . 'id = ' . SellerProduct::DB_TBL_RELATED_PRODUCTS_PREFIX . 'sellerproduct_id');
        $srch->joinTable(SellerProduct::DB_TBL . '_lang', 'LEFT JOIN', 'slang.' . SellerProduct::DB_TBL_LANG_PREFIX . 'selprod_id = ' . SellerProduct::DB_TBL_RELATED_PRODUCTS_PREFIX . 'sellerproduct_id AND ' . SellerProduct::DB_TBL_LANG_PREFIX . 'lang_id = ' . $this->siteLangId, 'slang');
        $srch->joinTable(Product::DB_TBL, 'LEFT JOIN', Product::DB_TBL_PREFIX . 'id = ' . SellerProduct::DB_TBL_PREFIX . 'product_id');
        $srch->joinTable(Product::DB_TBL . '_lang', 'LEFT JOIN', 'lang.productlang_product_id = ' . SellerProduct::DB_TBL_LANG_PREFIX . 'selprod_id AND productlang_lang_id = ' . $this->siteLangId, 'lang');

        if (!empty($keyword)) {
            $condition = $srch->addCondition('product_name', 'like', '%' . $post['keyword'] . '%');
            $condition->attachCondition('product_identifier', 'like', '%' . $post['keyword'] . '%', 'OR');
            $condition->attachCondition('selprod_title', 'like', '%' . $post['keyword'] . '%', 'OR');
        }
        $srch->addCondition('selprod_user_id', '=', 'mysql_func_' . $this->userParentId, 'AND', true);
        $srch->joinTable(User::DB_TBL_CRED, 'LEFT OUTER JOIN', 'tuc.credential_user_id = selprod_user_id', 'tuc');
        $srch->addGroupBy('related_sellerproduct_id');

        $this->setRecordCount(clone $srch, $pagesize, $page, $post, true);
        $srch->doNotCalculateRecords();

        $srch->addMultipleFields(['related_sellerproduct_id', 'credential_username', 'selprod_id', 'selprod_product_id', 'product_updated_on', 'selprod_title', 'product_name', 'product_identifier']);
        $srch->setPageNumber($page);
        $srch->setPageSize($pagesize);

        if ($selProdId) {
            $srch->addFld('if(related_sellerproduct_id = ' . $selProdId . ', 1 , 0) as priority');
            $srch->addOrder('priority', 'DESC');
        }
        $records = FatApp::getDb()->fetchAll($srch->getResultSet(), 'related_sellerproduct_id');
        foreach ($records as $relatedProd) {
            $productId = $relatedProd['related_sellerproduct_id'];
            $relProdSrch = SellerProduct::searchRelatedProducts($this->siteLangId);
            $relProdSrch->addFld('if(related_sellerproduct_id = ' . $selProdId . ', 1 , 0) as priority');
            $relProdSrch->addOrder('priority', 'DESC');
            $relProdSrch->addCondition('related_sellerproduct_id', '=', $productId);
            $relProdSrch->doNotCalculateRecords();
            $relProdSrch->doNotLimitRecords();
            $rs = $relProdSrch->getResultSet();
            $records[$productId]['products'] = FatApp::getDb()->fetchAll($rs);
            $records[$productId]['credential_username'] = $relatedProd['credential_username'];
        }

        $this->set("arrListing", $records);
        $this->set('canEdit', $this->userPrivilege->canEditRelatedProducts(UserAuthentication::getLoggedUserId(), true));
        $this->set('postedData', $post);
        $this->set('pageSize', FatApp::getConfig('CONF_PAGE_SIZE', FatUtility::VAR_INT, 10));
        $this->_template->render(false, false);
    }

    private function getRelatedProductsSearchForm()
    {
        $frm = new Form('frmSearch', array('id' => 'frmSearch'));
        $frm->setRequiredStarWith('caption');
        $frm->addTextBox(Labels::getLabel('FRM_KEYWORD', $this->siteLangId), 'keyword');
        $frm->addSubmitButton('', 'btn_submit', Labels::getLabel('BTN_SEARCH', $this->siteLangId));
        $frm->addButton("", "btn_clear", Labels::getLabel('BTN_CLEAR', $this->siteLangId), array('onclick' => 'clearSearch();'));
        return $frm;
    }

    public function setupRelatedProduct()
    {
        $this->userPrivilege->canEditRelatedProducts(UserAuthentication::getLoggedUserId());
        $post = FatApp::getPostedData();
        $selprod_id = FatUtility::int($post['selprod_id']);
        if (!UserPrivilege::canEditSellerProduct($this->userParentId, $selprod_id)) {
            FatUtility::dieJsonError(Labels::getLabel("ERR_PLEASE_SELECT_A_VALID_PRODUCT", $this->siteLangId));
        }
        if ($selprod_id <= 0) {
            FatUtility::dieJsonError(Labels::getLabel('ERR_PLEASE_SELECT_A_VALID_PRODUCT', $this->siteLangId));
        }
        $relatedProducts = (isset($post['products_related'])) ? array_filter($post['products_related']) : array();
        if (count($relatedProducts) < 1) {
            FatUtility::dieJsonError(Labels::getLabel("ERR_YOU_NEED_TO_ADD_ATLEAST_ONE_RELATED_PRODUCT", $this->siteLangId));
        }
        $sellerProdObj = new sellerProduct();
        if (!$sellerProdObj->addUpdateSellerRelatedProdcts($selprod_id, $relatedProducts)) {
            FatUtility::dieJsonError($sellerProdObj->getError());
        }

        $this->set('msg', Labels::getLabel('MSG_RELATED_PRODUCT_SETUP_SUCCESSFUL', $this->siteLangId));
        $this->_template->render(false, false, 'json-success.php');
    }

    public function deleteSelprodRelatedProduct($selprod_id, $relprod_id)
    {
        $this->userPrivilege->canEditRelatedProducts(UserAuthentication::getLoggedUserId());
        $selprod_id = FatUtility::int($selprod_id);
        $relprod_id = FatUtility::int($relprod_id);
        if (!$selprod_id || !$relprod_id) {
            Message::addErrorMessage(Labels::getLabel('ERR_INVALID_REQUEST', $this->siteLangId));
            FatApp::redirectUser($_SESSION['referer_page_url']);
        }

        $db = FatApp::getDb();
        if (!$db->deleteRecords(SellerProduct::DB_TBL_RELATED_PRODUCTS, array('smt' => 'related_sellerproduct_id = ? AND related_recommend_sellerproduct_id = ?', 'vals' => array($selprod_id, $relprod_id)))) {
            Message::addErrorMessage(Labels::getLabel("LBL_" . $db->getError(), $this->siteLangId));
            FatApp::redirectUser($_SESSION['referer_page_url']);
        }

        $this->set('selprod_id', $selprod_id);
        $this->set('msg', Labels::getLabel('MSG_RECORD_DELETED', $this->siteLangId));
        $this->_template->render(false, false, 'json-success.php');
    }

    public function getUpsellProductsList($selprod_id)
    {
        $selprod_id = FatUtility::int($selprod_id);
        $srch = SellerProduct::searchUpsellProducts($this->siteLangId);
        $srch->addCondition('selprod_user_id', '=', 'mysql_func_' . $this->userParentId, 'AND', true);
        $srch->addCondition(SellerProduct::DB_TBL_UPSELL_PRODUCTS_PREFIX . 'sellerproduct_id', '=', $selprod_id);
        $srch->addGroupBy('selprod_id');
        $srch->addGroupBy('upsell_sellerproduct_id');
        $srch->addOrder('selprod_id', 'DESC');
        $srch->doNotCalculateRecords();
        $rs = $srch->getResultSet();
        $upsellProds = FatApp::getDb()->fetchAll($rs);
        $json = array(
            'selprodId' => $selprod_id,
            'upsellProducts' => $upsellProds
        );
        FatUtility::dieJsonSuccess($json);
    }

    private function getUpsellProductsForm()
    {
        $frm = new Form('frmUpsellSellerProduct');
        $prodName = $frm->addSelectBox(Labels::getLabel('FRM_PRODUCT', $this->siteLangId), 'selprod_id', [], '', array('class' => 'selProd--js', 'placeholder' => Labels::getLabel('FRM_SELECT_PRODUCT', $this->siteLangId)));
        $prodName->requirements()->setRequired();
        $frm->addSelectBox(Labels::getLabel('FRM_BUY_TOGETHER_PRODUCTS', $this->siteLangId), 'products_upsell[]', [], '');

        return $frm;
    }

    public function upsellProducts($selProd_id = 0)
    {
        $this->userPrivilege->canViewBuyTogetherProducts(UserAuthentication::getLoggedUserId());
        if (!UserPrivilege::isUserHasValidSubsription($this->userParentId)) {
            Message::addInfo(Labels::getLabel("MSG_Please_buy_subscription", $this->siteLangId));
            FatApp::redirectUser(UrlHelper::generateUrl('Seller', 'Packages'));
        }
        $selProd_id = FatUtility::int($selProd_id);
        if (0 < $selProd_id || 0 > $selProd_id) {
            $selProd_id = SellerProduct::getAttributesByID($selProd_id, 'selprod_id', false);
            if (empty($selProd_id)) {
                Message::addErrorMessage(Labels::getLabel('ERR_INVALID_REQUEST', $this->siteLangId));
                FatApp::redirectUser(UrlHelper::generateUrl('Seller', 'volumeDiscount'));
            }
        }

        $srchFrm = $this->getVolumeDiscountSearchForm();
        $selProdIdsArr = FatApp::getPostedData('selprod_ids', FatUtility::VAR_INT, 0);

        $dataToEdit = array();
        if (!empty($selProdIdsArr) || 0 < $selProd_id) {
            $selProdIdsArr = (0 < $selProd_id) ? array($selProd_id) : $selProdIdsArr;
            $productsTitle = SellerProduct::getProductDisplayTitle($selProdIdsArr, $this->siteLangId);
            foreach ($selProdIdsArr as $selProdId) {
                $dataToEdit[] = array(
                    'product_name' => html_entity_decode($productsTitle[$selProdId], ENT_QUOTES, 'UTF-8'),
                    'voldiscount_selprod_id' => $selProdId
                );
            }
        } else {
            $post = $srchFrm->getFormDataFromArray(FatApp::getPostedData());

            if (false === $post) {
                FatUtility::dieJsonError(current($srchFrm->getValidationErrors()));
            } else {
                unset($post['btn_submit'], $post['btn_clear']);
                $srchFrm->fill($post);
            }
        }
        if (0 < $selProd_id) {
            $srchFrm->addHiddenField('', 'selprod_id', $selProd_id);
            $srchFrm->fill(array('keyword' => $productsTitle[$selProdId]));
        }
        $this->set("canEdit", $this->userPrivilege->canEditBuyTogetherProducts(UserAuthentication::getLoggedUserId(), true));
        $this->set("dataToEdit", $dataToEdit);
        $this->set("frmSearch", $srchFrm);

        $this->set("selProd_id", $selProd_id);
        $this->_template->addJs(array('js/select2.js'));
        $this->_template->addCss(array('css/select2.min.css'));
        $this->_template->render();
    }

    public function upsellProductsForm()
    {
        $this->set("frm", $this->getUpsellProductsForm());
        $this->set('includeTabs', false);
        $this->set('html', $this->_template->render(false, false, NULL, true));
        $this->_template->render(false, false, 'json-success.php', true, false);
    }

    public function searchUpsellProducts()
    {
        $this->userPrivilege->canViewBuyTogetherProducts(UserAuthentication::getLoggedUserId());
        $userId = $this->userParentId;
        $page = FatApp::getPostedData('page', FatUtility::VAR_INT, 1);
        $selProdId = FatApp::getPostedData('selprod_id', FatUtility::VAR_INT, 0);
        $keyword = FatApp::getPostedData('keyword', FatUtility::VAR_STRING, '');
        $pagesize = FatApp::getConfig('CONF_PAGE_SIZE', FatUtility::VAR_INT, 10);
        $post = FatApp::getPostedData();


        $srch = new SearchBase(SellerProduct::DB_TBL_UPSELL_PRODUCTS);
        $srch->joinTable(SellerProduct::DB_TBL, 'INNER JOIN', SellerProduct::DB_TBL_PREFIX . 'id = ' . SellerProduct::DB_TBL_UPSELL_PRODUCTS_PREFIX . 'sellerproduct_id', 'sp');
        $srch->joinTable(SellerProduct::DB_TBL . '_lang', 'LEFT JOIN', 'slang.' . SellerProduct::DB_TBL_LANG_PREFIX . 'selprod_id = ' . SellerProduct::DB_TBL_UPSELL_PRODUCTS_PREFIX . 'sellerproduct_id AND ' . SellerProduct::DB_TBL_LANG_PREFIX . 'lang_id = ' . $this->siteLangId, 'slang');
        $srch->joinTable(Product::DB_TBL, 'INNER JOIN', 'p.product_id = sp.selprod_product_id', 'p');
        $srch->joinTable(Product::DB_TBL_LANG, 'LEFT JOIN', 'p.product_id = p_l.productlang_product_id AND p_l.productlang_lang_id = ' . $this->siteLangId, 'p_l');

        $srch->addCondition('selprod_user_id', '=', 'mysql_func_' . $this->userParentId, 'AND', true);
        $srch->joinTable(User::DB_TBL_CRED, 'LEFT OUTER JOIN', 'tuc.credential_user_id = selprod_user_id', 'tuc');

        if ($keyword != '') {
            $cnd = $srch->addCondition('product_name', 'LIKE', '%' . $keyword . '%');
            $cnd->attachCondition('selprod_title', 'LIKE', '%' . $keyword . '%', 'OR');
            $cnd->attachCondition('product_identifier', 'LIKE', '%' . $keyword . '%', 'OR');
        }

        $srch->addGroupBy('upsell_sellerproduct_id');

        $this->setRecordCount(clone $srch, $pagesize, $page, $post, true);
        $srch->doNotCalculateRecords();
        $srch->addOrder('selprod_id', 'DESC');
        $srch->setPageNumber($page);
        $srch->setPageSize($pagesize);

        $srch->addMultipleFields([
            'upsell_sellerproduct_id',
            'selprod_id',
            'selprod_product_id',
            'product_updated_on',
            'selprod_title',
            'product_name',
            'product_identifier',
        ]);

        $upsellProds = FatApp::getDb()->fetchAll($srch->getResultSet(), 'upsell_sellerproduct_id');
        foreach ($upsellProds as $productId => $upsellProd) {
            $srch = SellerProduct::searchUpsellProducts($this->siteLangId, [], false);
            $srch->addFld('if(upsell_sellerproduct_id = ' . $selProdId . ', 1 , 0) as priority');
            $srch->addOrder('priority', 'DESC');
            $srch->addCondition('upsell_sellerproduct_id', '=', $productId);
            $srch->addGroupBy('selprod_id');
            $srch->addGroupBy('upsell_sellerproduct_id');
            $srch->doNotCalculateRecords();
            $srch->doNotLimitRecords();
            $rs = $srch->getResultSet();
            $upsellProds[$productId]['products'] = FatApp::getDb()->fetchAll($rs);
        }

        $this->set("arrListing", $upsellProds);
        $this->set('canEdit', $this->userPrivilege->canEditBuyTogetherProducts(UserAuthentication::getLoggedUserId(), true));
        $this->set('postedData', $post);
        $this->_template->render(false, false);
    }


    public function setupUpsellProduct()
    {
        $this->userPrivilege->canEditBuyTogetherProducts(UserAuthentication::getLoggedUserId());
        $post = FatApp::getPostedData();
        $selprod_id = FatUtility::int($post['selprod_id']);
        if (!UserPrivilege::canEditSellerProduct($this->userParentId, $selprod_id)) {
            FatUtility::dieJsonError(Labels::getLabel("ERR_PLEASE_SELECT_A_VALID_PRODUCT", $this->siteLangId));
        }
        if ($selprod_id <= 0) {
            FatUtility::dieJsonError(Labels::getLabel('ERR_PLEASE_SELECT_A_VALID_PRODUCT', $this->siteLangId));
        }
        $upsellProducts = (isset($post['products_upsell'])) ? array_filter($post['products_upsell']) : array();
        if (count($upsellProducts) < 1) {
            FatUtility::dieJsonError(Labels::getLabel("ERR_YOU_NEED_TO_ADD_ATLEAST_ONE_BUY_TOGETHER_PRODUCT", $this->siteLangId));
        }
        $sellerProdObj = new sellerProduct();
        /* saving of product Upsell Product[ */
        if (!$sellerProdObj->addUpdateSellerUpsellProducts($selprod_id, $upsellProducts)) {
            FatUtility::dieJsonError($sellerProdObj->getError());
        }
        /* ] */

        $this->set('msg', Labels::getLabel('MSG_BUY_TOGETHER_PRODUCT_SETUP_SUCCESSFUL', $this->siteLangId));
        $this->_template->render(false, false, 'json-success.php');
    }

    public function deleteSelprodUpsellProduct($selprod_id, $relprod_id)
    {
        $this->userPrivilege->canEditBuyTogetherProducts(UserAuthentication::getLoggedUserId());
        $selprod_id = FatUtility::int($selprod_id);
        $relprod_id = FatUtility::int($relprod_id);
        if (!$selprod_id || !$relprod_id) {
            Message::addErrorMessage(Labels::getLabel('ERR_INVALID_REQUEST', $this->siteLangId));
            FatApp::redirectUser($_SESSION['referer_page_url']);
        }

        $db = FatApp::getDb();
        if (!$db->deleteRecords(SellerProduct::DB_TBL_UPSELL_PRODUCTS, array('smt' => 'upsell_sellerproduct_id = ? AND upsell_recommend_sellerproduct_id = ?', 'vals' => array($selprod_id, $relprod_id)))) {
            Message::addErrorMessage(Labels::getLabel("LBL_" . $db->getError(), $this->siteLangId));
            FatApp::redirectUser($_SESSION['referer_page_url']);
        }

        $this->set('selprod_id', $selprod_id);
        $this->set('msg', Labels::getLabel('MSG_RECORD_DELETED', $this->siteLangId));
        $this->_template->render(false, false, 'json-success.php');
    }

    public function isProductRewriteUrlUnique()
    {
        $selprod_id = FatApp::getPostedData('recordId', FatUtility::VAR_INT, 0);
        $urlKeyword = FatApp::getPostedData('url_keyword');
        $sellerProdObj = new SellerProduct($selprod_id);
        $seoUrl = $sellerProdObj->sanitizeSeoUrl($urlKeyword);
        if (1 > $selprod_id) {
            $isUnique = UrlRewrite::isCustomUrlUnique($seoUrl);
            if ($isUnique) {
                FatUtility::dieJsonSuccess(UrlHelper::generateFullUrl('', '', array(), CONF_WEBROOT_FRONT_URL) . $seoUrl);
            }
            FatUtility::dieJsonError(Labels::getLabel('ERR_NOT_AVAILABLE._PLEASE_TRY_USING_ANOTHER_KEYWORD', $this->siteLangId));
        }

        $originalUrl = $sellerProdObj->getRewriteProductOriginalUrl();
        $customUrlData = UrlRewrite::getDataByCustomUrl($seoUrl, $originalUrl);
        if (empty($customUrlData)) {
            FatUtility::dieJsonSuccess(UrlHelper::generateFullUrl('', '', array(), CONF_WEBROOT_FRONT_URL) . $seoUrl);
        }
        FatUtility::dieJsonError(Labels::getLabel('ERR_NOT_AVAILABLE._PLEASE_TRY_USING_ANOTHER_KEYWORD', $this->siteLangId));
    }

    public function badgeAutocomplete(int $badgeType, int $recordType = 0)
    {
        $pagesize = 20;
        $keyword = FatApp::getPostedData('keyword', FatUtility::VAR_STRING, '');

        switch ($recordType) {
            case BadgeLinkCondition::RECORD_TYPE_SELLER_PRODUCT:
                $srch = SellerProduct::getSearchObject(0);
                $srch->addCondition('selprod_user_id', '=', 'mysql_func_' . $this->userParentId, 'AND', true);
                $srch->joinTable(BadgeLinkCondition::DB_TBL_BADGE_LINKS, 'INNER JOIN', 'badgelink_record_id = selprod_id', 'bl');
                break;
            case BadgeLinkCondition::RECORD_TYPE_PRODUCT:
                $srch = new ProductSearch(0, null, null, true, true, true);
                $srch->addCondition('product_seller_id', '=', 'mysql_func_' . $this->userParentId, 'AND', true);
                $srch->joinTable(BadgeLinkCondition::DB_TBL_BADGE_LINKS, 'INNER JOIN', 'badgelink_record_id = product_id', 'bl');
                break;
            case BadgeLinkCondition::RECORD_TYPE_SHOP:
                $srch = Shop::getSearchObject(true);
                $srch->addCondition('shop_user_id', '=', 'mysql_func_' . $this->userParentId . 'AND', true);
                $srch->joinTable(BadgeLinkCondition::DB_TBL_BADGE_LINKS, 'INNER JOIN', 'badgelink_record_id = shop_id', 'bl');
                break;

            default:
                return '';
                break;
        }

        $srch->joinTable(BadgeLinkCondition::DB_TBL, 'INNER JOIN', 'blinkcond_id = badgelink_blinkcond_id', 'blc');
        $srch->joinTable(Badge::DB_TBL, 'INNER JOIN', 'badge_id = blinkcond_badge_id', 'bdg');
        $srch->joinTable(Badge::DB_TBL_LANG, 'LEFT JOIN', 'badgelang_badge_id = badge_id AND badgelang_lang_id = ' . $this->siteLangId, 'bdg_l');
        $srch->addCondition('blinkcond_record_type', '=', 'mysql_func_' . $recordType, 'AND', true);
        $srch->addCondition('badge_type', '=', 'mysql_func_' . $badgeType, 'AND', true);

        $srch->addDirectCondition(
            '(CASE 
                WHEN blinkcond_from_date != 0 AND blinkcond_to_date != 0
                THEN "' . date('Y-m-d H:i:s') . '" BETWEEN blinkcond_from_date AND blinkcond_to_date 
                WHEN blinkcond_from_date != 0 AND blinkcond_to_date = 0
                THEN "' . date('Y-m-d H:i:s') . '" >= blinkcond_from_date
                WHEN blinkcond_from_date = 0 AND blinkcond_to_date != 0
                THEN "' . date('Y-m-d H:i:s') . '" <= blinkcond_to_date 
                ELSE TRUE 
            END)'
        );

        if (!empty($keyword)) {
            $srch->addCondition(Badge::DB_TBL_PREFIX . 'name', 'LIKE', '%' . $keyword . '%');
        }

        $srch->setPageSize($pagesize);
        $srch->doNotCalculateRecords();
        $srch->addMultipleFields([
            Badge::DB_TBL_PREFIX . 'id as id',
            'COALESCE(badge_name, badge_identifier) as name',
        ]);

        $srch->addGroupBy('badge_id');
        $badges = FatApp::getDb()->fetchAll($srch->getResultSet());
        die(json_encode(['badges' => $badges]));
    }

    public function productMissingInfo()
    {
        $this->userPrivilege->canViewProducts(UserAuthentication::getLoggedUserId());

        $selProdId = FatApp::getPostedData('recordId', FatUtility::VAR_INT, 0);
        if (1 > $selProdId) {
            LibHelper::exitWithError($this->str_invalid_request_id, true);
        }

        $sellerProductRow = SellerProduct::getAttributesById($selProdId, ['selprod_id'], false, false);
        if (!$sellerProductRow) {
            LibHelper::exitWithError($this->str_invalid_request_id, true);
        }

        $this->set('infoArr', SellerProduct::getProdMissingInfo($selProdId, $this->siteLangId));
        $this->set('html', $this->_template->render(false, false, NULL, true));
        $this->_template->render(false, false, 'json-success.php', true, false);
    }
}
