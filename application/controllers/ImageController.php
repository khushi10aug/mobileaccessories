<?php

class ImageController extends FatController
{
    public function __construct()
    {
        CommonHelper::initCommonVariables();
    }

    public function user($recordId, $sizeType = '', $cropedImage = 0, $afile_id = 0)
    {
        $default_image = 'user_deafult_image.jpg';
        $recordId = FatUtility::int($recordId);
        $afile_id = FatUtility::int($afile_id);
        $cropedImage = FatUtility::int($cropedImage);

        $fileType = ($cropedImage) ? AttachedFile::FILETYPE_USER_PROFILE_CROPED_IMAGE : AttachedFile::FILETYPE_USER_PROFILE_IMAGE;

        if ($afile_id > 0) {
            $res = AttachedFile::getAttributesById($afile_id);
            if (!false == $res && $res['afile_type'] == $fileType) {
                $file_row = $res;
            }
        } else {
            $file_row = AttachedFile::getAttachment($fileType, $recordId);
            if ($cropedImage && $file_row == false) {
                $file_row = AttachedFile::getAttachment(AttachedFile::FILETYPE_USER_PROFILE_IMAGE, $recordId);
            }
        }

        $image_name = (isset($file_row['afile_physical_path']) && 0 < $file_row['afile_id']) ? $file_row['afile_physical_path'] : '';
        $image_name = AttachedFile::setNamePrefix($image_name, $sizeType);

        $imageDimensions = ImageDimension::getData(ImageDimension::TYPE_USER, $sizeType);

        if ($sizeType) {
            AttachedFile::displayImage($image_name, $imageDimensions['width'], $imageDimensions['height'], $default_image);
        } else {
            AttachedFile::displayOriginalImage($image_name, $default_image);
        }
    }

    public function customProduct($recordId, $sizeType, $afile_id = 0, $lang_id = 0, $fileType = 0)
    {
        $default_image = 'product_default_image.jpg';
        $recordId = FatUtility::int($recordId);
        $afile_id = FatUtility::int($afile_id);
        $lang_id = FatUtility::int($lang_id);

        $file_row = false;

        $objectName = 'AttachedFile';
        if ($fileType == $objectName::FILETYPE_CUSTOM_PRODUCT_IMAGE_TEMP) {
            $objectName = 'AttachedFileTemp';
        } else {
            $fileType =  $objectName::FILETYPE_CUSTOM_PRODUCT_IMAGE;
        }

        if ($afile_id > 0) {
            $res = $objectName::getAttributesById($afile_id);
            if (!false == $res && $res['afile_type'] == $fileType) {
                $file_row = $res;
            }
        }

        if ($file_row == false) {
            $file_row = $objectName::getAttachment($fileType, $recordId, 0, $lang_id);
        }
        $image_name = ((isset($file_row['afile_physical_path']) && 0 < $file_row['afile_id']) && !empty($file_row['afile_physical_path'])) ? AttachedFile::FILETYPE_PRODUCT_IMAGE_PATH . $file_row['afile_physical_path'] : '';
        $image_name = AttachedFile::setNamePrefix($image_name, $sizeType);
        $imageDimensions = ImageDimension::getData(ImageDimension::TYPE_PRODUCTS, $sizeType);

        if ($sizeType) {
            AttachedFile::displayImage($image_name, $imageDimensions['width'], $imageDimensions['height'], $default_image);
        } else {
            AttachedFile::displayImage($image_name, $imageDimensions[ImageDimension::VIEW_DEFAULT]['width'], $imageDimensions[ImageDimension::VIEW_DEFAULT]['height'], $default_image);
        }
    }

    /*
    function product(){}
    ARG1-> $recordId -> required, (product_id) if passed only then will fetch default single main image
    ARG2-> $sizeType -> required, (SMALL, LARGE, THUMB) etc if passed then show image as per requested Size.
    ARG3-> $selprod_id -> selprod_id, optional, if passed, will show option value specific image if uploaded, caluclated by itself,
    ARG4-> $afile_id -> optional, if passed, will fetch direct file, but care, recordId and sizeType needs to passed, and pass selprod_id = 0
    */
    public function product($recordId, $sizeType, $selprod_id = 0, $afile_id = 0, $lang_id = 0, $fileType = 0)
    {
        $default_image = 'product_default_image.jpg';
        $recordId = FatUtility::int($recordId);
        $afile_id = FatUtility::int($afile_id);
        $selprod_id = FatUtility::int($selprod_id);
        $lang_id = FatUtility::int($lang_id);

        /* code to fetch color specific images for a single product, and varies according to option value id, E.g: Color: White, Black, Grey[ */
        if ($selprod_id) {
            $srch = SellerProduct::getSearchObject();
            $srch->doNotCalculateRecords();
            $srch->joinTable(SellerProduct::DB_TBL_SELLER_PROD_OPTIONS, 'INNER JOIN', 'selprod_id = selprodoption_selprod_id and selprodoption_selprod_id = ' . $selprod_id, 'tspo');
            $srch->joinTable(OptionValue::DB_TBL, 'INNER JOIN', 'tspo.selprodoption_optionvalue_id = opval.optionvalue_id', 'opval');
            $srch->joinTable(Option::DB_TBL, 'INNER JOIN', 'opval.optionvalue_option_id = op.option_id', 'op');
            $srch->joinTable(AttachedFile::DB_TBL, 'INNER JOIN', 'sp.selprod_product_id = af.afile_record_id AND af.afile_record_subid =  tspo.selprodoption_optionvalue_id', 'af');
            $srch->addCondition('selprod_id', '=', $selprod_id);
            $srch->addCondition('af.afile_type', '=', AttachedFile::FILETYPE_PRODUCT_IMAGE);
            $srch->addOrder('af.afile_display_order');

            /* if( $lang_id > 0 ){ */
            $cnd = $srch->addCondition('af.afile_lang_id', '=', $lang_id);
            $cnd->attachCondition('af.afile_lang_id', '=', 0);
            $srch->addOrder('af.afile_lang_id');
            /* } */

            $srch->addDirectCondition('selprodoption_selprod_id IS NOT NULL', 'AND');
            $srch->addDirectCondition('af.afile_id IS NOT NULL', 'AND');
            $srch->setPageNumber(1);
            $srch->setPageSize(1);
            /* $srch->addMultipleFields(array('selprod_id', 'selprod_product_id', 'selprodoption_option_id', 'afile_id', 'afile_record_id', 'afile_record_subid')); */
            $srch->addMultipleFields(array('afile_id', 'afile_record_id', 'afile_record_subid'));
            $rs = $srch->getResultSet();
            $row = FatApp::getDb()->fetch($rs);
            /* CommonHelper::printArray($row); die(); */
        }
        /* ] */
        
        $objectName = 'AttachedFile';
        if ($fileType == $objectName::FILETYPE_PRODUCT_IMAGE_TEMP) {
            $objectName = 'AttachedFileTemp';
        } else {
            $fileType =  $objectName::FILETYPE_PRODUCT_IMAGE;
        }
        
        $file_row = false;
        if ($selprod_id && $row) {
            $file_row = $objectName::getAttachment($fileType, $row['afile_record_id'], $row['afile_record_subid'], $lang_id);
        } elseif ($afile_id > 0) {
            $res = $objectName::getAttributesById($afile_id);
            if (!false == $res && $res['afile_type'] == $fileType) {
                $file_row = $res;
            }
        }
        
        if ($file_row == false) { 
            $file_row = $objectName::getAttachment($fileType, $recordId, -1, $lang_id);
        }

        $image_name = ((isset($file_row['afile_physical_path']) && 0 < $file_row['afile_id']) && !empty($file_row['afile_physical_path'])) ? $objectName::FILETYPE_PRODUCT_IMAGE_PATH . $file_row['afile_physical_path'] : '';
        $image_name = $objectName::setNamePrefix($image_name, $sizeType);
        $imageDimensions = ImageDimension::getData(ImageDimension::TYPE_PRODUCTS, $sizeType);
        $apply_watermark  = $imageDimensions['width'] > 400 || $imageDimensions['width'] > 400;
        
        if ($sizeType) {
            $objectName::displayImage($image_name, $imageDimensions['width'], $imageDimensions['height'], $default_image, '', ImageResize::IMG_RESIZE_EXTRA_ADDSPACE, $apply_watermark);
        } else {
            $objectName::displayImage($image_name, $imageDimensions[ImageDimension::VIEW_DEFAULT]['width'], $imageDimensions[ImageDimension::VIEW_DEFAULT]['height'], $default_image, '', ImageResize::IMG_RESIZE_EXTRA_ADDSPACE, $apply_watermark);
        }
    }

