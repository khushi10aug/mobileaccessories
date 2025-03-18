<?php
class HtmlHelper
{
    public const SUCCESS = 1;
    public const WARNING = 2;
    public const DANGER = 3;
    public const PRIMARY = 4;
    public const INFO = 5;

    public const RECORD_COUNT_LIMIT = 11;

    public static function getLoader()
    {
        return '<div class="table-processing">
                    <div class="spinner spinner--sm spinner--brand"></div>
                </div>';
    }

    public static function getDefaultActionItems(array $fields, object $obj = null, int $langId = 0)
    {
        if (1 > $langId) {
            $langId = CommonHelper::getLangId();
        }

        $actionBtnArr = [
            'newRecordBtn' => true,
            'newRecordBtnAttrs' => [],
            'headerHtmlContent'  => NULL,
            'deleteButton' => false,
            'statusButtons' => false,
            'columnButtons' => false,
            'performBulkAction' => false,
            'bulkActionFormHiddenFields' => ['status' => ''],
            'formAction' => 'toggleBulkStatuses',
            'siteLangId' => $langId,
            'otherButtons' => [],
            'htmlContent'  => NULL,
            'searchFrmTemplate' => '_partial/listing/listing-search-form.php',
            'searchListingPage' => FatUtility::camel2dashed(LibHelper::getControllerName()) . '/search.php'
        ];

        if (null == $obj) {
            return $actionBtnArr;
        }

        if (array_key_exists($obj::tblFld('active'), $fields)) {
            $actionBtnArr = array_merge($actionBtnArr, ['performBulkAction' => true, 'statusButtons' => true]);
        }

        return $actionBtnArr;
    }

    public static function getDefaultSortingClass($key, $sortBy, $sortOrder)
    {
        if ($key != $sortBy) {
            return '';
        }

        return (($sortOrder == applicationConstants::SORT_ASC) ? 'sorting_desc' : 'sorting_asc');
    }

    public static function formatFormFields(Form &$frm, $col = 12)
    {
        $frm->setCustomRendererClass('FormRendererBS');
        /* For Each Row On Above Elements */
        $frm->developerTags['colWidthClassesDefault'] = [null, 'col-md-', null, null];
        $frm->developerTags['colWidthValuesDefault'] = [null, $col, null, null];
        /* For Each Row On Above Elements */

        /* For Input Fields */
        $frm->developerTags['fldWidthClassesDefault'] = ['', '', '', ''];
        $frm->developerTags['fldWidthValuesDefault'] = ['', '', '', ''];
        /* For Input Fields */

        /* For Labels Fields */
        $frm->developerTags['labelWidthClassesDefault'] = ['label', 'label', 'label', 'label'];
        $frm->developerTags['labelWidthValuesDefault'] = ['', '', '', ''];
        /* For Labels Fields */

        /* Group Label and Input field. */
        $frm->developerTags['fieldWrapperRowExtraClassDefault'] = $frm->developerTags['fieldWrapperRowExtraClassDefault'] ?? 'form-group';
        /* Group Label and Input field. */
    }

    public static function getTheDay(string $date, int $langId)
    {
        $currDate = strtotime(date("Y-m-d H:i:s"));
        $theDate = strtotime($date);
        $diff = round(($currDate - $theDate) / (60 * 60 * 24));
        switch ($diff) {
            case 0:
                return Labels::getLabel('LBL_TODAY', $langId);
                break;
            case 1:
                return Labels::getLabel('LBL_YESTERDAY', $langId);
                break;
            case 2:
            case 3:
            case 4:
                return CommonHelper::replaceStringData(Labels::getLabel('LBL_{COUNT}_DAYS_AGO', $langId), ['{COUNT}' => $diff]);
                break;
            default:
                return FatDate::format($date);
                break;
        }
    }

    private static function normalizeDatetimeValue($value)
    {
        try {

            if (
                ($timestamp = DateTime::createFromFormat(
                    'Y-m-d|',
                    $value
                )
                ) !== false
            ) {
                // try Y-m-d format (support invalid dates like 2012-13-01)
                return  $timestamp;
            }
            if (
                ($timestamp = DateTime::createFromFormat(
                    'Y-m-d H:i:s',
                    $value
                )
                ) !== false
            ) {
                // try Y-m-d H:i:s format (support invalid dates like 2012-13-01 12:63:12)
                return  $timestamp;
            }

            return new DateTime($value);
        } catch (\Exception $e) {
            throw new InvalidArgumentException("'$value' is not a valid date time value: " . $e->getMessage()
                . "\n" . print_r(DateTime::getLastErrors(), true), $e->getCode(), $e);
        }
    }

