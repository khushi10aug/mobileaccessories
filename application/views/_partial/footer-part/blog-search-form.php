<?php defined('SYSTEM_INIT') or die('Invalid Usage'); ?>
<!-- offcanvas-blog-search -->
<div class="offcanvas offcanvas-top offcanvas-blog-search" tabindex="-1" id="blog-search">
    <div class="blog-search">
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
                    alt="<?php echo FatApp::getConfig('CONF_WEBSITE_NAME_' . $siteLangId, FatUtility::VAR_STRING, '') ?>"
                    title="<?php echo FatApp::getConfig('CONF_WEBSITE_NAME_' . $siteLangId, FatUtility::VAR_STRING, '') ?>" <?php echo $logoWidth; ?>>
            </a>
        </div>
        <div class="blog-search-inner">
            <?php $blogSearchFrm->setFormTagAttribute('onSubmit', 'submitBlogSearch(this); return(false);');
            $blogSearchFrm->setFormTagAttribute('class', 'blog-search-form');
            $blogSearchFrm->developerTags['colClassPrefix'] = 'col-lg-12 col-md-12 col-sm-';
            $blogSearchFrm->developerTags['fld_default_col'] = 12;
            $blogSearchFrm->removeField($blogSearchFrm->getField('btnProductSrchSubmit'));
            $keywordFld = $blogSearchFrm->getField('keyword');
            $keywordFld->developerTags['noCaptionTag'] = true;
            $keywordFld->setFieldTagAttribute('class', 'blog-search-input');
            $keywordFld->setFieldTagAttribute('id', 'blogAutoCompleteJs');
            $keywordFld->setFieldTagAttribute('placeholder', Labels::getLabel('LBL_Search_In_Blogs...'));
            echo $blogSearchFrm->getFormTag();
            echo $blogSearchFrm->getFieldHTML('pageSize');
            echo $blogSearchFrm->getFieldHTML('keyword');
            echo $blogSearchFrm->getExternalJS(); ?>
            <div class="search-suggestions" id="blogSuggetionList"></div>
        </div>
        <button type="button" class="btn btn-close text-reset btn-search-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <script type="application/javascript">
        let timeout = null;
        $(document).on('focus keyup', '#blogAutoCompleteJs', function(e) {
            let keyword = $(this).val();
            if (keyword.length < 3) {
                return;
            }
            if (timeout != null) {
                clearTimeout(timeout);
            }

            timeout = setTimeout(function() {
                blogAutocomplete(keyword);
                timeout = nulll
            }, 1000);

        });

        function blogAutocomplete(keyword) {
            $('#blogSuggetionList').html("");
            fcom.updateWithAjax(
                fcom.makeUrl("blog", "autocomplete"), {
                    keyword
                },
                function(t) {
                    $('#blogSuggetionList').html(t.html);
                },
            );
        }
    </script>
</div>