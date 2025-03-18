<?php
if (1 > count($attachments)) {
    return;
}
$arr_flds = array(
    'mainfile' => Labels::getLabel('LBL_DD_FILE', $siteLangId),
    'preview' => Labels::getLabel('LBL_DD_PREVIEW', $siteLangId),
);

if (0 == $product['product_seller_id']) {
    $arr_flds['action'] = Labels::getLabel('LBL_ACTION_BUTTONS', $siteLangId);
}

$tbl = new HtmlElement('table', array('width' => '100%', 'class' => 'table table-justified'));
$th = $tbl->appendElement('thead')->appendElement('tr', array('class' => 'hide--mobile'));
foreach ($arr_flds as $key => $val) {
    $tdAttr = ('action' == $key) ? ['class' => 'align-right', 'width' => '20%'] : ['width' => '40%'];
    $e = $th->appendElement('th', $tdAttr, $val);
}

$serialNo = 0;
foreach ($attachments as $sn => $row) {
    $serialNo++;
    $tr = $tbl->appendElement('tr');
    foreach ($arr_flds as $key => $val) {
        $tdAttr = ('action' == $key) ? ['class' => 'align-right'] : [];
        $td = $tr->appendElement('td', $tdAttr);
        switch ($key) {
            case 'mainfile':
                $dvElem = $td->appendElement('div', array('class' => 'actions-downloads'));
                $dvElem->appendElement('div', array('class' => 'file-name'), $row[$key], true);
                if (0 < $row['afile_id']) {
                    if (0 == $product['product_seller_id']) {
                        $ul = new HtmlElement("ul", array("class" => "actions"));
                        $li = $ul->appendElement('li', ['data-bs-toggle' => 'tooltip', 'data-placement' => 'top']);
                        $li->appendElement(
                            "a",
                            array(
                                'title' => Labels::getLabel('LBL_DOWNLOAD', $siteLangId),
                                'href' => UrlHelper::generateUrl('Products', 'downloadAttachment', array($row['afile_id'], $recordId, $downloadrefType, 0, $row['mainfile'])),
                                'target' => '_blank'
                            ),
                            '<svg class="svg" width="18" height="18">
                                <use
                                    xlink:href="' . CONF_WEBROOT_URL . 'images/retina/sprite-actions.svg' . AttachedFile::setTimeParam(RELEASE_DATE) . '#download">
                                </use>
                            </svg>',
                            true
                        );
                        $li = $ul->appendElement('li', ['data-bs-toggle' => 'tooltip', 'data-placement' => 'top']);
                        $li->appendElement(
                            "a",
                            array(
                                'title' => Labels::getLabel('LBL_DELETE', $siteLangId),
                                'onclick' => 'deleteDigitalFile(' . $row['afile_id'] . ', ' . $row['afile_record_id'] . ')', 'href' => 'javascript:void(0);'
                            ),
                            '<svg class="svg" width="18" height="18">
                                <use
                                    xlink:href="' . CONF_WEBROOT_URL . 'images/retina/sprite-actions.svg' . AttachedFile::setTimeParam(RELEASE_DATE) . '#delete">
                                </use>
                            </svg>',
                            true
                        );
                    }
                    $dvElem->appendElement('plaintext', $tdAttr, $ul->getHtml(), true);
                } else {
                    $dvElem->appendElement('p', array(), Labels::getLabel('LBL_NA', $siteLangId), true);
                }
                break;
            case 'preview':
                $dvElem = $td->appendElement('div', array('class' => 'actions-downloads'));
                $ul = new HtmlElement("ul", array("class" => "actions"));
                if (0 < $row['prev_afile_id']) {
                    $dvElem->appendElement('div', array('class' => 'file-name'), $row[$key], true);
                    $li = $ul->appendElement('li', ['data-bs-toggle' => 'tooltip', 'data-placement' => 'top']);
                    $li->appendElement(
                        'a',
                        array(
                            'href' => UrlHelper::generateUrl('Products', 'downloadAttachment', array($row['prev_afile_id'], $recordId, $downloadrefType, 1, $row['preview'])),
                            'title' => Labels::getLabel('LBL_DOWNLOAD', $siteLangId),
                        ),
                        '<svg class="svg" width="18" height="18">
                            <use
                                xlink:href="' . CONF_WEBROOT_URL . 'images/retina/sprite-actions.svg' . AttachedFile::setTimeParam(RELEASE_DATE) . '#download">
                            </use>
                        </svg>',
                        true
                    );
                    if (0 == $product['product_seller_id']) {
                        $li = $ul->appendElement('li', ['data-bs-toggle' => 'tooltip', 'data-placement' => 'top']);
                        $li->appendElement(
                            'a',
                            array(
                                'href' => 'javascript:void(0)',
                                'title' => Labels::getLabel('LBL_DELETE', $siteLangId),
                                'onclick' => 'deleteDigitalFile(' . $row['prev_afile_id'] . ', ' . $row['afile_record_id'] . ', 1)'
                            ),
                            '<svg class="svg" width="18" height="18">
                            <use
                                xlink:href="' . CONF_WEBROOT_URL . 'images/retina/sprite-actions.svg' . AttachedFile::setTimeParam(RELEASE_DATE) . '#delete">
                            </use>
                            </svg>',
                            true
                        );
                    }
                } else {
                    $dvElem->appendElement('div', array('class' => 'file-name'),  Labels::getLabel('LBL_NA', $siteLangId), true);
                    $li = $ul->appendElement('li', ['data-bs-toggle' => 'tooltip', 'data-placement' => 'top']);
                    $li->appendElement(
                        "a",
                        array(
                            'title' => Labels::getLabel('LBL_ADD', $siteLangId),
                            'href' => 'javascript:void(0);',
                            'onclick' => 'attachDigitalPreviewFile(\'' . $row['pddr_options_code'] . '\', ' . $row['afile_lang_id'] . ', ' . $row['pddr_id'] . ', ' .  $row['afile_id'] . '); return false;',
                            'href' => 'javascript:void(0);'
                        ),
                        '<svg class="svg" width="18" height="18">
                            <use
                                xlink:href="' . CONF_WEBROOT_URL . 'images/retina/sprite-actions.svg' . AttachedFile::setTimeParam(RELEASE_DATE) . '#add">
                            </use>
                        </svg>',
                        true
                    );
                }
                $dvElem->appendElement('plaintext', $tdAttr, $ul->getHtml(), true);
                break;
            case 'action':
                if ((1 < $row['afile_id'] || 1 < $row['prev_afile_id']) && 0 == $product['product_seller_id']) {
                    $fileId = $row['afile_id'];
                    $isPreview = 0;
                    if (1 > $row['afile_id']) {
                        $fileId = $row['prev_afile_id'];
                        $isPreview = 1;
                    }

                    $data = [
                        'siteLangId' => $siteLangId,
                        'recordId' => $row['afile_id']
                    ];

                    $data['deleteButton'] = [
                        'onclick' => 'deleteDigitalFile(' . $fileId . ', ' . $row['afile_record_id'] . ', ' . $isPreview . ', 1)'
                    ];
                    $actionItems = $this->includeTemplate('_partial/listing/listing-action-buttons.php', $data, false, true);
                    $td->appendElement('plaintext', $tdAttr, $actionItems, true);
                }
                break;
            default:
                $td->appendElement('plaintext', array(), $row[$key], true);
                break;
        }
    }
}

if (empty($attachments)) {
    $tr = $tbl->appendElement('tr')->appendElement('td', ['colspan' => count($arr_flds)]);
    $tr->appendElement('plaintext', array(), Labels::getLabel('LBL_NO_RECORDS', $siteLangId), true);
}
?>
<div class="col-md-12">
    <div class="js-scrollable table-wrap table-responsive">
        <?php echo $tbl->getHtml(); ?>
    </div>
</div>