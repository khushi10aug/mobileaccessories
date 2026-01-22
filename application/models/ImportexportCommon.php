<?php

class ImportexportCommon extends FatModel
{
    protected $db;
    public $CSVfileName;
    public $pluginId = 0;

    public const IMPORT_ERROR_LOG_PATH = CONF_UPLOADS_PATH . 'import-error-log/';

    public const VALIDATE_POSITIVE_INT = 'positiveInt';
    public const VALIDATE_INT = 'int';
    public const VALIDATE_FLOAT = 'float';
    public const VALIDATE_NOT_NULL = 'notNull';

    public function __construct($id = 0)
    {
        //$this->defaultLangId = FatApp::getConfig('CONF_DEFAULT_SITE_LANG',FatUtility::VAR_INT,CommonHelper::getLangId());
        $this->defaultLangId = CommonHelper::getLangId();
        $this->db = FatApp::getDb();
        $this->settings = $this->getSettingsArr();
    }

    public function CSVFileName($fileName = '', $langId = 0)
    {
        $langId = FatUtility::int($langId);
        if (0 >= $langId) {
            $langId = CommonHelper::getLangId();
        }

        $langData = Language::getAttributesById($langId, array('language_code'));

        $fileName = empty($fileName) ? 'CSV_FILE' : str_replace(' ', '_', $fileName);

        return $fileName . '_' . $langData['language_code'] . '_' . date("d-M-Y-His") . mt_rand() . '.csv';
    }

    public function openCSVfileToWrite($fileName, $langId = 0, $errorLog = false, $headingsArr = array())
    {
        if (empty($fileName)) {
            return false;
        }
        $this->CSVfileName = $this->CSVFileName($fileName, $langId);

        if (true === $errorLog) {
            if (!file_exists(self::IMPORT_ERROR_LOG_PATH)) {
                mkdir(self::IMPORT_ERROR_LOG_PATH, 0777);
            }
            $file = self::IMPORT_ERROR_LOG_PATH . $this->CSVfileName;
            $headingsArr = array(
                Labels::getLabel('LBL_ROW', $langId),
                Labels::getLabel('LBL_COLUMN', $langId),
                Labels::getLabel('LBL_DESCRIPTION', $langId)
            );
            $handle = fopen($file, "w");
        }

        if (false === $errorLog) {
            $handle = fopen('php://memory', 'w');
        }

        $langId = FatUtility::int($langId);
        if (0 >= $langId) {
            $langId = CommonHelper::getLangId();
        }

        CommonHelper::writeToCSVFile($handle, $headingsArr, false, true);
        return $handle;
    }

    public function getCsvFileName()
    {
        return $this->CSVfileName;
    }

    public static function deleteErrorLogFiles($hoursBefore = '4')
    {
        if (empty($hoursBefore)) {
            return false;
        }
        $importErrorLogFilesDir = ImportexportCommon::IMPORT_ERROR_LOG_PATH;
        $errorLogFiles = array_diff(scandir($importErrorLogFilesDir), array('..', '.'));
        foreach ($errorLogFiles as $fileName) {
            $file = $importErrorLogFilesDir . $fileName;
            $modifiedOn = filemtime($file);
            if ($modifiedOn <= strtotime('-' . $hoursBefore . ' hour')) {
                unlink($file);
            }
        }
        return true;
    }

    public function isValidColumns($headingsArr, $coloumArr)
    {
        $arr = array_diff($headingsArr, $coloumArr);
        if (count($arr) || count($headingsArr) != count($coloumArr)) {
            return false;
        }
        return true;
    }

    public static function validateFields($requiredFields, $columnIndex, $columnTitle, $columnValue, $langId)
    {
        $errMsg = false;

        foreach ($requiredFields as $type => $fieldsArr) {
            if (!in_array($columnIndex, $fieldsArr)) {
                continue;
            }

            switch ($type) {
                case static::VALIDATE_POSITIVE_INT:
                    $errMsg = (0 >= FatUtility::int($columnValue)) ? Labels::getLabel("ERR_{column-name}_SHOULD_BE_GREATER_THAN_0.", $langId) : false;
                    break;
                case static::VALIDATE_NOT_NULL:
                    $errMsg = ('' == $columnValue) ? Labels::getLabel("ERR_{column-name}_IS_MANDATORY.", $langId) : false;
                    break;
                case static::VALIDATE_INT:
                    $errMsg = (0 > FatUtility::int($columnValue)) ? Labels::getLabel("ERR_{column-name}_SHOULD_BE_GREATER_THAN_EQUAL_TO_0.", $langId) : false;
                    break;
                case static::VALIDATE_FLOAT:
                    $errMsg = (0 > FatUtility::float($columnValue)) ? Labels::getLabel("ERR_{column-name}_SHOULD_BE_GREATER_THAN_0.", $langId) : false;
                    break;
            }

            $errMsg = (false !== $errMsg) ? mb_strtolower($errMsg)  : $errMsg;
            return (false !== $errMsg) ? mb_convert_case(str_replace('{column-name}', $columnTitle, $errMsg), MB_CASE_TITLE, "UTF-8") : $errMsg;
        }
        return $errMsg;
    }

    public function isUploadedFileValidMimes($files)
    {
        $csvValidMimes = array(
            'text/x-comma-separated-values',
            'text/comma-separated-values',
            'application/octet-stream',
            'application/vnd.ms-excel',
            'application/x-csv',
            'text/x-csv',
            'text/csv',
            'application/csv',
            'application/excel',
            'application/vnd.msexcel',
            'text/plain'
        );
        return (isset($files['name'])
            && $files['error'] == 0
            && in_array(trim($files['type']), $csvValidMimes)
            && $files['size'] > 0);
    }

    public function isDefaultSheetData($langId)
    {
        if ($langId == $this->defaultLangId) {
            return true;
        }
        return false;
    }

    public function displayDate($date)
    {
        return $this->displayDateTime($date, false);
    }

    public function displayDateTime($dt, $time = true)
    {
        try {
            if (trim($dt) == '' || $dt == '0000-00-00' || $dt == '0000-00-00 00:00:00') {
                return;
            }
            if ($time == false) {
                return date("m/d/Y", strtotime($dt));
            }
            return date('m/d/Y H:i:s', strtotime($dt));
        } catch (Exception $e) {
            return false;
        }
    }

    public function getDateTime($dt, $time = true)
    {
        $emptyDateArr = array('0000-00-00', '0000-00-00 00:00:00', '0000/00/00', '0000/00/00 00:00:00', '00/00/0000', '00/00/0000 00:00:00', '00/00/00', '00/00/00 00:00:00');
        if (trim($dt) == '' || in_array($dt, $emptyDateArr)) {
            return '0000-00-00';
        }

        try {
            $date = new DateTime($dt);
            $timeStamp = $date->getTimestamp();
            if ($time == false) {
                return date("Y-m-d", $timeStamp);
            }
            return date("Y-m-d H:i:s", $timeStamp);
        } catch (Exception $e) {
            return '0000-00-00';
        }
    }

    public function getCategoryColoumArr($langId, $userId = 0)
    {
        $arr = array();

        if ($this->settings['CONF_USE_CATEGORY_ID']) {
            $arr['prodcat_id'] = Labels::getLabel('LBL_CATEGORY_ID', $langId);
            if ($this->isDefaultSheetData($langId)) {
                $arr['prodcat_identifier'] = Labels::getLabel('LBL_CATEGORY_IDENTIFIER', $langId);
            }
        } else {
            $arr['prodcat_identifier'] = Labels::getLabel('LBL_CATEGORY_IDENTIFIER', $langId);
        }

        if ($this->isDefaultSheetData($langId)) {
            if ($this->settings['CONF_USE_CATEGORY_ID']) {
                $arr['prodcat_parent'] = Labels::getLabel('LBL_PARENT_ID', $langId);
            } else {
                $arr['prodcat_parent_identifier'] = Labels::getLabel('LBL_PARENT_IDENTIFIER', $langId);
            }
        }

        $arr['prodcat_name'] = Labels::getLabel('LBL_NAME', $langId);
        if (!$userId) {
            /*$arr['prodcat_description'] = Labels::getLabel('LBL_Description', $langId);*/
            /* $arr[] = Labels::getLabel('LBL_Content_block', $langId); */

            if ($this->isDefaultSheetData($langId)) {
                $arr['urlrewrite_custom'] = Labels::getLabel('LBL_SEO_FRIENDLY_URL', $langId);
                /*$arr['prodcat_featured'] = Labels::getLabel('LBL_Featured', $langId);*/
                $arr['prodcat_active'] = Labels::getLabel('LBL_ACTIVE', $langId);
                /* $arr['prodcat_status'] = Labels::getLabel('LBL_STATUS', $langId); */  // Not Required. It is being used in category request from seller.
                $arr['prodcat_display_order'] = Labels::getLabel('LBL_DISPLAY_ORDER', $langId);
                $arr['prodcat_deleted'] = Labels::getLabel('LBL_DELETED', $langId);
            }
        }
        return $arr;
    }