    /**
     * Formats the value as the time interval between a date and now in human readable form. 
     * @return string the formatted result.
     * @throws InvalidArgumentException if the input value can not be evaluated as a date value.
     */

    public static function getRelativeTime($datetime, $langId)
    {
        $timestamp = self::normalizeDatetimeValue($datetime);
        $dateNow = new DateTime('now');
        $interval = $timestamp->diff($dateNow);

        if ($interval->y >= 1 || $interval->m >= 1 || $interval->d >= 1) {
            return FatDate::format($datetime);
        }
        if ($interval->invert) {
            if ($interval->h >= 1) {
                $str =  $interval->h == 1 ? Labels::getLabel('LBL_IN_AN_HOUR', $langId) :  Labels::getLabel('LBL_IN_{INTERVAL}_HOURS', $langId);
                return str_replace("{interval}", $interval->h, $str);
            }
            if ($interval->i >= 1) {
                $str =  $interval->i == 1 ? Labels::getLabel('LBL_IN_A_MINUTE', $langId) :  Labels::getLabel('LBL_IN_{INTERVAL}_MINUTES', $langId);
                return str_replace("{interval}", $interval->i, $str);
            }

            if ($interval->s == 0) {
                return Labels::getLabel('LBL_JUST_NOW', $langId);
            }

            $str =  $interval->i == 1 ? Labels::getLabel('LBL_IN_A_SECOND', $langId) :  Labels::getLabel('LBL_IN_{INTERVAL}_SECONDS', $langId);
            return str_replace("{interval}", $interval->i, $str);
        } else {

            if ($interval->h >= 1) {
                $str =  $interval->h == 1 ? Labels::getLabel('LBL_AN_HOUR_AGO', $langId) :  Labels::getLabel('LBL_{INTERVAL}_HOURS_AGO', $langId);
                return str_replace("{interval}", $interval->h, $str);
            }
            if ($interval->i >= 1) {
                $str =  $interval->i == 1 ? Labels::getLabel('LBL_A_MINUTE_AGO', $langId) :  Labels::getLabel('LBL_{INTERVAL}_MINUTES_AGO', $langId);
                return str_replace("{interval}", $interval->i, $str);
            }
            if ($interval->s == 0) {
                return Labels::getLabel('LBL_JUST_NOW', $langId);
            }

            $str =  $interval->i == 1 ? Labels::getLabel('LBL_IN_A_SECOND', $langId) :  Labels::getLabel('LBL_IN_{INTERVAL}_SECONDS_AGO', $langId);
            return str_replace("{interval}", $interval->i, $str);
        }
    }

    public static function getStatusHtml(int $status, string $msg): string
    {
        switch ($status) {
            case self::SUCCESS:
                return '<span class="badge badge-success">' . $msg . '</span>';
                break;
            case self::WARNING:
                return '<span class="badge badge-warning">' . $msg . '</span>';
                break;
            case self::DANGER:
                return '<span class="badge badge-danger">' . $msg . '</span>';
                break;
            case self::PRIMARY:
                return '<span class="badge badge-primary">' . $msg . '</span>';
                break;
            case self::INFO:
                return '<span class="badge badge-info">' . $msg . '</span>';
                break;
            default:
                return '<span class="badge badge-info">' . $msg . '</span>';
                break;
        }
    }

    public static function addButtonHtml(string $lbl, string $type = 'button', $name = '', $class = '', $onclick = ''): string
    {
        $name = (!empty($name) ? 'name="' . $name . '"' : '');
        $onclick = (!empty($onclick) ? 'onclick="' . $onclick . ';"' : '');
        $class = !empty($class) ? $class : 'btn btn-brand btn-wide btn-search submitBtnJs';
        return '<button type="' . $type . '" ' . $name . ' class="' . $class . '" ' . $onclick . '>' . $lbl . '</button>';
    }

    public static function addSearchButton(Form &$frm, string $lbl = '')
    {
        $lbl = empty($lbl) ? Labels::getLabel('FRM_SEARCH', CommonHelper::getLangId()) : $lbl;
        $frm->addHtml('', 'btn_submit', self::addButtonHtml($lbl, 'submit', 'btn_submit'));
    }

