<div class="js-scrollable table-wrap table-responsive">
    <?php defined('SYSTEM_INIT') or die('Invalid Usage.');
    $arr_flds = array(
        'listserial' => '#',
        'brand_name' => Labels::getLabel('LBL_BRAND_NAME', $siteLangId),
        'brand_requested_on' => Labels::getLabel('LBL_REQUESTED_ON', $siteLangId),
        'brand_updated_on' => Labels::getLabel('LBL_UPDATED_ON', $siteLangId),
        'brand_status' => Labels::getLabel('LBL_STATUS', $siteLangId),
    );
    if ($canEdit) {
        $arr_flds['action'] = '';
    }
    $tableClass = '';
    if (0 < count($arrListing)) {
        $tableClass = "table-justified";
    }
    $tbl = new HtmlElement('table', array('width' => '100%', 'class' => 'table ' . $tableClass));
    $th = $tbl->appendElement('thead')->appendElement('tr', array('class' => ''));
    foreach ($arr_flds as $val) {
        $e = $th->appendElement('th', array(), $val);
    }

    $sr_no = ($page > 1) ? $recordCount - (($page - 1) * $pageSize) : $recordCount;
    foreach ($arrListing as $sn => $row) {
        $tr = $tbl->appendElement('tr', array('class' => ''));

        foreach ($arr_flds as $key => $val) {
            $td = $tr->appendElement('td');
            switch ($key) {
                case 'listserial':
                    $td->appendElement('plaintext', array(), $sr_no, true);
                    break;
                case 'brand_name':
                    $brandLogo = AttachedFile::getAttachment(AttachedFile::FILETYPE_BRAND_LOGO, $row['brand_id'], 0, $siteLangId, (count($languages) > 1) ? false : true);
                    $aspectRatioType = $brandLogo['afile_aspect_ratio'];
                    $aspectRatioType = ($aspectRatioType > 0) ? $aspectRatioType : 1;
                    $imageBrandDimensions = ImageDimension::getData(ImageDimension::TYPE_BRAND_LOGO, ImageDimension::VIEW_MINI_THUMB, $aspectRatioType);
                    $uploadedTime = AttachedFile::setTimeParam($row['brand_updated_on']);
                    $brandImage = '<img data-aspect-ratio = "' . $imageBrandDimensions[ImageDimension::VIEW_MINI_THUMB]['aspectRatio'] . '" width="' . $imageBrandDimensions['width'] . '" height="' . $imageBrandDimensions['height'] . '" title="' . $row['brand_name'] . '" alt="' . $row['brand_name'] . '" src="' . UrlHelper::getCachedUrl(UrlHelper::generateFileUrl('image', 'brand', array($row['brand_id'], $siteLangId, ImageDimension::VIEW_MINI_THUMB), CONF_WEBROOT_FRONT_URL) . $uploadedTime, CONF_IMG_CACHE_TIME, '.jpg') . '">';

                    $html = '<div class="product-profile">
                                <figure class="product-profile__pic">
                                    ' . $brandImage . '
                                </figure>
                                <div class="product-profile__description">
                                    <div class="product-profile__title">' . $row['brand_name'] . '</div>
                                    <div class="product-profile__sub_title"> (' . $row['brand_identifier'] . ') </div>
                                </div>
                            </div>';
                    $td->appendElement('plaintext', array(), $html, true);
                    break;
                case 'brand_status':
                    $td->appendElement('span', array('class' => 'badge badge-inline ' . $statusClassArr[$row[$key]]), $statusArr[$row[$key]] . '<br>', true);
                    $td->appendElement('small', array('class' => 'ml-1'), (isset($row['brand_status_updated_on']) && $row['brand_status_updated_on'] != '0000-00-00 00:00:00') ? FatDate::Format($row['brand_status_updated_on']) : '', true);
                    break;
                case 'brand_requested_on':
                    $td->appendElement('plaintext', array(), (isset($row[$key]) && $row[$key] != '0000-00-00 00:00:00') ? FatDate::Format($row[$key]) : Labels::getLabel('LBL_NA', $siteLangId), true);
                    break;
                case 'brand_updated_on':
                    $td->appendElement('plaintext', array(), (isset($row[$key]) && $row[$key] != '0000-00-00 00:00:00') ? FatDate::Format($row[$key], true) : Labels::getLabel('LBL_NA', $siteLangId), true);
                    break;
                case 'action':
                    $ul = $td->appendElement("ul", array('class' => 'actions'), '', true);
                    $li = $ul->appendElement("li");
                    if ($row['brand_status'] == Brand::BRAND_REQUEST_PENDING) {
                        $li->appendElement(
                            'a',
                            array('href' => 'javascript:void(0)', 'onclick' => "addBrandReqForm(" . $row['brand_id'] . ")", 'class' => '', 'title' => Labels::getLabel('LBL_Edit', $siteLangId)),
                            '<i class="icn">
                            <svg class="svg" width="18" height="18">
                                <use
                                    xlink:href="' . CONF_WEBROOT_URL . 'images/retina/sprite-actions.svg' . AttachedFile::setTimeParam(RELEASE_DATE) . '#edit">
                                </use>
                            </svg>
                        </i>',
                            true
                        );
                    }
                    break;
                default:
                    $td->appendElement('plaintext', array(), $row[$key], true);
                    break;
            }
        }
        $sr_no--;
    }

    echo $tbl->getHtml();
    if (count($arrListing) == 0) {
        $message = Labels::getLabel('LBL_No_Records_Found', $siteLangId);
        $this->includeTemplate('_partial/no-record-found.php', array('siteLangId' => $siteLangId, 'message' => $message));
    } ?>
</div>
<?php $postedData['page'] = $page;
echo FatUtility::createHiddenFormFromData($postedData, array('name' => 'frmSearchBrandRequest'));
$pagingArr = array('pageCount' => $pageCount, 'page' => $page, 'callBackJsFunc' => 'goToBrandSearchPage');
$this->includeTemplate('_partial/pagination.php', $pagingArr, false);
