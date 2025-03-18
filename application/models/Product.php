<?php

class Product extends MyAppModel
{
    public const DB_TBL = 'tbl_products';
    public const DB_TBL_LANG = 'tbl_products_lang';
    public const DB_TBL_PREFIX = 'product_';
    public const DB_TBL_LANG_PREFIX = 'productlang_';

    public const DB_NUMERIC_ATTRIBUTES_TBL = 'tbl_product_numeric_attributes';
    public const DB_NUMERIC_ATTRIBUTES_PREFIX = 'prodnumattr_';

    public const DB_TEXT_ATTRIBUTES_TBL = 'tbl_product_text_attributes';
    public const DB_TEXT_ATTRIBUTES_PREFIX = 'prodtxtattr_';

    public const DB_TBL_PRODUCT_TO_CATEGORY = 'tbl_product_to_category';
    public const DB_TBL_PRODUCT_TO_CATEGORY_PREFIX = 'ptc_';

    public const DB_PRODUCT_TO_OPTION = 'tbl_product_to_options';
    public const DB_PRODUCT_TO_OPTION_PREFIX = 'prodoption_';

    public const DB_PRODUCT_TO_SHIP = 'tbl_product_shipping_rates';
    public const DB_PRODUCT_TO_SHIP_PREFIX = 'pship_';

    public const DB_PRODUCT_TO_TAG = 'tbl_product_to_tags';
    public const DB_PRODUCT_TO_TAG_PREFIX = 'ptt_';

    public const DB_TBL_PRODUCT_FAVORITE = 'tbl_user_favourite_products';

    public const DB_PRODUCT_SPECIFICATION = 'tbl_product_specifications';
    public const DB_PRODUCT_SPECIFICATION_PREFIX = 'prodspec_';

    public const DB_PRODUCT_LANG_SPECIFICATION = 'tbl_product_specifications_lang';
    public const DB_PRODUCT_LANG_SPECIFICATION_PREFIX = 'prodspeclang_';

    public const DB_TBL_PRODUCT_SHIPPING = 'tbl_products_shipping';
    public const DB_TBL_PRODUCT_SHIPPING_PREFIX = 'ps_';

    public const DB_PRODUCT_SHIPPED_BY_SELLER = 'tbl_products_shipped_by_seller';
    public const DB_PRODUCT_SHIPPED_BY_SELLER_PREFIX = 'psbs_';

    public const DB_PRODUCT_MIN_PRICE = 'tbl_products_min_price';
    public const DB_PRODUCT_MIN_PRICE_PREFIX = 'pmp_';

    public const DB_PRODUCT_EXTERNAL_RELATIONS = 'tbl_product_external_relations';
    public const DB_PRODUCT_EXTERNAL_RELATIONS_PREFIX = 'perel_';

    public const DB_PRODUCT_TO_PLUGIN_PRODUCT = 'tbl_products_to_plugin_product';
    public const DB_PRODUCT_TO_PLUGIN_PRODUCT_PREFIX = 'ptpp_';

    public const PRODUCT_TYPE_PHYSICAL = 1;
    public const PRODUCT_TYPE_DIGITAL = 2;
    public const PRODUCT_TYPE_SERVICE = 3;

    public const APPROVED = 1;
    public const UNAPPROVED = 0;

    public const INVENTORY_TRACK = 1;
    public const INVENTORY_NOT_TRACK = 0;

    public const CONDITION_NEW = 1;
    public const CONDITION_USED = 2;
    public const CONDITION_REFURBISH = 3;

    public const PRODUCT_VIEW_ORGINAL_URL = 'products/view/';
    public const PRODUCT_REVIEWS_ORGINAL_URL = 'reviews/product/';
    public const PRODUCT_MORE_SELLERS_ORGINAL_URL = 'products/sellers/';

    public static $optionValueName = '';

    public const CATALOG_TYPE_PRIMARY = 0;
    public const CATALOG_TYPE_REQUEST = 1;
    public const CATALOG_TYPE_INVENTORY = 2;

    /* For API */
    public const FILTER_POSITION_DEFAULT = 0;
    public const FILTER_POSITION_ALTERNATE = 1;

    public const FILTER_TYPE_CATEGORY = 1;
    public const FILTER_TYPE_BRAND = 2;
    public const FILTER_TYPE_OPTION = 3;
    public const FILTER_TYPE_SORT_BY = 4;
    public const FILTER_TYPE_PRICE = 5;
    public const FILTER_TYPE_CONDITION = 6;
    public const FILTER_TYPE_AVAILABILITY = 7;

    /* Used in products/view API response */
    public const CONTENT_TYPE_PRODUCT = 1;
    public const CONTENT_TYPE_PRODUCT_IMAGES = 2;
    public const CONTENT_TYPE_OPTIONS = 3;
    public const CONTENT_TYPE_SPECIFICATIONS = 4;
    public const CONTENT_TYPE_VOLUME_DISCOUNT = 5;
    public const CONTENT_TYPE_BUY_TOGETHER = 6;
    public const CONTENT_TYPE_RELATED_PRODUCTS = 7;
    public const CONTENT_TYPE_RECOMMENDED_PRODUCTS = 8;
    public const CONTENT_TYPE_REVIEWS = 9;
    public const CONTENT_TYPE_BANNERS = 10;
    public const CONTENT_TYPE_RECENTLY_VIEWED = 12;
    public const CONTENT_TYPE_SHOP = 13;
    public const CONTENT_TYPE_PRODUCT_POLICIES = 14;
    public const CONTENT_TYPE_PRODUCT_DESCRIPTION = 15;
    public const CONTENT_TYPE_DIGITAL_FILES_AND_LINKS = 16;
    /* ------------------------------------------- */

    /* For API */

    public const WARRANTY_TYPE_DAY = 0;
    public const WARRANTY_TYPE_MONTH = 1;
    public const WARRANTY_TYPE_YEAR = 2;

    public const VIEW_MORE_SELLER_COUNT = 2;

    public $prodSpecId = 0;

    public function __construct($id = 0)
    {
        parent::__construct(static::DB_TBL, static::DB_TBL_PREFIX . 'id', $id);
    }

    public static function getSearchObject($langId = 0, $isDeleted = true, $joinSpecifics = false)
    {
        $srch = new SearchBase(static::DB_TBL, 'tp');

        if ($langId > 0) {
            $srch->joinTable(
                static::DB_TBL_LANG,
                'LEFT OUTER JOIN',
                'productlang_product_id = tp.product_id	AND productlang_lang_id = ' . $langId,
                'tp_l'
            );
        }

        if ($isDeleted) {
            $srch->addCondition(static::DB_TBL_PREFIX . 'deleted', '=', 'mysql_func_' . applicationConstants::NO, 'AND', true);
        }

        if (true === $joinSpecifics) {
            $srch->joinTable(
                ProductSpecifics::DB_TBL,
                'LEFT OUTER JOIN',
                'psp.' . ProductSpecifics::DB_TBL_PREFIX . 'product_id = tp.' . static::tblFld('id'),
                'psp'
            );
        }

        //$srch->addOrder(static::DB_TBL_PREFIX . 'active', 'DESC');
        return $srch;
    }

    public static function requiredFields($prodType = Product::PRODUCT_TYPE_PHYSICAL)
    {
        $arr = array(
            ImportexportCommon::VALIDATE_POSITIVE_INT => array(
                'product_id',
                'category_Id',
                'tax_category_id',
            ),
            ImportexportCommon::VALIDATE_FLOAT => array(
                'product_min_selling_price',
            ),
            ImportexportCommon::VALIDATE_NOT_NULL => array(
                'product_name',
                'product_identifier',
                'credential_username',
                'category_indentifier',
                'product_type_identifier',
                'tax_category_identifier'
            ),
            ImportexportCommon::VALIDATE_INT => array(
                'product_seller_id',
                'product_type',
                'product_ship_free',
            ),
        );

        if (FatApp::getConfig('CONF_PRODUCT_BRAND_MANDATORY', FatUtility::VAR_INT, 1)) {
            $arr[ImportexportCommon::VALIDATE_POSITIVE_INT][] = 'product_brand_id';
            $arr[ImportexportCommon::VALIDATE_NOT_NULL][] = 'brand_identifier';
        }

        if (FatApp::getConfig('CONF_PRODUCT_DIMENSIONS_ENABLE', FatUtility::VAR_INT, 0) && $prodType == Product::PRODUCT_TYPE_PHYSICAL) {
            $physical = array(
                'product_dimension_unit_identifier',
                'product_length',
                'product_width',
                'product_height',
            );
            $arr[ImportexportCommon::VALIDATE_NOT_NULL] = array_merge($arr[ImportexportCommon::VALIDATE_NOT_NULL], $physical);
        }

        if (FatApp::getConfig('CONF_PRODUCT_WEIGHT_ENABLE', FatUtility::VAR_INT, 0) && $prodType == Product::PRODUCT_TYPE_PHYSICAL) {
            $physical = array(
                'product_weight_unit_identifier',
                'product_weight',
            );
            $arr[ImportexportCommon::VALIDATE_NOT_NULL] = array_merge($arr[ImportexportCommon::VALIDATE_NOT_NULL], $physical);
        }

        if (Product::PRODUCT_TYPE_SERVICE != $prodType && FatApp::getConfig('CONF_PRODUCT_MODEL_MANDATORY', FatUtility::VAR_INT, 0)) {
            $physical = array(
                'product_model',
            );
            $arr[ImportexportCommon::VALIDATE_NOT_NULL] = array_merge($arr[ImportexportCommon::VALIDATE_NOT_NULL], $physical);
        }

        return $arr;
    }

    public static function validateFields($columnIndex, $columnTitle, $columnValue, $langId, $prodType = Product::PRODUCT_TYPE_PHYSICAL)
    {
        $requiredFields = static::requiredFields($prodType);
        return ImportexportCommon::validateFields($requiredFields, $columnIndex, $columnTitle, $columnValue, $langId);
    }

    public static function requiredMediaFields()
    {
        return array(
            ImportexportCommon::VALIDATE_POSITIVE_INT => array(
                'product_id',
            ),
            ImportexportCommon::VALIDATE_NOT_NULL => array(
                'product_identifier',
                'afile_physical_path',
                'afile_name',
            ),
        );
    }

    public static function validateMediaFields($columnIndex, $columnTitle, $columnValue, $langId)
    {
        $requiredFields = static::requiredMediaFields();
        return ImportexportCommon::validateFields($requiredFields, $columnIndex, $columnTitle, $columnValue, $langId);
    }

    public static function requiredShippingFields()
    {
        return array(
            ImportexportCommon::VALIDATE_POSITIVE_INT => array(
                'product_id',
                'country_id',
                'scompany_id',
                'sduration_id',
                'pship_charges',
            ),
            ImportexportCommon::VALIDATE_NOT_NULL => array(
                'product_identifier',
                'credential_username',
                'scompany_identifier',
                'sduration_identifier',
                'user_id',
            ),
        );
    }

    public static function validateShippingFields($columnIndex, $columnTitle, $columnValue, $langId)
    {
        $requiredFields = static::requiredShippingFields();
        return ImportexportCommon::validateFields($requiredFields, $columnIndex, $columnTitle, $columnValue, $langId);
    }

    public static function getApproveUnApproveArr($langId)
    {
        $langId = FatUtility::int($langId);
        if ($langId < 1) {
            $langId = FatApp::getConfig('CONF_ADMIN_DEFAULT_LANG');
        }

        return array(
            static::UNAPPROVED => Labels::getLabel('LBL_UN-APPROVED', $langId),
            static::APPROVED => Labels::getLabel('LBL_APPROVED', $langId),
        );
    }

    public static function getStatusClassArr()
    {
        return array(
            static::APPROVED => applicationConstants::CLASS_SUCCESS,
            static::UNAPPROVED => applicationConstants::CLASS_DANGER
        );
    }

    public static function getInventoryTrackArr($langId)
    {
        $langId = FatUtility::int($langId);
        if ($langId < 1) {
            $langId = FatApp::getConfig('CONF_ADMIN_DEFAULT_LANG');
        }

        return array(
            static::INVENTORY_TRACK => Labels::getLabel('LBL_TRACK', $langId),
            static::INVENTORY_NOT_TRACK => Labels::getLabel('LBL_DO_NOT_TRACK', $langId)
        );
    }

    public static function getConditionArr($langId)
    {
        $langId = FatUtility::int($langId);
        if ($langId < 1) {
            $langId = FatApp::getConfig('CONF_ADMIN_DEFAULT_LANG');
        }

        return array(
            static::CONDITION_NEW => Labels::getLabel('LBL_NEW', $langId),
            static::CONDITION_USED => Labels::getLabel('LBL_USED', $langId),
            static::CONDITION_REFURBISH => Labels::getLabel('LBL_REFURBISHED', $langId)
        );
    }

