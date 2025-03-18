<?php defined('SYSTEM_INIT') or die('Invalid usage');
$totReviews = $avgRating = $pixelToFillRight = 0;
if (!empty($reviews)) {
    $totReviews = (!empty($reviews['totReviews'])) ? FatUtility::int($reviews['totReviews']) : 0;
    $avgRating = (!empty($reviews['prod_rating'])) ? FatUtility::convertToType($reviews['prod_rating'], FatUtility::VAR_FLOAT) : 0;

    $pixelToFillRight = $avgRating / 5 * 160;
    $pixelToFillRight = FatUtility::convertToType($pixelToFillRight, FatUtility::VAR_FLOAT);
}

$productView = $productView ?? false;
?>
<section class="section border-top section--reviews">
    <div class="container" id="itemRatings">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="customer-reviews">
                    <?php if (true === $productView) { ?>
                        <div class="customer-reviews-head">
                            <div>
                                <h2 class="title"><?php echo Labels::getLabel('LBL_CUSTOMER_REVIEWS', $siteLangId); ?></h2>
                                <?php if (1 > $totReviews) { ?>
                                    <p class="mb-0">
                                        <?php echo Labels::getLabel('LBL_SHARE_YOUR_THOUGHTS_WITH_OTHER_CUSTOMERS', $siteLangId); ?>
                                    </p>
                                <?php } ?>
                            </div>
                            <?php if ($totReviews > 0) { ?>
                                <div class="">
                                    <div class="sort-byx" title="<?php echo Labels::getLabel("LBL_SORT_BY", $siteLangId); ?>"
                                        data-bs-toggle="tooltip">
                                        <div class="dropdown">
                                            <button class="dropdown-toggle-custom btn btn-outline-gray btn-dropdown"
                                                type="button" data-bs-toggle="dropdown" data-bs-auto-close="outside"
                                                data-display="static" aria-haspopup="true" aria-expanded="false">
                                                <span
                                                    class="sortByTxtJs"><?php echo Labels::getLabel('LBL_MOST_HELPFUL', $siteLangId); ?></span>
                                                <i class="dropdown-toggle-custom-arrow"></i>
                                            </button>

                                            <ul class="dropdown-menu dropdown-menu-fit dropdown-menu-right dropdown-menu-anim">
                                                <li class="dropdown-menu-item">
                                                    <a class="dropdown-menu-link sortByEleJs active" href="javascript:void(0);"
                                                        data-sort='most_helpful' onclick="getSortedReviews(this);return false;">
                                                        <?php echo Labels::getLabel('LBL_MOST_HELPFUL', $siteLangId); ?>
                                                    </a>
                                                </li>
                                                <li class="dropdown-menu-item">
                                                    <a class="dropdown-menu-link sortByEleJs" href="javascript:void(0);"
                                                        data-sort='most_recent' onclick="getSortedReviews(this);return false;">
                                                        <?php echo Labels::getLabel('LBL_MOST_RECENT', $siteLangId); ?>
                                                    </a>
                                                </li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            <?php } else { ?>
                                <div class="rating-block m-0">
                                    <div class="review-cta">
                                        <button class="btn btn-brand btn-wide" type="button"
                                            onclick="rateAndReviewProduct(<?php echo $product_id; ?>)">
                                            <?php echo Labels::getLabel('LBL_WRITE_A_REVIEW', $siteLangId); ?>
                                        </button>
                                    </div>
                                </div>
                            <?php } ?>
                        </div>
                    <?php } ?>
                    <?php if ($totReviews > 0) { ?>
                        <div class="customer-reviews-body">
                            <div class="all-reviews-images reviewsImageblockJs" style="display:none;">
                                <h6 class="h6"> <?php echo Labels::getLabel('LBL_REVIEWS_WITH_IMAGES'); ?></h6>
                                <div class="review-images reviewImagesListJs"></div>
                                <script>
                                    reviewsWithImages(<?php echo $product['selprod_id']; ?>);
                                </script>
                            </div>
                            <div class="divider my-5 reviewsImageblockJs" style="display:none;"></div>
                            <div class="rating-layout">
                                <!-- Rating Section -->
                                <div class="rating-layout-start">
                                    <div class="sticky-md-top">
                                        <div class="product-card">
                                            <?php if (false === $productView && !empty($product)) { ?>
                                                <div class="product-card-start">
                                                    <div class="product-card-img">
                                                        <img alt="<?php echo $product['product_name']; ?>"
                                                            src="<?php echo UrlHelper::getCachedUrl(UrlHelper::generateFileUrl('image', 'product', array($product['product_id'], ImageDimension::VIEW_SMALL, $product['selprod_id'], 0, $siteLangId)), CONF_IMG_CACHE_TIME, '.jpg'); ?>"
                                                            <?php echo HtmlHelper::getImgDimParm(ImageDimension::TYPE_PRODUCTS, ImageDimension::VIEW_SMALL); ?>>
                                                    </div>
                                                </div>
                                            <?php } ?>
                                            <div class="product-card-end">
                                                <?php
                                                if (!empty($product) && !$productView) { ?>
                                                    <div class="product-description">
                                                        <?php include(CONF_THEME_PATH . 'products/product-info.php'); ?>
                                                    </div>
                                                <?php } ?>
                                                <div class="rating-block">
                                                    <div class="average-rating">
                                                        <span class="rate"><?php echo round($avgRating, 1); ?>
                                                            <svg class="svg svg-star" width="16" height="16">
                                                                <use
                                                                    xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#star-yellow">
                                                                </use>
                                                            </svg>
                                                        </span>
                                                        <span
                                                            class="totals"><?php echo $totReviews . ' ' . Labels::getLabel("LBL_REVIEWS", $siteLangId); ?></span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="divider"></div>
                                        <?php $this->includeTemplate('_partial/product-overall-ratings.php', [
                                            'reviews' => $reviews,
                                            'ratingAspects' => $ratingAspects,
                                            'siteLangId' => $siteLangId,
                                            'canSubmitFeedback' => $canSubmitFeedback,
                                            'product_id' => $product_id,
                                        ], false); ?>
                                    </div>
                                </div>
                                <!-- Rating Section -->

                                <!-- Comments Section -->
                                <div class="rating-layout-end">
                                    <div class="reviewListJs"></div>
                                    <div id="loadMoreReviewsBtnDiv" class="align-center"></div>
                                </div>
                                <!-- Comments Section -->
                            </div>
                        </div>
                    <?php } else { ?>
                        <div class="no-reviews">
                            <h6><?php echo Labels::getLabel('MSG_BE_THE_FIRST_ONE_TO_WRITE_A_REVIEW!', $siteLangId); ?></h6>
                            <img width="320" src="<?php echo CONF_WEBROOT_URL . 'images/retina/no-reviews.svg' ?>">
                        </div>
                    <?php } ?>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
    var $linkMoreText = '<?php echo Labels::getLabel('Lbl_SHOW_MORE', $siteLangId); ?>';
    var $linkLessText = '<?php echo Labels::getLabel('Lbl_SHOW_LESS', $siteLangId); ?>';
    $('#itemRatings div.progress__fill').css({
        'clip': 'rect(0px, <?php echo $pixelToFillRight; ?>px, 160px, 0px)'
    });

    $(function () {
        function DropDown(el) {
            this.dd = el;
            this.placeholder = this.dd.children('span');
            this.opts = this.dd.find('ul.drop li');
            this.val = '';
            this.index = -1;
            this.initEvents();
        }

        DropDown.prototype = {
            initEvents: function () {
                var obj = this;
                obj.dd.on('click', function (e) {
                    e.preventDefault();
                    e.stopPropagation();
                    $(this).toggleClass('active');
                });
                obj.opts.on('click', function () {
                    var opt = $(this);
                    obj.val = opt.text();
                    obj.index = opt.index();
                    obj.placeholder.text(obj.val);
                    opt.siblings().removeClass('selected');
                    opt.filter(':contains("' + obj.val + '")').addClass('selected');
                }).change();
            },
            getValue: function () {
                return this.val;
            },
            getIndex: function () {
                return this.index;
            }
        };

        // create new variable for each menu
        var dd1 = new DropDown($('.js-wrap-drop-reviews'));
        $(document).on('click', function () {
            // close menu on document click
            $('.wrap-drop').removeClass('active');
        });
    });
</script>