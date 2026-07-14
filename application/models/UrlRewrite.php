<?php

class UrlRewrite extends MyAppModel
{
    public const DB_TBL = 'tbl_url_rewrite';
    public const DB_TBL_PREFIX = 'urlrewrite_';
    private $db;

    public function __construct($id = 0)
    {
        parent::__construct(static::DB_TBL, static::DB_TBL_PREFIX . 'id', $id);
        $this->db = FatApp::getDb();
    }

    public static function getSearchObject()
    {
        return new SearchBase(static::DB_TBL, 'ur');
    }

    public static function remove($originalUrl)
    {
        if (FatApp::getDb()->deleteRecords(static::DB_TBL, array('smt' => 'urlrewrite_original = ?', 'vals' => array($originalUrl)))) {
            return true;
        }
        return false;
    }

    /**
     * True when custom slug still has characters that break public SEO URLs
     * (unicode dashes, slash, pipe, entities leftovers, spaces, etc.).
     */
    public static function isMalformedCustomUrl($customUrl): bool
    {
        $customUrl = (string) $customUrl;
        if ($customUrl === '') {
            return false;
        }
        /* Anything outside a-z 0-9 hyphen, or repeated hyphens / HTML entity leftovers */
        if (preg_match('/[^a-z0-9\-]/', $customUrl)) {
            return true;
        }
        if (strpos($customUrl, '--') !== false) {
            return true;
        }
        return false;
    }

    /**
     * Re-sanitize urlrewrite_custom rows (and matching selprod_url_keyword) so sitemap / links
     * no longer emit – / | &ndash; etc. Returns counts: updated, unchanged, failed.
     */
    public static function cleanupMalformedCustomUrls(): array
    {
        $result = ['updated' => 0, 'unchanged' => 0, 'failed' => 0];
        $srch = static::getSearchObject();
        $srch->doNotCalculateRecords();
        $srch->doNotLimitRecords();
        $srch->addMultipleFields(['urlrewrite_id', 'urlrewrite_original', 'urlrewrite_custom']);
        $rows = FatApp::getDb()->fetchAll($srch->getResultSet());
        if (empty($rows)) {
            return $result;
        }

        $db = FatApp::getDb();
        foreach ($rows as $row) {
            $current = (string) $row['urlrewrite_custom'];
            if (!static::isMalformedCustomUrl($current) && $current === CommonHelper::seoUrl($current)) {
                $result['unchanged']++;
                continue;
            }

            $clean = static::getValidSeoUrl($current, $row['urlrewrite_original']);
            if ($clean === '' || $clean === $current) {
                $result['unchanged']++;
                continue;
            }

            if (
                !$db->updateFromArray(
                    static::DB_TBL,
                    ['urlrewrite_custom' => $clean],
                    ['smt' => 'urlrewrite_id = ?', 'vals' => [$row['urlrewrite_id']]]
                )
            ) {
                $result['failed']++;
                continue;
            }

            if (preg_match('#^products/view/(\d+)$#', (string) $row['urlrewrite_original'], $m)) {
                $db->updateFromArray(
                    SellerProduct::DB_TBL,
                    ['selprod_url_keyword' => $clean],
                    ['smt' => 'selprod_id = ?', 'vals' => [(int) $m[1]]]
                );
            }

            $result['updated']++;
        }

        return $result;
    }

    public static function update($originalUrl, $customUrl)
    {
        $seoUrlKeyword = array(
            'urlrewrite_original' => $originalUrl,
            'urlrewrite_custom' => $customUrl
        );
        if (FatApp::getDb()->insertFromArray(static::DB_TBL, $seoUrlKeyword, false, array(), array('urlrewrite_custom' => $customUrl))) {
            return true;
        }
        return false;
    }

