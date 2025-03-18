<?php defined('SYSTEM_INIT') or die('Invalid usage');

$totReviews = $avgRating = 0;
if (!empty($reviews)) {
    $totReviews = (!empty($reviews['totReviews'])) ? FatUtility::int($reviews['totReviews']) : 0;
    $avgRating = (!empty($reviews['avg_seller_rating'])) ? FatUtility::convertToType($reviews['avg_seller_rating'], FatUtility::VAR_FLOAT) : 0;
}
?>
<section class="section" data-section="section">
    <div class="container" id="itemRatings">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="customer-reviews">
                    <?php if (true === $shopView) { ?>
                        <div class="customer-reviews-head">
                            <h2 class="title"><?php echo Labels::getLabel('LBL_CUSTOMER_REVIEWS', $siteLangId); ?></h2>
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
                            <?php } ?>
                        </div>
                    <?php } ?>
                    <div class="customer-reviews-body">
                        <div class="rating-layout">
                            <!-- Rating Section -->
                            <div class="rating-layout-start">
                                <div class="sticky-md-top">
                                    <div class="product-card">
                                        <?php if (false === $shopView && !empty($shop)) { ?>
                                            <div class="product-card-start">
                                                <div class="product-card-img">
                                                    <img alt="<?php echo $shop['shop_name']; ?>"
                                                        src="<?php echo UrlHelper::getCachedUrl(UrlHelper::generateFileUrl('image', 'shopLogo', array($shop['shop_id'], $siteLangId, ImageDimension::VIEW_THUMB)), CONF_IMG_CACHE_TIME, '.jpg'); ?>"
                                                        <?php echo HtmlHelper::getImgDimParm(ImageDimension::TYPE_SHOP_LOGO, ImageDimension::VIEW_THUMB); ?>>
                                                </div>
                                            </div>
                                        <?php } ?>
                                        <div class="product-card-end">
                                            <div class="product-card-data">
                                                <?php
                                                if (true) {
                                                    //if (1 < $reviewId) {
                                                    $shop_city = $shop['shop_city'] ?? '';
                                                    $shop_state = (strlen((string) $shop_city) > 0) ? ', ' . $shop['shop_state_name'] : $shop['shop_state_name'];
                                                    $shop_country = (strlen((string) $shop_state) > 0) ? ', ' . $shop['shop_country_name'] : $shop['shop_country_name'];
                                                    $shopLocation = $shop_city . $shop_state . $shop_country;
                                                    ?>

                                                    <h1 class="title"> <a
                                                            href="<?php echo UrlHelper::generateUrl('Shops', 'view', array($shop['shop_id'])); ?>"><?php echo $shop['shop_name']; ?></a>
                                                    </h1>
                                                    <p class="location"><?php echo $shopLocation; ?></p>


                                                <?php } ?>
                                                <div class="rating-block">
                                                    <div class="average-rating">
                                                        <span class="rate"><?php echo round($shopRating, 1); ?>
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
                                    </div>
                                    <div class="divider"></div>
                                    <?php $this->includeTemplate('_partial/product-overall-ratings.php', [
                                        'reviews' => $reviews,
                                        'ratingAspects' => $ratingAspects,
                                        'siteLangId' => $siteLangId,
                                        'canSubmitFeedback' => false,
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
                </div>
            </div>
        </div>
    </div>
</section>