    public function getCategoryMediaColoumArr($langId)
    {
        $arr = array();
        if ($this->settings['CONF_USE_CATEGORY_ID']) {
            $arr['prodcat_id'] = Labels::getLabel('LBL_CATEGORY_ID', $langId);
        } else {
            $arr['prodcat_identifier'] = Labels::getLabel('LBL_CATEGORY_IDENTIFIER', $langId);
        }

        if ($this->settings['CONF_USE_LANG_ID']) {
            $arr['afile_lang_id'] = Labels::getLabel('LBL_LANG_ID', $langId);
        } else {
            $arr['afile_lang_code'] = Labels::getLabel('LBL_LANG_CODE', $langId);
        }

        $arr['afile_type'] = Labels::getLabel('LBL_IMAGE_TYPE', $langId);
        $arr['afile_screen'] = Labels::getLabel('LBL_DISPLAY_SCREEN', $langId);
        $arr['afile_physical_path'] = Labels::getLabel('LBL_FILE_PATH', $langId);
        $arr['afile_name'] = Labels::getLabel('LBL_FILE_NAME', $langId);
        $arr['afile_display_order'] = Labels::getLabel('LBL_DISPLAY_ORDER', $langId);
        return $arr;
    }

    public function getBrandColoumArr($langId, $userId = 0)
    {
        $arr = array();

        if ($this->settings['CONF_USE_BRAND_ID']) {
            $arr['brand_id'] = Labels::getLabel('LBL_BRAND_ID', $langId);
            if ($this->isDefaultSheetData($langId)) {
                $arr['brand_identifier'] = Labels::getLabel('LBL_BRAND_IDENTIFIER', $langId);
            }
        } else {
            $arr['brand_identifier'] = Labels::getLabel('LBL_BRAND_IDENTIFIER', $langId);
        }
        $arr['brand_name'] = Labels::getLabel('LBL_NAME', $langId);

        if (!$userId) {
            /*$arr['brand_short_description'] = Labels::getLabel('LBL_Description', $langId);*/

            if ($this->isDefaultSheetData($langId)) {
                $arr['urlrewrite_custom'] = Labels::getLabel('LBL_SEO_FRIENDLY_URL', $langId);
                /*$arr['brand_featured'] = Labels::getLabel('LBL_Featured', $langId);*/
                $arr['brand_active'] = Labels::getLabel('LBL_ACTIVE', $langId);
                $arr['brand_deleted'] = Labels::getLabel('LBL_DELETED', $langId);
            }
        }
        return $arr;
    }

    public function getcBrandColoumArr($langId, $userId = 0)
    {
        $arr = array();

        if ($this->settings['CONF_USE_BRAND_ID']) {
            $arr['cbrand_id'] = Labels::getLabel('LBL_CBRAND_ID', $langId);
            if ($this->isDefaultSheetData($langId)) {
                $arr['cbrand_identifier'] = Labels::getLabel('LBL_CBRAND_IDENTIFIER', $langId);
            }
        } else {
            $arr['cbrand_id'] = Labels::getLabel('LBL_CBRAND_ID', $langId);
            $arr['cbrand_identifier'] = Labels::getLabel('LBL_CBRAND_IDENTIFIER', $langId);
        }
        $arr['cbrand_name'] = Labels::getLabel('LBL_NAME', $langId);

        if (!$userId) {
            /*$arr['brand_short_description'] = Labels::getLabel('LBL_Description', $langId);*/

            if ($this->isDefaultSheetData($langId)) {
                $arr['cbrand_active'] = Labels::getLabel('LBL_ACTIVE', $langId);
                $arr['cbrand_deleted'] = Labels::getLabel('LBL_DELETED', $langId);
            }
        }
        return $arr;
    }    

    public function getBrandMediaColoumArr($langId)
    {
        $arr = array();
        if ($this->settings['CONF_USE_BRAND_ID']) {
            $arr['brand_id'] = Labels::getLabel('LBL_BRAND_ID', $langId);
        } else {
            $arr['brand_identifier'] = Labels::getLabel('LBL_BRAND_IDENTIFIER', $langId);
        }

        if ($this->settings['CONF_USE_LANG_ID']) {
            $arr['afile_lang_id'] = Labels::getLabel('LBL_LANG_ID', $langId);
        } else {
            $arr['afile_lang_code'] = Labels::getLabel('LBL_LANG_CODE', $langId);
        }

        $arr['afile_type'] = Labels::getLabel('LBL_FILE_TYPE', $langId);
        $arr['afile_screen'] = Labels::getLabel('LBL_DISPLAY_SCREEN', $langId);
        $arr['afile_physical_path'] = Labels::getLabel('LBL_FILE_PATH', $langId);
        $arr['afile_name'] = Labels::getLabel('LBL_FILE_NAME', $langId);
        $arr['afile_display_order'] = Labels::getLabel('LBL_DISPLAY_ORDER', $langId);
        return $arr;
    }

    public function getProductsCatalogColoumArr($langId, $userId = 0, $actionType = null)
    {
        $arr = array();

        if ($this->settings['CONF_USE_PRODUCT_ID']) {
            $arr['product_id'] = Labels::getLabel('LBL_PRODUCT_ID', $langId);
            if ($this->isDefaultSheetData($langId)) {
                $arr['product_identifier'] = Labels::getLabel('LBL_PRODUCT_IDENTIFIER', $langId);
            }
        } else {
            /*  if ($this->isDefaultSheetData($langId)) {
                $arr['product_id'] = Labels::getLabel('LBL_PRODUCT_ID', $langId);
            } */
            $arr['product_identifier'] = Labels::getLabel('LBL_PRODUCT_IDENTIFIER', $langId);
        }

        if ($this->isDefaultSheetData($langId) && $actionType != Importexport::ACTION_ADMIN_PRODUCTS) {
            if ($this->settings['CONF_USE_USER_ID']) {
                $arr['product_seller_id'] = Labels::getLabel('LBL_USER_ID', $langId);
            } else {
                $arr['credential_username'] = Labels::getLabel('LBL_USERNAME', $langId);
            }
        }

        $arr['product_name'] = Labels::getLabel('LBL_PRODUCT_NAME', $langId);
        /* $arr['product_short_description'] = Labels::getLabel('LBL_Short_Description', $langId); */
        $arr['product_description'] = Labels::getLabel('LBL_DESCRIPTION', $langId);
        $arr['product_youtube_video'] = Labels::getLabel('LBL_YOUTUBE_VIDEO', $langId);

        if ($this->isDefaultSheetData($langId)) {
            if ($this->settings['CONF_USE_CATEGORY_ID']) {
                $arr['category_Id'] = Labels::getLabel('LBL_CATEGORY_ID', $langId);
            } else {
                $arr['category_indentifier'] = Labels::getLabel('LBL_CATEGORY_IDENTIFIER', $langId);
            }

            if ($this->settings['CONF_USE_BRAND_ID']) {
                $arr['product_brand_id'] = Labels::getLabel('LBL_BRAND_ID', $langId);
            } else {
                $arr['brand_identifier'] = Labels::getLabel('LBL_BRAND_IDENTIFIER', $langId);
            }
           
            $arr['product_cbrand_id'] = Labels::getLabel('LBL_COMPATIBLE_BRAND_ID', $langId);
            

            if ($this->settings['CONF_USE_PRODUCT_TYPE_ID']) {
                $arr['product_type'] = Labels::getLabel('LBL_PRODUCT_TYPE_ID', $langId);
            } else {
                $arr['product_type_identifier'] = Labels::getLabel('LBL_PRODUCT_TYPE_IDENTIFIER', $langId);
            }

            $arr['product_model'] = Labels::getLabel('LBL_Model', $langId);
            $arr['product_min_selling_price'] = Labels::getLabel('LBL_MIN_SELLING_PRICE', $langId);

            if ($this->settings['CONF_USE_TAX_CATEOGRY_ID']) {
                $arr['tax_category_id'] = Labels::getLabel('LBL_TAX_CATEGORY_ID', $langId);
            } else {
                $arr['tax_category_identifier'] = Labels::getLabel('LBL_TAX_CATEGORY_IDENTIFIER', $langId);
            }

            $shippedBy = FatApp::getConfig('CONF_SHIPPED_BY_ADMIN_ONLY', FatUtility::VAR_INT, 0);

            if ((0 == $userId && $shippedBy) || !$shippedBy) {
                if ($this->settings['CONF_USE_SHIPPING_PROFILE_ID']) {
                    $arr['shipping_profile_id'] = Labels::getLabel('LBL_SHIPPING_PROFILE_ID', $langId);
                } else {
                    $arr['shipping_profile_identifier'] = Labels::getLabel('LBL_SHIPPING_PROFILE_IDENTIFIER', $langId);
                }
            }

            if (FatApp::getConfig("CONF_PRODUCT_DIMENSIONS_ENABLE", FatUtility::VAR_INT, 1)) {
                if ($this->settings['CONF_USE_SHIPPING_PACKAGE_ID']) {
                    $arr['product_ship_package_id'] = Labels::getLabel('LBL_SHIPPING_PACKAGE_ID', $langId);
                } else {
                    $arr['product_ship_package_identifier'] = Labels::getLabel('LBL_SHIPPING_PACKAGE_IDENTIFIER', $langId);
                }
            }

            /*  $arr['product_length'] = Labels::getLabel('LBL_Length', $langId);
             $arr['product_width'] = Labels::getLabel('LBL_Width', $langId);
             $arr['product_height'] = Labels::getLabel('LBL_Height', $langId); */

            /* if ($this->settings['CONF_USE_DIMENSION_UNIT_ID']) {
                $arr['product_dimension_unit'] = Labels::getLabel('LBL_Dimension_Unit_Id', $langId);
            } else {
                $arr['product_dimension_unit_identifier'] = Labels::getLabel('LBL_Dimension_Unit_Identifier', $langId);
            } */

            if (FatApp::getConfig("CONF_PRODUCT_WEIGHT_ENABLE", FatUtility::VAR_INT, 1)) {
                $arr['product_weight'] = Labels::getLabel('LBL_Weight', $langId);

                if ($this->settings['CONF_USE_WEIGHT_UNIT_ID']) {
                    $arr['product_weight_unit'] = Labels::getLabel('LBL_WEIGHT_UNIT_ID', $langId);
                } else {
                    $arr['product_weight_unit_identifier'] = Labels::getLabel('LBL_WEIGHT_UNIT_IDENTIFIER', $langId);
                }
            }

            $arr['product_warranty'] = Labels::getLabel('LBL_PRODUCT_WARRANTY_(DAYS)', $langId);
            // $arr['product_upc'] = Labels::getLabel('LBL_EAN/UPC/GTIN_CODE', $langId);

            if ((0 == $userId && $shippedBy) || !$shippedBy) {
                if ($this->settings['CONF_USE_COUNTRY_ID']) {
                    $arr['ps_from_country_id'] = Labels::getLabel('LBL_SHIPPING_COUNTRY_ID', $langId);
                } else {
                    $arr['country_code'] = Labels::getLabel('LBL_SHIPPING_COUNTRY_CODE', $langId);
                }
            }

            if (0 == $userId) {
                $arr['product_fulfillment_type'] = Labels::getLabel('LBL_FULFILLMENT_TYPE', $langId);
            }

            if ((0 == $userId && $shippedBy) || !$shippedBy) {
                // $arr['ps_free'] = Labels::getLabel('LBL_Free_Shipping', $langId);
                $arr['product_cod_enabled'] = Labels::getLabel('LBL_COD_AVAILABLE', $langId);
            }

            $arr['product_featured'] = Labels::getLabel('LBL_FEATURED', $langId);
            $arr['product_approved'] = Labels::getLabel('LBL_APPROVED', $langId);
            $arr['product_active'] = Labels::getLabel('LBL_ACTIVE', $langId);
            $arr['product_deleted'] = Labels::getLabel('LBL_DELETED', $langId);
            $arr['product_attachements_with_inventory'] = Labels::getLabel('LBL_DOWNLOAD_ATTACHMENTS_AT_INVENTORY', $langId);
        }


        return $arr;
    }

