<?php
class EmailHandler extends FatModel
{
    public const ADD_ADDITIONAL_ALERTS = 1;
    public const NO_ADDITIONAL_ALERT = 0;
    public const ONLY_SUPER_ADMIN = 1;
    public const NOT_ONLY_SUPER_ADMIN = 0;

    private $commonLangId;

    public function __construct()
    {
        $this->commonLangId = CommonHelper::getLangId();
    }

    public function sendSms($tpl, $phone, $arrReplacements, $langId)
    {
        $langId = 1 > FatUtility::int($langId) ? $this->commonLangId : FatUtility::int($langId);
        if (empty($phone) || empty($tpl) || empty($arrReplacements)) {
            $this->error = Labels::getLabel('ERR_INVALID_REQUEST', $langId);
            return false;
        }
        $phone = false !== strpos($phone, '+') ? $phone : '+' . $phone;

        $smsArchive = new SmsArchive();
        $smsArchive->toPhone($phone);
        $smsArchive->setTemplate($langId, $tpl, $arrReplacements);
        if (!$smsArchive->send()) {
            $this->error = $smsArchive->getError();
            return false;
        }
        return true;
    }

    // Send mail to super Admin, Sub Admin and additonal alert emails.
    public function sendMailToAdminAndAdditionalEmails($tpl, $arrReplacements, $additonalAlerts = 1, $onlySuperAdmin = 0, $langId = 0, $attachmentsArr = [])
    {
        $langId = FatUtility::int($langId);

        if (1 > $langId) {
            $langId = $this->commonLangId;
        }
        if (empty($tpl) || empty($arrReplacements)) {
            $this->error = Labels::getLabel('ERR_INVALID_REQUEST!!_FAILED_TO_SEND_MAIL_TO_ADMINS.', $langId);
            return false;
        }
        $onlySuperAdmin = FatUtility::int($onlySuperAdmin);
        $path = $attachmentsArr['path'] ?? '';
        $name = $attachmentsArr['name'] ?? '';
        if (0 < $onlySuperAdmin) {
            return (new FatMailer($langId, $tpl))
                ->setTo(FatApp::getConfig('CONF_SITE_OWNER_EMAIL'))
                ->setVariables($arrReplacements)
                ->addAttachment($path, $name)
                ->send();
        }

        $emails = array(FatApp::getConfig('CONF_SITE_OWNER_EMAIL'));

        $srch = AdminUsers::getSearchObject();
        $srch->addCondition('admin_id', '!=', 'mysql_func_' . Admin::SUPER, 'AND', true);
        $srch->addCondition('admin_email_notification', '=', 'mysql_func_' . applicationConstants::YES, 'AND', true);
        $srch->addMultipleFields(array('admin_id', 'admin_email'));
        $srch->doNotCalculateRecords();
        $rs = $srch->getResultSet();

        if ($rs) {
            $subAdmins = FatApp::getDb()->fetchAll($rs);
            $emailTempPermissionArr = $this->getEmailTemplatePermissionsArr();
            if (count($subAdmins) && array_key_exists($tpl, $emailTempPermissionArr)) {
                $tplPermission = $emailTempPermissionArr[$tpl];
                $privilege = new AdminPrivilege();
                foreach ($subAdmins as $record) {
                    $userPermissions = array_filter($privilege->getAdminPermissionLevel($record['admin_id']));
                    if (array_key_exists($tplPermission, $userPermissions) && $privilege->canViewNotifications($record['admin_id'], true)) {
                        $emails[] = $record['admin_email'];
                    }
                }
            }
        }

        $additonalAlerts = FatUtility::int($additonalAlerts);
        if (0 < $additonalAlerts) {
            $additionalAlertEmails = FatApp::getConfig("CONF_ADDITIONAL_ALERT_EMAILS", FatUtility::VAR_STRING, '');
            $additionalAlertEmails = array_filter(explode(',', $additionalAlertEmails));
            if (count($additionalAlertEmails)) {
                $emails = array_merge($emails, $additionalAlertEmails);
            }
        }

        $superAdminResp = false;
        foreach ($emails as $index => $email) {
            $email = trim($email);
            if ($email && preg_match('/^[^\@]+@.*.[a-z]{2,15}$/i', $email)) {
                $resp = (new FatMailer($langId, $tpl))
                    ->setTo($email)
                    ->setVariables($arrReplacements)
                    ->addAttachment($path, $name)
                    ->send();
                if (1 > $index) {
                    $superAdminResp = $resp;
                }
            }
        }
        return $superAdminResp;
    }

    public function sendSignupVerificationLink($langId, $d)
    {
        $tpl = 'user_signup_verification';

        $vars = array(
            '{user_full_name}' => $d['user_name'],
            '{verification_url}' => $d['link'],
        );

        if (isset($d['user_id']) && $d['user_id'] > 0) {
            $notificationObj = new Notifications();
            $notificationDataArr = array(
                'unotification_user_id' => $d['user_id'],
                'unotification_body' => Labels::getLabel('MSG_VERIFY_YOUR_ACCCOUNT_FROM_REGISTERED_EMAIL', $langId),
                'unotification_type' => 'REGISTRATION_VERIFICATION',
            );
            if (!$notificationObj->addNotification($notificationDataArr)) {
                $this->error = $notificationObj->getError();
                return false;
            }
        }

        $sendEmail = false;
        if (!empty($d['user_email'])) {
            $sendEmail = (new FatMailer($langId, $tpl))
                ->setTo($d['user_email'])
                ->setVariables($vars)
                ->send();
        } else {
            SystemLog::system('Unable to send Signup Verification Link as user(' . $d['user_name'] . ') email is empty', 'Unable to send Signup Verification Link');
        }

        if (false === $sendEmail) {
            return false;
        }

        return true;
    }

    public function sendEmailVerificationLink($langId, $d)
    {
        $tpl = 'user_email_verification';

        $vars = array(
            '{user_full_name}' => $d['user_name'],
            '{verification_url}' => $d['link'],
        );

        $sendEmail  = false;
        if (!empty($d['user_email'])) {
            $sendEmail = (new FatMailer($langId, $tpl))
                ->setTo($d['user_email'])
                ->setVariables($vars)
                ->send();
        } else {
            SystemLog::system('Unable to send Email Verification Link as user(' . $d['user_name'] . ') email is empty', 'Unable to send Email Verification Link');
        }

        $sendSms = $this->sendSms($tpl, ValidateElement::formatDialCode($d['user_phone_dcode']) . $d['user_phone'], $vars, $langId);
        if (false === $sendSms  && false === $sendEmail) {
            return false;
        }

        return true;
    }

    public function sendChangeEmailRequestNotification($langId, $d)
    {
        $tpl = 'user_change_email_request_notification';
        $vars = array(
            '{user_full_name}' => $d['user_name'],
            '{new_email}' => $d['user_new_email'],
        );

        $sendEmail = false;
        if (!empty($d['user_email'])) {
            $sendEmail = (new FatMailer($langId, $tpl))
                ->setTo($d['user_email'])
                ->setVariables($vars)
                ->send();
        }
        $sendSms = $this->sendSms($tpl, ValidateElement::formatDialCode($d['user_phone_dcode']) . $d['user_phone'], $vars, $langId);
        if (false === $sendEmail && false === $sendSms) {
            return false;
        }

        return true;
    }

    public function sendEmailChangedNotification($langId, $d)
    {
        $tpl = 'user_email_changed_notification';

        $vars = array(
            '{user_full_name}' => $d['user_name'],
            '{new_email}' => $d['user_new_email'],
        );
        $sendEmail = false;
        if (!empty($d['user_email'])) {
            $sendEmail = (new FatMailer($langId, $tpl))
                ->setTo($d['user_email'])
                ->setVariables($vars)
                ->send();
        }

        $sendSms = $this->sendSms($tpl, ValidateElement::formatDialCode($d['user_phone_dcode']) . $d['user_phone'], $vars, $langId);
        if (false === $sendEmail  && false === $sendSms) {
            return false;
        }

        return true;
    }

    public function sendNewRegistrationNotification($langId, $d)
    {
        $tpl = 'new_registration_admin';

        if (isset($d['user_is_affiliate']) && $d['user_is_affiliate']) {
            $tpl = 'new_affiliate_registration_admin';
        }

        $userType = !empty($d['user_type']) ? User::getUserTypesArr($langId)[$d['user_type']] : '';
        $vars = array(
            '{name}' => $d['user_name'],
            '{email}' => $d['user_email'],
            '{phone}' => ValidateElement::formatDialCode($d['user_phone_dcode']) . $d['user_phone'],
            '{username}' => $d['user_username'],
            '{user_type}' => $userType
        );

        if (!$this->sendMailToAdminAndAdditionalEmails($tpl, $vars, static::NO_ADDITIONAL_ALERT, static::NOT_ONLY_SUPER_ADMIN, $langId)) {
            return false;
        }
        $this->sendSms($tpl, ValidateElement::formatDialCode(FatApp::getConfig('CONF_SITE_PHONE_dcode')) . FatApp::getConfig('CONF_SITE_PHONE'), $vars, $langId);
        return true;
    }

    public function sendNewCatalogNotification($langId, $d)
    {
        $tpl = 'new_catalog_request_admin';
        $vars = array(
            '{request_title}' => $d['request_title'],
            '{brand_name}' => $d['brand_name']
        );
        if (!$this->sendMailToAdminAndAdditionalEmails($tpl, $vars, static::NO_ADDITIONAL_ALERT, static::NOT_ONLY_SUPER_ADMIN, $langId)) {
            return false;
        }
        $this->sendSms($tpl, ValidateElement::formatDialCode(FatApp::getConfig('CONF_SITE_PHONE_dcode')) . FatApp::getConfig('CONF_SITE_PHONE'), $vars, $langId);
        return true;
    }

    public function sendNewCustomCatalogNotification($langId, $d)
    {
        $tpl = 'new_custom_catalog_request_admin';

        $vars = array(
            '{request_title}' => $d['request_title'],
            '{brand_name}' => $d['brand_name'],
            '{product_model}' => $d['product_model'],
        );
        if (!$this->sendMailToAdminAndAdditionalEmails($tpl, $vars, static::NO_ADDITIONAL_ALERT, static::NOT_ONLY_SUPER_ADMIN, $langId)) {
            return false;
        }
        $this->sendSms($tpl, ValidateElement::formatDialCode(FatApp::getConfig('CONF_SITE_PHONE_dcode')) . FatApp::getConfig('CONF_SITE_PHONE'), $vars, $langId);
        return true;
    }

    public function sendWelcomeEmailToGuestUser($langId, $d)
    {
        $tpl = 'guest_welcome_registration';

        $vars = array(
            '{name}' => $d['user_name'],
            '{username}' => $d['user_name'],
            '{account_type}' => $d['account_type'],
            '{contact_us_email}' => FatApp::getConfig('CONF_CONTACT_EMAIL'),
        );

        if (!empty($d['user_email'])) {
            return (new FatMailer($langId, $tpl))
                ->setTo($d['user_email'])
                ->setVariables($vars)
                ->send();
        }
        return true;
    }

    public function sendWelcomeEmail($langId, $d)
    {
        $tpl = 'welcome_registration';

        if (isset($d['user_is_affiliate']) && $d['user_is_affiliate']) {
            $tpl = 'affiliate_welcome_registration';
        }

        $vars = array(
            '{name}' => $d['user_name'],
            '{username}' => $d['user_name'],
            '{contact_us_email}' => FatApp::getConfig('CONF_CONTACT_EMAIL'),
            '{account_type}' => $d['account_type'],
        );

        if (isset($d['user_id']) && $d['user_id'] > 0) {
            $notificationObj = new Notifications();
            $notificationDataArr = array(
                'unotification_user_id' => $d['user_id'],
                'unotification_body' => Labels::getLabel('MSG_THANK_YOU_FOR_ACCOUNT_VERIFICATION', $langId),
                'unotification_type' => 'REGISTRATION',
            );
            if (!$notificationObj->addNotification($notificationDataArr)) {
                $this->error = $notificationObj->getError();
                return false;
            }
        }
        $sendEmail = false;
        if (!empty($d['user_email'])) {
            $sendEmail = (new FatMailer($langId, $tpl))
                ->setTo($d['user_email'])
                ->setVariables($vars)
                ->send();
        }

        $sendSms = $this->sendSms($tpl, ValidateElement::formatDialCode($d['user_phone_dcode']) . $d['user_phone'], $vars, $langId);
        if (false === $sendEmail && false === $sendSms) {
            return false;
        }
        return true;
    }

    public function sendAdminNewUserCreationEmail($langId, $d)
    {
        $tpl = 'admin_new_user_creation_email';

        $vars = array(
            '{user_full_name}' => $d['user_name'],
            '{user_email}' => $d['user_email'],
            '{reset_url}' => $d['link'],
            '{days}' => $d['days'],
            '{account_type}' => $d['account_type'],
        );

        $sendEmail = false;
        if (!empty($d['user_email'])) {
            $sendEmail = (new FatMailer($langId, $tpl))
                ->setTo($d['user_email'])
                ->setVariables($vars)
                ->send();
        }

        if (false === $sendEmail) {
            return false;
        }

        return true;
    }

    public function sendForgotPasswordLinkEmail($langId, $d)
    {
        $tpl = 'forgot_password';
        $vars = array(
            '{user_full_name}' => $d['user_name'],
            '{reset_url}' => $d['link'],
        );
        $sendEmail = false;
        if (!empty($d['credential_email'])) {
            $sendEmail = (new FatMailer($langId, $tpl))
                ->setTo($d['credential_email'])
                ->setVariables($vars)
                ->send();
        }

        $phone = !empty($d['user_phone']) ? ValidateElement::formatDialCode($d['user_phone_dcode']) . $d['user_phone'] : '';
        $sendSms = $this->sendSms($tpl, $phone, $vars, $langId);

        if (false === $sendEmail && false === $sendSms) {
            return false;
        }
        return true;
    }

    public function sendResetPasswordConfirmationEmail($langId, &$d)
    {
        $tpl = 'password_changed_successfully';
        $vars = array(
            '{full_name}' => $d['user_name'],
            '{login_link}' => $d['link'],
        );
        $sendEmail = false;
        if (!empty($d['credential_email'])) {
            $sendEmail = (new FatMailer($langId, $tpl))
                ->setTo($d['credential_email'])
                ->setVariables($vars)
                ->send();
        }

        $phone = array_key_exists('user_phone', $d) ? ValidateElement::formatDialCode($d['user_phone_dcode']) . $d['user_phone']  : '';
        $sendSms = $this->sendSms($tpl, $phone, $vars, $langId);
        if (false === $sendEmail && false === $sendSms) {
            return false;
        }
        return true;
    }

    public function sendSupplierApprovalNotification($langId, $d, $approval_request = 1)
    {
        $tpl = 'new_seller_approved_admin';
        if ($approval_request == 1) {
            $tpl = 'new_supplier_approval_admin';
        }

        $vars = array(
            '{name}' => $d['user_name'],
            '{email}' => $d['user_email'],
            '{username}' => $d['username'],
            '{reference_number}' => $d['reference_number'],
        );

        if (!$this->sendMailToAdminAndAdditionalEmails($tpl, $vars, static::NO_ADDITIONAL_ALERT, static::NOT_ONLY_SUPER_ADMIN, $langId)) {
            return false;
        }

        $this->sendSms($tpl, ValidateElement::formatDialCode(FatApp::getConfig('CONF_SITE_PHONE_dcode')) . FatApp::getConfig('CONF_SITE_PHONE'), $vars, $langId);
        return true;
    }

    public function sendSupplierRequestStatusChangeNotification($langId, $d)
    {
        $tpl = 'supplier_request_status_change_buyer';

        $supplierRequestComments = '';
        if ($d['usuprequest_comments'] != '') {
            $supplierRequestComments = nl2br($d['usuprequest_comments']);
        }

        $statusArr = User::getSupplierReqStatusArr($langId);

        $vars = array(
            '{user_full_name}' => $d['user_name'],
            '{reference_number}' => $d['usuprequest_reference'],
            '{new_request_status}' => $statusArr[$d['usuprequest_status']],
            '{request_comments}' => $supplierRequestComments,
        );
        $sendEmail = false;
        if (!empty($d['credential_email'])) {
            $sendEmail = (new FatMailer($langId, $tpl))
                ->setTo($d['credential_email'])
                ->setVariables($vars)
                ->send();
        }

        $phone = !empty($d['user_phone']) ? ValidateElement::formatDialCode($d['user_phone_dcode']) . $d['user_phone'] : '';
        $sendSms = $this->sendSms($tpl, $phone, $vars, $langId);
        if (false === $sendEmail && false === $sendSms) {
            return false;
        }
        return true;
    }

    public function sendCatalogRequestStatusChangeNotification($langId, $d)
    {
        $tpl = 'seller_catalog_request_status_change';

        $userObj = new User($d['seller_id']);
        $userInfo = $userObj->getSellerData($langId, array('user_id', 'ifnull(shop_name, shop_identifier) as shop_name', 'user_phone_dcode', 'user_phone', 'credential_email'));

        $statusArr = Product::getApproveUnApproveArr($langId);

        $vars = array(
            '{shop_name}' => $userInfo['shop_name'],
            '{new_status}' => $statusArr[$d['status']],
            '{product_name}' => $d['product_name'],
        );

        $receipentsInfo = User::getSubUsersReceipents($userInfo['user_id'], 'canViewProducts');
        $bccEmails = $receipentsInfo['email'];

        if (!empty($userInfo['credential_email'])) {
            $mailerObj = (new FatMailer($langId, $tpl))
                ->setTo($userInfo['credential_email'])
                ->setVariables($vars);

            foreach ($bccEmails as $emailId => $emailName) {
                $mailerObj->addBcc($emailId, $emailName);
            }

            if (false === $mailerObj->send()) {
                return false;
            }
        }

        $phoneNumbers = $receipentsInfo['phone'];
        $phoneNumbers[] = !empty($userInfo['user_phone']) ? ValidateElement::formatDialCode($userInfo['user_phone_dcode']) . $userInfo['user_phone'] : '';
        foreach ($phoneNumbers as $phone) {
            $this->sendSms($tpl, $phone, $vars, $langId);
        }
        return true;
    }

    public function sendBrandRequestStatusChangeNotification($langId, $data)
    {
        $tpl = 'seller_brand_request_status_change';
        $brandRequestComments = '';
        if (isset($data['brand_comments']) != '') {
            $brandRequestComments = nl2br($data['brand_comments']);
        }
        $userObj = new User($data['brand_seller_id']);
        $userInfo = $userObj->getSellerData($langId, array('user_id', 'COALESCE(shop_name, shop_identifier, user_name, credential_username) as shop_name', 'user_phone_dcode', 'user_phone', 'credential_email'));
        $statusArr = Brand::getBrandReqStatusArr($langId);

        $vars = array(
            '{shop_name}' => $userInfo['shop_name'],
            '{new_request_status}' => $statusArr[$data['brand_status']],
            '{brand_name}' => $data['brand_name'],
            '{brand_request_comments}' => '',
        );
        if (!empty($brandRequestComments)) {
            $rTpl = new FatTemplate('', '');
            $rTpl->set('brandRequestComments', $brandRequestComments);
            $requestCommentTableFormatHtml = $rTpl->render(false, false, '_partial/emails/brand-request-comment-email.php', true);
            $vars["{brand_request_comments}"] = $requestCommentTableFormatHtml;
        }

        $receipentsInfo = User::getSubUsersReceipents($userInfo['user_id'], 'canViewProducts');

        $bccEmails = $receipentsInfo['email'];
        $phoneNumbers = $receipentsInfo['phone'];

        if (!empty($userInfo['credential_email'])) {
            $mailerObj = (new FatMailer($langId, $tpl))
                ->setTo($userInfo['credential_email'])
                ->setVariables($vars);

            foreach ($bccEmails as $emailId => $emailName) {
                $mailerObj->addBcc($emailId, $emailName);
            }

            if (false === $mailerObj->send()) {
                return false;
            }
        }

        $userPhone = !empty($userInfo['user_phone']) ? $userInfo['user_phone'] : '';
        $dialCode = !empty($userInfo['user_phone_dcode']) ? ValidateElement::formatDialCode($userInfo['user_phone_dcode']) : '';
        $phoneNumbers[] = $dialCode . $userPhone;
        foreach ($phoneNumbers as $phone) {
            $this->sendSms($tpl, $phone, $vars, $langId);
        }

        return true;
    }

    public function sendCustomCatalogRequestStatusChangeNotification($langId, $d)
    {
        $tpl = 'seller_custom_catalog_request_status_change';

        $catalogRequestComments = '';
        if ($d['preq_comment'] != '') {
            $catalogRequestComments = nl2br($d['preq_comment']);
        } else {
            $catalogRequestComments = '{new_request_status} ' . Labels::getLabel('LBL_BY_ADMIN', $langId);
        }

        $statusArr = ProductRequest::getStatusArr($langId);

        $vars = array(
            '{shop_name}' => $d['shop_name'],
            '{request_comments}' => $catalogRequestComments,
            '{new_request_status}' => $statusArr[$d['preq_status']],
            '{prod_title}' => isset($d['preq_content']) ? (json_decode($d['preq_content'], true))['product_identifier'] : '',
        );

        $receipentsInfo = User::getSubUsersReceipents($d['preq_user_id'], 'canViewProducts');
        $bccEmails = $receipentsInfo['email'];

        if (!empty($d['credential_email'])) {
            $mailerObj = (new FatMailer($langId, $tpl))
                ->setTo($d['credential_email'])
                ->setVariables($vars);

            foreach ($bccEmails as $emailId => $emailName) {
                $mailerObj->addBcc($emailId, $emailName);
            }
            if (false === $mailerObj->send()) {
                return false;
            }
        }

        $phoneNumbers = $receipentsInfo['phone'];
        $userPhone = !empty($d['user_phone']) ? ValidateElement::formatDialCode($d['user_phone_dcode']) . $d['user_phone'] : '';
        $phoneNumbers[] = $userPhone;
        foreach ($phoneNumbers as $phone) {
            $this->sendSms($tpl, $phone, $vars, $langId);
        }
        return true;
    }