    public static function getProductTypes($langId = 0)
    {
        $langId = FatUtility::convertToType($langId, FatUtility::VAR_INT);
        if (!$langId) {
            trigger_error(Labels::getLabel("ERR_ARGUMENTS_NOT_SPECIFIED.", $langId), E_USER_ERROR);
            return false;
        }
        return array(
            self::PRODUCT_TYPE_PHYSICAL => Labels::getLabel('LBL_PHYSICAL', $langId),
            self::PRODUCT_TYPE_DIGITAL => Labels::getLabel('LBL_DIGITAL', $langId),
            self::PRODUCT_TYPE_SERVICE => Labels::getLabel('LBL_SERVICE', $langId),
        );
    }

    public static function getWarrantyUnits(int $langId): array
    {
        return array(
            self::WARRANTY_TYPE_DAY => Labels::getLabel('LBL_DAYS', $langId),
            self::WARRANTY_TYPE_MONTH => Labels::getLabel('LBL_MONTHS', $langId),
            self::WARRANTY_TYPE_YEAR => Labels::getLabel('LBL_YEARS', $langId)
        );
    }

    public static function getAttributesById($recordId, $attr = null, $joinSpecifics = false)
    {
        $recordId = FatUtility::int($recordId);

        $db = FatApp::getDb();

        $srch = new SearchBase(static::DB_TBL, 'p');
        $srch->doNotCalculateRecords();
        $srch->setPageSize(1);
        $srch->addCondition(static::tblFld('id'), '=', 'mysql_func_' . $recordId, 'AND', true);

        if (true === $joinSpecifics) {
            $srch->joinTable(
                ProductSpecifics::DB_TBL,
                'LEFT OUTER JOIN',
                'ps.' . ProductSpecifics::DB_TBL_PREFIX . 'product_id = p.' . static::tblFld('id'),
                'ps'
            );
        }

        if (null != $attr) {
            if (is_array($attr)) {
                $srch->addMultipleFields($attr);
            } elseif (is_string($attr)) {
                $srch->addFld($attr);
            }
        }
        $rs = $srch->getResultSet();
        $row = $db->fetch($rs);
        if (!is_array($row)) {
            return false;
        }

        /* get Numeric attributes data[ */
        if (!$attr) {
            $num_attr_row = static::getProductNumericAttributes($recordId);
            if (!empty($num_attr_row)) {
                $row = array_merge($row, $num_attr_row);
            }
        }
        /* ] */

        if (is_string($attr)) {
            return $row[$attr];
        }
        return $row;
    }

    public static function getProductDataById($langId = 0, $productId = 0, $attr = array())
    {
        $productId = FatUtility::int($productId);
        $srch = self::getSearchObject($langId);
        $srch->addCondition('product_id', '=', 'mysql_func_' . $productId, 'AND', true);
        $srch->doNotLimitRecords(true);
        $srch->doNotCalculateRecords(true);
        if (null != $attr) {
            if (is_array($attr)) {
                $srch->addMultipleFields($attr);
            } elseif (is_string($attr)) {
                $srch->addFld($attr);
            }
        }
        $row = FatApp::getDb()->fetch($srch->getResultSet());
        return (is_array($row) ? $row : []);
    }

    public function deleteProductImage($productId, $imageId, $fileType)
    {
        $productId = FatUtility::int($productId);
        $imageId = FatUtility::int($imageId);
        $fileType = FatUtility::int($fileType);
        if (1 > $productId || 1 > $imageId || 1 >  $fileType) {
            $this->error = Labels::getLabel('ERR_INVALID_REQUEST', $this->commonLangId);
            return false;
        }

        if ($fileType == AttachedFile::FILETYPE_PRODUCT_IMAGE_TEMP) {
            $fileHandlerObj = new AttachedFileTemp();
        } else {
            $fileHandlerObj = new AttachedFile();
            $fileType = AttachedFile::FILETYPE_PRODUCT_IMAGE;
        }

        if (!$fileHandlerObj->deleteFile($fileType, $productId, $imageId)) {
            $this->error = $fileHandlerObj->getError();
            return false;
        }
        return true;
    }

    public function updateProdImagesOrder($product_id, $fileType, $order)
    {
        $product_id = FatUtility::int($product_id);
        if (is_array($order) && sizeof($order) > 0) {
            foreach ($order as $i => $id) {
                if (FatUtility::int($id) < 1) {
                    continue;
                }
                if ($fileType == AttachedFile::FILETYPE_PRODUCT_IMAGE_TEMP) {
                    FatApp::getDb()->updateFromArray('tbl_attached_files_temp', array('afile_display_order' => $i), array('smt' => 'afile_type = ? AND afile_record_id = ? AND afile_id = ?', 'vals' => array($fileType, $product_id, $id)));
                } else {
                    FatApp::getDb()->updateFromArray('tbl_attached_files', array('afile_display_order' => $i), array('smt' => 'afile_type = ? AND afile_record_id = ? AND afile_id = ?', 'vals' => array(AttachedFile::FILETYPE_PRODUCT_IMAGE, $product_id, $id)));
                }
            }
            return true;
        }
        return false;
    }

    public function addUpdateProductCategories($product_id, $categories = array())
    {
        if (!$product_id) {
            $this->error = Labels::getLabel('ERR_INVALID_REQUEST', $this->commonLangId);
            return false;
        }

        FatApp::getDb()->deleteRecords(static::DB_TBL_PRODUCT_TO_CATEGORY, array('smt' => static::DB_TBL_PRODUCT_TO_CATEGORY_PREFIX . 'product_id = ?', 'vals' => array($product_id)));
        if (empty($categories)) {
            return true;
        }

        $record = new TableRecord(static::DB_TBL_PRODUCT_TO_CATEGORY);
        foreach ($categories as $category_id) {
            $to_save_arr = array();
            $to_save_arr['ptc_product_id'] = $product_id;
            $to_save_arr['ptc_prodcat_id'] = $category_id;
            $record->assignValues($to_save_arr);
            if (!$record->addNew(array(), $to_save_arr)) {
                $this->error = $record->getError();
                return false;
            }
        }
        return true;
    }

    public function addUpdateProductOption($optionId, $optionValuesIds)
    {
        $optionId = FatUtility::int($optionId);
        if (!$this->mainTableRecordId || !$optionId) {
            $this->error = Labels::getLabel('ERR_INVALID_REQUEST', $this->commonLangId);
            return false;
        }
        $record = new TableRecord(static::DB_PRODUCT_TO_OPTION);
        $to_save_arr = array();
        $to_save_arr[static::DB_PRODUCT_TO_OPTION_PREFIX . 'product_id'] = $this->mainTableRecordId;
        $to_save_arr[static::DB_PRODUCT_TO_OPTION_PREFIX . 'option_id'] = $optionId;
        $to_save_arr[static::DB_PRODUCT_TO_OPTION_PREFIX . 'optionvalue_ids'] = $optionValuesIds;
        $record->assignValues($to_save_arr);
        if (!$record->addNew(array(), $to_save_arr)) {
            $this->error = $record->getError();
            return false;
        }
        $this->logUpdatedRecord();
        return true;
    }

    public function removeProductOption($option_id)
    {
        $db = FatApp::getDb();
        $option_id = FatUtility::int($option_id);
        if (!$this->mainTableRecordId || !$option_id) {
            $this->error = Labels::getLabel('ERR_INVALID_REQUEST', $this->commonLangId);
            return false;
        }
        if (!$db->deleteRecords(static::DB_PRODUCT_TO_OPTION, array('smt' => static::DB_PRODUCT_TO_OPTION_PREFIX . 'product_id = ? AND ' . static::DB_PRODUCT_TO_OPTION_PREFIX . 'option_id = ?', 'vals' => array($this->mainTableRecordId, $option_id)))) {
            $this->error = $db->getError();
            return false;
        }
        $this->logUpdatedRecord();
        return true;
    }

    public function addUpdateProductTag($tag_id)
    {
        $tag_id = FatUtility::int($tag_id);
        if (!$this->mainTableRecordId || !$tag_id) {
            $this->error = Labels::getLabel('ERR_INVALID_REQUEST', $this->commonLangId);
            return false;
        }
        $record = new TableRecord(static::DB_PRODUCT_TO_TAG);
        $to_save_arr = array();
        $to_save_arr[static::DB_PRODUCT_TO_TAG_PREFIX . 'product_id'] = $this->mainTableRecordId;
        $to_save_arr[static::DB_PRODUCT_TO_TAG_PREFIX . 'tag_id'] = $tag_id;
        $record->assignValues($to_save_arr);
        if (!$record->addNew(array(), $to_save_arr)) {
            $this->error = $record->getError();
            return false;
        }
        $this->logUpdatedRecord();
        return true;
    }

    public function addUpdateProductTags($tags = array())
    {
        if (!$this->mainTableRecordId) {
            $this->error = Labels::getLabel('ERR_INVALID_REQUEST', $this->commonLangId);
            return false;
        }

        FatApp::getDb()->deleteRecords(static::DB_PRODUCT_TO_TAG, array('smt' => static::DB_PRODUCT_TO_TAG_PREFIX . 'product_id = ?', 'vals' => array($this->mainTableRecordId)));
        if (empty($tags)) {
            return true;
        }

        foreach ($tags as $tag_id) {
            if (!$this->addUpdateProductTag($tag_id)) {
                return false;
            }
        }
        return true;
    }

    public function removeProductTag($tag_id)
    {
        $db = FatApp::getDb();
        $tag_id = FatUtility::int($tag_id);
        if (!$this->mainTableRecordId || !$tag_id) {
            $this->error = Labels::getLabel('ERR_INVALID_REQUEST', $this->commonLangId);
            return false;
        }
        if (!$db->deleteRecords(static::DB_PRODUCT_TO_TAG, array('smt' => static::DB_PRODUCT_TO_TAG_PREFIX . 'product_id = ? AND ' . static::DB_PRODUCT_TO_TAG_PREFIX . 'tag_id = ?', 'vals' => array($this->mainTableRecordId, $tag_id)))) {
            $this->error = $db->getError();
            return false;
        }
        $this->logUpdatedRecord();
        return true;
    }

    public static function getProductShippingRates($product_id, $lang_id, $country_id = 0, $sellerId = 0, $limit = 0)
    {
        $product_id = FatUtility::convertToType($product_id, FatUtility::VAR_INT);
        $sellerId = FatUtility::convertToType($sellerId, FatUtility::VAR_INT);
        $lang_id = FatUtility::convertToType($lang_id, FatUtility::VAR_INT);
        if (!$product_id || !$lang_id) {
            //trigger_error(Labels::getLabel("ERR_Arguments_not_specified.",$this->commonLangId), E_USER_ERROR);
            return false;
        }
        $srch = new SearchBase(static::DB_PRODUCT_TO_SHIP, 'tpsr');
        $srch->joinTable(Countries::DB_TBL_LANG, 'LEFT JOIN', 'tpsr.' . static::DB_PRODUCT_TO_SHIP_PREFIX . 'country=tc.' . Countries::DB_TBL_LANG_PREFIX . 'country_id and tc.' . Countries::DB_TBL_LANG_PREFIX . 'lang_id=' . $lang_id, 'tc');
        $srch->joinTable(ShippingCompanies::DB_TBL, 'LEFT JOIN', 'tpsr.pship_company=sc.scompany_id ', 'sc');
        $srch->joinTable(ShippingCompanies::DB_TBL_LANG, 'LEFT JOIN', 'tpsr.pship_company=tsc.scompanylang_scompany_id and tsc.' . ShippingCompanies::DB_TBL_LANG_PREFIX . 'lang_id=' . $lang_id, 'tsc');
        $srch->joinTable(ShippingDurations::DB_TBL_LANG, 'LEFT JOIN', 'tpsr.pship_duration=tsd.sdurationlang_sduration_id  and tsd.' . ShippingDurations::DB_TBL_PREFIX_LANG . 'lang_id=' . $lang_id, 'tsd');
        $srch->joinTable(ShippingDurations::DB_TBL, 'LEFT JOIN', 'tpsr.pship_duration=ts.sduration_id and sduration_deleted =0 ', 'ts');
        $srch->addCondition('tpsr.' . static::DB_PRODUCT_TO_SHIP_PREFIX . 'prod_id', '=', 'mysql_func_' . $product_id, 'AND', true);
        if ($country_id > 0) {
            $srch->addDirectCondition('( tpsr.' . static::DB_PRODUCT_TO_SHIP_PREFIX . 'country =' . FatUtility::int($country_id) . ' OR ' . 'tpsr.' . static::DB_PRODUCT_TO_SHIP_PREFIX . 'country =-1 )');
        }
        $srch->addCondition('tpsr.' . static::DB_PRODUCT_TO_SHIP_PREFIX . 'user_id', '=', 'mysql_func_' . $sellerId, 'AND', true);

        $srch->addOrder('(tpsr.' . static::DB_PRODUCT_TO_SHIP_PREFIX . 'country = -1),country_name');
        $srch->addMultipleFields(
            array(
                static::DB_PRODUCT_TO_SHIP_PREFIX . 'id',
                static::DB_PRODUCT_TO_SHIP_PREFIX . 'country',
                static::DB_PRODUCT_TO_SHIP_PREFIX . 'user_id',
                static::DB_PRODUCT_TO_SHIP_PREFIX . 'company',
                static::DB_PRODUCT_TO_SHIP_PREFIX . 'duration',
                static::DB_PRODUCT_TO_SHIP_PREFIX . 'charges',
                static::DB_PRODUCT_TO_SHIP_PREFIX . 'additional_charges',
                'IFNULL(' . Countries::DB_TBL_PREFIX . 'name',
                '\'' . Labels::getLabel('LBL_EVERYWHERE_ELSE', $lang_id) . '\') as country_name',
                'ifNull(' . ShippingCompanies::DB_TBL_PREFIX . 'name',
                ShippingCompanies::DB_TBL_PREFIX . 'identifier) as ' . ShippingCompanies::DB_TBL_PREFIX . 'name',
                ShippingCompanies::DB_TBL_PREFIX . 'id',
                ShippingCompanies::DB_TBL_LANG_PREFIX . 'scompany_id',
                ShippingDurations::DB_TBL_PREFIX . 'name',
                ShippingDurations::DB_TBL_PREFIX . 'id',
                ShippingDurations::DB_TBL_PREFIX . 'from',
                ShippingDurations::DB_TBL_PREFIX . 'identifier ',
                ShippingDurations::DB_TBL_PREFIX . 'to',
                ShippingDurations::DB_TBL_PREFIX . 'days_or_weeks',
            )
        );

        if ($limit > 0) {
            $srch->setPageSize($limit);
        } else {
            $srch->doNotLimitRecords();
        }
        $srch->doNotCalculateRecords();
        $rs = $srch->getResultSet();
        /* echo $srch->getQuery();die; */
        $row = FatApp::getDb()->fetchAll($rs);

        if ($row == false) {
            return array();
        } else {
            return $row;
        }
    }

