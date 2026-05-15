<?php

trait CatalogProduct
{

    abstract public function checkEditPrivilege(): void;

    /**
     * getForm
     *
     * @param  mixed $langId
     * @param  mixed $productType
     * @param  mixed $recordId  - isRequested = 0 then it is product id  else request id
     * @param  mixed $isRequested - is requested catalog by seller
     * @return object
     */

    private function getCatalogForm($langId, $productType = 0, $recordId = 0, $isRequested = 0): object
    {
        $frm = new Form('frmProduct');
        $productTypeArr = Product::getProductTypes($langId);
        $productType = $productType == 0 ? array_key_first($productTypeArr) : $productType;

        $fld = $frm->addSelectBox(Labels::getLabel('FRM_LANGUAGE', $langId), 'lang_id', Language::getDropDownList(), $langId, [], '');
        $fld->requirements()->setRequired();
        if (1 > $recordId) {
            $fld->addFieldTagAttribute('disabled', 'disabled');
        }

        //$fld = $frm->addRadioButtons(Labels::getLabel('FRM_PRODUCT_TYPE', $langId), 'product_type', $productTypeArr, $productType);
        $fld = $frm->addSelectBox(Labels::getLabel('FRM_PRODUCT_TYPE', $langId), 'product_type', $productTypeArr, $productType, [], '');
        $fld->requirements()->setRequired();

        if (0 == $isRequested) {
            $fld = $frm->addSelectBox(Labels::getLabel('FRM_USER', $langId), 'product_seller_id', [], '', [], Labels::getLabel('FRM_ADMIN', $langId));
        }
        $frm->addRequiredField(Labels::getLabel('FRM_PRODUCT_IDENTIFIER', $langId), 'product_identifier');
        $frm->addRequiredField(Labels::getLabel('FRM_PRODUCT_NAME', $langId), 'product_name');
        if (0 < FatApp::getConfig('CONF_WITHOUT_PROD_VARIANTS', FatUtility::VAR_INT, 0)) {
            $frm->addTextBox(Labels::getLabel('FRM_URL_KEYWORD', $this->siteLangId), 'selprod_url_keyword');
        }
        $fld = $frm->addSelectBox(Labels::getLabel('FRM_BRAND', $langId), 'product_brand_id', []);
        if (FatApp::getConfig("CONF_PRODUCT_BRAND_MANDATORY", FatUtility::VAR_INT, 1)) {
            $fld->requirements()->setRequired();
        }
        $fld = $frm->addSelectBox(Labels::getLabel('FRM_COMPARTIBLE_BRAND', $langId), 'product_cbrand_id', []);
        if (FatApp::getConfig("CONF_PRODUCT_COMPATIBLE_BRAND_MANDATORY", FatUtility::VAR_INT, 1)) {
            $fld->requirements()->setRequired();
        }        
        $fld = $frm->addSelectBox(Labels::getLabel('FRM_CATEGORY', $langId), 'ptc_prodcat_id', []);
        $fld->requirements()->setRequired();

        if ($productType != Product::PRODUCT_TYPE_SERVICE) {
            $fld = $frm->addTextBox(Labels::getLabel('FRM_MODEL', $langId), 'product_model');
            if (FatApp::getConfig("CONF_PRODUCT_MODEL_MANDATORY", FatUtility::VAR_INT, 1)) {
                $fld->requirements()->setRequired();
            }
        }

        if (0 < FatApp::getConfig('CONF_WITHOUT_PROD_VARIANTS', FatUtility::VAR_INT, 0)) {
            $costPrice = $frm->addFloatField(Labels::getLabel('FRM_COST_PRICE', $this->siteLangId) . ' [' . CommonHelper::getCurrencySymbol(true) . ']', 'selprod_cost');
            $costPrice->requirements()->setPositive();
        }


        if (0 < FatApp::getConfig('CONF_WITHOUT_PROD_VARIANTS', FatUtility::VAR_INT, 0)) {
            $lbl = Labels::getLabel('FRM_SELLING_PRICE', $langId);
        } else {
            $lbl = Labels::getLabel('FRM_MINIMUM_SELLING_PRICE', $langId);
        }
        $fld = $frm->addFloatField($lbl . ' [' . CommonHelper::getCurrencySymbol(true) . ']', 'product_min_selling_price', '');
        $fld->requirements()->setRange('0.01', '99999999.99');

        if (0 < FatApp::getConfig('CONF_WITHOUT_PROD_VARIANTS', FatUtility::VAR_INT, 0)) {
            if ($productType != Product::PRODUCT_TYPE_SERVICE) {
                $fld = $frm->addIntegerField(Labels::getLabel('FRM_STOCK', $this->siteLangId), 'selprod_stock');
                $fld->requirements()->setPositive();
                $fld_sku = $frm->addTextBox(Labels::getLabel('FRM_PRODUCT_SKU', $this->siteLangId), 'selprod_sku');
                if (FatApp::getConfig("CONF_PRODUCT_SKU_MANDATORY", FatUtility::VAR_INT, 1)) {
                    $fld_sku->requirements()->setRequired();
                }

                $fld = $frm->addIntegerField(Labels::getLabel('FRM_MIN_PURCHASE_QUANTITY', $this->siteLangId), 'selprod_min_order_qty');
                $fld->requirements()->setPositive();
            } else {
                $frm->addHiddenField('', 'selprod_stock', 1);
            }

            $frm->addDateField(Labels::getLabel('FRM_AVAILABLE_FROM', $this->siteLangId), 'selprod_available_from', '', array('readonly' => 'readonly', 'class' => 'field--calender datePickerJs'))->requirements()->setRequired();

            if ($productType == Product::PRODUCT_TYPE_DIGITAL) {
                $fld = $frm->addIntegerField(Labels::getLabel('FRM_MAX_DOWNLOAD_TIMES', $this->siteLangId), 'selprod_max_download_times');
                $fld->htmlAfterField = '<small class="text--small">' . Labels::getLabel('FRM_-1_for_unlimited', $this->siteLangId) . '</small>';

                $fld1 = $frm->addIntegerField(Labels::getLabel('FRM_DOWNLOAD_VALIDITY_(days)', $this->siteLangId), 'selprod_download_validity_in_days');
                $fld1->htmlAfterField = '<small class="text--small">' . Labels::getLabel('FRM_-1_for_unlimited', $this->siteLangId) . '</small>';
                $frm->addHiddenField('', 'selprod_condition', Product::CONDITION_NEW);
            } elseif ($productType == Product::PRODUCT_TYPE_SERVICE) {
                $frm->addHiddenField('', 'selprod_condition', Product::CONDITION_NEW);
            } else {
                $fld = $frm->addSelectBox(Labels::getLabel('FRM_PRODUCT_CONDITION', $this->siteLangId), 'selprod_condition', Product::getConditionArr($this->siteLangId), '', array(), Labels::getLabel('FRM_SELECT_CONDITION', $this->siteLangId));
                $fld->requirements()->setRequired();
            }

            if ($productType != Product::PRODUCT_TYPE_SERVICE) {
                if (false === Plugin::isActive('EasyEcom')) {
                    $frm->addCheckBox(Labels::getLabel('FRM_SYSTEM_SHOULD_MAINTAIN_STOCK_LEVELS', $this->siteLangId), 'selprod_subtract_stock', applicationConstants::YES, array(), false, 0);
                    $fld = $frm->addCheckBox(Labels::getLabel('FRM_SYSTEM_SHOULD_TRACK_PRODUCT_INVENTORY', $this->siteLangId), 'selprod_track_inventory', Product::INVENTORY_TRACK, ['class' => 'fieldsVisibilityJs'], false, 0);
                }
                $stockLevelReqFld = new FormFieldRequirement('selprod_threshold_stock_level', Labels::getLabel('FRM_ALERT_STOCK_LEVEL', $this->siteLangId));
                $stockLevelReqFld->setRequired(true);

                $stockLevelUnReqFld = new FormFieldRequirement('selprod_threshold_stock_level', Labels::getLabel('FRM_ALERT_STOCK_LEVEL', $this->siteLangId));
                $stockLevelUnReqFld->setRequired(false);

                $fld->requirements()->addOnChangerequirementUpdate(1, 'eq', 'selprod_threshold_stock_level', $stockLevelReqFld);
                $fld->requirements()->addOnChangerequirementUpdate(1, 'ne', 'selprod_threshold_stock_level', $stockLevelUnReqFld);

                $fld = $frm->addTextBox(Labels::getLabel('FRM_ALERT_STOCK_LEVEL', $this->siteLangId), 'selprod_threshold_stock_level');
                $fld->requirements()->setInt();
            } else {
                $frm->addHiddenField(Labels::getLabel('FRM_MINIMUM_PURCHASE_QUANTITY', $this->siteLangId), 'selprod_min_order_qty', 1);
            }
            $useShopPolicy = $frm->addCheckBox(Labels::getLabel('FRM_USE_SHOP_RETURN_AND_CANCELLATION_AGE_POLICY', $this->siteLangId), 'use_shop_policy', 1, ['id' => 'use_shop_policy'], false, 0);

            if ($productType == Product::PRODUCT_TYPE_PHYSICAL) {
                $fld = $frm->addIntegerField(Labels::getLabel('FRM_PRODUCT_ORDER_RETURN_PERIOD_(Days)', $this->siteLangId), 'selprod_return_age');
                $fld->htmlAfterField = '<span class="form-text text-muted">' . Labels::getLabel('FRM_WARRANTY_IN_DAYS', $this->siteLangId) . ' </span>';

                $orderReturnAgeReqFld = new FormFieldRequirement('selprod_return_age', Labels::getLabel('FRM_PRODUCT_ORDER_RETURN_PERIOD_(Days)', $this->siteLangId));
                $orderReturnAgeReqFld->setRequired(true);
                $orderReturnAgeReqFld->setPositive();


                $orderReturnAgeUnReqFld = new FormFieldRequirement('selprod_return_age', Labels::getLabel('FRM_PRODUCT_ORDER_RETURN_PERIOD_(Days)', $this->siteLangId));
                $orderReturnAgeUnReqFld->setRequired(false);
                $orderReturnAgeUnReqFld->setPositive();
            } else {
                $frm->addHiddenField('', 'selprod_return_age', 0);
            }
            $fld = $frm->addIntegerField(Labels::getLabel('FRM_PRODUCT_ORDER_CANCELLATION_PERIOD_(Days)', $this->siteLangId), 'selprod_cancellation_age');
            $fld->htmlAfterField = '<span class="form-text text-muted">' . Labels::getLabel('FRM_PERIOD_IN_DAYS', $this->siteLangId) . ' </span>';

            $orderCancellationAgeReqFld = new FormFieldRequirement('selprod_cancellation_age', Labels::getLabel('FRM_PRODUCT_ORDER_CANCELLATION_PERIOD_(Days)', $this->siteLangId));
            $orderCancellationAgeReqFld->setRequired(true);
            $orderCancellationAgeReqFld->setPositive();

            $orderCancellationAgeUnReqFld = new FormFieldRequirement('selprod_cancellation_age', Labels::getLabel('FRM_PRODUCT_ORDER_CANCELLATION_PERIOD_(Days)', $this->siteLangId));
            $orderCancellationAgeUnReqFld->setRequired(false);
            $orderCancellationAgeUnReqFld->setPositive();

            if ($productType == Product::PRODUCT_TYPE_PHYSICAL) {
                $useShopPolicy->requirements()->addOnChangerequirementUpdate(Shop::USE_SHOP_POLICY, 'eq', 'selprod_return_age', $orderReturnAgeUnReqFld);
                $useShopPolicy->requirements()->addOnChangerequirementUpdate(Shop::USE_SHOP_POLICY, 'ne', 'selprod_return_age', $orderReturnAgeReqFld);
            }

            $useShopPolicy->requirements()->addOnChangerequirementUpdate(Shop::USE_SHOP_POLICY, 'eq', 'selprod_cancellation_age', $orderCancellationAgeUnReqFld);
            $useShopPolicy->requirements()->addOnChangerequirementUpdate(Shop::USE_SHOP_POLICY, 'ne', 'selprod_cancellation_age', $orderCancellationAgeReqFld);


            $frm->addCheckBox(Labels::getLabel('FRM_PUBLISH_INVENTORY', $this->siteLangId), 'selprod_active', applicationConstants::ACTIVE, [], false, applicationConstants::INACTIVE);

            if (0 < FatApp::getConfig('CONF_RFQ_MODULE', FatUtility::VAR_INT, 0) && 1 > FatApp::getConfig('CONF_HIDE_PRICES', FatUtility::VAR_INT, 0)) {
                if (isset($this->admin_id)) {
                    $shopRfqEnabled = 1;
                    if (0 < $recordId) {
                        $userParentId = Product::getAttributesById($recordId, 'product_seller_id');
                        if (0 < $userParentId) {
                            $shopRfqEnabled = Shop::getAttributesByUserId($userParentId, 'shop_rfq_enabled', false);
                        }
                    }
                } else {
                    $shopRfqEnabled = Shop::getAttributesByUserId($this->userParentId, 'shop_rfq_enabled', false);
                }

                if (0 < $shopRfqEnabled) {
                    $cartTypeFld = $frm->addSelectBox(Labels::getLabel('FRM_CART_TYPE', $this->siteLangId), 'selprod_cart_type', SellerProduct::getCartType(), SellerProduct::CART_TYPE_BOTH, array(), '');
                    $cartTypeFld->requirements()->setRequired();
                    $frm->addCheckBox(Labels::getLabel("FRM_HIDE_PRICE", $this->siteLangId), 'selprod_hide_price', 1, array(), false, 0);
                }
            }
            $frm->addTextArea(Labels::getLabel('FRM_ANY_EXTRA_COMMENT_FOR_BUYER', $this->siteLangId), 'selprod_comments');
        }

        if ($productType != Product::PRODUCT_TYPE_DIGITAL) {
            $fld = $frm->addRequiredField(Labels::getLabel('FRM_PRODUCT_WARRANTY', $langId), 'product_warranty');
            $fld->requirements()->setInt();
            $fld->requirements()->setPositive();
            $frm->addHiddenField('', 'product_warranty_unit');
        }
        $frm->addTextArea(Labels::getLabel('FRM_SHORT_DESCRIPTION', $langId), 'product_short_description');
        $frm->addTextArea(Labels::getLabel('FRM_PRODUCT_FEATURES', $langId), 'product_features');
        $frm->addTextArea(Labels::getLabel('FRM_PRODUCT_KEY_FEATURES', $langId), 'product_key_features');
        $frm->addHtmlEditor(Labels::getLabel('FRM_DESCRIPTION', $langId), 'product_description');
        $frm->addTextBox(Labels::getLabel('FRM_YOUTUBE_VIDEO_URL', $langId), 'product_youtube_video');
        $frm->addTextBox(Labels::getLabel('FRM_HSN_CODE', $langId), 'product_hsn_code');
        $frm->addCheckBox(Labels::getLabel('FRM_MARK_AS_FEATURED', $langId), 'product_featured', 1, array(), false, 0);
        $frm->addCheckBox(Labels::getLabel("FRM_ACTIVE", $langId), 'product_active', applicationConstants::YES, array(), true, 0);

        $frm->addTextBox(Labels::getLabel('FRM_PRODUCT_TAGS', $langId), 'product_tags');
        $fld = $frm->addSelectBox(Labels::getLabel('FRM_TAX_CATEGORY', $langId), 'ptt_taxcat_id', []);
        $fld->requirements()->setRequired();

        if (0 == $isRequested && $productType != Product::PRODUCT_TYPE_SERVICE) {
            $frm->addSelectBox(Labels::getLabel('FRM_COUNTRY_OF_ORIGIN', $langId), 'ps_from_country_id', []);
        }
        if ($productType == Product::PRODUCT_TYPE_DIGITAL) {
            if (!FatApp::getConfig('CONF_WITHOUT_PROD_VARIANTS', FatUtility::VAR_INT, 0)) {
                $fld = $frm->addRadioButtons(Labels::getLabel('FRM_PRODUCT_DOWNLOAD_ATTACHEMENTS_AT_INVENTORY_LEVEL', $this->siteLangId), 'product_attachements_with_inventory', applicationConstants::getYesNoArr($langId), applicationConstants::NO);
                $fld->requirements()->setRequired();
            } else {
                $frm->addHiddenField('', 'product_attachements_with_inventory', 0);
            }
        } else {
            if ($productType != Product::PRODUCT_TYPE_SERVICE) {
                $fld = $frm->addCheckBox(Labels::getLabel('FRM_AVAILABLE_FOR_CASH_ON_DELIVERY_(COD)', $langId), 'product_cod_enabled', 1, array(), false, 0);
                $fulFillmentArr = Shipping::getFulFillmentArr($langId, FatApp::getConfig('CONF_FULFILLMENT_TYPE', FatUtility::VAR_INT, -1));
                $fld = $frm->addSelectBox(Labels::getLabel('FRM_FULFILLMENT_METHOD', $langId), 'product_fulfillment_type', $fulFillmentArr, applicationConstants::NO, [], Labels::getLabel('FRM_SELECT', $langId));
                $fld->requirements()->setRequired();

                if (FatApp::getConfig("CONF_PRODUCT_DIMENSIONS_ENABLE", FatUtility::VAR_INT, 1)) {
                    $shipPackArr = ShippingPackage::getNames();
                    $frm->addSelectBox(Labels::getLabel('FRM_SHIPPING_PACKAGE', $langId), 'product_ship_package', $shipPackArr, '', [], Labels::getLabel('FRM_SELECT', $langId))->requirements()->setRequired();
                }

                if (FatApp::getConfig("CONF_PRODUCT_WEIGHT_ENABLE", FatUtility::VAR_INT, 1)) {
                    $weightUnitsArr = applicationConstants::getWeightUnitsArr($langId);
                    $frm->addSelectBox(Labels::getLabel('FRM_WEIGHT_UNIT', $langId), 'product_weight_unit', $weightUnitsArr, '', [], Labels::getLabel('FRM_SELECT', $langId))->requirements()->setRequired();

                    $weightFld = $frm->addFloatField(Labels::getLabel('FRM_WEIGHT', $langId), 'product_weight', '0.00');
                    $weightFld->requirements()->setRequired(true);
                    $weightFld->requirements()->setFloatPositive();
                    $weightFld->requirements()->setRange('0.01', '9999999999');
                }

                if (0 == $isRequested) {
                    $frm->addSelectBox(Labels::getLabel('FRM_SHIPPING_PROFILE', $langId), 'shipping_profile', [], '', [], '');
                }
            }
        }

        if ($isRequested == 0) {
            $frm->addCheckBox(Labels::getLabel('FRM_APPROVAL_STATUS', $langId), 'product_approved', 1, array(), false, 0);
        }

        if ($isRequested == 0) {
            $languageArr = Language::getDropDownList();
            $translatorSubscriptionKey = FatApp::getConfig('CONF_TRANSLATOR_SUBSCRIPTION_KEY', FatUtility::VAR_STRING, '');
            if (!empty($translatorSubscriptionKey) && $langId == CommonHelper::getDefaultFormLangId() && 1 < count($languageArr)) {
                $frm->addCheckBox(Labels::getLabel('FRM_UPDATE_OTHER_LANGUAGES_DATA', $this->siteLangId), 'auto_update_other_langs_data', 1, array(), false, 0);
            }
        }

        if (FatApp::getConfig('CONF_WITHOUT_PROD_VARIANTS', FatUtility::VAR_INT, 0)) {
            $frm->addHiddenField('', 'upc_type', applicationConstants::YES);
        } else {
            $fld = $frm->addRadioButtons('', 'upc_type', applicationConstants::getYesNoArr($langId), applicationConstants::YES);
            $fld->requirements()->setRequired();
        }
        $frm->addHiddenField('', 'product_upcs');
        $frm->addHiddenField('', 'options');
        $frm->addHiddenField('', 'optionValues');
        $frm->addHiddenField('', 'specifications');

        if (1 > $recordId) {
            $fld = $frm->addHiddenField('', 'temp_product_id');
            $fld->requirements()->setRequired();
        }

        $frm->addHiddenField('', 'record_id', 0);
        $frm->addHiddenField('', 'selprod_id');
        $frm->addSubmitButton('', 'btn_submit', Labels::getLabel('BTN_SAVE_AND_NEXT', $langId));
        return $frm;
    }

