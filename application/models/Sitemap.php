<?php
class Sitemap extends FatModel
{
    private $siteMapLanguages = [];
    private $defaultLangId = 0;
    private $siteMapIndexArr = [];
    private $sitemapListInc = 1;
    private $recordCountInc = 0;
    private $limit = 2000;
    private $sitemapDir = 'sitemap';

    public function __construct()
    {
        $this->defaultLangId = FatApp::getConfig('CONF_DEFAULT_SITE_LANG', FatUtility::VAR_INT, 1);
        $recordId = 0;
        if (!FatApp::getConfig('CONF_LANG_SPECIFIC_URL', FatUtility::VAR_INT, 0)) {
            $recordId = $this->defaultLangId;
        }
        $this->siteMapLanguages = Language::getAllNames(false, $recordId);
    }

    public function generate()
    {
        set_time_limit(0);

        /* Sanitize broken SEO slugs (–, /, |, &ndash;, etc.) before writing XML locs. */
        UrlRewrite::cleanupMalformedCustomUrls();

        $structure = $this->getStructure();
        foreach ($structure as $val) {
            $this->sitemapListInc = 1;
            $this->recordCountInc = 0;
            $this->writeSitemapIndex($val);
        }

        $this->writeStructureIndex();


        if (1 < count($this->siteMapLanguages)) {
            $this->writeSitemapLangSpecific();
        }

        $this->writePrimarySitemapIndex();

        Message::addMessage(Labels::getLabel('MSG_SITEMAP_HAS_BEEN_UPDATED_SUCCESSFULLY'));
        CommonHelper::redirectUserReferer();
    }

    private function writeSitemapIndex($type)
    {
        switch ($type) {
            case 'products':
                $this->writeProductSitemap($type);
                break;
            case 'categories':
                $this->writeCategorySitemap($type);
                break;
            case 'brands':
                $this->writeBrandSitemap($type);
                break;
            case 'shops':
                $this->writeShopSitemap($type);
                break;
            case 'cms':
                $this->writeCmsSitemap($type);
                break;
        }
    }

    private function getProductSrchObj($langId = 0)
    {
        $prodSrchObj = new ProductSearch($langId);
        $prodSrchObj->setDefinedCriteria(1);
        $prodSrchObj->joinProductToCategory();
        $prodSrchObj->joinSellerSubscription();
        $prodSrchObj->addSubscriptionValidCondition();
        $prodSrchObj->doNotCalculateRecords();
        $prodSrchObj->doNotLimitRecords();
        return $prodSrchObj;
    }

    private function writeProductSitemap($type)
    {
        $prodSrch = $this->getProductSrchObj();

        foreach ($this->siteMapLanguages as $language) {
            $this->startSitemapXml();
            $url = UrlHelper::getUrlScheme();
            $file = $this->sitemapDir;
            if ($this->defaultLangId != $language['language_id']) {
                $url .=  '/' . strtolower($language['language_code']);
                $file .= '/' . strtolower($language['language_code']);
            }
            $file .= '/products';

            $prodSrch->addMultipleFields(array('selprod_id', 'product_id'));
            $prodSrch->addGroupBy('selprod_id');
            $prodSrch->doNotCalculateRecords();
            $prodSrch->doNotLimitRecords();
            $rs = $prodSrch->getResultSet();
            while ($row = FatApp::getDb()->fetch($rs)) {
                $productImagesArr = array();
                $options = SellerProduct::getSellerProductOptions($row['selprod_id'], false);
                if (count($options) > 0) {
                    foreach ($options as $op) {
                        $images = AttachedFile::getMultipleAttachments(AttachedFile::FILETYPE_PRODUCT_IMAGE, $row['product_id'], $op['selprodoption_optionvalue_id'], $language['language_id'], true);
                        if ($images) {
                            $productImagesArr += $images;
                        }
                    }
                }
                $this->writeSitemapUrl(UrlHelper::generateFullUrl('products', 'view', array($row['selprod_id']), CONF_WEBROOT_FRONT_URL, null, false, false, true, $language['language_id']), $file, 'weekly', ['product' => $productImagesArr]);
            }

            $this->endSitemapXml($file);
            $this->resetSiteMapListInc($type);
        }
    }

