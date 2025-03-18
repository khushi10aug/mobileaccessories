<?php defined('SYSTEM_INIT') or die('Invalid Usage.'); ?>
<?php if (isset($collection['categories']) && count($collection['categories'])) {
    $displayIcon = (Collections::TYPE_CATEGORY_LAYOUT7 == $collection['collection_layout_type']) ? true : false;
    ?>
<section class="section" data-collection="collection-categories">
    <div class="container">
        <div class="section-body">
            <div class="js-carousel industry-carousal" data-slides="8,6,4,4,4" data-arrows="true" data-slickdots="true"
                dir="<?php echo CommonHelper::getLayoutDirection(); ?>">
                <?php
                    foreach ($collection['categories'] as $category) {
                        $rootParentId = FatUtility::int(current(explode('_', $category['prodcat_code'])));
                        $rootParentId = (1 > $rootParentId) ? $category['prodcat_id'] : $rootParentId;
                        ?>
                <div class="industry-carousal-item">
                    <?php
                            $imageType = ($displayIcon == true) ? AttachedFile::FILETYPE_CATEGORY_ICON : AttachedFile::FILETYPE_CATEGORY_IMAGE;
                            $image = AttachedFile::getAttachment($imageType, $category['prodcat_id']);
                            if (!empty($image) && $image['afile_id'] <= 0) {
                                $image = AttachedFile::getAttachment($imageType, $rootParentId);
                            }
                            $uploadedTime = AttachedFile::setTimeParam($image['afile_updated_at']);
                            $afile_record_id = $image['afile_record_id'];
                            $afile_lang_id = $image['afile_lang_id'];
                            $afile_id = $image['afile_id'];

                            $viewType = ($displayIcon == true) ? ImageDimension::VIEW_ICON : ImageDimension::VIEW_THUMB;
                            $catIconUrl = UrlHelper::generateFileUrl('Category', $viewType, array($afile_record_id, $afile_lang_id, ImageDimension::VIEW_THUMB, $afile_id), CONF_WEBROOT_FRONT_URL) . $uploadedTime;
                            $prodCatUrl = UrlHelper::generateUrl('Category', 'View', array($category['prodcat_id']));
                            ?>
                    <a class="industry-carousal-link" title="<?php echo $category['prodcat_name']; ?>"
                        href="<?php echo $prodCatUrl; ?>">
                        <div class="industry-carousal-block">
                            <img class="industry-carousal-icon" src="<?php echo $catIconUrl; ?>" alt="<?php echo $category['prodcat_name']; ?>">
                            <div class="industry-carousal-name"><span><?php echo $category['prodcat_name']; ?></span>
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
    var displaySize = 8;
    $('.industryCarousalJs').not('.slick-initialized').slick({
        rtl: ('rtl' == langLbl.layoutDirection),
        draggable: true,
        slidesToShow: displaySize,
        slidesToScroll: 1,
        arrows: true,
        prevArrow: '<button class="slick-arrow slick-prev"><span></span> </button>',
        nextArrow: '<button class="slick-arrow slick-next"><span></span> </button>',
        responsive: [{
                breakpoint: 1180,
                settings: {
                    slidesToShow: displaySize,
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
                    slidesToShow: 3,
                }
            }
        ]
    })
    </script>
</section>
<?php } ?>