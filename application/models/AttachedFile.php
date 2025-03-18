<?php

use Aws\S3\S3Client;
use Aws\Exception\AwsException;

class AttachedFile extends MyAppModel
{
    public const DB_TBL = 'tbl_attached_files';
    public const DB_TBL_TEMP = 'tbl_attached_files_temp';
    public const DB_TBL_PREFIX = 'afile_';

    public const FILETYPE_PRODCAT_IMAGE = 1;
    public const FILETYPE_PRODUCT_IMAGE = 2;
    public const FILETYPE_SELLER_APPROVAL_FILE = 3;
    public const FILETYPE_SHOP_LOGO = 4;
    public const FILETYPE_SHOP_BANNER = 5;

    public const FILETYPE_PAYMENT_METHOD = 6;

    public const FILETYPE_USER_IMAGE = 7;
    public const FILETYPE_BRAND_LOGO = 8;
    public const FILETYPE_USER_PROFILE_IMAGE = 9; /*User profile original image*/
    public const FILETYPE_USER_PROFILE_CROPED_IMAGE = 10;    /*User profile croped image*/
    public const FILETYPE_CATEGORY_ICON = 11; /*Used for mobile and shop templates*/
    public const FILETYPE_CATEGORY_BANNER = 12; /* Used in category detail page */

    public const FILETYPE_CATEGORY_BANNER_SELLER = 13; /* Used in seller shop template page */
    public const FILETYPE_SHOP_BACKGROUND_IMAGE = 14; /* Used in seller shop template page */
    public const FILETYPE_FRONT_LOGO = 15;
    public const FILETYPE_HOME_PAGE_BANNER = 16;
    public const FILETYPE_SOCIAL_PLATFORM_IMAGE = 17;
    public const FILETYPE_BANNER = 18;
    public const FILETYPE_ADMIN_LOGO = 19;
    public const FILETYPE_EMAIL_LOGO = 20;
    public const FILETYPE_FAVICON = 21;
    public const FILETYPE_COLLECTION_IMAGE = 22;
    public const FILETYPE_COLLECTION_BG_IMAGE = 23;
    public const FILETYPE_CATEGORY_IMAGE = 24;
    public const FILETYPE_BUYER_RETURN_PRODUCT = 25;
    public const FILETYPE_SELLER_CATALOG_REQUEST = 26;
    public const FILETYPE_DISCOUNT_COUPON_IMAGE = 27;
    public const FILETYPE_BLOG_CONTRIBUTION = 28;
    public const FILETYPE_BLOG_POST_IMAGE = 29;
    public const FILETYPE_BATCH_IMAGE = 30;
    public const FILETYPE_SOCIAL_FEED_IMAGE = 31;
    public const FILETYPE_TESTIMONIAL_IMAGE = 32;
    public const FILETYPE_PROMOTION_MEDIA = 33;
    public const FILETYPE_PAYMENT_PAGE_LOGO = 34;
    public const FILETYPE_ADMIN_PROFILE_IMAGE = 35;
    public const FILETYPE_ADMIN_PROFILE_CROPED_IMAGE = 36;
    public const FILETYPE_WATERMARK_IMAGE = 37;
    public const FILETYPE_APPLE_TOUCH_ICON = 38;
    public const FILETYPE_MOBILE_LOGO = 39;
    public const FILETYPE_CPAGE_BACKGROUND_IMAGE = 40;
    public const FILETYPE_PROMOTION_BANNER = 41;
    public const FILETYPE_SELLER_PRODUCT_DIGITAL_DOWNLOAD = 42;
    public const FILETYPE_ORDER_PRODUCT_DIGITAL_DOWNLOAD = 43;
    public const FILETYPE_CATEGORY_COLLECTION_BG_IMAGE = 44;
    public const FILETYPE_SELLER_PAGE_SLOGAN_BG_IMAGE = 45;
    public const FILETYPE_ADVERTISER_PAGE_SLOGAN_BG_IMAGE = 46;
    public const FILETYPE_AFFILIATE_PAGE_SLOGAN_BG_IMAGE = 47;
    public const FILETYPE_CUSTOM_PRODUCT_IMAGE = 48;
    public const FILETYPE_INVOICE_LOGO = 49;
    public const FILETYPE_BRAND_COLLECTION_BG_IMAGE = 50;
    public const FILETYPE_BULK_IMAGES = 51;
    public const FILETYPE_BRAND_IMAGE = 52;
    public const FILETYPE_SHOP_COLLECTION_IMAGE = 53;
    public const FILETYPE_PLUGIN_LOGO = 54;
    public const FILETYPE_APP_MAIN_SCREEN_IMAGE = 55;
    public const FILETYPE_APP_LOGO = 56;
    public const FILETYPE_PUSH_NOTIFICATION_IMAGE = 57;
    public const FILETYPE_FIRST_PURCHASE_DISCOUNT_IMAGE = 58;
    public const FILETYPE_META_IMAGE = 59;
    public const FILETYPE_ORDER_FEEDBACK = 60;
    public const FILETYPE_BADGE = 61;
    public const FILETYPE_BADGE_REQUEST = 62;
    public const FILETYPE_SELLER_PRODUCT_DIGITAL_DOWNLOAD_PREVIEW = 63;
    public const FILETYPE_PRODUCT_IMAGE_TEMP = 64;
    public const FILETYPE_CUSTOM_PRODUCT_IMAGE_TEMP = 65;
    public const FILETYPE_CATEGORY_THUMB = 66; /* Used in category detail page */
    public const FILETYPE_RFQ = 67;
    public const FILETYPE_RFQ_OFFER_FILE = 68;
    public const FILETYPE_SHIPPING_COMPANY_USER_DOCUMENT = 69;

    public const APP_IMAGE_WIDTH = 640;
    public const APP_IMAGE_HEIGHT = 480;

    public const RATIO_TYPE_SQUARE = 1;
    public const RATIO_TYPE_RECTANGULAR = 2;
    public const RATIO_TYPE_CUSTOM = 3;

    public const FILETYPE_PRODCAT_IMAGE_PATH = 'category/';
    public const FILETYPE_PRODUCT_IMAGE_PATH = 'product/';
    public const FILETYPE_BLOG_POST_IMAGE_PATH = 'blog-post/';
    public const FILETYPE_BULK_IMAGES_PATH = 'bulk-images/';
    public const FILETYPE_BADGE_IMAGE_PATH = 'badge-images/';
    public const FILETYPE_BADGE_REQUEST_IMAGE_PATH = 'badge-request-images/';

    public const FILETYPE_SVG_LOGO_FILE_PATH = 'media/logos/';
    public const FILETYPE_VIDEOS_FILE_PATH = 'media/videos/';
    public const FILE_ATTACHMENT_TYPE_OTHER = 0;
    public const FILE_ATTACHMENT_TYPE_SVG = 1;
    public const FILE_ATTACHMENT_TYPE_VIDEO = 2;
    private $attachmentType = self::FILE_ATTACHMENT_TYPE_OTHER;

    private bool $isThumbnailImage = false;

    public function __construct($fileId = 0)
    {
        parent::__construct(static::DB_TBL, static::DB_TBL_PREFIX . 'id', $fileId);
        $this->objMainTableRecord->setSensitiveFields(array());
        ini_set('post_max_size', '64M');
        ini_set('upload_max_filesize', '64M');
    }

    public static function returnBytes($val)
    {
        $val = trim($val);
        $last = strtolower($val[strlen($val) - 1]);
        $size = Fatutility::int($val);
        switch ($last) {
            case 'g':
                $size *= (1024 * 1024 * 1024);
                break;
            case 'm':
                $size *= (1024 * 1024);
                break;
            case 'k':
                $size *= 1024;
                break;
            default:
                $size = 1024;
                break;
        }
        return $size;
    }

    public static function maxFileUploadInBytes()
    {
        //select maximum upload size
        $maxUpload = static::returnBytes(ini_get('upload_max_filesize'));
        //select post limit
        $maxPost = static::returnBytes(ini_get('post_max_size'));
        //select memory limit
        $memoryLimit = static::returnBytes(ini_get('memory_limit'));
        // return the smallest of them, this defines the real limit
        return min($maxUpload, $maxPost, $memoryLimit);
    }