    private function writeCategorySitemap($type)
    {
        foreach ($this->siteMapLanguages as $language) {
            $this->startSitemapXml();
            $url = UrlHelper::getUrlScheme();

            $file = $this->sitemapDir;
            if ($this->defaultLangId != $language['language_id']) {
                $url .=  '/' . strtolower($language['language_code']);
                $file .= '/' . strtolower($language['language_code']);
            }
            $file .= '/categories';
            $categoriesArr = ProductCategory::getArray($language['language_id'], 0, false, true, false, CONF_USE_FAT_CACHE, false);
            foreach ($categoriesArr as $key => $val) {
                $imagesArr = [];
                $imagesArr[] = AttachedFile::getAttachment(AttachedFile::FILETYPE_CATEGORY_BANNER, $val['prodcat_id'], 0, $language['language_id']);

                $this->writeSitemapUrl(UrlHelper::generateFullUrl('category', 'view', array($val['prodcat_id']), CONF_WEBROOT_FRONT_URL, null, false, false, true, $language['language_id']), $file, 'weekly', ['category' => $imagesArr]);
            }

            $this->endSitemapXml($file);
            $this->resetSiteMapListInc($type);
        }
    }

    private function writeBrandSitemap($type)
    {
        foreach ($this->siteMapLanguages as $language) {
            $prodSrchObj = $this->getProductSrchObj($language['language_id']);
            $this->startSitemapXml();
            $url = UrlHelper::getUrlScheme();
            $file = $this->sitemapDir;
            if ($this->defaultLangId != $language['language_id']) {
                $url .=  '/' . strtolower($language['language_code']);
                $file .= '/' . strtolower($language['language_code']);
            }
            $file .= '/brands';

            $brandSrch = clone $prodSrchObj;
            $brandSrch->addMultipleFields(array('brand_id', 'brand_updated_on'));
            $brandSrch->addGroupBy('brand_id');
            $brandSrch->addOrder('brand_name');
            $brandSrch->doNotCalculateRecords();
            $brandSrch->doNotLimitRecords();
            $brandRs = $brandSrch->getResultSet();
            while ($row = FatApp::getDb()->fetch($brandRs)) {
                if (empty($row['brand_id'])) {
                    continue;
                }
                $imagesArr = [];
                $imagesArr[] = AttachedFile::getAttachment(AttachedFile::FILETYPE_BRAND_LOGO, $row['brand_id'], 0, $language['language_id'], (count($this->siteMapLanguages) > 1) ? false : true);
                $this->writeSitemapUrl(UrlHelper::generateFullUrl('brands', 'view', array($row['brand_id']), CONF_WEBROOT_FRONT_URL, null, false, false, true, $language['language_id']), $file, 'weekly', ['brand' => $imagesArr]);
            }

            $this->endSitemapXml($file);
            $this->resetSiteMapListInc($type);
        }
    }

    private function writeShopSitemap($type)
    {
        foreach ($this->siteMapLanguages as $language) {
            $this->startSitemapXml();
            $url = UrlHelper::getUrlScheme();
            $file = $this->sitemapDir;
            if ($this->defaultLangId != $language['language_id']) {
                $url .=  '/' . strtolower($language['language_code']);
                $file .= '/' . strtolower($language['language_code']);
            }
            $file .= '/shops';

            $shopSrch = new ShopSearch();
            $shopSrch->setDefinedCriteria();
            $shopSrch->joinShopCountry();
            $shopSrch->joinShopState();
            $shopSrch->joinSellerSubscription();
            $shopSrch->doNotCalculateRecords();
            $shopSrch->doNotLimitRecords();
            $shopSrch->addMultipleFields(array('shop_id'));
            $rs = $shopSrch->getResultSet();
            while ($row = FatApp::getDb()->fetch($rs)) {
                $imagesArr = [];
                $imagesArr[] = AttachedFile::getAttachment(AttachedFile::FILETYPE_SHOP_LOGO, $row['shop_id'], 0, $language['language_id']);

                $this->writeSitemapUrl(UrlHelper::generateFullUrl('shops', 'view', array($row['shop_id']), CONF_WEBROOT_FRONT_URL, null, false, false, true, $language['language_id']), $file, 'weekly', ['shop' => $imagesArr]);
            }

            $this->endSitemapXml($file);
            $this->resetSiteMapListInc($type);
        }
    }

