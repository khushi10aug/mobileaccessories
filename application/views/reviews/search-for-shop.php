<?php defined('SYSTEM_INIT') or die('Invalid Usage.');
if ($reviewsList) { ?>
<div class="user-reviews">
    <?php foreach ($reviewsList as $review) {
            $images = AttachedFile::getMultipleAttachments(AttachedFile::FILETYPE_ORDER_FEEDBACK, $review['spreview_id']);
            if (!empty($images)) { ?>
    <div class="all-reviews-images">
        <h6 class="h6"> <?php echo Labels::getLabel('LBL_REVIEW_WITH_IMAGES'); ?></h6>
        <div class="review-images featherLightGalleryJs">
            <?php
                        $i = 0;
                        foreach ($images as $image) {
                            $uploadedTime = AttachedFile::setTimeParam($image['afile_updated_at']);
                            $imgUrl = UrlHelper::getCachedUrl(UrlHelper::generateFullFileUrl('Image', 'review', array($review['spreview_id'], 0, ImageDimension::VIEW_MINI_THUMB, $image['afile_id'])) . $uploadedTime, CONF_IMG_CACHE_TIME, '.jpg');
                            $largeImgUrl = UrlHelper::getCachedUrl(UrlHelper::generateFullFileUrl('Image', 'review', array($review['spreview_id'], 0, ImageDimension::VIEW_LARGE, $image['afile_id'])) . $uploadedTime, CONF_IMG_CACHE_TIME, '.jpg');
                            ?>
            <div class="image">
                <a class="thumbnail" href="<?php echo $largeImgUrl; ?>"
                    data-fancybox="gallery-<?php echo $review['spreview_id']; ?>">
                    <img src="<?php echo $imgUrl; ?>" data-altimg="<?php echo $largeImgUrl; ?>">
                </a>
            </div>
            <?php
                        } ?>
        </div>
    </div>
    <?php } ?>
    <div class="user-reviews-item">
        <ul class="rated-by">
            <?php
                    foreach ($recordRatings as $rating) {
                        if ($review['spreview_id'] != $rating['sprating_spreview_id']) {
                            continue;
                        }
                        ?>
            <li class="rated-by-item">
                <div class="product-ratings">
                    <svg class="svg svg-star" width="10" height="10">
                        <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#star-yellow">
                        </use>
                    </svg>

                    <span class="rate"><?php echo $rating['sprating_rating']; ?>/5</span>
                </div> <span class="rated-by-label"><?php echo $rating['ratingtype_name']; ?></span>

            </li>
            <?php } ?>
        </ul>
        <div class="review-data">
            <div class="cms">
                <h6><strong><?php echo $review['spreview_title']; ?></strong></h6>
                <p class='lessText'>
                    <?php echo CommonHelper::truncateCharacters($review['spreview_description'], 200, '', '', true); ?>
                </p>
                <?php if (strlen((string)$review['spreview_description']) > 200) { ?>
                <p class='moreText hidden'>
                    <?php echo nl2br($review['spreview_description']); ?>
                </p>
                <a class="readMore link-underline" href="javascript:void(0);">
                    <?php echo Labels::getLabel('Lbl_SHOW_MORE', $siteLangId); ?> </a>
                <?php } ?>
            </div>

            <!-- <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700;800&amp;display=swap" rel="stylesheet"> -->
        </div>
        <div class="user-reviews-foot">
            <div class="reviews-by"><?php echo CommonHelper::displayName($review['user_name']); ?> | <span
                    class="dated"><?php echo FatDate::format($review['spreview_posted_on']); ?></span>
            </div>
            <ul class="yes-no">
                <!-- <li class="yes-no-item"><?php // Labels::getLabel('LBL_WAS_THIS_REVIEW_HELPFUL?', $siteLangId); 
                                ?></li> -->
                <li class="yes-no-item">
                    <button class="btn btn-thumb btn-icon" type="button"
                        title="<?php echo Labels::getLabel('LBL_LIKE', $siteLangId); ?>"
                        onclick="markReviewHelpful(<?php echo FatUtility::int($review['spreview_id']); ?>,1)">
                        <svg class="svg" width="16" height="16">
                            <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite-actions.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#like">
                            </use>
                        </svg>
                        <span class="counts">(<?php echo $review['helpful']; ?>)</span>
                    </button>
                </li>
                <li class="yes-no-item">
                    <button class="btn btn-thumb btn-icon" type="button"
                        title="<?php echo Labels::getLabel('LBL_DISLIKE', $siteLangId); ?>"
                        onclick="markReviewHelpful(<?php echo FatUtility::int($review['spreview_id']); ?>, 0)">
                        <svg class="svg" width="16" height="16">
                            <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite-actions.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#dislike">
                            </use>
                        </svg>
                        <span class="counts">(<?php echo $review['notHelpful']; ?>)</span>
                    </button>
                </li>
                <?php if (1 > $reviewId) { ?>
                <li class="yes-no-item">
                    <a class="link-brand btn-underline"
                        href="<?php echo UrlHelper::generateUrl('Reviews', 'shopPermalink', array($review['spreview_seller_user_id'], $review['spreview_id'])) ?>">
                        <?php echo Labels::getLabel('LBL_PERMALINK', $siteLangId); ?>
                    </a>
                </li>
                <?php } ?>
            </ul>
        </div>
    </div>
    <?php } ?>
</div>
<?php
    echo FatUtility::createHiddenFormFromData($postedData, array('name' => 'frmSearchReviewsPaging'));
}