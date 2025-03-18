<?php defined('SYSTEM_INIT') or die('Invalid Usage.'); ?>
<?php $this->includeTemplate('_partial/dashboardNavigation.php'); ?>
<?php
$returnRequestMsgsForm->addHiddenField('', 'isSeller', 1);
$btn = $frmMsg->getField('btn_submit');
if (null != $btn) {
    $btn->addFieldTagAttribute('class', 'btn btn-brand');
}
?>
<div class="content-wrapper content-space">
    <?php
    $data = [
        'headingLabel' => Labels::getLabel('LBL_View_Order_Return_Request', $siteLangId),
        'siteLangId' => $siteLangId,
        'headingBackButton' => [
            'href' => UrlHelper::generateUrl('Seller', 'orderReturnRequests'),
            'onclick' => ''
        ]
    ];
    $this->includeTemplate('_partial/header/content-header.php', $data, false); ?>
    <div class="content-body">
        <div class="row">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-head">
                        <div class="card-head-label">
                            <h5 class="card-title"><?php echo Labels::getLabel('LBL_Request_Details', $siteLangId); ?></h5>
                        </div> <?php if ($canEdit) { ?>
                            <div class="btn-group">
                                <?php if ($canEscalateRequest) { ?>
                                    <a class="btn btn-outline-gray btn-sm" onclick="javascript: return confirm('<?php echo Labels::getLabel('MSG_Do_you_want_to_proceed?', $siteLangId); ?>')" href="<?php echo UrlHelper::generateUrl('Account', 'EscalateOrderReturnRequest', array($request['orrequest_id'])); ?>"><?php echo CommonHelper::replaceStringData(Labels::getLabel('LBL_Escalate_to_{websitename}', $siteLangId), ["{websitename}" => FatApp::getConfig('CONF_WEBSITE_NAME_' . $siteLangId)] ); ?></a>
                                <?php } ?>
                                <?php if ($canApproveReturnRequest) { ?>
                                    <a class="btn btn-outline-gray btn-sm" onclick="javascript: return confirm('<?php echo Labels::getLabel('MSG_Do_you_want_to_proceed?', $siteLangId); ?>')" href="<?php echo UrlHelper::generateUrl('Seller', 'approveOrderReturnRequest', array($request['orrequest_id'])); ?>"><?php echo Labels::getLabel('LBL_Approve_Refund', $siteLangId); ?></a>
                                <?php } ?>
                            </div>
                        <?php } ?>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-12"> <?php echo $this->includeTemplate('_partial/product/product-info-html.php', ['order' => $request, 'siteLangId' => $siteLangId], false, true); ?>
                                <div class="divider"></div>
                                <ul class="list-stats list-stats-double mt-4">
                                    <li class="list-stats-item">
                                        <span class="label"><?php echo Labels::getLabel('LBL_REQUEST_ID', $siteLangId); ?></span>
                                        <span class="value"><?php echo $request['orrequest_reference']; ?></span>
                                    </li>
                                    <li class="list-stats-item">
                                        <span class="label"><?php echo Labels::getLabel('LBL_Order_Id/Invoice_Number', $siteLangId); ?></span>
                                        <span class="value"><?php echo $request['op_invoice_number']; ?></span>
                                    </li>
                                    <li class="list-stats-item">
                                        <span class="label"><?php echo Labels::getLabel('LBL_Return_Qty', $siteLangId); ?></span>
                                        <span class="value"><?php echo $request['orrequest_qty']; ?></span>
                                    </li>
                                    <li class="list-stats-item">
                                        <span class="label"><?php echo Labels::getLabel('LBL_Request_Type', $siteLangId); ?></span>
                                        <span class="value"><?php echo $returnRequestTypeArr[$request['orrequest_type']]; ?></span>
                                    </li>
                                    <?php if (isset($attachedFile)) { ?>
                                        <li class="list-stats-item">
                                            <span class="label"><?php echo Labels::getLabel('LBL_DOWNLOAD_ATTACHED_FILE', $siteLangId); ?></span>
                                            <span class="value">
                                                <a class="btn btn-outline-gray btn-icon" href="<?php echo UrlHelper::generateUrl('Seller', 'downloadAttachedFileForReturn', array($request['orrequest_id'], 0)); ?>">
                                                    <svg class="svg" width="18" height="18">
                                                        <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite-actions.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#download">
                                                        </use>
                                                    </svg>
                                                    <?php echo Labels::getLabel('LBL_DOWNLOAD'); ?>
                                                </a>
                                            </span>
                                        </li>
                                    <?php } ?>
                                </ul>


                            </div>
                        </div>
                        <?php echo ($canEdit) ? $returnRequestMsgsForm->getFormHtml() : ''; ?>
                        <div class="mt-5 messageListBlock--js">
                            <h6>
                                <?php echo Labels::getLabel('LBL_Return_Request_Messages', $siteLangId); ?>
                            </h6>
                            <div id="loadMoreBtnDiv"></div>
                            <ul class="messages-list" id="messagesList"></ul>
                        </div>
                        <?php if ($canEdit && $request && ($request['orrequest_status'] != OrderReturnRequest::RETURN_REQUEST_STATUS_REFUNDED && $request['orrequest_status'] != OrderReturnRequest::RETURN_REQUEST_STATUS_WITHDRAWN)) {
                            $frmMsg->setFormTagAttribute('onSubmit', 'setUpReturnOrderRequestMessage(this); return false;');
                            $frmMsg->setFormTagAttribute('class', 'form');
                            $frmMsg->developerTags['colClassPrefix'] = 'col-lg-12 col-md-12 col-sm-';
                            $frmMsg->developerTags['fld_default_col'] = 12;
                            $btn = $frmMsg->getField('btn_submit');
                            $btn->developerTags['noCaptionTag'] = true;
                        ?>
                            <div class="messages-list">
                                <ul>
                                    <li>
                                        <div class="msg_db">
                                            <div class="avtar"><img src="<?php echo UrlHelper::generateFileUrl('Image', 'user', array($logged_user_id, ImageDimension::VIEW_THUMB, 1), CONF_WEBROOT_FRONTEND); ?>" alt="<?php echo $logged_user_name; ?>" title="<?php echo $logged_user_name; ?>"></div>
                                        </div>
                                        <div class="msg__desc">
                                            <span class="msg__title"><?php echo $logged_user_name; ?></span>
                                            <?php echo $frmMsg->getFormHtml(); ?>
                                        </div>
                                    </li>
                                </ul>
                            </div>
                        <?php } ?>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="ml-md-4">
                    <div class="order-block">
                        <h4><?php echo Labels::getLabel('LBL_REFUND_SUMMARY', $siteLangId); ?></h4>
                        <?php
                        $returnDataArr = CommonHelper::getOrderProductRefundAmtArr($request);
                        $totalAmount = round($returnDataArr['op_prod_price'] + $returnDataArr['op_refund_tax'] + $returnDataArr['op_refund_shipping'] + $request['op_rounding_off'], 2);
                        ?>
                        <div class="cart-summary">
                            <ul>
                                <li>
                                    <span class="lable"><?php echo Labels::getLabel('LBL_Reason', $siteLangId); ?> </span>
                                    <span class="value"><?php echo $request['orreason_title']; ?></span>
                                </li>
                                <li>
                                    <span class="lable"><?php echo Labels::getLabel('LBL_Date', $siteLangId); ?></span>
                                    <span class="value"><?php echo FatDate::format($request['orrequest_date']); ?></span>
                                </li>
                                <li>
                                    <span class="lable"><?php echo Labels::getLabel('LBL_Status', $siteLangId); ?></span>
                                    <span class="value"><?php echo $requestRequestStatusArr[$request['orrequest_status']]; ?></span>
                                </li>
                                <li>
                                    <span class="lable"><?php echo Labels::getLabel('LBL_Product_Price', $siteLangId); ?></span>
                                    <span class="value"><?php echo CommonHelper::displayMoneyFormat($returnDataArr['op_prod_price'], true, false); ?></span>
                                </li>
                                <li>
                                    <span class="lable"><?php echo Labels::getLabel('LBL_Tax', $siteLangId); ?></span>
                                    <span class="value"><?php echo CommonHelper::displayMoneyFormat($returnDataArr['op_refund_tax'], true, false); ?></span>
                                </li>
                                <li>
                                    <span class="lable"><?php echo Labels::getLabel('LBL_Shipping', $siteLangId); ?></span>
                                    <span class="value"><?php echo CommonHelper::displayMoneyFormat($returnDataArr['op_refund_shipping'], true, false); ?></span>
                                </li>
                                <li class="highlighted">
                                    <span class="lable"><?php echo Labels::getLabel('LBL_Total_Amount', $siteLangId); ?></span>
                                    <span class="value">
                                        <?php
                                        echo CommonHelper::displayMoneyFormat($totalAmount, true, false);
                                        if ($request['op_qty'] == $request['orrequest_qty'] && 0 != $request['op_rounding_off']) {
                                            echo ' (' . $request['op_rounding_off'] . ')';
                                        }
                                        ?>
                                    </span>
                                </li>
                            </ul>
                        </div>
                    </div>
                    <div class="order-block">
                        <h4><?php echo Labels::getLabel('LBL_VENDOR_RETURN_ADDRESS', $siteLangId); ?></h4>
                        <div class="order-block-data">
                            <ul class="list-stats list-stats-double">
                                <?php if ($request['op_shop_owner_name'] != '') { ?>
                                    <li class="list-stats-item">
                                        <span class="label"><?php echo Labels::getLabel('LBL_VENDOR_NAME', $siteLangId); ?></span>
                                        <span class="value"><?php echo $request['op_shop_owner_name']; ?></span>
                                    </li>
                                <?php } ?>
                                <?php if ($request['op_shop_name'] != '') { ?>
                                    <li class="list-stats-item">
                                        <span class="label"><?php echo Labels::getLabel('LBL_SHOP_NAME', $siteLangId); ?></span>
                                        <span class="value"><?php echo $request['op_shop_name']; ?></span>
                                    </li>
                                <?php } ?>
                                <?php if (strlen((string)$vendorReturnAddress['ura_name']) > 0) { ?>
                                    <li class="list-stats-item">
                                        <span class="label"><?php echo Labels::getLabel('LBL_ADDRESS_NAME', $siteLangId); ?></span>
                                        <span class="value"><?php echo $vendorReturnAddress['ura_name']; ?></span>
                                    </li>
                                <?php } ?>
                                <?php if (strlen((string)$vendorReturnAddress['ura_address_line_1']) > 0) { ?>
                                    <li class="list-stats-item list-stats-item-full">
                                        <span class="label"><?php echo Labels::getLabel('LBL_ADDRESS_LINE_1', $siteLangId); ?></span>
                                        <span class="value"><?php echo $vendorReturnAddress['ura_address_line_1']; ?></span>
                                    </li>
                                <?php } ?>
                                <?php if (strlen((string)$vendorReturnAddress['ura_address_line_2']) > 0) { ?>
                                    <li class="list-stats-item list-stats-item-full">
                                        <span class="label"><?php echo Labels::getLabel('LBL_ADDRESS_LINE_2', $siteLangId); ?></span>
                                        <span class="value"><?php echo $vendorReturnAddress['ura_address_line_2']; ?></span>
                                    </li>
                                <?php } ?>
                                <?php if (strlen((string)$vendorReturnAddress['ura_city']) > 0) { ?>
                                    <li class="list-stats-item">
                                        <span class="label"><?php echo Labels::getLabel('LBL_CITY', $siteLangId); ?></span>
                                        <span class="value"><?php echo $vendorReturnAddress['ura_city']; ?></span>
                                    </li>
                                <?php } ?>
                                <?php if (strlen((string)$vendorReturnAddress['state_name']) > 0) { ?>
                                    <li class="list-stats-item">
                                        <span class="label"><?php echo Labels::getLabel('LBL_STATE_NAME', $siteLangId); ?></span>
                                        <span class="value"><?php echo $vendorReturnAddress['state_name']; ?></span>
                                    </li>
                                <?php } ?>
                                <?php if (strlen((string)$vendorReturnAddress['country_name']) > 0) { ?>
                                    <li class="list-stats-item">
                                        <span class="label"><?php echo Labels::getLabel('LBL_COUNTRY_NAME', $siteLangId); ?></span>
                                        <span class="value"><?php echo $vendorReturnAddress['country_name']; ?></span>
                                    </li>
                                <?php } ?>
                                <?php if (strlen((string)$vendorReturnAddress['ura_zip']) > 0) { ?>
                                    <li class="list-stats-item">
                                        <span class="label"><?php echo Labels::getLabel('LBL_Zip', $siteLangId); ?></span>
                                        <span class="value"><?php echo $vendorReturnAddress['ura_zip']; ?></span>
                                    </li>
                                <?php } ?>
                                <?php if (strlen((string)$vendorReturnAddress['ura_phone']) > 0) { ?>
                                    <li class="list-stats-item">
                                        <span class="label"><?php echo Labels::getLabel('LBL_Phone', $siteLangId); ?></span>
                                        <span class="value"><span class="default-ltr"><?php echo ValidateElement::formatDialCode($vendorReturnAddress['ura_phone_dcode']) . $vendorReturnAddress['ura_phone']; ?></span></span>
                                    </li>
                                <?php } ?>
                            </ul>
                        </div>
                    </div>
                    <div class="order-block">
                        <h4 class="dropdown-toggle-custom" data-bs-toggle="collapse" data-bs-target="#customerAddress" aria-expanded="false" aria-controls="customerAddress">
                            <?php echo Labels::getLabel('LBL_CUSTOMER_DETAIL', $siteLangId); ?>
                            <i class="dropdown-toggle-custom-arrow"></i>
                        </h4>
                        <div class="collapse" id="customerAddress">
                            <div class="order-block-data">
                                <ul class="list-stats list-stats-double">
                                    <li class="list-stats-item">
                                        <span class="label"><?php echo Labels::getLabel('LBL_CUSTOMER_NAME', $siteLangId); ?></span>
                                        <span class="value"><?php echo $request['buyer_name']; ?></span>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>