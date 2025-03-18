<?php defined('SYSTEM_INIT') or die('Invalid Usage.'); ?>
<section class=" <?php echo (false === CommonHelper::isAppUser()) ? ' ' : ''; ?>">
    <div class="container">
        <div class="blog-detail post-data">
            <div class="blog-detail-left">
                <a class="btn btn-icon btn-link btn-back" href="<?php echo UrlHelper::generateUrl('Blog'); ?>">
                    <svg class="svg" width="20" height="20">
                        <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite-blog.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#arrow-back">
                        </use>
                    </svg>
                </a> <?php echo Labels::getLabel('LBL_BACK_TO_HOME', $siteLangId); ?>
                <div class="blog-head">
                    <h1 class="title"> <?php echo $blogPostData['post_title']; ?></h1>
                    <div class="posted-by">
                        <div class="user-profile">
                            <div class="user-profile_photo"> <img src="<?php echo CONF_WEBROOT_URL; ?>images/users/100_2.jpg" alt=""></div>
                            <div class="user-profile_data">
                                <span class="user-profile_title">
                                    <?php echo $blogPostData['post_author_name']; ?>
                                </span>
                                <span class="time"> <?php echo FatDate::format($blogPostData['post_added_on']); ?>
                                </span>
                            </div>
                        </div>
                        <span>
                            <?php $categoryIds = !empty($blogPostData['categoryIds']) ? explode(',', $blogPostData['categoryIds']) : array();
                            $categoryNames = !empty($blogPostData['categoryNames']) ? explode('~', $blogPostData['categoryNames']) : array();
                            $categories = array_combine($categoryIds, $categoryNames); ?>
                            <?php if (!empty($categories)) {
                                echo Labels::getLabel('Lbl_in', $siteLangId);
                                foreach ($categories as $id => $name) {
                                    if ($name == end($categories)) { ?>
                                        <a href="<?php echo UrlHelper::generateUrl('Blog', 'category', array($id)); ?>" class="text--dark"><?php echo $name; ?></a>
                                    <?php break;
                                    } ?>
                                    <a href="<?php echo UrlHelper::generateUrl('Blog', 'category', array($id)); ?>" class="text--dark"><?php echo $name; ?></a>,
                            <?php }
                            } ?>
                        </span>
                        <div class="share-blog">
                            <button class="btn btn-outline-brand btn-wide btn-icon no-after" type="button" data-bs-toggle="modal" data-bs-target="#socialSharing<?php echo $blogPostData['post_id']; ?>">
                                <svg class="svg" width="16" height="16">
                                    <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite-blog.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#share">
                                    </use>
                                </svg>
                                <?php echo Labels::getLabel('LBL_SHARE'); ?>
                            </button>
                        </div>
                    </div>
                </div>
                <?php if (false === CommonHelper::isAppUser()) { ?>
                    <div class="posted-media">
                        <?php if (!empty($post_images)) { ?>
                            <div class="post__pic">
                                <?php foreach ($post_images as $post_image) { ?>
                                    <div class="items">
                                        <div class="media-wrapper">
                                            <?php
                                            $uploadedTime = AttachedFile::setTimeParam($post_image['afile_updated_at']);
                                            $pictureAttr = [
                                                'webpImageUrl' => [ImageDimension::VIEW_DESKTOP => UrlHelper::getCachedUrl(UrlHelper::generateFullUrl('Image', 'blogPostFront', array($post_image['afile_record_id'], $post_image['afile_lang_id'], 'WEBP' . ImageDimension::VIEW_NORMAL, 0, $post_image['afile_id']), CONF_WEBROOT_FRONT_URL) . $uploadedTime, CONF_IMG_CACHE_TIME, '.webp')],
                                                'jpgImageUrl' => [ImageDimension::VIEW_DESKTOP => UrlHelper::getCachedUrl(UrlHelper::generateFullUrl('Image', 'blogPostFront', array($post_image['afile_record_id'], $post_image['afile_lang_id'], ImageDimension::VIEW_NORMAL, 0, $post_image['afile_id']), CONF_WEBROOT_FRONT_URL) . $uploadedTime, CONF_IMG_CACHE_TIME, '.jpg')],
                                                'ratio' => '16:9',
                                                'imageUrl' => UrlHelper::getCachedUrl(UrlHelper::generateFullUrl('Image', 'blogPostFront', array($post_image['afile_record_id'], $post_image['afile_lang_id'], ImageDimension::VIEW_NORMAL, 0, $post_image['afile_id']), CONF_WEBROOT_FRONT_URL) . $uploadedTime, CONF_IMG_CACHE_TIME, '.jpg'),
                                                'alt' => (!empty($post_image['afile_attribute_alt'])) ? $post_image['afile_attribute_alt'] : $blogPostData['post_title'],
                                                'siteLangId' => $siteLangId,
                                            ];
                                            $this->includeTemplate('_partial/picture-tag.php', $pictureAttr);
                                            ?>
                                        </div>
                                    </div>
                                <?php } ?>
                            </div>
                        <?php } ?>
                    </div>
                <?php } ?>
                <div class="post-content">
                    <?php if (false === CommonHelper::isAppUser()) { ?>
                        <!-- <div class="post-meta-detail">
                            <?php /*<ul class="likes-count">
                                    <!--<li><i class="icn-like"><img src="<?php echo CONF_WEBROOT_URL; ?>images/eye.svg"></i>500                     Views </li>-->
                            <?php if ($blogPostData['post_comment_opened']) { ?>
                            <li><i class="icn-msg">
                            <img
                                        src="<?php echo CONF_WEBROOT_URL; ?>images/comments.svg"></i><?php echo $commentsCount,' ',Labels::getLabel('Lbl_Comments', $siteLangId); ?>
                            </li>
                            
                            </ul>*/ ?>
                        </div> -->

                    <?php } ?>

                    <?php echo FatUtility::decodeHtmlEntities($blogPostData['post_description']); ?>

                </div>
                <?php
                if (false === CommonHelper::isAppUser()) {
                    if ($blogPostData['post_comment_opened']) { ?>
                        <?php echo $srchCommentsFrm->getFormHtml(); ?>
                        <div class="comments" id="container--comments">
                            <h5>
                                <?php echo ($commentsCount) ? sprintf(Labels::getLabel('Lbl_Comments(%s)', $siteLangId), $commentsCount) : Labels::getLabel('Lbl_Comments', $siteLangId); ?>
                            </h5>
                            <div id="comments--listing"> </div>
                            <div class="text-center" id="loadMoreCommentsBtnDiv"></div>
                        </div>
                    <?php } ?>
                    <?php if ($blogPostData['post_comment_opened'] && UserAuthentication::isUserLogged() && isset($postCommentFrm)) { ?>

                        <div id="respond" class="comment-respond">
                            <h4>
                                <?php echo Labels::getLabel('Lbl_Leave_A_Comment', $siteLangId); ?>
                            </h4>
                            <?php
                            $postCommentFrm->setFormTagAttribute('class', 'form');
                            $postCommentFrm->setFormTagAttribute('onsubmit', 'setupPostComment(this);return false;');
                            $postCommentFrm->setRequiredStarPosition(Form::FORM_REQUIRED_STAR_POSITION_NONE);
                            $postCommentFrm->developerTags['colClassPrefix'] = 'col-md-';
                            $postCommentFrm->developerTags['fld_default_col'] = 12;
                            $nameFld = $postCommentFrm->getField('bpcomment_author_name');
                            $nameFld->addFieldTagAttribute('readonly', true);
                            $nameFld->setFieldTagAttribute('placeholder', Labels::getLabel('LBL_Name', $siteLangId));
                            $nameFld->developerTags['col'] = 6;
                            $emailFld = $postCommentFrm->getField('bpcomment_author_email');
                            $emailFld->addFieldTagAttribute('readonly', true);
                            $emailFld->setFieldTagAttribute('placeholder', Labels::getLabel('LBL_Email_Address', $siteLangId));
                            $emailFld->developerTags['col'] = 6;
                            $commentFld = $postCommentFrm->getField('bpcomment_content');
                            $commentFld->setFieldTagAttribute('placeholder', Labels::getLabel('LBL_Message', $siteLangId));

                            $btnSubmitFld = $postCommentFrm->getField('btn_submit');
                            $btnSubmitFld->setFieldTagAttribute('class', 'btn btn-brand btn-wide');

                            echo $postCommentFrm->getFormHtml(); ?>
                        </div>

                <?php }
                } ?>
            </div>
            <?php if (false === CommonHelper::isAppUser()) {
                $this->includeTemplate('_partial/blogSidePanel.php', array('popularPostList' => $popularPostList, 'featuredPostList' => $featuredPostList));
            } ?>
        </div>
    </div>
