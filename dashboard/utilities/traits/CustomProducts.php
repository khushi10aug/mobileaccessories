<?php

trait CustomProducts
{
    public function searchCustomProduct()
    {
        if (!User::canAddCustomProduct()) {
            FatUtility::dieWithError(Labels::getLabel('MSG_Invalid_Access', $this->siteLangId));
        }
        $post = FatApp::getPostedData();
        $page = (empty($post['page']) || $post['page'] <= 0) ? 1 : intval($post['page']);
        $pagesize = FatApp::getConfig('CONF_PAGE_SIZE', FatUtility::VAR_INT, 10);
        $srch = Product::getSearchObject($this->siteLangId);
        $srch->addCondition('product_seller_id', '=', $this->userParentId);

        $keyword = FatApp::getPostedData('keyword', null, '');
        if (!empty($keyword)) {
            $cnd = $srch->addCondition('product_name', 'like', '%' . $keyword . '%');
            $cnd->attachCondition('product_identifier', 'like', '%' . $keyword . '%', 'OR');
            $cnd->attachCondition('product_model', 'like', '%' . $keyword . '%');
        }

        $srch->addMultipleFields(
            array(
                'product_id',
                'product_identifier',
                'product_active',
                'product_approved',
                'product_added_on',
                'product_name'
            )
        );
        $srch->addOrder('product_added_on', 'DESC');
        $srch->setPageNumber($page);
        $srch->setPageSize($pagesize);

        $db = FatApp::getDb();
        $rs = $srch->getResultSet();
        $arrListing = $db->fetchAll($rs);

        $this->set("arrListing", $arrListing);
        $this->set('pageCount', $srch->pages());
        $this->set('page', $page);
        $this->set('pageSize', $pagesize);
        $this->set('postedData', $post);
        $this->set('siteLangId', $this->siteLangId);
        $this->set('CONF_CUSTOM_PRODUCT_REQUIRE_ADMIN_APPROVAL', FatApp::getConfig("CONF_CUSTOM_PRODUCT_REQUIRE_ADMIN_APPROVAL", FatUtility::VAR_INT, 1));

        $this->_template->render(false, false);
    }



    public function productOptions($productId = 0)
    {
        $productId = FatUtility::int($productId);
        if (!$productId) {
            FatUtility::dieWithError(Labels::getLabel('MSG_Invalid_Request', $this->siteLangId));
        }

        /* Validate product belongs to current logged seller[ */
        if (!UserPrivilege::canSellerEditCustomProduct($this->userParentId, $productId)) {
            Message::addErrorMessage(Labels::getLabel('ERR_INVALID_ACCESS', $this->siteLangId));
            FatUtility::dieWithError(Message::getHtml());
        }
        /* ] */

        $productOptions = Product::getProductOptions($productId, $this->siteLangId);
        $this->set('productOptions', $productOptions);
        $this->set('productId', $productId);
        $this->_template->render(false, false);
    }

    private function getCustomProductOptionForm()
    {
        $frm = new Form('frmProductOptions', array('id' => 'frmProductOptions'));
        $frm->addHtml('', 'product_name', '');
        $fld1 = $frm->addTextBox(Labels::getLabel('FRM_ADD_OPTION_GROUPS', $this->siteLangId), 'option_name');
        $fld1->htmlAfterField = '<div class=""><small><a href="javascript:void(0);" onclick="optionForm(0);">' . Labels::getLabel('FRM_ADD_NEW_OPTION', $this->siteLangId) . '</a></small></div><div class="row"><div class="col-md-12"><ul class="list--vertical" id="product_options_list"></ul></div>';
        $frm->addHiddenField('', 'product_id', '', array('id' => 'product_id'));

        return $frm;
    }

    public function updateProductOption()
    {
        $post = FatApp::getPostedData();
        $product_id = FatUtility::int($post['product_id']);
        $option_id = FatUtility::int($post['option_id']);

        if (!$product_id || !$option_id) {
            FatUtility::dieJsonError(Labels::getLabel('ERR_INVALID_REQUEST', $this->siteLangId));
        }
        if (!UserPrivilege::isUserHasValidSubsription($this->userParentId)) {
            FatUtility::dieJsonError(Labels::getLabel("ERR_PLEASE_BUY_SUBSCRIPTION", $this->siteLangId));
        }

        if (!UserPrivilege::canSellerEditCustomProduct($this->userParentId, $product_id)) {
            FatUtility::dieJsonError(Labels::getLabel('ERR_INVALID_ACCESS', $this->siteLangId));
        }
        $productOptions = Product::getProductOptions($product_id, $this->siteLangId, false, 1);
        $optionSeparateImage = Option::getAttributesById($option_id, 'option_is_separate_images');
        if (count($productOptions) > 0 && $optionSeparateImage == 1) {
            FatUtility::dieJsonError(Labels::getLabel('ERR_YOU_HAVE_ALREADY_ADDED_OPTION_HAVING_SEPARATE_IMAGE', $this->siteLangId));
        }
        $prodObj = new Product($product_id);
        if (!$prodObj->addUpdateProductOption($option_id)) {
            FatUtility::dieJsonError($prodObj->getError());
        }
        Product::updateMinPrices($product_id);
        $this->set('msg', Labels::getLabel('MSG_OPTION_UPDATED_SUCCESSFULLY', $this->siteLangId));
        $this->_template->render(false, false, 'json-success.php');
    }

    public function checkOptionLinkedToInventory()
    {
        $post = FatApp::getPostedData();
        $productId = FatUtility::int($post['product_id']);
        $optionId = FatUtility::int($post['option_id']);

        if (!$productId || !$optionId) {
            Message::addErrorMessage(Labels::getLabel('LBL_INVALID_REQUEST', $this->siteLangId));
            FatUtility::dieWithError(Message::getHtml());
        }

        /* Validate product belongs to current logged seller[ */
        if ($productId) {
            $productRow = Product::getAttributesById($productId, array('product_seller_id'));
            if ($productRow['product_seller_id'] != $this->userParentId) {
                FatUtility::dieWithError(Labels::getLabel('MSG_Invalid_Access', $this->siteLangId));
            }
        }
        /* ] */

        /* Validate option is binded with seller product [ */
        $optionSrch = SellerProduct::getSearchObject();
        $optionSrch->joinTable(SellerProduct::DB_TBL_SELLER_PROD_OPTIONS, 'LEFT OUTER JOIN', 'sp.selprod_id = spo.selprodoption_selprod_id', 'spo');
        $optionSrch->joinTable(Product::DB_PRODUCT_TO_OPTION, 'LEFT OUTER JOIN', 'sp.selprod_product_id = po.prodoption_product_id', 'po');
        $optionSrch->addMultipleFields(array('selprodoption_option_id'));
        $optionSrch->addCondition('selprod_product_id', '=', $productId);
        $optionSrch->addCondition('prodoption_option_id', '=', $optionId);
        $optionSrch->addCondition('selprodoption_option_id', '=', $optionId);
        $optionSrch->addCondition('selprod_deleted', '=', applicationConstants::NO);
        $optionSrch->doNotCalculateRecords();
        $optionSrch->setPageSize(1);
        $rs = $optionSrch->getResultSet();
        $db = FatApp::getDb();
        $row = $db->fetch($rs);
        if (!empty($row)) {
            FatUtility::dieJsonError(Labels::getLabel('ERR_THIS_OPTION_IS_LINKED_WITH_THE_INVENTORY,_SO_CAN_NOT_BE_DELETED', $this->siteLangId));
            return;
        }
        FatUtility::dieJsonSuccess(Labels::getLabel("MSG_OPTION_CAN_BE_DELETED", $this->siteLangId));
        /* ] */
    }

