<?php
class ImageDimension extends FatUtility
{
    public const TYPE_SLIDE = 1;
    public const TYPE_BANNER = 2;
    public const TYPE_PRODUCTS = 3;
    public const TYPE_USER = 4;
    public const TYPE_CUSTOM_PRODUCTS = 5;
    public const TYPE_SHOP_LOGO = 6;
    public const TYPE_SHOP_BANNER = 7;
    public const TYPE_PROMOTION_MEDIA = 8;
    public const TYPE_BRAND_LOGO = 9;
    public const TYPE_BRAND_IMAGE = 10;
    public const TYPE_EMAIL_LOGO = 11;
    public const TYPE_SOCIAL_FEED = 12;
    public const TYPE_WATERMARK = 13;
    public const TYPE_APPLE_TOUCH_ICON = 14;
    public const TYPE_MOBILE_LOGO = 15;
    public const TYPE_INVOICE_LOGO = 16;
    public const TYPE_CATEGORY_COLLECTION_BG = 17;
    public const TYPE_COUPON = 18;
    public const TYPE_META = 19;
    public const TYPE_FIRST_PURCHASE_COUPON = 20;
    public const TYPE_FEVICON = 21;
    public const TYPE_SOCIAL_PLATFORM = 22;
    public const TYPE_DISPLAY_COLLECTION_IMAGE = 23;
    public const TYPE_DISPLAY_COLLECTION_BG_IMAGE = 24;
    public const TYPE_BLOG_POST = 25;
    public const TYPE_BATCH_PRODUCT = 26;
    public const TYPE_TESTIMONIAL = 27;
    public const TYPE_CPAGE_BG = 28;
    public const TYPE_CBLOCK_BG = 29;
    public const TYPE_SHOP_COLLECTION_IMAGE = 30;
    public const TYPE_PLUGIN_IMAGE = 31;
    public const TYPE_REVIEW_IMAGE = 32;
    public const TYPE_BADGE_ICON = 33;
    public const TYPE_BADGE_REQUEST_IMAGE = 34;
    public const TYPE_USER_PROFILE_IMAGE = 35;
    public const TYPE_CATEGORY_IMAGE = 36;
    public const TYPE_CATEGORY_ICON = 37;
    public const TYPE_CATEGORY_THUMB = 38;
    public const TYPE_CATEGORY_SELLER_BANNER = 39;
    public const TYPE_CATEGORY_BANNER = 40;
    public const TYPE_ADMIN_BADGE_REQUEST = 41;
    public const TYPE_PUSH_NOTIFICATION = 42;

    public const WIDTH = 'width';
    public const HEIGHT = 'height';

    public const VIEW_DESKTOP = 'DESKTOP';
    public const VIEW_TABLET = 'TABLET';
    public const VIEW_MOBILE = 'MOBILE';
    public const VIEW_ORIGINAL = 'ORIGINAL';

    public const VIEW_MEDIUM = 'MEDIUM';
    public const VIEW_SMALL = 'SMALL';
    public const VIEW_EXTRA_SMALL = 'EXTRA-SMALL';
    public const VIEW_THUMB = 'THUMB';
    public const VIEW_MINI = 'MINI';
    public const VIEW_MINI_THUMB = 'MINITHUMB';

    public const VIEW_CLAYOUT4 = 'CLAYOUT4';
    public const VIEW_CLAYOUT3 = 'CLAYOUT3';
    public const VIEW_CLAYOUT2 = 'CLAYOUT2';
    public const VIEW_CLAYOUT1 = 'CLAYOUT1';

    public const VIEW_FB_RECOMMEND = 'FB_RECOMMEND';
    public const VIEW_DEFAULT = 'DEFAULT';
    public const VIEW_PREVIEW = 'PREVIEW';
    public const VIEW_LISTING_PAGE = 'LISTING_PAGE';
    public const VIEW_COLLECTION_PAGE = 'COLLECTION_PAGE';
    public const VIEW_NORMAL = 'NORMAL';
    public const VIEW_HOME = 'HOME';
    public const VIEW_LAYOUT1 = 'LAYOUT1';
    public const VIEW_LAYOUT2 = 'LAYOUT2';
    public const VIEW_FEATURED = 'FEATURED';
    public const VIEW_SHOP = 'SHOP';
    public const VIEW_ICON = 'ICON';
    public const VIEW_LARGE = 'LARGE';
    public const VIEW_HOME_PAGE_BANNER_TOP_LAYOUT = "TOPLAYOUT";
    public const VIEW_HOME_PAGE_BANNER_MIDDLE_LAYOUT = "MIDDLELAYOUT";
    public const VIEW_HOME_PAGE_BANNER_BOTTOM_LAYOUT = "BOTTOMLAYOUT";
    public const VIEW_HOME_PAGE_BANNER_PRODUCT_LAYOUT = "PRODUCTLAYOUT";
    public const VIEW_PROD_PROMOTIONAL_BANNER = "PRODUCTBANNER";
    public const VIEW_CROPED = "CROPED";

