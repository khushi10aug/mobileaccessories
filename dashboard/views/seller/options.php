<?php defined('SYSTEM_INIT') or die('Invalid Usage.');
$this->includeTemplate('_partial/dashboardNavigation.php'); ?>

<div class="content-wrapper content-space">
    <?php
    $data = [
        'headingLabel' =>  Labels::getLabel('LBL_Seller_Options', $siteLangId),
        'siteLangId' => $siteLangId,
        'canEdit' => $canEdit
    ];

    if ($canEdit) {
        $data['otherButtons'] = [
            [
                'attr' => [
                    'onclick' => 'optionForm(0)',
                    'title' => Labels::getLabel('LBL_Add_Option', $siteLangId)
                ],
                'label' => '<svg class="svg btn-icon-start" width="18" height="18">
                                <use xlink:href="' . CONF_WEBROOT_URL . 'images/retina/sprite-actions.svg' . AttachedFile::setTimeParam(RELEASE_DATE) . '#add">
                                </use>
                            </svg>' . Labels::getLabel('LBL_Add_Option', $siteLangId)
            ],
        ];
    }
    $this->includeTemplate('_partial/header/content-header.php', $data, false); ?>
    <div class="content-body">
        <div class="row">
            <div class="col-lg-12">
                <div class="card">
                    <?php require_once(CONF_THEME_PATH . '_partial/listing/listing-search-form.php'); ?>
                    <div class="card-table">
                        <div class="table-responsive table-scrollable js-scrollable" id="optionListing">

                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>