    public function removeProductOption()
    {
        $post = FatApp::getPostedData();
        $productId = FatUtility::int($post['product_id']);
        $optionId = FatUtility::int($post['option_id']);

        if (!$productId || !$optionId) {
            FatUtility::dieJsonError(Labels::getLabel('ERR_INVALID_REQUEST', $this->siteLangId));
        }

        /* Validate product belongs to current logged seller[ */
        if ($productId) {
            $productRow = Product::getAttributesById($productId, array('product_seller_id'));
            if ($productRow['product_seller_id'] != $this->userParentId) {
                FatUtility::dieJsonError(Labels::getLabel('ERR_INVALID_ACCESS', $this->siteLangId));
            }
        }
        /* ] */

        /* Get Linked Products [ */
        $srch = SellerProduct::getSearchObject();
        $srch->joinTable(SellerProduct::DB_TBL_SELLER_PROD_OPTIONS, 'LEFT OUTER JOIN', 'selprod_id = selprodoption_selprod_id', 'tspo');
        $srch->addCondition('selprod_product_id', '=', $productId);
        $srch->addCondition('tspo.selprodoption_option_id', '=', $optionId);
        $srch->addCondition('selprod_deleted', '=', applicationConstants::NO);
        $srch->addFld(array('selprod_id'));
        $srch->doNotCalculateRecords();
        $srch->setPageSize(1);
        $rs = $srch->getResultSet();
        $row = FatApp::getDb()->fetch($rs);
        if (!empty($row)) {
            FatUtility::dieJsonError(Labels::getLabel('ERR_OPTION_IS_LINKED_WITH_SELLER_INVENTORY', $this->siteLangId));
        }
        /* ] */

        $prodObj = new Product($productId);
        if (!$prodObj->removeProductOption($optionId)) {
            Message::addErrorMessage(Labels::getLabel($prodObj->getError(), FatApp::getConfig('CONF_PAGE_SIZE', FatUtility::VAR_INT, 1)));
            FatUtility::dieWithError(Message::getHtml());
        }
        Product::updateMinPrices($productId);
        FatUtility::dieJsonSuccess(Labels::getLabel('LBL_Option_removed_successfully.', $this->siteLangId));
    }

    public function customProductImages($product_id)
    {
        if (!User::canAddCustomProduct()) {
            FatUtility::dieWithError(Labels::getLabel('MSG_INVALID_ACCESS', $this->siteLangId));
        }
        if (!UserPrivilege::isUserHasValidSubsription($this->userParentId)) {
            FatUtility::dieWithError(Labels::getLabel("MSG_Please_buy_subscription", $this->siteLangId));
        }
        $product_id = FatUtility::int($product_id);

        if (!$product_id) {
            FatUtility::dieWithError(Labels::getLabel('MSG_Invalid_Request', $this->siteLangId));
        }

        if (!$productRow = Product::getAttributesById($product_id, array('product_seller_id'))) {
            FatUtility::dieWithError(Labels::getLabel('MSG_Invalid_Request', $this->siteLangId));
        }

        if ($productRow['product_seller_id'] != $this->userParentId) {
            FatUtility::dieWithError(Labels::getLabel('MSG_INVALID_ACCESS', $this->siteLangId));
        }

        $imagesFrm = $this->getImagesFrm($product_id, $this->siteLangId);

        $imgTypesArr = $this->getSeparateImageOptions($product_id, $this->siteLangId);

        $productType = Product::getAttributesById($product_id, 'product_type');

        $hideButtons = FatApp::getPostedData('hideButtons', FatUtility::VAR_INT, 0);

        $this->set('product_id', $product_id);
        $this->set('imagesFrm', $imagesFrm);
        $this->set('productType', $productType);
        $this->set('hideButtons', $hideButtons);
        $this->_template->render(false, false);
    }

    public function images($product_id, $option_id = 0, $lang_id = 0)
    {
        if (!User::canAddCustomProduct()) {
            FatUtility::dieWithError(Labels::getLabel('MSG_INVALID_ACCESS', $this->siteLangId));
        }
        if (!UserPrivilege::isUserHasValidSubsription($this->userParentId)) {
            FatUtility::dieWithError(Labels::getLabel("MSG_Please_buy_subscription", $this->siteLangId));
        }
        $product_id = FatUtility::int($product_id);
        $option_id = Product::encodeImageOptionSubId($option_id);

        if (!$product_id) {
            FatUtility::dieWithError(Labels::getLabel('MSG_Invalid_Request', $this->siteLangId));
        }

        if (!$productRow = Product::getAttributesById($product_id, array('product_seller_id'))) {
            FatUtility::dieWithError(Labels::getLabel('MSG_Invalid_Request', $this->siteLangId));
        }

        if ($productRow['product_seller_id'] != $this->userParentId) {
            FatUtility::dieWithError(Labels::getLabel('MSG_INVALID_ACCESS', $this->siteLangId));
        }
        $languages = Language::getAllNames();
        if (count($languages) > 1) {
            $lang_id = $lang_id;
        } else {
            $lang_id = array_key_first($languages);
        }

        $product_images = AttachedFile::getMultipleAttachments(AttachedFile::FILETYPE_PRODUCT_IMAGE, $product_id, $option_id, $lang_id, false, 0, 0, true);
        $imgTypesArr = $this->getSeparateImageOptions($product_id, $this->siteLangId);

        $this->set('images', $product_images);
        $this->set('product_id', $product_id);
        $this->set('imgTypesArr', $imgTypesArr);
        $this->set('languages', Language::getAllNames());
        $this->_template->render(false, false);
    }