    public function imageForm(int $recordId = 0, $tempProductId = 0)
    {
        $frm = $this->getImageFrm();
        if (1 > $recordId) {
            $frm->fill(['file_type' => AttachedFile::FILETYPE_PRODUCT_IMAGE_TEMP, 'record_id' => $tempProductId]);
        } else {
            $frm->fill(['file_type' => AttachedFile::FILETYPE_PRODUCT_IMAGE, 'record_id' => $recordId]);
        }

        $this->set('frm', $frm);
        $this->set('html', $this->_template->render(false, false, NULL, true));
        $this->_template->render(false, false, 'json-success.php', true, false);
    }

    public function customProductImageForm(int $recordId = 0, $tempProductId = 0)
    {
        $frm = $this->getImageFrm();
        if (1 > $recordId) {
            $frm->fill(['file_type' => AttachedFile::FILETYPE_CUSTOM_PRODUCT_IMAGE_TEMP, 'record_id' => $tempProductId]);
        } else {
            $frm->fill(['file_type' => AttachedFile::FILETYPE_CUSTOM_PRODUCT_IMAGE, 'record_id' => $recordId]);
        }

        $this->set('frm', $frm);
        $this->set('html', $this->_template->render(false, false, 'products/image-form.php', true));
        $this->_template->render(false, false, 'json-success.php', true, false);
    }