    public function shopLogo($recordId, $lang_id = 0, $sizeType = '', $afile_id = 0, $displayUniversalImage = true)
    {
        $default_image = 'product_default_image.jpg';

        $recordId = FatUtility::int($recordId);
        $afile_id = FatUtility::int($afile_id);
        $lang_id = FatUtility::int($lang_id);

        if ($afile_id > 0) {
            $res = AttachedFile::getAttributesById($afile_id);
            if (!false == $res && $res['afile_type'] == AttachedFile::FILETYPE_SHOP_LOGO) {
                $file_row = $res;
            }
        } else {
            $file_row = AttachedFile::getAttachment(AttachedFile::FILETYPE_SHOP_LOGO, $recordId, 0, $lang_id, $displayUniversalImage);
        }

        $image_name = (isset($file_row['afile_physical_path']) && 0 < $file_row['afile_id']) ? $file_row['afile_physical_path'] : '';
        $image_name = AttachedFile::setNamePrefix($image_name, $sizeType);
        $aspectRatioType = $file_row['afile_aspect_ratio'];
        $aspectRatioType = ($aspectRatioType > 0) ? $aspectRatioType : 1;

        $imageDimensions = ImageDimension::getData(ImageDimension::TYPE_SHOP_LOGO, $sizeType, $aspectRatioType);

        if ($sizeType) {
            AttachedFile::displayImage($image_name, $imageDimensions['width'], $imageDimensions['height'], $default_image);
        } else {
            AttachedFile::displayOriginalImage($image_name, $default_image);
        }
    }

    public function shopBanner($recordId, $lang_id = 0, $sizeType = '', $afile_id = 0, $screen = 0)
    {
        $default_image = 'banner-default-image.png';

        $recordId = FatUtility::int($recordId);
        $afile_id = FatUtility::int($afile_id);
        $lang_id = FatUtility::int($lang_id);

        if ($afile_id > 0) {
            $file_row = AttachedFile::getAttributesById($afile_id);
            if (false == $file_row || (!false == $file_row && $file_row['afile_type'] != AttachedFile::FILETYPE_SHOP_BANNER)) {
                return;
            }
        } else {
            $file_row = AttachedFile::getAttachment(AttachedFile::FILETYPE_SHOP_BANNER, $recordId, 0, $lang_id, true, $screen);
        }

        $image_name = (isset($file_row['afile_physical_path']) && 0 < $file_row['afile_id']) ? $file_row['afile_physical_path'] : '';
        $image_name = AttachedFile::setNamePrefix($image_name, $sizeType);
        $imageDimensions = ImageDimension::getData(ImageDimension::TYPE_SHOP_BANNER, $sizeType);

        if ($sizeType) {
            AttachedFile::displayImage($image_name, $imageDimensions['width'], $imageDimensions['height'], $default_image);
        } else {
            AttachedFile::displayOriginalImage($image_name, $default_image);
        }
    }

    public function promotionMedia($recordId, $lang_id = 0, $sizeType = '', $afile_id = 0)
    {
        $default_image = 'product_default_image.jpg';

        $recordId = FatUtility::int($recordId);
        $afile_id = FatUtility::int($afile_id);
        $lang_id = FatUtility::int($lang_id);

        if ($afile_id > 0) {
            $file_row = AttachedFile::getAttributesById($afile_id);
            if (false == $file_row || (!false == $file_row && $file_row['afile_type'] != AttachedFile::FILETYPE_PROMOTION_MEDIA)) {
                return;
            }
        } else {
            $file_row = AttachedFile::getAttachment(AttachedFile::FILETYPE_PROMOTION_MEDIA, $recordId, 0, $lang_id);
        }

        $image_name = (isset($file_row['afile_physical_path']) && 0 < $file_row['afile_id']) ? $file_row['afile_physical_path'] : '';
        $image_name = AttachedFile::setNamePrefix($image_name, $sizeType);

        $imageDimensions = ImageDimension::getData(ImageDimension::TYPE_PROMOTION_MEDIA, $sizeType);

        if ($sizeType) {

            AttachedFile::displayImage($image_name, $imageDimensions['width'], $imageDimensions['height'], $default_image);
        } else {
            AttachedFile::displayImage($image_name, $imageDimensions[ImageDimension::VIEW_DEFAULT]['width'], $imageDimensions[ImageDimension::VIEW_DEFAULT]['height'], $default_image);
        }
    }

    public function brandReal($recordId, $langId = 0, $sizeType = '', $afile_id = 0)
    {
        $this->displayBrandLogo($recordId, $langId, $sizeType, $afile_id, false);
    }

    public function brand($recordId, $langId = 0, $sizeType = '', $afile_id = 0)
    {
        $this->displayBrandLogo($recordId, $langId, $sizeType, $afile_id);
    }