    public function getProductOptionColoumArr($langId)
    {
        $arr = array();
        if ($this->settings['CONF_USE_PRODUCT_ID']) {
            $arr['product_id'] = Labels::getLabel('LBL_PRODUCT_ID', $langId);
        } else {
            $arr['product_identifier'] = Labels::getLabel('LBL_PRODUCT_IDENTIFIER', $langId);
        }

        if ($this->settings['CONF_USE_OPTION_ID']) {
            $arr['option_id'] = Labels::getLabel('LBL_OPTION_ID', $langId);
        } else {
            $arr['option_identifier'] = Labels::getLabel('LBL_OPTION_IDENTIFIER', $langId);
        }

        if ($this->settings['CONF_OPTION_VALUE_ID']) {
            $arr['option_value_ids'] = Labels::getLabel('LBL_OPTION_VALUE_Ids', $langId);
        } else {
            $arr['option_values_identifiers'] = Labels::getLabel('LBL_OPTION_VALUE_IDENTIFIERS', $langId);
        }

        return $arr;
    }

    public function getProductTagColoumArr($langId)
    {
        $arr = array();
        if ($this->settings['CONF_USE_PRODUCT_ID']) {
            $arr['product_id'] = Labels::getLabel('LBL_PRODUCT_ID', $langId);
        } else {
            $arr['product_identifier'] = Labels::getLabel('LBL_PRODUCT_IDENTIFIER', $langId);
        }

        if ($this->settings['CONF_USE_TAG_ID']) {
            $arr['tag_id'] = Labels::getLabel('LBL_TAG_ID', $langId);
        } else {
            $arr['tag_identifier'] = Labels::getLabel('LBL_TAG_IDENTIFIER', $langId);
        }

        return $arr;
    }

    public function getProductSpecificationColoumArr($langId)
    {
        $arr = array();
        if ($this->settings['CONF_USE_PRODUCT_ID']) {
            $arr['product_id'] = Labels::getLabel('LBL_PRODUCT_ID', $langId);
        } else {
            $arr['product_identifier'] = Labels::getLabel('LBL_pRODUCT_IDENTIFIER', $langId);
        }

        if ($this->settings['CONF_USE_LANG_ID']) {
            $arr['prodspeclang_lang_id'] = Labels::getLabel('LBL_LANG_ID', $langId);
        } else {
            $arr['prodspeclang_lang_code'] = Labels::getLabel('LBL_LANG_CODE', $langId);
        }

        $arr['prodspec_name'] = Labels::getLabel('LBL_SPECIFICATION_NAME', $langId);
        $arr['prodspec_value'] = Labels::getLabel('LBL_SPECIFICATION_VALUE', $langId);
        $arr['prodspec_group'] = Labels::getLabel('LBL_SPECIFICATION_GROUP', $langId);

        return $arr;
    }

    public function getProductShippingColoumArr($langId)
    {
        $arr = array();
        if ($this->settings['CONF_USE_PRODUCT_ID']) {
            $arr['product_id'] = Labels::getLabel('LBL_PRODUCT_ID', $langId);
        } else {
            $arr['product_identifier'] = Labels::getLabel('LBL_PRODUCT_IDENTIFIER', $langId);
        }

        if ($this->settings['CONF_USE_USER_ID']) {
            $arr['user_id'] = Labels::getLabel('LBL_USER_ID', $langId);
        } else {
            $arr['credential_username'] = Labels::getLabel('LBL_USERNAME', $langId);
        }

        if ($this->settings['CONF_USE_COUNTRY_ID']) {
            $arr['country_id'] = Labels::getLabel('LBL_SHIPPING_COUNTRY_ID', $langId);
        } else {
            $arr['country_code'] = Labels::getLabel('LBL_SHIPPING_COUNTRY_CODE', $langId);
        }

        if ($this->settings['CONF_USE_SHIPPING_COMPANY_ID']) {
            $arr['scompany_id'] = Labels::getLabel('LBL_SHIPPING_COMPANY_ID', $langId);
        } else {
            $arr['scompany_identifier'] = Labels::getLabel('LBL_SHIPPING_COMPANY_IDENTIFIER', $langId);
        }

        if ($this->settings['CONF_USE_SHIPPING_DURATION_ID']) {
            $arr['sduration_id'] = Labels::getLabel('LBL_SHIPPING_DURATION_ID', $langId);
        } else {
            $arr['sduration_identifier'] = Labels::getLabel('LBL_SHIPPING_DURATION_IDENTIFIER', $langId);
        }

        $arr['pship_charges'] = Labels::getLabel('LBL_COST', $langId);
        $arr['pship_additional_charges'] = Labels::getLabel('LBL_ADDITIONAL_ITEM_COST', $langId);
        return $arr;
    }

    public function getProductMediaColoumArr($langId)
    {
        $arr = array();
        if ($this->settings['CONF_USE_PRODUCT_ID']) {
            $arr['product_id'] = Labels::getLabel('LBL_PRODUCT_ID', $langId);
        } else {
            $arr['product_identifier'] = Labels::getLabel('LBL_PRODUCT_IDENTIFIER', $langId);
        }

        if ($this->settings['CONF_USE_LANG_ID']) {
            $arr['afile_lang_id'] = Labels::getLabel('LBL_LANG_ID', $langId);
        } else {
            $arr['afile_lang_code'] = Labels::getLabel('LBL_LANG_CODE', $langId);
        }

        if ($this->settings['CONF_USE_OPTION_ID']) {
            $arr['option_id'] = Labels::getLabel('LBL_OPTION_ID', $langId);
        } else {
            $arr['option_identifier'] = Labels::getLabel('LBL_OPTION_IDENTIFER', $langId);
        }

        if ($this->settings['CONF_OPTION_VALUE_ID']) {
            $arr['optionvalue_id'] = Labels::getLabel('LBL_OPTION_VALUE_ID', $langId);
        } else {
            $arr['optionvalue_identifier'] = Labels::getLabel('LBL_OPTION_VALUE_IDENTIFER', $langId);
        }

        $arr['afile_physical_path'] = Labels::getLabel('LBL_FILE_PATH', $langId);
        $arr['afile_name'] = Labels::getLabel('LBL_FILE_NAME', $langId);
        $arr['afile_display_order'] = Labels::getLabel('LBL_DISPLAY_ORDER', $langId);
        return $arr;
    }

