<?php defined('SYSTEM_INIT') or die('Invalid Usage.'); ?>
<div class="js-scrollable table-wrap table-responsive">
    <?php $arr_flds = [
        'select_all' => '',
        'listserial' => Labels::getLabel('LBL_#', $siteLangId),
        'selprod_title' => Labels::getLabel('LBL_PRODUCT', $siteLangId),
        'abprod_item_group_identifier' => Labels::getLabel('LBL_ITEM_GROUP_IDENTIFIER', $siteLangId),
        'abprod_cat_id' => Labels::getLabel('LBL_CATEGORY', $siteLangId),
        'abprod_age_group' => Labels::getLabel('LBL_AGE_GROUP', $siteLangId),
        'adsbatch_name' => Labels::getLabel('LBL_BATCH', $siteLangId),
        'action' => '',
    ];
    if (1 > count($arrListing)) {
        unset($arr_flds['select_all']);
    }

    if (true === $isPublished) {
        unset($arr_flds['select_all'], $arr_flds['action']);
    }

    $tableClass = '';
    if (0 < count($arrListing)) {
        $tableClass = "table-justified";
    }
    $tbl = new HtmlElement('table', array('width' => '100%', 'class' => 'table ' . $tableClass, 'id' => 'plugin'));
    $th = $tbl->appendElement('thead')->appendElement('tr');
    foreach ($arr_flds as $key => $val) {
        if ('select_all' == $key) {
            $th->appendElement('th')->appendElement('plaintext', array(), '<label class="checkbox"><input title="' . $val . '" type="checkbox" onclick="selectAll( $(this) )" class="selectAll-js"></label>', true);
        } else {
            $e = $th->appendElement('th', array(), $val);
        }
    }

    $sr_no = $page == 1 ? 0 : $pageSize * ($page - 1);
    foreach ($arrListing as $sn => $row) {
        $sr_no++;
        $tr = $tbl->appendElement('tr', array('id' => $row['abprod_adsbatch_id'], 'class' => ''));
        foreach ($arr_flds as $key => $val) {
            $td = $tr->appendElement('td');
            switch ($key) {
                case 'select_all':
                    $td->appendElement('plaintext', array(), '<label class="checkbox"><input class="selectItem--js" type="checkbox" name="selprod_ids[]" value=' . $row['abprod_selprod_id'] . '></label>', true);
                    break;
                case 'listserial':
                    $td->appendElement('plaintext', array(), $sr_no);
                    break;
                case 'selprod_title':
                    $options = SellerProduct::getSellerProductOptions($row['abprod_selprod_id'], true, $siteLangId);
                    $variantsStr = '';
                    array_walk($options, function ($item, $key) use (&$variantsStr) {
                        $variantsStr .= ' | ' . $item['option_name'] . ' : ' . $item['optionvalue_name'];
                    });

                    $td->appendElement('plaintext', [], $row[$key] . $variantsStr, true);
                    break;
                case 'abprod_cat_id':
                    $catName = html_entity_decode($catIdArr[$row[$key]], ENT_QUOTES, 'UTF-8');
                    $td->appendElement('plaintext', [], $catName);
                    break;
                case 'abprod_age_group':
                    $td->appendElement('plaintext', [], ucfirst($row[$key]));
                    break;
                case 'action':
                    $ul = $td->appendElement("ul", array("class" => "actions actions--centered"), '', true);

                    $li = $ul->appendElement('li');
                    $li->appendElement(
                        'a',
                        [
                            'href' => 'javascript:void(0)',
                            'title' => Labels::getLabel('LBL_UNLINK', $siteLangId), "onclick" => "unlinkProduct(" . $row['abprod_adsbatch_id'] . ", " . $row['abprod_selprod_id'] . ")"
                        ],
                        '<svg class="svg" width="18" height="18">
                            <use
                                xlink:href="' . CONF_WEBROOT_URL . 'images/retina/sprite-actions.svg' . AttachedFile::setTimeParam(RELEASE_DATE) . '#delete">
                            </use>
                        </svg>',
                        true
                    );
                    $li = $ul->appendElement('li');
                    $li->appendElement(
                        'a',
                        [
                            'href' => 'javascript:void(0)',
                            'title' => Labels::getLabel('LBL_EDIT', $siteLangId), "onclick" => "bindproductform(" . $row['abprod_adsbatch_id'] . ", " . $row['abprod_selprod_id'] . ")"
                        ],
                        '<svg class="svg" width="18" height="18">
        <use
            xlink:href="' . CONF_WEBROOT_URL . 'images/retina/sprite-actions.svg' . AttachedFile::setTimeParam(RELEASE_DATE) . '#edit">
        </use>
    </svg>',
                        true
                    );
                    break;
                default:
                    $td->appendElement('plaintext', [], $row[$key], true);
                    break;
            }
        }
    }

    $frm = new Form('frmBatchSelprodListing', ['id' => 'frmBatchSelprodListing']);
    $frm->setFormTagAttribute('class', 'form');

    echo $frm->getFormTag();
    echo $tbl->getHtml(); ?>
    </form>
    <?php
    if (count($arrListing) == 0) {
        $message = Labels::getLabel('LBL_RECORD_NOT_FOUND', $siteLangId);
        $this->includeTemplate('_partial/no-record-found.php', ['siteLangId' => $siteLangId, 'message' => $message]);
    } ?>
</div>
<?php $postedData['page'] = $page;
echo FatUtility::createHiddenFormFromData($postedData, array('name' => 'frmSearchPaging'));

$pagingArr = ['pageCount' => $pageCount, 'page' => $page];
$this->includeTemplate('_partial/pagination.php', $pagingArr, false);
