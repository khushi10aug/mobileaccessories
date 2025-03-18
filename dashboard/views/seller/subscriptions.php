<?php defined('SYSTEM_INIT') or die('Invalid Usage.');

$dateFromFld = $frmSearch->getField('date_from');
$dateFromFld->setFieldTagAttribute('class', 'field--calender');

$dateToFld = $frmSearch->getField('date_to');
$dateToFld->setFieldTagAttribute('class', 'field--calender');

$message = '';
if ($currentActivePlan) {
    if (strtotime(date("Y-m-d")) >= strtotime('-3 day', strtotime($currentActivePlan[OrderSubscription::DB_TBL_PREFIX . 'till_date']))) {
        if ($currentActivePlan[OrderSubscription::DB_TBL_PREFIX . 'type'] == SellerPackages::PAID_TYPE && FatDate::diff(date("Y-m-d"), $currentActivePlan[OrderSubscription::DB_TBL_PREFIX . 'till_date']) > 0) {
            $message = sprintf(Labels::getLabel('MSG_Your_Subscription_is_going_to_expire_in_%s_day(s),Please_maintain_your_wallet_to_continue_your_subscription,_Amount_required_%s', $siteLangId), FatDate::diff(date("Y-m-d"), $currentActivePlan[OrderSubscription::DB_TBL_PREFIX . 'till_date']), CommonHelper::displayMoneyFormat($currentActivePlan[OrderSubscription::DB_TBL_PREFIX . 'price']));
        } elseif ($currentActivePlan[OrderSubscription::DB_TBL_PREFIX . 'type'] == SellerPackages::PAID_TYPE && FatDate::diff(date("Y-m-d"), $currentActivePlan[OrderSubscription::DB_TBL_PREFIX . 'till_date']) == 0) {
            $message = sprintf(Labels::getLabel('MSG_Your_Subscription_is_going_to_expire_today,_Please_maintain_your_wallet_to_continue_your_subscription,_Amount_required_%s', $siteLangId), CommonHelper::displayMoneyFormat($currentActivePlan[OrderSubscription::DB_TBL_PREFIX . 'price']));
        } elseif ($currentActivePlan[OrderSubscription::DB_TBL_PREFIX . 'type'] == SellerPackages::PAID_TYPE && FatDate::diff(date("Y-m-d"), $currentActivePlan[OrderSubscription::DB_TBL_PREFIX . 'till_date']) < 0 && $autoRenew) {
            $message = sprintf(Labels::getLabel('MSG_Your_Subscription_has_been_expired,Please_purchase_new_plan_or_maintain_your_wallet_to_continue_your_subscription,_Amount_required_%s', $siteLangId), CommonHelper::displayMoneyFormat($currentActivePlan[OrderSubscription::DB_TBL_PREFIX . 'price']));
        } elseif ($currentActivePlan[OrderSubscription::DB_TBL_PREFIX . 'type'] == SellerPackages::PAID_TYPE && FatDate::diff(date("Y-m-d"), $currentActivePlan[OrderSubscription::DB_TBL_PREFIX . 'till_date']) < 0  && !$autoRenew) {
            $message = sprintf(Labels::getLabel('MSG_Your_Subscription_has_been_expired,Please_purchase_new_plan_or_add_%s_in_your_wallet_before_renewing_your_subscription', $siteLangId), CommonHelper::displayMoneyFormat($currentActivePlan[OrderSubscription::DB_TBL_PREFIX . 'price']));
        } elseif ($currentActivePlan[OrderSubscription::DB_TBL_PREFIX . 'type'] == SellerPackages::FREE_TYPE && FatDate::diff(date("Y-m-d"), $currentActivePlan[OrderSubscription::DB_TBL_PREFIX . 'till_date']) > 0) {
            $message = sprintf(Labels::getLabel('MSG_Your_Free_Subscription_is_going_to_expire_in_%s_day(s),Please_Purchase_new_Subscription_to_continue_services', $siteLangId), FatDate::diff(date("Y-m-d"), $currentActivePlan[OrderSubscription::DB_TBL_PREFIX . 'till_date']));
        } elseif ($currentActivePlan[OrderSubscription::DB_TBL_PREFIX . 'type'] == SellerPackages::FREE_TYPE && FatDate::diff(date("Y-m-d"), $currentActivePlan[OrderSubscription::DB_TBL_PREFIX . 'till_date']) == 0) {
            $message = sprintf(Labels::getLabel('MSG_Your_Free_Subscription_is_going_to_expire_today,_Please_Purchase_new_Subscription_to_continue_services', $siteLangId));
        } elseif ($currentActivePlan[OrderSubscription::DB_TBL_PREFIX . 'type'] == SellerPackages::FREE_TYPE && FatDate::diff(date("Y-m-d"), $currentActivePlan[OrderSubscription::DB_TBL_PREFIX . 'till_date']) < 0) {
            $message = Labels::getLabel('MSG_Your_Free_Subscription_has_been_expired,Please_Purchase_new_Subscription_to_continue_services', $siteLangId);
        }
    }
}

$this->includeTemplate('_partial/dashboardNavigation.php'); ?>
<div class="content-wrapper content-space">
    <?php
    $data = [
        'headingLabel' => Labels::getLabel('LBL_My_Subscriptions', $siteLangId),
        'siteLangId' => $siteLangId
    ];

    if ($canEdit) {
        $attributes = ($autoRenew) ? "checked" : "";
        $attributes .= ' onclick="toggleAutoRenewal()"';
        $btnTxt = HtmlHelper::configureSwitchForCheckboxStatic('', '', $attributes, Labels::getLabel('LBL_AutoRenew_Subscription', $siteLangId));
        $data['otherButtons']['html'] = $btnTxt;
    }

    $this->includeTemplate('_partial/header/content-header.php', $data, false); ?>
    <div class="content-body">
        <?php if (!empty($message)) { ?>
            <div class="alert alert-info" role="alert">
                <div class="alert-icon">
                    <svg class="svg" width="18" height="18">
                        <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#info">
                        </use>
                    </svg>
                </div>
                <div class="alert-text"> <?php echo $message; ?> </div>
            </div>
        <?php } ?>
        <div class="row">
            <div class="col-lg-12">
                <div class="card">
                    <?php require_once(CONF_THEME_PATH . '_partial/listing/listing-search-form.php'); ?>
                    <div class="card-table ">
                        <div id="ordersListing"><?php echo Labels::getLabel('LBL_Loading..', $siteLangId); ?></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>