    public function getSelProdGeneralColoumArr($langId, $userId = 0)
    {
        $arr = array();
        $arr['selprod_id'] = Labels::getLabel('LBL_SELLER_PRODUCT_ID', $langId);

        if ($this->settings['CONF_USE_PRODUCT_ID']) {
            $arr['selprod_product_id'] = Labels::getLabel('LBL_PRODUCT_ID', $langId);
        } else {
            $arr['product_identifier'] = Labels::getLabel('LBL_PRODUCT_IDENTIFIER', $langId);
        }

        if (!$userId) {
            if ($this->settings['CONF_USE_USER_ID']) {
                $arr['selprod_user_id'] = Labels::getLabel('LBL_USER_ID', $langId);
            } else {
                $arr['credential_username'] = Labels::getLabel('LBL_USERNAME', $langId);
            }
        }

        if ($this->isDefaultSheetData($langId)) {
            if (0 < FatApp::getConfig('CONF_RFQ_MODULE', FatUtility::VAR_INT, 0) && 1 > FatApp::getConfig('CONF_HIDE_PRICES', FatUtility::VAR_INT, 0)) {
                $arr['selprod_cart_type'] = Labels::getLabel('LBL_CART_TYPE', $langId);
                $arr['selprod_hide_price'] = Labels::getLabel('LBL_HIDE_PRICE', $langId);
            }
            $arr['selprod_price'] = Labels::getLabel('LBL_SELLING_PRICE', $langId);
            $arr['selprod_cost'] = Labels::getLabel('LBL_COST_PRICE', $langId);
            $arr['selprod_stock'] = Labels::getLabel('LBL_STOCK', $langId);
            $arr['selprod_sku'] = Labels::getLabel('LBL_SKU', $langId);
            $arr['selprod_min_order_qty'] = Labels::getLabel('LBL_MIN_ORDER_QUANTITY', $langId);
            $arr['selprod_subtract_stock'] = Labels::getLabel('LBL_SUBTRACT_STOCK', $langId);
            $arr['selprod_track_inventory'] = Labels::getLabel('LBL_TRACK_INVENTORY', $langId);
            $arr['selprod_threshold_stock_level'] = Labels::getLabel('LBL_THRESHOLD_STOCK_LEVEL', $langId);

            if ($this->settings['CONF_USE_PROD_CONDITION_ID']) {
                $arr['selprod_condition'] = Labels::getLabel('LBL_CONDITION_ID', $langId);
            } else {
                $arr['selprod_condition_identifier'] = Labels::getLabel('LBL_CONDITION_IDENTIFIER', $langId);
            }
            $arr['selprod_max_download_times'] = Labels::getLabel('LBL_DIGITAL_PRODUCT_MAX_DOWNLOAD_TIME', $langId);
            $arr['selprod_download_validity_in_days'] = Labels::getLabel('LBL_DOWNLOAD_VALIDITY_IN_DAYS', $langId);
        }

        $arr['selprod_title'] = Labels::getLabel('LBL_TITLE', $langId);
        $arr['selprod_comments'] = Labels::getLabel('LBL_COMMENTS', $langId);
        $arr['selprod_return_age'] = Labels::getLabel('LBL_RETURN_AGE', $langId);
        $arr['selprod_cancellation_age'] = Labels::getLabel('LBL_CANCELLATION_AGE', $langId);

        if ($this->isDefaultSheetData($langId)) {
            $arr['selprod_url_keyword'] = Labels::getLabel('LBL_URL_KEYWORD', $langId);

            // if(!$userId){
            //     $arr['selprod_added_on'] = Labels::getLabel('LBL_Added_on', $langId);
            // }

            $arr['selprod_available_from'] = Labels::getLabel('LBL_AVAILABLE_FROM', $langId);
            $arr['selprod_active'] = Labels::getLabel('LBL_ACTIVE', $langId);
            $arr['selprod_cod_enabled'] = Labels::getLabel('LBL_COD_AVAILABLE', $langId);
            $arr['selprod_fulfillment_type'] = Labels::getLabel('LBL_FULFILLMENT_TYPE', $langId);
            $arr['selprod_deleted'] = Labels::getLabel('LBL_DELETED', $langId);
            if (!$userId) {
                $arr['selprod_sold_count'] = Labels::getLabel('LBL_SOLD_COUNT', $langId);
            }
        }
        return $arr;
    }

    public function getSelProdMediaColoumArr($langId)
    {
        $arr = array();
        $arr['selprod_id'] = Labels::getLabel('LBL_SELLER_PRODUCT_ID', $langId);
        if ($this->settings['CONF_USE_LANG_ID']) {
            $arr['afile_lang_id'] = Labels::getLabel('LBL_LANG_ID', $langId);
        } else {
            $arr['afile_lang_code'] = Labels::getLabel('LBL_LANG_CODE', $langId);
        }
        $arr['afile_physical_path'] = Labels::getLabel('LBL_FILE_PATH', $langId);
        $arr['afile_name'] = Labels::getLabel('LBL_FILE_NAME', $langId);
        $arr['afile_display_order'] = Labels::getLabel('LBL_DISPLAY_ORDER', $langId);
        return $arr;
    }

    public function getSelProdOptionsColoumArr($langId)
    {
        $arr = array();
        $arr['selprodoption_selprod_id'] = Labels::getLabel('LBL_SELLER_PRODUCT_ID', $langId);

        if ($this->settings['CONF_USE_OPTION_ID']) {
            $arr['option_id'] = Labels::getLabel('LBL_OPTION_ID', $langId);
        } else {
            $arr['option_identifier'] = Labels::getLabel('LBL_OPTION_IDENTIFIER', $langId);
        }

        if ($this->settings['CONF_OPTION_VALUE_ID']) {
            $arr['optionvalue_id'] = Labels::getLabel('LBL_OPTION_VALUE_ID', $langId);
        } else {
            $arr['optionvalue_identifier'] = Labels::getLabel('LBL_OPTION_VALUE_IDENTIFIER', $langId);
        }
        return $arr;
    }

    public function getSelProdSeoColoumArr($langId)
    {
        $arr = array();
        $arr['selprod_id'] = Labels::getLabel('LBL_SELLER_PRODUCT_ID', $langId);

        /* if ($this->isDefaultSheetData($langId)) {
            $arr['meta_identifier'] = Labels::getLabel('LBL_meta_identifier', $langId);
        } */
        $arr['meta_title'] = Labels::getLabel('LBL_META_TITLE', $langId);
        $arr['meta_keywords'] = Labels::getLabel('LBL_META_KEYWORDS', $langId);
        $arr['meta_description'] = Labels::getLabel('LBL_META_DESCRIPTION', $langId);
        $arr['meta_other_meta_tags'] = Labels::getLabel('LBL_OTHER_META_TAGS', $langId);
        return $arr;
    }

    public function getSelProdSpecialPriceColoumArr($langId)
    {
        $arr = array();
        $arr['selprod_id'] = Labels::getLabel('LBL_SELLER_PRODUCT_ID', $langId);
        $arr['splprice_start_date'] = Labels::getLabel('LBL_START_DATE', $langId);
        $arr['splprice_end_date'] = Labels::getLabel('LBL_END_DATE', $langId);
        $arr['splprice_price'] = Labels::getLabel('LBL_PRICE', $langId);

        /* if($this->settings['CONF_USE_PERSENT_OR_FLAT_CONDITION_ID']){
        $arr[] = Labels::getLabel('LBL_display_price_type_id', $langId);
        }else{
        $arr[] = Labels::getLabel('LBL_display_price_type', $langId);
        }

        $arr[] = Labels::getLabel('LBL_display_discount_value', $langId);
        $arr[] = Labels::getLabel('LBL_display_list_price', $langId); */
        return $arr;
    }

    public function getSelProdVolumeDiscountColoumArr($langId)
    {
        $arr = array();
        $arr['selprod_id'] = Labels::getLabel('LBL_SELLER_PRODUCT_ID', $langId);
        $arr['voldiscount_min_qty'] = Labels::getLabel('LBL_MIN_QUANTITY', $langId);
        $arr['voldiscount_percentage'] = Labels::getLabel('LBL_DISCOUNT_PERCENTAGE', $langId);
        return $arr;
    }

    public function getSelProdBuyTogetherColoumArr($langId)
    {
        $arr = array();
        $arr['selprod_id'] = Labels::getLabel('LBL_SELLER_PRODUCT_ID', $langId);
        $arr['upsell_recommend_sellerproduct_id'] = Labels::getLabel('LBL_BUY_TOGETHER_SELLER_PRODUCT_ID', $langId);
        return $arr;
    }

    public function getSelProdRelatedProductColoumArr($langId)
    {
        $arr = array();
        $arr['selprod_id'] = Labels::getLabel('LBL_SELLER_PRODUCT_ID', $langId);
        $arr['related_recommend_sellerproduct_id'] = Labels::getLabel('LBL_RELATED_SELLER_PRODUCT_ID', $langId);
        return $arr;
    }

    public function getSelProdPolicyColoumArr($langId)
    {
        $arr = array();
        $arr['selprod_id'] = Labels::getLabel('LBL_SELLER_PRODUCT_ID', $langId);
        if ($this->settings['CONF_USE_POLICY_POINT_ID']) {
            $arr['sppolicy_ppoint_id'] = Labels::getLabel('LBL_POLICY_POINT_ID', $langId);
        } else {
            $arr['ppoint_identifier'] = Labels::getLabel('LBL_POLICY_POINT_IDENTIFIER', $langId);
        }
        return $arr;
    }

