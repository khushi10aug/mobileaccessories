<?php
class ProductsController extends SellerBaseController
{
    use CatalogProduct {
        validateGetForm as validateForm;
    }
    use ProductDigitalDownloads;
    use ProductSetup;

    public function __construct($action)
    {
        parent::__construct($action);
        $this->userPrivilege->canViewProducts();
        $this->canAddProduct();
    }

    public function index()
    {
        FatApp::redirectUser(UrlHelper::generateUrl('Seller', 'catalog'));
    }

    /**
     * checkEditPrivilege - This function is used to check, set previlege and can be also used in parent class to validate request.
     *
     * @param  bool $setVariable
     * @return void
     */
    protected function checkEditPrivilege(bool $setVariable = false): void
    {
        if (true === $setVariable) {
            $this->set("canEdit", $this->userPrivilege->canEditProducts(UserAuthentication::getLoggedUserId(), true));
        } else {
            $this->userPrivilege->canEditProducts();
        }
    }

    public function form($recordId = 0, $productType = 0)
    {
        $this->checkEditPrivilege();

        $userId = $this->userParentId;

        if (
            0 == $recordId && FatApp::getConfig('CONF_ENABLE_SELLER_SUBSCRIPTION_MODULE', FatUtility::VAR_INT, 0) &&
            Product::getActiveCount($userId) >= SellerPackages::getAllowedLimit($userId, $this->siteLangId, 'ossubs_products_allowed')
        ) {
            LibHelper::exitWithError(Labels::getLabel('ERR_YOU_HAVE_CROSSED_YOUR_PACKAGE_LIMIT', $this->siteLangId), false, true);
            FatApp::redirectUser(UrlHelper::generateUrl('Seller', 'Packages'));
        }

        $recordId = FatUtility::int($recordId);
        $productType = FatUtility::int($productType);

        $langId = FatApp::getPostedData('langId', FatUtility::VAR_INT, 0);
        if (1 > $langId) {
            $langId = CommonHelper::getDefaultFormLangId();
        }

        $frm = $this->getForm($langId, $productType, $recordId);

        $imgFrm = $this->getImageFrm();
        $productOptions = [];
        $selProdId = 0;
        if (0 < $recordId) {
            if (0 < FatApp::getPostedData('autoFillLangData', FatUtility::VAR_INT, 0)) {
                $updateLangDataobj = new TranslateLangData(Product::DB_TBL_LANG);
                $translatedData = $updateLangDataobj->getTranslatedData($recordId, $langId, CommonHelper::getDefaultFormLangId());
                if (false === $translatedData) {
                    LibHelper::exitWithError($updateLangDataobj->getError());
                }
                $productData = current($translatedData);
                $productData += Product::getAttributesById($recordId);
            } else {
                $productData = Product::getAttributesByLangId($langId, $recordId, null, applicationConstants::JOIN_RIGHT);
            }
            if (empty($productData)) {
                LibHelper::exitWithError($this->str_invalid_request, false, true);
                FatApp::redirectUser(UrlHelper::generateUrl('Products'));
            }

            $productData['record_id'] = $recordId;

            if ($productData['product_seller_id'] != $userId) {
                FatUtility::dieWithError($this->str_invalid_request);
            }

            if (1 > $productType) {
                $frm = $this->getForm($langId, $productData['product_type'], $recordId);
            }

            $fld = $frm->getField('product_seller_id');
            if ($productData['product_seller_id'] > 0) {
                $userShopName = User::getUserShopName($productData['product_seller_id'], $langId);
                $fld->options = [$productData['product_seller_id'] => $userShopName['user_name'] . ' (' . $userShopName['shop_name'] . ')'];
            } else {
                $fld->options = [0 => Labels::getLabel('FRM_ADMIN', $langId)];
            }

            $prodSpecificsDetails = Product::getProductSpecificsDetails($recordId);
            if (false != $prodSpecificsDetails) {
                $productData += $prodSpecificsDetails;
            }

            $productTags = Product::getProductTags($recordId, $langId);
            $tagData = [];
            foreach ($productTags as $key => $data) {
                $tagData[$key]['id'] = $data['tag_id'];
                $tagData[$key]['value'] = htmlspecialchars($data['tag_name'], ENT_QUOTES, 'UTF-8');
            }

            $productData['product_tags'] = json_encode($tagData);

            if (0 < $productData['product_brand_id']) {
                $brandData = Brand::getAttributesByLangId($langId, $productData['product_brand_id'], [Brand::tblFld('name'), Brand::tblFld('identifier')], applicationConstants::JOIN_RIGHT, applicationConstants::YES, applicationConstants::NO);
                if (false != $brandData) {
                    $fld = $frm->getField('product_brand_id');
                    $fld->options = [$productData['product_brand_id'] => $brandData[Brand::tblFld('name')] ?? $brandData[Brand::tblFld('identifier')]];
                }
            }

            $productCategories = (new Product())->getProductCategories($recordId);
            if (!empty($productCategories)) {
                $categoryIds = array_column($productCategories, 'prodcat_id');
                $productData['ptc_prodcat_id'] = $categoryIds;
                $catOptions = [];
                foreach ($categoryIds as $prodcatId) {
                    $catData = ProductCategory::getAttributesByLangId($langId, $prodcatId, [ProductCategory::tblFld('name'), ProductCategory::tblFld('identifier')], applicationConstants::JOIN_RIGHT, applicationConstants::YES, applicationConstants::NO);
                    if (false != $catData) {
                        $catOptions[$prodcatId] = $catData[ProductCategory::tblFld('name')] ?? $catData[ProductCategory::tblFld('identifier')];
                    }
                }
                if (!empty($catOptions)) {
                    $fld = $frm->getField('ptc_prodcat_id');
                    $fld->options = $catOptions;
                }
            }

            $taxData = Tax::getTaxCatByProductId($recordId, $productData['product_seller_id'], $langId);
            if (false != $taxData) {
                $productData['ptt_taxcat_id'] = $taxData[Tax::tblFld('id')];
                $fld = $frm->getField('ptt_taxcat_id');
                $fld->options = [$productData['ptt_taxcat_id'] => $taxData[Tax::tblFld('name')] ?? $taxData[Tax::tblFld('identifier')]];
            }

            $prodShippingDetails = Product::getProductShippingDetails($recordId, $langId, $productData['product_seller_id']);

            if (false != $prodShippingDetails) {
                $productData['ps_from_country_id'] = $prodShippingDetails['ps_from_country_id'];
                $countryData = Countries::getAttributesByLangId($langId, $prodShippingDetails['ps_from_country_id'], [Countries::tblFld('name'), Countries::tblFld('code')], applicationConstants::JOIN_RIGHT, applicationConstants::YES);
                if (false != $countryData) {
                    $fld = $frm->getField('ps_from_country_id');
                    if (null != $fld) {
                        $fld->options = [$prodShippingDetails['ps_from_country_id'] => $countryData[Countries::tblFld('name')] ?? $countryData[Countries::tblFld('code')]];
                    }
                }
            }

            /* [ GET ATTACHED PROFILE ID */
            $profSrch = ShippingProfileProduct::getSearchObject();
            $profSrch->addCondition('shippro_product_id', '=', $recordId);
            $profSrch->addCondition('shippro_user_id', '=', $productData['product_seller_id']);
            $profSrch->doNotCalculateRecords();
            $profSrch->setPageSize(1);
            $proRs = $profSrch->getResultSet();
            $profileData = FatApp::getDb()->fetch($proRs);
            if (!empty($profileData)) {
                $productData['shipping_profile'] = $profileData['profile_id'];
            }
            /* ] */
            $productOptions = Product::getProductOptions($recordId, $langId, true);

            $srch = new SearchBase(UpcCode::DB_TBL);
            $srch->addCondition('upc_product_id', '=', $recordId);
            $srch->addFld('upc_options');
            $row = FatApp::getDb()->fetch($srch->getResultSet());
            $productData['upc_type'] = applicationConstants::YES;
            if (false != $row) {
                if ($row['upc_options'] != 0) {
                    $productData['upc_type'] = applicationConstants::NO;
                }
            }

            $this->set("productData", [
                'product_type' => $productData['product_type'],
                'product_seller_id' => $productData['product_seller_id'],
                'product_attachements_with_inventory' => $productData['product_attachements_with_inventory'],
            ]);

            /* to select product type in get */
            if (0 < $productType) {
                $productData['product_type'] = $productType;
            }

            if (0 < FatApp::getConfig('CONF_WITHOUT_PROD_VARIANTS', FatUtility::VAR_INT, 0)) {
                $selProdId = SellerProduct::getSelprodIdByProductId($recordId);
                $sellerProductRow = SellerProduct::getAttributesById($selProdId, null, true, true);
                if (is_array($sellerProductRow) && !empty($sellerProductRow)) {
                    $sellerProductLangRow = SellerProduct::getAttributesByLangId($langId, $selProdId);
                    $sellerProductLangRow = is_array($sellerProductLangRow) ? $sellerProductLangRow : [];

                    $urlSrch = UrlRewrite::getSearchObject();
                    $urlSrch->doNotCalculateRecords();
                    $urlSrch->doNotLimitRecords();
                    $urlSrch->addFld('urlrewrite_custom');
                    $urlSrch->addCondition('urlrewrite_original', '=', 'products/view/' . $selProdId);
                    $urlSrch->doNotCalculateRecords();
                    $urlSrch->setPageSize(1);
                    $rs = $urlSrch->getResultSet();
                    $urlRow = FatApp::getDb()->fetch($rs);

                    $sellerProductRow['selprod_url_keyword'] = '';
                    if ($urlRow) {
                        $data['urlrewrite_custom'] = $urlRow['urlrewrite_custom'];
                        $customUrl = explode("/", $urlRow['urlrewrite_custom']);
                        $sellerProductRow['selprod_url_keyword'] = $customUrl[0];
                    }

                    $user_shop_name = User::getUserShopName($sellerProductRow['selprod_user_id'], $this->siteLangId);
                    $sellerProductRow['selprod_user_shop_name'] = $user_shop_name['user_name'] . ' - ' . $user_shop_name['shop_name'];

                    $returnAge = isset($sellerProductRow['selprod_return_age']) ? FatUtility::int($sellerProductRow['selprod_return_age']) : '';
                    $cancellationAge = isset($sellerProductRow['selprod_cancellation_age']) ? FatUtility::int($sellerProductRow['selprod_cancellation_age']) : '';

                    if ('' === $returnAge || '' === $cancellationAge) {
                        $sellerProductRow['use_shop_policy'] = 1;
                    }

                    $productData = array_merge($productData, $sellerProductRow, $sellerProductLangRow);
                }
            }

            $frm->fill($productData);
            $imgFrm->fill(['file_type' => AttachedFile::FILETYPE_PRODUCT_IMAGE, 'record_id' => $recordId]);
        } else {
            $tempProductId = time() . $userId;
            $frm->fill(['temp_product_id' => $tempProductId]);
            $imgFrm->fill(['file_type' => AttachedFile::FILETYPE_PRODUCT_IMAGE_TEMP, 'record_id' => $tempProductId]);
        }

        $this->set("selProdId", $selProdId);
        $this->set("frm", $frm);
        $this->set("imgFrm", $imgFrm);

        $codEnabled = true;
        $paymentMethod = new PaymentMethods();
        if (!$paymentMethod->cashOnDeliveryIsActive()) {
            $codEnabled = false;
        }
        $this->set("codEnabled", $codEnabled);
        $this->set("canEditTags", $this->userPrivilege->canEditProducts(0, true));
        $this->set("langId", $langId);
        $this->set("recordId", $recordId);
        $this->set('hasInventory', Product::hasInventory($recordId));

        $this->set('productOptions', $productOptions);
        $this->set('formLayout', Language::getLayoutDirection($langId));
        if (FatUtility::isAjaxCall()) {
            $this->set('nodes', $this->getBreadcrumbNodes($this->_actionName));
            $this->set('html', $this->_template->render(false, false, NULL, true));
            $this->_template->render(false, false, 'json-success.php', true, false);
            return;
        }
        $this->_template->addJs(array('seller-requests/page-js/index.js', 'products/page-js/form.js', 'js/cropper.js', 'js/cropper-main.js', 'js/select2.js', 'js/tagify.min.js', 'js/tagify.polyfills.min.js'));
        $this->_template->addCss(array('css/select2.min.css', 'css/tagify.min.css'));
        $this->set("includeEditor", true);
        $this->_template->render(true, true, 'products/formWithNavigation.php');
    }