    public static function getSearchObject()
    {
        return new SearchBase(static::DB_TBL, 'ta');
    }


    public static function getFileTypeArray($langId)
    {
        return $arr = array(
            static::FILETYPE_PRODCAT_IMAGE => Labels::getLabel('LBL_PRODUCT_CATEGORY_IMAGE', $langId),
            static::FILETYPE_CATEGORY_ICON => Labels::getLabel('LBL_CATEGORY_ICON', $langId),
            static::FILETYPE_CATEGORY_THUMB => Labels::getLabel('LBL_CATEGORY_THUMB', $langId),
            static::FILETYPE_CATEGORY_IMAGE => Labels::getLabel('LBL_CATEGORY_IMAGE', $langId),
            static::FILETYPE_CATEGORY_BANNER => Labels::getLabel('LBL_CATEGORY_BANNER', $langId),
            static::FILETYPE_CATEGORY_BANNER_SELLER => Labels::getLabel('LBL_CATEGORY_BANNER_SELLER', $langId),
        );
        return $arr;
    }

    public static function getImgAttrTypeArray($langId)
    {
        $imgAttrTypeCacheVar = CacheHelper::get('imgAttrTypeCacheVar' . $langId, CONF_DEF_CACHE_TIME, '.txt');
        if ($imgAttrTypeCacheVar) {
            return json_decode($imgAttrTypeCacheVar, true);
        }

        $arr = array(
            static::FILETYPE_PRODUCT_IMAGE => Labels::getLabel('LBL_PRODUCTS', $langId),
            static::FILETYPE_BRAND_LOGO => Labels::getLabel('LBL_BRAND_LOGO', $langId),
            static::FILETYPE_BRAND_IMAGE => Labels::getLabel('LBL_BRAND_BANNER', $langId),
            /* static::FILETYPE_CATEGORY_IMAGE => Labels::getLabel('LBL_CATEGORIES', $langId), */
            static::FILETYPE_CATEGORY_BANNER => Labels::getLabel('LBL_CATEGORY_BANNER', $langId),
            static::FILETYPE_BLOG_POST_IMAGE => Labels::getLabel('LBL_BLOGS', $langId),
        );
        CacheHelper::create('imgAttrCacheVar' . $langId, json_encode($arr), CacheHelper::TYPE_LABELS);
        return $arr;
    }

    public static function getRatioTypeArray($langId)
    {
        return array(
            static::RATIO_TYPE_SQUARE => Labels::getLabel('LBL_1:1', $langId),
            static::RATIO_TYPE_RECTANGULAR => Labels::getLabel('LBL_16:9', $langId)
        );
    }

    public static function getRatioTypeWithCustom($langId)
    {
        return  self::getRatioTypeArray($langId) + [static::RATIO_TYPE_CUSTOM => Labels::getLabel('LBL_FREE', $langId)];
    }

    // $compareSize in KiloByte
    public function isUploadedFile($fileTmpName, $compareSize = 0)
    {
        $compareSize = FatUtility::int($compareSize);
        if (1 > $compareSize || static::maxFileUploadInBytes() < $compareSize) {
            $compareSize = static::maxFileUploadInBytes();
        }
        if (filesize($fileTmpName) > $compareSize) {
            $this->error = Labels::getLabel('ERR_INVALID_SIZE', CommonHelper::getLangId());
            return false;
        }

        if (!is_uploaded_file($fileTmpName)) {
            $this->error = Labels::getLabel('ERR_UNABLE_TO_UPLOAD_FILE', CommonHelper::getLangId());
            return false;
        }

        return true;
    }

    public static function checkSize($file, $compareSize)
    {
        $compareSize = FatUtility::convertToType($compareSize, FatUtility::VAR_FLOAT);
        if (filesize($file) > $compareSize) {
            return false;
        }
        return true;
    }


    public static function getMultipleAttachments($fileType, $recordId, $recordSubid = 0, $langId = 0, $displayUniversalImage = true, $screen = 0, $size = 0, $haveSubIdZero = false)
    {
        $fileType = FatUtility::int($fileType);
        $recordId = FatUtility::int($recordId);
        $recordSubid = FatUtility::int($recordSubid);
        $langId = FatUtility::int($langId);

        $srch = new SearchBase(static::DB_TBL);
        $srch->doNotCalculateRecords();
        $srch->addCondition('afile_type', '=', 'mysql_func_' . $fileType, 'AND', true);
        $srch->addCondition('afile_record_id', '=', 'mysql_func_' . $recordId, 'AND', true);

        $attr = ['afile_id', 'afile_type', 'afile_record_id', 'afile_record_subid', 'afile_lang_id', 'afile_screen', 'afile_physical_path', 'afile_name', 'afile_attachment_type', 'afile_attribute_title', 'afile_attribute_alt', 'afile_aspect_ratio', 'afile_display_order', 'afile_updated_at'];

        if ($fileType != AttachedFile::FILETYPE_PRODUCT_IMAGE_TEMP && $fileType != AttachedFile::FILETYPE_CUSTOM_PRODUCT_IMAGE_TEMP) {
            $attr[] = 'afile_downloaded_times';
        }

        $srch->addMultipleFields($attr);
        $orderBy = [];

        if ($recordSubid || $recordSubid == -1 || $haveSubIdZero) {
            if ($recordSubid == -1) {
                /* -1, becoz, needs to show, products universal image as well, in that case, value passed is as -1 */
                $recordSubid = 0;
            }
            $srch->addCondition('afile_record_subid', '=', 'mysql_func_' . $recordSubid, 'AND', true);
        }

        if ($langId > 0) {
            $cnd = $srch->addCondition('afile_lang_id', '=', 'mysql_func_' . $langId, 'AND', true);
            if ($displayUniversalImage) {
                $cnd->attachCondition('afile_lang_id', '=', '0');
                // $srch->addOrder('afile_lang_id', 'DESC');
                $orderBy['afile_lang_id'] = "DESC";
            }
        }

        // $srch->addOrder('afile_display_order');
        $orderBy['afile_display_order'] = "ASC";

        if ($recordId == 0) {
            // $srch->addOrder('afile_id', 'desc');
            $orderBy['afile_id'] = "desc";
        }

        if ($screen > 0) {
            $srch->addCondition('afile_screen', '=', $screen);
        }

        if ($langId == 0) {
            $cnd = $srch->addCondition('afile_lang_id', '=', 'mysql_func_' . FatApp::getConfig('CONF_DEFAULT_SITE_LANG', FatUtility::VAR_INT, 1), 'AND', true);
            $cnd->attachCondition('afile_lang_id', '=', 'mysql_func_0', 'OR', true);
        }

        $tmpSrch = new SearchBase("(" . $srch->getQuery() . ")", 't');
        $tmpSrch->doNotCalculateRecords();

        if ($size > 0) {
            $tmpSrch->setPageSize($size);
        }

        if (!empty($orderBy)) {
            foreach ($orderBy as $key => $val) {
                $tmpSrch->addOrder($key, $val);
            }
        }

        return FatApp::getDb()->fetchAll($tmpSrch->getResultSet(), 'afile_id');
    }

    public static function getAttachment($fileType, $recordId, $recordSubid = 0, $langId = 0, $displayUniversalImage = true, $screen = 0)
    {

        $data = static::getMultipleAttachments($fileType, $recordId, $recordSubid, $langId, $displayUniversalImage, $screen, 1);
        if (count($data) > 0) {
            reset($data);
            return current($data);
        }

        return [
            'afile_id' => -1,
            'afile_type' => 0,
            'afile_record_id' => -1,
            'afile_record_subid' => 0,
            'afile_lang_id' => $langId,
            'afile_screen' => 0,
            'afile_physical_path' => '',
            'afile_name' => '',
            'afile_attachment_type' => '',
            'afile_attribute_title' => '',
            'afile_attribute_alt' => '',
            'afile_aspect_ratio' => 0,
            'afile_display_order' => 0,
            'afile_downloaded_times' => 0,
            'afile_updated_at' => '',
        ];
    }

