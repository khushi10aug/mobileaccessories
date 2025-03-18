<?php $this->includeTemplate('_partial/dashboardNavigation.php');
$htm = '';
if (!empty($fields)) {
    $htm = '<div class="dropdown custom-drag-drop">
                        <button class="btn btn-outline-gray btn-icon dropdown-toggle no-after" type="button" id="dropdownMenuButton" data-bs-toggle="dropdown" data-bs-auto-close="outside"  aria-haspopup="true" aria-expanded="false">
                        <svg class="svg btn-icon-start" width="18" height="18">
                        <use xlink:href="' . CONF_WEBROOT_URL . 'images/retina/sprite-actions.svg' . AttachedFile::setTimeParam(RELEASE_DATE) . '#columns">
                        </use>
                    </svg>' . Labels::getLabel('LBL_COLUMNS', $siteLangId) . '
                        </button>
                        <div class="dropdown-menu dropdown-menu-right dropdown-menu-fit dropdown-menu-anim scroll scroll-y" aria-labelledby="dropdownMenuButton">
                                <ul class="list-drag-drop" id="sortable">';
    foreach ($fields as $key => $label) {
        $isDef = (in_array($key, $defaultColumns));
        $disabled = ($isDef) ? 'disabled' : '';
        $checked = ($isDef) ? 'checked="checked"' : '';

        $htm .= '<li>
                                        <label class="checkbox ' . $disabled . '">
                                            <input class="filterColumn-js" type="checkbox" name="reportColumns" value="' . $key . '" ' . $checked . $disabled . ' onclick=reloadList(false)>
                                            ' . $label . '
                                        </label>
                                        <i class="icn fas fa-grip-lines handleJs"></i>
                                    </li>';
    }
    $htm .= '</ul>
                        </div>
                    </div>';
}
?>

<div class="content-wrapper content-space">
    <?php
    $extraBtns = isset($actionButtons['otherButtons']) ? $actionButtons['otherButtons'] : [];

    $data = [
        'headingLabel' => $pageTitle,
        'siteLangId' => $siteLangId,
        'otherButtons' => [
            [
                'html' => $htm
            ],
            [
                'attr' => [
                    'onclick' => 'exportForm()',
                    'title' => Labels::getLabel('LBL_Export', $siteLangId)
                ],
                'icon' => '<svg class="svg btn-icon-start" width="18" height="18">
                    <use xlink:href="' . CONF_WEBROOT_URL . 'images/retina/sprite-actions.svg' . AttachedFile::setTimeParam(RELEASE_DATE) . '#export">
                    </use>
                </svg>',
                'label' => Labels::getLabel('LBL_Export', $siteLangId)
            ],
        ]
    ];

    $data['otherButtons'] = array_merge($extraBtns, $data['otherButtons']);

    $this->includeTemplate('_partial/header/content-header.php', $data, false); ?>
    <div class="content-body">
        <div class="row">
            <div class="col-lg-12">
                <div class="card">
                    <?php require_once (CONF_THEME_PATH . '_partial/listing/listing-search-form.php'); ?>
                    <div class="card-body p-0">
                        <div class="listing-tbl" id="listingDiv">
                            <?php echo Labels::getLabel('LBL_Loading..', $siteLangId); ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    var controllerName = '<?php echo str_replace('Controller', '', FatApp::getController()); ?>';
</script>