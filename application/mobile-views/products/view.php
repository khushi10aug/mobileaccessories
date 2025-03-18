<?php defined('SYSTEM_INIT') or die('Invalid Usage.');
$data['pageCount'] = 2;
if (1 == $page) {
    $data['cartHasProducts'] = $cartHasProducts;
    $data['cartSellerId'] = $cartSellerId;
    $conditionArr = Product::getConditionArr($siteLangId);
    $product['selprod_condition_title'] = $conditionArr[$product['selprod_condition']] ?? '';
    $product['ribbons'] = $selProdRibbons;

    /* Shop and SelProd Badge */
    $selProdBadge = Badge::getSelprodBadges($siteLangId, [$product['selprod_id']]);
    $shopBadge = Badge::getShopBadges($siteLangId, [$product['shop_id']]);
    $badgesArr = array_merge($selProdBadge, $shopBadge);
    $badges = [];
    foreach ($badgesArr as $bdgRow) {
        $icon = AttachedFile::getAttachment(AttachedFile::FILETYPE_BADGE, $bdgRow[BadgeLinkCondition::DB_TBL_PREFIX . 'badge_id'], 0, $siteLangId);
        $uploadedTime = AttachedFile::setTimeParam($icon['afile_updated_at']);
        $url = UrlHelper::getCachedUrl(UrlHelper::generateFullFileUrl('Image', 'badgeIcon', array($icon['afile_record_id'], $siteLangId, ImageDimension::VIEW_MINI, $icon['afile_screen']), CONF_WEBROOT_FRONT_URL) . $uploadedTime, CONF_IMG_CACHE_TIME, '.jpg');
        $badges[] = [
            'url' => $url,
            Badge::DB_TBL_PREFIX . 'name' => $bdgRow[Badge::DB_TBL_PREFIX . 'name'],
        ];
    }

    $product['badges'] = $badges;
    /* Shop and SelProd Badge */

    $btTRightRibbons = $upsellProductsRibbons['tRightRibbons'];
    foreach (array_filter($upsellProducts) as $index => $btProduct) {
        $selProdRibbons = [];

        if (array_key_exists($btProduct['selprod_id'], $btTRightRibbons)) {
            $selProdRibbons[] = $btTRightRibbons[$btProduct['selprod_id']];
        }

        $uploadedTime = AttachedFile::setTimeParam($btProduct['product_updated_on']);
        $upsellProducts[$index]['product_image_url'] = UrlHelper::getCachedUrl(UrlHelper::generateFullFileUrl('image', 'product', array($btProduct['product_id'], ImageDimension::VIEW_MEDIUM, $btProduct['selprod_id'], 0, $siteLangId)) . $uploadedTime, CONF_IMG_CACHE_TIME, '.jpg');
        $upsellProducts[$index]['discount'] = ($btProduct['selprod_price'] > $btProduct['theprice']) ? CommonHelper::showProductDiscountedText($btProduct, $siteLangId) : '';
        $upsellProducts[$index]['selprod_price'] = CommonHelper::displayMoneyFormat($btProduct['selprod_price']);
        $upsellProducts[$index]['theprice'] = CommonHelper::displayMoneyFormat($btProduct['theprice']);
        $upsellProducts[$index]['ribbons'] = $selProdRibbons;
    }

    foreach (array_filter($productImagesArr) as $afile_id => $image) {
        $uploadedTime = AttachedFile::setTimeParam($image['afile_updated_at']);
        $originalImgUrl = UrlHelper::getCachedUrl(UrlHelper::generateFullFileUrl('Image', 'product', array($product['product_id'], ImageDimension::VIEW_ORIGINAL, 0, $image['afile_id'])) . $uploadedTime, CONF_IMG_CACHE_TIME, '.jpg');
        $mainImgUrl = UrlHelper::getCachedUrl(UrlHelper::generateFullFileUrl('Image', 'product', array($product['product_id'], ImageDimension::VIEW_MEDIUM, 0, $image['afile_id'])) . $uploadedTime, CONF_IMG_CACHE_TIME, '.jpg');
        $productImagesArr[$afile_id]['product_image_url'] = $mainImgUrl;
    }

    $selectedOptionsArr = $product['selectedOptionValues'];
    foreach ($optionRows as $key => &$option) {
        foreach ($option['values'] as $index => &$opVal) {
            $opVal['theprice'] = CommonHelper::displayMoneyFormat($opVal['theprice']);
            $opVal['isAvailable'] = 1;
            $opVal['isSelected'] = 1;
            $opVal['optionUrlValue'] = $product['selprod_id'];
            if (!in_array($opVal['optionvalue_id'], $product['selectedOptionValues'])) {
                $opVal['isSelected'] = 0;
                $optionUrl = Product::generateProductOptionsUrl($product['selprod_id'], $selectedOptionsArr, $option['option_id'], $opVal['optionvalue_id'], $product['product_id']);
                $optionUrlArr = explode("::", $optionUrl);
                if (is_array($optionUrlArr) && count($optionUrlArr) == 2) {
                    $opVal['isAvailable'] = 0;
                }
                $optionUrl = Product::generateProductOptionsUrl($product['selprod_id'], $selectedOptionsArr, $option['option_id'], $opVal['optionvalue_id'], $product['product_id'], true);
                $opVal['optionUrlValue'] = $optionUrl;
            }
        }
    }

    if (!empty($product)) {
        if (isset($volumeDiscountRows) && !empty($volumeDiscountRows) && 0 < $currentStock) {
            foreach ($volumeDiscountRows as &$volumeDiscountRow) {
                $volumeDiscount = $product['theprice'] * ($volumeDiscountRow['voldiscount_percentage'] / 100);
                $price = ($product['theprice'] - $volumeDiscount);
                $volumeDiscountRow['price'] = CommonHelper::displayMoneyFormat($price, true, false, true, false, false, true);
                $volumeDiscountRow['theprice'] = CommonHelper::displayMoneyFormat($product['theprice'], true, false, true, false, false, true);
            }
        }

        $warrantTypes = Product::getWarrantyUnits($siteLangId);
        $product['product_warranty_unit_label'] = (isset($product['product_warranty_unit']) && array_key_exists($product['product_warranty_unit'], $warrantTypes)) ? $warrantTypes[$product['product_warranty_unit']] : '';

        $product['discount'] = ($product['selprod_price'] > $product['theprice']) ? CommonHelper::showProductDiscountedText($product, $siteLangId) : '';
        $product['selprod_price'] = CommonHelper::displayMoneyFormat($product['selprod_price']);
        $product['theprice'] = CommonHelper::displayMoneyFormat($product['theprice']);
        $product['inclusiveTax'] = FatUtility::int(FatApp::getConfig("CONF_PRODUCT_INCLUSIVE_TAX", FatUtility::VAR_INT, 0) && 0 == Tax::getActivatedServiceId());

        $product['youtubeUrlThumbnail'] = '';
        if (!empty($product['product_youtube_video'])) {
            $youtubeVideoUrl = $product['product_youtube_video'];
            $videoCode = UrlHelper::parseYouTubeurl($youtubeVideoUrl);
            $product['youtubeUrlThumbnail'] = 'https://img.youtube.com/vi/' . $videoCode . '/hqdefault.jpg';
        }
        $product['productUrl'] = UrlHelper::generateFullUrl('Products', 'View', array($product['selprod_id']));
    }

    $product['selprod_return_policies'] = !empty($product['selprod_return_policies']) ? $product['selprod_return_policies'] : (object) array();
    $product['selprod_warranty_policies'] = !empty($product['selprod_warranty_policies']) ? $product['selprod_warranty_policies'] : (object) array();
    $product['product_description'] = html_entity_decode($product['product_description'], ENT_QUOTES, 'utf-8');
    $product['product_description'] = str_replace('/editor/editor-image/', FatUtility::generateFullUrl() . 'editor/editor-image/', $product['product_description']);

    $product['product_cart_btn'] = 0;
    $fromDate = strtotime($product['selprod_available_from']);
    $currentDate = strtotime(FatDate::nowInTimezone(FatApp::getConfig('CONF_TIMEZONE'), 'Y-m-d'));

    if (
        $fromDate <= $currentDate &&
        false === SellerProduct::isPriceHidden($product['selprod_hide_price'], $product['shop_rfq_enabled']) &&
        (
            SellerProduct::isCartType($product['shop_rfq_enabled'], $product['selprod_cart_type'])
        )
    ) {
        $product['product_cart_btn'] = 1;
    }
    $product['product_rfq_btn'] = 0;
    if (
        RequestForQuote::isEnabled($product['shop_rfq_enabled'], $product['selprod_cart_type'])
    ) {
        $product['product_rfq_btn'] = 1;
    }

    if (!empty($product['moreSellersArr']) && 0 < count($product['moreSellersArr'])) {

        /* Shop and SelProd Badge */
        $shopIdsArr = array_column($product['moreSellersArr'], 'shop_id');
        $shopBadges = Badge::getShopBadges($siteLangId, $shopIdsArr);
        $shopBadgesArr = [];
        foreach ($shopBadges as $bdgRow) {
            $icon = AttachedFile::getAttachment(AttachedFile::FILETYPE_BADGE, $bdgRow[BadgeLinkCondition::DB_TBL_PREFIX . 'badge_id'], 0, $siteLangId);
            $uploadedTime = AttachedFile::setTimeParam($icon['afile_updated_at']);
            $url = UrlHelper::getCachedUrl(UrlHelper::generateFullFileUrl('Image', 'badgeIcon', array($icon['afile_record_id'], $siteLangId, ImageDimension::VIEW_MINI, $icon['afile_screen']), CONF_WEBROOT_FRONT_URL) . $uploadedTime, CONF_IMG_CACHE_TIME, '.jpg');
            $shopBadgesArr[$bdgRow['shop_id']] = [
                'url' => $url,
                Badge::DB_TBL_PREFIX . 'name' => $bdgRow[Badge::DB_TBL_PREFIX . 'name'],
            ];
        }

        $product['badges'] = $badges;
        /* Shop and SelProd Badge */
        foreach ($product['moreSellersArr'] as &$value) {
            if (FatApp::getConfig("CONF_ALLOW_REVIEWS", FatUtility::VAR_INT, 0)) {
                $shop_rating = SelProdRating::getSellerRating($value['selprod_user_id'], true);
                $value['shop_rating'] = round($shop_rating, 1);
            }
            $value['shopTotalReviews'] = SelProdReview::getSellerTotalReviews($value['shop_user_id'], true);
            $value['discount'] = ($value['selprod_price'] > $value['theprice']) ? CommonHelper::showProductDiscountedText($value, $siteLangId) : '';
            $value['selprod_price'] = CommonHelper::displayMoneyFormat($value['selprod_price']);
            $value['theprice'] = CommonHelper::displayMoneyFormat($value['theprice']);
            $value['badges'] = [];
            if (isset($shopBadgesArr[$value['shop_id']])) {
                $value['badges'][] = $shopBadgesArr[$value['shop_id']];
            }
        }
        if (!empty($shop)) {
            $shop['moreSellersArr'] = $product['moreSellersArr'];
        }
        unset($product['moreSellersArr']);
    }
    
    $product['codEnabled'] = (true === $codEnabled ? 1 : 0);
    $product['isOutOfMinOrderQty'] = $isOutOfMinOrderQty;
    $product['shippingDetails'] = empty($shippingDetails) ? (object) array() : $shippingDetails;
    $product['socialShareContent'] = empty($socialShareContent) ? (object) array() : $socialShareContent;

    $defaultImage = UrlHelper::getCachedUrl(UrlHelper::generateFullFileUrl('Image', 'product', array(0, ImageDimension::VIEW_MEDIUM, 0)), CONF_IMG_CACHE_TIME, '.jpg');
    $firstImage = current($productImagesArr);
    if (isset($firstImage['product_image_url']) && !empty($firstImage['product_image_url'])) {
        $defaultImage = $firstImage['product_image_url'];
    }

    $product['productDefaultImage'] = $defaultImage;

    $previewLinks = $product['preview_links'] ?? [];
    $previewAttachments = $product['preview_attachments'] ?? [];
    unset($product['preview_links'], $product['preview_attachments']);

    $data['data'][] = [
        'type' => Product::CONTENT_TYPE_PRODUCT_IMAGES,
        'title' => Labels::getLabel('LBL_PRODUCT_IMAGES', $siteLangId),
        'content' => array_values($productImagesArr),
    ];
    $data['data'][] = [
        'type' => Product::CONTENT_TYPE_PRODUCT,
        'title' => Labels::getLabel('LBL_PRODUCT_DETAIL', $siteLangId),
        'content' => empty($product) ? (object) array() : $product,
    ];

    if (!empty($optionRows)) {
        $data['data'][] = [
            'type' => Product::CONTENT_TYPE_OPTIONS,
            'title' => Labels::getLabel('LBL_PRODUCT_OPTIONS', $siteLangId),
            'content' => $optionRows,
        ];
    }

    $productPolicies = [];

    $returnAge = '' != $product['selprod_return_age'] ? $product['selprod_return_age'] : $product['shop_return_age'];
    if (!empty($product['shop_return_age']) && 0 < $returnAge && Product::PRODUCT_TYPE_PHYSICAL == $product['product_type']) {
        $lbl = Labels::getLabel('MSG_{DAYS}_DAYS_RETURN_BACK_POLICY', $siteLangId);
        $returnAge = !empty($product['selprod_return_age']) ? $product['selprod_return_age'] : $product['shop_return_age'];
        $returnAge = !empty($returnAge) ? $returnAge : 0;
        $returnAge = CommonHelper::replaceStringData($lbl, ['{DAYS}' => $returnAge]);
        $productPolicies[] = array(
            'title' => $returnAge,
            'isSvg' => Plugin::RETURN_FALSE,
            'icon' => CONF_WEBROOT_URL . 'images/easyreturns.png'
        );
    }

    $cancellationAge = '' != $product['selprod_cancellation_age'] ? $product['selprod_cancellation_age'] : $product['shop_cancellation_age'];
    if (!empty($product['shop_cancellation_age']) && 0 <  $cancellationAge && Product::PRODUCT_TYPE_PHYSICAL == $product['product_type']) {
        $lbl = Labels::getLabel('MSG_{DAYS}_DAYS_CANCELLATION_POLICY', $siteLangId);
        $cancellationAge = !empty($product['selprod_cancellation_age']) ? $product['selprod_cancellation_age'] : $product['shop_cancellation_age'];
        $cancellationAge = !empty($cancellationAge) ? $cancellationAge : 0;
        $cancellationAge = CommonHelper::replaceStringData($lbl, ['{DAYS}' => $cancellationAge]);
        $productPolicies[] = array(
            'title' => $cancellationAge,
            'isSvg' => Plugin::RETURN_FALSE,
            'icon' => CONF_WEBROOT_URL . 'images/easyreturns.png'
        );
    }
    if (!empty($product['product_warranty']) && !empty($product['product_warranty_unit_label'])) {
        $lbl = Labels::getLabel('MSG_{DAYS}_{UNIT}_WARRANTY', $siteLangId);
        $warranty = CommonHelper::replaceStringData($lbl, ['{DAYS}' => $product['product_warranty'], '{UNIT}' => $product['product_warranty_unit_label']]);
        $productPolicies[] = array(
            'title' => $warranty,
            'isSvg' => Plugin::RETURN_FALSE,
            'icon' => CONF_WEBROOT_URL . 'images/yearswarranty.png'
        );
    }

    if (Product::PRODUCT_TYPE_PHYSICAL == $product['product_type']) {
        if (isset($shippingDetails['ps_free']) && $shippingDetails['ps_free'] == applicationConstants::YES) {
            $productPolicies[] = array(
                'title' => Labels::getLabel('LBL_FREE_SHIPPING_ON_THIS_ORDER', $siteLangId),
                'isSvg' => Plugin::RETURN_FALSE,
                'icon' => CONF_WEBROOT_URL . 'images/freeshipping.png'
            );
        }
    }

    if (0 < $codEnabled && Product::PRODUCT_TYPE_PHYSICAL == $product['product_type']) {
        $productPolicies[] = array(
            'title' => Labels::getLabel('LBL_CASH_ON_DELIVERY_IS_AVAILABLE', $siteLangId),
            'isSvg' => Plugin::RETURN_FALSE,
            'icon' => CONF_WEBROOT_URL . 'images/safepayments.png'
        );
    }
    if (Product::PRODUCT_TYPE_PHYSICAL == $product['product_type']) {
        $fulfillmentLabel = Labels::getLabel('LBL_INVALID_FULFILLMENT', $siteLangId);
        $icon = CONF_WEBROOT_URL . 'images/';
        switch ($fulfillmentType) {
            case Shipping::FULFILMENT_SHIP:
                $fulfillmentLabel = Labels::getLabel('LBL_SHIPPED_ONLY', $siteLangId);
                $icon .= 'shipping_30x30.png';
                break;
            case Shipping::FULFILMENT_PICKUP:
                $fulfillmentLabel = Labels::getLabel('LBL_PICKUP_ONLY', $siteLangId);
                $icon .= 'item_pickup_30x30.png';
                break;
            case Shipping::FULFILMENT_ALL:
                $fulfillmentLabel = Labels::getLabel('LBL_SHIPPMENT_AND_PICKUP', $siteLangId);
                $icon .= 'shipping_30x30.png';
                break;
            default:
                $fulfillmentLabel = Labels::getLabel('LBL_SHIPPED_ONLY', $siteLangId);
                $icon .= 'shipping_30x30.png';
                break;
        }


        $productPolicies[] = array(
            'title' => $fulfillmentLabel,
            'isSvg' => Plugin::RETURN_TRUE,
            'icon' => $icon
        );
    }

    if (!empty($productPolicies)) {
        $data['data'][] = [
            'type' => Product::CONTENT_TYPE_PRODUCT_POLICIES,
            'title' => Labels::getLabel('LBL_PRODUCT_POLICIES', $siteLangId),
            'content' => $productPolicies
        ];
    }

    $productDescription = html_entity_decode($product['product_description'], ENT_QUOTES, 'utf-8');
    if (!empty(str_replace("\r\n", '', $productDescription))) {
        $productDescription = str_replace('/editor/editor-image/', FatUtility::generateFullUrl() . 'editor/editor-image/', $productDescription);
        $data['data'][] = [
            'type' => Product::CONTENT_TYPE_PRODUCT_DESCRIPTION,
            'title' => Labels::getLabel('LBL_PRODUCT_DESCRIPTION', $siteLangId),
            'content' => $productDescription
        ];
    }

    if (0 < count($previewLinks) || 0 < count($previewAttachments)) {
        $content = [];
        if (0 < count($previewLinks)) {
            $content['preview_links'] = array_values($previewLinks);
        }
        if (0 < count($previewAttachments)) {
            foreach ($previewAttachments as $key => &$attachment) {
                $attachment['downloadUrl'] = UrlHelper::generateFullUrl('Products', 'downloadPreview', array($attachment['prev_afile_id'], $product['selprod_id']));
            }
            $content['preview_attachments'] = array_values($previewAttachments);
        }

        if (!empty($content)) {
            $data['data'][] = [
                'type' => Product::CONTENT_TYPE_DIGITAL_FILES_AND_LINKS,
                'title' => Labels::getLabel('LBL_Preview_files', $siteLangId),
                'content' => $content
            ];
        }
    }

    if (!empty($productSpecifications)) {
        $data['data'][] = [
            'type' => Product::CONTENT_TYPE_SPECIFICATIONS,
            'title' => Labels::getLabel('LBL_PRODUCT_SPECIFICATIONS', $siteLangId),
            'content' => $productSpecifications
        ];
    }

    if (!empty($volumeDiscountRows)) {
        $data['data'][] = [
            'type' => Product::CONTENT_TYPE_VOLUME_DISCOUNT,
            'title' => Labels::getLabel('LBL_VOLUME_DISCOUNT', $siteLangId),
            'content' => $volumeDiscountRows,
        ];
    }

    if (!empty($upsellProducts)) {
        $data['data'][] = [
            'type' => Product::CONTENT_TYPE_BUY_TOGETHER,
            'title' => Labels::getLabel('LBL_BUY_TOGETHER', $siteLangId),
            'content' => $upsellProducts,
        ];
    }

    if (!empty($shop)) {
        $shop['shopTotalReviews'] = $shopTotalReviews;
        $shop['shop_rating'] = round($shop_rating, 1);
    }

    $data['data'][] = [
        'type' => Product::CONTENT_TYPE_SHOP,
        'title' => Labels::getLabel('LBL_Shop', $siteLangId),
        'content' => empty($shop) ? (object) array() : $shop,
    ];


    if (empty((array) $product)) {
        $status = applicationConstants::OFF;
    }
} else if (1 < $page) {
    $relTRightRibbons = $relatedProductsRibbons['tRightRibbons'];
    foreach (array_filter($relatedProductsRs) as $index => $rProduct) {
        $selProdRibbons = [];

        if (array_key_exists($rProduct['selprod_id'], $relTRightRibbons)) {
            $selProdRibbons[] = $relTRightRibbons[$rProduct['selprod_id']];
        }
        $uploadedTime = AttachedFile::setTimeParam($rProduct['product_updated_on']);
        $relatedProductsRs[$index]['product_image_url'] = UrlHelper::getCachedUrl(UrlHelper::generateFullFileUrl('image', 'product', array($rProduct['product_id'], ImageDimension::VIEW_MEDIUM, $rProduct['selprod_id'], 0, $siteLangId)) . $uploadedTime, CONF_IMG_CACHE_TIME, '.jpg');
        $relatedProductsRs[$index]['discount'] = ($rProduct['selprod_price'] > $rProduct['theprice']) ? CommonHelper::showProductDiscountedText($rProduct, $siteLangId) : '';
        $relatedProductsRs[$index]['selprod_price'] = CommonHelper::displayMoneyFormat($rProduct['selprod_price']);
        $relatedProductsRs[$index]['theprice'] = CommonHelper::displayMoneyFormat($rProduct['theprice']);
        $relatedProductsRs[$index]['ribbons'] = $selProdRibbons;
    }

    $recTRightRibbons = $recommendedProductsRibbons['tRightRibbons'];
    foreach (array_filter($recommendedProducts) as $index => $recProduct) {
        $selProdRibbons = [];
        if (array_key_exists($recProduct['selprod_id'], $recTRightRibbons)) {
            $selProdRibbons[] = $recTRightRibbons[$recProduct['selprod_id']];
        }
        $uploadedTime = AttachedFile::setTimeParam($recProduct['product_updated_on']);
        $recommendedProducts[$index]['product_image_url'] = UrlHelper::getCachedUrl(UrlHelper::generateFullFileUrl('image', 'product', array($recProduct['product_id'], ImageDimension::VIEW_MEDIUM, $recProduct['selprod_id'], 0, $siteLangId)) . $uploadedTime, CONF_IMG_CACHE_TIME, '.jpg');
        $recommendedProducts[$index]['discount'] = ($recProduct['selprod_price'] > $recProduct['theprice']) ? CommonHelper::showProductDiscountedText($recProduct, $siteLangId) : '';
        $recommendedProducts[$index]['selprod_price'] = CommonHelper::displayMoneyFormat($recProduct['selprod_price']);
        $recommendedProducts[$index]['theprice'] = CommonHelper::displayMoneyFormat($recProduct['theprice']);
        $recommendedProducts[$index]['ribbons'] = $selProdRibbons;
    }

    $recentTRightRibbons = $recentlyViewedRibbons['tRightRibbons'] ?? [];
    foreach (array_filter($recentlyViewed) as $index => $recViewed) {
        $selProdRibbons = [];
        if (array_key_exists($recViewed['selprod_id'], $recentTRightRibbons)) {
            $selProdRibbons[] = $recentTRightRibbons[$recViewed['selprod_id']];
        }
        $uploadedTime = AttachedFile::setTimeParam($recViewed['product_updated_on']);
        $recentlyViewed[$index]['product_image_url'] = UrlHelper::getCachedUrl(UrlHelper::generateFullFileUrl('image', 'product', array($recViewed['product_id'], ImageDimension::VIEW_MEDIUM, $recViewed['selprod_id'], 0, $siteLangId)) . $uploadedTime, CONF_IMG_CACHE_TIME, '.jpg');
        $recentlyViewed[$index]['discount'] = ($recViewed['selprod_price'] > $recViewed['theprice']) ? CommonHelper::showProductDiscountedText($recViewed, $siteLangId) : '';
        $recentlyViewed[$index]['selprod_price'] = CommonHelper::displayMoneyFormat($recViewed['selprod_price']);
        $recentlyViewed[$index]['theprice'] = CommonHelper::displayMoneyFormat($recViewed['theprice']);
        $recentlyViewed[$index]['ribbons'] = $selProdRibbons;
    }

    $productDetailPageBanner = [];
    if (!empty($banners) && $banners['blocation_active'] && count($banners['banners'])) {
        foreach ($banners['banners'] as &$val) {
            $bannerImageUrl = '';
            if (!AttachedFile::getMultipleAttachments(AttachedFile::FILETYPE_BANNER, $val['banner_id'], 0, $siteLangId)) {
                continue;
            } else {
                $slideArr = AttachedFile::getMultipleAttachments(AttachedFile::FILETYPE_BANNER, $val['banner_id'], 0, $siteLangId);
                foreach ($slideArr as $slideScreen) {
                    switch ($slideScreen['afile_screen']) {
                        case applicationConstants::SCREEN_MOBILE:
                            $bannerImageUrl = UrlHelper::generateFullUrl('Banner', 'BannerImage', array($val['banner_id'], $siteLangId, applicationConstants::SCREEN_MOBILE, 'PRODUCTLAYOUT'));
                            break;
                        case applicationConstants::SCREEN_IPAD:
                            $bannerImageUrl = UrlHelper::generateFullUrl('Banner', 'BannerImage', array($val['banner_id'], $siteLangId, applicationConstants::SCREEN_IPAD, 'PRODUCTLAYOUT'));
                            break;
                        case applicationConstants::SCREEN_DESKTOP:
                            $bannerImageUrl = UrlHelper::generateFullUrl('Banner', 'BannerImage', array($val['banner_id'], $siteLangId, applicationConstants::SCREEN_DESKTOP, 'PRODUCTLAYOUT'));
                            break;
                    }
                }
                $val['banner_image_url'] = $bannerImageUrl;
                $urlTypeData = CommonHelper::getUrlTypeData($val['banner_url']);
                if (false === $urlTypeData) {
                    $urlTypeData = array(
                        'url' => $val['banner_url'],
                        'recordId' => 0,
                        'urlType' => applicationConstants::URL_TYPE_EXTERNAL
                    );
                }

                $val['banner_url'] = ($urlTypeData['urlType'] == applicationConstants::URL_TYPE_EXTERNAL ? $urlTypeData['url'] : $urlTypeData['recordId']);
                $val['banner_url_type'] = $urlTypeData['urlType'];
            }
        }
        $productDetailPageBanner = $banners['banners'];
    }

    if (!empty($reviews)) {
        if (!empty($ratingAspects)) {
            foreach ($ratingAspects as &$ratingAsp) {
                $ratingAsp['prod_rating'] = CommonHelper::numberFormat($ratingAsp['prod_rating'], false, true, 1);
            }
        }

        $reviews['prod_rating'] = FatUtility::convertToType($reviews['prod_rating'], FatUtility::VAR_FLOAT);
        $reviews['ratingAspects'] = (array) $ratingAspects;
    }

    $reviewsWithImages = [];
    if (!empty($imageReviewsList) && is_array($imageReviewsList)) {
        foreach ($imageReviewsList as &$imgReview) {
            $uploadedTime = AttachedFile::setTimeParam($imgReview['user_updated_on']);
            $imgReview['user_image'] = UrlHelper::getCachedUrl(UrlHelper::generateFullFileUrl('image', 'user', [$imgReview['spreview_postedby_user_id'], ImageDimension::VIEW_THUMB]) . $uploadedTime, CONF_IMG_CACHE_TIME, '.jpg');
            $images = AttachedFile::getMultipleAttachments(AttachedFile::FILETYPE_ORDER_FEEDBACK, $imgReview['spreview_id']);
            $imgReview['images'] = [];
            foreach ($images as $image) {
                $uploadedTime = AttachedFile::setTimeParam($image['afile_updated_at']);
                $imgUrl = UrlHelper::getCachedUrl(UrlHelper::generateFullFileUrl('Image', 'review', array($imgReview['spreview_id'], 0, ImageDimension::VIEW_MINI_THUMB, $image['afile_id'])) . $uploadedTime, CONF_IMG_CACHE_TIME, '.jpg');
                $largeImgUrl = UrlHelper::getCachedUrl(UrlHelper::generateFullFileUrl('Image', 'review', array($imgReview['spreview_id'], 0, ImageDimension::VIEW_LARGE, $image['afile_id'])) . $uploadedTime, CONF_IMG_CACHE_TIME, '.jpg');
                $imgReview['images'][] = [
                    'imageUrl' => $imgUrl,
                    'largeImageUrl' => $largeImgUrl
                ];
            }
        }
        $reviewsWithImages['imageReviewsPageCount'] = $imageReviewsPageCount;
        $reviewsWithImages['imageReviewsRecordCount'] = $imageReviewsRecordCount;
        $reviewsWithImages['imageReviewsList'] = (array) $imageReviewsList;
    }

    if (!empty($reviewsList) && is_array($reviewsList)) {
        foreach ($reviewsList as &$review) {
            $uploadedTime = AttachedFile::setTimeParam($review['user_updated_on']);
            $review['user_image'] = UrlHelper::getCachedUrl(UrlHelper::generateFullFileUrl('image', 'user', [$review['spreview_postedby_user_id'], ImageDimension::VIEW_THUMB]) . $uploadedTime, CONF_IMG_CACHE_TIME, '.jpg');
            foreach ($recordRatings as $rating) {
                if ($review['spreview_id'] != $rating['sprating_spreview_id']) {
                    continue;
                }
                $review['ratingAspects'][] = $rating;
            }
        }

        $reviews['reviewsList'] = (array) $reviewsList;
    }

    if (!empty($reviewsWithImages) || !empty($reviews)) {
        $data['data'][] = [
            'type' => Product::CONTENT_TYPE_REVIEWS,
            'title' => Labels::getLabel('LBL_REVIEWS', $siteLangId),
            'content' => [
                'withImages' =>  (object) $reviewsWithImages,
                'withoutImages' => (object) $reviews
            ],
        ];
    }

    if (!empty($relatedProductsRs)) {
        $data['data'][] = [
            'type' => Product::CONTENT_TYPE_RELATED_PRODUCTS,
            'title' => Labels::getLabel('LBL_SIMILAR_PRODUCTS', $siteLangId),
            'content' => array_values($relatedProductsRs)
        ];
    }

    if (!empty($recommendedProducts)) {
        $data['data'][] = [
            'type' => Product::CONTENT_TYPE_RECOMMENDED_PRODUCTS,
            'title' => Labels::getLabel('LBL_RECOMMENDED_PRODUCTS', $siteLangId),
            'content' => $recommendedProducts
        ];
    }

    if (!empty($productDetailPageBanner)) {
        $data['data'][] = [
            'type' => Product::CONTENT_TYPE_BANNERS,
            'title' => Labels::getLabel('LBL_BANNER', $siteLangId),
            'content' => $productDetailPageBanner,
        ];
    }

    if (!empty($recentlyViewed)) {
        $data['data'][] = [
            'type' => Product::CONTENT_TYPE_RECENTLY_VIEWED,
            'title' => Labels::getLabel('LBL_RECENTLY_VIEWED', $siteLangId),
            'content' => array_values($recentlyViewed)
        ];
    }
}