    public static function getProductFreeShippingAvailabilty($product_id, $lang_id, $country_id = 0, $sellerId = 0)
    {
        $product_id = FatUtility::convertToType($product_id, FatUtility::VAR_INT);
        $lang_id = FatUtility::convertToType($lang_id, FatUtility::VAR_INT);
        $sellerId = FatUtility::convertToType($sellerId, FatUtility::VAR_INT);
        //if (!$product_id || !$lang_id || !$sellerId) {
        if (!$product_id || !$lang_id) {
            //trigger_error(Labels::getLabel("ERR_Arguments_not_specified.",$this->commonLangId), E_USER_ERROR);
            return false;
        }
        $srch = new SearchBase(static::DB_TBL_PRODUCT_SHIPPING, 'tps');
        $srch->joinTable(Countries::DB_TBL_LANG, 'LEFT JOIN', 'tps.' . static::DB_TBL_PRODUCT_SHIPPING_PREFIX . 'from_country_id=tc.' . Countries::DB_TBL_LANG_PREFIX . 'country_id and tc.' . Countries::DB_TBL_LANG_PREFIX . 'lang_id=' . $lang_id, 'tc');
        $srch->addCondition('tps.' . static::DB_TBL_PRODUCT_SHIPPING_PREFIX . 'product_id', '=', 'mysql_func_' . $product_id, 'AND', true);

        $srch->addCondition('tps.' . static::DB_TBL_PRODUCT_SHIPPING_PREFIX . 'user_id', '=', 'mysql_func_' . $sellerId, 'AND', true);
        $srch->addFld(
            array(
                static::DB_TBL_PRODUCT_SHIPPING_PREFIX . 'free'
            )
        );

        $srch->doNotLimitRecords(true);
        $srch->doNotCalculateRecords(true);
        $rs = $srch->getResultSet();
        $row = FatApp::getDb()->fetch($rs);

        if ($row) {
            return $row[static::DB_TBL_PRODUCT_SHIPPING_PREFIX . 'free'];
        }
        return 0;
    }

    public static function getProductShippingDetails($productId, $langId, $userId = 0)
    {
        $productId = FatUtility::convertToType($productId, FatUtility::VAR_INT);
        $userId = FatUtility::convertToType($userId, FatUtility::VAR_INT);
        if (!$productId || !$langId) {
            trigger_error(Labels::getLabel("ERR_ARGUMENTS_NOT_SPECIFIED.", CommonHelper::getLangId()), E_USER_ERROR);
            return false;
        }
        $srch = new SearchBase(static::DB_TBL_PRODUCT_SHIPPING);
        $srch->addCondition(static::DB_TBL_PRODUCT_SHIPPING_PREFIX . 'product_id', '=', 'mysql_func_' . $productId, 'AND', true);
        $srch->addCondition(static::DB_TBL_PRODUCT_SHIPPING_PREFIX . 'user_id', '=', 'mysql_func_' . $userId, 'AND', true);
        $srch->doNotCalculateRecords();
        $srch->setPageSize(1);
        return FatApp::getDb()->fetch($srch->getResultSet());
    }

    public static function getOption($product_id, $optionId)
    {
        $product_id = FatUtility::convertToType($product_id, FatUtility::VAR_INT);
        $optionId = FatUtility::convertToType($optionId, FatUtility::VAR_INT);

        $srch = new SearchBase(static::DB_PRODUCT_TO_OPTION);
        $srch->addCondition(static::DB_PRODUCT_TO_OPTION_PREFIX . 'product_id', '=', 'mysql_func_' . $product_id, 'AND', true);
        $srch->addCondition(static::DB_PRODUCT_TO_OPTION_PREFIX . 'option_id', '=', 'mysql_func_' . $optionId, 'AND', true);
        $srch->joinTable(Option::DB_TBL, 'INNER JOIN', Option::DB_TBL_PREFIX . 'id = ' . static::DB_PRODUCT_TO_OPTION_PREFIX . 'option_id');
        $srch->doNotCalculateRecords();
        $srch->setPageSize(1);
        return FatApp::getDb()->fetch($srch->getResultSet());
    }

    private static function getOptions($product_id, $lang_id = 0, $option_is_separate_images = 0)
    {
        $product_id = FatUtility::convertToType($product_id, FatUtility::VAR_INT);
        $srch = new SearchBase(static::DB_PRODUCT_TO_OPTION);

        if (0 < $product_id) {
            $srch->addCondition(static::DB_PRODUCT_TO_OPTION_PREFIX . 'product_id', '=', 'mysql_func_' . $product_id, 'AND', true);
        }

        $srch->joinTable(Option::DB_TBL, 'INNER JOIN', Option::DB_TBL_PREFIX . 'id = ' . static::DB_PRODUCT_TO_OPTION_PREFIX . 'option_id');

        $attr = array('option_id', 'option_identifier', 'prodoption_optionvalue_ids', 'option_is_separate_images');
        if (0 < $lang_id) {
            $srch->joinTable(Option::DB_TBL . '_lang', 'LEFT JOIN', 'lang.optionlang_option_id = ' . Option::DB_TBL_PREFIX . 'id AND optionlang_lang_id = ' . $lang_id, 'lang');
            $attr[] = 'IFNULL(option_name, option_identifier) as option_name';
        }

        $srch->addMultipleFields($attr);

        if ($option_is_separate_images) {
            $srch->addCondition('option_is_separate_images', '=', 'mysql_func_' . applicationConstants::YES, 'AND', true);
        }
        $srch->doNotCalculateRecords();
        return FatApp::getDb()->fetchAll($srch->getResultSet());
    }

    public static function validateProductOptionValue(int $product_id, string $needle): bool
    {
        $records = self::getOptions($product_id);

        if (!is_array($records) || 1 > count($records)) {
            return false;
        }

        if (!empty($needle) && false !== strpos($needle, '|')) {
            $optionValueIdsArr = explode('|', $needle);
        } else if (!empty($needle) && false !== strpos($needle, '_')) {
            $optionValueIdsArr = explode('_', $needle);
        } else {
            $optionValueIdsArr = (array) $needle;
        }

        if (count($records) != count($optionValueIdsArr)) {
            return false;
        }

        $optionValues = [];
        foreach ($records as $row) {
            $optionValues = $optionValues + static::getOptionValues($row['option_id'], CommonHelper::getLangId());
        }

        return count(array_intersect($optionValueIdsArr, array_keys($optionValues))) === count($optionValueIdsArr);
    }

    public static function setSearchOptionName($needle)
    {
        self::$optionValueName = $needle;
    }

    public static function getProductOptions($product_id, $lang_id, $includeOptionValues = false, $option_is_separate_images = 0)
    {
        $product_id = FatUtility::convertToType($product_id, FatUtility::VAR_INT);
        $lang_id = FatUtility::convertToType($lang_id, FatUtility::VAR_INT);
        if (!$product_id || !$lang_id) {
            trigger_error(Labels::getLabel("ERR_ARGUMENTS_NOT_SPECIFIED.", CommonHelper::getLangId()), E_USER_ERROR);
            return false;
        }

        $records = self::getOptions($product_id, $lang_id, $option_is_separate_images);

        $found = false;
        $data = array();
        foreach ($records as $row) {
            if ($includeOptionValues) {
                $row['optionValues'] = static::getOptionValues($row['option_id'], $lang_id, explode(",", $row['prodoption_optionvalue_ids']));
                $found = (false === $found ? !empty($row['optionValues']) : true);
            }
            $data[] = $row;
        }

        if (true === $found) {
            foreach ($data as &$options) {
                if (empty($options['optionValues'])) {
                    self::setSearchOptionName(''); /* Unset Search Option Value Name */
                    $options['optionValues'] = static::getOptionValues($options['option_id'], $lang_id);
                }
            }
        }

        return $data;
    }

    public static function getSeparateImageOptions($product_id, $lang_id)
    {
        $imgTypesArr = array(0 => Labels::getLabel('LBL_FOR_ALL_OPTIONS', $lang_id));
        $productOptions = Product::getProductOptions($product_id, $lang_id, true, 1);

        foreach ($productOptions as $val) {
            if (!empty($val['optionValues'])) {
                foreach ($val['optionValues'] as $k => $v) {
                    $imgTypesArr[$k] = $v;
                }
            }
        }
        return $imgTypesArr;
    }

    public static function getProductSpecifications($product_id, $lang_id)
    {
        $product_id = FatUtility::convertToType($product_id, FatUtility::VAR_INT);
        $lang_id = FatUtility::convertToType($lang_id, FatUtility::VAR_INT);
        if (!$product_id || !$lang_id) {
            trigger_error(Labels::getLabel("ERR_ARGUMENTS_NOT_SPECIFIED.", CommonHelper::getLangId()), E_USER_ERROR);
            return false;
        }
        $data = array();
        $languages = Language::getAllNames();

        foreach ($languages as $langId => $langName) {
            $srch = new SearchBase(static::DB_PRODUCT_SPECIFICATION);
            $srch->addCondition(static::DB_PRODUCT_SPECIFICATION_PREFIX . 'product_id', '=', 'mysql_func_' . $product_id, 'AND', true);
            $srch->joinTable(static::DB_PRODUCT_LANG_SPECIFICATION, 'LEFT JOIN', static::DB_PRODUCT_SPECIFICATION_PREFIX . 'id = ' . static::DB_PRODUCT_LANG_SPECIFICATION_PREFIX . 'prodspec_id and ' . static::DB_PRODUCT_LANG_SPECIFICATION_PREFIX . 'lang_id =' . $langId);
            $srch->addMultipleFields(
                array(
                    static::DB_PRODUCT_SPECIFICATION_PREFIX . 'id',
                    static::DB_PRODUCT_SPECIFICATION_PREFIX . 'name',
                    static::DB_PRODUCT_SPECIFICATION_PREFIX . 'value'
                )
            );
            $srch->doNotCalculateRecords();
            $rs = $srch->getResultSet();
            $row = FatApp::getDb()->fetchAll($rs);
            foreach ($row as $resRow) {
                $data[$resRow[static::DB_PRODUCT_SPECIFICATION_PREFIX . 'id']][$langId] = $resRow;
            }
        }

        return $data;
    }

    public static function getProductTags($product_id, $lang_id, $assoc = false, array $attrs = [])
    {
        $product_id = FatUtility::convertToType($product_id, FatUtility::VAR_INT);
        $lang_id = FatUtility::convertToType($lang_id, FatUtility::VAR_INT);
        if (!$product_id) {
            trigger_error(Labels::getLabel("ERR_ARGUMENTS_NOT_SPECIFIED.", $lang_id), E_USER_ERROR);
            return false;
        }

        $srch = new SearchBase(static::DB_PRODUCT_TO_TAG);
        $srch->doNotCalculateRecords();
        $srch->joinTable(Tag::DB_TBL, 'INNER JOIN', Tag::DB_TBL_PREFIX . 'id = ' . static::DB_PRODUCT_TO_TAG_PREFIX . 'tag_id');
        $srch->addCondition(static::DB_PRODUCT_TO_TAG_PREFIX . 'product_id', '=', 'mysql_func_' . $product_id, 'AND', true);
        $srch->addCondition(Tag::tblFld('lang_id'), '=', 'mysql_func_' . $lang_id, 'AND', true);
        if (true == $assoc) {
            if (count($attrs)) {
                $srch->addMultipleFields($attrs);
            } else {
                $srch->addMultipleFields(array('tag_id', 'tag_name'));
            }
            return FatApp::getDb()->fetchAllAssoc($srch->getResultSet());
        }
        if (count($attrs)) {
            $srch->addMultipleFields($attrs);
        }
        return FatApp::getDb()->fetchAll($srch->getResultSet());
    }