    public function setup()
    {
        $this->checkEditPrivilege();

        $recordId = FatApp::getPostedData('record_id', FatUtility::VAR_INT, 0);
        $productType = FatApp::getPostedData('product_type', FatUtility::VAR_INT, 0);
        $langId = FatApp::getPostedData('lang_id', FatUtility::VAR_INT, 1);
        if (1 > $langId || !array_key_exists($productType, Product::getProductTypes($langId))) {
            LibHelper::exitWithError($this->str_invalid_request, true);
        }

        $frm = $this->getForm($langId, $productType, $recordId);
        $post = $frm->getFormDataFromArray(FatApp::getPostedData());

        if (false === $post) {
            LibHelper::exitWithError(current($frm->getValidationErrors()), true);
        }
        /* [select2 data */
        $post['product_brand_id'] = FatApp::getPostedData('product_brand_id', FatUtility::VAR_INT, 0);

        $ptcIds = FatApp::getPostedData('ptc_prodcat_id');
        $post['ptc_prodcat_id'] = is_array($ptcIds) ? array_values(array_filter(array_map('intval', $ptcIds))) : (FatUtility::int($ptcIds) > 0 ? [FatUtility::int($ptcIds)] : []);
        $post['ptt_taxcat_id'] = FatApp::getPostedData('ptt_taxcat_id', FatUtility::VAR_INT, 0);
        $post['ps_from_country_id'] = FatApp::getPostedData('ps_from_country_id', FatUtility::VAR_INT, 0);
        /* select2 data ] */

        $this->validateGetForm($post);

        $recordId = $post['record_id'];
        $langId = $post['lang_id'];

        $isNewProduct = true;
        if (0 < $recordId) {
            $isNewProduct = false;
        }

        $post['product_seller_id'] = $this->userParentId;
        $post['product_added_by_admin_id'] = 0;

        if ($isNewProduct) {
            $prodRequireAdminApproval = FatApp::getConfig("CONF_CUSTOM_PRODUCT_REQUIRE_ADMIN_APPROVAL", FatUtility::VAR_INT, 1);
            $post['product_approved'] = ($prodRequireAdminApproval == 1) ? 0 : 1;
        }

        if (Product::PRODUCT_TYPE_PHYSICAL == $productType) {
            $fulfillmentType = -1;
            $shipBySeller = Product::isProductShippedBySeller($recordId, $this->userParentId, $this->userParentId);

            if ($shipBySeller && !FatApp::getConfig('CONF_SHIPPED_BY_ADMIN_ONLY', FatUtility::VAR_INT, 0)) {
                $fulfillmentType = Shop::getAttributesByUserId($this->userParentId, 'shop_fulfillment_type');
                $shopDetails = Shop::getAttributesByUserId($this->userParentId, null, false);
                $address = new Address(0, $this->siteLangId);
                $addresses = $address->getData(Address::TYPE_SHOP_PICKUP, $shopDetails['shop_id']);
                $fulfillmentType = empty($addresses) ? Shipping::FULFILMENT_SHIP : $fulfillmentType;
            } else {
                $fulfillmentType = FatApp::getConfig('CONF_FULFILLMENT_TYPE', FatUtility::VAR_INT, -1);
            }

            $post['product_fulfillment_type'] = FatApp::getPostedData('product_fulfillment_type', FatUtility::VAR_INT, 0);
            $fullfilmentOptions = Shipping::getFulFillmentArr($this->siteLangId, $fulfillmentType);
            if (!array_key_exists($post['product_fulfillment_type'], $fullfilmentOptions)) {
                LibHelper::exitWithError($this->str_invalid_request, true);
            }
        }

      //  $productCategories = $post['ptc_prodcat_id'];
      //  unset($post['ptc_prodcat_id']);


        $prodObj = new Product($recordId);
        $db = FatApp::getDb();
        $db->startTransaction();

        if (!$prodObj->saveProductData($post)) {
            $db->rollbackTransaction();
            $message = $prodObj->getError();
            if (false !== strpos(strtolower($message), 'duplicate')) {
                $message = Labels::getLabel('ERR_DUPLICATE_RECORD_IDENTIFIER', $this->siteLangId);
            }
            LibHelper::exitWithError($message, true);
        }
        $recordId = $prodObj->getMainTableRecordId();

        $this->setLangData($prodObj, [
            $prodObj::tblFld('name') => $post[$prodObj::tblFld('name')],
            $prodObj::tblFld('short_description') => $post[$prodObj::tblFld('short_description')] ?? '',
            $prodObj::tblFld('description') => $post[$prodObj::tblFld('description')],
            $prodObj::tblFld('youtube_video') => $post[$prodObj::tblFld('youtube_video')]
        ], $langId);


        if (0 < FatApp::getConfig('CONF_WITHOUT_PROD_VARIANTS', FatUtility::VAR_INT, 0)) {
            $selProdId = $this->setupInventory($recordId, $db);
            if (1 > $selProdId) {
                $db->rollbackTransaction();
                LibHelper::exitWithError($this->str_invalid_request, true);
            }
        }

        if (empty($post['ptc_prodcat_id'])) {
            $db->rollbackTransaction();
            LibHelper::exitWithError(Labels::getLabel('ERR_PLEASE_SELECT_AT_LEAST_ONE_CATEGORY', $langId), true);
        }
        if (!$prodObj->addUpdateProductCategories($recordId, $post['ptc_prodcat_id'])) {
            $db->rollbackTransaction();
            LibHelper::exitWithError($prodObj->getError(), true);
        }

        if (!$prodObj->saveProductTax($post['ptt_taxcat_id'], $post['product_seller_id'])) {
            $db->rollbackTransaction();
            LibHelper::exitWithError($prodObj->getError(), true);
        }

        if (isset($post['specifications']) && is_array($post['specifications'])) {
            foreach ($post['specifications'] as $specification) {
                if (!$prodObj->saveProductSpecifications($specification['id'], $langId, $specification['name'], $specification['value'], $specification['group'])) {
                    $db->rollbackTransaction();
                    LibHelper::exitWithError($prodObj->getError(), true);
                }

                $specId = $prodObj->prodSpecId;
                $autoUpdateOtherLangsData = FatApp::getPostedData('auto_update_other_langs_data', FatUtility::VAR_INT, 0);
                if (0 < $autoUpdateOtherLangsData && 0 < $specId && empty($post['record_id'])) {
                    $languages = Language::getAllNames(false);
                    unset($languages[$langId]);
                    foreach ($languages as $toLangId => $langData) {
                        $translateLangobj = new TranslateLangData(ProdSpecification::DB_TBL);
                        $translatedData = $translateLangobj->directTranslate($specification, $toLangId);
                        if (isset($translatedData[$toLangId]) && !empty($translatedData[$toLangId])) {
                            $translatedData = $translatedData[$toLangId];
                            if (!$prodObj->saveProductSpecifications($specId, $toLangId, $translatedData['name'], $translatedData['value'], $translatedData['group'])) {
                                $db->rollbackTransaction();
                                LibHelper::exitWithError($prodObj->getError(), true);
                            }
                        }
                    }
                }
            }
        }

        $psFree = isset($post['ps_free']) ? $post['ps_free'] : 0;
        if (!$prodObj->saveProductSellerShipping($post['product_seller_id'], $psFree, $post['ps_from_country_id'])) {
            $db->rollbackTransaction();
            LibHelper::exitWithError($prodObj->getError(), true);
        }

        if (isset($post['shipping_profile'])) {
            $sellerShippingProfile = false;
            if (1 > FatApp::getConfig('CONF_SHIPPED_BY_ADMIN_ONLY', FatUtility::VAR_INT, 0) && 0 < $post['product_seller_id']) {
                $defaultShippingProfileId = ShippingProfile::getDefaultProfileId($post['product_seller_id']);
                $shipProProdData = array(
                    'shippro_shipprofile_id' => !empty($post['shipping_profile']) ? $post['shipping_profile'] : $defaultShippingProfileId,
                    'shippro_product_id' => $recordId,
                    'shippro_user_id' => $post['product_seller_id'],
                );
                $spObj = new ShippingProfileProduct();
                if (!$spObj->addProduct($shipProProdData)) {
                    $db->rollbackTransaction();
                    LibHelper::exitWithError($spObj->getError(), true);
                }
                $sellerShippingProfile = true;
            }

            $defaultShippingProfileId = ShippingProfile::getDefaultProfileId(0);
            $shipProProdData = array(
                'shippro_shipprofile_id' => !empty($post['shipping_profile']) && false == $sellerShippingProfile ? $post['shipping_profile'] : $defaultShippingProfileId,
                'shippro_product_id' => $recordId,
                'shippro_user_id' => 0,
            );
            $spObj = new ShippingProfileProduct();
            if (!$spObj->addProduct($shipProProdData)) {
                $db->rollbackTransaction();
                LibHelper::exitWithError($spObj->getError(), true);
            }
        }

        $productSpecifics = new ProductSpecifics($recordId);
        $productSpecifics->assignValues(($post + ['ps_product_id' => $recordId]));
        $data = $productSpecifics->getFlds();
        if (!$productSpecifics->addNew(array(), $data)) {
            $db->rollbackTransaction();
            LibHelper::exitWithError($productSpecifics->getError(), true);
        }

        if (isset($post['product_tags']) && !empty($post['product_tags'])) {
            $productTags = json_decode($post['product_tags'], true);
            foreach ($productTags as $tag) {
                if (!isset($tag['id'])) {
                    $tagObj = new Tag();
                    $tagObj->assignValues(['tag_name' => $tag['value'], 'tag_lang_id' => $langId]);
                    if (!$tagObj->save()) {
                        $db->rollbackTransaction();
                        LibHelper::exitWithError($tagObj->getError(), true);
                    }
                    $tagId = $tagObj->getMainTableRecordId();
                } else {
                    $tagId = $tag['id'];
                }
                if (!$prodObj->addUpdateProductTag($tagId)) {
                    $db->rollbackTransaction();
                    LibHelper::exitWithError($prodObj->getError(), true);
                }
            }
        }

        if (isset($post['options']) && isset($post['optionValues'])) {
            foreach ($post['options'] as $index => $optionId) {
                $opValuesArr = array_column(json_decode($post['optionValues'][$index]), 'id');
                if (!$prodObj->addUpdateProductOption($optionId, implode(",", $opValuesArr))) {
                    $db->rollbackTransaction();
                    LibHelper::exitWithError($prodObj->getError(), true);
                }
            }
        }

        UpcCode::remove($recordId);
        if (isset($post['product_upcs']) && !empty($post['product_upcs'])) {
            foreach ($post['product_upcs'] as $optionsIds => $upcCode) {
                $dataToSave = array(
                    'upc_code' => $upcCode,
                    'upc_product_id' => $recordId,
                    'upc_options' => $optionsIds,
                );
                if (!$db->insertFromArray(UpcCode::DB_TBL, $dataToSave, false, [], $dataToSave)) {
                    $db->rollbackTransaction();
                    LibHelper::exitWithError($db->getError(), true);
                }
            }
        }


        Tag::updateProductTagString($recordId);
        if ($isNewProduct) {
            $prodObj->moveTempFiles($post['temp_product_id']);
        }

        $newTabLangId = 0;
        $languages = Language::getDropDownList(CommonHelper::getDefaultFormLangId());
        if (0 < count($languages)) {
            foreach ($languages as $langId => $langName) {
                if (!$prodObj::getAttributesByLangId($langId, $recordId)) {
                    $newTabLangId = $langId;
                    break;
                }
            }
        }

        $db->commitTransaction();
        CalculativeDataRecord::updateSelprodRequestCount();
        Product::updateMinPrices($recordId);
        $this->set('recordId', $recordId);
        $this->set('langId', $newTabLangId);
        $this->set('msg', $this->str_setup_successful);
        $this->_template->render(false, false, 'json-success.php');
    }

