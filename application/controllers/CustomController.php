<?php

class CustomController extends MyAppController
{
    public function contactUs()
    {
        $contactFrm = $this->contactUsForm();
        $contactFrm->addSecurityToken();
        $termsAndConditionsLinkHref = 'javascript:void(0)';
        $cPageSrch = ContentPage::getSearchObject($this->siteLangId);
        $cPageSrch->addCondition('cpage_id', '=', FatApp::getConfig('CONF_TERMS_AND_CONDITIONS_PAGE', FatUtility::VAR_INT, 0));
        $cPageSrch->doNotCalculateRecords();
        $cPageSrch->setPageSize(1);
        $cpage = FatApp::getDb()->fetch($cPageSrch->getResultSet());
        if (!empty($cpage) && is_array($cpage)) {
            $termsAndConditionsLinkHref = UrlHelper::generateUrl('Cms', 'view', array($cpage['cpage_id']));
        }

        $privacyPolicyLinkHref = 'javascript:void(0)';
        $cPageSrch = ContentPage::getSearchObject($this->siteLangId);
        $cPageSrch->addCondition('cpage_id', '=', FatApp::getConfig('CONF_PRIVACY_POLICY_PAGE', FatUtility::VAR_INT, 0));
        $cPageSrch->doNotCalculateRecords();
        $cPageSrch->setPageSize(1);
        $cpage = FatApp::getDb()->fetch($cPageSrch->getResultSet());
        if (!empty($cpage) && is_array($cpage)) {
            $privacyPolicyLinkHref = UrlHelper::generateUrl('Cms', 'view', array($cpage['cpage_id']));
        }

        $this->set('contactFrm', $contactFrm);
        $this->set('siteLangId', $this->siteLangId);
        $this->set('termsAndConditionsLinkHref', $termsAndConditionsLinkHref);
        $this->set('privacyPolicyLinkHref', $privacyPolicyLinkHref);
        $this->_template->render(true, true, 'custom/contact-us.php');
    }

    public function contactSubmit()
    {
        $frm = $this->contactUsForm();
        $post = $frm->getFormDataFromArray(FatApp::getPostedData(), [], !MOBILE_APP_API_CALL);

        if (false === $post) {
            $message = $frm->getValidationErrors();
            if (true === MOBILE_APP_API_CALL) {
                LibHelper::dieJsonError(current($message));
            }
            Message::addErrorMessage($message);
            FatApp::redirectUser(UrlHelper::generateUrl('Custom', 'ContactUs'));
        }

        if (!MOBILE_APP_API_CALL) {
            $frm->expireSecurityToken(FatApp::getPostedData());
        }


        if (false === MOBILE_APP_API_CALL && !CommonHelper::verifyCaptcha()) {
            $message = Labels::getLabel('ERR_THAT_CAPTCHA_WAS_INCORRECT', $this->siteLangId);
            if (true === MOBILE_APP_API_CALL) {
                FatUtility::dieJsonError($message);
            }
            Message::addErrorMessage($message);
            FatApp::redirectUser(UrlHelper::generateUrl('Custom', 'ContactUs'));
        }

        $email = explode(',', FatApp::getConfig("CONF_CONTACT_EMAIL"));
        foreach ($email as $emailId) {
            $emailId = trim($emailId);
            if (filter_var($emailId, FILTER_VALIDATE_EMAIL) === false) {
                continue;
            }

            $email = new EmailHandler();
            if (!$email->sendContactFormEmail($emailId, $this->siteLangId, $post)) {
                $message = Labels::getLabel('ERR_EMAIL_NOT_SENT_SERVER_ISSUE', $this->siteLangId);
                if (true === MOBILE_APP_API_CALL) {
                    FatUtility::dieJsonError($message);
                }
                Message::addErrorMessage($message);
            } else {
                Message::addMessage(Labels::getLabel('MSG_YOUR_MESSAGE_SENT_SUCCESSFULLY', $this->siteLangId));
            }

            if (true === MOBILE_APP_API_CALL) {
                $this->set('msg', Labels::getLabel('MSG_YOUR_MESSAGE_SENT_SUCCESSFULLY', $this->siteLangId));
                $this->_template->render();
            }

            FatApp::redirectUser(UrlHelper::generateUrl('Custom', 'ContactUs'));
        }
    }

    public function faq()
    {
        $cmsPagesToFaq = FatApp::getConfig('conf_cms_pages_to_faq_page', null, '');
        $cmsPagesToFaq = unserialize($cmsPagesToFaq);
        if (sizeof($cmsPagesToFaq) > 0 && is_array($cmsPagesToFaq)) {
            $contentPageSrch = ContentPage::getSearchObject($this->siteLangId);
            $contentPageSrch->addCondition('cpage_id', 'in', $cmsPagesToFaq);
            $contentPageSrch->addMultipleFields(array('cpage_id', 'cpage_identifier', 'cpage_title'));
            $contentPageSrch->doNotCalculateRecords();
            $rs = $contentPageSrch->getResultSet();
            $cpages = FatApp::getDb()->fetchAll($rs);
            $this->set('cpages', $cpages);
        }

        $srch = FaqCategory::getSearchObject($this->siteLangId);
        $srch->joinTable('tbl_faqs', 'LEFT OUTER JOIN', 'faq_faqcat_id = faqcat_id and faq_active = ' . applicationConstants::ACTIVE . '  and faq_deleted = ' . applicationConstants::NO);
        $srch->joinTable('tbl_faqs_lang', 'LEFT OUTER JOIN', 'faqlang_faq_id = faq_id');
        $srch->addCondition('faqlang_lang_id', '=', $this->siteLangId);
        $srch->addCondition('faqcat_active', '=', applicationConstants::ACTIVE);
        $srch->addCondition('faqcat_type', '=', FaqCategory::FAQ_PAGE);
        $srch->doNotLimitRecords();
        $rs = $srch->getResultSet();

        $this->set('recordCount', $srch->recordCount());
        $this->set('siteLangId', $this->siteLangId);
        $this->set('frm', $this->getSearchFaqForm());
        $this->_template->render();
    }