    public function brandImage($recordId, $langId = 0, $sizeType = '', $afile_id = 0, $slide_screen = 0)
    {
        $this->displayBrandImage($recordId, $langId, $sizeType, $afile_id, $slide_screen);
    }

    public function displayBrandLogo($recordId, $langId = 0, $sizeType = '', $afile_id = 0, $displayUniversalImage = true)
    {
        $default_image = 'brand_deafult_image.jpg';
        $recordId = FatUtility::int($recordId);
        $afile_id = FatUtility::int($afile_id);
        $langId = FatUtility::int($langId);

        if ($afile_id > 0) {
            $res = AttachedFile::getAttributesById($afile_id);
            if (!false == $res && $res['afile_type'] == AttachedFile::FILETYPE_BRAND_LOGO) {
                $file_row = $res;
            }
        } else {
            $file_row = AttachedFile::getAttachment(AttachedFile::FILETYPE_BRAND_LOGO, $recordId, 0, $langId, $displayUniversalImage);
        }
        $image_name = (isset($file_row['afile_physical_path']) && 0 < $file_row['afile_id']) ? $file_row['afile_physical_path'] : '';
        $image_name = AttachedFile::setNamePrefix($image_name, $sizeType);
        $aspectRatioType = $file_row['afile_aspect_ratio'];
        $aspectRatioType = ($aspectRatioType > 0) ? $aspectRatioType : 1;

        $imageDimensions = ImageDimension::getData(ImageDimension::TYPE_BRAND_LOGO, $sizeType, $aspectRatioType);

        if ($sizeType) {
            AttachedFile::displayImage($image_name, $imageDimensions['width'], $imageDimensions['height'], $default_image);
        } else {
            AttachedFile::displayOriginalImage($image_name, $default_image);
        }
    }

    public function displayBrandImage($recordId, $langId = 0, $sizeType = '', $afile_id = 0, $screen = 0, $displayUniversalImage = true)
    {
        $default_image = 'brand_deafult_banner_image.jpg';
        $recordId = FatUtility::int($recordId);
        $afile_id = FatUtility::int($afile_id);
        $langId = FatUtility::int($langId);

        if ($afile_id > 0) {
            $res = AttachedFile::getAttributesById($afile_id);
            if (!false == $res && $res['afile_type'] == AttachedFile::FILETYPE_BRAND_IMAGE) {
                $file_row = $res;
            }
        } else {
            $file_row = AttachedFile::getAttachment(AttachedFile::FILETYPE_BRAND_IMAGE, $recordId, 0, $langId, $displayUniversalImage, $screen);
        }
        $image_name = (isset($file_row['afile_physical_path']) && 0 < $file_row['afile_id']) ? $file_row['afile_physical_path'] : '';
        $image_name = AttachedFile::setNamePrefix($image_name, $sizeType);

        $imageDimensions = ImageDimension::getData(ImageDimension::TYPE_BRAND_IMAGE, $sizeType);

        if ($sizeType && $sizeType != ImageDimension::VIEW_COLLECTION_PAGE) {
            AttachedFile::displayImage($image_name, $imageDimensions['width'], $imageDimensions['height'], $default_image);
        } else {
            AttachedFile::displayOriginalImage($image_name, $default_image);
        }
    }

    /* 
    * All Payment methods moved to plugins section.
    */
    public function paymentMethod($recordId, $sizeType = '', $afile_id = 0)
    {
        $default_image = 'product_default_image.jpg';

        $recordId = FatUtility::int($recordId);
        $afile_id = FatUtility::int($afile_id);

        if ($afile_id > 0) {
            $file_row = AttachedFile::getAttributesById($afile_id);
            // if (false == $file_row || (!false == $file_row && $file_row['afile_type'] != AttachedFile::FILETYPE_PAYMENT_METHOD)) {
            if (false == $file_row || (!false == $file_row && $file_row['afile_type'] != AttachedFile::FILETYPE_PLUGIN_LOGO)) {
                return;
            }
        } else {
            // $file_row = AttachedFile::getAttachment(AttachedFile::FILETYPE_PAYMENT_METHOD, $recordId);
            $file_row = AttachedFile::getAttachment(AttachedFile::FILETYPE_PLUGIN_LOGO, $recordId);
        }

        $image_name = (isset($file_row['afile_physical_path']) && 0 < $file_row['afile_id']) ? $file_row['afile_physical_path'] : '';
        $image_name = AttachedFile::setNamePrefix($image_name, $sizeType);
        switch (strtoupper($sizeType)) {
            case 'ICON':
                $w = 30;
                $h = 30;
                AttachedFile::displayImage($image_name, $w, $h, $default_image);
                break;
            case 'MINITHUMB':
                $w = 61;
                $h = 61;
                AttachedFile::displayImage($image_name, $w, $h, $default_image);
                break;
            case 'THUMB':
                $w = 100;
                $h = 100;
                AttachedFile::displayImage($image_name, $w, $h, $default_image);
                break;
            case 'SMALL':
                $w = 200;
                $h = 200;
                AttachedFile::displayImage($image_name, $w, $h, $default_image);
                break;
            case 'MEDIUM':
                $w = 250;
                $h = 250;
                AttachedFile::displayImage($image_name, $w, $h, $default_image);
                break;
            default:
                $h = 400;
                $w = 400;
                AttachedFile::displayImage($image_name, $w, $h, $default_image);
                break;
        }
    }

    public function shopLayout($recordId, $sizeType = '')
    {
        $default_image = 'product_default_image.jpg';

        $recordId = FatUtility::int($recordId);
        $filePath = LayoutTemplate::LAYOUTTYPE_SHOP_IMAGE_PATH;
        switch (strtoupper($sizeType)) {
            case 'THUMB':
                $w = 200;
                $h = 200;
                AttachedFile::displayImage($recordId, $w, $h, $default_image, $filePath);
                break;
            case 'SMALL':
                $w = 250;
                $h = 250;
                AttachedFile::displayImage($recordId, $w, $h, $default_image, $filePath);
                break;
            default:
                $h = 400;
                $w = 400;
                AttachedFile::displayImage($recordId, $w, $h, $default_image, $filePath);
                break;
        }
    }