    public function setupCustomProductImages()
    {
        $this->userPrivilege->canEditProducts(UserAuthentication::getLoggedUserId());
        if (!User::canAddCustomProduct()) {
            FatUtility::dieJsonError(Labels::getLabel('ERR_INVALID_ACCESS', $this->siteLangId));
        }
        if (!UserPrivilege::isUserHasValidSubsription($this->userParentId)) {
            FatUtility::dieJsonError(Labels::getLabel("ERR_PLEASE_BUY_SUBSCRIPTION", $this->siteLangId));
        }

        $post = FatApp::getPostedData();
        if (empty($post)) {
            FatUtility::dieJsonError(Labels::getLabel('ERR_INVALID_REQUEST_OR_FILE_NOT_SUPPORTED', $this->siteLangId));
        }
        $product_id = FatUtility::int($post['product_id']);
        $optionKey = isset($post['option_id']) ? trim((string) $post['option_id']) : '0';
        $option_id = Product::encodeImageOptionSubId($optionKey);
        $lang_id = FatUtility::int($post['lang_id']);


        /* Validate product belongs to current logged seller[ */
        if ($product_id) {
            $productRow = Product::getAttributesById($product_id, array('product_seller_id'));
            $optionValues = Product::getSeparateImageOptions($product_id, $this->siteLangId);
            $optionKeyExists = array_key_exists($optionKey, $optionValues)
                || (ctype_digit($optionKey) && array_key_exists((int) $optionKey, $optionValues));
            if ($productRow['product_seller_id'] != $this->userParentId || !$optionKeyExists) {
                FatUtility::dieWithError(Labels::getLabel('MSG_INVALID_ACCESS', $this->siteLangId));
            }
        }

        $this->validateImageSubscriptionLimit($product_id, $optionKey, $lang_id);

        if (!is_uploaded_file($_FILES['cropped_image']['tmp_name'])) {
            FatUtility::dieJsonError(Labels::getLabel("ERR_PLEASE_SELECT_A_FILE", $this->siteLangId));
        }
        $fileHandlerObj = new AttachedFile();
        if (!$res = $fileHandlerObj->saveImage($_FILES['cropped_image']['tmp_name'], AttachedFile::FILETYPE_PRODUCT_IMAGE, $product_id, $option_id, $_FILES['cropped_image']['name'], -1, $unique_record = false, $lang_id)) {
            FatUtility::dieJsonError($fileHandlerObj->getError());
        }
        FatApp::getDb()->updateFromArray('tbl_products', array('product_updated_on' => date('Y-m-d H:i:s')), array('smt' => 'product_id = ?', 'vals' => array($product_id)));

        FatUtility::dieJsonSuccess(Labels::getLabel("MSG_Image_Uploaded_Successfully", $this->siteLangId));
    }

    public function deleteCustomProductImage($product_id, $image_id)
    {
        if (!User::canAddCustomProduct()) {
            FatUtility::dieWithError(Labels::getLabel('MSG_INVALID_ACCESS', $this->siteLangId));
        }
        $product_id = FatUtility::int($product_id);
        $image_id = FatUtility::int($image_id);
        if (!$image_id || !$product_id) {
            FatUtility::dieJsonError(Labels::getLabel("ERR_INVALID_REQUEST!", $this->siteLangId));
        }

        /* Validate product belongs to current logged seller[ */
        $productRow = Product::getAttributesById($product_id, array('product_seller_id'));
        if ($productRow['product_seller_id'] != $this->userParentId) {
            FatUtility::dieJsonError(Labels::getLabel('ERR_INVALID_ACCESS', $this->siteLangId));
        }
        /* ] */

        $productObj = new Product();
        if (!$productObj->deleteProductImage($product_id, $image_id)) {
            FatUtility::dieJsonError($productObj->getError());
        }
        FatApp::getDb()->updateFromArray('tbl_products', array('product_updated_on' => date('Y-m-d H:i:s')), array('smt' => 'product_id = ?', 'vals' => array($product_id)));

        FatUtility::dieJsonSuccess(Labels::getLabel('LBL_Image_removed_successfully.', $this->siteLangId));
    }

    public function setCustomProductImagesOrder()
    {
        if (!User::canAddCustomProduct()) {
            FatUtility::dieWithError(Labels::getLabel('MSG_Invalid_Access', $this->siteLangId));
        }

        $productObj = new Product();
        $post = FatApp::getPostedData();
        $product_id = FatUtility::int($post['product_id']);
        /* Validate product belongs to current logged seller[ */
        $productRow = Product::getAttributesById($product_id, array('product_seller_id'));
        if ($productRow['product_seller_id'] != $this->userParentId) {
            FatUtility::dieWithError(Labels::getLabel('MSG_Invalid_Access', $this->siteLangId));
        }
        /* ] */
        $imageIds = explode('-', $post['ids']);
        $count = 1;
        foreach ($imageIds as $row) {
            $order[$count] = $row;
            $count++;
        }

        if (!$productObj->updateProdImagesOrder($product_id, $order)) {
            Message::addErrorMessage($productObj->getError());
            FatUtility::dieWithError(Message::getHtml());
        }
        FatUtility::dieJsonSuccess(Labels::getLabel("LBL_Ordered_Successfully!", $this->siteLangId));
    }

    /* Custom product Specifications */

    public function customProductSpecifications($product_id)
    {
        $productSpecifications = Product::getProductSpecifications($product_id, $this->siteLangId);

        $prodCatId = 0;
        $product = new Product();
        $records = $product->getProductCategories($product_id);
        if (!empty($records)) {
            $prodcatArr = array_column($records, 'prodcat_id');
            $prodCatId = reset($prodcatArr);
        }

        $alertToShow = $this->CheckProductLinkWithCatBrand($product_id);
        $this->set('alertToShow', $alertToShow);
        $this->set('prodSpec', $productSpecifications);
        $this->set('product_id', $product_id);
        $this->set('prodcat_id', $prodCatId);
        $languages = Language::getAllNames();
        $this->set('languages', $languages);
        $this->set('activeTab', 'SPECIFICATIONS');
        $this->set('siteLangId', $this->siteLangId);
        $this->_template->render(false, false);
    }

    public function productSpecifications($productId)
    {
        if (!UserPrivilege::canSellerEditCustomProduct($this->userParentId, $productId)) {
            Message::addErrorMessage(Labels::getLabel('ERR_INVALID_ACCESS', $this->siteLangId));
            FatUtility::dieWithError(Message::getHtml());
        }

        $productSpecifications = Product::getProductSpecifications($productId, $this->siteLangId);

        $languages = Language::getAllNames();
        $this->set('prodSpec', $productSpecifications);
        $this->set('productId', $productId);
        $this->set('languages', $languages);
        $this->set('siteLangId', $this->siteLangId);

        $this->_template->render(false, false);
    }