    public function prodSpecifications()
    {
        $recordId = FatApp::getPostedData('recordId', FatUtility::VAR_INT, 0);
        $langId = FatApp::getPostedData('langId', FatUtility::VAR_INT, 0);
        if (1 > $langId) {
            $langId = CommonHelper::getDefaultFormLangId();
        }
        $productSpecifications = [];
        if (0 < $recordId) {
            $prod = new Product($recordId);
            $productSpecifications = $prod->getProdSpecificationsByLangId($langId);
        }
        $this->set('productSpecifications', $productSpecifications);
        $this->set('langId', $langId);
        $this->set('html', $this->_template->render(false, false, NULL, true));
        $this->_template->render(false, false, 'json-success.php', true, false);
    }

    public function removeProductOption()
    {
        $this->checkEditPrivilege();

        $recordId = FatApp::getPostedData('recordId', FatUtility::VAR_INT, 0);
        $optionId = FatApp::getPostedData('optionId', FatUtility::VAR_INT, 0);

        if (1 > $recordId || 1 > $optionId) {
            LibHelper::exitWithError($this->str_invalid_request, true);
        }

        if (SellerProduct::isOptionLinked($optionId, $recordId)) {
            LibHelper::exitWithError(Labels::getLabel('ERR_OPTION_IS_LINKED_WITH_SELLER_INVENTORY', $this->siteLangId), true);
        }

        $prodObj = new Product($recordId);
        if (!$prodObj->removeProductOption($optionId)) {
            LibHelper::exitWithError($prodObj->getError(), true);
        }
        UpcCode::remove($recordId);
        $this->set('msg', Labels::getLabel('MSG_OPTION_REMOVED_SUCCESSFULLY', $this->siteLangId));
        $this->_template->render(false, false, 'json-success.php');
    }

