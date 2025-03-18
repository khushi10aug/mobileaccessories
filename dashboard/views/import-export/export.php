<?php defined('SYSTEM_INIT') or die('Invalid Usage.'); ?>
<div class="card-head">
    <?php $variables = array('siteLangId' => $siteLangId, 'action' => $action, 'canEditImportExport' => $canEditImportExport, 'canUploadBulkImages' => $canUploadBulkImages);
    $this->includeTemplate('import-export/_partial/top-navigation.php', $variables, false); ?>
</div>
<div class="card-body">
    <div class="tabs__content">
        <div class="row">
            <div class="col-md-12" id="exportFormBlock">
                <div class="settings">
                    <?php foreach ($options as $key => $val) {
                        $onclick = 'exportForm(' . $key . '); return false;';
                        $href = 'javascript:void(0);';
                        if (Importexport::TYPE_LANGUAGE_LABELS == $key) {
                            $onclick = '';
                            $href = UrlHelper::generateUrl('ImportExport', 'exportLabels');
                        }
                    ?>
                        <a class="setting" href="<?php echo $href; ?>" onclick="<?php echo $onclick; ?>">
                            <div class="setting__icon">
                                <span class="icon">
                                    <svg class="icon" width="40" height="40">
                                        <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite-settings.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#general-settings">
                                        </use>
                                    </svg>
                                </span>
                            </div>
                            <div class="setting__detail">
                                <h6><?php echo $val; ?></h6>
                                <span><?php echo $optionsMessages[$key]; ?></span>
                            </div>
                        </a>
                    <?php } ?>
                </div>
            </div>
        </div>
    </div>
</div>