    public function deleteProdSpec($productId = 0)
    {
        $post = FatApp::getPostedData();

        $prodspec_id = FatUtility::int($post['prodSpecId']);
        if (!UserPrivilege::canSellerEditCustomProduct($this->userParentId, $productId)) {
            Message::addErrorMessage(Labels::getLabel('ERR_INVALID_ACCESS', $this->siteLangId));
            FatUtility::dieWithError(Message::getHtml());
        }
        if ($prodspec_id > 0) {
            if (!UserPrivilege::canEditSellerProductSpecification($prodspec_id, $productId)) {
                Message::addErrorMessage(Labels::getLabel('ERR_INVALID_ACCESS', $this->siteLangId));
                FatUtility::dieWithError(Message::getHtml());
            }
        }
        $prodSpecObj = new ProdSpecification($prodspec_id);
        if (!$prodSpecObj->deleteRecord(true)) {
            Message::addErrorMessage(Labels::getLabel($prodSpecObj->getError(), $this->siteLangId));
            FatUtility::dieWithError(Message::getHtml());
        }

        $this->set('msg', Labels::getLabel('MSG_SPECIFICATION_DELETED_SUCCESSFULLY', $this->siteLangId));
        $this->_template->render(false, false, 'json-success.php');
    }

    public function getShippingTab()
    {
        $shipping_rates = array();
        $post = FatApp::getPostedData();
        $userId = $this->userParentId;
        $product_id = $post['product_id'];
        //$shipping_rates = Products::getProductShippingRates();
        $this->set('siteLangId', $this->siteLangId);
        $shipping_rates = array();
        $shipping_rates = Product::getProductShippingRates($product_id, $this->siteLangId, 0, $userId);

        $this->set('siteLangId', $this->siteLangId);
        $this->set('product_id', $product_id);
        $this->set('shipping_rates', $shipping_rates);
        $this->_template->render(false, false);
    }

    public function countries_autocomplete()
    {
        $pagesize = 20;
        $page = FatApp::getPostedData('page', FatUtility::VAR_INT, 1);
        if ($page < 2) {
            $page = 1;
        }
        $post = FatApp::getPostedData();
        $srch = Countries::getSearchObject(true, $this->siteLangId);
        $srch->addOrder('country_name');

        $srch->addMultipleFields(array('country_id, country_name, country_code'));

        if (!empty($post['keyword'])) {
            $srch->addCondition('country_name', 'LIKE', '%' . $post['keyword'] . '%');
        }

        $srch->setPageSize($pagesize);
        $srch->setPageNumber($page);

        $countries = FatApp::getDb()->fetchAll($srch->getResultSet(), 'country_id');
        if (isset($post['includeEverywhere']) && $post['includeEverywhere']) {
            $everyWhereArr = array('country_id' => '-1', 'country_name' => Labels::getLabel('LBL_Everywhere_Else', $this->siteLangId));
            $countries[] = $everyWhereArr;
        }

        $json = array(
            'pageCount' => $srch->pages()
        );
        foreach ($countries as $key => $country) {
            $json['results'][] = array(
                'id' => $country['country_id'],
                'text' => strip_tags(html_entity_decode(isset($country['country_name']) ? $country['country_name'] : $country['country_code'], ENT_QUOTES, 'UTF-8')),
            );
        }
        die(json_encode($json));
    }

    public function shippingMethodsAutocomplete()
    {
        $pagesize = 10;
        $post = FatApp::getPostedData();
        $userId = $this->userParentId;
        $srch = ShippingApi::getSearchObject(true, $this->siteLangId);
        $srch->addOrder('shippingapi_name');

        $srch->addMultipleFields(array('shippingapi_id, shippingapi_name'));

        if (!empty($post['keyword'])) {
            $cnd = $srch->addCondition('shippingapi_name', 'LIKE', '%' . $post['keyword'] . '%');
        }

        $srch->setPageSize($pagesize);
        $srch->doNotCalculateRecords();
        $rs = $srch->getResultSet();
        $db = FatApp::getDb();

        $shippingMethods = $db->fetchAll($rs, 'shippingapi_id');


        $json = array();
        foreach ($shippingMethods as $key => $sMethod) {
            $json[] = array(
                'id' => $key,
                'name' => strip_tags(html_entity_decode($sMethod['shippingapi_name'], ENT_QUOTES, 'UTF-8')),

            );
        }
        die(json_encode($json));
    }

    public function shippingCompanyAutocomplete()
    {
        $pagesize = 10;
        $post = FatApp::getPostedData();
        $userId = $this->userParentId;
        $srch = ShippingCompanies::getSearchObject(true, $this->siteLangId);
        $srch->addOrder('scompany_name');

        $srch->addMultipleFields(array('scompany_id, scompany_name'));

        if (!empty($post['keyword'])) {
            $cnd = $srch->addCondition('scompany_name', 'LIKE', '%' . $post['keyword'] . '%');
        }
        $srch->doNotCalculateRecords();
        $srch->setPageSize($pagesize);
        $rs = $srch->getResultSet();
        $db = FatApp::getDb();

        $shippingCompanies = $db->fetchAll($rs, 'scompany_id');


        $json = array();
        foreach ($shippingCompanies as $key => $sCompany) {
            $json[] = array(
                'id' => $key,
                'name' => strip_tags(html_entity_decode($sCompany['scompany_name'], ENT_QUOTES, 'UTF-8')),

            );
        }
        die(json_encode($json));
    }