    public static function addClearButton(Form &$frm, string $btnClass = 'btn btn-link', string $lbl = '')
    {
        $lbl = empty($lbl) ? Labels::getLabel('FRM_CLEAR', CommonHelper::getLangId()) : $lbl;
        $frm->addHtml('', 'btn_clear', self::addButtonHtml($lbl, 'button', 'btn_clear', $btnClass, 'clearSearch()'));
    }

    public static function renderHiddenFields(Form $frmSearch)
    {
        foreach ($frmSearch->getAllFields() as $key => $frmFld) {
            if ('hidden' == $frmFld->fldType) {
                echo $frmSearch->getFieldHtml($frmFld->getName());
            }
        }
    }

    public static function getSuccessMessageHtml(string $message): string
    {
        return '<div class="alert alert-success" role="alert">
                    <div class="alert-icon"><i class="flaticon-warning"></i></div>
                    <div class="alert-text">' . $message . '</div>
                </div>';
    }

    public static function getErrorMessageHtml(string $message): string
    {
        return '<div class="alert alert-danger" role="alert">
                    <div class="alert-icon"><i class="flaticon-questions-circular-button"></i></div>
                    <div class="alert-text">' . $message . '</div>
                </div>';
    }

    public static function getCssStyleHtml(array $files = [], string $location = 'css'): string
    {
        $htm = '';
        foreach ($files as $fl) {
            $file = $location . '/' . $fl;
            $time = filemtime(CONF_THEME_PATH . $file);
            $cssFileLink = UrlHelper::getCachedUrl(UrlHelper::generateFileUrl('JsCss', $location, array(), '', false) . '&f=' . rawurlencode($file) . '&min=0&sid=' . $time, CONF_DEF_CACHE_TIME, '.css');
            $htm .= '<link rel="stylesheet" href="' . $cssFileLink . '"/>' . "\n";
        }
        return $htm;
    }

    public static function getJsScriptHtml(array $files = [], string $location = 'js'): string
    {
        $htm = '';
        foreach ($files as $fl) {
            $file = $location . '/' . $fl;
            $time = filemtime(CONF_THEME_PATH . $file);
            $jsFileLink = UrlHelper::getCachedUrl(UrlHelper::generateFileUrl('JsCss', $location, array(), '', false) . '&f=' . rawurlencode($file) . '&min=0&sid=' . $time, CONF_DEF_CACHE_TIME, '.js');
            $htm .= '<script language="javascript" type="text/javascript" src="' . $jsFileLink . '"></script>' . "\n";
        }
        return $htm;
    }

