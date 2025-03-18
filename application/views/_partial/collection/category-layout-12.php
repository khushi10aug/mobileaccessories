<?php defined('SYSTEM_INIT') or die('Invalid Usage.');
if (isset($collection['categories']) && count($collection['categories'])) { ?>
    <section class="section" data-section="category-layout">
        <div class="container">
            <header class="section-head">
                <?php echo ($collection['collection_name'] != '') ? ' <div class="section-heading"><h2>' . $collection['collection_name'] . '</h2></div>' : ''; ?>
                <?php if (count($collection['categories']) > Collections::LIMIT_CATEGORY_LAYOUT2) { ?>
                    <div class="section-action">
                        <a href="<?php echo UrlHelper::generateUrl('Collections', 'View', array($collection['collection_id'])); ?>" class="link-underline link-more"><?php echo Labels::getLabel('LBL_VIEW_ALL', $siteLangId); ?></a>
                    </div>
                <?php } ?>
            </header>
            <div class="section-body">
                <div class="category-layout">
                    <div class="category-layout-banner">
                        <?php $fileRow = CommonHelper::getImageAttributes(AttachedFile::FILETYPE_COLLECTION_IMAGE, $collection['collection_id']);
                        $uploadedTime = AttachedFile::setTimeParam($fileRow['afile_updated_at']);
                        ?>
                        <?php
                        $dimensions = ImageDimension::getDisplayCollectionImageData('', Collections::TYPE_CATEGORY_LAYOUT12);
                        $pictureAttr = [
                            'webpImageUrl' => [
                                ImageDimension::VIEW_DESKTOP => UrlHelper::generateFileUrl('Image', 'collectionReal', array($fileRow['afile_record_id'], $fileRow['afile_lang_id'], 'WEBP' . ImageDimension::VIEW_DESKTOP, AttachedFile::FILETYPE_COLLECTION_IMAGE, applicationConstants::SCREEN_DESKTOP, $collection['collection_layout_type'])) . $uploadedTime,
                                ImageDimension::VIEW_TABLET => UrlHelper::generateFileUrl('Image', 'collectionReal', array($fileRow['afile_record_id'], $fileRow['afile_lang_id'], 'WEBP' . ImageDimension::VIEW_TABLET, AttachedFile::FILETYPE_COLLECTION_IMAGE, applicationConstants::SCREEN_IPAD, $collection['collection_layout_type'])) . $uploadedTime,
                                ImageDimension::VIEW_MOBILE => UrlHelper::generateFileUrl('Image', 'collectionReal', array($fileRow['afile_record_id'], $fileRow['afile_lang_id'], 'WEBP' . ImageDimension::VIEW_MOBILE, AttachedFile::FILETYPE_COLLECTION_IMAGE, applicationConstants::SCREEN_MOBILE, $collection['collection_layout_type'])) . $uploadedTime
                            ],
                            'jpgImageUrl' => [
                                ImageDimension::VIEW_DESKTOP => UrlHelper::generateFileUrl('Image', 'collectionReal', array($fileRow['afile_record_id'], $fileRow['afile_lang_id'], ImageDimension::VIEW_DESKTOP, AttachedFile::FILETYPE_COLLECTION_IMAGE, applicationConstants::SCREEN_DESKTOP, $collection['collection_layout_type'])) . $uploadedTime,
                                ImageDimension::VIEW_TABLET => UrlHelper::generateFileUrl('Image', 'collectionReal', array($fileRow['afile_record_id'], $fileRow['afile_lang_id'], ImageDimension::VIEW_TABLET, AttachedFile::FILETYPE_COLLECTION_IMAGE, applicationConstants::SCREEN_IPAD, $collection['collection_layout_type'])) . $uploadedTime,
                                ImageDimension::VIEW_MOBILE => UrlHelper::generateFileUrl('Image', 'collectionReal', array($fileRow['afile_record_id'], $fileRow['afile_lang_id'], ImageDimension::VIEW_MOBILE, AttachedFile::FILETYPE_COLLECTION_IMAGE, applicationConstants::SCREEN_MOBILE, $collection['collection_layout_type'])) . $uploadedTime
                            ],
                            'ratio' => $dimensions['aspectRatio'],
                            'imageUrl' => UrlHelper::generateFileUrl('Image', 'collectionReal', array($fileRow['afile_record_id'], $fileRow['afile_lang_id'], ImageDimension::VIEW_DESKTOP, AttachedFile::FILETYPE_COLLECTION_IMAGE, applicationConstants::SCREEN_DESKTOP, $collection['collection_layout_type'])) . $uploadedTime,
                            'alt' => (!empty($fileRow['afile_attribute_alt'])) ? $fileRow['afile_attribute_alt'] : $collection['collection_name'],
                            'title' => (!empty($fileRow['afile_attribute_title'])) ? $fileRow['afile_attribute_title'] : $collection['collection_name'],
                            'siteLangId' => $siteLangId,
                        ];

                        $this->includeTemplate('_partial/picture-tag.php', $pictureAttr); ?>
                    </div>
                    <div class="category-layout-grid">
                        <?php foreach ($collection['categories'] as $category) { ?>
                            <div class="category-layout-item">
                                <h3 class="category-layout-head" title="<?php echo $category['prodcat_name']; ?>"><?php echo $category['prodcat_name']; ?></h3>
                                <div class="category-layout-body">
                                    <ul class="category-layout-links">
                                        <?php $i = 1;
                                        foreach ($category['subCategories'] as $subCat) { ?>
                                            <li>
                                                <a
                                                    href="<?php echo UrlHelper::generateUrl('Category', 'View', array($subCat['prodcat_id'])); ?>" title="<?php echo $subCat['prodcat_name']; ?>">
                                                    <?php echo $subCat['prodcat_name']; ?></a>
                                            </li>
                                        <?php $i++;
                                            if ($i > 5) {
                                                break;
                                            }
                                        } ?>
                                    </ul>
                                    <?php $fileRow = CommonHelper::getImageAttributes(AttachedFile::FILETYPE_CATEGORY_BANNER, $category['prodcat_id']);
                                    $uploadedTime = AttachedFile::setTimeParam($fileRow['afile_updated_at']);
                                    ?>
                                    <?php
                                    $pictureAttr = [
                                        'webpImageUrl' => [ImageDimension::VIEW_DESKTOP => UrlHelper::generateFileUrl('Category', 'thumb', array($category['prodcat_id'], $siteLangId, 'WEBP' . ImageDimension::VIEW_SMALL)) . $uploadedTime],
                                        'jpgImageUrl' => [ImageDimension::VIEW_DESKTOP => UrlHelper::generateFileUrl('Category', 'thumb', array($category['prodcat_id'], $siteLangId, ImageDimension::VIEW_SMALL)) . $uploadedTime],
                                        'ratio' => '4:1',
                                        'imageUrl' => UrlHelper::generateFileUrl('Category', 'thumb', array($category['prodcat_id'], $siteLangId, ImageDimension::VIEW_SMALL)) . $uploadedTime,
                                        'alt' => (!empty($fileRow['afile_attribute_alt'])) ? $fileRow['afile_attribute_alt'] : $category['prodcat_name'],
                                        'title' => (!empty($fileRow['afile_attribute_title'])) ? $fileRow['afile_attribute_title'] : $category['prodcat_name'],
                                        'siteLangId' => $siteLangId,
                                    ];

                                    $this->includeTemplate('_partial/picture-tag.php', $pictureAttr);
                                    ?>
                                </div>
                            </div>
                        <?php } ?>
                    </div>
                </div>
            </div>
        </div>
    </section>
<?php }
