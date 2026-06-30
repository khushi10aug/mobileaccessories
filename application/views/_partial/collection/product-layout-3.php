<?php defined('SYSTEM_INIT') or die('Invalid Usage.');
if (isset($collection['products']) && count($collection['products']) > 0) { ?>
    <section class="section bg-gray" data-section="section">
        <div class="container">
            <header class="section-head">
                <div class="section-heading">
                    <h2><?php echo ($collection['collection_name'] != '') ? $collection['collection_name'] : ''; ?></h2>
                </div> <?php if ($collection['totProducts'] > $collection['collection_primary_records']) { ?>
                    <div class="section-action">
                        <a href="<?php echo UrlHelper::generateUrl('Collections', 'View', array($collection['collection_id'])); ?>"
                            class="link-underline">
                            <?php echo Labels::getLabel('LBL_VIEW_ALL', $siteLangId); ?>
                        </a>
                    </div>
                <?php } ?>
            </header>
            <div class="section-body">
                <div class="product-layout-3"
                    data-view="<?php echo (0 < $collection['collection_primary_records'] ? $collection['collection_primary_records'] : 3); ?>">
                    <?php
                    $tRightRibbons = $collection['tRightRibbons'];
                    $i = 1;
                    foreach ($collection['products'] as $product) {
                        $selProdRibbons = [];
                        if (array_key_exists($product['selprod_id'], $tRightRibbons)) {
                            $selProdRibbons[] = $tRightRibbons[$product['selprod_id']];
                        }
                        $layoutClass = 'product-item';
                        $displayProductNotAvailableLable = false;
                        if (FatApp::getConfig('CONF_ENABLE_GEO_LOCATION', FatUtility::VAR_INT, 0) && !empty(FatApp::getConfig('CONF_GOOGLEMAP_API_KEY', FatUtility::VAR_STRING, ''))) {
                            $displayProductNotAvailableLable = true;
                        }
                        include('product-layout-3-list.php');
                        $i++;
                    } ?>
                </div>
            </div>
        </div>
    </section>
<?php }