    private function getImageFrm()
    {
        $frm = new Form('imageFrm');
        if (!FatApp::getConfig('CONF_WITHOUT_PROD_VARIANTS', FatUtility::VAR_INT, 0)) {
            $frm->addSelectBox(Labels::getLabel('FRM_IMAGE_FILE_TYPE', $this->siteLangId), 'option_id', [], '', array(), Labels::getLabel('FRM_FOR_ALL_OPTIONS', $this->siteLangId));
        } else {
            $frm->addHiddenField('', 'option_id', 0);
        }
        $languagesAssocArr = Language::getAllNames();
        if (count($languagesAssocArr) > 1) {
            $frm->addSelectBox(Labels::getLabel('FRM_LANGUAGE', $this->siteLangId), 'lang_id', array(0 => Labels::getLabel('LBL_All_Languages', $this->siteLangId)) + $languagesAssocArr, '', array(), '');
        } else {
            $langId = array_key_first($languagesAssocArr);
            $frm->addHiddenField('', 'lang_id', $langId);
        }
        $frm->addFileUpload(Labels::getLabel('FRM_UPLOAD', $this->siteLangId), 'prod_image');
        $frm->addHtml('', 'images', '');
        $imgDimension = ImageDimension::getProductImageData(ImageDimension::VIEW_ORIGINAL);
        $frm->addHiddenField('', 'min_width', $imgDimension[ImageDimension::WIDTH]);
        $frm->addHiddenField('', 'min_height', $imgDimension[ImageDimension::HEIGHT]);
        $frm->addHiddenField('', 'record_id');
        $frm->addHiddenField('', 'file_type');

        return $frm;
    }