    public function getOptionsColoumArr($langId, $userId = 0)
    {
        $arr = array();

        if ($this->settings['CONF_USE_OPTION_ID']) {
            $arr['option_id'] = Labels::getLabel('LBL_OPTION_ID', $langId);
            if ($this->isDefaultSheetData($langId)) {
                $arr['option_identifier'] = Labels::getLabel('LBL_OPTION_IDENTIFIER', $langId);
            }
        } else {
            $arr['option_identifier'] = Labels::getLabel('LBL_OPTION_IDENTIFIER', $langId);
        }

        $arr['option_name'] = Labels::getLabel('LBL_OPTION_NAME', $langId);

        if (!$userId) {
            if ($this->isDefaultSheetData($langId)) {
                if ($this->settings['CONF_USE_USER_ID']) {
                    $arr['option_seller_id'] = Labels::getLabel('LBL_USER_ID', $langId);
                } else {
                    $arr['credential_username'] = Labels::getLabel('LBL_USERNAME', $langId);
                }

                /* if($this->settings['CONF_USE_OPTION_TYPE_ID']){
                $arr[] = Labels::getLabel('LBL_Option_Type_ID', $langId);
                }else{
                $arr[] = Labels::getLabel('LBL_Option_Type', $langId);
                } */

                $arr['option_is_separate_images'] = Labels::getLabel('LBL_HAS_SEPARATE_IMAGE', $langId);
                $arr['option_is_color'] = Labels::getLabel('LBL_OPTION_IS_COLOR', $langId);
                $arr['option_display_in_filter'] = Labels::getLabel('LBL_DISPLAY_IN_FILTERS', $langId);
                if (!$userId) {
                    $arr['option_deleted'] = Labels::getLabel('LBL_DELETED', $langId);
                }
            }
        }
        return $arr;
    }

    public function getOptionsValueColoumArr($langId, $userId = 0)
    {
        $arr = array();

        if ($this->settings['CONF_OPTION_VALUE_ID']) {
            $arr['optionvalue_id'] = Labels::getLabel('LBL_OPTION_VALUE_ID', $langId);
            if ($this->isDefaultSheetData($langId)) {
                $arr['optionvalue_identifier'] = Labels::getLabel('LBL_OPTION_VALUE_IDENTIFIER', $langId);
            }
        } else {
            $arr['optionvalue_identifier'] = Labels::getLabel('LBL_OPTION_VALUE_IDENTIFIER', $langId);
        }

        if ($this->settings['CONF_USE_OPTION_ID']) {
            $arr['optionvalue_option_id'] = Labels::getLabel('LBL_OPTION_ID', $langId);
        } else {
            $arr['option_identifier'] = Labels::getLabel('LBL_OPTION_IDENTIFIER', $langId);
        }

        $arr['optionvalue_name'] = Labels::getLabel('LBL_OPTION_VALUE', $langId);

        if ($this->isDefaultSheetData($langId)) {
            $arr['optionvalue_color_code'] = Labels::getLabel('LBL_COLOR_CODE', $langId);
            if (!$userId) {
                $arr['optionvalue_display_order'] = Labels::getLabel('LBL_DISPLAY_ORDER', $langId);
            }
        }

        return $arr;
    }

    public function getTagColoumArr($langId, $userId = 0)
    {
        $arr = array();
        if ($this->settings['CONF_USE_TAG_ID']) {
            $arr['tag_id'] = Labels::getLabel('LBL_TAG_ID', $langId);
            if ($this->isDefaultSheetData($langId)) {
                $arr['tag_identifier'] = Labels::getLabel('LBL_TAG_IDENTIFIER', $langId);
            }
        } else {
            $arr['tag_identifier'] = Labels::getLabel('LBL_TAG_IDENTIFIER', $langId);
        }

        if (!$userId) {
            if ($this->isDefaultSheetData($langId)) {
                if ($this->settings['CONF_USE_USER_ID']) {
                    $arr['tag_user_id'] = Labels::getLabel('LBL_USER_ID', $langId);
                } else {
                    $arr['credential_username'] = Labels::getLabel('LBL_USERNAME', $langId);
                }
            }
        }

        $arr['tag_name'] = Labels::getLabel('LBL_TAG_NAME', $langId);
        return $arr;
    }

    public function getZoneColoumArr($langId, $userId = 0)
    {
        $arr = array();
        if ($this->settings['CONF_USE_ZONE_ID']) {
            $arr['zone_id'] = Labels::getLabel('LBL_ZONE_ID', $langId);
            if ($this->isDefaultSheetData($langId)) {
                $arr['zone_identifier'] = Labels::getLabel('LBL_ZONE_IDENTIFIER', $langId);
            }
        } else {
            $arr['zone_identifier'] = Labels::getLabel('LBL_ZONE_IDENTIFIER', $langId);
        }

        $arr['zone_name'] = Labels::getLabel('LBL_ZONE_NAME', $langId);

        if (!$userId) {
            if ($this->isDefaultSheetData($langId)) {
                $arr['zone_active'] = Labels::getLabel('LBL_ACTIVE', $langId);
            }
        }

        return $arr;
    }

    public function getCountryColoumArr($langId, $userId = 0)
    {
        $arr = array();
        if ($this->settings['CONF_USE_COUNTRY_ID']) {
            $arr['country_id'] = Labels::getLabel('LBL_COUNTRY_ID', $langId);
            if ($this->isDefaultSheetData($langId)) {
                $arr['country_code'] = Labels::getLabel('LBL_COUNTRY_CODE', $langId);
            }
        } else {
            $arr['country_code'] = Labels::getLabel('LBL_COUNTRY_CODE', $langId);
        }

        $arr['country_name'] = Labels::getLabel('LBL_COUNTRY_NAME', $langId);
        $arr['country_code_alpha3'] = Labels::getLabel('LBL_COUNTRY_ALPHA3_CODE', $langId);

        if (!$userId) {
            if ($this->isDefaultSheetData($langId)) {
                if ($this->settings['CONF_USE_CURRENCY_ID']) {
                    $arr['country_currency_id'] = Labels::getLabel('LBL_CURRENCY_ID', $langId);
                } else {
                    $arr['country_currency_code'] = Labels::getLabel('LBL_CURRENCY_CODE', $langId);
                }

                if ($this->settings['CONF_USE_LANG_ID']) {
                    $arr['country_language_id'] = Labels::getLabel('LBL_LANG_ID', $langId);
                } else {
                    $arr['country_language_code'] = Labels::getLabel('LBL_LANG_CODE', $langId);
                }

                $arr['country_active'] = Labels::getLabel('LBL_ACTIVE', $langId);
            }
        }

        return $arr;
    }

    public function getStatesColoumArr($langId, $userId = 0)
    {
        $arr = array();
        if ($this->settings['CONF_USE_STATE_ID']) {
            $arr['state_id'] = Labels::getLabel('LBL_STATE_ID', $langId);
            if ($this->isDefaultSheetData($langId)) {
                $arr['state_identifier'] = Labels::getLabel('LBL_STATE_IDENTIFIER', $langId);
            }
        } else {
            $arr['state_identifier'] = Labels::getLabel('LBL_STATE_IDENTIFIER', $langId);
        }

        if ($this->settings['CONF_USE_COUNTRY_ID']) {
            $arr['state_country_id'] = Labels::getLabel('LBL_COUNTRY_ID', $langId);
        } else {
            $arr['country_code'] = Labels::getLabel('LBL_COUNTRY_CODE', $langId);
        }

        $arr['state_name'] = Labels::getLabel('LBL_STATE_NAME', $langId);

        if ($this->isDefaultSheetData($langId)) {
            $arr['state_code'] = Labels::getLabel('LBL_STATE_CODE', $langId);
            if (!$userId) {
                $arr['state_active'] = Labels::getLabel('LBL_ACTIVE', $langId);
            }
        }
        return $arr;
    }

    public function getPolicyPointsColoumArr($langId, $userId = 0)
    {
        $arr = array();
        if ($this->settings['CONF_USE_POLICY_POINT_ID']) {
            $arr['ppoint_id'] = Labels::getLabel('LBL_POLICY_POINT_ID', $langId);
            if ($this->isDefaultSheetData($langId)) {
                $arr['ppoint_identifier'] = Labels::getLabel('LBL_POLICY_POINT_IDENTIFIER', $langId);
            }
        } else {
            $arr['ppoint_identifier'] = Labels::getLabel('LBL_POLICY_POINT_IDENTIFIER', $langId);
        }
        $arr['ppoint_title'] = Labels::getLabel('LBL_POLICY_POINT_TITLE', $langId);

        if ($this->isDefaultSheetData($langId)) {
            if ($this->settings['CONF_USE_POLICY_POINT_TYPE_ID']) {
                $arr['ppoint_type'] = Labels::getLabel('LBL_POLICY_POINT_TYPE_ID', $langId);
            } else {
                $arr['ppoint_type_identifier'] = Labels::getLabel('LBL_POLICY_POINT_TYPE_IDENTIFIER', $langId);
            }

            if (!$userId) {
                $arr['ppoint_display_order'] = Labels::getLabel('LBL_DISPLAY_ORDER', $langId);
                $arr['ppoint_active'] = Labels::getLabel('LBL_ACTIVE', $langId);
                $arr['ppoint_deleted'] = Labels::getLabel('LBL_DELETED', $langId);
            }
        }
        return $arr;
    }

    public function getUsersColoumArr($langId)
    {
        $arr = array();
        $arr['user_id'] = Labels::getLabel('LBL_USER_ID', $langId);
        $arr['user_name'] = Labels::getLabel('LBL_NAME', $langId);
        $arr['credential_username'] = Labels::getLabel('LBL_USERNAME', $langId);
        $arr['user_phone'] = Labels::getLabel('LBL_PHONE', $langId);
        $arr['user_is_buyer'] = Labels::getLabel('LBL_IS_BUYER', $langId);
        $arr['user_is_supplier'] = Labels::getLabel('LBL_IS_SUPPLIER', $langId);
        $arr['user_is_advertiser'] = Labels::getLabel('LBL_IS_ADVERTISER', $langId);
        $arr['user_is_affiliate'] = Labels::getLabel('LBL_IS_AFFILIATE', $langId);
        return $arr;
    }

