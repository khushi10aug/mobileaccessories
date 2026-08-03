<?php
class CustomProductsController extends SellerBaseController
{

    use CatalogProduct;
    use ProductDigitalDownloads;
    use ProductSetup;

    public function __construct($action)
    {
        parent::__construct($action);
        $this->userPrivilege->canViewSellerRequests();
        $this->canAddCustomCatalogProduct();
    }

    public function index()
    {
        FatApp::redirectUser(UrlHelper::generateUrl('sellerRequests'));
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
            $this->set("canEdit", $this->userPrivilege->canEditSellerRequests(UserAuthentication::getLoggedUserId(), true));
        } else {
            $this->userPrivilege->canEditSellerRequests();
        }
    }

    public function form($recordId = 0, $productType = 0)
    {
        $this->checkEditPrivilege();

        if (0 < FatApp::getConfig('CONF_WITHOUT_PROD_VARIANTS', FatUtility::VAR_INT, 0)) {
            CommonHelper::redirectUserReferer();
        }

        $userId = UserAuthentication::getLoggedUserId();

        $recordId = FatUtility::int($recordId);

        $productType = FatUtility::int($productType);
        $langId = FatApp::getPostedData('langId', FatUtility::VAR_INT, 0);
        if (1 > $langId) {
            $langId = CommonHelper::getDefaultFormLangId();
        }

        $frm = $this->getForm($langId, $productType, $recordId);
        $imgFrm = $this->getImageFrm($recordId);
        $productOptions = [];
        if (0 < $recordId) {
            if (0 < FatApp::getPostedData('autoFillLangData', FatUtility::VAR_INT, 0)) {
                $productData = ProductRequest::getAttributesByLangId(CommonHelper::getDefaultFormLangId(), $recordId, null, applicationConstants::JOIN_RIGHT);
                if (!empty($productData['preq_lang_data'])) {
                    $preqLangData = json_decode($productData['preq_lang_data'], true);
                    $updateLangDataobj = new TranslateLangData(ProductRequest::DB_TBL_LANG);
                    $translatedData = $updateLangDataobj->directTranslate($preqLangData, $langId);
                    if (false === $translatedData) {
                        LibHelper::exitWithError($updateLangDataobj->getError(), true);
                    }
                    $productData = array_merge($productData, current($translatedData));
                }
            } else {
                $productData = ProductRequest::getAttributesByLangId($langId, $recordId, null, applicationConstants::JOIN_RIGHT);
                if ($productData && !empty($productData['preq_lang_data'])) {
                    $productData = array_merge($productData, json_decode($productData['preq_lang_data'], true));
                }
            }

            if (empty($productData)) {
                LibHelper::exitWithError($this->str_invalid_request_id, true);
                FatApp::redirectUser(UrlHelper::generateUrl('CustomProducts'));
            }

            unset($productData['preq_lang_data']);

            if ($productData['preq_status'] != ProductRequest::STATUS_PENDING) {
                LibHelper::exitWithError($this->str_invalid_request, true);
            }

            $productData = array_merge(
                $productData,
                json_decode($productData['preq_content'], true)
            );
            unset($productData['preq_content']);

            if (1 > $productType) {
                $frm = $this->getForm($langId, $productData['product_type'], $recordId);
            }

            $tagData = [];
            if (!empty($productData['product_tags'])) {
                $srch = Tag::getSearchObject($langId);
                $srch->addCondition('tag_id', 'IN', $productData['product_tags']);
                $srch->addMultipleFields(['tag_id', 'tag_name']);
                $productTags = FatApp::getDb()->fetchAll($srch->getResultSet());
                foreach ($productTags as $key => $data) {
                    $tagData[$key]['id'] = $data['tag_id'];
                    $tagData[$key]['value'] = htmlspecialchars($data['tag_name'], ENT_QUOTES, 'UTF-8');
                }
            }
            $productData['product_tags'] = json_encode($tagData);
            if (0 < $productData['preq_brand_id']) {
                $productData['product_brand_id'] = $productData['preq_brand_id'];
                $brandData = Brand::getAttributesByLangId($langId, $productData['preq_brand_id'], [Brand::tblFld('name'), Brand::tblFld('identifier')], applicationConstants::JOIN_RIGHT, applicationConstants::YES, applicationConstants::NO);
                if (false != $brandData) {
                    $fld = $frm->getField('product_brand_id');
                    $fld->options = [$productData['preq_brand_id'] => $brandData[Brand::tblFld('name')] ?? $brandData[Brand::tblFld('identifier')]];
                }
                unset($productData['preq_brand_id']);
            }

            if (!empty($productData['preq_prodcat_id'])) {
                $productData['ptc_prodcat_id'] = $productData['preq_prodcat_id'];
                $catData = ProductCategory::getAttributesByLangId($langId, $productData['preq_prodcat_id'], [ProductCategory::tblFld('name'), ProductCategory::tblFld('identifier')], applicationConstants::JOIN_RIGHT, applicationConstants::YES, applicationConstants::NO);
                if (false != $catData) {
                    $fld = $frm->getField('ptc_prodcat_id');
                    $fld->options = [$productData['ptc_prodcat_id'] => $catData[ProductCategory::tblFld('name')] ?? $catData[ProductCategory::tblFld('identifier')]];
                }
                unset($productData['preq_prodcat_id']);
            }

            if (Tax::getActivatedServiceId()) {
                $taxCatMultiFields = ['concat(IFNULL(taxcat_name,taxcat_identifier)', '" (",taxcat_code,")") as taxcat_name', 'taxcat_id'];
            } else {
                $taxCatMultiFields = ['IFNULL(taxcat_name,taxcat_identifier) as taxcat_name', 'taxcat_id'];
            }

            //ptt_taxcat_id        
            if (!empty($productData['ptt_taxcat_id'])  && 0 < $productData['ptt_taxcat_id']) {
                $taxData = Tax::getAttributesByLangId($langId, $productData['ptt_taxcat_id'], $taxCatMultiFields, applicationConstants::JOIN_RIGHT, applicationConstants::YES, applicationConstants::NO);
                if ($catData) {
                    $fld = $frm->getField('ptt_taxcat_id');
                    $fld->options = [$productData['ptt_taxcat_id'] => $taxData[Tax::tblFld('name')] ?? $taxData[Tax::tblFld('identifier')]];
                }
            }

            if (!empty($productData['ps_from_country_id'])  && 0 < $productData['ps_from_country_id']) {
                $countryData = Countries::getAttributesByLangId($langId, $productData['ps_from_country_id'], [Countries::tblFld('name'), Countries::tblFld('code')], applicationConstants::JOIN_RIGHT, applicationConstants::YES);
                if ($countryData) {
                    $fld = $frm->getField('ps_from_country_id');
                    if (null != $fld) {
                        $fld->options = [$productData['ps_from_country_id'] => $countryData[Tax::tblFld('name')] ?? $taxData[Tax::tblFld('identifier')]];
                    }
                }
            }

            if (isset($productData['product_option']) && is_array($productData['product_option']) && count($productData['product_option'])) {
                $srch = Option::getSearchObject($langId);
                $srch->addMultipleFields(['option_id', 'option_identifier', 'option_name', 'option_is_separate_images']);
                $srch->addCondition('option_id', 'IN', $productData['product_option']);
                $prodOptions = FatApp::getDb()->fetchAll($srch->getResultSet(), 'option_id');
                foreach ($productData['product_option'] as $index => $optionId) {
                    if ($prodOptions[$optionId]) {
                        $productOptions[$index] = $prodOptions[$optionId];
                        $productOptions[$index]['optionValues'] = Product::getOptionValues($optionId, $langId, ($productData['product_option_values'][$index] ?? []));
                    }
                }
            }
            $productData['upc_type'] = applicationConstants::YES;
            $productData['preq_ean_upc_code'] = json_decode($productData['preq_ean_upc_code'], true);
            if (!empty($productData['preq_ean_upc_code']) && count($productData['preq_ean_upc_code']) &&  array_key_first($productData['preq_ean_upc_code']) != 0) {
                $productData['upc_type'] = applicationConstants::NO;
            }

            $this->set("productData", [
                'product_type' => $productData['product_type'],
                'product_seller_id' => $productData['preq_user_id'],
                'product_attachements_with_inventory' => ($productData['product_attachements_with_inventory'] ?? 0),
                'preq_submitted_for_approval' => $productData['preq_submitted_for_approval']
            ]);

            /* to select product type in get */
            if (0 < $productType) {
                $productData['product_type'] = $productType;
            }

            $productData['record_id'] = $recordId;

            if (0 < FatApp::getConfig('CONF_WITHOUT_PROD_VARIANTS', FatUtility::VAR_INT, 0)) {
                $selprodData = json_decode($productData['preq_sel_prod_data'], true);
                if (!empty($selprodData)) {
                    $productData = array_merge($productData, $selprodData);
                }
            }

            $frm->fill($productData);
            $imgFrm->fill(['file_type' => AttachedFile::FILETYPE_CUSTOM_PRODUCT_IMAGE, 'record_id' => $recordId]);
        } else {
            $tempProductId = time() . $userId;
            $frm->fill(['temp_product_id' => $tempProductId]);
            $imgFrm->fill(['file_type' => AttachedFile::FILETYPE_CUSTOM_PRODUCT_IMAGE_TEMP, 'record_id' => $tempProductId]);
        }

        $this->set("selProdId", 0);
        $this->set("frm", $frm);
        $this->set("imgFrm", $imgFrm);

        $codEnabled = true;
        $paymentMethod = new PaymentMethods();
        if (!$paymentMethod->cashOnDeliveryIsActive()) {
            $codEnabled = false;
        }
        $this->set("codEnabled", $codEnabled);
        $this->set("canEditTags",  $this->userPrivilege->canEditProducts(0, true));
        $this->set("langId", $langId);
        $this->set("recordId", $recordId);
        $this->set('productOptions', $productOptions);
        $this->set('formLayout', Language::getLayoutDirection($langId));
        if (FatUtility::isAjaxCall()) {
            $this->set('html', $this->_template->render(false, false, NULL, true));
            $this->_template->render(false, false, 'json-success.php', true, false);
            return;
        }

        $this->_template->addJs(array('custom-products/page-js/form.js', 'seller-requests/page-js/index.js', 'js/cropper.js', 'js/cropper-main.js', 'js/select2.js', 'js/tagify.min.js', 'js/tagify.polyfills.min.js'));
        $this->_template->addCss(array('css/select2.min.css', 'css/tagify.min.css'));
        $this->set("includeEditor", true);
        $this->_template->render(true, true, 'custom-products/formWithNavigation.php');
    }

    public function setup()
    {
        $this->checkEditPrivilege();
        $userId = UserAuthentication::getLoggedUserId();

        $recordId = FatApp::getPostedData('record_id', FatUtility::VAR_INT, 0);
        $isNewProduct  = true;
        if (0 < $recordId) {
            $productData = ProductRequest::getAttributesById($recordId, ['preq_user_id', 'preq_status']);
            if (empty($productData)) {
                LibHelper::exitWithError($this->str_invalid_request_id);
            }

            if ($productData['preq_status'] != ProductRequest::STATUS_PENDING) {
                LibHelper::exitWithError($this->str_invalid_request_id);
            }
            $isNewProduct  = false;
        }

        $productType = FatApp::getPostedData('product_type', FatUtility::VAR_INT, 0);
        $langId = FatApp::getPostedData('lang_id', FatUtility::VAR_INT, 0);
        if (1 > $langId ||  !array_key_exists($productType, Product::getProductTypes($langId))) {
            LibHelper::exitWithError($this->str_invalid_request, true);
        }

        $frm = $this->getForm($langId, $productType, $recordId);
        $post = $frm->getFormDataFromArray(FatApp::getPostedData());
        if (false === $post) {
            LibHelper::exitWithError(current($frm->getValidationErrors()), true);
        }

        $productIdentifier = FatApp::getPostedData('product_identifier', FatUtility::VAR_STRING, '');
        $isValid = ProductRequest::isValidProductIdentifier($productIdentifier, $recordId);
        if (!$isValid) {
            LibHelper::exitWithError(Labels::getLabel('LBL_DUPLICATE_PRODUCT_IDENTIFER'), true);
        }

        /* [select2 data */
        $post['product_brand_id'] = FatApp::getPostedData('product_brand_id', FatUtility::VAR_INT, 0);
        $post['ptc_prodcat_id'] = FatApp::getPostedData('ptc_prodcat_id', FatUtility::VAR_INT, 0);
        $post['ptt_taxcat_id'] = FatApp::getPostedData('ptt_taxcat_id', FatUtility::VAR_INT, 0);
        /* select2 data ] */

        $this->validateGetForm($post);

        $requestStatus = FatApp::getPostedData('request_status', FatUtility::VAR_INT, 0);

        unset($post['btn_submit'], $post['record_id'], $post['product_approved'], $post['request_status']);

        $db = FatApp::getDb();
        $db->startTransaction();

        $data = [
            'preq_brand_id' => $post['product_brand_id'],
            'preq_prodcat_id' => $post['ptc_prodcat_id'],
            'preq_ean_upc_code' => json_encode($post['product_upcs']),
        ];
        $langData =  [
            'product_name' => $post['product_name'],
            'product_description' => $post['product_description'],
            'product_youtube_video' => $post['product_youtube_video'] ?? '',
            'product_tags' => [],
            'specifications' => $post['specifications'] ?? '',
        ];

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
                $langData['product_tags'][] = $tagId;
            }
        }

        foreach ($post['options'] as $index => $optionId) {
            $data['preq_content']['product_option'][] = $optionId;
            $opValuesArr = array_column(json_decode($post['optionValues'][$index]), 'id');
            $data['preq_content']['product_option_values'][] = $opValuesArr;
        }

        unset(
            $post['options'],
            $post['optionValues'],
            $post['product_upcs'],
            $post['lang_id'],
            $post['record_id'],
            $post['upc_type'],
        );

        $data['preq_content']['shipping_profile'] = array_key_first(ShippingProfile::getProfileArr($langId, 0, true, true));
        $data['preq_content'] = array_merge($data['preq_content'], array_diff_key($post, $langData, $data));
        $data['preq_content'] = json_encode($data['preq_content']);
        $data['preq_status'] = $requestStatus;

        if ($isNewProduct) {
            $data['preq_added_on'] = date('Y-m-d H:i:s');
            $data['preq_user_id'] = $userId;
        }

        $data['preq_product_identifier'] = $productIdentifier;

        if (0 < FatApp::getConfig('CONF_WITHOUT_PROD_VARIANTS', FatUtility::VAR_INT, 0)) {
            $selProdData = $this->setupInventory(type: 'REQUESTED_CATALOG_PRODUCT');
            $data['preq_sel_prod_data'] = json_encode($selProdData);
        }

        $prodReqObj = new ProductRequest($recordId);
        $prodReqObj->assignValues($data);
        if (!$prodReqObj->save()) {
            $db->rollbackTransaction();
            LibHelper::exitWithError($prodReqObj->getError(), true);
        }

        $recordId = $prodReqObj->getMainTableRecordId();

        if (!$prodReqObj->updateLangData($langId, ['preq_lang_data' => json_encode($langData)])) {
            $db->rollbackTransaction();
            LibHelper::exitWithError($prodReqObj->getError(), true);
        }

        if ($isNewProduct) {
            $prodReqObj->moveTempFiles($post['temp_product_id']);
        }

        $newTabLangId = 0;
        $languages = Language::getDropDownList(CommonHelper::getDefaultFormLangId());
        if (0 < count($languages)) {
            foreach ($languages as $langId => $langName) {
                if (!$prodReqObj::getAttributesByLangId($langId, $recordId)) {
                    $newTabLangId = $langId;
                    break;
                }
            }
        }

        $db->commitTransaction();

        if (ProductRequest::STATUS_PENDING == $requestStatus) {
            CalculativeDataRecord::updateCustomCatalogCount();
        }

        $this->set('recordId', $recordId);
        $this->set('langId', $newTabLangId);
        $this->set('msg', $this->str_setup_successful);
        $this->_template->render(false, false, 'json-success.php');
    }

    public function images($recordId, $fileType = 0, $optionId = 0, $langId = 0)
    {
        $recordId = FatUtility::int($recordId);
        $fileType = FatUtility::int($fileType);
        $optionId = Product::encodeImageOptionSubId($optionId);

        $languages = Language::getAllNames();
        if (count($languages) <= 1) {
            $langId = array_key_first($languages);
        }

        if ($fileType == AttachedFile::FILETYPE_CUSTOM_PRODUCT_IMAGE_TEMP) {
            $images = AttachedFileTemp::getMultipleAttachments($fileType, $recordId, $optionId, $langId, (count($languages) <= 1) ? true : false, 0, 0, true);
        } else {
            $fileType = AttachedFile::FILETYPE_CUSTOM_PRODUCT_IMAGE;
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
        $post = FatApp::getPostedData();
        $recordId = FatUtility::int($post['record_id']);
        $fileType = FatUtility::int($post['file_type']);
        $imageIds = explode('-', $post['ids']);
        $count = 1;
        foreach ($imageIds as $row) {
            $order[$count] = $row;
            $count++;
        }
        $preqObj = new ProductRequest();
        if (!$preqObj->updateProdImagesOrder($recordId, $fileType, $order)) {
            LibHelper::exitWithError($preqObj->getError(), true);
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
        $optionKey = isset($post['option_id']) ? trim((string) $post['option_id']) : '0';
        $optionId = Product::encodeImageOptionSubId($optionKey);
        $fileType = FatUtility::int($post['file_type']);

        if (!in_array($fileType, [AttachedFile::FILETYPE_CUSTOM_PRODUCT_IMAGE, AttachedFile::FILETYPE_CUSTOM_PRODUCT_IMAGE_TEMP])) {
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
        if ($fileType != AttachedFile::FILETYPE_CUSTOM_PRODUCT_IMAGE_TEMP && 0 < $recordId) {
            $productUserId = ProductRequest::getAttributesById($recordId, 'preq_user_id');
            if ($productUserId != $this->userParentId) {
                LibHelper::exitWithError($this->str_invalid_request);
            }
        }

        if ($fileType == AttachedFile::FILETYPE_CUSTOM_PRODUCT_IMAGE_TEMP) {
            $fileHandlerObj = new AttachedFileTemp();
            $fileHandlerObj->setDownloadedAttr(true);
        } else {
            $fileHandlerObj = new AttachedFile();
        }
        if (!$fileHandlerObj->saveImage($_FILES['cropped_image']['tmp_name'], $fileType, $recordId, $optionId, $_FILES['cropped_image']['name'], -1, false, $langId)) {
            LibHelper::exitWithError($fileHandlerObj->getError(), true);
        }

        if (count($languages) > 1) {
            $this->set("isDefaultLayout", $langId == 0 && $optionKey === '0');
        } else {
            $this->set("isDefaultLayout", $langId == CommonHelper::getDefaultFormLangId() && $optionKey === '0');
        }

        $this->set("lang_id", $langId);
        $this->set("option_id", $optionKey);
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

        if ($fileType == AttachedFile::FILETYPE_CUSTOM_PRODUCT_IMAGE_TEMP) {
            $fileHandlerObj = new AttachedFileTemp();
        } else {
            $fileHandlerObj = new AttachedFile();
            $productUserId = ProductRequest::getAttributesById($recordId, 'preq_user_id');
            if ($productUserId != $this->userParentId) {
                LibHelper::exitWithError($this->str_invalid_request, true);
            }
        }

        $data = $fileHandlerObj::getAttributesById($imageId, ['afile_lang_id', 'afile_record_subid']);
        if (false == $data) {
            LibHelper::exitWithError($this->str_invalid_request, true);
        }

        $productObj = new ProductRequest();
        if (!$productObj->deleteProductImage($recordId, $imageId, $fileType)) {
            LibHelper::exitWithError($productObj->getError(), true);
        }

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

    public function prodSpecifications()
    {
        $recordId = FatApp::getPostedData('recordId', FatUtility::VAR_INT, 0);
        $langId = FatApp::getPostedData('langId', FatUtility::VAR_INT, 0);
        if (1 > $langId) {
            $langId = CommonHelper::getDefaultFormLangId();
        }
        $specifications =  [];
        if (0 < $recordId) {
            $langData = ProductRequest::getAttributesByLangId($langId, $recordId, 'preq_lang_data');
            if (!empty($langData)) {
                $langData = json_decode($langData, true);
                if (isset($langData['specifications']) && !empty($langData['specifications'])) {
                    foreach ($langData['specifications'] as $specification) {
                        $specifications[] = [
                            'prodspec_id' => '',
                            'prodspec_name' => $specification['name'],
                            'prodspec_value' => $specification['value'],
                            'prodspec_group' => $specification['group'],
                        ];
                    }
                }
            }
        }
        $this->set('productSpecifications', $specifications);
        $this->set('langId', $langId);
        $this->set('html', $this->_template->render(false, false, 'products/prod-specifications.php', true));
        $this->_template->render(false, false, 'json-success.php', true, false);
    }

    public function upcListing()
    {
        $recordId = FatApp::getPostedData('recordId', FatUtility::VAR_INT, 0);

        $langId = FatApp::getPostedData('langId', FatUtility::VAR_INT, 0);
        $productOptions = FatApp::getPostedData('productOptions');
        $type = FatApp::getPostedData('type', FatUtility::VAR_INT, 0);

        $upcCodeData = [];
        if (0 < $recordId) {
            $upcCodes = ProductRequest::getAttributesById($recordId, 'preq_ean_upc_code');
            if (!empty($upcCodes)) {
                $upcCodes = json_decode($upcCodes, true);
                if (!empty($upcCodes)) {
                    foreach ($upcCodes as $key => $upcCode) {
                        $upcCodeData[$key]['upc_code'] = $upcCode;
                    }
                }
            }
        }

        $optionCombinations = [];
        if ($type == applicationConstants::NO && is_array($productOptions)) {
            $optionCombinations = CommonHelper::combinationOfElementsOfArr($productOptions, 'optionValues');
        }

        $this->set('optionCombinations', $optionCombinations);
        $this->set('upcCodeData', $upcCodeData);
        $this->set('recordId', $recordId);
        $this->set('langId', $langId);
        $this->set('html', $this->_template->render(false, false, 'products/upc-listing.php', true));
        $this->_template->render(false, false, 'json-success.php', true, false);
    }

    public function submitForApproval($recordId)
    {
        $this->checkEditPrivilege();
        $recordId = FatUtility::int($recordId);
        if (1 > $recordId) {
            LibHelper::exitWithError($this->str_invalid_request, false, true);
            FatApp::redirectUser(UrlHelper::generateUrl('SellerRequests'));
        }

        if (!$productRow = ProductRequest::getAttributesById($recordId, array('preq_user_id', 'preq_content'))) {
            LibHelper::exitWithError($this->str_invalid_request, false, true);
            FatApp::redirectUser(UrlHelper::generateUrl('SellerRequests'));
        }

        $prodReqObj = new ProductRequest($recordId);
        $data = array(
            'preq_submitted_for_approval' => applicationConstants::YES,
            'preq_requested_on' => date('Y-m-d H:i:s'),
        );
        $prodReqObj->assignValues($data);
        if (!$prodReqObj->save()) {
            LibHelper::exitWithError($this->str_invalid_request, false, true);
            FatApp::redirectUser(UrlHelper::generateUrl('Seller', 'SellerRequests'));
        }

        $content = (!empty($productRow['preq_content'])) ? json_decode($productRow['preq_content'], true) : array();

        $mailData = array(
            'request_title' => $content['product_identifier'],
            'brand_name' => (!empty($content['brand_name'])) ? $content['brand_name'] : '',
            'product_model' => (!empty($content['product_model'])) ? $content['product_model'] : '',
        );

        $email = new EmailHandler();
        if (!$email->sendNewCustomCatalogNotification($this->siteLangId, $mailData)) {
            LibHelper::exitWithError(Labels::getLabel('ERR_EMAIL_COULD_NOT_BE_SENT', $this->siteLangId), false, true);
            FatApp::redirectUser(UrlHelper::generateUrl('SellerRequests'));
        }

        /* send notification to admin [ */
        $notificationData = array(
            'notification_record_type' => Notification::TYPE_CATALOG,
            'notification_record_id' => $recordId,
            'notification_user_id' => $this->userParentId,
            'notification_label_key' => Notification::NEW_CUSTOM_CATALOG_REQUEST_NOTIFICATION,
            'notification_added_on' => date('Y-m-d H:i:s'),
        );

        if (!Notification::saveNotifications($notificationData)) {
            LibHelper::exitWithError(Labels::getLabel('ERR_NOTIFICATION_COULD_NOT_BE_SENT', $this->siteLangId), false, true);
            FatApp::redirectUser(UrlHelper::generateUrl('SellerRequests'));
        }
        /* ] */
        Message::addMessage(Labels::getLabel('MSG_YOUR_CATALOG_REQUEST_SUBMITTED_FOR_APPROVAL', $this->siteLangId));
        FatApp::redirectUser(UrlHelper::generateUrl('SellerRequests'));
    }

    private function getForm($langId, $productType = 0, $recordId = 0)
    {
        return $this->getCatalogForm($langId, $productType, $recordId, 1);
    }

    private function getSeparateImageOptions($preq_id, $lang_id)
    {
        $imgTypesArr = array(0 => Labels::getLabel('LBL_For_All_Options', $this->siteLangId));

        if ($preq_id) {
            $reqData = ProductRequest::getAttributesById($preq_id, array('preq_content'));
            if (!empty($reqData)) {
                $reqData = json_decode($reqData['preq_content'], true);
            }
            $productOptions = isset($reqData['product_option']) ? $reqData['product_option'] : array();
            $optionsArr = [];
            if (!empty($productOptions)) {
                foreach ($productOptions as $optionId) {
                    $optionValues = Product::getOptionValues($optionId, $lang_id);
                    if (!empty($optionValues)) {
                        $optionsArr[] = ['optionValues' => $optionValues];
                    }
                }
            }
            if (!empty($optionsArr)) {
                $optionCombinations = CommonHelper::combinationOfElementsOfArr($optionsArr, 'optionValues', '_');
                if (!empty($optionCombinations)) {
                    $imgTypesArr = $imgTypesArr + $optionCombinations;
                }
            }
        }
        return $imgTypesArr;
    }

    protected function getCatalogType(): int
    {
        return Product::CATALOG_TYPE_REQUEST;
    }

    private function canAddCustomCatalogProduct()
    {
        if (!$this->isShopActive($this->userParentId)) {
            LibHelper::exitWithError(Labels::getLabel('ERR_YOUR_SHOP_IS_INACTIVE', $this->siteLangId), false, true);
            FatApp::redirectUser(UrlHelper::generateUrl('Seller', 'shop'));
        }

        if (!User::canAddCustomProductAvailableToAllSellers()) {
            LibHelper::exitWithError($this->str_invalid_request, false, true);
            FatApp::redirectUser(UrlHelper::generateUrl('Seller', 'Packages'));
        }

        if (!UserPrivilege::isUserHasValidSubsription($this->userParentId)) {
            LibHelper::exitWithError(Labels::getLabel("ERR_PLEASE_BUY_SUBSCRIPTION", $this->siteLangId), false, true);
            FatApp::redirectUser(UrlHelper::generateUrl('Seller', 'catalog'));
        }
    }
}
