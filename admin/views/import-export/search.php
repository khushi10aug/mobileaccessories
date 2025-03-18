<?php defined('SYSTEM_INIT') or die('Invalid Usage.');
$printData = false;
if (!isset($tbody)) {
    $printData = true;
    $tbody = new HtmlElement('tbody', ['class' => 'listingRecordJs']);
}

$serialNo = $page == 1 ? 0 : $pageSize * ($page - 1);
foreach ($arrListing as $sn => $row) {
    $serialNo++;
    $cls = (($serialNo % 2) == 0) ? 'even' : 'odd';
    $tr = $tbody->appendElement('tr', ['class' => $cls, 'data-row' => $serialNo]);

    foreach ($fields as $key => $val) {
        $tdAttr = ('action' == $key) ? ['class' => 'align-right'] : [];
        $td = $tr->appendElement('td', $tdAttr);
        switch ($key) {
            case 'listSerial':
                $td->appendElement('plaintext', $tdAttr, $serialNo);
                break;
            case 'user':
                if (!empty($row['credential_username'])) {
                    $td->appendElement('a', array('href' => 'javascript:void(0)', 'onclick' => 'redirectfunc("' . UrlHelper::generateUrl('Users') . '",' . $row['afile_record_id'] . ')'), $row['credential_username'] . '( ' . $row['credential_email'] . ' )');
                } else {
                    $td->appendElement('plaintext', $tdAttr, 'Admin', true);
                }
                break;
            case 'afile_physical_path':
                $path = AttachedFile::FILETYPE_BULK_IMAGES_PATH . $row['afile_physical_path'];
                $td->appendElement('plaintext', $tdAttr, $path, true);
                break;
            case 'files':
                $fullPath = CONF_UPLOADS_PATH . AttachedFile::FILETYPE_BULK_IMAGES_PATH . $row['afile_physical_path'];
                $count = Labels::getLabel('LBL_N/A', $siteLangId);
                if (file_exists($fullPath)) {
                    $allFiles = scandir($fullPath);
                    $files_count = array_diff($allFiles, array('..', '.'));
                    $count = count($files_count);
                }
                $td->appendElement('plaintext', $tdAttr, $count, true);
                break;
            case 'action':
                $data = [
                    'siteLangId' => $siteLangId
                ];

                if ($canEdit) {
                    $data['otherButtons'] = [
                        [
                            'attr' => [
                                'href' => 'javascript:void(0)',
                                'onclick' => "downloadPathsFile('" . base64_encode($fullPath) . "')",
                                'title' => Labels::getLabel('LBL_DOWNLOAD', $siteLangId)
                            ],
                            'label' => '<svg class="svg" width="18" height="18">
                                                <use
                                                    xlink:href="' . CONF_WEBROOT_URL . 'images/retina/sprite.yokart.svg' . AttachedFile::setTimeParam(RELEASE_DATE) . '#icon-download">
                                                </use>
                                            </svg>'
                        ],
                        [
                            'attr' => [
                                'href' => 'javascript:void(0)',
                                'onclick' => "removeDir('" . base64_encode(AttachedFile::FILETYPE_BULK_IMAGES_PATH . $row['afile_physical_path']) . "')",
                                'title' => Labels::getLabel('LBL_DELETE', $siteLangId)
                            ],
                            'label' => '<svg class="svg" width="18" height="18">
                                                <use
                                                    xlink:href="' . CONF_WEBROOT_URL . 'images/retina/sprite-actions.svg' . AttachedFile::setTimeParam(RELEASE_DATE) . '#delete">
                                                </use>
                                            </svg>'
                        ]
                    ];
                }
                $actionItems = $this->includeTemplate('_partial/listing/listing-action-buttons.php', $data, false, true);
                $td->appendElement('plaintext', $tdAttr, $actionItems, true);
                break;
            default:
                $td->appendElement('plaintext', $tdAttr, $row[$key], true);
                break;
        }
    }
}

include (CONF_THEME_PATH . '_partial/listing/no-record-found.php');

if ($printData) {
    echo $tbody->getHtml();
}