    public function sendBrandRequestAdminNotification($langId, $data)
    {
        $tpl = 'seller_brand_request_admin_email';

        $userObj = new User($data['brand_seller_id']);
        $userInfo = $userObj->getUserInfo(array('user_name', 'user_phone_dcode', 'user_phone', 'credential_email'));

        $vars = array(
            '{user_full_name}' => $userInfo['user_name'],
            '{brand_name}' => $data['brand_identifier'],
        );
        if (!$this->sendMailToAdminAndAdditionalEmails($tpl, $vars, static::NO_ADDITIONAL_ALERT, static::NOT_ONLY_SUPER_ADMIN, $langId)) {
            return false;
        }
        $phone = !empty($userInfo['user_phone']) ? $userInfo['user_phone'] : '';
        $dialCode = !empty($userInfo['user_phone_dcode']) ? ValidateElement::formatDialCode($userInfo['user_phone_dcode']) : '';
        $this->sendSms($tpl, $dialCode . $phone, $vars, $langId);
        return true;
    }

    public function sendCategoryRequestAdminNotification($langId, $data)
    {
        $tpl = 'seller_category_request_admin_email';

        $userObj = new User($data['prodcat_seller_id']);
        $userInfo = $userObj->getUserInfo(array('user_name', 'user_phone_dcode', 'user_phone', 'credential_email'));

        $vars = array(
            '{user_full_name}' => $userInfo['user_name'],
            '{prodcat_name}' => $data['prodcat_identifier'],
        );
        if (!$this->sendMailToAdminAndAdditionalEmails($tpl, $vars, static::NO_ADDITIONAL_ALERT, static::NOT_ONLY_SUPER_ADMIN, $langId)) {
            return false;
        }
        $phone = !empty($userInfo['user_phone']) ? $userInfo['user_phone'] : '';
        $dialCode = !empty($userInfo['user_phone_dcode']) ? ValidateElement::formatDialCode($userInfo['user_phone_dcode']) : '';
        $this->sendSms($tpl, $dialCode . $phone, $vars, $langId);
        return true;
    }

    public function sendContactRequestEmailToAdmin($langId, &$d)
    {
        $tpl = 'tpl_contact_request_received';
        $vars = array(
            '{requests_link}' => $d['link'],
        );

        $to = FatApp::getConfig('CONF_CONTACT_TO_EMAIL', FatUtility::VAR_STRING, '');
        if (strlen(trim($to)) < 1) {
            return $this->sendMailToAdminAndAdditionalEmails($tpl, $vars, static::NO_ADDITIONAL_ALERT, static::ONLY_SUPER_ADMIN, $langId);
        }

        $sendEmail = (new FatMailer($langId, $tpl))
            ->setTo($to)
            ->setVariables($vars)
            ->send();

        if (false === $sendEmail) {
            return false;
        }
        $this->sendSms($tpl, ValidateElement::formatDialCode(FatApp::getConfig('CONF_SITE_PHONE_dcode')) . FatApp::getConfig('CONF_SITE_PHONE'), $vars, $langId);
        return true;
    }

    public function sendContactFormEmail($to, $langId, $d)
    {
        $tpl = 'contact_us';
        $vars = array(
            '{name}' => $d['name'],
            '{email_address}' => $d['email'],
            '{phone_number}' => ValidateElement::formatDialCode($d['phone_dcode']) . $d['phone'],
            '{message}' => nl2br($d['message'])
        );
        $sendEmail = false;
        if (!empty($to)) {
            $sendEmail = (new FatMailer($langId, $tpl))
                ->setTo($to)
                ->setReplyTo($d['email'])
                ->setVariables($vars)
                ->send();
        }

        $d['phone'] = isset($d['phone']) ? ValidateElement::formatDialCode($d['phone_dcode']) . $d['phone'] : '';
        $sendSms = $this->sendSms($tpl, $d['phone'], $vars, $langId);
        if (false === $sendEmail && false === $sendSms) {
            return false;
        }
        return true;
    }

    public function newOrderBuyerAdmin($order_id, $langId = 0, $includeAdmin = true, $sendInstantNotification = false)
    {
        if ($order_id == '') {
            trigger_error(Labels::getLabel("ERR_ORDER_ID_NOT_SPECIFIED", $this->commonLangId), E_USER_ERROR);
        }
        $langId = FatUtility::int($langId);

        if (!$langId) {
            trigger_error(Labels::getLabel('ERR_LANGUAGE_ID_NOT_SPECIFIED', $this->commonLangId), E_USER_ERROR);
        }
        $orderObj = new Orders();
        $orderInfo = $orderObj->getOrderById($order_id, $langId);

        if ($orderInfo) {
            $order_discount_coupon = $orderInfo['order_discount_coupon_code'] != "" ? $orderInfo['order_discount_coupon_code'] : Labels::getLabel("LBL_-NA-", $langId);

            $orderProducts = $orderObj->getChildOrders(array('order_id' => $orderInfo['order_id']), $orderInfo['order_type'], $orderInfo['order_language_id'], true);

            $addresses = $orderObj->getOrderAddresses($orderInfo["order_id"]);

            $userObj = new User($orderInfo["order_user_id"]);
            $userInfo = $userObj->getUserInfo(array('user_name', 'credential_email', 'user_phone_dcode', 'user_phone'), true, false);

            $billingArr = array();
            if (!empty($addresses[Orders::BILLING_ADDRESS_TYPE])) {
                $billingArr = $addresses[Orders::BILLING_ADDRESS_TYPE];
            }

            $shippingArr = array();
            if (!empty($addresses[Orders::SHIPPING_ADDRESS_TYPE])) {
                $shippingArr = $addresses[Orders::SHIPPING_ADDRESS_TYPE];
            }

            foreach ($orderProducts as $opID => $val) {
                $opChargesLog = new OrderProductChargeLog($opID);
                $taxOptions = $opChargesLog->getData($langId);
                $orderProducts[$opID]['taxOptions'] = $taxOptions;
            }

            $orderProductsData = [];
            foreach ($orderProducts as $opID => $val) {
                $orderProductsData[$val["opshipping_pickup_addr_id"]][] = $val;
                $pickUpAddress = $orderObj->getOrderAddresses($orderInfo['order_id'], $opID);
                if (!empty($pickUpAddress[Orders::PICKUP_ADDRESS_TYPE])) {
                    $orderProductsData[$val["opshipping_pickup_addr_id"]]['pickupAddress'] = $pickUpAddress[Orders::PICKUP_ADDRESS_TYPE];
                } else {
                    $orderProductsData[$val["opshipping_pickup_addr_id"]]['pickupAddress'] = array();
                }
            }

            $tpl = new FatTemplate('', '');
            $tpl->set('orderInfo', $orderInfo);
            // $tpl->set('orderProducts', $orderProducts);
            $tpl->set('orderProductsData', $orderProductsData);
            $tpl->set('siteLangId', $langId);
            $tpl->set('billingAddress', $billingArr);
            $tpl->set('shippingAddress', $shippingArr);

            $order_products_table_format = $tpl->render(false, false, '_partial/emails/order-detail-email.php', true);

            $arrReplacements = array(
                '{user_full_name}' => trim($userInfo['user_name']),
                '{order_invoice_number}' => $orderInfo['order_number'],
                //'{reference_number}' => $orderInfo['order_id'],
                //'{company_name}' => $orderInfo['order_company_name'],
                '{order_date}' => FatDate::format($orderInfo["order_date_added"], true),
                '{shipping_method}' => $orderInfo['order_shippingapi_name'],
                '{discount_coupon}' => CommonHelper::displayNotApplicable($langId, $orderInfo['order_discount_coupon_code']),
                '{coupon_discount}' => CommonHelper::displayMoneyFormat($orderInfo['order_discount_total'], true, true),
                //'{payment_method}' => $orderInfo['order_payment_method'],
                //'{order_cart_total}' => CommonHelper::displayMoneyFormat($orderInfo['order_cart_total']),
                //'{shipping}' => CommonHelper::displayMoneyFormat($orderInfo['order_shipping_charged']),
                //'{payment_fees}' => CommonHelper::displayMoneyFormat($orderInfo['order_net_amount']),
                '{discount}' => CommonHelper::displayMoneyFormat($orderInfo['order_discount_total'], true, true),
                //'{sub_order_total}' => CommonHelper::displayMoneyFormat($orderInfo['order_sub_total']),
                //'{tax_vat}' => CommonHelper::displayMoneyFormat($orderInfo['order_tax_charged']),
                '{total_paid}' => CommonHelper::displayMoneyFormat($orderInfo['order_net_amount'], true, true),
                //'{order_credits_used}' => CommonHelper::displayMoneyFormat($orderInfo['order_credits_charged']),
                //'{order_payment_made}' => CommonHelper::displayMoneyFormat($orderInfo['order_actual_paid']),
                //'{discount_code}' => CommonHelper::displayNotApplicable($langId, $orderInfo['order_discount_coupon_code']),
                '{order_products_table_format}' => $order_products_table_format,
            );

            if ($includeAdmin && FatApp::getConfig('CONF_NEW_ORDER_EMAIL', FatUtility::VAR_INT, 1)) {
                $this->sendMailToAdminAndAdditionalEmails("admin_order_email", $arrReplacements, static::ADD_ADDITIONAL_ALERTS, static::NOT_ONLY_SUPER_ADMIN, $langId);
                $this->sendSms("admin_order_email", ValidateElement::formatDialCode(FatApp::getConfig('CONF_SITE_PHONE_dcode')) . FatApp::getConfig('CONF_SITE_PHONE'), $arrReplacements, $langId);
            }
            if (!empty($userInfo['credential_email'])) {
                $sendEmail = (new FatMailer($langId, 'customer_order_email'))
                    ->setTo($userInfo['credential_email'])
                    ->setVariables($arrReplacements)
                    ->send();
            }

            $phone = !empty($userInfo['user_phone']) ? ValidateElement::formatDialCode($userInfo['user_phone_dcode']) . $userInfo['user_phone'] : '';
            $this->sendSms("customer_order_email", $phone, $arrReplacements, $langId);

            $notificationObj = new Notifications();
            $notificationDataArr = array(
                'unotification_user_id' => $orderInfo["order_user_id"],
                'unotification_body' => CommonHelper::replaceStringData(Labels::getLabel('MSG_YOUR_ORDER_{ORDERID}_HAVE_BEEN_PLACE', $langId), array('{ORDERID}' => $orderInfo['order_number'])),
                'unotification_type' => 'BUYER_ORDER',
                'unotification_data' => json_encode(array('orderId' => $orderInfo['order_id'])),
            );
            if (!$notificationObj->addNotification($notificationDataArr, $sendInstantNotification)) {
                $this->error = $notificationObj->getError();
                return false;
            }

            /* if($orderProduct['op_product_type'] == Product::PRODUCT_TYPE_DIGITAL){
              self::sendMailTpl( $userInfo['credential_email'], "customer_digital_order_email", $langId, $arrReplacements );
              }else{
              self::sendMailTpl( $userInfo['credential_email'], "customer_order_email", $langId, $arrReplacements );
              } */
        }
        return true;
    }

    public function newDigitalOrderBuyer($orderId = 0, $opId = 0, $langId = 0)
    {
        if ($opId == '') {
            trigger_error(Labels::getLabel("ERR_OP_ID_NOT_SPECIFIED", $this->commonLangId), E_USER_ERROR);
        }
        $langId = FatUtility::int($langId);

        if (!$langId) {
            trigger_error(Labels::getLabel('ERR_LANGUAGE_ID_NOT_SPECIFIED', $this->commonLangId), E_USER_ERROR);
        }
        $orderObj = new Orders();
        $orderInfo = $orderObj->getOrderById($orderId, $langId);
        $childOrderInfo = $orderObj->getOrderProductsByOpId($opId, $langId);

        $opChargesLog = new OrderProductChargeLog($opId);
        $taxOptions = $opChargesLog->getData($langId);
        $childOrderInfo['taxOptions'] = $taxOptions;

        //CommonHelper::printArray($childOrderInfo, true);
        if ($childOrderInfo) {
            $userObj = new User($orderInfo["order_user_id"]);
            $userInfo = $userObj->getUserInfo(array('user_name', 'credential_email', 'user_phone_dcode', 'user_phone'), true, false);
            $tpl = new FatTemplate('', '');
            $tpl->set('orderInfo', $orderInfo);
            $tpl->set('orderProducts', $childOrderInfo);
            $tpl->set('siteLangId', $langId);
            $orderItemsTableFormatHtml = $tpl->render(false, false, '_partial/emails/child-order-detail-email.php', true);
            $vars = array(
                '{user_full_name}' => trim($userInfo['user_name']),
                '{order_items_table_format}' => $orderItemsTableFormatHtml,
                '{order_id}' => $orderInfo['order_number'],
            );
            if (!empty($userInfo['credential_email'])) {
                $sendEmail = (new FatMailer($langId, 'customer_digital_order_email'))
                    ->setTo($userInfo['credential_email'])
                    ->setVariables($vars)
                    ->send();
            }

            $phone = !empty($userInfo['user_phone']) ? ValidateElement::formatDialCode($userInfo['user_phone_dcode']) . $userInfo['user_phone'] : '';
            $this->sendSms("customer_digital_order_email", $phone, $vars, $langId);
        }
        return true;
    }

    public function orderPaymentUpdateBuyerAdmin($orderId)
    {
        $langId = FatApp::getConfig('conf_default_site_lang');
        $orderObj = new Orders();
        $orderDetail = $orderObj->getOrderById($orderId);

        $userObj = new User($orderDetail["order_user_id"]);
        $userInfo = $userObj->getUserInfo(array('user_name', 'credential_email', 'user_phone_dcode', 'user_phone'), true, false);

        $payementStatusArr = Orders::getOrderPaymentStatusArr($langId);

        if ($orderDetail) {
            $arrReplacements = array(
                '{user_full_name}' => trim($userInfo['user_name']),
                '{invoice_number}' => $orderDetail['order_number'],
                '{new_order_status}' => $payementStatusArr[$orderDetail['order_payment_status']],
            );

            $this->sendMailToAdminAndAdditionalEmails("primary_order_payment_status_change_admin", $arrReplacements, static::ADD_ADDITIONAL_ALERTS, static::NOT_ONLY_SUPER_ADMIN, $langId);
            $this->sendSms("primary_order_payment_status_change_admin", ValidateElement::formatDialCode(FatApp::getConfig('CONF_SITE_PHONE_dcode')) . FatApp::getConfig('CONF_SITE_PHONE'), $arrReplacements, $langId);

            $notiArrReplacements = array(
                '{ORDERID}' => $arrReplacements['{invoice_number}'],
                '{STATUS}' => $arrReplacements['{new_order_status}']
            );
            $appNotification = CommonHelper::replaceStringData(Labels::getLabel('MSG_PAYMENT_STATUS_FOR_ORDER_{ORDERID}_UPDATED_{STATUS}', $langId), $notiArrReplacements);

            $notificationObj = new Notifications();
            $notificationDataArr = array(
                'unotification_user_id' => $orderDetail["order_user_id"],
                'unotification_body' => $appNotification,
                'unotification_type' => 'ORDER_PAYMENT_STATUS',
                'unotification_data' => json_encode(array('orderId' => $orderDetail['order_id'], 'status' => $arrReplacements['{new_order_status}'])),
            );
            if (!$notificationObj->addNotification($notificationDataArr)) {
                $this->error = $notificationObj->getError();
                return false;
            }
            if (!empty($userInfo['credential_email'])) {
                $sendEmail = (new FatMailer($orderDetail['order_language_id'], 'primary_order_payment_status_change_buyer'))
                    ->setTo($userInfo['credential_email'])
                    ->setVariables($arrReplacements)
                    ->send();
            }

            $phone = !empty($userInfo['user_phone']) ? ValidateElement::formatDialCode($userInfo['user_phone_dcode']) . $userInfo['user_phone'] : '';
            $this->sendSms("primary_order_payment_status_change_buyer", $phone, $arrReplacements, $orderDetail['order_language_id']);
        }
        return true;
    }

    public function cashOnDeliveryOrderUpdateBuyerAdmin($orderId, $langId = 0)
    {
        $langId = FatApp::getConfig('conf_default_site_lang');
        $langId = FatUtility::int($langId);
        $orderObj = new Orders();
        $orderDetail = $orderObj->getOrderById($orderId, $langId);

        if (1 > $langId) {
            $langId = $orderDetail['order_language_id'];
        }

        $userObj = new User($orderDetail["order_user_id"]);
        $userInfo = $userObj->getUserInfo(array('user_name', 'credential_email', 'user_phone_dcode', 'user_phone'), true, false);
        // $payementStatusArr = Orders::getOrderPaymentStatusArr($langId);

        if ($orderDetail) {
            $arrReplacements = array(
                '{user_full_name}' => trim($userInfo['user_name']),
                '{invoice_number}' => $orderDetail['order_number'],
                '{order_payment_method}' => (isset($orderDetail['plugin_name']) && !empty($orderDetail['plugin_name'])) ? $orderDetail['plugin_name'] : $orderDetail['plugin_identifier'],
            );

            $this->sendMailToAdminAndAdditionalEmails("primary_order_payment_status_admin", $arrReplacements, static::ADD_ADDITIONAL_ALERTS, static::NOT_ONLY_SUPER_ADMIN, $langId);
            $this->sendSms("primary_order_payment_status_admin", ValidateElement::formatDialCode(FatApp::getConfig('CONF_SITE_PHONE_dcode')) . FatApp::getConfig('CONF_SITE_PHONE'), $arrReplacements, $langId);
            if (!empty($userInfo['credential_email'])) {
                $sendEmail = (new FatMailer($orderDetail['order_language_id'], 'primary_order_payment_status_buyer'))
                    ->setTo($userInfo['credential_email'])
                    ->setVariables($arrReplacements)
                    ->send();
            }

            $phone = !empty($userInfo['user_phone']) ? ValidateElement::formatDialCode($userInfo['user_phone_dcode']) . $userInfo['user_phone'] : '';
            $this->sendSms("primary_order_payment_status_buyer", $phone, $arrReplacements, $orderDetail['order_language_id']);
        }
        return true;
    }

    public function bankTranferOrderUpdateBuyerAdmin($orderId, $langId = 0)
    {
        $langId = FatApp::getConfig('conf_default_site_lang');
        $langId = FatUtility::int($langId);
        $orderObj = new Orders();
        $orderDetail = $orderObj->getOrderById($orderId, $langId);

        if (1 > $langId) {
            $langId = $orderDetail['order_language_id'];
        }

        $userObj = new User($orderDetail["order_user_id"]);
        $userInfo = $userObj->getUserInfo(array('user_name', 'credential_email', 'user_phone_dcode', 'user_phone'), true, false);

        $payementStatusArr = Orders::getOrderPaymentStatusArr($langId);

        if ($orderDetail) {
            $arrReplacements = array(
                '{user_full_name}' => trim($userInfo['user_name']),
                '{invoice_number}' => $orderDetail['order_number'],
                '{order_payment_method}' => !empty($orderDetail['plugin_name']) ? $orderDetail['plugin_name'] : $orderDetail['plugin_identifier'],
            );

            $this->sendMailToAdminAndAdditionalEmails("primary_order_bank_transfer_payment_status_admin", $arrReplacements, static::ADD_ADDITIONAL_ALERTS, static::NOT_ONLY_SUPER_ADMIN, $langId);
            $this->sendSms("primary_order_bank_transfer_payment_status_admin", ValidateElement::formatDialCode(FatApp::getConfig('CONF_SITE_PHONE_dcode')) . FatApp::getConfig('CONF_SITE_PHONE'), $arrReplacements, $langId);
            if (!empty($userInfo['credential_email'])) {
                $sendEmail = (new FatMailer($orderDetail['order_language_id'], 'primary_order_bank_transfer_payment_status_buyer'))
                    ->setTo($userInfo['credential_email'])
                    ->setVariables($arrReplacements)
                    ->send();
            }

            $phone = !empty($userInfo['user_phone']) ? ValidateElement::formatDialCode($userInfo['user_phone_dcode']) . $userInfo['user_phone'] : '';
            $this->sendSms("primary_order_bank_transfer_payment_status_buyer", $phone, $arrReplacements, $orderDetail['order_language_id']);
        }
        return true;
    }

    public function sendProductStockAlert($selprod_id, $langId = 0)
    {
        $langId = FatUtility::int($langId);
        $selprod_id = FatUtility::int($selprod_id);
        if ($langId == 0) {
            $langId = FatApp::getConfig('conf_default_site_lang');
        }

        $srch = SellerProduct::getSearchObject($langId);
        $srch->joinTable(User::DB_TBL, 'LEFT OUTER JOIN', 'u.user_id = sp.selprod_user_id', 'u');
        $srch->joinTable(User::DB_TBL_CRED, 'LEFT OUTER JOIN', 'c.credential_user_id = u.user_id', 'c');
        $srch->addCondition('selprod_id', '= ', 'mysql_func_' . $selprod_id, 'AND', true);
        $srch->joinTable(Shop::DB_TBL, 'LEFT OUTER JOIN', Shop::DB_TBL_PREFIX . 'user_id = u.user_id', 'shop');
        $srch->joinTable(Shop::DB_TBL_LANG, 'LEFT OUTER JOIN', 'shop.shop_id = s_l.shoplang_shop_id AND shoplang_lang_id = ' . $langId, 's_l');

        $srch->addMultipleFields(array('selprod_id', 'selprod_title', 'selprod_product_id', 'user_id', 'user_name', 'user_phone_dcode', 'user_phone', 'credential_email', 'ifnull(shop_name, shop_identifier) as shop_name'));
        $srch->doNotCalculateRecords();
        $srch->doNotLimitRecords();
        $productInfo = FatApp::getDb()->fetch($srch->getResultSet());

        if (empty($productInfo)) {
            return false;
        }

        $url = UrlHelper::generateUrl('seller', 'sellerProductForm', array($productInfo['selprod_product_id'], $productInfo['selprod_id']), CONF_WEBROOT_DASHBOARD, null, false, false, false);

        $arrReplacements = array(
            '{shop_name}' => $productInfo['shop_name'],
            '{prod_title}' => $productInfo["selprod_title"],
            '{click_here}' => $url,
        );

        $receipentsInfo = User::getSubUsersReceipents($productInfo['user_id'], 'canViewProducts');
        $bccEmails = $receipentsInfo['email'];
        if (!empty($productInfo["credential_email"])) {
            $mailerObj = (new FatMailer($langId, 'threshold_notification_vendor'))
                ->setTo($productInfo["credential_email"])
                ->setVariables($arrReplacements);

            foreach ($bccEmails as $emailId => $emailName) {
                $mailerObj->addBcc($emailId, $emailName);
            }
            $mailerObj->send();
        }

        $phoneNumbers = $receipentsInfo['phone'];
        $userPhone = !empty($productInfo['user_phone']) ? ValidateElement::formatDialCode($productInfo['user_phone_dcode']) . $productInfo['user_phone'] : '';
        $phoneNumbers[] = $userPhone;
        foreach ($phoneNumbers as $phone) {
            $this->sendSms("threshold_notification_vendor", $phone, $arrReplacements, $langId);
        }
        return true;
    }