    public static function getData(int $type, $sizeType = '', $aspectRatioType = 1, $layout = 0): array
    {
        $sizeType = strtoupper($sizeType);

        $imageDimensions = [];
        switch ($type) {
            case self::TYPE_SLIDE:
                $imageDimensions = self::getSlideData($sizeType);
                break;
            case self::TYPE_PRODUCTS:
            case self::TYPE_CUSTOM_PRODUCTS:
                $imageDimensions = self::getProductImageData($sizeType);
                break;
            case self::TYPE_USER:
                $imageDimensions = self::getUserImageData($sizeType);
                break;
            case self::TYPE_SHOP_LOGO:
                $imageDimensions = self::getShopLogoImageData($aspectRatioType, $sizeType);
                break;
            case self::TYPE_SHOP_BANNER:
                $imageDimensions = self::getShopBannerImageData($sizeType);
                break;
            case self::TYPE_PROMOTION_MEDIA:
                $imageDimensions = self::getPromotionMediaImageData($sizeType);
                break;
            case self::TYPE_BRAND_LOGO:
                $imageDimensions = self::getBrandLogoImageData($aspectRatioType, $sizeType);
                break;
            case self::TYPE_BRAND_IMAGE:
                $imageDimensions = self::getBrandImageData($sizeType);
                break;
            case self::TYPE_EMAIL_LOGO:
                $imageDimensions = self::getEmailLogoImageData($aspectRatioType, $sizeType);
                break;
            case self::TYPE_SOCIAL_FEED:
                $imageDimensions = self::getSocialFeedImageData($sizeType);
                break;
            case self::TYPE_WATERMARK:
                $imageDimensions = self::getWaterImageData($sizeType);
                break;
            case self::TYPE_APPLE_TOUCH_ICON:
                $imageDimensions = self::getAppleTouchIconImageData($sizeType);
                break;
            case self::TYPE_MOBILE_LOGO:
                $imageDimensions = self::getMobileLogoImageData($sizeType);
                break;
            case self::TYPE_INVOICE_LOGO:
                $imageDimensions = self::getInvoiceLogoImageData($sizeType);
                break;
            case self::TYPE_CATEGORY_COLLECTION_BG:
                $imageDimensions = self::getCategoryCollectionBGImageData($sizeType);
                break;
            case self::TYPE_COUPON:
                $imageDimensions = self::getCouponImageData($sizeType);
                break;
            case self::TYPE_META:
                $imageDimensions = self::getMetaImageData($sizeType);
                break;
            case self::TYPE_FIRST_PURCHASE_COUPON:
                $imageDimensions = self::getFirstPurchaseCouponImageData($sizeType);
                break;
            case self::TYPE_FEVICON:
                $imageDimensions = self::getFaviconImageData($sizeType);
                break;
            case self::TYPE_SOCIAL_PLATFORM:
                $imageDimensions = self::getSocialPlatformImageData($sizeType);
                break;
            case self::TYPE_DISPLAY_COLLECTION_IMAGE:
                $imageDimensions = self::getDisplayCollectionImageData($sizeType, $layout);
                break;
            case self::TYPE_DISPLAY_COLLECTION_BG_IMAGE:
                $imageDimensions = self::getDisplayCollectionBGImageData($sizeType);
                break;
            case self::TYPE_BLOG_POST:
                $imageDimensions = self::getBlogPostImageData($sizeType);
                break;
            case self::TYPE_BATCH_PRODUCT:
                $imageDimensions = self::getBatchProductImageData($sizeType);
                break;
            case self::TYPE_TESTIMONIAL:
                $imageDimensions = self::getTestimonialImageData($sizeType);
                break;
            case self::TYPE_CPAGE_BG:
                $imageDimensions = self::getCPageBackgroundImageData($sizeType);
                break;
            case self::TYPE_CBLOCK_BG:
                $imageDimensions = self::getCBlockBackgroundImageData($sizeType);
                break;
            case self::TYPE_SHOP_COLLECTION_IMAGE:
                $imageDimensions = self::getShopCollectionImageData($sizeType);
                break;
            case self::TYPE_PLUGIN_IMAGE:
                $imageDimensions = self::getPluginImageData($sizeType);
                break;
            case self::TYPE_REVIEW_IMAGE:
                $imageDimensions = self::getReviewImageData($sizeType);
                break;
            case self::TYPE_BADGE_ICON:
                $imageDimensions = self::getBadgeIconImageData($sizeType);
                break;
            case self::TYPE_BADGE_REQUEST_IMAGE:
                $imageDimensions = self::getBadgeRequestImageData($sizeType);
                break;
            case self::TYPE_USER_PROFILE_IMAGE:
                $imageDimensions = self::getUserProfileImageData($sizeType);
                break;
            case self::TYPE_BANNER:
                $imageDimensions = self::getBannerImageData($sizeType);
                break;
            case self::TYPE_CATEGORY_IMAGE:
                $imageDimensions = self::getCategoryImage($sizeType);
                break;
            case self::TYPE_CATEGORY_ICON:
                $imageDimensions = self::getCategoryIcon($sizeType);
                break;
            case self::TYPE_CATEGORY_THUMB:
                $imageDimensions = self::getCategoryThumb($sizeType);
                break;
            case self::TYPE_CATEGORY_SELLER_BANNER:
                $imageDimensions = self::getCategorySellerBanner($sizeType);
                break;
            case self::TYPE_CATEGORY_BANNER:
                $imageDimensions = self::getCategoryBanner($sizeType);
                break;
            case self::TYPE_ADMIN_BADGE_REQUEST:
                $imageDimensions = self::getAdminBadgeRequestImage($sizeType);
                break;
            case self::TYPE_PUSH_NOTIFICATION:
                $imageDimensions = self::getPushNotification($sizeType);
                break;
        }

        if (!empty($sizeType)) {
            $imageDimensions[$sizeType]['aspectRatio'] = self::getAspectRatio($imageDimensions[self::WIDTH], $imageDimensions[self::HEIGHT]);
            return $imageDimensions;
        }

        foreach ($imageDimensions as $key => $val) {
            $imageDimensions[$key]['aspectRatio'] = self::getAspectRatio($val[self::WIDTH], $val[self::HEIGHT]);
        }

        return $imageDimensions;
    }

