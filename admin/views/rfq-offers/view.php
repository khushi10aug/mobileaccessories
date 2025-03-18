<?php defined('SYSTEM_INIT') or die('Invalid Usage.');
$totalRecords = count($arrListing); ?>
<div class="modal-header">
    <h5 class="modal-title">
        <?php echo Labels::getLabel('LBL_OFFERS_HISTORY', $siteLangId); ?>
    </h5>
</div>
<div class="modal-body">
    <div class="form-edit-body loaderContainerJs">
        <?php
        if ($totalRecords == 0) {
            echo HtmlHelper::getErrorMessageHtml(Labels::getLabel('ERR_NO_RECORD_FOUND', $siteLangId));
        } else {
            $count = 1;
            $theDay = current($arrListing)['offer_added_on']; ?>
            <ul class="rfq-messages">
                <?php foreach ($arrListing as $sn => $row) {
                    $class = User::USER_TYPE_SELLER == $row['offer_user_type'] ? 'right' : 'left';
                ?>
                    <li class="rfq-messages-item <?php echo $class; ?> rowJs" data-reference="<?php echo $row['offer_added_on']; ?>">
                        <div class="rfq-messages-data">
                            <?php if (RfqOffers::STATUS_ACCEPTED == $row['offer_status']) { ?>
                                <p class="txt-accepted">
                                    <svg class="svg" width="16" height="16">
                                        <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#check-double"></use>
                                    </svg>
                                    <?php echo Labels::getLabel('MSG_THIS_OFFER_HAS_BEEN_ACCEPTED', $siteLangId); ?>
                                </p>
                            <?php } ?>
                            <?php if (RfqOffers::STATUS_REJECTED == $row['offer_status']) { ?>
                                <p class="txt-rejected">
                                    <svg class="svg" width="16" height="16">
                                        <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#close-circle"></use>
                                    </svg>
                                    <?php echo Labels::getLabel('MSG_THIS_OFFER_HAS_BEEN_REJECTED', $siteLangId); ?>
                                </p>
                            <?php } ?>
                            <p class="rfq-messages-stats">
                                <span class="">
                                    <strong><?php echo Labels::getLabel('LBL_QTY', $siteLangId); ?>:</strong> <?php echo  CommonHelper::displayText($row['offer_quantity']); ?>
                                </span>
                                <span class="">
                                    <strong><?php echo Labels::getLabel('LBL_PRICE_PER_' . applicationConstants::getWeightUnitName($siteLangId, $row['rfq_quantity_unit']), $siteLangId); ?>:</strong> <?php echo CommonHelper::displayMoneyFormat($row['offer_price'], true, false, true, false, true); ?>
                                </span>
                                <?php if ($row['offer_user_type'] == User::USER_TYPE_SELLER) { ?>
                                    <span class="">
                                        <strong><?php echo Labels::getLabel('LBL_COST_PER_' . applicationConstants::getWeightUnitName($siteLangId, $row['rfq_quantity_unit']), $siteLangId); ?>:</strong> <?php echo CommonHelper::displayMoneyFormat($row['offer_cost'], true, false, true, false, true); ?>
                                    </span>
                                <?php } ?>
                            </p>
                            <?php if (!empty($row['offer_comments'])) { ?>
                                <p>
                                    <span class="lessContent<?php echo $row['offer_id']; ?>Js">
                                        <?php echo 200 < strlen((string)$row['offer_comments']) ? substr($row['offer_comments'], 0, 200) . ' ... <button class="link-underline showMoreJs" data-row-id="' . $row['offer_id'] . '">' . Labels::getLabel('LBL_SHOW_MORE') . '</button>' : $row['offer_comments']; ?>
                                    </span>
                                    <span class="moreContent<?php echo $row['offer_id']; ?>Js" style="display:none">
                                        <?php echo $row['offer_comments'] . ' <button class="link-underline showLessJs" data-row-id="' . $row['offer_id'] . '">' . Labels::getLabel('LBL_SHOW_LESS') . '</button>'; ?>
                                    </span>
                                </p>
                            <?php } ?>
                        </div>
                        <div class="from">
                            <div class="user-profile">
                                <?php
                                $uploadedTime = AttachedFile::setTimeParam($row['user_updated_on']);
                                $userImageUrl = UrlHelper::getCachedUrl(UrlHelper::generateFileUrl('image', 'user', array($row['user_id'], ImageDimension::VIEW_MINI_THUMB, true), CONF_WEBROOT_FRONT_URL) . $uploadedTime, CONF_IMG_CACHE_TIME, '.jpg');
                                $getUserAspectRatio = ImageDimension::getData(ImageDimension::TYPE_USER, ImageDimension::VIEW_MINI_THUMB);
                                ?>
                                <div class="user-profile_photo" data-ratio="<?php echo $getUserAspectRatio[ImageDimension::VIEW_MINI_THUMB]['aspectRatio']; ?>">
                                    <img data-aspect-ratio="<?php echo $getUserAspectRatio[ImageDimension::VIEW_MINI_THUMB]['aspectRatio']; ?>" width="<?php echo $getUserAspectRatio['width']; ?>" height="<?php echo $getUserAspectRatio['height']; ?>" title="<?php echo $row['user_name']; ?>" alt="<?php echo $row['user_name']; ?>" src="<?php echo $userImageUrl; ?>">
                                </div>
                                <div class="user-profile_data">
                                    <span class="user-profile_title">
                                        <?php echo $row['credential_username']; ?>
                                    </span>
                                    <span class="date"><?php echo FatDate::format($row['offer_added_on']); ?></span>
                                </div>
                            </div>
                        </div>
                    </li>
                <?php } ?>
            </ul>
        <?php } ?>
    </div>
</div>