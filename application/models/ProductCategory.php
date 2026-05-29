<?php

class ProductCategory extends MyAppModel
{
    public const DB_TBL = 'tbl_product_categories';
    public const DB_TBL_PREFIX = 'prodcat_';
    public const DB_TBL_LANG = 'tbl_product_categories_lang';
    public const DB_TBL_LANG_PREFIX = 'prodcatlang_';

    public const DB_TBL_PROD_CAT_RELATIONS = 'tbl_product_category_relations';
    public const DB_TBL_PROD_CAT_REL_PREFIX = 'pcr_';

    public const DB_TBL_PROD_CAT_RATING_TYPES = 'tbl_prodcat_rating_types';
    public const DB_TBL_PROD_CAT_RT_PREFIX = 'prt_';

    public const REWRITE_URL_PREFIX = 'category/view/';
    public const REMOVED_OLD_IMAGE_TIME = 4;
    private $categoryTreeArr = array();

    public const REQUEST_PENDING = 0;
    public const REQUEST_APPROVED = 1;
    public const REQUEST_CANCELLED = 2;

    private $pageNumber = 0;

    public function __construct($id = 0)
    {
        parent::__construct(static::DB_TBL, static::DB_TBL_PREFIX . 'id', $id);
        $this->objMainTableRecord->setSensitiveFields([self::DB_TBL_PREFIX . 'id']);
    }

    public static function getStatusArr($langId)
    {
        $langId = FatUtility::int($langId);
        if ($langId == 0) {
            trigger_error(Labels::getLabel('ERR_LANGUAGE_ID_NOT_SPECIFIED.', CommonHelper::getLangId()), E_USER_ERROR);
        }
        $arr = array(
            static::REQUEST_PENDING => Labels::getLabel('LBL_PENDING', $langId),
            static::REQUEST_APPROVED => Labels::getLabel('LBL_APPROVED', $langId),
            static::REQUEST_CANCELLED => Labels::getLabel('LBL_CANCELLED', $langId)
        );
        return $arr;
    }

    public static function getStatusClassArr()
    {
        return array(
            static::REQUEST_PENDING => applicationConstants::CLASS_INFO,
            static::REQUEST_APPROVED => applicationConstants::CLASS_SUCCESS,
            static::REQUEST_CANCELLED => applicationConstants::CLASS_DANGER
        );
    }

    public static function getSearchObject($includeChildCount = false, $langId = 0, $prodcatActive = true, $prodcatStatus = 1)
    {
        $langId = FatUtility::int($langId);
        $prodcatStatus = FatUtility::int($prodcatStatus);
        $srch = new SearchBase(static::DB_TBL, 'm');

        if ($includeChildCount) {
            $childSrchbase = new SearchBase(static::DB_TBL, 'pcc');
            $childSrchbase->addCondition('pcc.prodcat_deleted', '=', 'mysql_func_' . applicationConstants::NO, 'AND', true);
            $childSrchbase->doNotCalculateRecords();
            $childSrchbase->doNotLimitRecords();

            $srch->joinTable('(' . $childSrchbase->getQuery() . ')', 'LEFT OUTER JOIN', 's.prodcat_parent = m.prodcat_id', 's');
            $srch->addGroupBy('m.prodcat_id');
            $srch->addFld('COUNT(s.prodcat_id) AS child_count');
        }

        if ($langId > 0) {
            $srch->joinTable(
                static::DB_TBL_LANG,
                'LEFT OUTER JOIN',
                'pc_l.' . static::DB_TBL_LANG_PREFIX . 'prodcat_id = m.' . static::tblFld('id') . ' and
			pc_l.' . static::DB_TBL_LANG_PREFIX . 'lang_id = ' . $langId,
                'pc_l'
            );
        }

        if (-1 != $prodcatStatus) {
            $srch->addCondition('m.prodcat_status', '=', 'mysql_func_' . $prodcatStatus, 'AND', true);
        }

        if ($prodcatActive) {
            $srch->addCondition('m.prodcat_active', '=', 'mysql_func_' . applicationConstants::ACTIVE, 'AND', true);
        }

        return $srch;
    }

    public static function requiredFields()
    {
        return array(
            ImportexportCommon::VALIDATE_POSITIVE_INT => array(
                'prodcat_id'
            ),
            ImportexportCommon::VALIDATE_NOT_NULL => array(
                'prodcat_identifier',
                'prodcat_name',
            )
        );
    }

    public static function validateFields($columnIndex, $columnTitle, $columnValue, $langId)
    {
        $requiredFields = static::requiredFields();
        return ImportexportCommon::validateFields($requiredFields, $columnIndex, $columnTitle, $columnValue, $langId);
    }

    public static function requiredMediaFields()
    {
        return array(
            ImportexportCommon::VALIDATE_POSITIVE_INT => array(
                'prodcat_id'
            ),
            ImportexportCommon::VALIDATE_NOT_NULL => array(
                'prodcat_identifier',
                'afile_physical_path',
                'afile_name',
                'afile_type',
            )
        );
    }

    public static function validateMediaFields($columnIndex, $columnTitle, $columnValue, $langId)
    {
        $requiredFields = static::requiredMediaFields();
        return ImportexportCommon::validateFields($requiredFields, $columnIndex, $columnTitle, $columnValue, $langId);
    }

    public function updateCatCode()
    {
        $categoryId = $this->mainTableRecordId;
        if (1 > $categoryId) {
            return false;
        }

        $categoryArray = array($categoryId);

        /* Not Required. */
        /* $parentCatData = ProductCategory::getAttributesById($categoryId, array('prodcat_parent'));
        if (array_key_exists('prodcat_parent', $parentCatData) && $parentCatData['prodcat_parent'] > 0) {
            array_push($categoryArray, $parentCatData['prodcat_parent']);
        } */

        foreach ($categoryArray as $categoryId) {
            $srch = ProductCategory::getSearchObject(false, 0, false, -1);
            $srch->addOrder('m.prodcat_active', 'DESC');
            $srch->doNotCalculateRecords();
            $srch->doNotLimitRecords();
            $srch->addMultipleFields(array('prodcat_id', 'GETCATCODE(`prodcat_id`) as prodcat_code', 'GETCATORDERCODE(`prodcat_id`) as prodcat_ordercode'));
            $srch->addCondition('GETCATCODE(`prodcat_id`)', 'LIKE', '%' . str_pad($categoryId, 6, '0', STR_PAD_LEFT) . '%', 'AND', true);
            $rs = $srch->getResultSet();
            $catCode = FatApp::getDb()->fetchAll($rs);
            foreach ($catCode as $row) {
                $record = new ProductCategory($row['prodcat_id']);
                $data = array('prodcat_code' => $row['prodcat_code'], 'prodcat_ordercode' => $row['prodcat_ordercode']);
                $record->assignValues($data);
                if (!$record->save()) {
                    Message::addErrorMessage($record->getError());
                    return false;
                }
            }
        }
        return true;
    }

    public static function updateCatOrderCode($prodCatId = 0)
    {
        $prodCatId = FatUtility::int($prodCatId);

        $srch = ProductCategory::getSearchObject(false, 0, false);
        $srch->addOrder('m.prodcat_active', 'DESC');
        $srch->doNotCalculateRecords();
        $srch->doNotLimitRecords();
        $srch->addMultipleFields(array('prodcat_id', 'GETCATORDERCODE(`prodcat_id`) as prodcat_ordercode'));
        if ($prodCatId) {
            $srch->addCondition('prodcat_id', '=', 'mysql_func_' . $prodCatId, 'AND', true);
        }

        $rs = $srch->getResultSet();
        $orderCode = FatApp::getDb()->fetchAll($rs);
        foreach ($orderCode as $row) {
            $record = new ProductCategory($row['prodcat_id']);
            $data = array('prodcat_ordercode' => $row['prodcat_ordercode']);
            $record->assignValues($data);
            if (!$record->save()) {
                Message::addErrorMessage($record->getError());
                return false;
            }
        }
    }

    public function getMaxOrder($parent = 0)
    {
        $srch = new SearchBase(static::DB_TBL);
        $srch->addFld("MAX(" . static::DB_TBL_PREFIX . "display_order) as max_order");
        if ($parent > 0) {
            $srch->addCondition(static::DB_TBL_PREFIX . 'parent', '=', 'mysql_func_' . $parent, 'AND', true);
        }
        $srch->doNotCalculateRecords();
        $srch->doNotLimitRecords();
        $rs = $srch->getResultSet();
        $record = FatApp::getDb()->fetch($rs);
        if (!empty($record)) {
            return $record['max_order'] + 1;
        }
        return 1;
    }

