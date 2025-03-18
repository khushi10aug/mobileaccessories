<?php defined('SYSTEM_INIT') or die('Invalid Usage.'); ?>
<div class="row justify-content-center">
    <div class="col-lg-7">
        <div class="descriptions" id="accordionExample">
            <?php
            $youtube_embed_code = '';
            if (!empty($product["product_youtube_video"])) {
                $youtube_embed_code = UrlHelper::parseYoutubeUrl($product["product_youtube_video"]);
            }
            ?>
            <?php if (Product::PRODUCT_TYPE_DIGITAL == $product['product_type'] && (0 < count($product['preview_attachments']) || 0 < count($product['preview_links']))) { ?>
                <?php $this->includeTemplate('_partial/product/dd-preview-list.php', array('siteLangId' => $siteLangId, 'product' => $product), false); ?>
            <?php } ?>
            <?php
            $firstIsVisible = false;
            if (count($productSpecifications) > 0) {
                $prodSpeciByGroup = array();
                foreach ($productSpecifications as $productSpecification) {
                    $prodSpeciByGroup[$productSpecification['prodspec_group']][] = $productSpecification;
                }
                $firstIsVisible = true;
            ?>
                <div class="descriptions-item accordianSectionJs">
                    <h2 class="descriptions-head" data-bs-toggle="collapse" data-bs-target="#specification" aria-expanded="true"><?php echo Labels::getLabel('LBL_Specifications', $siteLangId); ?>
                        <?php ?>
                        <svg class="svg plus toggleAccordianJs" width="16" height="16">
                            <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#plus">
                            </use>
                        </svg>
                    </h2>
                    <div id="specification" class="collapse show" data-bs-parent="#accordionExample">
                        <div class="descriptions-data">
                            <?php foreach ($prodSpeciByGroup as $key => $speciGroup) {
                                if (!empty(trim($key))) { ?>
                                    <div class="specification-group">
                                        <h6><?php echo ucfirst($key); ?></h6>
                                    <?php } ?>
                                    <ul class="list-specification">
                                        <?php foreach ($speciGroup as $specification) { ?>
                                            <li class="list-specification-item">
                                                <span class="label"><?php echo $specification['prodspec_name'] . ":"; ?></span>
                                                <span class="value"><?php echo CommonHelper::renderHtml(htmlspecialchars($specification['prodspec_value']), true); ?>
                                                </span>
                                            </li>
                                        <?php } ?>
                                    </ul>
                                    <?php
                                    if (!empty(trim($key))) { ?>
                                    </div>
                            <?php  }
                                }
                            ?>
                        </div>
                    </div>
                </div>
            <?php } ?>
            <?php
            if ('' != trim((string)$product['product_description'])) { ?>
                <div class="descriptions-item accordianSectionJs">
                    <h2 class="descriptions-head  <?php echo ($firstIsVisible ? 'collapsed' : ''); ?>" data-bs-toggle="collapse" data-bs-target="#description" aria-expanded="true"><?php echo Labels::getLabel('LBL_Description', $siteLangId); ?>
                        <svg class="svg plus toggleAccordianJs" width="16" height="16">
                            <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#plus">
                            </use>
                        </svg>
                    </h2>
                    <div id="description" class="collapse <?php echo (false === $firstIsVisible ? 'show' : ''); ?>" data-bs-parent="#accordionExample">
                        <div class="descriptions-data">
                            <div class="cms">
                                <p><?php echo CommonHelper::renderHtml($product['product_description'], true); ?></p>
                            </div>
                        </div>
                    </div>
                </div>
            <?php $firstIsVisible = true;
            } ?>
            <?php if ($youtube_embed_code) { ?>
                <div class="descriptions-item accordianSectionJs">
                    <h2 class="descriptions-head  <?php echo ($firstIsVisible ? 'collapsed' : ''); ?>" data-bs-toggle="collapse" data-bs-target="#video" aria-expanded="true"><?php echo Labels::getLabel('LBL_Video', $siteLangId); ?>
                        <svg class="svg plus toggleAccordianJs" width="16" height="16">
                            <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#plus">
                            </use>
                        </svg>
                    </h2>
                    <?php if ($youtube_embed_code != "") { ?>
                        <div id="video" class="collapse <?php echo (false === $firstIsVisible ? 'show' : ''); ?>" data-bs-parent="#accordionExample">
                            <div class="descriptions-data">
                                <div class="mb-4 video-wrapper">
                                    <iframe width="100%" height="315" src="//www.youtube.com/embed/<?php echo $youtube_embed_code ?>" allowfullscreen></iframe>
                                </div>
                            </div>
                        </div>
                    <?php $firstIsVisible = true;
                    } ?>
                </div>
            <?php } ?>
            <?php if ($shop['shop_payment_policy'] != '' || !empty($shop["shop_delivery_policy"] != "") || !empty($shop["shop_delivery_policy"] != "")) { ?>
                <div class="descriptions-item accordianSectionJs">
                    <h2 class="descriptions-head  <?php echo ($firstIsVisible ? 'collapsed' : ''); ?>" data-bs-toggle="collapse" data-bs-target="#policies" aria-expanded="true"><?php echo Labels::getLabel('LBL_Shop_Policies', $siteLangId); ?>
                        <svg class="svg plus toggleAccordianJs" width="16" height="16">
                            <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#plus">
                            </use>
                        </svg>
                    </h2>
                    <div id="policies" class="collapse <?php echo (false === $firstIsVisible ? 'show' : ''); ?>" data-bs-parent="#accordionExample">
                        <div class="descriptions-data">
                            <div class="cms">
                                <?php if ($shop['shop_payment_policy'] != '') { ?>
                                    <h6><?php echo Labels::getLabel('LBL_Payment_Policy', $siteLangId) ?></h6>
                                    <p><?php echo !empty($shop['shop_payment_policy']) ? nl2br($shop['shop_payment_policy']) : ''; ?></p>
                                    <br>
                                <?php } ?>
                                <?php if ($shop['shop_delivery_policy'] != '') { ?>
                                    <h6><?php echo Labels::getLabel('LBL_Delivery_Policy', $siteLangId) ?></h6>
                                    <p><?php echo (!empty($shop['shop_delivery_policy'])) ? nl2br($shop['shop_delivery_policy']) : ''; ?></p>
                                    <br>
                                <?php } ?>
                                <?php if ($shop['shop_refund_policy'] != '') { ?>
                                    <h6><?php echo Labels::getLabel('LBL_Refund_Policy', $siteLangId) ?></h6>
                                    <p><?php echo (!empty($shop['shop_refund_policy'])) ? nl2br($shop['shop_refund_policy']) : ''; ?></p>
                                <?php } ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php $firstIsVisible = true;
            } ?>
            <?php if (!empty($product['selprodComments'])) { ?>
                <div class="descriptions-item accordianSectionJs">
                    <h2 class="descriptions-head <?php echo ($firstIsVisible ? 'collapsed' : ''); ?>" data-bs-toggle="collapse" data-bs-target="#extra_comments" aria-expanded="true"><?php echo Labels::getLabel('LBL_Extra_comments', $siteLangId); ?>
                        <svg class="svg plus toggleAccordianJs" width="16" height="16">
                            <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#plus">
                            </use>
                        </svg>
                    </h2>
                    <div id="extra_comments" class="collapse <?php echo (false === $firstIsVisible ? 'show' : ''); ?>" data-bs-parent="#accordionExample">
                        <div class="descriptions-data">
                            <div class="cms">
                                <p>
                                    <?php echo CommonHelper::displayNotApplicable($siteLangId, nl2br($product['selprodComments'])); ?>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            <?php } ?>
        </div>
    </div>
</div>
<script>
    $(function() {
        if (1 >= $('.accordianSectionJs').length) {
            $('.toggleAccordianJs').parent('[data-bs-toggle]').removeAttr('data-bs-toggle');
            $('.toggleAccordianJs').remove();
        }
    });
</script>