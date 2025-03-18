<?php
defined('SYSTEM_INIT') or die('Invalid Usage.');
$dateFromFld = $frmSearch->getField('date_from');
$dateFromFld->setFieldTagAttribute('class', 'field--calender');
$dateFromFld->setFieldTagAttribute('placeholder', Labels::getLabel('FRM_FROM_DATE', $siteLangId));

$dateToFld = $frmSearch->getField('date_to');
$dateToFld->setFieldTagAttribute('class', 'field--calender');
$dateToFld->setFieldTagAttribute('placeholder', Labels::getLabel('FRM_TO_DATE', $siteLangId));

$showTotalBalanceAvailableDiv = ($userTotalWalletBalance != $userWalletBalance || ($promotionWalletToBeCharged) || ($withdrawlRequestAmount));

$this->includeTemplate('_partial/dashboardNavigation.php'); ?>

<div class="content-wrapper content-space">
    <?php
    $data = [
        'headingLabel' => Labels::getLabel('LBL_MY_CREDITS', $siteLangId),
        'siteLangId' => $siteLangId,
    ];

    if ($canAddMoneyToWallet) {
        $data['newRecordBtn'] = true;
        $data['newRecordBtnAttrs'] = [
            'attr' => [
                'onclick' => 'addCredits()',
                'title' => Labels::getLabel('BTN_RECHARGE_YOUR_WALLET', $siteLangId),
            ],
            'label' => '<svg class="svg btn-icon-start" width="18" height="18">
                            <use xlink:href="' . CONF_WEBROOT_URL . 'images/retina/sprite-actions.svg' . AttachedFile::setTimeParam(RELEASE_DATE) . '#add">
                            </use>
                        </svg>' . Labels::getLabel('BTN_ADD_CREDITS', $siteLangId)
        ];
    }

    if ($canRedeemGiftCard) {
        $data['otherButtons'] = [
            [
                'attr' => [
                    'href' => 'javascript:void(0)',
                    'class' => 'btn btn-outline-brand btn-icon',
                    'onclick' => "redeemGiftCard()",
                    'title' => Labels::getLabel('BTN_REDEEM_GIFT_CARD', $siteLangId)
                ],
                'label' => '<span>' . Labels::getLabel('BTN_REDEEM_GIFT_CARD', $siteLangId) . '</span>',
            ]
        ];
    }

    $this->includeTemplate('_partial/header/content-header.php', $data, false); ?>
    <div class="content-body">
        <div class="row mb-4">
            <div class="col-lg-12">
                <?php if ($codMinWalletBalance > -1) { ?>
                    <p class="note">
                        <?php echo Labels::getLabel('MSG_MINIMUM_BALANCE_REQUIRED_FOR_COD', $siteLangId) . ' : ' . CommonHelper::displaymoneyformat($codMinWalletBalance); ?>
                    </p>
                <?php } ?>
                <div class="row">
                    <?php
                    $col = 6;
                    if ($showTotalBalanceAvailableDiv) {
                        if ($promotionWalletToBeCharged) {
                            $col = ($withdrawlRequestAmount) ? 4 : 6; ?>
                            <div class="col-md-<?php echo $col; ?>">
                                <div class="card card-commerce card-commerce-bg card-full-height" style="background-image: url(<?php echo CONF_WEBROOT_URL; ?>/images/card-commerce-bg-2.png);">
                                    <div class="card-head border-0">
                                        <h6> <?php echo Labels::getLabel('LBL_PENDING_PROMOTIONS_CHARGES', $siteLangId); ?>: </h6>
                                        <i class="icn"> </i>
                                    </div>

                                    <div class="card-body ">
                                        <h3 class="h3">
                                            <?php echo CommonHelper::displayMoneyFormat($promotionWalletToBeCharged); ?> </h3>
                                        <?php if (CommonHelper::getCurrencyId() != FatApp::getConfig('CONF_CURRENCY', FatUtility::VAR_INT, 1)) { ?>
                                            <small>
                                                <?php echo Labels::getLabel('LBL_APPROX.', $siteLangId);
                                                echo CommonHelper::displayMoneyFormat($promotionWalletToBeCharged, true, true); ?>
                                            </small>
                                        <?php } ?>
                                    </div>
                                </div>
                            </div>
                        <?php }

                        if ($withdrawlRequestAmount) { ?>
                            <div class="col-md-<?php echo $col; ?>">
                                <div class="card card-commerce card-commerce-bg card-full-height" style="background-image: url(<?php echo CONF_WEBROOT_URL; ?>/images/card-commerce-bg-3.png);">
                                    <div class="card-head border-0">
                                        <h6> <?php echo Labels::getLabel('LBL_PENDING_WITHDRAWL_REQUESTS', $siteLangId); ?>: </h6>
                                        <i class="icn"> </i>
                                    </div>
                                    <div class="card-body">
                                        <h3 class="h3">
                                            <?php echo CommonHelper::displayMoneyFormat($withdrawlRequestAmount); ?> </h3>

                                        <?php if (CommonHelper::getCurrencyId() != FatApp::getConfig('CONF_CURRENCY', FatUtility::VAR_INT, 1)) { ?>
                                            <small><?php echo Labels::getLabel('LBL_Approx.', $siteLangId); ?> <?php echo CommonHelper::displayMoneyFormat($withdrawlRequestAmount, true, true); ?></small>
                                        <?php } ?>
                                    </div>
                                </div>
                            </div>
                    <?php }
                    } ?>
                    <div class="col-md-<?php echo $col; ?>">
                        <div class="card card-commerce card-commerce-bg card-full-height" style="background-image: url(<?php echo CONF_WEBROOT_URL; ?>/images/card-commerce-bg-1.png);">
                            <div class="card-head border-0">
                                <h6>
                                    <p><?php echo Labels::getLabel('LBL_AVAILABLE_BALANCE', $siteLangId); ?>: </p>
                                </h6>
                            </div>
                            <div class="card-body">
                                <h3><?php echo CommonHelper::displayMoneyFormat($userWalletBalance); ?></h3>
                                <button class="btn btn-outline-gray btn-sm" onclick="withdrawalOptionsForm();">
                                    <?php echo Labels::getLabel('LBL_WITHDRAW', $siteLangId); ?>
                                </button>
                                <?php if (CommonHelper::getCurrencyId() != FatApp::getConfig('CONF_CURRENCY', FatUtility::VAR_INT, 1)) { ?>
                                    <small class="d-block">
                                        <?php echo Labels::getLabel('LBL_Approx.', $siteLangId); ?>
                                        <?php echo CommonHelper::displayMoneyFormat($userWalletBalance, true, true); ?>
                                    </small>
                                <?php } ?>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row mt-4">
                    <div class="col-md-12">
                        <div class="row">
                            <div class="col-lg-12">
                                <div class="card">
                                    <?php require_once(CONF_THEME_PATH . '_partial/listing/listing-search-form.php'); ?>
                                    <div class="card-table">
                                        <div id="creditListing"><?php echo Labels::getLabel('LBL_LOADING..', $siteLangId); ?></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>