    private function writeCmsSitemap($type)
    {
        foreach ($this->siteMapLanguages as $language) {
            $this->startSitemapXml();
            $url = UrlHelper::getUrlScheme();
            $file = $this->sitemapDir;
            if ($this->defaultLangId != $language['language_id']) {
                $url .=  '/' . strtolower($language['language_code']);
                $file .= '/' . strtolower($language['language_code']);
            }
            $file .= '/cms';

            $cmsSrch = new NavigationLinkSearch();
            $cmsSrch->joinNavigation();
            $cmsSrch->joinProductCategory();
            $cmsSrch->joinContentPages();
            $cmsSrch->doNotCalculateRecords();
            $cmsSrch->doNotLimitRecords();
            $cmsSrch->addOrder('nav_id');
            $cmsSrch->addOrder('nlink_display_order');

            $cmsSrch->addCondition('nlink_deleted', '=', '0');
            $cmsSrch->addCondition('nav_active', '=', applicationConstants::ACTIVE);
            $cmsSrch->addMultipleFields(array('nlink_cpage_id, nlink_type'));
            $rs = $cmsSrch->getResultSet();
            $this->writeSitemapUrl(UrlHelper::generateFullUrl('', '', array(), CONF_WEBROOT_FRONT_URL, null, false, false, true, $language['language_id']), $file);
            while ($row = FatApp::getDb()->fetch($rs)) {
                if ($row['nlink_type'] == NavigationLinks::NAVLINK_TYPE_CMS && $row['nlink_cpage_id']) {
                    $this->writeSitemapUrl(UrlHelper::generateFullUrl('Cms', 'view', array($row['nlink_cpage_id']), CONF_WEBROOT_FRONT_URL, null, false, false, true, $language['language_id']), $file);
                }
            }
            $this->endSitemapXml($file);
            $this->resetSiteMapListInc($type);
        }
    }

    private function startSitemapXml()
    {
        ob_start();
        echo '<?xml version="1.0" encoding="utf-8"?>' . "\n";
        echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:image="https://www.google.com/schemas/sitemap-image/1.1">' . "\n";
    }

