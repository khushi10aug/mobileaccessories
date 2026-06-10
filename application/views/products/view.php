<?php
defined('SYSTEM_INIT') or die('Invalid Usage.');
$buyQuantity = $frmBuyProduct->getField('quantity');
$buyQuantity->addFieldTagAttribute('class', 'qty-input cartQtyTextBox productQty-js');
$buyQuantity->addFieldTagAttribute('data-page', 'product-view'); ?>
<main id="main" class="main detail-page">
    <section class="">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-10">
                    <div class="breadcrumb">
                        <?php $this->includeTemplate('_partial/custom/header-breadcrumb.php',['is_product'=>1,'product'=>$product]); ?>
                    </div>
                    <div class="detail-first-fold">
                        <?php include ('product-detail-gallery.php'); ?>
                        <?php include ('product-description.php'); ?>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <div class="relatedProductsSectionJs"></div>
    <section class="section" data-section="section">
        <div class="container">
            <?php include ('prod-desc-nav-detail.php'); ?>
        </div>
    </section>
    <div class="recommendedProductsSectionJs"></div>
    <?php if (FatApp::getConfig("CONF_ALLOW_REVIEWS", FatUtility::VAR_INT, 0) && (!empty($reviews) || $canSubmitFeedback)) {
        echo $frmReviewSearch->getFormHtml();
        $product_id = $product['product_id'];
        include (CONF_THEME_PATH . '_partial/product-reviews.php');
    } ?>
    <!-- Banners -->
    <?php include ('banners.php'); ?>
    <!-- Banners -->
    <div class="recentlyViewedProductsSectionJs"></div>
</main>
<script>
    var mainSelprodId = <?php echo $product['selprod_id']; ?>;
    var layout = '<?php echo CommonHelper::getLayoutDirection(); ?>';

    $(function () {
        let fnCalled = false;
        $(window).scroll(function () {
            if (false == fnCalled) {
                fnCalled = true;
                interRelatedProducts(<?php echo $product['selprod_id']; ?>);
            }
        });

        /*zheight = $(window).height() - 180; */
        zwidth = $(window).width() / 3 - 15;

        window.setInterval(function () {
            var scrollPos = $(window).scrollTop();
            if (scrollPos > 0) {
                setProductWeightage('<?php echo $product['selprod_code']; ?>');
            }
        }, 5000);

        $("#btnAddToCart").addClass("quickView");
        // $('#slider-for').slick(getSlickGallerySettings(false));
        // $('#slider-nav').slick(getSlickGallerySettings(true, '<?php /* echo CommonHelper::getLayoutDirection(); */ ?>'));

        /* for toggling of tab/list view[ */
        $('.list-js').hide();
        $('.view--link-js').on('click', function (e) {
            $('.view--link-js').removeClass("btn--active");
            $(this).addClass("btn--active");
            if ($(this).hasClass('list')) {
                $('.tab-js').hide();
                $('.list-js').show();
            } else if ($(this).hasClass('tab')) {
                $('.list-js').hide();
                $('.tab-js').show();
            }
        });
        /* ] */
    });

    $(document).ready(function () {
        ykevents.viewItem({
            item_id: "<?php echo $product['selprod_id']; ?>",
            item_name: "<?php echo $product['selprod_title']; ?>",
            discount: "<?php echo ($product['selprod_price'] - $product['theprice']); ?>",
            index: 0,
            item_brand: "<?php echo $product['brand_name']; ?>",
            item_category: "<?php echo $product['prodcat_name']; ?>",
            price: "<?php echo $product['theprice']; ?>",
            quantity: 1
        });
    });
</script>

<!-- Product Schema Code -->

<?php

$image = AttachedFile::getAttachment(
    AttachedFile::FILETYPE_PRODUCT_IMAGE,
    $product['product_id']
);

$imageUrl = UrlHelper::getCachedUrl(
    UrlHelper::generateFullFileUrl(
        'Image',
        'product',
        [
            $product['product_id'],
            ImageDimension::VIEW_THUMB,
            0,
            $image['afile_id']
        ]
    ),
    CONF_IMG_CACHE_TIME,
    '.jpg'
);
$imageUrl = [];
if ($productImagesArr) {
    foreach ($productImagesArr as $afile_id => $image) {
        $uploadedTime = AttachedFile::setTimeParam($image['afile_updated_at']);
        $imageUrl[] = UrlHelper::getCachedUrl(UrlHelper::generateFullFileUrl('Image', 'product', array($product['product_id'], ImageDimension::VIEW_THUMB, 0, $image['afile_id'])) . $uploadedTime, CONF_IMG_CACHE_TIME, '.jpg');

    }
}