    public static function getProductIdsByTagId($tagId)
    {
        $tagId = FatUtility::int($tagId);
        if (!$tagId) {
            return array();
        }

        $srch = new SearchBase(static::DB_PRODUCT_TO_TAG);
        $srch->doNotCalculateRecords();
        $srch->doNotLimitRecords();
        $srch->addCondition(static::DB_PRODUCT_TO_TAG_PREFIX . 'tag_id', '=', 'mysql_func_' . $tagId, 'AND', true);
        $rs = $srch->getResultSet();
        return FatApp::getDb()->fetchAll($rs);
    }

    public static function getOptionValues($option_id, $lang_id, $valuesIds = [])
    {
        $option_id = FatUtility::int($option_id);
        $lang_id = FatUtility::int($lang_id);
        if (!$option_id || !$lang_id) {
            trigger_error(Labels::getLabel('ERR_INVALID_ARGUMENTS!', $lang_id), E_USER_ERROR);
        }
        $srch = new SearchBase(OptionValue::DB_TBL);
        $srch->joinTable(OptionValue::DB_TBL . '_lang', 'LEFT JOIN', 'lang.optionvaluelang_optionvalue_id = ' . OptionValue::DB_TBL_PREFIX . 'id AND optionvaluelang_lang_id = ' . $lang_id, 'lang');
        $srch->addCondition(OptionValue::DB_TBL_PREFIX . 'option_id', '=', 'mysql_func_' . $option_id, 'AND', true);
        if (!empty($valuesIds)) {
            $srch->addCondition(OptionValue::tblFld('id'), 'IN', $valuesIds);
        }

        if (!empty(self::$optionValueName)) {
            $srch->addCondition('optionvalue_name', 'LIKE', "%" . self::$optionValueName . "%");
        }

        $srch->doNotCalculateRecords();
        $srch->doNotLimitRecords();
        $srch->addOrder('optionvalue_display_order');
        $srch->addOrder('optionvalue_option_id');
        $srch->addMultipleFields(array('optionvalue_id', 'COALESCE(optionvalue_name, optionvalue_identifier) as optionvalue_name'));
        $rs = $srch->getResultSet();
        $db = FatApp::getDb();
        return $db->fetchAllAssoc($rs);
    }

    public function getProductCategories($product_id)
    {
        $product_id = FatUtility::int($product_id);
        $srch = new SearchBase(static::DB_TBL_PRODUCT_TO_CATEGORY, 'ptc');
        $srch->addCondition(static::DB_TBL_PRODUCT_TO_CATEGORY_PREFIX . 'product_id', '=', 'mysql_func_' . $product_id, 'AND', true);
        $srch->joinTable(ProductCategory::DB_TBL, 'INNER JOIN', ProductCategory::DB_TBL_PREFIX . 'id = ptc.' . static::DB_TBL_PRODUCT_TO_CATEGORY_PREFIX . 'prodcat_id', 'cat');
        $srch->addMultipleFields(array('prodcat_id'));
        $srch->doNotCalculateRecords();
        $rs = $srch->getResultSet();
        $records = FatApp::getDb()->fetchAll($rs, 'prodcat_id');
        if (!$records) {
            return false;
        }
        return $records;
    }

    public function addUpdateNumericAttributes($data)
    {
        $record = new TableRecord(self::DB_NUMERIC_ATTRIBUTES_TBL);
        $record->assignValues($data);
        if (!$record->addNew(array(), $data)) {
            $this->error = $record->getError();
            return false;
        }
        return true;
    }

    public function addUpdateTextualAttributes($data)
    {
        $record = new TableRecord(self::DB_TEXT_ATTRIBUTES_TBL);
        $record->assignValues($data);
        if (!$record->addNew(array(), $data)) {
            $this->error = $record->getError();
            return false;
        }
        return true;
    }

    public static function getProductNumericAttributes($product_id)
    {
        if (!$product_id) {
            trigger_error(Labels::getLabel('ERR_INVALID_ARGUMENTS!', CommonHelper::getLangId()), E_USER_ERROR);
        }
        $record = new TableRecord(static::DB_NUMERIC_ATTRIBUTES_TBL);
        $record->loadFromDb(array('smt' => static::DB_NUMERIC_ATTRIBUTES_PREFIX . 'product_id = ?', 'vals' => array($product_id)));
        return $record->getFlds();
    }

    public static function getProductTextualAttributes($langId, $product_id)
    {
        $product_id = FatUtility::int($product_id);
        $langId = FatUtility::int($langId);
        if (!$product_id || !$langId) {
            trigger_error(Labels::getLabel('ERR_INVALID_ARGUMENTS!', $langId), E_USER_ERROR);
        }
        $record = new TableRecord(static::DB_TEXT_ATTRIBUTES_TBL);
        $record->loadFromDb(array('smt' => static::DB_TEXT_ATTRIBUTES_PREFIX . 'product_id = ? AND ' . static::DB_TEXT_ATTRIBUTES_PREFIX . 'lang_id = ?', 'vals' => array($product_id, $langId)));
        return $record->getFlds();
    }

    public static function generateProductOptionsUrl($selprod_id, $selectedOptions, $option_id, $optionvalue_id, $product_id, $returnId = false)
    {
        $selprod_id = FatUtility::int($selprod_id);
        $product_id = FatUtility::int($product_id);
        $selectedOptions[$option_id] = $optionvalue_id;
        sort($selectedOptions);

        $selprod_code = $product_id . '_' . implode('_', $selectedOptions);

        $prodSrchObj = new ProductSearch();
        $prodSrchObj->setDefinedCriteria(0, 0, ['doNotJoinSellers' => true]);
        $prodSrchObj->joinProductToCategory();
        $prodSrchObj->doNotCalculateRecords();
        $prodSrchObj->addCondition('selprod_id', '!=', 'mysql_func_' . $selprod_id, 'AND', true);
        $prodSrchObj->addMultipleFields(array('product_id', 'selprod_id', 'theprice'));
        $prodSrchObj->addCondition('product_id', '=', 'mysql_func_' . $product_id, 'AND', true);

        $prodSrch = clone $prodSrchObj;

        $prodSrch->addCondition('selprod_code', '=', $selprod_code);
        $prodSrch->doNotLimitRecords();
        $prodSrch->addOrder('theprice', 'ASC');
        $productRs = $prodSrch->getResultSet();
        //echo $prodSrch->getQuery();
        $product = FatApp::getDb()->fetch($productRs);
        if ($product) {
            if ($returnId) {
                return $product['selprod_id'];
            }
            return UrlHelper::generateUrl('Products', 'view', array($product['selprod_id']));
        } else {
            $prodSrch2 = new ProductSearch(CommonHelper::getLangId());
            $prodSrch2->doNotCalculateRecords();
            $prodSrch2->setDefinedCriteria(0, 0, ['doNotJoinSellers' => true]);
            $prodSrch2->addCondition('selprod_id', '!=', 'mysql_func_' . $selprod_id, 'AND', true);
            $prodSrch2->addCondition('product_id', '=', 'mysql_func_' . $product_id, 'AND', true);
            $prodSrch2->addCondition('selprod_code', 'LIKE', '%_' . $optionvalue_id . '%');
            $prodSrch2->addMultipleFields(array('selprod_id', 'special_price_found', 'theprice'));
            $prodSrch2->setPageSize(1);
            $prodSrch2->addOrder('theprice', 'ASC');
            $productRs = $prodSrch2->getResultSet();
            $product = FatApp::getDb()->fetch($productRs);

            if ($product) {
                if ($returnId) {
                    return $product['selprod_id'];
                }
                return UrlHelper::generateUrl('Products', 'view', array($product['selprod_id'])) . "::";
            } else {
                return false;
            }
            return false;
        }
    }

    public static function uniqueProductAction($selprodCode, $weightageKey)
    {
        /* $ipAddress = $_SERVER['REMOTE_ADDR'];
        list($product_id) = explode('_',$selprodCode);
        $product_id = FatUtility::int($product_id);

        $srch = new SearchBase('tbl_smart_log_actions');

        $date = date('Y-m-d H:i:s');
        $currentDate = strtotime($date);
        $futureDate = $currentDate - (60*5);
        $formatDate = date("Y-m-d H:i:s", $futureDate);

        $srch->addDirectCondition("slog_ip = '".$ipAddress."' and '".$formatDate."' < slog_datetime and      slog_swsetting_key = '".$weightageKey."' and slog_record_code = '".$selprodCode."' and slog_record_id = '".$product_id."' and slog_type = '".SmartUserActivityBrowsing::TYPE_PRODUCT."'");
        $srch->doNotCalculateRecords();
        $srch->doNotLimitRecords();
        $srch->addMultipleFields(array('slog_ip'));
        $rs = $srch->getResultSet();
        $row =  FatApp::getDb()->fetch($rs);
        return ($row == false)?true:false; */
    }

    public static function recordProductWeightage($selprodCode, $weightageKey, $eventWeightage = 0)
    {
        list($productId) = explode('_', $selprodCode);
        $productId = FatUtility::int($productId);

        if (1 > $productId) {
            return false;
        }

        if ($eventWeightage == 0) {
            $weightageArr = SmartWeightageSettings::getWeightageAssoc();
            $eventWeightage = !empty($weightageArr[$weightageKey]) ? $weightageArr[$weightageKey] : 0;
        }

        if (!UserAuthentication::isUserLogged()) {
            $userId = CommonHelper::getUserIdFromCookies();
        } else {
            $userId = UserAuthentication::getLoggedUserId();
        }

        $record = new TableRecord('tbl_recommendation_activity_browsing');

        $assignFields = array();
        $assignFields['rab_session_id'] = session_id();
        $assignFields['rab_user_id'] = $userId;
        $assignFields['rab_record_id'] = $productId;
        $assignFields['rab_record_type'] = SmartUserActivityBrowsing::TYPE_PRODUCT;
        $assignFields['rab_weightage_key'] = $weightageKey;
        $assignFields['rab_weightage'] = $eventWeightage;
        $assignFields['rab_last_action_datetime'] = date('Y-m-d H:i:s');

        $onDuplicateKeyUpdate = array_merge($assignFields, array('rab_weightage' => 'mysql_func_rab_weightage + ' . $eventWeightage));

        FatApp::getDb()->insertFromArray('tbl_recommendation_activity_browsing', $assignFields, true, array(), $onDuplicateKeyUpdate);
    }

    public static function addUpdateProductBrowsingHistory($selprodCode, $weightageKey, $weightageVal = 1)
    {
        /* list($productId) = explode('_',$selprodCode);
        $productId = FatUtility::int($productId);
        $weightageVal = FatUtility::int($weightageVal);

        $weightageKey = FatUtility::int($weightageKey);
        $weightageKey = 1 ;

        if(1 > $weightageKey || 1 > $weightageVal) { return false;}

        if(!static::uniqueProductAction($selprodCode,$weightageKey)){ return false ;}

        if (!UserAuthentication::isUserLogged()) {
        $userId = CommonHelper::getUserIdFromCookies();
        }else{
        $userId = UserAuthentication::getLoggedUserId();
        }

        $record = new TableRecord('tbl_products_browsing_history');

        $assignFields = array();
        $assignFields['pbhistory_sessionid'] = session_id();
        $assignFields['pbhistory_selprod_code'] = $selprodCode;
        $assignFields['pbhistory_swsetting_key'] = $weightageKey;
        $assignFields['pbhistory_user_id'] = $userId;
        $assignFields['pbhistory_product_id'] = $productId;
        $assignFields['pbhistory_count'] = $weightageVal;
        $assignFields['pbhistory_datetime'] = date('Y-m-d H:i:s');

        $onDuplicateKeyUpdate = array_merge($assignFields,array('pbhistory_count'=>'mysql_func_pbhistory_count + '.$weightageVal));

        FatApp::getDb()->insertFromArray('tbl_products_browsing_history',$assignFields,true,array(),$onDuplicateKeyUpdate);  */
    }

