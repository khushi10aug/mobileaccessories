<?php defined('SYSTEM_INIT') or die('Invalid Usage.');
$this->includeTemplate('_partial/dashboardNavigation.php'); ?>

<div class="content-wrapper content-space">
    <?php
    $data = [
        'headingLabel' => Labels::getLabel('Lbl_Advertiser', $siteLangId),
        'siteLangId' => $siteLangId,
    ];

    $this->includeTemplate('_partial/header/content-header.php', $data); ?>
    <div class="content-body">
        <div class="row">
            <div class="col-lg-4 order-lg-2">
                <div class="widget-scroll">
                    <?php if ($userParentId == UserAuthentication::getLoggedUserId()) { ?>
                        <div class="widget widget-stats">
                            <a href="<?php echo UrlHelper::generateUrl('Account', 'credits'); ?>">
                                <div class="card card-commerce card-commerce-bg" style="background-image: url(<?php echo CONF_WEBROOT_URL; ?>/images/card-commerce-bg-1.png);">
                                    <div class="card-head border-0">
                                        <h5 class="card-title"><?php echo Labels::getLabel('LBL_Credits', $siteLangId); ?></h5>

                                    </div>
                                    <div class="card-body ">
                                        <div class="stats">
                                            <div class="stats-number">
                                                <ul>
                                                    <li>
                                                        <span class="total"><?php echo Labels::getLabel('LBL_AVAILABLE_BALANCE', $siteLangId); ?></span>
                                                        <span class="total-numbers"><?php echo CommonHelper::displayMoneyFormat($walletBalance); ?></span>
                                                    </li>
                                                    <li>
                                                        <span class="total"><?php echo Labels::getLabel('LBL_CREDITED_TODAY', $siteLangId); ?></span>
                                                        <span class="total-numbers"><?php echo CommonHelper::displayMoneyFormat($txnsSummary['total_earned']); ?></span>
                                                    </li>
                                                </ul>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </a>
                        </div>
                    <?php } ?>
                    <div class="widget widget-stats">
                        <a href="javascript:void(0)" onclick="redirectToPromotions('<?php echo UrlHelper::generateUrl('advertiser', 'promotions'); ?>')">
                            <div class="card card-commerce card-commerce-bg" style="background-image: url(<?php echo CONF_WEBROOT_URL; ?>/images/card-commerce-bg-2.png);">
                                <div class="card-head border-0">
                                    <h5 class="card-title">
                                        <?php echo Labels::getLabel('LBL_Active_Promotions', $siteLangId); ?></h5>
                                </div>
                                <div class="card-body ">
                                    <div class="stats">
                                        <div class="stats-number">
                                            <ul>
                                                <li>
                                                    <span class="total"><?php echo Labels::getLabel('LBL_Total_Active_promotions', $siteLangId); ?></span>
                                                    <span class="total-numbers"><?php echo $totActivePromotions; ?></span>
                                                </li>
                                                <li>
                                                    <span class="total"><?php echo Labels::getLabel('LBL_Active_promotions_Expense', $siteLangId); ?></span>
                                                    <span class="total-numbers"><?php echo CommonHelper::displayMoneyFormat($activePromotionChargedAmount); ?></span>
                                                </li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </a>
                    </div>
                    <div class="widget widget-stats">
                        <a href="<?php echo UrlHelper::generateUrl('advertiser', 'promotionCharges'); ?>">
                            <div class="card card-commerce card-commerce-bg" style="background-image: url(<?php echo CONF_WEBROOT_URL; ?>/images/card-commerce-bg-3.png);">
                                <div class="card-head border-0">
                                    <h5 class="card-title"><?php echo Labels::getLabel('LBL_All_Promotions', $siteLangId); ?>
                                    </h5>

                                </div>
                                <div class="card-body ">
                                    <div class="stats">
                                        <div class="stats-number">
                                            <ul>
                                                <li>
                                                    <span class="total"><?php echo Labels::getLabel('LBL_Total_Promotions', $siteLangId); ?></span>
                                                    <span class="total-numbers"><?php echo $totPromotions; ?></span>
                                                </li>
                                                <li>
                                                    <span class="total"><?php echo Labels::getLabel('LBL_Total_Expense', $siteLangId); ?></span>
                                                    <span class="total-numbers"><?php echo CommonHelper::displayMoneyFormat($totChargedAmount); ?></span>
                                                </li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </a>
                    </div>
                </div>
            </div>
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-head">
                        <h5 class="card-title "><?php echo Labels::getLabel('LBL_Active_Promotions', $siteLangId); ?>
                        </h5>
                        <?php if (count($activePromotions) > 0) { ?>
                            <div class="action">
                                <a href="<?php echo UrlHelper::generateUrl('advertiser', 'promotions'); ?>" class="link"><?php echo Labels::getLabel('Lbl_View_All', $siteLangId); ?></a>
                            </div>
                        <?php } ?>
                    </div>
                    <div class="card-table">
                        <div class="js-scrollable table-wrap table-responsive">
                            <table class="table table-justified">
                                <thead>
                                    <tr class="">
                                        <th><?php echo Labels::getLabel('LBL_Promotions', $siteLangId); ?></th>
                                        <th><?php echo Labels::getLabel('LBL_Budget', $siteLangId); ?></th>
                                        <th><?php echo Labels::getLabel('LBL_CPC', $siteLangId); ?></th>
                                        <th><?php echo Labels::getLabel('LBL_Duration', $siteLangId); ?></th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (count($activePromotions) > 0) {
                                        foreach ($activePromotions as $promotionId => $row) {
                                            $duraionStr = '<span class="form-text">' . Labels::getLabel('LBL_Start_Date', $siteLangId) . ' : </span>' . FatDate::format($row['promotion_start_date']) . '<br>';
                                            $duraionStr .= '<span class="form-text">' . Labels::getLabel('LBL_End_Date', $siteLangId) . ' : </span>' . FatDate::format($row['promotion_end_date']); ?>
                                            <tr>
                                                <td>
                                                    <div class="item">
                                                        <div class="item__description">
                                                            <div class="item__title"><?php echo $row['promotion_name']; ?></div>
                                                            <div class="item__brand form-text"><?php echo Labels::getLabel('LBL_Type', $siteLangId); ?> : <?php echo $typeArr[$row['promotion_type']]; ?></div>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <?php echo CommonHelper::displayMoneyFormat($row['promotion_budget']); ?>
                                                </td>
                                                <td>
                                                    <div class="item">
                                                        <div class="item__description">
                                                            <div class="item__brand form-text"><?php echo Labels::getLabel('LBL_CPC', $siteLangId); ?> : <?php echo CommonHelper::displayMoneyFormat($row['promotion_cpc']); ?></div>
                                                            <div class="item__title"><?php echo Labels::getLabel('LBL_TOTAL_CLICKS', $siteLangId); ?> : <?php echo FatUtility::int($row['clicks']); ?></div>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <?php echo $duraionStr; ?>
                                                </td>
                                                <td>
                                                    <ul class="actions">
                                                        <li>
                                                            <a title="<?php echo Labels::getLabel('LBL_Analytics', $siteLangId); ?>" href="<?php echo UrlHelper::generateUrl('advertiser', 'analytics', array($row['promotion_id'])); ?>">
                                                                <svg class="svg" width="18" height="18">
                                                                    <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite-actions.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#view">
                                                                    </use>
                                                                </svg>
                                                            </a>
                                                        </li>
                                                    </ul>
                                                </td>
                                            </tr>
                                        <?php
                                        }
                                    } else { ?>
                                        <tr>
                                            <td colspan="8">
                                                <?php $this->includeTemplate('_partial/no-record-found.php', array('siteLangId' => $siteLangId), false); ?>
                                            </td>
                                        </tr>
                                    <?php } ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <?php if ($userParentId == UserAuthentication::getLoggedUserId()) { ?>
                    <div class="card">
                        <div class="card-head">
                            <h5 class="card-title ">
                                <?php echo Labels::getLabel('LBL_Transaction_History', $siteLangId); ?></h5>
                            <?php if (count($transactions) > 0) { ?>
                                <div class="action">
                                    <a href="<?php echo UrlHelper::generateUrl('Account', 'credits'); ?>" class="link"><?php echo Labels::getLabel('Lbl_View_All', $siteLangId); ?></a>
                                </div>
                            <?php } ?>
                        </div>
                        <div class="card-table">
                            <div class="js-scrollable table-wrap table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr class="">
                                            <th><?php echo Labels::getLabel('LBL_Txn._Id', $siteLangId); ?></th>
                                            <th><?php echo Labels::getLabel('LBL_Date', $siteLangId); ?></th>
                                            <th><?php echo Labels::getLabel('LBL_Credit', $siteLangId); ?></th>
                                            <th><?php echo Labels::getLabel('LBL_Debit', $siteLangId); ?></th>
                                            <th><?php echo Labels::getLabel('LBL_Balance', $siteLangId); ?></th>
                                            <th><?php echo Labels::getLabel('LBL_Comments', $siteLangId); ?></th>
                                            <th><?php echo Labels::getLabel('LBL_Status', $siteLangId); ?></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (count($transactions) > 0) {
                                            foreach ($transactions as $row) { ?>
                                                <tr>
                                                    <td>
                                                        <div class="txn__id">
                                                            <?php echo Labels::getLabel('Lbl_Txn._Id', $siteLangId) ?>:
                                                            <?php echo Transactions::formatTransactionNumber($row['utxn_id']); ?>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <div class="txn__date">
                                                            <?php echo FatDate::format($row['utxn_date']); ?>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <div class="txn__credit">
                                                            <?php echo CommonHelper::displayMoneyFormat($row['utxn_credit']); ?>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <div class="txn__debit">
                                                            <?php echo CommonHelper::displayMoneyFormat($row['utxn_debit']); ?>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <div class="txn__balance">
                                                            <?php echo CommonHelper::displayMoneyFormat($row['balance']); ?>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <div class="txn__comments">
                                                            <?php echo $row['utxn_comments']; ?>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <span class="badge badge-inline <?php echo $txnStatusClassArr[$row['utxn_status']]; ?>">
                                                            <?php echo $txnStatusArr[$row['utxn_status']]; ?> </span>
                                                    </td>
                                                </tr>
                                            <?php }
                                        } else { ?>
                                            <tr>
                                                <td colspan="7">
                                                    <?php $this->includeTemplate('_partial/no-record-found.php', array('siteLangId' => $siteLangId), false); ?>
                                                </td>
                                            </tr>
                                        <?php } ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                <?php } ?>
            </div>

        </div>


    </div>
</div>

<script>
    redirectToPromotions = function(url) {
        var input = '<input type="hidden" name="active_promotion" value="' + 1 + '">';
        $('<form action="' + url + '" method="POST">' + input + '</form>').appendTo($(document.body)).submit();
    };
</script>