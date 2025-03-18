<?php defined('SYSTEM_INIT') or die('Invalid Usage.');
$arr_flds = array(
    'listserial' => Labels::getLabel('LBL_#', $siteLangId),
    Badge::DB_TBL_PREFIX . 'shape_type' => Labels::getLabel('LBL_IMAGE', $siteLangId),
    Badge::DB_TBL_PREFIX . 'name' => (Badge::TYPE_RIBBON == $badgeType) ? Labels::getLabel('LBL_RIBBON_NAME', $siteLangId) : Labels::getLabel('LBL_BADGE_NAME', $siteLangId),
    Badge::DB_TBL_PREFIX . 'trigger_type' => Labels::getLabel('LBL_CONDITION_TYPE', $siteLangId),
    Badge::DB_TBL_PREFIX . 'required_approval' => Labels::getLabel('LBL_APPROVAL', $siteLangId),
    'action' => Labels::getLabel('LBL_ACTION_BUTTONS', $siteLangId),
);

if (!$canEdit) {
    unset($arr_flds['action']);
}

if (Badge::TYPE_RIBBON == $badgeType) {
    unset($arr_flds[Badge::DB_TBL_PREFIX . 'required_approval'], $arr_flds[Badge::DB_TBL_PREFIX . 'trigger_type']);
}

$conditionTypeArr = Badge::getTriggerCondTypeArr($siteLangId);
$recordTypeArr = BadgeLinkCondition::getRecordTypeArr($siteLangId);

$tbl = new HtmlElement('table', array('width' => '100%', 'class' => 'table table-justified'));

$th = $tbl->appendElement('thead')->appendElement('tr');
foreach ($arr_flds as $key => $val) {
    $th->appendElement('th', array(), $val);
}