    public function newOrderVendor($orderId, $langId = 0, $paymentType = '')
    {
        $langId = FatApp::getConfig('conf_default_site_lang');
        $orderObj = new Orders();
        $orderDetail = $orderObj->getOrderById($orderId);
        if (1 > $langId) {
            $langId = $orderDetail['order_language_id'];
        }
        if ($orderDetail) {
            $addresses = $orderObj->getOrderAddresses($orderId);

            $billingArr = array();
            if (!empty($addresses[Orders::BILLING_ADDRESS_TYPE])) {
                $billingArr = $addresses[Orders::BILLING_ADDRESS_TYPE];
            }

            $shippingArr = array();
            if (!empty($addresses[Orders::SHIPPING_ADDRESS_TYPE])) {
                $shippingArr = $addresses[Orders::SHIPPING_ADDRESS_TYPE];
            } /* else {
              $shippingArr = $billingArr;
              } */
            $orderVendors = $orderObj->getChildOrders(array("order" => $orderId), $orderDetail['order_type'], $orderDetail['order_language_id']);
            foreach ($orderVendors as $key => $val) :
                $shippingHanldedBySeller = CommonHelper::canAvailShippingChargesBySeller($val['op_selprod_user_id'], $val['opshipping_by_seller_user_id']);

                $opChargesLog = new OrderProductChargeLog($val['op_id']);
                $taxOptions = $opChargesLog->getData($langId);
                $val['taxOptions'] = $taxOptions;

                $pickUpAddress = $orderObj->getOrderAddresses($orderId, $val['op_id']);
                if (!empty($pickUpAddress[Orders::PICKUP_ADDRESS_TYPE])) {
                    $val['pickupAddress'] = $pickUpAddress[Orders::PICKUP_ADDRESS_TYPE];
                } else {
                    $val['pickupAddress'] = array();
                }

                $tpl = new FatTemplate('', '');
                //$tpl->set('orderInfo', $orderDetail);
                $tpl->set('orderProducts', $val);
                $tpl->set('siteLangId', $langId);
                $tpl->set('userType', User::USER_TYPE_SELLER);
                $tpl->set('shippingHanldedBySeller', $shippingHanldedBySeller);
                $tpl->set('billingAddress', $billingArr);
                $tpl->set('shippingAddress', $shippingArr);
                $orderItemsTableFormatHtml = $tpl->render(false, false, '_partial/emails/child-order-detail-email-seller.php', true);
                $userObj = new User($orderDetail["order_user_id"]);
                $userInfo = $userObj->getUserInfo(array('user_name', 'credential_email', 'user_phone_dcode', 'user_phone'), true, false);
                $arrReplacements = array(
                    '{vendor_name}' => trim($val['op_shop_owner_name']),
                    '{order_items_table_format}' => $orderItemsTableFormatHtml,
                    '{order_shipping_information}' => '',
                    '{order_user_email}' => $userInfo['credential_email'],
                    '{order_id}' => $orderDetail['order_number'],
                );

                if (!empty($paymentType)  && in_array(strtolower($paymentType), ['cashondelivery', 'payatstore'])) {
                    $tpl = "vendor_cod_order_email";
                } else if (!empty($paymentType) && strtolower($paymentType) == 'transferbank') {
                    $tpl = "vendor_bank_transfer_order_email";
                } else {
                    if ($val['op_product_type'] == Product::PRODUCT_TYPE_DIGITAL) {
                        $tpl = "vendor_digital_order_email";
                    } else {
                        $tpl = "vendor_order_email";
                    }
                }

                $receipentsInfo = User::getSubUsersReceipents($val['op_selprod_user_id'], 'canViewSales');
                $bccEmails = $receipentsInfo['email'];

                $mailerObj = (new FatMailer($langId, $tpl))
                    ->setTo($val["op_shop_owner_email"])
                    ->setVariables($arrReplacements);

                foreach ($bccEmails as $emailId => $emailName) {
                    $mailerObj->addBcc($emailId, $emailName);
                }
                $mailerObj->send();

                if (!in_array(strtolower($paymentType), ['vendor_bank_transfer_order_email'])) {
                    $sellerInfo = User::getAttributesById($val['op_selprod_user_id'], array('user_phone_dcode', 'user_phone'));
                    $sellerPhone = !empty($sellerInfo['user_phone']) ? ValidateElement::formatDialCode($sellerInfo['user_phone_dcode']) . $sellerInfo['user_phone'] : '';
                    $this->sendSms($tpl, $sellerPhone, $arrReplacements, $langId);
                }
                $notiArrReplacements = array(
                    '{PRODUCT}' => $val["op_product_name"],
                    '{ORDERID}' => $orderDetail['order_number']
                );

                $appNotification = CommonHelper::replaceStringData(Labels::getLabel('INV_{PRODUCT}_ORDER_{ORDERID}_HAS_BEEN_PLACED', $langId), $notiArrReplacements);

                $notificationObj = new Notifications();
                $notificationDataArr = array(
                    'unotification_user_id' => $val["op_selprod_user_id"],
                    'unotification_body' => $appNotification,
                    'unotification_type' => 'SELLER_ORDER',
                    'unotification_data' => json_encode(array('orderId' => $orderDetail['order_id'], 'productName' => $val["op_product_name"])),
                );
                if (!$notificationObj->addNotification($notificationDataArr, false)) {
                    $this->error = $notificationObj->getError();
                    return false;
                }
            endforeach;
        }
        return true;
    }

    public function orderStatusUpdateBuyer($commentId, $langId, $buyerId = 0)
    {
        $langId = FatUtility::int($langId);
        $buyerId = FatUtility::int($buyerId);

        $orderObj = new Orders();
        $orderComment = $orderObj->getOrderComments($langId, array("id" => $commentId, "buyer_id" => $buyerId), 1); /*1 no of records*/

        if ($orderComment && $orderComment["oshistory_customer_notified"]) {
            $msgComments = '';
            if ($orderComment['oshistory_comments'] != "") {
                $msgComments = '<br>
                    <div style="background: #f6f6f6; font-size: 14px; text-align: center; border-radius: 4px; color: #212529; padding: 20px; line-height: 24px">
                        <h5 style="font-weight: $font-weight-bold; text-transform: uppercase; color: #212529; letter-spacing: -0.2px; margin: 0">' . Labels::getLabel('LBL_COMMENTS_FOR_YOUR_ORDER', $langId) . '</h5>
                        <p style="margin: 0">' . nl2br($orderComment['oshistory_comments']) . '</p>
                    </div>
                ';
            }
            $shipmentInformation = '';
            if ($orderComment['oshistory_tracking_number'] != "") {
                $shipmentInformation = Labels::getLabel('LBL_SHIPMENT_INFORMATION', $langId) . "<br>" . Labels::getLabel('LBL_TRACKING_NUMBER', $langId) . " " . $orderComment['oshistory_tracking_number'] . " " . Labels::getLabel('LBL_VIA', $langId) . " " . $orderComment["op_shipping_duration_name"];
                if (!empty($orderComment['oshistory_tracking_url'])) {
                    $shipmentInformation .= '
                        <div class="btn-wrapper" style="text-align: center">
                            <a href="' . $orderComment['oshistory_tracking_url'] . '" target="_blank" style="
                                    border-radius: 4px;
                                    background-color: #f13925;
                                    color: #fff;
                                    font-size: 14px;
                                    letter-spacing: -0.28px;
                                    text-decoration: none;
                                    padding: 9px 20px;
                                    display: inline-block;
                                    margin-bottom: 30px;
                                ">Track Shipment</a>
                        </div>';
                }
            }

            $charges = $orderObj->getOrderProductChargesArr($orderComment['op_id']);
            $orderComment['charges'] = $charges;

            $opChargesLog = new OrderProductChargeLog($orderComment['op_id']);
            $taxOptions = $opChargesLog->getData($langId);
            $orderComment['taxOptions'] = $taxOptions;

            $tpl = new FatTemplate('', '');
            $tpl->set('orderProducts', $orderComment);
            $tpl->set('siteLangId', $langId);
            $orderItemsTableFormatHtml = $tpl->render(false, false, '_partial/emails/child-order-detail-email.php', true);
            $statuesArr = Orders::getOrderProductStatusArr($orderComment["order_language_id"]);

            $arrReplacements = array(
                '{user_full_name}' => trim($orderComment["buyer_name"]),
                '{new_order_status}' => $statuesArr[$orderComment["oshistory_orderstatus_id"]],
                '{invoice_number}' => $orderComment["op_invoice_number"],
                '{order_items_table_format}' => $orderItemsTableFormatHtml,
                '{order_admin_comments}' => $msgComments,
                '{shipment_information}' => $shipmentInformation,
            );
            if (!empty($orderComment["buyer_email"])) {
                $sendEmail = (new FatMailer($langId, 'child_order_status_change'))
                    ->setTo($orderComment["buyer_email"])
                    ->setVariables($arrReplacements)
                    ->send();
            }

            $this->sendSms("child_order_status_change", ValidateElement::formatDialCode($orderComment['buyer_phone_dcode']) . $orderComment["buyer_phone"], $arrReplacements, $langId);
            $replaceVal = array(
                '{INVOICE}' => $orderComment["op_invoice_number"],
                '{PRODUCT}' => isset($orderComment["op_selprod_title"]) && !empty($orderComment["op_selprod_title"]) ? $orderComment["op_selprod_title"] : $orderComment["op_product_name"],
                '{STATUS}' => $statuesArr[$orderComment["oshistory_orderstatus_id"]]
            );
            $appNotification = CommonHelper::replaceStringData(Labels::getLabel('MSG_YOUR_ORDER_{INVOICE}_{PRODUCT}_STATUS_{STATUS}', $langId), $replaceVal, true);

            $notificationData = array(
                'invoiceNumber' => $orderComment["op_invoice_number"],
                'productName' => $orderComment["op_product_name"],
                'status' => $statuesArr[$orderComment["oshistory_orderstatus_id"]],
                'orderId' => $orderComment["op_order_id"],
                'orderProductId' => $orderComment["op_id"],
            );

            $notificationObj = new Notifications();
            $notificationDataArr = array(
                'unotification_user_id' => $buyerId,
                'unotification_body' => $appNotification,
                'unotification_type' => 'BUYER_ORDER_STATUS',
                'unotification_data' => json_encode($notificationData),
            );
            if (!$notificationObj->addNotification($notificationDataArr)) {
                $this->error = $notificationObj->getError();
                return false;
            }

            return true;
        } else {
            $this->error = Labels::getLabel('ERR_INVALID_REQUEST', $this->commonLangId);
        }
    }

    public function orderStatusUpdateSeller($commentId, $langId, $sellerId = 0)
    {
        $langId = FatUtility::int($langId);
        $sellerId = FatUtility::int($sellerId);

        $orderObj = new Orders();
        $orderComment = $orderObj->getOrderComments($langId, array("id" => $commentId, "seller_id" => $sellerId), 1);  //1 no of records

        if ($orderComment && $orderComment["oshistory_customer_notified"]) {
            $msgComments = '';

            if ($orderComment['oshistory_comments'] != "") {
                $msgComments = Labels::getLabel('LBL_COMMENTS_FOR_YOUR_ORDER', $langId) . ":<br/><br/><em>" . $orderComment['oshistory_comments'] . ".</em><br/><br/>";
            }
            $shipmentInformation = '';
            if ($orderComment['oshistory_tracking_number'] != "") {
                $shipmentInformation = Labels::getLabel('LBL_SHIPMENT_INFORMATION', $langId) . ": " . Labels::getLabel('LBL_TRACKING_NUMBER', $langId) . " " . $orderComment['oshistory_tracking_number'] . " " . Labels::getLabel('LBL_VIA', $langId) . " " . $orderComment["op_shipping_duration_name"] . "<br/>";
            }

            $charges = $orderObj->getOrderProductChargesArr($orderComment['op_id']);
            $orderComment['charges'] = $charges;

            $opChargesLog = new OrderProductChargeLog($orderComment['op_id']);
            $taxOptions = $opChargesLog->getData($langId);
            $orderComment['taxOptions'] = $taxOptions;

            $shippingHanldedBySeller = CommonHelper::canAvailShippingChargesBySeller($orderComment['op_selprod_user_id'], $orderComment['opshipping_by_seller_user_id']);

            $tpl = new FatTemplate('', '');
            $tpl->set('orderProducts', $orderComment);
            $tpl->set('siteLangId', $langId);
            $tpl->set('shippingHanldedBySeller', $shippingHanldedBySeller);
            $tpl->set('userType', User::USER_TYPE_SELLER);
            $orderItemsTableFormatHtml = $tpl->render(false, false, '_partial/emails/child-order-detail-email-seller.php', true);
            $statuesArr = Orders::getOrderProductStatusArr($orderComment["order_language_id"]);

            $arrReplacements = array(
                '{user_full_name}' => trim($orderComment["seller_name"]),
                '{new_order_status}' => $statuesArr[$orderComment["oshistory_orderstatus_id"]],
                '{invoice_number}' => $orderComment["op_invoice_number"],
                '{order_items_table_format}' => $orderItemsTableFormatHtml,
                '{order_admin_comments}' => nl2br($msgComments),
                '{shipment_information}' => "<br/><br/>" . $shipmentInformation,
            );
            if (!empty($orderComment["seller_email"])) {
                $sendEmail = (new FatMailer($langId, 'child_order_status_change'))
                    ->setTo($orderComment["seller_email"])
                    ->setVariables($arrReplacements)
                    ->send();
            }

            $this->sendSms("child_order_status_change", ValidateElement::formatDialCode($orderComment['seller_phone_dcode']) . $orderComment["seller_phone"], $arrReplacements, $langId);
            return true;
        } else {
            $this->error = Labels::getLabel('ERR_INVALID_REQUEST', $this->commonLangId);
        }
    }

    public function sendTaxApiOrderCreationFailure($data, $langId)
    {
        $adminEmail = FatApp::getConfig("CONF_SITE_OWNER_EMAIL", FatUtility::VAR_STRING, "");
        $tpl = "taxapi_order_creation_failure";
        $defaultSiteLangId = FatApp::getConfig('conf_default_site_lang');
        $arrReplacements = array(
            '{invoice_number}' => $data['op_invoice_number'],
            '{error_message}' => $data['error_message']
        );

        $sendEmail = (new FatMailer($langId, $tpl))
            ->setTo($adminEmail)
            ->setVariables($arrReplacements)
            ->send();

        if (false == $sendEmail) {
            return false;
        }
        return true;
    }

    public function sendTxnNotification($txnId, $langId)
    {
        $langId = FatUtility::int($langId);
        $txn = new Transactions($txnId);

        $txnDetail = $txn->getAttributesWithUserInfo(0, array('utxn_credit', 'utxn_debit', 'utxn_comments', 'user_name', 'user_phone_dcode', 'user_phone', 'credential_email', 'utxn_user_id'));

        $txnAmount = $txnDetail["utxn_credit"] > 0 ? $txnDetail["utxn_credit"] : $txnDetail["utxn_debit"];
        $arrReplacements = array(
            '{user_name}' => trim($txnDetail["user_name"]),
            '{txn_id}' => Transactions::formatTransactionNumber($txnId),
            '{txn_type}' => ($txnDetail["utxn_credit"] > 0) ? Labels::getLabel('LBL_CREDITED_TO', $langId) : Labels::getLabel('LBL_DEBITED_FROM', $langId),
            '{txn_amount}' => CommonHelper::displayMoneyFormat($txnAmount),
            '{txn_comments}' => Transactions::formatTransactionComments($txnDetail["utxn_comments"]),
        );

        if (!empty($txnDetail["credential_email"])) {
            (new FatMailer($langId, 'account_credited_debited'))
                ->setTo($txnDetail["credential_email"])
                ->setVariables($arrReplacements)
                ->send();
        }
        $phone = !empty($txnDetail['user_phone']) ? ValidateElement::formatDialCode($txnDetail['user_phone_dcode']) . $txnDetail['user_phone'] : '';
        $this->sendSms("account_credited_debited", $phone, $arrReplacements, $langId);
        $notiArrReplacements = array(
            '{txnid}' => Transactions::formatTransactionNumber($txnId),
            '{txntype}' => ($txnDetail["utxn_credit"] > 0) ? Labels::getLabel('LBL_CREDITED', $langId) : Labels::getLabel('LBL_DEBITED', $langId),
            '{txnamount}' => CommonHelper::displayMoneyFormat($txnAmount, true, true),
        );

        $appNotification = CommonHelper::replaceStringData(Labels::getLabel('MSG_AMOUNT_{txnamount}_WITH_{txnid}_HAS_BEEN_{txntype}', $langId), $notiArrReplacements, true);

        $notificationObj = new Notifications();
        $notificationDataArr = array(
            'unotification_user_id' => $txnDetail["utxn_user_id"],
            'unotification_body' => $appNotification,
            'unotification_type' => 'TXN',
            'unotification_data' => json_encode(array('txnAmount' => $arrReplacements['{txn_amount}'], 'txnId' => $arrReplacements['{txn_id}'], 'txnType' => $arrReplacements['{txn_type}'])),
        );
        if (!$notificationObj->addNotification($notificationDataArr)) {
            $this->error = $notificationObj->getError();
            return false;
        }

        return true;
    }

    public function sendWithdrawRequestNotification($requestId, $langId, $adminOrUser = "A")
    {
        $langId = FatUtility::int($langId);
        $requestId = FatUtility::int($requestId);
        if (1 > $langId) {
            return 'ERR_Invalid_Lang';
        }
        $this->langId = $langId;

        $srch = new WithdrawalRequestsSearch();
        $srch->joinUsers(true);
        $srch->joinForUserBalance();
        $srch->joinTable(User::DB_TBL_USR_WITHDRAWAL_REQ_SPEC, 'LEFT JOIN', User::DB_TBL_USR_WITHDRAWAL_REQ_SPEC_PREFIX . 'withdrawal_id = tuwr.withdrawal_id');
        $srch->addMultipleFields(
            array(
                'tuwr.*',
                'GROUP_CONCAT(CONCAT(`uwrs_key`, ":", `uwrs_value`)) as payout_detail',
                'user_name',
                'credential_email as user_email',
                'credential_username as user_username',
                'user_phone_dcode',
                'user_phone'
            )
        );
        $srch->addCondition('tuwr.withdrawal_id', '=', 'mysql_func_' . $requestId, 'AND', true);
        $srch->doNotCalculateRecords();
        $srch->setPageSize(1);
        $rs = $srch->getResultSet();
        if (!$rs) {
            return 'ERR_Invalid_Access';
        }

        $withdrawalRequestData = FatApp::getDb()->fetch($rs);
        if (!$withdrawalRequestData) {
            return 'ERR_No_Record_Found';
        }

        $formattedRequestValue = "#" . str_pad($requestId, 6, '0', STR_PAD_LEFT);
        $url = UrlHelper::generateFullUrl('account', 'messages', array(), CONF_WEBROOT_URL);
        $url = '<a href="' . $url . '">' . Labels::getLabel('LBL_CLICK_HERE', $langId) . '</a>';

        $statusArr = Transactions::getWithdrawlStatusArr($langId);

        $tpl = new FatTemplate('', '');
        $tpl->set('siteLangId', $langId);
        $tpl->set('data', $withdrawalRequestData);
        $withdrawalDetailsTableFormatHtml = $tpl->render(false, false, '_partial/emails/withdrawal-request-details-email.php', true);

        $arrReplacements = array(
            '{request_id}' => $formattedRequestValue,
            '{username}' => $withdrawalRequestData['user_username'],
            '{request_amount}' => CommonHelper::displayMoneyFormat($withdrawalRequestData["withdrawal_amount"], true, true),
            '{request_bank}' => $withdrawalRequestData['withdrawal_bank'],
            '{request_account_holder}' => $withdrawalRequestData['withdrawal_account_holder_name'],
            '{request_account_number}' => $withdrawalRequestData['withdrawal_account_number'],
            '{request_ifsc_swift_number}' => $withdrawalRequestData['withdrawal_ifc_swift_code'],
            '{request_bank_address}' => $withdrawalRequestData['withdrawal_bank_address'],
            '{request_comments}' => $withdrawalRequestData['withdrawal_instructions'],
            '{request_status}' => $statusArr[$withdrawalRequestData['withdrawal_status']],
            '{withdrawal_detail_table_format_html}' => $withdrawalDetailsTableFormatHtml,
            '{user_name}' => $withdrawalRequestData['user_name'],
        );

        if ($adminOrUser == "A") {
            $this->sendMailToAdminAndAdditionalEmails("withdrawal_request_admin", $arrReplacements, static::ADD_ADDITIONAL_ALERTS, static::NOT_ONLY_SUPER_ADMIN, $langId);

            $tpl = 'withdrawal_request_admin';
            $phone = ValidateElement::formatDialCode(FatApp::getConfig('CONF_SITE_PHONE_dcode')) . FatApp::getConfig('CONF_SITE_PHONE');
        } else {
            if (!empty($withdrawalRequestData["user_email"])) {
                (new FatMailer($langId, 'withdrawal_request_approved_declined'))
                    ->setTo($withdrawalRequestData["user_email"])
                    ->setVariables($arrReplacements)
                    ->send();
            }

            $tpl = 'withdrawal_request_approved_declined';
            $phone = !empty($withdrawalRequestData['user_phone']) ? ValidateElement::formatDialCode($withdrawalRequestData['user_phone_dcode']) . $withdrawalRequestData['user_phone'] : '';
        }

        if (!empty($phone)) {
            $this->sendSms($tpl, $phone, $arrReplacements, $langId);
        }

        $notiArrReplacements = array(
            '{requestid}' => $formattedRequestValue,
            '{requestamount}' => CommonHelper::displayMoneyFormat($withdrawalRequestData["withdrawal_amount"], true, true),
            '{requeststatus}' => $statusArr[$withdrawalRequestData['withdrawal_status']],
        );
        $appNotification = CommonHelper::replaceStringData(Labels::getLabel('MSG_AMOUNT_{requestamount}_WITH_{requestid}_HAS_BEEN_{requeststatus}', $langId), $notiArrReplacements, true);

        $notificationObj = new Notifications();
        $notificationDataArr = array(
            'unotification_user_id' => $withdrawalRequestData["withdrawal_user_id"],
            'unotification_body' => $appNotification,
            'unotification_type' => 'FUNDS_WITHDRAWAL_REQUEST_CHANGED',
            'unotification_data' => json_encode(array('requestAmount' => $arrReplacements['{request_amount}'], 'requestId' => $arrReplacements['{request_id}'], 'requestStatus' => $arrReplacements['{request_status}'])),
        );
        if (!$notificationObj->addNotification($notificationDataArr)) {
            $this->error = $notificationObj->getError();
            return false;
        }

        return true;
    }