    public function shippingMethodDurationAutocomplete()
    {
        $pagesize = 10;
        $post = FatApp::getPostedData();
        $userId = $this->userParentId;
        $srch = ShippingDurations::getSearchObject($this->siteLangId, true);
        $srch->addOrder('sduration_name');

        $srch->addMultipleFields(array('sduration_id, sduration_name', 'sduration_from', 'sduration_to', 'sduration_days_or_weeks'));

        if (!empty($post['keyword'])) {
            $cnd = $srch->addCondition('sduration_id', 'LIKE', '%' . $post['keyword'] . '%');
        }

        $srch->setPageSize($pagesize);
        $srch->doNotCalculateRecords();
        $rs = $srch->getResultSet();
        $db = FatApp::getDb();

        $shipDurations = $db->fetchAll($rs, 'sduration_id');

        $json = array();
        foreach ($shipDurations as $key => $shipDuration) {
            $json[] = array(
                'id' => $key,
                'name' => strip_tags(html_entity_decode($shipDuration['sduration_name'], ENT_QUOTES, 'UTF-8')),
                'duraion' => ShippingDurations::getShippingDurationTitle($shipDuration, $this->siteLangId),

            );
        }
        die(json_encode($json));
    }
    /*  ---  Seller Product Links  --- - */
    public function customProductLinks($productId = 0)
    {
        if (!UserPrivilege::canSellerEditCustomProduct($this->userParentId, $productId)) {
            Message::addErrorMessage(Labels::getLabel('ERR_INVALID_ACCESS', $this->siteLangId));
            FatUtility::dieWithError(Message::getHtml());
        }
       
        $lang_id = $this->siteLangId;
        $frm = $this->getLinksForm($productId);

        $srch = Product::getSearchObject($lang_id);
        $srch->joinTable(Brand::DB_TBL, 'LEFT OUTER JOIN', 'tp.product_brand_id = brand.brand_id', 'brand');

        $srch->joinTable(Brand::DB_TBL_LANG, 'LEFT OUTER JOIN', 'brandlang_brand_id = brand.brand_id AND brandlang_lang_id = ' . $lang_id);

        $srch->addMultipleFields(array('product_id', 'brand_status', 'brand_deleted', 'product_brand_id', 'IFNULL(product_name,product_identifier) as product_name', 'IFNULL(brand_name,brand_identifier) as brand_name'));
        $srch->addCondition('product_id', '=', $productId);
        $srch->addCondition('brand.brand_active', '=', 'mysql_func_' . applicationConstants::YES, 'AND', true);
        $srch->addCondition('brand.brand_deleted', '=', 'mysql_func_' . applicationConstants::NO, 'AND', true);
        $srch->doNotCalculateRecords();
        $srch->setPageSize(1);
        $rs = $srch->getResultSet();
        $product_row = FatApp::getDb()->fetch($rs);
        $prodObj = new Product();
        $product_tags = $prodObj->getProductTags($productId, $lang_id);

        $alertToShow = $this->CheckProductLinkWithCatBrand($productId);
        $this->set('alertToShow', $alertToShow);

        $prodCatId = 0;
        $product = new Product();
        $records = $product->getProductCategories($productId);
        if (!empty($records)) {
            $prodcatArr = array_column($records, 'prodcat_id');
            $prodCatId = reset($prodcatArr);
        }

        $frm->fill($product_row);

        $this->set('product_name', $product_row['product_name']);
        $this->set('product_tags', $product_tags);
        $this->set('frmLinks', $frm);
        $this->set('product_id', $productId);
        $this->set('prodcat_id', $prodCatId);
        $this->set('activeTab', 'LINKS');
        $this->_template->render(false, false);
    }

    public function setupProductLinks()
    {
        $this->userPrivilege->canEditProducts(UserAuthentication::getLoggedUserId());
        $post = FatApp::getPostedData();
        if (!UserPrivilege::canSellerEditCustomProduct($this->userParentId, $post['product_id'])) {
            Message::addErrorMessage(Labels::getLabel('ERR_INVALID_ACCESS', $this->siteLangId));
            FatUtility::dieWithError(Message::getHtml());
        }
        $product_tags = (isset($post['product_tag'])) ? $post['product_tag'] : array();
        $frm = $this->getLinksForm($post['product_id']);
        $post = $frm->getFormDataFromArray(FatApp::getPostedData());
        if (false === $post) {
            Message::addErrorMessage(current($frm->getValidationErrors()));
            FatUtility::dieWithError(Message::getHtml());
        }
        $product_id = $post['product_id'];
        unset($post['product_id']);

        if ($product_id <= 0) {
            Message::addErrorMessage(Labels::getLabel('ERR_INVALID_REQUEST', $this->siteLangId));
            FatUtility::dieWithError(Message::getHtml());
        }

        /*$product_categories = $post['product_category'];
        $product_categories = explode(',',$product_categories);*/

        $prodObj = new Product($product_id);

        $data_to_be_save['product_brand_id'] = FatUtility::int($post['product_brand_id']);
        $prodObj->assignValues($data_to_be_save);

        if (!$prodObj->save()) {
            Message::addErrorMessage($prodObj->getError());
            FatUtility::dieWithError(Message::getHtml());
        }

        /* saving of product categories[
        if( !$prodObj->addUpdateProductCategories($product_id, $product_categories ) ){
        Message::addErrorMessage( $prodObj->getError() );
        FatUtility::dieWithError(Message::getHtml());
        }
        /* ] */
        /* saving of product Tag[ */


        if (!$prodObj->addUpdateProductTags($product_tags)) {
            Message::addErrorMessage($prodObj->getError());
            FatUtility::dieWithError(Message::getHtml());
        }

        Tag::updateProductTagString($product_id);
        /* ] */

        $this->set('msg', Labels::getLabel('MSG_Record_Updated_Successfully!', $this->siteLangId));
        $this->_template->render(false, false, 'json-success.php');
    }

    public function tagsAutoComplete()
    {
        $post = FatApp::getPostedData();

        $srch = Tag::getSearchObject($this->siteLangId);
        $srch->addOrder('tag_name');
        $srch->addMultipleFields(array('tag_id', 'tag_name'));

        if (!empty($post['keyword'])) {
            $cnd = $srch->addCondition('tag_name', 'LIKE', '%' . $post['keyword'] . '%');
            $cnd->attachCondition('tag_name', 'LIKE', '%' . $post['keyword'] . '%', 'OR');
        }
        $srch->doNotCalculateRecords();
        $rs = $srch->getResultSet();
        $db = FatApp::getDb();
        $options = $db->fetchAll($rs, 'tag_id');
        $json = array();
        foreach ($options as $key => $option) {
            $json[] = array(
                'id' => $key,
                'name' => strip_tags(html_entity_decode($option['tag_name'], ENT_QUOTES, 'UTF-8')),
            );
        }
        die(json_encode($json));
    }

    public function tagSetup()
    {
        $frm = $this->getTagsForm();
        $post = $frm->getFormDataFromArray(FatApp::getPostedData());

        if (false === $post) {
            FatUtility::dieJsonError(current($frm->getValidationErrors()));
        }

        $tag_id = $post['tag_id'];
        unset($post['tag_id']);

        $record = new Tag($tag_id);
        $record->assignValues($post);

        if (!$record->save()) {
            FatUtility::dieJsonError(Labels::getLabel('ERR_THIS_IDENTIFIER_IS_NOT_AVAILABLE._PLEASE_TRY_WITH_ANOTHER_ONE.', $this->siteLangId));
        }

        $newTabLangId = 0;
        if ($tag_id > 0) {
            $languages = Language::getAllNames();
            foreach ($languages as $langId => $langName) {
                if (!$row = Tag::getAttributesByLangId($langId, $tag_id)) {
                    $newTabLangId = $langId;
                    break;
                }
            }
        } else {
            $tag_id = $record->getMainTableRecordId();
            $newTabLangId = FatApp::getConfig('CONF_ADMIN_DEFAULT_LANG', FatUtility::VAR_INT, 1);
        }

        /* update product tags association and tag string in products lang table[ */
        Tag::updateTagStrings($tag_id);
        /* ] */

        $this->set('msg', Labels::getLabel('MSG_TAG_UPDATED_SUCCESSFUL', $this->siteLangId));
        $this->set('tagId', $tag_id);
        $this->set('langId', $newTabLangId);
        $this->_template->render(false, false, 'json-success.php');
    }