    /** use while deleting opvalue from catalog form */
    public function canDeleteOpValue()
    {
        $recordId = FatApp::getPostedData('recordId', FatUtility::VAR_INT, 0);
        $optionId = FatApp::getPostedData('optionId', FatUtility::VAR_INT, 0);
        $optionValueId = FatApp::getPostedData('optionValueId', FatUtility::VAR_INT, 0);

        if (1 > $recordId || 1 > $optionId || 1 > $optionValueId) {
            LibHelper::exitWithError($this->str_invalid_request, true);
        }

        if (SellerProduct::isOptionValueLinked($optionId, $optionValueId, $recordId)) {
            LibHelper::exitWithError(Labels::getLabel('ERR_OPTION_VALUE_IS_LINKED_WITH_SELLER_INVENTORY', $this->siteLangId), true);
        }
        FatUtility::dieJsonSuccess('');
    }

    public function updateProductTag()
    {
        $this->checkEditPrivilege();
        $recordId = FatApp::getPostedData('product_id', FatUtility::VAR_INT, 0);
        $tagId = FatApp::getPostedData('tag_id', FatUtility::VAR_INT, 0);
        if ($recordId < 1 || $tagId < 1) {
            LibHelper::exitWithError($this->str_invalid_request, true);
        }
        $prod = new Product($recordId);
        if (!$prod->addUpdateProductTag($tagId)) {
            LibHelper::exitWithError($prod->getError(), true);
        }

        Tag::updateProductTagString($recordId);

        $this->set('msg', $this->str_update_record);
        $this->_template->render(false, false, 'json-success.php');
    }