    public function sendMessageNotification($messageId, $langId)
    {
        $messageId = FatUtility::int($messageId);
        $langId = FatUtility::int($langId);

        $srch = new MessageSearch();
        $srch->joinThreadMessage();
        $srch->joinMessagePostedFromUser();
        $srch->joinMessagePostedToUser();
        $srch->addMultipleFields(array('tth.*', 'ttm.message_text', 'ttm.message_to'));
        $srch->addCondition('ttm.message_deleted', '=', 'mysql_func_' . applicationConstants::NO, 'AND', true);
        $srch->addCondition('ttm.message_id', '=', 'mysql_func_' . $messageId, 'AND', true);
        $srch->doNotCalculateRecords();
        $srch->setPageSize(1);
        $rs = $srch->getResultSet();
        $message = FatApp::getDb()->fetch($rs);
        if ($message == false || empty($message)) {
            return false;
        }
        $arrReplacements = array(
            '{user_full_name}' => $message['message_to_name'],
            '{username}' => $message['message_from_username'],
            '{message_subject}' => $message['thread_subject'] != "" ? $message['thread_subject'] : "-NA-",
            '{message}' => nl2br($message['message_text']),
            '{click_here}' => UrlHelper::generateFullUrl('account', 'viewMessages', array($message['thread_id'], $messageId), CONF_WEBROOT_FRONT_URL),
        );

        $sendEmail = (new FatMailer($langId, 'send_message'))
            ->setTo($message["message_to_email"])
            ->setVariables($arrReplacements)
            ->send();

        $uData = User::getAttributesById($message["message_to"], ['user_phone_dcode', 'user_phone']);
        if (!empty($uData)) {
            $phone = ValidateElement::formatDialCode($uData['user_phone_dcode']) . $uData['user_phone'];
            $this->sendSms("send_message", $phone, $arrReplacements, $langId);
        }

        $notificationObj = new Notifications();
        $notificationDataArr = array(
            'unotification_user_id' => $message["message_to"],
            'unotification_body' => CommonHelper::replaceStringData(Labels::getLabel('MSG_YOU_HAVE_A_NEW_MESSAGE_FROM_{username}', $langId), array('{username}' => $message['message_from_username'])),
            'unotification_type' => 'MESSAGE',
            'unotification_data' => json_encode(array('username' => $message['message_from_username'], 'threadId' => $message['thread_id'], 'messageId' => $messageId)),
        );
        if (!$notificationObj->addNotification($notificationDataArr)) {
            $this->error = $notificationObj->getError();
            return false;
        }

        return true;
    }

    public function sendOrderCancellationNotification($ocrequest_id, $langId)
    {
        $ocRequestSrch = new OrderCancelRequestSearch();
        $ocRequestSrch->doNotCalculateRecords();
        $ocRequestSrch->doNotLimitRecords();
        $ocRequestSrch->joinOrderProducts();
        $ocRequestSrch->joinOrderSellerUser();
        $ocRequestSrch->joinOrders($langId);
        $ocRequestSrch->joinOrderBuyerUser();
        //$ocRequestSrch->joinShops();
        $ocRequestSrch->joinOrderCancelReasons($langId);
        $ocRequestSrch->addCondition('ocrequest_id', '=', 'mysql_func_' . $ocrequest_id, 'AND', true);
        $ocRequestSrch->addMultipleFields(array('order_id', 'op_id', 'op_invoice_number', 'op_shop_owner_name', 'op_shop_owner_phone_dcode', 'op_shop_owner_phone', 'op_shop_owner_email', 'IFNULL(ocreason_title, ocreason_identifier) as ocreason_title', 'ocrequest_message', 'seller.user_id as seller_id', 'COALESCE(buyer.user_name, buyer_cred.credential_username) as username'));
        $ocRequestSrch->doNotCalculateRecords();
        $ocRequestSrch->setPageSize(1);
        $ocRequestRow = FatApp::getDb()->fetch($ocRequestSrch->getResultSet());
        if (!$ocRequestRow) {
            $this->error = Labels::getLabel('ERR_INVALID_REQUEST', $this->commonLangId);
            return false;
        }

        $sellerOrderDetailUrl = UrlHelper::generateFullUrl('Seller', 'ViewOrder', array($ocRequestRow["op_id"]), CONF_WEBROOT_DASHBOARD, null, false, false, false);
        $sellerOrderAnchor = "<a href='" . $sellerOrderDetailUrl . "'>" . $ocRequestRow["op_invoice_number"] . "</a>";

        $arrReplacements = array(
            '{user_name}' => $ocRequestRow['op_shop_owner_name'],
            '{username}' => $ocRequestRow['username'],  /* Buyer User Name. */
            '{invoice_number}' => $sellerOrderAnchor,
            '{cancel_reason}' => $ocRequestRow['ocreason_title'],
            '{cancel_comments}' => nl2br($ocRequestRow['ocrequest_message']),
        );
        $tpl = "order_cancellation_notification";

        $receipentsInfo = User::getSubUsersReceipents($ocRequestRow["seller_id"], 'canViewCancellationRequests');
        $bccEmails = $receipentsInfo['email'];

        $mailerObj = (new FatMailer($langId, $tpl))
            ->setTo($ocRequestRow["op_shop_owner_email"])
            ->setVariables($arrReplacements);

        foreach ($bccEmails as $emailId => $emailName) {
            $mailerObj->addBcc($emailId, $emailName);
        }
        $mailerObj->send();

        $phoneNumbers = $receipentsInfo['phone'];
        $phoneNumbers[] = ValidateElement::formatDialCode($ocRequestRow["op_shop_owner_phone_dcode"]) . $ocRequestRow["op_shop_owner_phone"];
        foreach ($phoneNumbers as $phone) {
            $arrReplacements['{invoice_number}'] = $sellerOrderDetailUrl;
            $this->sendSms($tpl, $phone, $arrReplacements, $langId);
        }

        $adminOrderDetailUrl = UrlHelper::generateFullUrl('Orders', 'view', array($ocRequestRow["order_id"]), CONF_WEBROOT_BACKEND);
        $adminOrderAnchor = "<a href='" . $adminOrderDetailUrl . "'>" . $ocRequestRow["op_invoice_number"] . "</a>";
        $arrReplacements['{invoice_number}'] = $adminOrderAnchor;

        $arrReplacements["{user_name}"] = Labels::getLabel("LBL_ADMIN", $langId);

        $this->sendMailToAdminAndAdditionalEmails($tpl, $arrReplacements, static::ADD_ADDITIONAL_ALERTS, static::NOT_ONLY_SUPER_ADMIN, $langId);

        $arrReplacements['{invoice_number}'] = $adminOrderDetailUrl;
        $this->sendSms($tpl, ValidateElement::formatDialCode(FatApp::getConfig('CONF_SITE_PHONE_dcode')) . FatApp::getConfig('CONF_SITE_PHONE'), $arrReplacements, $langId);

        $appNotification = CommonHelper::replaceStringData(Labels::getLabel('INV_RECEIVED_CANCELLATION_FOR_INVOICE_{invoicenumber}', $langId), array('{invoicenumber}' => $sellerOrderAnchor), true);

        $notificationObj = new Notifications();
        $notificationDataArr = array(
            'unotification_user_id' => $ocRequestRow["seller_id"],
            'unotification_body' => $appNotification,
            'unotification_type' => 'ORDER_CANCELLATION_REQUEST',
            'unotification_data' => json_encode(array('invoiceNumber' => $ocRequestRow["op_invoice_number"])),
        );
        if (!$notificationObj->addNotification($notificationDataArr)) {
            $this->error = $notificationObj->getError();
            return false;
        }

        return true;
    }

    public function sendOrderReturnRequestNotification($orrmsg_id, $langId)
    {
        $langId = FatUtility::int($langId);
        $orrmsg_id = FatUtility::int($orrmsg_id);

        if (!$langId) {
            trigger_error(Labels::getLabel('ERR_LANGUAGE_ID_NOT_SPECIFIED.', $this->commonLangId), E_USER_ERROR);
        }

        if (!$orrmsg_id) {
            trigger_error(Labels::getLabel('ERR_MESSAGE_ID_NOT_SPECIFIED.', $this->commonLangId), E_USER_ERROR);
        }

        $srch = new OrderReturnRequestMessageSearch();
        $srch->joinOrderReturnRequests();
        $srch->joinOrderProducts($langId);
        $srch->joinOrders($langId);
        $srch->joinOrderBuyerUser();
        $srch->joinReturnReason($langId);
        $srch->doNotCalculateRecords();
        $srch->doNotLimitRecords();
        $srch->addCondition('orrmsg_id', '=', 'mysql_func_' . $orrmsg_id, 'AND', true);
        $srch->addMultipleFields(
            array(
                'op_selprod_id',
                'op_selprod_user_id',
                'op_is_batch',
                'orrmsg_orrequest_id',
                'op_product_name',
                'op_selprod_title',
                'op_shop_owner_name',
                'buyer_cred.credential_username as buyer_username',
                'orrequest_qty',
                'orrequest_type',
                'orrequest_reference',
                'IFNULL(orreason_title, orreason_identifier) as orreason_title',
                'orrmsg_msg',
                'op_shop_owner_email',
                'op_shop_owner_phone_dcode',
                'op_shop_owner_phone',
                'op_selprod_options',
                'op_brand_name',
                'op_invoice_number',
                'orrequest_user_id'
            )
        );
        $rs = $srch->getResultSet();
        if (!$msgDetail = FatApp::getDb()->fetch($rs)) {
            $this->error = Labels::getLabel('ERR_INVALID_REQUEST', $this->commonLangId);
            return false;
        }

        if ($msgDetail['op_is_batch']) {
            $productUrl = UrlHelper::generateFullUrl('Products', 'batch', array($msgDetail['op_selprod_id']));
        } else {
            $productUrl = UrlHelper::generateFullUrl('Products', 'view', array($msgDetail['op_selprod_id']));
        }

        $productTitle = ($msgDetail['op_selprod_title'] != '') ? $msgDetail['op_selprod_title'] : $msgDetail['op_product_name'];

        $productExtraDetails = '';
        if ($msgDetail['op_selprod_options'] != '') {
            $productExtraDetails .= '<br/>' . $msgDetail['op_selprod_options'];
        }

        if ($msgDetail['op_brand_name'] != '') {
            $productExtraDetails .= '<br/>' . Labels::getLabel('LBL_BRAND', $langId) . ': ' . $msgDetail['op_brand_name'];
        }

        $returnRequestArr = OrderReturnRequest::getRequestTypeArr($langId);
        $returnRequestTypeName = $returnRequestArr[$msgDetail['orrequest_type']];

        $prodTitleAnchor = "<a href='" . $productUrl . "'>" . $productTitle . "</a>" . $productExtraDetails;

        $arrReplacements = array(
            '{user_name}' => $msgDetail['op_shop_owner_name'],
            '{username}' => $msgDetail['buyer_username'],
            '{child_order_invoice_number}' => $msgDetail['op_invoice_number'],
            '{return_prod_title}' => $prodTitleAnchor,
            '{return_request_id}' => $msgDetail['orrequest_reference'], /* CommonHelper::formatOrderReturnRequestNumber( $msgDetail['orrmsg_orrequest_id'] ), */
            '{return_qty}' => $msgDetail['orrequest_qty'],
            '{return_request_type}' => $returnRequestTypeName,
            '{return_reason}' => $msgDetail['orreason_title'],
            '{return_comments}' => nl2br($msgDetail['orrmsg_msg']),
        );

        $receipentsInfo = User::getSubUsersReceipents($msgDetail['op_selprod_user_id'], 'canViewReturnRequests');
        $bccEmails = $receipentsInfo['email'];
        $mailerObj = (new FatMailer($langId, 'product_return'))
            ->setTo($msgDetail["op_shop_owner_email"])
            ->setVariables($arrReplacements);

        foreach ($bccEmails as $emailId => $emailName) {
            $mailerObj->addBcc($emailId, $emailName);
        }
        $mailerObj->send();

        $phoneNumbers = $receipentsInfo['phone'];
        $phoneNumbers[] = $msgDetail["op_shop_owner_phone_dcode"] . $msgDetail["op_shop_owner_phone"];
        foreach ($phoneNumbers as $phone) {
            $this->sendSms("product_return", $phone, $arrReplacements, $langId);
        }
        /*         * ** Notification For Seller ********** */

        $notiArrReplacements = array(
            '{username}' => $msgDetail['buyer_username'],
            '{returnrequestid}' => $msgDetail['orrequest_reference'],
        );

        $appNotification = CommonHelper::replaceStringData(Labels::getLabel('INV_RECEIVED_RETURN_FROM_{username}_WITH_REFERENCE_NUMBER_{returnrequestid}', $langId), $notiArrReplacements, true);

        $notificationObj = new Notifications();
        $notificationDataArr = array(
            'unotification_user_id' => $msgDetail['op_selprod_user_id'],
            'unotification_body' => $appNotification,
            'unotification_type' => 'SELLER_RETURN_REQUEST',
            'unotification_data' => json_encode(array('username' => $msgDetail['buyer_username'], 'returnRequestId' => $arrReplacements['{return_request_id}'])),
        );
        if (!$notificationObj->addNotification($notificationDataArr)) {
            $this->error = $notificationObj->getError();
            return false;
        }
        /*         * ** End Notification For Seller ********** */


        /*         * ** Notification For Buyer ********** */
        $notiArrReplacements = array(
            '{returnprodtitle}' => $prodTitleAnchor,
            '{returnrequestid}' => $msgDetail['orrequest_reference'],
        );
        $appNotification = CommonHelper::replaceStringData(Labels::getLabel('MSG_RETURN_FOR_{returnprodtitle}_with_{returnrequestid}_SUBMITTED', $langId), $notiArrReplacements, true);

        $notificationDataArr = array(
            'unotification_user_id' => $msgDetail['orrequest_user_id'],
            'unotification_body' => $appNotification,
            'unotification_type' => 'BUYER_RETURN_REQUEST',
        );
        if (!$notificationObj->addNotification($notificationDataArr)) {
            $this->error = $notificationObj->getError();
            return false;
        }
        /*         * ** End Notification For Buyer ********** */


        $arrReplacements["{user_name}"] = Labels::getLabel("LBL_ADMIN", $langId);

        $this->sendMailToAdminAndAdditionalEmails("product_return", $arrReplacements, static::ADD_ADDITIONAL_ALERTS, static::NOT_ONLY_SUPER_ADMIN, $langId);
        $this->sendSms("product_return", ValidateElement::formatDialCode(FatApp::getConfig('CONF_SITE_PHONE_dcode')) . FatApp::getConfig('CONF_SITE_PHONE'), $arrReplacements, $langId);
        return true;
    }

    public function sendReturnRequestMessageNotification($orrmsg_id, $langId)
    {
        $langId = FatUtility::int($langId);
        $orrmsg_id = FatUtility::int($orrmsg_id);
        if (!$langId) {
            trigger_error(Labels::getLabel('ERR_LANGUAGE_ID_NOT_SPECIFIED.', $this->commonLangId), E_USER_ERROR);
        }
        if (!$orrmsg_id) {
            trigger_error(Labels::getLabel('ERR_MESSAGE_ID_NOT_SPECIFIED.', $this->commonLangId), E_USER_ERROR);
        }

        $srch = new OrderReturnRequestMessageSearch();
        $srch->joinOrderReturnRequests();
        $srch->joinOrderProducts($langId);
        $srch->joinOrders($langId);
        $srch->joinOrderBuyerUser();
        $srch->joinMessageAdmin();
        $srch->joinReturnReason($langId);
        $srch->doNotCalculateRecords();
        $srch->doNotLimitRecords();
        $srch->addCondition('orrmsg_id', '=', 'mysql_func_' . $orrmsg_id, 'AND', true);
        $srch->addMultipleFields(
            array(
                'op_selprod_id',
                'op_is_batch',
                'op_product_name',
                'op_selprod_title',
                'op_shop_owner_name',
                'op_shop_owner_username',
                'op_shop_owner_email',
                'op_shop_owner_phone_dcode',
                'op_shop_owner_phone',
                'op_selprod_user_id',
                'buyer_cred.credential_username as buyer_username',
                'buyer_cred.credential_email as buyer_email',
                'orrequest_id',
                'orrequest_qty',
                'orrequest_reference',
                'orrequest_type',
                'orrequest_user_id',
                'orrmsg_from_user_id',
                'IFNULL(orreason_title, orreason_identifier) as orreason_title',
                'orrmsg_msg',
                'orrequest_status',
                'buyer.user_name as buyer_name',
                'buyer.user_phone_dcode as buyer_phone_dcode',
                'buyer.user_phone as buyer_phone',
                'buyer.user_id as buyer_id',
                'op_selprod_user_id as seller_id',
                'orrmsg_from_admin_id',
                'admin_name',
                'admin_username'
            )
        );
        $rs = $srch->getResultSet();
        if (!$msgDetail = FatApp::getDb()->fetch($rs)) {
            $this->error = Labels::getLabel('ERR_INVALID_REQUEST', $this->commonLangId);
            return false;
        }

        /* Buyer Notification [ */
        $arrReplacements = array(
            '{username}' => FatApp::getConfig('CONF_WEBSITE_NAME_' . $langId),
            '{request_number}' => $msgDetail["orrequest_reference"], /* CommonHelper::formatOrderReturnRequestNumber($msgDetail['orrequest_id']), */
            '{message}' => nl2br($msgDetail["orrmsg_msg"]),
            '{user_full_name}' => $msgDetail["buyer_name"],
            '{click_here}' => UrlHelper::generateFullUrl('Buyer', 'ViewOrderReturnRequest', array($msgDetail['orrequest_id']), CONF_WEBROOT_DASHBOARD, null, false, false, false),
            '{prod_title}' => $msgDetail['op_selprod_title'],
        );

        if ($msgDetail["orrequest_user_id"] != $msgDetail["orrmsg_from_user_id"]) {
            $arrReplacements["{user_full_name}"] = $msgDetail["buyer_name"];

            $arrReplacements["{username}"] = $msgDetail['op_shop_owner_name'];

            if ($msgDetail['orrmsg_from_admin_id']) {
                $arrReplacements["{username}"] = FatApp::getConfig('CONF_WEBSITE_NAME_' . $langId);
            }
            if (!empty($msgDetail["buyer_email"])) {
                (new FatMailer($langId, 'return_request_message_user'))
                    ->setTo($msgDetail["buyer_email"])
                    ->setVariables($arrReplacements)
                    ->send();
            }

            $this->sendSms("return_request_message_user", ValidateElement::formatDialCode($msgDetail['buyer_phone_dcode']) . $msgDetail['buyer_phone'], $arrReplacements, $langId);
        }
        /* ] */


        /* Vendor Notification [ */
        if ($msgDetail["op_selprod_user_id"] != $msgDetail["orrmsg_from_user_id"]) {
            $arrReplacements["{user_full_name}"] = $msgDetail["op_shop_owner_name"];
            $arrReplacements["{username}"] = $msgDetail["buyer_username"];
            if ($msgDetail['orrmsg_from_admin_id']) {
                $arrReplacements["{username}"] = FatApp::getConfig('CONF_WEBSITE_NAME_' . $langId);
            }
            $arrReplacements['{click_here}'] = UrlHelper::generateFullUrl('Seller', 'ViewOrderReturnRequest', array($msgDetail['orrequest_id']), CONF_WEBROOT_DASHBOARD, null, false, false, false);
            /* if ($return_request['refmsg_from_type']=="U"){
              $arr_replacements["{username}"] = $return_request["message_sent_by_username"];
              } */

            $receipentsInfo = User::getSubUsersReceipents($msgDetail["seller_id"], 'canViewReturnRequests');
            $bccEmails = $receipentsInfo['email'];

            if (!empty($msgDetail["op_shop_owner_email"])) {
                $mailerObj = (new FatMailer($langId, 'return_request_message_user'))
                    ->setTo($msgDetail["op_shop_owner_email"])
                    ->setVariables($arrReplacements);

                foreach ($bccEmails as $emailId => $emailName) {
                    $mailerObj->addBcc($emailId, $emailName);
                }
                $mailerObj->send();
            }

            $phoneNumbers = $receipentsInfo['phone'];
            $phoneNumbers[] = ValidateElement::formatDialCode($msgDetail['op_shop_owner_phone_dcode']) . $msgDetail['op_shop_owner_phone'];
            foreach ($phoneNumbers as $phone) {
                $this->sendSms("return_request_message_user", $phone, $arrReplacements, $langId);
            }
            $notification_user_id = $msgDetail["seller_id"];

            $notiArrReplacements = array(
                '{username}' => FatApp::getConfig('CONF_WEBSITE_NAME_' . $langId),
                '{requestnumber}' => $msgDetail["orrequest_reference"],
            );

            $appNotification = CommonHelper::replaceStringData(Labels::getLabel('MSG_NEW_MESSAGE_POSTED_BY_{username}_ON_RETURN_{requestnumber}', $langId), $notiArrReplacements, true);

            $notificationObj = new Notifications();
            $notificationDataArr = array(
                'unotification_user_id' => $notification_user_id,
                'unotification_body' => $appNotification,
                'unotification_type' => 'MESSAGE_RETURN_REQUEST',
                'unotification_data' => json_encode(array('username' => $msgDetail["buyer_username"], 'requestNumber' => $msgDetail["orrequest_reference"])),
            );
            if (!$notificationObj->addNotification($notificationDataArr)) {
                $this->error = $notificationObj->getError();
                return false;
            }
        }
        /* ] */



        /* To Admin[ */
        if ($msgDetail['orrequest_status'] == OrderReturnRequest::RETURN_REQUEST_STATUS_ESCALATED) {
            $adminReturnRequestUrl = CommonHelper::getAdminUrl('OrderReturnRequests', 'View', array($msgDetail['orrequest_id']));
            $adminReturnRequestUrl = '<a href="' . $adminReturnRequestUrl . '">' . Labels::getLabel('LBL_CLICK_HERE', $langId) . '</a>';
            $arrReplacements["{user_full_name}"] = "Admin";
            $arrReplacements["{click_here}"] = $adminReturnRequestUrl;

            $this->sendMailToAdminAndAdditionalEmails("return_request_message_user", $arrReplacements, static::ADD_ADDITIONAL_ALERTS, static::NOT_ONLY_SUPER_ADMIN, $langId);
            $this->sendSms("return_request_message_user", ValidateElement::formatDialCode(FatApp::getConfig('CONF_SITE_PHONE_dcode')) . FatApp::getConfig('CONF_SITE_PHONE'), $arrReplacements, $langId);
        }
        /* ] */
        return true;
    }

