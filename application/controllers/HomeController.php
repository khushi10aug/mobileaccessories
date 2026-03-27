<?php
class HomeController extends MyAppController
{
    public function index()
    {
        $loggedUserId = UserAuthentication::getLoggedUserId(true);
        $sponsoredShopsInCollection = $sponsoredProdsInCollection = [];
        $collections = $this->getCollections($loggedUserId, $sponsoredShopsInCollection, $sponsoredProdsInCollection);
        $sponShopLayoutCount = count($sponsoredShopsInCollection);
        $sponProdLayoutCount = count($sponsoredProdsInCollection);
        if (0 < $sponProdLayoutCount) {
            foreach ($sponsoredProdsInCollection as $indexId => $collectionId) {
                $ind = $collectionId;
                if (true === MOBILE_APP_API_CALL) {
                    $ind =  array_search(Collections::COLLECTION_TYPE_SPONSORED_PRODUCTS, array_column($collections, 'collection_type'));
                }
                $sponsoredProds = $this->getSponsoredProducts($loggedUserId);

                if (empty($sponsoredProds)) {
                    unset($collections[$ind]);
                    continue;
                }

                $selProdIdsArr = array_column($sponsoredProds, 'selprod_id');
                $tRightRibbons = Badge::getRibbons($this->siteLangId, Badge::RIBB_POS_TRIGHT, $selProdIdsArr);

                if (true === MOBILE_APP_API_CALL) {
                    foreach ($sponsoredProds as &$product) {
                        $selProdRibbons = [];
                        if (array_key_exists($product['selprod_id'], $tRightRibbons)) {
                            $selProdRibbons[] = $tRightRibbons[$product['selprod_id']];
                        }
                        $uploadedTime = AttachedFile::setTimeParam($product['product_updated_on']);
                        $product['product_image_url'] = UrlHelper::getCachedUrl(UrlHelper::generateFullFileUrl('image', 'product', array($product['product_id'], ImageDimension::VIEW_CLAYOUT3, $product['selprod_id'], 0, $this->siteLangId)) . $uploadedTime, CONF_IMG_CACHE_TIME, '.jpg');
                        $product['discount'] = ($product['selprod_price'] > $product['theprice']) ? CommonHelper::showProductDiscountedText($product, $this->siteLangId) : '';
                        $product['selprod_price'] = CommonHelper::displayMoneyFormat($product['selprod_price']);
                        $product['theprice'] = CommonHelper::displayMoneyFormat($product['theprice']);
                        $product['ribbons'] = $selProdRibbons;
                    }
                }

                $collections[$ind]['products'] = $sponsoredProds;
                $collections[$ind]['totProducts'] = count($sponsoredProds);

                if (false === MOBILE_APP_API_CALL) {
                    $collections[$ind]['tRightRibbons'] = $tRightRibbons;
                }
            }
        }

        if (0 < $sponShopLayoutCount) {
            foreach ($sponsoredShopsInCollection as $indexId => $collectionId) {
                $recordId = $collectionId;
                if (true === MOBILE_APP_API_CALL) {
                    $recordId =  array_search(Collections::COLLECTION_TYPE_SPONSORED_SHOPS, array_column($collections, 'collection_type'));
                }
                $sponsoredShops = $this->getSponsoredShops();

                if (empty($sponsoredShops)) {
                    unset($collections[$recordId]);
                    continue;
                }

                $collections[$recordId]['shops'] = $sponsoredShops;
                $collections[$recordId]['totShops'] = count($sponsoredShops);
            }
        }

        $this->set('collections', $collections);

        if (true === MOBILE_APP_API_CALL) {
            $slides = $this->getSlides();
            $this->set('slides', $slides);
            $this->_template->render();
            die;
        } else {
            $slides = $this->getSlides(1);
            $this->set('slides', $slides);
        }

        $displayProductNotAvailableLable = false;
        //availableInLocation
        if (FatApp::getConfig('CONF_ENABLE_GEO_LOCATION', FatUtility::VAR_INT, 0) && !empty(FatApp::getConfig('CONF_GOOGLEMAP_API_KEY', FatUtility::VAR_STRING, ''))) {
            $displayProductNotAvailableLable = true;
        }

        $this->_template->addJs(['js/slick.min.js', 'js/slick-carousels.js']);
        $geoAddress = Address::getYkGeoData();
        $cacheKey = $this->siteLangId . '-' . CommonHelper::getCurrencyId() . '-' . serialize($geoAddress);
        $cacheKey .= FatApp::getConfig('LAST_FAV_MARK_TIME', FatUtility::VAR_INT, 0);

        $collectionTemplates = array();
        foreach ($collections as $collection) {
            
            switch ($collection['collection_layout_type']) {
                case Collections::TYPE_HERO_SLIDES_LAYOUT1:
                    $tpl = new FatTemplate('', '');
                    $tpl->set('siteLangId', $this->siteLangId);
                    if (true === MOBILE_APP_API_CALL) {
                        $tpl->set('slides', $collection['slides']);
                    } else {
                        $tpl->set('slides', $slides);
                    }
                    $tpl->set('fullWidth', $collection['collection_full_width']);
                    $sponsoredProdsLayout = $tpl->render(false, false, '_partial/homePageSlides.php', true, true);
                    $collectionTemplates[$collection['collection_id']]['html'] = $sponsoredProdsLayout;
                    break;
                case Collections::TYPE_SPONSORED_PRODUCT_LAYOUT:
                    $tpl = new FatTemplate('', '');
                    $tpl->set('siteLangId', $this->siteLangId);
                    $tpl->set('collection', $collection);
                    $tpl->set('displayProductNotAvailableLable', $displayProductNotAvailableLable);
                    $sponsoredProdsLayout = $tpl->render(false, false, '_partial/collection/sponsored-products.php', true, true);
                    $collectionTemplates[$collection['collection_id']]['html'] = $sponsoredProdsLayout;
                    break;
                case Collections::TYPE_SPONSORED_SHOP_LAYOUT:
                    $tpl = new FatTemplate('', '');
                    $tpl->set('siteLangId', $this->siteLangId);
                    $tpl->set('collection', $collection);
                    $sponsoredShopsLayout = $tpl->render(false, false, '_partial/collection/sponsored-shops.php', true, true);
                    $collectionTemplates[$collection['collection_id']]['html'] = $sponsoredShopsLayout;
                    break;
                case Collections::TYPE_BANNER_LAYOUT1:
                    if (isset($collection['banners'])) {
                        $tpl = new FatTemplate('', '');
                        $tpl->set('siteLangId', $this->siteLangId);
                        $tpl->set('bannerLayout1', $collection['banners']);
                        $tpl->set('fullWidth', ($collection['collection_full_width'] ?? 1));
                        $bannerFirstLayout = $tpl->render(false, false, '_partial/banners/home-banner-first-layout.php', true, true);
                        $collectionTemplates[$collection['collection_id']]['html'] = $bannerFirstLayout;
                    }
                    break;
                case Collections::TYPE_BANNER_LAYOUT2:
                    if (isset($collection['banners'])) {
                        $tpl = new FatTemplate('', '');
                        $tpl->set('siteLangId', $this->siteLangId);
                        $tpl->set('bannerLayout1', $collection['banners']);
                        $tpl->set('collection', $collection);
                        $bannersecondLayout = $tpl->render(false, false, '_partial/banners/home-banner-second-layout.php', true, true);
                        $collectionTemplates[$collection['collection_id']]['html'] = $bannersecondLayout;
                    }
                    break;
                case Collections::TYPE_BANNER_LAYOUT4:
                    if (isset($collection['banners'])) {
                        $tpl = new FatTemplate('', '');
                        $tpl->set('siteLangId', $this->siteLangId);
                        $tpl->set('bannerLayout1', $collection['banners']);
                        $tpl->set('collection', $collection);
                        $bannersecondLayout = $tpl->render(false, false, '_partial/banners/home-banner-fourth-layout.php', true, true);
                        $collectionTemplates[$collection['collection_id']]['html'] = $bannersecondLayout;
                    }
                    break;
                case Collections::TYPE_BANNER_LAYOUT5:
                    if (isset($collection['banners'])) {
                        $tpl = new FatTemplate('', '');
                        $tpl->set('siteLangId', $this->siteLangId);
                        $tpl->set('bannerLayout1', $collection['banners']);
                        $tpl->set('collection', $collection);
                        $bannersecondLayout = $tpl->render(false, false, '_partial/banners/home-banner-fifth-layout.php', true, true);
                        $collectionTemplates[$collection['collection_id']]['html'] = $bannersecondLayout;
                    }
                    break;
                case Collections::TYPE_PRODUCT_LAYOUT1:
                case Collections::TYPE_PRODUCT_LAYOUT6:
                    $homePageProdLayout1 = CacheHelper::get('homePageProdLayout1' . $collection['collection_id'] . $cacheKey, CONF_HOME_PAGE_CACHE_TIME, '.txt');
                    if (!$homePageProdLayout1) {
                        $tpl = new FatTemplate('', '');
                        $tpl->set('siteLangId', $this->siteLangId);
                        $tpl->set('collection', $collection);
                        $tpl->set('displayProductNotAvailableLable', $displayProductNotAvailableLable);
                        $homePageProdLayout1 = $tpl->render(false, false, '_partial/collection/product-layout-1.php', true, true);
                        CacheHelper::create('homePageProdLayout1' . $collection['collection_id'] . $cacheKey, $homePageProdLayout1, CacheHelper::TYPE_COLLECTIONS);
                    }
                    $collectionTemplates[$collection['collection_id']]['html'] = $homePageProdLayout1;
                    break;
                case Collections::TYPE_PRODUCT_LAYOUT7:
                    $homePageProdLayout1 = CacheHelper::get('homePageProdLayout1' . $collection['collection_id'] . $cacheKey, CONF_HOME_PAGE_CACHE_TIME, '.txt');
                    if (!$homePageProdLayout1) {
                        $tpl = new FatTemplate('', '');
                        $tpl->set('siteLangId', $this->siteLangId);
                        $tpl->set('collection', $collection);
                        $tpl->set('displayProductNotAvailableLable', $displayProductNotAvailableLable);
                        $homePageProdLayout1 = $tpl->render(false, false, '_partial/collection/product-layout-7.php', true, true);
                        CacheHelper::create('homePageProdLayout1' . $collection['collection_id'] . $cacheKey, $homePageProdLayout1, CacheHelper::TYPE_COLLECTIONS);
                    }
                    $collectionTemplates[$collection['collection_id']]['html'] = $homePageProdLayout1;
                    break;
                case Collections::TYPE_PRODUCT_LAYOUT2:
                    $homePageProdLayout2 = CacheHelper::get('homePageProdLayout2' . $collection['collection_id'] . $cacheKey, CONF_HOME_PAGE_CACHE_TIME, '.txt');
                    if (!$homePageProdLayout2) {
                        $tpl = new FatTemplate('', '');
                        $tpl->set('siteLangId', $this->siteLangId);
                        $tpl->set('collection', $collection);
                        $tpl->set('displayProductNotAvailableLable', $displayProductNotAvailableLable);
                        $homePageProdLayout2 = $tpl->render(false, false, '_partial/collection/product-layout-2.php', true, true);
                        CacheHelper::create('homePageProdLayout2' . $collection['collection_id'] . $cacheKey, $homePageProdLayout2, CacheHelper::TYPE_COLLECTIONS);
                    }
                    $collectionTemplates[$collection['collection_id']]['html'] = $homePageProdLayout2;
                    break;

                case Collections::TYPE_PRODUCT_LAYOUT3:
                    $homePageProdLayout3 = CacheHelper::get('homePageProdLayout3' . $collection['collection_id'] . $cacheKey, CONF_HOME_PAGE_CACHE_TIME, '.txt');
                    if (!$homePageProdLayout3) {
                        $tpl = new FatTemplate('', '');
                        $tpl->set('siteLangId', $this->siteLangId);
                        $tpl->set('collection', $collection);
                        $tpl->set('displayProductNotAvailableLable', $displayProductNotAvailableLable);
                        $homePageProdLayout3 = $tpl->render(false, false, '_partial/collection/product-layout-3.php', true, true);
                        CacheHelper::create('homePageProdLayout3' . $collection['collection_id'] . $cacheKey, $homePageProdLayout3, CacheHelper::TYPE_COLLECTIONS);
                    }
                    $collectionTemplates[$collection['collection_id']]['html'] = $homePageProdLayout3;
                    break;
                case Collections::TYPE_PRODUCT_LAYOUT4:
                    $homePageProdLayout4 = CacheHelper::get('homePageProdLayout4' . $collection['collection_id'] . $cacheKey, CONF_HOME_PAGE_CACHE_TIME, '.txt');
                    if (!$homePageProdLayout4) {
                        $tpl = new FatTemplate('', '');
                        $tpl->set('siteLangId', $this->siteLangId);
                        $tpl->set('collection', $collection);
                        $tpl->set('displayProductNotAvailableLable', $displayProductNotAvailableLable);
                        $homePageProdLayout4 = $tpl->render(false, false, '_partial/collection/product-layout-4.php', true, true);
                        CacheHelper::create('homePageProdLayout4' . $collection['collection_id'] . $cacheKey, $homePageProdLayout4, CacheHelper::TYPE_COLLECTIONS);
                    }
                    $collectionTemplates[$collection['collection_id']]['html'] = $homePageProdLayout4;
                    break;
                case Collections::TYPE_CATEGORY_LAYOUT1:
                    $homePageCatLayout1 = CacheHelper::get('homePageCatLayout1' . $collection['collection_id'] . $cacheKey, CONF_HOME_PAGE_CACHE_TIME, '.txt');
                    if (!$homePageCatLayout1) {
                        $tpl = new FatTemplate('', '');
                        $tpl->set('siteLangId', $this->siteLangId);
                        $tpl->set('collection', $collection);
                        $tpl->set('displayProductNotAvailableLable', $displayProductNotAvailableLable);
                        $homePageCatLayout1 = $tpl->render(false, false, '_partial/collection/category-layout-1.php', true, true);
                        CacheHelper::create('homePageCatLayout1' . $collection['collection_id'] . $cacheKey, $homePageCatLayout1, CacheHelper::TYPE_COLLECTIONS);
                    }
                    $collectionTemplates[$collection['collection_id']]['html'] = $homePageCatLayout1;
                    break;
                case Collections::TYPE_CATEGORY_LAYOUT2:
                    $homePageCatLayout2 = CacheHelper::get('homePageCatLayout2' . $collection['collection_id'] . $cacheKey, CONF_HOME_PAGE_CACHE_TIME, '.txt');
                    if (!$homePageCatLayout2) {
                        $tpl = new FatTemplate('', '');
                        $tpl->set('siteLangId', $this->siteLangId);
                        $tpl->set('collection', $collection);
                        $tpl->set('`displayProductNotAvailableLable`', $displayProductNotAvailableLable);
                        $homePageCatLayout2 = $tpl->render(false, false, '_partial/collection/category-layout-2.php', true, true);
                        CacheHelper::create('homePageCatLayout2' . $collection['collection_id'] . $cacheKey, $homePageCatLayout2, CacheHelper::TYPE_COLLECTIONS);
                    }
                    $collectionTemplates[$collection['collection_id']]['html'] = $homePageCatLayout2;
                    break;
                case Collections::TYPE_CATEGORY_LAYOUT12:
                    $homePageCatLayout12 = CacheHelper::get('homePageCatLayout12' . $collection['collection_id'] . $cacheKey, CONF_HOME_PAGE_CACHE_TIME, '.txt');
                    if (!$homePageCatLayout12) {
                        $tpl = new FatTemplate('', '');
                        $tpl->set('siteLangId', $this->siteLangId);
                        $tpl->set('collection', $collection);
                        $tpl->set('`displayProductNotAvailableLable`', $displayProductNotAvailableLable);
                        $homePageCatLayout12 = $tpl->render(false, false, '_partial/collection/category-layout-12.php', true, true);
                        CacheHelper::create('homePageCatLayout12' . $collection['collection_id'] . $cacheKey, $homePageCatLayout12, CacheHelper::TYPE_COLLECTIONS);
                    }
                    $collectionTemplates[$collection['collection_id']]['html'] = $homePageCatLayout12;
                    break;
                case Collections::TYPE_CATEGORY_LAYOUT3:
                    $homePageCatLayout3 = CacheHelper::get('homePageCatLayout3' . $collection['collection_id'] . $cacheKey, CONF_HOME_PAGE_CACHE_TIME, '.txt');
                    if (!$homePageCatLayout3) {
                        $tpl = new FatTemplate('', '');
                        $tpl->set('siteLangId', $this->siteLangId);
                        $tpl->set('collection', $collection);
                        $tpl->set('`displayProductNotAvailableLable`', $displayProductNotAvailableLable);
                        $homePageCatLayout3 = $tpl->render(false, false, '_partial/collection/category-layout-3.php', true, true);
                        CacheHelper::create('homePageCatLayout3' . $collection['collection_id'] . $cacheKey, $homePageCatLayout3, CacheHelper::TYPE_COLLECTIONS);
                    }
                    $collectionTemplates[$collection['collection_id']]['html'] = $homePageCatLayout3;
                    break;
                case Collections::TYPE_CATEGORY_LAYOUT4:
                case Collections::TYPE_CATEGORY_LAYOUT8:
                    $homePageCatLayout4 = CacheHelper::get('homePageCatLayout4' . $collection['collection_id'] . $cacheKey, CONF_HOME_PAGE_CACHE_TIME, '.txt');
                    if (!$homePageCatLayout4) {
                        $tpl = new FatTemplate('', '');
                        $tpl->set('siteLangId', $this->siteLangId);
                        $tpl->set('collection', $collection);
                        $tpl->set('displayProductNotAvailableLable', $displayProductNotAvailableLable);
                        $homePageCatLayout4 = $tpl->render(false, false, '_partial/collection/category-layout-4.php', true, true);
                        CacheHelper::create('homePageCatLayout4' . $collection['collection_id'] . $cacheKey, $homePageCatLayout4, CacheHelper::TYPE_COLLECTIONS);
                    }
                    $collectionTemplates[$collection['collection_id']]['html'] = $homePageCatLayout4;
                    break;
                case Collections::TYPE_CATEGORY_LAYOUT10:
                    $homePageCatLayout10 = CacheHelper::get('homePageCatLayout10' . $collection['collection_id'] . $cacheKey, CONF_HOME_PAGE_CACHE_TIME, '.txt');
                    if (!$homePageCatLayout10) {
                        $tpl = new FatTemplate('', '');
                        $tpl->set('siteLangId', $this->siteLangId);
                        $tpl->set('collection', $collection);
                        $tpl->set('displayProductNotAvailableLable', $displayProductNotAvailableLable);
                        $homePageCatLayout10 = $tpl->render(false, false, '_partial/collection/category-layout-10.php', true, true);
                        CacheHelper::create('homePageCatLayout10' . $collection['collection_id'] . $cacheKey, $homePageCatLayout10, CacheHelper::TYPE_COLLECTIONS);
                    }
                    $collectionTemplates[$collection['collection_id']]['html'] = $homePageCatLayout10;
                    break;
                case Collections::TYPE_CATEGORY_LAYOUT11:
                    $homePageCatLayout11 = CacheHelper::get('homePageCatLayout11' . $collection['collection_id'] . $cacheKey, CONF_HOME_PAGE_CACHE_TIME, '.txt');
                    if (!$homePageCatLayout11) {
                        $tpl = new FatTemplate('', '');
                        $tpl->set('siteLangId', $this->siteLangId);
                        $tpl->set('collection', $collection);
                        $tpl->set('displayProductNotAvailableLable', $displayProductNotAvailableLable);
                        $homePageCatLayout11 = $tpl->render(false, false, '_partial/collection/category-layout-11.php', true, true);
                        CacheHelper::create('homePageCatLayout11' . $collection['collection_id'] . $cacheKey, $homePageCatLayout11, CacheHelper::TYPE_COLLECTIONS);
                    }
                    $collectionTemplates[$collection['collection_id']]['html'] = $homePageCatLayout11;
                    break;
                case Collections::TYPE_CATEGORY_LAYOUT7:
                    $homePageCatLayout7 = CacheHelper::get('homePageCatLayout7' . $collection['collection_id'] . $cacheKey, CONF_HOME_PAGE_CACHE_TIME, '.txt');
                    if (!$homePageCatLayout7) {
                        $tpl = new FatTemplate('', '');
                        $tpl->set('siteLangId', $this->siteLangId);
                        $tpl->set('collection', $collection);
                        $tpl->set('`displayProductNotAvailableLable`', $displayProductNotAvailableLable);
                        $homePageCatLayout7 = $tpl->render(false, false, '_partial/collection/category-layout-7.php', true, true);
                        CacheHelper::create('homePageCatLayout7' . $collection['collection_id'] . $cacheKey, $homePageCatLayout7, CacheHelper::TYPE_COLLECTIONS);
                    }
                    $collectionTemplates[$collection['collection_id']]['html'] = $homePageCatLayout7;
                    break;
                case Collections::TYPE_CATEGORY_LAYOUT9:
                    $homePageCatLayout9 = CacheHelper::get('homePageCatLayout9' . $collection['collection_id'] . $cacheKey, CONF_HOME_PAGE_CACHE_TIME, '.txt');
                    if (!$homePageCatLayout9) {
                        $tpl = new FatTemplate('', '');
                        $tpl->set('siteLangId', $this->siteLangId);
                        $tpl->set('collection', $collection);
                        $tpl->set('`displayProductNotAvailableLable`', $displayProductNotAvailableLable);
                        $homePageCatLayout9 = $tpl->render(false, false, '_partial/collection/category-layout-9.php', true, true);
                        CacheHelper::create('homePageCatLayout9' . $collection['collection_id'] . $cacheKey, $homePageCatLayout9, CacheHelper::TYPE_COLLECTIONS);
                    }
                    $collectionTemplates[$collection['collection_id']]['html'] = $homePageCatLayout9;
                    break;
                case Collections::TYPE_SHOP_LAYOUT1:
                    $homePageShopLayout1 = CacheHelper::get('homePageShopLayout1' . $collection['collection_id'] . $cacheKey, CONF_HOME_PAGE_CACHE_TIME, '.txt');
                    if (!$homePageShopLayout1) {
                        $tpl = new FatTemplate('', '');
                        $tpl->set('siteLangId', $this->siteLangId);
                        $tpl->set('collection', $collection);
                        $homePageShopLayout1 = $tpl->render(false, false, '_partial/collection/shop-layout-1.php', true, true);
                        CacheHelper::create('homePageShopLayout1' . $collection['collection_id'] . $cacheKey, $homePageShopLayout1, CacheHelper::TYPE_COLLECTIONS);
                    }
                    $collectionTemplates[$collection['collection_id']]['html'] = $homePageShopLayout1;
                    break;
                case Collections::TYPE_SHOP_LAYOUT2:
                    $homePageShopLayout2 = CacheHelper::get('homePageShopLayout2' . $collection['collection_id'] . $cacheKey, CONF_HOME_PAGE_CACHE_TIME, '.txt');
                    if (!$homePageShopLayout2) {
                        $tpl = new FatTemplate('', '');
                        $tpl->set('siteLangId', $this->siteLangId);
                        $tpl->set('collection', $collection);
                        $homePageShopLayout2 = $tpl->render(false, false, '_partial/collection/shop-layout-2.php', true, true);
                        CacheHelper::create('homePageShopLayout2' . $collection['collection_id'] . $cacheKey, $homePageShopLayout2, CacheHelper::TYPE_COLLECTIONS);
                    }
                    $collectionTemplates[$collection['collection_id']]['html'] = $homePageShopLayout2;
                    break;
                case Collections::TYPE_SHOP_LAYOUT3:
                    $homePageShopLayout3 = CacheHelper::get('homePageShopLayout3' . $collection['collection_id'] . $cacheKey, CONF_HOME_PAGE_CACHE_TIME, '.txt');
                    if (!$homePageShopLayout3) {
                        $tpl = new FatTemplate('', '');
                        $tpl->set('siteLangId', $this->siteLangId);
                        $tpl->set('collection', $collection);
                        $homePageShopLayout3 = $tpl->render(false, false, '_partial/collection/shop-layout-3.php', true, true);
                        CacheHelper::create('homePageShopLayout3' . $collection['collection_id'] . $cacheKey, $homePageShopLayout3, CacheHelper::TYPE_COLLECTIONS);
                    }
                    $collectionTemplates[$collection['collection_id']]['html'] = $homePageShopLayout3;
                    break;
                case Collections::TYPE_BRAND_LAYOUT1:
                case Collections::TYPE_BRAND_LAYOUT3:
                    $homePageBrandLayout1 = CacheHelper::get('homePageBrandLayout1' . $collection['collection_id'] . $cacheKey, CONF_HOME_PAGE_CACHE_TIME, '.txt');
                    if (!$homePageBrandLayout1) {
                        $tpl = new FatTemplate('', '');
                        $tpl->set('siteLangId', $this->siteLangId);
                        $tpl->set('collection', $collection);
                        $homePageBrandLayout1 = $tpl->render(false, false, '_partial/collection/brand-layout-1.php', true, true);
                        CacheHelper::create('homePageBrandLayout1' . $collection['collection_id'] . $cacheKey, $homePageBrandLayout1, CacheHelper::TYPE_COLLECTIONS);
                    }
                    $collectionTemplates[$collection['collection_id']]['html'] = $homePageBrandLayout1;
                    break;
                case Collections::TYPE_BRAND_LAYOUT2:
                    $homePageBrandLayout2 = CacheHelper::get('homePageBrandLayout2' . $collection['collection_id'] . $cacheKey, CONF_HOME_PAGE_CACHE_TIME, '.txt');
                    if (!$homePageBrandLayout2) {
                        $tpl = new FatTemplate('', '');
                        $tpl->set('siteLangId', $this->siteLangId);
                        $tpl->set('collection', $collection);
                        $homePageBrandLayout2 = $tpl->render(false, false, '_partial/collection/brand-layout-2.php', true, true);
                        CacheHelper::create('homePageBrandLayout2' . $collection['collection_id'] . $cacheKey, $homePageBrandLayout2, CacheHelper::TYPE_COLLECTIONS);
                    }
                    $collectionTemplates[$collection['collection_id']]['html'] = $homePageBrandLayout2;
                    break;
                case Collections::TYPE_BLOG_LAYOUT1:
                    $homePageBlogLayout1 = CacheHelper::get('homePageBlogLayout1' . $collection['collection_id'] . $cacheKey, CONF_HOME_PAGE_CACHE_TIME, '.txt');
                    if (!$homePageBlogLayout1) {
                        $tpl = new FatTemplate('', '');
                        $tpl->set('siteLangId', $this->siteLangId);
                        $tpl->set('collection', $collection);
                        $homePageBlogLayout1 = $tpl->render(false, false, '_partial/collection/blog-layout-1.php', true, true);
                        CacheHelper::create('homePageBlogLayout1' . $collection['collection_id'] . $cacheKey, $homePageBlogLayout1, CacheHelper::TYPE_COLLECTIONS);
                    }
                    $collectionTemplates[$collection['collection_id']]['html'] = $homePageBlogLayout1;
                    break;
                case Collections::TYPE_FAQ_LAYOUT1:
                    $homePageFaqLayout1 = CacheHelper::get('homePageFaqLayout1' . $collection['collection_id'] . $cacheKey, CONF_HOME_PAGE_CACHE_TIME, '.txt');
                    if (!$homePageFaqLayout1) {
                        $tpl = new FatTemplate('', '');
                        $tpl->set('siteLangId', $this->siteLangId);
                        $tpl->set('collection', $collection);
                        $homePageFaqLayout1 = $tpl->render(false, false, '_partial/collection/faq-layout-1.php', true, true);
                        CacheHelper::create('homePageFaqLayout1' . $collection['collection_id'] . $cacheKey, $homePageFaqLayout1, CacheHelper::TYPE_COLLECTIONS);
                    }
                    $collectionTemplates[$collection['collection_id']]['html'] = $homePageFaqLayout1;
                    break;
                case Collections::TYPE_TESTIMONIAL_LAYOUT1:
                case Collections::TYPE_TESTIMONIAL_LAYOUT2:
                    $homePageTestimonialLayout1 = CacheHelper::get('homePageTestimonialLayout1' . $collection['collection_id'] . $cacheKey, CONF_HOME_PAGE_CACHE_TIME, '.txt');
                    if (!$homePageTestimonialLayout1) {
                        $tpl = new FatTemplate('', '');
                        $tpl->set('siteLangId', $this->siteLangId);
                        $tpl->set('collection', $collection);
                        $viewFile = '_partial/collection/testimonial-layout-1.php';
                        if (Collections::TYPE_TESTIMONIAL_LAYOUT2 == $collection['collection_layout_type']) {
                            $viewFile = '_partial/collection/testimonial-layout-2.php';
                        }
                        $homePageTestimonialLayout1 = $tpl->render(false, false, $viewFile, true, true);
                        CacheHelper::create('homePageTestimonialLayout1' . $collection['collection_id'] . $cacheKey, $homePageTestimonialLayout1, CacheHelper::TYPE_COLLECTIONS);
                    }
                    $collectionTemplates[$collection['collection_id']]['html'] = $homePageTestimonialLayout1;
                    break;
                case Collections::TYPE_CONTENT_BLOCK_LAYOUT1:
                case Collections::TYPE_CONTENT_BLOCK_LAYOUT2:
                    $homePageContentBlockLayout1 = CacheHelper::get('homePageContentBlockLayout1' . $collection['collection_id'] . $cacheKey, CONF_HOME_PAGE_CACHE_TIME, '.txt');
                    if (!$homePageContentBlockLayout1) {
                        $tpl = new FatTemplate('', '');
                        $tpl->set('siteLangId', $this->siteLangId);
                        $tpl->set('collection', $collection);
                        $homePageContentBlockLayout1 = $tpl->render(false, false, '_partial/collection/content-block-layout-1.php', true, true);
                        CacheHelper::create('homePageContentBlockLayout1' . $collection['collection_id'] . $cacheKey, $homePageContentBlockLayout1, CacheHelper::TYPE_COLLECTIONS);
                    }
                    $collectionTemplates[$collection['collection_id']]['html'] = $homePageContentBlockLayout1;
                    break;
            }
        }
        $this->set('collectionTemplates', $collectionTemplates);

        $this->_template->render();
    }

