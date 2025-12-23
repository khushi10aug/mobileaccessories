<?php defined('SYSTEM_INIT') or die('Invalid Usage.');

$imgArr = [];
if (!empty($image) && isset($image['afile_id']) && $image['afile_id'] != -1) {
    $uploadedTime = AttachedFile::setTimeParam($image['afile_updated_at']);
    $imgArr = [
        'url' => UrlHelper::getCachedUrl(UrlHelper::generateFileUrl('Image', $imageFunction, array($image['afile_record_id'], $image['afile_lang_id'], ImageDimension::VIEW_THUMB, $image['afile_id'], $image['afile_screen']), CONF_WEBROOT_FRONT_URL) . $uploadedTime, CONF_IMG_CACHE_TIME, '.jpg'),
        'name' => $image['afile_name'],
        'afile_id' => $image['afile_id'],
        'data-aspect-ratio' => $imageBrandDimensions[ImageDimension::VIEW_THUMB]['aspectRatio'],
    ]; 
 } 

 echo HtmlHelper::getfileInputHtml(
    ['onChange' => 'loadImageCropper(this)', 'accept' => 'image/*', 'data-name' => $file_type == 'logo' ? Labels::getLabel("FRM_BRAND_LOGO", $siteLangId) : Labels::getLabel("FRM_BRAND_BANNER_IMAGE", $siteLangId)],
    $siteLangId,
    ($canEdit ? 'deleteMedia(' . $image['afile_record_id'] . ',\'' . $file_type . '\',' . $image['afile_id'] .',' . $image['afile_lang_id'].',' . $image['afile_screen'] .')' :''),
    ($canEdit ? 'editDropZoneImages(this)': ''),
    $imgArr,
    'mt-3 dropzone-custom dropzoneContainerJs'
);
