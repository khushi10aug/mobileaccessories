<?php

class CompatibleBrand extends MyAppModel
{
    public const DB_TBL = 'tbl_compatible_brands';
    public const DB_TBL_LANG = 'tbl_compatible_brands_lang';
    public const DB_TBL_PREFIX = 'cbrand_';
    public const DB_TBL_LANG_PREFIX = 'cbrandlang_';

    public const BRAND_REQUEST_PENDING = 0;
    public const BRAND_REQUEST_APPROVED = 1;
    public const BRAND_REQUEST_CANCELLED = 2;

    public const REWRITE_URL_PREFIX = 'cbrands/view/';

    public function __construct($id = 0)
    {
        parent::__construct(static::DB_TBL, static::DB_TBL_PREFIX . 'id', $id);
    }

    public static function getSearchObject($langId = 0, $isDeleted = true, $isActive = false, $addOrderBy = true)
    {
        $srch = new SearchBase(static::DB_TBL, 'b');

        if ($isDeleted == true) {
            $srch->addCondition('b.' . static::DB_TBL_PREFIX . 'deleted', '=', 'mysql_func_' . applicationConstants::NO, 'AND', true);
        }
        if ($isActive == true) {
            $srch->addCondition('b.' . static::DB_TBL_PREFIX . 'active', '=', 'mysql_func_' . applicationConstants::ACTIVE, 'AND', true);
        }

        if ($langId > 0) {
            $srch->joinTable(
                static::DB_TBL_LANG,
                'LEFT OUTER JOIN',
                'b_l.' . static::DB_TBL_LANG_PREFIX . 'cbrand_id = b.' . static::tblFld('id') . ' and
			b_l.' . static::DB_TBL_LANG_PREFIX . 'lang_id = ' . $langId,
                'b_l'
            );
        }

        if (true === $addOrderBy) {
            $srch->addOrder('b.' . static::DB_TBL_PREFIX . 'active', 'DESC');
        }
        return $srch;
    }

    public static function requiredFields()
    {
        return array(
            ImportexportCommon::VALIDATE_POSITIVE_INT => array(
                'brand_id'
            ),
            ImportexportCommon::VALIDATE_NOT_NULL => array(
                'brand_identifier',
                'brand_name',
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
                'cbrand_id'
            ),
            ImportexportCommon::VALIDATE_NOT_NULL => array(
                'cbrand_identifier',
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

    public static function getListingObj($langId, $attr = null, $isActive = false)
    {
        $srch = self::getSearchObject($langId, true, $isActive);

        if (null != $attr) {
            if (is_array($attr)) {
                $srch->addMultipleFields($attr);
            } elseif (is_string($attr)) {
                $srch->addFld($attr);
            }
        }

        $srch->addMultipleFields(
            array(
                'IFNULL(b_l.cbrand_name,b.cbrand_identifier) as cbrand_name'
            )
        );

        return $srch;
    }

    public static function getAllIdentifierAssoc(int $langId = 0): array
    {
        $langId = FatUtility::int($langId);
        $srch = self::getSearchObject($langId, true);
        $srch->doNotCalculateRecords();
        $srch->doNotLimitRecords();
        $srch->addMultipleFields(array(static::tblFld('id'), static::tblFld('identifier')));
        return FatApp::getDb()->fetchAllAssoc($srch->getResultSet());
    }

    public function canRecordMarkDelete($id)
    {
        $srch = $this->getSearchObject();
        $srch->addCondition('b.' . static::DB_TBL_PREFIX . 'id', '=', 'mysql_func_' . $id, 'AND', true);
        $srch->addFld('b.' . static::DB_TBL_PREFIX . 'id');
        $srch->doNotCalculateRecords();
        $srch->setPageSize(1);
        $rs = $srch->getResultSet();
        $row = FatApp::getDb()->fetch($rs);
        if (!empty($row) && $row[static::DB_TBL_PREFIX . 'id'] == $id) {
            return true;
        }
        return false;
    }

    public function rewriteUrl(string $keyword)
    {
        if ($this->mainTableRecordId < 1) {
            return false;
        }

        $originalUrl = Brand::REWRITE_URL_PREFIX . $this->mainTableRecordId;

        $seoUrl = CommonHelper::seoUrl($keyword);

        $customUrl = UrlRewrite::getValidSeoUrl($seoUrl, $originalUrl, $this->mainTableRecordId);

        return UrlRewrite::update($originalUrl, $customUrl);
    }

    public static function getBrandReqStatusArr(int $langId)
    {
        $langId = FatUtility::int($langId);
        if ($langId == 0) {
            trigger_error(Labels::getLabel('ERR_LANGUAGE_ID_NOT_SPECIFIED.', CommonHelper::getLangId()), E_USER_ERROR);
        }
        $arr = array(
            static::BRAND_REQUEST_PENDING => Labels::getLabel('LBL_PENDING', $langId),
            static::BRAND_REQUEST_APPROVED => Labels::getLabel('LBL_APPROVED', $langId),
            static::BRAND_REQUEST_CANCELLED => Labels::getLabel('LBL_CANCELLED', $langId)
        );
        return $arr;
    }

    public static function getBrandReqStatusClassArr()
    {
        return array(
            static::BRAND_REQUEST_PENDING => applicationConstants::CLASS_INFO,
            static::BRAND_REQUEST_APPROVED => applicationConstants::CLASS_SUCCESS,
            static::BRAND_REQUEST_CANCELLED => applicationConstants::CLASS_DANGER
        );
    }

    public static function getBrandName(int $brandId, int $langId, bool $isActive = true): string
    {
        $srch = static::getListingObj($langId, null, $isActive);
        $srch->addCondition('b.' . static::DB_TBL_PREFIX . 'id', '=', 'mysql_func_' . $brandId, 'AND', true);
        $srch->doNotCalculateRecords();
        $srch->setPageSize(1);
        $row = FatApp::getDb()->fetch($srch->getResultSet());
        return $row['brand_name'] ?? '';
    }
}