    public function getSlidesHtml()
    {
        $this->set('slides', $this->getSlides());
        $this->set('fullWidth', FatApp::getPostedData('fullWidth', FatUtility::VAR_INT, 0));
        $this->set('html', $this->_template->render(false, false, '_partial/homePageSlides.php', true, true));
        $this->_template->render(false, false, 'json-success.php', false, false);
    }

    private function getProductSearchObj($loggedUserId, $criteria = [])
    {
        $loggedUserId = FatUtility::int($loggedUserId);

        $productSrchObj = new ProductSearch($this->siteLangId);
        $productSrchObj->setLocationBasedInnerJoin(false);
        $productSrchObj->setGeoAddress();
        $productSrchObj->joinProductToCategory($this->siteLangId, true, true, true, false, $criteria);
        $productSrchObj->joinProductToTax();
        /* $productSrchObj->doNotCalculateRecords();
        $productSrchObj->setPageSize( 10 ); */
        $criteria['doNotJoinSellers'] = true;
        $productSrchObj->setDefinedCriteria(0, 0, $criteria);
        $productSrchObj->joinSellerSubscription($this->siteLangId, true);
        $productSrchObj->addSubscriptionValidCondition();
        $productSrchObj->validateAndJoinDeliveryLocation(false);
        // $productSrchObj->joinProductRating();

        /*  if (FatApp::getConfig('CONF_ADD_FAVORITES_TO_WISHLIST', FatUtility::VAR_INT, 1) == applicationConstants::NO) {
            $productSrchObj->joinFavouriteProducts($loggedUserId);
            $productSrchObj->addFld('IFNULL(ufp_id, 0) as ufp_id');
        } else {
            $productSrchObj->joinUserWishListProducts($loggedUserId);
            $productSrchObj->addFld('IFNULL(uwlp.uwlp_selprod_id, 0) as is_in_any_wishlist');
        } */

        $productSrchObj->addCondition('selprod_deleted', '=', 'mysql_func_' . applicationConstants::NO, 'AND', true);
        $productSrchObj->addMultipleFields(array('product_id', 'selprod_id', 'IFNULL(product_name, product_identifier) as product_name', 'prodcat_code', 'IFNULL(selprod_title  ,IFNULL(product_name, product_identifier)) as selprod_title', 'product_updated_on', 'special_price_found', 'splprice_display_list_price', 'splprice_display_dis_val', 'splprice_display_dis_type', 'theprice', 'selprod_price', 'selprod_stock', 'selprod_condition', 'prodcat_id', 'IFNULL(prodcat_name, prodcat_identifier) as prodcat_name', 'selprod_sold_count', 'IF(selprod_stock > 0, 1, 0) AS in_stock', 'shop_id', 'selprod_min_order_qty', 'selprod_cart_type', 'selprod_hide_price', 'product_rating', 'shop_rfq_enabled', 'product_type'));
        return $productSrchObj;
    }