    public function removeProductTag()
    {
        $this->checkEditPrivilege();
        $recordId = FatApp::getPostedData('product_id', FatUtility::VAR_INT, 0);
        $tagId = FatApp::getPostedData('tag_id', FatUtility::VAR_INT, 0);
        if ($recordId < 1 || $tagId < 1) {
            LibHelper::exitWithError($this->str_invalid_request, true);
        }

        $prod = new Product($recordId);
        if (!$prod->removeProductTag($tagId)) {
            LibHelper::exitWithError($prod->getError(), true);
        }

        Tag::updateProductTagString($recordId);

        $this->set('msg', Labels::getLabel('MSG_TAG_REMOVED_SUCCESSFULLY', $this->siteLangId));
        $this->_template->render(false, false, 'json-success.php');
    }

    public function images($recordId, $fileType = 0, $optionId = 0, $langId = 0)
    {
        $recordId = FatUtility::int($recordId);
        $fileType = FatUtility::int($fileType);
        if (1 > $recordId) {
            LibHelper::exitWithError($this->str_invalid_request_id, true);
        }

        $languages = Language::getAllNames();
        if (count($languages) <= 1) {
            $langId = array_key_first($languages);
        }

        if ($fileType == AttachedFile::FILETYPE_PRODUCT_IMAGE_TEMP) {
            $images = AttachedFileTemp::getMultipleAttachments($fileType, $recordId, $optionId, $langId, (count($languages) <= 1) ? true : false, 0, 0, true);
        } else {
            $fileType = AttachedFile::FILETYPE_PRODUCT_IMAGE;
            if (!Product::getAttributesById($recordId, 'product_id')) {
                LibHelper::exitWithError($this->str_invalid_request_id, true);
            }
            $images = AttachedFile::getMultipleAttachments($fileType, $recordId, $optionId, $langId, (count($languages) <= 1) ? true : false, 0, 0, true);
        }

        $this->set('images', $images);
        $this->set('recordId', $recordId);
        $this->set('isDefaultLayout', FatApp::getPostedData('isDefaultLayout', FatUtility::VAR_INT, 0));
        $this->checkEditPrivilege(true);
        $this->set('html', $this->_template->render(false, false, NULL, true));
        $this->_template->render(false, false, 'json-success.php', true, false);
    }