    public function faqDetail($catId, $faqId = 0)
    {
        $cmsPagesToFaq = FatApp::getConfig('conf_cms_pages_to_faq_page');
        $cmsPagesToFaq = unserialize($cmsPagesToFaq);
        if (sizeof($cmsPagesToFaq) > 0 && is_array($cmsPagesToFaq)) {
            $contentPageSrch = ContentPage::getSearchObject($this->siteLangId);
            $contentPageSrch->addCondition('cpage_id', 'in', $cmsPagesToFaq);
            $contentPageSrch->addMultipleFields(array('cpage_id', 'cpage_identifier', 'cpage_title'));
            $contentPageSrch->doNotCalculateRecords();
            $cpages = FatApp::getDb()->fetchAll($contentPageSrch->getResultSet());
            $this->set('cpages', $cpages);
        }
        $this->set('siteLangId', $this->siteLangId);
        $this->set('faqCatId', $catId);
        $this->set('faqId', $faqId);
        $this->set('frm', $this->getSearchFaqForm());
        $this->_template->render();
    }

    public function searchFaqsDetail($catId = 0, $faqId = 0)
    {
        $srch = FaqCategory::getSearchObject($this->siteLangId);
        $srch->joinTable('tbl_faqs', 'LEFT OUTER JOIN', 'faq_faqcat_id = faqcat_id AND faq_active = ' . applicationConstants::ACTIVE . '  AND faq_deleted = ' . applicationConstants::NO);
        $srch->joinTable('tbl_faqs_lang', 'LEFT OUTER JOIN', 'faqlang_faq_id = faq_id');
        $srch->addCondition('faqlang_lang_id', '=', $this->siteLangId);
        $srch->addCondition('faqcat_active', '=', applicationConstants::ACTIVE);
        $srch->addCondition('faqcat_type', '=', FaqCategory::FAQ_PAGE);
        if ($catId > 0) {
            $srch->addCondition('faqcat_id', '=', $catId);
        }

        if ($faqId > 0) {
            $srch->addCondition('faq_id', '=', $faqId);
        }

        $srch->setPageSize(1);

        $question = FatApp::getPostedData('question', FatUtility::VAR_STRING, '');
        if (!empty($question)) {
            $srchCondition = $srch->addCondition('faq_title', 'like', "%$question%");
            $srch->doNotLimitRecords();
        }
        $srch->addOrder('faqcat_display_order', 'asc');
        $srch->addOrder('faq_faqcat_id', 'asc');
        $srch->addOrder('faq_display_order', 'asc');

        $rs = $srch->getResultSet();
        $records = FatApp::getDb()->fetchAll($rs);

        $json['recordCount'] = $srch->recordCount();

        if (isset($srchCondition)) {
            $srchCondition->remove();
        }
        $this->set('siteLangId', $this->siteLangId);
        $this->set('list', $records);

        $json['html'] = $this->_template->render(false, false, '_partial/no-record-found.php', true, false);
        if (!empty($records)) {
            $json['html'] = $this->_template->render(false, false, 'custom/search-faqs-detail.php', true, false);
        }

        FatUtility::dieJsonSuccess($json);
    }

    public function searchFaqs($page = 'faq', $catId = 0)
    {
        if ($page == 'faq') {
            $faqPage = FaqCategory::FAQ_PAGE;
            $faqMainCat = FatApp::getConfig("CONF_FAQ_PAGE_MAIN_CATEGORY", null, '');
        } else {
            $faqPage = FaqCategory::SELLER_PAGE;
            $faqMainCat = FatApp::getConfig("CONF_SELLER_PAGE_MAIN_CATEGORY", null, '');
        }

        if (!empty($catId) && $catId > 0) {
            $faqCatId = array($catId);
        } elseif ($faqMainCat) {
            $faqCatId = array($faqMainCat);
        } else {
            $srchFAQCat = FaqCategory::getSearchObject($this->siteLangId);
            $srchFAQCat->setPageSize(1);
            $srchFAQCat->addFld('faqcat_id');
            $srchFAQCat->addCondition('faqcat_active', '=', applicationConstants::ACTIVE);
            $srchFAQCat->addCondition('faqcat_type', '=', $faqPage);
            $srchFAQCat->doNotCalculateRecords();
            $rs = $srchFAQCat->getResultSet();
            $faqCatId = FatApp::getDb()->fetch($rs, 'faqcat_id');
        }

        $srch = FaqCategory::getSearchObject($this->siteLangId);
        $srch->joinTable('tbl_faqs', 'LEFT OUTER JOIN', 'faq_faqcat_id = faqcat_id and faq_active = ' . applicationConstants::ACTIVE . '  and faq_deleted = ' . applicationConstants::NO);
        $srch->joinTable('tbl_faqs_lang', 'LEFT OUTER JOIN', 'faqlang_faq_id = faq_id');
        $srch->addCondition('faqlang_lang_id', '=', $this->siteLangId);
        $srch->addCondition('faqcat_active', '=', applicationConstants::ACTIVE);
        $srch->addCondition('faqcat_deleted', '=', applicationConstants::NO);
        $srch->addCondition('faqcat_type', '=', $faqPage);
        if ($faqCatId) {
            $srch->addCondition('faqcat_id', 'IN', $faqCatId);
        }

        $question = FatApp::getPostedData('question', FatUtility::VAR_STRING, '');
        if (!empty($question)) {
            $srchCondition = $srch->addCondition('faq_identifier', 'like', "%$question%");
            $srchCondition->attachCondition('faq_title', 'LIKE', '%' . $question . '%', 'OR');
            $srchCondition->attachCondition('faq_content', 'LIKE', '%' . $question . '%', 'OR');
            $srchCondition->attachCondition('faqcat_name', 'LIKE', '%' . $question . '%', 'OR');
            $srchCondition->attachCondition('faqcat_identifier', 'LIKE', '%' . $question . '%', 'OR');
            $srch->doNotLimitRecords();
        }

        $srch->addOrder('faqcat_display_order', 'asc');
        $srch->addOrder('faq_faqcat_id', 'asc');
        $srch->addOrder('faq_display_order', 'asc');

        $rs = $srch->getResultSet();
        $records = FatApp::getDb()->fetchAll($rs);

        $json['recordCount'] = $srch->recordCount();


        if (isset($srchCondition)) {
            $srchCondition->remove();
        }

        $this->set('siteLangId', $this->siteLangId);
        $this->set('faqCatIdArr', $faqCatId);
        $this->set('list', $records);

        if (true === MOBILE_APP_API_CALL) {
            $this->_template->render();
        }

        $json['html'] = $this->_template->render(false, false, '_partial/no-record-found.php', true, false);
        if (!empty($records)) {
            $json['html'] = $this->_template->render(false, false, 'custom/search-faqs.php', true, false);
        }
        FatUtility::dieJsonSuccess($json);
    }