    public function siteLogo($lang_id = 0, $sizeType = '')
    {
        $lang_id = FatUtility::int($lang_id);
        $recordId = 0;
        $file_row = AttachedFile::getAttachment(AttachedFile::FILETYPE_FRONT_LOGO, $recordId, 0, $lang_id, false);
        $image_name = (isset($file_row['afile_physical_path']) && 0 < $file_row['afile_id']) ? $file_row['afile_physical_path'] : '';
        $default_image = 'logo_default-red.svg';
        $image_name = AttachedFile::setNamePrefix($image_name, $sizeType);
        switch (strtoupper($sizeType)) {
            case 'THUMB':
                $w = ($file_row['afile_aspect_ratio'] == AttachedFile::RATIO_TYPE_SQUARE  ? 100 : 120);
                $h = ($file_row['afile_aspect_ratio'] == AttachedFile::RATIO_TYPE_SQUARE  ? 100 : 68);
                AttachedFile::displayImage($image_name, $w, $h, $default_image, '', ImageResize::IMG_RESIZE_RESET_DIMENSIONS);
                break;
            default:
                $w = ($file_row['afile_aspect_ratio'] == AttachedFile::RATIO_TYPE_SQUARE  ? 60 : 120);
                $h = ($file_row['afile_aspect_ratio'] == AttachedFile::RATIO_TYPE_SQUARE  ? 60 : 68);
                AttachedFile::displayImage($image_name, $w, $h, $default_image, '', ImageResize::IMG_RESIZE_RESET_DIMENSIONS);
                // AttachedFile::displayOriginalImage($image_name, $default_image);
                break;
        }
    }

    public function emailLogo($lang_id = 0, $sizeType = '')
    {
        $lang_id = FatUtility::int($lang_id);
        $recordId = 0;
        $file_row = AttachedFile::getAttachment(AttachedFile::FILETYPE_EMAIL_LOGO, $recordId, 0, $lang_id);
        $image_name = (isset($file_row['afile_physical_path']) && 0 < $file_row['afile_id']) ? $file_row['afile_physical_path'] : '';
        $default_image = 'no_image.jpg';
        $image_name = AttachedFile::setNamePrefix($image_name, $sizeType);
        $default_image = AttachedFile::setNamePrefix($default_image, $sizeType);

        $aspectRatioType = $file_row['afile_aspect_ratio'];
        $aspectRatioType = ($aspectRatioType > 0) ? $aspectRatioType : 1;

        $imageDimensions = ImageDimension::getData(ImageDimension::TYPE_EMAIL_LOGO, $sizeType, $aspectRatioType);

        if ($sizeType) {
            AttachedFile::displayImage($image_name, $imageDimensions['width'], $imageDimensions['height'], $default_image);
        } else {
            AttachedFile::displayImage($image_name, $imageDimensions[ImageDimension::VIEW_DEFAULT]['width'], $imageDimensions[ImageDimension::VIEW_DEFAULT]['height'],  $default_image);
        }
    }

    public function socialFeed($lang_id = 0, $sizeType = '')
    {
        $lang_id = FatUtility::int($lang_id);
        $recordId = 0;
        $file_row = AttachedFile::getAttachment(AttachedFile::FILETYPE_SOCIAL_FEED_IMAGE, $recordId, 0, $lang_id);
        $image_name = (isset($file_row['afile_physical_path']) && 0 < $file_row['afile_id']) ? $file_row['afile_physical_path'] : '';
        $default_image = 'no_image.jpg';
        $image_name = AttachedFile::setNamePrefix($image_name, $sizeType);
        $imageDimensions = ImageDimension::getData(ImageDimension::TYPE_SOCIAL_FEED, $sizeType);

        if ($sizeType) {

            AttachedFile::displayImage($image_name, $imageDimensions['width'], $imageDimensions['height'], $default_image);
        } else {
            AttachedFile::displayImage($image_name, $imageDimensions[ImageDimension::VIEW_DEFAULT]['width'], $imageDimensions[ImageDimension::VIEW_DEFAULT]['height'],  $default_image);
        }
    }

    public function paymentPageLogo($lang_id = 0, $sizeType = '')
    {
        $lang_id = FatUtility::int($lang_id);
        $recordId = 0;
        $file_row = AttachedFile::getAttachment(AttachedFile::FILETYPE_PAYMENT_PAGE_LOGO, $recordId, 0, $lang_id);
        $image_name = (isset($file_row['afile_physical_path']) && 0 < $file_row['afile_id']) ? $file_row['afile_physical_path'] : '';
        $default_image = 'no_image.jpg';
        $image_name = AttachedFile::setNamePrefix($image_name, $sizeType);

        $imageDimensions = ImageDimension::getPaymentPageLogo($sizeType);

        if ($sizeType) {
            AttachedFile::displayImage($image_name, $imageDimensions['width'], $imageDimensions['height'], $default_image);
        } else {
            AttachedFile::displayImage($image_name, $imageDimensions[ImageDimension::VIEW_DEFAULT]['width'], $imageDimensions[ImageDimension::VIEW_DEFAULT]['height'],  $default_image);
        }
    }

    public function watermarkImage($lang_id = 0, $sizeType = '')
    {
        $lang_id = FatUtility::int($lang_id);
        $recordId = 0;
        $file_row = AttachedFile::getAttachment(AttachedFile::FILETYPE_WATERMARK_IMAGE, $recordId, 0, $lang_id);
        $image_name = (isset($file_row['afile_physical_path']) && 0 < $file_row['afile_id']) ? $file_row['afile_physical_path'] : '';
        $default_image = 'no_image.jpg';
        $image_name = AttachedFile::setNamePrefix($image_name, $sizeType);

        $imageDimensions = ImageDimension::getData(ImageDimension::TYPE_WATERMARK, $sizeType);

        if ($sizeType) {

            AttachedFile::displayImage($image_name, $imageDimensions['width'], $imageDimensions['height'], $default_image);
        } else {

            AttachedFile::displayOriginalImage($image_name, $default_image);
        }
    }

    public function appleTouchIcon($lang_id = 0, $sizeType = '')
    {
        $lang_id = FatUtility::int($lang_id);
        $recordId = 0;
        $file_row = AttachedFile::getAttachment(AttachedFile::FILETYPE_APPLE_TOUCH_ICON, $recordId, 0, $lang_id);

        $image_name = (isset($file_row['afile_physical_path']) && 0 < $file_row['afile_id']) ? $file_row['afile_physical_path'] : '';
        $default_image = 'brand_deafult_image.jpg';

        $image_name = AttachedFile::setNamePrefix($image_name, $sizeType);

        $imageDimensions = ImageDimension::getData(ImageDimension::TYPE_APPLE_TOUCH_ICON, $sizeType);
        if ($sizeType) {
            AttachedFile::displayImage($image_name, $imageDimensions['width'], $imageDimensions['height'], $default_image);
        } else {
            AttachedFile::displayOriginalImage($image_name, $default_image);
        }
    }

