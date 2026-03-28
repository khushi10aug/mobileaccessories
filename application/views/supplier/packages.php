<?php defined('SYSTEM_INIT') or die('Invalid Usage.'); ?>
<div id="body" class="body seller-packages-page">
    <section class="section supplier-pkg-section">
        <div class="container container--fixed">
            <div class="section-head section-head-center supplier-pkg-hero">
                <div class="section-heading">
                    <h2><?php echo Labels::getLabel('LBL_SELLER_SUBSCRIPTION_PACKAGES', $siteLangId); ?></h2>
                </div>
                
            </div>

            <?php if ($pendingPlanId > 0) { ?>
                <div class="alert alert--info mb-4">
                    <?php echo Labels::getLabel('MSG_YOUR_SELECTED_PACKAGE_IS_READY._COMPLETE_REGISTRATION_TO_CONTINUE.', $siteLangId); ?>
                </div>
            <?php } ?>

            <?php if (empty($packagesArr)) { ?>
                <?php $this->includeTemplate('_partial/no-record-found.php', array('siteLangId' => $siteLangId), false); ?>
            <?php } else { ?>
                <ul class="packages-box supplier-pkg-grid">
                    <?php
                    $packageArrClass = SellerPackages::getPackageClass();
                    $inc = 1;
                    foreach ($packagesArr as $package) {
                        $descLines = [];
                        if (!empty($package['spackage_description'])) {
                            $descLines = preg_split("/\r\n|\n|\r/", (string) $package['spackage_description']);
                            $descLines = array_values(array_filter(array_map('trim', $descLines)));
                        }
                        ?>
                        <li class="packages-box-item supplier-pkg-card box <?php echo $packageArrClass[$inc] ?? ''; ?>">
                            <div class="packages-box-head supplier-pkg-card__head">
                                <div class="supplier-pkg-card__title-block">
                                    <h3 class="supplier-pkg-card__name"><?php echo $package['spackage_name']; ?></h3>
                                    <?php if (!empty($package['spackage_text'])) { ?>
                                        <p class="supplier-pkg-card__tagline"><?php echo $package['spackage_text']; ?></p>
                                    <?php } ?>
                                </div>
                                <div class="supplier-pkg-card__from" aria-label="<?php echo Labels::getLabel('LBL_PLAN_PRICE', $siteLangId); ?>">
                                    <span class="supplier-pkg-card__from-label"><?php echo Labels::getLabel('LBL_FROM', $siteLangId); ?></span>
                                    <div class="valid supplier-pkg-card__price-preview">
                                        <?php echo SellerPackagePlans::getCheapPlanPriceDisplayForPackage($package['cheapPlan']); ?>
                                    </div>
                                </div>
                            </div>

                            <div class="packages-box-body supplier-pkg-card__body">
                                <ul class="features supplier-pkg-stats p-0">
                                    <li class="features-item supplier-pkg-stat">
                                        <span><?php echo CommonHelper::displayComissionPercentage($package[SellerPackages::DB_TBL_PREFIX . 'commission_rate']); ?>%</span>
                                        <?php echo Labels::getLabel('LBL_Commision_rate', $siteLangId); ?>
                                    </li>
                                    <li class="features-item supplier-pkg-stat">
                                        <span><?php echo $package[SellerPackages::DB_TBL_PREFIX . 'products_allowed']; ?></span>
                                        <?php echo ($package[SellerPackages::DB_TBL_PREFIX . 'products_allowed'] == 1) ? Labels::getlabel('LBL_active_product', $siteLangId) : Labels::getlabel('LBL_active_products', $siteLangId); ?>
                                    </li>
                                    <?php if (1 > FatApp::getConfig('CONF_WITHOUT_PROD_VARIANTS', FatUtility::VAR_INT, 0)) { ?>
                                        <li class="features-item supplier-pkg-stat">
                                            <span><?php echo $package[SellerPackages::DB_TBL_PREFIX . 'inventory_allowed']; ?></span>
                                            <?php echo Labels::getlabel('LBL_Product_Inventory', $siteLangId); ?>
                                        </li>
                                    <?php } ?>
                                    <li class="features-item supplier-pkg-stat">
                                        <span><?php echo $package[SellerPackages::DB_TBL_PREFIX . 'images_per_product']; ?></span>
                                        <?php echo ($package[SellerPackages::DB_TBL_PREFIX . 'images_per_product'] == 1) ? Labels::getlabel('LBL_image_per_product', $siteLangId) : Labels::getlabel('LBL_images_per_product', $siteLangId); ?>
                                    </li>
                                    <li class="features-item supplier-pkg-stat">
                                        <span><?php echo CommonHelper::replaceStringData(Labels::getLabel('LBL_{LIMIT}_RFQ_OFFERS', $siteLangId), ['{LIMIT}' => $package[SellerPackages::DB_TBL_PREFIX . 'rfq_offers_allowed']]); ?></span>
                                    </li>
                                </ul>

                                <?php if (!empty($descLines)) { ?>
                                    <details class="supplier-pkg-details">
                                        <summary class="supplier-pkg-details__summary"><?php echo Labels::getLabel('LBL_VIEW_DETAILS', $siteLangId); ?></summary>
                                        <ul class="features p-0 package-desc-list supplier-pkg-details__list">
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
                                    </details>
                                <?php } ?>
                            </div>

                            <div class="packages-box-foot supplier-pkg-card__foot">
                                <p class="supplier-pkg-card__pick-label"><?php echo Labels::getLabel('LBL_SELECT_PLAN', $siteLangId); ?></p>
                                <form method="post" action="<?php echo UrlHelper::generateUrl('Supplier', 'selectPackage'); ?>" class="supplier-pkg-form">
                                    <div class="supplier-pkg-plans" role="radiogroup" aria-label="<?php echo Labels::getLabel('LBL_SELECT_PLAN', $siteLangId); ?>">
                                        <?php foreach ($package['plans'] as $pi => $plan) {
                                            $planId = (int) $plan[SellerPackagePlans::DB_TBL_PREFIX . 'id'];
                                            $checked = (0 === $pi) ? ' checked' : '';
                                            ?>
                                            <label class="supplier-pkg-plan">
                                                <input type="radio" name="spplan_id" value="<?php echo $planId; ?>" required<?php echo $checked; ?>>
                                                <span class="supplier-pkg-plan__ui"><?php echo SellerPackagePlans::getPlanPriceDisplayForPackage($plan); ?></span>
                                            </label>
                                        <?php } ?>
                                    </div>
                                    <button type="submit" class="btn btn-brand btn-block supplier-pkg-submit">
                                        <?php echo Labels::getLabel('LBL_CONTINUE', $siteLangId); ?>
                                    </button>
                                </form>
                            </div>
                        </li>
                    <?php $inc++;
                    } ?>
                </ul>
            <?php } ?>
        </div>
    </section>
</div>