$productUrl = UrlHelper::generateFullUrl(
    'Products',
    'view',
    [$product['selprod_id']]
);

$description = strip_tags(
    CommonHelper::renderHtml($product['product_description'])
);
$description = preg_replace('/\s+/', ' ', $description);
$description = trim($description);

$schema = [
    '@context' => 'https://schema.org/',
    '@type' => 'Product',
    'name' => $product['selprod_title'],
    'description' => $description,
    'image' => $imageUrl,
    'url' => $productUrl,
];

/* SKU */
if (!empty($product['selprod_sku'])) {
    $schema['sku'] = $product['selprod_sku'];
}

/* MPN */
if (!empty($product['product_model'])) {
    $schema['mpn'] = $product['product_model'];
}

/* Brand */
if (!empty($product['brand_name'])) {
    $schema['brand'] = [
        '@type' => 'Brand',
        'name' => $product['brand_name']
    ];
}
$schema['hasMerchantReturnPolicy'] = [
    '@type' => 'MerchantReturnPolicy',
    "returnPolicyCategory"=> "https://schema.org/MerchantReturnFiniteReturnWindow",
    "merchantReturnDays"=> 7,
    "merchantReturnLink"=> "https://www.mobileaccessories.in/return-policy",
    "applicableCountry"=> "IN",
    "returnFees"=> "https://schema.org/FreeReturn",
    "returnMethod"=> "https://schema.org/ReturnByMail",
    "refundType"=> "https://schema.org/FullRefund",
    "itemCondition"=> "https://schema.org/NewCondition",
    "returnPolicySeasonalOverride"=> false
];
$schema['shippingDetails'] = [
    "@type"=> "OfferShippingDetails",
    "shippingRate"=> "Customized Shipping As per Convenience"
];
/* Offers */
$schema['offers'] = [
    '@type' => 'Offer',
    'url' => $productUrl,
    'priceCurrency' => CommonHelper::getCurrencyCode(),
    'price' => (string)$product['theprice'],
    'priceValidUntil' => date('Y-m-d', strtotime('+1 year')),
    'availability' => 'https://schema.org/InStock',
    'itemCondition' => 'https://schema.org/NewCondition',
    'seller' => [
        '@type' => 'Organization',
        'name' => FatApp::getConfig("CONF_WEBSITE_NAME_" . $siteLangId)
    ],
];

/* Aggregate Rating */
if (
    isset($reviews['prod_rating']) &&
    $reviews['prod_rating'] > 0 &&
    !empty($reviews['totReviews'])
) {
    $schema['aggregateRating'] = [
        '@type' => 'AggregateRating',
        'ratingValue' => round((float)$reviews['prod_rating'], 1),
        'reviewCount' => (int)$reviews['totReviews'],
        'bestRating' => 5,
        'worstRating' => 1
    ];
}

/* Additional Properties */
$schema['additionalProperty'] = [];

if (!empty($product['brand_name'])) {
    $schema['additionalProperty'][] = [
        '@type' => 'PropertyValue',
        'name' => 'Brand',
        'value' => $product['brand_name']
    ];
}

if (!empty($product['selprod_sku'])) {
    $schema['additionalProperty'][] = [
        '@type' => 'PropertyValue',
        'name' => 'SKU',
        'value' => $product['selprod_sku']
    ];
}

/* Optional compatibility field */
if (!empty($product['product_short_description'])) {
    $sdescription = strip_tags(
        CommonHelper::renderHtml($product['product_short_description'])
    );
    $sdescription = preg_replace('/\s+/', ' ', $sdescription);
    $sdescription = trim($sdescription);
    
    $schema['additionalProperty'][] = [
        '@type' => 'PropertyValue',
        'name' => 'Short Description',
        'value' => $sdescription
    ];
}

?>

<script type="application/ld+json">
<?php
echo json_encode(
    $schema,
    JSON_UNESCAPED_SLASHES |
    JSON_UNESCAPED_UNICODE |
    JSON_PRETTY_PRINT
);
?>
</script>


<?php 
$image = AttachedFile::getAttachment(AttachedFile::FILETYPE_PRODUCT_IMAGE, $product['product_id']); ?>
<!-- End Product Schema Code -->

<!--Here is the facebook OG for this product  -->
<?php echo $this->includeTemplate('_partial/shareThisScript.php'); ?>

<!-- JWPlayer -->
<script>
    jwplayer.key = '<?php echo FatApp::getConfig("CONF_JW_PLAYER_KEY", null, ''); ?>';
</script>
<!-- JWPlayer -->