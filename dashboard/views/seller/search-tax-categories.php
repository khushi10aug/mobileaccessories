<?php defined('SYSTEM_INIT') or die('Invalid Usage.'); ?>
<div class="js-scrollable table-wrap table-responsive">
    <?php
    $arr_flds = array(
        'listserial' => 'Sr.',
        'taxcat_name' => Labels::getLabel('LBL_Tax_Category', $siteLangId)
    );
    $tableClass = '';
    if ($activatedTaxServiceId) {
        $arr_flds['taxcat_code'] = Labels::getLabel('LBL_Tax_Code', $siteLangId);
    } else {
        if (0 < count($arrListing)) {
            $tableClass = "table-justified";
        }
        $arr_flds['tax_rates'] = Labels::getLabel('LBL_Tax_Rates', $siteLangId);
    }

    $tbl = new HtmlElement('table', array('width' => '100%', 'class' => 'table ' . $tableClass));
    $th = $tbl->appendElement('thead')->appendElement('tr', array('class' => ''));
    foreach ($arr_flds as $val) {
        $e = $th->appendElement('th', array(), $val);
    }

    $defaultStringLength = applicationConstants::DEFAULT_STRING_LENGTH;
    $sr_no = ($page == 1) ? 0 : ($pageSize * ($page - 1));
    foreach ($arrListing as $sn => $row) {
        $sr_no++;
        $tr = $tbl->appendElement('tr', array('class' => ''));

        foreach ($arr_flds as $key => $val) {
            $attr = ('taxcat_name' == $key ? ['title' => $row[$key]] : []);
            $td = $tr->appendElement('td', $attr);
            switch ($key) {
                case 'listserial':
                    $td->appendElement('plaintext', array(), $sr_no, true);
                    break;
                case 'taxcat_name':
                    $taxCatName = substr($row[$key], 0, $defaultStringLength);
                    if ($defaultStringLength < strlen((string)$row[$key])) {
                        $taxCatName .= '...';
                    }
                    $td->appendElement('plaintext', array(), $taxCatName . '<br>', true);
                    break;
                case 'tax_rates':
                    $ul = $td->appendElement("ul", array("class" => "actions"), '', true);
                    $li = $ul->appendElement("li");
                    $li->appendElement(
                        'a',
                        array('href' => UrlHelper::generateUrl('Seller', 'taxRules', array($row['taxcat_id'])), 'class' => '', 'title' => Labels::getLabel('LBL_View_Tax_Rates', $siteLangId)),
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
    }

    echo $tbl->getHtml();
    if (count($arrListing) == 0) {
        $message = Labels::getLabel('LBL_No_Record_found', $siteLangId);
        $this->includeTemplate('_partial/no-record-found.php', array('siteLangId' => $siteLangId, 'message' => $message));
    } ?>
</div>
<?php $postedData['page'] = $page;
echo FatUtility::createHiddenFormFromData($postedData, array('name' => 'frmSearchTaxCatPaging'));
$pagingArr = array('pageCount' => $pageCount, 'page' => $page, 'callBackJsFunc' => 'goToSearchPage');
$this->includeTemplate('_partial/pagination.php', $pagingArr, false);
