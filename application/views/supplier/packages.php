<?php defined('SYSTEM_INIT') or die('Invalid Usage.'); ?>
<div id="body" class="body seller-packages-page">
    <section class="section">
        <div class="container container--fixed">
            <div class="section-head section-head-center">
                <div class="section-heading">
                    <h2><?php echo Labels::getLabel('LBL_SELLER_SUBSCRIPTION_PACKAGES', $siteLangId); ?></h2>
                </div>
                <?php if (!empty($pageData['epage_content'])) { ?>
                    <p><?php echo html_entity_decode($pageData['epage_content']); ?></p>
                <?php } ?>
            </div>

            <?php if ($pendingPlanId > 0) { ?>
                <div class="alert alert--info mb-4">
                    <?php echo Labels::getLabel('MSG_YOUR_SELECTED_PACKAGE_IS_READY._COMPLETE_REGISTRATION_TO_CONTINUE.', $siteLangId); ?>
                </div>
            <?php } ?>

            <?php if (empty($packagesArr)) { ?>
                <?php $this->includeTemplate('_partial/no-record-found.php', array('siteLangId' => $siteLangId), false); ?>
            <?php } else { ?>
                <ul class="packages-box">
                    <?php
                    $packageArrClass = SellerPackages::getPackageClass();
                    $inc = 1;
                    foreach ($packagesArr as $package) { ?>
                        <li class="packages-box-item box <?php echo $packageArrClass[$inc] ?? ''; ?>">
                            <div class="packages-box-head">
                                <div class="name">
                                    <?php echo $package['spackage_name']; ?>
                                    <span><?php echo $package['spackage_text']; ?></span>
                                </div>
                                <div class="valid">
                                    <?php echo SellerPackagePlans::getCheapPlanPriceWithPeriod($package['cheapPlan'], $package['cheapPlan'][SellerPackagePlans::DB_TBL_PREFIX . 'price']); ?>
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
                                                <?php echo Labels::getlabel('LBL_Product_Inventory', $siteLangId) ?>
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
                            <div class="packages-box-foot">
                                <p><?php echo CommonHelper::replaceStringData(Labels::getLabel('LBL_SELECT_YOUR_{PACKAGE-NAME}_PRICE', $siteLangId), ['{PACKAGE-NAME}' => $package['spackage_name']]); ?></p>
                                <form method="post" action="<?php echo UrlHelper::generateUrl('Supplier', 'selectPackage'); ?>">
                                    <select name="spplan_id" class="form-select" required>
                                        <?php foreach ($package['plans'] as $plan) { ?>
                                            <option value="<?php echo $plan[SellerPackagePlans::DB_TBL_PREFIX . 'id']; ?>">
                                                <?php echo SellerPackagePlans::getPlanPriceWithPeriod($plan, $plan[SellerPackagePlans::DB_TBL_PREFIX . 'price']); ?>
                                            </option>
                                        <?php } ?>
                                    </select>
                                    <button type="submit" class="btn btn-brand btn-block mt-2">
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