    public function languages()
    {
        $languages = Language::getAllNames(false);
        $languageArr = array();
        if (0 < count($languages)) {
            $siteDefaultLangId = FatApp::getConfig('CONF_DEFAULT_SITE_LANG', FatUtility::VAR_INT, 1);
            foreach ($languages as &$language) {
                $language['isSiteDefaultLang'] = ($language['language_id'] === $siteDefaultLangId) ? 1 : 0;
                $languageArr[] = $language;
            }
        }
        $this->set('languages', $languageArr);
        $this->_template->render();
    }

    public function setLanguage($langId = 0, $pathname = '')
    {
        if (!FatUtility::isAjaxCall()) {
            die('Invalid Action.');
        }

        $pathname = FatApp::getPostedData('pathname', FatUtility::VAR_STRING, '');
        $pathname = str_replace(ltrim(CONF_WEBROOT_FRONTEND, '/'), '', $pathname);

        $redirectUrl = '';
        if (empty($pathname)) {
            $redirectUrl = UrlHelper::generateFullUrl();
        }

        $isDefaultLangId = false;
        if ($langId == FatApp::getConfig('CONF_DEFAULT_SITE_LANG', FatUtility::VAR_INT, 1)) {
            $isDefaultLangId = true;
        }

        if (FatApp::getConfig('CONF_LANG_SPECIFIC_URL', FatUtility::VAR_INT, 0) && count(LANG_CODES_ARR) > 1) {
            $langCodeArr = LANG_CODES_ARR;
            if (count($langCodeArr) > 1) {
                if (!empty($pathname)) {
                    $existingUrlLangCode = strtoupper(substr(ltrim($pathname, '/'), 0, 2));
                } else {
                    $existingUrlLangCode = $langCodeArr[CommonHelper::getLangId()];
                }

                if (in_array($existingUrlLangCode, LANG_CODES_ARR)) {
                    $pathname = ltrim(substr(ltrim($pathname, '/'), 2), '/');
                } else {
                    $pathname = ltrim($pathname, '/');
                }

                $srch = UrlRewrite::getSearchObject();
                $srch->joinTable(UrlRewrite::DB_TBL, 'LEFT OUTER JOIN', 'temp.urlrewrite_original = ur.urlrewrite_original and temp.urlrewrite_lang_id = ' . $langId, 'temp');
                $srch->doNotCalculateRecords();
                $srch->setPageSize(1);
                $srch->addMultipleFields(array('ifnull(temp.urlrewrite_custom, ur.urlrewrite_custom) customurl'));
                $srch->addCondition('ur.' . UrlRewrite::DB_TBL_PREFIX . 'custom', '=', $pathname);
                // $srch->addCondition('ur.' . UrlRewrite::DB_TBL_PREFIX . 'lang_id', '=', $existingUrlLangId);

                $rs = $srch->getResultSet();
                $row = FatApp::getDb()->fetch($rs);

                if (!empty($row)) {
                    $redirectUrl = UrlHelper::generateFullUrl('', '', [], CONF_WEBROOT_FRONTEND, null, false, false, false);

                    if (false == $isDefaultLangId) {
                        $redirectUrl .= strtolower($langCodeArr[$langId]) . '/';
                    }
                    $redirectUrl .= $row['customurl'];
                }
            }

            if (empty($redirectUrl)) {
                $redirectUrl = UrlHelper::generateFullUrl('', '', [], CONF_WEBROOT_FRONTEND, null, false, false, false);
                if (false == $isDefaultLangId) {
                    $redirectUrl .= strtolower($langCodeArr[$langId]) . '/';
                }
                $redirectUrl .= ltrim($pathname, '/');;
            }
        } else {
            if (empty($redirectUrl)) {
                $redirectUrl = UrlHelper::generateFullUrl('', '', [], CONF_WEBROOT_FRONTEND, null, false, false, false) . ltrim($pathname, '/');
            }
        }


        $langId = FatUtility::int($langId);
        if (0 < $langId) {
            $languages = Language::getAllNames();
            if (array_key_exists($langId, $languages)) {
                setcookie('defaultSiteLang', $langId, time() + 3600 * 24 * 10, CONF_WEBROOT_URL);
            }
        }
        $this->set('redirectUrl', $redirectUrl);
        $this->_template->render(false, false, 'json-success.php');
    }

    /**
     * currencies : Used for APPs
     *
     * @return void
     */
    public function currencies()
    {
        $defaultCurrencyId = FatApp::getConfig('CONF_CURRENCY', FatUtility::VAR_INT, 1);
        $cObj = Currency::getSearchObject($this->siteLangId, true);
        $cObj->doNotCalculateRecords();
        $cObj->addMultipleFields(
            array(
                'currency_id',
                'currency_code',
                'IFNULL(curr_l.currency_name,curr.currency_code) as currency_name',
                'IF(currency_id = ' . $defaultCurrencyId . ', 1, 0) as isDefault'
            )
        );
        $currencies = FatApp::getDb()->fetchAll($cObj->getResultSet());
        $this->set('data', ['currencies' => $currencies]);
        $this->_template->render();
    }

    public function setCurrency($currencyId = 0)
    {
        if (!FatUtility::isAjaxCall()) {
            die('Invalid Action.');
        }

        $currencyId = FatUtility::int($currencyId);
        if (0 < $currencyId) {
            $currencies = Currency::getCurrencyAssoc($this->siteLangId);
            if (array_key_exists($currencyId, $currencies)) {
                setcookie('defaultSiteCurrency', $currencyId, time() + 3600 * 24 * 10, CONF_WEBROOT_URL);
            }
        }
    }

    public function languageLabels($download = 0, $langId = 0)
    {
        $langId = FatUtility::int($langId) > 0 ? $langId : $this->siteLangId;
        $download = FatUtility::int($download);
        $langCode = Language::getAttributesById($langId, 'language_code', false);

        if (0 < $download) {
            if (!Labels::updateDataToFile($langId, $langCode, Labels::TYPE_APP)) {
                FatUtility::dieJsonError(Labels::getLabel('ERR_Unable_to_update_file', $langId));
            }
            $fileName = $langCode . '.json';
            $filePath = Labels::JSON_FILE_DIR_NAME . '/' . Labels::TYPE_APP . '/AP/' . $fileName;

            if (false === file_exists(CONF_UPLOADS_PATH . $filePath)) {
                FatUtility::dieJsonError(Labels::getLabel('ERR_FILE_NOT_FOUND._PLEASE_SYNC_FILE_FROM_ADMIN.', $langId));
            }
            AttachedFile::downloadAttachment($filePath, $fileName);
            exit;
        }

        $data = array(
            'languageCode' => $langCode,
            'downloadUrl' => UrlHelper::generateFullUrl('Home', 'languageLabels', array(1, $langId)),
            'langLabelUpdatedAt' => FatApp::getConfig('CONF_LANG_LABELS_UPDATED_AT', FatUtility::VAR_INT, time())
        );

        $this->set('data', $data);
        $this->_template->render();
    }

    public function setCurrentLocation()
    {
        $post = FatApp::getPostedData();

        $countryCode = $post['country'];
        $this->updateSettingByCurrentLocation($countryCode);

        if (!$_SESSION['geo_location']) {
            FatUtility::dieJsonError(Labels::getLabel('ERR_Current_Location', $this->siteLangId));
        }
        $this->set('msg', Labels::getLabel('MSG_Settings_with_your_current_location_setup_successful', $this->siteLangId));
        $this->_template->render(false, false, 'json-success.php');
    }

    public function updateSettingByCurrentLocation($countryCode = '')
    {
        if (!$countryCode) {
            return;
        }

        $row = Countries::getCountryByCode($countryCode, array('country_code', 'country_id', 'country_currency_id', 'country_language_id'));
        if ($row == false) {
            return false;
        }
        $_SESSION['geo_location'] = true;
        $this->setCurrency($row['country_currency_id']);
        $this->setLanguage($row['country_language_id']);
    }

    public function affiliateReferral($referralCode)
    {
        $userSrchObj = User::getSearchObject();
        $userSrchObj->doNotCalculateRecords();
        $userSrchObj->doNotLimitRecords();
        $userSrchObj->addCondition('user_referral_code', '=', $referralCode);
        $userSrchObj->addMultipleFields(array('user_id', 'user_referral_code'));
        $rs = $userSrchObj->getResultSet();
        $row = FatApp::getDb()->fetch($rs);

        if ($row && $referralCode != '' && $row['user_referral_code'] == $referralCode) {
            $cookieExpiryDays = FatApp::getConfig("CONF_AFFILIATE_REFERRER_URL_VALIDITY", FatUtility::VAR_INT, 5);

            $cookieValue = array('data' => $row['user_referral_code'], 'creation_time' => time());
            $cookieValue = serialize($cookieValue);
            CommonHelper::setCookie('affiliate_referrer_code_signup', $cookieValue, time() + 3600 * 24 * $cookieExpiryDays);
        }
        FatApp::redirectUser(UrlHelper::generateUrl());
    }