    public function getSalesTaxColumArr($langId, $userId = 0)
    {
        $arr = array();
        $arr['taxcat_id'] = Labels::getLabel('LBL_TAX_CATEGORY_ID', $langId);
        $arr['taxcat_identifier'] = Labels::getLabel('LBL_TAX_CATEGORY_IDENTIFIER', $langId);
        $arr['taxcat_name'] = Labels::getLabel('LBL_TAX_CATEGORY_NAME', $langId);
        if (!$userId) {
            if ($this->isDefaultSheetData($langId)) {
                $arr['taxcat_last_updated'] = Labels::getLabel('LBL_LAST_UPDATED', $langId);
                $arr['taxcat_active'] = Labels::getLabel('LBL_ACTIVE', $langId);
                $arr['taxcat_deleted'] = Labels::getLabel('LBL_DELETED', $langId);
            }
        }
        return $arr;
    }

    public function getAllRewriteUrls($startingWith)
    {
        $keywordSrch = UrlRewrite::getSearchObject();
        $keywordSrch->doNotCalculateRecords();
        $keywordSrch->doNotLimitRecords();
        $keywordSrch->addMultipleFields(array('urlrewrite_original', 'urlrewrite_custom'));
        $keywordSrch->addCondition(UrlRewrite::DB_TBL_PREFIX . 'original', 'like', $startingWith . '%');
        $keywordRs = $keywordSrch->getResultSet();
        $urlKeywords = $this->db->fetchAllAssoc($keywordRs, 'brand_identifier');
        return $urlKeywords;
    }

    public function getSettingsArr($siteConfiguration = false)
    {
        $arr =  array(
            'CONF_USE_BRAND_ID' => ($siteConfiguration) ? FatApp::getConfig('CONF_USE_BRAND_ID', FatUtility::VAR_INT, 0) : false,
            'CONF_USE_CATEGORY_ID' => ($siteConfiguration) ? FatApp::getConfig('CONF_USE_CATEGORY_ID', FatUtility::VAR_INT, 0) : false,
            'CONF_USE_PRODUCT_ID' => ($siteConfiguration) ? FatApp::getConfig('CONF_USE_PRODUCT_ID', FatUtility::VAR_INT, 0) : false,
            'CONF_USE_USER_ID' => false,
            'CONF_USE_OPTION_ID' => ($siteConfiguration) ? FatApp::getConfig('CONF_USE_OPTION_ID', FatUtility::VAR_INT, 0) : false,
            'CONF_OPTION_VALUE_ID' => ($siteConfiguration) ? FatApp::getConfig('CONF_OPTION_VALUE_ID', FatUtility::VAR_INT, 0) : false,
            'CONF_USE_TAG_ID' => ($siteConfiguration) ? FatApp::getConfig('CONF_USE_TAG_ID', FatUtility::VAR_INT, 0) : false,
            'CONF_USE_TAX_CATEOGRY_ID' => ($siteConfiguration) ? FatApp::getConfig('CONF_USE_TAX_CATEOGRY_ID', FatUtility::VAR_INT, 0) : false,
            'CONF_USE_PRODUCT_TYPE_ID' => ($siteConfiguration) ? FatApp::getConfig('CONF_USE_PRODUCT_TYPE_ID', FatUtility::VAR_INT, 0) : false,
            'CONF_USE_DIMENSION_UNIT_ID' => ($siteConfiguration) ? FatApp::getConfig('CONF_USE_DIMENSION_UNIT_ID', FatUtility::VAR_INT, 0) : false,
            'CONF_USE_WEIGHT_UNIT_ID' => ($siteConfiguration) ? FatApp::getConfig('CONF_USE_WEIGHT_UNIT_ID', FatUtility::VAR_INT, 0) : false,
            'CONF_USE_LANG_ID' => ($siteConfiguration) ? FatApp::getConfig('CONF_USE_LANG_ID', FatUtility::VAR_INT, 0) : false,
            'CONF_USE_CURRENCY_ID' => ($siteConfiguration) ? FatApp::getConfig('CONF_USE_CURRENCY_ID', FatUtility::VAR_INT, 0) : false,
            'CONF_USE_PROD_CONDITION_ID' => ($siteConfiguration) ? FatApp::getConfig('CONF_USE_PROD_CONDITION_ID', FatUtility::VAR_INT, 0) : false,
            'CONF_USE_PERSENT_OR_FLAT_CONDITION_ID' => ($siteConfiguration) ? FatApp::getConfig('CONF_USE_PERSENT_OR_FLAT_CONDITION_ID', FatUtility::VAR_INT, 0) : false,
            'CONF_USE_STATE_ID' => ($siteConfiguration) ? FatApp::getConfig('CONF_USE_STATE_ID', FatUtility::VAR_INT, 0) : false,
            'CONF_USE_COUNTRY_ID' => ($siteConfiguration) ? FatApp::getConfig('CONF_USE_COUNTRY_ID', FatUtility::VAR_INT, 0) : false,
            'CONF_USE_POLICY_POINT_ID' => ($siteConfiguration) ? FatApp::getConfig('CONF_USE_POLICY_POINT_ID', FatUtility::VAR_INT, 0) : false,
            'CONF_USE_POLICY_POINT_TYPE_ID' => ($siteConfiguration) ? FatApp::getConfig('CONF_USE_POLICY_POINT_TYPE_ID', FatUtility::VAR_INT, 0) : false,
            'CONF_USE_SHIPPING_COMPANY_ID' => ($siteConfiguration) ? FatApp::getConfig('CONF_USE_SHIPPING_COMPANY_ID', FatUtility::VAR_INT, 0) : false,
            'CONF_USE_SHIPPING_DURATION_ID' => ($siteConfiguration) ? FatApp::getConfig('CONF_USE_SHIPPING_DURATION_ID', FatUtility::VAR_INT, 0) : false,
            'CONF_USE_SHIPPING_PROFILE_ID' => ($siteConfiguration) ? FatApp::getConfig('CONF_USE_SHIPPING_PROFILE_ID', FatUtility::VAR_INT, 0) : false,
            'CONF_USE_SHIPPING_PACKAGE_ID' => ($siteConfiguration) ? FatApp::getConfig('CONF_USE_SHIPPING_PACKAGE_ID', FatUtility::VAR_INT, 0) : false,
            'CONF_USE_O_OR_1' => ($siteConfiguration) ? FatApp::getConfig('CONF_USE_O_OR_1', FatUtility::VAR_INT, 0) : false,
            'CONF_PRODUCT_BRAND_MANDATORY' => ($siteConfiguration) ? FatApp::getConfig('CONF_PRODUCT_BRAND_MANDATORY', FatUtility::VAR_INT, false) : false,
            'CONF_USE_ZONE_ID' => ($siteConfiguration) ? FatApp::getConfig('CONF_USE_ZONE_ID', FatUtility::VAR_INT, 0) : false,
        );

        if (FatApp::getConfig('CONF_WITHOUT_PROD_VARIANTS', FatUtility::VAR_INT, 0)) {
            unset($arr['CONF_USE_OPTION_ID']);
            unset($arr['CONF_OPTION_VALUE_ID']);
        }
        return $arr;
    }

    public function getSettings($userId = 0)
    {
        $userId = FatUtility::int($userId);
        $res = $this->getSettingsArr(true);
        if (!$userId) {
            return $res;
        }

        $srch = new SearchBase(Importexport::DB_TBL_SETTINGS, 's');
        $srch->addCondition('impexp_setting_user_id', '=', $userId);
        $srch->doNotCalculateRecords();
        $srch->doNotLimitRecords();
        $srch->addMultipleFields(array('impexp_setting_key', 'impexp_setting_value'));
        $rs = $srch->getResultSet();
        $row = $this->db->fetchAllAssoc($rs);
        if (!$row) {
            return $res;
        }
        $row['CONF_USE_USER_ID'] = false;
        return $row;
    }

    public function getAllCategoryIdentifiers($byId = true, $catIdOrIdentifier = false)
    {
        $srch = ProductCategory::getSearchObject(false, false, false);
        $srch->addOrder('m.prodcat_active', 'DESC');
        $srch->doNotCalculateRecords();
        if ($catIdOrIdentifier) {
            $srch->setPageSize(1);
        } else {
            $srch->doNotLimitRecords();
        }

        if ($byId) {
            $srch->addMultipleFields(array('prodcat_id', 'prodcat_identifier'));
            if ($catIdOrIdentifier) {
                $srch->addCondition('prodcat_id', '=', $catIdOrIdentifier);
            }
        } else {
            $srch->addMultipleFields(array('prodcat_identifier', 'prodcat_id'));
            if ($catIdOrIdentifier) {
                $srch->addCondition('prodcat_identifier', '=', $catIdOrIdentifier);
            }
        }
        $rs = $srch->getResultSet();
        return $this->db->fetchAllAssoc($rs);
    }

    public function getAllProductsIdentifiers($byId = true, $productIdOrIdentifier = false)
    {
        $srch = Product::getSearchObject();
        $srch->doNotCalculateRecords();

        if ($productIdOrIdentifier) {
            $srch->setPageSize(1);
        } else {
            $srch->doNotLimitRecords();
        }

        if ($byId) {
            $srch->addMultipleFields(array('product_id', 'trim(product_identifier)'));
            if ($productIdOrIdentifier) {
                $srch->addCondition('product_id', '=', $productIdOrIdentifier);
            }
        } else {
            $srch->addMultipleFields(array('trim(product_identifier)', 'product_id'));
            if ($productIdOrIdentifier) {
                $srch->addCondition('product_identifier', '=', $productIdOrIdentifier);
            }
        }
        return $this->db->fetchAllAssoc($srch->getResultSet());
    }