    public function sendCatalogRequestMessageNotification($scatrequestmsg_id, $langId)
    {
        $langId = FatUtility::int($langId);
        $scatrequestmsg_id = FatUtility::int($scatrequestmsg_id);
        if (!$langId) {
            trigger_error(Labels::getLabel('ERR_LANGUAGE_ID_NOT_SPECIFIED.', $this->commonLangId), E_USER_ERROR);
        }
        if (!$scatrequestmsg_id) {
            trigger_error(Labels::getLabel('ERR_MESSAGE_ID_NOT_SPECIFIED.', $this->commonLangId), E_USER_ERROR);
        }

        $srch = new CatalogRequestMessageSearch();
        $srch->joinCatalogRequests();
        $srch->joinMessageUser();
        $srch->joinMessageAdmin();
        $srch->joinReceiverUser();
        $srch->doNotCalculateRecords();
        $srch->doNotLimitRecords();
        $srch->addCondition('scatrequestmsg_id', '=', 'mysql_func_' . $scatrequestmsg_id, 'AND', true);
        $srch->addMultipleFields(
            array(
                'scatrequestmsg_from_user_id',
                'scatrequestmsg_msg',
                'scatrequest_status',
                'scatrequest_id',
                'scatrequest_user_id',
                'scatrequestmsg_from_admin_id',
                'admin_name',
                'admin_username',
                'receiver_user.user_name',
                'receiver_user_cred.credential_email'
            )
        );
        $rs = $srch->getResultSet();
        if (!$msgDetail = FatApp::getDb()->fetch($rs)) {
            $this->error = Labels::getLabel('ERR_INVALID_REQUEST', $this->commonLangId);
            return false;
        }
        $requestDetailUrl = UrlHelper::generateFullUrl('Seller', 'requestedCatalog', array(), CONF_WEBROOT_FRONT_URL);
        $requestDetailUrl = '<a href="' . $requestDetailUrl . '">' . Labels::getLabel('LBL_CLICK_HERE', $langId) . '</a>';

        /* Buyer Notification [ */
        $arrReplacements = array(
            '{username}' => FatApp::getConfig('CONF_WEBSITE_NAME_' . $langId),
            '{message}' => nl2br($msgDetail["scatrequestmsg_msg"]),
            '{user_full_name}' => $msgDetail["user_name"],
            '{click_here}' => $requestDetailUrl,
        );

        if ($msgDetail["scatrequest_user_id"] != $msgDetail["scatrequestmsg_from_user_id"]) {
            $arrReplacements["{user_full_name}"] = $msgDetail["user_name"];

            if ($msgDetail['scatrequestmsg_from_admin_id']) {
                $arrReplacements["{username}"] = FatApp::getConfig('CONF_WEBSITE_NAME_' . $langId);
            }
            if (!empty($msgDetail["credential_email"])) {
                (new FatMailer($langId, 'catalog_request_message_user'))
                    ->setTo($msgDetail["credential_email"])
                    ->setVariables($arrReplacements)
                    ->send();
            }
        }
        /* ] */


        /* To Admin[ */

        $adminCatRequestUrl = CommonHelper::getAdminUrl('Users', 'sellerCatalogRequests');
        $adminCatRequestUrl = '<a href="' . $adminCatRequestUrl . '">' . Labels::getLabel('LBL_CLICK_HERE', $langId) . '</a>';
        $arrReplacements["{user_full_name}"] = "Admin";
        $arrReplacements["{click_here}"] = $adminCatRequestUrl;

        $this->sendMailToAdminAndAdditionalEmails("catalog_request_message_user", $arrReplacements, static::ADD_ADDITIONAL_ALERTS, static::NOT_ONLY_SUPER_ADMIN, $langId);

        /* ] */
        return true;
    }

    public function sendOrderReturnRequestStatusChangeNotification($orrequest_id, $langId)
    {
        $orrequest_id = FatUtility::int($orrequest_id);
        $langId = FatUtility::int($langId);
        if (!$orrequest_id || !$langId) {
            trigger_error(Labels::getLabel('ERR_INVALID_ARGUMENT_PASSED.', $this->commonLangId), E_USER_ERROR);
        }
        $db = FatApp::getDb();
        $srch = new OrderReturnRequestSearch();
        $srch->joinOrderProducts();
        $srch->joinOrders();
        $srch->joinOrderBuyerUser();
        $srch->joinOrderSellerUser();
        $srch->doNotCalculateRecords();
        $srch->doNotLimitRecords();
        $srch->addCondition('orrequest_id', '=', 'mysql_func_' . $orrequest_id, 'AND', true);
        $srch->addMultipleFields(
            array(
                'orrequest_id',
                'orrequest_user_id',
                'orrequest_status',
                'orrequest_reference',
                'buyer.user_name as buyer_name',
                'buyer_cred.credential_email as buyer_email',
                'op_selprod_user_id',
                'seller.user_name as seller_name',
                'seller_cred.credential_email as seller_email'
            )
        );
        $rs = $srch->getResultSet();
        $request = $db->fetch($rs);
        if (!$request) {
            $this->error = Labels::getLabel(Labels::getLabel('ERR_INVALID_REQUEST', $this->commonLangId), $langId);
            return false;
        }

        $msgSrch = new OrderReturnRequestMessageSearch();
        $msgSrch->joinMessageUser();
        $msgSrch->joinMessageAdmin();
        $msgSrch->joinOrderReturnRequests();
        $msgSrch->doNotCalculateRecords();
        $msgSrch->addCondition('orrmsg_orrequest_id', '=', 'mysql_func_' . $orrequest_id, 'AND', true);
        $msgSrch->addOrder('orrmsg_id', 'DESC');
        $msgSrch->setPageNumber(1);
        $msgSrch->setPageSize(1);
        $msgSrch->addMultipleFields(
            array(
                'orrmsg_id',
                'orrmsg_from_user_id',
                'user_name',
                'user_phone_dcode',
                'user_phone',
                'orrmsg_from_admin_id',
                'admin_name',
                'admin_username'
            )
        );
        $msgRs = $msgSrch->getResultSet();
        $lastMsgRow = $db->fetch($msgRs);

        $arrReplacements = array(
            '{username}' => $lastMsgRow['user_name'],
            '{request_number}' => $request['orrequest_reference'], /* CommonHelper::formatOrderReturnRequestNumber( $request['orrequest_id'] ), */
            '{user_full_name}' => $request["buyer_name"],
            '{new_status_name}' => OrderReturnRequest::getRequestStatusArr($langId)[$request['orrequest_status']],
        );

        if ($lastMsgRow['orrmsg_from_admin_id']) {
            $arrReplacements['{username}'] = FatApp::getConfig('CONF_WEBSITE_NAME_' . $langId);
        }

        if ($lastMsgRow && $lastMsgRow['orrmsg_from_user_id'] != $request['orrequest_user_id']) {
            if (!empty($request["buyer_email"])) {
                $sendEmail = (new FatMailer($langId, 'return_request_status_change_notification'))
                    ->setTo($request["buyer_email"])
                    ->setVariables($arrReplacements)
                    ->send();
            }
        }

        if ($lastMsgRow && $lastMsgRow["orrmsg_from_user_id"] != $request["op_selprod_user_id"]) {
            $arrReplacements["{user_full_name}"] = $request["seller_name"];
            if (!empty($request["seller_email"])) {
                $sendEmail = (new FatMailer($langId, 'return_request_status_change_notification'))
                    ->setTo($request["seller_email"])
                    ->setVariables($arrReplacements)
                    ->send();
            }
        }
        $phone = !empty($lastMsgRow['user_phone']) ? ValidateElement::formatDialCode($lastMsgRow['user_phone_dcode']) . $lastMsgRow['user_phone'] : '';
        $this->sendSms("return_request_status_change_notification", $phone, $arrReplacements, $langId);

        /* code to send emails to admin accordingly and below code is not handled[  */
        if ($lastMsgRow['orrmsg_from_user_id'] > 0) {
            $arrReplacements["{user_full_name}"] = "Admin";

            $this->sendMailToAdminAndAdditionalEmails("return_request_status_change_notification", $arrReplacements, static::ADD_ADDITIONAL_ALERTS, static::NOT_ONLY_SUPER_ADMIN, $langId);
            $this->sendSms("return_request_status_change_notification", ValidateElement::formatDialCode(FatApp::getConfig('CONF_SITE_PHONE_dcode')) . FatApp::getConfig('CONF_SITE_PHONE'), $arrReplacements, $langId);
        }
        /* ] */
        return true;
        /* global $return_status_arr;
          $p=new Products();
          $return_request=$p->getReturnRequest($return_request);
          $last_updated_by=$return_request["last_updated_by"]!=""?$return_request["last_updated_by"]:Settings::getSetting("CONF_WEBSITE_NAME");
          if ($return_request){
          $arr_replacements = array(
          '{site_domain}' => CONF_SERVER_PATH,
          '{website_name}' => Settings::getSetting("CONF_WEBSITE_NAME"),
          '{username}' => $last_updated_by,
          '{request_number}' => format_return_request_number($return_request['refund_id']),
          '{user_full_name}' => $return_request["buyer_name"],
          '{new_status_name}' => $return_status_arr[$return_request["refund_request_status"]],
          );

          if ($return_request['refmsg_from_type']=="A") {
          $arr_replacements["{username}"]=Settings::getSetting("CONF_WEBSITE_NAME");
          }

          if ($return_request["refund_request_updated_by"]!=$return_request["refund_user_id"]){
          sendMailTpl($return_request["buyer_email"], "return_request_status_change_notification",$arr_replacements);
          }

          if ($return_request["refund_request_updated_by"]!=$return_request["shop_user_id"]){
          $arr_replacements["{user_full_name}"]=$return_request["vendor_name"];
          sendMailTpl($return_request["opr_shop_owner_email"], "return_request_status_change_notification",$arr_replacements);
          }

          if ($return_request['refund_request_action_by']=="U") {
          $arr_replacements["{user_full_name}"]="Admin";
          sendMailTpl(Settings::getSetting("CONF_ADMIN_EMAIL"), "return_request_status_change_notification", $arr_replacements);
          $emails = explode(',', Settings::getSetting("CONF_ADDITIONAL_ALERT_EMAILS",FatUtility::VAR_STRING,''));
          foreach ($emails as $email) {
          if (mb_strlen($email) > 0 && preg_match('/^[^\@]+@.*.[a-z]{2,15}$/i', $email)) {
          sendMailTpl($email, "return_request_status_change_notification", $langId, $arr_replacements);
          }
          }

          }
          return true;
          } else{
          $this->error = getLabel('M_INVALID_REQUEST');
          } */
    }

    public function sendOrderCancellationRequestUpdateNotification($ocrequest_id, $langId)
    {
        $ocrequest_id = FatUtility::int($ocrequest_id);
        $langId = FatUtility::int($langId);
        if (!$ocrequest_id || !$langId) {
            trigger_error(Labels::getLabel('ERR_INVALID_ARGUMENT_PASSED.', $this->commonLangId), E_USER_ERROR);
        }

        $srch = new OrderCancelRequestSearch();
        $srch->joinOrderProducts();
        $srch->joinOrders();
        $srch->joinOrderBuyerUser();
        $srch->addCondition('ocrequest_id', '=', 'mysql_func_' . $ocrequest_id, 'AND', true);
        $srch->doNotCalculateRecords();
        $srch->doNotLimitRecords();
        $srch->addMultipleFields(
            array(
                'ocrequest_id',
                'ocrequest_op_id',
                'ocrequest_ocreason_id',
                'ocrequest_status',
                'op_invoice_number',
                'buyer.user_name as buyer_name',
                'buyer.user_phone_dcode as buyer_phone_dcode',
                'buyer.user_phone as buyer_phone',
                'buyer_cred.credential_email as buyer_email',
                'buyer.user_id as buyer_id'
            )
        );
        $rs = $srch->getResultSet();
        $row = FatApp::getDb()->fetch($rs);
        if (!$row) {
            $this->error = Labels::getLabel('ERR_INVALID_REQUEST', $this->commonLangId);
            return false;
        }
        $arrReplacements = array(
            '{invoice_number}' => $row["op_invoice_number"],
            '{request_status}' => OrderCancelRequest::getRequestStatusArr($langId)[$row['ocrequest_status']],
            '{user_name}' => $row['buyer_name'],
        );
        if (!empty($row['buyer_email'])) {
            (new FatMailer($langId, 'cancellation_request_approved_declined'))
                ->setTo($row['buyer_email'])
                ->setVariables($arrReplacements)
                ->send();
        }

        $this->sendSms("cancellation_request_approved_declined", ValidateElement::formatDialCode($row['buyer_phone_dcode']) . $row['buyer_phone'], $arrReplacements, $langId);
        $notiArrReplacements = array(
            '{invoicenumber}' => $row["op_invoice_number"],
            '{requeststatus}' => OrderCancelRequest::getRequestStatusArr($langId)[$row['ocrequest_status']],
        );

        $appNotification = CommonHelper::replaceStringData(Labels::getLabel('MSG_STATUS_FOR_CANCELLATION_{invoicenumber}_UPDATED_{requeststatus}', $langId), $notiArrReplacements, true);

        $notificationObj = new Notifications();
        $notificationDataArr = array(
            'unotification_user_id' => $row["buyer_id"],
            'unotification_body' => $appNotification,
            'unotification_type' => 'CANCELLATION_REQUEST_STATUS',
            'unotification_data' => json_encode(array('invoiceNumber' => $row["op_invoice_number"], 'requestStatus' => $arrReplacements["{request_status}"])),
        );
        if (!$notificationObj->addNotification($notificationDataArr)) {
            $this->error = $notificationObj->getError();
            return false;
        }

        return true;
    }

    public function sendShopReportNotification($sreport_id, $langId)
    {
        $sreport_id = FatUtility::int($sreport_id);
        $langId = FatUtility::int($langId);
        if (!$sreport_id || !$langId) {
            trigger_error(Labels::getLabel('ERR_INVALID_ARGUMENT_PASSED.', $this->commonLangId), E_USER_ERROR);
        }

        $srch = new ShopReportSearch();
        $srch->doNotCalculateRecords();
        $srch->joinUser();
        $srch->joinShops($langId);
        $srch->addCondition('sreport_id', '=', 'mysql_func_' . $sreport_id, 'AND', true);
        $srch->addMultipleFields(
            array(
                'sreport_id',
                'sreport_reportreason_id',
                'IFNULL(shop_name, shop_identifier) as shop_name',
                'credential_username',
                'sreport_message'
            )
        );
        $rs = $srch->getResultSet();
        $row = FatApp::getDb()->fetch($rs);
        if (!$row) {
            $this->error = Labels::getLabel('ERR_INVALID_REQUEST', $this->commonLangId);
            return false;
        }

        $arrReportReasons = ShopReportReason::getReportReasonArr($langId);
        $arrReplacements = array(
            '{username}' => $row['credential_username'],
            '{shop_name}' => $row['shop_name'],
            '{report_reason}' => $arrReportReasons[$row['sreport_reportreason_id']],
            '{report_message}' => nl2br($row['sreport_message']),
        );

        $this->sendMailToAdminAndAdditionalEmails("report_shop", $arrReplacements, static::ADD_ADDITIONAL_ALERTS, static::NOT_ONLY_SUPER_ADMIN, $langId);
        $this->sendSms("report_shop", ValidateElement::formatDialCode(FatApp::getConfig('CONF_SITE_PHONE_dcode')) . FatApp::getConfig('CONF_SITE_PHONE'), $arrReplacements, $langId);
        return true;
    }

    public function sendBlogContributionStatusChangeEmail($langId, $d)
    {
        $tpl = 'blog_contribution_status_changed';
        $statusArr = BlogContribution::getBlogContributionStatusArr(FatApp::getConfig('CONF_ADMIN_DEFAULT_LANG'));
        $vars = array(
            '{user_full_name}' => $d['bcontributions_author_first_name'],
            '{new_status}' => $statusArr[$d['bcontributions_status']],
            '{posted_on_datetime}' => $d['bcontributions_added_on'],
        );
        $sendEmail = false;
        if (!empty($d['bcontributions_author_email'])) {
            $sendEmail = (new FatMailer($langId, $tpl))
                ->setTo($d['bcontributions_author_email'])
                ->setVariables($vars)
                ->send();
        }

        $phone = ValidateElement::formatDialCode($d['bcontributions_author_phone_dcode']) . $d['bcontributions_author_phone'];
        $sendSms = $this->sendSms($tpl, $phone, $vars, $langId);
        if (false === $sendEmail && false === $sendSms) {
            return false;
        }
        return true;
    }

    public function sendBlogCommentStatusChangeEmail($langId, $d)
    {
        $tpl = 'blog_comment_status_changed';
        $statusArr = BlogComment::getBlogCommentStatusArr(FatApp::getConfig('CONF_ADMIN_DEFAULT_LANG'));
        $vars = array(
            '{user_full_name}' => $d['bpcomment_author_name'],
            '{new_status}' => $statusArr[$d['bpcomment_approved']],
            '{post_title}' => $d['post_title'],
            '{comment}' => $d['bpcomment_content'],
            '{posted_on_datetime}' => $d['bpcomment_added_on'],
        );

        $sendEmail = (new FatMailer($langId, $tpl))
            ->setTo($d['bpcomment_author_email'])
            ->setVariables($vars)
            ->send();
        if (false === $sendEmail) {
            return false;
        }
        $uData = User::getAttributesById($d['bpcomment_user_id'], ['user_phone_dcode', 'user_phone']);
        if (is_array($uData) && 0 < count($uData)) {
            $phone = ValidateElement::formatDialCode($uData['user_phone_dcode']) . $uData['user_phone'];
            $this->sendSms($tpl, $phone, $vars, $langId);
        }
        return true;
    }

    public function sendBuyerReviewNotification($opId, $langId = 0)
    {
        if ($opId == '') {
            trigger_error(Labels::getLabel('ERR_ORDER_PRODUCT_ID_NOT_SPECIFIED.', $this->commonLangId), E_USER_ERROR);
        }
        $langId = FatUtility::int($langId);

        if (!$langId) {
            trigger_error(Labels::getLabel('ERR_LANGUAGE_ID_NOT_SPECIFIED.', $this->commonLangId), E_USER_ERROR);
        }
        $orderObj = new Orders();
        $orderProduct = $orderObj->getOrderProductsByOpId($opId, $langId);

        $opChargesLog = new OrderProductChargeLog($opId);
        $taxOptions = $opChargesLog->getData($langId);
        $orderProduct['taxOptions'] = $taxOptions;

        if ($orderProduct) {
            $tpl = new FatTemplate('', '');
            $tpl->set('orderProducts', $orderProduct);
            $tpl->set('siteLangId', $langId);
            $orderItemsTableFormatHtml = $tpl->render(false, false, '_partial/emails/child-order-detail-email.php', true);

            $statuesArr = Orders::getOrderProductStatusArr($orderProduct["order_language_id"]);

            $userObj = new User($orderProduct["order_user_id"]);
            $userInfo = $userObj->getUserInfo(array('user_name', 'credential_email', 'user_phone_dcode', 'user_phone'));

            $arrReplacements = array(
                '{user_full_name}' => isset($userInfo["user_name"]) ? trim($userInfo["user_name"]) : $userInfo["credential_email"],
                '{new_order_status}' => $statuesArr[$orderProduct["op_status_id"]],
                '{invoice_number}' => $orderProduct["op_invoice_number"],
                '{order_items_table_format}' => $orderItemsTableFormatHtml,
                '{review_page_url}' => UrlHelper::generateFullUrl('Buyer', 'orderFeedback', array($orderProduct['op_id']), CONF_WEBROOT_DASHBOARD, null, false, false, false),
            );

            if (!empty($userInfo["credential_email"])) {
                (new FatMailer($langId, 'buyer_notification_review_order_product'))
                    ->setTo($userInfo["credential_email"])
                    ->setVariables($arrReplacements)
                    ->send();
            }

            $phone = !empty($userInfo['user_phone']) ? ValidateElement::formatDialCode($userInfo['user_phone_dcode']) . $userInfo['user_phone'] : '';
            $this->sendSms("buyer_notification_review_order_product", $phone, $arrReplacements, $langId);
            return true;
        } else {
            $this->error = Labels::getLabel('ERR_INVALID_REQUEST', $this->commonLangId);
        }
    }

    public function sendBuyerReviewStatusUpdatedNotification($spreviewId, $langId = 0)
    {
        if ($spreviewId == '') {
            trigger_error(Labels::getLabel('ERR_REVIEW_ID_NOT_SPECIFIED.', $this->commonLangId), E_USER_ERROR);
        }
        $langId = FatUtility::int($langId);

        if (!$langId) {
            trigger_error(Labels::getLabel('ERR_LANGUAGE_ID_NOT_SPECIFIED.', $this->commonLangId), E_USER_ERROR);
        }

        $schObj = new SelProdReviewSearch($langId);
        $schObj->joinUser();
        $schObj->joinProducts($langId);
        $schObj->joinSellerProducts($langId);
        $schObj->addCondition('spreview_id', '=', $spreviewId);
        $schObj->addCondition('spreview_status', '!=', 'mysql_func_' . SelProdReview::STATUS_PENDING, 'AND', true);
        $schObj->addMultipleFields(array('spreview_selprod_id', 'spreview_status', 'IFNULL(selprod_title  ,IFNULL(product_name, product_identifier)) as selprod_title', 'user_name', 'user_phone_dcode', 'user_phone', 'credential_email'));
        $schObj->doNotCalculateRecords();
        $schObj->setPageSize(1);
        $spreviewData = FatApp::getDb()->fetch($schObj->getResultSet());

        if (false == $spreviewData) {
            $this->error = Labels::getLabel('ERR_INVALID_REQUEST', $this->commonLangId);
            return false;
        }

        $reviewStatusArr = SelProdReview::getReviewStatusArr($langId);
        $newStatus = $reviewStatusArr[$spreviewData['spreview_status']];

        $productUrl = UrlHelper::generateFullUrl('Products', 'View', array($spreviewData["spreview_selprod_id"]));
        $prodTitleAnchor = "<a href='" . $productUrl . "'>" . $spreviewData['selprod_title'] . "</a>";

        $arrReplacements = array(
            '{user_full_name}' => trim($spreviewData["user_name"]),
            '{new_status}' => $newStatus,
            '{product_link}' => $prodTitleAnchor
        );
        if (!empty($spreviewData["credential_email"])) {
            (new FatMailer($langId, 'buyer_notification_review_status_updated'))
                ->setTo($spreviewData["credential_email"])
                ->setVariables($arrReplacements)
                ->send();
        }

        $phone = !empty($spreviewData['user_phone']) ? ValidateElement::formatDialCode($spreviewData['user_phone_dcode']) . $spreviewData['user_phone'] : '';
        $this->sendSms("buyer_notification_review_status_updated", $phone, $arrReplacements, $langId);
        return true;
    }