    public function searchFaqsListing($type = FaqCategory::FAQ_PAGE)
    {
        $question = FatApp::getPostedData('question', FatUtility::VAR_STRING, '');
        if (empty($question)) {
            LibHelper::exitWithError(Labels::getLabel('ERR_INVALID_SEARCH_STRING'));
        }

        $srch = FaqCategory::getSearchObject($this->siteLangId);
        $srch->joinTable('tbl_faqs', 'LEFT OUTER JOIN', 'faq_faqcat_id = faqcat_id and faq_active = ' . applicationConstants::ACTIVE . '  and faq_deleted = ' . applicationConstants::NO);
        $srch->joinTable('tbl_faqs_lang', 'LEFT OUTER JOIN', 'faqlang_faq_id = faq_id');
        $srch->addCondition('faqlang_lang_id', '=', $this->siteLangId);
        $srch->addCondition('faqcat_active', '=', applicationConstants::ACTIVE);
        $srch->addCondition('faqcat_type', '=', $type);

        $cnd = $srch->addCondition('faq_identifier', 'like', "%$question%");
        $cnd->attachCondition('faq_title', 'LIKE', '%' . $question . '%', 'OR');
        $cnd->attachCondition('faq_content', 'LIKE', '%' . $question . '%', 'OR');
        $cnd->attachCondition('faqcat_name', 'LIKE', '%' . $question . '%', 'OR');
        $cnd->attachCondition('faqcat_identifier', 'LIKE', '%' . $question . '%', 'OR');

        $srch->addOrder('faqcat_display_order', 'asc');
        $srch->addOrder('faq_faqcat_id', 'asc');
        $srch->addOrder('faq_display_order', 'asc');
        $srch->doNotLimitRecords();
        $srch->doNotCalculateRecords();
        $result = FatApp::getDb()->fetchAll($srch->getResultSet());

        $this->set('result', $result);
        $this->set('page', $type == FaqCategory::SELLER_PAGE ? 'seller' : 'faq');
        $this->set('type', $type);
        $this->set('html', $this->_template->render(false, false, NULL, true));
        $this->_template->render(false, false, 'json-success.php', true, false);
    }

    public function faqCategoriesPanel()
    {
        $srch = FaqCategory::getSearchObject($this->siteLangId);
        $srch->joinTable('tbl_faqs', 'LEFT OUTER JOIN', 'faq_faqcat_id = faqcat_id and faq_active = ' . applicationConstants::ACTIVE . '  and faq_deleted = ' . applicationConstants::NO);
        $srch->joinTable('tbl_faqs_lang', 'LEFT OUTER JOIN', 'faqlang_faq_id = faq_id');
        $srch->addCondition('faqlang_lang_id', '=', $this->siteLangId);
        $srch->addCondition('faqcat_active', '=', applicationConstants::ACTIVE);
        $srch->addCondition('faqcat_type', '=', FaqCategory::FAQ_PAGE);
        $question = FatApp::getPostedData('question', FatUtility::VAR_STRING, '');
        if (!empty($question)) {
            $srchCondition = $srch->addCondition('faq_identifier', 'like', "%$question%");
            $srchCondition->attachCondition('faq_title', 'LIKE', '%' . $question . '%', 'OR');
        }
        $srch->addOrder('faqcat_display_order', 'asc');
        $srch->addOrder('faq_faqcat_id', 'asc');
        $srch->addOrder('faq_display_order', 'asc');
        $srch->doNotLimitRecords();
        $records = FatApp::getDb()->fetchAll($srch->getResultSet());

        $json['recordCount'] = $srch->recordCount();

        $srch->addGroupBy('faqcat_id');
        $srch->addMultipleFields(array('IFNULL(faqcat_name, faqcat_identifier) as faqcat_name', 'faqcat_id'));
        $srch->addFld('COUNT(1) AS faq_count');
        if (isset($srchCondition)) {
            $srchCondition->remove();
        }
        $srch->doNotCalculateRecords();
        $rsCat = $srch->getResultSet();
        $recordsCategories = FatApp::getDb()->fetchAll($rsCat);
        $faqMainCat = FatApp::getConfig("CONF_FAQ_PAGE_MAIN_CATEGORY", null, '');

        $this->set('siteLangId', $this->siteLangId);
        $this->set('list', $records);
        $this->set('listCategories', $recordsCategories);
        $this->set('faqMainCat', $faqMainCat);
        $this->set('page', 'faq');

        if (true === MOBILE_APP_API_CALL) {
            $this->_template->render();
        }

        $json['html'] = $this->_template->render(false, false, '_partial/no-record-found.php', true, false);
        if (!empty($records)) {
            $json['html'] = $this->_template->render(false, false, 'custom/search-faqs.php', true, false);
        }
        $json['categoriesPanelHtml'] = $this->_template->render(false, false, 'custom/faq-categories-panel.php', true, false);
        FatUtility::dieJsonSuccess($json);
    }