    private function writeSitemapUrl($url, $file, $freq = 'weekly', $imagesUrl = [])
    {
        $this->recordCountInc++;
        if ($this->recordCountInc > $this->limit) {
            $this->endSitemapXml($file, true);
            $this->startSitemapXml();
            $this->recordCountInc = 0;
        }
        echo "
			<url>
				<loc>" . $url . "</loc>
                <lastmod>" . date('Y-m-d') . "</lastmod>
                <changefreq>" . $freq . "</changefreq>
                <priority>0.8</priority>";

        if (isset($imagesUrl) && !empty($imagesUrl)) {
            foreach ($imagesUrl as $key => $imagesArr) {
                switch ($key) {
                    case 'product':
                        foreach (array_filter($imagesArr) as $image) {
                            if (1 > $image['afile_id']) {
                                continue;
                            }
                            $uploadedTime = AttachedFile::setTimeParam($image['afile_updated_at']);
                            $mainImgUrl = UrlHelper::getCachedUrl(UrlHelper::generateFullFileUrl('Image', 'product', array($image['afile_record_id'], ImageDimension::VIEW_MEDIUM, 0, $image['afile_id']), CONF_WEBROOT_FRONTEND) . $uploadedTime, CONF_IMG_CACHE_TIME, '.jpg');
                            echo "<image:image>
                            <image:loc>" . $mainImgUrl . "</image:loc>
                            </image:image>";
                        }
                        break;
                    case 'brand':
                        foreach (array_filter($imagesArr) as $image) {
                            if (1 > $image['afile_id']) {
                                continue;
                            }
                            $uploadedTime = AttachedFile::setTimeParam($image['afile_updated_at']);
                            $mainImgUrl = UrlHelper::getCachedUrl(UrlHelper::generateFullFileUrl('Image', 'brand', array($image['afile_record_id'], ImageDimension::VIEW_MINI_THUMB, 0, $image['afile_id']), CONF_WEBROOT_FRONTEND) . $uploadedTime, CONF_IMG_CACHE_TIME, '.jpg');
                            echo "<image:image>
                            <image:loc>" . $mainImgUrl . "</image:loc>
                            </image:image>";
                        }
                        break;
                    case 'category':
                        foreach (array_filter($imagesArr) as $image) {
                            if (1 > $image['afile_id']) {
                                continue;
                            }
                            $uploadedTime = AttachedFile::setTimeParam($image['afile_updated_at']);
                            $mainImgUrl = UrlHelper::getCachedUrl(UrlHelper::generateFullFileUrl('Category', 'Banner', array($image['afile_record_id'], $image['afile_lang_id'], ImageDimension::VIEW_DESKTOP, $image['afile_id'], applicationConstants::SCREEN_DESKTOP), CONF_WEBROOT_FRONTEND) . $uploadedTime, CONF_IMG_CACHE_TIME, '.jpg');
                            echo "<image:image>
                                <image:loc>" . $mainImgUrl . "</image:loc>
                                </image:image>";
                        }
                        break;
                    case 'shop':
                        foreach (array_filter($imagesArr) as $image) {
                            if (1 > $image['afile_id']) {
                                continue;
                            }
                            $uploadedTime = AttachedFile::setTimeParam($image['afile_updated_at']);
                            $mainImgUrl = UrlHelper::getCachedUrl(UrlHelper::generateFullFileUrl('Image', 'ShopLogo', array($image['afile_record_id'], $image['afile_lang_id'], ImageDimension::VIEW_THUMB, $image['afile_id']), CONF_WEBROOT_FRONTEND) . $uploadedTime, CONF_IMG_CACHE_TIME, '.jpg');
                            echo "<image:image>
                                <image:loc>" . $mainImgUrl . "</image:loc>
                                </image:image>";
                        }
                        break;
                }
            }
        }

        echo "</url>";
        echo "\n";
    }

    private function endSitemapXml($file, $changeList = false)
    {
        $file = $file . $this->sitemapListInc;
        if ($changeList) {
            $this->sitemapListInc++;
        }

        echo '</urlset>' . "\n";
        $contents = ob_get_clean();
        $rs = '';
        CommonHelper::writeFile($file . '.xml', $contents, $rs);
    }

    private function writePrimarySitemapIndex()
    {
        ob_start();
        echo "<?xml version='1.0' encoding='UTF-8'?>
		<sitemapindex xmlns:xsi='http://www.w3.org/2001/XMLSchema-instance' xsi:schemaLocation='http://www.sitemaps.org/schemas/sitemap/0.9 http://www.sitemaps.org/schemas/sitemap/0.9/siteindex.xsd' xmlns='http://www.sitemaps.org/schemas/sitemap/0.9'>\n";

        if (1 < count($this->siteMapLanguages)) {
            foreach ($this->siteMapLanguages as $language) {
                $url = UrlHelper::getUrlScheme() . '/' . $this->sitemapDir;
                // $url .=  "/" . strtolower($language['language_code']);
                if ($this->defaultLangId != $language['language_id']) {
                    $url .=  "/" . strtolower($language['language_code']);
                }
                echo "<sitemap><loc>" . $url . "/sitemap.xml</loc></sitemap>\n";
            }
        } else {
            $structure = $this->getStructure();
            foreach ($structure as $val) {
                echo "<sitemap><loc>" . UrlHelper::getUrlScheme() . '/' . $this->sitemapDir . '/' . strtolower($val) . ".xml</loc></sitemap>\n";
            }
        }

        echo "</sitemapindex>";
        $contents = ob_get_clean();
        $rs = '';
        CommonHelper::writeFile('sitemap.xml', $contents, $rs);
    }

