<?php defined('SYSTEM_INIT') or die('Invalid Usage.'); ?>
<div class="js-scrollable table-wrap table-responsive">
    <?php $arr_flds = [
        'listserial' => Labels::getLabel('LBL_#', $siteLangId),
        'adsbatch_name' => Labels::getLabel('LBL_BATCH_NAME', $siteLangId),
        'adsbatch_lang_id' => Labels::getLabel('LBL_CONTENT_LANG', $siteLangId),
        'adsbatch_target_country_id' => Labels::getLabel('LBL_TARGET_COUNTRY', $siteLangId),
        'adsbatch_expired_on' => Labels::getLabel('LBL_EXPIRY_DATE', $siteLangId),
        'adsbatch_synced_on' => Labels::getLabel('LBL_LAST_SYNCED', $siteLangId),
        'adsbatch_status' => Labels::getLabel('LBL_STATUS', $siteLangId),
        'action' => '',
    ];
    if (!$canEdit) {
        unset($arr_flds['action']);
    }
    if (1 > count($arrListing)) {
        unset($arr_flds['select_all']);
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
        $tr = $tbl->appendElement('tr', array('id' => $row['adsbatch_id'], 'class' => ''));
        foreach ($arr_flds as $key => $val) {
            $td = $tr->appendElement('td');
            switch ($key) {
                case 'listserial':
                    $td->appendElement('plaintext', array(), $sr_no);
                    break;
                case 'adsbatch_name':
                    $td->appendElement('plaintext', array(), $row[$key], true);
                    break;
                case 'adsbatch_lang_id':
                    $languages = Language::getAllNames();
                    $td->appendElement('plaintext', array(), $languages[$row[$key]], true);
                    break;
                case 'adsbatch_target_country_id':
                    $countryObj = new Countries();
                    $countriesArr = $countryObj->getCountriesAssocArr($siteLangId);
                    $td->appendElement('plaintext', array(), $countriesArr[$row[$key]], true);
                    break;
                case 'adsbatch_expired_on':
                    $timestamp = strtotime($row[$key]);
                    $date = 0 < $timestamp ? date('Y-m-d', strtotime($row[$key])) : Labels::getLabel('LBL_N/A', $siteLangId);
                    $td->appendElement('plaintext', array(), $date, true);
                    break;
                case 'adsbatch_synced_on':
                    $timestamp = strtotime($row[$key]);
                    $date = 0 < $timestamp ? date('Y-m-d H:i:s', strtotime($row[$key])) : Labels::getLabel('LBL_IN_QUEUE', $siteLangId);
                    $td->appendElement('plaintext', array(), $date, true);
                    break;
                case 'adsbatch_status':
                    $statusArr = AdsBatch::statusArr();
                    $title = Labels::getLabel('LBL_PRODUCTS_ARE_PENDING_TO_BE_PUBLISHED.', $siteLangId);
                    switch ($row[$key]) {
                        case AdsBatch::STATUS_PENDING:
                            $class = 'badge-info';
                            break;
                        case AdsBatch::STATUS_PARTIALLY_PENDING:
                            $title = Labels::getLabel('LBL_SOME_PRODUCTS_ARE_PENDING_TO_BE_PUBLISHED.', $siteLangId);
                            $class = 'badge-info';
                            break;
                        case AdsBatch::STATUS_PUBLISHED:
                            $title = Labels::getLabel('LBL_ALL_PRODUCTS_HAVE_BEEN_PUBLISHED.', $siteLangId);
                            $class = 'badge-success';
                            break;
                        default:
                            $class = 'badge-dark';
                            break;
                    }
                    $htm = '<span class="badge ' . $class . '" title="' . $title . '" data-bs-toggle="tooltip">'  . $statusArr[$row[$key]] . '</span>';
                    $td->appendElement('plaintext', array(), $htm, true);
                    break;
                case 'action':
                    $data = [
                        'siteLangId' => $siteLangId,
                        'recordId' => $row['adsbatch_id']
                    ];

                    if ($canEdit) {
                        $data['editButton'] = [
                            'onclick' => "batchForm(" . $row['adsbatch_id'] . ", " . $row['adsbatch_lang_id'] . ")"
                        ];

                        if (!empty($merchantId)) {
                            $isPublished = (AdsBatch::STATUS_PUBLISHED == $row['adsbatch_status']);
                            $title = ($isPublished) ? Labels::getLabel('LBL_RE_-_PUBLISH', $siteLangId) : Labels::getLabel('LBL_PUBLISH', $siteLangId);
                            $data['otherButtons'][] = [
                                'attr' => [
                                    'href' => 'javascript:void(0)',
                                    'onclick' => "publishBatch(" . $row['adsbatch_id'] . ")",
                                    'title' => $title,
                                ],
                                'label' => '<svg class="svg" width="18" height="18">
                                                <use
                                                    xlink:href="' . CONF_WEBROOT_URL . 'images/retina/sprite-actions.svg' . AttachedFile::setTimeParam(RELEASE_DATE) . '#publish-rss">
                                                </use>
                                            </svg>'
                            ];
                        }

                        $data['otherButtons'][] = [
                            'attr' => [
                                'href' => UrlHelper::generateUrl($keyName, 'bindProducts', [$row['adsbatch_id']]),
                                'title' => Labels::getLabel('LBL_BIND_PRODUCTS', $siteLangId),
                            ],
                            'label' => '<svg class="svg" width="18" height="18">
                                            <use
                                                xlink:href="' . CONF_WEBROOT_URL . 'images/retina/sprite-actions.svg' . AttachedFile::setTimeParam(RELEASE_DATE) . '#link">
                                            </use>
                                        </svg>'
                        ];
                    }

                    if ($canView) {
                        $title = Labels::getLabel('LBL_DOWNLOAD_XML_FILE', $siteLangId);
                        $data['otherButtons'][] = [
                            'attr' => [
                                'href' => 'javascript:void(0)',
                                'onclick' => "publishBatch(" . $row['adsbatch_id'] . ", 1)",
                                'title' => $title,
                            ],
                            'label' => '<svg class="svg" width="18" height="18">
                                            <use
                                                xlink:href="' . CONF_WEBROOT_URL . 'images/retina/sprite-actions.svg' . AttachedFile::setTimeParam(RELEASE_DATE) . '#icon-download">
                                            </use>
                                        </svg>'
                        ];
                    }

                    if ($canEdit && AdsBatch::STATUS_PENDING == $row['adsbatch_status']) {
                        $data['deleteButton'][] = [
                            'onclick' => "deleteBatch(" . $row['adsbatch_id'] . ")"
                        ];
                    }

                    if (AdsBatch::STATUS_PUBLISHED == $row['adsbatch_status'] || AdsBatch::STATUS_PARTIALLY_PENDING == $row['adsbatch_status']) {
                        $title = Labels::getLabel('LBL_VIEW', $siteLangId);
                        $data['otherButtons'][] = [
                            'attr' => [
                                'href' => UrlHelper::generateUrl($keyName, 'viewProducts', [$row['adsbatch_id']]),
                                'title' => $title,
                            ],
                            'label' => '<svg class="svg" width="18" height="18">
                                            <use
                                                xlink:href="' . CONF_WEBROOT_URL . 'images/retina/sprite-actions.svg' . AttachedFile::setTimeParam(RELEASE_DATE) . '#view">
                                            </use>
                                        </svg>'
                        ];
                    }
                    $actionItems = $this->includeTemplate('_partial/listing/listing-action-buttons.php', $data, false, true);
                    $td->appendElement('plaintext', [], $actionItems, true);
                    break;
            }
        }
    }
    echo $tbl->getHtml();
    if (count($arrListing) == 0) {
        $message = Labels::getLabel('LBL_RECORD_NOT_FOUND', $siteLangId);
        $this->includeTemplate('_partial/no-record-found.php', ['siteLangId' => $siteLangId, 'message' => $message]);
    } ?>
</div>
<?php $postedData['page'] = $page;
echo FatUtility::createHiddenFormFromData($postedData, array('name' => 'frmSearchPaging'));
$pagingArr = ['pageCount' => $pageCount, 'page' => $page];
$this->includeTemplate('_partial/pagination.php', $pagingArr, false);