    public function tagLangSetup()
    {
        $post = FatApp::getPostedData();

        $tag_id = FatUtility::int($post['tag_id']);
        $lang_id = FatUtility::int($post['lang_id']);

        if ($tag_id < 1) {
            Message::addErrorMessage($this->str_invalid_request_id);
            FatUtility::dieWithError(Message::getHtml());
        }

        $frm = $this->getTagLangForm($tag_id, $lang_id);
        $post = $frm->getFormDataFromArray(FatApp::getPostedData());
        unset($post['tag_id']);
        unset($post['lang_id']);
        $data = array(
            'taglang_lang_id' => $lang_id,
            'taglang_tag_id' => $tag_id,
            'tag_name' => $post['tag_name'],
        );

        $tagObj = new Tag($tag_id);
        if (!$tagObj->updateLangData($lang_id, $data)) {
            FatUtility::dieJsonError($tagObj->getError());
        }

        $autoUpdateOtherLangsData = FatApp::getPostedData('auto_update_other_langs_data', FatUtility::VAR_INT, 0);
        if (0 < $autoUpdateOtherLangsData) {
            $updateLangDataobj = new TranslateLangData(Tag::DB_TBL_LANG);
            if (false === $updateLangDataobj->updateTranslatedData($tag_id)) {
                Message::addErrorMessage($updateLangDataobj->getError());
                FatUtility::dieWithError(Message::getHtml());
            }
        }

        $newTabLangId = 0;
        $languages = Language::getAllNames();
        foreach ($languages as $langId => $langName) {
            if (!$row = Tag::getAttributesByLangId($langId, $tag_id)) {
                $newTabLangId = $langId;
                break;
            }
        }

        /* update product tags association and tag string in products lang table[ */
        Tag::updateTagStrings($tag_id);
        /* ] */

        $this->set('msg', Labels::getLabel('MSG_TAG_UPDATED_SUCCESSFUL', $this->siteLangId));
        $this->set('tagId', $tag_id);
        $this->set('langId', $newTabLangId);
        $this->_template->render(false, false, 'json-success.php');
    }

    public function addtagsForm($tag_id = 0)
    {
        $tag_id = FatUtility::int($tag_id);
        $frm = $this->getTagsForm($tag_id);

        if (0 < $tag_id) {
            $data = Tag::getAttributesById($tag_id, array('tag_id', 'tag_name'));
            if ($data === false) {
                FatUtility::dieWithError($this->str_invalid_request);
            }
            $frm->fill($data);
        }

        $this->set('languages', Language::getAllNames());
        $this->set('tag_id', $tag_id);
        $this->set('frmTag', $frm);
        $this->set('langId', $this->siteLangId);
        $this->_template->render(false, false);
    }

    public function tagsLangForm($tag_id = 0, $lang_id = 0, $autoFillLangData = 0)
    {
        $tag_id = FatUtility::int($tag_id);
        $lang_id = FatUtility::int($lang_id);

        if ($tag_id == 0 || $lang_id == 0) {
            FatUtility::dieWithError($this->str_invalid_request);
        }

        $tagLangFrm = $this->getTagLangForm($tag_id, $lang_id);

        if (0 < $autoFillLangData) {
            $updateLangDataobj = new TranslateLangData(Tag::DB_TBL_LANG);
            $translatedData = $updateLangDataobj->getTranslatedData($tag_id, $lang_id);
            if (false === $translatedData) {
                Message::addErrorMessage($updateLangDataobj->getError());
                FatUtility::dieWithError(Message::getHtml());
            }
            $langData = current($translatedData);
        } else {
            $langData = Tag::getAttributesByLangId($lang_id, $tag_id);
        }

        if ($langData) {
            $tagLangFrm->fill($langData);
        }

        $this->set('languages', Language::getAllNames());
        $this->set('tag_id', $tag_id);
        $this->set('tag_lang_id', $lang_id);
        $this->set('siteLangId', $this->siteLangId);
        $this->set('tagLangFrm', $tagLangFrm);
        $this->set('formLayout', Language::getLayoutDirection($lang_id));
        $this->_template->render(false, false);
    }

    /*...................................Product Shipping Rates..................................*/
    public function removeProductShippingRates($product_id, $userId = 0)
    {
        $db = FatApp::getDb();
        $product_id = FatUtility::int($product_id);
        $userId = FatUtility::int($userId);


        if (!$db->deleteRecords(ShippingApi::DB_TBL_PRODUCT_SHIPPING_RATES, array('smt' => ShippingApi::DB_TBL_PRODUCT_SHIPPING_RATES_PREFIX . 'prod_id = ? and   ' . ShippingApi::DB_TBL_PRODUCT_SHIPPING_RATES_PREFIX . 'user_id = ?', 'vals' => array($product_id, $userId)))) {
            $this->error = $db->getError();
            return false;
        }

        return true;
    }

    private function addUpdateProductShippingRates($product_id, $data)
    {
        $this->removeProductShippingRates($product_id, $this->userParentId);

        if (!empty($data) && count($data) > 0) {
            foreach ($data as $key => $val) :
                if ((isset($val["country_id"]) && $val["country_id"] >= 0 || $val["country_id"] == -1) && $val["company_id"] > 0 && $val["processing_time_id"] > 0) {
                    $prodShipData = array(
                        'pship_prod_id' => $product_id,
                        'pship_user_id' => $this->userParentId,
                        'pship_country' => (isset($val["country_id"]) && FatUtility::int($val["country_id"])) ? FatUtility::int($val["country_id"]) : 0,
                        'pship_company' => (isset($val["company_id"]) && FatUtility::int($val["company_id"])) ? FatUtility::int($val["company_id"]) : 0,
                        'pship_duration' => (isset($val["processing_time_id"]) && FatUtility::int($val["processing_time_id"])) ? FatUtility::int($val["processing_time_id"]) : 0,
                        'pship_charges' => (1 > FatUtility::float($val["cost"]) ? 0 : FatUtility::float($val["cost"])),
                        'pship_additional_charges' => FatUtility::float($val["additional_cost"]),
                    );
                    if (isset($val["pship_id"])) {
                        $prodShipData['pship_id'] = FatUtility::int($val["pship_id"]);
                    }
                    if (!FatApp::getDb()->insertFromArray(ShippingApi::DB_TBL_PRODUCT_SHIPPING_RATES, $prodShipData, false, array(), $prodShipData)) {
                        $this->error = FatApp::getDb()->getError();
                        return false;
                    }
                }
            endforeach;
        }
        return true;
    }

