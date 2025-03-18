<?php defined('SYSTEM_INIT') or die('Invalid Usage.');
$printData = false;
if (!isset($tbody)) {
    $printData = true;
    $tbody = new HtmlElement('tbody', ['class' => 'listingRecordJs']);
}

$serialNo = ($page - 1) * $pageSize + 1;
foreach ($arrListing as $sn => $row) {
    $cls = (($serialNo % 2) == 0) ? 'even' : 'odd';
    $tr = $tbody->appendElement('tr', ['class' => $cls, 'data-row' => $serialNo, 'id' => $row['slide_id']]);
    foreach ($fields as $key => $val) {
        $tdAttr = ('action' == $key) ? ['class' => 'align-right'] : (('select_all' == $key) ? ['class' => 'col-check'] : []);
        $td = $tr->appendElement('td', $tdAttr);
        switch ($key) {
            case 'dragdrop':
                $div = $td->appendElement('div', ['class' => 'handleJs']);
                $div->appendElement('plaintext', $tdAttr, '<svg class="svg" width="18" height="18">
                    <use xlink:href="' . CONF_WEBROOT_URL . 'images/retina/sprite-actions.svg' . AttachedFile::setTimeParam(RELEASE_DATE) . '#drag"></use>
                </svg>', true);
                break;
            case 'select_all':
                $td->appendElement('plaintext', $tdAttr, '<label class="checkbox"><input class="selectItemJs" type="checkbox" name="record_ids[]" value=' . $row['slide_id'] . '><i class="input-helper"></i></label>', true);
                break;
            case 'listSerial':
                $td->appendElement('plaintext', $tdAttr, $serialNo);
                break;
            case 'slide_media':
                $icon = AttachedFile::getAttachment(AttachedFile::FILETYPE_HOME_PAGE_BANNER, $row['slide_id'], 0, $siteLangId);
                $uploadedTime = AttachedFile::setTimeParam($icon['afile_updated_at']);
                $imageSlideDimensions = ImageDimension::getData(ImageDimension::TYPE_SLIDE, ImageDimension::VIEW_THUMB);
                $url = UrlHelper::getCachedUrl(
                    UrlHelper::generateFileUrl(
                        'Image',
                        'Slide',
                        array(
                            $row['slide_id'],
                            applicationConstants::SCREEN_DESKTOP,
                            $siteLangId,
                            ImageDimension::VIEW_THUMB,
                            false
                        ),
                        CONF_WEBROOT_FRONT_URL
                    ) . $uploadedTime,
                    CONF_IMG_CACHE_TIME,
                    '.jpg'
                );
                $slideImage = '<a href="' . UrlHelper::getCachedUrl(UrlHelper::generateFileUrl('Image', 'Slide', array($row['slide_id'], applicationConstants::SCREEN_DESKTOP, $siteLangId, ImageDimension::VIEW_ORIGINAL, false), CONF_WEBROOT_FRONT_URL) . $uploadedTime, CONF_IMG_CACHE_TIME, '.jpg') . '" data-featherlight="image"><img data-aspect-ratio = "' . $imageSlideDimensions[ImageDimension::VIEW_THUMB]['aspectRatio'] . '" class="banner-thumb"  title="' . $row['slide_title'] . '" alt="' . $row['slide_title'] . '" src="' . $url . '"></a>';
                $td->appendElement('plaintext', $tdAttr, $slideImage, true);
                break;
            case 'slide_title':
                $title = !empty($row['epage_label']) ? $row['epage_label'] : $row[$key];
                $td->appendElement('plaintext', $tdAttr, $title, true);
                break;
            case 'slide_active':
                $statusAct = ($canEdit) ? 'updateStatus(event, this, ' . $row['slide_id'] . ', ' . ((int) !$row[$key]) . ')' : 'return false;';
                $statusClass = ($canEdit) ? '' : 'disabled';
                $checked = applicationConstants::ACTIVE == $row[$key] ? 'checked' : '';

                $htm = '<span class="switch switch-sm switch-icon">
                    <label>
                        <input type="checkbox" data-old-status="' . $row[$key] . '" value="' . $row['slide_id'] . '" ' . $checked . ' onclick="' . $statusAct . '" ' . $statusClass . '>
                        <span class="input-helper"></span>
                    </label>
                </span>';
                $td->appendElement('plaintext', $tdAttr, $htm, true);
                break;
            case 'action':
                $data = [
                    'siteLangId' => $siteLangId,
                    'recordId' => $row['slide_id']
                ];

                if ($canEdit) {
                    $data['editButton'] = [];
                    $data['deleteButton'] = [];
                }
                $actionItems = $this->includeTemplate('_partial/listing/listing-action-buttons.php', $data, false, true);
                $td->appendElement('plaintext', $tdAttr, $actionItems, true);
                break;
            default:
                $td->appendElement('plaintext', $tdAttr, $row[$key], true);
                break;
        }
    }
    $serialNo++;
}

include(CONF_THEME_PATH . '_partial/listing/no-record-found.php');

if ($printData) {
    echo $tbody->getHtml();
}
