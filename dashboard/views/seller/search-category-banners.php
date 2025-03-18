<?php defined('SYSTEM_INIT') or die('Invalid Usage.'); ?>
<div class="js-scrollable table-wrap table-responsive">
    <?php $arr_flds = array(
        'listserial' => 'Sr.',
        'prodcat_name' => Labels::getLabel('LBL_Category', $siteLangId),
        'banner' => Labels::getLabel('LBL_Banner', $siteLangId),
        'action' => ''
    );
    $tableClass = '';
    if (0 < count($arrListing)) {
        $tableClass = "table-justified";
    }
    $tbl = new HtmlElement('table', array('width' => '100%', 'class' => 'table ' . $tableClass));
    $th = $tbl->appendElement('thead')->appendElement('tr', array('class' => ''));
    foreach ($arr_flds as $val) {
        $e = $th->appendElement('th', array(), $val);
    }

    $sr_no = ($page == 1) ? 0 : ($pageSize * ($page - 1));
    foreach ($arrListing as $sn => $row) {
        $sr_no++;
        $tr = $tbl->appendElement('tr', array('class' => ''));

        foreach ($arr_flds as $key => $val) {
            $td = $tr->appendElement('td');
            switch ($key) {
                case 'listserial':
                    $td->appendElement('plaintext', array(), $sr_no, true);
                    break;
                case 'banner':
                    $td->appendElement('plaintext', array(), '<img src="' . UrlHelper::generateUrl('category', 'sellerBanner', array($row['shop_id'], $row['prodcat_id'], $siteLangId, ImageDimension::VIEW_THUMB), CONF_WEBROOT_FRONTEND) . '">', true);
                    break;
                case 'action':
                    $ul = $td->appendElement("ul", array('class' => 'actions'), '', true);
                    $li = $ul->appendElement("li");
                    $li->appendElement(
                        'a',
                        array('href' => 'javascript:void(0)', 'onclick' => 'addCategoryBanner(' . $row['prodcat_id'] . ')', 'class' => '', 'title' => Labels::getLabel('LBL_Media', $siteLangId)),
                        '<svg class="svg" width="18" height="18">
        <use
            xlink:href="' . CONF_WEBROOT_URL . 'images/retina/sprite-actions.svg' . AttachedFile::setTimeParam(RELEASE_DATE) . '#edit">
        </use>
    </svg>',
                        true
                    );
                    break;
                default:
                    $td->appendElement('plaintext', array(), $row[$key], true);
                    break;
            }
        }
    }
    ?>
    <?php
    $variables = array('language' => $language, 'siteLangId' => $siteLangId, 'shop_id' => $shop_id, 'action' => $action);
    $this->includeTemplate('seller/_partial/shop-navigation.php', $variables, false);
    ?>
    <?php
    echo $tbl->getHtml();
    if (count($arrListing) == 0) {
        $message = Labels::getLabel('LBL_No_Records_Found', $siteLangId);
        $this->includeTemplate('_partial/no-record-found.php', array('siteLangId' => $siteLangId, 'message' => $message));
    } ?>
</div>
<?php $postedData['page'] = $page;
echo FatUtility::createHiddenFormFromData($postedData, array('name' => 'frmCategoryBannerSrchPaging'));
$pagingArr = array('pageCount' => $pageCount, 'page' => $page, 'callBackJsFunc' => 'goToCategoryBannerSrchPage');
$this->includeTemplate('_partial/pagination.php', $pagingArr, false);