    public function referral($userReferralCode)
    {
        $userSrchObj = User::getSearchObject();
        $userSrchObj->doNotCalculateRecords();
        $userSrchObj->doNotLimitRecords();
        $userSrchObj->addCondition('user_referral_code', '=', $userReferralCode);
        $userSrchObj->addMultipleFields(array('user_id', 'user_referral_code'));
        $rs = $userSrchObj->getResultSet();
        $row = FatApp::getDb()->fetch($rs);

        if ($row && $userReferralCode != '' && $row['user_referral_code'] == $userReferralCode) {
            $cookieExpiryDays = FatApp::getConfig("CONF_REFERRER_URL_VALIDITY", FatUtility::VAR_INT, 10);

            $cookieValue = array('data' => $row['user_referral_code'], 'creation_time' => time());
            $cookieValue = serialize($cookieValue);

            CommonHelper::setCookie('referrer_code_signup', $cookieValue, time() + 3600 * 24 * $cookieExpiryDays);
            CommonHelper::setCookie('referrer_code_checkout', $row['user_referral_code'], time() + 3600 * 24 * $cookieExpiryDays);
        }
        FatApp::redirectUser(UrlHelper::generateUrl());
    }

    private function getCollections($loggedUserId, &$sponsoredShopsInCollection, &$sponsoredProdsInCollection)
    {
        $langId = $this->siteLangId;
        $geoAddress = Address::getYkGeoData();
        $page = FatApp::getPostedData('page', FatUtility::VAR_INT, 1);
        $pageSize = FatApp::getPostedData('pageSize', FatUtility::VAR_INT, Collections::HOMEPAGE_COLLECTION_LIMIT);
        $pagesCount = 0;

        $cacheKey = $langId . '_' . CommonHelper::getCurrencyId() . '_' . FatUtility::int(MOBILE_APP_API_CALL) . '_' . serialize($geoAddress);
        $cacheKey .= FatApp::getConfig('LAST_FAV_MARK_TIME', FatUtility::VAR_INT, 0);
        if (MOBILE_APP_API_CALL) {
            $cacheKey .= '_' . $page . '_' . $pageSize;
        }

        $collectionCache = CacheHelper::get('collectionCache_' . $cacheKey, CONF_HOME_PAGE_CACHE_TIME, '.txt');
        /* Sponsered shops and products shall not be added in cache and we have handled rest of the through this cache variable*/
        $db = FatApp::getDb();

        $collectionsArr = CacheHelper::get('collectionsArr' . $cacheKey, CONF_HOME_PAGE_CACHE_TIME, '.txt');
        if (!empty($collectionsArr)) {
            if (MOBILE_APP_API_CALL) {
                $collectionsArr = unserialize($collectionsArr);
                $pagesCount = $collectionsArr['pageCount'];
                $collectionsArr = $collectionsArr['collectionArr'];
            } else {
                $collectionsArr = unserialize($collectionsArr);
            }
        } else {
            $srch = new CollectionSearch($langId);
            if (MOBILE_APP_API_CALL) {
                $srch->setPageNumber($page);
                $srch->setPageSize($pageSize);
                if ($page > 1) {
                    $srch->doNotCalculateRecords();
                }
            } else {
                $srch->doNotCalculateRecords();
                $srch->doNotLimitRecords();
            }
            $srch->addOrder('collection_display_order', 'ASC');
            $srch->addMultipleFields(array('collection_id', 'IFNULL(collection_name,collection_identifier) as collection_name', 'IFNULL( collection_description, "" ) as collection_description', 'IFNULL(collection_link_caption, "") as collection_link_caption', 'collection_link_url', 'collection_layout_type', 'collection_type', 'collection_criteria', 'collection_child_records', 'collection_primary_records', 'collection_display_media_only', 'collection_for_app', 'collection_for_web', 'collection_full_width', 'collection_display_order', 'collection_updated_on'));

            $applicableForCol = (true === MOBILE_APP_API_CALL) ? 'collection_for_app' : 'collection_for_web';
            $srch->addCondition($applicableForCol, '=', applicationConstants::YES);

            $rs = $srch->getResultSet();
            $collectionsArr = $db->fetchAll($rs, 'collection_id');
            if (MOBILE_APP_API_CALL) {
                $cacheData = [
                    'pageCount' => (1 == $page ? $srch->pages() : $pagesCount),
                    'collectionArr' => $collectionsArr,
                ];
            } else {
                $cacheData = $collectionsArr;
            }

            if (!empty($collectionsArr)) {
                CacheHelper::create('collectionsArr' . $cacheKey, serialize($cacheData), CacheHelper::TYPE_COLLECTIONS);
            }
            $pagesCount = (1 == $page ? $srch->pages() : $pagesCount);
        }

        if (MOBILE_APP_API_CALL) {
            $this->set('page', $page);
            $this->set('pageCount', $pagesCount);
            $this->set('pageSize', $pageSize);
        }

        if (empty($collectionsArr)) {
            return array();
        }

        $collections = array();

        $productCatSrchObj = ProductCategory::getSearchObject(false, $langId);
        $productCatSrchObj->addOrder('m.prodcat_active', 'DESC');
        $productCatSrchObj->addMultipleFields(array('prodcat_id', 'IFNULL(prodcat_name, prodcat_identifier) as prodcat_name', 'prodcat_description', 'prodcat_code'));

        $collectionObj = new CollectionSearch();
        $collectionObj->joinCollectionRecords();
        $collectionObj->addMultipleFields(array('ctr_record_id', 'ctr_display_order'));
        $collectionObj->addCondition('ctr_record_id', '!=', 'NULL');

        $i = 0;
        foreach ($collectionsArr as $collection_id => $collection) {
            if ($collectionCache && !in_array($collection['collection_type'], [Collections::COLLECTION_TYPE_SPONSORED_SHOPS, Collections::COLLECTION_TYPE_SPONSORED_PRODUCTS, Collections::COLLECTION_TYPE_BANNER, Collections::COLLECTION_TYPE_HERO_SLIDES])) {
                continue;
            }

            if (true === MOBILE_APP_API_CALL && 0 < $collection['collection_display_media_only'] && !in_array($collection['collection_type'], Collections::COLLECTION_WITHOUT_MEDIA)) {
                $uploadedTime = AttachedFile::setTimeParam($collection['collection_updated_on']);

                $collection['collection_image'] = UrlHelper::getCachedUrl(UrlHelper::generateFullFileUrl('image', 'collectionReal', array($collection_id, $langId, ImageDimension::VIEW_MOBILE, AttachedFile::FILETYPE_COLLECTION_IMAGE)) . $uploadedTime, CONF_IMG_CACHE_TIME, '.jpg');
                $collections[] = $collection;
                $i++;
                continue;
            }

            $ind = (true === MOBILE_APP_API_CALL) ? $i : $collection['collection_id'];
            switch ($collection['collection_type']) {
                case Collections::COLLECTION_TYPE_HERO_SLIDES:
                    $collections[$ind] = $collection;
                    $slides = $this->getSlides();
                    if (true === MOBILE_APP_API_CALL) {
                        $appScreenType = CommonHelper::getAppScreenType();
                        $resType = $appScreenType == applicationConstants::SCREEN_IPAD ? ImageDimension::VIEW_TABLET : ImageDimension::VIEW_MOBILE;
                        foreach ($slides as &$slideDetail) {
                            $uploadedTime = AttachedFile::setTimeParam($slideDetail['slide_img_updated_on']);
                            $slideDetail['slide_image_url'] = UrlHelper::getCachedUrl(UrlHelper::generateFullFileUrl('Image', 'slide', array($slideDetail['slide_id'], $appScreenType, $this->siteLangId, $resType)) . $uploadedTime, CONF_IMG_CACHE_TIME, '.jpg');
                            $urlTypeData = CommonHelper::getUrlTypeData($slideDetail['slide_url']);
                            $slideUrl = $slideDetail['slide_url'];
                            $slideDetail['slide_url'] = $slideDetail['slide_url_type'] = $slideDetail['slide_url_title'] = "";
                            if (false != $urlTypeData) {
                                $slideDetail['slide_url'] = ($urlTypeData['urlType'] == applicationConstants::URL_TYPE_EXTERNAL ? $slideUrl : $urlTypeData['recordId']);
                                $slideDetail['slide_url_type'] = $urlTypeData['urlType'];

                                switch ($urlTypeData['urlType']) {
                                    case applicationConstants::URL_TYPE_SHOP:
                                        $slideDetail['slide_url_title'] = Shop::getName($urlTypeData['recordId'], $this->siteLangId);
                                        break;
                                    case applicationConstants::URL_TYPE_PRODUCT:
                                        $slideDetail['slide_url_title'] = SellerProduct::getProductDisplayTitle($urlTypeData['recordId'], $this->siteLangId);
                                        break;
                                    case applicationConstants::URL_TYPE_CATEGORY:
                                        $slideDetail['slide_url_title'] = ProductCategory::getProductCategoryName($urlTypeData['recordId'], $this->siteLangId);
                                        break;
                                    case applicationConstants::URL_TYPE_BRAND:
                                        $slideDetail['slide_url_title'] = Brand::getBrandName($urlTypeData['recordId'], $this->siteLangId);
                                        break;
                                }
                            }
                        }
                    }
                    $collections[$ind]['slides'] = $slides;
                    break;
                case Collections::COLLECTION_TYPE_SPONSORED_PRODUCTS:
                    $collections[$ind] = $collection;
                    $sponsoredProdsInCollection[$ind] = $collection['collection_id'];
                    break;
                case Collections::COLLECTION_TYPE_SPONSORED_SHOPS:
                    $collections[$ind] = $collection;
                    $sponsoredShopsInCollection[$ind] = $collection['collection_id'];

                    break;
                case Collections::COLLECTION_TYPE_BANNER:
                    $banners = BannerLocation::getPromotionalBanners($collection_id, $langId, $collection['collection_primary_records']);
                    $collections[$ind] = $collection;

                    if (true === MOBILE_APP_API_CALL && array_key_exists('banners', $banners) && !empty($banners['banners'])) {
                        $screen = CommonHelper::getAppScreenType();
                        if (Collections::TYPE_BANNER_LAYOUT2 == $collection['collection_layout_type']) {
                            $screen = 0;
                        }

                        foreach ($banners['banners'] as &$banner) {
                            $uploadedTime = AttachedFile::setTimeParam($banner['banner_updated_on']);
                            $urlTypeData = CommonHelper::getUrlTypeData($banner['banner_url']);
                            if (false === $urlTypeData) {
                                $urlTypeData = array(
                                    'url' => $banner['banner_url'],
                                    'recordId' => 0,
                                    'urlType' => applicationConstants::URL_TYPE_EXTERNAL
                                );
                            }

                            $banner['banner_image'] = UrlHelper::getCachedUrl(UrlHelper::generateFullFileUrl('Banner', 'BannerImage', array($banner['banner_id'], $this->siteLangId, $screen, 'TOPLAYOUT')) . $uploadedTime, CONF_IMG_CACHE_TIME, '.jpg');

                            $banner['banner_url'] = ($urlTypeData['urlType'] == applicationConstants::URL_TYPE_EXTERNAL ? $banner['banner_url'] : $urlTypeData['recordId']);
                            $banner['banner_url_type'] = $urlTypeData['urlType'];
                            $banner['banner_url_title'] = "";
                            switch ($urlTypeData['urlType']) {
                                case applicationConstants::URL_TYPE_SHOP:
                                    $banner['banner_url_title'] = Shop::getName($urlTypeData['recordId'], $this->siteLangId);
                                    break;
                                case applicationConstants::URL_TYPE_PRODUCT:
                                    $banner['banner_url_title'] = SellerProduct::getProductDisplayTitle($urlTypeData['recordId'], $this->siteLangId);
                                    break;
                                case applicationConstants::URL_TYPE_CATEGORY:
                                    $banner['banner_url_title'] = ProductCategory::getProductCategoryName($urlTypeData['recordId'], $this->siteLangId);
                                    break;
                                case applicationConstants::URL_TYPE_BRAND:
                                    $banner['banner_url_title'] = Brand::getBrandName($urlTypeData['recordId'], $this->siteLangId);
                                    break;
                            }
                        }
                    }


                    $collections[$ind]['banners'] = empty($banners) && (true === MOBILE_APP_API_CALL) ? (object) [] : $banners;

                    break;
                case Collections::COLLECTION_TYPE_PRODUCT:
                    $tempObj = clone $collectionObj;
                    $tempObj->addCondition('collection_id', '=', $collection_id);
                    $tempObj->doNotCalculateRecords();
                    $tempObj->doNotLimitRecords();
                    $rs = $tempObj->getResultSet();
                    $collectionProdRecords = $db->fetchAll($rs, 'ctr_record_id');
                    $selProdIds = array_keys($collectionProdRecords);

                    $productSrchTempObj = $this->getProductSearchObj($loggedUserId, ['selProdIds' => $selProdIds]);
                    $productSrchTempObj->joinTable('(' . $tempObj->getQuery() . ')', 'INNER JOIN', 'selprod_id = ctr.ctr_record_id', 'ctr');
                    if (true === MOBILE_APP_API_CALL) {
                        // $productSrchTempObj->joinProductRating();
                        $productSrchTempObj->addFld('product_rating as prod_rating');
                    }

                    $productSrchTempObj->addCondition('selprod_deleted', '=', applicationConstants::NO);
                    $productSrchTempObj->addGroupBy('selprod_id');

                    if (false === MOBILE_APP_API_CALL) {
                        //$pageSize = $collection['collection_primary_records'] ?? 4;
                        $pageSize = Collections::getLayoutLimit($collection['collection_layout_type']);
                        $pageSize = $pageSize ?? 4;

                        $productSrchTempObj->setPageSize((0 < $pageSize ? $pageSize : 4));
                    }

                    $recordCount = $this->getRecordsCount(clone $productSrchTempObj, true);
                    if (empty($recordCount)) {
                        continue 2;
                    }
                    $productSrchTempObj->doNotCalculateRecords();
                    $productSrchTempObj->addOrder('ctr.ctr_display_order', 'ASC');
                    $rs = $productSrchTempObj->getResultSet();

                    $products = $db->fetchAll($rs, 'selprod_id');
                    $selProdIdsArr = array_keys($products);
                    $tRightRibbons = Badge::getRibbons($this->siteLangId, Badge::RIBB_POS_TRIGHT, $selProdIdsArr);

                    if (true === MOBILE_APP_API_CALL) {
                        foreach ($products as &$product) {
                            $selProdRibbons = [];
                            if (array_key_exists($product['selprod_id'], $tRightRibbons)) {
                                $selProdRibbons[] = $tRightRibbons[$product['selprod_id']];
                            }
                            $uploadedTime = AttachedFile::setTimeParam($product['product_updated_on']);
                            $product['product_image_url'] = UrlHelper::getCachedUrl(UrlHelper::generateFullFileUrl('image', 'product', array($product['product_id'], ImageDimension::VIEW_CLAYOUT3, $product['selprod_id'], 0, $this->siteLangId)) . $uploadedTime, CONF_IMG_CACHE_TIME, '.jpg');
                            $product['discount'] = ($product['selprod_price'] > $product['theprice']) ? CommonHelper::showProductDiscountedText($product, $this->siteLangId) : '';
                            $product['selprod_price'] = CommonHelper::displayMoneyFormat($product['selprod_price']);
                            $product['theprice'] = CommonHelper::displayMoneyFormat($product['theprice']);
                            $product['ribbons'] = $selProdRibbons;
                        }
                    }

                    $collections[$ind] = $collection;
                    $collections[$ind]['products'] = (true === MOBILE_APP_API_CALL) ? array_values($products) : $products;
                    $collections[$ind]['totProducts'] = $recordCount;

                    if (false === MOBILE_APP_API_CALL) {
                        $collections[$ind]['tRightRibbons'] = $tRightRibbons;
                    }
                    /* ] */
                    unset($tempObj);
                    unset($productSrchTempObj);
                    break;

                case Collections::COLLECTION_TYPE_CATEGORY:
                    if (true === MOBILE_APP_API_CALL && in_array($collection['collection_layout_type'], [Collections::TYPE_CATEGORY_LAYOUT2, Collections::TYPE_CATEGORY_LAYOUT12])) {
                        continue 2;
                    }
                    $tempObj = clone $collectionObj;
                    $tempObj->addCondition('collection_id', '=', $collection_id);
                    $tempObj->doNotCalculateRecords();
                    $tempObj->doNotLimitRecords();
                    $rs = $tempObj->getResultSet();
                    $collectionCatRecords = $db->fetchAll($rs, 'ctr_record_id');
                    $catIds = array_keys($collectionCatRecords);

                    /* fetch Categories data[ */
                    $productCatSrchTempObj = clone $productCatSrchObj;
                    $productCatSrchTempObj->joinTable('(' . $tempObj->getQuery() . ')', 'INNER JOIN', 'prodcat_id = ctr.ctr_record_id', 'ctr');

                    $productCatSrchTempObj->addCondition('prodcat_deleted', '=', applicationConstants::NO);
                    $basedOnItemCountArr = Collections::displayRecordsCount($collection['collection_layout_type']);
                    if (!empty($basedOnItemCountArr)) {
                        $pageSize =  $collection['collection_primary_records'];
                    } else {
                        $pageSize = Collections::getLayoutLimit($collection['collection_layout_type']);
                    }

                    if (false === MOBILE_APP_API_CALL) {
                        if (!in_array($collection['collection_layout_type'], [Collections::TYPE_CATEGORY_LAYOUT7, Collections::TYPE_CATEGORY_LAYOUT9, Collections::TYPE_CATEGORY_LAYOUT11, Collections::TYPE_CATEGORY_LAYOUT12])) {
                            $productCatSrchTempObj->setPageSize(($pageSize > 0) ? $pageSize : 5);
                        }
                    }

                    $productCatSrchTempObj->setPageSize(($pageSize > 0) ? $pageSize : 5);
                    $recordCount = $this->getRecordsCount(clone $productCatSrchTempObj);
                    if (empty($recordCount)) {
                        continue 2;
                    }

                    $productCatSrchTempObj->doNotCalculateRecords();
                    $productCatSrchTempObj->addOrder('ctr.ctr_display_order', 'ASC');
                    $rs = $productCatSrchTempObj->getResultSet();

                    /* ] */
                    $collections[$ind] = $collection;
                    $counter = 0;
                    if (in_array($collection['collection_layout_type'], [Collections::TYPE_CATEGORY_LAYOUT2, Collections::TYPE_CATEGORY_LAYOUT3, Collections::TYPE_CATEGORY_LAYOUT5, Collections::TYPE_CATEGORY_LAYOUT6, Collections::TYPE_CATEGORY_LAYOUT7, Collections::TYPE_CATEGORY_LAYOUT9, Collections::TYPE_CATEGORY_LAYOUT12])) {
                        while ($catData = $db->fetch($rs)) {
                            if (true === MOBILE_APP_API_CALL) {
                                $imgUpdatedOn = ProductCategory::getAttributesById($catData['prodcat_id'], 'prodcat_updated_on');
                                $uploadedTime = AttachedFile::setTimeParam($imgUpdatedOn);
                                $productName = !empty($catData['prodcat_name']) ? html_entity_decode($catData['prodcat_name'], ENT_QUOTES, 'utf-8') : '';
                                $productDescription = !empty($catData['prodcat_description']) ? html_entity_decode($catData['prodcat_description'], ENT_QUOTES, 'utf-8') : '';
                                $catData['prodcat_name'] = $productName;
                                $catData['prodcat_description'] = $productDescription;
                                $fileRow = CommonHelper::getImageAttributes(AttachedFile::FILETYPE_CATEGORY_BANNER, $catData['prodcat_id'], 0, 0, applicationConstants::SCREEN_MOBILE);

                                $catData['category_image_url'] = UrlHelper::getCachedUrl(UrlHelper::generateFullFileUrl('Category', 'thumb', array($catData['prodcat_id'], $this->siteLangId, ImageDimension::VIEW_ICON, 0), CONF_WEBROOT_FRONT_URL) . $uploadedTime, CONF_IMG_CACHE_TIME, '.jpg');

                                $collections[$ind]['categories'][$counter] = $catData;
                            } else {
                                $subCategories = [];
                                if (!in_array($collection['collection_layout_type'], [Collections::TYPE_CATEGORY_LAYOUT7, Collections::TYPE_CATEGORY_LAYOUT9])) {
                                    /* fetch Sub-Categories[ */
                                    $subCategorySrch = clone $productCatSrchObj;
                                    $subCategorySrch->doNotCalculateRecords();
                                    //$subCategorySrch->joinTable('(' . $tempObj->getQuery() . ')', 'INNER JOIN', 'prodcat_id = ctr.ctr_record_id', 'ctr');
                                    //$subCategorySrch->addCondition('prodcat_id', '=', $catData['prodcat_id']);
                                    $subCategorySrch->addCondition('prodcat_parent', '=', $catData['prodcat_id']);
                                    $subCategorySrch->addCondition('prodcat_deleted', '=', applicationConstants::NO);
                                    //$subCategorySrch->addOrder('ctr.ctr_record_id', 'ASC');
                                    $subCategorySrch->setPageSize(5);
                                    $subCatRs = $subCategorySrch->getResultSet();
                                    $subCategories = $db->fetchAll($subCatRs, 'prodcat_id');
                                }
                                $collections[$ind]['categories'][$catData['prodcat_id']] = $catData;
                                $collections[$ind]['categories'][$catData['prodcat_id']]['subCategories'] = $subCategories;

                                $product = [];
                                if ($collection['collection_layout_type'] == Collections::TYPE_CATEGORY_LAYOUT3 || $collection['collection_layout_type'] == Collections::TYPE_CATEGORY_LAYOUT4) {
                                    $allCats = [$catData['prodcat_id']] + array_keys($subCategories);
                                    $productShopSrchTempObj = $this->getProductSearchObj($loggedUserId, ['categoryIds' => $allCats]);
                                    $prodObj = $this->getProductSearchObj($loggedUserId);
                                    $prodObj->addCondition('prodcat_id', 'IN', $allCats);
                                    $prodObj->addGroupBy('selprod_product_id');
                                    $prodObj->setPageSize(1);
                                    $prodRs = $prodObj->getResultSet();
                                    $product = $db->fetch($prodRs);
                                }
                                $collections[$ind]['categories'][$catData['prodcat_id']]['product'] = $product;
                            }

                            /* ] */
                            $counter++;
                        }
                    } else if (in_array($collection['collection_layout_type'], [Collections::TYPE_CATEGORY_LAYOUT1, Collections::TYPE_CATEGORY_LAYOUT4, Collections::TYPE_CATEGORY_LAYOUT8])) {
                        while ($catData = $db->fetch($rs)) {
                            /* fetch Product data[ */
                            $productShopSrchTempObj = $this->getProductSearchObj($loggedUserId, ['categoryIds' => [$catData['prodcat_id']]]);
                            $productShopSrchTempObj->joinTable('(' . $tempObj->getQuery() . ')', 'INNER JOIN', 'prodcat_id = ctr.ctr_record_id', 'ctr');
                            $productShopSrchTempObj->addCondition('prodcat_id', '=', $catData['prodcat_id']);
                            //$productShopSrchTempObj->addOrder('in_stock', 'DESC');
                            $productShopSrchTempObj->addGroupBy('selprod_product_id');

                            $basedOnItemCountArr = Collections::displayRecordsCount($collection['collection_layout_type']);
                            if (!empty($basedOnItemCountArr)) {
                                $limit =  $collection['collection_primary_records'];
                            } else {
                                $limit = Collections::getLayoutLimit($collection['collection_layout_type']);
                            }
                            $productShopSrchTempObj->setPageSize($limit ?? 4);

                            if (CommonHelper::demoUrl(true)) {
                                $productShopSrchTempObj->addOrder('product_featured', 'DESC');
                            }

                            $recordCount = $this->getRecordsCount(clone $productShopSrchTempObj, true);
                            if (empty($recordCount)) {
                                continue;
                            }

                            $productShopSrchTempObj->doNotCalculateRecords();
                            $productShopSrchTempObj->addOrder('ctr.ctr_record_id', 'ASC');
                            $Prs = $productShopSrchTempObj->getResultSet();
                            $prodData = $db->fetchAll($Prs);

                            $selProdIdsArr = array_column($prodData, 'selprod_id');
                            $tRightRibbons = Badge::getRibbons($this->siteLangId, Badge::RIBB_POS_TRIGHT, $selProdIdsArr);

                            $counterInd = (true === MOBILE_APP_API_CALL) ? $counter : $catData['prodcat_id'];

                            if (true === MOBILE_APP_API_CALL) {
                                $imgUpdatedOn = ProductCategory::getAttributesById($catData['prodcat_id'], 'prodcat_updated_on');
                                $uploadedTime = AttachedFile::setTimeParam($imgUpdatedOn);
                                $productName = !empty($catData['prodcat_name']) ? html_entity_decode($catData['prodcat_name'], ENT_QUOTES, 'utf-8') : '';
                                $productDescription = !empty($catData['prodcat_description']) ? html_entity_decode($catData['prodcat_description'], ENT_QUOTES, 'utf-8') : '';
                                $catData['prodcat_name'] = $productName;
                                $catData['prodcat_description'] = $productDescription;
                                $fileRow = CommonHelper::getImageAttributes(AttachedFile::FILETYPE_CATEGORY_BANNER, $catData['prodcat_id'], 0, 0, applicationConstants::SCREEN_MOBILE);
                                $catData['category_image_url'] = UrlHelper::getCachedUrl(UrlHelper::generateFullFileUrl('Category', 'banner', array($catData['prodcat_id'], $this->siteLangId, 'MOBILE', $fileRow['afile_id'], applicationConstants::SCREEN_MOBILE)) . $uploadedTime, CONF_IMG_CACHE_TIME, '.jpg');

                                $collections[$ind]['categories'][$counter] = $catData;

                                foreach ($prodData as &$product) {
                                    $selProdRibbons = [];
                                    if (array_key_exists($product['selprod_id'], $tRightRibbons)) {
                                        $selProdRibbons[] = $tRightRibbons[$product['selprod_id']];
                                    }

                                    $uploadedTime = AttachedFile::setTimeParam($product['product_updated_on']);
                                    $product['product_image_url'] = UrlHelper::getCachedUrl(UrlHelper::generateFullFileUrl('image', 'product', array($product['product_id'], ImageDimension::VIEW_CLAYOUT3, $product['selprod_id'], 0, $this->siteLangId)) . $uploadedTime, CONF_IMG_CACHE_TIME, '.jpg');
                                    $product['discount'] = ($product['selprod_price'] > $product['theprice']) ? CommonHelper::showProductDiscountedText($product, $this->siteLangId) : '';
                                    $product['selprod_price'] = CommonHelper::displayMoneyFormat($product['selprod_price']);
                                    $product['theprice'] = CommonHelper::displayMoneyFormat($product['theprice']);
                                    $product['ribbons'] = $selProdRibbons;
                                }
                            } else {
                                $collections[$ind]['categories'][$counterInd]['catData'] = $catData;
                                $collections[$ind]['categories'][$counterInd]['tRightRibbons'] = $tRightRibbons;
                            }

                            $collections[$ind]['categories'][$counterInd]['products'] = $prodData;
                            /* ] */
                            $counter++;
                        }
                    } else {
                        while ($catData = $db->fetch($rs)) {
                            /* fetch Product data[ */
                            $productShopSrchTempObj = $this->getProductSearchObj($loggedUserId, ['categoryIds' => [$catData['prodcat_id']]]);
                            $productShopSrchTempObj->joinTable('(' . $tempObj->getQuery() . ')', 'INNER JOIN', 'prodcat_id = ctr.ctr_record_id', 'ctr');
                            $productShopSrchTempObj->addCondition('prodcat_id', '=', $catData['prodcat_id']);
                            //$productShopSrchTempObj->addOrder('in_stock', 'DESC');
                            $productShopSrchTempObj->addGroupBy('selprod_product_id');
                            $productShopSrchTempObj->setPageSize(7);

                            $recordCount = $this->getRecordsCount(clone $productShopSrchTempObj, true);
                            if (empty($recordCount)) {
                                continue;
                            }

                            $productShopSrchTempObj->doNotCalculateRecords();
                            $productShopSrchTempObj->addOrder('ctr.ctr_record_id', 'ASC');
                            $Prs = $productShopSrchTempObj->getResultSet();
                            $prodData = $db->fetchAll($Prs);

                            $selProdIdsArr = array_column($prodData, 'selprod_id');
                            $tRightRibbons = Badge::getRibbons($this->siteLangId, Badge::RIBB_POS_TRIGHT, $selProdIdsArr);

                            $counterInd = (true === MOBILE_APP_API_CALL) ? $counter : $catData['prodcat_id'];

                            if (true === MOBILE_APP_API_CALL) {
                                $imgUpdatedOn = ProductCategory::getAttributesById($catData['prodcat_id'], 'prodcat_updated_on');
                                $uploadedTime = AttachedFile::setTimeParam($imgUpdatedOn);
                                $catData['prodcat_name'] = html_entity_decode($catData['prodcat_name'], ENT_QUOTES, 'utf-8');
                                $catData['prodcat_description'] = strip_tags(html_entity_decode($catData['prodcat_description'], ENT_QUOTES, 'utf-8'));
                                $fileRow = CommonHelper::getImageAttributes(AttachedFile::FILETYPE_CATEGORY_BANNER, $catData['prodcat_id'], 0, 0, applicationConstants::SCREEN_MOBILE);
                                $catData['category_image_url'] = UrlHelper::getCachedUrl(UrlHelper::generateFullFileUrl('Category', 'banner', array($catData['prodcat_id'], $this->siteLangId, 'MOBILE', $fileRow['afile_id'], applicationConstants::SCREEN_MOBILE)) . $uploadedTime, CONF_IMG_CACHE_TIME, '.jpg');

                                $collections[$ind]['categories'][$counter] = $catData;

                                foreach ($prodData as &$product) {
                                    $selProdRibbons = [];
                                    if (array_key_exists($product['selprod_id'], $tRightRibbons)) {
                                        $selProdRibbons[] = $tRightRibbons[$product['selprod_id']];
                                    }

                                    $uploadedTime = AttachedFile::setTimeParam($product['product_updated_on']);
                                    $product['product_image_url'] = UrlHelper::getCachedUrl(UrlHelper::generateFullFileUrl('image', 'product', array($product['product_id'], ImageDimension::VIEW_CLAYOUT3, $product['selprod_id'], 0, $this->siteLangId)) . $uploadedTime, CONF_IMG_CACHE_TIME, '.jpg');
                                    $product['discount'] = ($product['selprod_price'] > $product['theprice']) ? CommonHelper::showProductDiscountedText($product, $this->siteLangId) : '';
                                    $product['selprod_price'] = CommonHelper::displayMoneyFormat($product['selprod_price']);
                                    $product['theprice'] = CommonHelper::displayMoneyFormat($product['theprice']);
                                    $product['ribbons'] = $selProdRibbons;
                                }
                            } else {
                                $collections[$ind]['categories'][$counterInd]['catData'] = $catData;
                                $collections[$ind]['categories'][$counterInd]['tRightRibbons'] = $tRightRibbons;
                            }

                            $collections[$ind]['categories'][$counterInd]['products'] = $prodData;
                            /* ] */
                            $counter++;
                        }
                    }
                    $collections[$ind]['totCategories'] = $recordCount;
                    unset($tempObj);
                    break;
                case Collections::COLLECTION_TYPE_SHOP:
                    $tempObj = clone $collectionObj;
                    $tempObj->addCondition('collection_id', '=', $collection_id);
                    $tempObj->doNotCalculateRecords();
                    $tempObj->doNotLimitRecords();

                    $shopObj = new ShopSearch($langId);
                    $shopObj->setDefinedCriteria($langId);
                    $shopObj->joinSellerSubscription();
                    //$shopObj->addCondition('shop_id', 'IN', array_keys($shopIds));
                    $shopObj->joinTable('(' . $tempObj->getQuery() . ')', 'INNER JOIN', 'shop_id = ctr.ctr_record_id', 'ctr');

                    if (false === MOBILE_APP_API_CALL) {
                        $pageSize = $collection['collection_primary_records'] ?? 4;
                        if ($collection['collection_layout_type'] == Collections::TYPE_SHOP_LAYOUT3) {
                            $pageSize = 3;
                        }
                        $shopObj->setPageSize((0 < $pageSize ? $pageSize : 4));
                    }

                    $recordCount = $this->getRecordsCount(clone $shopObj);
                    if (empty($recordCount)) {
                        continue 2;
                    }

                    $shopObj->doNotCalculateRecords();
                    $shopObj->addMultipleFields(array('ctr.ctr_display_order', 'shop_id', 'shop_user_id', 'IFNULL(shop_name, shop_identifier) as shop_name', 'IFNULL(country_name, country_code) as country_name', 'IFNULL(state_name, state_identifier) as state_name', 'shop_updated_on', 'shop_avg_rating', 'shop_total_reviews'));
                    $shopObj->addOrder('ctr.ctr_display_order', 'ASC');
                    $rs = $shopObj->getResultSet();

                    $collections[$ind] = $collection;
                    $collections[$ind]['totShops'] = $recordCount;
                    $counter = 0;
                    while ($shopsData = $db->fetch($rs)) {
                        $rating = 0;
                        if (FatApp::getConfig("CONF_ALLOW_REVIEWS", FatUtility::VAR_INT, 0)) {
                            $rating = SelProdRating::getSellerRating($shopsData['shop_user_id'], true);
                        }

                        if (true === MOBILE_APP_API_CALL) {
                            $shopsData['shop_id'] = $shopsData['shop_id'];
                            $shopsData['shop_logo'] = UrlHelper::getCachedUrl(UrlHelper::generateFullFileUrl('image', 'shopLogo', array($shopsData['shop_id'], $this->siteLangId)), CONF_IMG_CACHE_TIME, '.jpg');
                            $shopsData['shop_banner'] = UrlHelper::getCachedUrl(UrlHelper::generateFullFileUrl('image', 'shopBanner', array($shopsData['shop_id'], $this->siteLangId, ImageDimension::VIEW_MOBILE, 0, applicationConstants::SCREEN_MOBILE)), CONF_IMG_CACHE_TIME, '.jpg');

                            $collections[$ind]['shops'][$counter] = $shopsData;

                            $collections[$ind]['shops'][$counter]['rating'] = $rating;
                        } else {
                            if (in_array($collection['collection_layout_type'], [Collections::TYPE_SHOP_LAYOUT2, Collections::TYPE_SHOP_LAYOUT3])) {
                                $prodObj = $this->getProductSearchObj($loggedUserId, ['shop_id' => $shopsData['shop_id']]);
                                $prodObj->addCondition('shop_id', '=', $shopsData['shop_id']);
                                if (CommonHelper::demoUrl(true)) {
                                    $prodObj->addCondition('product_featured', '=', applicationConstants::YES);
                                }
                                if ($collection['collection_layout_type'] == Collections::TYPE_SHOP_LAYOUT3) {
                                    $prodObj->doNotCalculateRecords();
                                    $prodObj->setPageSize(3);
                                    $shopsData['product'] = (array) $db->fetchAll($prodObj->getResultSet());
                                } else {
                                    $prodObj->setPageSize(1);
                                    $shopsData['product'] = (array) $db->fetch($prodObj->getResultSet());
                                }
                            }
                            $collections[$ind]['shops'][$shopsData['shop_id']]['shopData'] = $shopsData;

                            $collections[$ind]['rating'][$shopsData['shop_id']] = $rating;
                        }
                        $counter++;
                    }
                    unset($tempObj);
                    break;
                case Collections::COLLECTION_TYPE_BRAND:
                    $tempObj = clone $collectionObj;
                    $tempObj->addCondition('collection_id', '=', $collection_id);
                    $tempObj->doNotCalculateRecords();
                    $tempObj->doNotLimitRecords();

                    /* fetch Brand data[ */
                    $brandSearchObj = Brand::getSearchObject($langId, true, true);
                    $brandSearchTempObj = clone $brandSearchObj;
                    $brandSearchTempObj->joinTable('(' . $tempObj->getQuery() . ')', 'INNER JOIN', 'brand_id = ctr.ctr_record_id', 'ctr');
                    $brandSearchTempObj->addMultipleFields(array('brand_id', 'IFNULL(brand_name, brand_identifier) as brand_name'));
                    //$brandSearchTempObj->addCondition('brand_id', 'IN', array_keys($brandIds));

                    if (false === MOBILE_APP_API_CALL) {
                        $pageSize = $collection['collection_primary_records'];
                        $brandSearchTempObj->setPageSize((0 < $pageSize ? $pageSize : 4));
                    }

                    $recordCount = $this->getRecordsCount(clone $brandSearchTempObj);
                    if (empty($recordCount)) {
                        continue 2;
                    }
                    $brandSearchTempObj->doNotCalculateRecords();
                    $brandSearchTempObj->addOrder('ctr_display_order', 'ASC');
                    $rs = $brandSearchTempObj->getResultSet();
                    $brands = $db->fetchAll($rs);

                    if (true === MOBILE_APP_API_CALL) {
                        foreach ($brands as &$brand) {
                            $brand['brand_image'] = UrlHelper::getCachedUrl(UrlHelper::generateFullFileUrl('image', 'brand', array($brand['brand_id'], $this->siteLangId)), CONF_IMG_CACHE_TIME, '.jpg');
                        }
                    } else {
                        if ($collection['collection_layout_type'] == Collections::TYPE_BRAND_LAYOUT2) {
                            foreach ($brands as &$brand) {
                                $prodObj = $this->getProductSearchObj($loggedUserId, ['brand_id' => $brand['brand_id']]);
                                $prodObj->addCondition('brand_id', '=', $brand['brand_id']);
                                $prodObj->addGroupBy('brand_id');
                                $prodObj->setPageSize(1);
                                $brand['product'] = (array) $db->fetch($prodObj->getResultSet());
                            }
                        }
                    }

                    /* ] */
                    $collections[$ind] = $collection;
                    $collections[$ind]['totBrands'] = $recordCount;
                    $collections[$ind]['brands'] = $brands;
                    unset($brandSearchTempObj);
                    unset($tempObj);
                    break;
                case Collections::COLLECTION_TYPE_BLOG:
                    $tempObj = clone $collectionObj;
                    $tempObj->addCondition('collection_id', '=', $collection_id);
                    $tempObj->doNotCalculateRecords();
                    $tempObj->doNotLimitRecords();

                    /* fetch Blog data[ */
                    $attr = [
                        'post_id',
                        'post_author_name',
                        'IFNULL(post_title, post_identifier) as post_title',
                        'post_updated_on',
                        'post_updated_on',
                        'group_concat(IFNULL(bpcategory_name, bpcategory_identifier) SEPARATOR "~") categoryNames',
                        'post_description'
                    ];
                    $blogSearchObj = BlogPost::getSearchObject($langId, true, true);
                    $blogSearchTempObj = clone $blogSearchObj;
                    $blogSearchTempObj->addMultipleFields($attr);
                    $blogSearchTempObj->joinTable('(' . $tempObj->getQuery() . ')', 'INNER JOIN', 'post_id = ctr.ctr_record_id', 'ctr');
                    //$blogSearchTempObj->addCondition('post_id', 'IN', array_keys($blogPostIds));
                    if (false === MOBILE_APP_API_CALL) {
                        $blogSearchTempObj->setPageSize($collection['collection_primary_records']);
                    }
                    $blogSearchTempObj->addGroupBy('post_id');
                    $recordCount = $this->getRecordsCount(clone $blogSearchTempObj, true);
                    if (empty($recordCount)) {
                        continue 2;
                    }

                    $blogSearchTempObj->doNotCalculateRecords();
                    $blogSearchTempObj->addOrder('ctr.ctr_display_order', 'ASC');
                    $rs = $blogSearchTempObj->getResultSet();
                    $blogPostsDetail = $db->fetchAll($rs);
                    /* ] */
                    if (true === MOBILE_APP_API_CALL) {
                        array_walk($blogPostsDetail, function (&$value, $key) {
                            $value['post_image'] = UrlHelper::generateFullUrl('Image', 'blogPostFront', array($value['post_id'], $this->siteLangId, ImageDimension::VIEW_LAYOUT2));
                        });
                    }
                    $collections[$ind] = $collection;
                    $collections[$ind]['totBlogs'] = $recordCount;
                    $collections[$ind]['blogs'] = $blogPostsDetail;

                    unset($blogSearchTempObj);
                    unset($tempObj);
                    break;
                case Collections::COLLECTION_TYPE_FAQ:
                    $tempObj = clone $collectionObj;
                    $tempObj->addCondition('collection_id', '=', $collection_id);
                    $tempObj->doNotCalculateRecords();
                    $tempObj->doNotLimitRecords();

                    /* fetch FAQ data[ */
                    $attr = [
                        'faq_id',
                        'faqcat_id',
                        'IFNULL(faq_title, faq_identifier) as faq_title',
                        'faq_content',
                        'IFNULL(faqcat_name, faqcat_identifier) as faqcat_name'
                    ];
                    $faqSearchObj = Faq::getSearchObject($langId);
                    $faqSearchTempObj = clone $faqSearchObj;
                    $faqSearchTempObj->joinTable(
                        FaqCategory::DB_TBL,
                        'INNER JOIN',
                        'faq_faqcat_id = faqcat_id',
                        'fc'
                    );
                    $faqSearchTempObj->joinTable(FaqCategory::DB_TBL_LANG, 'LEFT OUTER JOIN', 'fc_l.' . FaqCategory::DB_TBL_LANG_PREFIX . 'faqcat_id = fc.' . FaqCategory::tblFld('id') . ' and fc_l.' . FaqCategory::DB_TBL_LANG_PREFIX . 'lang_id = ' . $langId, 'fc_l');
                    $faqSearchTempObj->joinTable('(' . $tempObj->getQuery() . ')', 'INNER JOIN', 'faq_id = ctr.ctr_record_id', 'ctr');
                    $faqSearchTempObj->addMultipleFields($attr);
                    $faqSearchTempObj->addCondition('fc.faqcat_deleted', '=', applicationConstants::NO);
                    $faqSearchTempObj->addCondition('fc.faqcat_active', '=', applicationConstants::ACTIVE);
                    //$faqSearchTempObj->addCondition('faq_id', 'IN', array_keys($faqIds));
                    $faqSearchTempObj->addGroupBy('faq_id');

                    $recordCount = $this->getRecordsCount(clone $faqSearchTempObj, true);
                    if (empty($recordCount)) {
                        continue 2;
                    }

                    $faqSearchTempObj->doNotCalculateRecords();

                    $faqSearchTempObj->addOrder('ctr.ctr_display_order', 'ASC');
                    $res = $faqSearchTempObj->getResultSet();
                    $faqsDetail = $db->fetchAll($res);
                    /* ] */
                    $collections[$ind] = $collection;
                    $collections[$ind]['totFaqs'] = $recordCount;
                    $collections[$ind]['faqs'] = $faqsDetail;

                    unset($faqSearchTempObj);
                    unset($tempObj);
                    break;
                case Collections::COLLECTION_TYPE_FAQ_CATEGORY:
                    $tempObj = clone $collectionObj;
                    $tempObj->addCondition('collection_id', '=', $collection_id);
                    $tempObj->doNotCalculateRecords();
                    $tempObj->doNotLimitRecords();

                    /* fetch FAQ data[ */
                    $attr = [
                        'faqcat_id',
                        'IFNULL(faqcat_name, faqcat_identifier) as faqcat_name'
                    ];
                    $faqCategorySearchObj = FaqCategory::getSearchObject($langId);
                    $faqCategorySearchObj->joinTable('tbl_faqs', 'LEFT OUTER JOIN', 'faq_faqcat_id = faqcat_id and faq_active = ' . applicationConstants::ACTIVE . '  and faq_deleted = ' . applicationConstants::NO);
                    $faqCategorySearchObj->joinTable('(' . $tempObj->getQuery() . ')', 'INNER JOIN', 'faqcat_id = ctr.ctr_record_id', 'ctr');
                    $faqCategorySearchObj->addMultipleFields($attr);
                    $faqCategorySearchObj->addOrder('ctr.ctr_display_order', 'ASC');
                    $faqCategorySearchObj->addGroupBy('faqcat_id');
                    $faqCategorySearchObj->addFld('COUNT(1) AS faq_count');
                    $res = $faqCategorySearchObj->getResultSet();
                    $faqCats = $db->fetchAll($res);
                    if (empty($faqCats)) {
                        continue 2;
                    }
                    $faqMainCat = FatApp::getConfig("CONF_FAQ_PAGE_MAIN_CATEGORY", null, '');
                    if (count($faqCats)) {
                        $faqMainCat = empty($faqMainCat) ? current($faqCats)['faqcat_id'] : $faqMainCat;
                    }
                    $collections[$ind] = $collection;
                    $collections[$ind]['faqMainCat'] = $faqMainCat;
                    $collections[$ind]['listCategories'] = $faqCats;

                    unset($faqSearchTempObj);
                    unset($tempObj);
                    break;

                case Collections::COLLECTION_TYPE_TESTIMONIAL:
                    $tempObj = clone $collectionObj;
                    $tempObj->addCondition('collection_id', '=', $collection_id);
                    $tempObj->doNotCalculateRecords();
                    $tempObj->doNotLimitRecords();

                    /* fetch Testimonial data[ */
                    $attr = [
                        'testimonial_id',
                        'testimonial_user_name',
                        'IFNULL(testimonial_title, testimonial_identifier) as testimonial_title',
                        'testimonial_text',
                        'testimonial_added_on'
                    ];
                    $testimonialSrchObj = Testimonial::getSearchObject($langId, true);
                    $testimonialSrchObj = clone $testimonialSrchObj;
                    $testimonialSrchObj->joinTable('(' . $tempObj->getQuery() . ')', 'INNER JOIN', 'testimonial_id = ctr.ctr_record_id', 'ctr');
                    $testimonialSrchObj->addMultipleFields($attr);
                    $testimonialSrchObj->addGroupBy('testimonial_id');
                    if ($collection['collection_layout_type'] == Collections::LIMIT_TESTIMONIAL_LAYOUT1) {
                        $testimonialSrchObj->setPageSize(Collections::LIMIT_TESTIMONIAL_LAYOUT1);
                    } else {
                        $pageSize = $collection['collection_primary_records'] ?? 4;
                        $testimonialSrchObj->setPageSize((0 < $pageSize ? $pageSize : 4));
                    }

                    $recordCount = $this->getRecordsCount(clone $testimonialSrchObj, true);
                    if (empty($recordCount)) {
                        continue 2;
                    }

                    $testimonialSrchObj->doNotCalculateRecords();
                    $testimonialSrchObj->addOrder('ctr.ctr_display_order', 'ASC');
                    $res = $testimonialSrchObj->getResultSet();
                    $testimonialsDetail = $db->fetchAll($res);
                    /* ] */


                    if (true === MOBILE_APP_API_CALL) {
                        foreach ($testimonialsDetail as &$testimonial) {
                            $testimonial['user_image'] = UrlHelper::getCachedUrl(UrlHelper::generateFullFileUrl('Image', 'testimonial', array($testimonial['testimonial_id'], $this->siteLangId, ImageDimension::VIEW_MEDIUM)), CONF_IMG_CACHE_TIME, '.jpg');
                        }
                    }

                    $collections[$ind] = $collection;
                    $collections[$ind]['totTestimonials'] = $recordCount;
                    $collections[$ind]['testimonials'] = $testimonialsDetail;

                    unset($testimonialSrchObj);
                    unset($tempObj);
                    break;

                case Collections::COLLECTION_TYPE_REVIEWS:
                    $collections[$ind] = $collection;
                    $collections[$ind]['pendingForReviews'] = array();
                    $loggedUserId = UserAuthentication::getLoggedUserId(true);
                    if (0 < $loggedUserId && (FatApp::getConfig('CONF_ALLOW_REVIEWS', FatUtility::VAR_INT, 0))) {
                        $pendingForReviews = OrderProduct::pendingForReviews($loggedUserId, $this->siteLangId);
                        if (is_array($pendingForReviews) && 0 < count($pendingForReviews)) {
                            foreach ($pendingForReviews as $key => &$orderProduct) {
                                $canSubmitFeedback = Orders::canSubmitFeedback($orderProduct['order_user_id'], $orderProduct['order_id'], $orderProduct['op_selprod_id']);
                                if (false === $canSubmitFeedback) {
                                    continue;
                                }
                                $orderProduct['product_image_url'] = UrlHelper::generateFullUrl('image', 'product', array($orderProduct['selprod_product_id'], ImageDimension::VIEW_THUMB, $orderProduct['op_selprod_id'], 0, $this->siteLangId));
                            }
                            $collections[$ind]['pendingForReviews'] = $pendingForReviews;
                        }
                    }
                    break;
                case Collections::COLLECTION_TYPE_CONTENT_BLOCK:
                    $collections[$ind] = $collection;
                    break;
            }
            $i++;
        }

        if ($collectionCache) {
            return unserialize($collectionCache);
        }

        CacheHelper::create('collectionCache_' . $cacheKey, serialize($collections), CacheHelper::TYPE_COLLECTIONS);
        return $collections;
    }