    public function faqQuestionsPanel($catId = 0)
    {
        $searchFrm = $this->getSearchFaqForm();
        $post = $searchFrm->getFormDataFromArray(FatApp::getPostedData());
        $srch = FaqCategory::getSearchObject($this->siteLangId);
        $srch->joinTable('tbl_faqs', 'LEFT OUTER JOIN', 'faq_faqcat_id = faqcat_id and faq_active = ' . applicationConstants::ACTIVE . '  and faq_deleted = ' . applicationConstants::NO);
        $srch->joinTable('tbl_faqs_lang', 'LEFT OUTER JOIN', 'faqlang_faq_id = faq_id');
        $srch->addCondition('faqlang_lang_id', '=', $this->siteLangId);
        $srch->addCondition('faqcat_active', '=', applicationConstants::ACTIVE);
        $srch->addCondition('faqcat_type', '=', FaqCategory::FAQ_PAGE);
        $srch->addCondition('faqcat_id', '=', $catId);
        $srch->addOrder('faqcat_display_order', 'ASC');
        $srch->addOrder('faq_faqcat_id', 'ASC');
        $srch->addOrder('faq_display_order', 'ASC');
        $srch->addMultipleFields(array('faq_title', 'faqcat_id', 'faq_id'));
        $srch->doNotLimitRecords();
        $rs = $srch->getResultSet();
        $records = FatApp::getDb()->fetchAll($rs);

        $json['recordCount'] = $srch->recordCount();
        $this->set('siteLangId', $this->siteLangId);
        $this->set('listCategories', $records);
        $json['html'] = $this->_template->render(false, false, '_partial/no-record-found.php', true, false);
        if (!empty($records)) {
            $json['html'] = $this->_template->render(false, false, 'custom/search-faqs.php', true, false);
        }
        $json['categoriesPanelHtml'] = $this->_template->render(false, false, 'custom/faq-questions-panel.php', true, false);
        FatUtility::dieJsonSuccess($json);
    }

    public function getBreadcrumbNodes($action)
    {
        $nodes = array();
        $parameters = FatApp::getParameters();

        switch ($action) {

            case 'faqDetail':

                $srch = FaqCategory::getSearchObject($this->siteLangId);
                $srch->addCondition('faqcat_active', '=', applicationConstants::ACTIVE);
                $srch->addCondition('faqcat_type', '=', FaqCategory::FAQ_PAGE);
                $srch->addCondition('faqcat_id', '=', $parameters[0]);
                $srch->setPageSize(1);
                $srch->doNotCalculateRecords();
                $records = FatApp::getDb()->fetch($srch->getResultSet());

                $nodes[] = array('title' => Labels::getLabel('MSG_FAQ', $this->siteLangId), 'href' => UrlHelper::generateUrl('custom', 'Faq'));
                if (!empty($records)) {
                    $nodes[] = array('title' => $records['faqcat_name'] ?? '');
                }

                break;

            case 'faq':
                $nodes[] = array('title' => Labels::getLabel('MSG_FAQ', $this->siteLangId), 'href' => UrlHelper::generateUrl('custom', 'Faq'));
                break;

            default:
                $nodes[] = array('title' => FatUtility::camel2dashed($action));
                break;
        }
        return $nodes;
    }

    public function paymentFailed()
    {
        $textMessage = sprintf(Labels::getLabel('MSG_CUSTOMER_FAILURE_ORDER', $this->siteLangId), UrlHelper::generateUrl('custom', 'contactUs'));
        $this->set('textMessage', $textMessage);
        if (!FatApp::getConfig('CONF_MAINTAIN_CART_ON_PAYMENT_FAILURE', FatUtility::VAR_INT, applicationConstants::NO) && isset($_SESSION['cart_order_id']) && $_SESSION['cart_order_id'] != '') {
            $cartOrderId = $_SESSION['cart_order_id'];
            $orderObj = new Orders();
            $orderDetail = $orderObj->getOrderById($cartOrderId);

            $cartInfo = json_decode($orderDetail['order_cart_data'], true);
            unset($cartInfo['shopping_cart']);

            $db = FatApp::getDb();
            if (!$db->deleteRecords('tbl_user_cart', array('smt' => '`usercart_user_id`=? and `usercart_type`=?', 'vals' => array(UserAuthentication::getLoggedUserId(), CART::TYPE_PRODUCT)))) {
                Message::addErrorMessage($db->getError());
                FatApp::redirectUser(UrlHelper::generateFullUrl('Checkout'));
            }
            /* $cartObj = new Cart();
            foreach ($cartInfo as $key => $quantity) {
                $keyDecoded = json_decode(base64_decode($key), true);

                $selprod_id = 0;


                if (strpos($keyDecoded, Cart::CART_KEY_PREFIX_PRODUCT) !== false) {
                    $selprod_id = FatUtility::int(str_replace(Cart::CART_KEY_PREFIX_PRODUCT, '', $keyDecoded));
                }
                $cartObj->add($selprod_id, $quantity);
            }
            $cartObj->updateUserCart(); */
        }
        if (CommonHelper::isAppUser()) {
            $this->set('exculdeMainHeaderDiv', true);
            $this->_template->render(false, false);
        } else {
            $this->_template->render();
        }
    }