    public static function getSlideData(string $sizeType = ''): array
    {
        $arr =  [
            self::VIEW_DESKTOP => [self::WIDTH => 2000, self::HEIGHT => 666],
            self::VIEW_MOBILE => [self::WIDTH => 640, self::HEIGHT => 360],
            self::VIEW_TABLET => [self::WIDTH => 1024, self::HEIGHT => 576],
            self::VIEW_THUMB => [self::WIDTH => 200, self::HEIGHT => 112],
        ];

        return self::returnData($arr, self::VIEW_DESKTOP, $sizeType);
    }


    public static function getProductImageData(string $sizeType = ''): array
    {
        $arr =  [
            self::VIEW_CLAYOUT2 => [self::WIDTH => 468, self::HEIGHT => 468],
            self::VIEW_CLAYOUT1 => [self::WIDTH => 341, self::HEIGHT => 341],
            self::VIEW_CLAYOUT3 => [self::WIDTH => 300, self::HEIGHT => 300],
            self::VIEW_CLAYOUT4 => [self::WIDTH => 478, self::HEIGHT => 478],
            self::VIEW_MOBILE => [self::WIDTH => 180, self::HEIGHT => 180],
            self::VIEW_TABLET => [self::WIDTH => 346, self::HEIGHT => 346],

            self::VIEW_THUMB => [self::WIDTH => 110, self::HEIGHT => 110],
            self::VIEW_MINI => [self::WIDTH => 50, self::HEIGHT => 50],
            self::VIEW_EXTRA_SMALL => [self::WIDTH => 60, self::HEIGHT => 60],
            self::VIEW_SMALL => [self::WIDTH => 230, self::HEIGHT => 230],
            self::VIEW_MEDIUM => [self::WIDTH => 500, self::HEIGHT => 500],
            self::VIEW_LARGE => [self::WIDTH => 800, self::HEIGHT => 800],
            self::VIEW_ORIGINAL => [self::WIDTH => 1500, self::HEIGHT => 1500],
            self::VIEW_FB_RECOMMEND => [self::WIDTH => 1200, self::HEIGHT => 630],
            self::VIEW_DEFAULT => [self::WIDTH => 400, self::HEIGHT => 400],
        ];

        return self::returnData($arr, self::VIEW_DEFAULT, $sizeType);
    }

    public static function getUserImageData(string $sizeType = ''): array
    {
        $arr =  [
            self::VIEW_MINI_THUMB => [self::WIDTH => 40, self::HEIGHT => 40],
            self::VIEW_THUMB => [self::WIDTH => 150, self::HEIGHT => 150],
            self::VIEW_MINI => [self::WIDTH => 70, self::HEIGHT => 70],
            self::VIEW_SMALL => [self::WIDTH => 200, self::HEIGHT => 200],
            self::VIEW_MEDIUM => [self::WIDTH => 500, self::HEIGHT => 500],
        ];

        return self::returnData($arr, self::VIEW_DEFAULT, $sizeType);
    }

    public static function getShopLogoImageData(int $aspectRatioType, string $sizeType = ''): array
    {
        $sizeType = self::formatString($sizeType);

        $arr[AttachedFile::RATIO_TYPE_RECTANGULAR] =  [
            self::VIEW_THUMB => [self::WIDTH => 62, self::HEIGHT => 35],
            self::VIEW_DEFAULT => [self::WIDTH => 500, self::HEIGHT => 280],
        ];

        $arr[AttachedFile::RATIO_TYPE_SQUARE] =  [
            self::VIEW_THUMB => [self::WIDTH => 200, self::HEIGHT => 200],
            self::VIEW_DEFAULT => [self::WIDTH => 500, self::HEIGHT => 500],
        ];

        $arr[AttachedFile::RATIO_TYPE_CUSTOM] =  $arr[AttachedFile::RATIO_TYPE_SQUARE];

        if (!empty($sizeType)) {
            if (!array_key_exists($sizeType, $arr[$aspectRatioType])) {
                return $arr[$aspectRatioType][self::VIEW_DEFAULT];
            }
            return $arr[$aspectRatioType][$sizeType];
        }

        return $arr[$aspectRatioType];
    }

    public static function getShopBannerImageData(string $sizeType = ''): array
    {
        $arr =  [
            self::VIEW_DESKTOP => [self::WIDTH => 2048, self::HEIGHT => 512],
            self::VIEW_MOBILE => [self::WIDTH => 640, self::HEIGHT => 360],
            self::VIEW_TABLET => [self::WIDTH => 1024, self::HEIGHT => 576],
            self::VIEW_THUMB => [self::WIDTH => 250, self::HEIGHT => 100],
        ];

        return self::returnData($arr, self::VIEW_DEFAULT, $sizeType);
    }


