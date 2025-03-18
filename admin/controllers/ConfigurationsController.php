<?php
class ConfigurationsController extends ListingBaseController
{
    /* these variables must be only those which will store array type data and will saved as serialized array [*/
    private array $serializeArrayValues = ['CONF_VENDOR_ORDER_STATUS', 'CONF_BUYER_ORDER_STATUS', 'CONF_PROCESSING_ORDER_STATUS', 'CONF_COMPLETED_ORDER_STATUS', 'CONF_REVIEW_READY_ORDER_STATUS', 'CONF_ALLOW_CANCELLATION_ORDER_STATUS', 'CONF_DIGITAL_ALLOW_CANCELLATION_ORDER_STATUS', 'CONF_RETURN_EXCHANGE_READY_ORDER_STATUS', 'CONF_DIGITAL_RETURN_READY_ORDER_STATUS', 'CONF_ENABLE_DIGITAL_DOWNLOADS', 'CONF_PURCHASE_ORDER_STATUS', 'CONF_BUYING_YEAR_REWARD_ORDER_STATUS', 'CONF_SUBSCRIPTION_ORDER_STATUS', 'CONF_SELLER_SUBSCRIPTION_STATUS', /* 'CONF_BADGE_COUNT_ORDER_STATUS', */ 'CONF_PRODUCT_IS_ON_ORDER_STATUSES', 'CONF_ALLOW_FILES_TO_ADD_WITH_ORDER_STATUSES'];
    /* ] */
    protected $pageKey = 'GENERAL_CONFIGURATION';

    public function __construct($action)
    {
        parent::__construct($action);
        $this->objPrivilege->canViewGeneralSettings();
        $this->set("includeEditor", true);
    }

    public function index($activeTab = Configurations::FORM_LOCAL)
    {
        $tabs = Configurations::getTabsArr($this->siteLangId);
        if (!array_key_exists($activeTab, $tabs)) {
            $activeTab = Configurations::FORM_LOCAL;
        }

        $this->setGeneralForm($activeTab, CommonHelper::getDefaultFormLangId());
        $svgIconNames = Configurations::getSvgIconNames();
        $pageData = PageLanguageData::getAttributesByKey($this->pageKey, $this->siteLangId);
        $pageTitle = $pageData['plang_title'] ?? LibHelper::getControllerName(true);

        $this->set('pageData', $pageData);
        $this->set('pageTitle', $pageTitle);
        $this->set('svgIconNames', $svgIconNames);
        $this->_template->addCss('css/cropper.css');
        $this->_template->addJs('js/cropper.js');
        $this->_template->addJs('js/cropper-main.js');
        $this->set('defaultLangId', CommonHelper::getDefaultFormLangId());
        $this->set('activeTab', $activeTab);
        $this->set('tourStep', SiteTourHelper::getStepIndex());
        $this->_template->addJs('js/jscolor.js');
        $this->_template->render(true, true, NULL, false, false);
    }

    public function setGeneralForm($frmType, $langId)
    {
        $record = Configurations::getConfigurations();
        $arrayValues = array();

        foreach ($this->serializeArrayValues as $val) {
            if (array_key_exists($val, $record)) {
                $data = @unserialize($record[$val]);
                if ($data !== false) {
                    $arrayValues[$val] = $data;
                    unset($record[$val]);
                }
            } else {
                $arrayValues[$val] = array();
            }
        }

        $frm = $this->getForm($frmType, $langId);
        if (array_key_exists('CONF_SITE_FAX_DCODE', $record)) {
            $record['CONF_SITE_FAX_dcode'] = $record['CONF_SITE_FAX_DCODE'];
            unset($record['CONF_SITE_FAX_DCODE']);
        }

        if (array_key_exists('CONF_SITE_PHONE_DCODE', $record)) {
            $record['CONF_SITE_PHONE_dcode'] = $record['CONF_SITE_PHONE_DCODE'];
            unset($record['CONF_SITE_PHONE_DCODE']);
        }

        $frm->fill(($record + $arrayValues));

        $this->set('frm', $frm);
        $this->set('record', $record);
        $this->set('frmType', $frmType);

        $dispLangTab = false;
        if (in_array($frmType, Configurations::getLangTypeFormArr())) {
            $dispLangTab = true;
            $this->set('languagesNames', Language::getAllNames());
        }

        $redirection = Configurations::redirectionLink($frmType);
        if (!empty($redirection)) {
            $headerHtmlContent = '<a href="' . $redirection['link'] . '" class="btn btn-icon btn-outline-gray ms-2" title="" data-bs-toggle="tooltip" data-placement="top" data-bs-original-title="' . $redirection['title'] . '">
                                    <svg class="svg btn-icon-start" width="18" height="18">
                                        <use xlink:href="' . CONF_WEBROOT_URL . 'images/retina/sprite-actions.svg' . AttachedFile::setTimeParam(RELEASE_DATE) . '#gear">
                                        </use>
                                    </svg>                                
                                </a>';
            $this->set('headerHtmlContent', $headerHtmlContent);
        }

        $tabs = Configurations::getTabsArr($langId);
        $tabsMsgArr = Configurations::getTabsMsgArr($langId);
        $this->set('tabs', $tabs);
        $this->set('tabsMsgArr', $tabsMsgArr);
        $this->set('dispLangTab', $dispLangTab);
        $this->set('lang_id', $langId);
        $this->set('formLayout', Language::getLayoutDirection($langId));
    }

    public function form(int $frmType, int $langId = 0)
    {
        if (1 > $langId) {
            $langId = CommonHelper::getDefaultFormLangId();
        }
        $this->setGeneralForm($frmType, $langId);
        $this->set('html', $this->_template->render(false, false, NULL, true, false));
        $this->_template->render(false, false, 'json-success.php', true, false);
    }

    public function setup()
    {
        $this->objPrivilege->canEditGeneralSettings();

        $post = FatApp::getPostedData();
        $langId  = FatApp::getPostedData('lang_id', FatUtility::VAR_INT, 0);
        $user_state_id = 0;
        if (isset($post['CONF_STATE'])) {
            $user_state_id = FatUtility::int($post['CONF_STATE']);
        }

        if (isset($post['CONF_GEO_DEFAULT_STATE'])) {
            $geoState = $post['CONF_GEO_DEFAULT_STATE'];
        }

        $frmType = FatUtility::int($post['form_type']);

        if (1 > $frmType) {
            LibHelper::exitWithError($this->str_invalid_request, true);
        }

        if (in_array($frmType, Configurations::getLangTypeFormArr())) {
            if (1 > $langId) {
                LibHelper::exitWithError($this->str_invalid_request, true);
            }
        }

        $frm = $this->getForm($frmType, $langId);
        $post = $frm->getFormDataFromArray($post);
        if ($user_state_id > 0) {
            $post['CONF_STATE'] = $user_state_id;
        }
        if (false === $post) {
            LibHelper::exitWithError(current($frm->getValidationErrors()), true);
        }

        if (isset($post['CONF_RFQ_MODULE']) || isset($post['CONF_HIDE_PRICES'])) {
            $rfqModule = isset($post['CONF_RFQ_MODULE']) ? $post['CONF_RFQ_MODULE'] : FatApp::getConfig('CONF_RFQ_MODULE', FatUtility::VAR_INT, 0);
            $hidePrice = isset($post['CONF_HIDE_PRICES']) ? $post['CONF_HIDE_PRICES'] : FatApp::getConfig('CONF_HIDE_PRICES', FatUtility::VAR_INT, 0);
            if (applicationConstants::INACTIVE == $rfqModule && applicationConstants::ACTIVE == $hidePrice) {
                LibHelper::exitWithError(Labels::getLabel('LBL_RFQ_MODULE_SHOULD_BE_ENABLED_ALONG_WITH_HIDE_PRICE_SETTING.', $this->siteLangId), true);
            }
        }

        unset($post['form_type']);
        unset($post['btn_submit']);
        foreach ($this->serializeArrayValues as $val) {
            if (array_key_exists($val, $post)) {
                if (is_array($post[$val])) {
                    $post[$val] = serialize($post[$val]);
                }
            } else {
                if (isset($post[$val])) {
                    $post[$val] = 0;
                }
            }
        }

        if (!empty($geoState)) {
            $post['CONF_GEO_DEFAULT_STATE'] = $geoState;
        }

        $record = new Configurations();

        if (isset($post["CONF_SEND_SMTP_EMAIL"]) && $post["CONF_SEND_EMAIL"] && $post["CONF_SEND_SMTP_EMAIL"] && (($post["CONF_SEND_SMTP_EMAIL"] != FatApp::getConfig("CONF_SEND_SMTP_EMAIL")) || ($post["CONF_SMTP_HOST"] != FatApp::getConfig("CONF_SMTP_HOST")) || ($post["CONF_SMTP_PORT"] != FatApp::getConfig("CONF_SMTP_PORT")) || ($post["CONF_SMTP_USERNAME"] != FatApp::getConfig("CONF_SMTP_USERNAME")) || ($post["CONF_SMTP_SECURE"] != FatApp::getConfig("CONF_SMTP_SECURE")) || ($post["CONF_SMTP_PASSWORD"] != FatApp::getConfig("CONF_SMTP_PASSWORD")))) {
            $smtp_arr = [
                "host" => $post["CONF_SMTP_HOST"],
                "port" => $post["CONF_SMTP_PORT"],
                "username" => $post["CONF_SMTP_USERNAME"],
                "password" => $post["CONF_SMTP_PASSWORD"],
                "secure" => $post["CONF_SMTP_SECURE"]
            ];

            if (EmailHandler::sendSmtpTestEmail($this->siteLangId, $smtp_arr)) {
                Message::addMessage(Labels::getLabel('MSG_WE_HAVE_SENT_A_TEST_EMAIL_TO_ADMINISTRATOR_ACCOUNT' . FatApp::getConfig("CONF_SITE_OWNER_EMAIL"), $this->siteLangId));
            } else {
                unset($post["CONF_SEND_SMTP_EMAIL"]);
                foreach ($smtp_arr as $skey => $sval) {
                    unset($post['CONF_SMTP_' . strtoupper($skey)]);
                }
                LibHelper::exitWithError(Labels::getLabel("ERR_SMTP_SETTINGS_PROVIDED_IS_INVALID_OR_UNABLE_TO_SEND_EMAIL_SO_WE_HAVE_NOT_SAVED_SMTP_SETTINGS", $this->siteLangId), true);
            }
        }

        if (isset($post['CONF_USE_SSL']) && $post['CONF_USE_SSL'] == 1) {
            if (!$this->isSslEnabled()) {
                if ($post['CONF_USE_SSL'] != FatApp::getConfig('CONF_USE_SSL')) {
                    LibHelper::exitWithError(Labels::getLabel('ERR_SSL_NOT_INSTALLED_FOR_WEBSITE_TRY_TO_SAVE_DATA_WITHOUT_ENABLING_SSL', $this->siteLangId), true);
                }

                unset($post['CONF_USE_SSL']);
            }
        }

        if (isset($post['CONF_SITE_ROBOTS_TXT'])) {
            $filePath = CONF_UPLOADS_PATH . 'robots.txt';
            $robotfile = fopen($filePath, "w");
            fwrite($robotfile, $post['CONF_SITE_ROBOTS_TXT']);
            fclose($robotfile);
        }

        if (array_key_exists('CONF_CURRENCY', $post)) {
            $data = Currency::getAttributesById($post['CONF_CURRENCY']);
            if (empty($data) || ($data['currency_value'] * 1) != 1) {
                LibHelper::exitWithError(Labels::getLabel('ERR_PLEASE_SET_DEFAULT_CURRENCY_VALUE_TO_1', $this->siteLangId), true);
            }
        }

        if (isset($post['CONF_MIN_COD_ORDER_LIMIT']) && $post['CONF_MIN_COD_ORDER_LIMIT'] > 0) {
            if ($post['CONF_MAX_COD_ORDER_LIMIT'] <= $post['CONF_MIN_COD_ORDER_LIMIT']) {
                LibHelper::exitWithError(Labels::getLabel('ERR_MAX_COD_VALUE_IS_LESS_THEN_MIN_COD_VALUE', $this->siteLangId), true);
            }
        }

        /* if (isset($post['CONF_PRODUCT_INCLUSIVE_TAX']) && 1 == $post['CONF_PRODUCT_INCLUSIVE_TAX']) {
            $post['CONF_TAX_AFTER_DISOCUNT'] = 0;
        } elseif (!isset($post['CONF_PRODUCT_INCLUSIVE_TAX']) && 1 == FatApp::getConfig('CONF_PRODUCT_INCLUSIVE_TAX', FatUtility::VAR_INT, 0)) {
            $post['CONF_TAX_AFTER_DISOCUNT'] = 0;
        } */


        if (isset($post['CONF_WITHOUT_PROD_VARIANTS']) && FatApp::getConfig('CONF_WITHOUT_PROD_VARIANTS', FatUtility::VAR_INT, 0) != $post['CONF_WITHOUT_PROD_VARIANTS']) {
            $srch = new ProductSearch(0, '', '', false, false, false);
            $srch->joinProductVariant('INNER JOIN');
            $srch->addFld(1);
            $srch->doNotCalculateRecords();
            $srch->setPageSize(1);
            $rs = $srch->getResultSet();
            $row = FatApp::getDb()->fetch($rs);
            if (!empty($row)) {
                LibHelper::exitWithError(Labels::getLabel('ERR_PLEASE_REMOVE_PRODUCTS_DATA_TO_CHANGE_THIS_SETTINGS', $this->siteLangId), true);
            }
        }

        if (false == CONF_DEVELOPMENT_MODE) {
            unset($post['CONF_DEFAULT_SITE_LANG']); /* Restrict to update default site language. */
        }

        if (!$record->update($post)) {
            LibHelper::exitWithError($record->getError(), true);
        }

        $this->set('form_type', $frmType);
        $this->set('lang_id', $langId);
        $this->set('msg', Labels::getLabel('MSG_SETTINGS_SAVED!!', $this->siteLangId));
        $this->_template->render(false, false, 'json-success.php');
    }

    public function updateMaintenanceMode()
    {
        $this->objPrivilege->canEditGeneralSettings();

        $post = FatApp::getPostedData();
        $langId  = FatApp::getPostedData('lang_id', FatUtility::VAR_INT, 0);
        if (1 > $langId) {
            LibHelper::exitWithError($this->str_invalid_request, true);
        }

        if (empty(FatApp::getConfig('CONF_MAINTENANCE_TEXT_' . $langId, FatUtility::VAR_STRING, ''))) {
            LibHelper::exitWithError(Labels::getLabel('ERR_PLEASE_ADD_MAINTENANCE_MODE_TEXT_FIRST', $this->siteLangId), true);
        }

        $record = new Configurations();
        if (!$record->update($post)) {
            LibHelper::exitWithError($record->getError(), true);
        }

        $this->set('msg', $this->str_setup_successful);
        $this->_template->render(false, false, 'json-success.php');
    }

