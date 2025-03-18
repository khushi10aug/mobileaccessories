<?php defined('SYSTEM_INIT') or die('Invalid usage');

$totReviews = 0;
$rate_5_width = $rate_4_width = $rate_3_width = $rate_2_width = $rate_1_width = 0;
$totReviews = (!empty($reviews['totReviews'])) ? FatUtility::int($reviews['totReviews']) : 0;
$totReviews *= (!empty($reviews['totalType'])) ? FatUtility::int($reviews['totalType']) : 0;

$totalType = (!empty($reviews['totalType'])) ? FatUtility::int($reviews['totalType']) : 0;
if ($totReviews) {
    $rated_1 = FatUtility::int($reviews['rated_1']);
    $rated_2 = FatUtility::int($reviews['rated_2']);
    $rated_3 = FatUtility::int($reviews['rated_3']);
    $rated_4 = FatUtility::int($reviews['rated_4']);
    $rated_5 = FatUtility::int($reviews['rated_5']);

    $rate_5_width = round(FatUtility::convertToType($rated_5 / $totReviews * 100, FatUtility::VAR_FLOAT), 2);
    $rate_4_width = round(FatUtility::convertToType($rated_4 / $totReviews * 100, FatUtility::VAR_FLOAT), 2);
    $rate_3_width = round(FatUtility::convertToType($rated_3 / $totReviews * 100, FatUtility::VAR_FLOAT), 2);
    $rate_2_width = round(FatUtility::convertToType($rated_2 / $totReviews * 100, FatUtility::VAR_FLOAT), 2);
    $rate_1_width = round(FatUtility::convertToType($rated_1 / $totReviews * 100, FatUtility::VAR_FLOAT), 2);
}
?>
<div class="rating-block">
    <p class="text-muted text-info"><?php echo $totalType . ' ' . Labels::getLabel('LBL_RATING`S_TYPE', $siteLangId); ?>
    </p>
    <ul class="progress-block">
        <li class="progress-block-item">
            <span class="star">
                <span class="txt">5</span> <svg class="svg svg-star" width="9" height="9">
                    <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#star-yellow">
                    </use>
                </svg></span>
            <progress class="progress" min="0" max="100" value="<?php echo $rate_5_width; ?>"
                data-rating="<?php echo $rated_5; ?>"></progress>
            <span class="count"><?php echo $rate_5_width; ?>%</span>
        </li>
        <li class="progress-block-item">
            <span class="star">
                <span class="txt">4</span><svg class="svg svg-star" width="9" height="9">
                    <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#star-yellow">
                    </use>
                </svg></span>
            <progress class="progress" min="0" max="100" value="<?php echo $rate_4_width; ?>"
                data-rating="<?php echo $rated_4; ?>"></progress>
            <span class="count"><?php echo $rate_4_width; ?>%</span>
        </li>
        <li class="progress-block-item">
            <span class="star">
                <span class="txt">3</span> <svg class="svg svg-star" width="9" height="9">
                    <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#star-yellow">
                    </use>
                </svg></span>
            <progress class="progress" min="0" max="100" value="<?php echo $rate_3_width; ?>"
                data-rating="<?php echo $rated_3; ?>"></progress>
            <span class="count"><?php echo $rate_3_width; ?>%</span>
        </li>
        <li class="progress-block-item">
            <span class="star">
                <span class="txt">2</span><svg class="svg svg-star" width="9" height="9">
                    <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#star-yellow">
                    </use>
                </svg></span>
            <progress class="progress" min="0" max="100" value="<?php echo $rate_2_width; ?>"
                data-rating="<?php echo $rated_4; ?>"></progress>
            <span class="count"><?php echo $rate_2_width; ?>%</span>
        </li>
        <li class="progress-block-item">
            <span class="star">
                <span class="txt">1</span>
                <svg class="svg svg-star" width="9" height="9">
                    <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#star-yellow">
                    </use>
                </svg></span>
            <progress class="progress" min="0" max="100" value="<?php echo $rate_1_width; ?>"
                data-rating="<?php echo $rated_1; ?>"></progress>
            <span class="count"><?php echo $rate_1_width; ?>%</span>
        </li>
    </ul>
</div>
<?php if (0 < count($ratingAspects)) { ?>
<div class="divider"></div>
<div class="rating-block">
    <h5 class="title-sub"><?php echo Labels::getLabel('LBL_BY_CATEGORY', $siteLangId); ?></h5>
    <ul class="rating-by-category">
        <?php foreach ($ratingAspects as $rating) {
                $ratingValue = CommonHelper::numberFormat($rating['prod_rating'], false, true, 1);
                $width = round(FatUtility::convertToType($rating['prod_rating'] / 5 * 100, FatUtility::VAR_FLOAT), 2);
                $label = Labels::getLabel('LBL_{RATING}_RATING_OUT_OF_5_FOR_{NAME}', $siteLangId);
                $label = CommonHelper::replaceStringData($label, [
                    '{RATING}' => $ratingValue,
                    '{NAME}' => $rating['ratingtype_name'],
                ]);
            ?>
        <li class="rating-by-category-item">
            <span class="label">
                <?php echo $rating['ratingtype_name']; ?>
            </span>
            <span class="value">
                <svg class="svg svg-star" width="11" height="11">
                    <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#star-yellow">
                    </use>
                </svg>
                <span class="out-of"><?php echo $ratingValue; ?> /5</span>
            </span>
        </li>
        <?php } ?>
    </ul>
</div>
<?php } ?>
<?php if ($canSubmitFeedback) { ?>
<div class="divider"></div>
<div class="rating-block">
    <div class="review-cta">
        <h5 class="title-sub"><?php echo Labels::getLabel('LBL_REVIEW_THIS_PRODUCT', $siteLangId); ?></h5>
        <p><?php echo Labels::getLabel('LBL_SHARE_YOUR_THOUGHTS_WITH_OTHER_CUSTOMERS', $siteLangId); ?></p>
        <button class="btn btn-brand btn-block" type="button"
            onclick="rateAndReviewProduct(<?php echo $product_id; ?>)">
            <?php echo Labels::getLabel('LBL_WRITE_A_REVIEW', $siteLangId); ?>
        </button>
    </div>
</div>
<?php } ?>