    public static function getArray($langId, $parentId = 0, $sortByName = false, $excludeCatHavingNoProducts = false, $keywords = false, $useCache = false, $parseTree = true)
    {
        $cacheKey = $langId . '-' . $parentId . '-' . $sortByName . '-' . $excludeCatHavingNoProducts . '-' . $keywords . '-' . $parseTree;
        global $rootCatArr;
        if (!empty($rootCatArr) && array_key_exists($cacheKey, $rootCatArr)) {
            return $rootCatArr[$cacheKey];
        }

        if (true == $useCache) {
            $categoryArrCache = CacheHelper::get('categoryArrCache' . $cacheKey, CONF_HOME_PAGE_CACHE_TIME, '.txt');
            if ($categoryArrCache) {
                return unserialize($categoryArrCache);
            }
        }

        if (0 < $parentId) {
            $catCode = static::getAttributesById($parentId, 'prodcat_code');
        }

        $srch = new SearchBase(self::DB_TBL_PROD_CAT_RELATIONS, 'cr');
        if ($excludeCatHavingNoProducts) {
            $prodSrchObj = new ProductSearch();
            // $prodSrchObj->addMultipleFields(array('count(selprod_id) as productCounts', 'c.prodcat_id as qryProducts_prodcat_id'));
            $prodSrchObj->addMultipleFields(array('DISTINCT(prodcat_code)', 'cr.pcr_parent_id as qryProducts_prodcat_id'));
            $prodSrchObj->setDefinedCriteria(0, 0, array('doNotJoinSpecialPrice' => true, 'doNotJoinSellers' => true, 'doNotJoinShippingPkg' => true));
            $prodSrchObj->joinSellerSubscription(0, true);
            $prodSrchObj->addSubscriptionValidCondition();
            $prodSrchObj->doNotCalculateRecords();
            $prodSrchObj->doNotLimitRecords();
            $prodSrchObj->joinProductToCategory();
            $prodSrchObj->joinCategoryRelationWithChild();
            $prodSrchObj->addCondition('selprod_deleted', '=', 'mysql_func_' . applicationConstants::NO, 'AND', true);
            if (0 < $parentId) {
                $prodSrchObj->addCondition('prodcat_code', 'like', $catCode . '%');
            }
            /*  echo $prodSrchObj->getQuery();
            exit; */
            $srch->joinTable('(' . $prodSrchObj->getQuery() . ')', 'INNER JOIN', 'qryProducts.qryProducts_prodcat_id = cr.pcr_prodcat_id', 'qryProducts');
        }
        $srch->joinTable(self::DB_TBL, 'INNER JOIN', 'c.prodcat_id = cr.pcr_prodcat_id', 'c');
        $srch->joinTable(
            ProductCategory::DB_TBL_LANG,
            'LEFT OUTER JOIN',
            'prodcatlang_prodcat_id = c.prodcat_id
            AND prodcatlang_lang_id = ' . $langId,
            'c_l'
        );
        $srch->doNotCalculateRecords();
        $srch->doNotLimitRecords();
        // $srch->addMultipleFields(['cr.pcr_prodcat_id', 'cr.pcr_parent_id']);
        $srch->addGroupBy('cr.pcr_prodcat_id');
        $srch->addMultipleFields(array('c.prodcat_id', 'COALESCE(c_l.prodcat_name,c.prodcat_identifier ) as prodcat_name', 'substr(c.prodcat_code,1,6) AS prodrootcat_code',  'c_l.prodcat_content_block', 'c.prodcat_active', 'c.prodcat_parent', 'c.prodcat_code', 'c.prodcat_ordercode', 'prodcat_has_child', 'prodcat_updated_on'));

        if ($sortByName) {
            $srch->addOrder('prodcat_name');
            $srch->addOrder('prodcat_identifier');
        } else {
            $srch->addOrder('prodcat_ordercode');
        }

        if (!empty($keywords)) {
            $cnd = $srch->addCondition('prodcat_identifier', 'like', '%' . $keywords . '%');
            $cnd->attachCondition('prodcat_name', 'like', '%' . $keywords . '%');
        }

        if (0 < $parentId) {
            $srch->addCondition('cr.pcr_parent_id', '=', 'mysql_func_' . $parentId, 'AND', true);
            // $srch->addCondition('c.prodcat_code', 'like', $catCode . '%');
        }
        $srch->addCondition('prodcat_status', '=', 'mysql_func_' . self::REQUEST_APPROVED, 'AND', true);
        $srch->addCondition('prodcat_active', '=', 'mysql_func_' . applicationConstants::ACTIVE, 'AND', true);
        $srch->addCondition('prodcat_deleted', '=', 'mysql_func_' . applicationConstants::NO, 'AND', true);

        $rs = $srch->getResultSet();
        $categoriesArr = FatApp::getDb()->fetchAll($rs, 'prodcat_id');
        if (true == $parseTree) {
            $categoriesArr = static::parseTree($categoriesArr, $parentId);
        }

        $rootCatArr[$cacheKey] = $categoriesArr;

        if (true == $useCache) {
            CacheHelper::create('categoryArrCache' . $cacheKey, serialize($categoriesArr), CacheHelper::TYPE_PRODUCT_CATEGORIES);
        }
        return $categoriesArr;
    }

    public static function getTreeArr($langId, $parentId = 0, $sortByName = false, $prodCatSrch = false, $excludeCatHavingNoProducts = false, $keywords = false)
    {
        $parentId = FatUtility::int($parentId);
        $langId = FatUtility::int($langId);
        if (!$langId) {
            trigger_error("Language not specified", E_USER_ERROR);
        }

        if (false == $prodCatSrch) {
            return self::getArray($langId, $parentId, $sortByName, $excludeCatHavingNoProducts, $keywords, CONF_USE_FAT_CACHE);
        }

        if (!empty($keywords)) {
            $cnd = $prodCatSrch->addCondition('prodcat_identifier', 'like', '%' . $keywords . '%');
            $cnd->attachCondition('prodcat_name', 'like', '%' . $keywords . '%');
        }

        $prodCatSrch->doNotCalculateRecords();
        $prodCatSrch->doNotLimitRecords();
        $prodCatSrch->addMultipleFields(array('prodcat_id'));

        if (0 < $parentId) {
            $catCode = static::getAttributesById($parentId, 'prodcat_code');
            // $prodCatSrch->addCondition('prodcat_code', 'like', $catCode . '%');
        }

        $srch = new SearchBase(self::DB_TBL_PROD_CAT_RELATIONS, 'cr');
        $srch->joinTable('( ' . $prodCatSrch->getQuery() . ' )', 'INNER JOIN', 'temp.prodcat_id = cr.pcr_prodcat_id', 'temp');
        $srch->joinTable(self::DB_TBL, 'INNER JOIN', 'c.prodcat_id = cr.pcr_parent_id', 'c');
        $srch->joinTable(
            ProductCategory::DB_TBL_LANG,
            'LEFT OUTER JOIN',
            'prodcatlang_prodcat_id = c.prodcat_id
        AND prodcatlang_lang_id = ' . $langId,
            'c_l'
        );
        $srch->doNotCalculateRecords();
        $srch->doNotLimitRecords();
        $srch->addGroupBy('cr.pcr_parent_id');
        $srch->addMultipleFields(array('cr.pcr_prodcat_id', 'cr.pcr_parent_id', 'c.prodcat_id', 'COALESCE(c_l.prodcat_name,c.prodcat_identifier ) as prodcat_name', 'substr(c.prodcat_code,1,6) AS prodrootcat_code',  'c_l.prodcat_content_block', 'c.prodcat_active', 'c.prodcat_parent', 'c.prodcat_code', 'c.prodcat_ordercode'));

        if ($excludeCatHavingNoProducts) {
            $prodSrchObj = new ProductSearch();
            // $prodSrchObj->addMultipleFields(array('count(selprod_id) as productCounts', 'c.prodcat_id as qryProducts_prodcat_id'));
            $prodSrchObj->addMultipleFields(array('DISTINCT(prodcat_code)', 'cr.pcr_parent_id as qryProducts_prodcat_id'));
            $prodSrchObj->setDefinedCriteria(0, 0, array('doNotJoinSpecialPrice' => true, 'doNotJoinSellers' => true));
            $prodSrchObj->joinSellerSubscription(0, true);
            $prodSrchObj->addSubscriptionValidCondition();
            $prodSrchObj->doNotCalculateRecords();
            $prodSrchObj->doNotLimitRecords();
            $prodSrchObj->joinProductToCategory();
            $prodSrchObj->joinCategoryRelationWithChild();
            $prodSrchObj->addCondition('selprod_deleted', '=', 'mysql_func_' . applicationConstants::NO, 'AND', true);
            if (0 < $parentId) {
                $prodSrchObj->addCondition('prodcat_code', 'like', $catCode . '%');
            }
            $srch->joinTable('(' . $prodSrchObj->getQuery() . ')', 'INNER JOIN', 'qryProducts.qryProducts_prodcat_id = cr.pcr_prodcat_id', 'qryProducts');
        }
        if ($sortByName) {
            $srch->addOrder('prodcat_name');
            $srch->addOrder('prodcat_identifier');
        } else {
            //$prodCatSrch->addOrder('prodrootcat_code');
            $srch->addOrder('prodcat_ordercode');
        }
        $rs = $srch->getResultSet();
        $categoriesArr = FatApp::getDb()->fetchAll($rs, 'prodcat_id');
        // static::addMissingParentDetails($categoriesArr, $langId);
        $categoriesArr = static::parseTree($categoriesArr, $parentId);
        return $categoriesArr;
    }

    public static function addMissingParentDetails(&$categoriesArr, $langId)
    {
        foreach ($categoriesArr as $category) {
            if (!$category['prodcat_parent'] || array_key_exists($category['prodcat_parent'], $categoriesArr)) {
                continue;
            }

            $catCode = explode('_', rtrim($category['prodcat_code'], '_'));
            foreach ($catCode as $code) {
                $catId = ltrim($code, 0);

                if (!$catId || array_key_exists($catId, $categoriesArr)) {
                    continue;
                }

                $srch = new ProductCategorySearch($langId, true, true, false);
                $srch->addCondition('prodcat_id', '=', $catId);
                $srch->setPageSize(1);
                $srch->addMultipleFields(array('prodcat_id', 'COALESCE(prodcat_name,prodcat_identifier ) as prodcat_name', 'substr(prodcat_code,1,6) AS prodrootcat_code',  'prodcat_content_block', 'prodcat_active', 'prodcat_parent', 'prodcat_code', 'prodcat_ordercode'));
                $srch->doNotCalculateRecords();
                $rs = $srch->getResultSet();
                $data = FatApp::getDb()->fetch($rs);
                $categoriesArr[$catId] = $data;

                if (empty($data)) {
                    unset($categoriesArr[$catId]);
                }
            }
        }
    }

    public static function parseTree($tree, $root = 0)
    {
        $return = array();
        foreach ($tree as $categoryId => $category) {
            $parent = $category['prodcat_parent'];
            if ($parent == $root) {
                unset($tree[$categoryId]);
                $return[$categoryId] = $category;
                $child = static::parseTree($tree, $categoryId);
                $return[$categoryId]['isLastChildCategory'] = (0 < count($child)) ? 0 : 1;
                $return[$categoryId]['children'] = (true === MOBILE_APP_API_CALL) ? array_values($child) : $child;
            }
        }
        return empty($return) ? array() : $return;
    }