    public function mobileLogo($lang_id = 0, $sizeType = '')
    {
        $lang_id = FatUtility::int($lang_id);
        $recordId = 0;
        $file_row = AttachedFile::getAttachment(AttachedFile::FILETYPE_MOBILE_LOGO, $recordId, 0, $lang_id);
        $image_name = (isset($file_row['afile_physical_path']) && 0 < $file_row['afile_id']) ? $file_row['afile_physical_path'] : '';
        $default_image = 'no_image.jpg';
        $image_name = AttachedFile::setNamePrefix($image_name, $sizeType);

        $imageDimensions = ImageDimension::getData(ImageDimension::TYPE_MOBILE_LOGO, $sizeType);

        if ($sizeType) {
            AttachedFile::displayImage($image_name, $imageDimensions['width'], $imageDimensions['height'], $default_image);
        } else {
            AttachedFile::displayImage($image_name, $imageDimensions[ImageDimension::VIEW_DEFAULT]['width'], $imageDimensions[ImageDimension::VIEW_DEFAULT]['height'], $default_image);
        }
    }

    public function invoiceLogo($lang_id = 0, $sizeType = '')
    {
        $lang_id = FatUtility::int($lang_id);
        $recordId = 0;
        $file_row = AttachedFile::getAttachment(AttachedFile::FILETYPE_INVOICE_LOGO, $recordId, 0, $lang_id);
        $image_name = (isset($file_row['afile_physical_path']) && 0 < $file_row['afile_id']) ? $file_row['afile_physical_path'] : '';
        $default_image = 'no_image.jpg';
        $image_name = AttachedFile::setNamePrefix($image_name, $sizeType);
        $imageDimensions = ImageDimension::getData(ImageDimension::TYPE_INVOICE_LOGO, $sizeType);

        if ($sizeType) {
            AttachedFile::displayImage($image_name, $imageDimensions['width'], $imageDimensions['height'], $default_image);
        } else {
            AttachedFile::displayImage($image_name, $imageDimensions[ImageDimension::VIEW_DEFAULT]['width'], $imageDimensions[ImageDimension::VIEW_DEFAULT]['height'], $default_image);
        }
    }

    public function CategoryCollectionBgImage($langId = 0, $sizeType = '')
    {
        $recordId = 0;
        $file_row = AttachedFile::getAttachment(AttachedFile::FILETYPE_CATEGORY_COLLECTION_BG_IMAGE, $recordId, 0, $langId);
        $image_name = (isset($file_row['afile_physical_path']) && 0 < $file_row['afile_id']) ? $file_row['afile_physical_path'] : '';
        $default_image = 'no_image.jpg';
        $image_name = AttachedFile::setNamePrefix($image_name, $sizeType);
        $imageDimensions = ImageDimension::getData(ImageDimension::TYPE_CATEGORY_COLLECTION_BG, $sizeType);

        if ($sizeType) {

            AttachedFile::displayImage($image_name, $imageDimensions['width'], $imageDimensions['height'], $default_image);
        } else {

            AttachedFile::displayOriginalImage($image_name, $default_image);
        }
    }

    public function coupon($coupon_id, $lang_id = 0, $sizeType = '')
    {
        $coupon_id = FatUtility::int($coupon_id);

        $file_row = AttachedFile::getAttachment(AttachedFile::FILETYPE_DISCOUNT_COUPON_IMAGE, $coupon_id, 0, $lang_id);
        $image_name = (isset($file_row['afile_physical_path']) && 0 < $file_row['afile_id']) ? $file_row['afile_physical_path'] : '';
        $default_image = 'no_image.jpg';
        $image_name = AttachedFile::setNamePrefix($image_name, $sizeType);
        $imageDimensions = ImageDimension::getData(ImageDimension::TYPE_COUPON, $sizeType);

        if ($sizeType) {
            AttachedFile::displayImage($image_name, $imageDimensions['width'], $imageDimensions['height'], $default_image);
        } else {
            AttachedFile::displayImage($image_name, $imageDimensions[ImageDimension::VIEW_DEFAULT]['width'], $imageDimensions[ImageDimension::VIEW_DEFAULT]['height'], $default_image);
        }
    }

    public function metaImage($lang_id = 0, $sizeType = ImageDimension::VIEW_DEFAULT)
    {
        $lang_id = FatUtility::int($lang_id);
        $file_row = AttachedFile::getAttachment(AttachedFile::FILETYPE_META_IMAGE, 0, 0, $lang_id);
        $image_name = (isset($file_row['afile_physical_path']) && 0 < $file_row['afile_id']) ? $file_row['afile_physical_path'] : '';
        $default_image = 'no_image.jpg';
        $image_name = AttachedFile::setNamePrefix($image_name, $sizeType);
        $imageDimensions = ImageDimension::getData(ImageDimension::TYPE_META, $sizeType);

        if ($sizeType) {
            AttachedFile::displayImage($image_name, $imageDimensions['width'], $imageDimensions['height'], $default_image);
        } else {
            AttachedFile::displayImage($image_name, $imageDimensions[ImageDimension::VIEW_DEFAULT]['width'], $imageDimensions[ImageDimension::VIEW_DEFAULT]['height'], $default_image);
        }
    }

    public function firstPurchaseCoupon($lang_id = 0, $sizeType = '')
    {
        $lang_id = FatUtility::int($lang_id);
        $file_row = AttachedFile::getAttachment(AttachedFile::FILETYPE_FIRST_PURCHASE_DISCOUNT_IMAGE, 0, 0, $lang_id);
        $image_name = (isset($file_row['afile_physical_path']) && 0 < $file_row['afile_id']) ? $file_row['afile_physical_path'] : '';
        $default_image = 'no_image.jpg';
        $image_name = AttachedFile::setNamePrefix($image_name, $sizeType);
        $imageDimensions = ImageDimension::getData(ImageDimension::TYPE_FIRST_PURCHASE_COUPON, $sizeType);

        if ($sizeType) {

            AttachedFile::displayImage($image_name, $imageDimensions['width'], $imageDimensions['height'], $default_image);
        } else {

            AttachedFile::displayImage($image_name, $imageDimensions[ImageDimension::VIEW_DEFAULT]['width'], $imageDimensions[ImageDimension::VIEW_DEFAULT]['height'], $default_image);
        }
    }

    public function favicon($lang_id = 0, $sizeType = '')
    {
        $lang_id = FatUtility::int($lang_id);
        $recordId = 0;
        $file_row = AttachedFile::getAttachment(AttachedFile::FILETYPE_FAVICON, $recordId, 0, $lang_id);
        $image_name = (isset($file_row['afile_physical_path']) && 0 < $file_row['afile_id']) ? $file_row['afile_physical_path'] : '';
        $default_image = 'no_image.jpg';
        $image_name = AttachedFile::setNamePrefix($image_name, $sizeType);
        $imageDimensions = ImageDimension::getData(ImageDimension::TYPE_FEVICON, $sizeType);

        if ($sizeType) {

            AttachedFile::displayImage($image_name, $imageDimensions['width'], $imageDimensions['height'], $default_image);
        } else {

            AttachedFile::displayOriginalImage($image_name, $default_image);
        }
    }