    public static function getDataByCustomUrl($customUrl, $originalUrl = false)
    {
        $urlSrch = static::getSearchObject();
        $urlSrch->doNotCalculateRecords();
        $urlSrch->setPageSize(1);
        $urlSrch->addMultipleFields(array('urlrewrite_id', 'urlrewrite_original', 'urlrewrite_custom'));
        $urlSrch->addCondition('urlrewrite_custom', '=', $customUrl);
        if ($originalUrl) {
            $urlSrch->addCondition('urlrewrite_original', '!=', $originalUrl);
        }
        $rs = $urlSrch->getResultSet();
        $urlRow = FatApp::getDb()->fetch($rs);
        if ($urlRow == false) {
            return array();
        }

        return $urlRow;
    }
    public static function getDataByOriginalUrl($originalUrl, $excludeThisCustomUrl = false)
    {
        $urlSrch = static::getSearchObject();
        $urlSrch->doNotCalculateRecords();
        $urlSrch->setPageSize(1);
        $urlSrch->addMultipleFields(array('urlrewrite_id', 'urlrewrite_original', 'urlrewrite_custom'));
        $urlSrch->addCondition('urlrewrite_original', '=', $originalUrl);
        if ($excludeThisCustomUrl) {
            $urlSrch->addCondition('urlrewrite_custom', '!=', $excludeThisCustomUrl);
        }
        $rs = $urlSrch->getResultSet();
        $urlRow = FatApp::getDb()->fetch($rs);
        if ($urlRow == false) {
            return array();
        }

        return $urlRow;
    }

    public static function getValidSeoUrl($urlKeyword, $originalUrl, $recordId = 0)
    {
        $customUrl = CommonHelper::seoUrl($urlKeyword);

        $res = static::getDataByCustomUrl($customUrl, $originalUrl);
        if (empty($res)) {
            return $customUrl;
        }

        $i = 1;
        if ($recordId > 0) {
            $customUrl = preg_replace('/-' . $recordId . '$/', '', $customUrl) . '-' . $recordId;
        }

        $slug = $customUrl;

        while (static::getDataByCustomUrl($slug, $originalUrl)) {
            $slug = $customUrl . "-" . $i++;
        }

        return $slug;
    }

    public static function isCustomUrlUnique($customUrl)
    {
        return 1 > count(static::getDataByCustomUrl($customUrl));
    }

    public static function getTypeArray($langId)
    {
        $urlRewriteOrgAssoc = CacheHelper::get('urlRewriteOrgAssoc' .  $langId, CONF_DEF_CACHE_TIME, '.txt');
        if ($urlRewriteOrgAssoc) {
            return json_decode($urlRewriteOrgAssoc, true);
        }

        $arr = [
            Shop::SHOP_VIEW_ORGINAL_URL => Labels::getLabel('FRM_SHOP_URLS', $langId),
            Shop::SHOP_REVIEWS_ORGINAL_URL => Labels::getLabel('FRM_SHOP_REVIEW_URLS', $langId),
            Shop::SHOP_POLICY_ORGINAL_URL => Labels::getLabel('FRM_SHOP_POLICY_URLS', $langId),
            Shop::SHOP_SEND_MESSAGE_ORGINAL_URL => Labels::getLabel('FRM_SHOP_MESSAGE_URLS', $langId),
            Shop::SHOP_TOP_PRODUCTS_ORGINAL_URL => Labels::getLabel('FRM_SHOP_TOP_PRODUCTS_URLS', $langId),
            Shop::SHOP_COLLECTION_ORGINAL_URL => Labels::getLabel('FRM_SHOP_COLLECTION_URLS', $langId),
            Brand::REWRITE_URL_PREFIX => Labels::getLabel('FRM_BRANDS_URLS', $langId),
            BlogPost::REWRITE_URL_PREFIX => Labels::getLabel('FRM_BLOG_POST_URLS', $langId),
            BlogPostCategory::REWRITE_URL_PREFIX => Labels::getLabel('FRM_BLOG_CATEGORY_URLS', $langId),
            ContentPage::REWRITE_URL_PREFIX => Labels::getLabel('FRM_CMS_PAGES_URLS', $langId),
            Extrapage::REWRITE_URL_PREFIX => Labels::getLabel('FRM_EXTRA_PAGES_URLS', $langId),
            ProductCategory::REWRITE_URL_PREFIX => Labels::getLabel('FRM_CATEGORIES_URLS', $langId),
            Product::PRODUCT_VIEW_ORGINAL_URL => Labels::getLabel('FRM_PRODUCT_URLS', $langId),
            Product::PRODUCT_REVIEWS_ORGINAL_URL => Labels::getLabel('FRM_PRODUCT_REVIEWS_URLS', $langId),
            Product::PRODUCT_MORE_SELLERS_ORGINAL_URL => Labels::getLabel('FRM_MORE_SELLERS_URLS', $langId),
        ];

        CacheHelper::create('urlRewriteOrgAssoc' . $langId, FatUtility::convertToJson($arr), CacheHelper::TYPE_LABELS);
        return $arr;
    }
}