    private function getSlides($recordLimit = 0)
    {
        $langId = $this->siteLangId;
        $slidesCache = CacheHelper::get('slidesCache' . $langId, 7200, '.txt');
        if ($slidesCache) {
            $data = unserialize($slidesCache);
            if ($recordLimit == 1) {
                $data = array_slice($data, 0, 1);
            }
            return $data;
        }

        $db = FatApp::getDb();
        $srchSlide = new SlideSearch($langId);
        $srchSlide->doNotCalculateRecords();
        $srchSlide->joinPromotions($langId, true, true, true);
        $srchSlide->addPromotionTypeCondition();
        $srchSlide->joinUserWallet();
        $srchSlide->joinActiveUser();
        $srchSlide->addSkipExpiredPromotionAndSlideCondition();
        $srchSlide->joinBudget();
        $srchSlide->joinAttachedFile();
        $srchSlide->addMultipleFields(array('slide_id', 'slide_record_id', 'slide_type', 'IFNULL(promotion_name, promotion_identifier) as promotion_name,IFNULL(slide_title, slide_identifier) as slide_title', 'slide_target', 'slide_url', 'promotion_id', 'daily_cost', 'weekly_cost', 'monthly_cost', 'total_cost', 'slide_img_updated_on'));

        // if ($recordLimit == 1) {
        //     $totalSlidesPageSize = 1;
        //     $ppcSlidesPageSize = 1;
        // } else {
        $totalSlidesPageSize = FatApp::getConfig('CONF_TOTAL_SLIDES_HOME_PAGE', FatUtility::VAR_INT, 4);
        $ppcSlidesPageSize = FatApp::getConfig('CONF_PPC_SLIDES_HOME_PAGE', FatUtility::VAR_INT, 4);
        // }

        $ppcSlides = array();
        $adminSlides = array();

        $slidesSrch = new SearchBase('(' . $srchSlide->getQuery() . ') as t');
        $slidesSrch->addMultipleFields(array('slide_id', 'slide_type', 'slide_record_id', 'slide_url', 'slide_target', 'slide_title', 'promotion_id', 'userBalance', 'daily_cost', 'weekly_cost', 'monthly_cost', 'total_cost', 'promotion_budget', 'promotion_duration', 'slide_img_updated_on'));
        $slidesSrch->addOrder('', 'rand()');
        $slidesSrch->doNotCalculateRecords();

        if (0 < $ppcSlidesPageSize) {
            $ppcSrch = clone $slidesSrch;
            $ppcSrch->addDirectCondition(
                '((CASE
					WHEN promotion_duration=' . Promotion::DAILY . ' THEN promotion_budget > COALESCE(daily_cost,0)
					WHEN promotion_duration=' . Promotion::WEEKLY . ' THEN promotion_budget > COALESCE(weekly_cost,0)
					WHEN promotion_duration=' . Promotion::MONTHLY . ' THEN promotion_budget > COALESCE(monthly_cost,0)
					WHEN promotion_duration=' . Promotion::DURATION_NOT_AVAILABALE . ' THEN promotion_budget = -1
				  END ) )'
            );