    private function writeSitemapLangSpecific()
    {
        $structure = $this->getStructure();
        foreach ($this->siteMapLanguages as $language) {
            ob_start();
            echo "<?xml version='1.0' encoding='UTF-8'?>
    <sitemapindex xmlns:xsi='http://www.w3.org/2001/XMLSchema-instance' xsi:schemaLocation='http://www.sitemaps.org/schemas/sitemap/0.9 http://www.sitemaps.org/schemas/sitemap/0.9/siteindex.xsd' xmlns='http://www.sitemaps.org/schemas/sitemap/0.9'>\n";
            foreach ($structure as $val) {
                $url = UrlHelper::getUrlScheme() . '/' . $this->sitemapDir;
                if ($this->defaultLangId != $language['language_id']) {
                    $url .=  "/" . strtolower($language['language_code']);
                }
                $url .= "/" . strtolower($val) . '.xml';

                echo "<sitemap><loc>" . $url . "</loc></sitemap>\n";
            }

            echo "</sitemapindex>";
            $contents = ob_get_clean();
            $rs = '';

            $file = $this->sitemapDir;
            if ($this->defaultLangId != $language['language_id']) {
                $file .= '/' . strtolower($language['language_code']);
            }

            CommonHelper::writeFile($file . '/sitemap.xml', $contents, $rs);
        }
    }

    private function writeStructureIndex()
    {
        foreach ($this->siteMapLanguages as $language) {
            foreach ($this->siteMapIndexArr as $type => $listingCount) {
                if (1 == $listingCount) {
                    $url = CONF_UPLOADS_PATH . '/' . $this->sitemapDir;
                    if ($this->defaultLangId != $language['language_id']) {
                        $url .= '/' . strtolower($language['language_code']);
                    }

                    $url .= '/' . strtolower($type);

                    rename($url . '1.xml', $url . '.xml');
                    continue;
                }

                ob_start();
                echo "<?xml version='1.0' encoding='UTF-8'?>
		<sitemapindex xmlns:xsi='http://www.w3.org/2001/XMLSchema-instance' xsi:schemaLocation='http://www.sitemaps.org/schemas/sitemap/0.9 http://www.sitemaps.org/schemas/sitemap/0.9/siteindex.xsd' xmlns='http://www.sitemaps.org/schemas/sitemap/0.9'>\n";

                for ($i = 1; $i <= $listingCount; $i++) {
                    $url = UrlHelper::getUrlScheme() . '/' . $this->sitemapDir;
                    if ($this->defaultLangId != $language['language_id']) {
                        $url .= '/' . strtolower($language['language_code']);
                    }
                    echo "<sitemap><loc>" . $url . '/' . strtolower($type) . $i . ".xml</loc></sitemap>\n";
                }

                echo "</sitemapindex>";
                $contents = ob_get_clean();
                $rs = '';

                $file = $this->sitemapDir;
                if ($this->defaultLangId != $language['language_id']) {
                    $file .= '/' . strtolower($language['language_code']);
                }
                CommonHelper::writeFile($file . '/' . strtolower($type) . '.xml', $contents, $rs);
            }
        }
    }

    private function getStructure()
    {
        return  [
            'products',
            'categories',
            'brands',
            'shops',
            'cms'
        ];
    }

    private function resetSiteMapListInc($type)
    {
        $this->siteMapIndexArr[$type] = $this->sitemapListInc;
        $this->sitemapListInc = 1;
    }
}