    public function validateFile($file, $name, $defaultLangIdForErrors)
    {
        if (!empty($file) && !empty($name) && file_exists($file)) {
            $fileExt = pathinfo($name, PATHINFO_EXTENSION);
            $fileExt = strtolower($fileExt);
            if (false === in_array($fileExt, applicationConstants::allowedFileExtensions())) {
                $this->error = Labels::getLabel('ERR_INVALID_FILE_EXTENSION', $defaultLangIdForErrors);
                return false;
            }

            if (strpos(CONF_UPLOADS_PATH, 's3://') === false) {
                $fileMimeType = mime_content_type($file);
                if (false === in_array($fileMimeType, applicationConstants::allowedMimeTypes())) {
                    $this->error = Labels::getLabel('ERR_INVALID_FILE_MIME_TYPE', $defaultLangIdForErrors);
                    return false;
                }
            }
        } else {
            $this->error = Labels::getLabel('ERR_NO_FILE_UPLOADED', $defaultLangIdForErrors);
            return false;
        }
    }

    public function saveAttachment($fl, $fileType, $recordId, $recordSubid, $name, $displayOrder = 0, $uniqueRecord = false, $langId = 0, $screen = 0, $aspectRatio = 0)
    {
        $defaultLangIdForErrors = ($langId == 0) ? $this->commonLangId : $langId;

        if (false === $this->validateFile($fl, $name, $defaultLangIdForErrors)) {
            return false;
        }

        $path = CONF_UPLOADS_PATH;

        $path = $this->fileLocToSave($fileType, $path);

        $date_wise_path = '';
        $langCode = '';
        if (in_array($this->attachmentType, [self::FILE_ATTACHMENT_TYPE_SVG, self::FILE_ATTACHMENT_TYPE_VIDEO])) {
            $fileLangId = (0 == $langId ? CommonHelper::getLangId() : $langId);
            $langCode = strtolower(Language::getAttributesById($fileLangId, 'language_code'));
            $path .= $langCode . '/';
            if (self::FILE_ATTACHMENT_TYPE_SVG == $this->attachmentType) {
                $saveName = $fileType . '.svg';
            } else {
                $saveName = time() . '-' . preg_replace('/[^a-zA-Z0-9.]/', '', $name);
            }
        } else {
            /* creation of folder date wise [ */
            $date_wise_path = date('Y') . '/' . date('m') . '/';
            /* ] */
            $path = $path . $date_wise_path;

            $saveName = time() . '-' . preg_replace('/[^a-zA-Z0-9.]/', '', $name);
            if (strpos(CONF_UPLOADS_PATH, 's3://') !== false) {
                $fileExt = pathinfo($name, PATHINFO_EXTENSION);
                $fileExt = strtolower($fileExt);
                if ('zip' == $fileExt) {
                    $saveName = time() . '-' . preg_replace('/[^a-zA-Z0-9.]/', '', $name);
                }
            }
        }
        if (!file_exists($path)) {
            mkdir($path, 0777, true);
        }

        if (self::FILE_ATTACHMENT_TYPE_OTHER == $this->attachmentType) {
            while (file_exists($path . $saveName)) {
                $saveName = rand(10, 99) . '-' . $saveName;
            }
        }

        if (!move_uploaded_file($fl, $path . $saveName)) {
            $this->error = Labels::getLabel('ERR_COULD_NOT_SAVE_FILE', $defaultLangIdForErrors);
            return false;
        }

        if (strpos(CONF_UPLOADS_PATH, 's3://') !== false) {
            if (isset($fileExt) && $fileExt == 'zip') {
                $saveName = preg_replace('/[^a-zA-Z0-9-]/', '', $saveName);
            } else {
                $saveName = preg_replace('/[^a-zA-Z0-9-.]/', '', $saveName);
            }
        }

        if (self::FILE_ATTACHMENT_TYPE_SVG == $this->attachmentType) {
            $fileLoc = self::FILETYPE_SVG_LOGO_FILE_PATH . $langCode . '/' . $saveName;
        } else if (self::FILE_ATTACHMENT_TYPE_VIDEO == $this->attachmentType) {
            $fileLoc = self::FILETYPE_VIDEOS_FILE_PATH . $langCode . '/' . $saveName;
        } else {
            $fileLoc = $date_wise_path . $saveName;
        }

        return $this->updateFileToDb($fileType, $recordId, $recordSubid, $fileLoc, $name, $langId, $screen, $displayOrder, $uniqueRecord, $aspectRatio);
    }

    public function moveAttachment($filePath, $fileType, $recordId, $recordSubid, $name, $displayOrder = 0, $uniqueRecord = false, $langId = 0, $screen = 0)
    {
        $defaultLangIdForErrors = ($langId == 0) ? $this->commonLangId : $langId;

        $path = CONF_UPLOADS_PATH;
        $file = $path . $filePath;

        if (false === $this->validateFile($file, $name, $defaultLangIdForErrors)) {
            return false;
        }

        $path = $this->fileLocToSave($fileType, $path);
        $date_wise_path = '';
        $langCode = '';
        if (in_array($this->attachmentType, [self::FILE_ATTACHMENT_TYPE_SVG, self::FILE_ATTACHMENT_TYPE_VIDEO])) {
            $fileLangId = (0 == $langId ? CommonHelper::getLangId() : $langId);
            $langCode = strtolower(Language::getAttributesById($fileLangId, 'language_code'));
            $path .= $langCode . '/';
            if (self::FILE_ATTACHMENT_TYPE_SVG == $this->attachmentType) {
                $saveName = $fileType . '.svg';
            } else {
                $saveName = time() . '-' . preg_replace('/[^a-zA-Z0-9]/', '', $name) . '.' . pathinfo($name, PATHINFO_EXTENSION);
            }
        } else {
            /* creation of folder date wise [ */
            $date_wise_path = date('Y') . '/' . date('m') . '/';
            /* ] */
            $path = $path . $date_wise_path;

            $saveName = time() . '-' . preg_replace('/[^a-zA-Z0-9]/', '', $name);
        }

        if (!file_exists($path)) {
            mkdir($path, 0777, true);
        }

        if (self::FILE_ATTACHMENT_TYPE_OTHER == $this->attachmentType) {
            while (file_exists($path . $saveName)) {
                $saveName = rand(10, 99) . '-' . $saveName;
            }
        }

        if (false === copy($file, $path . $saveName)) {
            $this->error = Labels::getLabel('ERR_COULD_NOT_SAVE_FILE', $defaultLangIdForErrors);
            return false;
        }

        if (self::FILE_ATTACHMENT_TYPE_SVG == $this->attachmentType) {
            $fileLoc = self::FILETYPE_SVG_LOGO_FILE_PATH . $langCode . '/' . $saveName;
        } else if (self::FILE_ATTACHMENT_TYPE_VIDEO == $this->attachmentType) {
            $fileLoc = self::FILETYPE_VIDEOS_FILE_PATH . $langCode . '/' . $saveName;
        } else {
            $fileLoc = $date_wise_path . $saveName;
        }

        return $this->updateFileToDb($fileType, $recordId, $recordSubid, $fileLoc, $name, $langId, $screen, $displayOrder, $uniqueRecord);
    }