    public function paymentCancel()
    {
        /* echo FatApp::getConfig('CONF_MAINTAIN_CART_ON_PAYMENT_CANCEL',FatUtility::VAR_INT,applicationConstants::NO);
        echo $_SESSION['cart_order_id']; */

        if (!FatApp::getConfig('CONF_MAINTAIN_CART_ON_PAYMENT_CANCEL', FatUtility::VAR_INT, applicationConstants::NO) && isset($_SESSION['cart_order_id']) && $_SESSION['cart_order_id'] != '') {
            $cartOrderId = $_SESSION['cart_order_id'];
            $orderObj = new Orders();
            $orderDetail = $orderObj->getOrderById($cartOrderId);

            $cartInfo = json_decode($orderDetail['order_cart_data'], true);
            unset($cartInfo['shopping_cart']);
            $db = FatApp::getDb();
            if (!$db->deleteRecords('tbl_user_cart', array('smt' => '`usercart_user_id`=? and `usercart_type`=?', 'vals' => array(UserAuthentication::getLoggedUserId(), CART::TYPE_PRODUCT)))) {
                Message::addErrorMessage($db->getError());
                FatApp::redirectUser(UrlHelper::generateFullUrl('Checkout'));
            }

            /* $cartObj = new Cart();
            foreach ($cartInfo as $key => $quantity) {
                $keyDecoded = json_decode(base64_decode($key), true);

                $selprod_id = 0;


                if (strpos($keyDecoded, Cart::CART_KEY_PREFIX_PRODUCT) !== false) {
                    $selprod_id = FatUtility::int(str_replace(Cart::CART_KEY_PREFIX_PRODUCT, '', $keyDecoded));
                }
                $cartObj->add($selprod_id, $quantity);
            }
            $cartObj->updateUserCart(); */
        }
        if (isset($_SESSION['order_type']) && $_SESSION['order_type'] == Orders::ORDER_GIFT_CARD) {
            FatApp::redirectUser(UrlHelper::generateFullUrl('buyer', 'giftCards', [], CONF_WEBROOT_DASHBOARD, null, false, false, false));
        }
        if (isset($_SESSION['order_type']) && $_SESSION['order_type'] == Orders::ORDER_SUBSCRIPTION) {
            FatApp::redirectUser(UrlHelper::generateFullUrl('SubscriptionCheckout', '', [], CONF_WEBROOT_DASHBOARD, null, false, false, false));
        }

        FatApp::redirectUser(UrlHelper::generateFullUrl('Checkout'));
    }