</section>

<div class="modal fade" id="socialSharing<?php echo $blogPostData['post_id']; ?>" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header border-bottom-0">
                <h5 class="modal-title"> </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="share-wrap">
                    <h6><?php echo Labels::getLabel('LBL_SHARE_THIS_VIA', $siteLangId); ?></h6>
                    <ul class="social-sharing">
                        <li class="social-facebook">
                            <a class="social-link st-custom-button" data-network="facebook" data-url="<?php echo UrlHelper::generateFullUrl('Blog', 'postDetail', array($blogPostData['post_id'])) . '/'; ?>">
                                <svg class="svg" width="16" height="16">
                                    <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#facebook">
                                    </use>
                                </svg>

                            </a>
                        </li>
                        <li class="social-twitter">
                            <a class="social-link st-custom-button" data-network="twitter">
                                <svg class="svg" width="16" height="16">
                                    <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#twitter">
                                    </use>
                                </svg>

                            </a>
                        </li>
                        <li class="social-pintrest">
                            <a class="social-link st-custom-button" data-network="pinterest">
                                <svg class="svg" width="16" height="16">
                                    <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#pinterest">
                                    </use>
                                </svg>

                            </a>
                        </li>
                        <li class="social-email">
                            <a class="social-link st-custom-button" data-network="email">

                                <svg class="svg" width="16" height="16">
                                    <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#envelope">
                                    </use>
                                </svg>

                            </a>
                        </li>
                    </ul>
                    <div class="gap"></div>
                    <h6><?php echo Labels::getLabel('MSG_OR_COPY_LINK', $siteLangId); ?></h6>
                    <div class="clipboard">
                        <span class="copy-input clipboardTextJs">
                            <?php echo UrlHelper::generateFullUrl('Blog', 'postDetail', array($blogPostData['post_id'])); ?>
                        </span>
                        <button class="copy-btn" type="button" onclick="copyText(this, true)" data-bs-toggle="tooltip" data-placement="top" title="<?php echo Labels::getLabel('MSG_COPY_TO_CLIPBOARD', $siteLangId); ?>">
                            <svg class="svg" width="18" height="18">
                                <use xlink:href="<?php echo  CONF_WEBROOT_FRONTEND; ?>images/retina/sprite.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#copy-to-all">
                                </use>
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php if (false === CommonHelper::isAppUser()) { ?>
    <script>
        var boolLoadComments = (<?php echo FatUtility::int($blogPostData['post_comment_opened']); ?>) ? true : false;
        /* for social sticky */
        $(window).scroll(function() {
            body_height = $(".post-data").position();
            scroll_position = $(window).scrollTop();
            if (body_height.top < scroll_position)
                $(".post-data").addClass("is-fixed");
            else
                $(".post-data").removeClass("is-fixed");

        });
    </script>
    <?php echo $this->includeTemplate('_partial/shareThisScript.php'); ?>
<?php } ?>