    public static function tempHoldStockCount($selprod_id = 0, $userId = 0, $pshold_prodgroup_id = 0, $useProductGroup = false)
    {
        $selprod_id = FatUtility::int($selprod_id);
        $pshold_prodgroup_id = FatUtility::int($pshold_prodgroup_id);
        $intervalInMinutes = FatApp::getConfig('cart_stock_hold_minutes', FatUtility::VAR_INT, 15);

        $srch = new SearchBase('tbl_product_stock_hold');
        $srch->doNotCalculateRecords();
        $srch->addOrder('pshold_id', 'ASC');
        $srch->addCondition('pshold_added_on', '>=', 'mysql_func_DATE_SUB( NOW(), INTERVAL ' . $intervalInMinutes . ' MINUTE )', 'AND', true);
        $srch->addCondition('pshold_selprod_id', '=', 'mysql_func_' . $selprod_id, 'AND', true);

        if ($useProductGroup == true) {
            $srch->addCondition('pshold_prodgroup_id', '=', 'mysql_func_' . $pshold_prodgroup_id, 'AND', true);
        }

        if ($userId > 0) {
            $srch->addCondition('pshold_user_id', '=', $userId, 'AND');
        }
        $srch->addMultipleFields(array('IFNULL(SUM(pshold_selprod_stock), 0) as stockHold'));
        $srch->setPageNumber(1);
        $srch->setPageSize(1);
        $stockHoldRow = FatApp::getDb()->fetch($srch->getResultSet());
        return $stockHoldRow['stockHold'] ?? 0;
    }

    public function addUpdateUserFavoriteProduct(int $user_id, int $selProdId)
    {
        $data_to_save = array('ufp_user_id' => $user_id, 'ufp_selprod_id' => $selProdId);
        $data_to_save_on_duplicate = array('ufp_selprod_id' => $selProdId);
        if (!FatApp::getDb()->insertFromArray(static::DB_TBL_PRODUCT_FAVORITE, $data_to_save, false, array(), $data_to_save_on_duplicate)) {
            $this->error = FatApp::getDb()->getError();
            return false;
        }
        return true;
    }

    public static function getUserFavouriteProducts($user_id, $langId)
    {
        $user_id = FatUtility::int($user_id);
        $srch = new UserFavoriteProductSearch();
        $srch->setDefinedCriteria($langId);
        $srch->joinBrands();
        $srch->joinSellers();
        $srch->joinShops();
        $srch->joinProductToCategory();
        $srch->joinSellerSubscription($langId, true);
        $srch->addSubscriptionValidCondition();
        $srch->addCondition('selprod_deleted', '=', 'mysql_func_' . applicationConstants::NO, 'AND', true);
        $srch->addCondition('ufp_user_id', '=', 'mysql_func_' . $user_id, 'AND', true);
        $srch->addMultipleFields(array('selprod_id', 'IFNULL(selprod_title  ,IFNULL(product_name, product_identifier)) as selprod_title', 'product_id', 'IFNULL(product_name, product_identifier) as product_name', 'IF(selprod_stock > 0, 1, 0) AS in_stock', 'product_updated_on'));
        $srch->setPageNumber(1);
        $srch->setPageSize(4);
        $srch->addGroupBy('selprod_id');

        /* die($srch->getQuery());  */
        $rs = $srch->getResultSet();
        $result['uwlist_id'] = 0;
        $result['uwlist_title'] = Labels::getLabel('LBL_FAVORITE_LIST', $langId);
        $result['uwlist_type'] = UserWishList::TYPE_FAVOURITE;

        $result['totalProducts'] = $srch->recordCount();
        $result['products'] = FatApp::getDb()->fetchAll($rs);
        return $result;
    }

    public static function getProductMetaData($selProductId = 0)
    {
        if ($selProductId <= 0) {
            return false;
        }
        $srch = MetaTag::getSearchObject();
        $srch->addCondition(MetaTag::DB_TBL_PREFIX . 'record_id', '=', 'mysql_func_' . $selProductId, 'AND', true);
        $srch->addCondition(MetaTag::DB_TBL_PREFIX . 'controller', '=', 'Products');
        $srch->addCondition(MetaTag::DB_TBL_PREFIX . 'action', '=', 'view');
        $srch->addMultipleFields(array('meta_id'));
        $srch->doNotCalculateRecords();
        $srch->setPageSize(1);
        $rs = $srch->getResultSet();
        $records = FatApp::getDb()->fetch($rs);
        return $records;
    }

    public static function isProductShippedBySeller($productId, $productAddedBySellerId, $selProdSellerId)
    {
        $productAddedBySellerId = FatUtility::int($productAddedBySellerId);
        if (0 < FatApp::getConfig('CONF_SHIPPED_BY_ADMIN_ONLY', FatUtility::VAR_INT, 0)) {
            return false;
        }

        $productId = FatUtility::int($productId);
        $selProdSellerId = FatUtility::int($selProdSellerId);
        if ($productAddedBySellerId && $productAddedBySellerId == $selProdSellerId) {
            return true;
        }
        $srch = new SearchBase(static::DB_PRODUCT_SHIPPED_BY_SELLER, 'psbs');
        $srch->addCondition('psbs_product_id', '=', 'mysql_func_' . $productId, 'AND', true);
        $srch->addCondition('psbs_user_id', '=', 'mysql_func_' . $selProdSellerId, 'AND', true);
        $srch->doNotCalculateRecords();
        $srch->setPageSize(1);
        $row = FatApp::getDb()->fetch($srch->getResultSet());
        return (!empty($row) && $row['psbs_user_id'] == $selProdSellerId);
    }

    public function getTotalProductsAddedByUser($user_id)
    {
        $srch = SellerProduct::getSearchObject(CommonHelper::getLangId());
        $srch->joinTable(Product::DB_TBL, 'INNER JOIN', 'p.product_id = sp.selprod_product_id', 'p');
        $srch->joinTable(Product::DB_TBL_LANG, 'LEFT OUTER JOIN', 'p.product_id = p_l.productlang_product_id AND p_l.productlang_lang_id = ' . CommonHelper::getLangId(), 'p_l');
        $srch->addOrder('product_name');
        $srch->addCondition('selprod_user_id', '=', $user_id);
        /* $srch->addCondition('selprod_deleted', '=', 0); */
        $srch->addMultipleFields(
            array(
                'count(selprod_id) as totProducts'
            )
        );
        $srch->addOrder('selprod_active', 'DESC');
        $srch->doNotCalculateRecords();

        $db = FatApp::getDb();
        $rs = $srch->getResultSet();
        $produtcCountList = $db->fetch($rs);
        $totalProduct = $produtcCountList['totProducts'];
        return $totalProduct;
    }

    public static function getProductShippingTitle($langId, $shippingDetails = array()): string
    {
        $langId = FatUtility::int($langId);
        if (1 > $langId || empty($shippingDetails)) {
            return '';
        }

        return FatUtility::decodeHtmlEntities('<em><strong>' . $shippingDetails['country_name'] . '</em></strong> ' . Labels::getLabel('LBL_BY', $langId) . ' <strong>' . $shippingDetails['scompany_name'] . '</strong> ' . Labels::getLabel('LBL_IN', $langId) . ' ' . ShippingDurations::getShippingDurationTitle($shippingDetails, $langId));
    }

    public static function isSellProdAvailableForUser($selProdCode, $langId, $userId = 0, $selprod_id = 0)
    {
        $userId = FatUtility::int($userId);
        $langId = FatUtility::int($langId);
        if ($langId < 1) {
            $langId = FatApp::getConfig('CONF_ADMIN_DEFAULT_LANG');
        }

        if (1 > $userId) {
            return false;
        }

        $srch = SellerProduct::getSearchObject($langId);
        $srch->addCondition('selprod_code', '=', $selProdCode);
        $srch->addCondition('selprod_user_id', '=', $userId);
        /* $srch->addCondition('selprod_deleted','=',applicationConstants::NO); */
        if ($selprod_id) {
            $srch->addCondition('selprod_id', '!=', $selprod_id);
        }
        $db = FatApp::getDb();

        $srch->addMultipleFields(array('selprod_id', 'selprod_deleted'));
        $srch->doNotCalculateRecords();
        $srch->setPageSize(1);
        $rs = $srch->getResultSet();
        $row = $db->fetch($rs);

        if ($row == false) {
            return array();
        }

        return $row;
    }

    public static function availableForAddToStore($productId, $userId)
    {
        $productId = FatUtility::int($productId);
        $userId = FatUtility::int($userId);

        $srch = SellerProduct::getSearchObject();
        $srch->joinTable(SellerProduct::DB_TBL_SELLER_PROD_OPTIONS, 'LEFT JOIN', 'selprod_id = selprodoption_selprod_id', 'tspo');
        $srch->addCondition('selprod_product_id', '=', $productId);
        $srch->addCondition('selprod_user_id', '=', $userId);
        $srch->addCondition('selprod_deleted', '=', applicationConstants::NO);
        $srch->doNotCalculateRecords();
        $srch->doNotLimitRecords();
        // $srch->addCondition('tspo.selprodoption_optionvalue_id', 'is', 'mysql_func_null', 'and', true);
        $srch->addFld('count(DISTINCT selprod_code) as count');
        $rs = $srch->getResultSet();
        $alreadyAdded = FatApp::getDb()->fetch($rs);
        if ($alreadyAdded == false || 1 > $alreadyAdded['count']) {
            return true;
        }
        $alreadyAddedOptions = $alreadyAdded['count'];

        $srch = new SearchBase(static::DB_PRODUCT_TO_OPTION);
        $srch->addCondition(static::DB_PRODUCT_TO_OPTION_PREFIX . 'product_id', '=', $productId);
        /*
        $srch->joinTable(OptionValue::DB_TBL, 'LEFT JOIN', 'prodoption_option_id = opval.optionvalue_option_id', 'opval');
        $srch->addFld('count(DISTINCT optionvalue_id) as count');
        $srch->addGroupBy('prodoption_option_id');
        */
        $srch->addFld('prodoption_optionvalue_ids');
        $srch->doNotCalculateRecords();
        $srch->doNotLimitRecords();
        $rs = $srch->getResultSet();
        $totalOptionCombination = 1;

        while ($row = FatApp::getDb()->fetch($rs)) {
            $totalOptionCombination *= count(explode(",", $row['prodoption_optionvalue_ids']));
        }

        return ($totalOptionCombination - $alreadyAddedOptions) > 0 ? true : false;
    }

    public static function hasInventory(int $productId, int $userId = 0): bool
    {
        if (1 > $productId) {
            return false;
        }

        $srch = SellerProduct::getSearchObject();
        $srch->doNotCalculateRecords();
        $srch->setPageSize(1);
        $srch->addFld('selprod_id');
        $srch->addCondition('selprod_product_id', '=', $productId);
        if (0 < $userId) {
            $srch->addCondition('selprod_user_id', '=', $userId);
        }
        $srch->addCondition('selprod_deleted', '=', applicationConstants::NO);
        $result = (array) FatApp::getDb()->fetch($srch->getResultSet());
        return (0 < count($result));
    }

    public static function addUpdateProductSellerShipping($product_id, $data_to_be_save, $userId)
    {
        $productSellerShiping = array();
        $productSellerShiping['ps_product_id'] = $product_id;
        $productSellerShiping['ps_user_id'] = $userId;
        $productSellerShiping['ps_from_country_id'] = $data_to_be_save['ps_from_country_id'];
        $productSellerShiping['ps_free'] = !empty($data_to_be_save['ps_free']) ?? 0;
        if (!FatApp::getDb()->insertFromArray(Product::DB_TBL_PRODUCT_SHIPPING, $productSellerShiping, false, array(), $productSellerShiping)) {
            return false;
        }
        return true;
    }

    public static function addUpdateProductShippingRates($product_id, $data, $userId = 0)
    {
        static::removeProductShippingRates($product_id, $userId);

        if (empty($data) || count($data) == 0) {
            // $this->error = Labels::getLabel('ERR_INVALID_REQUEST', $this->adminLangId);
            return false;
        }

        foreach ($data as $key => $val) {
            if (isset($val["country_id"]) && ($val["country_id"] > 0 || $val["country_id"] == -1) && $val["company_id"] > 0 && $val["processing_time_id"] > 0) {
                $prodShipData = array(
                    'pship_prod_id' => $product_id,
                    'pship_country' => (isset($val["country_id"]) && FatUtility::int($val["country_id"])) ? FatUtility::int($val["country_id"]) : 0,
                    'pship_user_id' => $userId,
                    'pship_company' => (isset($val["company_id"]) && FatUtility::int($val["company_id"])) ? FatUtility::int($val["company_id"]) : 0,
                    'pship_duration' => (isset($val["processing_time_id"]) && FatUtility::int($val["processing_time_id"])) ? FatUtility::int($val["processing_time_id"]) : 0,
                    'pship_charges' => (1 > FatUtility::float($val["cost"]) ? 0 : FatUtility::float($val["cost"])),
                    'pship_additional_charges' => FatUtility::float($val["additional_cost"]),
                );

                if (!FatApp::getDb()->insertFromArray(ShippingApi::DB_TBL_PRODUCT_SHIPPING_RATES, $prodShipData, false, array(), $prodShipData)) {
                    // $this->error = FatApp::getDb()->getError();
                    return false;
                }
            }
        }

        return true;
    }