    public function sendAdminAbusiveReviewNotification($spreviewId, $langId = 0)
    {
        if ($spreviewId == '') {
            trigger_error(Labels::getLabel('ERR_REVIEW_ID_NOT_SPECIFIED.', $this->commonLangId), E_USER_ERROR);
        }
        $langId = FatUtility::int($langId);

        if (!$langId) {
            trigger_error(Labels::getLabel('ERR_LANGUAGE_ID_NOT_SPECIFIED.', $this->commonLangId), E_USER_ERROR);
        }

        $schObj = new SelProdReviewSearch($langId);
        $schObj->joinUser();
        $schObj->addCondition('spreview_id', '=', $spreviewId);
        $schObj->doNotCalculateRecords();
        $schObj->setPageSize(1);
        $spreviewData = FatApp::getDb()->fetch($schObj->getResultSet());
        if (false == $spreviewData) {
            $this->error = Labels::getLabel('ERR_INVALID_REQUEST', $this->commonLangId);
            return false;
        }

        $arrReplacements = array(
            '{user_full_name}' => trim($spreviewData["user_name"]),
            '{review_url}' => CommonHelper::getAdminUrl('ProductReviews', 'index')
        );

        $to = FatApp::getConfig('CONF_CONTACT_EMAIL', FatUtility::VAR_STRING, '');
        if (strlen(trim($to)) < 1) {
            return $this->sendMailToAdminAndAdditionalEmails("admin_notification_abusive_review_posted", $arrReplacements, static::NO_ADDITIONAL_ALERT, static::NOT_ONLY_SUPER_ADMIN, $langId);
        }

        $sendEmail = (new FatMailer($langId, 'admin_notification_abusive_review_posted'))
            ->setTo($to)
            ->setVariables($arrReplacements)
            ->send();

        $this->sendSms("admin_notification_abusive_review_posted", ValidateElement::formatDialCode(FatApp::getConfig('CONF_SITE_PHONE_dcode')) . FatApp::getConfig('CONF_SITE_PHONE'), $arrReplacements, $langId);
        return true;
    }

    public function sendRewardPointsNotification($langId, $urpId)
    {
        $langId = FatUtility::int($langId);
        $urpId = FatUtility::int($urpId);
        if (!$urpId || !$langId) {
            trigger_error(Labels::getLabel('ERR_INVALID_ARGUMENT_PASSED.', $this->commonLangId), E_USER_ERROR);
        }

        $srch = new UserRewardSearch();
        $srch->doNotCalculateRecords();
        $srch->doNotLimitRecords();
        $srch->joinUser();
        $srch->addCondition('urp_id', '=', 'mysql_func_' . $urpId, 'AND', true);
        $srch->addMultipleFields(array('urp.*', 'u.user_name', 'u.user_phone_dcode', 'u.user_phone', 'uc.credential_email'));
        $row = FatApp::getDb()->fetch($srch->getResultSet());
        if (!$row) {
            $this->error = Labels::getLabel('ERR_INVALID_REQUEST', $this->commonLangId);
            return false;
        }

        $arrReplacements = array(
            '{user_name}' => trim($row["user_name"]),
            '{debit_credit_type}' => $row['urp_points'] > 0 ? Labels::getLabel('LBL_CREDITED', $langId) : Labels::getLabel('LBL_DEBITED', $langId),
            '{reward_points}' => abs($row['urp_points']),
            '{comments}' => $row["urp_comments"],
            '{reward_point_balance}' => UserRewardBreakup::rewardPointBalance($row["urp_user_id"]),
        );

        if (!empty($row["credential_email"])) {
            (new FatMailer($langId, 'reward_points_credited_debited'))
                ->setTo($row["credential_email"])
                ->setVariables($arrReplacements)
                ->send();
        }
        $phone = !empty($row['user_phone']) ? ValidateElement::formatDialCode($row['user_phone_dcode']) . $row['user_phone'] : '';
        $this->sendSms("reward_points_credited_debited", $phone, $arrReplacements, $langId);
        $notiArrReplacements = array(
            '{debitcredittype}' => $row['urp_points'] > 0 ? Labels::getLabel('LBL_CREDITED', $langId) : Labels::getLabel('LBL_DEBITED', $langId),
            '{rewardpoints}' => abs($row['urp_points']),
        );

        $appNotification = CommonHelper::replaceStringData(Labels::getLabel('MSG_REWARDS_{rewardpoints}_HAS_BEEN_{debitcredittype}_ACCOUNT', $langId), $notiArrReplacements);

        $notificationObj = new Notifications();
        $notificationDataArr = array(
            'unotification_user_id' => $row["urp_user_id"],
            'unotification_body' => $appNotification,
            'unotification_type' => 'REWARD_POINTS',
            'unotification_data' => json_encode(array('rewardPoints' => abs($row['urp_points']), 'debitCreditType' => $arrReplacements["{debit_credit_type}"])),
        );
        if (!$notificationObj->addNotification($notificationDataArr)) {
            $this->error = $notificationObj->getError();
            return false;
        }

        return true;
    }

    public function sendDiscountCouponNotification($couponId, $userId, $langId = 0)
    {
        $userId = FatUtility::int($userId);
        $couponId = FatUtility::int($couponId);
        $langId = FatUtility::int($langId);

        $userCoupons = DiscountCoupons::getCouponUsers($couponId);
        if (empty($userCoupons)) {
            return false;
        }

        foreach ($userCoupons as $row) {
            if (!isset($row['ctu_user_id']) || $row['ctu_user_id'] != $userId) {
                continue;
            }

            $discountValue = ($row['coupon_discount_in_percent'] == applicationConstants::PERCENTAGE) ? $row['coupon_discount_value'] . ' %' : CommonHelper::displayMoneyFormat($row['coupon_discount_value'], true, true);
            $arrReplacements = array(
                '{user_name}' => trim($row["user_name"]),
                '{coupon_code}' => $row['coupon_code'],
                '{discount_value}' => $discountValue,
                '{expired_on}' => FatDate::format($row["coupon_end_date"]),
            );
            if (!empty($row['credential_email'])) {
                (new FatMailer($langId, 'user_discount_coupon_notification'))
                    ->setTo($row['credential_email'])
                    ->setVariables($arrReplacements)
                    ->send();
            }
            $phone = !empty($row['user_phone']) ? ValidateElement::formatDialCode($row['user_phone_dcode']) . $row['user_phone'] : '';
            $this->sendSms('user_discount_coupon_notification', $phone, $arrReplacements, $langId);
        }
    }

    public function sendMailShareEarn($senderId, $receiverEmail, $langId)
    {
        if (empty($senderId)) {
            trigger_error(Labels::getLabel('ERR_SENDER_ID_NOT_SPECIFIED.', $this->commonLangId), E_USER_ERROR);
        }
        if (empty($receiverEmail)) {
            trigger_error(Labels::getLabel('ERR_RECEIVER_EMAIL_ADDRESS_NOT_SPECIFIED.', $this->commonLangId), E_USER_ERROR);
        }
        $tpl = 'share_earn_invitation_email';
        $userObj = new User($senderId);
        $userInfo = $userObj->getUserInfo(array('user_name', 'user_phone_dcode', 'user_phone', 'user_referral_code'));

        $vars = array(
            '{user_full_name}' => $userInfo['user_name'],
            '{tracking_url}' => CommonHelper::referralTrackingUrl($userInfo['user_referral_code']),
            //'{invitation_message}' => $personalMsg,
        );
        $sendEmail = false;
        if (!empty($receiverEmail)) {
            $sendEmail = (new FatMailer($langId, $tpl))
                ->setTo($receiverEmail)
                ->setVariables($vars)
                ->send();
        }

        $phone = !empty($userInfo['user_phone']) ? ValidateElement::formatDialCode($userInfo['user_phone_dcode']) . $userInfo['user_phone'] : '';
        $sendSms = $this->sendSms($tpl, $phone, $vars, $langId);
        if (false === $sendEmail && false === $sendSms) {
            return false;
        }
        return true;
    }

    public function sendAffiliateMailShare($senderId, $receiverEmail, $personalMsg, $langId)
    {
        if (empty($senderId)) {
            trigger_error(Labels::getLabel('ERR_SENDER_ID_NOT_SPECIFIED.', $this->commonLangId), E_USER_ERROR);
        }
        if (empty($receiverEmail)) {
            trigger_error(Labels::getLabel('ERR_RECEIVER_EMAIL_ADDRESS_NOT_SPECIFIED.', $this->commonLangId), E_USER_ERROR);
        }
        $tpl = 'affiliate_share_invitation_email';
        $userObj = new User($senderId);
        $userInfo = $userObj->getUserInfo(array('user_name', 'user_referral_code'));

        $vars = array(
            '{user_full_name}' => $userInfo['user_name'],
            '{tracking_url}' => CommonHelper::affiliateReferralTrackingUrl($userInfo['user_referral_code']),
            '{invitation_message}' => $personalMsg,
        );
        $sendEmail = false;
        if (!empty($receiverEmail)) {
            $sendEmail = (new FatMailer($langId, $tpl))
                ->setTo($receiverEmail)
                ->setVariables($vars)
                ->send();
        }

        $srch = User::getSearchObject(true);
        $srch->addMultipleFields(array('u.user_phone_dcode', 'u.user_phone'));
        $srch->addCondition('uc.credential_email', '=', $receiverEmail);
        $srch->doNotCalculateRecords();
        $srch->setPageSize(1);
        $rs = $srch->getResultSet();
        $receiverData = FatApp::getDb()->fetch($rs);
        if (is_array($receiverData) && count($receiverData)) {
            $phone = ValidateElement::formatDialCode($receiverData['user_phone_dcode']) . $receiverData['user_phone'];
            $sendSms = $this->sendSms($tpl, $phone, $vars, $langId);
        }

        if (false === $sendEmail && false === $sendSms) {
            return false;
        }
        return true;
    }

    public function sendCancelSubscriptionNotification($user_id, $ossubs_id, $langId)
    {
        $tpl = 'cancel_subscription_email';
        $userObj = new User($user_id);
        $userInfo = $userObj->getUserInfo(array('user_name', 'user_phone_dcode', 'user_phone', 'credential_email'));

        OrderSubscription::setOrderSubscriptionId($ossubs_id);
        $currentPlanData = OrderSubscription::getUserCurrentActivePlanDetails($langId, $user_id, array('ossubs_subscription_name'));
        if (!$currentPlanData) {
            return false;
        }

        $spackage_name = $currentPlanData['ossubs_subscription_name'];
        $vars = array(
            '{user_full_name}' => $userInfo['user_name'],
            '{spackage_name}' => $spackage_name
        );
        $sendEmail = false;
        if (!empty($userInfo['credential_email'])) {
            $sendEmail = (new FatMailer($langId, $tpl))
                ->setTo($userInfo['credential_email'])
                ->setVariables($vars)
                ->send();
        }

        $phone = !empty($userInfo['user_phone']) ? ValidateElement::formatDialCode($userInfo['user_phone_dcode']) . $userInfo['user_phone'] : '';
        $sendSms = $this->sendSms($tpl, $phone, $vars, $langId);
        if (false === $sendEmail && false === $sendSms) {
            return false;
        }
        return true;
    }

    public function sendSubscriptionReminderEmail($langId, $data)
    {
        if ($data['ossubs_type'] == SellerPackages::FREE_TYPE) {
            $tpl = 'subscription_free_package_reminder_email';
        } elseif ($data['ossubs_type'] == SellerPackages::PAID_TYPE) {
            $tpl = 'subscription_reminder_email';
        }
        $spackage_detail = OrderSubscription::getUserCurrentActivePlanDetails($langId, $data['user_id'], array('spackage_name', 'ossubs_till_date'));
        $pending_days = FatDate::diff(date("Y-m-d"), $spackage_detail[OrderSubscription::DB_TBL_PREFIX . 'till_date']);
        $vars = array(
            '{user_full_name}' => $data['user_name'],
            '{spackage_name}' => $spackage_detail['spackage_name'],
            '{pending_days}' => $pending_days
        );
        $sendEmail = false;
        if (!empty($data['credential_email'])) {
            $sendEmail = (new FatMailer($langId, $tpl))
                ->setTo($data['credential_email'])
                ->setVariables($vars)
                ->send();
        }

        $phone = !empty($data['user_phone']) ? ValidateElement::formatDialCode($data['user_phone_dcode']) . $data['user_phone'] : '';
        $sendSms = $this->sendSms($tpl, $phone, $vars, $langId);
        if (false === $sendEmail && false === $sendSms) {
            return false;
        }
        return true;
    }

    public function orderPurchasedSubscriptionEmail($orderId)
    {
        $langId = FatApp::getConfig('conf_default_site_lang');
        $orderObj = new Orders();
        $orderDetail = $orderObj->getOrderById($orderId);

        $userObj = new User($orderDetail["order_user_id"]);
        $userInfo = $userObj->getUserInfo(array('user_name', 'credential_email', 'user_phone_dcode', 'user_phone'));

        $payementStatusArr = Orders::getOrderPaymentStatusArr($langId);

        $srch = new OrderSubscriptionSearch($orderDetail['order_language_id'], true, true);
        $srch->joinOrderUser();
        $srch->addOrderProductCharges();
        $srch->addCondition('ossubs_order_id', '=', $orderId);
        $srch->doNotCalculateRecords();
        $srch->setPageSize(1);
        $subsOrderDetail = FatApp::getDb()->fetch($srch->getResultSet());
        if ($orderDetail) {
            $tpl = new FatTemplate('', '');
            $tpl->set('orderDetail', $subsOrderDetail);
            $tpl->set('siteLangId', $langId);
            $orderItemsTableFormatHtml = $tpl->render(false, false, '_partial/emails/order-detail-subscription-email.php', true);

            $arrReplacements = array(
                '{user_full_name}' => trim($userInfo['user_name']),
                '{invoice_number}' => $orderDetail['order_number'],
                '{order_products_table_format}' => $orderItemsTableFormatHtml,
                '{amount}' => CommonHelper::displayMoneyFormat($subsOrderDetail['ossubs_price']),
                '{new_order_status}' => $payementStatusArr[$orderDetail['order_payment_status']],
            );

            $this->sendMailToAdminAndAdditionalEmails("new_subscription_purchase_admin", $arrReplacements, static::ADD_ADDITIONAL_ALERTS, static::NOT_ONLY_SUPER_ADMIN, $langId);
            $this->sendSms("new_subscription_purchase_admin", ValidateElement::formatDialCode(FatApp::getConfig('CONF_SITE_PHONE_dcode')) . FatApp::getConfig('CONF_SITE_PHONE'), $arrReplacements, $langId);
            $sendEmail = false;
            if (!empty($userInfo['credential_email'])) {
                $sendEmail = (new FatMailer($orderDetail['order_language_id'], 'new_subscription_purchase'))
                    ->setTo($userInfo['credential_email'])
                    ->setVariables($arrReplacements)
                    ->send();
            }

            $phone = !empty($userInfo['user_phone']) ? ValidateElement::formatDialCode($userInfo['user_phone_dcode']) . $userInfo['user_phone'] : '';
            $sendSms = $this->sendSms("new_subscription_purchase", $phone, $arrReplacements, $langId);
            if (false === $sendEmail && false === $sendSms) {
                return false;
            }
        }
        return true;
    }

    public function orderRenewSubscriptionEmail($orderId)
    {
        $langId = FatApp::getConfig('conf_default_site_lang');
        $orderObj = new Orders();
        $orderDetail = $orderObj->getOrderById($orderId);

        $userObj = new User($orderDetail["order_user_id"]);
        $userInfo = $userObj->getUserInfo(array('user_name', 'credential_email', 'user_phone_dcode', 'user_phone'));

        $payementStatusArr = Orders::getOrderPaymentStatusArr($langId);

        $srch = new OrderSubscriptionSearch($orderDetail['order_language_id'], true, true);
        $srch->joinOrderUser();
        $srch->addOrderProductCharges();
        $srch->addCondition('ossubs_order_id', '=', $orderId);
        $srch->doNotCalculateRecords();
        $srch->setPageSize(1);
        $rs = $srch->getResultSet();
        $orderDetail = FatApp::getDb()->fetch($rs);

        if ($orderDetail) {
            $tpl = new FatTemplate('', '');
            $tpl->set('orderDetail', $orderDetail);
            $tpl->set('siteLangId', $langId);
            $orderItemsTableFormatHtml = $tpl->render(false, false, '_partial/emails/order-detail-subscription-email.php', true);

            $arrReplacements = array(
                '{user_full_name}' => trim($userInfo['user_name']),
                '{invoice_number}' => $orderDetail['order_number'],
                '{order_products_table_format}' => $orderItemsTableFormatHtml,
                '{new_order_status}' => $payementStatusArr[$orderDetail['order_payment_status']],
            );

            $this->sendMailToAdminAndAdditionalEmails("subscription_renew_admin", $arrReplacements, static::ADD_ADDITIONAL_ALERTS, static::NOT_ONLY_SUPER_ADMIN, $langId);
            $this->sendSms("subscription_renew_admin", ValidateElement::formatDialCode(FatApp::getConfig('CONF_SITE_PHONE_dcode')) . FatApp::getConfig('CONF_SITE_PHONE'), $arrReplacements, $langId);
            $sendEmail = false;
            if (!empty($userInfo['credential_email'])) {
                $sendEmail = (new FatMailer($orderDetail['order_language_id'], 'subscription_renew_user'))
                    ->setTo($userInfo['credential_email'])
                    ->setVariables($arrReplacements)
                    ->send();
            }

            $phone = !empty($userInfo['user_phone']) ? ValidateElement::formatDialCode($userInfo['user_phone_dcode']) . $userInfo['user_phone'] : '';
            $sendSms = $this->sendSms("subscription_renew_user", $phone, $arrReplacements, $orderDetail['order_language_id']);
            if (false === $sendEmail && false === $sendSms) {
                return false;
            }
        }
        return true;
    }

    public static function sendSmtpTestEmail(int $langId, $smtpArr = null, $vars = array())
    {
        $obj = new FatMailer($langId, 'test_email');
        $obj->setTo(FatApp::getConfig("CONF_SITE_OWNER_EMAIL"));
        $obj->forceSmtp();
        if (!empty($smtpArr)) {
            $obj->setSmtpDetails($smtpArr);
        }
        if (!empty($vars)) {
            $obj->setVariables($vars);
        }
        return $obj->send();
    }

    public function sendLowBalancePromotionalNotification($langId, $userId, $balanceRequired)
    {
        $userId = FatUtility::int($userId);
        $langId = FatUtility::int($langId);
        $userObj = new User($userId);
        $userInfo = $userObj->getUserInfo(array('user_name', 'user_phone_dcode', 'user_phone', 'credential_email'));
        $arrReplacements = array(
            '{user_name}' => trim($userInfo["user_name"]),
            '{requiredBalance}' => CommonHelper::displayMoneyFormat($balanceRequired, true, true),
        );
        $sendEmail = false;
        if (!empty($userInfo["credential_email"])) {
            $sendEmail = (new FatMailer($langId, 'low_balance_promotional_email'))
                ->setTo($userInfo["credential_email"])
                ->setVariables($arrReplacements)
                ->send();
        }

        $phone = !empty($userInfo['user_phone']) ? ValidateElement::formatDialCode($userInfo['user_phone_dcode']) . $userInfo['user_phone'] : '';
        $sendSms = $this->sendSms("low_balance_promotional_email", $phone, $arrReplacements, $langId);
        if (false === $sendEmail && false === $sendSms) {
            return false;
        }
        return true;
    }

    public function sendLowBalanceSubscriptionNotification($langId, $userId, $balanceRequired)
    {
        $userId = FatUtility::int($userId);
        $langId = FatUtility::int($langId);
        $userObj = new User($userId);
        $userInfo = $userObj->getUserInfo(array('user_name', 'user_phone_dcode', 'user_phone', 'credential_email'));
        $arrReplacements = array(
            '{user_name}' => trim($userInfo["user_name"]),
            '{requiredBalance}' => CommonHelper::displayMoneyFormat($balanceRequired, true, true),
        );
        $sendEmail = false;
        if (!empty($userInfo["credential_email"])) {
            $sendEmail = (new FatMailer($langId, 'low_balance_subscription_email'))
                ->setTo($userInfo["credential_email"])
                ->setVariables($arrReplacements)
                ->send();
        }
        $phone = !empty($userInfo['user_phone']) ? ValidateElement::formatDialCode($userInfo['user_phone_dcode']) . $userInfo['user_phone'] : '';
        $sendSms = $this->sendSms("low_balance_subscription_email", $phone, $arrReplacements, $langId);
        if (false === $sendEmail && false === $sendSms) {
            return false;
        }
        return true;
    }

    public static function sendPromotionStatusChangeNotification($langId, $userId, $d)
    {
        $tpl = 'promotion_request_status_change';
        $statusArr = Promotion::getPromotionReqStatusArr($langId);
        $promotionDetails = Promotion::getAttributesByLangId($langId, $d['promotion_id']);
        $userObj = new User($userId);
        $userInfo = $userObj->getUserInfo(array('user_name', 'credential_email', 'user_phone_dcode', 'user_phone'));

        $vars = array(
            '{user_full_name}' => $userInfo['user_name'],
            '{promotion_name}' => isset($promotionDetails['promotion_name']) ? $promotionDetails['promotion_name'] : $d['promotion_identifier'],
            '{new_request_status}' => $statusArr[$d['promotion_approved']],
        );

        $sendEmail = false;
        if (!empty($userInfo["credential_email"])) {
            $sendEmail = (new FatMailer($langId, $tpl))
                ->setTo($userInfo["credential_email"])
                ->setVariables($vars)
                ->send();
        }

        $phone = !empty($userInfo['user_phone']) ? ValidateElement::formatDialCode($userInfo['user_phone_dcode']) . $userInfo['user_phone'] : '';
        $sendSms = (new self())->sendSms($tpl, $phone, $vars, $langId);
        if (false === $sendEmail && false === $sendSms) {
            return false;
        }
        return true;
    }

