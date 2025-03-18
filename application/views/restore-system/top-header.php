<button class="restore-btn restoreBtnJs" type="button" data-bs-toggle="offcanvas" data-bs-target="#offcanvasRestore" aria-label="restore button">
    <svg class="svg" width="24" height="24">
        <use xlink:href="<?php echo CONF_WEBROOT_FRONTEND; ?>images/retina/sprite-restore.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#gear">
        </use>
    </svg>    
</button>
<div class="fixed-right-btn">
    <button type="button" class="sticker-Btn popup-btn" onClick="demoRequestForm()"><span class="arrow-btn"><i class="arrow-anim"></i></span>BOOK A DEMO</>
</div>
<div class="offcanvas offcanvas-end offcanvas-restore" tabindex="-1" id="offcanvasRestore">
    <div class="offcanvas-body">
        <div class="demo">
            <div class="demo-restore timerSectionJs">
                <button class="demo-restore-btn" type="button" onclick="showRestorePopup()" aria-label="data restore">
                    <svg class="svg" width="20" height="20">                       
                        <use xlink:href="<?php echo CONF_WEBROOT_FRONTEND; ?>images/retina/sprite-restore.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#clock">
                        </use>
                    </svg>
                    <span class="restore-counter restoreCounterJs">00:00:00</span>
                </button>
            </div>
            <div class="demo-bg-wrap">
                <div class="demo-switch">
                    <ul class="views">
                        <?php
                        $admin = '';
                        $mobileSite = '';
                        $tabSite = '';
                        $desktopSite = '';
                        $adminUrl = 'admin';
                        if ('SiteDemoController' == FatApp::getController()) {
                            switch (FatApp::getAction()) {
                                case 'mobile':
                                    $mobileSite = 'is-active';
                                    break;
                                case 'tab':
                                    $tabSite = 'is-active';
                                    break;
                            }
                        } elseif (strpos($_SERVER['REQUEST_URI'], rtrim(CONF_WEBROOT_BACKEND, '/')) !== false) {
                            $admin = 'is-active';
                            $adminUrl = '';
                        } else {
                            $desktopSite = 'is-active';
                        }
                        ?>

                        <li class="views-item <?php echo $admin; ?> restoreElementJs">
                            <a class="views-links" title="Admin" href="<?php echo UrlHelper::generateUrl($adminUrl, '', [], CONF_WEBROOT_FRONTEND, null, false, false, false); ?>">
                                <svg class="svg" width="32" height="32">
                                    <use xlink:href="<?php echo CONF_WEBROOT_FRONTEND; ?>images/retina/sprite-restore.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#admin">
                                    </use>
                                </svg>
                                <span class="label"> Admin </span>
                            </a>
                        </li>
                        <li class="views-item <?php echo $desktopSite; ?> restoreElementJs">
                            <a class="views-links" title="Marketplace" href="<?php echo UrlHelper::generateUrl('', '', array(), CONF_WEBROOT_FRONTEND); ?>">
                                <svg class="svg" width="32" height="32">
                                    <use xlink:href="<?php echo CONF_WEBROOT_FRONTEND; ?>images/retina/sprite-restore.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#marketplace">
                                    </use>
                                </svg>
                                <span class="label"> Marketplace </span>
                            </a>
                        </li>

                        <li class="views-item <?php echo $mobileSite; ?> restoreElementJs">
                            <a class="views-links" title="Marketplace Mobile View" href="<?php echo UrlHelper::generateUrl('SiteDemo', 'mobile', array(), CONF_WEBROOT_FRONTEND); ?>">

                                <svg class="svg" width="32" height="32">
                                    <use xlink:href="<?php echo CONF_WEBROOT_FRONTEND; ?>images/retina/sprite-restore.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#mobile">
                                    </use>
                                </svg>
                                <span class="label">
                                    Mobile
                                </span>
                            </a>
                        </li>
                    </ul>
                </div>
                <div class="demo-action">
                    <a class="btn btn-brand" href="https://www.yo-kart.com/contact-us.html?demo-cta" rel="noopener" target="_blank">Start Your Marketplace</a>
                    <a class="btn btn-outline-brand" href="https://www.yo-kart.com/request-demo.html" rel="noopener"> Request a Demo </a>
                    <a class="btn btn-underline" href="https://www.yo-kart.com/yokart-marketing-website-feedback.html">Share Your Feedback</a>
                </div>
            </div>

        </div>
    </div>
</div>
<script>
    $(document).ready(function() {
        if ($(window).width() < 1025) {
            $(".restoreElementJs").remove();
        }
    });
</script>