<?php defined('SYSTEM_INIT') or die('Invalid Usage . ');

$dateWiseArr = [];
if (is_array($trackingInfo['data']['tracking']['checkpoints']) && count($trackingInfo['data']['tracking']['checkpoints'])) {
    foreach ($trackingInfo['data']['tracking']['checkpoints'] as $data) {
        $dateWiseArr[FatDate::format($data['checkpoint_time'])][] = $data;
    }
}
?>
<div class="modal-header">
    <h5 class="modal-title">
        <a class="btn-back" href="javascript:void(0)" onclick="getItemStatusHistory(<?php echo $orderId; ?> ,<?php echo $op_id; ?>)">
            <svg class="svg" width="24" height="24">
                <use xlink:href="<?php echo CONF_WEBROOT_URL ?>images/retina/sprite-actions.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#back">
                </use>
            </svg>
        </a>
        <?php echo Labels::getLabel('LBL_TRACKING_DETAIL', $siteLangId) . " - " . $orderNumber; ?>
    </h5>
</div>
<div class="modal-body opStausLogJs">
    <div class="form-edit-body loaderContainerJs">
        <div class="timeline-v4">
            <?php foreach ($dateWiseArr as $date => $dataArr) {
                $headTitle = HtmlHelper::getTheDay((current($dataArr))['checkpoint_time'], $siteLangId);
            ?>
                <div class="rowJs" data-reference="<?php echo $date; ?>">
                    <div class="timeline-v4__item-date">
                        <span class="tag"><?php echo $headTitle; ?></span>
                    </div>
                    <ul class="timeline-v4__items">
                        <?php foreach ($dataArr as $date => $data) { ?>
                            <li class="timeline-v4__item">
                                <span class="timeline-v4__item-time"><?php echo date('H:i', strtotime($data['checkpoint_time'])); ?></span>
                                <div class="timeline-v4__item-desc">
                                    <span class="timeline-v4__item-text">
                                        <b><?php echo $data['message']; ?></b>
                                    </span>
                                    <span class="timeline-v4__item-text">
                                        <span><?php echo $data['location']; ?></span>
                                    </span>
                                </div>
                            </li>
                        <?php } ?>
                    </ul>
                </div>
            <?php }
            $arrListing = $dateWiseArr;
            $tbody = new HtmlElement('table');
            include(CONF_THEME_PATH . '_partial/listing/no-record-found.php');
            echo $tbody->getHtml();
            ?>
        </div>
    </div>
</div>