    public static function removeProductShippingRates($product_id, $userId)
    {
        $db = FatApp::getDb();
        $product_id = FatUtility::int($product_id);
        $userId = FatUtility::int($userId);

        if (!$db->deleteRecords(ShippingApi::DB_TBL_PRODUCT_SHIPPING_RATES, array('smt' => ShippingApi::DB_TBL_PRODUCT_SHIPPING_RATES_PREFIX . 'prod_id = ? and ' . ShippingApi::DB_TBL_PRODUCT_SHIPPING_RATES_PREFIX . 'user_id = ?', 'vals' => array($product_id, $userId)))) {
            // $this->error = $db->getError();
            return false;
        }
        return true;
    }

    public function removeProductCategory($option_id)
    {
        $db = FatApp::getDb();
        $option_id = FatUtility::int($option_id);
        if (!$this->mainTableRecordId || !$option_id) {
            $this->error = Labels::getLabel('ERR_INVALID_REQUEST', $this->commonLangId);
            return false;
        }
        if (!$db->deleteRecords(static::DB_TBL_PRODUCT_TO_CATEGORY, array('smt' => static::DB_TBL_PRODUCT_TO_CATEGORY_PREFIX . 'product_id = ? AND ' . static::DB_TBL_PRODUCT_TO_CATEGORY_PREFIX . 'prodcat_id = ?', 'vals' => array($this->mainTableRecordId, $option_id)))) {
            $this->error = $db->getError();
            return false;
        }

        if (!$this->updateModifiedTime()) {
            return false;
        }

        return true;
    }

    public function addUpdateProductCategory($prodCatId)
    {
        $prodCatId = FatUtility::int($prodCatId);
        if (!$this->mainTableRecordId || !$prodCatId) {
            $this->error = Labels::getLabel('ERR_INVALID_REQUEST', $this->commonLangId);
            return false;
        }
        $record = new TableRecord(static::DB_TBL_PRODUCT_TO_CATEGORY);
        $to_save_arr = array();
        $to_save_arr[static::DB_TBL_PRODUCT_TO_CATEGORY_PREFIX . 'product_id'] = $this->mainTableRecordId;
        $to_save_arr[static::DB_TBL_PRODUCT_TO_CATEGORY_PREFIX . 'prodcat_id'] = $prodCatId;
        $record->assignValues($to_save_arr);
        if (!$record->addNew(array(), $to_save_arr)) {
            $this->error = $record->getError();
            return false;
        }

        if (!$this->updateModifiedTime()) {
            return false;
        }
        return true;
    }

    public function deleteProduct()
    {
        $productId = FatUtility::int($this->mainTableRecordId);
        if (0 >= $productId) {
            Message::addErrorMessage(Labels::getLabel('LBL_INVALID_REQUEST_ID'));
            FatUtility::dieWithError(Message::getHtml());
        }

        $product = new Product($productId);
        if (!$product->deleteRecord()) {
            $this->error = $product->getError();
            return false;
        }
        return true;
    }

    public static function verifyProductIsValid($selprod_id)
    {
        $prodSrch = new ProductSearch();
        $prodSrch->setDefinedCriteria(0, 0, ['doNotJoinSpecialPrice' => true, 'doNotJoinSellers' => true]);
        $prodSrch->joinProductToCategory();
        $prodSrch->joinSellerSubscription();
        $prodSrch->addSubscriptionValidCondition();
        $prodSrch->addMultipleFields(array('selprod_id', 'product_id'));
        $prodSrch->addCondition('selprod_id', '=', $selprod_id);
        $prodSrch->doNotLimitRecords();
        $productRs = $prodSrch->getResultSet();
        $product = FatApp::getDb()->fetch($productRs);

        if ($product == false) {
            return false;
        }
        return true;
    }

    public static function convertArrToSrchFiltersAssocArr($arr)
    {
        return SearchItem::convertArrToSrchFiltersAssocArr($arr);
    }

    public static function getListingObj($criteria, $langId = 0, $userId = 0)
    {
        $srch = new ProductSearch($langId);
        /* $join_price = 0;
        if (array_key_exists('join_price', $criteria)) {
            $join_price = FatUtility::int($criteria['join_price']);
        } */

        $keyword = '';
        if (array_key_exists('keyword', $criteria)) {
            $keyword = $criteria['keyword'];
        }

        if (true === MOBILE_APP_API_CALL) {
            $criteria['optionvalue'] = !empty($criteria['optionvalue']) ? json_decode($criteria['optionvalue'], true) : '';
        }

        $shop_id = 0;
        if (array_key_exists('shop_id', $criteria)) {
            $shop_id = FatUtility::int($criteria['shop_id']);
        }
        $criteria['max_price'] = true;
        //$srch->setDefinedCriteria($join_price, 0, $criteria, true);
        if (0 < $shop_id) {
            $srch->joinSellerProducts(0, '', $criteria, true);
        } else {
            $srch->joinForPrice('', $criteria, true);
        }
        $srch->unsetDefaultLangForJoins();
        /* $srch->joinSellers(); */
        $srch->setGeoAddress();
        $srch->joinShops($langId, true, true, $shop_id, true);
        $srch->validateAndJoinDeliveryLocation();
        /* $srch->joinShopCountry();
        $srch->joinShopState(); */
        $srch->joinBrands($langId);
        $srch->joinProductToCategory($langId);
        $srch->joinProductToTax();
        $srch->addCondition('selprod_code', 'IS NOT', 'mysql_func_null', 'and', true);
        /* $srch->joinSellerSubscription(0, false, true);
        $srch->addSubscriptionValidCondition(); */

        /* to check current product is in wish list or not[ */
        /*  if (FatApp::getConfig('CONF_ADD_FAVORITES_TO_WISHLIST', FatUtility::VAR_INT, 1) == applicationConstants::NO) {
            $srch->joinFavouriteProducts($userId);
            $srch->addFld('IFNULL(ufp_id, 0) as ufp_id');
        } else {
            $srch->joinUserWishListProducts($userId);
            $srch->addFld('COALESCE(uwlp.uwlp_selprod_id, 0) as is_in_any_wishlist');
        } */
        /*substring_index(group_concat(IFNULL(prodcat_name, prodcat_identifier) ORDER BY IFNULL(prodcat_name, prodcat_identifier) ASC SEPARATOR "," ) , ",", 1) as prodcat_name*/
        $srch->addMultipleFields(
            array(
                'prodcat_code',
                'product_id',
                'prodcat_id',
                'COALESCE(product_name, product_identifier) as product_name',
                'product_model',
                'product_updated_on',
                'COALESCE(prodcat_name, prodcat_identifier) as prodcat_name',
                'selprod_id',
                'selprod_user_id',
                'selprod_code',
                'selprod_stock',
                'selprod_condition',
                'selprod_price',
                'COALESCE(selprod_title  ,COALESCE(product_name, product_identifier)) as selprod_title',
                'splprice_display_list_price',
                'splprice_display_dis_val',
                'splprice_display_dis_type',
                'splprice_start_date',
                'splprice_end_date',
                'brand_id',
                'COALESCE(brand_name, brand_identifier) as brand_name',
                'shop_name as user_name',
                'IF(selprod_stock > 0, 1, 0) AS in_stock',
                'selprod_sold_count',
                'selprod_return_policy', /*'maxprice', 'ifnull(sq_sprating.totReviews,0) totReviews','IF(ufp_id > 0, 1, 0) as isfavorite', */
                'selprod_min_order_qty',
                'shop.shop_id',
                'shop.shop_lat',
                'shop.shop_lng',
                'COALESCE(shop_name, shop_identifier) as shop_name',
                'selprod_cart_type',
                'selprod_hide_price',
                'shop.shop_rfq_enabled',
                'product_type'
            )
        );


        $includeRating = false;

        if (true === MOBILE_APP_API_CALL) {
            $includeRating = true;
        }

        if (array_key_exists('top_products', $criteria)) {
            $includeRating = true;
            $srch->addHaving('prod_rating', '>=', 3);
        }

        /*if (!empty($keyword)) {
            $includeRating = true;
        }*/

        if (array_key_exists('sortBy', $criteria)) {
            $sortBy = $criteria['sortBy'];
            $sortByArr = explode("_", $sortBy);
            $sortBy = isset($sortByArr[0]) ? $sortByArr[0] : $sortBy;
            if ($sortBy == 'rating') {
                $includeRating = true;
            }
        }

        if (isset($criteria['vtype']) && $criteria['vtype'] == 'map') {
            $includeRating = true;
        }

        if (true === $includeRating) {
            $selProdReviewObj = new SelProdReviewSearch();
            $selProdReviewObj->joinSelProdRating();
            $selProdReviewObj->addCondition('ratingtype_type', '=', RatingType::TYPE_PRODUCT);
            $selProdReviewObj->doNotCalculateRecords();
            $selProdReviewObj->doNotLimitRecords();
            $selProdReviewObj->addGroupBy('spr.spreview_product_id');
            $selProdReviewObj->addCondition('spr.spreview_status', '=', SelProdReview::STATUS_APPROVED);
            $selProdReviewObj->addMultipleFields(array('spr.spreview_selprod_id', 'spr.spreview_product_id', "ROUND(AVG(sprating_rating),2) as prod_rating", "count(spreview_id) as totReviews"));
            $selProdRviewSubQuery = $selProdReviewObj->getQuery();
            $srch->joinTable('(' . $selProdRviewSubQuery . ')', 'LEFT OUTER JOIN', 'sq_sprating.spreview_product_id = product_id', 'sq_sprating');
            $srch->addMultipleFields(['COALESCE(prod_rating,0) prod_rating', 'COALESCE(totReviews,0) totReviews']);
        }

        if (array_key_exists('category', $criteria)) {
            $srch->addCategoryCondition($criteria['category']);
        }

        if (array_key_exists('prodcat', $criteria)) {
            if (true === MOBILE_APP_API_CALL) {
                $criteria['prodcat'] = json_decode($criteria['prodcat'], true);
            }
            $srch->addCategoryCondition($criteria['prodcat']);
        }

        if (0 < $shop_id) {
            $srch->addShopIdCondition($shop_id);
        }


        if (array_key_exists('collection_id', $criteria)) {
            $collection_id = FatUtility::int($criteria['collection_id']);
            if (0 < $collection_id) {
                $srch->addCollectionIdCondition($collection_id);
            }
        }

        if (!empty($keyword)) {
            $srch->addKeywordSearch($keyword);
            $srch->addFld('if(selprod_title LIKE ' . FatApp::getDb()->quoteVariable('%' . $keyword . '%') . ',  1,   0  ) as keywordmatched');
            $srch->addFld('if(selprod_title LIKE ' . FatApp::getDb()->quoteVariable('%' . $keyword . '%') . ',  IFNULL(splprice_price, selprod_price),   theprice ) as theprice');
            $srch->addFld(
                'if(selprod_title LIKE ' . FatApp::getDb()->quoteVariable('%' . $keyword . '%') . ',  CASE WHEN splprice_selprod_id IS NULL THEN 0 ELSE 1
END,   special_price_found ) as special_price_found'
            );
            $sortBy = 'keyword_relevancy';
        } else {
            $srch->addFld('theprice');
            $srch->addFld('special_price_found');
            $sortBy = 'popularity';
        }

        if (array_key_exists('brand', $criteria)) {
            if (!empty($criteria['brand'])) {
                if (true === MOBILE_APP_API_CALL && !is_array($criteria['brand'])) {
                    $criteria['brand'] = json_decode($criteria['brand'], true);
                }
                $srch->addBrandCondition($criteria['brand']);
            }
        }

        if (array_key_exists('optionvalue', $criteria)) {
            if (!empty($criteria['optionvalue'])) {
                $srch->addOptionCondition($criteria['optionvalue']);
            }
        }

        if (array_key_exists('condition', $criteria)) {
            if (true === MOBILE_APP_API_CALL) {
                $criteria['condition'] = json_decode($criteria['condition'], true);
            }
            $condition = is_array($criteria['condition']) ? array_filter($criteria['condition']) : $criteria['condition'];
            $srch->addConditionCondition($condition);
        }

        if (array_key_exists('out_of_stock', $criteria)) {
            if (!empty($criteria['out_of_stock']) && $criteria['out_of_stock'] == 1) {
                $srch->excludeOutOfStockProducts();
            }
        }

        $minPriceRange = '';
        if (array_key_exists('price-min-range', $criteria)) {
            $minPriceRange = $criteria['price-min-range'];
        } elseif (array_key_exists('min_price_range', $criteria)) {
            $minPriceRange = $criteria['min_price_range'];
        }
        //currency_id
        if (!empty($minPriceRange)) {
            $currCurrencyId = isset($criteria['currency_id']) ? $criteria['currency_id'] : FatApp::getConfig('CONF_CURRENCY', FatUtility::VAR_INT, 1);
            $min_price_range_default_currency = CommonHelper::convertExistingToOtherCurrency($currCurrencyId, $minPriceRange, FatApp::getConfig('CONF_CURRENCY', FatUtility::VAR_INT, 1), false);
            //$min_price_range_default_currency =  CommonHelper::getDefaultCurrencyValue($minPriceRange, false, false);
            $srch->addHaving('theprice', '>=', $min_price_range_default_currency);
        }

