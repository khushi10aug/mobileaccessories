<?php
$arr_flds['listserial'] = Labels::getLabel('LBL_#', $siteLangId);
$arr_flds['mainfile'] = Labels::getLabel('LBL_DD_File', $siteLangId);
$arr_flds['preview'] = Labels::getLabel('LBL_DD_Preview', $siteLangId);
//$arr_flds['afile_lang_id'] = Labels::getLabel('LBL_DD_Language', $siteLangId);

if (true === $canDoDigDownload) {
    $arr_flds['action'] = Labels::getLabel('LBL_Action', $siteLangId);
}

$tbl = new HtmlElement('table', array('width' => '100%', 'class' => 'table table-justified'));
$th = $tbl->appendElement('thead')->appendElement('tr', array('class' => 'hide--mobile'));
foreach ($arr_flds as $val) {
    $e = $th->appendElement('th', array(), $val);
}

$sr_no = 0;
foreach ($records as $sn => $row) {
    $sr_no++;
    $tr = $tbl->appendElement('tr');

    foreach ($arr_flds as $key => $val) {
        $td = $tr->appendElement('td');
        switch ($key) {
            case 'listserial':
                $td->appendElement('plaintext', array(), $sr_no, true);
                break;
            case 'mainfile':
                $dvElem = $td->appendElement('div', array('class' => 'actions-downloads'));
                $dvElem->appendElement('div', array('class' => 'file-name'), $row[$key], true);
                if (0 < $row['afile_id']) {
                    if (true === $canDoDigDownload) {
                        $ul = $dvElem->appendElement('ul', array('class' => 'actions'));
                        $li = $ul->appendElement('li');
                        $li->appendElement(
                            'a',
                            array(
                                'class' => '',
                                'title' => Labels::getLabel('LBL_download', $siteLangId),
                                'href' => UrlHelper::generateUrl('Seller', 'downloadAttachment', array($row['afile_id'], $recordId, Product::CATALOG_TYPE_INVENTORY, 0, $row['mainfile'])),
                                'target' => '_blank'
                            ),
                            '<svg class="svg" width="18" height="18">
                                <use
                                    xlink:href="' . CONF_WEBROOT_URL . 'images/retina/sprite-actions.svg' . AttachedFile::setTimeParam(RELEASE_DATE) . '#download">
                                </use>
                            </svg>',
                            true
                        );

                        $li = $ul->appendElement('li');
                        $li->appendElement(
                            "a",
                            array(
                                'class' => '',
                                'title' => Labels::getLabel('LBL_Delete', $siteLangId),
                                'onclick' => 'deleteDigitalFile(' . $row['afile_id'] . ', ' . $row['afile_record_id'] . ')', 'href' => 'javascript:void(0);'
                            ),
                            '<svg class="svg" width="18" height="18">
                                <use xlink:href="' . CONF_WEBROOT_URL . 'images/retina/sprite-actions.svg' . AttachedFile::setTimeParam(RELEASE_DATE) . '#delete">
                                </use>
                            </svg>',
                            true
                        );
                    }
                } else {
                    $td->appendElement('p', array(), Labels::getLabel('LBL_NA', $siteLangId), true);
                }
                break;
            case 'preview':
                $dvElem = $td->appendElement('div', array('class' => 'actions-downloads'));
                if (0 < $row['prev_afile_id']) {
                    $dvElem->appendElement('div', array('class' => 'file-name'), $row[$key], true);
                    $ul = $dvElem->appendElement('ul', array('class' => 'actions'));
                    $li = $ul->appendElement('li');
                    $li->appendElement(
                        "a",
                        array(
                            'class' => '',
                            'title' => Labels::getLabel('LBL_download', $siteLangId),
                            'href' => UrlHelper::generateUrl('Seller', 'downloadAttachment', array($row['prev_afile_id'], $recordId, Product::CATALOG_TYPE_INVENTORY, 1, $row['preview'])),
                            'target' => '_blank'
                        ),
                        '<svg class="svg" width="18" height="18">
                        <use
                            xlink:href="' . CONF_WEBROOT_URL . 'images/retina/sprite-actions.svg' . AttachedFile::setTimeParam(RELEASE_DATE) . '#download">
                        </use>
                    </svg>',
                        true
                    );
                    if (true === $canDoDigDownload) {
                        $li = $ul->appendElement('li');
                        $li->appendElement(
                            "a",
                            array(
                                'class' => '',
                                'title' => Labels::getLabel('LBL_Delete', $siteLangId),
                                'onclick' => 'deleteDigitalFile(' . $row['prev_afile_id'] . ', ' . $row['afile_record_id'] . ', 1)',
                                'href' => 'javascript:void(0);'
                            ),
                            '<svg class="svg" width="18" height="18">
                            <use xlink:href="' . CONF_WEBROOT_URL . 'images/retina/sprite-actions.svg' . AttachedFile::setTimeParam(RELEASE_DATE) . '#delete">
                            </use>
                        </svg>',
                            true
                        );
                    }
                } else {
                    if (true === $canDoDigDownload) {
                        $dvElem->appendElement(
                            "a",
                            array(
                                'class' => 'btn btn-icon btn-outline-gray btn-sm',
                                'title' => Labels::getLabel('LBL_Add', $siteLangId),
                                'href' => 'javascript:void(0);',
                                'onclick' => 'attachDigitalPreviewFile(\'' . $row['pddr_options_code'] . '\', ' . $row['afile_lang_id'] . ', ' . $row['pddr_id'] . ', ' .  $row['afile_id'] . '); return false;',
                                'href' => 'javascript:void(0);'

                            ),
                            '<svg class="svg" width="18" height="18">
                            <use
                                xlink:href="' . CONF_WEBROOT_URL . 'images/retina/sprite-actions.svg' . AttachedFile::setTimeParam(RELEASE_DATE) . '#add">
                            </use>
                        </svg>' . Labels::getLabel('LBL_Add', $siteLangId),
                            true
                        );
                    } else {
                        $td->appendElement('p', [], Labels::getLabel('LBL_NA', $siteLangId), true);
                    }
                }
                break;
            case 'pddr_options_code':
                if (array_key_exists($row['pddr_options_code'], $options)) {
                    $val = $options[$row['pddr_options_code']];
                } else {
                    $val = 'Invalid option link - ' . $row['pddr_options_code'];
                }
                $td->appendElement('plaintext', array(), $val, true);
                break;
            case 'afile_lang_id':
                $lang_name = Labels::getLabel('LBL_All', $siteLangId);
                if ($row['afile_lang_id'] > 0) {
                    $lang_name = $languages[$row['afile_lang_id']];
                }
                $td->appendElement('plaintext', array(), $lang_name, true);
                break;
            case 'action':
                if (1 < $row['afile_id'] || 1 < $row['prev_afile_id']) {
                    $fileId = $row['afile_id'];
                    $isPreview = 0;
                    if (1 > $row['afile_id']) {
                        $fileId = $row['prev_afile_id'];
                        $isPreview = 1;
                    }

                    if (true === $canDoDigDownload) {
                        $ul = $td->appendElement('ul', array('class' => 'actions'));
                        $li = $ul->appendElement('li');
                        $li->appendElement(
                            "a",
                            array(
                                'class' => '',
                                'title' => Labels::getLabel('LBL_Delete', $siteLangId),
                                'onclick' => 'deleteDigitalFile(' . $fileId . ', ' . $row['afile_record_id'] . ', ' . $isPreview . ', 1)', 'href' => 'javascript:void(0);'
                            ),
                            '<svg class="svg" width="18" height="18">
                            <use xlink:href="' . CONF_WEBROOT_URL . 'images/retina/sprite-actions.svg' . AttachedFile::setTimeParam(RELEASE_DATE) . '#delete">
                            </use>
                        </svg>',
                            true
                        );
                    }
                }
                break;
            default:
                $td->appendElement('plaintext', array(), $row[$key], true);
                break;
        }
    }
}
?>
<div class="col-md-12">
    <div class="js-scrollable table-wrap table-responsive">
        <?php
        if (empty($records)) {
            echo $tbl->getHtml();
            $message = Labels::getLabel('LBL_No_Records_Found', $siteLangId);
            $this->includeTemplate('_partial/no-record-found.php', array('siteLangId' => $siteLangId, 'message' => $message));
        } else {
            echo $tbl->getHtml();
        } ?>
    </div>
</div>