    public function addUpdateProductSellerShipping($product_id, $data_to_be_save)
    {
        $productSellerShiping = array();
        $productSellerShiping['ps_product_id'] = $product_id;
        $productSellerShiping['ps_user_id'] = $this->userParentId;
        $productSellerShiping['ps_from_country_id'] = $data_to_be_save['ps_from_country_id'];
        $productSellerShiping['ps_free'] = isset($data_to_be_save['ps_free']) ? $data_to_be_save['ps_free'] : 0;
        if (!FatApp::getDb()->insertFromArray(Product::DB_TBL_PRODUCT_SHIPPING, $productSellerShiping, false, array(), $productSellerShiping)) {
            $this->error = FatApp::getDb()->getError();
            return false;
        }
        return true;
    }


    private function getCustomProductSearchForm()
    {
        $frm = new Form('frmSearchCustomProduct');
        $frm->addTextBox('', 'keyword');
        $frm->addSubmitButton('', 'btn_submit', Labels::getLabel('BTN_SEARCH', $this->siteLangId));
        $frm->addButton("", "btn_clear", Labels::getLabel("BTN_CLEAR", $this->siteLangId), array('onclick' => 'clearSearch();'));
        $frm->addHiddenField('', 'page');
        return $frm;
    }

    private function getTagsForm($tag_id = 0)
    {
        $tag_id = FatUtility::int($tag_id);

        $frm = new Form('frmTag', array('id' => 'frmTag'));
        $frm->addHiddenField('', 'tag_id', $tag_id);
        $frm->addRequiredField(Labels::getLabel("FRM_TAG_NAME", $this->siteLangId), 'tag_name');
        $frm->addSubmitButton('', 'btn_submit', Labels::getLabel("BTN_SAVE_CHANGES", $this->siteLangId));
        return $frm;
    }

    private function getTagLangForm($tag_id = 0, $lang_id = 0)
    {
        $frm = new Form('frmTagLang', array('id' => 'frmTagLang'));
        $frm->addHiddenField('', 'tag_id', $tag_id);
        $frm->addSelectBox(Labels::getLabel('FRM_LANGUAGE', $this->siteLangId), 'lang_id', Language::getAllNames(), $lang_id, array(), '');
        $frm->addRequiredField(Labels::getLabel('FRM_TAG_NAME', $this->siteLangId), 'tag_name');

        $siteLangId = FatApp::getConfig('conf_default_site_lang', FatUtility::VAR_INT, 1);
        $translatorSubscriptionKey = FatApp::getConfig('CONF_TRANSLATOR_SUBSCRIPTION_KEY', FatUtility::VAR_STRING, '');

        if (!empty($translatorSubscriptionKey) && $lang_id == $siteLangId) {
            $frm->addCheckBox(Labels::getLabel('FRM_UPDATE_OTHER_LANGUAGES_DATA', $this->siteLangId), 'auto_update_other_langs_data', 1, array(), false, 0);
        }

        $frm->addSubmitButton('', 'btn_submit', Labels::getLabel("BTN_UPDATE", $this->siteLangId));
        return $frm;
    }

    private function getLinksForm($product_id = 0)
    {
        if (!UserPrivilege::canSellerEditCustomProduct($this->userParentId, $product_id)) {
            Message::addErrorMessage(Labels::getLabel('ERR_INVALID_ACCESS', $this->siteLangId));
            FatUtility::dieWithError(Message::getHtml());
        }

        $frm = new Form('frmLinks', array('id' => 'frmLinks'));
        $frm->addTextBox(Labels::getLabel('FRM_PRODUCT_NAME', $this->siteLangId), 'product_name');

        $fld1 = $frm->addTextBox(Labels::getLabel('FRM_CATEGORY', $this->siteLangId), 'choose_links');
        $fld2 = $frm->addHtml('', 'addNewOptionLink', '</a><div id="product_links_list" class="col-xs-10" ></div>');
        $fld1->attachField($fld2);
        $frm->addHiddenField('', 'product_brand_id');

        $fld1 = $frm->addTextBox(Labels::getLabel('FRM_ADD_TAG', $this->siteLangId), 'tag_name');
        $fld1->htmlAfterField = '<div class="col-md-12"><small><a href="javascript:void(0);" onclick="addTagForm(0);">' . Labels::getLabel('LBL_Tag_Not_Found?_Click_here_to_', $this->siteLangId) . ' ' . Labels::getLabel('LBL_Add_New_Tag', $this->siteLangId) . '</a></small></div><div class="row"><div class="col-md-12"><ul class="list--vertical" id="product-tag"></ul></div>';

        //$frm->addHtml('','product-tag','');

        $frm->addHiddenField('', 'product_id', $product_id);
        $frm->addSubmitButton('', 'btn_submit', Labels::getLabel("BTN_SAVE_CHANGES", $this->siteLangId));
        return $frm;
    }

    private function getProductSpecForm()
    {
        $frm = new Form('frmProductSpec');
        $languages = Language::getAllNames();
        $defaultLang = true;
        foreach ($languages as $langId => $langName) {
            $attr['class'] = 'langField_' . $langId;
            if (true === $defaultLang) {
                $attr['class'] .= ' defaultLang';
                $defaultLang = false;
            }
            $frm->addRequiredField(
                Labels::getLabel('FRM_SPECIFICATION_NAME', $this->siteLangId),
                'prod_spec_name[' . $langId . ']',
                '',
                $attr
            );
            $frm->addRequiredField(
                Labels::getLabel('FRM_SPECIFICATION_VALUE', $this->siteLangId),
                'prod_spec_value[' . $langId . ']',
                '',
                $attr
            );
        }
        $frm->addHiddenField('', 'product_id');
        $frm->addHiddenField('', 'prodspec_id');
        $frm->addSubmitButton('', 'btn_submit', Labels::getLabel('BTN_SAVE_CHANGES', $this->siteLangId));

        return $frm;
    }