    public function setImageOrder()
    {
        $this->checkEditPrivilege();
        $recordId = FatApp::getPostedData('record_id', FatUtility::VAR_INT, 0);
        $fileType = FatApp::getPostedData('file_type', FatUtility::VAR_INT, 0);

        $post = FatApp::getPostedData();
        $imageIds = explode('-', $post['ids']);
        $count = 1;
        foreach ($imageIds as $row) {
            $order[$count] = $row;
            $count++;
        }
        $product = new Product();
        if (!$product->updateProdImagesOrder($recordId, $fileType, $order)) {
            LibHelper::exitWithError($product->getError(), true);
        }
        $this->set("msg", $this->str_update_record);
        $this->_template->render(false, false, 'json-success.php');
    }

    public function uploadMedia()
    {
        $this->checkEditPrivilege();
        $post = FatApp::getPostedData();
        if (empty($post)) {
            LibHelper::exitWithError(Labels::getLabel('ERR_INVALID_REQUEST_OR_FILE_NOT_SUPPORTED', $this->siteLangId), true);
        }
        if (!is_uploaded_file($_FILES['cropped_image']['tmp_name'])) {
            LibHelper::exitWithError(Labels::getLabel('ERR_PLEASE_SELECT_A_FILE', $this->siteLangId), true);
        }

        $recordId = $recordId = FatUtility::int($post['record_id']);
        $optionId = FatUtility::int($post['option_id']);
        $fileType = FatUtility::int($post['file_type']);
        if (!in_array($fileType, [AttachedFile::FILETYPE_PRODUCT_IMAGE, AttachedFile::FILETYPE_PRODUCT_IMAGE_TEMP])) {
            LibHelper::exitWithError($this->str_invalid_request, true);
        }

        if (1 > $recordId) {
            LibHelper::exitWithError($this->str_invalid_request, true);
        }

        $languages = Language::getAllNames();
        if (count($languages) > 1) {
            $langId = FatUtility::int($post['lang_id']);
        } else {
            $langId = array_key_first($languages);
        }
        /* Validate product belongs to current logged seller[ */
        if ($fileType != AttachedFile::FILETYPE_PRODUCT_IMAGE_TEMP && 0 < $recordId) {
            $productRow = Product::getAttributesById($recordId, array('product_seller_id'));
            $optionValues = Product::getSeparateImageOptions($recordId, $this->siteLangId);
            if ($productRow['product_seller_id'] != $this->userParentId || !array_key_exists($optionId, $optionValues)) {
                LibHelper::exitWithError($this->str_invalid_request);
            }
        }

        $this->validateImageSubscriptionLimit($recordId, $optionId, $langId, $fileType, $this->userParentId);

        if ($fileType == AttachedFile::FILETYPE_PRODUCT_IMAGE_TEMP) {
            $fileHandlerObj = new AttachedFileTemp();
            $fileHandlerObj->setDownloadedAttr(true);
        } else {
            $fileHandlerObj = new AttachedFile();
        }
        if (!$fileHandlerObj->saveImage($_FILES['cropped_image']['tmp_name'], $fileType, $recordId, $optionId, $_FILES['cropped_image']['name'], -1, false, $langId)) {
            LibHelper::exitWithError($fileHandlerObj->getError(), true);
        }

        if (AttachedFile::FILETYPE_PRODUCT_IMAGE == $fileType) {
            FatApp::getDb()->updateFromArray('tbl_products', array('product_img_updated_on' => date('Y-m-d H:i:s')), array('smt' => 'product_id = ?', 'vals' => array($recordId)));
        }

        if (count($languages) > 1) {
            $this->set("isDefaultLayout", $langId == 0 && $optionId == 0);
        } else {
            $this->set("isDefaultLayout", $langId == CommonHelper::getDefaultFormLangId() && $optionId == 0);
        }

        $this->set("lang_id", $langId);
        $this->set("option_id", $optionId);
        $this->set("record_id", $recordId);
        $this->set("file_type", $fileType);
        $this->set("msg", Labels::getLabel('MSG_FILE_UPLOADED_SUCCESSFULLY', $this->siteLangId));
        $this->_template->render(false, false, 'json-success.php');
    }