    public static function getPromotionMediaImageData(string $sizeType = ''): array
    {
        $arr =  [
            self::VIEW_PREVIEW => [self::WIDTH => 1298, self::HEIGHT => 600],
            self::VIEW_DEFAULT => [self::WIDTH => 1298, self::HEIGHT => 600]
        ];

        return self::returnData($arr, self::VIEW_DEFAULT, $sizeType);
    }

    public static function getBrandLogoImageData(int $aspectRatioType, string $sizeType = ''): array
    {
        $sizeType = self::formatString($sizeType);

        $arr[AttachedFile::RATIO_TYPE_RECTANGULAR] =  [
            self::VIEW_MINI_THUMB => [self::WIDTH => 62, self::HEIGHT => 35],
            self::VIEW_THUMB => [self::WIDTH => 62, self::HEIGHT => 35],
            self::VIEW_LISTING_PAGE => [self::WIDTH => 500, self::HEIGHT => 280],
            self::VIEW_DEFAULT => [self::WIDTH => 480, self::HEIGHT => 270],
            self::VIEW_COLLECTION_PAGE => [self::WIDTH => 160, self::HEIGHT => 90],
        ];

        $arr[AttachedFile::RATIO_TYPE_SQUARE] =  [
            self::VIEW_MINI_THUMB => [self::WIDTH => 42, self::HEIGHT => 42],
            self::VIEW_THUMB => [self::WIDTH => 61, self::HEIGHT => 61],
            self::VIEW_LISTING_PAGE => [self::WIDTH => 530, self::HEIGHT => 530],
            self::VIEW_DEFAULT => [self::WIDTH => 500, self::HEIGHT => 500],
            self::VIEW_COLLECTION_PAGE => [self::WIDTH => 100, self::HEIGHT => 100],
        ];

        if (!empty($sizeType)) {
            if (!array_key_exists($sizeType, $arr[$aspectRatioType])) {
                return $arr[$aspectRatioType][self::VIEW_DEFAULT];
            }
            return $arr[$aspectRatioType][$sizeType];
        }

        return $arr[$aspectRatioType];
    }

    public static function getBrandImageData(string $sizeType = ''): array
    {
        $arr =  [
            self::VIEW_THUMB => [self::WIDTH => 200, self::HEIGHT => 112],
            self::VIEW_MOBILE => [self::WIDTH => 640, self::HEIGHT => 360],
            self::VIEW_TABLET => [self::WIDTH => 1024, self::HEIGHT => 576],
            self::VIEW_DESKTOP => [self::WIDTH => 2000, self::HEIGHT => 500],
        ];
        return self::returnData($arr, self::VIEW_DESKTOP, $sizeType);
    }

    public static function getEmailLogoImageData(int $aspectRatioType, string $sizeType = ''): array
    {
        $arr = [
            AttachedFile::RATIO_TYPE_SQUARE => [
                self::VIEW_THUMB => [self::WIDTH => 150, self::HEIGHT => 150],
                self::VIEW_DEFAULT => [self::WIDTH => 150, self::HEIGHT => 150],
            ],
            AttachedFile::RATIO_TYPE_RECTANGULAR =>
            [
                self::VIEW_THUMB => [self::WIDTH => 150, self::HEIGHT => 85],
                self::VIEW_DEFAULT => [self::WIDTH => 150, self::HEIGHT => 85],
            ]
        ];

        if (!empty($sizeType)) {
            if (!array_key_exists($sizeType, $arr[$aspectRatioType])) {
                return $arr[$aspectRatioType][self::VIEW_DEFAULT];
            }
            return $arr[$aspectRatioType][$sizeType];
        }

        return $arr[$aspectRatioType];
    }

    public static function getSocialFeedImageData(string $sizeType = ''): array
    {
        $arr =  [
            self::VIEW_THUMB => [self::WIDTH => 120, self::HEIGHT => 80],
            self::VIEW_DEFAULT => [self::WIDTH => 240, self::HEIGHT => 160],
        ];

        return self::returnData($arr, self::VIEW_DEFAULT, $sizeType);
    }

    public static function getWaterImageData(string $sizeType = ''): array
    {
        $arr =  [
            self::VIEW_THUMB => [self::WIDTH => 100, self::HEIGHT => 100],
        ];

        return self::returnData($arr, self::VIEW_THUMB, $sizeType);
    }

    public static function getAppleTouchIconImageData(string $sizeType = ''): array
    {
        $sizeType = self::formatString($sizeType);

        $arr =  [
            self::VIEW_MINI => [self::WIDTH => 72, self::HEIGHT => 72],
            self::VIEW_SMALL => [self::WIDTH => 114, self::HEIGHT => 114],
            self::VIEW_THUMB => [self::WIDTH => 150, self::HEIGHT => 150],
        ];

        if (!empty($sizeType)) {
            if (!array_key_exists($sizeType, $arr)) {

                $arr_size = explode('-', $sizeType);
                if (count($arr_size) > 0) {
                    list($w, $h) = $arr_size;
                    $arr[$sizeType] = array(self::WIDTH => $w, self::HEIGHT => $h);
                    return $arr[$sizeType];
                } else {
                    return $arr;
                }
            }
            return $arr[$sizeType];
        }

        return self::returnData($arr, self::VIEW_SMALL, $sizeType);
    }

    public static function getMobileLogoImageData(string $sizeType = ''): array
    {
        $arr =  [
            self::VIEW_THUMB => [self::WIDTH => 100, self::HEIGHT => 100],
            self::VIEW_DEFAULT => [self::WIDTH => 82, self::HEIGHT => 268],
        ];

        return self::returnData($arr, self::VIEW_DEFAULT, $sizeType);
    }