    public function slide($slide_id, $screen = 0, $lang_id = 0, $sizeType = '', $displayUniversalImage = true)
    {
        $default_image = 'hero_deafult_image.jpg';
        $slide_id = FatUtility::int($slide_id);

        $file_row = AttachedFile::getAttachment(AttachedFile::FILETYPE_HOME_PAGE_BANNER, $slide_id, 0, $lang_id, $displayUniversalImage, $screen);
        $image_name = (isset($file_row['afile_physical_path']) && 0 < $file_row['afile_id']) ? $file_row['afile_physical_path'] : '';
        $image_name = AttachedFile::setNamePrefix($image_name, $sizeType);

        $imageDimensions = ImageDimension::getSlideData($sizeType);

        if ($sizeType) {
            AttachedFile::displayImage($image_name, $imageDimensions['width'], $imageDimensions['height'], $default_image, '', ImageResize::IMG_RESIZE_EXTRA_ADDSPACE, false, true, false);
        } else {
            AttachedFile::displayOriginalImage($image_name, $default_image);
        }
        exit;
    }


    public function banner($banner_id, $collectionLayoutType, $lang_id, $screen = 0,  $sizeType = '', $displayUniversalImage = true)
    {

        $default_image = 'banner-default-image.png';
        $banner_id = FatUtility::int($banner_id);
        $sizeType = strtoupper($sizeType);
        $file_row = AttachedFile::getAttachment(AttachedFile::FILETYPE_BANNER, $banner_id, 0, $lang_id, $displayUniversalImage, $screen);
        $image_name = (isset($file_row['afile_physical_path']) && 0 < $file_row['afile_id']) ? $file_row['afile_physical_path'] : '';
        $image_name = AttachedFile::setNamePrefix($image_name, $sizeType);

        $imageDimensions = ImageDimension::getBannerData($sizeType, $collectionLayoutType);

        if ($sizeType) {
            AttachedFile::displayImage($image_name, $imageDimensions['width'], $imageDimensions['height'], $default_image);
        } else {
            AttachedFile::displayOriginalImage($image_name, $default_image);
        }
    }

    public function SocialPlatform($splatform_id, $sizeType = '')
    {
        $default_image = 'social_default_image.jpg';
        $splatform_id = FatUtility::int($splatform_id);

        $file_row = AttachedFile::getAttachment(AttachedFile::FILETYPE_SOCIAL_PLATFORM_IMAGE, $splatform_id);
        $image_name = (isset($file_row['afile_physical_path']) && 0 < $file_row['afile_id']) ? $file_row['afile_physical_path'] : '';
        $image_name = AttachedFile::setNamePrefix($image_name, $sizeType);

        $imageDimensions = ImageDimension::getData(ImageDimension::TYPE_SOCIAL_PLATFORM, $sizeType);

        if ($sizeType) {
            AttachedFile::displayImage($image_name, $imageDimensions['width'], $imageDimensions['height'], $default_image);
        } else {
            AttachedFile::displayImage($image_name, $imageDimensions[ImageDimension::VIEW_DEFAULT]['width'], $imageDimensions[ImageDimension::VIEW_DEFAULT]['height'], $default_image);
        }
    }


    public function collectionReal($recordId, $langId = 0, $sizeType = '', $fileType = '', $screen = 0, $collection_layout_type = 0)
    {
        $this->displayCollectionImage($recordId, $langId, $sizeType, true, $fileType, $screen, $collection_layout_type);
    }

    public function collection($recordId, $langId = 0, $sizeType = '')
    {
        $this->displayCollectionImage($recordId, $langId, $sizeType);
    }

    public function displayCollectionImage($collectionId, $langId = 0, $sizeType = '', $displayUniversalImage = true, $fileType = '', $screen = 0, $collection_layout_type = 0)
    {
        $collectionId = FatUtility::int($collectionId);
        $fileType = empty($fileType) ? AttachedFile::FILETYPE_COLLECTION_IMAGE : $fileType;
        //$file_row = AttachedFile::getAttachment( AttachedFile::FILETYPE_COLLECTION_IMAGE, $collectionId );
        $file_row = AttachedFile::getAttachment($fileType, $collectionId, 0, $langId, $displayUniversalImage, $screen);
        $image_name = (isset($file_row['afile_physical_path']) && 0 < $file_row['afile_id']) ? $file_row['afile_physical_path'] : '';
        $image_name = AttachedFile::setNamePrefix($image_name, $sizeType);

        $default_image = 'banner-default-image.png';

        $imageDimensions = ImageDimension::getData(ImageDimension::TYPE_DISPLAY_COLLECTION_IMAGE, $sizeType, layout: $collection_layout_type);

        if ($sizeType) {
            AttachedFile::displayImage($image_name, $imageDimensions['width'], $imageDimensions['height'], $default_image);
        } else {

            AttachedFile::displayOriginalImage($image_name, $default_image);
        }
    }

    public function collectionBgReal($recordId, $langId = 0, $sizeType = '')
    {
        $this->displayCollectionBgImage($recordId, $langId, $sizeType, false);
    }

    public function collectionBg($recordId, $langId = 0, $sizeType = '')
    {
        $this->displayCollectionBgImage($recordId, $langId, $sizeType);
    }

    public function displayCollectionBgImage($collectionId, $langId = 0, $sizeType = '', $displayUniversalImage = true)
    {
        $collectionId = FatUtility::int($collectionId);
        $file_row = AttachedFile::getAttachment(AttachedFile::FILETYPE_COLLECTION_BG_IMAGE, $collectionId, 0, $langId, $displayUniversalImage);
        $image_name = (isset($file_row['afile_physical_path']) && 0 < $file_row['afile_id']) ? $file_row['afile_physical_path'] : '';
        $image_name = AttachedFile::setNamePrefix($image_name, $sizeType);
        $imageDimensions = ImageDimension::getData(ImageDimension::TYPE_DISPLAY_COLLECTION_BG_IMAGE, $sizeType);

        $default_image = 'banner-default-image.png';
        if ($sizeType) {
            AttachedFile::displayImage($image_name, $imageDimensions['width'], $imageDimensions['height'], $default_image);
        } else {

            AttachedFile::displayOriginalImage($image_name, $default_image);
        }
    }

    public function blogPostAdmin($postId, $langId = 0, $size_type = '', $subRecordId = 0, $afile_id = 0)
    {
        $this->blogPost($postId, $langId, $size_type, $subRecordId, $afile_id, false);
    }

    public function blogPostFront($postId, $langId = 0, $size_type = '', $subRecordId = 0, $afile_id = 0)
    {
        $this->blogPost($postId, $langId, $size_type, $subRecordId, $afile_id);
    }