$sr_no = ($page > 1) ? $recordCount - (($page - 1) * $pageSize) : $recordCount;
foreach ($arrListing as $sn => $row) {
    $tr = $tbl->appendElement('tr');
    $name = $row[Badge::DB_TBL_PREFIX . 'identifier'];
    if (array_key_exists(Badge::DB_TBL_PREFIX . 'name', $row) && !empty($row[Badge::DB_TBL_PREFIX . 'name'])) {
        $name = $row[Badge::DB_TBL_PREFIX . 'name'];
    }
    foreach ($arr_flds as $key => $val) {
        $td = $tr->appendElement('td');
        switch ($key) {
            case 'listserial':
                $td->appendElement('plaintext', [], $sr_no, true);
                break;
            case Badge::DB_TBL_PREFIX . 'name':
                $td->appendElement('plaintext', [], $name, true);
                break;
            case BadgeLinkCondition::DB_TBL_PREFIX . 'record_type':
                $txt = empty($row[$key]) ? Labels::getLabel("LBL_N/A", $siteLangId) : $recordTypeArr[$row[$key]];
                $td->appendElement('plaintext', [], $txt, true);
                break;
            case Badge::DB_TBL_PREFIX . 'trigger_type':
                $class = Badge::COND_AUTO == $row[$key] ? 'badge-success' : 'badge-info';
                $html = '<span class="badge ' . $class . ' rounded-pill">' . $conditionTypeArr[$row[$key]] . '</span>';
                $td->appendElement('plaintext', [], $html, true);
                break;
            case Badge::DB_TBL_PREFIX . 'shape_type':
                if (Badge::TYPE_BADGE == $row[Badge::DB_TBL_PREFIX . 'type']) {
                    $getBadgeRatio = ImageDimension::getData(ImageDimension::TYPE_BADGE_ICON, ImageDimension::VIEW_MINI);
                    $icon = AttachedFile::getAttachment(AttachedFile::FILETYPE_BADGE, $row[Badge::DB_TBL_PREFIX . 'id'], 0, $siteLangId);
                    $uploadedTime = AttachedFile::setTimeParam($icon['afile_updated_at']);
                    $td->appendElement('img', ['data-aspect-ratio' => $getBadgeRatio[ImageDimension::VIEW_MINI]['aspectRatio'], 'src' => UrlHelper::getCachedUrl(UrlHelper::generateFileUrl('Image', 'badgeIcon', array($icon['afile_record_id'], $icon['afile_lang_id'], ImageDimension::VIEW_MINI, $icon['afile_screen']), CONF_WEBROOT_FRONTEND) . $uploadedTime, CONF_IMG_CACHE_TIME, '.jpg'), 'title' => $name, 'alt' => $name], '', true);
                } else {
                    $ribbon = $this->includeTemplate('_partial/ribbon-ui.php', ['ribbRow' => $row], false, true);
                    $html = '<div class="badge-wrap">' . $ribbon . '</div>';
                    $td->appendElement('plaintext', [], $html, true);
                }
                break;
            case Badge::DB_TBL_PREFIX . 'required_approval':
                $class = (applicationConstants::YES == $row[$key] ? 'badge-warning' : 'badge-success');
                $htm = ' <span class="badge badge-success rounded-pill">' . Labels::getLabel('LBL_NOT_REQUIRED', $siteLangId) . '</span>';;
                if (Badge::TYPE_BADGE == $row[Badge::DB_TBL_PREFIX . 'type']) {
                    $class = (Badge::COND_AUTO == $row[Badge::DB_TBL_PREFIX . 'trigger_type']) ? 'badge-danger' : $class;

                    if (Badge::COND_MANUAL == $row[Badge::DB_TBL_PREFIX . 'trigger_type'] && 0 < (int) $row['canAccess'] && $row[Badge::DB_TBL_PREFIX . 'required_approval'] == Badge::APPROVAL_REQUIRED && 0 < $row[BadgeRequest::DB_TBL_PREFIX . 'id']) {
                        $lbl = Labels::getLabel('LBL_APPROVED', $siteLangId);
                        $class = 'badge-success';
                    } else if (Badge::COND_MANUAL == $row[Badge::DB_TBL_PREFIX . 'trigger_type'] && 0 < (int) $row['breq_id'] && BadgeRequest::REQUEST_PENDING == (int) $row['breq_status']) {
                        $lbl = Labels::getLabel('LBL_REQUESTED', $siteLangId);
                        $class = 'badge-info';
                    } else {
                        $lbl = (Badge::COND_AUTO == $row[Badge::DB_TBL_PREFIX . 'trigger_type']) ? Labels::getLabel('LBL_NOT_ALLOWED', $siteLangId) : $approvalStatusArr[$row[$key]];
                    }
                    $htm = ' <span class="badge ' . $class . ' rounded-pill">' . $lbl . '</span>';
                }

                $td->appendElement('plaintext', [], $htm, true);
                break;
            case 'action':
                $ul = $td->appendElement("ul", array("class" => "actions"));
                if ($canEdit && (Badge::COND_MANUAL == $row[Badge::DB_TBL_PREFIX . 'trigger_type'])) {
                    if (0 < (int) $row['canAccess']) {
                        $condManualReq = (Badge::COND_MANUAL == $row[Badge::DB_TBL_PREFIX . 'trigger_type'] && 0 < (int) $row['canAccess'] && $row[Badge::DB_TBL_PREFIX . 'required_approval'] == Badge::APPROVAL_REQUIRED);

                        $icon = $condManualReq ? '<svg class="svg" width="18" height="18">
                                                    <use
                                                        xlink:href="' . CONF_WEBROOT_URL . 'images/retina/sprite-actions.svg' . AttachedFile::setTimeParam(RELEASE_DATE) . '#view">
                                                    </use>
                                                </svg>' : '<svg class="svg" width="18" height="18">
                                                                <use
                                                                    xlink:href="' . CONF_WEBROOT_URL . 'images/retina/sprite-actions.svg' . AttachedFile::setTimeParam(RELEASE_DATE) . '#linking">
                                                                </use>
                                                            </svg>';
                        $title = $condManualReq ? Labels::getLabel('LBL_VIEW', $siteLangId) : Labels::getLabel('LBL_BIND_CONDITION', $siteLangId);

                        $li = $ul->appendElement("li");
                        $li->appendElement('a', array('href' => UrlHelper::generateUrl('BadgeLinkConditions', 'list', [$row[Badge::DB_TBL_PREFIX . 'id'], $row[Badge::DB_TBL_PREFIX . 'type']]), 'title' => $title), $icon, true);

                        if ($condManualReq && 0 < $row[BadgeRequest::DB_TBL_PREFIX . 'id']) {
                            $li = $ul->appendElement("li");
                            $li->appendElement(
                                'a',
                                array('href' => 'javascript:void(0)', 'onclick' => "deleteBadgeRequest(" . $row['breq_id'] . ")", 'title' => Labels::getLabel('LBL_DELETE_REQUEST', $siteLangId)),
                                '<svg class="svg" width="18" height="18">
                                    <use
                                        xlink:href="' . CONF_WEBROOT_URL . 'images/retina/sprite-actions.svg' . AttachedFile::setTimeParam(RELEASE_DATE) . '#delete">
                                    </use>
                                </svg>',
                                true
                            );
                        }
                    } else if (0 < (int) $row['breq_id'] && BadgeRequest::REQUEST_PENDING == (int) $row['breq_status']) {
                        $htm = ' <span class="badge badge-danger rounded-pill">' . Labels::getLabel('LBL_N/A', $siteLangId) . '</span>';
                        $td->appendElement('plaintext', [], $htm, true);
                    } else {
                        $icon = '<svg class="svg" width="18" height="18">
                                        <use xlink:href="' . CONF_WEBROOT_URL . 'images/retina/sprite-actions.svg' . AttachedFile::setTimeParam(RELEASE_DATE) . '#requests"></use>
                                    </svg>';
                        $function = "addBadgeReqForm(0, " . $row[Badge::DB_TBL_PREFIX . 'id'] . ")";
                        $li = $ul->appendElement("li");
                        $li->appendElement('a', array('href' => 'javascript:void(0)', 'onclick' => $function, 'title' => Labels::getLabel('LBL_REQUEST', $siteLangId)), $icon, true);
                    }
                } else {
                    $li = $ul->appendElement("li");
                    $li->appendElement('a', array('href' => UrlHelper::generateUrl('BadgeLinkConditions', 'list', [$row[Badge::DB_TBL_PREFIX . 'id'], $row[Badge::DB_TBL_PREFIX . 'type']]), 'title' => Labels::getLabel('LBL_VIEW', $siteLangId)), '<svg class="svg" width="18" height="18">
                                                                                <use
                                                                                    xlink:href="' . CONF_WEBROOT_URL . 'images/retina/sprite-actions.svg' . AttachedFile::setTimeParam(RELEASE_DATE) . '#view">
                                                                                </use>
                                                                            </svg>', true);
                }
                break;
        }
    }
    $sr_no--;
}

if (count($arrListing) == 0) {
    $img = $this->includeTemplate('_partial/no-record-found.php', [], false, true);
    $tbl->appendElement('tr')->appendElement(
        'td',
        array(
            'colspan' => isset($arr_flds) ? count($arr_flds) : 1,
            'class' => 'noRecordFoundJs'
        ),
        $img,
        true
    );
}

$frm = new Form('frmSearchListing');
$frm->setFormTagAttribute('class', 'web_form last_td_nowrap actionButtons-js badgesList--js');
$frm->setFormTagAttribute('onsubmit', 'formAction(this, reloadList ); return(false);');
$frm->setFormTagAttribute('action', UrlHelper::generateUrl('Badges', 'toggleBulkStatuses'));
$frm->addHiddenField('', 'status'); ?>

<div class="js-scrollable table-wrap table-responsive">
    <?php echo $frm->getFormTag();
    echo $frm->getFieldHtml('status');
    echo $tbl->getHtml();
    echo '</form>';
    $postedData['page'] = $page;
    echo FatUtility::createHiddenFormFromData($postedData, array(
        'name' => 'frmSrchPaging'
    ));
    $pagingArr = array('pageCount' => $pageCount, 'page' => $page, 'recordCount' => $recordCount, 'siteLangId' => $siteLangId);
    $this->includeTemplate('_partial/pagination.php', $pagingArr, false); ?>
</div>