    public static function getInvoiceLogoImageData(string $sizeType = ''): array
    {
        $arr =  [
            self::VIEW_THUMB => [self::WIDTH => 100, self::HEIGHT => 100],
            self::VIEW_DEFAULT => [self::WIDTH => 37, self::HEIGHT => 168],
        ];

        return self::returnData($arr, self::VIEW_DEFAULT, $sizeType);
    }

    public static function getCategoryCollectionBGImageData(string $sizeType = ''): array
    {
        $arr =  [
            self::VIEW_THUMB => [self::WIDTH => 100, self::HEIGHT => 100]
        ];

        return self::returnData($arr, self::VIEW_THUMB, $sizeType);
    }


    public static function getCouponImageData(string $sizeType = ''): array
    {
        $arr =  [
            self::VIEW_THUMB => [self::WIDTH => 100, self::HEIGHT => 100],
            self::VIEW_NORMAL => [self::WIDTH => 120, self::HEIGHT => 120],
            self::VIEW_DEFAULT => [self::WIDTH => 600, self::HEIGHT => 400],
        ];

        return self::returnData($arr, self::VIEW_DEFAULT, $sizeType);
    }


    public static function getMetaImageData(string $sizeType = ''): array
    {
        $arr =  [
            self::VIEW_THUMB => [self::WIDTH => 100, self::HEIGHT => 100],
            self::VIEW_DEFAULT => [self::WIDTH => 600, self::HEIGHT => 400]
        ];

        return self::returnData($arr, self::VIEW_DEFAULT, $sizeType);
    }

    public static function getFirstPurchaseCouponImageData(string $sizeType = ''): array
    {
        $arr =  [
            self::VIEW_THUMB => [self::WIDTH => 100, self::HEIGHT => 100],
            self::VIEW_NORMAL => [self::WIDTH => 120, self::HEIGHT => 150],
            self::VIEW_DEFAULT => [self::WIDTH => 600, self::HEIGHT => 400],

        ];

        return self::returnData($arr, self::VIEW_DEFAULT, $sizeType);
    }

    public static function getFaviconImageData(string $sizeType = ''): array
    {
        $sizeType = self::formatString($sizeType);

        $arr =  [
            self::VIEW_MINI => [self::WIDTH => 72, self::HEIGHT => 72],
            self::VIEW_SMALL => [self::WIDTH => 114, self::HEIGHT => 114],
        ];


        if (!empty($sizeType)) {
            if (!array_key_exists($sizeType, $arr)) {

                $arr_size = explode('-', $sizeType);
                if (count($arr_size) > 0) {
                    list($w, $h) = $arr_size;
                    $arr[$sizeType] = array(self::WIDTH => $w, self::HEIGHT => $h);
                    return $arr[$sizeType];
                } else {
                    return $arr;
                }
            }
            return $arr[$sizeType];
        }

        return $arr;
    }


    public static function getSocialPlatformImageData(string $sizeType = ''): array
    {
        $arr =  [
            self::VIEW_THUMB => [self::WIDTH => 200, self::HEIGHT => 100],
            self::VIEW_DEFAULT => [self::WIDTH => 30, self::HEIGHT => 30]
        ];

        return self::returnData($arr, self::VIEW_DEFAULT, $sizeType);
    }


    public static function getDisplayCollectionImageData(string $sizeType = '', int $layout = 0): array
    {
        $arr =  [
            self::VIEW_DESKTOP => [self::WIDTH => 278, self::HEIGHT => 417],
            self::VIEW_MOBILE => [self::WIDTH => 640, self::HEIGHT => 480],
            self::VIEW_THUMB => [self::WIDTH => 100, self::HEIGHT => 100],
            self::VIEW_HOME => [self::WIDTH => 76, self::HEIGHT => 92]
        ];
        if($layout == Collections::TYPE_CATEGORY_LAYOUT12) {
            $arr = [
                self::VIEW_DESKTOP => [self::WIDTH => 278, self::HEIGHT => 417],
                self::VIEW_MOBILE => [self::WIDTH => 375, self::HEIGHT => 200],
                self::VIEW_TABLET => [self::WIDTH => 1200, self::HEIGHT => 400],
                self::VIEW_THUMB => [self::WIDTH => 100, self::HEIGHT => 100],
                self::VIEW_HOME => [self::WIDTH => 76, self::HEIGHT => 92]
            ];
        }
        foreach ($arr as $key => $val) {
            $arr[$key]['aspectRatio'] = self::getAspectRatio($arr[$key][self::WIDTH], $arr[$key][self::HEIGHT]);
        }

        $arr['aspectRatio'] = self::getAspectRatio($arr[self::VIEW_DESKTOP][self::WIDTH], $arr[self::VIEW_DESKTOP][self::HEIGHT]);

        return self::returnData($arr, self::VIEW_THUMB, $sizeType);
    }

    public static function getDisplayCollectionBGImageData(string $sizeType = ''): array
    {
        $arr =  [
            self::VIEW_THUMB => [self::WIDTH => 100, self::HEIGHT => 100]
        ];

        return self::returnData($arr, self::VIEW_THUMB, $sizeType);
    }