        $maxPriceRange = '';
        if (array_key_exists('price-max-range', $criteria)) {
            $maxPriceRange = $criteria['price-max-range'];
        } elseif (array_key_exists('max_price_range', $criteria)) {
            $maxPriceRange = $criteria['max_price_range'];
        }

        if (!empty($maxPriceRange)) {
            $currCurrencyId = isset($criteria['currency_id']) ? $criteria['currency_id'] : FatApp::getConfig('CONF_CURRENCY', FatUtility::VAR_INT, 1);
            $max_price_range_default_currency = CommonHelper::convertExistingToOtherCurrency($currCurrencyId, $maxPriceRange, FatApp::getConfig('CONF_CURRENCY', FatUtility::VAR_INT, 1), false);
            //$max_price_range_default_currency =  CommonHelper::getDefaultCurrencyValue($maxPriceRange, false, false);
            $srch->addHaving('theprice', '<=', $max_price_range_default_currency);
        }

        if (array_key_exists('featured', $criteria)) {
            $featured = FatUtility::int($criteria['featured']);
            if (0 < $featured) {
                $srch->addCondition('product_featured', '=', $featured);
            }
        }

        //var_dump($criteria); exit;
        //$srch->addOrder('in_stock', 'DESC');

        if (array_key_exists('sortBy', $criteria)) {
            $sortBy = $criteria['sortBy'];
        }

        $srch->addCondition('selprod_deleted', '=', applicationConstants::NO);
        // $srch->addCondition('selprod_available_from', '>=', FatDate::nowInTimezone(FatApp::getConfig('CONF_TIMEZONE'), 'Y-m-d'));

        $srch->addGroupBy('product_id');
        if (!empty($keyword)) {
            $srch->addGroupBy('keywordmatched');
            // $srch->addOrder('keywordmatched', 'desc');
        }

