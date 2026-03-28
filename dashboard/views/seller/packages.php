<?php defined('SYSTEM_INIT') or die('Invalid Usage.');
$this->includeTemplate('_partial/dashboardNavigation.php');
$totalPackages = count($packagesArr);
?>

<div class="content-wrapper content-space">
    <div class="content-body">
        <?php if (!empty($pageData)) { ?>
            <div class="section-head section-head-center my-4">
                <div class="section-heading">
                    <?php echo html_entity_decode($pageData['epage_content']); ?>
                </div>
            </div>
        <?php
        }
        if (1 > $currentActivePlanId && !$canEdit) {
            echo HtmlHelper::getErrorMessageHtml(Labels::getLabel('ERR_PARENT_MERCHANT_MUST_NEED_TO_BUY_A_VALID_SUBSCRIPTION.', $siteLangId));
        }

        if ($totalPackages > 0) { ?>
            <ul class="packages-box">
                <?php
                $packageArrClass = SellerPackages::getPackageClass();
                $inc = 1;
                foreach ($packagesArr as $package) {
                    $planIds = array_column($package['plans'], SellerPackagePlans::DB_TBL_PREFIX . 'id');
                    $selectedClass = '';
                    if (in_array($currentActivePlanId, $planIds)) {
                        $selectedClass = 'is-active';
                    } ?>
                    <li class="packages-box-item packagesBoxJs box <?php echo $packageArrClass[$inc] . " " . $selectedClass ?>">
                        <div class="packages-box-head">
                            <div class="name">
                                <?php echo $package['spackage_name']; ?>
                                <span><?php echo $package['spackage_text']; ?></span>
                            </div>
                            <div class="valid">
                                <?php
                                if (in_array($currentActivePlanId, $planIds)) {
                                    echo SellerPackagePlans::getCheapPlanPriceDisplayForPackage($currentPlanData);
                                } else {
                                    echo SellerPackagePlans::getCheapPlanPriceDisplayForPackage($package['cheapPlan']);
                                }
                                ?>
                            </div>
                        </div>
                        <div class="packages-box-body">
                            <div class="trial">
                                <ul class="features p-0">
                                    <li class="features-item">
                                        <span><?php echo CommonHelper::displayComissionPercentage($package[SellerPackages::DB_TBL_PREFIX . 'commission_rate']); ?>%</span>
                                        <?php echo Labels::getLabel('LBL_Commision_rate', $siteLangId); ?>
                                    </li>
                                    <li class="features-item">
                                        <span><?php echo $package[SellerPackages::DB_TBL_PREFIX . 'products_allowed']; ?></span>
                                        <?php echo ($package[SellerPackages::DB_TBL_PREFIX . 'products_allowed'] == 1) ? Labels::getlabel('LBL_active_product', $siteLangId) : Labels::getlabel('LBL_active_products', $siteLangId); ?>
                                    </li>
                                    <?php if (1 > FatApp::getConfig('CONF_WITHOUT_PROD_VARIANTS', FatUtility::VAR_INT, 0)) { ?>
                                        <li class="features-item">
                                            <span><?php echo $package[SellerPackages::DB_TBL_PREFIX . 'inventory_allowed']; ?></span>
                                            <?php echo  Labels::getlabel('LBL_Product_Inventory', $siteLangId) ?>
                                        </li>
                                    <?php } ?>
                                    <li class="features-item">
                                        <span><?php echo $package[SellerPackages::DB_TBL_PREFIX . 'images_per_product']; ?></span>
                                        <?php echo ($package[SellerPackages::DB_TBL_PREFIX . 'images_per_product'] == 1) ? Labels::getlabel('LBL_image_per_product', $siteLangId) : Labels::getlabel('LBL_images_per_product', $siteLangId); ?>
                                    </li>
                                    <li class="features-item">
                                        <span><?php echo CommonHelper::replaceStringData(Labels::getLabel('LBL_{LIMIT}_RFQ_OFFERS', $siteLangId), ['{LIMIT}' => $package[SellerPackages::DB_TBL_PREFIX . 'rfq_offers_allowed']]); ?></span>
                                    </li>
                                </ul>
                                <?php
                                $descLines = [];
                                if (!empty($package['spackage_description'])) {
                                    $descLines = preg_split("/\r\n|\n|\r/", (string) $package['spackage_description']);
                                    $descLines = array_values(array_filter(array_map('trim', $descLines)));
                                }
                                if (!empty($descLines)) { ?>
                                    <ul class="features p-0 package-desc-list">
                                        <?php foreach ($descLines as $line) {
                                            $iconType = 'check';
                                            $text = $line;
                                            $prefix = substr($line, 0, 1);
                                            if ('+' === $prefix) {
                                                $iconType = 'check';
                                                $text = trim(substr($line, 1));
                                            } elseif ('-' === $prefix) {
                                                $iconType = 'cross';
                                                $text = trim(substr($line, 1));
                                            }
                                            if ('' === $text) {
                                                continue;
                                            } ?>
                                            <li class="features-item features-item--desc">
                                                <span class="desc-check <?php echo ('cross' === $iconType) ? 'desc-check--cross' : ''; ?>">
                                                    <?php echo ('cross' === $iconType) ? '×' : '✓'; ?>
                                                </span>
                                                <span class="desc-text"><?php echo $text; ?></span>
                                            </li>
                                        <?php } ?>
                                    </ul>
                                <?php } ?>
                            </div>
                        </div>
                        <?php if ($canEdit) { ?>
                            <div class="packages-box-foot">
                                <p>
                                    <?php
                                    echo CommonHelper::replaceStringData(Labels::getLabel('LBL_SELECT_YOUR_{PACKAGE-NAME}_PRICE', $siteLangId), ['{PACKAGE-NAME}' => $package['spackage_name']]);
                                    ?>
                                </p>
                                <?php $disabled = (!$canEdit) ? 'disabled=disabled' : ''; ?>
                                <select name="packages" class="form-select packagesJS" <?php echo $disabled; ?>>
                                    <?php foreach ($package['plans'] as $plan) {
                                        $isActive = ($currentActivePlanId == $plan[SellerPackagePlans::DB_TBL_PREFIX . 'id']) ? 'selected=selected' : '';
                                    ?>
                                        <option value="<?php echo $plan[SellerPackagePlans::DB_TBL_PREFIX . 'id']; ?>" <?php echo $isActive; ?>>
                                            <?php echo SellerPackagePlans::getPlanPriceDisplayForPackage($plan); ?>
                                        </option>
                                    <?php } ?>
                                </select>

                                <?php if ($currentActivePlanId) {
                                    $buyPlanText = Labels::getLabel('LBL_Change_Plan', $siteLangId);
                                } else {
                                    $buyPlanText = Labels::getLabel('LBL_Buy_Plan', $siteLangId);
                                } ?>
                                <button class="btn btn-brand btn-block buySubscription--js " type="button" data-id="<?php echo $package[SellerPackages::DB_TBL_PREFIX . 'id']; ?>"><?php echo $buyPlanText; ?>
                                </button>
                            </div>
                        <?php } ?>
                    </li>
                <?php $inc++;
                } ?>
            </ul>
        <?php } else {
            $message = Labels::getLabel('LBL_NO_SUBSCRIPTION_PACKAGE_FOUND', $siteLangId);
            $this->includeTemplate('_partial/no-record-found.php', array('siteLangId' => $siteLangId, 'message' => $message));
        } ?>
    </div>
</div>

<script>
    var currentActivePlanId = <?php echo ($currentActivePlanId) ? $currentActivePlanId : 0; ?>
</script>

<style>
    .packages-box .package-desc-list {
        margin-top: 14px;
        padding-top: 14px;
        border-top: 1px solid rgba(0, 0, 0, 0.08);
        text-align: left;
    }

    .packages-box .features-item--desc {
        display: grid;
        grid-template-columns: 18px 1fr;
        gap: 10px;
        align-items: start;
        padding: 6px 0;
        list-style: none;
        margin: 0;
    }

    .packages-box .features-item--desc .desc-check {
        display: inline-flex;
        width: 18px;
        height: 18px;
        align-items: center;
        justify-content: center;
        border-radius: 999px;
        background: rgba(16, 185, 129, 0.12);
        color: #065f46;
        font-weight: 900;
        font-size: 12px;
        line-height: 1;
        margin-top: 2px;
    }

    .packages-box .features-item--desc .desc-check--cross {
        background: rgba(244, 63, 94, 0.12);
        color: #9f1239;
    }

    .packages-box .features-item--desc .desc-text {
        font-weight: 500;
        line-height: 1.45;
    }
</style>