    public function sendPromotionApprovalRequestAdmin($langId, $userId, $d)
    {
        $tpl = 'promotion_approval_required_to_admin';
        $promotionDetails = Promotion::getAttributesByLangId($langId, $d['promotion_id']);
        $userObj = new User($userId);
        $userInfo = $userObj->getUserInfo(array('user_name', 'credential_email', 'user_phone_dcode', 'user_phone'));

        $vars = array(
            '{user_full_name}' => $userInfo['user_name'],
            '{promotion_name}' => isset($promotionDetails['promotion_name']) ? $promotionDetails['promotion_name'] : $d['promotion_identifier'],
        );
        if (!$this->sendMailToAdminAndAdditionalEmails($tpl, $vars, static::NO_ADDITIONAL_ALERT, static::NOT_ONLY_SUPER_ADMIN, $langId)) {
            return false;
        }
        $this->sendSms($tpl, ValidateElement::formatDialCode(FatApp::getConfig('CONF_SITE_PHONE_dcode')) . FatApp::getConfig('CONF_SITE_PHONE'), $vars, $langId);
        return true;
    }

    public function remindBuyerForCartItems($langId, $d)
    {
        $tpl = 'reminder_for_items_in_cart';

        $cartData = Cart::getCartData($d['user_id']);
        $cartInfo = json_decode($cartData, true);
        $selProdIds = array();
        unset($cartInfo['shopping_cart']);
        foreach ($cartInfo as $key => $quantity) {
            $keyDecoded = json_decode(base64_decode($key), true);

            if (strpos($keyDecoded, Cart::CART_KEY_PREFIX_PRODUCT) === false) {
                continue;
            }
            $selProdIds[] = FatUtility::int(str_replace(Cart::CART_KEY_PREFIX_PRODUCT, '', $keyDecoded));
        }
        if (empty($selProdIds)) {
            return false;
        }
        $prodSrch = new ProductSearch($langId);
        $prodSrch->setDefinedCriteria(0, 0, array(), false);
        $prodSrch->joinProductToCategory();
        $prodSrch->joinSellerSubscription();
        $prodSrch->addSubscriptionValidCondition();
        $prodSrch->doNotCalculateRecords();
        $prodSrch->addCondition('selprod_id', 'IN', $selProdIds);
        $prodSrch->addCondition('selprod_deleted', '=', 'mysql_func_' . applicationConstants::NO, 'AND', true);
        $prodSrch->doNotLimitRecords();
        $prodSrch->addMultipleFields(
            array(
                'product_id',
                'product_identifier',
                'IFNULL(product_name,product_identifier) as product_name',
                'product_seller_id',
                'product_model',
                'product_type',
                'prodcat_id',
                'IFNULL(prodcat_name,prodcat_identifier) as prodcat_name',
                'product_upc',
                'product_isbn',
                'selprod_id',
                'selprod_user_id',
                'selprod_condition',
                'selprod_price',
                'special_price_found',
                'IFNULL(selprod_title  ,IFNULL(product_name, product_identifier)) as selprod_title',
                'theprice',
                'selprod_stock',
                'selprod_threshold_stock_level',
                'IF(selprod_stock > 0, 1, 0) AS in_stock',
                'brand_id',
                'IFNULL(brand_name, brand_identifier) as brand_name',
                'user_name',
                'shop_id',
                'shop_name',
                'splprice_display_dis_type',
                'splprice_display_dis_val',
                'splprice_display_list_price'
            )
        );
        $productRs = $prodSrch->getResultSet();
        $products = FatApp::getDb()->fetchAll($productRs);

        $fatTpl = new FatTemplate('', '');
        $fatTpl->set('products', $products);
        $fatTpl->set('siteLangId', $langId);
        $productsInCartFormatHtml = $fatTpl->render(false, false, '_partial/emails/products-in-cart-wishlist-email.php', true);

        $vars = array(
            '{user_full_name}' => $d['user_name'],
            '{checkout_url}' => $d['link'],
            '{products_in_cart_format}' => $productsInCartFormatHtml,
        );
        $sendEmail = false;
        if (!empty($d['user_email'])) {
            $sendEmail = (new FatMailer($langId, $tpl))
                ->setTo($d['user_email'])
                ->setVariables($vars)
                ->send();
        }
        $sendSms = $this->sendSms($tpl, ValidateElement::formatDialCode($d['user_phone_dcode']) . $d['user_phone'], $vars, $langId);
        if (false === $sendEmail && false === $sendSms) {
            return false;
        }
        return true;
    }

    public function remindBuyerForWishlistItems($langId, $d)
    {
        $tpl = 'reminder_for_items_in_wishlist';

        $wListObj = new UserWishList();
        $srch = UserWishList::getSearchObject($d['user_id']);
        $wListObj->joinWishListProducts($srch);
        $srch->addMultipleFields(array('uwlp_selprod_id', 'uwlist_id'));
        $srch->addGroupBy('uwlp_selprod_id');
        $srch->doNotCalculateRecords();
        $srch->doNotLimitRecords();
        $selProdIds = FatApp::getDb()->fetchAllAssoc($srch->getResultSet());
        $selProdIds = array_keys($selProdIds);
        $prodSrch = new ProductSearch($langId);
        $prodSrch->setDefinedCriteria(0, 0, array(), false);
        $prodSrch->joinProductToCategory();
        $prodSrch->joinSellerSubscription();
        $prodSrch->addSubscriptionValidCondition();
        $prodSrch->doNotCalculateRecords();
        $prodSrch->addCondition('selprod_id', 'IN', $selProdIds);
        $prodSrch->addCondition('selprod_deleted', '=', 'mysql_func_' . applicationConstants::NO, 'AND', true);
        $prodSrch->addGroupBy('selprod_id');
        $prodSrch->setPageSize(9);
        $prodSrch->addMultipleFields(
            array(
                'product_id',
                'selprod_product_id',
                'product_identifier',
                'IFNULL(product_name,product_identifier) as product_name',
                'product_seller_id',
                'product_model',
                'product_type',
                'prodcat_id',
                'IFNULL(prodcat_name,prodcat_identifier) as prodcat_name',
                'product_upc',
                'product_isbn',
                'selprod_id',
                'selprod_user_id',
                'selprod_condition',
                'selprod_price',
                'special_price_found',
                'IFNULL(selprod_title  ,IFNULL(product_name, product_identifier)) as selprod_title',
                'theprice',
                'selprod_stock',
                'selprod_threshold_stock_level',
                'IF(selprod_stock > 0, 1, 0) AS in_stock',
                'brand_id',
                'IFNULL(brand_name, brand_identifier) as brand_name',
                'user_name',
                'shop_id',
                'shop_name',
                'splprice_display_dis_type',
                'splprice_display_dis_val',
                'splprice_display_list_price'
            )
        );
        $productRs = $prodSrch->getResultSet();
        $products = FatApp::getDb()->fetchAll($productRs);

        $fatTpl = new FatTemplate('', '');
        $fatTpl->set('products', $products);
        $fatTpl->set('siteLangId', $langId);
        $productsInWishlistFormatHtml = $fatTpl->render(false, false, '_partial/emails/products-in-cart-wishlist-email.php', true);

        $vars = array(
            '{user_full_name}' => $d['user_name'],
            '{wishlist_url}' => $d['link'],
            '{products_in_wishlist_format}' => $productsInWishlistFormatHtml,
        );

        $sendEmail = false;
        if (!empty($d['user_email'])) {
            $sendEmail = (new FatMailer($langId, $tpl))
                ->setTo($d['user_email'])
                ->setVariables($vars)
                ->send();
        }

        $sendSms = $this->sendSms($tpl, ValidateElement::formatDialCode($d['user_phone_dcode']) . $d['user_phone'], $vars, $langId);
        if (false === $sendEmail && false === $sendSms) {
            return false;
        }

        return true;
    }

    public function failedLoginAttempt($langId, $data)
    {
        $tpl = 'failed_login_attempt';

        $vars = array(
            '{user_full_name}' => $data['user_name'],
        );
        $sendEmail = false;
        if (!empty($data['credential_email'])) {
            $sendEmail = (new FatMailer($langId, $tpl))
                ->setTo($data['credential_email'])
                ->setVariables($vars)
                ->send();
        }
        $sendSms = $this->sendSms($tpl, ValidateElement::formatDialCode($data['user_phone_dcode']) . $data['user_phone'], $vars, $langId);
        if (false === $sendEmail && false === $sendSms) {
            return false;
        }
        return false;
    }

    public function sendDataRequestNotification($data, $langId)
    {
        $tpl = 'data_request_notification_to_admin';
        $userObj = new User($data['user_id']);
        $userInfo = $userObj->getUserInfo(array('user_name', 'credential_email', 'credential_username', 'user_phone_dcode', 'user_phone'));

        $vars = array(
            '{user_full_name}' => $userInfo['user_name'],
            '{username}' => $userInfo['credential_username'],
            '{user_phone}' => ValidateElement::formatDialCode($userInfo['user_phone_dcode']) . $userInfo['user_phone'],
            '{ureq_purpose}' => $data['ureq_purpose'],
        );

        if (!$this->sendMailToAdminAndAdditionalEmails($tpl, $vars, static::NO_ADDITIONAL_ALERT, static::NOT_ONLY_SUPER_ADMIN, $langId)) {
            return false;
        }
        $this->sendSms($tpl, ValidateElement::formatDialCode(FatApp::getConfig('CONF_SITE_PHONE_dcode')) . FatApp::getConfig('CONF_SITE_PHONE'), $vars, $langId);
        return true;
    }

    public function gdprRequestStatusUpdate($reqId, $langId)
    {
        $tpl = 'gdpr_request_status_update_notification_to_user';
        $reqData = UserGdprRequest::getAttributesById($reqId, array('ureq_user_id', 'ureq_type'));
        if (UserGdprRequest::TYPE_DATA_REQUEST == $reqData['ureq_type']) {
            $tpl = 'gdpr_request_data_to_user';
        }

        $reqTypeArr = UserGdprRequest::getUserRequestTypesArr($langId);
        $reqTypeName = $reqTypeArr[$reqData['ureq_type']];

        $userObj = new User($reqData['ureq_user_id']);
        $srch = $userObj->getUserSearchObj(array('credential_email', 'user_phone_dcode', 'user_phone', 'credential_username'), true, false);
        $srch->doNotCalculateRecords();
        $srch->setPageSize(1);
        $rs = $srch->getResultSet();
        $userInfo = FatApp::getDb()->fetch($rs);
        $vars = array(
            '{username}' => $userInfo['credential_username'],
            '{request_type}' => $reqTypeName,
        );
        $sendEmail = false;
        if (!empty($userInfo['credential_email'])) {
            $sendEmail = (new FatMailer($langId, $tpl))
                ->setTo($userInfo['credential_email'])
                ->setVariables($vars)
                ->send();
        }

        if (!empty($userInfo['user_phone'])) {
            $phone = !empty($userInfo['user_phone']) ? ValidateElement::formatDialCode($userInfo['user_phone_dcode']) . $userInfo['user_phone'] : '';
            $sendSms = $this->sendSms($tpl, $phone, $vars, $langId);
        }

        if (false === $sendEmail && false === $sendSms) {
            return false;
        }
        return true;
    }

    public function sendEmailToUser($langId, $data)
    {
        $tpl = 'user_send_email';

        $replacements = array(
            '{full_name}' => $data['user_name'],
            '{admin_subject}' => $data['mail_subject'],
            '{admin_message}' => $data["mail_message"]
        );
        $sendEmail = false;
        if (!empty($data['credential_email'])) {
            $sendEmail = (new FatMailer($langId, $tpl))
                ->setTo($data['credential_email'])
                ->setVariables($replacements)
                ->send();
        }

        if (!empty($data['user_phone'])) {
            $sendSms = $this->sendSms($tpl, ValidateElement::formatDialCode($data['user_phone_dcode']) . $data['user_phone'], $replacements, $langId);
        }

        if (false === $sendEmail && false === $sendSms) {
            return false;
        }

        return true;
    }

    public static function getEmailTemplatePermissionsArr()
    {
        return array(
            'new_registration_admin' => AdminPrivilege::SECTION_USERS,
            'new_catalog_request_admin' => AdminPrivilege::SECTION_CATALOG_REQUESTS,
            'new_custom_catalog_request_admin' => AdminPrivilege::SECTION_CATALOG_REQUESTS,
            'new_seller_approved_admin' => AdminPrivilege::SECTION_USERS,
            'new_supplier_approval_admin' => AdminPrivilege::SECTION_USERS,
            'seller_brand_request_admin_email' => AdminPrivilege::SECTION_BRAND_REQUESTS,
            'tpl_contact_request_received' => AdminPrivilege::SECTION_USERS,
            'admin_order_email' => AdminPrivilege::SECTION_ORDERS,
            'primary_order_payment_status_change_admin' => AdminPrivilege::SECTION_ORDERS,
            'primary_order_payment_status_admin' => AdminPrivilege::SECTION_ORDERS,
            'withdrawal_request_admin' => AdminPrivilege::SECTION_WITHDRAW_REQUESTS,
            'order_cancellation_notification' => AdminPrivilege::SECTION_ORDER_CANCELLATION_REQUESTS,
            'product_return' => AdminPrivilege::SECTION_ORDER_RETURN_REQUESTS,
            'return_request_message_user' => AdminPrivilege::SECTION_ORDER_RETURN_REQUESTS,
            'catalog_request_message_user' => AdminPrivilege::SECTION_CUSTOM_CATALOG_PRODUCT_REQUESTS,
            'return_request_status_change_notification' => AdminPrivilege::SECTION_ORDER_RETURN_REQUESTS,
            'report_shop' => AdminPrivilege::SECTION_SHOPS,
            'admin_notification_abusive_review_posted' => AdminPrivilege::SECTION_ABUSIVE_WORDS,
            'new_subscription_purchase_admin' => AdminPrivilege::SECTION_SUBSCRIPTION_ORDERS,
            'subscription_renew_admin' => AdminPrivilege::SECTION_SUBSCRIPTION_ORDERS,
            'promotion_approval_required_to_admin' => AdminPrivilege::SECTION_PROMOTIONS,
            'data_request_notification_to_admin' => AdminPrivilege::SECTION_USERS,
        );
    }

    /**
     * This function returns the path of file from where it calls
     *
     * @param string $fileName
     * @return integer $directory
     */
    public static function getTemplatePath($fileName = '', $directory = '_partial/emails')
    {
        if (empty($fileName)) {
            $fileName = current(debug_backtrace())['file'];
        }
        return CONF_VIEW_DIR_PATH . $directory . '/' . basename($fileName);
    }

    public function sendCodOtpVerification($langId, $d)
    {
        $tpl = 'COD_OTP_VERIFICATION';
        $vars = array(
            '{USER_NAME}' => $d['user_name'],
            '{OTP}' => $d['otp'],
        );

        $sendEmail = false;
        if (!empty($d['credential_email'])) {
            $sendEmail = (new FatMailer($langId, $tpl))
                ->setTo($d['credential_email'])
                ->setVariables($vars)
                ->send();
        }

        if (false === $sendEmail) {
            $this->error = Labels::getLabel("ERR_UNABLE_TO_SEND_EMAIL", $langId);
            return false;
        }
        return true;
    }

    /**
     * sendTransferBankNotification - This will trigger once buyer submitted bank transfer detail for order.
     *
     * @param  mixed $langId
     * @param  mixed $d
     * @return bool
     */
    public function sendTransferBankNotification($langId, $d, $isBuyerDash)
    {
        $tpl = 'ADMIN_ORDER_PAYMENT_TRANSFERRED_TO_BANK';
        $vars = array(
            '{USER_NAME}' => $d['user_name'],
            '{ORDER_ID}' => $d['order_number'],
            '{PAYMENT_METHOD}' => $d['payment_method'],
            '{TRANSACTION_ID}' => $d['transaction_id'],
            '{AMOUNT}' => $d['amount'],
            '{COMMENTS}' => $d['comments'],
        );

        if (!$this->sendMailToAdminAndAdditionalEmails($tpl, $vars, static::NO_ADDITIONAL_ALERT, static::ONLY_SUPER_ADMIN, $langId)) {
            return false;
        }

        $this->sendSms($tpl, ValidateElement::formatDialCode(FatApp::getConfig('CONF_SITE_PHONE_dcode')) . FatApp::getConfig('CONF_SITE_PHONE'), $vars, $langId);

        /* Send Success Notification To Buyer With Bank Transfer Order Placed.  */
        $msg = Labels::getLabel('MSG_ORDER_#{ORDER-ID}_BANK_TRANSFER_TRANSACTION_DETAIL_HAVE_BEEN_ADDED', $langId);
        $msg = CommonHelper::replaceStringData($msg, ['{ORDER-ID}' => $d['order_number']]);
        $notificationObj = new Notifications();
        $notificationDataArr = array(
            'unotification_user_id' => $d["order_user_id"],
            'unotification_body' => $msg,
            'unotification_type' => 'ORDER_PAYMENT_TRANSFERRED_TO_BANK',
            'unotification_data' => json_encode(array('orderId' => $d['order_id'])),
        );
        if (!$notificationObj->addNotification($notificationDataArr)) {
            $this->error = $notificationObj->getError();
            return false;
        }

        if(!$isBuyerDash) {
            $notificationObj = new Notifications();
            $notificationDataArr = array(
                'unotification_user_id' => $d["order_user_id"],
                'unotification_body' => CommonHelper::replaceStringData(Labels::getLabel('MSG_YOUR_ORDER_{ORDERID}_HAVE_BEEN_PLACE', $langId), array('{ORDERID}' => $d['order_number'])),
                'unotification_type' => 'BUYER_ORDER',
                'unotification_data' => json_encode(array('orderId' => $d['order_id'])),
            );
            if (!$notificationObj->addNotification($notificationDataArr)) {
                $this->error = $notificationObj->getError();
                return false;
            }
        }
        /* Send Success Notification To Buyer With Bank Transfer Order Placed.  */

        return true;
    }

    public function sendBadgeRequestStatusChangeNotification($langId, $data)
    {
        $tpl = 'seller_badge_request_status_change';
        $userObj = new User($data['breq_user_id']);
        $userInfo = $userObj->getSellerData($langId, array('user_id', 'user_name', 'IFNULL(shop_name, shop_identifier) as shop_name', 'user_phone_dcode', 'user_phone', 'credential_email'));
        $statusArr = BadgeRequest::getStatusArr($langId);
        $vars = array(
            '{user_full_name}' => $userInfo['shop_name'],
            '{shop_name}' => $userInfo['shop_name'],
            '{new_request_status}' => $statusArr[$data['breq_status']],
            '{badge_name}' => $data['badge_name'],
        );

        $receipentsInfo = User::getSubUsersReceipents($userInfo['user_id'], 'canViewProducts');

        $bccEmails = $receipentsInfo['email'];
        $phoneNumbers = $receipentsInfo['phone'];

        if (!empty($userInfo['credential_email'])) {
            $mailerObj = (new FatMailer($langId, $tpl))
                ->setTo($userInfo['credential_email'])
                ->setVariables($vars);

            foreach ($bccEmails as $emailId => $emailName) {
                $mailerObj->addBcc($emailId, $emailName);
            }

            if (false === $mailerObj->send()) {
                return false;
            }
        }

        $userPhone = !empty($userInfo['user_phone']) ? $userInfo['user_phone'] : '';
        $dialCode = !empty($userInfo['user_phone_dcode']) ? ValidateElement::formatDialCode($userInfo['user_phone_dcode']) : '';
        $phoneNumbers[] = $dialCode . $userPhone;
        foreach ($phoneNumbers as $phone) {
            $this->sendSms($tpl, $phone, $vars, $langId);
        }

        return true;
    }

    public function sendNewRfqNotification($langId, $data)
    {
        $tpl = new FatTemplate('', '');
        $tpl->set('data', $data);
        $tpl->set('siteLangId', $langId);
        $rfqTableFormat = $tpl->render(false, false, '_partial/emails/request-for-quote.php', true);

        $vars = array(
            '{rfq_table}' => $rfqTableFormat,
            '{rfq_number}' => $data['rfq_number']
        );

        $attachmentsArr = [];
        $res = AttachedFile::getAttachment(AttachedFile::FILETYPE_RFQ, $data['rfq_id']);
        if (!empty($res) && !empty($res['afile_physical_path']) && !empty($res['afile_physical_path']) && !empty($res['afile_name'])) {
            $attachmentsArr = [
                'path' => CONF_UPLOADS_PATH . $res['afile_physical_path'],
                'name' => $res['afile_name'],
            ];
        }

        if (!$this->sendMailToAdminAndAdditionalEmails('NEW_RFQ', $vars, onlySuperAdmin: static::NOT_ONLY_SUPER_ADMIN, langId: $langId, attachmentsArr: $attachmentsArr)) {
            return false;
        }

        $vars = array(
            '{rfq_title}' => $data['rfq_title'],
            '{rfq_number}' => $data['rfq_number'],
            '{qty}' => $data['rfq_quantity'] . ' ' . applicationConstants::getWeightUnitName($langId, $data['rfq_quantity_unit'], true),
        );
        $this->sendSms('NEW_RFQ', ValidateElement::formatDialCode(FatApp::getConfig('CONF_SITE_PHONE_dcode')) . FatApp::getConfig('CONF_SITE_PHONE'), $vars, $langId);

        return true;
    }