    public static function getBlogPostImageData(string $sizeType = ''): array
    {
        $arr =  [
            self::VIEW_THUMB => [self::WIDTH => 100, self::HEIGHT => 100],
            self::VIEW_SMALL => [self::WIDTH => 200, self::HEIGHT => 200],
            self::VIEW_LAYOUT1 => [self::WIDTH => 800, self::HEIGHT => 450],
            self::VIEW_LAYOUT2 => [self::WIDTH => 645, self::HEIGHT => 363],
            self::VIEW_FEATURED => [self::WIDTH => 510, self::HEIGHT => 287],
            self::VIEW_DEFAULT => [self::WIDTH => 1024, self::HEIGHT => 576]
        ];
        return self::returnData($arr, self::VIEW_DEFAULT, $sizeType);
    }

    public static function getBatchProductImageData(string $sizeType = ''): array
    {
        $arr =  [
            self::VIEW_THUMB => [self::WIDTH => 100, self::HEIGHT => 100],
            self::VIEW_SMALL => [self::WIDTH => 200, self::HEIGHT => 200],
            self::VIEW_DEFAULT => [self::WIDTH => 400, self::HEIGHT => 400]
        ];

        return self::returnData($arr, self::VIEW_DEFAULT, $sizeType);
    }

    public static function getTestimonialImageData(string $sizeType = ''): array
    {
        $arr =  [
            self::VIEW_MEDIUM => [self::WIDTH => 300, self::HEIGHT => 300],
            self::VIEW_THUMB => [self::WIDTH => 61, self::HEIGHT => 61],
            self::VIEW_MINI_THUMB => [self::WIDTH => 42, self::HEIGHT => 52],
            self::VIEW_DEFAULT => [self::WIDTH => 200, self::HEIGHT => 200]
        ];

        return self::returnData($arr, self::VIEW_DEFAULT, $sizeType);
    }

    public static function getCPageBackgroundImageData(string $sizeType = ''): array
    {
        $arr =  [
            self::VIEW_THUMB => [self::WIDTH => 150, self::HEIGHT => 45],
            self::VIEW_COLLECTION_PAGE => [self::WIDTH => 45, self::HEIGHT => 41],
            self::VIEW_DEFAULT => [self::WIDTH => 1300, self::HEIGHT => 400],
        ];
        return self::returnData($arr, self::VIEW_DEFAULT, $sizeType);
    }


    public static function getCBlockBackgroundImageData(string $sizeType = ''): array
    {
        $arr =  [
            self::VIEW_THUMB => [self::WIDTH => 100, self::HEIGHT => 100],
            self::VIEW_DEFAULT => [self::WIDTH => 1300, self::HEIGHT => 400]
        ];

        return self::returnData($arr, self::VIEW_DEFAULT, $sizeType);
    }

    public static function getShopCollectionImageData(string $sizeType = ''): array
    {
        $arr =  [
            self::VIEW_THUMB => [self::WIDTH => 100, self::HEIGHT => 100],
            self::VIEW_SHOP => [self::WIDTH => 610, self::HEIGHT => 305]
        ];

        return self::returnData($arr, self::VIEW_SHOP, $sizeType);
    }

    public static function getPluginImageData(string $sizeType = ''): array
    {
        $arr =  [
            self::VIEW_ICON => [self::WIDTH => 30, self::HEIGHT => 30],
            self::VIEW_MINI_THUMB => [self::WIDTH => 61, self::HEIGHT => 61],
            self::VIEW_THUMB => [self::WIDTH => 100, self::HEIGHT => 100],
            self::VIEW_SMALL => [self::WIDTH => 200, self::HEIGHT => 200],
            self::VIEW_MEDIUM => [self::WIDTH => 250, self::HEIGHT => 250],
            self::VIEW_LARGE => [self::WIDTH => 350, self::HEIGHT => 350],

        ];

        return self::returnData($arr, self::VIEW_LARGE, $sizeType);
    }

    public static function getReviewImageData(string $sizeType = ''): array
    {
        $arr =  [
            self::VIEW_ICON => [self::WIDTH => 30, self::HEIGHT => 30],
            self::VIEW_MINI_THUMB => [self::WIDTH => 61, self::HEIGHT => 61],
            self::VIEW_THUMB => [self::WIDTH => 100, self::HEIGHT => 100],
            self::VIEW_SMALL => [self::WIDTH => 200, self::HEIGHT => 200],
            self::VIEW_MEDIUM => [self::WIDTH => 250, self::HEIGHT => 250],
            self::VIEW_LARGE => [self::WIDTH => 500, self::HEIGHT => 500],

        ];

        return self::returnData($arr, self::VIEW_LARGE, $sizeType);
    }

    public static function getBadgeIconImageData(string $sizeType = ''): array
    {
        $arr =  [
            self::VIEW_THUMB => [self::WIDTH => 60, self::HEIGHT => 60],
            self::VIEW_MINI => [self::WIDTH => 40, self::HEIGHT => 40]
        ];
        return self::returnData($arr, self::VIEW_THUMB, $sizeType);
    }

    public static function getUserProfileImageData(string $sizeType = ''): array
    {
        $arr =  [
            self::VIEW_THUMB => [self::WIDTH => 100, self::HEIGHT => 100],
            self::VIEW_CROPED => [self::WIDTH => 230, self::HEIGHT => 230]
        ];

        return self::returnData($arr, self::VIEW_THUMB, $sizeType);
    }