    private function validateGetForm(&$post)
    {
        $langId = $post['lang_id'];
        $recordId = $post['record_id'];
        if (1 > $recordId) {
            if (!isset($post['temp_product_id']) || 1 > $post['temp_product_id']) {
                LibHelper::exitWithError($this->str_invalid_request, true);
            }
        }

        if (isset($post['options'])) {
            $post['options'] = is_array($post['options']) ? array_filter($post['options']) : [];
            if (count($post['options'])) {
                if (!isset($post['optionValues']) || empty($post['optionValues']) ||  count($post['options']) != count($post['optionValues'])) {
                    LibHelper::exitWithError(Labels::getLabel('ERR_OPTION_VALUES_IS_REQUIRED', $langId), true);
                }
                $srch = Option::getSearchObject(0);
                $srch->addMultipleFields(['option_id', 'option_is_separate_images']);
                $srch->doNotLimitRecords();
                $srch->doNotCalculateRecords();
                $srch->addCondition(Option::tblFld('id'), 'IN', $post['options']);
                $records =  FatApp::getDb()->fetchAll($srch->getResultSet());

                if (count($records) != count($post['options'])) {
                    LibHelper::exitWithError(Labels::getLabel('ERR_INVALID_OPTION_ID', $langId), true);
                }

                $opImageCount = 0;
                foreach ($records as $records) {
                    if ($records['option_is_separate_images'] == 1) {
                        $opImageCount++;
                    }
                    if (1 < $opImageCount) {
                        LibHelper::exitWithError(Labels::getLabel('ERR_YOU_HAVE_ALREADY_ADDED_OPTION_HAVING_SEPARATE_IMAGE', $langId), true);
                        break;
                    }
                }

                if (0 < $recordId) {
                    $srch = new SearchBase(Product::DB_PRODUCT_TO_OPTION);
                    $srch->doNotLimitRecords();
                    $srch->doNotCalculateRecords();
                    $srch->addCondition(Product::DB_PRODUCT_TO_OPTION_PREFIX . 'product_id', '=', $recordId);
                    $srch->addFld('prodoption_option_id');
                    $oldOptions = FatApp::getDb()->fetchAll($srch->getResultSet());
                    if ($oldOptions) {
                        $oldOptions = array_column($records, 'prodoption_option_id');
                        $oldDeletedOptions = array_diff(array_column($records, 'prodoption_option_id'), $post['options']);
                        if ($oldDeletedOptions) {
                            if (SellerProduct::isOptionLinked($oldDeletedOptions, $recordId)) {
                                LibHelper::exitWithError(Labels::getLabel('ERR_OPTION_IS_LINKED_WITH_SELLER_INVENTORY', $this->siteLangId), true);
                            }
                        }
                    }
                }

                foreach ($post['options'] as $index => $optionId) {
                    if (!isset($post['optionValues'][$index])) {
                        LibHelper::exitWithError(Labels::getLabel('ERR_INVALID_OPTION_VALUES_ID', $langId), true);
                    }
                    if (empty($post['optionValues'][$index])) {
                        $optionData = Option::getAttributesByLangId(CommonHelper::getDefaultFormLangId(), $optionId, ['IFNULL(option_name,option_identifier) as option_name'], applicationConstants::JOIN_RIGHT);
                        $optionName = $optionData['option_name'];
                        LibHelper::exitWithError(CommonHelper::replaceStringData(Labels::getLabel('ERR_OPTION_VALUES_IS_REQUIRED_FOR_{OPTION-NAME}', $langId), ['{OPTION-NAME}' => $optionName]), true);
                    }
                    $opValuesArr = array_column(json_decode($post['optionValues'][$index]), 'id');
                    $srch = OptionValue::getSearchObject(0, false);
                    $srch->doNotLimitRecords();
                    $srch->addCondition(OptionValue::tblFld('option_id'), '=', $optionId);
                    $srch->addCondition(OptionValue::tblFld('id'), 'IN', $opValuesArr);
                    $srch->getResultSet();
                    if ($srch->recordCount() != count($opValuesArr)) {
                        LibHelper::exitWithError(Labels::getLabel('ERR_INVALID_OPTION_VALUES_ID', $langId), true);
                    }
                }
            }
        }
        $post['product_upcs'] = $post['product_upcs'] ?? [];
        if (isset($post['product_upcs']) && !empty($post['product_upcs'])) {
            foreach ($post['product_upcs'] as $optionsIds => $upcCode) {
                if (empty($upcCode)) {
                    unset($post['product_upcs'][$optionsIds]);
                    continue;
                }
                $row = UpcCode::getUpcDataByCode($upcCode);

                if ($row && $row['upc_product_id'] != $recordId) {
                    LibHelper::exitWithError(Labels::getLabel('ERR_THIS_UPC/EAN_CODE_ALREADY_ASSIGNED_TO_ANOTHER_PRODUCT', $langId), true);
                }
            }
        }
    }