    public function deleteImage($recordId, $imageId, $fileType)
    {
        $this->checkEditPrivilege();
        $recordId = FatUtility::int($recordId);
        $imageId = FatUtility::int($imageId);
        $fileType = FatUtility::int($fileType);

        if (1 > $imageId || 1 > $recordId || 1 > $fileType) {
            LibHelper::exitWithError($this->str_invalid_request, true);
        }

        if ($fileType == AttachedFile::FILETYPE_PRODUCT_IMAGE_TEMP) {
            $fileHandlerObj = new AttachedFileTemp();
        } else {
            $fileHandlerObj = new AttachedFile();
            $productUserId = Product::getAttributesById($recordId, 'product_seller_id');
            if ($productUserId != $this->userParentId) {
                LibHelper::exitWithError($this->str_invalid_request, true);
            }
        }

        $data = $fileHandlerObj::getAttributesById($imageId, ['afile_lang_id', 'afile_record_subid']);
        if (false == $data) {
            LibHelper::exitWithError($this->str_invalid_request, true);
        }

        $productObj = new Product();
        if (!$productObj->deleteProductImage($recordId, $imageId, $fileType)) {
            LibHelper::exitWithError($productObj->getError(), true);
        }

        FatApp::getDb()->updateFromArray('tbl_products', array('product_img_updated_on' => date('Y-m-d H:i:s')), array('smt' => 'product_id = ?', 'vals' => array($recordId)));
        $languages = Language::getAllNames();
        if (count($languages) > 1) {
            $this->set("isDefaultLayout", $data['afile_lang_id'] == 0 && $data['afile_record_subid'] == 0);
        } else {
            $this->set("isDefaultLayout", $data['afile_lang_id'] == CommonHelper::getDefaultFormLangId() && $data['afile_record_subid'] == 0);
        }
        $this->set("optionId", $data['afile_record_subid']);
        $this->set("langId", $data['afile_lang_id']);
        $this->set("msg", $this->str_delete_record);
        $this->_template->render(false, false, 'json-success.php');
    }

    public function upcListing()
    {
        $recordId = FatApp::getPostedData('recordId', FatUtility::VAR_INT, 0);
        $langId = FatApp::getPostedData('langId', FatUtility::VAR_INT, 0);
        $productOptions = FatApp::getPostedData('productOptions');
        $type = FatApp::getPostedData('type', FatUtility::VAR_INT, 0);

        $upcCodeData = [];
        if (0 < $recordId) {
            $srch = UpcCode::getSearchObject();
            $srch->addCondition('upc_product_id', '=', $recordId);
            $srch->doNotCalculateRecords();
            $upcCodeData = FatApp::getDb()->fetchAll($srch->getResultSet(), 'upc_options');
        }

        $optionCombinations = [];
        if ($type == applicationConstants::NO && is_array($productOptions)) {
            $optionCombinations = CommonHelper::combinationOfElementsOfArr($productOptions, 'optionValues');
        }

        $this->set('optionCombinations', $optionCombinations);
        $this->set('upcCodeData', $upcCodeData);
        $this->set('recordId', $recordId);
        $this->set('langId', $langId);
        $this->set('html', $this->_template->render(false, false, NULL, true));
        $this->_template->render(false, false, 'json-success.php', true, false);
    }

    public function deleteProdSpec()
    {
        $this->checkEditPrivilege();
        $prodSpecId = FatApp::getPostedData('prodSpecId', FatUtility::VAR_INT, 0);
        $prodSpecLangId = FatApp::getPostedData('prodSpecLangId', FatUtility::VAR_INT, 0);
        if ($prodSpecId < 1) {
            LibHelper::exitWithError($this->str_invalid_request, true);
        }

        $productId = ProdSpecification::getAttributesById($prodSpecId, 'prodspec_product_id');
        if (1 > $productId ||   $this->userParentId != Product::getAttributesById($productId, 'product_seller_id')) {
            LibHelper::exitWithError($this->str_invalid_request, true);
        }

        $prodSpec = new ProdSpecification($prodSpecId);
        if (!$prodSpec->deleteRecords($prodSpecLangId)) {
            LibHelper::exitWithError($prodSpec->getError(), true);
        }

        $this->set('msg', $this->str_delete_record);
        $this->_template->render(false, false, 'json-success.php');
    }

