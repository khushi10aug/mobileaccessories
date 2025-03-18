<?php defined('SYSTEM_INIT') or die('Invalid Usage.'); ?>

<main class="main mainJs">
    <div class="container">
        <?php
        $this->includeTemplate('_partial/header/header-breadcrumb.php', [], false); ?>
        <div class="grid-layout">
            <div class="grid-layout-left">
                <button class="float-btn" type="button" data-bs-toggle="offcanvas" data-bs-target="#card-aside" aria-controls="card-aside">
                    <svg class="svg" width="20" height="20">
                        <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite-actions.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#nav">
                        </use>
                    </svg>
                </button>
                <div class="card  offcanvas sticky-sidebar sticky-top  card-aside" tabindex="-1" id="card-aside" aria-labelledby="card-asideLabel">
                    <div class="card-head">
                        <div class="card-head-label">
                            <h3 class="card-head-title">
                                <?php echo Labels::getLabel('LBL_headings', $siteLangId); ?>
                            </h3>
                        </div>
                        <div class="card-toolbar">
                            <button type="button" class="btn-close card-aside-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <div class="settings-inner">
                            <?php
                            $this->includeTemplate('import-export/_partial/top-navigation.php', ['siteLangId' => $siteLangId, 'action' => $action], false); ?>
                        </div>
                    </div>
                </div>
            </div>
            <div class="grid-layout-right">
                <div class="card" id="tabData"></div>
            </div>
        </div>
    </div>
</main>