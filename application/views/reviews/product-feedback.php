<?php defined('SYSTEM_INIT') or die('Invalid Usage.');
$frm->setFormTagAttribute('class', 'form form--review');
$frm->setFormTagAttribute('onSubmit', 'setupProductFeedback(this); return false;');
$frm->developerTags['colClassPrefix'] = 'col-md-';
$frm->developerTags['fld_default_col'] = 12;

$titleFld = $frm->getField('spreview_title');
$titleFld->developerTags['noCaptionTag'] = true;
$descFld = $frm->getField('spreview_description');
$descFld->developerTags['noCaptionTag'] = true;
$fileFld = $frm->getField('spreview_image[]');
$fileFld->developerTags['noCaptionTag'] = true;
$agreeFld = $frm->getField('agree');
$agreeFld->developerTags['cbLabelAttributes'] = ['class' => 'checkbox'];
$btnSubmit = $frm->getField('btn_submit');
$btnSubmit->setFieldTagAttribute('class', 'btn btn-brand');
$btnSubmit->setFieldTagAttribute('disabled', 'disabled');
$btnSubmit->developerTags['noCaptionTag'] = true;

foreach ($ratingAspects as $ratingTypeId => $ratingTypeLabel) {
    $ratingFld = $frm->getField('review_rating[' . $ratingTypeId . ']');
    if ($ratingFld) {
        $ratingFld->developerTags['noCaptionTag'] = true;
        $ratingFld->setFieldTagAttribute('class', 'd-none star-rating');
    }
}