    public static function getBadgeRequestImageData(string $sizeType = ''): array
    {
        $arr =  [
            self::VIEW_THUMB => [self::WIDTH => 60, self::HEIGHT => 60],
            self::VIEW_MINI => [self::WIDTH => 35, self::HEIGHT => 35]
        ];

        return self::returnData($arr, self::VIEW_THUMB, $sizeType);
    }


    public static function getCategoryImage(string $sizeType = ''): array
    {
        $arr =  [
            self::VIEW_THUMB => [self::WIDTH => 100, self::HEIGHT => 100],
            self::VIEW_LARGE => [self::WIDTH => 400, self::HEIGHT => 400],
            self::VIEW_COLLECTION_PAGE => [self::WIDTH => 45, self::HEIGHT => 41]
        ];

        return self::returnData($arr, self::VIEW_THUMB, $sizeType);
    }


    public static function getCategoryIcon(string $sizeType = ''): array
    {
        $arr =  [
            self::VIEW_THUMB => [self::WIDTH => 100, self::HEIGHT => 100],
            self::VIEW_COLLECTION_PAGE => [self::WIDTH => 48, self::HEIGHT => 48],
            self::VIEW_DEFAULT => [self::WIDTH => 100, self::HEIGHT => 100]
        ];

        return self::returnData($arr, self::VIEW_THUMB, $sizeType);
    }

    public static function getCategoryThumb(string $sizeType = ''): array
    {
        $arr =  [
            self::VIEW_THUMB => [self::WIDTH => 60, self::HEIGHT => 60],
            self::VIEW_SMALL => [self::WIDTH => 128, self::HEIGHT => 128],
            self::VIEW_ICON => [self::WIDTH => 300, self::HEIGHT => 300],
            self::VIEW_DEFAULT => [self::WIDTH => 300, self::HEIGHT => 300],
        ];

        return self::returnData($arr, self::VIEW_THUMB, $sizeType);
    }

    public static function getCategorySellerBanner(string $sizeType = ''): array
    {
        $arr =  [
            self::VIEW_THUMB => [self::WIDTH => 250, self::HEIGHT => 100],
            self::VIEW_ICON => [self::WIDTH => 1320, self::HEIGHT => 320]
        ];

        return self::returnData($arr, self::VIEW_THUMB, $sizeType);
    }


    public static function getCategoryBanner(string $sizeType = ''): array
    {
        $arr =  [
            self::VIEW_THUMB => [self::WIDTH => 200, self::HEIGHT => 112],
            self::VIEW_MEDIUM => [self::WIDTH => 300, self::HEIGHT => 75],
            self::VIEW_MOBILE => [self::WIDTH => 640, self::HEIGHT => 360],
            self::VIEW_TABLET => [self::WIDTH => 1024, self::HEIGHT => 576],
            self::VIEW_DESKTOP => [self::WIDTH => 2000, self::HEIGHT => 500],
        ];

        return self::returnData($arr, self::VIEW_THUMB, $sizeType);
    }

    public static function getAdminBadgeRequestImage(string $sizeType = ''): array
    {
        $arr =  [
            self::VIEW_THUMB => [self::WIDTH => 50, self::HEIGHT => 50],
        ];

        return self::returnData($arr, self::VIEW_THUMB, $sizeType);
    }


    public static function getBannerImageData(string $sizeType = ''): array
    {
        $arr =  [
            self::VIEW_HOME_PAGE_BANNER_TOP_LAYOUT => [self::WIDTH => 1350, self::HEIGHT => 405],
            self::VIEW_HOME_PAGE_BANNER_MIDDLE_LAYOUT => [self::WIDTH => 600, self::HEIGHT => 338],
            self::VIEW_HOME_PAGE_BANNER_BOTTOM_LAYOUT => [self::WIDTH => 600, self::HEIGHT => 198],
            self::VIEW_HOME_PAGE_BANNER_PRODUCT_LAYOUT => [self::WIDTH => 600, self::HEIGHT => 198],
            self::VIEW_PROD_PROMOTIONAL_BANNER => [self::WIDTH => 743, self::HEIGHT => 223],
            self::VIEW_THUMB => [self::WIDTH => 200, self::HEIGHT => 50],
            self::VIEW_MINI_THUMB => [self::WIDTH => 52, self::HEIGHT => 42]
        ];

        return self::returnData($arr, self::VIEW_THUMB, $sizeType);
    }