    public function getCategoryStructure($prodcat_id, $category_tree_array = '', $langId = 0)
    {
        if (!is_array($category_tree_array)) {
            $category_tree_array = array();
        }
        $langId = FatUtility::int($langId);

        $srch = static::getSearchObject();
        $srch->addCondition('m.prodcat_deleted', '=', 'mysql_func_' . applicationConstants::NO, 'AND', true);
        $srch->addCondition('m.prodcat_active', '=', 'mysql_func_' . applicationConstants::ACTIVE, 'AND', true);
        $srch->addCondition('m.prodcat_id', '=', $prodcat_id);
        $srch->addOrder('m.prodcat_display_order', 'asc');
        $srch->addOrder('m.prodcat_identifier', 'asc');

        if ($langId > 0) {
            $srch->joinTable(static::DB_TBL_LANG, 'LEFT OUTER JOIN', static::DB_TBL_LANG_PREFIX . 'prodcat_id = ' . static::tblFld('id') . ' and ' . static::DB_TBL_LANG_PREFIX . 'lang_id = ' . $langId);
            $srch->addFld(array('COALESCE(prodcat_name,prodcat_identifier) as prodcat_name'));
        } else {
            $srch->addFld(array('prodcat_identifier as prodcat_name'));
        }

        $srch->addMultipleFields(array('prodcat_id', 'prodcat_identifier', 'prodcat_parent'));
        $srch->doNotCalculateRecords();
        $rs = $srch->getResultSet();
        while ($categories = FatApp::getDb()->fetch($rs)) {
            $category_tree_array[] = $categories;
            $category_tree_array = $this->getCategoryStructure($categories['prodcat_parent'], $category_tree_array, $langId);
        }

        return $category_tree_array;
    }

    public function addUpdateProdCatLang($data, $lang_id, $prodcat_id)
    {
        $tbl = new TableRecord(static::DB_TBL_LANG);
        $data['prodcatlang_prodcat_id'] = FatUtility::int($prodcat_id);
        $tbl->assignValues($data);
        if ($this->isExistProdCatLang($lang_id, $prodcat_id)) {
            if (!$tbl->update(array('smt' => 'prodcatlang_prodcat_id = ? and prodcatlang_lang_id = ? ', 'vals' => array($prodcat_id, $lang_id)))) {
                $this->error = $tbl->getError();
                return false;
            }
            return $prodcat_id;
        }
        if (!$tbl->addNew()) {
            $this->error = $tbl->getError();
            return false;
        }
        return true;
    }

    public function isExistProdCatLang($lang_id, $prodcat_id)
    {
        $srch = new SearchBase(static::DB_TBL_LANG);
        $srch->addCondition('prodcatlang_prodcat_id', '=', $prodcat_id);
        $srch->addCondition('prodcatlang_lang_id', '=', $lang_id);
        $srch->doNotCalculateRecords();
        $row = FatApp::getDb()->fetch($srch->getResultSet());
        if (!empty($row)) {
            return true;
        }
        return false;
    }

    public function getParentTreeStructure($prodCat_id = 0, $level = 0, $name_suffix = '', $langId = 0, $active = true, $status = 1)
    {
        $langId = FatUtility::int($langId);
        $srch = static::getSearchObject(false, $langId, $active, $status);
        if (0 < $langId) {
            $srch->addFld('m.prodcat_id, COALESCE(pc_l.prodcat_name, m.prodcat_identifier) as prodcat_name, m.prodcat_identifier, m.prodcat_parent');
        } else {
            $srch->addFld('m.prodcat_id, m.prodcat_identifier, m.prodcat_parent');
        }
        $srch->addCondition('m.prodcat_deleted', '=', 'mysql_func_' . applicationConstants::NO, 'AND', true);
        $srch->addCondition('m.prodCat_id', '=', 'mysql_func_' . FatUtility::int($prodCat_id), 'AND', true);
        $srch->doNotCalculateRecords();
        $rs = $srch->getResultSet();
        $records = FatApp::getDb()->fetch($rs);
        $name = $name_suffix;
        $seprator = '';
        if ($level > 0) {
            $seprator = ' &nbsp;&nbsp;&raquo;&raquo;&nbsp;&nbsp;';
        }

        if ($records) {
            $name = $records['prodcat_name'] ?? $records['prodcat_identifier'];
            $name = strip_tags($name) . $seprator . $name_suffix;
            if ($records['prodcat_parent'] > 0) {
                $name = $this->getParentTreeStructure($records['prodcat_parent'], $level + 1, $name, $langId, $active, $status);
            }
        }
        return $name;
    }

    public static function isLastChildCategory($prodCat_id = 0)
    {
        $srch = static::getSearchObject();
        $srch->addCondition('prodcat_parent', '=', $prodCat_id);
        $srch->addCondition('prodcat_active', '=', 'mysql_func_' . applicationConstants::ACTIVE, 'AND', true);
        $srch->addCondition('prodcat_deleted', '=', 'mysql_func_' . applicationConstants::NO, 'AND', true);
        $srch->addMultipleFields(array('prodcat_id'));
        $srch->doNotCalculateRecords();
        $srch->setPageSize(1);
        $rs = $srch->getResultSet();
        $records = FatApp::getDb()->fetch($rs);
        if (empty($records)) {
            return true;
        }
        return false;
    }

    public function getProdCatAutoSuggest($keywords = '', $limit = 20, $langId = 0, $excludeRecords = [], $page = 1)
    {
        $srch = static::getSearchObject(false, $langId);
        if (0 < $langId) {
            $srch->addFld('m.prodcat_id, COALESCE(pc_l.prodcat_name, m.prodcat_identifier) as prodcat_name, m.prodcat_identifier, m.prodcat_parent');
        } else {
            $srch->addFld('m.prodcat_id, m.prodcat_identifier, m.prodcat_parent');
        }
        $srch->addCondition('m.prodcat_deleted', '=', 'mysql_func_' . applicationConstants::NO, 'AND', true);
        $srch->addCondition('m.prodcat_active', '=', 'mysql_func_' . applicationConstants::ACTIVE, 'AND', true);
        if (!empty($keywords)) {
            $cnd = $srch->addCondition('m.prodcat_identifier', 'like', '%' . $keywords . '%');
            if (0 < $langId) {
                $cnd->attachCondition('pc_l.prodcat_name', 'LIKE', '%' . $keywords . '%');
            }
        }

        if (!empty($excludeRecords) && is_array($excludeRecords)) {
            $srch->addCondition('prodcat_id', 'NOT IN', $excludeRecords);
        }

        $srch->addOrder('m.prodcat_parent', 'asc');
        $srch->addOrder('m.prodcat_display_order', 'asc');
        if (0 < $langId) {
            $srch->addOrder('pc_l.prodcat_name', 'asc');
        } else {
            $srch->addOrder('m.prodcat_identifier', 'asc');
        }
        $srch->setPageNumber($page);
        $srch->setPageSize($limit);
        $rs = $srch->getResultSet();
        $records = FatApp::getDb()->fetchAll($rs);

        $return = array();
        foreach ($records as $row) {
            /*  if (count($return) >= $limit) {
                break;
            } */
            if ($row['prodcat_parent'] > 0) {
                $return[$row['prodcat_id']] = $this->getParentTreeStructure($row['prodcat_id'], 0, '', $langId);
            } else {
                $return[$row['prodcat_id']] = (0 < $langId) ? $row['prodcat_name'] : $row['prodcat_identifier'];
            }
        }
        return [$return, $srch->pages()];
    }

    public function getNestedArray($langId)
    {
        $arr = $this->getCategoriesForSelectBox($langId);
        $out = array();
        foreach ($arr as $id => $cat) {
            $tree = str_split($cat['prodcat_code'], 6);
            array_pop($tree);
            $parent = &$out;
            foreach ($tree as $parentId) {
                $parentId = intval($parentId);
                $parent = &$parent['children'][$parentId];
            }
            $parent['children'][$id]['name'] = $cat['prodcat_name'];
        }
        return $out;
    }

    public function makeAssociativeArray($arr, $prefix = ' » ')
    {
        $out = array();
        $tempArr = array();
        foreach ($arr as $key => $value) {
            $tempArr[] = $key;
            $name = $value['prodcat_name'];
            $code = str_replace('_', '', $value['prodcat_code']);
            $hierarchyArr = str_split($code, 6);

            $this_deleted = 0;
            foreach ($hierarchyArr as $node) {
                $this_deleted = 0;
                $node = FatUtility::int($node);
                if (!in_array($node, $tempArr)) {
                    $this_deleted = 1;
                    continue;
                }
            }
            if ($this_deleted == 0) {
                $level = strlen($code) / 6;
                for ($i = 1; $i < $level; $i++) {
                    $name = $prefix . $name;
                }
                $out[$key] = $name;
            }
        }
        return $out;
    }