    private function isSslEnabled()
    {

        // url connection
        $url = "https://" . $_SERVER["HTTP_HOST"];

        // Initiate connection
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.6) Gecko/20070725 Firefox/2.0.0.6"); // set browser/user agent
        // Set cURL and other options
        curl_setopt($ch, CURLOPT_URL, $url); // set url
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // allow https verification if true
        curl_setopt($ch, CURLOPT_NOBODY, true);
        // grab URL and pass it to the browser
        $res = curl_exec($ch);
        if (!$res) {
            return false;
        }
        return true;
    }

    public function uploadMedia()
    {
        $this->objPrivilege->canEditGeneralSettings();
        $post = FatApp::getPostedData();

        if (empty($post)) {
            LibHelper::exitWithError(Labels::getLabel('ERR_INVALID_REQUEST_OR_FILE_NOT_SUPPORTED', $this->siteLangId), true);
        }
        $file_type = FatApp::getPostedData('file_type', FatUtility::VAR_INT, 0);
        $lang_id = FatApp::getPostedData('lang_id', FatUtility::VAR_INT, 0);
        $aspectRatio = FatApp::getPostedData('ratio_type', FatUtility::VAR_INT, 0);
        $afileIsSvg = FatApp::getPostedData('afile_attachment_type', FatUtility::VAR_INT, 0);

        if (AttachedFile::FILE_ATTACHMENT_TYPE_SVG == $afileIsSvg) {
            if (false == AttachedFile::isSvgType($_FILES['cropped_image'])) {
                LibHelper::exitWithError(Labels::getLabel('ERR_PLEASE_SELECT_SVG_FILE', $this->siteLangId), true);
            }
        }

        if (!$file_type) {
            LibHelper::exitWithError($this->str_invalid_request, true);
        }

        $allowedFileTypeArr = array(
            AttachedFile::FILETYPE_ADMIN_LOGO,
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
            AttachedFile::FILETYPE_FIRST_PURCHASE_DISCOUNT_IMAGE,
            AttachedFile::FILETYPE_META_IMAGE,
        );

        if (!in_array($file_type, $allowedFileTypeArr)) {
            LibHelper::exitWithError($this->str_invalid_request, true);
        }

        if (!is_uploaded_file($_FILES['cropped_image']['tmp_name'])) {
            LibHelper::exitWithError(Labels::getLabel('ERR_PLEASE_SELECT_A_FILE', $this->siteLangId), true);
        }

        $fileHandlerObj = new AttachedFile();
        $fileHandlerObj->setAttachmentType($afileIsSvg);
        if (!$res = $fileHandlerObj->saveImage($_FILES['cropped_image']['tmp_name'], $file_type, 0, 0, $_FILES['cropped_image']['name'], -1, true, $lang_id, $_FILES['cropped_image']['type'], 0, $aspectRatio)) {
            LibHelper::exitWithError($fileHandlerObj->getError(), true);
        }

        $this->set('file', $_FILES['cropped_image']['name']);
        $this->set('frmType', Configurations::FORM_LOCAL);
        $this->set('msg', $_FILES['cropped_image']['name'] . Labels::getLabel('MSG_UPLOADED_SUCCESSFULLY', $this->siteLangId));
        $this->_template->render(false, false, 'json-success.php');
    }

    public function redirect()
    {
        include_once CONF_INSTALLATION_PATH . 'library/analytics/analyticsapi.php';
        $analyticArr = array(
            'clientId' => FatApp::getConfig("CONF_ANALYTICS_CLIENT_ID"),
            'clientSecretKey' => FatApp::getConfig("CONF_ANALYTICS_SECRET_KEY"),
            'redirectUri' => UrlHelper::generateFullUrl('configurations', 'redirect', array(), '', false),
            'googleAnalyticsID' => FatApp::getConfig("CONF_ANALYTICS_ID")
        );
        try {
            $analytics = new Ykart_analytics($analyticArr);
            $obj = FatApplication::getInstance();
            $get = $obj->getQueryStringVar();
        } catch (exception $e) {
            Message::addErrorMessage($e->getMessage());
        }

        if (isset($get['code']) && isset($get['code']) != '') {
            $code = $get['code'];
            $auth = $analytics->getAccessToken($code);
            if ($auth['refreshToken'] != '') {
                $arr = array('CONF_ANALYTICS_ACCESS_TOKEN' => $auth['refreshToken']);
                $record = new Configurations();
                if (!$record->update($arr)) {
                    Message::addErrorMessage($record->getError());
                } else {
                    Message::addMessage(Labels::getLabel('MSG_SETTING_UPDATED_SUCCESSFULLY', $this->siteLangId));
                }
            } else {
                Message::addErrorMessage(Labels::getLabel('ERR_INVALID_ACCESS_TOKEN', $this->siteLangId));
            }
        } else {
            Message::addErrorMessage(Labels::getLabel('ERR_INVALID_ACCESS', $this->siteLangId));
        }
        FatApp::redirectUser(UrlHelper::generateUrl('configurations', 'index'));
    }

    public function removeMediaImage($file_type, $lang_id = 0)
    {
        $this->objPrivilege->canEditGeneralSettings();

        $fileType = FatUtility::int($file_type);
        $lang_id = FatUtility::int($lang_id);

        $fileHandlerObj = new AttachedFile();
        if (!$fileHandlerObj->deleteFile($fileType, 0, 0, 0, $lang_id)) {
            LibHelper::exitWithError($fileHandlerObj->getError(), true);
        }

        $this->set('msg', Labels::getLabel('MSG_DELETED_SUCCESSFULLY', $this->siteLangId));
        $this->_template->render(false, false, 'json-success.php');
    }

    private function getForm($type, $langId)
    {
        $frm = new Form('frmConfiguration');
        switch ($type) {
            case Configurations::FORM_CMS:
                $cpagesArr = ContentPage::getPagesForSelectBox($langId);

                $frm->addSelectBox(Labels::getLabel('FRM_ABOUT_US', $langId), 'CONF_ABOUT_US_PAGE', $cpagesArr, '', [], Labels::getLabel('FRM_SELECT', $langId));
                $frm->addSelectBox(Labels::getLabel('FRM_PRIVACY_POLICY_PAGE', $langId), 'CONF_PRIVACY_POLICY_PAGE', $cpagesArr, '', [], Labels::getLabel('FRM_SELECT', $langId));
                $frm->addSelectBox(Labels::getLabel('FRM_TERMS_AND_CONDITIONS_PAGE', $langId), 'CONF_TERMS_AND_CONDITIONS_PAGE', $cpagesArr, '', [], Labels::getLabel('FRM_SELECT', $langId));
                $frm->addSelectBox(Labels::getLabel('FRM_GDPR_POLICY_PAGE', $langId), 'CONF_GDPR_POLICY_PAGE', $cpagesArr, '', [], Labels::getLabel('FRM_SELECT', $langId));

                $frm->addSelectBox(Labels::getLabel('FRM_COOKIES_POLICIES_PAGE', $langId), 'CONF_COOKIES_BUTTON_LINK', $cpagesArr, '', [], Labels::getLabel('FRM_SELECT', $langId));

                $faqCategoriesArr = FaqCategory::getFaqPageCategories();
                $sellerCategoriesArr = FaqCategory::getSellerPageCategories();

                /* $frm->addSelectBox(Labels::getLabel('FRM_FAQ_PAGE_MAIN_CATEGORY', $langId), 'CONF_FAQ_PAGE_MAIN_CATEGORY', $faqCategoriesArr, '', [], Labels::getLabel('FRM_SELECT', $langId));
                $frm->addSelectBox(Labels::getLabel('FRM_SELLER_PAGE_MAIN_FAQ_CATEGORY', $langId), 'CONF_SELLER_PAGE_MAIN_CATEGORY', $sellerCategoriesArr, '', [], Labels::getLabel('FRM_SELECT', $langId));
 */
                /* $fld3 = $frm->addTextBox(Labels::getLabel("FRM_ADMIN_DEFAULT_ITEMS_PER_PAGE", $langId), "CONF_ADMIN_PAGESIZE");
                $fld3->requirements()->setInt();
                $fld3->requirements()->setRange('1', '2000');
                $fld3->htmlAfterField = "<span class='form-text text-muted'>" . Labels::getLabel("FRM_DETERMINES_HOW_MANY_ITEMS_ARE_SHOWN_PER_PAGE_(user_listing,_categories,_etc)", $langId) . ".</span>"; */
                $fld = $frm->addCheckBox(Labels::getLabel('FRM_COOKIES_POLICIES', $langId), 'CONF_ENABLE_COOKIES', 1, array(), false, 0);
                HtmlHelper::configureSwitchForCheckbox($fld, Labels::getLabel("FRM_COOKIES_POLICIES_SECTION_WILL_BE_SHOWN_ON_FRONTEND", $langId));
                $fld->developerTags['colWidthValues'] = [null, '12', null, null];

                $fld = $frm->addTextarea(Labels::getLabel('FRM_COOKIES_POLICIES_TEXT', $langId), 'CONF_COOKIES_TEXT_' . $langId);
                $fld->developerTags['colWidthValues'] = [null, '12', null, null];

                $iframeFld = $frm->addTextarea(Labels::getLabel('FRM_GOOGLE_MAP_IFRAME', $langId), 'CONF_MAP_IFRAME_CODE');
                $iframeFld->developerTags['colWidthValues'] = [null, '12', null, null];
                $iframeFld->htmlAfterField = '<span class="form-text text-muted">' . Labels::getLabel("FRM_THIS_IS_THE_GOGLE_MAP_IFRAME_SCRIPT,_used_to_display_google_map_on_contact_us_page", $langId) . '</span>';
                break;

            case Configurations::FORM_LOCAL:
                $frm->addTextBox(Labels::getLabel("FRM_BUSINESS_NAME", $langId), 'CONF_WEBSITE_NAME_' . $langId);
                /* $frm->addTextBox(Labels::getLabel("FRM_SITE_OWNER", $langId), 'CONF_SITE_OWNER_' . $langId); */
                $frm->addEmailField(Labels::getLabel('FRM_BUSINESS_EMAIL', $langId), 'CONF_SITE_OWNER_EMAIL');
                $frm->addHiddenField('', 'CONF_SITE_PHONE_dcode');
                $phnFld = $frm->addTextBox(Labels::getLabel('FRM_TELEPHONE', $langId), 'CONF_SITE_PHONE', '', array('class' => 'phoneJs ltr-right', 'placeholder' => ValidateElement::PHONE_NO_FORMAT, 'maxlength' => ValidateElement::PHONE_NO_LENGTH));
                $phnFld->requirements()->setRegularExpressionToValidate(ValidateElement::PHONE_REGEX);
                $phnFld->requirements()->setCustomErrorMessage(Labels::getLabel('FRM_PLEASE_ENTER_VALID_PHONE_NUMBER.', $langId));
                $phnFld->overrideFldType('tel');

                $faxFld = $frm->addTextBox(Labels::getLabel('FRM_FAX', $langId), 'CONF_SITE_FAX', '', array('class' => 'phoneJs ltr-right', 'placeholder' => ValidateElement::PHONE_NO_FORMAT, 'maxlength' => ValidateElement::PHONE_NO_LENGTH));
                $frm->addHiddenField('', 'CONF_SITE_FAX_DCODE');
                $faxFld->requirements()->setRegularExpressionToValidate(ValidateElement::PHONE_REGEX);
                $faxFld->requirements()->setCustomErrorMessage(Labels::getLabel('FRM_PLEASE_ENTER_VALID_FORMAT.', $langId));
                $faxFld->addFieldTagAttribute('dir', Language::getLayoutDirection($langId));
                $countryObj = new Countries();
                $countriesArr = $countryObj->getCountriesAssocArr($langId);
                $fld = $frm->addSelectBox(Labels::getLabel('FRM_COUNTRY', $langId), 'CONF_COUNTRY', $countriesArr, '', ['id' => 'user_country_id', 'onChange' => 'getCountryStates(this.value,' . FatApp::getConfig('CONF_STATE', FatUtility::VAR_INT, 1) . ',\'#user_state_id\')'], Labels::getLabel('FRM_SELECT', $langId));

                $frm->addSelectBox(Labels::getLabel('FRM_STATE', $langId), 'CONF_STATE', array(), '', ['id' => 'user_state_id'], Labels::getLabel('FRM_SELECT', $langId));
                $frm->addRequiredField(Labels::getLabel("FRM_POSTAL_CODE", $langId), 'CONF_ZIP_CODE');
                $frm->addTextBox(Labels::getLabel("FRM_CITY", $langId), 'CONF_CITY_' . $langId);

                $fld = $frm->addTextarea(Labels::getLabel("FRM_ADDRESS", $langId), 'CONF_ADDRESS_' . $langId);
                $fld->requirements()->setRequired(true);
                $frm->addTextarea(Labels::getLabel("FRM_ADDRESS_LINE_2", $langId), 'CONF_ADDRESS_LINE_2_' . $langId);

                $fld = $frm->addTextBox(Labels::getLabel("FRM_GST_NUMBER", $langId), 'CONF_ADMIN_GST_NUMBER');

                break;

            case Configurations::FORM_SEO:
                $fld = $frm->addCheckBox(Labels::getLabel('FRM_ENABLE_LANGUAGE_SPECIFIC_URLS', $langId), 'CONF_LANG_SPECIFIC_URL', 1, array(), false, 0);
                $fld->developerTags['colWidthValues'] = [null, '12', null, null];
                HtmlHelper::configureSwitchForCheckbox($fld, Labels::getLabel("FRM_ENABLE_LANGUAGE_SPECIFIC_URLS_MSG", $langId));

                $fld2 = $frm->addTextarea(Labels::getLabel('FRM_SITE_TRACKER_CODE', $langId), 'CONF_SITE_TRACKER_CODE');
                $fld2->developerTags['colWidthValues'] = [null, '12', null, null];
                $fld2->htmlAfterField = '<span class="form-text text-muted">' . Labels::getLabel("FRM_SITE_TRACKER_CODE_MSG", $langId) . ' http://www.google.com/analytics/</span>';

                $robotsFld = $frm->addTextarea(Labels::getLabel('FRM_ROBOTS_TXT', $langId), 'CONF_SITE_ROBOTS_TXT');
                $robotsFld->developerTags['colWidthValues'] = [null, '12', null, null];
                $robotsFld->htmlAfterField = '<span class="form-text text-muted">' . Labels::getLabel("FRM_ROBOTS_TXT_MSG", $langId) . '</span>';

                $fld = $frm->addHtml('', 'seperatorGoogleTag', '<div class="separator separator-dashed my-2"></div>');
                $fld->developerTags['colWidthValues'] = [null, '12', null, null];

                $fld = $frm->addHtml('', 'googleTagManager', '<h3 class="form-section-head">' . Labels::getLabel("FRM_GOOGLE_TAG_MANAGER", $langId) . '</h3>');
                $fld->htmlAfterField = '<span class="form-text text-muted">' . Labels::getLabel("FRM_GOOGLE_TAG_MANAGER_MSG", $langId) . '</span>';

                $fld->developerTags['colWidthValues'] = [null, '12', null, null];
                $fld = $frm->addTextarea(Labels::getLabel("FRM_HEAD_SCRIPT", $langId), 'CONF_GOOGLE_TAG_MANAGER_HEAD_SCRIPT');
                $fld->developerTags['colWidthValues'] = [null, '12', null, null];
                $fld->htmlAfterField = "<span class='form-text text-muted'>" . Labels::getLabel("FRM_GOOGLE_TAG_HEAD_SCRIPT_MSG", $langId) . "</span>";

                $fld = $frm->addTextarea(Labels::getLabel("FRM_BODY_SCRIPT", $langId), 'CONF_GOOGLE_TAG_MANAGER_BODY_SCRIPT');
                $fld->developerTags['colWidthValues'] = [null, '12', null, null];
                $fld->htmlAfterField = "<span class='form-text text-muted'>" . Labels::getLabel("FRM_GOOGLE_TAG_BODY_SCRIPT_MSG", $langId) . "</span>";
                $fld = $frm->addHtml('', 'googlewebmaster', '<div class="separator separator-dashed my-2"></div>');
                $fld->developerTags['colWidthValues'] = [null, '12', null, null];
                $fld = $frm->addHtml('', 'googleFileVerification', '<h3 class="form-section-head">' . Labels::getLabel("FRM_GOOGLE_WEBMASTER", $langId) . '</h3>');
                $htmlAfterField = '';
                if (file_exists(CONF_UPLOADS_PATH . '/google-site-verification.html')) {
                    $htmlAfterField .= $fld->htmlAfterField = '<a href="' . UrlHelper::generateFullUrl('', '', array(), CONF_WEBROOT_FRONT_URL) . 'google-site-verification.html" target="_blank" class="btn btn-clean btn-sm btn-icon" title="' . Labels::getLabel("FRM_VIEW_FILE", $langId) . '"><i class="fas fa-eye icon"></i></a><a href="javascript:void();" class="btn btn-clean btn-sm btn-icon" title="' . Labels::getLabel("FRM_DELETE_FILE", $langId) . '" onclick="deleteVerificationFile(\'google\')"><i class="fa fa-trash  icon"></i></a>';
                }
                $htmlAfterField .= "<span class='form-text text-muted'>" . Labels::getLabel("FRM_GOOGLE_WEBMASTER_MSG", $langId) . "</span>";
                $fld->htmlAfterField = $htmlAfterField;

                $fld = $frm->addHtml('', 'bingFileVerification', '<h3 class="form-section-head">' . Labels::getLabel("FRM_BING_WEBMASTER", $langId) . '</h3>');
                $htmlAfterField = '';
                if (file_exists(CONF_UPLOADS_PATH . '/BingSiteAuth.xml')) {
                    $htmlAfterField .= $fld->htmlAfterField = '<a href="' . UrlHelper::generateFullUrl('', '', array(), CONF_WEBROOT_FRONT_URL) . 'BingSiteAuth.xml' . '" target="_blank" class="btn btn-clean btn-sm btn-icon" title="' . Labels::getLabel("FRM_VIEW_FILE", $langId) . '"><i class="fas fa-eye icon"></i></a><a href="javascript:void();" class="btn btn-clean btn-sm btn-icon" title="' . Labels::getLabel("FRM_DELETE_FILE", $langId) . '" onclick="deleteVerificationFile(\'bing\')"><i class="fa fa-trash  icon"></i></a>';
                }
                $htmlAfterField .= "<span class='form-text text-muted'>" . Labels::getLabel("FRM_BING_WEBMASTER_MSG", $langId) . "</span>";
                $fld->htmlAfterField = $htmlAfterField;

                $fld = $frm->addFileUpload(Labels::getLabel('FRM_HTML_FILE_VERIFICATION', $langId), 'google_file_verification', array('accept' => '.html', 'onChange' => 'updateVerificationFile(this, "google")'));
                $fld->htmlAfterField = '<span class="form-text text-muted">' . Labels::getLabel("FRM_HTML_FILE_VERIFICATION_MSG", $langId) . '</span>';

                $fld = $frm->addFileUpload(Labels::getLabel('FRM_XML_FILE_AUTHENTICATION', $langId), 'bing_file_verification', array('accept' => '.xml', 'onChange' => 'updateVerificationFile(this, "bing")'));
                $fld->htmlAfterField = '<span class="form-text text-muted">' . Labels::getLabel("FRM_XML_FILE_VERIFICATION_MSG", $langId) . '</span>';

                $fld = $frm->addHtml('', 'hotjar', '<div class="separator separator-dashed my-2"></div><h3 class="form-section-head">' . Labels::getLabel("FRM_HOTJAR", $langId) . '</h3>');
                $fld->developerTags['colWidthValues'] = [null, '12', null, null];
                $fld = $frm->addTextarea(Labels::getLabel("FRM_HEAD_SCRIPT", $langId), 'CONF_HOTJAR_HEAD_SCRIPT');
                $fld->developerTags['colWidthValues'] = [null, '12', null, null];

                $fld->htmlAfterField = "<span class='form-text text-muted'>" . Labels::getLabel("FRM_HOTJAR_MSG", $langId) . "</span>";

                $fld = $frm->addHtml('', 'schemacode', '<div class="separator separator-dashed my-2"></div><h3 class="form-section-head">' . Labels::getLabel("FRM_SCHEMA_CODES", $langId) . '</h3>');
                $fld->developerTags['colWidthValues'] = [null, '12', null, null];
                $fld = $frm->addTextarea(Labels::getLabel("FRM_DEFAULT_SCHEMA", $langId), 'CONF_DEFAULT_SCHEMA_CODES_SCRIPT');
                $fld->developerTags['colWidthValues'] = [null, '12', null, null];
                $fld->htmlAfterField = "<span class='form-text text-muted'>" . Labels::getLabel("FRM_DEFAULT_SCHEMA_MSG", $langId) . "</span>";

                break;
            case Configurations::FORM_RFQ:
                $fld = $frm->addCheckBox(Labels::getLabel("FRM_ENABLE_MAIN_RFQ_MODULE", $langId), 'CONF_RFQ_MODULE', 1, array(), false, 0);
                HtmlHelper::configureSwitchForCheckbox($fld, Labels::getLabel('FRM_ENABLING_THIS,_BUYER_CAN_REQUEST_FOR_QUOTATION', $langId));

                $fld = $frm->addCheckBox(Labels::getLabel("FRM_ENABLE_GLOBAL_RFQ_MODULE", $langId), 'CONF_GLOBAL_RFQ_MODULE', 1, array(), false, 0);
                HtmlHelper::configureSwitchForCheckbox($fld, Labels::getLabel('FRM_ENABLING_THIS,_BUYER_CAN_REQUEST_GLOBALLY_FOR_QUOTATION', $langId));

                $fld = $frm->addCheckBox(Labels::getLabel("FRM_HIDE_PRODUCT_PRICES", $langId), 'CONF_HIDE_PRICES', 1, array(), false, 0);
                HtmlHelper::configureSwitchForCheckbox($fld, Labels::getLabel('FRM_ENABLING_THIS,_PRODUCT_PRICE_WILL_NOT_DISPLAY', $langId));

                $typeArr = RequestForQuote::getTypeArr($langId);
                $fld = $frm->addSelectBox(Labels::getLabel('FRM_RFQ_MODULE_TYPE', $langId), 'CONF_RFQ_MODULE_TYPE', $typeArr, '', array(), '');
                $fld->htmlAfterField = '<span class="form-text text-muted">' . Labels::getLabel('FRM_TEXT_FOR_RFQ_MODULE_TYPE_1.', $langId) . '</span>';

                $fld = $frm->addCheckBox(
                    Labels::getLabel("FRM_ENABLE_ADMIN_APPROVAL_ON_NEW_RFQ", $langId),
                    'CONF_ENABLE_ADMIN_APPROVAL_ON_NEW_RFQ',
                    1,
                    array(),
                    true,
                    0
                );
                HtmlHelper::configureSwitchForCheckbox($fld, Labels::getLabel('FRM_ENABLING_THIS,_ADMIN_APPROVAL_IS_REQUIRED_FOR_NEW_RFQ.', $langId));

                break;
            case Configurations::FORM_PRODUCT:
                // $frm->addHtml('', 'Product', '<h3 class="form-section-head">' . Labels::getLabel('FRM_PRODUCT', $langId) . '</h3>');

                $fld = $frm->addCheckBox(
                    Labels::getLabel("FRM_ALLOW_SELLERS_TO_ADD_PRODUCTS", $langId),
                    'CONF_ENABLED_SELLER_CUSTOM_PRODUCT',
                    1,
                    array(),
                    false,
                    0
                );
                HtmlHelper::configureSwitchForCheckbox($fld);

                $fld = $frm->addCheckBox(
                    Labels::getLabel("FRM_ENABLE_ADMIN_APPROVAL_ON_PRODUCTS_ADDED_BY_SELLERS", $langId),
                    'CONF_CUSTOM_PRODUCT_REQUIRE_ADMIN_APPROVAL',
                    1,
                    array(),
                    false,
                    0
                );
                HtmlHelper::configureSwitchForCheckbox($fld);

                $fld = $frm->addCheckBox(
                    Labels::getLabel("FRM_ALLOW_SELLERS_TO_REQUEST_PRODUCTS_WHICH_ARE_AVAILABLE_TO_ALL_SELLERS", $langId),
                    'CONF_SELLER_CAN_REQUEST_CUSTOM_PRODUCT',
                    1,
                    array(),
                    false,
                    0
                );
                HtmlHelper::configureSwitchForCheckbox($fld);

                $fld = $frm->addCheckBox(Labels::getLabel("FRM_ADDING_MODEL_#_for_products_will_be_mandatory", $langId), 'CONF_PRODUCT_MODEL_MANDATORY', 1, array(), false, 0);
                HtmlHelper::configureSwitchForCheckbox($fld);

                $fld = $frm->addCheckBox(Labels::getLabel("FRM_ADDING_SKU_FOR_PRODUCTS_WILL_BE_MANDATORY", $langId), 'CONF_PRODUCT_SKU_MANDATORY', 1, array(), false, 0);
                HtmlHelper::configureSwitchForCheckbox($fld);

                $fld = $frm->addCheckBox(Labels::getLabel("FRM_ENABLE_LINKING_SHIPPING_PACKAGES_TO_PRODUCTS", $langId), 'CONF_PRODUCT_DIMENSIONS_ENABLE', 1, array(), false, 0);
                HtmlHelper::configureSwitchForCheckbox($fld, Labels::getLabel("FRM_SHIPPING_PACKAGES_ARE_REQUIRED_IN_CASE_SHIPPING_API_IS_ENABLED", $langId));

                $fld = $frm->addCheckBox(Labels::getLabel("FRM_ENABLE_THIS_SETTING_TO_ADD_WEIGHT_AND_WEIGHT_UNIT", $langId), 'CONF_PRODUCT_WEIGHT_ENABLE', 1, array(), false, 0);
                HtmlHelper::configureSwitchForCheckbox($fld, Labels::getLabel("FRM_YOU_CAN_ADD_WEIGHT_AND_WEIGHT_UNIT_TO_PRODUCT_IF_THIS_SETTING_IS_ENABLED.", $langId));

                $fld = $frm->addCheckBox(Labels::getLabel("FRM_BRANDS_REQUESTED_BY_SELLERS_WILL_REQUIRE_APPROVAL", $langId), 'CONF_BRAND_REQUEST_APPROVAL', 1, array(), false, 0);
                HtmlHelper::configureSwitchForCheckbox($fld, Labels::getLabel("FRM_ON_ENABLING_THIS_FEATURE,_Admin_Need_To_Approve_the_brand_requests_(User_Cannot_link_the_requested_brand_with_any_product_until_it_gets_approved_by_Admin)", $langId));

                $fld = $frm->addCheckBox(Labels::getLabel("FRM_CATEGORIES_REQUESTED_BY_SELLERS_WILL_REQUIRE_APPROVAL", $langId), 'CONF_PRODUCT_CATEGORY_REQUEST_APPROVAL', 1, array(), false, 0);
                HtmlHelper::configureSwitchForCheckbox($fld, Labels::getLabel("FRM_ON_ENABLING_THIS_FEATURE,_Admin_Need_To_Approve_the_Product_category_requests_(User_Cannot_link_the_requested_category_with_any_product_until_it_gets_approved_by_Admin)", $langId));

                $fld = $frm->addCheckBox(Labels::getLabel("FRM_BRAND_WILL_BE_MANDATORY_FOR_PRODUCTS", $langId), 'CONF_PRODUCT_BRAND_MANDATORY', 1, array(), false, 0);
                HtmlHelper::configureSwitchForCheckbox($fld);

                $fld = $frm->addCheckBox(Labels::getLabel("FRM_PRODUCT_PRICES_WILL_BE_INCLUSIVE_OF_TAX", $langId), 'CONF_PRODUCT_INCLUSIVE_TAX', 1, array(), false, 0);
                HtmlHelper::configureSwitchForCheckbox($fld, Labels::getLabel('FRM_ENABLING_THIS,_TAX_AFTER_DISCOUNT_FEATURE_WILL_BE_DEACTIVED', $langId));

                $fld = $frm->addCheckBox(Labels::getLabel("FRM_ENABLE_TAX_CODE_FOR_CATEGORIES", $langId), 'CONF_TAX_CATEGORIES_CODE', 1, array(), false, 0);
                HtmlHelper::configureSwitchForCheckbox($fld, Labels::getLabel("FRM_THIS_WILL_ENABLE_TAX_CATEGORIES_CODE", $langId));

                $fulFillmentArr = Shipping::getFulFillmentArr($langId);
                $frm->addSelectBox(Labels::getLabel('FRM_FULFILLMENT_METHOD', $langId), 'CONF_FULFILLMENT_TYPE', $fulFillmentArr, applicationConstants::NO, array(), '');

                $fld3 = $frm->addTextBox(Labels::getLabel("FRM_DEFAULT_ITEMS_PER_PAGE_(Catalog)", $langId), "CONF_ITEMS_PER_PAGE_CATALOG");
                $fld3->requirements()->setInt();
                $fld3->requirements()->setRange('1', '2000');
                $fld3->htmlAfterField = "<span class='form-text text-muted'>" . Labels::getLabel("FRM_DETERMINES_HOW_MANY_CATALOG_ITEMS_ARE_SHOWN_PER_PAGE_(products,_categories,_etc)", $langId) . ".</span>";

                $fld = $frm->addHtml('', 'geolocation', '<div class="separator separator-dashed my-2"></div><h3 class="form-section-head">' . Labels::getLabel('FRM_LOCATION', $langId) . '</h3>');
                $fld->developerTags['colWidthValues'] = [null, '12', null, null];

                $fld = $frm->addCheckBox(Labels::getLabel("FRM_ACTIVATE_GEO_LOCATION", $langId), 'CONF_ENABLE_GEO_LOCATION', 1, array(), false, 0);
                HtmlHelper::configureSwitchForCheckbox($fld, Labels::getLabel("FRM_PLEASE_INSURE_GOOGLE_MAP_API_IS_FILLED", $langId));
                $fld->developerTags['colWidthValues'] = [null, '12', null, null];

                $prodGeoSettingArr = applicationConstants::getProductListingSettings($langId);

                $shippingServiceActive = Plugin::isActiveByType(Plugin::TYPE_SHIPPING_SERVICES);
                if ($shippingServiceActive) {
                    unset($prodGeoSettingArr[applicationConstants::BASED_ON_DELIVERY_LOCATION]);
                }
                $fld = $frm->addRadioButtons(
                    Labels::getLabel("FRM_PRODUCT_LISTING", $langId),
                    'CONF_PRODUCT_GEO_LOCATION',
                    $prodGeoSettingArr,
                    '',
                    array('class' => 'list-radio'),
                    array('class' => 'geoLocation')
                );
                $fld->developerTags['colWidthValues'] = [null, '12', null, null];
                HtmlHelper::configureSwitchForRadio($fld);
                // $fld->htmlAfterField = "<span class='form-text text-muted'>" . Labels::getLabel("FRM_DISPLAY_AND_SEARCH_PRODUCTS_BASED_ON_LOCATION", $langId) . "</span>";

                $fld = $frm->addRadioButtons(
                    Labels::getLabel("FRM_PRODUCT_LISTING_FILTER", $langId),
                    'CONF_LOCATION_LEVEL',
                    applicationConstants::getLocationLevels($langId),
                    '',
                    array('class' => 'list-radio listingFilter')
                );
                if (FatApp::getConfig('CONF_PRODUCT_GEO_LOCATION', FatUtility::VAR_INT, 0) == applicationConstants::BASED_ON_RADIUS) {
                    $fld->setFieldTagAttribute('disabled', 'disabled');
                }

                $fld->developerTags['colWidthValues'] = [null, '12', null, null];
                HtmlHelper::configureSwitchForRadio($fld, Labels::getLabel("FRM_DISPLAY_AND_SEARCH_PRODUCTS_BASED_ON_CRITERIA", $langId));

                $fld = $frm->addTextBox(Labels::getLabel('FRM_RADIUS_MAX_DISTANCE_IN_MILES', $langId), 'CONF_RADIUS_DISTANCE_IN_MILES');
                $fld->requirements()->setInt();
                if (FatApp::getConfig('CONF_PRODUCT_GEO_LOCATION', FatUtility::VAR_INT, 0) != applicationConstants::BASED_ON_RADIUS) {
                    $fld->setFieldTagAttribute('disabled', 'disabled');
                }

                $fld = $frm->addCheckBox(Labels::getLabel("FRM_SET_DEFAULT_GEO_LOCATION", $langId), 'CONF_DEFAULT_GEO_LOCATION', applicationConstants::YES, array(), false, applicationConstants::NO);
                HtmlHelper::configureSwitchForCheckbox($fld, Labels::getLabel("FRM_SET_DEFAULT_LOCATION_FOR_PRODUCT_LISTING", $langId));
                $fld->developerTags['colWidthValues'] = [null, '12', null, null];

                $countryObj = new Countries();
                $countriesArr = $countryObj->getCountriesAssocArr($langId, true, 'country_code');
                $countryFld = $frm->addSelectBox(Labels::getLabel('FRM_COUNTRY', $langId), 'CONF_GEO_DEFAULT_COUNTRY', $countriesArr, '', [], Labels::getLabel('FRM_SELECT', $langId));
                $countryFld->setFieldTagAttribute('id', 'geo_country_code');
                $defaultState = FatApp::getConfig('CONF_GEO_DEFAULT_STATE', FatUtility::VAR_STRING, 1);
                $countryFld->setFieldTagAttribute('onChange', 'getStatesByCountryCode(this.value,' . (empty($defaultState) ? 1 : $defaultState) . ',\'#geo_state_code\', \'state_code\')');

                $stateFld = $frm->addSelectBox(Labels::getLabel('FRM_STATE', $langId), 'CONF_GEO_DEFAULT_STATE', array(), '', [], Labels::getLabel('FRM_SELECT', $langId));
                $stateFld->setFieldTagAttribute('id', 'geo_state_code');

                $zipFld = $frm->addTextBox(Labels::getLabel("FRM_POSTAL_CODE", $langId), 'CONF_GEO_DEFAULT_ZIPCODE', '', ['id' => 'geo_postal_code']);
                $frm->addHiddenField('', 'CONF_GEO_DEFAULT_LAT', FatApp::getConfig('CONF_GEO_DEFAULT_LAT', FatUtility::VAR_FLOAT, 40.72), ['id' => 'lat']);
                $frm->addHiddenField('', 'CONF_GEO_DEFAULT_LNG', FatApp::getConfig('CONF_GEO_DEFAULT_LNG', FatUtility::VAR_FLOAT, -73.96), ['id' => 'lng']);
                $frm->addHiddenField('', 'CONF_GEO_DEFAULT_ADDR', FatApp::getConfig('CONF_GEO_DEFAULT_ADDR', FatUtility::VAR_STRING, '', ['id' => 'geo_city']));

                if (FatApp::getConfig('CONF_DEFAULT_GEO_LOCATION', FatUtility::VAR_INT, 0) != applicationConstants::YES) {
                    $countryFld->setFieldTagAttribute('disabled', 'disabled');
                    $stateFld->setFieldTagAttribute('disabled', 'disabled');
                    $zipFld->setFieldTagAttribute('disabled', 'disabled');
                }

                break;

            case Configurations::FORM_USER_ACCOUNT:
                $fld = $frm->addCheckBox(
                    Labels::getLabel("FRM_ACTIVATE_ADMIN_APPROVAL_AFTER_REGISTRATION_(Sign_Up)", $langId),
                    'CONF_ADMIN_APPROVAL_REGISTRATION',
                    1,
                    array(),
                    false,
                    0
                );
                HtmlHelper::configureSwitchForCheckbox($fld, Labels::getLabel("FRM_ON_ENABLING_THIS_FEATURE,_admin_need_to_approve_each_user_after_registration_(User_cannot_login_until_admin_approves)", $langId));

                $fld = $frm->addCheckBox(
                    Labels::getLabel("FRM_ACTIVATE_EMAIL_VERIFICATION_AFTER_REGISTRATION", $langId),
                    'CONF_EMAIL_VERIFICATION_REGISTRATION',
                    1,
                    array(),
                    false,
                    0
                );
                HtmlHelper::configureSwitchForCheckbox($fld, Labels::getLabel("FRM_USER_NEED_TO_VERIFY_THEIR_EMAIL_ADDRESS_PROVIDED_DURING_REGISTRATION", $langId));


                $fld = $frm->addCheckBox(
                    Labels::getLabel("FRM_ACTIVATE_NOTIFY_ADMINISTRATOR_ON_EACH_REGISTRATION", $langId),
                    'CONF_NOTIFY_ADMIN_REGISTRATION',
                    1,
                    array(),
                    false,
                    0
                );
                HtmlHelper::configureSwitchForCheckbox($fld, Labels::getLabel("FRM_ON_ENABLING_THIS_FEATURE,_notification_mail_will_be_sent_to_administrator_on_each_registration.", $langId));

                $fld = $frm->addCheckBox(
                    Labels::getLabel("FRM_ACTIVATE_AUTO_LOGIN_AFTER_REGISTRATION", $langId),
                    'CONF_AUTO_LOGIN_REGISTRATION',
                    1,
                    array(),
                    false,
                    0
                );
                HtmlHelper::configureSwitchForCheckbox($fld, Labels::getLabel("FRM_ON_ENABLING_THIS_FEATURE,_users_will_be_automatically_logged-in_after_registration", $langId));

                $fld = $frm->addCheckBox(
                    Labels::getLabel("FRM_ACTIVATE_SENDING_WELCOME_MAIL_AFTER_REGISTRATION", $langId),
                    'CONF_WELCOME_EMAIL_REGISTRATION',
                    1,
                    array(),
                    false,
                    0
                );
                HtmlHelper::configureSwitchForCheckbox($fld, Labels::getLabel("FRM_ON_ENABLING_THIS_FEATURE,_users_will_receive_a_welcome_mail_after_registration.", $langId));

                $fld = $frm->addCheckBox(
                    Labels::getLabel("FRM_ACTIVATE_SEPARATE_SELLER_SIGN_UP_FORM", $langId),
                    'CONF_ACTIVATE_SEPARATE_SIGNUP_FORM',
                    1,
                    array(),
                    false,
                    0
                );
                HtmlHelper::configureSwitchForCheckbox($fld, Labels::getLabel("FRM_ON_ENABLING_THIS_FEATURE,_buyers_and_seller_will_have_a_separate_sign_up_form.", $langId));

                $fld = $frm->addCheckBox(
                    Labels::getLabel("FRM_ACTIVATE_ADMINISTRATOR_APPROVAL_ON_SELLER_REQUEST", $langId),
                    'CONF_ADMIN_APPROVAL_SUPPLIER_REGISTRATION',
                    1,
                    array(),
                    false,
                    0
                );
                HtmlHelper::configureSwitchForCheckbox($fld, Labels::getLabel("FRM_ON_ENABLING_THIS_FEATURE,_admin_need_to_approve_Seller's_request_after_registration", $langId));

                $fld = $frm->addCheckBox(
                    Labels::getLabel("FRM_BUYERS_CAN_SEE_SELLER_TAB", $langId),
                    'CONF_BUYER_CAN_SEE_SELLER_TAB',
                    1,
                    array(),
                    false,
                    0
                );
                HtmlHelper::configureSwitchForCheckbox($fld, Labels::getLabel("FRM_ON_ENABLING_THIS_FEATURE,_buyers_will_be_able_to_see_Seller_tab", $langId));

                $fld = $frm->addIntegerField(Labels::getLabel("FRM_MAX_SELLER_REQUEST_ATTEMPTS", $langId), 'CONF_MAX_SUPPLIER_REQUEST_ATTEMPT', '');
                $fld->htmlAfterField = "<span class='form-text text-muted'>" . Labels::getLabel("FRM_MAXIMUM_SELLER_REQUEST_ATTEMPTS_ALLOWED", $langId) . "</span>";

                $fld = $frm->addIntegerField(Labels::getLabel("FRM_MINIMUM_GIFT_CARD_AMOUNT", $langId), 'CONF_MINIMUM_GIFT_CARD_AMOUNT', '');
                $fld->htmlAfterField = "<span class='form-text text-muted'>" . Labels::getLabel("FRM_SET_MINIMUM_AMOUNT_FOR_GIFT_CARDS", $langId) . "</span>";

                $fld = $frm->addHtml('', 'Withdrawal', '<div class="separator separator-dashed my-2"></div><h3 class="form-section-head">' . Labels::getLabel("FRM_WITHDRAWAL", $langId) . '</h3>');
                $fld->developerTags['colWidthValues'] = [null, '12', null, null];

                $fld = $frm->addIntegerField(Labels::getLabel("FRM_MINIMUM_WITHDRAWAL_AMOUNT", $langId) . ' [' . $this->siteDefaultCurrencyCode . ']', 'CONF_MIN_WITHDRAW_LIMIT', '');
                $fld->htmlAfterField = "<span class='form-text text-muted'> " . Labels::getLabel("FRM_THIS_IS_THE_MINIMUM_WITHDRAWABLE_AMOUNT.", $langId) . "</span>";

                $fld = $frm->addIntegerField(Labels::getLabel("FRM_MAXIMUM_WITHDRAWAL_AMOUNT", $langId) . ' [' . $this->siteDefaultCurrencyCode . ']', 'CONF_MAX_WITHDRAW_LIMIT', '');
                $fld->htmlAfterField = "<span class='form-text text-muted'> " . Labels::getLabel("FRM_THIS_IS_THE_MAXIMUM_WITHDRAWABLE_AMOUNT.", $langId) . "</span>";

                $fld = $frm->addIntegerField(Labels::getLabel("FRM_MINIMUM_INTERVAL_[Days]", $langId), 'CONF_MIN_INTERVAL_WITHDRAW_REQUESTS', '');
                $fld->htmlAfterField = "<span class='form-text text-muted'>" . Labels::getLabel("FRM_THIS_IS_THE_MINIMUM_INTERVAL_IN_DAYS_BETWEEN_TWO_WITHDRAWAL_REQUESTS.", $langId) . "</span>";
                break;

            case Configurations::FORM_CHECKOUT_PROCESS:
                $fld = $frm->addHtml('', 'Checkout', '<h3 class="form-section-head">' . Labels::getLabel('FRM_COD_PAYMENTS', $langId) . '</h3>');
                $fld->developerTags['colWidthValues'] = [null, '12', null, null];

                $fld = $frm->addTextBox(Labels::getLabel('FRM_MINIMUM_COD_ORDER_TOTAL', $langId) . ' [' . $this->siteDefaultCurrencyCode . ']', 'CONF_MIN_COD_ORDER_LIMIT');
                $fld->htmlAfterField = "<span class='form-text text-muted'>" . Labels::getLabel("FRM_THIS_IS_THE_MINIMUM_CASH_ON_DELIVERY_ORDER_TOTAL,_eligible_for_COD_payments.", $langId) . "</span>";
                $fld = $frm->addTextBox(Labels::getLabel('FRM_MAXIMUM_COD_ORDER_TOTAL', $langId) . ' [' . $this->siteDefaultCurrencyCode . ']', 'CONF_MAX_COD_ORDER_LIMIT');
                $fld->htmlAfterField = "<span class='form-text text-muted'>" . Labels::getLabel("FRM_THIS_IS_THE_MAXIMUM_CASH_ON_DELIVERY_ORDER_TOTAL,_eligible_for_COD_payments._Default_is_0", $langId) . "</span>";
                $fld = $frm->addTextBox(Labels::getLabel('FRM_MINIMUM_WALLET_BALANCE', $langId) . ' [' . $this->siteDefaultCurrencyCode . ']', 'CONF_COD_MIN_WALLET_BALANCE');
                $fld->htmlAfterField = "<span class='form-text text-muted'>" . Labels::getLabel("FRM_SELLER_NEEDS_TO_MAINTAIN_TO_ACCEPT_COD_ORDERS._Default_is_-1", $langId) . "</span>";

                $fld = $frm->addHtml('', 'pickup', '<div class="separator separator-dashed my-2"></div><h3 class="form-section-head">' . Labels::getLabel('FRM_PICKUP', $langId) . '</h3>');
                $fld->developerTags['colWidthValues'] = [null, '12', null, null];

                $fld = $frm->addTextBox(Labels::getLabel('FRM_DISPLAY_TIME_SLOTS_AFTER_ORDER', $langId) . ' [' . Labels::getLabel('FRM_HOURS', $langId) . ']', 'CONF_TIME_SLOT_ADDITION', 2);
                $fld->requirements()->setInt();
                $fld->requirements()->setRange('2', '9999999999');
                $fld->requirements()->setRequired(true);
                $fld->htmlAfterField = "<span class='form-text text-muted'>" . Labels::getLabel("FRM_SHOP_PICKUP_INTERVAL_INFO", $langId) . "</span>";

                $fld = $frm->addHtml('', 'cprocess', '<div class="separator separator-dashed my-2"></div><h3 class="form-section-head">' . Labels::getLabel('FRM_CHECKOUT_PROCESS', $langId) . '</h3>');
                $fld->developerTags['colWidthValues'] = [null, '12', null, null];

                $fld = $frm->addCheckBox(Labels::getLabel('FRM_ACTIVATE_LIVE_PAYMENT_TRANSACTION_MODE', $langId), 'CONF_TRANSACTION_MODE', 1, array(), false, 0);
                HtmlHelper::configureSwitchForCheckbox($fld, Labels::getLabel("FRM_SET_TRANSACTION_MODE_TO_LIVE_ENVIRONMENT", $langId));

                $obj = new Plugin();
                if ($obj->getDefaultPluginData(Plugin::TYPE_SHIPPING_SERVICES, 'plugin_active')) {
                    $fld = $frm->addCheckBox(
                        Labels::getLabel("FRM_USE_MANUAL_SHIPPING_RATES._INSTEAD_OF_THIRD_PARTY.", $langId),
                        'CONF_MANUAL_SHIPPING_RATES_ADMIN',
                        1,
                        array(),
                        false,
                        0
                    );
                    HtmlHelper::configureSwitchForCheckbox($fld, Labels::getLabel("FRM_MANUAL_SHIPPING_RATES_WERE_CONSIDERED_FOR_ADMIN_SHIPPING.", $langId));
                }

                $fld = $frm->addCheckBox(Labels::getLabel('FRM_NEW_ORDER_ALERT_EMAIL', $langId), 'CONF_NEW_ORDER_EMAIL', 1, array(), false, 0);
                HtmlHelper::configureSwitchForCheckbox($fld, Labels::getLabel("FRM_SEND_AN_EMAIL_TO_STORE_OWNER_WHEN_NEW_ORDER_IS_PLACED.", $langId));

                $orderStatusArr = Orders::getOrderProductStatusArr($langId);

                $fld = $frm->addCheckBox(Labels::getLabel("FRM_TAX_COLLECTED_BY_SELLER", $langId), 'CONF_TAX_COLLECTED_BY_SELLER', 1, array(), false, 0);
                HtmlHelper::configureSwitchForCheckbox($fld, Labels::getLabel("FRM_ON_ENABLING_THIS_FEATURE,_seller_will_be_able_to_collect_tax.", $langId));

                $fld = $frm->addCheckBox(Labels::getLabel("FRM_TAX_AFTER_DISCOUNTS", $langId), 'CONF_TAX_AFTER_DISOCUNT', 1, array(), false, 0);
                /* if (FatApp::getConfig('CONF_PRODUCT_INCLUSIVE_TAX', FatUtility::VAR_INT, 0)) {
                    $fld->setFieldTagAttribute('disabled', 'disabled');
                } */
                HtmlHelper::configureSwitchForCheckbox($fld, Labels::getLabel("FRM_ON_ENABLING_THIS_FEATURE,_tax_will_be_applicable_after_discounts", $langId));


                $splitPaymentMethodActive = Plugin::isActiveByType(Plugin::TYPE_SPLIT_PAYMENT_METHOD);
                if (!$splitPaymentMethodActive) {
                    $fld = $frm->addCheckBox(Labels::getLabel("FRM_RETURN_SHIPPING_CHARGES_TO_CUSTOMER", $langId), 'CONF_RETURN_SHIPPING_CHARGES_TO_CUSTOMER', 1, array(), false, 0);
                    HtmlHelper::configureSwitchForCheckbox($fld, Labels::getLabel("FRM_ON_ENABLING_RETURN_SHIPPING_CHARGES_TO_CUSTOMER", $langId));
                }

                $fld = $frm->addCheckBox(Labels::getLabel("FRM_SHIPPED_BY_ADMIN_ONLY", $langId), 'CONF_SHIPPED_BY_ADMIN_ONLY', 1, array(), false, 0);
                HtmlHelper::configureSwitchForCheckbox($fld, Labels::getLabel("FRM_ON_ENABLING_SHIPPING_CHARGES_MANGED_BY_ADMIN_ONLY", $langId));

                $fld = $frm->addSelectBox(
                    Labels::getLabel("FRM_DEFAULT_CHILD_ORDER_STATUS", $langId),
                    'CONF_DEFAULT_ORDER_STATUS',
                    $orderStatusArr,
                    false,
                    array(),
                    ''
                );

                $fld = $frm->addSelectBox(
                    Labels::getLabel("FRM_DEFAULT_PAID_ORDER_STATUS", $langId),
                    'CONF_DEFAULT_PAID_ORDER_STATUS',
                    $orderStatusArr,
                    false,
                    array(),
                    ''
                );
                $fld->htmlAfterField = "<span class='form-text text-muted'>" . Labels::getLabel("FRM_SET_THE_DEFAULT_CHILD_ORDER_STATUS_WHEN_AN_ORDER_IS_MARKED_PAID.", $langId) . "</span>";

                $fld = $frm->addSelectBox(
                    Labels::getLabel("FRM_DEFAULT_APPROVED_ORDER_STATUS", $langId),
                    'CONF_DEFAULT_APPROVED_ORDER_STATUS',
                    $orderStatusArr,
                    false,
                    array(),
                    ''
                );
                $fld->htmlAfterField = "<span class='form-text text-muted'>" . Labels::getLabel("FRM_SET_THE_DEFAULT_APPROVED_ORDER_STATUS", $langId) . "</span>";

                $fld = $frm->addSelectBox(
                    Labels::getLabel("FRM_DEFAULT_INPROCESS_ORDER_STATUS", $langId),
                    'CONF_DEFAULT_INPROCESS_ORDER_STATUS',
                    $orderStatusArr,
                    false,
                    array(),
                    ''
                );
                $fld->htmlAfterField = "<span class='form-text text-muted'>" . Labels::getLabel("FRM_SET_THE_DEFAULT_IN-process_order_status", $langId) . "</span>";

                $fld = $frm->addSelectBox(
                    Labels::getLabel("FRM_DEFAULT_SHIPPING_ORDER_STATUS", $langId),
                    'CONF_DEFAULT_SHIPPING_ORDER_STATUS',
                    $orderStatusArr,
                    false,
                    array(),
                    ''
                );
                $fld->htmlAfterField = "<span class='form-text text-muted'>" . Labels::getLabel("FRM_SET_THE_DEFAULT_CHILD_ORDER_STATUS_WHEN_AN_ORDER_IS_MARKED_SHIPPED.", $langId) . "</span>";

                $fld = $frm->addSelectBox(
                    Labels::getLabel("FRM_DEFAULT_DELIVERED_ORDER_STATUS", $langId),
                    'CONF_DEFAULT_DEIVERED_ORDER_STATUS',
                    $orderStatusArr,
                    false,
                    array(),
                    ''
                );
                $fld->htmlAfterField = "<span class='form-text text-muted'>" . Labels::getLabel("FRM_SET_THE_DEFAULT_CHILD_ORDER_STATUS_WHEN_AN_ORDER_IS_MARKED_DELIVERED.", $langId) . "</span>";

                $fld = $frm->addSelectBox(
                    Labels::getLabel("FRM_DEFAULT_CANCELLED_ORDER_STATUS", $langId),
                    'CONF_DEFAULT_CANCEL_ORDER_STATUS',
                    $orderStatusArr,
                    false,
                    array(),
                    ''
                );
                $fld->htmlAfterField = "<span class='form-text text-muted'>" . Labels::getLabel("FRM_SET_THE_DEFAULT_CHILD_ORDER_STATUS_WHEN_AN_ORDER_IS_MARKED_CANCELLED.", $langId) . "</span>";

                $fld = $frm->addSelectBox(
                    Labels::getLabel("FRM_RETURN_REQUESTED_ORDER_STATUS", $langId),
                    'CONF_RETURN_REQUEST_ORDER_STATUS',
                    $orderStatusArr,
                    false,
                    array(),
                    ''
                );
                $fld->htmlAfterField = "<span class='form-text text-muted'>" . Labels::getLabel("FRM_SET_THE_DEFAULT_CHILD_ORDER_STATUS_WHEN_RETURN_REQUEST_IS_OPENED_ON_ANY_ORDER.", $langId) . "</span>";

                $fld = $frm->addSelectBox(Labels::getLabel("FRM_RETURN_REQUEST_WITHDRAWN_ORDER_STATUS", $langId), 'CONF_RETURN_REQUEST_WITHDRAWN_ORDER_STATUS', $orderStatusArr, false, array(), '');
                $fld->htmlAfterField = "<span class='form-text text-muted'>" . Labels::getLabel("FRM_SET_THE_DEFAULT_CHILD_ORDER_STATUS_WHEN_RETURN_REQUEST_IS_WITHDRAWN.", $langId) . "</span>";

                $fld = $frm->addSelectBox(Labels::getLabel("FRM_RETURN_REQUEST_APPROVED_ORDER_STATUS", $langId), 'CONF_RETURN_REQUEST_APPROVED_ORDER_STATUS', $orderStatusArr, false, array(), '');
                $fld->htmlAfterField = "<span class='form-text text-muted'>" . Labels::getLabel("FRM_SET_THE_DEFAULT_CHILD_ORDER_STATUS_WHEN_RETURN_REQUEST_IS_ACCEPTED_BY_THE_SELLER.", $langId) . "</span>";

                $fld = $frm->addSelectBox(Labels::getLabel("FRM_PAY_AT_STORE_ORDER_STATUS", $langId), 'CONF_PAY_AT_STORE_ORDER_STATUS', $orderStatusArr, false, array(), '');
                $fld->htmlAfterField = "<span class='form-text text-muted'>" . Labels::getLabel("FRM_SET_THE_PAY_AT_STORE_ORDER_STATUS.", $langId) . "</span>";

                $fld = $frm->addSelectBox(Labels::getLabel("FRM_CASH_ON_DELIVERY_ORDER_STATUS", $langId), 'CONF_COD_ORDER_STATUS', $orderStatusArr, false, array(), '');
                $fld->htmlAfterField = "<span class='form-text text-muted'>" . Labels::getLabel("FRM_SET_THE_CASH_ON_DELIVERY_ORDER_STATUS.", $langId) . "</span>";

                $fld = $frm->addSelectBox(Labels::getLabel("FRM_READY_FOR_PICKUP_ORDER_STATUS", $langId), 'CONF_PICKUP_READY_ORDER_STATUS', $orderStatusArr, false, array(), '');
                $fld->htmlAfterField = "<span class='form-text text-muted'>" . Labels::getLabel("FRM_SET_THE_READY_FOR_PICKUP_ORDER_STATUS.", $langId) . "</span>";

                $fld = $frm->addSelectBox(
                    Labels::getLabel("FRM_STATUS_USED_BY_SYSTEM_TO_MARK_ORDER_AS_COMPLETED", $langId),
                    'CONF_DEFAULT_COMPLETED_ORDER_STATUS',
                    $orderStatusArr,
                    false,
                    array(),
                    ''
                );
                $fld->htmlAfterField = "<span class='form-text text-muted'>" . Labels::getLabel("FRM_SET_THE_DEFAULT_CHILD_ORDER_STATUS_WHEN_AN_ORDER_IS_MARKED_COMPLETED.", $langId) . "</span>";

                /*
                $returnAge = FatApp::getConfig("CONF_DEFAULT_RETURN_AGE", FatUtility::VAR_INT, 7);
                $fld = $frm->addIntegerField(Labels::getLabel("FRM_DEFAULT_RETURN_AGE_[Days]", $langId), 'CONF_DEFAULT_RETURN_AGE', $returnAge);
                $fld->htmlAfterField = "<span class='form-text text-muted'>" . Labels::getLabel("FRM_IT_WILL_CONSIDERED_IF_NO_RETURN_AGE_IS_DEFINED_IN_SHOP_OR_SELLER_PRODUCT.", $langId) . "</span>";
                */

                $fld = $frm->addCheckBoxes(Labels::getLabel("FRM_SELLER_ORDER_STATUSES", $langId), 'CONF_VENDOR_ORDER_STATUS', $orderStatusArr, [], array('class' => 'list-checkboxes'));
                $fld->developerTags['colWidthValues'] = [null, '12', null, null];
                $fld->developerTags['cbLabelAttributes'] = ['class' => 'checkbox'];
                $fld->developerTags['cbHtmlBeforeCheckbox'] = '';

                $fld->htmlAfterField = "<span class='form-text text-muted'>" . Labels::getLabel("FRM_SET_THE_ORDER_STATUS_THE_CUSTOMER's_order_must_reach_before_the_order_starts_displaying_to_Sellers.", $langId) . "</span>";

                $fld = $frm->addCheckBoxes(Labels::getLabel("FRM_BUYER_ORDER_STATUSES", $langId), 'CONF_BUYER_ORDER_STATUS', $orderStatusArr, [], array('class' => 'list-checkboxes'));
                $fld->developerTags['colWidthValues'] = [null, '12', null, null];
                $fld->developerTags['cbLabelAttributes'] = ['class' => 'checkbox'];
                $fld->developerTags['cbHtmlBeforeCheckbox'] = '';
                $fld->htmlAfterField = "<span class='form-text text-muted'>" . Labels::getLabel("FRM_SET_THE_ORDER_STATUS_THE_CUSTOMER's_order_must_reach_before_the_order_starts_displaying_to_Buyers.", $langId) . "</span>";

                $fld = $frm->addCheckBoxes(Labels::getLabel("FRM_PROCESSING_ORDER_STATUS", $langId), 'CONF_PROCESSING_ORDER_STATUS', $orderStatusArr, [], array('class' => 'list-checkboxes'));
                $fld->developerTags['colWidthValues'] = [null, '12', null, null];
                $fld->developerTags['cbLabelAttributes'] = ['class' => 'checkbox'];
                $fld->developerTags['cbHtmlBeforeCheckbox'] = '';
                $fld->htmlAfterField = "<span class='form-text text-muted'>" . Labels::getLabel("FRM_SET_THE_ORDER_STATUS_THE_CUSTOMER's_order_must_reach_before_the_order_starts_stock_subtraction.", $langId) . "</span>";

                $fld = $frm->addCheckBoxes(Labels::getLabel("FRM_COMPLETED_ORDER_STATUS", $langId), 'CONF_COMPLETED_ORDER_STATUS', $orderStatusArr, [], array('class' => 'list-checkboxes'));
                $fld->developerTags['colWidthValues'] = [null, '12', null, null];
                $fld->developerTags['cbLabelAttributes'] = ['class' => 'checkbox'];
                $fld->developerTags['cbHtmlBeforeCheckbox'] = '';
                $fld->htmlAfterField = "<span class='form-text text-muted'>" . Labels::getLabel("FRM_SET_THE_ORDER_STATUS_THE_CUSTOMER's_order_must_reach_before_they_are_considered_completed_and_payment_released_to_Sellers.", $langId) . "</span>";

                $fld = $frm->addCheckBoxes(Labels::getLabel("FRM_FEEDBACK_READY_ORDER_STATUS", $langId), 'CONF_REVIEW_READY_ORDER_STATUS', $orderStatusArr, [], array('class' => 'list-checkboxes'));
                $fld->developerTags['colWidthValues'] = [null, '12', null, null];
                $fld->developerTags['cbLabelAttributes'] = ['class' => 'checkbox'];
                $fld->developerTags['cbHtmlBeforeCheckbox'] = '';
                $fld->htmlAfterField = "<span class='form-text text-muted'>" . Labels::getLabel("FRM_SET_THE_ORDER_STATUS_THE_CUSTOMER's_order_must_reach_before_they_are_allowed_to_review_the_orders.", $langId) . "</span>";


                $fld = $frm->addCheckBoxes(Labels::getLabel("FRM_ALLOW_ORDER_CANCELLATION_BY_BUYERS", $langId), 'CONF_ALLOW_CANCELLATION_ORDER_STATUS', $orderStatusArr, [], array('class' => 'list-checkboxes'));
                $fld->developerTags['colWidthValues'] = [null, '12', null, null];
                $fld->developerTags['cbLabelAttributes'] = ['class' => 'checkbox'];
                $fld->developerTags['cbHtmlBeforeCheckbox'] = '';
                $fld->htmlAfterField = "<span class='form-text text-muted'>" . Labels::getLabel("FRM_SET_THE_ORDER_STATUS_THE_CUSTOMER's_order_must_reach_before_they_are_allowed_to_place_cancellation_request_on_orders.", $langId) . "</span>";

                $fld = $frm->addCheckBoxes(Labels::getLabel("FRM_ALLOW_ORDER_CANCELLATION_BY_BUYERS_ON_DIGITAL", $langId), 'CONF_DIGITAL_ALLOW_CANCELLATION_ORDER_STATUS', $orderStatusArr, [], array('class' => 'list-checkboxes'));
                $fld->developerTags['colWidthValues'] = [null, '12', null, null];
                $fld->developerTags['cbLabelAttributes'] = ['class' => 'checkbox'];
                $fld->developerTags['cbHtmlBeforeCheckbox'] = '';
                $fld->htmlAfterField = "<span class='form-text text-muted'>" . Labels::getLabel("FRM_SET_THE_ORDER_STATUS_THE_CUSTOMER's_order_must_reach_before_they_are_allowed_to_place_cancellation_request_on_orders.", $langId) . "</span>";

                $fld = $frm->addCheckBoxes(Labels::getLabel("FRM_ALLOW_RETURN/Exchange", $langId), 'CONF_RETURN_EXCHANGE_READY_ORDER_STATUS', $orderStatusArr, [], array('class' => 'list-checkboxes'));
                $fld->developerTags['colWidthValues'] = [null, '12', null, null];
                $fld->developerTags['cbLabelAttributes'] = ['class' => 'checkbox'];
                $fld->developerTags['cbHtmlBeforeCheckbox'] = '';
                $fld->htmlAfterField = "<span class='form-text text-muted'>" . Labels::getLabel("FRM_SET_THE_ORDER_STATUS_THE_CUSTOMER's_order_must_reach_before_they_are_allowed_to_place_return/exchange_request_on_orders.", $langId) . "</span>";

                $fld = $frm->addCheckBoxes(Labels::getLabel("FRM_ENABLE_DIGITAL_DOWNLOAD", $langId), 'CONF_ENABLE_DIGITAL_DOWNLOADS', $orderStatusArr, [], array('class' => 'list-checkboxes'));
                $fld->developerTags['colWidthValues'] = [null, '12', null, null];
                $fld->developerTags['cbLabelAttributes'] = ['class' => 'checkbox'];
                $fld->developerTags['cbHtmlBeforeCheckbox'] = '';
                $fld->htmlAfterField = "<span class='form-text text-muted'>" . Labels::getLabel("FRM_SET_THE_ORDER_STATUS_THE_CUSTOMER's_order_must_reach_before_they_are_allowed_to_access_their_downloadable_Products.", $langId) . "</span>";

                $fld = $frm->addCheckBoxes(Labels::getLabel("FRM_ORDER_STATUSES_TO_ALLOW_TO_ATTACH_MORE_FILES_WITH_ORDER_PRODUCT", $langId), 'CONF_ALLOW_FILES_TO_ADD_WITH_ORDER_STATUSES', $orderStatusArr, [], array('class' => 'list-checkboxes'));
                $fld->developerTags['colWidthValues'] = [null, '12', null, null];
                $fld->developerTags['cbLabelAttributes'] = ['class' => 'checkbox'];
                $fld->developerTags['cbHtmlBeforeCheckbox'] = '';
                $fld->htmlAfterField = "<span class='form-text text-muted'>" . Labels::getLabel("FRM_SET_ORDER_STATUSES_TO_ALLOW_SELLER_OR_ADMIN_TO_ATTACH_MORE_FILES_WITH_ORDER_PRODUCTS", $langId) . "</span>";

                /*  $fld = $frm->addCheckBoxes(Labels::getLabel("FRM_ORDER_STATUSES_TO_CALCULATE_BADGE_COUNT_(For_Admin)", $langId), 'CONF_BADGE_COUNT_ORDER_STATUS', $orderStatusArr, [], array('class' => 'list-checkboxes'));
                $fld->developerTags['colWidthValues'] = [null, '12', null, null];
                $fld->developerTags['cbLabelAttributes'] = ['class' => 'checkbox'];
                $fld->developerTags['cbHtmlBeforeCheckbox'] = '';
                $fld->htmlAfterField = "<span class='form-text text-muted'>" . Labels::getLabel("FRM_ORDER_STATUSES_TO_CALCULATE_BADGE_COUNT_FOR_SELLER_ORDERS_IN_ADMIN_LEFT_NAVIGATION_PANEL", $langId) . "</span>"; */

                $fld = $frm->addCheckBoxes(Labels::getLabel("FRM_PRODUCTS_ON_ORDER_STAGE(For_Seller_Inventory_Report)", $langId), 'CONF_PRODUCT_IS_ON_ORDER_STATUSES', $orderStatusArr, [], array('class' => 'list-checkboxes'));
                $fld->developerTags['colWidthValues'] = [null, '12', null, null];
                $fld->developerTags['cbLabelAttributes'] = ['class' => 'checkbox'];
                $fld->developerTags['cbHtmlBeforeCheckbox'] = '';
                $fld->htmlAfterField = "<span class='form-text text-muted'>" . Labels::getLabel("FRM_PRODUCTS_ARE_IN_ON_ORDER_USED_ON_SELLER_DASHBOARD_PRODUCTS_INVENTORY_STOCK_STATUS_REPORT", $langId) . "</span>";

                break;

            case Configurations::FORM_CART_WISHLIST:
                $fld = $frm->addRadioButtons(Labels::getLabel("FRM_ADD_PRODUCTS_TO_WISHLIST_OR_FAVORITE?", $langId), 'CONF_ADD_FAVORITES_TO_WISHLIST', UserWishList::wishlistOrFavtArr($langId), applicationConstants::YES, array('class' => 'list-radio'));
                HtmlHelper::configureSwitchForRadio($fld);

                $fld = $frm->addHtml('', 'Cart', '<div class="separator separator-dashed my-2"></div><h3 class="form-section-head">' . Labels::getLabel("FRM_CART", $langId) . '</h3>');
                $fld->developerTags['colWidthValues'] = [null, '12', null, null];

                $fld = $frm->addCheckBox(Labels::getLabel('FRM_ON_PAYMENT_CANCEL_MAINTAIN_CART', $langId), 'CONF_MAINTAIN_CART_ON_PAYMENT_CANCEL', 1, array(), false, 0);
                HtmlHelper::configureSwitchForCheckbox($fld, Labels::getLabel("FRM_CART_ITEMS_WILL_BE_RETAINED_ON_CANCELLING_THE_PAYMENT", $langId));

                $fld = $frm->addCheckBox(Labels::getLabel('FRM_ON_PAYMENT_FAILURE_MAINTAIN_CART', $langId), 'CONF_MAINTAIN_CART_ON_PAYMENT_FAILURE', 1, array(), false, 0);
                HtmlHelper::configureSwitchForCheckbox($fld, Labels::getLabel("FRM_CART_ITEMS_WILL_BE_RETAINED_ON_PAYMENT_FAILURE", $langId));

                $fld = $frm->addIntegerField(Labels::getLabel("FRM_REMINDER_INTERVAL_FOR_PRODUCTS_IN_CART_[Days]", $langId), 'CONF_REMINDER_INTERVAL_PRODUCTS_IN_CART', '');
                $fld->htmlAfterField = "<span class='form-text text-muted'>" . Labels::getLabel("FRM_THIS_IS_THE_INTERVAL_IN_DAYS_TO_SEND_AUTO_NOTIFICATION_ALERT_TO_BUYER_FOR_PRODUCTS_IN_CART.", $langId) . "</span>";

                $fld = $frm->addIntegerField(Labels::getLabel("FRM_SET_NOTIFICATION_COUNT_TO_BE_SENT", $langId), 'CONF_SENT_CART_REMINDER_COUNT', '');
                $fld->htmlAfterField = "<span class='form-text text-muted'>" . Labels::getLabel("FRM_SET_HOW_MANY_NOTIFICATIONS_WILL_BE_SENT_TO_BUYER.", $langId) . "</span>";

                $fld = $frm->addHtml('', 'Wishlist', '<div class="separator separator-dashed my-2"></div><h3 class="form-section-head">' . Labels::getLabel("FRM_WISHLIST", $langId) . '</h3>');
                $fld->developerTags['colWidthValues'] = [null, '12', null, null];

                $fld = $frm->addIntegerField(Labels::getLabel("FRM_REMINDER_INTERVAL_FOR_PRODUCTS_IN_WISHLIST_[Days]", $langId), 'CONF_REMINDER_INTERVAL_PRODUCTS_IN_WISHLIST', '');
                $fld->htmlAfterField = "<span class='form-text text-muted'>" . Labels::getLabel("FRM_THIS_IS_THE_INTERVAL_IN_DAYS_TO_SEND_AUTO_NOTIFICATION_ALERT_TO_BUYER_FOR_PRODUCTS_IN_WISHLIST.", $langId) . "</span>";

                $fld = $frm->addIntegerField(Labels::getLabel("FRM_SET_NOTIFICATION_COUNT_TO_BE_SENT", $langId), 'CONF_SENT_WISHLIST_REMINDER_COUNT', '');
                $fld->htmlAfterField = "<span class='form-text text-muted'>" . Labels::getLabel("FRM_SET_HOW_MANY_NOTIFICATIONS_WILL_BE_SENT_TO_BUYER.", $langId) . "</span>";

                break;

            case Configurations::FORM_COMMISSION:

                $fld = $frm->addCheckBox(Labels::getLabel("FRM_COMMISSION_CHARGED_INCLUDING_SHIPPING", $langId), 'CONF_COMMISSION_INCLUDING_SHIPPING', 1, array(), false, 0);
                HtmlHelper::configureSwitchForCheckbox($fld, Labels::getLabel("FRM_COMMISSION_CHARGED_INCLUDING_SHIPPING_CHARGES", $langId));
                $fld->developerTags['noCaptionTag'] = true;

                $fld = $frm->addCheckBox(Labels::getLabel("FRM_COMMISSION_CHARGED_INCLUDING_TAX", $langId), 'CONF_COMMISSION_INCLUDING_TAX', 1, array(), false, 0);
                HtmlHelper::configureSwitchForCheckbox($fld, Labels::getLabel("FRM_COMMISSION_CHARGED_INCLUDING_TAX_CHARGES", $langId));
                $fld->developerTags['noCaptionTag'] = true;

                $fld = $frm->addIntegerField(Labels::getLabel("FRM_MAXIMUM_SITE_COMMISSION", $langId) . ' [' . $this->siteDefaultCurrencyCode . ']', 'CONF_MAX_COMMISSION', '');
                $fld->requirements()->setFloatPositive();
                $fld->htmlAfterField = "<span class='form-text text-muted'>" . Labels::getLabel("FRM_THIS_IS_MAXIMUM_COMMISSION/Fees_that_will_be_charged_on_a_particular_product.", $langId) . "</span>";

                break;

            case Configurations::FORM_AFFILIATE:
                /* Affiliate Accounts[ */

                $fld = $frm->addRadioButtons(
                    Labels::getLabel("FRM_REQUIRES_APPROVAL", $langId),
                    'CONF_AFFILIATES_REQUIRES_APPROVAL',
                    applicationConstants::getYesNoArr($langId),
                    '',
                    array('class' => 'list-radio')
                );
                $fld->developerTags['colWidthValues'] = [null, '12', null, null];
                HtmlHelper::configureSwitchForRadio($fld, Labels::getLabel("FRM_AUTOMATICALLY_APPROVE_ANY_NEW_AFFILIATES_WHO_SIGN_UP.", $langId));

                $fld = $frm->addTextBox(Labels::getLabel('FRM_SIGN_UP_COMMISSION', $langId) . ' [' . $this->siteDefaultCurrencyCode . ']', 'CONF_AFFILIATE_SIGNUP_COMMISSION');
                $fld->requirements()->setInt();
                $fld->htmlAfterField = '<span class="form-text text-muted">' . Labels::getLabel('FRM_AFFILIATE_WILL_GET_COMMISSION_WHEN_NEW_REGISTRATION_IS_RECEIVED_THROUGH_AFFILIATE.', $langId) . '</span>';

                $cpagesArr = ContentPage::getPagesForSelectBox($langId);
                $fld = $frm->addSelectBox(Labels::getLabel('FRM_AFFILIATE_TERMS', $langId), 'CONF_AFFILIATE_TERMS_AND_CONDITIONS_PAGE', $cpagesArr, '', array(), '');
                $fld->htmlAfterField = '<span class="form-text text-muted">' . Labels::getLabel('FRM_FORCES_AFFILIATE_TO_AGREE_TO_TERMS_BEFORE_AN_AFFILIATE_ACCOUNT_CAN_BE_CREATED.', $langId) . '</span>';

                $fld = $frm->addTextBox(Labels::getLabel("FRM_REFERRER_URL/link_Validity_Period", $langId), 'CONF_AFFILIATE_REFERRER_URL_VALIDITY');
                $fld->htmlAfterField = '<span class="form-text text-muted">' . Labels::getLabel('FRM_DAYS,_After_Which_Referrer_Url_Is_Expired.(Cookie_Data_on_landed_user)', $langId) . '</span>';

                $fld = $frm->addRadioButtons(
                    Labels::getLabel("FRM_NEW_AFFILIATE_ALERT_MAIL", $langId),
                    'CONF_NOTIFY_ADMIN_AFFILIATE_REGISTRATION',
                    applicationConstants::getYesNoArr($langId),
                    '',
                    array('class' => 'list-radio')
                );
                HtmlHelper::configureSwitchForRadio($fld, Labels::getLabel("FRM_SEND_AN_EMAIL_TO_THE_STORE_OWNER_WHEN_A_NEW_AFFILIATE_IS_REGISTERED", $langId));

                $fld = $frm->addCheckBox(
                    Labels::getLabel("FRM_ACTIVATE_EMAIL_VERIFICATION_AFTER_REGISTRATION", $langId),
                    'CONF_EMAIL_VERIFICATION_AFFILIATE_REGISTRATION',
                    1,
                    array(),
                    false,
                    0
                );
                HtmlHelper::configureSwitchForCheckbox($fld, Labels::getLabel("FRM_AFFILIATE_USER_NEED_TO_VERIFY_THEIR_EMAIL_ADDRESS", $langId));
                $fld->developerTags['colWidthValues'] = [null, '12', null, null];

                $fld = $frm->addCheckBox(
                    Labels::getLabel("FRM_ACTIVATE_SENDING_WELCOME_MAIL_AFTER_REGISTRATION", $langId),
                    'CONF_WELCOME_EMAIL_AFFILIATE_REGISTRATION',
                    1,
                    array(),
                    false,
                    0
                );
                HtmlHelper::configureSwitchForCheckbox($fld, Labels::getLabel("FRM_ON_ENABLING_THIS_FEATURE,_affiliate_will_receive_a_welcome_e-mail_after_registration.", $langId));
                $fld->developerTags['colWidthValues'] = [null, '12', null, null];
                break;

            case Configurations::FORM_REWARD_POINTS:
                $fld = $frm->addIntegerField(Labels::getLabel("FRM_REWARD_POINTS", $langId), 'CONF_REWARD_POINT');
                $fld->htmlAfterField = "<span class='form-text text-muted'>" . Labels::getLabel("FRM_SET_HOW_MANY_REWARDS_POINTS_EQUAL_TO", $langId) . " 1 " . $this->siteDefaultCurrencyCode . "</span>";
                $fld = $frm->addIntegerField(Labels::getLabel("FRM_MINIMUM_REWARD_POINT_REQUIRED_TO_USE", $langId), 'CONF_MIN_REWARD_POINT');
                $fld->htmlAfterField = "<span class='form-text text-muted'>" . Labels::getLabel("FRM_SET_MINIMUN_REWARD_POINTS_REQUIRED_USER_TO_AVAIL_DISCOUNT_DURING_CHECKOUT", $langId) . " .</span>";

                $fld = $frm->addIntegerField(Labels::getLabel("FRM_MAXIMUM_REWARD_POINT", $langId), 'CONF_MAX_REWARD_POINT');
                $fld->htmlAfterField = "<span class='form-text text-muted'>" . Labels::getLabel("FRM_SET_MAXIMUM_REWARD_POINTS_LIMIT_TO_AVAIL_DISCOUNT_DURING_CHECKOUT", $langId) . "</span>";

                $fld = $frm->addCheckBox(
                    Labels::getLabel("FRM_ACTIVATE_REWARD_POINT_ON_EVERY_PURCHASE", $langId),
                    'CONF_ENABLE_REWARDS_ON_PURCHASE',
                    1,
                    array(),
                    false,
                    0
                );
                HtmlHelper::configureSwitchForCheckbox($fld, Labels::getLabel("MSG_BUYER_WILL_REWARD_POINT_ON_EVERY_PURCHASE_AS_DEFINED_SETTINGS", $langId));
                $fld->developerTags['colWidthValues'] = [null, '12', null, null];

                $fld = $frm->addIntegerField(Labels::getLabel("FRM_REWARD_POINT_VALIDITY", $langId), 'CONF_REWARDS_VALIDITY_ON_PURCHASE');
                $fld->htmlAfterField = "<span class='form-text text-muted'>" . Labels::getLabel("FRM_REWARD_POINT_VALIDITY_IN_DAYS_FROM_DATE_OF_CREDIT", $langId) . "</span>";
                $fld = $frm->addHtml('', 'rewardSetting', '<label class="label"></label><a class="btn btn-underline" href="' . UrlHelper::generateUrl('rewardsOnPurchase') . '">' . Labels::getLabel("LBL_REWARD_ON_PURCHASE_CRITERIA", $langId) . '</a>');
                $fld =  $frm->addCheckBox(
                    Labels::getLabel("FRM_ENABLE_BIRTHDAY_DISCOUNT", $langId),
                    'CONF_ENABLE_BIRTHDAY_DISCOUNT_REWARDS',
                    1,
                    [],
                    false,
                    0
                );
                $fld->developerTags['colWidthValues'] = [null, '12', null, null];
                HtmlHelper::configureSwitchForCheckbox($fld, Labels::getLabel("FRM_ENABLE_BIRTHDAY_DISCOUNT_MSG", $langId));

                $fld = $frm->addTextBox(Labels::getLabel("FRM_BIRTHDAY_REWARD_POINTS", $langId), 'CONF_BIRTHDAY_REWARD_POINTS');
                $fld->htmlAfterField = "<span class='form-text text-muted'>" . Labels::getLabel("FRM_USER_GET_THIS_REWARD_POINTS_ON_HIS_BIRTHDAY.", $langId) . "</span>";

                $fld = $frm->addTextBox(Labels::getLabel("FRM_REWARD_POINTS_VALIDITY", $langId), 'CONF_BIRTHDAY_REWARD_POINTS_VALIDITY');
                $fld->htmlAfterField = "<span class='form-text text-muted'>" . Labels::getLabel("FRM_REWARD_POINTS_VALIDITY_IN_DAYS_FROM_THE_DATE_OF_CREDIT._Please_leave_it_blank_if_you_don't_want_reward_points_to_expire.", $langId) . "</span>";

                $fld = $frm->addHtml('', 'BuyingAnYear', '<div class="separator separator-dashed my-2"></div><h3 class="form-section-head">' . Labels::getLabel("FRM_BUYING_IN_AN_YEAR_REWARD_POINTS", $langId) . '</h3>');
                $fld->developerTags['colWidthValues'] = [null, '12', null, null];
                $fld = $frm->addRadioButtons(
                    Labels::getLabel("FRM_ENABLE_MODULE", $langId),
                    'CONF_ENABLE_BUYING_IN_AN_YEAR_REWARDS',
                    applicationConstants::getYesNoArr($langId),
                    '',
                    array('class' => 'list-radio')
                );
                HtmlHelper::configureSwitchForRadio($fld, Labels::getLabel("FRM_ENABLE_BUYING_IN_AN_YEAR_REWARD_POINTS_MODULE", $langId));

                $fld = $frm->addTextBox(Labels::getLabel("FRM_MINIMUM_BUYING_VALUE", $langId), 'CONF_BUYING_IN_AN_YEAR_MIN_VALUE');
                $fld->htmlAfterField = "<span class='form-text text-muted'>" . Labels::getLabel("FRM_MIN_BUYING_VALUE_IN_AN_YEAR_TO_GET_REWARD_POINTS", $langId) . "</span>";

                $fld = $frm->addTextBox(Labels::getLabel("FRM_REWARD_POINTS", $langId), 'CONF_BUYING_IN_AN_YEAR_REWARD_POINTS');
                $fld->htmlAfterField = "<span class='form-text text-muted'>" . Labels::getLabel("FRM_USER_GET_THIS_REWARD_POINTS_ON_MIN_BUYING_VALUE_IN_AN_YEAR", $langId) . "</span>";

                $fld = $frm->addTextBox(Labels::getLabel("FRM_REWARD_POINTS_VALIDITY", $langId), 'CONF_BUYING_IN_AN_YEAR_REWARD_POINTS_VALIDITY');
                $fld->htmlAfterField = "<span class='form-text text-muted'>" . Labels::getLabel("FRM_REWARD_POINTS_VALIDITY_IN_DAYS_FROM_THE_DATE_OF_CREDIT", $langId) . "</span>";

                $orderStatusArr = Orders::getOrderProductStatusArr($langId);
                $fld = $frm->addCheckBoxes(Labels::getLabel("FRM_BUYING_COMPLETION_ORDER_STATUS", $langId), 'CONF_BUYING_YEAR_REWARD_ORDER_STATUS', $orderStatusArr, [], array('class' => 'list-checkboxes'));
                $fld->developerTags['cbLabelAttributes'] = ['class' => 'checkbox'];
                $fld->developerTags['cbHtmlBeforeCheckbox'] = '';

                $fld->htmlAfterField = "<span class='form-text text-muted'>" . Labels::getLabel("FRM_SET_THE_ORDER_STATUS_THE_CUSTOMER's_order_must_reach_before_they_are_considered_completed_and_payment_released_to_Sellers.", $langId) . "</span>";
                $fld->developerTags['colWidthValues'] = [null, '12', null, null];

                break;

            case Configurations::FORM_REVIEWS:

                $fld = $frm->addRadioButtons(Labels::getLabel("FRM_ALLOW_REVIEWS", $langId), 'CONF_ALLOW_REVIEWS', applicationConstants::getYesNoArr($langId), '', array('class' => 'list-radio'));
                HtmlHelper::configureSwitchForRadio($fld);

                // $fld = $frm->addRadioButtons(Labels::getLabel("FRM_NEW_REVIEW_ALERT_EMAIL", $langId), 'CONF_REVIEW_ALERT_EMAIL', applicationConstants::getYesNoArr($langId), '', array('class' => 'list-radio'));
                // HtmlHelper::configureSwitchForRadio($fld);

                $reviewStatusArr = SelProdReview::getReviewStatusArr($langId);
                $fld = $frm->addSelectBox(
                    Labels::getLabel("FRM_DEFAULT_REVIEW_STATUS", $langId),
                    'CONF_DEFAULT_REVIEW_STATUS',
                    $reviewStatusArr,
                    false,
                    array(),
                    ''
                );
                $fld->htmlAfterField = "<span class='form-text text-muted'>" . Labels::getLabel("FRM_SET_THE_DEFAULT_REVIEW_ORDER_STATUS_WHEN_A_NEW_REVIEW_IS_PLACED", $langId) . "</span>";

                break;

            case Configurations::FORM_EMAIL:
                $frm->addTextBox(Labels::getLabel("FRM_FROM_NAME", $langId), 'CONF_FROM_NAME_' . $langId);
                $fld = $frm->addEmailField(Labels::getLabel("FRM_FROM_EMAIL", $langId), 'CONF_FROM_EMAIL');
                $fld->htmlAfterField = "<span class='form-text text-muted'>" . Labels::getLabel("FRM_REQUIRED_FOR_SENDING_EMAILS", $langId) . "</span>";
                // $fld = $frm->addEmailField(Labels::getLabel("FRM_REPLY_TO_EMAIL_ADDRESS", $langId), 'CONF_REPLY_TO_EMAIL');
                $fld->htmlAfterField = "<span class='form-text text-muted'>" . Labels::getLabel("FRM_REQUIRED_FOR_EMAIL_HEADERS_-_user_can_reply_to_this_email", $langId) . "</span>";

                $fld = $frm->addEmailField(Labels::getLabel("FRM_CONTACT_EMAIL_ADDRESS", $langId), 'CONF_CONTACT_EMAIL');
                $fld->htmlAfterField = "<span class='form-text text-muted'>" . Labels::getLabel("FRM_EMAIL_ID_TO_CONTACT_SITE_OWNER", $langId) . "</span>";

                $fld = $frm->addCheckBox(Labels::getLabel("FRM_SEND_EMAIL", $langId), 'CONF_SEND_EMAIL', 1, array(), false, 0);
                HtmlHelper::configureSwitchForCheckbox($fld);
                $fld->developerTags['colWidthValues'] = [null, '12', null, null];

                /* $fld = $frm->addRadioButtons(Labels::getLabel("FRM_SEND_EMAIL", $langId), 'CONF_SEND_EMAIL', applicationConstants::getYesNoArr($langId), '', array('class' => 'list-radio')); */
                HtmlHelper::configureSwitchForRadio($fld);

                if (FatApp::getConfig('CONF_SEND_EMAIL', FatUtility::VAR_INT, 1)) {
                    $fld = $frm->addHTML('', 'sendmailhtml', '<div class="border p-3 text-center cms" role="alert"><p>' . Labels::getLabel("FRM_CLICK_BUTTON_TO_SEND_TEST_EMAIL_TO_SITE_OWNER_AT", $langId) . ' -<br><strong>' . FatApp::getConfig("CONF_SITE_OWNER_EMAIL") . '</strong></p><a class="btn btn-secondary btn-sm" href="javascript:void(0)" id="testMail-js">' . Labels::getLabel("FRM_CLICK_HERE", $langId) . '</a></div>');
                    $fld->developerTags['colWidthValues'] = [null, '12', null, null];
                }

                $fld = $frm->addCheckBox(Labels::getLabel("FRM_SEND_SMTP_EMAIL", $langId), 'CONF_SEND_SMTP_EMAIL', 1, array(), false, 0);
                HtmlHelper::configureSwitchForCheckbox($fld);

                $fld = $frm->addRadioButtons(Labels::getLabel("FRM_SMTP_SECURE", $langId), 'CONF_SMTP_SECURE', applicationConstants::getSmtpSecureArr($langId), '', array('class' => 'list-radio'));
                HtmlHelper::configureSwitchForRadio($fld);

                /*   $frm->addRadioButtons(Labels::getLabel("FRM_SEND_SMTP_EMAIL", $langId), 'CONF_SEND_SMTP_EMAIL', applicationConstants::getYesNoArr($langId), '', array('class' => 'list-inline')); */

                $fld = $frm->addTextBox(Labels::getLabel("FRM_SMTP_HOST", $langId), 'CONF_SMTP_HOST');
                $fld = $frm->addTextBox(Labels::getLabel("FRM_SMTP_PORT", $langId), 'CONF_SMTP_PORT');
                $fld = $frm->addTextBox(Labels::getLabel("FRM_SMTP_USERNAME", $langId), 'CONF_SMTP_USERNAME');
                $fld = $frm->addPasswordField(Labels::getLabel("FRM_SMTP_PASSWORD", $langId), 'CONF_SMTP_PASSWORD');


                $fld = $frm->addTextarea(Labels::getLabel("FRM_ADDITIONAL_ALERT_E-Mails", $langId), 'CONF_ADDITIONAL_ALERT_EMAILS');
                $fld->developerTags['colWidthValues'] = [null, '12', null, null];
                $fld->htmlAfterField = "<span class='form-text text-muted'>" . Labels::getLabel("FRM_ANY_ADDITIONAL_EMAILS_YOU_WANT_TO_RECEIVE_THE_ALERT_EMAIL", $langId) . "</span>";

                break;

            case Configurations::FORM_LIVE_CHAT:
                $fld = $frm->addRadioButtons(
                    Labels::getLabel("FRM_ACTIVATE_LIVE_CHAT", $langId),
                    'CONF_ENABLE_LIVECHAT',
                    applicationConstants::getYesNoArr($langId),
                    '',
                    array('class' => 'list-radio')
                );
                HtmlHelper::configureSwitchForRadio($fld, Labels::getLabel("FRM_ACTIVATE_3RD_PARTY_LIVE_CHAT.", $langId));

                $fld = $frm->addTextarea(Labels::getLabel("FRM_LIVE_CHAT_CODE", $langId), 'CONF_LIVE_CHAT_CODE');
                $fld->developerTags['colWidthValues'] = [null, '12', null, null];
                $fld->htmlAfterField = "<span class='form-text text-muted'>" . Labels::getLabel("FRM_THIS_IS_THE_LIVE_CHAT_SCRIPT/code_provided_by_the_3rd_party_API_for_integration.", $langId) . "</span>";

                break;

            case Configurations::FORM_THIRD_PARTY_API:
                $frm->addHtml('', 'GooglePushNotification', '<h3 class="form-section-head">' . Labels::getLabel("FRM_GOOGLE_PUSH_NOTIFICATION", $langId) . '</h3>');

                $fld = $frm->addTextarea(Labels::getLabel('FRM_FIREBASE_SERVICE_ACCOUNT_PRIVATE_KEY_JSON_FILE'), 'CONF_FIREBASE_SERVICE_ACCOUNT_JSON_KEY');
                $fld->developerTags['colWidthValues'] = [null, '12', null, null];
                $link = '<a href="javascript:void(0)" title="' . Labels::getLabel('FRM_FIREBASE_SERVICE_ACCOUNT_PRIVATE_KEY_JSON_FILE_STEPS') . '" data-bs-toggle="modal" data-bs-target="#firebaseServiceAccountStepsJs">' . Labels::getLabel('LBL_STEPS', $this->siteLangId) . '</a>';
                $link .= HtmlHelper::getModalStructure('firebaseServiceAccountStepsJs', Labels::getLabel('LBL_FIREBASE_SERVICE_ACCOUNT_PRIVATE_KEY_JSON', $this->siteLangId), Extrapage::getFirebaseServiceAccountSteps($this->siteLangId));
                $lbl = CommonHelper::replaceStringData(Labels::getLabel("LBL_PLEASE_FOLLOW_{STEPS}_TO_GET_FIREBASE_SERVICE_ACCOUNT_JSON_KEY", $this->siteLangId), ['{STEPS}' => $link]);
                $fld->htmlAfterField = "<small class='form-text text-muted'>" . $lbl . "</small>";

                $fld = $frm->addHtml('', 'FaceBookPixel', '<div class="separator separator-dashed my-2"></div><h3 class="form-section-head">' . Labels::getLabel("FRM_FACEBOOK_PIXEL", $langId) . '</h3>');
                $fld->developerTags['colWidthValues'] = [null, '12', null, null];

                $fld = $frm->addTextBox(Labels::getLabel("FRM_FACEBOOK_PIXEL_ID", $langId), 'CONF_FACEBOOK_PIXEL_ID');
                $fld->htmlAfterField = "<span class='form-text text-muted'>" . Labels::getLabel("FRM_THIS_IS_THE_FACEBOOK_PIXEL_ID_USED_IN_TRACK_EVENTS.", $langId) . "</span>";

                $fld = $frm->addHtml('', 'Engagespot', '<div class="separator separator-dashed my-2"></div><h3 class="form-section-head">' . Labels::getLabel("FRM_ENGAGESPOT_PUSH_NOTIFICATIONS_(WEB)", $langId) . '</h3>');
                $fld->developerTags['colWidthValues'] = [null, '12', null, null];
                $fld = $frm->addRadioButtons(Labels::getLabel("FRM_ENABLE_ENGAGESPOT", $langId), 'CONF_ENABLE_ENGAGESPOT_PUSH_NOTIFICATION', applicationConstants::getYesNoArr($langId), '', array('class' => 'list-radio'));
                HtmlHelper::configureSwitchForRadio($fld);

                $fld = $frm->addTextBox(Labels::getLabel("FRM_API_KEY", $langId), 'CONF_ENGAGESPOT_API_KEY');
                $fld->htmlAfterField = "<span class='form-text text-muted'>" . Labels::getLabel("FRM_THIS_IS_THE_API_KEY_PROVIDED_BY_ENGAGESPOT.", $langId) . "</span>";

                $fld = $frm->addTextBox(Labels::getLabel("FRM_SECRET_KEY", $langId), 'CONF_ENGAGESPOT_SECRET_KEY');
                $fld->htmlAfterField = "<span class='form-text text-muted'>" . Labels::getLabel("FRM_THIS_IS_THE_SECRET_KEY_PROVIDED_BY_ENGAGESPOT.", $langId) . "</span>";

                // $fld = $frm->addTextarea(Labels::getLabel("FRM_ENGAGESPOT_CODE", $langId), 'CONF_ENGAGESPOT_PUSH_NOTIFICATION_CODE');
                // $fld->developerTags['colWidthValues'] = [null, '12', null, null];
                // $fld->htmlAfterField = "<span class='form-text text-muted'>" . Labels::getLabel("FRM_THIS_IS_THE_CODE_PROVIDED_BY_THE_ENGAGESPOT_FOR_INTEGRATION.", $langId) . "</span>";


                $fld = $frm->addHtml('', 'GoogleMap', '<div class="separator separator-dashed my-2"></div><h3 class="form-section-head">' . Labels::getLabel("FRM_GOOGLE_MAP_API", $langId) . '</h3>');
                $fld->developerTags['colWidthValues'] = [null, '12', null, null];
                $fld = $frm->addTextBox(Labels::getLabel("FRM_GOOGLE_MAP_API_KEY", $langId), 'CONF_GOOGLEMAP_API_KEY');
                $fld->developerTags['colWidthValues'] = [null, '12', null, null];
                $fld->htmlAfterField = "<span class='form-text text-muted'>" . Labels::getLabel("FRM_THIS_IS_THE_GOOGLE_MAP_API_KEY_USED_TO_GET_USER_CURRENT_LOCATION.", $langId) . "</span>";

                $fld = $frm->addHtml('', 'Newsletter', '<div class="separator separator-dashed my-2"></div><h3 class="form-section-head">' . Labels::getLabel("FRM_NEWSLETTER_SUBSCRIPTION", $langId) . '</h3>');
                $fld->developerTags['colWidthValues'] = [null, '12', null, null];

                $fld = $frm->addRadioButtons(Labels::getLabel("FRM_ACTIVATE_NEWSLETTER_SUBSCRIPTION", $langId), 'CONF_ENABLE_NEWSLETTER_SUBSCRIPTION', applicationConstants::getYesNoArr($langId), '', array('class' => 'list-radio'));
                HtmlHelper::configureSwitchForRadio($fld);

                $fld = $frm->addRadioButtons(Labels::getLabel("FRM_EMAIL_MARKETING_SYSTEM", $langId), 'CONF_NEWSLETTER_SYSTEM', applicationConstants::getNewsLetterSystemArr($langId), '', array('class' => 'list-radio'));
                HtmlHelper::configureSwitchForRadio($fld, Labels::getLabel("FRM_PLEASE_SELECT_THE_SYSTEM_YOU_WISH_TO_USE_FOR_EMAIL_MARKETING.", $langId));

                $fld = $frm->addTextBox(Labels::getLabel("FRM_MAILCHIMP_KEY", $langId), 'CONF_MAILCHIMP_KEY');
                $fld->htmlAfterField = "<span class='form-text text-muted'>" . Labels::getLabel("FRM_THIS_IS_THE_MAILCHIMP's_application_key_used_in_subscribe_and_send_newsletters.", $langId) . "</span>";

                $fld = $frm->addTextBox(Labels::getLabel("FRM_MAILCHIMP_LIST_ID", $langId), 'CONF_MAILCHIMP_LIST_ID');
                $fld->htmlAfterField = "<span class='form-text text-muted'>" . Labels::getLabel("FRM_THIS_IS_THE_MAILCHIMP'S_SUBSCRIBERS_LIST_ID.", $langId) . "</span>";

                $fld = $frm->addTextarea(Labels::getLabel("FRM_AWEBER_SIGNUP_FORM_CODE", $langId), 'CONF_AWEBER_SIGNUP_CODE');
                $fld->developerTags['colWidthValues'] = [null, '12', null, null];
                $fld->htmlAfterField = "<span class='form-text text-muted'>" . Labels::getLabel("FRM_ENTER_THE_NEWSLETTER_SIGNUP_CODE_RECEIVED_FROM_AWEBER", $langId) . "</span>";

                $fld = $frm->addHtml('', 'Analytics', '<div class="separator separator-dashed my-2"></div><h3 class="form-section-head">' . Labels::getLabel("FRM_GOOGLE_ANALYTICS", $langId) . '</h3>');
                $fld->developerTags['colWidthValues'] = [null, '12', null, null];

                $ga4Fld = $frm->addCheckBox(
                    Labels::getLabel("LBL_ACTIVATE_GOOGLE_ANALYTICS_4", $this->siteLangId),
                    'CONF_GOOGLE_ANALYTICS_4',
                    1,
                    array('class' => 'fieldsVisibilityJs onlyShowHideJs ga4ToggleEleJs'),
                    false,
                    0
                );
                $ga4Fld->developerTags['colWidthValues'] = [null, '12', null, null];
                HtmlHelper::configureSwitchForCheckbox($ga4Fld, Labels::getLabel("LBL_WHEN_DISABLED_CODE_WILL_BE_IN_SYNC_WITH_THE_OLDER_GOOGLE_ANALYTICS_VERSION.", $this->siteLangId));

                /* $fld = $frm->addRadioButtons(Labels::getLabel("FRM_ADVANCE_ECOMMERCE_TRACKING", $langId), 'CONF_ANALYTICS_ADVANCE_ECOMMERCE', applicationConstants::getYesNoArr($langId), applicationConstants::NO, array('class' => 'list-radio'));
                $fld->htmlAfterField = "<span class='form-text text-muted'>" . Labels::getLabel("FRM_INCASE_OF_GOOGLE_ANALYTICS_4_YOU_NEED_TO_UPDATE_JS_TRACKING_CODE_IN_SEO_TAB.", $langId) . "</span>";
                HtmlHelper::configureSwitchForRadio($fld); */


                $fld = $frm->addTextBox(Labels::getLabel("FRM_CLIENT_ID", $langId), 'CONF_ANALYTICS_CLIENT_ID');
                $fld->htmlAfterField = "<span class='form-text text-muted'>" . Labels::getLabel("FRM_THIS_IS_THE_APPLICATION_CLIENT_ID_USED_IN_ANALYTICS_DASHBOARD.", $langId) . "</span>";

                $fld = $frm->addTextBox(Labels::getLabel("FRM_SECRET_KEY", $langId), 'CONF_ANALYTICS_SECRET_KEY');
                $fld->htmlAfterField = "<span class='form-text text-muted'>" . Labels::getLabel("FRM_THIS_IS_THE_APPLICATION_SECRET_KEY_USED_IN_ANALYTICS_DASHBOARD.", $langId) . "</span>";

                $fld = $frm->addTextBox(Labels::getLabel("FRM_ANALYTICS_ID", $langId), 'CONF_ANALYTICS_ID');
                $fld->htmlAfterField = "<span class='form-text text-muted'>" . Labels::getLabel("FRM_THIS_IS_THE_GOOGLE_ANALYTICS_ID._Ex._UA-xxxxxxx-xx.", $langId) . "</span>";

                $fld = $frm->addTextBox(Labels::getLabel("LBL_GOOGLE_ANALYTICS_PROPERTY_ID", $this->siteLangId), 'CONF_PROPERTY_ID');
                $link = '<a href="javascript:void(0)" title="' . Labels::getLabel('LBL_GOOGLE_ANALYTICS_PROPERTY_ID') . '" data-bs-toggle="modal" data-bs-target="#propertyIdStepsJs">' . Labels::getLabel('LBL_STEPS', $this->siteLangId) . '</a>';
                $link .= HtmlHelper::getModalStructure('propertyIdStepsJs', Labels::getLabel('LBL_GA4_PROPERTY_ID', $this->siteLangId), Extrapage::getGoogleAnalyticsPropertyIdSteps($this->siteLangId));
                $lbl = CommonHelper::replaceStringData(Labels::getLabel("LBL_PLEASE_FOLLOW_{STEPS}_TO_GET_GA4_PROPERTY_ID:", $this->siteLangId), ['{STEPS}' => $link]);
                $fld->htmlAfterField = "<small class='form-text text-muted'>" . $lbl . "</small>";

                $fld = $frm->addTextarea(Labels::getLabel('LBL_GOOGLE_SERVICE_ACCOUNT_JSON'), 'CONF_GOOGLE_ANALYTICS_CLIENT_JSON');
                $fld->developerTags['colWidthValues'] = [null, '12', null, null];

                $link = '<a href="javascript:void(0)" title="' . Labels::getLabel('LBL_GOOGLE_SERVICE_ACCOUNT_JSON') . '" data-bs-toggle="modal" data-bs-target="#serviceAccountStepsJs">' . Labels::getLabel('LBL_STEPS', $this->siteLangId) . '</a>';
                $link .= HtmlHelper::getModalStructure('serviceAccountStepsJs', Labels::getLabel('LBL_GOOGLE_SERVICE_ACCOUNT_DETAIL', $this->siteLangId), Extrapage::getGoogleServiceAccountSteps($this->siteLangId));
                $lbl = CommonHelper::replaceStringData(Labels::getLabel("LBL_PLEASE_FOLLOW_{STEPS}_TO_GET_SERVICE_ACCOUNT_DETAIL_:", $this->siteLangId), ['{STEPS}' => $link]);
                $fld->htmlAfterField = "<small class='form-text text-muted'>" . $lbl . "</small>";

                if (!isset($_POST['CONF_ANALYTICS_ID'])) {
                    $aClientId = new FormFieldRequirement('CONF_ANALYTICS_CLIENT_ID', 'value');
                    $aClientId->setRequired(false);
                    $reqaClientId = new FormFieldRequirement('CONF_ANALYTICS_CLIENT_ID', 'value');
                    $reqaClientId->setRequired(true);

                    $aSecretKey = new FormFieldRequirement('CONF_ANALYTICS_SECRET_KEY', 'value');
                    $aSecretKey->setRequired(false);
                    $reqaSecretKey = new FormFieldRequirement('CONF_ANALYTICS_SECRET_KEY', 'value');
                    $reqaSecretKey->setRequired(true);

                    $analyticsId = new FormFieldRequirement('CONF_ANALYTICS_ID', 'value');
                    $analyticsId->setRequired(false);
                    $reqAnalyticsId = new FormFieldRequirement('CONF_ANALYTICS_ID', 'value');
                    $reqAnalyticsId->setRequired(true);

                    $propertyId = new FormFieldRequirement('CONF_PROPERTY_ID', 'value');
                    $propertyId->setRequired(false);
                    $reqPropertyId = new FormFieldRequirement('CONF_PROPERTY_ID', 'value');
                    $reqPropertyId->setRequired(true);

                    $ga4ClientJson = new FormFieldRequirement('CONF_GOOGLE_ANALYTICS_CLIENT_JSON', 'value');
                    $ga4ClientJson->setRequired(false);
                    $reqGa4ClientJson = new FormFieldRequirement('CONF_GOOGLE_ANALYTICS_CLIENT_JSON', 'value');
                    $reqGa4ClientJson->setRequired(true);

                    $ga4Fld->requirements()->addOnChangerequirementUpdate(applicationConstants::YES, 'eq', 'CONF_PROPERTY_ID', $reqPropertyId);
                    $ga4Fld->requirements()->addOnChangerequirementUpdate(applicationConstants::YES, 'eq', 'CONF_GOOGLE_ANALYTICS_CLIENT_JSON', $reqGa4ClientJson);
                    $ga4Fld->requirements()->addOnChangerequirementUpdate(applicationConstants::YES, 'eq', 'CONF_ANALYTICS_CLIENT_ID', $aClientId);
                    $ga4Fld->requirements()->addOnChangerequirementUpdate(applicationConstants::YES, 'eq', 'CONF_ANALYTICS_SECRET_KEY', $aSecretKey);
                    $ga4Fld->requirements()->addOnChangerequirementUpdate(applicationConstants::YES, 'eq', 'CONF_ANALYTICS_ID', $analyticsId);

                    $ga4Fld->requirements()->addOnChangerequirementUpdate(applicationConstants::NO, 'eq', 'CONF_PROPERTY_ID', $propertyId);
                    $ga4Fld->requirements()->addOnChangerequirementUpdate(applicationConstants::NO, 'eq', 'CONF_GOOGLE_ANALYTICS_CLIENT_JSON', $ga4ClientJson);
                    $ga4Fld->requirements()->addOnChangerequirementUpdate(applicationConstants::NO, 'eq', 'CONF_ANALYTICS_CLIENT_ID', $reqaClientId);
                    $ga4Fld->requirements()->addOnChangerequirementUpdate(applicationConstants::NO, 'eq', 'CONF_ANALYTICS_SECRET_KEY', $reqaSecretKey);
                    $ga4Fld->requirements()->addOnChangerequirementUpdate(applicationConstants::NO, 'eq', 'CONF_ANALYTICS_ID', $reqGa4ClientJson);
                }

                if (0 == FatApp::getConfig('CONF_GOOGLE_ANALYTICS_4', FatUtility::VAR_INT, 0)) {
                    $accessToken = FatApp::getConfig("CONF_ANALYTICS_ACCESS_TOKEN", FatUtility::VAR_STRING, '');

                    include_once CONF_INSTALLATION_PATH . 'library/analytics/analyticsapi.php';
                    $analyticArr = array(
                        'clientId' => FatApp::getConfig("CONF_ANALYTICS_CLIENT_ID", FatUtility::VAR_STRING, ''),
                        'clientSecretKey' => FatApp::getConfig("CONF_ANALYTICS_SECRET_KEY", FatUtility::VAR_STRING, ''),
                        'redirectUri' => UrlHelper::generateFullUrl('configurations', 'redirect', array(), '', false),
                        'googleAnalyticsID' => FatApp::getConfig("CONF_ANALYTICS_ID", FatUtility::VAR_STRING, '')
                    );
                    try {
                        $analytics = new Ykart_analytics($analyticArr);
                        $authUrl = $analytics->buildAuthUrl();
                    } catch (exception $e) {
                        $authUrl = '';
                    }

                    if ($authUrl) {
                        $authenticateText = ($accessToken == '') ? 'Authenticate' : 'Re-Authenticate';
                        $lbl = Labels::getLabel('LBL_{CLICK-HERE}_TO_{TXT}_SETTINGS.', $this->siteLangId);
                        $lbl = CommonHelper::replaceStringData($lbl, [
                            '{CLICK-HERE}' => '<a class="link-underline" href="' . $authUrl . '" >' . Labels::getLabel('LBL_CLICK_HERE', $this->siteLangId) . '</a>',
                            '{TXT}' => $authenticateText,
                        ]);

                        $fld = $frm->addHTML('', 'accessToken', '<div class="cta-settings gaAccessTokenJs">' . $lbl . '</div>', '', 'class="medium"');
                    } else {
                        $fld = $frm->addHTML('', 'accessToken', '<div class="cta-settings gaAccessTokenJs">' . Labels::getLabel('LBL_PLEASE_CONFIGURE_YOUR_SETTINGS_AND_THEN_AUTHENTICATE_THEM', $this->siteLangId) . '</div>', '', 'class="medium"');
                    }

                    $fld->developerTags['colWidthValues'] = [null, '12', null, null];
                }

                $fld = $frm->addHtml('', 'seperator', '<div class="separator separator-dashed my-2"></div>');
                $fld->developerTags['colWidthValues'] = [null, '12', null, null];

                $fld =  $frm->addHtml('', 'GoogleReCaptcha', '<h3 class="form-section-head">' . Labels::getLabel("FRM_GOOGLE_RECAPTCHA_V3", $langId) . '</h3>');
                $fld->developerTags['colWidthValues'] = [null, '12', null, null];
                $fld = $frm->addTextBox(Labels::getLabel("FRM_SITE_KEY", $langId), 'CONF_RECAPTCHA_SITEKEY');
                $fld->htmlAfterField = "<span class='form-text text-muted'>" . Labels::getLabel("FRM_THIS_IS_THE_APPLICATION_SITE_KEY_USED_FOR_GOOGLE_RECAPTCHA.", $langId) . "</span>";

                $fld = $frm->addTextBox(Labels::getLabel("FRM_SECRET_KEY", $langId), 'CONF_RECAPTCHA_SECRETKEY');
                $fld->htmlAfterField = "<span class='form-text text-muted'>" . Labels::getLabel("FRM_THIS_IS_THE_APPLICATION_SECRET_KEY_USED_FOR_GOOGLE_RECAPTCHA.", $langId) . "</span>";

                $fld =  $frm->addHtml('', 'Translatorseperator', '<div class="separator separator-dashed my-2"></div>');
                $fld->developerTags['colWidthValues'] = [null, '12', null, null];

                $fld = $frm->addHtml('', 'Microsoft Translator Text API', '<h3 class="form-section-head">' . Labels::getLabel("FRM_MICROSOFT_TRANSLATOR_TEXT_API", $langId) . '</h3>');
                $fld->developerTags['colWidthValues'] = [null, '12', null, null];

                $fld = $frm->addTextBox(Labels::getLabel("FRM_SUBSCRIPTION_KEY", $langId), 'CONF_TRANSLATOR_SUBSCRIPTION_KEY');
                $fld->htmlAfterField = "<span class='form-text text-muted'>" . Labels::getLabel("FRM_MICROSOFT_TRANSLATOR_TEXT_API_3.0_SUBSCRIPTION_KEY.", $langId) . "</span>";

                $fld = $frm->addTextBox(Labels::getLabel("FRM_SUBSCRIPTION_KEY_REGION", $langId), 'CONF_TRANSLATOR_SUBSCRIPTION_KEY_REGION');
                $fld->htmlAfterField = "<span class='form-text text-muted'>" . Labels::getLabel("FRM_MICROSOFT_TRANSLATOR_TEXT_API_3.0_SUBSCRIPTION_KEY_REGION_(OPTIONAL).", $langId) . "</span>";

                $fld =  $frm->addHtml('', 'JWPlayerseperator', '<div class="separator separator-dashed my-2"></div>');
                $fld->developerTags['colWidthValues'] = [null, '12', null, null];
                /* JW player Settings */
                $frm->addHtml('', 'JWPlayerSettings', '<h3 class="form-section-head">' . Labels::getLabel("FRM_JW_PLAYER_SETTINGS", $langId) . '</h3>');
                $frm->addHtml('', 'GoogleFontsAPI', '<h3 class="form-section-head">' . Labels::getLabel("FRM_GOOGLE_FONTS_API", $langId) . '</h3>');

                $fld = $frm->addTextBox(Labels::getLabel("FRM_JW_PLAYER_KEY", $langId), 'CONF_JW_PLAYER_KEY');
                $fld->htmlAfterField = "<span class='form-text text-muted'>" . Labels::getLabel("FRM_THIS_IS_THE_KEY_PROVIDED_BY_JW_PLAYER", $langId) . "</span>";
                /* JW player Settings */

                $fld = $frm->addTextBox(Labels::getLabel("FRM_API_KEY", $langId), 'CONF_GOOGLE_FONTS_API_KEY');
                break;
            case Configurations::FORM_REFERAL:
                $fld = $frm->addRadioButtons(
                    Labels::getLabel("FRM_ENABLE_REFERRAL_MODULE", $langId),
                    'CONF_ENABLE_REFERRER_MODULE',
                    applicationConstants::getYesNoArr($langId),
                    '',
                    array('class' => 'list-radio')
                );

                $fld = $frm->addIntegerField(Labels::getLabel("FRM_REFERRER_URL/Link_Validity_Period", $langId), 'CONF_REFERRER_URL_VALIDITY');
                $fld->requirements()->setIntPositive();
                $string = Labels::getLabel("FRM_DAYS,_after_which_Referrer_Url_is_Expired.", $langId);
                $fld->htmlAfterField = "<span class='form-text text-muted'>" . $string . "</span>";

                $fld = $frm->addHtml('', 'RewardsOnRegistration', '<div class="separator separator-dashed my-2"></div><h3 class="form-section-head">' . Labels::getLabel("FRM_REWARD_BENEFITS_ON_REGISTRATION_(_APPLICABLE_FOR_WEB_INTERFACE_ONLY_)", $langId) . '</h3>');
                $fld->developerTags['colWidthValues'] = [null, '12', null, null];

                $fld = $frm->addTextBox(Labels::getLabel("FRM_REFERRER_REWARD_POINTS", $langId), 'CONF_REGISTRATION_REFERRER_REWARD_POINTS');
                $fld->requirements()->setIntPositive();
                $fld->htmlAfterField = "<span class='form-text text-muted'>" . Labels::getLabel("FRM_REFERRERS_GET_THIS_REWARD_POINTS_WHEN_THEIR_REFERRALS_(friends)_will_register.", $langId) . "</span>";

                $fld = $frm->addTextBox(Labels::getLabel("FRM_REFERRER_REWARD_POINTS_VALIDITY", $langId), 'CONF_REGISTRATION_REFERRER_REWARD_POINTS_VALIDITY');
                $fld->requirements()->setIntPositive();
                $fld->htmlAfterField = "<span class='form-text text-muted'>" . Labels::getLabel("FRM_REWARDS_POINTS_VALIDITY_IN_DAYS_FROM_THE_DATE_OF_CREDIT", $langId) . "</span>";

                $fld = $frm->addTextBox(Labels::getLabel("FRM_REFERRAL_REWARD_POINTS", $langId), 'CONF_REGISTRATION_REFERRAL_REWARD_POINTS');
                $fld->requirements()->setIntPositive();
                $fld->htmlAfterField = "<span class='form-text text-muted'>" . Labels::getLabel("FRM_REFERRALS_GET_THIS_REWARD_POINTS_WHEN_THEY_REGISTER_THROUGH_REFERRER.", $langId) . "</span>";

                $fld = $frm->addTextBox(Labels::getLabel("FRM_REFERRAL_REWARD_POINTS_VALIDITY", $langId), 'CONF_REGISTRATION_REFERRAL_REWARD_POINTS_VALIDITY');
                $fld->requirements()->setIntPositive();
                $fld->htmlAfterField = "<span class='form-text text-muted'>" . Labels::getLabel("FRM_REWARDS_POINTS_VALIDITY_IN_DAYS_FROM_THE_DATE_OF_CREDIT", $langId) . "</span>";

                $fld =  $frm->addHtml('', 'RewardsonPurchase', '<div class="separator separator-dashed my-2"></div><h3 class="form-section-head">' . Labels::getLabel("FRM_REWARD_BENEFITS_ON_FIRST_PURCHASE_(_APPLICABLE_FOR_WEB_INTERFACE_ONLY_)", $langId) . '</h3>');
                $fld->developerTags['colWidthValues'] = [null, '12', null, null];

                $fld = $frm->addTextBox(Labels::getLabel("FRM_REFERRER_REWARD_POINTS", $langId), 'CONF_SALE_REFERRER_REWARD_POINTS');
                $fld->requirements()->setIntPositive();
                $fld->htmlAfterField = "<span class='form-text text-muted'>" . Labels::getLabel("FRM_REFERRERS_GET_THIS_REWARD_POINTS_WHEN_THEIR_REFERRALS_(friends)_will_make_first_purchase.", $langId) . "</span>";

                $fld = $frm->addTextBox(Labels::getLabel("FRM_REFERRER_REWARD_POINTS_VALIDITY", $langId), 'CONF_SALE_REFERRER_REWARD_POINTS_VALIDITY');
                $fld->requirements()->setIntPositive();
                $fld->htmlAfterField = "<span class='form-text text-muted'>" . Labels::getLabel("FRM_REWARDS_POINTS_VALIDITY_IN_DAYS_FROM_THE_DATE_OF_CREDIT", $langId) . "</span>";

                $fld = $frm->addTextBox(Labels::getLabel("FRM_REFERRAL_REWARD_POINTS", $langId), 'CONF_SALE_REFERRAL_REWARD_POINTS');
                $fld->requirements()->setIntPositive();
                $fld->htmlAfterField = "<span class='form-text text-muted'>" . Labels::getLabel("FRM_REFERRALS_GET_THIS_REWARD_POINTS_WHEN_THEY_WILL_MAKE_FIRST_PURCHASE_THROUGH_THEIR_REFERRERS.", $langId) . "</span>";

                $fld = $frm->addTextBox(Labels::getLabel("FRM_REWARDS_POINTS_VALIDITY_IN_DAYS", $langId), 'CONF_SALE_REFERRAL_REWARD_POINTS_VALIDITY');
                $fld->requirements()->setIntPositive();
                $fld->htmlAfterField = "<span class='form-text text-muted'>" . Labels::getLabel("FRM_NOTE:Rewards_points_validity_in_days_from_the_date_of_credit", $langId) . "</span>";

                break;

            case Configurations::FORM_DISCOUNT:
                $fld = $frm->addRadioButtons(
                    Labels::getLabel("FRM_ENABLE_1ST_TIME_BUYERS_DISCOUNT", $langId),
                    'CONF_ENABLE_FIRST_TIME_BUYER_DISCOUNT',
                    applicationConstants::getYesNoArr($langId),
                    '',
                    array('class' => 'list-radio')
                );
                HtmlHelper::configureSwitchForRadio($fld);

                $percentageFlatArr = applicationConstants::getPercentageFlatArr($langId);
                $frm->addSelectBox(Labels::getLabel("FRM_DISCOUNT_IN", $langId), 'CONF_FIRST_TIME_BUYER_COUPON_IN_PERCENT', $percentageFlatArr, '', array('class' => 'discountInJs'), '');

                $fld =  $frm->addTextBox(Labels::getLabel("FRM_DISCOUNT_VALUE", $langId), 'CONF_FIRST_TIME_BUYER_COUPON_DISCOUNT_VALUE');
                $fld->requirements()->setPositive();

                $fld = $frm->addTextBox(Labels::getLabel("FRM_MINIMUM_ORDER_VALUE", $langId), 'CONF_FIRST_TIME_BUYER_COUPON_MIN_ORDER_VALUE');
                $fld->htmlAfterField = "<span class='form-text text-muted'>" . Labels::getLabel("FRM_MINIMUM_ORDER_VALUE_ON_WHICH_THE_COUPON_CAN_BE_APPLIED.", $langId) . "</span>";
                $fld->requirements()->setPositive();

                $fld = $frm->addTextBox(Labels::getLabel("FRM_MAX_DISCOUNT_VALUE", $langId), 'CONF_FIRST_TIME_BUYER_COUPON_MAX_DISCOUNT_VALUE');
                $fld->htmlAfterField = "<span class='form-text text-muted'>" . Labels::getLabel("FRM_MAX_DISCOUNT_VALUE_USER_CAN_GET_BY_USING_THIS_COUPON.", $langId) . "</span>";
                $fld->setWrapperAttribute('class', 'maxDisValJs');

                $fld = $frm->addTextBox(Labels::getLabel("FRM_DISCOUNT_COUPON_VALIDITY", $langId), 'CONF_FIRST_TIME_BUYER_COUPON_VALIDITY');
                $fld->htmlAfterField = "<span class='form-text text-muted'>" . Labels::getLabel("FRM_COUPON_VALIDITY_IN_DAYS_FROM_THE_DATE_OF_CREDIT", $langId) . "</span>";
                break;
            case Configurations::FORM_SUBSCRIPTION:
                $fld = $frm->addRadioButtons(
                    Labels::getLabel('FRM_ENABLE_SUBSCRIPTION_MODULE', $langId),
                    'CONF_ENABLE_SELLER_SUBSCRIPTION_MODULE',
                    applicationConstants::getYesNoArr($langId),
                    '',
                    array('class' => 'list-radio')
                );
                HtmlHelper::configureSwitchForRadio($fld, Labels::getLabel('FRM_SELLER_NEEDS_TO_PURCHASE_THE_SUBSCRPTION_BEFORE_LISTING_PRODUCTS', $langId));

                $fld = $frm->addRadioButtons(
                    Labels::getLabel('FRM_ENABLE_ADJUST_AMOUNT', $langId),
                    'CONF_ENABLE_ADJUST_AMOUNT_CHANGE_PLAN',
                    applicationConstants::getYesNoArr($langId),
                    '',
                    array('class' => 'list-radio')
                );
                HtmlHelper::configureSwitchForRadio($fld, Labels::getLabel('FRM_SUBSCRIPTION_PAYMENT_WILL_BE_ADJUSTED_WHILE_UPGRADING/downgrading_plan', $langId));

                $orderSubscriptionStatusArr = Orders::getOrderSubscriptionStatusArr($langId);
                $fld = $frm->addTextBox(Labels::getLabel("FRM_REMINDER_EMAIL_BEFORE_SUBSCRIPTION_EXPIRE_DAYS", $langId), 'CONF_BEFORE_EXIPRE_SUBSCRIPTION_REMINDER_EMAIL_DAYS');
                $fld->htmlAfterField = "<span class='form-text text-muted'>" . Labels::getLabel("FRM_BEFORE_HOW_MANY_DAYS_EMAIL_NEEDS_TO_BE_SENT_TO_USER_BEFORE_ENDING_SUBSCRIPTION.", $langId) . "</span>";

                $fld = $frm->addSelectBox(
                    Labels::getLabel("FRM_IN-Active_Order_Status", $langId),
                    'CONF_SUBSCRIPTION_INACTIVE_ORDER_STATUS',
                    $orderSubscriptionStatusArr,
                    false,
                    array(),
                    ''
                );

                $fld = $frm->addCheckBoxes(Labels::getLabel("FRM_SELLER_SUBSCRIPTION_STATUSES", $langId), 'CONF_SELLER_SUBSCRIPTION_STATUS', $orderSubscriptionStatusArr, [], array('class' => 'list-checkboxes'));
                $fld->developerTags['colWidthValues'] = [null, '12', null, null];
                $fld->developerTags['cbLabelAttributes'] = ['class' => 'checkbox'];
                $fld->developerTags['cbHtmlBeforeCheckbox'] = '';
                break;

            case Configurations::FORM_SYSTEM:
                $fld = $frm->addCheckBox(Labels::getLabel("FRM_ENABLE_MAINTENANCE_MODE", $langId), 'CONF_MAINTENANCE', applicationConstants::YES, array(), false, applicationConstants::NO);
                HtmlHelper::configureSwitchForCheckbox($fld, Labels::getLabel("FRM_NOTE:_Enable_Maintenance_Mode_Text", $langId));
                $fld->developerTags['colWidthValues'] = [null, '12', null, null];

                $fld = $frm->addCheckBox(Labels::getLabel("FRM_USE_SSL", $langId), 'CONF_USE_SSL', applicationConstants::YES, array(), false, applicationConstants::NO);
                HtmlHelper::configureSwitchForCheckbox($fld, Labels::getLabel("FRM_NOTE:_To_use_SSL,_check_with_your_host_if_a_SSL_certificate_is_installed_and_enable_it_from_here.", $langId));
                $fld->developerTags['colWidthValues'] = [null, '12', null, null];

                $fld = $frm->addSelectBox(
                    Labels::getLabel('FRM_DEFAULT_SITE_LAGUAGE', $langId),
                    'CONF_DEFAULT_SITE_LANG',
                    Language::getAllNames(),
                    false,
                    array('disabled' => 'disabled', 'title' => Labels::getLabel('ERR_YOU_CANNOT_CHANGE_SITE_DEFAULT_LANGUAGE', $langId), 'data-bs-toggle' => 'tooltip'),
                    ''
                );
                $fld->htmlAfterField = '<span class="form-text text-muted">' . Labels::getLabel("FRM_CHANGING_DEFAULT_SITE_LANGUAGE,_MAKE_SURE_ALL_THE_INFORMATION_IS_UPDATED_WITH_THIS_LANGUAGE", $langId) . '</span>';

                $currencyArr = Currency::getCurrencyNameWithCode($langId);
                $frm->addSelectBox(Labels::getLabel('FRM_DEFAULT_SYSTEM_CURRENCY', $langId), 'CONF_CURRENCY', $currencyArr, false, array(), '');

                $currencySeparatorArr = applicationConstants::currencySeparatorArr($langId);
                $frm->addSelectBox(Labels::getLabel('FRM_DEFAULT_CURRENCY_DECIMAL_SEPARATOR', $langId), 'CONF_DEFAULT_CURRENCY_SEPARATOR', $currencySeparatorArr, false, array(), '');

                $fld = $frm->addSelectBox(Labels::getLabel('FRM_TIMEZONE', $langId), 'CONF_TIMEZONE', Configurations::dateTimeZoneArr(), false, array(), '');
                $fld->htmlAfterField = '<span class="form-text text-muted">' . Labels::getLabel("FRM_CURRENT", $langId) . ' <span id="currentDate">' . CommonHelper::currentDateTime(null, true) . '</span></span>';

                $frm->addSelectBox(Labels::getLabel('FRM_DATE_FORMAT', $langId), 'CONF_DATE_FORMAT', Configurations::dateFormatPhpArr(), false, array(), '');

                $fld = $frm->addTextBox(Labels::getLabel('FRM_TIME_FOR_AUTO_CLOSE_MESSAGES', $langId), 'CONF_TIME_AUTO_CLOSE_SYSTEM_MESSAGES');
                $fld->htmlAfterField = '<span class="form-text text-muted">' . Labels::getLabel("FRM_NOTE:_After_how_much_seconds_system_message_should_be_close", $langId) . '.</span>';
                $fld->requirements()->setInt();

                $fld = $frm->addCheckBox(Labels::getLabel("FRM_AUTO_CLOSE_SYSTEM_MESSAGES", $langId), 'CONF_AUTO_CLOSE_SYSTEM_MESSAGES', applicationConstants::YES, array(), false, applicationConstants::NO);
                HtmlHelper::configureSwitchForCheckbox($fld);
                $fld->addFieldTagAttribute("onchange", "changedMessageAutoCloseSetting(this.value);");

                $fld = $frm->addCheckBox(Labels::getLabel("FRM_HOME_PAGE_LOADER", $langId), 'CONF_LOADER', 1, array(), false, 0);
                HtmlHelper::configureSwitchForCheckbox($fld);

                $fld = $frm->addCheckBox(Labels::getLabel("FRM_HEADER_MEGA_MENU", $langId), 'CONF_LAYOUT_MEGA_MENU', 1, array(), false, 0);
                HtmlHelper::configureSwitchForCheckbox($fld);

                $fld = $frm->addCheckBox(Labels::getLabel("FRM_HEADER_FULL_WIDTH", $langId), 'CONF_HEADER_FULL_WIDTH', 1, array(), false, 0);
                HtmlHelper::configureSwitchForCheckbox($fld);

                $fld = $frm->addCheckBox(Labels::getLabel("FRM_SINGLE_SELLER_CART", $langId), 'CONF_SINGLE_SELLER_CART', 1, array(), false, 0);
                HtmlHelper::configureSwitchForCheckbox($fld);

                $fld = $frm->addCheckBox(Labels::getLabel("FRM_WITHOUT_PRODUCT_VARIANTS", $langId), 'CONF_WITHOUT_PROD_VARIANTS', 1, array(), false, 0);
                $fld->htmlAfterField = '<span class="form-text text-muted">' . Labels::getLabel("FRM_IT_WILL_MERGE_PROD_ADD_FORM_&_REMOVE_PROD_VARIANTS", $langId) . '.</span><span class="form-text text-muted">' . Labels::getLabel('ERR_PLEASE_REMOVE_PRODUCTS_DATA_TO_CHANGE_THIS_SETTINGS', $this->siteLangId) . '.</span>';
                HtmlHelper::configureSwitchForCheckbox($fld);

                $fld = $frm->addHtmlEditor(Labels::getLabel('FRM_MAINTENANCE_TEXT', $this->siteLangId), 'CONF_MAINTENANCE_TEXT_' . $langId);
                $fld->developerTags['colWidthValues'] = [null, '12', null, null];
                $fld->requirements()->setRequired(true);

                break;
            case Configurations::FORM_PPC:
                $fld = $frm->addFloatField(Labels::getLabel('FRM_MINIMUM_WALLET_BALANCE', $langId), 'CONF_PPC_MIN_WALLET_BALANCE');
                $fld->requirements()->setPositive();
                $fld->htmlAfterField = '<span class="form-text text-muted">' . Labels::getLabel("MSG_MINIMUM_WALLET_BALANCE_TO_START_PROMOTION", $langId) . '</span>';

                $fld = $frm->addTextBox(Labels::getLabel('FRM_DAYS_INTERVAL_TO_CHARGE_WALLET', $langId), 'CONF_PPC_WALLET_CHARGE_DAYS_INTERVAL');
                $fld->htmlAfterField = '<span class="form-text text-muted">' . Labels::getLabel("MSG_DAYS_INTERVAL_TO_CHARGE_WALLET", $langId) . '</span>';

                $fld = $frm->addFloatField(Labels::getLabel('FRM_COST_PER_CLICK_(product)', $langId), 'CONF_CPC_PRODUCT');
                $fld->requirements()->setCompareWith('CONF_PPC_MIN_WALLET_BALANCE', 'lt');
                $fld->requirements()->setPositive();
                $fld->htmlAfterField = '<span class="form-text text-muted">' . Labels::getLabel("MSG_PPC_COST_PER_CLICK_FOR_PRODUCT", $langId) . '</span>';

                $fld = $frm->addFloatField(Labels::getLabel('FRM_COST_PER_CLICK_(shop)', $langId), 'CONF_CPC_SHOP');
                $fld->requirements()->setCompareWith('CONF_PPC_MIN_WALLET_BALANCE', 'lt');
                $fld->requirements()->setPositive();
                $fld->htmlAfterField = '<span class="form-text text-muted">' . Labels::getLabel("MSG_PPC_COST_PER_CLICK_FOR_SHOP", $langId) . '</span>';

                $fld = $frm->addFloatField(Labels::getLabel('FRM_COST_PER_CLICK_(slide)', $langId), 'CONF_CPC_SLIDES');
                $fld->requirements()->setCompareWith('CONF_PPC_MIN_WALLET_BALANCE', 'lt');
                $fld->requirements()->setPositive();
                $fld->htmlAfterField = '<span class="form-text text-muted">' . Labels::getLabel("MSG_PPC_COST_PER_CLICK_FOR_SLIDE", $langId) . '</span>';

                $fld = $frm->addSelectBox(Labels::getLabel("FRM_PPC_PRODUCTS_COUNT_HOME_PAGE", $langId), 'CONF_PPC_PRODUCTS_HOME_PAGE', Collections::sponsoredItemsHomePageCount(), '', array(), '');
                $fld->htmlAfterField = '<span class="form-text text-muted">' . Labels::getLabel("MSG_HOW_MANY_PPC_PRODUCTS_SHOWN_ON_HOME_PAGE", $langId) . '</span>';

                $fld = $frm->addTextBox(Labels::getLabel('FRM_PPC_SLIDES_COUNT_HOME_PAGE', $langId), 'CONF_PPC_SLIDES_HOME_PAGE');
                $fld->htmlAfterField = '<span class="form-text text-muted">' . Labels::getLabel("MSG_HOW_MANY_PPC_SLIDES_SHOWN_ON_HOME_PAGE", $langId) . '</span>';
                $fld = $frm->addTextBox(Labels::getLabel('FRM_PPC_CLICKS_COUNT_TIME_INTERVAL(Minutes)', $langId), 'CONF_PPC_CLICK_COUNT_TIME_INTERVAL');
                $fld->htmlAfterField = '<span class="form-text text-muted">' . Labels::getLabel("MSG_SET_TIME_INTERVAL_TO_CALCULATE_NO._of_click_from_one_user_for_each_promotion", $langId) . '</span>';

                break;
            case Configurations::FORM_MEDIA:
                $ratioArr = AttachedFile::getRatioTypeArray($langId);

                $fileTypeArr = [
                    AttachedFile::FILETYPE_ADMIN_LOGO,
                    AttachedFile::FILETYPE_FRONT_LOGO,
                    AttachedFile::FILETYPE_FAVICON,
                    AttachedFile::FILETYPE_SOCIAL_FEED_IMAGE,
                    AttachedFile::FILETYPE_PAYMENT_PAGE_LOGO,
                    AttachedFile::FILETYPE_WATERMARK_IMAGE,
                    AttachedFile::FILETYPE_APPLE_TOUCH_ICON,
                    AttachedFile::FILETYPE_MOBILE_LOGO,
                    AttachedFile::FILETYPE_FIRST_PURCHASE_DISCOUNT_IMAGE,
                    AttachedFile::FILETYPE_META_IMAGE,
                ];
                foreach ($fileTypeArr as $fileType) {
                    $this->getUploadMediaStructure($frm, $fileType, $langId, $ratioArr);
                }
                break;
            case Configurations::FORM_SHARING:
                $fld =  $frm->addHtml('', 'ShareAndEarn', '<h3 class="form-section-head">' . Labels::getLabel('FRM_SHARE_AND_EARN_SETTINGS', $langId) . '</h3>');
                $fld->developerTags['colWidthValues'] = [null, '12', null, null];

                $fld = $frm->addTextBox(Labels::getLabel("FRM_FACEBOOK_APP_ID", $langId), 'CONF_FACEBOOK_APP_ID');
                $fld->htmlAfterField = "<span class='form-text text-muted'>" . Labels::getLabel("FRM_THIS_IS_THE_APPLICATION_ID_USED_IN_POST.", $langId) . "</span>";

                $fld = $frm->addTextBox(Labels::getLabel("FRM_FACEBOOK_APP_SECRET", $langId), 'CONF_FACEBOOK_APP_SECRET');
                $fld->htmlAfterField = "<span class='form-text text-muted'>" . Labels::getLabel("FRM_THIS_IS_THE_FACEBOOK_SECRET_KEY_USED_FOR_AUTHENTICATION_AND_OTHER_FACEBOOK_RELATED_PLUGINS_SUPPORT.", $langId) . "</span>";

                $fld = $frm->addTextBox(Labels::getLabel('FRM_TWITTER_USERNAME', $langId), 'CONF_TWITTER_USERNAME');
                $fld->htmlAfterField = '<span class="form-text text-muted">' . Labels::getLabel("FRM_TWITTER_USERNAME_MSG", $langId) . '</span>';

                $fld = $frm->addTextBox(Labels::getLabel("FRM_TWITTER_API_KEY", $langId), 'CONF_TWITTER_API_KEY');
                $fld->htmlAfterField = "<span class='form-text text-muted'>" . Labels::getLabel("FRM_THIS_IS_THE_APPLICATION_ID_USED_IN_POST.", $langId) . "</span>";

                $fld = $frm->addTextBox(Labels::getLabel("FRM_TWITTER_API_SECRET", $langId), 'CONF_TWITTER_API_SECRET');
                $fld->htmlAfterField = "<span class='form-text text-muted'>" . Labels::getLabel("FRM_THIS_IS_THE_TWITTER_SECRET_KEY_USED_FOR_AUTHENTICATION_AND_OTHER_TWITTER_RELATED_PLUGINS_SUPPORT.", $langId) . "</span>";

                $fld = $frm->addTextarea(Labels::getLabel("FRM_TWITTER_POST_DESCRIPTION", $langId), 'CONF_SOCIAL_FEED_TWITTER_POST_TITLE' . $langId);
                $fld->htmlAfterField = "<span class='form-text text-muted'>" . Labels::getLabel("FRM_THIS_DESCRIPTION_SHARED_ON_TWITTER", $langId) . "</span>";
                $fld->developerTags['colWidthValues'] = [null, '12', null, null];
                break;
        }
        $frm->addHiddenField('', 'form_type', $type);
        $frm->addHiddenField('', 'lang_id', $langId);
        return $frm;
    }

    private function getUploadMediaStructure(Form $frm, int $fileType, int $langId, array $ratioArr)
    {
        $width = $height = '';
        $hasRatio = false;
        $imageArr = [];
        $fileData = AttachedFile::getAttachment($fileType, 0, 0, $langId);
        $uploadedTime = !empty($fileData['afile_updated_at']) ? AttachedFile::setTimeParam($fileData['afile_updated_at']) : '';

        if (AttachedFile::FILE_ATTACHMENT_TYPE_SVG == $fileData['afile_attachment_type']) {
           $imgUrl = UrlHelper::getStaticImageUrl($fileData['afile_physical_path']) . $uploadedTime;
        }

        $hasSvg = in_array($fileType, AttachedFile::getSvgFileType());

        switch ($fileType) {
            case AttachedFile::FILETYPE_ADMIN_LOGO:
                $title = Labels::getLabel("FRM_ADMIN_LOGO", $langId);
                $imageDisclaimer = Labels::getLabel("LBL_ADMIN_LOGO_IMAGE_DISCLAIMER", $langId);
                if (0 < $fileData['afile_id']) {
                    if (AttachedFile::FILE_ATTACHMENT_TYPE_OTHER == $fileData['afile_attachment_type']) {
                        $imgUrl = UrlHelper::getCachedUrl(UrlHelper::generateFullFileUrl('Image', 'siteAdminLogo', array($langId)) . $uploadedTime, CONF_IMG_CACHE_TIME, '.jpg');
                    }
                    $imageArr = ['name' =>  $fileData['afile_name'], 'url' => $imgUrl];
                }
                break;
            case AttachedFile::FILETYPE_FRONT_LOGO:
                $title = Labels::getLabel("FRM_DESKTOP_LOGO", $langId);
                $imageDisclaimer = Labels::getLabel("LBL_DESKTOP_LOGO_IMAGE_DISCLAIMER", $langId);
                if (0 < $fileData['afile_id']) {
                    if (AttachedFile::FILE_ATTACHMENT_TYPE_OTHER == $fileData['afile_attachment_type']) {
                        $imgUrl = UrlHelper::getCachedUrl(UrlHelper::generateFullFileUrl('Image', 'siteLogo', array($langId), CONF_WEBROOT_FRONT_URL) . $uploadedTime, CONF_IMG_CACHE_TIME, '.jpg');
                    }
                    $imageArr = ['name' =>  $fileData['afile_name'], 'url' => $imgUrl];
                }
                break;
            case AttachedFile::FILETYPE_FAVICON:
                $title = Labels::getLabel("FRM_WEBSITE_FAVICON", $langId);
                $imageDisclaimer = Labels::getLabel("LBL_WEBSITE_FAVICON_IMAGE_DISCLAIMER", $langId);
                if (0 < $fileData['afile_id']) {
                    $imgUrl = UrlHelper::getCachedUrl(UrlHelper::generateFullFileUrl('Image', 'favicon', array($langId), CONF_WEBROOT_FRONT_URL) . $uploadedTime, CONF_IMG_CACHE_TIME, '.jpg');
                    $imageArr = ['name' =>  $fileData['afile_name'], 'url' => $imgUrl];
                }
                $width = $height = 16;
                break;
            case AttachedFile::FILETYPE_SOCIAL_FEED_IMAGE:
                $title = Labels::getLabel("FRM_SOCIAL_FEED_IMAGE", $langId);
                $imageDisclaimer = Labels::getLabel('LBL_SOCIAL_FEED_IMAGE_DISCLAIMER', $langId);
                if (0 < $fileData['afile_id']) {
                    if (AttachedFile::FILE_ATTACHMENT_TYPE_OTHER == $fileData['afile_attachment_type']) {
                        $imgUrl = UrlHelper::getCachedUrl(UrlHelper::generateFullFileUrl('Image', 'socialFeed', array($langId, ImageDimension::VIEW_THUMB), CONF_WEBROOT_FRONT_URL) . $uploadedTime, CONF_IMG_CACHE_TIME, '.jpg');
                    }
                    $imageArr = ['name' =>  $fileData['afile_name'], 'url' => $imgUrl];
                }
                $width = 160;
                $height = 240;
                break;
            case AttachedFile::FILETYPE_PAYMENT_PAGE_LOGO:
                $title = Labels::getLabel("FRM_PAYMENT_PAGE_LOGO", $langId);
                $imageDisclaimer = Labels::getLabel("LBL_PAYMENT_PAGE_LOGO_IMAGE_DISCLAIMER", $langId);
                if (0 < $fileData['afile_id']) {
                    if (AttachedFile::FILE_ATTACHMENT_TYPE_OTHER == $fileData['afile_attachment_type']) {
                        $imgUrl = UrlHelper::getCachedUrl(UrlHelper::generateFullFileUrl('Image', 'paymentPageLogo', array($langId, ImageDimension::VIEW_THUMB), CONF_WEBROOT_FRONT_URL) . $uploadedTime, CONF_IMG_CACHE_TIME, '.jpg');
                    }
                    $imageArr = ['name' =>  $fileData['afile_name'], 'url' => $imgUrl];
                }
                break;
            case AttachedFile::FILETYPE_WATERMARK_IMAGE:
                $title = Labels::getLabel("FRM_WATERMARK_IMAGE", $langId);
                $imageDisclaimer = Labels::getLabel('LBL_WATERMARK_IMAGE_DISCLAIMER', $langId);
                if (0 < $fileData['afile_id']) {
                    $imgUrl = UrlHelper::getCachedUrl(UrlHelper::generateFullFileUrl('Image', 'watermarkImage', array($langId, ImageDimension::VIEW_THUMB), CONF_WEBROOT_FRONT_URL) . $uploadedTime, CONF_IMG_CACHE_TIME, '.jpg');
                    $imageArr = ['name' =>  $fileData['afile_name'], 'url' => $imgUrl];
                }
                $width = 120;
                $height = 90;
                break;
            case AttachedFile::FILETYPE_APPLE_TOUCH_ICON:
                $title = Labels::getLabel("FRM_APPLE_TOUCH_ICON", $langId);
                $imageDisclaimer = Labels::getLabel("LBL_APPLE_TOUCH_ICON_IMAGE_DISCLAIMER", $langId);
                if (0 < $fileData['afile_id']) {
                    $imgUrl = UrlHelper::getCachedUrl(UrlHelper::generateFullFileUrl('Image', 'appleTouchIcon', array($langId, ImageDimension::VIEW_THUMB), CONF_WEBROOT_FRONT_URL) . $uploadedTime, CONF_IMG_CACHE_TIME, '.jpg');
                    $imageArr = ['name' =>  $fileData['afile_name'], 'url' => $imgUrl];
                }
                $width = $height = 152;
                break;
            case AttachedFile::FILETYPE_MOBILE_LOGO:
                $title = Labels::getLabel("FRM_MOBILE_LOGO", $langId);
                $imageDisclaimer = Labels::getLabel('LBL_MOBILE_LOGO_IMAGE_DISCLAIMER', $langId);
                if (0 < $fileData['afile_id']) {
                    if (AttachedFile::FILE_ATTACHMENT_TYPE_OTHER == $fileData['afile_attachment_type']) {
                        $imgUrl = UrlHelper::getCachedUrl(UrlHelper::generateFullFileUrl('Image', 'mobileLogo', array($langId, ImageDimension::VIEW_THUMB), CONF_WEBROOT_FRONT_URL) . $uploadedTime, CONF_IMG_CACHE_TIME, '.jpg');
                    }
                    $imageArr = ['name' =>  $fileData['afile_name'], 'url' => $imgUrl];
                }
                $width = 168;
                $height = 37;
                break;
            case AttachedFile::FILETYPE_FIRST_PURCHASE_DISCOUNT_IMAGE:
                $title = Labels::getLabel("FRM_FIRST_PURCHASE_DISCOUNT_IMAGE", $langId);
                $imageDisclaimer = Labels::getLabel('LBL_FIRST_PURCHASE_DISCOUNT_IMAGE_DISCLAIMER', $langId);
                if (0 < $fileData['afile_id']) {
                    $imgUrl = UrlHelper::getCachedUrl(UrlHelper::generateFullFileUrl('Image', 'firstPurchaseCoupon', array($langId, ImageDimension::VIEW_THUMB), CONF_WEBROOT_FRONT_URL) . $uploadedTime, CONF_IMG_CACHE_TIME, '.jpg');
                    $imageArr = ['name' =>  $fileData['afile_name'], 'url' => $imgUrl];
                }
                $width = $height = 120;
                break;
            case AttachedFile::FILETYPE_META_IMAGE:
                $title = Labels::getLabel("FRM_META_IMAGE", $langId);
                $imageDisclaimer = Labels::getLabel("LBL_META_IMAGE_DISCLAIMER", $langId);
                if (0 < $fileData['afile_id']) {
                    if (AttachedFile::FILE_ATTACHMENT_TYPE_OTHER == $fileData['afile_attachment_type']) {
                        $imgUrl = UrlHelper::getCachedUrl(UrlHelper::generateFullFileUrl('Image', 'metaImage', array($langId, ImageDimension::VIEW_THUMB), CONF_WEBROOT_FRONT_URL) . $uploadedTime, CONF_IMG_CACHE_TIME, '.jpg');
                    }
                    $imageArr = ['name' =>  $fileData['afile_name'], 'url' => $imgUrl, 'ratio_type' => $fileData['afile_aspect_ratio']];
                }
                $width = $height = 150;
                $hasRatio = true;
                break;

            default:
                trigger_error(Labels::getLabel('ERR_INVALID_TYPE', $this->siteLangId), E_USER_ERROR);
                break;
        }

        $headingFld = $frm->addHtml('', 'main_heading', '<h6>' . $title . ' </h6>
                    <span class="form-text text-muted">
                        <strong> ' . Labels::getLabel("MSG_IMAGE_DISCLAIMER", $langId) . ':</strong> ' . $imageDisclaimer . '</span>');

        $ratioFld = $svgFld = '';
        if ($hasSvg) {
            $logoImageType = AttachedFile::getLogoImageTypeArr($langId);
            $fileSvgType = $fileData['afile_attachment_type'] ?? array_key_first($logoImageType);
            $svgFld = $frm->addRadioButtons(
                Labels::getLabel("FRM_FILE_TYPE", $langId),
                'afile_attachment_type_' . $fileType,
                $logoImageType,
                $fileSvgType,
                [
                    'class' => 'list-radio svgFileTypeSectionJs',
                    'data-logo-type' => $fileType
                ]
            );
            $svgFld->addFieldTagAttribute('class', 'svgFileTypeJs');
            HtmlHelper::configureSwitchForRadio($svgFld);
        }

        if ($hasRatio) {
            $selectedRadio = $imageArr['ratio_type'] ?? array_key_first($ratioArr);
            $frm->addRadioButtons(
                Labels::getLabel("FRM_ASPECT_RATIO", $langId),
                'ratio_type_' . $fileType,
                $ratioArr,
                $selectedRadio,
                [],
                ['class' => 'prefRatio-js']
            );

            $ratioFld = HtmlHelper::configureRadioAsButton($frm, 'ratio_type_' . $fileType);
            $ratioFld->setWrapperAttribute('class', 'prefRatioSectionJs' . $fileType);
            if ($fileData['afile_attachment_type']) {
                $ratioFld->setWrapperAttribute('style', 'display:none');
            }
        }

        $fileInputAttr = ['onChange' => 'popupImage(this)', 'data-file_type' => $fileType, 'accept' => 'image/*', 'data-name' => $title];
        if (0 < $width && 0 < $height) {
            $fileInputAttr['data-min_width'] = $width;
            $fileInputAttr['data-min_height'] = $height;
        }

        $fld = $frm->addHtml('', 'file_input', HtmlHelper::getfileInputHtml(
            $fileInputAttr,
            $langId,
            'removeMediaImage(' . $fileType . ',' . $langId . ')',
            'editDropZoneImages(this)',
            $imageArr,
            'mt-3 dropzoneContainerJs'
        ));

        if ($hasRatio && !empty($ratioFld)) {
            if (false == $hasSvg) {
                $ratioFld->attachField($fld);
            }
        }

        if ($hasSvg && !empty($svgFld)) {
            if (!empty($hasRatio)) {
                $headingFld->developerTags['colWidthValues'] = [null, '12', null, null];
                $fld->developerTags['colWidthValues'] = [null, '12', null, null];
            } else {
                $svgFld->attachField($fld);
            }
        }
    }

    public function testEmail()
    {
        $emailObj = new FatMailer($this->siteLangId, 'test_email');
        $emailObj->setTo(FatApp::getConfig('CONF_SITE_OWNER_EMAIL'));
        if (!$emailObj->send()) {
            LibHelper::exitWithError($emailObj->getError(), true);
        }
        FatUtility::dieJsonSuccess("Mail sent to - " . FatApp::getConfig('CONF_SITE_OWNER_EMAIL'));
    }

    public function displayDateTime()
    {
        $post = FatApp::getPostedData();
        $timeZone = $post['time_zone'];
        $dateTime = CommonHelper::currentDateTime(null, true, null, $timeZone);
        $this->set("dateTime", $dateTime);
        $this->_template->render(false, false, 'json-success.php');
    }

    public function updateVerificationFile()
    {
        $post = FatApp::getPostedData();
        if (empty($post)) {
            LibHelper::exitWithError(Labels::getLabel('ERR_INVALID_REQUEST_OR_FILE_NOT_SUPPORTED', $this->siteLangId), true);
        }
        $fileType = FatApp::getPostedData('fileType', FatUtility::VAR_STRING, '');
        if (!isset($_FILES['verification_file']['name'])) {
            LibHelper::exitWithError(Labels::getLabel('ERR_PLEASE_SELECT_A_FILE', $this->siteLangId), true);
        }

        $target_dir = CONF_UPLOADS_PATH;
        $file = $_FILES['verification_file']['name'];
        $temp_name = $_FILES['verification_file']['tmp_name'];
        $path = pathinfo($file);
        $ext = $path['extension'];
        if (!in_array(strtoupper($ext), ['XML', 'HTML'])) {
            LibHelper::exitWithError(Labels::getLabel('ERR_INVALID_REQUEST_OR_FILE_NOT_SUPPORTED', $this->siteLangId), true);
        }

        if ($fileType == 'bing') {
            /*If we change file name then we also need to update in htacces file */
            $path_filename = $target_dir . 'BingSiteAuth.xml';
        } else if ($fileType == 'google') {
            $path_filename = $target_dir . 'google-site-verification.html';
        }
        // Check if file already exists
        if (file_exists($path_filename)) {
            unlink($path_filename);
        }
        move_uploaded_file($temp_name, $path_filename);
        $this->set('msg', Labels::getLabel('MSG_FILE_UPLOADED_SUCCESSFULLY', $this->siteLangId));
        $this->_template->render(false, false, 'json-success.php');
    }

    public function deleteVerificationFile($fileType)
    {
        if ($fileType == '') {
            LibHelper::exitWithError($this->str_invalid_request, true);
        }
        $target_dir = CONF_UPLOADS_PATH;
        if ($fileType == 'bing') {
            $path_filename = $target_dir . 'BingSiteAuth.xml';
        } else {
            $path_filename = $target_dir . 'google-site-verification.html';
        }
        if (file_exists($path_filename)) {
            unlink($path_filename);
        }
        $this->set('msg', Labels::getLabel('MSG_FILE_DELETED_SUCCESSFULLY', $this->siteLangId));
        $this->_template->render(false, false, 'json-success.php');
    }

    public function getBreadcrumbNodes($action)
    {
        switch ($action) {
            case 'index':
                $pageData = PageLanguageData::getAttributesByKey($this->pageKey, $this->siteLangId);
                $pageTitle = $pageData['plang_title'] ?? LibHelper::getControllerName(true);

                $this->nodes = [
                    ['title' => Labels::getLabel('LBL_SETTINGS', $this->siteLangId), 'href' => UrlHelper::generateUrl('Settings')],
                    ['title' => $pageTitle]
                ];
                break;
            default:
                parent::getBreadcrumbNodes($action);
                break;
        }
        return $this->nodes;
    }
}