    public function getAllUserArr($byId = true, $userIdOrUsername = false)
    {
        $srch = User::getSearchObject(true);
        $srch->doNotCalculateRecords();

        if ($userIdOrUsername) {
            $srch->setPageSize(1);
        } else {
            $srch->doNotLimitRecords();
        }

        if ($byId) {
            $srch->addMultipleFields(array('user_id', 'credential_username'));
            if ($userIdOrUsername) {
                $srch->addCondition('user_id', '=', $userIdOrUsername);
            }
        } else {
            $srch->addMultipleFields(array('credential_username', 'user_id'));
            if ($userIdOrUsername) {
                $srch->addCondition('credential_username', '=', $userIdOrUsername);
            }
        }

        $rs = $srch->getResultSet();
        return $this->db->fetchAllAssoc($rs);
    }

    public function getTaxCategoryArr($byId = true, $taxCatIdOrIdentifier = false)
    {
        $srch = Tax::getSearchObject(false, false);
        $srch->doNotCalculateRecords();

        if ($taxCatIdOrIdentifier) {
            $srch->setPageSize(1);
        } else {
            $srch->doNotLimitRecords();
        }

        if ($byId) {
            $srch->addMultipleFields(array('taxcat_id', 'taxcat_identifier'));
            if ($taxCatIdOrIdentifier) {
                $srch->addCondition('taxcat_id', '=', $taxCatIdOrIdentifier);
            }
        } else {
            $srch->addMultipleFields(array('taxcat_identifier', 'taxcat_id'));
            if ($taxCatIdOrIdentifier) {
                $srch->addCondition('taxcat_identifier', '=', $taxCatIdOrIdentifier);
            }
        }
        $rs = $srch->getResultSet();
        return $this->db->fetchAllAssoc($rs);
    }

    public function getTaxCategoryByProductId($productId)
    {
        $taxData = array();
        $taxObj = Tax::getTaxCatObjByProductId($productId);
        $taxObj->addMultipleFields(array('ptt_taxcat_id'));
        $taxObj->doNotCalculateRecords();
        $taxObj->setPageSize(1);
        $taxObj->addOrder('ptt_seller_user_id', 'ASC');
        $rs = $taxObj->getResultSet();
        return $taxData = FatApp::getDb()->fetch($rs);
    }

    public function getAllBrandsArr($byId = true, $brandIdOrIdentifier = false)
    {
        $srch = Brand::getSearchObject(false, false, false);
        $srch->doNotCalculateRecords();

        if ($brandIdOrIdentifier) {
            $srch->setPageSize(1);
        } else {
            $srch->doNotLimitRecords();
        }

        if ($byId) {
            $srch->addMultipleFields(array('brand_id', 'brand_identifier'));
            if ($brandIdOrIdentifier) {
                $srch->addCondition('brand_id', '=', $brandIdOrIdentifier);
            }
        } else {
            $srch->addMultipleFields(array('brand_identifier', 'brand_id'));
            if ($brandIdOrIdentifier) {
                $srch->addCondition('brand_identifier', '=', $brandIdOrIdentifier);
            }
        }
        $rs = $srch->getResultSet();
        return $this->db->fetchAllAssoc($rs);
    }

    public function getShippingPackageArr($byId = true, $taxCatIdOrIdentifier = false)
    {
        $srch = ShippingPackage::getSearchObject();
        $srch->doNotCalculateRecords();

        if ($taxCatIdOrIdentifier) {
            $srch->setPageSize(1);
        } else {
            $srch->doNotLimitRecords();
        }

        if ($byId) {
            $srch->addMultipleFields(array('shippack_id', 'shippack_name'));
            if ($taxCatIdOrIdentifier) {
                $srch->addCondition('shippack_id', '=', $taxCatIdOrIdentifier);
            }
        } else {
            $srch->addMultipleFields(array('shippack_name', 'shippack_id'));
            if ($taxCatIdOrIdentifier) {
                $srch->addCondition('shippack_name', '=', $taxCatIdOrIdentifier);
            }
        }
        $rs = $srch->getResultSet();
        return $this->db->fetchAllAssoc($rs);
    }

    public function getShippingProfileArr($byId = true, $taxCatIdOrIdentifier = false, $userId = 0)
    {
        $srch = ShippingProfile::getSearchObject(NULL, false);
        $srch->doNotCalculateRecords();

        if ($taxCatIdOrIdentifier) {
            $srch->setPageSize(1);
        } else {
            $srch->doNotLimitRecords();
        }

        if ($byId) {
            $srch->addMultipleFields(array('shipprofile_id', 'shipprofile_identifier'));
            if ($taxCatIdOrIdentifier) {
                $srch->addCondition('shipprofile_id', '=', $taxCatIdOrIdentifier);
            }
        } else {
            $srch->addMultipleFields(array('shipprofile_identifier', 'shipprofile_id', 'shipprofile_user_id'));
            if ($taxCatIdOrIdentifier) {
                $srch->addCondition('shipprofile_identifier', '=', $taxCatIdOrIdentifier);
            }
        }

        if (FatApp::getConfig('CONF_SHIPPED_BY_ADMIN_ONLY', FatUtility::VAR_INT, 0)) {
            $srch->addCondition('shipprofile_user_id', '=', 0);
        } else {
            if (0 < $userId) {
                $cnd = $srch->addCondition('shipprofile_user_id', '=', 0);
                $cnd->attachCondition('shipprofile_user_id', '=', $userId);
            }
        }

        $rs = $srch->getResultSet();

        $res = [];
        if ($byId) {
            $res = $this->db->fetchAllAssoc($rs);
        } else {
            while ($row = $this->db->fetch($rs)) {
                $res[$row['shipprofile_identifier']][$row['shipprofile_user_id']] = $row['shipprofile_id'];
            }
        }

        return $res;
    }

    public function getTaxCategoriesArr($byId = true, $taxCatIdOrIdentifier = false)
    {
        $srch = Tax::getSearchObject(false, false);
        $srch->doNotCalculateRecords();

        if ($taxCatIdOrIdentifier) {
            $srch->setPageSize(1);
        } else {
            $srch->doNotLimitRecords();
        }

        if ($byId) {
            $srch->addMultipleFields(array('taxcat_id', 'taxcat_identifier'));
            if ($taxCatIdOrIdentifier) {
                $srch->addCondition('taxcat_id', '=', $taxCatIdOrIdentifier);
            }
        } else {
            $srch->addMultipleFields(array('taxcat_identifier', 'taxcat_id'));
            if ($taxCatIdOrIdentifier) {
                $srch->addCondition('taxcat_identifier', '=', $taxCatIdOrIdentifier);
            }
        }
        $rs = $srch->getResultSet();
        return $this->db->fetchAllAssoc($rs);
    }

    public function getCountriesAssocArr($byId = true, $countryIdOrCode = false)
    {
        $srch = Countries::getSearchObject(false, false);
        $srch->doNotCalculateRecords();

        if ($countryIdOrCode) {
            $srch->setPageSize(1);
        } else {
            $srch->doNotLimitRecords();
        }

        if ($byId) {
            $srch->addMultipleFields(array('country_id', 'country_code'));
            if ($countryIdOrCode) {
                $srch->addCondition('country_id', '=', $countryIdOrCode);
            }
        } else {
            $srch->addMultipleFields(array('country_code', 'country_id'));
            if ($countryIdOrCode) {
                $srch->addCondition('country_code', '=', $countryIdOrCode);
            }
        }
        $rs = $srch->getResultSet();
        return $this->db->fetchAllAssoc($rs);
    }

    public function getProductCategoriesByProductId($productId, $byId = true)
    {
        $srch = new SearchBase(Product::DB_TBL_PRODUCT_TO_CATEGORY, 'ptc');
        $srch->addCondition(Product::DB_TBL_PRODUCT_TO_CATEGORY_PREFIX . 'product_id', '=', $productId);

        $srch->joinTable(ProductCategory::DB_TBL, 'INNER JOIN', ProductCategory::DB_TBL_PREFIX . 'id = ptc.' . Product::DB_TBL_PRODUCT_TO_CATEGORY_PREFIX . 'prodcat_id', 'cat');
        if ($byId) {
            $srch->addMultipleFields(array(ProductCategory::DB_TBL_PREFIX . 'id', ProductCategory::DB_TBL_PREFIX . 'identifier'));
        } else {
            $srch->addMultipleFields(array(ProductCategory::DB_TBL_PREFIX . 'identifier', ProductCategory::DB_TBL_PREFIX . 'id'));
        }
        $rs = $srch->getResultSet();
        $records = $this->db->fetchAllAssoc($rs);
        if (!$records) {
            return false;
        }
        return $records;
    }

    public function getAllOptions($byId = true, $optionIdOrIdentifier = false)
    {
        $srch = Option::getSearchObject(false, false);
        $srch->doNotCalculateRecords();
        if ($byId) {
            $srch->addMultipleFields(array('option_id', 'option_identifier'));
            if ($optionIdOrIdentifier) {
                $srch->setPageSize(1);
                $srch->addCondition('option_id', '=', $optionIdOrIdentifier);
            } else {
                $srch->doNotLimitRecords();
            }
        } else {
            $srch->addMultipleFields(array('option_identifier', 'option_id'));
            if ($optionIdOrIdentifier) {
                $srch->setPageSize(1);
                $srch->addCondition('option_identifier', '=', $optionIdOrIdentifier);
            } else {
                $srch->doNotLimitRecords();
            }
        }
        $rs = $srch->getResultSet();
        return $this->db->fetchAllAssoc($rs);
    }

