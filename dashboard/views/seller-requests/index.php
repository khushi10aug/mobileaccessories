<?php defined('SYSTEM_INIT') or die('Invalid Usage.');
$this->includeTemplate('_partial/dashboardNavigation.php');
$canRequestCustomProducts = (0 < FatApp::getConfig('CONF_SELLER_CAN_REQUEST_CUSTOM_PRODUCT', FatUtility::VAR_INT, 0) && 1 > FatApp::getConfig('CONF_WITHOUT_PROD_VARIANTS', FatUtility::VAR_INT, 0)) ? 1 : 0;
?>
<div class="content-wrapper content-space">
    <?php
    $data = [
        'headingLabel' => Labels::getLabel('LBL_Requests', $siteLangId),
        'siteLangId' => $siteLangId,
    ];

    if ($canEdit && !$noRecordFound) {
        $otherBtnHtml = '<div class="dropdown">
                                <button class="btn btn-outline-gray dropdown-toggle" type="button" id="dashboardDropdown" data-bs-toggle="dropdown" data-bs-auto-close="outside"  aria-haspopup="true" aria-expanded="false">
                                    ' . Labels::getLabel('LBL_New_Request', $siteLangId) . '
                                </button>
                                
                                    <ul class="dropdown-menu dropdown-menu-fit dropdown-menu-right dropdown-menu-anim" aria-labelledby="dashboardDropdown">';
        if ($canRequestCustomProducts) {
            $otherBtnHtml .= '<li class="dropdown-menu-item">
                                                <a class="dropdown-menu-link" href="' . UrlHelper::generateUrl('customProducts', 'form') . '">
                                                    ' . Labels::getLabel('LBL_Marketplace_Product', $siteLangId) . '
                                                </a>
                                            </li>';
        }

        if (FatApp::getConfig('CONF_BRAND_REQUEST_APPROVAL', FatUtility::VAR_INT, 0)) {
            $otherBtnHtml .= '<li class="dropdown-menu-item">
                                                <a class="dropdown-menu-link" href="javascript:void(0);" onclick="addBrandReqForm(0)">
                                                    ' . Labels::getLabel('LBL_Brand', $siteLangId) . '
                                                </a>
                                            </li>';
        }

        if (FatApp::getConfig('CONF_PRODUCT_CATEGORY_REQUEST_APPROVAL', FatUtility::VAR_INT, 0)) {
            $otherBtnHtml .= '<li class="dropdown-menu-item">
                                                <a class="dropdown-menu-link" href="javascript:void(0);" onclick="addCategoryReqForm(0)">
                                                    ' . Labels::getLabel('LBL_Category', $siteLangId) . '
                                                </a>
                                            </li>';
        }

        if ($canRequestBadge && !empty($approvalRequiredBadges)) {
            $otherBtnHtml .= '<li class="dropdown-menu-item">
                                                <a class="dropdown-menu-link" href="javascript:void(0);" onclick="addBadgeReqForm(0)">
                                                    ' . Labels::getLabel('LBL_BADGE_REQUEST', $siteLangId) . '
                                                </a>
                                            </li>';
        }
        $otherBtnHtml .= '</ul>
                               
                            </div>';

        $data['otherButtons'] = [
            'html' => $otherBtnHtml
        ];
    }
    $this->includeTemplate('_partial/header/content-header.php', $data, false); ?>
    <div class="content-body">
        <div class="card card-tabs">
            <div class="card-head">
                <?php
                if (!$noRecordFound) {
                    $variables = array('siteLangId' => $siteLangId, 'action' => $action, 'canRequestBadge' => $canRequestBadge, 'approvalRequiredBadges' => $approvalRequiredBadges, 'reqBadges' => $reqBadges);
                    $this->includeTemplate('seller-requests/_partial/requests-navigation.php', $variables, false);
                }
                ?>
            </div>
            <div class="card-table">
                <div class="pagebody--js">
                    <?php if ($noRecordFound) { ?>
                        <div class="row justify-content-center my-5">
                            <div class="col-md-6">
                                <div class="info">
                                    <span> <svg class="svg">
                                            <use xlink:href="<?php echo CONF_WEBROOT_FRONT_URL; ?>images/retina/sprite.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#info"
                                                href="<?php echo CONF_WEBROOT_FRONT_URL; ?>images/retina/sprite.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#info">
                                            </use>
                                        </svg><?php echo Labels::getLabel('LBL_Generate_requests_using_buttons_below', $siteLangId); ?></span>
                                </div>
                            </div>
                        </div>
                        <div class="row justify-content-center">
                            <?php if ($canRequestCustomProducts) { ?>
                                <div class="col-md-3">
                                    <div class="no-data-found">
                                        <div class="img">
                                            <img src="<?php echo CONF_WEBROOT_FRONT_URL; ?>images/retina/no-product-requests.svg"
                                                width="70px" height="70px">
                                        </div>
                                        <div class="data">
                                            <div class="action">
                                                <a class="btn btn-outline-gray btn-sm"
                                                    href="<?php echo UrlHelper::generateUrl('CustomProducts', 'form'); ?>"><?php echo Labels::getLabel('LBL_New_Product_Request', $siteLangId); ?></a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php } ?>
                            <?php if (FatApp::getConfig('CONF_BRAND_REQUEST_APPROVAL', FatUtility::VAR_INT, 0)) { ?>
                                <div class="col-md-3">
                                    <div class="no-data-found">
                                        <div class="img">
                                            <img src="<?php echo CONF_WEBROOT_FRONT_URL; ?>images/retina/no-brand-requests.svg"
                                                width="70px" height="70px">
                                        </div>
                                        <div class="data">
                                            <div class="action">
                                                <a class="btn btn-outline-gray btn-sm" href="javascript:void(0);"
                                                    onclick="addBrandReqForm(0)"><?php echo Labels::getLabel('LBL_New_Brand_Request', $siteLangId); ?></a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php } ?>
                            <?php if (FatApp::getConfig('CONF_PRODUCT_CATEGORY_REQUEST_APPROVAL', FatUtility::VAR_INT, 0)) { ?>
                                <div class="col-md-3">
                                    <div class="no-data-found">
                                        <div class="img">
                                            <img src="<?php echo CONF_WEBROOT_FRONT_URL; ?>images/retina/no-category-requests.svg"
                                                width="70px" height="70px">
                                        </div>
                                        <div class="data">
                                            <div class="action">
                                                <a class="btn btn-outline-gray btn-sm" href="javascript:void(0);"
                                                    onclick="addCategoryReqForm(0)"><?php echo Labels::getLabel('LBL_New_Category_Request', $siteLangId); ?></a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php } ?>
                            <?php if ($canRequestBadge) { ?>
                                <?php if (!empty($reqBadges)) { ?>
                                    <div class="col-md-3">
                                        <div class="no-data-found">
                                            <div class="img">
                                                <img src="<?php echo CONF_WEBROOT_FRONT_URL; ?>images/retina/no-brand-requests.svg"
                                                    width="70px" height="70px">
                                            </div>
                                            <div class="data">
                                                <div class="action">
                                                    <a class="btn btn-outline-gray btn-sm" href="javascript:void(0);"
                                                        onclick="addBadgeReqForm(0)"><?php echo Labels::getLabel('LBL_ADD_BADGE_REQUEST', $siteLangId); ?></a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php } else if (empty($reqBadges) && !empty($approvalRequiredBadges)) { ?>
                                    <div class="col-md-3">
                                        <div class="no-data-found">
                                            <div class="img">
                                                <img src="<?php echo CONF_WEBROOT_FRONT_URL; ?>images/retina/no-brand-requests.svg"
                                                    width="70px" height="70px">
                                            </div>
                                            <div class="data">
                                                <div class="action">
                                                    <a class="btn btn-outline-gray btn-sm" href="javascript:void(0);"
                                                        onclick="addBadgeReqForm(0)"><?php echo Labels::getLabel('LBL_ADD_BADGE_REQUEST', $siteLangId); ?></a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php } ?>
                            <?php } ?>

                        </div>
                    <?php } else { ?>
                        <div id="listing">
                            <div class="container m-2"><?php echo Labels::getLabel('LBL_Processing...', $siteLangId); ?>
                            </div>
                        </div>
                    <?php } ?>
                </div>
                <span class="editRecord--js"></span>
            </div>
        </div>
    </div>
</div>
<script>
    var ratioTypeSquare = <?php echo AttachedFile::RATIO_TYPE_SQUARE; ?>;
    var canRequestCustomProduct = <?php echo $canRequestCustomProducts; ?>;
    var withoutVariants = <?php echo FatApp::getConfig('CONF_WITHOUT_PROD_VARIANTS', FatUtility::VAR_INT, 0); ?>;
    $(document).ready(function() {
        if (canRequestCustomProduct && 1 > withoutVariants) {
            searchCustomCatalogProducts();
        } else {
            searchBrandRequests();
        }
    });

    var RECORD_TYPE_SHOP = <?php echo BadgeLinkCondition::RECORD_TYPE_SHOP; ?>;
</script>