        return $srch;
    }

    public static function setOrderOnListingObj(&$srch, &$get = [])
    {
        $srch->doNotCalculateRecords();

        $keyword = '';
        if (array_key_exists('keyword', $get)) {
            $keyword = $get['keyword'];
        }

        $sortBy = 'popularity';
        if (array_key_exists('keyword', $get) && !empty($get['keyword'])) {
            $sortBy = 'keyword_relevancy';
        }

        if (array_key_exists('sortBy', $get)) {
            $sortBy = $get['sortBy'];
        }

        $sortOrder = 'desc';
        if (array_key_exists('sortOrder', $get)) {
            $sortOrder = $get['sortOrder'];
        }

        if (!empty($sortBy)) {
            $sortByArr = explode("_", $sortBy);
            $sortBy = isset($sortByArr[0]) ? $sortByArr[0] : $sortBy;
            $sortOrder = isset($sortByArr[1]) ? $sortByArr[1] : $sortOrder;

            if (!in_array($sortOrder, array('asc', 'desc'))) {
                $sortOrder = 'desc';
            }

            if (!in_array($sortBy, array('keyword', 'price', 'popularity', 'rating', 'discounted'))) {
                $sortOrder = 'keyword_relevancy';
            }

            switch ($sortBy) {
                case 'keyword':
                    if (FatApp::getConfig('CONF_ENABLE_GEO_LOCATION', FatUtility::VAR_INT, 0) && !empty(FatApp::getConfig('CONF_GOOGLEMAP_API_KEY', FatUtility::VAR_STRING, '')) && FatApp::getConfig('CONF_PRODUCT_GEO_LOCATION', FatUtility::VAR_INT, 0) != applicationConstants::BASED_ON_CURRENT_LOCATION && !Plugin::isActiveByType(Plugin::TYPE_SHIPPING_SERVICES)) {
                        $srch->addOrder('availableInLocation', 'DESC');
                    }
                    $srch->addOrder('keyword_relevancy', 'DESC');
                    break;
                case 'price':
                    $srch->addOrder('theprice', $sortOrder);
                    break;
                case 'popularity':
                    if (FatApp::getConfig('CONF_ENABLE_GEO_LOCATION', FatUtility::VAR_INT, 0) && !empty(FatApp::getConfig('CONF_GOOGLEMAP_API_KEY', FatUtility::VAR_STRING, '')) && FatApp::getConfig('CONF_PRODUCT_GEO_LOCATION', FatUtility::VAR_INT, 0) != applicationConstants::BASED_ON_CURRENT_LOCATION && !Plugin::isActiveByType(Plugin::TYPE_SHIPPING_SERVICES)) {
                        $srch->addOrder('availableInLocation', 'DESC');
                    }
                    $srch->addOrder('selprod_sold_count', $sortOrder);
                    break;
                case 'discounted':
                    $srch->addFld('ROUND(((selprod_price - theprice)*100)/selprod_price) as discountedValue');
                    $srch->addOrder('discountedValue', 'DESC');
                    break;
                case 'rating':
                    $srch->addOrder('prod_rating', $sortOrder);
                    break;
                default:
                    $srch->addOrder('keyword_relevancy', 'DESC');
                    break;
            }
        }

        if (array_key_exists('keyword', $get) && !empty($get['keyword'])) {
            $srch->addOrder('keywordmatched', 'desc');
        }
        $srch->addOrder('selprod_id', 'DESC');
        return $srch;
    }

    public static function getActiveCount($sellerId, $prodId = 0, $checkApproved = true)
    {
        if (0 > FatUtility::int($sellerId)) {
            return false;
        }
        $prodId = FatUtility::int($prodId);

        $srch = new SearchBase(static::DB_TBL);

        $srch->addCondition(static::DB_TBL_PREFIX . 'seller_id', '=', $sellerId);

        $srch->addMultipleFields(array(static::DB_TBL_PREFIX . 'id'));
        $srch->addCondition(static::DB_TBL_PREFIX . 'active', '=', applicationConstants::YES);
        $srch->addCondition(static::DB_TBL_PREFIX . 'deleted', '=', applicationConstants::NO);
        if ($checkApproved) {
            $srch->addCondition(static::DB_TBL_PREFIX . 'approved', '=', applicationConstants::YES);
        }
        if ($prodId) {
            $srch->addCondition(static::DB_TBL_PREFIX . 'id', '!=', $prodId);
        }
        $srch->getResultSet();
        return $srch->recordCount();
    }

    public static function isShippedBySeller($selprodUserId = 0, $productSellerId = 0, $shippedBySellerId = false)
    {
        $productSellerId = FatUtility::int($productSellerId);
        $selprodUserId = FatUtility::int($selprodUserId);
        if (FatApp::getConfig('CONF_SHIPPED_BY_ADMIN_ONLY', FatUtility::VAR_INT, 0)) {
            return false;
        }

        if ($productSellerId > 0 && $selprodUserId == $productSellerId) {
            /* Catalog-Product Added By Seller so also shipped by seller */
            return $selprodUserId;
        } else {
            $shippedBySellerId = FatUtility::int($shippedBySellerId);
            if ($shippedBySellerId > 0 && $selprodUserId == $shippedBySellerId) {
                return $shippedBySellerId;
            }
        }
        return false;
    }

    public static function updateMinPrices($productId = 0, $shopId = 0, $brandId = 0, $countryId = 0, $stateId = 0)
    {
        $criteria = array();
        $shopId = FatUtility::int($shopId);
        $brandId = FatUtility::int($brandId);
        $productId = FatUtility::int($productId);
        $countryId = FatUtility::int($countryId);

        $criteria = array(
            'max_price' => true,
            'product_id' => $productId,
            'brand_id' => $brandId,
            'country_id' => $countryId,
            'shop_id' => $shopId,
            'state_id' => $stateId,
            'doNotJoinSellers' => true,
            'doNotJoinShippingPkg' => true,
            'doNotJoinShopCountry' => true,
            'doNotJoinShopState' => true,
        );

        $srch = new ProductSearch();
        $srch->addMultipleFields(array('DISTINCT(product_id) as product_id', 'selprod_id', 'theprice', 'IFNULL(splprice_id, 0) as splprice_id'));
        $srch->setDefinedCriteria(1, 0, $criteria, true, false);
        $srch->joinProductToCategory();
        $srch->addCondition('selprod_active', '=', applicationConstants::YES);
        $srch->addCondition('selprod_deleted', '=', applicationConstants::NO);
        $srch->addCondition('selprod_available_from', '<=', FatDate::nowInTimezone(FatApp::getConfig('CONF_TIMEZONE'), 'Y-m-d'));
        $srch->doNotLimitRecords();
        $srch->doNotCalculateRecords();
        $srch->removeFld('1 as availableInLocation');

        // $srch->addGroupBy('product_id');

        if (0 < $productId) {
            $srch->addCondition('product_id', '=', $productId);
        }

        if (0 < $brandId) {
            $srch->addCondition('brand_id', '=', $brandId);
        }

        if (0 < $shopId) {
            $srch->addCondition('shop_id', '=', $shopId);
        }

        if (0 < $countryId) {
            $srch->addCondition('country_id', '=', $countryId);
        }
        if (0 < $stateId) {
            $srch->addCondition('state_id', '=', $stateId);
        }

        $tmpQry = $srch->getQuery();

        $qry = "INSERT INTO " . static::DB_PRODUCT_MIN_PRICE . " (pmp_product_id, pmp_selprod_id, pmp_min_price, pmp_splprice_id) SELECT * FROM (" . $tmpQry . ") AS t ON DUPLICATE KEY UPDATE pmp_selprod_id = t.selprod_id, pmp_min_price = t.theprice, pmp_splprice_id = t.splprice_id";
        FatApp::getDb()->query($qry);

        $query = "DELETE m FROM " . static::DB_PRODUCT_MIN_PRICE . " m LEFT OUTER JOIN (" . $tmpQry . ") as t ON m.pmp_product_id = product_id WHERE m.pmp_product_id IS NULL";
        FatApp::getDb()->query($query);
    }

    public static function getProductsCount()
    {
        $srch = static::getSearchObject();
        $srch->addFld('COUNT(' . static::DB_TBL_PREFIX . 'id) as total_products');
        $srch->doNotCalculateRecords();
        $rs = $srch->getResultSet();
        return FatApp::getDb()->fetch($rs);
    }

    public function saveProductData($data)
    {
        if (empty($data)) {
            $this->error = Labels::getLabel('ERR_INVALID_REQUEST', $this->commonLangId);
            return false;
        }

        unset($data['product_id']);
        if ($this->mainTableRecordId < 1) {
            $data['product_added_on'] = 'mysql_func_now()';
            $data['product_added_by_admin_id'] = isset($data['product_added_by_admin_id']) ? $data['product_added_by_admin_id'] : applicationConstants::YES;
        }
        $this->assignValues($data, true);
        if (!$this->save()) {
            $this->error = $this->getError();
            return false;
        }
        return true;
    }

    public function saveProductLangData($langData)
    {
        if ($this->mainTableRecordId < 1 || empty($langData)) {
            $this->error = Labels::getLabel('ERR_INVALID_REQUEST', $this->commonLangId);
            return false;
        }

        $autoUpdateOtherLangsData = isset($langData['auto_update_other_langs_data']) ? FatUtility::int($langData['auto_update_other_langs_data']) : 0;
        foreach ($langData['product_name'] as $langId => $prodName) {
            if (empty($prodName) && $autoUpdateOtherLangsData > 0) {
                $this->saveTranslatedProductLangData($langId);
            } elseif (!empty($prodName)) {
                $data = array(
                    static::DB_TBL_LANG_PREFIX . 'product_id' => $this->mainTableRecordId,
                    static::DB_TBL_LANG_PREFIX . 'lang_id' => $langId,
                    'product_name' => $prodName,
                    'product_description' => $langData['product_description_' . $langId],
                    'product_youtube_video' => $langData['product_youtube_video'][$langId],
                );
                if (!$this->updateLangData($langId, $data)) {
                    $this->error = $this->getError();
                    return false;
                }
            }
        }
        return true;
    }

    public function saveTranslatedProductLangData($langId)
    {
        $langId = FatUtility::int($langId);
        if ($this->mainTableRecordId < 1 || $langId < 1) {
            $this->error = Labels::getLabel('ERR_INVALID_REQUEST', $this->commonLangId);
            return false;
        }

        $translateLangobj = new TranslateLangData(static::DB_TBL_LANG);
        if (false === $translateLangobj->updateTranslatedData($this->mainTableRecordId, 0, $langId)) {
            $this->error = $translateLangobj->getError();
            return false;
        }
        return true;
    }

    public function getTranslatedProductData($data, $toLangId)
    {
        $toLangId = FatUtility::int($toLangId);
        if (empty($data) || $toLangId < 1) {
            $this->error = Labels::getLabel('ERR_INVALID_REQUEST', $this->commonLangId);
            return false;
        }

        $translateLangobj = new TranslateLangData(static::DB_TBL_LANG);
        $translatedData = $translateLangobj->directTranslate($data, $toLangId);
        if (false === $translatedData) {
            $this->error = $translateLangobj->getError();
            return false;
        }
        return $translatedData;
    }

    public function saveProductCategory($categoryId)
    {
        $categoryId = FatUtility::int($categoryId);
        if ($this->mainTableRecordId < 1 || $categoryId < 1) {
            $this->error = Labels::getLabel('ERR_INVALID_REQUEST', $this->commonLangId);
            return false;
        }

        FatApp::getDb()->deleteRecords(static::DB_TBL_PRODUCT_TO_CATEGORY, array('smt' => static::DB_TBL_PRODUCT_TO_CATEGORY_PREFIX . 'product_id = ?', 'vals' => array($this->mainTableRecordId)));

        $record = new TableRecord(static::DB_TBL_PRODUCT_TO_CATEGORY);
        $data = array(
            static::DB_TBL_PRODUCT_TO_CATEGORY_PREFIX . 'product_id' => $this->mainTableRecordId,
            static::DB_TBL_PRODUCT_TO_CATEGORY_PREFIX . 'prodcat_id' => $categoryId
        );
        $record->assignValues($data);
        if (!$record->addNew(array(), $data)) {
            $this->error = $record->getError();
            return false;
        }
        return true;
    }

    public function saveProductTax($taxId, $userId = 0)
    {
        $taxId = FatUtility::int($taxId);
        if ($this->mainTableRecordId < 1 || $taxId < 1) {
            $this->error = Labels::getLabel('ERR_INVALID_REQUEST', $this->commonLangId);
            return false;
        }

        $data = array(
            'ptt_product_id' => $this->mainTableRecordId,
            'ptt_taxcat_id' => $taxId,
            'ptt_seller_user_id' => $userId
        );
        $tax = new Tax();
        if ($userId > 0) {
            $tax->removeTaxSetByAdmin($this->mainTableRecordId);
        }
        if (!$tax->addUpdateProductTaxCat($data)) {
            $this->error = $tax->getError();
            return false;
        }
        return true;
    }

    public static function getCatalogProductCount($productId)
    {
        $productId = FatUtility::int($productId);
        $srch = SellerProduct::getSearchObject();
        $srch->joinTable(Product::DB_TBL, 'INNER JOIN', 'p.product_id = sp.selprod_product_id', 'p');
        $srch->addCondition('selprod_deleted', '=', 0);
        $srch->addCondition('selprod_product_id', '=', $productId);
        $srch->addFld('selprod_id');
        $rs = $srch->getResultSet();
        return $srch->recordCount();
    }

    public function saveProductSpecifications($prodSpecId, $langId, $prodSpecName, $prodSpecValue, $prodSpecGroup)
    {
        $this->prodSpecId = FatUtility::int($prodSpecId);
        $langId = FatUtility::int($langId);
        if ($langId < 1 || empty($prodSpecName) || empty($prodSpecValue) || ($this->prodSpecId < 1 && $this->mainTableRecordId < 1)) {
            $this->error = Labels::getLabel('ERR_PLEASE_FILL_PRODUCT_SPEICIFICATION_TEXT_AND_VALUE', $this->commonLangId);
            return false;
        }

        if ($this->prodSpecId < 1) {
            $prodSpec = new ProdSpecification($this->prodSpecId);
            $data['prodspec_product_id'] = $this->mainTableRecordId;
            $prodSpec->assignValues($data);
            if (!$prodSpec->save()) {
                $this->error = $prodSpec->getError();
                return false;
            }
            $this->prodSpecId = $prodSpec->getMainTableRecordId();
        }

        $prodSpec = new ProdSpecification($this->prodSpecId);
        $langData = array(
            'prodspeclang_prodspec_id' => $this->prodSpecId,
            'prodspeclang_lang_id' => $langId,
            'prodspec_name' => $prodSpecName,
            'prodspec_value' => $prodSpecValue,
            'prodspec_group' => $prodSpecGroup
        );
        if (!$prodSpec->updateLangData($langId, $langData)) {
            $this->error = $prodSpec->getError();
            return false;
        }
        return true;
    }

    public function getProdSpecificationsByLangId($langId)
    {
        $langId = FatUtility::int($langId);
        if ($this->mainTableRecordId < 1 || $langId < 1) {
            $this->error = Labels::getLabel('ERR_INVALID_REQUEST', $this->commonLangId);
            return false;
        }
        $srch = new SearchBase(static::DB_PRODUCT_SPECIFICATION);
        $srch->joinTable(static::DB_PRODUCT_LANG_SPECIFICATION, 'LEFT JOIN', static::DB_PRODUCT_SPECIFICATION_PREFIX . 'id = ' . static::DB_PRODUCT_LANG_SPECIFICATION_PREFIX . 'prodspec_id');
        $srch->addCondition(static::DB_PRODUCT_SPECIFICATION_PREFIX . 'product_id', '=', $this->mainTableRecordId);
        $srch->addCondition(static::DB_PRODUCT_LANG_SPECIFICATION_PREFIX . 'lang_id', '=', $langId);
        $srch->addMultipleFields(
            array(
                static::DB_PRODUCT_SPECIFICATION_PREFIX . 'id',
                static::DB_PRODUCT_SPECIFICATION_PREFIX . 'name',
                static::DB_PRODUCT_SPECIFICATION_PREFIX . 'value',
                static::DB_PRODUCT_SPECIFICATION_PREFIX . 'group'
            )
        );
        $srch->doNotCalculateRecords();
        $rs = $srch->getResultSet();
        return FatApp::getDb()->fetchAll($rs);
    }

    public function saveProductSellerShipping($prodSellerId, $psFree, $psCountryId)
    {
        if ($this->mainTableRecordId < 1) {
            $this->error = Labels::getLabel('ERR_INVALID_REQUEST', $this->commonLangId);
            return false;
        }
        $prodSellerShip = array(
            'ps_product_id' => $this->mainTableRecordId,
            'ps_user_id' => $prodSellerId,
            'ps_free' => $psFree,
            'ps_from_country_id' => $psCountryId
        );
        if (!FatApp::getDb()->insertFromArray(Product::DB_TBL_PRODUCT_SHIPPING, $prodSellerShip, false, array(), $prodSellerShip)) {
            $this->error = FatApp::getDb()->getError();
            return false;
        }
        return true;
    }

    public static function getProductSpecificsDetails($productId)
    {
        $productId = FatUtility::int($productId);
        if ($productId < 1) {
            return false;
        }
        $srch = new SearchBase(ProductSpecifics::DB_TBL);
        $srch->addCondition(ProductSpecifics::DB_TBL_PREFIX . 'product_id', '=', $productId);
        $srch->doNotCalculateRecords();
        $rs = $srch->getResultSet();
        return FatApp::getDb()->fetch($rs);
    }

    public static function isShipFromConfigured($productId, $userId = 0)
    {
        $productId = FatUtility::int($productId);
        $userId = FatUtility::int($userId);

        $srch = new SearchBase(static::DB_TBL_PRODUCT_SHIPPING, 'ps');
        $srch->addCondition('ps_product_id', '=', $productId);
        $srch->addCondition('ps_user_id', '=', $userId);
        $srch->setPageSize(1);
        $srch->doNotCalculateRecords();
        $res = FatApp::getDb()->fetch($srch->getResultSet());
        if (!empty($res)) {
            return true;
        }
        return false;
    }

    public function updateUpdatedOn()
    {
        $productId = FatUtility::int($this->mainTableRecordId);
        FatApp::getDb()->updateFromArray('tbl_products', array('product_updated_on' => date('Y-m-d H:i:s')), array('smt' => 'product_id = ?', 'vals' => array($productId)));
    }


    /**
     * setProductFulfillmentType - Need to enhance later.
     *
     * @param  int $productId
     * @param  int $loggedUserId
     * @param  int $fulfillmentType
     * @return int
     */
    public static function setProductFulfillmentType(int $productId, int $loggedUserId, int $fulfillmentType): int
    {
        return $fulfillmentType;
    }

    public static function getProdIdByPlugin(int $pluginId, int $pluginProdId): int
    {
        $srch = new SearchBase(static::DB_PRODUCT_TO_PLUGIN_PRODUCT);
        $srch->addCondition(static::DB_PRODUCT_TO_PLUGIN_PRODUCT_PREFIX . 'plugin_id', '=', $pluginId);
        $srch->addCondition(static::DB_PRODUCT_TO_PLUGIN_PRODUCT_PREFIX . 'plugin_product_id', '=', $pluginProdId);
        $srch->addFld('ptpp_product_id');
        $srch->doNotCalculateRecords();
        $rs = $srch->getResultSet();
        $records = FatApp::getDb()->fetch($rs);
        if (!$records) {
            return 0;
        }
        return $records['ptpp_product_id'];
    }

    public static function getMoreSeller($selprodCode, $langId, $userId = 0, $includeSeller = false)
    {
        $userId = FatUtility::int($userId);
        $langId = FatUtility::int($langId);

        $moreSellerSrch = new ProductSearch($langId);
        $moreSellerSrch->setGeoAddress();
        $moreSellerSrch->addMoreSellerCriteria($selprodCode, $userId, $includeSeller);
        $moreSellerSrch->validateAndJoinDeliveryLocation();
        /*$moreSellerSrch->addMultipleFields(array( 'selprod_id', 'selprod_user_id', 'selprod_price', 'special_price_found', 'theprice', 'shop_id', 'shop_name' ,'IF(selprod_stock > 0, 1, 0) AS in_stock'));*/
        $moreSellerSrch->addMultipleFields(
            array(
                'selprod_id',
                'selprod_user_id',
                'selprod_price',
                'special_price_found',
                'theprice',
                'shop_id',
                'shop_name',
                'shop_user_id',
                'product_seller_id',
                'product_id',
                'shop_country_l.country_name as shop_country_name',
                'shop_state_l.state_name as shop_state_name',
                'shop_city',
                'shop_rfq_enabled',
                'selprod_cod_enabled',
                'product_cod_enabled',
                'IF(selprod_stock > 0, 1, 0) AS in_stock',
                'selprod_min_order_qty',
                'selprod_available_from',
                'shop_lat',
                'shop_lng',
                'product_updated_on',
                'selprod_title',
                'selprod_code',
                'selprod_cart_type',
                'selprod_hide_price'
            )
        );
        $moreSellerSrch->addHaving('in_stock', '>', 0);
        $moreSellerSrch->addOrder('theprice');
        $moreSellerSrch->addGroupBy('selprod_id');
        $moreSellerSrch->doNotCalculateRecords();

        return FatApp::getDb()->fetchAll($moreSellerSrch->getResultSet());
    }

    public static function getStatusHtml(int $langId, int $status): string
    {
        $arr = self::getApproveUnApproveArr($langId);
        $msg = $arr[$status] ?? Labels::getLabel('LBL_N/A', $langId);
        switch ($status) {
            case static::APPROVED:
                $status = HtmlHelper::SUCCESS;
                break;
            default:
                $status = HtmlHelper::DANGER;
                break;
        }
        return HtmlHelper::getStatusHtml($status, $msg);
    }

    /**
     * move images from temp to main table
     */
    public function moveTempFiles(int $tempRecordId)
    {
        if (!$this->mainTableRecordId) {
            $this->error = Labels::getLabel('ERR_INVALID_REQUEST', $this->commonLangId);
            return false;
        }

        $db = FatApp::getDb();
        $sql = "INSERT INTO tbl_attached_files(
            afile_type,
            afile_record_id,
            afile_record_subid,
            afile_lang_id,
            afile_screen,
            afile_physical_path,
            afile_name,
            afile_attribute_title,
            afile_attribute_alt,
            afile_aspect_ratio,
            afile_display_order,
            afile_updated_at
        )
        SELECT
            " . AttachedFile::FILETYPE_PRODUCT_IMAGE . ",
            $this->mainTableRecordId,
            afile_record_subid,
            afile_lang_id,
            afile_screen,
            afile_physical_path,
            afile_name,
            afile_attribute_title,
            afile_attribute_alt,
            afile_aspect_ratio,
            afile_display_order,
            afile_updated_at
        FROM
            tbl_attached_files_temp
        WHERE
            afile_type = " . AttachedFile::FILETYPE_PRODUCT_IMAGE_TEMP . " AND afile_record_id = $tempRecordId";


        if (!$db->query($sql)) {
            $this->error = $db->getError();
            return false;
        }

        $sql = "delete from tbl_attached_files_temp where afile_type = " . AttachedFile::FILETYPE_PRODUCT_IMAGE_TEMP . " AND afile_record_id = $tempRecordId";
        if (!$db->query($sql)) {
            $this->error = $db->getError();
            return false;
        }

        $db->updateFromArray('tbl_products', array('product_img_updated_on' => date('Y-m-d H:i:s')), array('smt' => 'product_id = ?', 'vals' => array($this->mainTableRecordId)));
        if (!$db->query($sql)) {
            $this->error = $db->getError();
            return false;
        }
        return true;
    }
}