    public function blogPost($postId, $langId = 0, $sizeType = '', $subRecordId = 0, $afile_id = 0, $displayUniversalImage = true)
    {
        $default_image = 'blog_deafult_image.jpg';

        $langId = FatUtility::int($langId);
        $afile_id = FatUtility::int($afile_id);
        $postId = FatUtility::int($postId);
        $subRecordId = FatUtility::int($subRecordId);

        if ($afile_id > 0) {
            $res = AttachedFile::getAttributesById($afile_id);
            if (!false == $res && $res['afile_type'] == AttachedFile::FILETYPE_BLOG_POST_IMAGE) {
                $file_row = $res;
            }
        } else {
            $file_row = AttachedFile::getAttachment(AttachedFile::FILETYPE_BLOG_POST_IMAGE, $postId, $subRecordId, $langId, $displayUniversalImage);
        }

        $image_name = (isset($file_row['afile_physical_path']) && 0 < $file_row['afile_id']) ? AttachedFile::FILETYPE_BLOG_POST_IMAGE_PATH . $file_row['afile_physical_path'] : '';
        $image_name = AttachedFile::setNamePrefix($image_name, $sizeType);
        $imageDimensions = ImageDimension::getData(ImageDimension::TYPE_BLOG_POST, $sizeType);
        if ($sizeType) {
            AttachedFile::displayImage($image_name, $imageDimensions['width'], $imageDimensions['height'], $default_image);
        } else {
            AttachedFile::displayImage($image_name, $imageDimensions[ImageDimension::VIEW_DEFAULT]['width'], $imageDimensions[ImageDimension::VIEW_DEFAULT]['height'], $default_image);
        }
    }

    public function BatchProduct($prodgroup_id, $lang_id, $sizeType = '')
    {
        $prodgroup_id = FatUtility::int($prodgroup_id);
        $lang_id = FatUtility::int($lang_id);
        $default_image = 'no_image.jpg';

        $file_row = AttachedFile::getAttachment(AttachedFile::FILETYPE_BATCH_IMAGE, $prodgroup_id, 0, $lang_id);

        $image_name = (isset($file_row['afile_physical_path']) && 0 < $file_row['afile_id']) ? $file_row['afile_physical_path'] : '';
        $image_name = AttachedFile::setNamePrefix($image_name, $sizeType);
        $imageDimensions = ImageDimension::getData(ImageDimension::TYPE_BATCH_PRODUCT, $sizeType);

        if ($sizeType) {
            AttachedFile::displayImage($image_name, $imageDimensions['width'], $imageDimensions['height'], $default_image);
        } else {
            AttachedFile::displayImage($image_name, $imageDimensions[ImageDimension::VIEW_DEFAULT]['width'], $imageDimensions[ImageDimension::VIEW_DEFAULT]['height'], $default_image);
        }
    }

    public function testimonial($recordId, $langId = 0, $sizeType = '', $afile_id = 0, $displayUniversalImage = true)
    {
        $default_image = 'user_deafult_image.jpg';
        $recordId = FatUtility::int($recordId);
        $afile_id = FatUtility::int($afile_id);
        $langId = FatUtility::int($langId);

        if ($afile_id > 0) {
            $res = AttachedFile::getAttributesById($afile_id);
            if (!false == $res && $res['afile_type'] == AttachedFile::FILETYPE_TESTIMONIAL_IMAGE) {
                $file_row = $res;
            }
        } else {
            $file_row = AttachedFile::getAttachment(AttachedFile::FILETYPE_TESTIMONIAL_IMAGE, $recordId, 0, -1, $displayUniversalImage);
        }
        
        $image_name = (isset($file_row['afile_physical_path']) && 0 < $file_row['afile_id']) ? $file_row['afile_physical_path'] : '';
        $image_name = AttachedFile::setNamePrefix($image_name, $sizeType);
        $imageDimensions = ImageDimension::getData(ImageDimension::TYPE_TESTIMONIAL, $sizeType);

        if ($sizeType) {
            AttachedFile::displayImage($image_name, $imageDimensions['width'], $imageDimensions['height'], $default_image);
        } else {
            AttachedFile::displayImage($image_name, $imageDimensions[ImageDimension::VIEW_DEFAULT]['width'], $imageDimensions[ImageDimension::VIEW_DEFAULT]['height'], $default_image);
        }
    }

    public function cpageBackgroundImage($cpageId, $langId = 0, $sizeType = '')
    {
        $cpageId = FatUtility::int($cpageId);
        $langId = FatUtility::int($langId);
        $file_row = AttachedFile::getAttachment(AttachedFile::FILETYPE_CPAGE_BACKGROUND_IMAGE, $cpageId, 0, $langId);
        $image_name = (isset($file_row['afile_physical_path']) && 0 < $file_row['afile_id']) ? $file_row['afile_physical_path'] : '';
        $image_name = AttachedFile::setNamePrefix($image_name, $sizeType);
        $imageDimensions = ImageDimension::getData(ImageDimension::TYPE_CPAGE_BG, $sizeType);
        $default_image = 'seller-bg.png';

        if ($sizeType) {
            AttachedFile::displayImage($image_name, $imageDimensions['width'], $imageDimensions['height'], $default_image);
        } else {
            AttachedFile::displayOriginalImage($image_name, $default_image);
        }
    }

    public function cblockBackgroundImage($cblockId, $langId = 0, $sizeType = '', $fileType = '')
    {
        $cblockId = FatUtility::int($cblockId);
        $langId = FatUtility::int($langId);
        $file_row = AttachedFile::getAttachment($fileType, $cblockId, 0, $langId);
        $image_name = (isset($file_row['afile_physical_path']) && 0 < $file_row['afile_id']) ? $file_row['afile_physical_path'] : '';
        $image_name = AttachedFile::setNamePrefix($image_name, $sizeType);
        $default_image = 'seller-bg.png';
        $imageDimensions = ImageDimension::getData(ImageDimension::TYPE_CBLOCK_BG, $sizeType);

        if ($sizeType) {

            AttachedFile::displayImage($image_name, $imageDimensions['width'], $imageDimensions['height'], $default_image);
        } else {

            AttachedFile::displayOriginalImage($image_name, $default_image);
        }
    }

    public function shopCollectionImage($recordId, $langId = 0, $sizeType = '', $displayUniversalImage = true)
    {
        $default_image = 'shop-collections-default-image.jpg';
        $recordId = FatUtility::int($recordId);
        $langId = FatUtility::int($langId);
        $file_row = AttachedFile::getAttachment(AttachedFile::FILETYPE_SHOP_COLLECTION_IMAGE, $recordId, 0, $langId, $displayUniversalImage);
        $image_name = (isset($file_row['afile_physical_path']) && 0 < $file_row['afile_id']) ? $file_row['afile_physical_path'] : '';
        $image_name = AttachedFile::setNamePrefix($image_name, $sizeType);

        $imageDimensions = ImageDimension::getData(ImageDimension::TYPE_SHOP_COLLECTION_IMAGE, $sizeType);

        if ($sizeType) {

            AttachedFile::displayImage($image_name, $imageDimensions['width'], $imageDimensions['height'], $default_image);
        } else {

            AttachedFile::displayOriginalImage($image_name, $default_image);
        }
    }