    protected function updateFileToDb($fileType, $recordId, $recordSubid, $fileLoc, $name, $langId, $screen, $displayOrder, $uniqueRecord, $aspectRatio = 0)
    {
        $defaultLangIdForErrors = ($langId == 0) ? $this->commonLangId : $langId;
        $this->assignValues(
            array(
                'afile_type' => $fileType,
                'afile_record_id' => $recordId,
                'afile_record_subid' => $recordSubid,
                'afile_physical_path' => $fileLoc,
                'afile_name' => $name,
                'afile_attachment_type' => $this->attachmentType,
                'afile_lang_id' => $langId,
                'afile_screen' => $screen,
                'afile_aspect_ratio' => $aspectRatio
            )
        );

        $db = FatApp::getDb();

        if ($displayOrder == -1) {
            //@todo display order thing needs to be checked.
            $smt = $db->prepareStatement(
                'SELECT MAX(afile_display_order) AS max_order FROM ' . static::DB_TBL . '
                    WHERE afile_type = ? AND afile_record_id = ? AND afile_record_subid = ? AND afile_lang_id = ?'
            );
            $smt->bindParameters('iii', $fileType, $recordId, $recordSubid, $langId);

            $smt->execute();
            $row = $smt->fetchAssoc();

            $displayOrder = FatUtility::int($row['max_order']) + 1;
        }

        $this->setFldValue('afile_display_order', $displayOrder);
        $this->setFldValue('afile_updated_at', date("Y-m-d H:i:s"));

        if (!$this->save()) {
            $this->error = Labels::getLabel('ERR_COULD_NOT_SAVE_FILE', $defaultLangIdForErrors);
            return false;
        }

        if ($uniqueRecord) {
            $db->deleteRecords(
                static::DB_TBL,
                array(
                    'smt' => 'afile_type = ? AND afile_record_id = ? AND afile_record_subid = ? AND afile_lang_id = ?  AND afile_id != ? AND afile_screen = ?',
                    'vals' => array($fileType, $recordId, $recordSubid, $langId, $this->mainTableRecordId, $screen)
                )
            );
        }

        $this->setRecordModifiedTime($fileType, $recordId);

        return $fileLoc;
    }

    public function fileLocToSave($fileType, $path = '')
    {
        /* files path[ */
        switch ($fileType) {
            case self::FILETYPE_PRODCAT_IMAGE:
                $path .= self::FILETYPE_PRODCAT_IMAGE_PATH;
                break;
            case self::FILETYPE_PRODUCT_IMAGE:
            case self::FILETYPE_PRODUCT_IMAGE_TEMP:
            case self::FILETYPE_CUSTOM_PRODUCT_IMAGE:
            case self::FILETYPE_CUSTOM_PRODUCT_IMAGE_TEMP:
                $path .= self::FILETYPE_PRODUCT_IMAGE_PATH;
                break;
            case self::FILETYPE_BLOG_POST_IMAGE:
                $path .= self::FILETYPE_BLOG_POST_IMAGE_PATH;
                break;
            case self::FILETYPE_BULK_IMAGES:
                $path .= self::FILETYPE_BULK_IMAGES_PATH;
                break;
            case self::FILETYPE_BADGE:
                $path .= self::FILETYPE_BADGE_IMAGE_PATH;
                break;
            case self::FILETYPE_BADGE_REQUEST:
                $path .= self::FILETYPE_BADGE_REQUEST_IMAGE_PATH;
                break;
        }
        /* ] */

        if (self::FILE_ATTACHMENT_TYPE_SVG == $this->attachmentType) {
            $path .= self::FILETYPE_SVG_LOGO_FILE_PATH;
        } else if (self::FILE_ATTACHMENT_TYPE_VIDEO == $this->attachmentType) {
            $path .= self::FILETYPE_VIDEOS_FILE_PATH;
        }

        return $path;
    }

    public function setAttachmentType(int $attachmentType = self::FILE_ATTACHMENT_TYPE_OTHER)
    {
        $this->attachmentType = $attachmentType;
    }

    public function saveImage($fl, $fileType, $recordId, $recordSubid, $name, $displayOrder = 0, $uniqueRecord = false, $lang_id = 0, $mimeType = '', $screen = 0, $aspectRatio = 0)
    {
        if (getimagesize($fl) === false && !in_array($mimeType, ['image/svg+xml'])) {
            $this->error = Labels::getLabel('ERR_UNRECOGNISED_IMAGE_FILE', $this->commonLangId);
            return false;
        }
        return $this->saveAttachment($fl, $fileType, $recordId, $recordSubid, $name, $displayOrder, $uniqueRecord, $lang_id, $screen, $aspectRatio);
    }

    public static function displayWebpImage($imageName, $w, $h, $noImage = 'no_image.jpg', $uploadedFilePath = '', $resizeType = ImageResize::IMG_RESIZE_EXTRA_ADDSPACE, $apply_watermark = false, $cache = true, $imageCompression = false)
    {
        ob_end_clean();
        ini_set('memory_limit', '-1');
        $noImage = 'images/defaults/' . $noImage;
        $imageQuality = (true == $imageCompression) ? 80 : 100;

        $uploadedFilePath = CONF_UPLOADS_PATH . trim($uploadedFilePath);

        $fileMimeType = '';
        $imagePath = $uploadedFilePath . $imageName;

        if (empty($imageName) || !file_exists($uploadedFilePath . $imageName)) {
            $imagePath = $noImage;
        }

        $fileMimeType = mime_content_type($imagePath);
        if (strpos($_SERVER['REQUEST_URI'], '?t=') === false) {
            $filemtime = filemtime($imagePath);
            $_SERVER['REQUEST_URI'] = rtrim($_SERVER['REQUEST_URI'], '/') . '/?t=' . $filemtime;
        }

        static::setHeaders();
        static::checkModifiedHeader($imagePath);

        if (CONF_USE_FAT_CACHE && $cache) {
            ob_get_clean();
            ob_start();
            static::setContentType($imagePath);
            $fileContent = FatCache::get($_SERVER['REQUEST_URI'], null, '.webp');
            if ($fileContent) {
                static::loadImage($fileContent, $imagePath);
            }
        }

        /*In S3 bucket for some large files PHP functions like getImageSize gives some error. So handled the same accordingly */
        if (strpos(CONF_UPLOADS_PATH, 's3://') !== false) {
            static::setLastModified($imagePath);
            static::setContentType($imagePath, 'image/webp');
            $readFileFromCache = FatCache::get($imagePath, CONF_IMG_CACHE_TIME, '.jpg');
            if (!$readFileFromCache) {
                $fileContent = file_get_contents($imagePath);
                FatCache::set($imagePath, $fileContent, '.jpg');
            }
            $imagePath = CONF_INSTALLATION_PATH . 'public' . UrlHelper::getCachedUrl($imagePath, CONF_IMG_CACHE_TIME, '.jpg');
        } else {
            static::setLastModified($imagePath);
            static::setContentType($imagePath, 'image/webp');
        }

        $w = FatUtility::int($w);
        $h = FatUtility::int($h);

        list($width, $height) = getimagesize($imagePath);
        $ratio_orig = $width / $height;

        $thumb = imagecreatetruecolor($w, $h);
        $newWidth = $w;
        $newHeight = $h;
        if ($w / $h > $ratio_orig) {
            $newWidth = floor($h * $ratio_orig);
        } else {
            $newHeight = floor($w / $ratio_orig);
        }

        switch ($fileMimeType) {
            case 'image/png':
                $img = imagecreatefrompng($imagePath);
                break;
            case 'image/webp':
                $img = imagecreatefromwebp($imagePath);
                break;
            case 'image/jpg':
            case 'image/jpeg':
                $img = imagecreatefromjpeg($imagePath);
                break;
            default:
                $img = imagecreatefromjpeg($imagePath);
                break;
        }


        $color_fill = imagecolorallocate($thumb, 255, 255, 255);
        imagefill($thumb, 0, 0, $color_fill);

        if ($apply_watermark && !empty($imagePath)) {
            $file_row = AttachedFile::getAttachment(AttachedFile::FILETYPE_WATERMARK_IMAGE, 0, 0, CommonHelper::getLangId());
            $wtrmrkFile = isset($file_row['afile_physical_path']) ? $file_row['afile_physical_path'] : '';
            if (!empty($wtrmrkFile)) {
                $wtrmrkFile = $uploadedFilePath . $wtrmrkFile;
                $stampMimeType = mime_content_type($wtrmrkFile);

                switch ($stampMimeType) {
                    case 'image/png':
                        $stamp = imagecreatefrompng($wtrmrkFile);
                        break;
                    case 'image/webp':
                        $stamp = imagecreatefromwebp($wtrmrkFile);
                        break;
                    case 'image/jpg':
                    case 'image/jpeg':
                        $stamp = imagecreatefromjpeg($wtrmrkFile);
                        break;
                    default:
                        $stamp = imagecreatefromjpeg($wtrmrkFile);
                        break;
                }

                // Set the margins for the stamp and get the height/width of the stamp image
                $marge_right = 10;
                $marge_bottom = 10;
                $sx = imagesx($stamp);
                $sy = imagesy($stamp);

                // Copy the stamp image onto our photo using the margin offsets and the photo 
                // width to calculate positioning of the stamp. 
                imagecopy($img, $stamp, imagesx($img) - $sx - $marge_right, imagesy($img) - $sy - $marge_bottom, 0, 0, imagesx($stamp), imagesy($stamp));
            }
        }

        $xPosition = floor(($w - $newWidth) / 2);
        $yPosition = floor(($h - $newHeight) / 2);
        imagecopyresampled($thumb, $img, $xPosition, $yPosition, 0, 0, $newWidth, $newHeight, $width, $height);

        if (CONF_USE_FAT_CACHE && $cache) {
            ob_end_clean();
            ob_start();
            ini_set('memory_limit', '-1');
            static::setContentType($imagePath, 'image/webp');
            imagewebp($thumb, null, $imageQuality);
            imagedestroy($thumb);
            $imgData = ob_get_clean();
            FatCache::set($_SERVER['REQUEST_URI'], $imgData, '.jpg');
            static::loadImage($imgData, $imagePath);
        }

        imagewebp($thumb, null, $imageQuality);
        imagedestroy($thumb);
        exit;
    }