    public function sendNewRfqAssignedNotification($langId, $data)
    {
        $tpl = new FatTemplate('', '');
        $tpl->set('data', $data);
        $tpl->set('siteLangId', $langId);
        $rfqTableFormat = $tpl->render(false, false, '_partial/emails/request-for-quote.php', true);

        $vars = array(
            '{rfq_table}' => $rfqTableFormat,
            '{rfq_number}' => $data['rfq_number'],
            '{shop_user_name}' => $data['shop_user_name']
        );

        $path = $name = '';
        $res = AttachedFile::getAttachment(AttachedFile::FILETYPE_RFQ, $data['rfqts_rfq_id']);
        if (!empty($res) && !empty($res['afile_physical_path']) && !empty($res['afile_physical_path']) && !empty($res['afile_name'])) {
            $path = CONF_UPLOADS_PATH . $res['afile_physical_path'];
            $name = $res['afile_name'];
        }

        if (!empty($data['shop_user_email'])) {
            if (!(new FatMailer($langId, 'NEW_RFQ_ASSIGNED'))
                ->setTo($data['shop_user_email'])
                ->setVariables($vars)
                ->addAttachment($path, $name)
                ->send()) {
                return false;
            }
        }

        $qty = $data['rfq_quantity'] . ' ' . applicationConstants::getWeightUnitName($langId, $data['rfq_quantity_unit'], true);
        if (!empty($data['user_phone']) && !empty($data['user_phone_dcode'])) {
            $vars = array(
                '{shop_user_name}' => $data['shop_user_name'],
                '{rfq_title}' => $data['rfq_title'],
                '{rfq_number}' => $data['rfq_number'],
                '{qty}' => $qty,
            );
            $this->sendSms('NEW_RFQ_ASSIGNED', ValidateElement::formatDialCode($data['user_phone_dcode']) . $data['user_phone'], $vars, $langId);
        }

        return true;
    }

    public function sendApprovalStatusRfqNotification($langId, $data)
    {
        $tpl = new FatTemplate('', '');
        $tpl->set('data', $data);
        $tpl->set('siteLangId', $langId);
        $rfqTableFormat = $tpl->render(false, false, '_partial/emails/request-for-quote.php', true);
        $approvalStatus = RequestForQuote::getApprovalStatusArr($langId)[$data['rfq_approved']] ?? '';
        $vars = array(
            '{user_name}' => $data['user_name'],
            '{rfq_table}' => $rfqTableFormat,
            '{approval_status}' => $approvalStatus,
            '{rfq_number}' => $data['rfq_number']
        );

        if (!empty($data['credential_email'])) {
            if (!(new FatMailer($langId, 'RFQ_APPROVAL'))
                ->setTo($data['credential_email'])
                ->setVariables($vars)
                ->send()) {
                return false;
            }
        }

        $qty = $data['rfq_quantity'] . ' ' . applicationConstants::getWeightUnitName($langId, $data['rfq_quantity_unit'], true);
        if (!empty($data['user_phone']) && !empty($data['user_phone_dcode'])) {
            $vars = array(
                '{user_name}' => $data['user_name'],
                '{rfq_title}' => $data['rfq_title'],
                '{rfq_number}' => $data['rfq_number'],
                '{qty}' => $qty,
                '{approval_status}' => $approvalStatus,
            );
            $this->sendSms('RFQ_APPROVAL', ValidateElement::formatDialCode($data['user_phone_dcode']) . $data['user_phone'], $vars, $langId);
        }

        $rData = ['{RFQ-NUMBER}' => $data['rfq_number'], '{APPROVAL-STATUS}' => $approvalStatus];
        $appNotification = CommonHelper::replaceStringData(Labels::getLabel('MSG_RFQ_({RFQ-NUMBER})_HAS_BEEN_{APPROVAL-STATUS}', $langId), $rData);

        $notificationObj = new Notifications();

        $customData = array(
            'rfq_id' => $data["rfq_id"],
            'rfq_title' => $data['rfq_title'],
            'rfq_number' => $data['rfq_number'],
            'qty' => $qty,
            'approval_status' => $approvalStatus
        );

        $notificationDataArr = array(
            'unotification_user_id' => $data["user_id"],
            'unotification_body' => $appNotification,
            'unotification_type' => 'RFQ_APPROVAL',
            'unotification_data' => json_encode($customData),
            'customData' => $customData,
        );
        $notificationObj->addNotification($notificationDataArr);
        return true;
    }

    public function sendNewRfqOfferNotification($langId, $data)
    {
        $tpl = new FatTemplate('', '');
        $tpl->set('data', $data);
        $tpl->set('siteLangId', $langId);
        $offerTableFormat = $tpl->render(false, false, '_partial/emails/new-rfq-offer.php', true);
        $vars = array(
            '{shop_name}' => $data['shop_name'],
            '{user_name}' => $data['buyer_user_name'],
            '{offer_table}' => $offerTableFormat,
            '{rfq_number}' => $data['rfq_number']
        );
        if (!empty($data['buyer_credential_email'])) {
            if (!(new FatMailer($langId, 'NEW_RFQ_OFFER'))
                ->setTo($data['buyer_credential_email'])
                ->setVariables($vars)
                ->send()) {
                return false;
            }
        }

        if (!empty($data['buyer_phone']) && !empty($data['buyer_phone_dcode'])) {
            $vars = array(
                '{rfq_number}' => $data['rfq_number'],
                '{shop_name}' => $data['shop_name'],
                '{user_name}' => $data['buyer_user_name'],
                '{qty}' => $data['offer_quantity'],
                '{offer_price}' => CommonHelper::displayMoneyFormat($data['offer_price']),
            );
            $this->sendSms('NEW_RFQ_OFFER', ValidateElement::formatDialCode($data['buyer_phone_dcode']) . $data['buyer_phone'], $vars, $langId);
        }

        $rData = ['{RFQ-NUMBER}' => $data['rfq_number'], '{SHOP-NAME}' => $data['shop_name']];
        $appNotification = CommonHelper::replaceStringData(Labels::getLabel('MSG_NEW_OFFER_RECEIVED_FROM_{SHOP-NAME}_ON_RFQ_({RFQ-NUMBER})', $langId), $rData);

        $customData = array(
            'rfq_id' => $data['offer_rfq_id'],
            'rfq_number' => $data['rfq_number'],
            'qty' => $data['offer_quantity'],
            'offer_price' => CommonHelper::displayMoneyFormat($data['offer_price'])
        );

        $notificationObj = new Notifications();
        $notificationDataArr = array(
            'unotification_user_id' => $data["buyer_user_id"],
            'unotification_body' => $appNotification,
            'unotification_type' => 'NEW_RFQ_OFFER',
            'unotification_data' => json_encode($customData),
            'customData' => $customData,
        );
        $notificationObj->addNotification($notificationDataArr);
        return true;
    }

    public function sendCounterRfqOfferNotification($langId, $data)
    {
        $ftpl = new FatTemplate('', '');
        $ftpl->set('data', $data);
        $ftpl->set('siteLangId', $langId);
        if ($data['isSeller']) {
            $offerTableFormat = $ftpl->render(false, false, '_partial/emails/counter-rfq-offer-seller.php', true);
            $tpl = 'COUNTER_RFQ_OFFER_SELLER';
            $phone = ValidateElement::formatDialCode($data['buyer_phone_dcode']) . $data['buyer_phone'];
        } else {
            $offerTableFormat = $ftpl->render(false, false, '_partial/emails/counter-rfq-offer-buyer.php', true);
            $tpl = 'COUNTER_RFQ_OFFER_BUYER';
            $phone = ValidateElement::formatDialCode(FatApp::getConfig('CONF_SITE_PHONE_dcode')) . FatApp::getConfig('CONF_SITE_PHONE');
        }
        $vars = array(
            '{shop_name}' => $data['shop_name'],
            '{user_name}' => $data['buyer_user_name'],
            '{offer_table}' => $offerTableFormat,
            '{rfq_number}' => $data['rfq_number']
        );

        if ($data['isSeller']) {
            if (!empty($data['buyer_credential_email'])) {
                if (!(new FatMailer($langId, $tpl))
                    ->setTo($data['buyer_credential_email'])
                    ->setVariables($vars)
                    ->send()) {
                    return false;
                }
            }

            $rData = ['{RFQ-NUMBER}' => $data['rfq_number'], '{SHOP-NAME}' => $data['shop_name']];
            $appNotification = CommonHelper::replaceStringData(Labels::getLabel('MSG_COUNTER_OFFER_RECEIVED_FROM_{SHOP-NAME}_ON_RFQ_({RFQ-NUMBER})', $langId), $rData);

            $customData = array(
                'rfq_id' => $data['offer_rfq_id'],
                'rfq_number' => $data['rfq_number'],
                'qty' => $data['offer_quantity'],
                'offer_price' => CommonHelper::displayMoneyFormat($data['offer_price'])
            );

            $notificationObj = new Notifications();
            $notificationDataArr = array(
                'unotification_user_id' => $data["buyer_user_id"],
                'unotification_body' => $appNotification,
                'unotification_type' => $tpl,
                'unotification_data' => json_encode($customData),
                'customData' => $customData,
            );
            $notificationObj->addNotification($notificationDataArr);
        } else {
            if (!empty($data['seller_email'])) {
                if (!(new FatMailer($langId, $tpl))
                    ->setTo($data['seller_email'])
                    ->setVariables($vars)
                    ->send()) {
                    return false;
                }
            } else {
                if (!$this->sendMailToAdminAndAdditionalEmails($tpl, $vars, onlySuperAdmin: static::NOT_ONLY_SUPER_ADMIN, langId: $langId)) {
                    return false;
                }
            }
        }

        if (!empty($phone)) {
            $vars = array(
                '{rfq_number}' => $data['rfq_number'],
                '{shop_name}' => $data['shop_name'],
                '{user_name}' => $data['buyer_user_name'],
                '{qty}' => ($data['isSeller']) ? $data['offer_quantity'] : $data['counter_offer_quantity'],
                '{offer_price}' => ($data['isSeller']) ? CommonHelper::displayMoneyFormat($data['offer_price']) : CommonHelper::displayMoneyFormat($data['counter_offer_price']),
            );
            $this->sendSms($tpl, $phone, $vars, $langId);
        }
        return true;
    }

    public function sendRfqOfferActionNotification($langId, $data, $acceptance = 0)
    {
        $ftpl = new FatTemplate('', '');
        $ftpl->set('data', $data);
        $ftpl->set('siteLangId', $langId);
        if ($data['isSeller']) {
            $offerTableFormat = $ftpl->render(false, false, '_partial/emails/rfq-offer-action-seller.php', true);
            $tpl = 'RFQ_OFFER_ACTION_SELLER';
            $phone = ValidateElement::formatDialCode($data['user_phone_dcode']) . $data['user_phone'];
        } else {
            $offerTableFormat = $ftpl->render(false, false, '_partial/emails/rfq-offer-action-buyer.php', true);
            $tpl = 'RFQ_OFFER_ACTION_BUYER';
            $phone = ValidateElement::formatDialCode(FatApp::getConfig('CONF_SITE_PHONE_dcode')) . FatApp::getConfig('CONF_SITE_PHONE');
        }

        if (0 < $acceptance) {
            $data['offer_status'] = RfqOffers::STATUS_ACCEPTED;
        }

        $offerStatusArr = RfqOffers::getStatusArr($langId);
        $vars = array(
            '{shop_name}' => $data['shop_name'],
            '{user_name}' => $data['user_name'],
            '{offer_status}' => $offerStatusArr[$data['offer_status']],
            '{offer_table}' => $offerTableFormat,
            '{rfq_number}' => $data['rfq_number']
        );

        if ($data['isSeller']) {
            if (!empty($data['credential_email'])) {
                if (!(new FatMailer($langId, $tpl))
                    ->setTo($data['credential_email'])
                    ->setVariables($vars)
                    ->send()) {
                    return false;
                }
            }

            $rData = ['{RFQ-NUMBER}' => $data['rfq_number'], '{SHOP-NAME}' => $data['shop_name'], '{OFFER-STATUS}' => $offerStatusArr[$data['offer_status']]];
            $appNotification = CommonHelper::replaceStringData(Labels::getLabel('MSG_YOUR_OFFER_HAS_BEEN_{OFFER-STATUS}_BY_{SHOP-NAME}_ON_RFQ_({RFQ-NUMBER})', $langId), $rData);

            $customData = array(
                'rfq_id' => $data['offer_rfq_id'],
                'rfq_number' => $data['rfq_number'],
                'qty' => $data['offer_quantity'],
                'offer_price' => CommonHelper::displayMoneyFormat($data['offer_price']),
                'offer_status_id' => $data['offer_status'],
                'offer_status' => $offerStatusArr[$data['offer_status']]
            );

            $notificationObj = new Notifications();
            $notificationDataArr = array(
                'unotification_user_id' => $data["user_id"],
                'unotification_body' => $appNotification,
                'unotification_type' => $tpl,
                'unotification_data' => json_encode($customData),
                'customData' => $customData,
            );
            $notificationObj->addNotification($notificationDataArr);
        } else {
            if (!empty($data['seller_email'])) {
                if (!(new FatMailer($langId, $tpl))
                    ->setTo($data['seller_email'])
                    ->setVariables($vars)
                    ->send()) {
                    return false;
                }
            } else {
                if (!$this->sendMailToAdminAndAdditionalEmails($tpl, $vars, onlySuperAdmin: static::NOT_ONLY_SUPER_ADMIN, langId: $langId)) {
                    return false;
                }
            }
        }

        if (isset($data['sellers']) && !empty($data['sellers'])) {
            $acceptedTpl = $data['isSeller'] ? 'RFQ_OFFER_ACCEPTED_BY_SELLER' : 'RFQ_OFFER_ACCEPTED_BY_BUYER';
            foreach ($data['sellers'] as $seller) {
                if (!empty($seller['rfqts_user_id']) && $data['seller_user_id'] != $seller['rfqts_user_id'] && !empty($seller['shop_user_email'])) {
                    $otherVars = array(
                        '{shop_name}' => $seller['shop_name'],
                        '{user_name}' => $data['user_name'],
                        '{rfq_number}' => $data['rfq_number']
                    );

                    if (!(new FatMailer($langId, $acceptedTpl))
                        ->setTo($seller['shop_user_email'])
                        ->setVariables($otherVars)
                        ->send()) {
                        return false;
                    }

                    if (!empty($data['shop_phone_dcode']) && !empty($data['shop_phone'])) {
                        $otherSellersPhone = ValidateElement::formatDialCode($data['shop_phone_dcode']) . $data['shop_phone'];
                        $this->sendSms($acceptedTpl, $otherSellersPhone, $otherVars, $langId);
                    }
                }
            }
        }

        if (!empty($phone)) {
            $vars = array(
                '{rfq_number}' => $data['rfq_number'],
                '{shop_name}' => $data['shop_name'],
                '{user_name}' => $data['user_name'],
                '{offer_status}' => $offerStatusArr[$data['offer_status']],
                '{qty}' => $data['offer_quantity'],
                '{offer_price}' => CommonHelper::displayMoneyFormat($data['offer_price']),
            );
            $this->sendSms($tpl, $phone, $vars, $langId);
        }
        return true;
    }

    public function sendDeletionRfqNotification($langId, $data)
    {
        $tpl = new FatTemplate('', '');
        $tpl->set('data', $data);
        $tpl->set('siteLangId', $langId);
        $rfqTableFormat = $tpl->render(false, false, '_partial/emails/request-for-quote.php', true);
        $vars = array(
            '{user_name}' => $data['user_name'],
            '{rfq_table}' => $rfqTableFormat,
            '{rfq_number}' => $data['rfq_number']
        );

        if (!empty($data['credential_email'])) {
            if (!(new FatMailer($langId, 'RFQ_DELETION'))
                ->setTo($data['credential_email'])
                ->setVariables($vars)
                ->send()) {
                return false;
            }
        }

        $qty = $data['rfq_quantity'] . ' ' . applicationConstants::getWeightUnitName($langId, $data['rfq_quantity_unit'], true);
        if (!empty($data['user_phone']) && !empty($data['user_phone_dcode'])) {
            $vars = array(
                '{user_name}' => $data['user_name'],
                '{rfq_title}' => $data['rfq_title'],
                '{rfq_number}' => $data['rfq_number'],
                '{qty}' => $qty,
            );
            $this->sendSms('RFQ_DELETION', ValidateElement::formatDialCode($data['user_phone_dcode']) . $data['user_phone'], $vars, $langId);
        }
        $rData = ['{RFQ-NUMBER}' => $data['rfq_number']];
        $appNotification = CommonHelper::replaceStringData(Labels::getLabel('MSG_YOUR_RFQ_({RFQ-NUMBER})_HAS_BEEN_DELETED', $langId), $rData);

        $customData = array(
            'rfq_id' => $data['rfq_id'],
            'rfq_title' => $data['rfq_title'],
            'rfq_number' => $data['rfq_number'],
            'qty' => $qty
        );

        $notificationObj = new Notifications();
        $notificationDataArr = array(
            'record_id' => $data["rfq_id"],
            'unotification_user_id' => $data["user_id"],
            'unotification_body' => $appNotification,
            'unotification_type' => 'RFQ_DELETION',
            'unotification_data' => json_encode($customData),
            'customData' => $customData,
        );
        $notificationObj->addNotification($notificationDataArr);
        return true;
    }

    public function sendMailToAdminAndRecipient($orderId)
    {
        $srch = new SearchBase(GiftCards::DB_TBL, 'ogcards');
        $srch->joinTable(Orders::DB_TBL, 'INNER JOIN', 'ogcards.ogcards_order_id = orders.order_id', 'orders');
        $srch->joinTable(User::DB_TBL, 'INNER JOIN', 'user.user_id = orders.order_user_id', 'user');
        $srch->joinTable(User::DB_TBL_CRED, 'INNER JOIN', 'user.user_id = usercred.credential_user_id', 'usercred');
        $srch->addCondition('ogcards_order_id', '=', $orderId);
        $srch->addMultipleFields([
            'ogcards_receiver_email',
            'ogcards_receiver_name',
            'ogcards_code',
            'order_net_amount',
            'user.user_name',
            'usercred.credential_email'
        ]);
        $srch->doNotCalculateRecords();
        $srch->doNotLimitRecords();
        $giftcard = FatApp::getDb()->fetch($srch->getResultSet());
        if (!empty($giftcard)) {
            $this->sendEmailToAdmin($giftcard);
            $this->sendEmailToRecipient($giftcard);
        }
        return true;
    }

    public function sendEmailToAdmin(array $giftcard): bool
    {
        $tpl = "admin-gift-card";
        $langId = $this->commonLangId;
        $vars = [
            '{sender_name}' => $giftcard['user_name'],
            '{sender_email}' => !empty($giftcard['credential_email']) ? $giftcard['credential_email'] : Labels::getLabel('LBL_N/A', $langId),
            '{recipient_name}' => $giftcard['ogcards_receiver_name'],
            '{recipient_email}' => $giftcard['ogcards_receiver_email'],
            '{giftcard_code}' => $giftcard['ogcards_code'],
            '{giftcard_amount}' => CommonHelper::displayMoneyFormat($giftcard['order_net_amount'], true, true)
        ];

        $sendEmail = false;
        if (!empty(FatApp::getConfig('CONF_SITE_OWNER_EMAIL'))) {
            $sendEmail = (new FatMailer($langId, $tpl))
                ->setTo(FatApp::getConfig('CONF_SITE_OWNER_EMAIL'))
                ->setVariables($vars)
                ->send();
        }

        if (false === $sendEmail) {
            $this->error = Labels::getLabel("ERR_UNABLE_TO_SEND_EMAIL", $langId);
            return false;
        }
        return true;
    }

    public function sendEmailToRecipient(array $giftcard)
    {
        $tpl = "receiver-gift-card";
        $langId = $this->commonLangId;
        $vars = [
            '{sender_name}' => $giftcard['user_name'],
            '{sender_email}' => !empty($giftcard['credential_email']) ? $giftcard['credential_email'] : Labels::getLabel('LBL_N/A', $langId),
            '{recipient_name}' => $giftcard['ogcards_receiver_name'],
            '{giftcard_code}' => $giftcard['ogcards_code'],
        ];
        $sendEmail = false;
        if (!empty($giftcard['ogcards_receiver_email'])) {
            $sendEmail = (new FatMailer($langId, $tpl))
                ->setTo($giftcard['ogcards_receiver_email'])
                ->setVariables($vars)
                ->send();
        }

        if (false === $sendEmail) {
            $this->error = Labels::getLabel("ERR_UNABLE_TO_SEND_EMAIL", $langId);
            return false;
        }
        return true;
    }

    public function sendRedeemGiftCardNotification(array $card)
    {
        $tpl = 'user-redeem-gift-card';
        $langId = $this->commonLangId;
        $vars = array(
            '{user_full_name}' => $card['user_name'],
            '{redeemed_code}' => $card['ogcards_code'],
        );
        $sendEmail  = false;
        if (!empty($card['credential_email'])) {
            $sendEmail = (new FatMailer($langId, $tpl))
                ->setTo($card['credential_email'])
                ->setVariables($vars)
                ->send();
        } else {
            SystemLog::system('Unable to send Redeem Gift Card Email to user(' . $card['user_name'] . ')', 'Unable to send Redeem Gift Card Email to user');
        }

        if (false === $sendEmail) {
            $this->error = Labels::getLabel("ERR_UNABLE_TO_SEND_EMAIL", $langId);
            return false;
        }
        return true;
    }

    public function sendTransferBankActionNotification($langId, $d)
    {
        $tpl = 'BANK_TRANSFER_ORDER_PAYMENT_ACTIONS';
        $vars = array(
            '{USER_NAME}' => $d['user_name'],
            '{ORDER_ID}' => $d['order_number'],
            '{STATUS}' => $d['txn_status'],
        );

        if (!(new FatMailer($langId, $tpl))
            ->setTo($d['credential_email'])
            ->setVariables($vars)
            ->send()) {
            return false;
        }

        if (!empty($d['user_phone']) && !empty($d['user_phone_dcode'])) {
            $phone = ValidateElement::formatDialCode($d['user_phone_dcode']) . $d['user_phone'];
            $this->sendSms($tpl, $phone, $vars, $langId);
        }

        /* Send Notification To Buyer about Bank Transfer transaction status.  */
        $msg = Labels::getLabel('MSG_ORDER_#{ORDER-ID}_TXN._HAS_BEEN_{STATUS}', $langId);
        $msg = CommonHelper::replaceStringData($msg, $vars);
        $notificationObj = new Notifications();
        $notificationDataArr = array(
            'unotification_user_id' => $d["order_user_id"],
            'unotification_body' => $msg,
            'unotification_type' => 'BANK_TRANSFER_ORDER_PAYMENT_APPROVAL',
            'unotification_data' => json_encode(array('orderId' => $d['order_id'])),
        );
        if (!$notificationObj->addNotification($notificationDataArr)) {
            $this->error = $notificationObj->getError();
            return false;
        }
        /* Send Notification To Buyer about Bank Transfer transaction status.  */
        return true;
    }
}
