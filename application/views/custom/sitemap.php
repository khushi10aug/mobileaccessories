<div id="body" class="body">
    <?php $this->includeTemplate('_partial/page-head-section.php', ['headLabel' => Labels::getLabel('LBL_SITEMAP'), 'includeBreadcrumb' => true]); ?>
    <section class="section" data-section="section">
        <div class="container">
            <div class="row">
                <div class="col-lg-12 ">
                    <div class="cg-main">
                        <?php if (!empty($contentPages)) { ?>
                            <h5 class="big-title-main">
                                <?php echo Labels::getLabel('LBL_CONTENT_PAGES', $siteLangId); ?>
                            </h5>
                            <div class="cg-main-item">
                                <ul>
                                    <?php
                                    foreach ($contentPages as $contentId => $contentPageName) {
                                        ?>
                                        <li>
                                            <a href="<?php echo UrlHelper::generateUrl('cms', 'view', array($contentId)); ?>">
                                                <?php echo $contentPageName; ?>
                                            </a>
                                        </li>
                                    <?php } ?>
                                </ul>
                            </div>
                            <?php
                        }
                        if ($categoriesArr) { ?>
                            <h5 class="big-title-main">
                                <?php echo Labels::getLabel('LBL_Categories', $siteLangId); ?>
                            </h5>

                            <?php $this->includeTemplate('_partial/custom/categories-list.php', array('categoriesArr' => $categoriesArr), false); ?>

                            <?php
                        }
                        if (!empty($allShops)) { ?>
                            <h5 class="big-title-main">
                                <?php echo Labels::getLabel('LBL_Shops', $siteLangId); ?>
                            </h5>
                            <div class="cg-main-item">
                                <ul>
                                    <?php foreach ($allShops as $shop) {
                                        ?>
                                        <li>
                                            <a
                                                href="<?php echo UrlHelper::generateUrl('Shops', 'view', array($shop['shop_id'])); ?>">
                                                <?php echo $shop['shop_name'] ?? $shop['shop_identifier']; ?>
                                            </a>
                                        </li>
                                    <?php } ?>
                                </ul>
                            </div>
                            <?php
                        }

                        if (!empty($allBrands)) { ?>
                            <h5 class="big-title-main">
                                <?php echo Labels::getLabel('LBL_Brands', $siteLangId); ?>
                            </h5>
                            <div class="cg-main-item">
                                <ul>
                                    <?php foreach ($allBrands as $brands) {
                                        ?>
                                        <li>
                                            <a
                                                href="<?php echo UrlHelper::generateUrl('Brands', 'view', array($brands['brand_id'])); ?>">
                                                <?php echo $brands['brand_name']; ?>
                                            </a>
                                        </li>
                                    <?php } ?>
                                </ul>
                            </div>
                            <?php
                        }
                        ?>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>