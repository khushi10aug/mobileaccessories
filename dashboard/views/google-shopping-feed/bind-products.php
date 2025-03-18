<?php defined('SYSTEM_INIT') or die('Invalid Usage.');

$this->includeTemplate('_partial/dashboardNavigation.php'); ?>

<input type='hidden' name='adsBatchId' value="<?php echo $adsBatchId; ?>">
<div class="content-wrapper content-space">
    <?php
    $batchName = AdsBatch::getAttributesById($adsBatchId, 'adsbatch_name');
    $str = Labels::getLabel('LBL_ADD_PRODUCTS_TO_{BATCH}', $siteLangId);
    $data = [
        'headingLabel' => CommonHelper::replaceStringData($str, ['{BATCH}' => $batchName]),
        'siteLangId' => $siteLangId,
        'headingBackButton' => true,
    ];

    if (!isset($bindProductForm) || true === $bindProductForm) {
        $data['newRecordBtn'] = true;
        $data['newRecordBtnAttrs'] = [
            'attr' => [
                'onclick' => 'bindproductform(' . $adsBatchId . ')',
                'title' => Labels::getLabel('BTN_BIND_PRODUCTS', $siteLangId)
            ],
        ];
    }
    $this->includeTemplate('_partial/header/content-header.php', $data, false);
    ?>
    <div class="content-body">
        <div class="row">
            <div class="col-lg-12">
                <div class="card">
                    <?php
                    if (!empty($frmSearch)) {
                        $listTopButtons = [
                            [
                                'attr' => [
                                    'class' => 'btn btn-outline-gray btn-icon formActionBtn-js disabled',
                                    'onclick' => 'unlinkproducts(' . $adsBatchId . ')',
                                    'title' => Labels::getLabel('LBL_UNLINK', $siteLangId)
                                ],
                                'label' => '<svg class="svg btn-icon-start" width="18" height="18">
                                            <use xlink:href="' . CONF_WEBROOT_URL . 'images/retina/sprite-actions.svg' . AttachedFile::setTimeParam(RELEASE_DATE) . '#delete">
                                            </use>
                                        </svg><span class="btn-txt">' . Labels::getLabel('LBL_UNLINK', $siteLangId) . '</span>'
                            ]
                        ];
                        $frmSearch->addFormTagAttribute('onsubmit', 'searchProducts(this); return false;');
                        $fld = $frmSearch->getField('keyword');
                        $fld->setFieldTagAttribute('data-callback', 'searchProducts();');
                        require_once(CONF_THEME_PATH . '_partial/listing/listing-search-form.php');
                    } ?>
                    <div class="card-body">
                        <div id="listing">
                            <?php echo Labels::getLabel('LBL_Loading..', $siteLangId); ?>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>
</div>