<?php
class HtmlHelper
{
    public const SUCCESS = 1;
    public const WARNING = 2;
    public const DANGER = 3;
    public const PRIMARY = 4;
    public const INFO = 5;

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

    public static function configureSwitchForCheckboxStatic($name, $value, $attributes = '', $caption = '')
    {
        $name = !empty($name) ? 'name="' . $name . '"' : '';
        $value = !empty($value) ? 'value="' . $value . '"' : '';
        return '<label class="switch switch-sm switch-icon">
                    <input data-field-caption="' . $caption . '" type="checkbox" ' . $name . ' ' . $value . ' ' . $attributes . '>
                    <span class="input-helper"></span>
                    ' . $caption . '
                </label>';
    }

    public static function configureSwitchForRadio($fld, $msg = '')
    {
        $fld->developerTags['rdLabelAttributes'] = ['class' => 'radio'];
        if (!empty($msg)) {
            $fld->htmlAfterField = '<span class="form-text text-muted">' . $msg . '</span>';
        }
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
                $fld->addOptionListTagAttribute('class', 'list-radio');
                self::configureSwitchForRadio($fld);
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
                'class' => 'form-label',
            ], $caption);
        }

        if ($fld->requirements()->isRequired()) {
            $label->appendElement('span', [
                'class' => 'spn_must_field',
            ], '*');
        }

        if (!empty($labelInfoText)) {
            $label->appendElement('i', [
                'class' => 'fas fa-exclamation-triangle',
                'data-bs-toggle' => 'tooltip',
                'data-original-title' => $labelInfoText,
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

    public static function addStatusBtnHtml(bool $canEdit, int $recordId, int $status, bool $disabled = false, string $title = '')
    {
        $statusAct = ($canEdit) ? 'updateStatus(event, this, ' . $recordId . ', ' . ((int) !$status) . ')' : 'return false;';
        $statusClass = ($canEdit) ? '' : 'disabled';
        $disabled = ($disabled) ? 'disabled' : '';
        $checked = applicationConstants::ACTIVE == $status ? 'checked' : '';
        return '<label class="switch switch-sm switch-icon" title="' . $title . '" data-bs-toggle="tooltip" data-placement="top">
                    <input type="checkbox" data-old-status="' . $status . '" value="' . $recordId . '" ' . $checked . ' ' . $disabled . ' onclick="' . $statusAct . '" ' . $statusClass . '>
                    <span class="input-helper"></span>
                </label>';
    }

    public static function getTheDay(string $date, int $langId)
    {
        $currDate = strtotime(date("Y-m-d H:i:s"));
        $theDate = strtotime($date);
        $diff = floor(($currDate - $theDate) / (60 * 60 * 24));
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

    public static function configureCheckboxLabel(&$frm, $fldName)
    {
        $fld = $frm->getField($fldName);
        $fld->developerTags['noCaptionTag'] = true;
        $fld->developerTags['cbLabelAttributes'] = ['class' => 'checkbox'];
        $fld->developerTags['cbHtmlAfterCheckbox'] = '<span class="input-helper"></span>';
    }

    public static function setFieldEncryptedValue($fld, $value)
    {
        $fld->developerTags['fldWidthValues'] = ['cover position-relative', null, null, null];
        $fld->setFieldTagAttribute('data-encrypted-value', $value);
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
    /*
        attach Transalate Iconwith select box
    */
    public static function attachTransalateIcon(object $langFld, int $langId, string $onclickFn)
    {
        $translatorSubscriptionKey = FatApp::getConfig('CONF_TRANSLATOR_SUBSCRIPTION_KEY', FatUtility::VAR_STRING, '');
        if (!empty($translatorSubscriptionKey) && $langId != CommonHelper::getDefaultFormLangId()) {
            $langFld->developerTags['fldWidthValues'] = ['d-flex', '', '', ''];
            $langFld->htmlAfterField = '<a href="javascript:void(0);" onclick="' . $onclickFn . '" class="btn" title="' .  Labels::getLabel('BTN_AUTOFILL_LANGUAGE_DATA', $langId) . '">
                                <svg class="svg" width="18" height="18">
                                    <use xlink:href="' . CONF_WEBROOT_URL . 'images/retina/sprite.yokart.svg' . AttachedFile::setTimeParam(RELEASE_DATE) . '#icon-translate">
                                    </use>
                                </svg>
                            </a>';
        }
    }

    public static function getIdentifierText($identifier, $langId)
    {

        return Labels::getLabel('LBL_SYSTEM_IDENTIFIER', $langId) . " : " . $identifier;
    }

    public static function displayWordsFirstLetter($keyword, int $len = 2)
    {
        $titleArr = explode(' ', $keyword);
        $title = '';
        foreach ($titleArr as $val) {
            $title .= mb_substr($val, 0, 1);
            if (mb_strlen($title) == $len) {
                break;
            }
        }
        return strtoupper($title);
    }

    public static function getSuccessMessageHtml(string $message): string
    {
        return '<div class="alert alert-success" role="alert">
                    <div class="alert-icon"><i class="flaticon-warning"></i></div>
                    <div class="alert-text">' . $message . '</div>
                </div>';
    }

    public static function getInfoMessageHtml(string $message): string
    {
        return '<div class="alert alert-info" role="alert">
                    <div class="alert-icon"><i class="flaticon-warning"></i></div>
                    <div class="alert-text">' . $message . '</div>
                </div>';
    }

    public static function getErrorMessageHtml(string $message, string $icon = ''): string
    {
        $icon = empty($icon) ? '<svg class="svg" height="18" width="18">
                                    <use xlink:href="' . CONF_WEBROOT_URL . 'images/retina/sprite.svg' . AttachedFile::setTimeParam(RELEASE_DATE) . '#warning">
                                    </use>
                                </svg>' :  $icon;
        return '<div class="alert alert-danger" role="alert">
                    <div class="alert-icon">' . $icon . '</div>
                    <div class="alert-text">' . $message . '</div>
                </div>';
    }

    public static function getImgDimParm(int $dimensionType, string $sizeType)
    {
        $dimensions = ImageDimension::getData($dimensionType, $sizeType);
        return ' data-aspect-ratio="' . $dimensions[$sizeType]['aspectRatio'] . '" ';
        // return ' data-aspect-ratio="' . $dimensions[$sizeType]['aspectRatio'] . '"  width="' . $dimensions[ImageDimension::WIDTH] . '" height="' . $dimensions[ImageDimension::HEIGHT] . '"';
    }

    public static function getModalStructure(string $id, string $title, string $body)
    {
        return '<div class="modal fade" id="' . $id . '" tabindex="-1" role="dialog" aria-hidden="true"><div class="modal-dialog modal-dialog-centered" role="document"><div class="modal-content"><div class="modal-header"><h5 class="modal-title">' . $title . '</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div><div class="modal-body"><div class="cms"><p>' . $body . '</p></div></div></div></div></div>';
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
            return '<date>0000-00-00</date>';
        }
        $timezone = FatApp::getConfig('CONF_TIMEZONE', FatUtility::VAR_STRING, date_default_timezone_get());
        $timeFormat = FatApp::getConfig('CONF_DATE_FORMAT_TIME', FatUtility::VAR_STRING, 'H:i');
        $formattedDT = FatDate::format($dateTime, $showTime, $usetimezone, $timezone);

        if (false === $showTime) {
            return '<date>' . $formattedDT . '</date>';
        }

        $time = date($timeFormat, strtotime($dateTime));
        $date = FatDate::format($dateTime, false, $usetimezone, $timezone);
        return '<p class="date">' . $date . '
                    <time datetime="' . $dateTime . '">' . $time . '</time>
                </p>';
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
}