            $ppcSrch->addCondition('slide_type', '=', Slides::TYPE_PPC);
            $ppcSrch->setPageSize($ppcSlidesPageSize);
            $ppcRs = $ppcSrch->getResultSet();
            $ppcSlides = $db->fetchAll($ppcRs, 'slide_id');
        }

        $ppcSlidesCount = count($ppcSlides);
        if ($totalSlidesPageSize > $ppcSlidesCount) {
            $totalSlidesPageSize = $totalSlidesPageSize - $ppcSlidesCount;
            $adminSlideSrch = clone $slidesSrch;
            $adminSlideSrch->addCondition('slide_type', '=', Slides::TYPE_SLIDE);
            $adminSlideSrch->setPageSize($totalSlidesPageSize);
            $slideRs = $adminSlideSrch->getResultSet();
            $adminSlides = $db->fetchAll($slideRs, 'slide_id');
        }

        $data = array_merge($ppcSlides, $adminSlides);
        $data = CommonHelper::sortArray($data, 'slide_id');
        CacheHelper::create('slidesCache' . $langId, serialize($data), CacheHelper::TYPE_COLLECTIONS);
        if ($recordLimit == 1) {
            $data = array_slice($data, 0, 1);
        }
        return $data;
    }

    private function getSponsoredShops()
    {
        $langId = $this->siteLangId;
        $shopPageSize = FatApp::getConfig('CONF_PPC_SHOPS_HOME_PAGE', FatUtility::VAR_INT, 2);
        if (1 > $shopPageSize) {
            return array();
        }

        $sponsoredShops = array();
        $db = FatApp::getDb();

        $shopObj = new PromotionSearch($langId);
        $shopObj->setDefinedCriteria();
        $shopObj->joinActiveUser();
        $shopObj->joinShops($langId, true, true);
        $shopObj->joinShopCountry();
        $shopObj->joinShopState();
        $shopObj->addPromotionTypeCondition(Promotion::TYPE_SHOP);
        $shopObj->addShopActiveExpiredCondition();
        $shopObj->joinUserWallet();
        $shopObj->joinBudget();
        $shopObj->addBudgetCondition();
        $shopObj->addOrder('', 'rand()');
        $shopObj->setPageSize($shopPageSize);
        $shopObj->doNotCalculateRecords();
        $shopObj->addMultipleFields(array('shop_id', 'shop_user_id', 'IFNULL(shop_name, shop_identifier) as shop_name', 'IFNULL(country_name, country_code) as country_name', 'IFNULL(state_name, state_identifier) as state_name', 'shop_updated_on', 'promotion_id', 'promotion_record_id'));

        $rs = $shopObj->getResultSet();
        $i = 0;
        while ($shops = $db->fetch($rs)) {
            /* fetch Shop data[ */
            $rating = 0;
            if (FatApp::getConfig("CONF_ALLOW_REVIEWS", FatUtility::VAR_INT, 0)) {
                $rating = SelProdRating::getSellerRating($shops['shop_user_id'], true);
            }

            if (true === MOBILE_APP_API_CALL) {
                $sponsoredShops[$i] = $shops;
                $sponsoredShops[$i]['rating'] = $rating;
                $sponsoredShops[$i]['shop_logo'] = UrlHelper::generateFullUrl('image', 'shopLogo', array($shops['shop_id'], $langId));
                $sponsoredShops[$i]['shop_banner'] = UrlHelper::generateFullUrl('image', 'shopBanner', array($shops['shop_id'], $langId, ImageDimension::VIEW_MOBILE));
            } else {
                $sponsoredShops['shops'][$shops['shop_id']]['shopData'] = $shops;
                $sponsoredShops['shops'][$shops['shop_id']]['shopData']['promotion_id'] = $shops['promotion_id'];

                $sponsoredShops['rating'][$shops['shop_id']] = $rating;
            }
            /* ] */
            $i++;
        }
        return $sponsoredShops;
    }

    private function getSponsoredProductsObj($loggedUserId)
    {
        $langId = $this->siteLangId;
        $prodObj = new PromotionSearch($langId);
        $prodObj->joinProducts();
        $prodObj->joinShops();
        $prodObj->addPromotionTypeCondition(Promotion::TYPE_PRODUCT);
        $prodObj->joinActiveUser();
        $prodObj->setDefinedCriteria();
        $prodObj->addShopActiveExpiredCondition();
        $prodObj->joinUserWallet();
        $prodObj->joinBudget();
        $prodObj->addBudgetCondition();
        $prodObj->doNotCalculateRecords();
        $prodObj->addMultipleFields(array('selprod_id as proSelProdId', 'promotion_id', 'promotion_record_id'));

        $productSrchSponObj = $this->getProductSearchObj($loggedUserId);
        $productSrchSponObj->joinTable('(' . $prodObj->getQuery() . ') ', 'INNER JOIN', 'selprod_id = ppr.proSelProdId ', 'ppr');
        $productSrchSponObj->addFld(array('promotion_id', 'promotion_record_id'));
        $productSrchSponObj->joinSellers();
        $productSrchSponObj->joinSellerSubscription($langId);
        $productSrchSponObj->addGroupBy('selprod_id');
        $productSrchSponObj->addOrder('', 'rand()');
        return $productSrchSponObj;
    }

    // For Home Page
    private function getSponsoredProducts($productSrchObj)
    {
        $productPageSize = (true === MOBILE_APP_API_CALL) ? 4 : FatApp::getConfig('CONF_PPC_PRODUCTS_HOME_PAGE', FatUtility::VAR_INT, 6);

        if (1 > $productPageSize) {
            return array();
        }

        $db = FatApp::getDb();
        $productSrchSponObj = $this->getSponsoredProductsObj($productSrchObj);
        if (true === MOBILE_APP_API_CALL) {
            // $productSrchSponObj->joinProductRating();
            $productSrchSponObj->addFld('product_rating as prod_rating');
        }
        $productSrchSponObj->doNotCalculateRecords();
        $productSrchSponObj->setPageSize($productPageSize);
        $rs = $productSrchSponObj->getResultSet();
        return $db->fetchAll($rs);
    }

    // Used for APP
    public function getAllSponsoredProducts()
    {
        $loggedUserId = UserAuthentication::getLoggedUserId(true);

        $page = FatApp::getPostedData('page', FatUtility::VAR_INT, 1);
        $page = ($page < 2) ? 1 : $page;

        $pagesize = FatApp::getConfig('conf_page_size', FatUtility::VAR_INT, 10);
        $productSrchSponObj = $this->getSponsoredProductsObj($loggedUserId);
        // $productSrchSponObj->joinProductRating();
        $productSrchSponObj->addFld('product_rating as prod_rating');
        $productSrchSponObj->setPageNumber($page);
        $productSrchSponObj->setPageSize($pagesize);
        $sponsoredProds = FatApp::getDb()->fetchAll($productSrchSponObj->getResultSet());

        $selProdIdsArr = array_column($sponsoredProds, 'selprod_id');
        $tRightRibbons = Badge::getRibbons($this->siteLangId, Badge::RIBB_POS_TRIGHT, $selProdIdsArr);

        $this->set('sponsoredProds', $sponsoredProds);
        $this->set('tRightRibbons', $tRightRibbons);
        $this->set('page', $page);
        $this->set('pageCount', $productSrchSponObj->pages());
        $this->set('recordCount', $productSrchSponObj->recordCount());
        $this->set('postedData', FatApp::getPostedData());
        $this->_template->render();
    }

    public function getImage()
    {
        $post = FatApp::getPostedData();
        if (1 > count($post)) {
            $message = Labels::getLabel('ERR_INVALID_REQUEST', $this->siteLangId);
            FatUtility::dieJsonError($message);
        }
        $type = FatApp::getPostedData('type', null, '');
        if (empty($type)) {
            $message = Labels::getLabel('ERR_TYPE_IS_MANDATORY', $this->siteLangId);
            FatUtility::dieJsonError($message);
        }
        $image_url = "";
        switch (strtoupper($type)) {
            case 'PRODUCT_PRIMARY':
                $product_id = FatApp::getPostedData('product_id', null, 0);
                $seller_product_id = FatApp::getPostedData('seller_product_id', null, 0);
                if (1 > $product_id || 1 > $seller_product_id) {
                    $message = Labels::getLabel('ERR_PRODUCT_ID_&_SELLER_PRODUCT_ID_IS_MANDATORY.', $this->siteLangId);
                    FatUtility::dieJsonError($message);
                }
                $image_url = UrlHelper::generateFullUrl('image', 'product', array($product_id, ImageDimension::VIEW_MEDIUM, $seller_product_id, 0, $this->siteLangId));
                break;
            case 'SLIDE':
                $slide_id = FatApp::getPostedData('slide_id', null, 0);
                if (1 > $slide_id) {
                    $message = Labels::getLabel('ERR_SLIDE_ID_IS_MANDATORY.', $this->siteLangId);
                    FatUtility::dieJsonError($message);
                }
                $image_url = UrlHelper::generateFullUrl('Image', 'slide', array($slide_id, 0, $this->siteLangId));
                break;
            case 'BANNER':
                $banner_id = FatApp::getPostedData('banner_id', null, 0);
                if (1 > $banner_id) {
                    $message = Labels::getLabel('ERR_BANNER_ID_IS_MANDATORY.', $this->siteLangId);
                    FatUtility::dieJsonError($message);
                }
                $image_url = UrlHelper::generateFullUrl('Banner', 'HomePageAfterFirstLayout', array($banner_id, $this->siteLangId));
                break;
        }
        $this->set('image_url', $image_url);
        $this->_template->render();
    }

    /**
     * countries : Used for APPs
     *
     * @return void
     */
    public function countries()
    {
        $countryObj = new Countries();
        $countriesArr = $countryObj->getCountriesArr($this->siteLangId);

        $arrCountry = array();
        foreach ($countriesArr as $country) {
            $arrCountry[] = [
                "id" => $country['country_id'],
                'name' => $country['country_name'],
                'country_code' => $country['country_code'],
            ];
        }
        $this->set('data', ['countries' => $arrCountry]);
        $this->_template->render();
    }

    public function states($countryId)
    {
        $countryId = FatUtility::int($countryId);
        if (1 > $countryId) {
            $message = Labels::getLabel('ERR_INVALID_REQUEST', $this->siteLangId);
            FatUtility::dieJsonError($message);
        }
        $statesArr = $this->getStates($countryId, 0, true);
        $states = array();
        foreach ($statesArr as $key => $val) {
            $states[] = array("id" => $key, 'name' => $val);
        }
        $this->set('states', $states);
        $this->_template->render();
    }

    public function splashScreenData()
    {
        $data = [
            'CONF_ENABLE_GEO_LOCATION' => FatApp::getConfig('CONF_ENABLE_GEO_LOCATION', FatUtility::VAR_INT, 0),
            'CONF_DEFAULT_CURRENCY_SEPARATOR' => FatApp::getConfig('CONF_DEFAULT_CURRENCY_SEPARATOR', FatUtility::VAR_STRING, '.'),
            'CONF_SINGLE_SELLER_CART' => FatApp::getConfig('CONF_SINGLE_SELLER_CART', FatUtility::VAR_INT, 0)
        ];

        $data['languageLabels'] = [
            'language_code' => CommonHelper::getLangCode(),
            'language_layout_direction' => CommonHelper::getLayoutDirection(),
            'downloadUrl' => UrlHelper::generateFullUrl('Home', 'languageLabels', array(1, $this->siteLangId)),
            'langLabelUpdatedAt' => FatApp::getConfig('CONF_LANG_LABELS_UPDATED_AT', FatUtility::VAR_INT, time())
        ];

        $data['appThemeSetting'] = [
            'primaryThemeColor' => FatApp::getConfig('CONF_THEME_COLOR', FatUtility::VAR_STRING, "#FF3A59"),
            'primaryInverseThemeColor' => FatApp::getConfig('CONF_THEME_COLOR_INVERSE', FatUtility::VAR_STRING, ''),
            'secondaryThemeColor' => FatApp::getConfig('CONF_SECONDARY_THEME_COLOR', FatUtility::VAR_STRING, ''),
            'secondaryInverseThemeColor' => FatApp::getConfig('CONF_SECONDARY_THEME_COLOR_INVERSE', FatUtility::VAR_STRING, ''),
        ];

        $data['isWishlistEnable'] = FatApp::getConfig('CONF_ADD_FAVORITES_TO_WISHLIST', FatUtility::VAR_INT, 1);
        $data['canSendSms'] = SmsArchive::canSendSms() ? 1 : 0;
        $data['canAddReview'] = FatApp::getConfig('CONF_ALLOW_REVIEWS', FatUtility::VAR_INT, 1);
        $data['currency_id'] = CommonHelper::getCurrencyId();
        $defultCountryId = FatApp::getConfig('CONF_COUNTRY', FatUtility::VAR_INT, 0);
        $data['defaultCountry'] = [
            'country_id' => $defultCountryId,
            'country_code' => Countries::getAttributesById($defultCountryId, 'country_code') ?? '',
        ];
        $data['siteLangId'] = $this->siteLangId;
        $data['newsletterEnabled'] = FatApp::getConfig('CONF_ENABLE_NEWSLETTER_SUBSCRIPTION', FatUtility::VAR_INT, 1);

        $data['app_session_id'] = isset($_SERVER['HTTP_X_APP_SESSION_ID']) && !empty($_SERVER['HTTP_X_APP_SESSION_ID']) ? $_SERVER['HTTP_X_APP_SESSION_ID'] : session_id();
        $data['CONF_RFQ_MODULE_TYPE'] = FatApp::getConfig('CONF_RFQ_MODULE_TYPE', FatUtility::VAR_INT, 0);
        $data['CONF_GLOBAL_RFQ_MODULE'] = FatApp::getConfig('CONF_GLOBAL_RFQ_MODULE', FatUtility::VAR_INT, 0);
        $data['CONF_RFQ_MODULE'] = FatApp::getConfig('CONF_RFQ_MODULE', FatUtility::VAR_INT, 0);
        $data['CONF_HIDE_PRICES'] = FatApp::getConfig('CONF_HIDE_PRICES', FatUtility::VAR_INT, 0);

        $this->set('data', $data);
        $this->_template->render();
    }

    public function getUrlSegmentsDetail()
    {
        $url = FatApp::getPostedData('url', FatUtility::VAR_STRING, '');
        if (empty($url)) {
            LibHelper::dieJsonError(Labels::getLabel('ERR_INVALID_REQUEST', $this->siteLangId));
        }
        $detail = CommonHelper::getUrlTypeData($url);
        $this->set('data', ['urlSegmentsDetail' => (object) $detail]);
        $this->_template->render();
    }

    public function getGeoAddress()
    {
        $address = new Address();
        $lat = FatApp::getPostedData('lat', FatUtility::VAR_STRING, '');
        $lng = FatApp::getPostedData('lng', FatUtility::VAR_STRING, '');

        $response = $address->getGeoData($lat, $lng);
        if (false === $response['status']) {
            FatUtility::dieJsonError($response['msg']);
        }
        FatUtility::dieJsonSuccess($response);
    }

    public function pwaManifest()
    {
        $manifestFile = CONF_UPLOADS_PATH . '/manifest-' . $this->siteLangId . '.json';
        if (!file_exists($manifestFile)) {
            $iconsArr = [36, 48, 57, 60, 70, 72, 76, 96, 114, 120, 144, 192, 152, 180, 150, 310, 512];
            $websiteName = FatApp::getConfig('CONF_WEBSITE_NAME_' . $this->siteLangId, FatUtility::VAR_STRING, '');

            $srch = new MetaTagSearch($this->siteLangId);
            $srch->addCondition('meta_controller', '=', 'Home');
            $srch->addCondition('meta_action', '=', 'index');
            $srch->doNotCalculateRecords();
            $srch->setPageSize(1);
            $srch->addMultipleFields(array(
                'meta_title',
                'meta_keywords',
                'meta_description',
                'meta_other_meta_tags'
            ));

            $rs = $srch->getResultSet();
            $metas = FatApp::getDb()->fetch($rs);

            if (empty($metas)) {
                $srch = new MetaTagSearch($this->siteLangId);
                $srch->addCondition('meta_default', '=', 1);
                $srch->setPageSize(1);
                $srch->doNotCalculateRecords();
                $srch->addMultipleFields(array(
                    'meta_title',
                    'meta_keywords',
                    'meta_description',
                    'meta_other_meta_tags'
                ));
                $rs = $srch->getResultSet();
                $metas = FatApp::getDb()->fetch($rs);
            }

            $arr = array(
                "display" => "standalone",
                "name" => $websiteName,
                "short_name" => $websiteName,
                "description" => isset($metas['meta_description']) ? $metas['meta_description'] : $websiteName,
                "lang" => CommonHelper::getLangCode(),
                "start_url" => CONF_WEBROOT_URL,
                "display" => "standalone",
                "background_color" => FatApp::getConfig('CONF_THEME_COLOR', FatUtility::VAR_STRING, "#FF3A59"),
                "theme_color" => FatApp::getConfig('CONF_THEME_COLOR', FatUtility::VAR_STRING, "#FF3A59"),
            );

            foreach ($iconsArr as $key => $val) {
                $iconUrl = UrlHelper::getCachedUrl(UrlHelper::generateFullFileUrl('Image', 'appleTouchIcon', array($this->siteLangId, $val . '-' . $val)) . UrlHelper::getCacheTimestamp($this->siteLangId), CONF_IMG_CACHE_TIME, '.png');
                $icons = [
                    'src' => $iconUrl,
                    'sizes' => $val . 'x' . $val,
                    'type' => 'image/png',
                    'purpose' => 'any maskable'
                ];
                $arr['icons'][] = $icons;
            }
            file_put_contents($manifestFile, FatUtility::convertToJson($arr, JSON_UNESCAPED_UNICODE));
        }
        echo file_get_contents($manifestFile);
        exit;
    }

    public function languageArea()
    {
        $languages = Language::getAllNames(false);
        $currencies = Currency::getCurrencyAssoc($this->siteLangId);

        $this->set('languages', $languages);
        $this->set('currencies', $currencies);
        $this->_template->render(false, false);
    }

    public function setGeoLocation()
    {
        $this->_template->render(false, false);
    }

    public function dummy()
    {
        $this->_template->addJs(['js/slick.min.js', 'js/slick-carousels.js']);
        $this->_template->render();
    }
    public function dummy2()
    {
        $this->_template->render();
    }
}