    public static function getBannerData(string $sizeType = '', $layout = ''): array
    {
        $sizeType = self::formatString($sizeType);

        switch ($layout) {
            case Collections::TYPE_BANNER_LAYOUT1:
                $arr =  [
                    self::VIEW_DESKTOP => [self::WIDTH => 2000, self::HEIGHT => 666],
                    self::VIEW_MOBILE => [self::WIDTH => 640, self::HEIGHT => 360],
                    self::VIEW_TABLET => [self::WIDTH => 1024, self::HEIGHT => 576],
                    self::VIEW_THUMB => [self::WIDTH => 200, self::HEIGHT => 112],
                ];
                break;
            case Collections::TYPE_BANNER_LAYOUT2:
                $arr =  [
                    self::VIEW_DESKTOP => [self::WIDTH => 920, self::HEIGHT => 690],
                    self::VIEW_MOBILE => [self::WIDTH => 640, self::HEIGHT => 360],
                    self::VIEW_TABLET => [self::WIDTH => 1024, self::HEIGHT => 576],
                    self::VIEW_THUMB => [self::WIDTH => 200, self::HEIGHT => 112],
                ];
                break;
                /* case Collections::TYPE_BANNER_LAYOUT3:
                $arr =  [
                    self::VIEW_DESKTOP => [self::WIDTH => 640, self::HEIGHT => 360],
                    self::VIEW_MOBILE => [self::WIDTH => 640, self::HEIGHT => 360],
                    self::VIEW_TABLET => [self::WIDTH => 1024, self::HEIGHT => 576],
                    self::VIEW_THUMB => [self::WIDTH => 200, self::HEIGHT => 112],
                ];
                break; */
            case Collections::TYPE_BANNER_LAYOUT4:
                $arr =  [
                    self::VIEW_DESKTOP => [self::WIDTH => 1200, self::HEIGHT => 360],
                    self::VIEW_MOBILE => [self::WIDTH => 640, self::HEIGHT => 360],
                    self::VIEW_TABLET => [self::WIDTH => 1024, self::HEIGHT => 576],
                    self::VIEW_THUMB => [self::WIDTH => 200, self::HEIGHT => 112],
                ];
                break;
            case Collections::TYPE_BANNER_LAYOUT5:
                $arr =  [
                    self::VIEW_DESKTOP => [self::WIDTH => 500, self::HEIGHT => 250],
                    self::VIEW_MOBILE => [self::WIDTH => 600, self::HEIGHT => 300],
                    self::VIEW_TABLET => [self::WIDTH => 600, self::HEIGHT => 300],
                    self::VIEW_THUMB => [self::WIDTH => 200, self::HEIGHT => 100],
                ];
                break;
        }

        if (empty($layout)) {
            $arr =  [
                self::VIEW_DESKTOP => [self::WIDTH => 2000, self::HEIGHT => 666],
                self::VIEW_MOBILE => [self::WIDTH => 640, self::HEIGHT => 360],
                self::VIEW_TABLET => [self::WIDTH => 1024, self::HEIGHT => 576],
            ];
        }


        if (!empty($sizeType)) {
            $arr[$sizeType]['aspectRatio'] = self::getAspectRatio($arr[$sizeType][self::WIDTH], $arr[$sizeType][self::HEIGHT]);
            return $arr[$sizeType];
        }
        foreach ($arr as $key => $val) {
            $arr[$key]['aspectRatio'] = self::getAspectRatio($arr[$key][self::WIDTH], $arr[$key][self::HEIGHT]);
        }

        $arr['aspectRatio'] = self::getAspectRatio($arr[self::VIEW_DESKTOP][self::WIDTH], $arr[self::VIEW_DESKTOP][self::HEIGHT]);
        return $arr;
    }

    public static function getPushNotification(string $sizeType = ''): array
    {
        $arr =  [
            self::VIEW_DEFAULT => [self::WIDTH => 1000, self::HEIGHT => 563]
        ];

        return self::returnData($arr, self::VIEW_DEFAULT, $sizeType);
    }

    public static function getPaymentPageLogo(string $sizeType = ''): array
    {
        $arr =  [
            self::VIEW_THUMB => [self::WIDTH => 100, self::HEIGHT => 100],
            self::VIEW_DEFAULT => [self::WIDTH => 100, self::HEIGHT => 100]
        ];

        return self::returnData($arr, self::VIEW_DEFAULT, $sizeType);
    }

    public static function getAspectRatio(int $width, int $height)
    {
        $greatestCommonDivisor = static function ($width, $height) use (&$greatestCommonDivisor) {
            return ($width % $height) ? $greatestCommonDivisor($height, $width % $height) : $height;
        };

        $divisor = $greatestCommonDivisor($width, $height);
        return $width / $divisor . ':' . $height / $divisor;
    }

    public static function getUploadRatio()
    {
        return $arr =  [
            '1' => '1:1',
            '2' => '16:9'
        ];
    }

    public static function getScreenSizes($type)
    {
        $sizetypes = self::getData($type);

        return  [
            self::VIEW_DESKTOP => $sizetypes[self::VIEW_DESKTOP],
            self::VIEW_MOBILE => $sizetypes[self::VIEW_MOBILE],
            self::VIEW_TABLET => $sizetypes[self::VIEW_TABLET]
        ];
    }

    private static function formatString($sizeType)
    {
        $sizeType = strtoupper($sizeType);
        if (substr($sizeType, 0, 4) == 'WEBP') {
            $sizeType = substr($sizeType, 4);
        }
        return $sizeType;
    }

    private static function returnData($arr, $defaultMode, $sizeType = '')
    {
        if (empty($sizeType)) {
            return $arr;
        }

        $sizeType = self::formatString($sizeType);

        if (array_key_exists($sizeType, $arr)) {
            return $arr[$sizeType];
        }

        return $arr[$defaultMode];
    }

    public static function getPictureTagMedia($key = '')
    {
        $arr = [
            self::VIEW_MOBILE => ['key' => 'max-width', 'value' => 576],
            self::VIEW_TABLET => ['key' => 'max-width', 'value' => 1199],
            self::VIEW_DESKTOP => ['key' => 'min-width', 'value' => 1200]
        ];

        if (empty($key)) {
            return $arr;
        }

        if (!array_key_exists($key, $arr)) {
            return $arr[self::VIEW_DESKTOP];
        }

        return $arr[$key];
    }
}