$prodTitle = !empty($product['selprod_title']) ? $product['selprod_title'] : $product['product_name'];
$selProdCodeArr = explode('_', $product['selprod_code']);
$prodImg = UrlHelper::getCachedUrl(UrlHelper::generateFileUrl('image', 'product', array($selProdCodeArr[0], ImageDimension::VIEW_MEDIUM, $product['selprod_id'], 0, $siteLangId)), CONF_IMG_CACHE_TIME, '.jpg');
?>
<section class="section section--review-feedback">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="section-head">
                    <div class="section-heading">
                        <h2><?php echo Labels::getLabel('LBL_WRITE_A_REVIEW', $siteLangId); ?></h2>
                    </div>
                </div>
                <div class="card review-feedback-card">
                    <div class="card-body p-0">
                        <?php echo $frm->getFormTag(); ?>
                        <div class="order-feedback-section">
                            <div class="feedback-block">
                                <h5 class="card-title"><?php echo Labels::getLabel('LBL_PRODUCT_FEEDBACK', $siteLangId); ?></h5>
                                <div class="feedback-block_content">
                                    <div class="product-profile mb-4">
                                        <div class="product-profile__pic">
                                            <a href="<?php echo UrlHelper::generateUrl('products', 'view', array($product['selprod_id'])); ?>">
                                                <img <?php echo HtmlHelper::getImgDimParm(ImageDimension::TYPE_PRODUCTS, ImageDimension::VIEW_MEDIUM); ?> src="<?php echo $prodImg; ?>" alt="<?php echo htmlspecialchars($prodTitle); ?>" title="<?php echo htmlspecialchars($prodTitle); ?>">
                                            </a>
                                        </div>
                                        <div class="product-profile__description">
                                            <div class="item__category">
                                                <a href="<?php echo UrlHelper::generateUrl('shops', 'view', array($product['shop_id'])); ?>"><?php echo $product['shop_name']; ?></a>
                                            </div>
                                            <div class="product-profile__title">
                                                <a title="<?php echo htmlspecialchars($prodTitle); ?>" href="<?php echo UrlHelper::generateUrl('products', 'view', array($product['selprod_id'])); ?>"><?php echo $prodTitle; ?></a>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="rating-listing rating-listing-column">
                                        <?php foreach ($selProdRating as $ratingTypeId => $ratingTypeLabel) { ?>
                                            <div class="rating rating-f">
                                                <span class="rating__text"><?php echo $ratingTypeLabel; ?>*</span>
                                                <?php echo $frm->getFieldHtml('review_rating[' . $ratingTypeId . ']'); ?>
                                            </div>
                                        <?php } ?>
                                    </div>

                                    <div class="form-group">
                                        <label class="form-label"><?php echo $titleFld->getCaption(); ?></label>
                                        <?php echo $frm->getFieldHtml('spreview_title'); ?>
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label"><?php echo $descFld->getCaption(); ?></label>
                                        <?php echo $frm->getFieldHtml('spreview_description'); ?>
                                    </div>
                                    <div class="form-group mb-0">
                                        <div class="file__upload">
                                            <?php
                                            $fileFld->setFieldTagAttribute('multiple', 'multiple');
                                            $fileFld->setFieldTagAttribute('class', 'multipleImgs--js');
                                            echo $frm->getFieldHtml('spreview_image[]'); ?>
                                            <span class="upload-icon">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24">
                                                    <path d="M3 4V1h2v3h3v2H5v3H3V6H0V4zm3 6V7h3V4h7l1.83 2H21a2.006 2.006 0 0 1 2 2v12a2.006 2.006 0 0 1-2 2H5a2.006 2.006 0 0 1-2-2V10zm7 9a5 5 0 1 0-5-5 5 5 0 0 0 5 5zm-3.2-5a3.2 3.2 0 1 0 3.2-3.2A3.2 3.2 0 0 0 9.8 14z"></path>
                                                </svg>
                                            </span>
                                            <span><?php echo Labels::getLabel('LBL_UPLOAD_IMAGES', $siteLangId); ?></span>
                                        </div>
                                        <div class="uploaded-media multipleImgsGallery--js"></div>
                                    </div>
                                </div>
                            </div>

                            <?php if (!empty($shopRatingTypesArr)) { ?>
                                <div class="divider"></div>
                                <div class="feedback-block">
                                    <h5 class="card-title"><?php echo Labels::getLabel('LBL_SELLER_FEEDBACK', $siteLangId); ?></h5>
                                    <div class="feedback-block_content">
                                        <div class="shop-rating-wrap">
                                            <div class="shop-card">
                                                <div class="shop-card__img">
                                                    <img width="48" height="48" src="<?php echo UrlHelper::generateFileUrl('image', 'shopLogo', array($product['shop_id'], $siteLangId, ImageDimension::VIEW_THUMB)); ?>" alt="<?php echo htmlspecialchars($product['shop_name']); ?>" />
                                                </div>
                                                <div class="shop-card__detail">
                                                    <h6><?php echo $product['shop_name']; ?></h6>
                                                    <?php if (!empty($product['user_regdate'])) {
                                                        $date = new DateTime($product['user_regdate']); ?>
                                                        <span class="shop-opened">
                                                            <?php echo Labels::getLabel('LBL_Shop_Opened_On', $siteLangId); ?>
                                                            <?php echo $date->format('M d, Y'); ?>
                                                        </span>
                                                    <?php } ?>
                                                </div>
                                            </div>
                                            <div class="rating-listing rating-listing-column">
                                                <?php foreach ($shopRatingTypesArr as $ratingTypeId => $ratingTypeLabel) { ?>
                                                    <div class="rating rating-f">
                                                        <span class="rating__text"><?php echo $ratingTypeLabel; ?>*</span>
                                                        <?php echo $frm->getFieldHtml('review_rating[' . $ratingTypeId . ']'); ?>
                                                    </div>
                                                <?php } ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php } ?>

                            <div class="divider"></div>
                            <div class="feedback-block">
                                <div class="form-group review-agree">
                                    <?php echo $frm->getFieldHtml('agree'); ?>
                                </div>
                                <?php
                                echo $frm->getFieldHtml('product_id');
                                echo $frm->getFieldHtml('selprod_id');
                                echo $frm->getFieldHtml('referrer');
                                echo $frm->getFieldHtml('btn_submit');
                                ?>
                            </div>
                        </div>
                        </form>
                        <?php echo $frm->getExternalJS(); ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
<script type="text/javascript">
    $(document).ready(function() {
        $('.star-rating').barrating({
            showSelectedRating: false
        });
        $("input[name='agree']").change(function() {
            if (this.checked) {
                $("input[name='btn_submit']").removeAttr('disabled');
            } else {
                $("input[name='btn_submit']").attr('disabled', 'disabled');
            }
        });
    });
</script>
