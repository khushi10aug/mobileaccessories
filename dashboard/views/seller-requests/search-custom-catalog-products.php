<div class="js-scrollable table-wrap table-responsive">
    <?php defined('SYSTEM_INIT') or die('Invalid Usage.');
    $arr_flds = array(
        'listserial' => Labels::getLabel('LBL_#', $siteLangId),
        'product_identifier' => Labels::getLabel('LBL_Product', $siteLangId),
        'preq_added_on' => Labels::getLabel('LBL_Added_on', $siteLangId),
        'preq_requested_on' => Labels::getLabel('LBL_Requested_on', $siteLangId),
        'preq_status' => Labels::getLabel('LBL_Status', $siteLangId),
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
                case 'product_identifier':
                    $html = '<div class="product-profile"><figure class="product-profile__pic"><img src="' . UrlHelper::getCachedUrl(UrlHelper::generateFileUrl('image', 'CustomProduct', array($row['preq_id'], $siteLangId, ImageDimension::VIEW_SMALL, 0), CONF_WEBROOT_FRONTEND), CONF_IMG_CACHE_TIME, '.jpg') . '" title="' . $row['product_name'] . '" alt="' . $row['product_name'] . '"></figure>
                                <div class="product-profile__description">
                                    <div class="product-profile__title">' . $row['product_name'] . '</div>
                                    <div class="product-profile__sub_title"> (' . $row[$key] . ') </div>
                            </div></div>';
                    $td->appendElement('plaintext', array(), $html, true);
                    break;
                case 'preq_status':
                    $td->appendElement('span', array('class' => 'badge badge-inline ' . $statusClassArr[$row[$key]]), $statusArr[$row[$key]] . '<br>', true);
                    $td->appendElement('p', array('class' => 'small'), ($row['preq_status_updated_on'] != '0000-00-00 00:00:00') ? FatDate::Format($row['preq_status_updated_on']) : '', true);
                    break;
                case 'preq_added_on':
                    $td->appendElement('plaintext', array(), FatDate::Format($row[$key]), true);
                    break;
                case 'preq_requested_on':
                    $td->appendElement('plaintext', array(), ($row[$key] != '0000-00-00 00:00:00') ? FatDate::Format($row[$key]) : Labels::getLabel('LBL_NA', $siteLangId), true);
                    break;
                case 'action':
                    $ul = $td->appendElement("ul", array('class' => 'actions'), '', true);
                    $li = $ul->appendElement("li");
                    if ($row['preq_status'] == ProductRequest::STATUS_PENDING) {
                        $li->appendElement(
                            'a',
                            array('href' => UrlHelper::generateUrl('CustomProducts', 'form', array($row['preq_id'])), 'class' => '', 'title' => Labels::getLabel('LBL_Edit', $siteLangId)),
                            '<i class="icn">
                            <svg class="svg" width="18" height="18">
                                <use
                                    xlink:href="' . CONF_WEBROOT_URL . 'images/retina/sprite-actions.svg' . AttachedFile::setTimeParam(RELEASE_DATE) . '#edit">
                                </use>
                            </svg>
                        </i>',
                            true
                        );

                        /* $li = $ul->appendElement("li");
                    $li->appendElement("a", array('title' => Labels::getLabel('LBL_Product_Images', $siteLangId), 'onclick' => 'customCatalogProductImages('.$row['preq_id'].')', 'href'=>'javascript:void(0)'), '<i class="fas fa-images"></i>', true); */
                    }
                    $li = $ul->appendElement("li");
                    $li->appendElement(
                        'a',
                        array('href' => 'javascript:void(0)', 'onclick' => 'customCatalogInfo(' . $row['preq_id'] . ')', 'class' => '', 'title' => Labels::getLabel('LBL_product_Info', $siteLangId), true),
                        '<i class="icn">
                        <svg class="svg" width="18" height="18">
                            <use
                                xlink:href="' . CONF_WEBROOT_URL . 'images/retina/sprite-actions.svg' . AttachedFile::setTimeParam(RELEASE_DATE) . '#view">
                            </use>
                        </svg>
                    </i>',
                        true
                    );
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
echo FatUtility::createHiddenFormFromData($postedData, array('name' => 'frmCatalogProductSearchPaging'));
$pagingArr = array('pageCount' => $pageCount, 'page' => $page, 'callBackJsFunc' => 'goToCustomCatalogProductSearchPage');
$this->includeTemplate('_partial/pagination.php', $pagingArr, false);