    /* always call this function using image controller and pass relavant arguments. */
    public static function displayImage($imageName, $w, $h, $noImage = 'no_image.jpg', $uploadedFilePath = '', $resizeType = ImageResize::IMG_RESIZE_EXTRA_ADDSPACE, $apply_watermark = false, $cache = true, $imageCompression = false)
    {

        if (substr($imageName, 0, 5) == 'webp/') {
            $imageName = substr($imageName, 5);
            self::displayWebpImage($imageName, $w, $h, $noImage, $uploadedFilePath, $resizeType, $apply_watermark, $cache, $imageCompression);
        }

        ob_end_clean();
        ini_set('memory_limit', '-1');
        $noImage = 'images/defaults/' . $noImage;
        $imageQuality = (true == $imageCompression) ? 80 : 100;

        $uploadedFilePath = CONF_UPLOADS_PATH . trim($uploadedFilePath);

        $fileMimeType = '';
        $imagePath = $uploadedFilePath . $imageName;

        if (empty($imageName) || !is_file($uploadedFilePath . $imageName) || !file_exists($uploadedFilePath . $imageName)) {
            $imagePath = $noImage;
        }

        if (strpos($_SERVER['REQUEST_URI'], '?t=') === false) {
            $filemtime = filemtime($imagePath);
            $_SERVER['REQUEST_URI'] = rtrim($_SERVER['REQUEST_URI'], '/') . '/?t=' . $filemtime;
        }

        static::setHeaders();

        $w = FatUtility::int($w);
        $h = FatUtility::int($h);

        static::checkModifiedHeader($imagePath);

        if (CONF_USE_FAT_CACHE && $cache) {
            ob_get_clean();
            ob_start();
            static::setContentType($imagePath);
            $fileContent = FatCache::get($_SERVER['REQUEST_URI'], null, '.jpg');
            if ($fileContent) {
                static::loadImage($fileContent, $imagePath);
            }
        }


        /*In S3 bucket for some large files PHP functions like getImageSize gives some error. So handled the same accordingly */
        $tempPath = '';

        if (strpos(CONF_UPLOADS_PATH, 's3://') !== false) {

            static::setLastModified($imagePath);
            static::setContentType($imagePath);
            $readFileFromCache = FatCache::get($imagePath, CONF_IMG_CACHE_TIME, '.jpg');
            if (!$readFileFromCache) {
                $fileContent = file_get_contents($imagePath);
                FatCache::set($imagePath, $fileContent, '.jpg');
            }
            $tempPath = CONF_INSTALLATION_PATH . 'public' . UrlHelper::getCachedUrl($imagePath, CONF_IMG_CACHE_TIME, '.jpg');
        } else {

            $tempPath = $imagePath;
            static::setLastModified($imagePath);
            static::setContentType($imagePath);
        }

        try {

            $img = new ImageResize($tempPath);
        } catch (Exception $e) {

            $img = static::getDefaultImage($imagePath, $w, $h);
            $img->setExtraSpaceColor(204, 204, 204);
        }

        $img->setResizeMethod($resizeType);
        //$img->setResizeMethod(ImageResize::IMG_RESIZE_RESET_DIMENSIONS);
        if ($w && $h) {
            $img->setMaxDimensions($w, $h);
        }

        if ($apply_watermark && !empty($imagePath)) {
            $file_row = AttachedFile::getAttachment(AttachedFile::FILETYPE_WATERMARK_IMAGE, 0, 0, CommonHelper::getLangId());
            $wtrmrk_file = isset($file_row['afile_physical_path']) ? $file_row['afile_physical_path'] : '';
            if (!empty($wtrmrk_file)) {
                $wtrmrk_file = $uploadedFilePath . $wtrmrk_file;
                $imageInfo = getimagesize($wtrmrk_file);
                $img_w = $w;
                $img_h = $h;
                $wtrmrk_w = $imageInfo[0];
                $wtrmrk_h = $imageInfo[1];
                $img->setWaterMark($wtrmrk_file, $img_w - $wtrmrk_w - 20, $img_h - $wtrmrk_h - 20);
                $fileMimeType = 'image/png';
            }
        }

        if (CONF_USE_FAT_CACHE && $cache) {
            ob_end_clean();
            ob_start();
            ini_set('memory_limit', '-1');
            static::setContentType($imagePath, $fileMimeType);
            $img->displayImage($imageQuality, false);
            $imgData = ob_get_clean();
            FatCache::set($_SERVER['REQUEST_URI'], $imgData, '.jpg');
            static::loadImage($imgData, $imagePath);
        }
        static::setContentType($imagePath);
        $img->displayImage($imageQuality, false);
    }

    public static function loadImage($imgData, $image_name)
    {
        static::setHeaders();
        static::setLastModified($image_name);
        echo $imgData;
        exit;
    }

    public static function setHeaders()
    {
        header('Cache-Control: public, max-age=31536000, stale-while-revalidate=604800');
        header("Pragma: public");
        header("Expires: " . date('r', strtotime("+1 year")));
    }

    public static function setContentType($imagePath, $fileMimeType = '')
    {
        if (strpos(CONF_UPLOADS_PATH, 's3://') === false) {
            if (empty($fileMimeType)) {
                $fileMimeType = mime_content_type($imagePath);
            }

            if ($fileMimeType != '') {
                header("content-type: " . $fileMimeType);
            } else {
                header("content-type: image/jpeg");
            }
            return;
        }

        if (substr($imagePath, strlen($imagePath) - 3, strlen($imagePath)) == "svg") {
            header("Content-type: image/svg+xml");
        } else {
            if (empty($fileMimeType)) {
                $fileMimeType = mime_content_type($imagePath);
            }

            if ($fileMimeType != '') {
                header("content-type: " . $fileMimeType);
            } else {
                header("content-type: image/jpeg");
            }
        }
    }

    public static function setLastModified($image_name)
    {
        header('Last-Modified: ' . gmdate('D, d M Y H:i:s', filemtime($image_name)) . ' GMT', true, 200);
    }

    public static function checkModifiedHeader($image_name)
    {
        $headers = FatApp::getApacheRequestHeaders();
        if (isset($headers['If-Modified-Since']) && (strtotime($headers['If-Modified-Since']) == filemtime($image_name))) {
            header('Last-Modified: ' . gmdate('D, d M Y H:i:s', filemtime($image_name)) . ' GMT', true, 304);
            exit;
        }
    }

    public static function getDefaultImage($image_name, $width, $height, $useCache = false)
    {
        $file_extension = substr($image_name, strlen($image_name) - 3, strlen($image_name));
        if ($file_extension == "svg") {
            header("Content-type: image/svg+xml");
            static::setLastModified($image_name);
            // $image_name = static::setDimensions($image_name, $width, $height);
            echo file_get_contents($image_name);
            exit;
        }
        return  new ImageResize($image_name);
    }

