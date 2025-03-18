<div class="container">
    <div class="header-blog-inner">
        <?php
        $imgDataType = '';
        $logoWidth = '';
        $fileData = AttachedFile::getAttachment(AttachedFile::FILETYPE_FRONT_LOGO, 0, 0, $siteLangId, false);
        $uploadedTime = AttachedFile::setTimeParam($fileData['afile_updated_at']);
        if (AttachedFile::FILE_ATTACHMENT_TYPE_SVG == $fileData['afile_attachment_type']) {
            $siteLogo = UrlHelper::getStaticImageUrl($fileData['afile_physical_path']) . $uploadedTime;
            $imgDataType = 'data-type="svg"';
            $logoWidth = 'width="120"';
        } else {
            $aspectRatioArr = AttachedFile::getRatioTypeArray($siteLangId);
            $siteLogo = UrlHelper::getCachedUrl(UrlHelper::generateFullFileUrl('Image', 'siteLogo', array($siteLangId), CONF_WEBROOT_FRONT_URL) . $uploadedTime, CONF_IMG_CACHE_TIME, '.jpg');
        }
        ?>
        <div class="logo" <?php echo $imgDataType; ?>>
            <a href="<?php echo UrlHelper::generateUrl(); ?>">
                <img <?php if (AttachedFile::FILE_ATTACHMENT_TYPE_OTHER == $fileData['afile_attachment_type'] && $fileData['afile_aspect_ratio'] > 0) { ?>
                    data-ratio="<?php echo $aspectRatioArr[$fileData['afile_aspect_ratio']]; ?>" <?php } ?>
                    src="<?php echo $siteLogo; ?>"
                    alt="<?php echo FatApp::getConfig('CONF_WEBSITE_NAME_' . $siteLangId); ?>"
                    title="<?php echo FatApp::getConfig('CONF_WEBSITE_NAME_' . $siteLangId) ?>" <?php echo $logoWidth; ?>>
            </a>
        </div>
        <div class="header-blog-right">
            <span class="blog-overlay blogOverlayJs" id="blog-overlay"></span>
            <?php if (!empty($categoriesArr)) {
                $noOfCharAllowedInNav = 60;
                $navLinkCount = 0;
                foreach ($categoriesArr as $cat) {
                    if (!$cat) {
                        break;
                    }

                    $noOfCharAllowedInNav = $noOfCharAllowedInNav - mb_strlen($cat);
                    if ($noOfCharAllowedInNav < 0) {
                        break;
                    }
                    $navLinkCount++;
                } ?>
                <div class="menu-nav menuNavJs">
                    <ul class="nav-blog <?php echo ($navLinkCount > 4) ? 'justify-content-between' : ''; ?>">
                        <?php $mainNavigation = array_slice($categoriesArr, 0, $navLinkCount, true);
                        $hiddenCategories = array_diff($categoriesArr, $mainNavigation);
                        foreach ($mainNavigation as $id => $cat) { ?>
                            <li class="nav-blog-item">
                                <a class="nav-blog-link" href="<?php echo UrlHelper::generateUrl('Blog', 'category', array($id)); ?>">
                                    <?php echo $cat; ?>
                                </a>
                            </li>
                        <?php } ?>
                        <?php if (!empty($hiddenCategories)) { ?>
                            <li class="nav-blog-item">
                                <a class="nav-blog-link nav-blog-more" data-bs-toggle="collapse" href="#blog-more" role="button" aria-expanded="false" aria-controls="blog-more">
                                    <?php echo Labels::getLabel('LBL_MORE'); ?>
                                </a>
                                <div class="collapse blog-more" id="blog-more">
                                    <div class="container">
                                        <ul>
                                            <?php
                                            foreach ($hiddenCategories as $id => $cat) { ?>
                                                <li>
                                                    <a href="<?php echo UrlHelper::generateUrl('Blog', 'category', array($id)); ?>">
                                                        <?php echo $cat; ?>
                                                    </a>
                                                </li>
                                            <?php } ?>
                                        </ul>
                                    </div>
                                </div>
                            </li>
                        <?php } ?>
                    </ul>
                </div>
            <?php } ?>

            <button class="btn-menu blogPageBurgerIconJs" data-bs-backdrop="true" data-bs-toggle="offcanvas" data-bs-target="#blog-menu">
                <svg class="svg" width="20" height="20">
                    <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite-blog.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#burgerMenu">
                    </use>
                </svg>
            </button>
            <button class="btn-blog-search" data-bs-backdrop="true" data-bs-toggle="offcanvas" data-bs-target="#blog-search">
                <svg class="svg" width="20" height="20">
                    <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite-blog.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#magnifying">
                    </use>
                </svg>
            </button>
        </div>
    </div>
</div>