    private function getImagesFrm($product_id = 0, $lang_id = 0)
    {
        $imgTypesArr = $this->getSeparateImageOptions($product_id, $lang_id);
        $frm = new Form('imageFrm', array('id' => 'imageFrm'));
        $frm->addSelectBox(Labels::getLabel('FRM_IMAGE_FILE_TYPE', $this->siteLangId), 'option_id', $imgTypesArr, 0, array('class' => 'option'), '');
        $languagesAssocArr = Language::getAllNames();

        if (count($languagesAssocArr) > 1) {
            $frm->addSelectBox(Labels::getLabel('FRM_LANGUAGE', $this->siteLangId), 'lang_id', array(0 => Labels::getLabel('FRM_ALL_LANGUAGES', $this->siteLangId)) + $languagesAssocArr, '', array('class' => 'language'), '');
        } else {
            $lang_id = array_key_first($languagesAssocArr);
            $frm->addHiddenField('', 'lang_id', $lang_id);
        }

        $fldImg = $frm->addFileUpload(Labels::getLabel('FRM_PHOTO(s)', $this->siteLangId), 'prod_image', array('id' => 'prod_image'));
        $fldImg->htmlBeforeField = '<div class="filefield">';
        $fldImg->htmlAfterField = '</div><span class="form-text text-muted">' . Labels::getLabel('FRM_PLEASE_KEEP_IMAGE_DIMENSIONS_GREATER_THAN_500_X_500', $this->siteLangId) . '</span>';
        $frm->addHiddenField('', 'min_width', 500);
        $frm->addHiddenField('', 'min_height', 500);
        $frm->addHiddenField('', 'product_id', $product_id);
        return $frm;
    }

    private function getSeparateImageOptions($product_id, $lang_id)
    {
        return Product::getSeparateImageOptions($product_id, $lang_id);
    }

    private function getCustomProductImagesForm()
    {
        $frm = new Form('frmCustomProductImage');
        $fldImg = $frm->addFileUpload(Labels::getLabel('FRM_PHOTO(S):', $this->siteLangId), 'prod_image', array('id' => 'prod_image'));
        $fldImg->htmlBeforeField = '<div class="filefield"><span class="filename"></span>';
        $fldImg->htmlAfterField = '</div><br/><span class="form-text text-muted">' . Labels::getLabel('FRM_PLEASE_KEEP_IMAGE_DIMENSIONS_GREATER_THAN_500_X_500', $this->siteLangId) . '</span>';
        $frm->addHiddenField('', 'product_id');
        return $frm;
    }

    private function getCustomProductLangForm($langId)
    {
        $langId = FatUtility::int($langId);
        $frm = new Form('frmCustomProductLang');
        $frm->addHiddenField('', 'product_id')->requirements()->setRequired();;
        $frm->addSelectBox(Labels::getLabel('FRM_LANGUAGE', $this->siteLangId), 'lang_id', Language::getAllNames(), $langId, array(), '');
        $frm->addRequiredField(Labels::getLabel('FRM_PRODUCT_NAME', $langId), 'product_name');
        /* $frm->addTextArea( Labels::getLabel('FRM_SHORT_DESCRIPTION', $langId),'product_short_description');         */
        $frm->addTextBox(Labels::getLabel('FRM_YOUTUBE_VIDEO', $langId), 'product_youtube_video');
        $fld = $frm->addHtmlEditor(Labels::getLabel('FRM_DESCRIPTION', $langId), 'product_description');
        $fld->htmlBeforeField = '<div class="editor-bar">';
        $fld->htmlAfterField = '</div>';

        $siteLangId = FatApp::getConfig('conf_default_site_lang', FatUtility::VAR_INT, 1);
        $translatorSubscriptionKey = FatApp::getConfig('CONF_TRANSLATOR_SUBSCRIPTION_KEY', FatUtility::VAR_STRING, '');

        if (!empty($translatorSubscriptionKey) && $langId == $siteLangId) {
            $frm->addCheckBox(Labels::getLabel('FRM_UPDATE_OTHER_LANGUAGES_DATA', $this->siteLangId), 'auto_update_other_langs_data', 1, array(), false, 0);
        }

        $frm->addSubmitButton('', 'btn_submit', Labels::getLabel('BTN_SAVE_CHANGES', $langId));
        return $frm;
    }

    public function CheckProductLinkWithCatBrand($productId)
    {
        $alertToShow = false;
        if ($productId) {
            $productRow = Product::getAttributesById($productId, array('product_brand_id'));
            $prodObj = new Product();
            $prodCategories = $prodObj->getProductCategories($productId);
            if (!$prodCategories || $productRow['product_brand_id'] == 0) {
                $alertToShow = true;
            }
            $this->set('alertToShow', $alertToShow);
        }
        return $alertToShow;
    }

    public function getTranslatedSpecData()
    {
        $siteDefaultLangId = FatApp::getConfig('conf_default_site_lang', FatUtility::VAR_INT, 1);
        $prodSpecName = FatApp::getPostedData('prod_spec_name');
        $prodSpecValue = FatApp::getPostedData('prod_spec_value');

        if (empty($prodSpecName) || empty($prodSpecValue)) {
            FatUtility::dieJsonError(Labels::getLabel('ERR_INVALID_REQUEST', $this->siteLangId));
        }

        $translatedText = $this->translateLangFields(ProdSpecification::DB_TBL_LANG, ['prod_spec_name' => $prodSpecName[$siteDefaultLangId], 'prod_spec_value' => $prodSpecValue[$siteDefaultLangId]]);
        $data = [];
        foreach ($translatedText as $langId => $value) {
            $data[$langId]['prod_spec_name[' . $langId . ']'] = $value['prod_spec_name'];
            $data[$langId]['prod_spec_value[' . $langId . ']'] = $value['prod_spec_value'];
        }
        CommonHelper::jsonEncodeUnicode($data, true);
    }



    public function prodSpecGroupAutoComplete()
    {
        $post = FatApp::getPostedData();
        $srch = ProdSpecification::getSearchObject($post['langId'], false);
        if (!empty($post['keyword'])) {
            $srch->addCondition('prodspec_group', 'LIKE', '%' . $post['keyword'] . '%');
        }
        $srch->setPageSize(FatApp::getConfig('CONF_ADMIN_PAGESIZE', FatUtility::VAR_INT, 10));
        $srch->addMultipleFields(array('DISTINCT(prodspec_group)'));
        $srch->doNotCalculateRecords();
        $rs = $srch->getResultSet();
        $prodSpecGroup = FatApp::getDb()->fetchAll($rs);
        $json = array();
        foreach ($prodSpecGroup as $key => $group) {
            $json[] = array(
                'name' => strip_tags(html_entity_decode($group['prodspec_group'], ENT_QUOTES, 'UTF-8'))
            );
        }
        die(json_encode($json));
    }
}