    public function paymentSuccess($orderNo)
    {
        if (empty($orderNo)) {
            FatUtility::exitWithErrorCode(404);
        }

        $orderInfo = Orders::getOrderByOrderNo($orderNo, $this->siteLangId);
        if (empty($orderInfo)) {
            FatUtility::exitWithErrorCode(404);
        }

        $showOrderDetails = UserAuthentication::isGuestUserLogged()  || UserAuthentication::isUserLogged();

        $user_id = User::getUserParentId(UserAuthentication::getLoggedUserId(true));
        $orderId = $orderInfo['order_id'];

        $user = [];
        if ($orderInfo['order_user_id'] > 0) {
            if (0 < UserAuthentication::getLoggedUserId(true) && $orderInfo['order_user_id'] != $user_id) {
                $message = Labels::getLabel("ERR_INVALID_ORDER", $this->siteLangId);
                if (true === MOBILE_APP_API_CALL) {
                    LibHelper::dieJsonError($message);
                }
                Message::addErrorMessage($message);
                FatApp::redirectUser(UrlHelper::generateUrl());
            }

            $orderProdData = OrderProduct::getOpArrByOrderId($orderId);
            foreach ($orderProdData as $data) {
                $amount = $data['op_unit_price'] * $data['op_qty'];
                AbandonedCart::saveAbandonedCart($orderInfo['order_user_id'], $data['op_selprod_id'], $data['op_qty'], AbandonedCart::ACTION_PURCHASED, $amount);
            }

            $userObj = new User($orderInfo['order_user_id']);
            $srch = $userObj->getUserSearchObj(['credential_email']);
            $srch->doNotCalculateRecords();
            $srch->setPageSize(1);
            $rs = $srch->getResultSet();
            if (!$rs) {
                if (true === MOBILE_APP_API_CALL) {
                    LibHelper::dieJsonError($srch->getError());
                }
                FatUtility::exitWithErrorCode(404);
            }
            $user = FatApp::getDb()->fetch($rs);
            if ($orderInfo['order_type'] == Orders::ORDER_SUBSCRIPTION) {
                $cartObj = new SubscriptionCart($orderInfo['order_user_id'], $this->siteLangId);
                $cartObj->clear();
                $cartObj->updateUserSubscriptionCart();
            } else {
                $cartObj = new Cart($orderInfo['order_user_id'], $this->siteLangId, $this->app_user['temp_user_id']);
                $cartObj->clear();
                $cartObj->updateUserCart();
            }
        }

        if ($orderInfo['order_type'] == Orders::ORDER_PRODUCT) {
            if (!empty($user)) {
                $searchReplaceArray = array(
                    '{BUYER-EMAIL}' => '<strong>' . $user['credential_email'] . '</strong>',
                );
                $textMessage = Labels::getLabel('MSG_CUSTOMER_SUCCESS_ORDER_{BUYER-EMAIL}', $this->siteLangId);
                $textMessage = CommonHelper::replaceStringData($textMessage, $searchReplaceArray);
            } else {
                $textMessage = Labels::getLabel('MSG_CUSTOMER_SUCCESS_ORDER', $this->siteLangId);
            }

            if (true === $showOrderDetails) {
                $srch = new OrderProductSearch($this->siteLangId);
                $srch->joinShippingCharges();
                $srch->joinAddress();
                $srch->addCondition('op_order_id', '=', $orderId);
                $srch->doNotCalculateRecords();
                $srch->doNotLimitRecords();

                $srch->addMultipleFields(
                    array('ops.*', 'op_product_type', 'op_invoice_number', 'addr.*', 'ts.*', 'tc.*', 'COALESCE(state_name, state_identifier) as state_name', 'COALESCE(country_name, country_code) as country_name')
                );
                $srch->addGroupBy('opshipping_pickup_addr_id');
                $this->set('orderFulFillmentTypeArr', FatApp::getDb()->fetchAll($srch->getResultSet()));
            }
        } elseif ($orderInfo['order_type'] == Orders::ORDER_SUBSCRIPTION) {
            $searchReplaceArray = array(
                '{account}' => '<a href="' . UrlHelper::generateUrl('seller', '', [], CONF_WEBROOT_DASHBOARD, null, false, false, false) . '" class="link">' . Labels::getLabel('MSG_My_Account', $this->siteLangId) . '</a>',
                '{subscription}' => '<a href="' . UrlHelper::generateUrl('seller', 'subscriptions', [], CONF_WEBROOT_DASHBOARD, null, false, false, false) . '" class="link">' . Labels::getLabel('MSG_MY_SUBSCRIPTION', $this->siteLangId) . '</a>',
            );
            $textMessage = Labels::getLabel('MSG_SUBSCRIPTION_SUCCESS_ORDER_{account}_{subscription}', $this->siteLangId);
            $textMessage = str_replace(array_keys($searchReplaceArray), array_values($searchReplaceArray), $textMessage);
        } elseif ($orderInfo['order_type'] == Orders::ORDER_WALLET_RECHARGE) {
            $searchReplaceArray = array(
                '{account}' => '<a href="' . UrlHelper::generateUrl('account', '', [], CONF_WEBROOT_DASHBOARD, null, false, false, false) . '" class="link">' . Labels::getLabel('MSG_MY_ACCOUNT', $this->siteLangId) . '</a>',
                '{credits}' => '<a href="' . UrlHelper::generateUrl('account', 'credits', [], CONF_WEBROOT_DASHBOARD, null, false, false, false) . '" class="link">' . Labels::getLabel('MSG_MY_CREDITS', $this->siteLangId) . '</a>',
            );
            $textMessage = Labels::getLabel('MSG_WALLET_SUCCESS_ORDER_{account}_{credits}', $this->siteLangId);
            $textMessage = str_replace(array_keys($searchReplaceArray), array_values($searchReplaceArray), $textMessage);
        } elseif ($orderInfo['order_type'] == Orders::ORDER_GIFT_CARD) {

            $searchReplaceArray = array(
                '{account}' => '<a href="' . UrlHelper::generateUrl('account', '', [], CONF_WEBROOT_DASHBOARD, null, false, false, false) . '" class="link">' . Labels::getLabel('MSG_MY_ACCOUNT', $this->siteLangId) . '</a>',
                '{credits}' => '<a href="' . UrlHelper::generateUrl('buyer', 'giftCards', [], CONF_WEBROOT_DASHBOARD, null, false, false, false) . '" class="link">' . Labels::getLabel('MSG_MY_GIFT_CARDS', $this->siteLangId) . '</a>',
            );
            $textMessage = Labels::getLabel('MSG_GIFT_CARDS_SUCCESS_ORDER_{account}_{credits}', $this->siteLangId);
            $textMessage = str_replace(array_keys($searchReplaceArray), array_values($searchReplaceArray), $textMessage);
        } else {
            $message = Labels::getLabel('ERR_INVALID_ORDER_TYPE', $this->siteLangId);
            if (true === MOBILE_APP_API_CALL) {
                LibHelper::dieJsonError($message);
            }
            FatUtility::exitWithErrorCode(404);
        }

        if (!UserAuthentication::isUserLogged() && !UserAuthentication::isGuestUserLogged()) {
            $textMessage = str_replace('{contactus}', '<a href="' . UrlHelper::generateUrl('custom', 'contactUs') . '" class="link">' . Labels::getLabel('MSG_Store_Owner', $this->siteLangId) . '</a>', Labels::getLabel('MSG_GUEST_SUCCESS_ORDER_{contactus}', $this->siteLangId));
        }

        $orderObj = new Orders();
        if (true === $showOrderDetails) {
            $address = $orderObj->getOrderAddresses($orderInfo['order_id']);
            if (!empty($address)) {
                $orderInfo['billingAddress'] = $address[Orders::BILLING_ADDRESS_TYPE];
                $orderInfo['shippingAddress'] = (!empty($address[Orders::SHIPPING_ADDRESS_TYPE]) ? $address[Orders::SHIPPING_ADDRESS_TYPE] : []);
            }
        }
        $orderInfo['orderProducts'] = $orderObj->getChildOrders(['order_id' => $orderInfo['order_id']], $orderInfo['order_type'], $orderInfo['order_language_id'], true);

        if (UserAuthentication::isGuestUserLogged()) {
            // unset($_SESSION[UserAuthentication::SESSION_ELEMENT_NAME]);
        }

        if (UserAuthentication::isUserLogged()) {
            unset($_SESSION['offer_checkout']);
            $_SESSION[UserAuthentication::SESSION_ELEMENT_NAME]['acceptedOffers'] = RfqOffers::getAllAcceptedOffers(UserAuthentication::getLoggedUserId(true));
        }

        $this->set('orderId', $orderId);
        $this->set('textMessage', $textMessage);
        $this->set('orderInfo', $orderInfo);
        $this->set('showOrderDetails', $showOrderDetails);

        if (CommonHelper::isAppUser() && false ===  MOBILE_APP_API_CALL) {
            $this->set('exculdeMainHeaderDiv', true);
            $this->_template->render(false, false);
        } else {
            $this->_template->render();
        }
    }