    public static function getDropZoneHtml($url, $headerClass = '', $callbackfn = '')
    {
        $str =  '<div class="dropzone ' . $headerClass . '">
                    <div class="upload_cover" onclick="$(this).parent().click();">                        
                            <div class="file-upload">
                                <img src="' . CONF_WEBROOT_URL . 'images/upload/upload_img.png">
                            </div>
                    </div>
                </div>
                <script>
                $.initDropZone("' . $url . '")
                .on("reset",function(event){    
                    if(!$(".dz-error").length){
                        $(".upload_cover").removeClass("hidden");     
                    }               
                })
                .on("addedfile",function(event){  
                    $(".upload_cover").addClass("hidden");
                })
                .on("error",function(event){  
                    $(".upload_cover").removeClass("hidden");    
                    this.removeFile(event);            
                    fcom.displayErrorMessage($(".dz-error-message").text());                                                         
                })               
                .on("success",function(event){                                 
                    $(".upload_cover").removeClass("hidden");                   
                })
                .on("sending", function(file, xhr, formData){';
        if (!empty($callbackfn)) {
            $str .= $callbackfn . '(file, xhr, formData)';;
        }
        $str .= '});                
                </script>';
        return $str;
    }

    public static function configureSwitchForCheckbox($fld, $msg = '')
    {
        $fld->developerTags['fldWidthValues'] = ['setting-block', null, null, null];
        $fld->developerTags['cbLabelAttributes'] = ['class' => 'switch switch-sm switch-icon'];
        $fld->developerTags['cbHtmlAfterCheckbox'] = '<span class="input-helper"></span>';
        if (!empty($msg)) {
            $fld->htmlAfterField = '<span class="form-text text-muted">' . $msg . '</span>';
        }
        $fld->developerTags['noCaptionTag'] = true;
    }

    public static function configureSwitchForRadio($fld, $msg = '')
    {
        $fld->developerTags['rdLabelAttributes'] = ['class' => 'radio'];
        if (!empty($msg)) {
            $fld->htmlAfterField = '<span class="form-text text-muted">' . $msg . '</span>';
        }
        $fld->developerTags['rdHtmlAfterRadio'] = '<i class="input-helper"></i>';
    }

    public static function configureRadioAsButton(&$frm, $fldName)
    {
        $fld = $frm->getField($fldName);
        $str = '<label class="label">' . $fld->getCaption() . '</label>
                    <div class="radio-button-group">';
        $opCount = 1;
        foreach ($fld->options as $opValue => $opName) {
            $opId = $fldName . "__" . $opCount;
            $str .= '<div class="item">
                    <input type="radio" name="' . $fldName . '" class="radio-button ' . $fld->getFieldTagAttribute('class') . '" id="' . $opId . '"  value="' . $opValue . '"  ' . ($opValue . '" ' . $opValue == $fld->value ? 'checked' : '') . ' >
                    <label for="' . $opId . '">' . $opName . '</label>
                </div>';
            $opCount++;
        }
        $str .= '</div>';

        $htmlFld = $frm->addHTML('', $fldName . '_html', $str);
        $frm->changeFieldPosition($htmlFld->getFormIndex(), $fld->getFormIndex());
        $frm->removeField($fld);
        $htmlFld->developerTags = $fld->developerTags;
        return $htmlFld;
    }

    public static function addFieldLabelInfo(&$frm, $fldName, $msg, $setFieldTagAttrs = [])
    {
        $str = self::getFieldHtml($frm, $fldName, 6, $setFieldTagAttrs, '', $msg, [], true);
        $fld = $frm->getField($fldName);

        $htmlFld = $frm->addHTML('', $fldName . '_html', $str);
        $frm->changeFieldPosition($htmlFld->getFormIndex(), $fld->getFormIndex());
        $frm->removeField($fld);
        $htmlFld->developerTags = $fld->developerTags;
        return $htmlFld;
    }

    /**
     * options array ex. [1 => 'optionName'];
     */
    public static function getRadioAsButtonHtml(string $fldName, string $caption, array $options, $selectedVal = '', $fldClass = '')
    {
        $str = '<label class="label">' . $caption . '</label>
                    <div class="radio-button-group">';
        $opCount = 1;
        foreach ($options as $opValue => $opName) {
            $opId = $fldName . "__" . $opCount;
            $str .= '<div class="item">
                    <input type="radio" name="' . $fldName . '" class="radio-button ' . $fldClass . '" id="' . $opId . '"  value="' . $opValue . '"  ' . ($opValue . '" ' . $selectedVal ? 'checked' : '') . ' >
                    <label for="' . $opId . '">' . $opName . '</label>
                </div>';
            $opCount++;
        }
        return $str .= '</div>';
    }

    /**
     * $imageArr ex. ['name' => 'fav.png','url'=>'imageurl' ,'afile_id'=> 66]
     */

    public static function getfileInputHtml(array $fileInputAttributes, int $langId, string $removeFn, string $editFn = '', $imageArr = [], $headerClass = '')
    {
        $str =  '<div class="dropzone ' . $headerClass . '">';
        if (1 > count($imageArr)) {
            $str .= ' 
                            <div class="dropzone-upload dropzoneUploadJs">                 
                                <div class="file-upload">
                                    <img src="' . CONF_WEBROOT_URL . 'images/upload/upload_img.png">                                
                                </div>
                                <div class="needsclick">
                                    <h3 class="dropzone-msg-title">' . Labels::getLabel("FRM_CLICK_HERE_TO_UPLOAD", $langId) . '</h3>                              
                                </div> 
                            </div>                                        
                        ';
        } else {
            $str .=
                '<div class="dropzone-uploaded dropzoneUploadedJs">
                                <img src="' . $imageArr['url'] . '" title=""  data-afile_id="' . ($imageArr['afile_id'] ?? 0) . '">    
                                <div class="dropzone-uploaded-action">
                                <ul class="actions">';
            if (!empty($editFn)) {
                $str .= '
                                    <li>
                                        <a href="javascript:void(0)"  onclick="' . $editFn . '" data-bs-toggle="tooltip" data-placement="top" title="' . Labels::getLabel('FRM_CLICK_HERE_TO_EDIT', $langId) . '">
                                            <svg class="svg" width="18" height="18">
                                                <use
                                                    xlink:href="' . CONF_WEBROOT_URL . 'images/retina/sprite-actions.svg' . AttachedFile::setTimeParam(RELEASE_DATE) . '#edit">
                                                </use>
                                            </svg>
                                        </a>
                                    </li>';
            }
            $str .= '<li>
                                            <a href="javascript:void(0)"  onclick="' . $removeFn . '" data-bs-toggle="tooltip" data-placement="top" title="' . Labels::getLabel('FRM_CLICK_HERE_TO_REMOVE', $langId) . '">
                                                <svg class="svg" width="18" height="18">
                                                    <use
                                                        xlink:href="' . CONF_WEBROOT_URL . 'images/retina/sprite-actions.svg' . AttachedFile::setTimeParam(RELEASE_DATE) . '#delete">
                                                    </use>
                                                </svg>
                                                </a>
                                        </li>
                                </ul></div>
                            </div>';
        }

        $str .= '<input name="dropzoneInput" data-fatreq="{&quot;required&quot;:false}" class="dropzone-input dropzoneInputJs ' . (count($imageArr) ? "hide" : "") . '" type="file"';
        foreach ($fileInputAttributes as $attrName => $attrVal) {
            $str .= ' ' . $attrName . '="' . $attrVal . '"';
        }
        $str .= '>';


        $str .= '</div>';
        return   $str;
    }

    public static function imageListCard(int $imageType, string $defaultImageName, int $recordId, int $recordSubid = 0, $updatedOn = NULL)
    {
        $images = AttachedFile::getMultipleAttachments($imageType, $recordId, $recordSubid, 0,  true,  0, 4);
        $str = '<div class="media-group featherLightGalleryJs">';
        $count  = 0;
        $dimensionType = ImageDimension::TYPE_PRODUCTS;
        foreach ($images as $key => $image) {
            switch ($imageType) {
                case AttachedFile::FILETYPE_PRODUCT_IMAGE:
                    $imgSrc = UrlHelper::generateFileUrl('image', 'product', array($recordId, ImageDimension::VIEW_MINI, 0, $image['afile_id'], 0), CONF_WEBROOT_FRONTEND);
                    $imgOrgSrc = UrlHelper::generateFileUrl('image', 'product', array($recordId, ImageDimension::VIEW_ORIGINAL, 0, $image['afile_id'], 0), CONF_WEBROOT_FRONTEND);
                    break;
                case AttachedFile::FILETYPE_CUSTOM_PRODUCT_IMAGE:
                    $imgSrc = UrlHelper::generateFileUrl('image', 'customProduct', array($recordId, ImageDimension::VIEW_MINI, $image['afile_id'], 0), CONF_WEBROOT_FRONTEND);
                    $imgOrgSrc = UrlHelper::generateFileUrl('image', 'customProduct', array($recordId, ImageDimension::VIEW_ORIGINAL, $image['afile_id'], 0), CONF_WEBROOT_FRONTEND);
                    break;
                default:
            }

            if ($count > 2) {
                $str .= ' 
                <span class="media media-sm media-circle"
                    data-bs-toggle="tooltip" data-skin="brand"
                    data-placement="top" title="">
                    <span>3+</span>
                </span>';
                break;
            }
            if ($updatedOn) {
                $uploadedTime = AttachedFile::setTimeParam($updatedOn);
                $imgSrc  = UrlHelper::getCachedUrl($imgSrc . $uploadedTime, CONF_IMG_CACHE_TIME, '.jpg');
                $imgOrgSrc  = UrlHelper::getCachedUrl($imgOrgSrc . $uploadedTime, CONF_IMG_CACHE_TIME, '.jpg');
            }
            $str .= '
                <span class="media media-sm media-circle"
                    data-bs-toggle="tooltip" data-skin="brand"
                    data-placement="top" title="' . (!empty($image['afile_attribute_title']) ? $image['afile_attribute_title'] : $defaultImageName) . '"
                    data-original-title="' . (!empty($image['afile_attribute_title']) ? $image['afile_attribute_title'] : $defaultImageName) . '">
                    <a href="' . $imgOrgSrc . '" data-featherlight="image">
                        <img ' . HtmlHelper::getImgDimParm($dimensionType, ImageDimension::VIEW_MINI) . '
                            src="' . $imgSrc . '"
                            alt="' . ($image['afile_attribute_alt'] ?? $defaultImageName) . '">
                    </a>
                </span>';
            $count++;
        }

        if (!count($images)) {
            $str .= '
            <span class="media media-sm media-circle"
                data-bs-toggle="tooltip" data-skin="brand"
                data-placement="top" 
                data-original-title="' . $defaultImageName . '">
                <img ' . HtmlHelper::getImgDimParm($dimensionType, ImageDimension::VIEW_MINI) . '
                    src="' . CONF_WEBROOT_FRONTEND . 'images/defaults/product_default_image.jpg"
                    alt="' . $defaultImageName . '">
            </span>';
        }

        $str .= '</div>';
        return  $str;
    }

    /**
     * getFieldHtml
     *
     * @param  Form $frm
     * @param  string $fldName
     * @param  int $col
     * @param  array $setFieldTagAttrs
     * @param  string $fieldInfoText
     * @param  string $labelInfoText : To show tooltip on label
     * @param  array $labelExtraArr : [
     *                                   'attr' => [
     *                                       'href' => 'javascript:void(0)',
     *                                       'onclick' => 'FN()',
     *                                       'title' => <TITLE>
     *                                   ],
     *                                   'label' => <LABEL>
     *                               ]
     * @param  bool $doNotAddFieldWrapper
     * @return string
     */
    public static function getFieldHtml(Form $frm, string $fldName, int $col = 6, array $setFieldTagAttrs = [],  string $fieldInfoText = '', string $labelInfoText = '', array $labelExtraArr = [], bool $doNotAddFieldWrapper = false): string
    {
        $fld = $frm->getField($fldName);
        if (null == $fld) {
            return '';
        }

        foreach ($setFieldTagAttrs as $attrkey => $attrVal) {
            $fld->setfieldTagAttribute($attrkey, $attrVal);
        }
        $caption = $fld->getCaption();

        switch ($fld->fldType) {
            case 'radio':
                $fld->addOptionListTagAttribute('class', 'list-radio product-type');
                HtmlHelper::configureSwitchForRadio($fld);
                break;
            case 'hidden':
                return $fld->getHtml();
                break;
        }

        if ($doNotAddFieldWrapper) {
            if (!empty($labelExtraArr)) {
                $mainDiv = $div = new HtmlElement('div', [
                    'class' => 'd-flex justify-content-between',
                ]);

                $label = $div->appendElement('label', [
                    'class' => 'label',
                ], $caption);
            } else {

                $mainDiv = $div = $label =  new HtmlElement('label', [
                    'class' => 'label',
                ], $caption);
            }
        } else {
            $mainDiv = new HtmlElement("div", [
                'class' => 'col-md-' . $col,
            ]);

            $div1 =  $div = $mainDiv->appendElement('div', [
                'class' => 'form-group',
            ]);

            if (!empty($labelExtraArr)) {
                $div =  $div->appendElement('div', [
                    'class' => 'd-flex justify-content-between',
                ]);
            }

            $label = $div->appendElement('label', [
                'class' => 'label',
            ], $caption);
        }

        if ($fld->requirements()->isRequired()) {
            $label->appendElement('span', [
                'class' => 'spn_must_field',
            ], '*');
        }

        if (!empty($labelInfoText)) {
            $label->appendElement('i', [
                'class' => 'fas fa-exclamation-circle',
                'data-bs-toggle' => 'tooltip',
                'title' => $labelInfoText,
            ]);
        }

        if (isset($labelExtraArr['attr']) && isset($labelExtraArr['label'])) {
            $div->appendElement('a', $labelExtraArr['attr'], $labelExtraArr['label']);
        }
        /*** label  ] */

        if (!empty($fieldInfoText)) {
            $fld->htmlAfterField = '<span class="form-text text-muted">' . $fieldInfoText . '</span>';
        }

        if ($doNotAddFieldWrapper) {
            return $mainDiv->getHtml() . (new HtmlElement('plaintext', [], $fld->getHtml(), true))->getHtml();
        } else {
            $div1->appendElement('plaintext', [], $fld->getHtml(), true);
            return $mainDiv->getHtml();
        }
    }

    public static function getModalStructure(string $id, string $title, string $body)
    {
        return '<div class="modal fade" id="' . $id . '" tabindex="-1" role="dialog" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered" role="document">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">' . $title . '</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <div class="cms">
                                    <p>' . $body . '</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>';
    }

    public static function addStatusBtnHtml(bool $canEdit, int $recordId, int $status, bool $disabled = false, string $title = '', string $callback = '')
    {
        $statusAct = ($canEdit) ? 'updateStatus(event, this, ' . $recordId . ', ' . ((int) !$status) . ',\'' . $callback . '\')' : 'return false;';
        $statusClass = ($canEdit) ? '' : 'disabled';
        $disabled = ($disabled) ? 'disabled' : '';
        $checked = applicationConstants::ACTIVE == $status ? 'checked' : '';
        return '<label class="switch switch-sm switch-icon" title="' . $title . '" data-bs-toggle="tooltip" data-placement="top">
                    <input type="checkbox" data-old-status="' . $status . '" value="' . $recordId . '" ' . $checked . ' ' . $disabled . ' onclick="' . $statusAct . '" ' . $statusClass . '>
                    <span class="input-helper"></span>
                </label>';
    }

    /**
     * Converts a date from yyyy-mm-dd[ hh:ii:ss] format (or any format supported by php DateTime) to format set with CONF_DATE_FORMAT and CONF_DATE_FORMAT_TIME if needed. Return Html.
     * 
     * @param string $dateTime   The date string to be displayed
     * @param bool $showTime     If time is to be included
     * @param bool $usetimezone  If to be converted to some timezone.
     * @param bool $timezone     In which timezone to convert. Supports php timezone strings.
     * @return string
     */
    public static function formatDateTime(string $dateTime, bool $showTime = false, bool $usetimezone = false, string $timezone = '')
    {
        if ('0000-00-00 00:00:00' ==  $dateTime || '0000-00-00' ==  $dateTime) {
            return '<p class="date">0000-00-00</p>';
        }
        $timezone = FatApp::getConfig('CONF_TIMEZONE', FatUtility::VAR_STRING, date_default_timezone_get());
        $timeFormat = FatApp::getConfig('CONF_DATE_FORMAT_TIME', FatUtility::VAR_STRING, 'H:i');
        $formattedDT = FatDate::format($dateTime, $showTime, $usetimezone, $timezone);

        if (false === $showTime) {
            return '<p class="date">' . $formattedDT . '</p>';
        }

        $time = date($timeFormat, strtotime($dateTime));
        $date = FatDate::format($dateTime, false, $usetimezone, $timezone);
        return '<p class="date">' . $date . '
                    <time>' . $time . '</time>
                </p>';
    }

    public static function configureCheckboxLabel(&$frm, $fldName)
    {
        $fld = $frm->getField($fldName);
        $fld->developerTags['noCaptionTag'] = true;
        $fld->developerTags['cbLabelAttributes'] = ['class' => 'checkbox'];
        $fld->developerTags['cbHtmlAfterCheckbox'] = '<span class="input-helper"></span>';
    }

    public static function seoFriendlyUrl($url)
    {
        return '<a href="' . $url . '" target="_blank">' . $url . '</a>';
    }

    public static function getIdentifierText($identifier, $langId)
    {

        return Labels::getLabel('LBL_SYSTEM_IDENTIFIER', $langId) . " : " . $identifier;
    }

    public static function addIdentierToFrm($fld, $identifier, int $langId = 0)
    {
        if (1 > $langId) {
            $langId = CommonHelper::getDefaultFormLangId();
        }

        $fld->addFieldTagAttribute('onkeyup', "getIdentifier(this);");
        $fld->htmlAfterField = "<small class='form-text text-muted'>" . HtmlHelper::getIdentifierText($identifier, $langId) . '</small>';
    }

    public static function displayNumberWithPlus(int $recordCount)
    {
        return ((self::RECORD_COUNT_LIMIT - 1) < $recordCount) ? $recordCount . '+' : $recordCount;
    }

    public static function getImgDimParm(int $dimensionType, string $sizeType)
    {
        $dimensions = ImageDimension::getData($dimensionType, $sizeType);
        return ' data-aspect-ratio="' . $dimensions[$sizeType]['aspectRatio'] . '"  width="' . $dimensions[ImageDimension::WIDTH] . '" hieght="' . $dimensions[ImageDimension::HEIGHT] . '"';
    }

    public static function getHtml($tplPath = '', $data = '')
    {
        $template = new FatTemplate('', '');
        $template->set('data', $data);
        return $template->render(false, false, $tplPath, true, false);
    }
}
