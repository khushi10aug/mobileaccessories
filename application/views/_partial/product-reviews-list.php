<?php defined('SYSTEM_INIT') or die('Invalid usage');

echo $frmReviewSearch->getFormHtml();
$this->includeTemplate('_partial/product-reviews.php', [
    'reviews' => $reviews,
    'ratingAspects' => $ratingAspects,
    'siteLangId' => $siteLangId,
    'product_id' => $product['product_id'],
    'canSubmitFeedback' => $canSubmitFeedback
], false);
?>
<div class="listings">
    <div class="listings__head">
        <div class="row">
            <div class="col-md-12">
                <div class="ratings--overall rating-wrapper">
                    <div class="row align-items-center">
                        <div class="col-md-4 column">
                            <div class="products__rating overall-rating-count">
                                <svg class="svg">
                                    <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#star-icon"></use>
                                </svg>
                                <span class="rate"><?php echo round($avgRating, 1); ?></span>
                            </div>
                            <h6 class="rating-based-on small text-center">
                                <span><?php echo Labels::getLabel('Lbl_Based_on', $siteLangId); ?></span>
                                <strong><?php echo $totReviews ?></strong>
                                <?php echo Labels::getLabel('Lbl_ratings', $siteLangId); ?>
                            </h6>
                        </div>
                        <div class="col-md-8 column">
                            <div class="listing--progress-wrapper">
                                <ul class="listing--progress">
                                    <?php foreach ($ratingAspects as $rating) {
                                        $ratingValue = CommonHelper::numberFormat($rating['prod_rating'], false, true, 1);
                                        $width = round(FatUtility::convertToType($ratingValue / 5 * 100, FatUtility::VAR_FLOAT), 2);
                                        $label = Labels::getLabel('LBL_{RATING}_RATING_OUT_OF_5_FOR_{NAME}', $siteLangId);
                                        $label = CommonHelper::replaceStringData($label, [
                                            '{RATING}' => $ratingValue,
                                            '{NAME}' => $rating['ratingtype_name'],
                                        ]);
                                    ?>
                                        <li>
                                            <div class="progress">
                                                <span class="progress__lbl"><?php echo $rating['ratingtype_name']; ?></span>
                                                <div class="progress__bar">
                                                    <div title="<?php echo $label; ?>" style="width: <?php echo $width; ?>%" class="progress__fill"></div>
                                                </div>
                                                <span class="progress__count"><?php echo $ratingValue; ?></span>
                                            </div>
                                        </li>
                                    <?php } ?>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php if ($canSubmitFeedback) { ?>
                <div class="col-md-4">
                    <div class="box box--white rounded p-3 have-you">
                        <h5><?php echo Labels::getLabel('Lbl_Share_your_thoughts', $siteLangId); ?></h5>
                        <p><?php echo Labels::getLabel('Lbl_With_other_customers', $siteLangId); ?></p>
                        <a class="btn btn-brand btn-sm" href="<?php echo UrlHelper::generateUrl('Reviews', 'write', array($product_id)); ?>"><?php echo Labels::getLabel('Lbl_Write_a_Review', $siteLangId); ?></a>
                    </div>
                </div>
            <?php } ?>
        </div>
    </div>
    <div class="listings__body mt-4">
        <div class="row">
            <div class="col-md-6 col-sm-6"><span id='reviews-pagination-strip--js' hidden><?php echo Labels::getLabel('Lbl_Displaying_Reviews', $siteLangId); ?> <span id='reviewStartIndex'></span>-<span id='reviewEndIndex'></span>
                    <?php echo Labels::getLabel('Lbl_of', $siteLangId); ?> <span id='reviewsTotal'></span></span></div>
        </div>
        <div class="row mt-5 mt-sm-0">
            <div class="col-auto">
                <a href="javascript:void(0);" class="btn btn-brand " data-sort='most_recent' onclick="getSortedReviews(this);return false;"><?php echo Labels::getLabel('Lbl_Most_Recent', $siteLangId); ?></a>
            </div>
            <div class="col">
                <a href="javascript:void(0);" class="btn btn-outline-brand" data-sort='most_helpful' onclick="getSortedReviews(this);return false;"><?php echo Labels::getLabel('Lbl_Most_Helpful', $siteLangId); ?> </a>
            </div>
        </div>

        <div class="reviewListJs"></div>
        <div id="loadMoreReviewsBtnDiv" class="text-center"></div>
    </div>
</div>
<script>
    $(function() {
        var $linkMoreText = '<?php echo Labels::getLabel('Lbl_SHOW_MORE', $siteLangId); ?>';
        var $linkLessText = '<?php echo Labels::getLabel('Lbl_SHOW_LESS', $siteLangId); ?>';

        $('#itemRatings div.progress__fill').css({
            'clip': 'rect(0px, <?php echo $pixelToFillRight; ?>px, 160px, 0px)'
        });
    });
</script>