    private function getForm($langId, $productType = 0, $recordId = 0)
    {
        $frm = $this->getCatalogForm($langId, $productType, $recordId);
        $shippingObj = new Shipping($this->userParentId);
        $profileFld = $frm->getField('shipping_profile');
        if (null != $profileFld) {
            if (FatApp::getConfig('CONF_SHIPPED_BY_ADMIN_ONLY', FatUtility::VAR_INT, 0) || ($shippingObj->getShippingApiObj($this->userParentId) && !Shop::getAttributesByUserId($this->userParentId, 'shop_use_manual_shipping_rates'))) {
                $frm->removeField($profileFld);
            } else {
                $profileFld->options = ShippingProfile::getProfileArr($langId, $this->userParentId, true, true);
            }
        }
        $fld = $frm->getField('product_approved');
        if (null != $fld) {
            $frm->removeField($fld);
        }

        if (!in_array($productType, [Product::PRODUCT_TYPE_DIGITAL, Product::PRODUCT_TYPE_SERVICE])) {
            $fulfillmentType = -1;
            $shipBySeller = Product::isProductShippedBySeller($recordId, $this->userParentId, $this->userParentId);

            if ($shipBySeller && !FatApp::getConfig('CONF_SHIPPED_BY_ADMIN_ONLY', FatUtility::VAR_INT, 0)) {
                $fulfillmentType = Shop::getAttributesByUserId($this->userParentId, 'shop_fulfillment_type');
            } else {
                $fulfillmentType = FatApp::getConfig('CONF_FULFILLMENT_TYPE', FatUtility::VAR_INT, -1);
            }

            $shopDetails = Shop::getAttributesByUserId($this->userParentId, null, false);
            $address = new Address(0, $langId);
            $addresses = $address->getData(Address::TYPE_SHOP_PICKUP, $shopDetails['shop_id']);
            $fulfillmentType = empty($addresses) ? Shipping::FULFILMENT_SHIP : $fulfillmentType;

            $productFulfillmentType = $frm->getField('product_fulfillment_type');
            $productFulfillmentType->options = Shipping::getFulFillmentArr($langId, $fulfillmentType);
        }
        return $frm;
    }

    protected function setLangData(object $classObj, array $langDataArr, $langId = 0)
    {
        $recordId = $classObj->getMainTableRecordId();
        if (!$classObj->updateLangData((0 < $langId  ? $langId : CommonHelper::getDefaultFormLangId()), $langDataArr)) {
            LibHelper::exitWithError($classObj->getError(), true);
        }

        $languages = Language::getDropDownList(CommonHelper::getDefaultFormLangId());
        if (0 < count($languages)) {
            foreach ($languages as $languageId => $langName) {
                if (!$classObj::getAttributesByLangId($languageId, $recordId)) {
                    $this->newTabLangId = $languageId;
                    break;
                }
            }
        }

        $autoUpdateOtherLangsData = FatApp::getPostedData('auto_update_other_langs_data', FatUtility::VAR_INT, 0);
        if (0 < $autoUpdateOtherLangsData && 0 < $langId) {
            $updateLangDataobj = new TranslateLangData($classObj::DB_TBL_LANG);
            if (false === $updateLangDataobj->updateTranslatedData($recordId)) {
                LibHelper::exitWithError($updateLangDataobj->getError(), true);
            }
        }
    }

    protected function getCatalogType(): int
    {
        return Product::CATALOG_TYPE_PRIMARY;
    }


    private function canAddProduct()
    {
        if (!$this->isShopActive($this->userParentId)) {
            LibHelper::exitWithError($this->str_invalid_request, false, true);
            FatApp::redirectUser(UrlHelper::generateUrl('Seller', 'shop'));
        }

        if (!User::canAddCustomProduct()) {
            LibHelper::exitWithError($this->str_invalid_request, false, true);
            FatApp::redirectUser(UrlHelper::generateUrl('Products'));
        }

        if (!UserPrivilege::isUserHasValidSubsription($this->userParentId)) {
            LibHelper::exitWithError(Labels::getLabel('ERR_PLEASE_BUY_SUBSCRIPTION', $this->siteLangId), false, true);
            FatApp::redirectUser(UrlHelper::generateUrl('Seller', 'Packages'));
        }
    }

    private function validateGetForm(&$post)
    {
        $recordId = $post['record_id'];

        $prodData = [];
        if (0 < $recordId) {
            $prodData = Product::getAttributesById($recordId, ['product_seller_id', 'product_active']);
            $prodSellerId = $prodData['product_seller_id'];
            if ($prodSellerId != $this->userParentId) {
                FatUtility::dieWithError($this->str_invalid_request);
            }
        }

        if (
            ((!empty($prodData) && applicationConstants::ACTIVE == $post['product_active'] && $prodData['product_active'] != $post['product_active']) || 1 > $recordId) &&
            FatApp::getConfig('CONF_ENABLE_SELLER_SUBSCRIPTION_MODULE', FatUtility::VAR_INT, 0) &&
            Product::getActiveCount($this->userParentId, 0, false) >= SellerPackages::getAllowedLimit($this->userParentId, $this->siteLangId, 'ossubs_products_allowed')
        ) {
            LibHelper::exitWithError(Labels::getLabel('ERR_YOU_HAVE_CROSSED_YOUR_PACKAGE_LIMIT', $this->siteLangId), false, true);
            FatApp::redirectUser(UrlHelper::generateUrl('Seller', 'Packages'));
        }

        $this->validateForm($post);
    }

    public function getBreadcrumbNodes($action)
    {
        $className = get_class($this);
        $arr = explode('-', FatUtility::camel2dashed($className));
        array_pop($arr);
        $className = ucwords(implode('_', $arr));

        if ($action == 'form') {
            $action = str_replace('-', '_', FatUtility::camel2dashed($action));
            $this->nodes[] = array('title' => Labels::getLabel('LBL_CATALOG'), 'href' => UrlHelper::generateUrl("Seller", "catalog"));
            $this->nodes[] = array('title' => ucwords(Labels::getLabel('BCN_' . $action)));
        } else {
            $action = str_replace('-', '_', FatUtility::camel2dashed($action));
            $this->nodes[] = array('title' => ucwords(Labels::getLabel('BCN_' . $action)));
        }
        return $this->nodes;
    }
}
