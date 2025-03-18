<?php if (isset($collection['faqs']) && count($collection['faqs']) > 0) {
    $faqCategories = array();
    foreach ($collection['faqs'] as $faq) {
        $faqCategories[$faq['faqcat_id']]['faqcat_name'] = $faq['faqcat_name'];
        $faqCategories[$faq['faqcat_id']]['faqs'][$faq['faq_id']] = $faq;
    } ?>
    <section class="section" data-section="section">
        <div class="container">
            <header class="section-head section-head-center">
                <div class="section-heading">
                    <h2>
                        <?php echo $collection['collection_name']; ?>
                    </h2>
                </div>
            </header>
            <div class="section-body">
                <div class="faq-layout-1">
                    <ul class="nav nav-tabs tabs-masking nav-tabs-center" role="tablist">
                        <?php $count = 0;
                        foreach ($faqCategories as $faqCatId => $faqCat) {
                            if ($count < Collections::LIMIT_FAQ_LAYOUT1) {
                                ?>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link <?php echo 0 == $count ? 'active' : ''; ?>" data-bs-toggle="tab"
                                        data-bs-target="#faq<?php echo $faqCatId . $collection['collection_id']; ?>" type="button"
                                        role="tab" aria-selected="true">
                                        <?php echo $faqCat['faqcat_name']; ?></button>
                                </li>
                                <?php
                            }
                            ?>

                            <?php $count++;
                        } ?>
                    </ul>
                    <div class="tab-content">
                        <?php $x = 0;
                        foreach ($faqCategories as $faqCatId => $faqCat) {
                            $x++; ?>
                            <div class="tab-pane fade <?php echo 1 == $x ? 'show active' : ''; ?>"
                                id="faq<?php echo $faqCatId . $collection['collection_id']; ?>">
                                <ul class="faq-list"
                                    id="faqCollapseParent<?php echo $faqCatId . $collection['collection_id']; ?>">
                                    <?php
                                    $i = 0;
                                    foreach ($faqCat['faqs'] as $faqId => $faq) { ?>
                                        <li class="faq-list-item">
                                            <button class="faq-list-link collapsed" type="button" data-bs-toggle="collapse"
                                                data-bs-target="#faqCollapse<?php echo $faqId; ?>"
                                                aria-expanded="<?php echo ($i == 0 ? 'true' : 'false'); ?>">
                                                <?php echo $faq['faq_title']; ?>
                                            </button>
                                            <div class="collapse <?php echo ($i == 0 ? 'show' : ''); ?>"
                                                id="faqCollapse<?php echo $faqId; ?>"
                                                data-parent="#faqCollapseParent<?php echo $faqCatId . $collection['collection_id']; ?>">
                                                <p class="faq_data">
                                                    <?php echo FatUtility::decodeHtmlEntities($faq['faq_content']); ?>
                                                </p>
                                            </div>
                                        </li>
                                        <?php $i++;
                                    } ?>
                                </ul>
                            </div>

                        <?php }
                        if (count($faqCategories) > Collections::LIMIT_FAQ_LAYOUT1) { ?>
                        </div>
                        <div class="section-foot">
                            <a class="link-underline"
                                href="<?php echo UrlHelper::generateUrl('custom', 'faq'); ?>"><?php echo Labels::getLabel('LBL_View_All', $siteLangId); ?>
                            </a>
                        </div>
                    </div>
                </div>
            <?php } ?>
        </div>
    </section>
<?php } ?>
<script>
    var $linkMoreText = '<?php echo Labels::getLabel('Lbl_SHOW_MORE', $siteLangId); ?>';
    var $linkLessText = '<?php echo Labels::getLabel('Lbl_SHOW_LESS', $siteLangId); ?>';
</script>