    public function getCategoriesForSelectBox($langId, $ignoreCategoryId = 0, $prefCategoryid = array(), $checkActive = true)
    {
        /* $srch = new SearchBase(static::DB_TBL); */
        $srch = static::getSearchObject(false, 0, $checkActive, -1);
        $srch->joinTable(static::DB_TBL_LANG, 'LEFT OUTER JOIN', 'prodcatlang_prodcat_id = prodcat_id
			AND prodcatlang_lang_id = ' . $langId);
        $srch->addCondition(static::DB_TBL_PREFIX . 'deleted', '=', 'mysql_func_' . applicationConstants::NO, 'AND', true);
        $srch->addMultipleFields(array(
            'prodcat_id',
            'COALESCE(prodcat_name, prodcat_identifier) AS prodcat_name',
            'prodcat_code'
        ));

        //$srch->addOrder('GETCATORDERCODE(prodcat_id)');
        $srch->addOrder('prodcat_ordercode');

        if (count($prefCategoryid) > 0) {
            foreach ($prefCategoryid as $prefCategoryids) {
                $srch->addHaving('prodcat_code', 'LIKE', '%' . $prefCategoryids . '%', 'OR');
            }
        }

        if ($ignoreCategoryId > 0) {
            $srch->addHaving('prodcat_code', 'NOT LIKE', '%' . str_pad($ignoreCategoryId, 6, '0', STR_PAD_LEFT) . '%');
        }

        $srch->doNotCalculateRecords();
        $srch->doNotLimitRecords();
        // echo $srch->getQuery(); die;
        $rs = $srch->getResultSet();

        return FatApp::getDb()->fetchAll($rs, 'prodcat_id');
    }

    public function getProdCatTreeStructure($parent_id = 0, $langId = 0, $keywords = '', $level = 0, $name_prefix = '', $isActive = true, $isDeleted = true, $isForCsv = false)
    {
        $langId = FatUtility::int($langId);
        $srch = static::getSearchObject(false, $langId, $isActive);
        if ($langId) {
            $srch->addFld('m.prodcat_id, COALESCE(pc_l.prodcat_name, m.prodcat_identifier) as prodcat_name');
        } else {
            $srch->addFld('m.prodcat_id, m.prodcat_identifier as prodcat_name');
        }

        if ($isDeleted) {
            $srch->addCondition('m.prodcat_deleted', '=', 'mysql_func_' . applicationConstants::NO, 'AND', true);
        }

        if ($isActive) {
            $srch->addCondition('m.prodcat_active', '=', 'mysql_func_' . applicationConstants::ACTIVE, 'AND', true);
        }
        $srch->addCondition('m.prodcat_parent', '=', 'mysql_func_' . FatUtility::int($parent_id), 'AND', true);

        if (!empty($keywords)) {
            $srch->addCondition('prodcat_name', 'like', '%' . $keywords . '%');
        }

        $srch->addOrder('m.prodcat_display_order', 'asc');
        $srch->addOrder('prodcat_name', 'asc');
        $srch->doNotCalculateRecords();
        $rs = $srch->getResultSet();
        $records = FatApp::getDb()->fetchAllAssoc($rs);

        $return = array();
        $seprator = '';
        if ($level > 0) {
            if ($isForCsv) {
                $seprator = '->-> ';
            } else {
                $seprator = '&raquo;&raquo;&nbsp;&nbsp;';
            }
            $seprator = CommonHelper::renderHtml($seprator);
        }
        foreach ($records as $prodcat_id => $prodcat_identifier) {
            $name = $name_prefix . $seprator . $prodcat_identifier;
            $return[$prodcat_id] = $name;
            $return += $this->getProdCatTreeStructure($prodcat_id, $langId, $keywords, $level + 1, $name, $isActive, $isDeleted, $isForCsv);
        }
        return $return;
    }

    public function getProdCatTreeStructureSearch($parent_id = 0, $langId = 0, $keywords = '', $level = 0, $name_prefix = '', $isActive = true, $isDeleted = true, $isForCsv = false)
    {
        $langId = FatUtility::int($langId);
        $srch = static::getSearchObject(false, $langId, $isActive);
        if ($langId) {
            $srch->addFld('m.prodcat_id, COALESCE(pc_l.prodcat_name, m.prodcat_identifier) as prodcat_name');
        } else {
            $srch->addFld('m.prodcat_id, m.prodcat_identifier as prodcat_name');
        }

        if ($isDeleted) {
            $srch->addCondition('m.prodcat_deleted', '=', 'mysql_func_' . applicationConstants::NO, 'AND', true);
        }

        if ($isActive) {
            $srch->addCondition('m.prodcat_active', '=', 'mysql_func_' . applicationConstants::ACTIVE, 'AND', true);
        }
        $srch->addCondition('m.prodcat_parent', '=', 'mysql_func_' . FatUtility::int($parent_id) . 'AND', true);

        if (!empty($keywords)) {
            //$srch->addCondition('prodcat_name','like','%'.$keywords.'%');
        }
        $srch->addOrder('m.prodcat_display_order', 'asc');
        $srch->addOrder('prodcat_name', 'asc');
        $srch->doNotCalculateRecords();
        $rs = $srch->getResultSet();
        $records = FatApp::getDb()->fetchAllAssoc($rs);

        $return = array();
        $seprator = '';
        if ($level > 0) {
            if ($isForCsv) {
                $seprator = '->-> ';
            } else {
                $seprator = '&raquo;&raquo;&nbsp;&nbsp;';
            }
            $seprator = CommonHelper::renderHtml($seprator);
        }
        //print_r($records); die;
        foreach ($records as $prodcat_id => $prodcat_identifier) {
            $name = $name_prefix . $seprator . $prodcat_identifier;
            //echo $name."<br>";
            $flag = 0;
            if ($keywords) {
                if (stripos($name, $keywords) !== false) {
                    $return[$prodcat_id] = $name;
                }
            } else {
                $return[$prodcat_id] = $name;
            }
            $return += $this->getProdCatTreeStructureSearch($prodcat_id, $langId, $keywords, $level + 1, $name, $isActive, $isDeleted, $isForCsv);
            //print_r($return); die;
        }
        return $return;
    }

    public function getAutoCompleteProdCatTreeStructure($parent_id = 0, $langId = 0, $keywords = '', $level = 0, $namePrefix = '', $isActive = true, $isDeleted = true, $isForCsv = false)
    {
        $langId = FatUtility::int($langId);

        $srch = static::getSearchObject(false, $langId, $isActive);
        $srch->addMultipleFields(['m.prodcat_id', 'm.prodcat_code', 'TRIM(LEADING "0" FROM substr(prodcat_code,1,6)) AS prodrootcat_id']);
        if ($langId) {
            $srch->addFld('COALESCE(pc_l.prodcat_name, m.prodcat_identifier) as prodcat_name');
        } else {
            $srch->addFld('m.prodcat_identifier as prodcat_name');
        }

        if ($isDeleted) {
            $srch->addCondition('m.prodcat_deleted', '=', 'mysql_func_' . applicationConstants::NO, 'AND', true);
        }

        if ($parent_id > 0) {
            $srch->addCondition('m.prodcat_id', '=', 'mysql_func_' . FatUtility::int($parent_id), 'AND', true);
        }

        if (!empty($keywords)) {
            $srch->addCondition('prodcat_name', 'like', '%' . $keywords . '%');
        }
        $srch->doNotCalculateRecords();
        $srch->doNotLimitRecords();
        $srch->addOrder('prodcat_ordercode', 'asc');
        $srch->addOrder('prodcat_name', 'asc');

        /*Fetch searched parent category data */
        $catSrch = static::getSearchObject(false, $langId, $isActive);
        $catSrch->addFld('DISTINCT(m.prodcat_id),m.prodcat_code, COALESCE(pc_l.prodcat_name, m.prodcat_identifier) as prodcat_name');
        $catSrch->doNotCalculateRecords();
        $catSrch->doNotLimitRecords();
        $catSrch->joinTable('(' . $srch->getQuery() . ')', 'INNER JOIN', "m.prodcat_code like CONCAT('%',temp.prodrootcat_id,'%')", 'temp');
        $catSrch->addOrder('m.prodcat_code');
        $rs = $catSrch->getResultSet();
        $catRecords = FatApp::getDb()->fetchAll($rs, 'prodcat_id');
        // CommonHelper::printArray($catRecords);

        $rs = $srch->getResultSet();
        $records = FatApp::getDb()->fetchAll($rs, 'prodcat_code');

        $treeArr = [];
        foreach ($catRecords as $row) {
            if (!array_key_exists($row['prodcat_code'], $records)) {
                continue;
            }
            $level = 0;
            $seprator = '';
            $namePrefix = '';
            $prodCat = explode("_", substr($row['prodcat_code'], 0, -1));

            foreach ($prodCat as $key => $prodcatParent) {
                if ($level > 0) {
                    if ($isForCsv) {
                        $seprator = '->-> ';
                    } else {
                        $seprator = '&raquo;&raquo;&nbsp;&nbsp;';
                    }
                    $seprator = CommonHelper::renderHtml($seprator);
                }
                $namePrefix = $namePrefix . $seprator . $catRecords[FatUtility::int($prodcatParent)]['prodcat_name'];
                $treeArr[$row['prodcat_id']] = $namePrefix;
                $level++;
            }
        }

        $catRecords = [];
        return $treeArr;
    }

    public static function getProdCatParentChildWiseArr(int $langId = 0, int $parentId = 0, bool $includeChildCat = true, bool $forSelectBox = false, bool $sortByName = false, $prodCatSrchObj = false, bool $excludeCategoriesHavingNoProducts = false, bool $requireInStockProducts = false)
    {
        $cacheKey = '';
        if (!is_object($prodCatSrchObj)) {
            $cacheKey = LibHelper::getCacheKey();
            $categoryArrCache = CacheHelper::get($cacheKey, CONF_HOME_PAGE_CACHE_TIME, '.txt');
            if ($categoryArrCache) {
                return unserialize($categoryArrCache);
            }
        }

        if (!$langId) {
            trigger_error("Language not specified", E_USER_ERROR);
        }

        if (is_object($prodCatSrchObj)) {
            $prodCatSrch = clone $prodCatSrchObj;
        } else {
            $prodCatSrch = new ProductCategorySearch($langId, true, true, false);
            $prodCatSrch->setParent($parentId);
        }
        $prodCatSrch->doNotCalculateRecords();
        $prodCatSrch->doNotLimitRecords();

        $prodCatSrch->addMultipleFields(array('prodcat_id', 'COALESCE(prodcat_name,prodcat_identifier ) as prodcat_name', 'substr(prodcat_code,1,6) AS prodrootcat_code', 'prodcat_content_block', 'prodcat_active', 'prodcat_parent', 'prodcat_code as prodcat_code', 'prodcat_updated_on', 'prodcat_has_child'));

        if ($excludeCategoriesHavingNoProducts) {
            $prodSrchObj = new ProductSearch();
            $prodSrchObj->setDefinedCriteria(0, 0, array('doNotJoinSpecialPrice' => true, 'doNotJoinSellers' => true, 'doNotJoinShippingPkg' => true));
            $prodSrchObj->doNotCalculateRecords();
            $prodSrchObj->doNotLimitRecords();
            $prodSrchObj->joinSellerSubscription(0, true);
            $prodSrchObj->addSubscriptionValidCondition();
            $prodSrchObj->addMultipleFields(array('product_id'));
            $prodSrchObj->addCondition('selprod_deleted', '=', 'mysql_func_' . applicationConstants::NO, 'AND', true);
            if ($requireInStockProducts) {
                $prodSrchObj->excludeOutOfStockProducts();
            }
            $prodSrchObj->addGroupBy('product_id');

            $prodCatSrch->joinProductCategoryRelations();
            $prodCatSrch->addFld('COALESCE(COUNT(ptc.ptc_product_id), 0) as productCounts');
            $prodCatSrch->joinTable('(' . $prodSrchObj->getQuery() . ')', 'LEFT OUTER JOIN', 'qryProducts.product_id = ptc.ptc_product_id', 'qryProducts');

            $prodCatSrch->addHaving('productCounts', '>', 0);
        }

        if ($sortByName) {
            $prodCatSrch->addOrder('prodcat_name');
            $prodCatSrch->addOrder('prodcat_identifier');
        } else {
            $prodCatSrch->addOrder('prodcat_ordercode');
        }

        $rs = $prodCatSrch->getResultSet();
        if ($forSelectBox) {
            $categoriesArr = FatApp::getDb()->fetchAllAssoc($rs);
        } else {
            $categoriesArr = FatApp::getDb()->fetchAll($rs);
        }

        if (true === $includeChildCat && $categoriesArr) {
            foreach ($categoriesArr as $key => $cat) {
                $uploadedTime = AttachedFile::setTimeParam($cat['prodcat_updated_on']);
                $categoriesArr[$key] = $cat;
                $categoriesArr[$key]['isLastChildCategory'] = ($cat['prodcat_has_child']) ? 0 : 1;
                $categoriesArr[$key]['icon'] = UrlHelper::generateFullUrl('Category', 'icon', array($cat['prodcat_id'], $langId, 'COLLECTION_PAGE')) . $uploadedTime;
                $categoriesArr[$key]['image'] = UrlHelper::generateFullUrl('Category', 'banner', array($cat['prodcat_id'], $langId, 'MOBILE', applicationConstants::SCREEN_MOBILE)) . $uploadedTime;
                $categoriesArr[$key]['children'] = self::getProdCatParentChildWiseArr($langId, $cat['prodcat_id'], $includeChildCat, $forSelectBox, $sortByName, $prodCatSrchObj, $excludeCategoriesHavingNoProducts, $requireInStockProducts);
            }
        }

        if (!empty($cacheKey)) {
            CacheHelper::create($cacheKey, serialize($categoriesArr), CacheHelper::TYPE_PRODUCT_CATEGORIES);
        }
        return $categoriesArr;
    }

    public static function getRootProdCatArr($langId)
    {
        $langId = FatUtility::int($langId);
        if (!$langId) {
            trigger_error(Labels::getLabel('ERR_LANGUAGE_NOT_SPECIFIED', $langId), E_USER_ERROR);
        }
        return static::getProdCatParentChildWiseArr($langId, 0, false, true);
    }

    /**
     * Remove parent categories whose children were all filtered out (e.g. no in-stock products).
     */
    public static function pruneEmptyParentCategories(array $categories): array
    {
        $pruned = [];
        foreach ($categories as $cat) {
            if (!empty($cat['children'])) {
                $cat['children'] = self::pruneEmptyParentCategories($cat['children']);
            }
            $hasChildCategories = !empty($cat['children']);
            $isLeaf = empty($cat['prodcat_has_child']);
            if ($isLeaf || $hasChildCategories) {
                $pruned[] = $cat;
            }
        }
        return $pruned;
    }

    public function canRecordMarkDelete($prodcat_id)
    {
        $srch = static::getSearchObject(false, 0, false);
        $srch->addCondition('m.prodcat_deleted', '=', 'mysql_func_' . applicationConstants::NO, 'AND', true);
        $srch->addCondition('m.prodcat_id', '=', $prodcat_id);
        $srch->doNotCalculateRecords();
        $srch->addFld('m.prodcat_id');
        $rs = $srch->getResultSet();
        $row = FatApp::getDb()->fetch($rs);
        if (!empty($row) && $row['prodcat_id'] == $prodcat_id) {
            return true;
        }
        return false;
    }

    public function canRecordUpdateStatus($prodcat_id)
    {
        $srch = static::getSearchObject();
        $srch->addCondition('m.prodcat_deleted', '=', 'mysql_func_' . applicationConstants::NO, 'AND', true);
        $srch->addCondition('m.prodcat_id', '=', $prodcat_id);
        $srch->addFld('m.prodcat_id,m.prodcat_active');
        $srch->doNotCalculateRecords();
        $rs = $srch->getResultSet();
        $row = FatApp::getDb()->fetch($rs);
        if (!empty($row) && $row['prodcat_id'] == $prodcat_id) {
            return $row;
        }
        return false;
    }

    public static function recordCategoryWeightage($categoryId)
    {
        /* $categoryId =  FatUtility::int($categoryId);
        if(1 > $categoryId){ return false;}
        $obj = new SmartUserActivityBrowsing();
        return $obj->addUpdate($categoryId,SmartUserActivityBrowsing::TYPE_CATEGORY); */
    }

    public static function getDeletedProductCategoryByIdentifier($identifier = '')
    {
        $srch = static::getSearchObject(false, 0, false);
        $srch->addCondition('m.prodcat_deleted', '=', 'mysql_func_' . applicationConstants::YES, 'AND', true);
        $srch->addCondition('m.prodcat_identifier', '=', $identifier);

        $srch->addFld('m.prodcat_id');
        $srch->doNotCalculateRecords();
        $rs = $srch->getResultSet();

        $row = FatApp::getDb()->fetch($rs);
        if ($row) {
            return $row['prodcat_id'];
        } else {
            return false;
        }
    }

    public static function getProductCategoryName(int $catId, int $langId)
    {
        $srch = static::getSearchObject(false, $langId);
        $srch->addCondition('m.prodcat_active', '=', 'mysql_func_' . applicationConstants::ACTIVE, 'AND', true);
        $srch->addCondition('m.prodcat_deleted', '=', 'mysql_func_' . applicationConstants::NO, 'AND', true);
        $srch->addCondition('m.prodcat_id', '=', $catId);
        $srch->addFld('COALESCE(prodcat_name,prodcat_identifier) as prodcat_name');
        $srch->doNotCalculateRecords();
        $row = FatApp::getDb()->fetch($srch->getResultSet());
        return $row['prodcat_name'] ?? '';
    }

    public function getCategoryTreeForSearch($siteLangId, $categories, &$globalCatTree = array(), $attr = array())
    {
        if ($categories) {
            $remainingCatCods = $categories;
            $catId = $categories[0];
            unset($remainingCatCods[0]);
            $remainingCatCods = array_values($remainingCatCods);
            $catId = FatUtility::int($catId);
            if (!empty($attr) && is_array($attr)) {
                $prodCatSrch = new ProductCategorySearch($siteLangId);
                $prodCatSrch->addMultipleFields(array('prodcat_id', 'COALESCE(prodcat_name,prodcat_identifier ) as prodcat_name', 'substr(prodcat_code,1,6) AS prodrootcat_code', 'prodcat_content_block', 'prodcat_active', 'prodcat_parent', 'prodcat_code as prodcat_code'));
                $prodCatSrch->addCondition('prodcat_id', '=', 'mysql_func_' . $catId, 'AND', true);
                $prodCatSrch->doNotCalculateRecords();
                $rs = $prodCatSrch->getResultSet();
                $rows = FatApp::getDb()->fetch($rs);
                foreach ($rows as $key => $val) {
                    $globalCatTree[$catId][$key] = $val;
                }
            } else {
                $prodCatSrch = new ProductCategorySearch($siteLangId);
                $prodCatSrch->addFld('COALESCE(prodcat_name,prodcat_identifier ) as prodcat_name');
                $prodCatSrch->addCondition('prodcat_id', '=', 'mysql_func_' . $catId, 'AND', true);
                $prodCatSrch->doNotCalculateRecords();
                $rs = $prodCatSrch->getResultSet();
                $rows = FatApp::getDb()->fetch($rs);

                $globalCatTree[$catId]['prodcat_name'] = $rows['prodcat_name'];
                $globalCatTree[$catId]['prodcat_id'] = $catId;
            }
            if (count($remainingCatCods) > 0) {
                $this->getCategoryTreeForSearch($siteLangId, $remainingCatCods, $globalCatTree[$catId]['children'], $attr);
            }
        }
    }

    public function getCategoryTreeArr($siteLangId, $categoriesDataArr, $attr = array())
    {
        foreach ($categoriesDataArr as $categoriesData) {
            $categoryCode = substr($categoriesData['prodcat_code'], 0, -1);
            $prodCats = explode("_", $categoryCode);
            $remaingCategories = $prodCats;
            unset($remaingCategories[0]);
            $remaingCategories = array_values($remaingCategories);

            $parentId = FatUtility::int($prodCats[0]);
            if (!array_key_exists($parentId, $this->categoryTreeArr)) {
                $this->categoryTreeArr[$parentId] = array();
            }
            if (!empty($attr) && is_array($attr)) {
                $prodCatSrch = new ProductCategorySearch($siteLangId);
                $prodCatSrch->addMultipleFields($attr);
                $prodCatSrch->addCondition('prodcat_id', '=', 'mysql_func_' . FatUtility::int($prodCats[0]), 'AND', true);
                $prodCatSrch->doNotCalculateRecords();
                $rs = $prodCatSrch->getResultSet();
                $rows = FatApp::getDb()->fetch($rs);
                foreach ($rows as $key => $val) {
                    $this->categoryTreeArr[$parentId][$key] = $val;
                }
            } else {
                /* $this->categoryTreeArr [$parentId]['prodcat_name'] = productCategory::getAttributesByLangId($siteLangId,FatUtility::int($prodCats[0]),'prodcat_name'); */
                $prodCatSrch = new ProductCategorySearch($siteLangId);
                $prodCatSrch->addFld('COALESCE(prodcat_name,prodcat_identifier ) as prodcat_name');
                $prodCatSrch->addCondition('prodcat_id', '=', 'mysql_func_' . FatUtility::int($prodCats[0]), 'AND', true);
                $prodCatSrch->doNotCalculateRecords();
                $rs = $prodCatSrch->getResultSet();
                $row = FatApp::getDb()->fetch($rs);

                $this->categoryTreeArr[$parentId]['prodcat_name'] = $row['prodcat_name'];
                $this->categoryTreeArr[$parentId]['prodcat_id'] = FatUtility::int($prodCats[0]);
            }

            if (!isset($this->categoryTreeArr[$parentId]['children'])) {
                $this->categoryTreeArr[$parentId]['children'] = array();
            }
            $this->getCategoryTreeForSearch($siteLangId, $remaingCategories, $this->categoryTreeArr[$parentId]['children'], $attr);
        }
        return $this->categoryTreeArr;
    }

    public function getProdRootCategoriesWithKeyword($langId = 0, $keywords = '', $returnWithChildArr = false, $prodcatCode = false, $inludeChildCount = false)
    {
        $srch = static::getSearchObject($inludeChildCount, $langId);
        $srch->addFld('m.prodcat_id,COALESCE(pc_l.prodcat_name,m.prodcat_identifier) as prodcat_name,m.prodcat_parent,substr(m.prodcat_code,1,6) AS prodrootcat_code');
        $srch->addCondition('m.prodcat_deleted', '=', 'mysql_func_' . applicationConstants::NO, 'AND', true);
        $srch->addCondition('m.prodcat_active', '=', 'mysql_func_' . applicationConstants::ACTIVE, 'AND', true);
        if (!empty($keywords)) {
            $cnd = $srch->addCondition('m.prodcat_identifier', 'like', '%' . $keywords . '%');
            $cnd->attachCondition('pc_l.prodcat_name', 'like', '%' . $keywords . '%');
        }
        $srch->addOrder('m.prodcat_parent', 'asc');
        $srch->addOrder('m.prodcat_display_order', 'asc');
        $srch->addOrder('m.prodcat_identifier', 'asc');
        if ($returnWithChildArr == false) {
            $srch->addFld('count(m.prodcat_id) as totalRecord');
            $srch->addGroupBy('prodrootcat_code');
        }

        if ($prodcatCode) {
            $srch->addHaving('prodrootcat_code', '=', $prodcatCode);
        }
        $srch->doNotCalculateRecords();
        $rs = $srch->getResultSet();
        $records = FatApp::getDb()->fetchAll($rs);

        $return = array();
        if ($returnWithChildArr) {
            foreach ($records as $row) {
                if ($row['prodcat_parent'] > 0) {
                    $return[$row['prodrootcat_code']][$row['prodcat_id']]['structure'] = $this->getParentTreeStructure($row['prodcat_id'], 0, '', $langId);
                    $return[$row['prodrootcat_code']][$row['prodcat_id']]['prodcat_name'] = $row['prodcat_name'];
                }
            }
        } else {
            $return = $records;
        }
        return $return;
    }

    public function haveProducts(bool $isActive = true)
    {
        $prodSrchObj = new ProductSearch(0, null, null, $isActive);
        $prodSrchObj->setDefinedCriteria(0, 0, array('doNotJoinSpecialPrice' => true, 'doNotJoinSellers' => true));
        $prodSrchObj->joinProductToCategory(0, $isActive);
        $prodSrchObj->doNotCalculateRecords();
        $prodSrchObj->setPageSize(1);

        $prodSrchObj->addMultipleFields(array('count(selprod_id) as productCounts', 'prodcat_id'));
        /* $prodSrchObj->addMultipleFields(array('substr(prodcat_code,1,6) AS prodrootcat_code','count(selprod_id) as productCounts', 'prodcat_id')); */

        $cnd = $prodSrchObj->addCondition('c.prodcat_id', '=', 'mysql_func_' . $this->mainTableRecordId, 'AND', true);
        $cnd->attachCondition('c.prodcat_code', 'like', '%' . str_pad($this->mainTableRecordId, 6, '0', STR_PAD_LEFT) . '%');

        /*  if (0 < $this->mainTableRecordId) {
            $prodSrchObj->addHaving('prodrootcat_code', 'LIKE', '%' . str_pad($this->mainTableRecordId, 6, '0', STR_PAD_LEFT) . '%', 'AND', true);
        } */

        $prodSrchObj->addHaving('productCounts', '>', 0);

        $rs = $prodSrchObj->getResultSet();
        $productRows = FatApp::getDb()->fetch($rs);
        if (!empty($productRows) && $productRows['productCounts'] > 0) {
            return true;
        }
        return false;
    }

    public function rewriteUrl($keyword, $suffixWithId = true, $parentId = 0)
    {
        if ($this->mainTableRecordId < 1) {
            return false;
        }

        $parentId = FatUtility::int($parentId);
        /* $parentUrl = '';
        if (0 < $parentId) {
            $parentUrlRewriteData = UrlRewrite::getDataByOriginalUrl(ProductCategory::REWRITE_URL_PREFIX.$parentId);
            if (!empty($parentUrlRewriteData)) {
                $parentUrl = preg_replace('/-'.$parentId.'$/', '', $parentUrlRewriteData['urlrewrite_custom']);
            }
        } */

        $originalUrl = ProductCategory::REWRITE_URL_PREFIX . $this->mainTableRecordId;

        $keyword = preg_replace('/-' . $this->mainTableRecordId . '$/', '', $keyword);
        $seoUrl = CommonHelper::seoUrl($keyword);
        if ($suffixWithId) {
            $seoUrl = $seoUrl . '-' . $this->mainTableRecordId;
        }

        /* $seoUrl = str_replace($parentUrl, '', $seoUrl);
        $seoUrl = $parentUrl.'-'.$seoUrl; */

        $customUrl = UrlRewrite::getValidSeoUrl($seoUrl, $originalUrl, $this->mainTableRecordId);
        return UrlRewrite::update($originalUrl, $customUrl);
    }

    public static function setImageUpdatedOn($userId, $date = '')
    {
        $date = empty($date) ? date('Y-m-d  H:i:s') : $date;
        $where = array('smt' => 'prodcat_id = ?', 'vals' => array($userId));
        FatApp::getDb()->updateFromArray(static::DB_TBL, array('prodcat_updated_on' => date('Y-m-d  H:i:s')), $where);
    }

    public function saveCategoryData($post)
    {
        $parentCatId = FatUtility::int($post['prodcat_parent']);
        $prodCatId = FatUtility::int($post['prodcat_id']);
        unset($post['prodcat_id']);

        // $siteDefaultLangId = FatApp::getConfig('CONF_DEFAULT_SITE_LANG', FatUtility::VAR_INT, 1);
        $siteDefaultLangId = CommonHelper::getDefaultFormLangId();
        if ($this->mainTableRecordId == 0) {
            $post['prodcat_display_order'] = $this->getMaxOrder($parentCatId);
        }

        if ($post['prodcat_parent'] == $this->mainTableRecordId) {
            $post['prodcat_parent'] = 0;
        }

        $this->assignValues($post);
        if ($this->save()) {
            $this->rewriteUrl($post['urlrewrite_custom'], false, $parentCatId);
        } else {
            $categoryId = self::getDeletedProductCategoryByIdentifier($post['prodcat_identifier']);
            if (!$categoryId) {
                $this->error = $this->getError();
                return false;
            }

            $record = new ProductCategory($categoryId);
            $data = $post;
            $data['prodcat_deleted'] = applicationConstants::NO;
            $record->assignValues($data);
            if (!$record->save()) {
                $this->error = $record->getError();
                return false;
            }
            $this->mainTableRecordId = $record->getMainTableRecordId();
        }

        ProductCategory::UpdateHasChildCategoryFlag($this->mainTableRecordId);

        /*  $childrens = $this->getChildrens();
        $db = FatApp::getDb();
        foreach ($childrens as $catId => $notInUse) {
            if (!$db->query('CALL updateCategoryRelations(' . $catId . ')')) {
                $this->error = $db->getError();
                return false;
            }
        } */

        if (array_key_exists('prodcat_active', $post)) {
            if (applicationConstants::INACTIVE == $post['prodcat_active']) {
                $this->disableChildCategories();
            } else {
                $this->enableParentCategories();
            }
        }

        $this->saveLangData($siteDefaultLangId, $post['prodcat_name'][$siteDefaultLangId]); // For site default language
        $catNameArr = $post['prodcat_name'];
        unset($catNameArr[$siteDefaultLangId]);

        $autoUpdateOtherLangsData = FatApp::getPostedData('auto_update_other_langs_data', FatUtility::VAR_INT, 0);
        if (0 < $autoUpdateOtherLangsData) {
            $updateLangDataobj = new TranslateLangData(static::DB_TBL_LANG);
            if (false === $updateLangDataobj->updateTranslatedData($this->mainTableRecordId)) {
                LibHelper::exitWithError($updateLangDataobj->getError(), true);
            }
        }

        foreach ($catNameArr as $langId => $catName) {
            if (!empty($catName)) {
                $this->saveLangData($langId, $catName);
            }
        }

        if (isset($post['rating_type']) && !empty($post['rating_type'])) {
            $ratingTypeArr = json_decode($post['rating_type'], true);
            foreach ($ratingTypeArr as $rating) {
                if (!isset($rating['id'])) {
                    $ratingId = RatingType::getAttributesByIdentifier($rating['value'], 'ratingtype_id');
                    if (empty($ratingId)) {
                        $ratingObj = new RatingType();
                        $ratingObj->assignValues([
                            'ratingtype_active' => 1,
                            'ratingtype_identifier' => $rating['value'],
                            'ratingtype_type' => RatingType::TYPE_OTHER
                        ]);
                        if (!$ratingObj->save()) {
                            LibHelper::exitWithError($ratingObj->getError(), true);
                        }
                        $ratingId = $ratingObj->getMainTableRecordId();
                        if (!$ratingObj->updateLangData(CommonHelper::getLangId(), ['ratingtype_name' => $rating['value']])) {
                            FatUtility::dieJsonError($ratingObj->getError());
                        }
                    }
                } else {
                    $ratingId = $rating['id'];
                }

                if (!$this->addUpdateRatingType($ratingId)) {
                    LibHelper::exitWithError($this->getError(), true);
                }
            }
        }

        CacheHelper::clear(CacheHelper::TYPE_PRODUCT_CATEGORIES);
        return true;
    }

    public function saveLangData($langId, $prodCatName)
    {
        $langId = FatUtility::int($langId);
        if ($this->mainTableRecordId < 1 || $langId < 1) {
            $this->error = Labels::getLabel('ERR_INVALID_REQUEST', $this->commonLangId);
            return false;
        }

        $data = array(
            'prodcatlang_prodcat_id' => $this->mainTableRecordId,
            'prodcatlang_lang_id' => $langId,
            'prodcat_name' => $prodCatName,
        );
        if (!$this->updateLangData($langId, $data)) {
            $this->error = $this->getError();
            return false;
        }
        CacheHelper::clear(CacheHelper::TYPE_PRODUCT_CATEGORIES);
        return true;
    }

    public function saveTranslatedLangData($langId)
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

    public function updateMedia($ImageIds)
    {
        if (count($ImageIds) == 0) {
            return false;
        }
        foreach ($ImageIds as $imageId) {
            if ($imageId > 0) {
                $data = array('afile_record_id' => $this->mainTableRecordId);
                $where = array('smt' => 'afile_id = ?', 'vals' => array($imageId));
                FatApp::getDb()->updateFromArray(AttachedFile::DB_TBL, $data, $where);
            }
        }
        return true;
    }

    public function getTranslatedCategoryData($data, $toLangId)
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

    private function categoryObj($includeProductCount = true, $includeSubCategoriesCount = true)
    {
        $attr = [
            'm.*',
            'COALESCE(prodcat_name,m.prodcat_identifier ) as prodcat_name'
        ];
        $srch = static::getSearchObject(false, $this->commonLangId, false);
        $srch->addCondition('m.' . static::DB_TBL_PREFIX . 'deleted', '=', 'mysql_func_' . applicationConstants::NO, 'AND', true);
        if ($includeProductCount === true) {
            $srch->joinTable(self::DB_TBL_PROD_CAT_RELATIONS, 'INNER JOIN', 'cr.pcr_parent_id = m.prodcat_id', 'cr');
            $srch->joinTable(Product::DB_TBL_PRODUCT_TO_CATEGORY, 'LEFT JOIN', 'ptc.ptc_prodcat_id = cr.pcr_prodcat_id', 'ptc');
            $srch->joinTable(Product::DB_TBL, 'LEFT JOIN', 'p.product_id = ptc.ptc_product_id AND p.' . Product::DB_TBL_PREFIX . 'deleted = 0', 'p');
            $attr[] = 'SUM(IF(p.product_id IS NULL, 0, 1)) as category_products';
        }

        if (true === $includeSubCategoriesCount) {
            $srchRelation = new SearchBase(ProductCategory::DB_TBL_PROD_CAT_RELATIONS, 'cr');
            $srchRelation->joinTable(static::DB_TBL, 'INNER JOIN', 'cr.pcr_prodcat_id = pccr.prodcat_id AND pccr.prodcat_deleted = 0 AND pccr.prodcat_status = 1', 'pccr');

            $srchRelation->addCondition('pcr_parent_id', '=', 'mysql_func_m.prodcat_id', 'AND', true);
            $srchRelation->addFld('(COUNT(pcr_prodcat_id) - 1) as subcategory_count');

            $srchRelation->doNotCalculateRecords();
            $srchRelation->doNotLimitRecords();
            $srchRelation->addGroupBy('pcr_parent_id');
            $attr[] = '(' . $srchRelation->getQuery() . ') as subcategory_count';
        }

        $srch->addMultipleFields($attr);
        $srch->addGroupBy('m.prodcat_id');
        $srch->addOrder('prodcat_display_order', 'asc');
        $srch->doNotCalculateRecords();
        $srch->doNotLimitRecords();
        return $srch;
    }

    public function getData($includeProductCount = true, $includeSubCategoriesCount = true)
    {
        $srch = $this->categoryObj($includeProductCount, $includeSubCategoriesCount);
        $srch->addCondition('m.' . static::DB_TBL_PREFIX . 'id', '=', 'mysql_func_' . $this->mainTableRecordId, 'AND', true);
        $srch->doNotCalculateRecords();
        return FatApp::getDb()->fetch($srch->getResultSet());
    }

    public function getCategories($includeProductCount = true, $includeSubCategoriesCount = true)
    {
        $srch = $this->categoryObj($includeProductCount, $includeSubCategoriesCount);
        $srch->addCondition('m.' . static::DB_TBL_PREFIX . 'parent', '=', 'mysql_func_' . $this->mainTableRecordId, 'AND', true);
        $srch->doNotCalculateRecords();
        return FatApp::getDb()->fetchAll($srch->getResultSet());
    }

    public function getSubCategoriesCount($prodCatId)
    {
        $prodCatId = FatUtility::int($prodCatId);
        $srch = static::getSearchObject(false, 0, false);
        $srch->addCondition(static::DB_TBL_PREFIX . 'deleted', '=', 'mysql_func_' . applicationConstants::NO, 'AND', true);
        $srch->addCondition(static::DB_TBL_PREFIX . 'parent', '=', 'mysql_func_' . $prodCatId, 'AND', true);
        $srch->addMultipleFields(array('COUNT(' . static::DB_TBL_PREFIX . 'id) as subcategory_count'));
        $srch->doNotCalculateRecords();
        $rs = $srch->getResultSet();
        $record = FatApp::getDb()->fetch($rs);
        return $record['subcategory_count'];
    }

    public static function getActiveInactiveCategoriesCount($active)
    {
        $srch = static::getSearchObject(false, 0, false);
        $srch->addCondition(static::DB_TBL_PREFIX . 'deleted', '=', 'mysql_func_' . applicationConstants::NO, 'AND', true);
        $srch->addCondition(static::DB_TBL_PREFIX . 'active', '=', $active);
        $srch->doNotCalculateRecords();
        $srch->doNotLimitRecords();
        $srch->addFld('COUNT(' . static::DB_TBL_PREFIX . 'id) as categories_count');
        $rs = $srch->getResultSet();
        return FatApp::getDb()->fetch($rs);
    }

    public static function deleteImagesWithOutCategoryId($fileType)
    {
        $allowedFileTypes = [AttachedFile::FILETYPE_CATEGORY_ICON, AttachedFile::FILETYPE_CATEGORY_BANNER];
        if (empty($fileType) || !in_array($fileType, $allowedFileTypes)) {
            return false;
        }

        $currentDate = date('Y-m-d  H:i:s');
        $prevDate = strtotime('-' . static::REMOVED_OLD_IMAGE_TIME . ' hour', strtotime($currentDate));
        $prevDate = date('Y-m-d  H:i:s', $prevDate);
        $where = array('smt' => 'afile_type = ? AND afile_record_id = ? AND afile_updated_at <= ?', 'vals' => array($fileType, 0, $prevDate));
        if (!FatApp::getDb()->deleteRecords(AttachedFile::DB_TBL, $where)) {
            return false;
        }
        return true;
    }

    public function updateCatParent($parentCatId)
    {
        if ($this->mainTableRecordId < 1) {
            return false;
        }
        $parentCatId = FatUtility::int($parentCatId);
        $childrens = $this->getChildrens();
        $db = FatApp::getDb();
        $db->updateFromArray(static::DB_TBL, array(static::DB_TBL_PREFIX . 'parent' => $parentCatId), array('smt' => static::DB_TBL_PREFIX . 'id = ?', 'vals' => array($this->mainTableRecordId)));
        foreach ($childrens as $catId => $notInUse) {
            if (!$db->query('CALL updateCategoryRelations(' . $catId . ')')) {
                $this->error = $db->getError();
                return false;
            }
        }
        ProductCategory::UpdateHasChildCategoryFlag($this->mainTableRecordId);
        return true;
    }

    /**
     * updateCategoryRelations
     *
     * @param  int $recordId
     * @param  string $prodcatCode
     * @return bool
     */
    public static function updateCategoryRelations(int $recordId = 0, string $prodcatCode = ''): bool
    {
        $db = FatApp::getDb();
        if (!$db->query('CALL updateCategoryRelations(' . $recordId . ')')) {
            echo $db->getError();
            die;
            return false;
        }
        CacheHelper::clear(CacheHelper::TYPE_PRODUCT_CATEGORIES);
        return true;
    }

    /**
     * enableParentCategories
     *
     * @return bool
     */
    public function enableParentCategories(): bool
    {
        $catId = $this->getMainTableRecordId();
        if (1 > $catId) {
            $this->error = Labels::getLabel('ERR_INVALID_REQUEST', CommonHelper::getLangId());
            return false;
        }

        $qry = 'UPDATE ' . static::DB_TBL . '
        INNER JOIN ' . static::DB_TBL_PROD_CAT_RELATIONS . ' ON pcr_parent_id = prodcat_id
        SET prodcat_active = ' . applicationConstants::ACTIVE . '
        WHERE pcr_prodcat_id = ' . $catId;

        $db = FatApp::getDb();
        if (!$db->query($qry)) {
            $this->error = $db->getError();
            return false;
        }
        CacheHelper::clear(CacheHelper::TYPE_PRODUCT_CATEGORIES);
        return true;
    }

    /**
     * disableChildCategories
     *
     * @return bool
     */
    public function disableChildCategories(): bool
    {
        $catId = $this->getMainTableRecordId();
        if (1 > $catId) {
            $this->error = Labels::getLabel('ERR_INVALID_REQUEST', CommonHelper::getLangId());
            return false;
        }

        $qry = 'UPDATE ' . static::DB_TBL . '
        INNER JOIN ' . static::DB_TBL_PROD_CAT_RELATIONS . ' ON pcr_prodcat_id = prodcat_id
        SET prodcat_active = ' . applicationConstants::INACTIVE . '
        WHERE pcr_parent_id = ' . $catId . ' or pcr_prodcat_id = ' . $catId;

        $db = FatApp::getDb();
        if (!$db->query($qry)) {
            $this->error = $db->getError();
            return false;
        }
        CacheHelper::clear(CacheHelper::TYPE_PRODUCT_CATEGORIES);
        return true;
    }

    public function unDeleteParentCategories(): bool
    {
        $catId = $this->getMainTableRecordId();
        if (1 > $catId) {
            $this->error = Labels::getLabel('ERR_INVALID_REQUEST', CommonHelper::getLangId());
            return false;
        }

        $qry = 'UPDATE ' . static::DB_TBL . '
        INNER JOIN ' . static::DB_TBL_PROD_CAT_RELATIONS . ' ON pcr_parent_id = prodcat_id
        SET prodcat_deleted = ' . applicationConstants::NO . '
        WHERE pcr_prodcat_id = ' . $catId;

        $db = FatApp::getDb();
        if (!$db->query($qry)) {
            $this->error = $db->getError();
            return false;
        }
        ProductCategory::UpdateHasChildCategoryFlag(0);
        return true;
    }

    public function deleteChildCategories(): bool
    {
        $catId = $this->getMainTableRecordId();
        if (1 > $catId) {
            $this->error = Labels::getLabel('ERR_INVALID_REQUEST', CommonHelper::getLangId());
            return false;
        }

        $qry = 'UPDATE ' . static::DB_TBL . '
        INNER JOIN ' . static::DB_TBL_PROD_CAT_RELATIONS . ' ON pcr_prodcat_id = prodcat_id
        SET prodcat_deleted = ' . applicationConstants::YES . '
        WHERE pcr_parent_id = ' . $catId . ' or pcr_prodcat_id = ' . $catId;

        $db = FatApp::getDb();
        if (!$db->query($qry)) {
            $this->error = $db->getError();
            return false;
        }
        ProductCategory::UpdateHasChildCategoryFlag(0);
        return true;
    }

    /**
     * getParents
     *
     * @param  array $attr
     * @return array
     */
    public function getParents(array $attr = []): array
    {
        $catId = $this->getMainTableRecordId();
        $srch = new SearchBase(ProductCategory::DB_TBL_PROD_CAT_RELATIONS, 'cr');
        $srch->addCondition('pcr_prodcat_id', '=', 'mysql_func_' . $catId, 'AND', true);
        $srch->addOrder('pcr_level', 'DESC');
        if (!empty($attr)) {
            $attr = in_array('pcr_parent_id', $attr) ? $attr : array_merge($attr, ['pcr_parent_id']);
            $srch->addMultipleFields($attr);
        } else {
            $srch->addFld('pcr_parent_id');
        }

        $srch->doNotCalculateRecords();
        $srch->doNotLimitRecords();
        $rs = $srch->getResultSet();
        return (array) FatApp::getDb()->fetchAll($rs, 'pcr_parent_id');
    }

    /**
     * getChildrens
     *
     * @param  array $attr
     * @return array
     */
    public function getChildrens(array $attr = [], bool $skipDeleted = true): array
    {
        $catId = $this->getMainTableRecordId();
        $srch = new SearchBase(ProductCategory::DB_TBL_PROD_CAT_RELATIONS, 'cr');
        $srch->addCondition('pcr_parent_id', '=', 'mysql_func_' . $catId, 'AND', true);
        if ($skipDeleted) {
            $srch->joinTable(static::DB_TBL, 'INNER JOIN', 'cr.pcr_prodcat_id = pccr.prodcat_id AND pccr.prodcat_deleted = 0', 'pccr');
        }
        $srch->addOrder('pcr_level', 'DESC');
        if (!empty($attr)) {
            $attr = in_array('pcr_prodcat_id', $attr) ? $attr : array_merge($attr, ['pcr_prodcat_id']);
            $srch->addMultipleFields($attr);
        } else {
            $srch->addFld('pcr_prodcat_id');
        }

        $srch->doNotCalculateRecords();
        $srch->doNotLimitRecords();
        $rs = $srch->getResultSet();
        return (array) FatApp::getDb()->fetchAll($rs, 'pcr_prodcat_id');
    }

    public function addUpdateRatingType(int $rtId)
    {
        if (1 > $this->mainTableRecordId || 1 > $rtId) {
            $this->error = Labels::getLabel('ERR_INVALID_REQUEST', $this->commonLangId);
            return false;
        }
        $record = new TableRecord(static::DB_TBL_PROD_CAT_RATING_TYPES);

        $data = [
            static::DB_TBL_PROD_CAT_RT_PREFIX . 'prodcat_id' => $this->mainTableRecordId,
            static::DB_TBL_PROD_CAT_RT_PREFIX . 'ratingtype_id' => $rtId
        ];

        $record->assignValues($data);
        if (!$record->addNew(array(), $data)) {
            $this->error = $record->getError();
            return false;
        }

        return true;
    }

    public function removeRatingType(int $rtId)
    {
        $db = FatApp::getDb();
        if (1 > $this->mainTableRecordId || 1 > $rtId) {
            $this->error = Labels::getLabel('ERR_INVALID_REQUEST', $this->commonLangId);
            return false;
        }

        if (!$db->deleteRecords(static::DB_TBL_PROD_CAT_RATING_TYPES, array('smt' => static::DB_TBL_PROD_CAT_RT_PREFIX . 'prodcat_id = ? AND ' . static::DB_TBL_PROD_CAT_RT_PREFIX . 'ratingtype_id = ?', 'vals' => array($this->mainTableRecordId, $rtId)))) {
            $this->error = $db->getError();
            return false;
        }

        return true;
    }

    public function getRatingTypes(int $langId = 0, int $isActive = -1): array
    {
        $langId = 1 > $langId ? $this->commonLangId : $langId;

        if (1 > $this->mainTableRecordId) {
            $this->error = Labels::getLabel('ERR_INVALID_REQUEST', $langId);
            return [];
        }

        $srch = self::getRatingTypesObj($langId, $isActive);
        $srch->addCondition('prt_prodcat_id', '=', 'mysql_func_' . $this->mainTableRecordId, 'AND', true);
        $srch->addCondition('ratingtype_active', '=', 'mysql_func_' . applicationConstants::ACTIVE, 'AND', true);
        $srch->addMultipleFields(['ratingtype_id', 'COALESCE(ratingtype_name, ratingtype_identifier) as ratingtype_name', 'ratingtype_active', 'ratingtype_default']);
        $srch->doNotCalculateRecords();
        $rs = $srch->getResultSet();
        return (array) FatApp::getDb()->fetchAll($rs);
    }

    public static function getRatingTypesObj(int $langId = 0, int $isActive = -1)
    {
        $srch = new SearchBase(static::DB_TBL_PROD_CAT_RATING_TYPES, 'prt');
        $srch->joinTable(
            RatingType::DB_TBL,
            'INNER JOIN',
            'rt.ratingtype_id = prt.prt_ratingtype_id',
            'rt'
        );
        $srch->joinTable(
            RatingType::DB_TBL_LANG,
            'LEFT OUTER JOIN',
            'rt_l.ratingtypelang_ratingtype_id = rt.ratingtype_id AND rt_l.ratingtypelang_lang_id = ' . $langId,
            'rt_l'
        );

        if (0 < $isActive) {
            $srch->addCondition('ratingtype_active', '=', 'mysql_func_' . $isActive, 'AND', true);
        }
        $srch->doNotCalculateRecords();
        $srch->doNotLimitRecords();
        return $srch;
    }


    public static function isParentCategory($prodCat_id)
    {
        $srch = static::getSearchObject();
        $srch->addCondition('prodcat_parent', '=', 0);
        $srch->addCondition('prodcat_active', '=', applicationConstants::ACTIVE);
        $srch->addCondition('prodcat_deleted', '=', applicationConstants::NO);
        $srch->addCondition('prodcat_id', '=', $prodCat_id);
        $srch->addMultipleFields(array('prodcat_id'));
        $srch->doNotCalculateRecords();
        $srch->setPageSize(1);
        $rs = $srch->getResultSet();
        $records = FatApp::getDb()->fetch($rs);
        if (empty($records)) {
            return false;
        }
        return true;
    }

    public static function UpdateHasChildCategoryFlag($catId = 0)
    {
        FatApp::getDb()->query('CALL UpdateHasChildCategoryFlag(' . $catId . ')');
    }

    public static function sortCategoriesByNameAsc(array $categories): array
    {
        uasort($categories, function ($a, $b) {
            return strcasecmp($a['prodcat_name'], $b['prodcat_name']);
        });
    
        foreach ($categories as &$category) {
            if (!empty($category['children']) && is_array($category['children'])) {
                $category['children'] = self::sortCategoriesByNameAsc($category['children']);
            }
        }
    
        return $categories;
    }
    
    

}
