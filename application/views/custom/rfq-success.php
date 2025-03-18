<?php defined('SYSTEM_INIT') or die('Invalid Usage.'); ?>
<main id="body" class="body main">
    <section class="section">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-xl-9">
                    <div class="order-completed">
                        <div class="thanks-screen text-center">
                            <!-- Icon -->
                            <div class="success-animation">
                                <svg class="checkmark" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 52 52">
                                    <circle class="checkmark__circle" cx="26" cy="26" r="25" fill="none"></circle>
                                    <path class="checkmark__check" fill="none" d="M14.1 27.2l7.1 7.2 16.7-16.8"></path>
                                </svg>
                            </div>
                            <h2><?php echo Labels::getLabel('LBL_THANK_YOU!', $siteLangId); ?></h2>
                            <h3>
                                <?php
                                $rfqUrl = UrlHelper::generateUrl('RequestForQuotes', '', [], CONF_WEBROOT_DASHBOARD, null, false, false, false);
                                $msg = Labels::getLabel('LBL_YOUR_REQUEST_FOR_QUOTE_({RFQ-NO})_HAS_BEEN_PLACED!', $siteLangId);
                                echo CommonHelper::replaceStringData($msg, [
                                    '{RFQ-NO}' => '<a href="' . $rfqUrl . '" class="link-brand link-underline">' . $rfqData['rfq_number'] . '</a>'
                                ]);
                                ?>
                            </h3>
                            <p>
                                <svg class="svg" width="22px" height="22px">
                                    <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#TimePlaced" href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#TimePlaced">
                                    </use>
                                </svg>
                                <?php
                                $replace = [
                                    '{TIME-PLACED}' => '<strong>' . Labels::getLabel('LBL_TIME_PLACED', $siteLangId) . '</strong>',
                                    '{DATE-TIME}' => FatDate::format($rfqData['rfq_added_on'], true),
                                ];
                                $msg = Labels::getLabel('LBL_{TIME-PLACED}:_{DATE-TIME}', $siteLangId);
                                $msg = CommonHelper::replaceStringData($msg, $replace);
                                echo $msg;
                                ?>
                            </p>
                        </div>
                        <ul class="completed-detail">
                            <li class="completed-detail-item">
                                <h4><?php echo Labels::getLabel('LBL_RFQ_FOR', $siteLangId); ?>:</h4>
                                <p><?php echo $rfqData['rfq_title']; ?></p>
                            </li>
                            <li class="completed-detail-item">
                                <h4><?php echo Labels::getLabel('LBL_REQUESTED_QUANTITY', $siteLangId); ?>:</h4>
                                <p><?php echo $rfqData['rfq_quantity'] . ' ' . applicationConstants::getWeightUnitName($siteLangId, $rfqData['rfq_quantity_unit'], true); ?></p>
                            </li>
                            <?php if (!empty($rfqData['rfq_delivery_date']) && 0 < strtotime($rfqData['rfq_delivery_date'])) { ?>
                                <li class="completed-detail-item">
                                    <h4><?php echo Labels::getLabel('LBL_EXPECTED_DELIVERY_DATE', $siteLangId); ?>:</h4>
                                    <p><?php echo  FatDate::format($rfqData['rfq_delivery_date']); ?></p>
                                </li>
                            <?php } ?>
                            <?php if (Product::PRODUCT_TYPE_DIGITAL != $rfqData['rfq_product_type']) { ?>
                                <li class="completed-detail-item">
                                    <h4><?php echo Labels::getLabel('LBL_DELIVERY_ADDRESS', $siteLangId); ?>:</h4>
                                    <p>
                                        <?php if (!empty($rfqData['addr_name'])) { ?>
                                            <strong><?php echo $rfqData['addr_name']; ?></strong>
                                            <br>
                                        <?php } ?>
                                        <?php echo $rfqData['addr_address1']; ?>,
                                        <?php echo (!empty($rfqData['addr_address2'])) ? $rfqData['addr_address2'] . ',' : ''; ?>
                                        <?php echo (!empty($rfqData['addr_city'])) ? $rfqData['addr_city'] . ',' : ''; ?>
                                        <?php echo (!empty($rfqData['state_name'])) ? $rfqData['state_name'] . ',' : ''; ?>
                                        <?php echo (!empty($rfqData['country_name'])) ? $rfqData['country_name'] . ',' : ''; ?>
                                        <?php echo (!empty($rfqData['addr_zip'])) ? Labels::getLabel('LBL_Zip:', $siteLangId) . $rfqData['addr_zip'] . ',' : ''; ?>
                                        <?php $dcode = (!empty($rfqData['addr_phone_dcode'])) ? ValidateElement::formatDialCode($rfqData['addr_phone_dcode']) : ''; ?>
                                        <?php echo (!empty($rfqData['addr_phone'])) ? Labels::getLabel('LBL_Phone:', $siteLangId) . $dcode . $rfqData['addr_phone'] . ',' : ''; ?>
                                    </p>
                                </li>
                            <?php } ?>
                        </ul>
                        <div class="text-center mt-5">
                            <a class="btn btn-outline-brand" href="<?php echo UrlHelper::generateUrl('', '', [], CONF_WEBROOT_FRONTEND); ?>">
                                <?php echo Labels::getLabel('LBL_CONTINUE_SHOPPING'); ?>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
    </section>
</main>