    public function pushNotificationImage($pNotificationId, $sizeType = '')
    {
        $default_image = 'no_image.jpg';
        $file_row = AttachedFile::getAttachment(AttachedFile::FILETYPE_PUSH_NOTIFICATION_IMAGE, $pNotificationId);
        $image_name = (isset($file_row['afile_physical_path']) && 0 < $file_row['afile_id']) ? $file_row['afile_physical_path'] : '';
        $image_name = AttachedFile::setNamePrefix($image_name, $sizeType);

        AttachedFile::displayOriginalImage($image_name, $default_image);
    }

    public function plugin($recordId, $sizeType = '', $displayUniversalImage = true)
    {
        $default_image = 'product_default_image.jpg';
        $recordId = FatUtility::int($recordId);
        $file_row = AttachedFile::getAttachment(AttachedFile::FILETYPE_PLUGIN_LOGO, $recordId, 0, 0, $displayUniversalImage);
        $image_name = (isset($file_row['afile_physical_path']) && 0 < $file_row['afile_id']) ? $file_row['afile_physical_path'] : '';
        $image_name = AttachedFile::setNamePrefix($image_name, $sizeType);

        $imageDimensions = ImageDimension::getData(ImageDimension::TYPE_PLUGIN_IMAGE, $sizeType);

        if ($sizeType) {
            AttachedFile::displayImage($image_name, $imageDimensions['width'], $imageDimensions['height'], $default_image);
        } else {
            AttachedFile::displayOriginalImage($image_name, $default_image);
        }
    }

    public function review($recordId, $lang_id = 0, $sizeType = '', $afile_id = 0, $displayUniversalImage = true)
    {
        $default_image = 'no_image.jpg';
        $recordId = FatUtility::int($recordId);
        $afile_id = FatUtility::int($afile_id);
        $lang_id = FatUtility::int($lang_id);

        if ($afile_id > 0) {
            $res = AttachedFile::getAttributesById($afile_id);
            if (isset($res['afile_type']) && $res['afile_type'] == AttachedFile::FILETYPE_ORDER_FEEDBACK) {
                $file_row = $res;
            }
        } else {
            $file_row = AttachedFile::getAttachment(AttachedFile::FILETYPE_ORDER_FEEDBACK, $recordId, 0, $lang_id, $displayUniversalImage);
        }

        $image_name = (isset($file_row['afile_physical_path']) && 0 < $file_row['afile_id']) ? $file_row['afile_physical_path'] : '';
        $image_name = AttachedFile::setNamePrefix($image_name, $sizeType);

        $imageDimensions = ImageDimension::getData(ImageDimension::TYPE_REVIEW_IMAGE, $sizeType);

        if ($sizeType) {
            AttachedFile::displayImage($image_name, $imageDimensions['width'], $imageDimensions['height'], $default_image);
        } else {
            AttachedFile::displayOriginalImage($image_name, $default_image);
        }
    }

    public function productVideo($afileId)
    {
        $afileId = FatUtility::int($afileId);
        if ($afileId < 0) {
            return false;
        }

        $res = AttachedFile::getAttributesById($afileId);
        if (false === $res) {
            return false;
        }
        if ($res['afile_type'] != AttachedFile::FILETYPE_SELLER_PRODUCT_DIGITAL_DOWNLOAD_PREVIEW) {
            return false;
        }

        echo AttachedFile::getVideo($res['afile_physical_path']);
        exit;
    }

    public function previewImage(int $afileId)
    {
        if ($afileId < 0) {
            return false;
        }

        $res = AttachedFile::getAttributesById($afileId);

        if (false === $res) {
            return false;
        }
        if ($res['afile_type'] != AttachedFile::FILETYPE_SELLER_PRODUCT_DIGITAL_DOWNLOAD_PREVIEW) {
            return false;
        }
        $imageName = (isset($res['afile_physical_path']) && 0 < $res['afile_id']) ? $res['afile_physical_path'] : '';
        $w = 500;
        $h = 500;

        echo AttachedFile::displayImage($imageName, $w, $h);
        exit;
    }

    public function badgeIcon($badgeId, $langId = 0, $sizeType = '')
    {
        $default_image = 'badge_default.png';
        $badgeId = FatUtility::int($badgeId);
        $langId = FatUtility::int($langId);
        $file_row = AttachedFile::getAttachment(AttachedFile::FILETYPE_BADGE, $badgeId, 0, $langId);
        $image_name = (isset($file_row['afile_physical_path']) && 0 < $file_row['afile_id']) ? $file_row['afile_physical_path'] : '';
        $image_name = AttachedFile::setNamePrefix($image_name, $sizeType);
        $filePath = AttachedFile::FILETYPE_BADGE_IMAGE_PATH;

        $imageDimensions = ImageDimension::getData(ImageDimension::TYPE_BADGE_ICON, $sizeType);

        if ($sizeType) {
            if (is_numeric($sizeType)) {
                AttachedFile::displayImage($image_name, $sizeType, $sizeType, $default_image, $filePath);
            } else {
                AttachedFile::displayImage($image_name, $imageDimensions['width'], $imageDimensions['height'], $default_image, $filePath);
            }
        } else {
            AttachedFile::displayOriginalImage($image_name, $default_image, $filePath);
        }
    }

    public function badgeRequestImage(int $bReqId, int $langId = 0, $sizeType = '')
    {
        $file_row = AttachedFile::getAttachment(AttachedFile::FILETYPE_BADGE_REQUEST, $bReqId, 0, $langId);
        $image_name = (isset($file_row['afile_physical_path']) && 0 < $file_row['afile_id']) ? $file_row['afile_physical_path'] : '';
        $image_name = AttachedFile::setNamePrefix($image_name, $sizeType);
        $filePath = AttachedFile::FILETYPE_BADGE_REQUEST_IMAGE_PATH;
        $default_image = 'badge_default.png';
        $imageDimensions = ImageDimension::getData(ImageDimension::TYPE_BADGE_REQUEST_IMAGE, $sizeType);

        if ($sizeType) {
            if (is_numeric($sizeType)) {
                AttachedFile::displayImage($image_name, $sizeType, $sizeType, $default_image, $filePath);
            } else {
                AttachedFile::displayImage($image_name, $imageDimensions['width'], $imageDimensions['height'], $default_image, $filePath);
            }
        } else {
            AttachedFile::displayOriginalImage($image_name, $default_image, $filePath);
        }
    }
}
