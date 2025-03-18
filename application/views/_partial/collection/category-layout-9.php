<?php defined('SYSTEM_INIT') or die('Invalid Usage.'); ?>
<?php if (isset($collection['categories']) && count($collection['categories'])) {
    $recordCount = count($collection['categories']);
    ?>
    <section class="section" data-collection="collection-categories">
        <div class="container">
            <div class="section-body">
                <div class="catalog-carousal">
                    <?php
                    foreach ($collection['categories'] as $category) {
                        $rootParentId = FatUtility::int(current(explode('_', $category['prodcat_code'])));
                        $rootParentId = (1 > $rootParentId) ? $category['prodcat_id'] : $rootParentId;
                        ?>
                        <div class="catalog-carousal-item">
                            <?php
                            $image = AttachedFile::getAttachment(AttachedFile::FILETYPE_CATEGORY_THUMB, $category['prodcat_id']);
                            if (!empty($image) && $image['afile_id'] <= 0) {
                                $image = AttachedFile::getAttachment(AttachedFile::FILETYPE_CATEGORY_THUMB, $rootParentId);
                            }
                            $uploadedTime = AttachedFile::setTimeParam($image['afile_updated_at']);
                            $afile_record_id = $image['afile_record_id'];
                            $afile_lang_id = $image['afile_lang_id'];
                            $afile_id = $image['afile_id'];

                            $catIconUrl = UrlHelper::generateFileUrl('Category', 'thumb', array($afile_record_id, $afile_lang_id, ImageDimension::VIEW_ICON, $afile_id), CONF_WEBROOT_FRONT_URL) . $uploadedTime;
                            $prodCatUrl = UrlHelper::generateUrl('Category', 'View', array($category['prodcat_id']));
                            ?>
                            <a class="catalog-carousal-link" title="<?php echo $category['prodcat_name']; ?>"
                                href="<?php echo $prodCatUrl; ?>">
                                <div class="catalog-carousal-block">
                                    <img class="catalog-carousal-icon" src="<?php echo $catIconUrl; ?>" alt="<?php echo $category['prodcat_name']; ?>">
                                    <div class="catalog-carousal-name"><?php echo $category['prodcat_name']; ?>
                                    </div>
                                </div>
                            </a>

                        </div>
                    <?php } ?>
                </div>
            </div>
            <?php /*if (count($collection['categories']) > Collections::LIMIT_CATEGORY_LAYOUT3) { ?>
       <div class="section-foot">
           <a href="<?php echo UrlHelper::generateUrl('Collections', 'View', array($collection['collection_id'])); ?>"
               class="link-underline">
               <?php echo Labels::getLabel('LBL_VIEW_ALL', $siteLangId); ?>
           </a>
       </div>
       <?php } */ ?>
        </div>
        <script>
            $('.industryCarousalJs').not('.slick-initialized').slick({
                rtl: ('rtl' == langLbl.layoutDirection),
                draggable: true,
                slidesToShow: 8,
                slidesToScroll: 1,
                arrows: true,
                prevArrow: '<button class="slick-arrow slick-prev"><span></span> </button>',
                nextArrow: '<button class="slick-arrow slick-next"><span></span> </button>',
                responsive: [{
                    breakpoint: 1180,
                    settings: {
                        slidesToShow: 8,
                    }
                },
                {
                    breakpoint: 769,
                    settings: {
                        slidesToShow: 5,
                    }
                },
                {
                    breakpoint: 480,
                    settings: {
                        slidesToShow: 4,
                    }
                }
                ]
            })
        </script>
    </section>
<?php } ?>