    public function referral($userReferralCode, $sharingUrl)
    {
        //echo 'Issue Pending, i.e if Sharing Url of structure like this: products/view/8, then it is not handeled, so need to add fix of URL.';
        //echo $sharingUrl; die();

        if (!FatApp::getConfig("CONF_ENABLE_REFERRER_MODULE")) {
            Message::addErrorMessage(Labels::getLabel("ERR_REFFERAL_MODULE_NO_LONGER_ACTIVE", $this->siteLangId));
            FatApp::redirectUser(UrlHelper::generateUrl());
        }
        $userSrchObj = User::getSearchObject();
        $userSrchObj->doNotCalculateRecords();
        $userSrchObj->setPageSize(1);
        $userSrchObj->addCondition('user_referral_code', '=', $userReferralCode);
        $userSrchObj->addMultipleFields(array('user_id', 'user_referral_code'));
        $rs = $userSrchObj->getResultSet();
        $row = FatApp::getDb()->fetch($rs);

        if (!$row || $userReferralCode == '' || $row['user_referral_code'] != $userReferralCode || $sharingUrl == '') {
            Message::addErrorMessage(Labels::getLabel("ERR_INVALID_REFERRAL_CODE", $this->siteLangId));
        }

        /* NOT HANDLED:, if user entered referral url with referral code and any abc string, then still that computer system will save the referral code and upon signing up will credit points to referral user as per the logic implemented in application. */

        $cookieExpiryDays = FatApp::getConfig("CONF_REFERRER_URL_VALIDITY", FatUtility::VAR_INT, 10);

        $cookieValue = array('data' => $row['user_referral_code'], 'creation_time' => time());
        $cookieValue = serialize($cookieValue);

        CommonHelper::setCookie('referrer_code_signup', $cookieValue, time() + 3600 * 24 * $cookieExpiryDays);
        CommonHelper::setCookie('referrer_code_checkout', $row['user_referral_code'], time() + 3600 * 24 * $cookieExpiryDays);

        /* setcookie( 'referrer_code_signup', $row['user_referral_code'], time()+3600*24*$cookieExpiryDays, CONF_WEBROOT_URL, '', false, true );
        setcookie( 'referrer_code_checkout', $row['user_referral_code'], time()+3600*24*$cookieExpiryDays, CONF_WEBROOT_URL, '', false, true ); */
        FatApp::redirectUser('/' . $sharingUrl);
    }

    private function getSearchFaqForm()
    {
        $frm = new Form('frmSearchFaqs');
        $frm->addTextbox(Labels::getLabel('FRM_ENTER_YOUR_QUESTION', $this->siteLangId), 'question');
        $frm->addSubmitButton('', 'btn_submit', '');
        return $frm;
    }

    private function contactUsForm()
    {
        $frm = new Form('frmContact');
        $frm->addRequiredField(Labels::getLabel('FRM_YOUR_NAME', $this->siteLangId), 'name', '');
        $frm->addEmailField(Labels::getLabel('FRM_YOUR_EMAIL', $this->siteLangId), 'email', '');

        $frm->addHiddenField('', 'phone_dcode');
        $fld_phn = $frm->addRequiredField(Labels::getLabel('FRM_YOUR_PHONE', $this->siteLangId), 'phone', '', array('class' => 'phone-js ltr-right', 'placeholder' => ValidateElement::PHONE_NO_FORMAT, 'maxlength' => ValidateElement::PHONE_NO_LENGTH));
        $fld_phn->requirements()->setRegularExpressionToValidate(ValidateElement::PHONE_REGEX);
        $fld_phn->requirements()->setCustomErrorMessage(Labels::getLabel('ERR_PLEASE_ENTER_VALID_PHONE_NUMBER_FORMAT.', $this->siteLangId));

        $frm->addTextArea(Labels::getLabel('FRM_YOUR_MESSAGE', $this->siteLangId), 'message', '')->requirements()->setRequired();

        CommonHelper::addCaptchaField($frm);
        $fld = $frm->addCheckBox('', 'agree', 1);
        $fld->requirements()->setRequired();
        $fld->requirements()->setCustomErrorMessage(Labels::getLabel('ERR_TERMS_CONDITION_AND_PRIVACY_POLICY_IS_MANDATORY.', $this->siteLangId));
        $frm->addSubmitButton('', 'btn_submit', Labels::getLabel('BTN_SUBMIT', $this->siteLangId));
        return $frm;
    }