    private function validateImageSubscriptionLimit($recordId, $productOptionId, $langId, $fileType, $sellerId)
    {
        if (FatApp::getConfig('CONF_ENABLE_SELLER_SUBSCRIPTION_MODULE', FatUtility::VAR_INT, 0) && 0 < $sellerId) {
            $currentPlanData = OrderSubscription::getUserCurrentActivePlanDetails($this->siteLangId, $sellerId, array('ossubs_images_allowed'));
            $allowed_images = $currentPlanData['ossubs_images_allowed'];

            if ($fileType == AttachedFile::FILETYPE_PRODUCT_IMAGE_TEMP) {
                $srch = new SearchBase(AttachedFileTemp::DB_TBL);
            } else {
                $srch = new SearchBase(AttachedFile::DB_TBL);
                $optionValues = Product::getSeparateImageOptions($recordId, $this->siteLangId);
                $srch->addCondition('afile_record_subid', 'IN', array_keys($optionValues));
            }

            $srch->doNotCalculateRecords();
            $srch->addCondition('afile_type', '=', $fileType);
            $srch->addCondition('afile_record_id', '=', $recordId);
            if ($langId > 0) {
                $srch->addCondition('afile_lang_id', 'IN', [$langId, 0]);
            } else {
                $srch->addCondition('afile_lang_id', '=', 0);
            }
            if (0 < $productOptionId) {
                $srch->addCondition('afile_record_subid', 'IN', [$productOptionId, 0]);
                $images = FatApp::getDb()->fetchAll($srch->getResultSet());
                $allReadyAddedCount = count($images);
            } else {
                $srch->addGroupBy('afile_record_subid');
                $srch->addOrder('image_count', 'desc');
                $srch->addMultipleFields(['count(afile_id) as image_count', 'afile_record_subid']);
                $images = FatApp::getDb()->fetchAll($srch->getResultSet(), 'afile_record_subid');
                $allReadyAddedCount = 0;
                if ($images) {
                    if (isset($images[0])) {
                        $allReadyAddedCount += $images[0]['image_count'];
                        unset($images[0]);
                    }
                    if (count($images)) {
                        /* adding all option  + max count of other option */
                        $allReadyAddedCount += current($images)['image_count'];
                    }
                }
            }

            if ($allowed_images > 0 && $allReadyAddedCount >= $allowed_images) {
                FatUtility::dieJsonError(Labels::getLabel("ERE_CANT_UPLOAD_MORE_THAN_ALLOWED_IMAGES", $this->siteLangId));
            }
        }
    }
}
