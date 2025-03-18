<?php defined('SYSTEM_INIT') or die('Invalid Usage.'); ?>
<div class="content-body" id="listingDiv">
    <div class="card card-tabs">
        <div class="card-head">
            <nav class="nav nav-tabs">
                <a class="nav-link active navLinkJs favtProductsJs" onclick="searchWishList()" href="javascript:void(0);" id="tab-wishlist">
                    <?php echo Labels::getLabel("LBL_WISHLIST", $siteLangId); ?>
                </a>
                <a class="nav-link navLinkJs favtShopsJs" onclick="searchFavoriteShop();" href="javascript:void(0);"><?php echo Labels::getLabel('LBL_Shops', $siteLangId); ?></a>
            </nav>
        </div>
        <div class="card-body">
            <div class="account-fav-listing">
                <?php if (FatApp::getConfig('CONF_ADD_FAVORITES_TO_WISHLIST', FatUtility::VAR_INT, 1) == applicationConstants::YES) { ?>
                    <div class="wishlists">

                        <div class="wishlists__body">
                            <div class="add-wishlist">
                                <h6 class="h6">
                                    <?php echo Labels::getLabel('LBL_Create_new_list', $siteLangId); ?>
                                </h6>
                                <?php
                                $frm->setFormTagAttribute('onsubmit', 'setupWishList2(this,event); return(false);');
                                $frm->addFormTagAttribute('class', 'form');
                                $frm->developerTags['colClassPrefix'] = 'col-lg-12 col-md-12 col-sm-';
                                $frm->developerTags['fld_default_col'] = 12;
                                $titleFld = $frm->getField('uwlist_title');
                                $titleFld->setFieldTagAttribute('placeholder', Labels::getLabel('LBL_Enter_List_Name', $siteLangId));
                                $titleFld->setFieldTagAttribute('title', Labels::getLabel('LBL_List_Name', $siteLangId));
                                $titleFld->setFieldTagAttribute('maxlength', 20);

                                $btnSubmitFld = $frm->getField('btn_submit');
                                $btnSubmitFld->setFieldTagAttribute('class', 'btn btn-brand btn-block');
                                $btnSubmitFld->value = Labels::getLabel('LBL_Create', $siteLangId);
                                $btnSubmitFld->developerTags['noCaptionTag'] = true;

                                echo $frm->getFormHtml(); ?>

                            </div>
                        </div>
                    </div>
                <?php } ?>
                <?php if ($wishLists) {
                    foreach ($wishLists as $wishlist) {
                        if (count($wishlist['products']) > 0 || FatApp::getConfig('CONF_ADD_FAVORITES_TO_WISHLIST', FatUtility::VAR_INT, 1) == applicationConstants::YES) { ?>
                            <div class="wishlists">
                                <div class="wishlists__head">
                                    <h3 class="heading">
                                        <?php echo (isset($wishlist['uwlist_type']) && $wishlist['uwlist_type'] == UserWishList::TYPE_DEFAULT_WISHLIST) ? Labels::getLabel('LBL_Default_list', $siteLangId) : $wishlist['uwlist_title']; ?>
                                    </h3>
                                    <div class="actions">
                                        <?php if ($wishlist['totalProducts'] > 0) {
                                            $functionName = 'searchFavouriteListItems';
                                            if (!isset($wishlist['uwlist_type']) || (isset($wishlist['uwlist_type']) && $wishlist['uwlist_type'] != UserWishList::TYPE_FAVOURITE)) {
                                                $functionName = 'searchWishListItems';
                                            } ?>
                                            <a href="javascript:void(0)" class="icons-wrapper" onclick="<?php echo $functionName; ?>(<?php echo $wishlist['uwlist_id']; ?>);">
                                                <svg class="svg" width="18" height="18">
                                                    <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite-actions.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#view">
                                                    </use>
                                                </svg>
                                            </a>
                                        <?php } ?>
                                        <?php if ((!isset($wishlist['uwlist_type']) || (isset($wishlist['uwlist_type']) && $wishlist['uwlist_type'] != UserWishList::TYPE_FAVOURITE)) && $wishlist['uwlist_type'] != UserWishList::TYPE_DEFAULT_WISHLIST) { ?>
                                            <a href="javascript:void(0)" onclick="deleteWishList(<?php echo $wishlist['uwlist_id']; ?>);" class="icons-wrapper">
                                                <svg class="svg" width="18" height="18">
                                                    <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#bin" href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#bin">
                                                    </use>
                                                </svg>
                                            </a>
                                        <?php } ?>
                                    </div>
                                </div>
                                <div class="wishlists__body">
                                    <?php if ($wishlist['products']) { ?>
                                        <ul class="media-wishlist">
                                            <?php foreach ($wishlist['products'] as $product) {
                                                $uploadedTime = AttachedFile::setTimeParam($product['product_updated_on']);
                                                $productUrl = UrlHelper::generateUrl('Products', 'View', array($product['selprod_id']), CONF_WEBROOT_FRONTEND); ?>
                                                <li class="item <?php echo (!$product['in_stock']) ? 'item--sold' : ''; ?>">
                                                    <?php if (!$product['in_stock']) {?>
                                                        <span class="tag--soldout tag--soldout-small"><?php echo Labels::getLabel('LBL_Sold_Out', $siteLangId); ?></span>
                                                    <?php } ?>
                                                    <a href="<?php echo $productUrl; ?>">
                                                        <?php
                                                        $pictureAttr = [
                                                            'webpImageUrl' => [ImageDimension::VIEW_DESKTOP => UrlHelper::getCachedUrl(UrlHelper::generateFileUrl('image', 'product', array($product['product_id'], 'WEBP' . ImageDimension::VIEW_THUMB, $product['selprod_id'], 0, $siteLangId), CONF_WEBROOT_FRONTEND) . $uploadedTime, CONF_IMG_CACHE_TIME, '.webp')],
                                                            'jpgImageUrl' => [ImageDimension::VIEW_DESKTOP => UrlHelper::getCachedUrl(UrlHelper::generateFileUrl('image', 'product', array($product['product_id'], ImageDimension::VIEW_THUMB, $product['selprod_id'], 0, $siteLangId), CONF_WEBROOT_FRONTEND) . $uploadedTime, CONF_IMG_CACHE_TIME, '.jpg')],
                                                            'imageUrl' => UrlHelper::getCachedUrl(UrlHelper::generateFileUrl('image', 'product', array($product['product_id'], ImageDimension::VIEW_THUMB, $product['selprod_id'], 0, $siteLangId), CONF_WEBROOT_FRONTEND) . $uploadedTime, CONF_IMG_CACHE_TIME, '.jpg'),
                                                            'alt' => $product['product_name'],
                                                            'title' => $product['product_name']
                                                        ];
                                                        $this->includeTemplate('_partial/picture-tag.php', $pictureAttr, false);
                                                        ?>
                                                    </a>

                                                </li>
                                            <?php
                                            } ?>
                                        </ul>
                                    <?php
                                    } else {
                                        $this->includeTemplate('_partial/no-record-found.php', array('siteLangId' => $siteLangId, 'message' => Labels::getLabel('LBL_No_items_added_to_this_wishlist.', $siteLangId)));
                                    } ?>
                                </div>
                            </div>
                <?php } else {
                            $this->includeTemplate('_partial/no-record-found.php', array('siteLangId' => $siteLangId), false);
                        }
                    }
                } ?>
            </div>
        </div>
    </div>
</div>