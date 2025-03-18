<div class="products-foot">
    <?php /* if(round($product['prod_rating'])>0 && FatApp::getConfig("CONF_ALLOW_REVIEWS",FatUtility::VAR_INT,0)){ ?>
<?php if(round($product['prod_rating'])>0 ){ ?>
<div class="product-ratings"> <i class="icn"><svg class="svg svg-star" width="14" height="14">
           <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#star-yellow"></use>
       </svg></i> <?php if(round($product['prod_rating'])>0 ){ ?>
   <span class="rate"><?php echo round($product['prod_rating'],1);?></span>
   <?php } ?>
   <?php if(isset($firstToReview) && $firstToReview){ ?>
   <?php if(round($product['prod_rating'])==0 ){  ?>
   <span class="be-first"> <a
           href="javascript:void(0)"><?php echo Labels::getLabel('LBL_Be_the_first_to_review_this_product', $siteLangId); ?>
       </a> </span>
   <?php } ?>
   <?php }?>
</div>
<?php }  ?>
<?php } */ ?>
    <a class="products-category"
        href="<?php echo UrlHelper::generateUrl('Category', 'View', array($product['prodcat_id'])); ?>"><?php echo $product['prodcat_name']; ?>
    </a>
    <a class="products-title" title="<?php echo $product['selprod_title']; ?>"
        href="<?php echo UrlHelper::generateUrl('Products', 'View', array($product['selprod_id'])); ?>"><?php echo $product['selprod_title']; ?>
    </a>
    <?php include(CONF_THEME_PATH . '_partial/collection/product-price.php'); ?>
</div>