    public static function displayOriginalImageWebp($imageName, $noImage = 'no_image.jpg', $uploadedFilePath = '', $cache = false)
    {
        ob_end_clean();
        ini_set('memory_limit', '-1');
        $noImage = 'images/defaults/' . $noImage;
        $imageQuality = 100;

        $uploadedFilePath = CONF_UPLOADS_PATH . trim($uploadedFilePath);

        $fileMimeType = '';
        $imagePath = $uploadedFilePath . $imageName;

        if (empty($imageName) || !file_exists($uploadedFilePath . $imageName)) {
            $imagePath = $noImage;
        }

        $fileMimeType = mime_content_type($imagePath);
        if (strpos($_SERVER['REQUEST_URI'], '?t=') === false) {
            $filemtime = filemtime($imagePath);
            $_SERVER['REQUEST_URI'] = rtrim($_SERVER['REQUEST_URI'], '/') . '/?t=' . $filemtime;
        }

        static::setHeaders();
        static::checkModifiedHeader($imagePath);

        if (CONF_USE_FAT_CACHE && $cache) {
            ob_get_clean();
            ob_start();
            static::setContentType($imagePath, 'image/webp');
            $fileContent = FatCache::get($_SERVER['REQUEST_URI'], null, '.jpg');
            if ($fileContent) {
                static::loadImage($fileContent, $imagePath);
            }
        }

        /*In S3 bucket for some large files PHP functions like getImageSize gives some error. So handled the same accordingly */
        if (strpos(CONF_UPLOADS_PATH, 's3://') !== false) {
            static::setLastModified($imagePath);
            static::setContentType($imagePath, 'image/webp');
            $readFileFromCache = FatCache::get($imagePath, CONF_IMG_CACHE_TIME, '.jpg');
            if (!$readFileFromCache) {
                $fileContent = file_get_contents($imagePath);
                FatCache::set($imagePath, $fileContent, '.jpg');
            }
            $imagePath = CONF_INSTALLATION_PATH . 'public' . UrlHelper::getCachedUrl($imagePath, CONF_IMG_CACHE_TIME, '.jpg');
        } else {
            static::setLastModified($imagePath);
            static::setContentType($imagePath, 'image/webp');
        }

        list($width, $height) = getimagesize($imagePath);

        $thumb = imagecreatetruecolor($width, $height);

        switch ($fileMimeType) {
            case 'image/png':
                $img = imagecreatefrompng($imagePath);
                break;
            case 'image/webp':
                $img = imagecreatefromwebp($imagePath);
                break;
            case 'image/jpg':
            case 'image/jpeg':
                $img = imagecreatefromjpeg($imagePath);
                break;
            default:
                $img = imagecreatefromjpeg($imagePath);
                break;
        }

        $color_fill = imagecolorallocate($thumb, 255, 255, 255);
        imagefill($thumb, 0, 0, $color_fill);
        imagecopyresampled($thumb, $img, 0, 0, 0, 0, $width, $height, $width, $height);

        if (CONF_USE_FAT_CACHE && $cache) {
            ob_end_clean();
            ob_start();
            ini_set('memory_limit', '-1');
            static::setContentType($imagePath, 'image/webp');
            imagewebp($thumb, null, $imageQuality);
            imagedestroy($thumb);
            $imgData = ob_get_clean();
            FatCache::set($_SERVER['REQUEST_URI'], $imgData, '.jpg');
            static::loadImage($imgData, $imagePath);
        }

        imagewebp($thumb, null, $imageQuality);
        imagedestroy($thumb);
        exit;
    }

    public static function displayOriginalImage($imageName, $noImage = 'no_image.jpg', $uploadedFilePath = '', $cache = false)
    {
        if (substr($imageName, 0, 5) == 'webp/') {
            $imageName = substr($imageName, 5);
            self::displayOriginalImageWebp($imageName, $noImage, $uploadedFilePath, $cache);
        }
        ob_end_clean();
        $noImage = 'images/defaults/' . $noImage;
        $uploadedFilePath = CONF_UPLOADS_PATH . trim($uploadedFilePath);
        $imagePath = $uploadedFilePath . $imageName;

        if (empty($imageName) || !file_exists($uploadedFilePath . $imageName)) {
            $imagePath = $noImage;
        }

        if (strpos($_SERVER['REQUEST_URI'], '?t=') === false) {
            $filemtime = filemtime($imagePath);
            $_SERVER['REQUEST_URI'] = rtrim($_SERVER['REQUEST_URI'], '/') . '/?t=' . $filemtime;
        }

        static::setHeaders();
        static::checkModifiedHeader($imagePath);

        if (CONF_USE_FAT_CACHE && $cache) {
            ob_get_clean();
            ob_start();
            static::setContentType($imagePath);
            $fileContent = FatCache::get($_SERVER['REQUEST_URI'], null, '.jpg');
            if ($fileContent) {
                static::loadImage($fileContent, $imagePath);
            }
        }

        try {
            static::setLastModified($imagePath);
            static::setContentType($imagePath);
            $fileContent = file_get_contents($imagePath);
            if (CONF_USE_FAT_CACHE && $cache) {
                FatCache::set($_SERVER['REQUEST_URI'], $fileContent, '.jpg');
                static::loadImage($fileContent, $imagePath);
            }
        } catch (Exception $e) {
            static::setLastModified($imagePath);
            static::setContentType($imagePath);
            $fileContent = file_get_contents($imagePath);
        }

        if (CONF_USE_FAT_CACHE && $cache) {
            FatCache::set($_SERVER['REQUEST_URI'], $fileContent, '.jpg');
            static::loadImage($fileContent, $imagePath);
        }
        echo $fileContent;
    }

    public static function updateDownloadCount($aFileId = 0)
    {
        $aFileId = FatUtility::int($aFileId);
        $digitalFile = array('afile_downloaded_times' => 'mysql_func_afile_downloaded_times+1');
        $where = array('smt' => 'afile_id = ?', 'vals' => array($aFileId));
        $db = FatApp::getDb();
        if (!$db->updateFromArray(static::DB_TBL, $digitalFile, $where, true)) {
            return false;
        }
        return true;
    }

    public static function downloadAttachment($image_name, $downloadFileName)
    {
        ob_end_clean();
        // die(CONF_UPLOADS_PATH . $image_name);
        if (!empty($image_name) && file_exists(CONF_UPLOADS_PATH . $image_name)) {
            $image_name = CONF_UPLOADS_PATH . $image_name;
            $mineType =  mime_content_type($image_name);
            header('Content-Description: File Transfer');
            header("Content-type: $mineType");
            if (strpos($_SERVER['HTTP_USER_AGENT'], "MSIE") > 0) {
                header('Content-Disposition: attachment; filename="' . rawurlencode(basename($downloadFileName)) . '"');
            } else {
                header('Content-Disposition: attachment; filename*=UTF-8\'\'' . rawurlencode(basename($downloadFileName)));
            }
            header('Content-Length: ' . filesize($image_name));
            header('Content-Transfer-Encoding: binary');
            header('Expires: 0');
            header('Cache-Control: public');
            header('Pragma: public');
            readfile($image_name);
        }
    }

    public static function getTempImages($limit = false)
    {
        $srch = new SearchBase(AttachedFile::DB_TBL_TEMP, 'aft');
        $srch->addCondition('aft.afile_downloaded', '=', 'mysql_func_' . applicationConstants::NO, 'AND', true);
        //$srch->addOrder('aft.afile_id', 'asc');
        $srch->addOrder('rand()');
        if ($limit > 0) {
            $srch->setPageSize($limit);
        }
        $srch->doNotCalculateRecords();
        $rs = $srch->getResultSet();
        $row = FatApp::getDb()->fetchAll($rs);
        if ($row == false) {
            return array();
        } else {
            return $row;
        }
    }

    public static function getImageName($url, $arr = array())
    {
        if (empty($arr)) {
            return;
        }

        $imageName = '';
        $isUrlArr = parse_url($url);
        if (is_array($isUrlArr) && isset($isUrlArr['host'])) {
            if (static::isValidImageUrl($url)) {
                $imgFileContent = static::getRemoteFileContent($url);
                if ($imgFileContent) {
                    $imageName = static::uploadTempImage($imgFileContent, $url, $arr);
                }
            }
        } else {
            $imageName = $url;
        }
        return $imageName;
    }