    public function getAllOptionValues($optionId, $byId = true, $optionValueIdOrIdentifier = false)
    {
        $optionId = FatUtility::convertToType($optionId, FatUtility::VAR_INT);
        $srch = OptionValue::getSearchObject();
        $srch->addCondition('ov.optionvalue_option_id', '=', $optionId);
        $srch->doNotCalculateRecords();
        if ($byId) {
            $srch->addMultipleFields(array('optionvalue_id', 'optionvalue_identifier'));
            if ($optionValueIdOrIdentifier) {
                $srch->setPageSize(1);
                $srch->addCondition('optionvalue_id', '=', $optionValueIdOrIdentifier);
            } else {
                $srch->doNotLimitRecords();
            }
        } else {
            $srch->addMultipleFields(array('optionvalue_identifier', 'optionvalue_id'));
            if ($optionValueIdOrIdentifier) {
                $srch->setPageSize(1);
                $srch->addCondition('optionvalue_identifier', '=', $optionValueIdOrIdentifier);
            } else {
                $srch->doNotLimitRecords();
            }
        }
        $rs = $srch->getResultSet();
        return $this->db->fetchAllAssoc($rs);
    }

    public function getAllTags($byId = true, $tagIdOrIdentifier = false)
    {
        $srch = Tag::getSearchObject();
        $srch->doNotCalculateRecords();
        if ($byId) {
            $srch->addMultipleFields(array('tag_id', 'tag_identifier'));
            if ($tagIdOrIdentifier) {
                $srch->setPageSize(1);
                $srch->addCondition('tag_id', '=', $tagIdOrIdentifier);
            } else {
                $srch->doNotLimitRecords();
            }
        } else {
            $srch->addMultipleFields(array('tag_identifier', 'tag_id'));
            if ($tagIdOrIdentifier) {
                $srch->setPageSize(1);
                $srch->addCondition('tag_identifier', '=', $tagIdOrIdentifier);
            } else {
                $srch->doNotLimitRecords();
            }
        }
        $rs = $srch->getResultSet();
        return $this->db->fetchAllAssoc($rs);
    }

    public function getAllShippingCompany($byId = true, $scompanyIdOrIdentifier = false)
    {
        $srch = ShippingCompanies::getSearchObject(false);
        $srch->doNotCalculateRecords();
        if ($byId) {
            $srch->addMultipleFields(array('scompany_id', 'scompany_identifier'));
            if ($scompanyIdOrIdentifier) {
                $srch->setPageSize(1);
                $srch->addCondition('scompany_id', '=', $scompanyIdOrIdentifier);
            } else {
                $srch->doNotLimitRecords();
            }
        } else {
            $srch->addMultipleFields(array('scompany_identifier', 'scompany_id'));
            if ($scompanyIdOrIdentifier) {
                $srch->setPageSize(1);
                $srch->addCondition('scompany_identifier', '=', $scompanyIdOrIdentifier);
            } else {
                $srch->doNotLimitRecords();
            }
        }
        $rs = $srch->getResultSet();
        return $this->db->fetchAllAssoc($rs);
    }

    public function getAllShippingDurations($byId = true, $durationIdOrIdentifier = false)
    {
        $srch = ShippingDurations::getSearchObject(false, false);
        $srch->doNotCalculateRecords();
        if ($byId) {
            $srch->addMultipleFields(array('sduration_id', 'sduration_identifier'));
            if ($durationIdOrIdentifier) {
                $srch->setPageSize(1);
                $srch->addCondition('sduration_id', '=', $durationIdOrIdentifier);
            } else {
                $srch->doNotLimitRecords();
            }
        } else {
            $srch->addMultipleFields(array('sduration_identifier', 'sduration_id'));
            if ($durationIdOrIdentifier) {
                $srch->setPageSize(1);
                $srch->addCondition('sduration_identifier', '=', $durationIdOrIdentifier);
            } else {
                $srch->doNotLimitRecords();
            }
        }
        $rs = $srch->getResultSet();
        return $this->db->fetchAllAssoc($rs);
    }

    public function getAllPrivacyPoints($byId = true, $policyPointIdOrIdentifier = false)
    {
        $srch = PolicyPoint::getSearchObject(false, false);
        $srch->doNotCalculateRecords();
        if ($byId) {
            $srch->addMultipleFields(array('ppoint_id', 'ppoint_identifier'));
            if ($policyPointIdOrIdentifier) {
                $srch->setPageSize(1);
                $srch->addCondition('ppoint_id', '=', $policyPointIdOrIdentifier);
            } else {
                $srch->doNotLimitRecords();
            }
        } else {
            $srch->addMultipleFields(array('ppoint_identifier', 'ppoint_id'));
            if ($policyPointIdOrIdentifier) {
                $srch->setPageSize(1);
                $srch->addCondition('ppoint_identifier', '=', $policyPointIdOrIdentifier);
            } else {
                $srch->doNotLimitRecords();
            }
        }
        $rs = $srch->getResultSet();
        return $this->db->fetchAllAssoc($rs);
    }

    public function getProductIdByTempId($tempId, $userId = 0)
    {
        $srch = new SearchBase(Importexport::DB_TBL_TEMP_PRODUCT_IDS, 't');
        $srch->doNotCalculateRecords();
        $srch->setPageSize(1);
        $srch->addCondition('pti_product_temp_id', '=', $tempId);
        $srch->addCondition('pti_user_id', '=', $userId);
        $rs = $srch->getResultSet();
        $row = $this->db->fetch($rs);
        if (!is_array($row)) {
            return false;
        }
        return $row;
    }

    public function getCheckAndSetProductIdByTempId($sellerTempId, $userId)
    {
        $productId = 0;
        $userTempIdData = $this->getProductIdByTempId($sellerTempId, $userId);

        if (!empty($userTempIdData) && $userTempIdData['pti_product_temp_id'] == $sellerTempId) {
            $productId = $userTempIdData['pti_product_id'];
        } else {
            $row = Product::getAttributesById($sellerTempId, array('product_id', 'product_seller_id'));

            if (!empty($row) && $row['product_seller_id'] == $userId) {
                $productId = $row['product_id'];
                $tempData = array(
                    'pti_product_id' => $productId,
                    'pti_product_temp_id' => $sellerTempId,
                    'pti_user_id' => $userId,
                );
                $this->db->deleteRecords(Importexport::DB_TBL_TEMP_PRODUCT_IDS, array('smt' => 'pti_product_id = ? and pti_user_id = ?', 'vals' => array($productId, $userId)));
                $this->db->insertFromArray(Importexport::DB_TBL_TEMP_PRODUCT_IDS, $tempData, false, array(), $tempData);
            }
        }
        return     $productId;
    }

    public function getTempSelProdIdByTempId($tempId, $userId = 0)
    {
        $srch = new SearchBase(Importexport::DB_TBL_TEMP_SELPROD_IDS, 't');
        $srch->doNotCalculateRecords();
        $srch->setPageSize(1);
        $srch->addCondition('spti_selprod_temp_id', '=', $tempId);
        $srch->addCondition('spti_user_id', '=', $userId);
        $rs = $srch->getResultSet();
        $row = $this->db->fetch($rs);
        if (!is_array($row)) {
            return false;
        }
        return $row;
    }

    public function getCheckAndSetSelProdIdByTempId($sellerTempId, $userId)
    {
        $selprodId = 0;
        $userTempIdData = $this->getTempSelProdIdByTempId($sellerTempId, $userId);
        if (!empty($userTempIdData) && $userTempIdData['spti_selprod_temp_id'] == $sellerTempId) {
            $selprodId = $userTempIdData['spti_selprod_id'];
        } else {
            $row = SellerProduct::getAttributesById($sellerTempId, array('selprod_id', 'selprod_user_id'));
            if (!empty($row) && $row['selprod_user_id'] == $userId) {
                $selprodId = $row['selprod_id'];
                $tempData = array(
                    'spti_selprod_id' => $selprodId,
                    'spti_selprod_temp_id' => $sellerTempId,
                    'spti_user_id' => $userId,
                );
                $this->db->deleteRecords(Importexport::DB_TBL_TEMP_SELPROD_IDS, array('smt' => 'spti_selprod_id = ? and spti_user_id = ?', 'vals' => array($selprodId, $userId)));
                $this->db->insertFromArray(Importexport::DB_TBL_TEMP_SELPROD_IDS, $tempData, false, array(), $tempData);
            }
        }
        return     $selprodId;
    }

    public function getProductInventoryColumnArr($langId)
    {
        return array(
            Labels::getLabel("LBL_SELLER_PRODUCT_ID", $langId),
            Labels::getLabel("LBL_SKU", $langId),
            Labels::getLabel("LBL_PRODUCT", $langId),
            Labels::getLabel("LBL_COST_PRICE", $langId),
            Labels::getLabel("LBL_PRICE", $langId),
            Labels::getLabel("LBL_STOCK/QUANTITY", $langId)
        );
    }

    public function setPluginId(int $pluginId)
    {
        $this->pluginId = $pluginId;
    }
}
