<?php
if (!$showNoRecordFound && empty($attachments)) {
    return;
}
$arr_flds = array(
    'listSerial' => Labels::getLabel('LBL_#', $siteLangId),
    'mainfile' => Labels::getLabel('LBL_DD_FILE', $siteLangId),
    'preview' => Labels::getLabel('LBL_DD_PREVIEW', $siteLangId),
    'action' => Labels::getLabel('LBL_ACTION_BUTTONS', $siteLangId)
);

if ($canDo) {
    unset($arr_flds['listSerial']);
} else {
    unset($arr_flds['action']);
}

$tbl = new HtmlElement('table', array('width' => '100%', 'class' => 'table ' . (isset($arr_flds['action']) ? 'table-justified' : '')));
$th = $tbl->appendElement('thead')->appendElement('tr', array('class' => 'hide--mobile'));
foreach ($arr_flds as $key => $val) {
    $tdAttr = ('action' == $key || 'listSerial' == $key) ? ['width' => '20%'] : ['width' => '40%'];
    if ('action' == $key) {
        $tdAttr['class'] = 'align-right';
    }
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
            case 'listSerial':
                $td->appendElement('plaintext', array(), $serialNo, true);
                break;
            case 'mainfile':
                $dvElem = $td->appendElement('div', array('class' => 'actions-downloads'));

                if (0 < $row['afile_id']) {
                    $dvElem->appendElement('div', array('class' => 'file-name'), $row[$key], true);
                    if ($canDo) {
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
                                'onclick' => 'deleteDigitalFile(' . $row['afile_id'] . ', ' . $row['afile_record_id'] . ')',
                                'href' => 'javascript:void(0);'
                            ),
                            '<svg class="svg" width="18" height="18">
                                <use
                                    xlink:href="' . CONF_WEBROOT_URL . 'images/retina/sprite-actions.svg' . AttachedFile::setTimeParam(RELEASE_DATE) . '#delete">
                                </use>
                            </svg>',
                            true
                        );

                        $dvElem->appendElement('plaintext', $tdAttr, $ul->getHtml(), true);
                    }
                } else {
                    $dvElem->appendElement('p', array(), Labels::getLabel('LBL_NA', $siteLangId), true);
                }
                break;
            case 'preview':
                $dvElem = $td->appendElement('div', array('class' => 'actions-downloads'));
                $ul = new HtmlElement("ul", array("class" => "actions"));
                if (0 < $row['prev_afile_id']) {
                    $dvElem->appendElement('div', array('class' => 'text-breakxx'), $row[$key], true);

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
                    if ($canDo) {
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
                    $dvElem->appendElement('div', array('class' => 'text-breakxx'), Labels::getLabel('LBL_NA', $siteLangId), true);
                    if ($canDo) {
                        $li = $ul->appendElement('li', ['data-bs-toggle' => 'tooltip', 'data-placement' => 'top']);
                        $li->appendElement(
                            "a",
                            array(
                                'title' => Labels::getLabel('LBL_ADD', $siteLangId),
                                'href' => 'javascript:void(0);',
                                'onclick' => 'attachDigitalPreviewFile(\'' . $row['pddr_options_code'] . '\', ' . $row['afile_lang_id'] . ', ' . $row['pddr_id'] . ', ' . $row['afile_id'] . '); return false;',
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
                }
                $dvElem->appendElement('plaintext', $tdAttr, $ul->getHtml(), true);
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
                $lang_name = Labels::getLabel('LBL_ALL', $siteLangId);
                if ($row['afile_lang_id'] > 0) {
                    $lang_name = $languages[$row['afile_lang_id']];
                }
                $td->appendElement('plaintext', array(), $lang_name, true);
                break;
            case 'action':
                if ((1 < $row['afile_id'] || 1 < $row['prev_afile_id'])) {
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

?>
<div class="">
    <?php
    if (empty($attachments)) {
        $message = Labels::getLabel('LBL_No_Records_Found', $siteLangId);
        $this->includeTemplate('_partial/no-record-found.php', array('siteLangId' => $siteLangId, 'message' => $message));
    } else {
        ?>
        <div class="js-scrollable table-wrap table-responsive">
            <?php
            echo $tbl->getHtml();
            ?>
        </div>
        <?php
    }
    ?>
</div>