/* $data = array(
    'shop_rating' => round($shop_rating, 1),
    'shop' => empty($shop) ? (object) array() : $shop,
    'shopTotalReviews' => $shopTotalReviews,
    
);

if (!empty($data['shop'])) {
    if (isset($data['shop']['shop_payment_policy']) && !empty(array_filter((array)$data['shop']['shop_payment_policy']))) {
        $data['shop']['policies'][] = [
            'title' => Labels::getLabel('LBL_Payment', $siteLangId),
            'description' => $data['shop']['shop_payment_policy']
        ];
    }
    if (isset($data['shop']['shop_delivery_policy']) && !empty(array_filter((array)$data['shop']['shop_delivery_policy']))) {
        $data['shop']['policies'][] = [
            'title' => Labels::getLabel('LBL_Shipping', $siteLangId),
            'description' => $data['shop']['shop_delivery_policy']
        ];
    }
    if (isset($data['shop']['shop_refund_policy']) && !empty(array_filter((array)$data['shop']['shop_refund_policy']))) {
        $data['shop']['policies'][] = [
            'title' => Labels::getLabel('LBL_Refunds_Exchanges', $siteLangId),
            'description' => $data['shop']['shop_refund_policy']
        ];
    }

    $data['shop']['policies'] = !empty($data['shop']['policies']) ? $data['shop']['policies'] : [];

    unset($data['shop']['shop_payment_policy'], $data['shop']['shop_delivery_policy'], $data['shop']['shop_refund_policy'], $data['shop']['shop_additional_info'], $data['shop']['shop_seller_info']);
}

$data['shop'] = !empty($data['shop']) ? $data['shop'] : (object)array();
 */
