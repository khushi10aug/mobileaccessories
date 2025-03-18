<?php defined('SYSTEM_INIT') or die('Invalid Usage.'); ?>
<div class="modal-header">
    <h5 class="modal-title">
        <?php echo Labels::getLabel('LBL_COLLECTIONS_LAYOUTS', $siteLangId); ?>
    </h5>
</div>
<div class="modal-body form-edit">
    <div class="form-edit-body p-0 loaderContainerJs">
        <ul class="layout">
            <?php foreach ($typeLayouts as $type => $layouts) { ?>
                <li class="layout-item">
                    <div class="layout-head dropdown-toggle-custom collapsed" data-bs-toggle="collapse"
                        data-bs-target="#collectionType<?php echo $type; ?>" aria-expanded="false"
                        aria-controls="collectionType<?php echo $type; ?>">
                        <span class="h3"><?php echo $typeArr[$type]; ?></span>
                        <i class="dropdown-toggle-custom-arrow"></i>
                    </div>
                    <div class="layout-data collapse" id="collectionType<?php echo $type; ?>">
                        <?php
                        $appOnlyCollections = Collections::COLLECTIONS_FOR_APP_ONLY;
                        $webOnlyCollections = Collections::COLLECTIONS_FOR_WEB_ONLY;
                        foreach ($layouts as $layoutId => $layout) {
                        ?>
                            <div class="layout-block" data-bs-toggle="tooltip" data-bs-placement="top"
                                title="<?php echo $layout; ?>"
                                onclick="collectionForm(<?php echo $type; ?>, <?php echo $layoutId; ?>)">
                                <?php if (in_array($layoutId, $appOnlyCollections)) { ?>
                                    <div class="app-only">
                                        <svg class="svg" width="14" height="14">
                                            <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite-layout.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#mobile">
                                            </use>
                                        </svg>
                                    </div>
                                <?php  } else if (in_array($layoutId, $webOnlyCollections)) { ?>
                                    <div class="app-only">
                                        <svg class="svg" width="14" height="14">
                                            <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite-layout.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#web">
                                            </use>
                                        </svg>
                                    </div>
                                <?php  } else { ?>
                                    <div class="app-only">
                                        <svg class="svg" width="14" height="14">
                                            <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite-layout.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#device">
                                            </use>
                                        </svg>
                                    </div>
                                <?php } ?>
                                <svg class="svg" width="140" height="70">
                                    <use
                                        xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite-layout.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#<?php echo Collections::layoutIconClass($layoutId); ?>">
                                    </use>
                                </svg>
                            </div>
                        <?php } ?>
                    </div>
                </li>
            <?php } ?>
        </ul>
    </div>
</div>