    public static function uploadTempImage($imgFileContent, $url, $arr = array())
    {
        if (empty($arr)) {
            return;
        }

        $name = substr(preg_replace('/[^a-zA-Z0-9\/\-\_\.]/', '', basename($url)), 0, 50);
        $path = CONF_UPLOADS_PATH;

        /* files path[ */
        switch ($arr['afile_type']) {
            case self::FILETYPE_PRODCAT_IMAGE:
                $path .= self::FILETYPE_PRODCAT_IMAGE_PATH;
                break;
            case self::FILETYPE_PRODUCT_IMAGE:
                $path .= self::FILETYPE_PRODUCT_IMAGE_PATH;
                break;
            case self::FILETYPE_BLOG_POST_IMAGE:
                $path .= self::FILETYPE_BLOG_POST_IMAGE_PATH;
                break;
        }
        /* ] */

        /* creation of folder date wise [ */
        $date_wise_path = date('Y') . '/' . date('m') . '/';
        /* ] */
        $path = $path . $date_wise_path;

        // $saveName = time() . '-' . preg_replace('/[^a-zA-Z0-9]/', '', $name);
        $saveName = time() . '-' . substr(preg_replace('/[^a-zA-Z0-9]/', '', $name), -12);
        if (!file_exists($path)) {
            mkdir($path, 0777, true);
        }

        while (file_exists($path . $saveName)) {
            $saveName = rand(10, 99) . '-' . $saveName;
        }

        $localfile = $path . $saveName;
        $res = file_put_contents($localfile, $imgFileContent);
        if (!$res) {
            return;
        }

        $fileType = $arr['afile_type'];
        $recordId = $arr['afile_record_id'];
        $recordSubid = $arr['afile_record_subid'];
        $langId = $arr['afile_lang_id'];
        $screen = $arr['afile_screen'];
        $data = array(
            'afile_type' => $fileType,
            'afile_record_id' => $recordId,
            'afile_record_subid' => $recordSubid,
            'afile_physical_path' => $date_wise_path . $saveName,
            'afile_lang_id' => $langId,
            'afile_screen' => $arr['afile_screen'],
            'afile_display_order' => $arr['afile_display_order'],
            'afile_name' => $name,
            'afile_updated_at' => date('Y-m-d H:i:s'),
        );

        $db = FatApp::getDb();
        if ($arr['afile_unique'] == applicationConstants::YES) {
            $db->deleteRecords(
                static::DB_TBL,
                array(
                    'smt' => 'afile_type = ? AND afile_record_id = ? AND afile_record_subid = ? AND afile_lang_id = ?  AND afile_screen = ?',
                    'vals' => array($fileType, $recordId, $recordSubid, $langId, $screen)
                )
            );
        }

        $db->insertFromArray(static::DB_TBL, $data);
        return $date_wise_path . $saveName;
    }

    public static function isValidImageUrl($url)
    {
        if (getimagesize($url) !== false) {
            return true;
        }
        return false;
    }