    public function sitemap()
    {
        $brandSrch = Brand::getListingObj($this->siteLangId, array('brand_id', 'IFNULL(brand_name, brand_identifier) as brand_name'), true);
        $brandSrch->doNotCalculateRecords();
        $brandSrch->doNotLimitRecords();
        $brandSrch->addOrder('brand_name', 'asc');
        $brandRs = $brandSrch->getResultSet();
        $brandsArr = FatApp::getDb()->fetchAll($brandRs);
        $categoriesArr = ProductCategory::getProdCatParentChildWiseArr($this->siteLangId, 0, true, false, true);
        $contentPages = ContentPage::getPagesForSelectBox($this->siteLangId);

        $srch = new ShopSearch($this->siteLangId);
        $srch->setDefinedCriteria($this->siteLangId, true);
        /* $srch->joinShopCountry();
        $srch->joinShopState();
        $srch->joinSellerSubscription(); */
        $srch->doNotCalculateRecords();
        $srch->addOrder('shop_name');
        $shopRs = $srch->getResultSet();
        $allShops = FatApp::getDb()->fetchAll($shopRs, 'shop_id');


        $this->set('allShops', $allShops);
        $this->set('contentPages', $contentPages);
        $this->set('categoriesArr', $categoriesArr);
        $this->set('allBrands', $brandsArr);
        $this->_template->render();
    }

    public function updateUserCookies()
    {
        $statisticalCookies = FatApp::getPostedData('statistical_cookies', FatUtility::VAR_INT, 0);
        $personaliseCookies = FatApp::getPostedData('personalise_cookies', FatUtility::VAR_INT, 0);
        $userId = UserAuthentication::getLoggedUserId(true);
        if ($userId > 0) {
            $user = new User($userId);
            if (!$user->saveUserCookiesPreferences($statisticalCookies, $personaliseCookies)) {
                FatUtility::dieJsonError($user->getError());
            }
        } else {
            setcookie('ykStatisticalCookies', $statisticalCookies, time() + 3600 * 24 * 10, CONF_WEBROOT_FRONT_URL);
            setcookie('ykPersonaliseCookies', $personaliseCookies, time() + 3600 * 24 * 10, CONF_WEBROOT_FRONT_URL);
        }
        $this->_template->render(false, false, 'json-success.php');
    }

    public function requestDemo()
    {
        $this->_template->render(false, false);
    }

    public function feedback()
    {
        $this->_template->render();
    }

    public function downloadLogFile($fileName)
    {
        AttachedFile::downloadAttachment('import-error-log/' . $fileName, $fileName);
    }

    public function deleteErrorLogFiles($hoursBefore = '4')
    {
        if (!ImportexportCommon::deleteErrorLogFiles($hoursBefore)) {
            FatUtility::dieJsonError(Labels::getLabel('ERR_INVALID_HOURS', $this->siteLangId));
        }
    }

    public function deleteBulkUploadSubDirs($hoursBefore = '48')
    {
        $obj = new UploadBulkImages();
        $msg = $obj->deleteBulkUploadSubDirs($hoursBefore);
        FatUtility::dieJsonSuccess($msg);
    }

    public function signupAgreementUrls()
    {
        $privacyPolicyLink = FatApp::getConfig('CONF_PRIVACY_POLICY_PAGE', FatUtility::VAR_STRING, '');
        $termsAndConditionsLink = FatApp::getConfig('CONF_TERMS_AND_CONDITIONS_PAGE', FatUtility::VAR_STRING, '');
        $data = array(
            'privacyPolicyLink' => UrlHelper::generateFullUrl('cms', 'view', array($privacyPolicyLink)),
            'faqLink' => UrlHelper::generateFullUrl('custom', 'faq'),
            'termsAndConditionsLink' => UrlHelper::generateFullUrl('cms', 'view', array($termsAndConditionsLink)),
        );
        $this->set('data', $data);
        $this->_template->render();
    }

    public function setupSidebarVisibility($openSidebar = 1)
    {
        setcookie('openSidebar', $openSidebar, 0, CONF_WEBROOT_FRONTEND);
    }

    public function updateScreenResolution($width, $height)
    {
        setcookie('screenWidth', $width, 0, CONF_WEBROOT_FRONTEND);
        setcookie('screenHeight', $height, 0, CONF_WEBROOT_FRONTEND);
    }

    public function cookiePreferencesData()
    {
        $this->_template->render(false, false);
    }

    public function rfqSuccess()
    {
        $rfqId = FatApp::getQueryStringData('rfq_id', FatUtility::VAR_INT, 0);
        if (empty($rfqId)) {
            Message::addErrorMessage(Labels::getLabel('ERR_RFQ_ID_NOT_FOUND!', $this->siteLangId));
            CommonHelper::redirectUserReferer();
        }
        $srch = new RequestForQuoteSearch();
        $srch->joinBuyer();
        $srch->joinBuyerAddress($this->siteLangId);
        $srch->joinCountry(true);
        $srch->joinState(true);

        $dbFlds = array_merge(RequestForQuote::FIELDS, ['addr_name', 'addr_address1', 'addr_address2', 'addr_city', 'state_name', 'country_name', 'addr_zip', 'addr_phone_dcode', 'addr_phone', 'buc.credential_username as credential_username', 'bu.user_id as user_id', 'bu.user_updated_on', 'credential_email', 'bu.user_name', 'IFNULL(country_name, country_code) as country_name', 'IFNULL(state_name, state_identifier) as state_name']);
        $srch->addMultipleFields($dbFlds);

        $srch->addCondition('rfq_id', '=', $rfqId);
        $this->set("rfqData", FatApp::getDb()->fetch($srch->getDataResultSet()));
        $this->_template->render();
    }
}