    public static function getRemoteFileContent($url)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        // curl_setopt($ch, CURLOPT_BINARYTRANSFER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 200);
        curl_setopt($ch, CURLOPT_AUTOREFERER, false);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        $file = curl_exec($ch);
        if ($file === false) {
            return false;
        }
        curl_close($ch);
        return $file;
    }

    public function deleteFile($fileType, $recordId, $fileId = 0, $record_subid = 0, $langId = -1, $screen = 0)
    {
        $fileType = FatUtility::int($fileType);
        $recordId = FatUtility::int($recordId);
        $fileId = FatUtility::int($fileId);
        $record_subid = FatUtility::int($record_subid);
        $langId = FatUtility::int($langId);
        $allowedFileTypes = [
            AttachedFile::FILETYPE_ADMIN_LOGO,
            AttachedFile::FILETYPE_ADMIN_PROFILE_CROPED_IMAGE,
            AttachedFile::FILETYPE_ADMIN_PROFILE_IMAGE,
            AttachedFile::FILETYPE_FRONT_LOGO,
            AttachedFile::FILETYPE_EMAIL_LOGO,
            AttachedFile::FILETYPE_FAVICON,
            AttachedFile::FILETYPE_SOCIAL_FEED_IMAGE,
            AttachedFile::FILETYPE_PAYMENT_PAGE_LOGO,
            AttachedFile::FILETYPE_WATERMARK_IMAGE,
            AttachedFile::FILETYPE_APPLE_TOUCH_ICON,
            AttachedFile::FILETYPE_MOBILE_LOGO,
            AttachedFile::FILETYPE_CATEGORY_COLLECTION_BG_IMAGE,
            AttachedFile::FILETYPE_BRAND_COLLECTION_BG_IMAGE,
            AttachedFile::FILETYPE_INVOICE_LOGO,
            AttachedFile::FILETYPE_APP_MAIN_SCREEN_IMAGE,
            AttachedFile::FILETYPE_APP_LOGO,
            AttachedFile::FILETYPE_PUSH_NOTIFICATION_IMAGE,
            AttachedFile::FILETYPE_BADGE_REQUEST,
            AttachedFile::FILETYPE_BLOG_POST_IMAGE,
            AttachedFile::FILETYPE_AFFILIATE_PAGE_SLOGAN_BG_IMAGE,
        ];
        //if (!in_array($fileType, $allowedFileTypes) && (!$fileType || !$recordId)) {
        // Remove condition of $recordId for handle all data of add/edit product category in single form
        if (!in_array($fileType, $allowedFileTypes) && !$fileType) {
            $this->error = Labels::getLabel('ERR_INVALID_REQUEST', $this->commonLangId);
            return false;
        }

        /* default will delete all files of requested recordId */
        $smt1 = 'afile_type = ? AND afile_record_id = ?';
        $dataArr1 = array($fileType, $recordId);
        $deleteStatementArr = array('smt' => 'afile_type = ? AND afile_record_id = ?', 'vals' => array($fileType, $recordId));

        if ($record_subid > 0) {
            $deleteStatementArr = array('smt' => 'afile_type = ? AND afile_record_id = ? AND afile_record_subid = ?', 'vals' => array($fileType, $recordId, $record_subid));
        }

        if ($langId != -1) {
            /* delete lang Specific file */
            $deleteStatementArr = array('smt' => 'afile_type = ? AND afile_record_id = ? AND afile_lang_id = ? AND afile_screen = ?', 'vals' => array($fileType, $recordId, $langId, $screen));
            if ($record_subid > 0) {
                $deleteStatementArr = array('smt' => 'afile_type = ? AND afile_record_id = ? AND afile_record_subid = ? AND afile_lang_id = ?', 'vals' => array($fileType, $recordId, $record_subid, $langId));
            }
        }

        if (0 < $fileId) {
            /* delete single file */
            $deleteStatementArr = array('smt' => 'afile_type = ? AND afile_record_id = ? AND afile_id=?', 'vals' => array($fileType, $recordId, $fileId));
        }

        $db = FatApp::getDb();
        if (!$db->deleteRecords(static::DB_TBL, $deleteStatementArr)) {
            $this->error = $db->getError();
            return false;
        }

        $this->setRecordModifiedTime($fileType, $recordId);
        //@todo:: not deleted physical file from the system.
        return true;
    }

    public function extractZip($file)
    {
        rename($file, $file . '.zip');
        $zip = new ZipArchive();
        $res = $zip->open($file . '.zip');
        if ($res === true) {
            $zip->extractTo($file);
            $zip->close();
            unlink($file . '.zip');
            return true;
        } else {
            return false;
        }
    }

    public static function registerS3ClientStream()
    {
        if (strpos(CONF_UPLOADS_PATH, 's3://') === false) {
            return;
        }

        if (!defined('S3_KEY')) {
            trigger_error('S3 Settings not found.', E_USER_ERROR);
        }

        $client = S3Client::factory([
            'credentials' => ['key' => S3_KEY, 'secret' => S3_SECRET],
            'region' => S3_REGION,
            'version' => 'latest'
        ]);
        $client->registerStreamWrapper();
    }

    public static function setTimeParam($dateTime = '')
    {
        if (empty($dateTime)) {
            return;
        }

        $time = strtotime($dateTime);
        if (0 < $time) {
            return '?t=' . $time;
        }
        $time = strtotime(date('Y-m-d'));
        return '?t=' . $time;
    }

    public static function uploadErrorMessage($code, $langId)
    {
        switch ($code) {
            case UPLOAD_ERR_INI_SIZE:
                $message = Labels::getLabel("ERR_THE_UPLOADED_FILE_EXCEEDS_THE_UPLOAD_MAX_FILESIZE_DIRECTIVE_IN_PHP.INI", $langId);
                break;
            case UPLOAD_ERR_FORM_SIZE:
                $message = Labels::getLabel("ERR_THE_UPLOADED_FILE_EXCEEDS_THE_MAX_FILE_SIZE_DIRECTIVE_THAT_WAS_SPECIFIED_IN_THE_HTML_FORM", $langId);
                break;
            case UPLOAD_ERR_PARTIAL:
                $message = Labels::getLabel("ERR_THE_UPLOADED_FILE_WAS_ONLY_PARTIALLY_UPLOADED", $langId);
                break;
            case UPLOAD_ERR_NO_FILE:
                $message = Labels::getLabel("ERR_NO_FILE_WAS_UPLOADED", $langId);
                break;
            case UPLOAD_ERR_NO_TMP_DIR:
                $message = Labels::getLabel("ERR_MISSING_A_TEMPORARY_FOLDER", $langId);
                break;
            case UPLOAD_ERR_CANT_WRITE:
                $message = Labels::getLabel("ERR_FAILED_TO_WRITE_FILE_TO_DISK", $langId);
                break;
            case UPLOAD_ERR_EXTENSION:
                $message = Labels::getLabel("ERR_FILE_UPLOAD_STOPPED_BY_EXTENSION", $langId);
                break;

            default:
                $message = Labels::getLabel("ERR_UNKNOWN_UPLOAD_ERROR", $langId);
                break;
        }
        return $message;
    }

    public static function getProductPreviewVideoUrl($afileId)
    {
        $mediaPath = '';

        $res = static::getAttributesById($afileId);

        if (false === $res) {
            return $mediaPath;
        }

        if ($res['afile_type'] !== static::FILETYPE_SELLER_PRODUCT_DIGITAL_DOWNLOAD_PREVIEW) {
            return $mediaPath;
        }

        $mediaPath =  LibHelper::generateFullUrl('image', 'productVideo', array($afileId)) . '?' . time();
        /* if (defined('S3_SECRET') && !empty(S3_SECRET)) {
            $mediaPath = self::FILETYPE_PRODUCT_VIDEOS_PATH . $file_row['afile_physical_path'];
        } */

        return $mediaPath;
    }

    public static function getVideo($path)
    {
        if (empty($path)) {
            return '';
        }
        $path = CONF_UPLOADS_PATH . $path;

        $fileMimeType = mime_content_type($path);
        header("Content-Type: " . $fileMimeType);
        header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
        /* header("Expires: Mon, 26 Jul 1997 05:00:00 GMT"); */
        header("Accept-Ranges: bytes");
        header("Content-Length: " . filesize($path));
        return readfile($path);
    }

    public static function setNamePrefix($imageName = '', &$sizeType = '')
    {
        if ('' != $sizeType && substr(strtoupper($sizeType), 0, 4) == 'WEBP') {
            $sizeType = substr($sizeType, 4);
            return 'webp/' . $imageName;
        }
        return $imageName;
    }

    public static function setRecordModifiedTime(int $fileType, int $recordId)
    {
        $recordObj = false;
        switch ($fileType) {
            case self::FILETYPE_PRODUCT_IMAGE:
                $recordObj = new Product($recordId);
                break;
            case self::FILETYPE_SHOP_LOGO:
            case self::FILETYPE_SHOP_BANNER:
            case self::FILETYPE_SHOP_BACKGROUND_IMAGE:
                $recordObj = new Shop($recordId);
                break;
            case self::FILETYPE_BRAND_LOGO:
            case self::FILETYPE_BRAND_IMAGE:
                $recordObj = new Brand($recordId);
                break;
            case self::FILETYPE_USER_IMAGE:
            case self::FILETYPE_USER_PROFILE_IMAGE:
                $recordObj = new User($recordId);
                break;
            case self::FILETYPE_BLOG_POST_IMAGE:
                $recordObj = new BlogPost($recordId);
                break;
            case self::FILETYPE_COLLECTION_IMAGE:
            case self::FILETYPE_COLLECTION_BG_IMAGE:
                $recordObj = new Collections($recordId);
                break;
            case self::FILETYPE_CATEGORY_ICON:
            case self::FILETYPE_CATEGORY_THUMB:
            case self::FILETYPE_CATEGORY_BANNER:
            case self::FILETYPE_CATEGORY_IMAGE:
            case self::FILETYPE_PRODCAT_IMAGE:
                $recordObj = new ProductCategory($recordId);
                break;
            case self::FILETYPE_BADGE:
                $recordObj = new Badge($recordId);
                break;
            case self::FILETYPE_SHOP_COLLECTION_IMAGE:
                $recordObj = new ShopCollection($recordId);
                break;
            case self::FILETYPE_SELLER_PAGE_SLOGAN_BG_IMAGE:
            case self::FILETYPE_ADVERTISER_PAGE_SLOGAN_BG_IMAGE:
            case self::FILETYPE_AFFILIATE_PAGE_SLOGAN_BG_IMAGE:
                $recordObj = new Extrapage($recordId);
                break;
            case self::FILETYPE_BANNER:
                $recordObj = new Banner($recordId);
                break;
        }

        if (false != $recordObj) {
            return $recordObj->updateModifiedTime();
        }
        return false;
    }
    public static function getLogoImageTypeArr(int $langId): array
    {
        $svgImageTypeArr = CacheHelper::get('svgImageTypeArr' . $langId, CONF_DEF_CACHE_TIME, '.txt');
        if ($svgImageTypeArr) {
            return json_decode($svgImageTypeArr, true);
        }
        $arr = [
            self::FILE_ATTACHMENT_TYPE_OTHER => Labels::getLabel('LBL_NON_SVG', $langId),
            self::FILE_ATTACHMENT_TYPE_SVG => Labels::getLabel('LBL_SVG', $langId),
        ];

        CacheHelper::create('svgImageTypeArr' . $langId, json_encode($arr), CacheHelper::TYPE_LABELS);
        return $arr;
    }

    public static function getSvgFileType()
    {
        return [
            AttachedFile::FILETYPE_ADMIN_LOGO,
            AttachedFile::FILETYPE_FRONT_LOGO,
            AttachedFile::FILETYPE_SOCIAL_FEED_IMAGE,
            AttachedFile::FILETYPE_PAYMENT_PAGE_LOGO,
            AttachedFile::FILETYPE_MOBILE_LOGO,
            AttachedFile::FILETYPE_META_IMAGE
        ];
    }

    public static function isSvgType(array $file): bool
    {
        return ('image/svg+xml' == $file['type'] && 'svg' == pathinfo($file['name'], PATHINFO_EXTENSION));
    }

    public static function getFileAttachmentTypeArr(int $langId): array
    {
        $fileAttachmentTypeArr = CacheHelper::get('fileAttachmentTypeArr' . $langId, CONF_DEF_CACHE_TIME, '.txt');
        if ($fileAttachmentTypeArr) {
            return json_decode($fileAttachmentTypeArr, true);
        }
        $arr = [
            self::FILE_ATTACHMENT_TYPE_OTHER => Labels::getLabel('LBL_OTHER', $langId),
            self::FILE_ATTACHMENT_TYPE_SVG => Labels::getLabel('LBL_SVG', $langId),
            self::FILE_ATTACHMENT_TYPE_VIDEO => Labels::getLabel('LBL_VIDEO', $langId),
        ];

        CacheHelper::create('fileAttachmentTypeArr' . $langId, json_encode($arr), CacheHelper::TYPE_LABELS);
        return $arr;
    }
}
