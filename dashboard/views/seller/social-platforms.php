<?php defined('SYSTEM_INIT') or die('Invalid Usage.'); ?>

<div class="card-head border-0 py-4">
    <div class="card-head-label">
        <h5 class="card-title"><?php echo Labels::getLabel('LBL_SOCIAL_PLATFORMS', $siteLangId); ?></h5>
    </div> <?php
            if ($canEdit) {
                $btnData = [
                    'siteLangId' => $siteLangId,
                    'canEdit' => $canEdit
                ];

                if ($canEdit) {
                    $btnData['listTopButtons'] = [
                        [
                            'attr' => [
                                'class' => 'btn btn-outline-gray btn-icon btn-add',
                                'onclick' => 'addForm(0)',
                                'title' => Labels::getLabel('LBL_ADD_SOCIAL_PLATFORM', $siteLangId)
                            ],
                            'label' => '<svg class="svg btn-icon-start" width="18" height="18">
                                    <use xlink:href="' . CONF_WEBROOT_URL . 'images/retina/sprite-actions.svg' . AttachedFile::setTimeParam(RELEASE_DATE) . '#add">
                                    </use>
                                </svg><span>' . Labels::getLabel('BTN_NEW', $siteLangId) . '</span>'
                        ],
                    ];
                }
                $this->includeTemplate('_partial/listing/action-buttons.php', $btnData);
            }
            ?>
</div>
<div class="card-table">
    <div class="js-scrollable table-wrap table-responsive">
        <?php
        $arr_flds = array(
            'listserial' => '#',
            'splatform_identifier' => Labels::getLabel('LBL_Title', $siteLangId),
            'splatform_url' => Labels::getLabel('LBL_URL', $siteLangId),
            'splatform_active' => Labels::getLabel('LBL_Status', $siteLangId)
        );
        if ($canEdit) {
            $arr_flds['action'] = '';
        }
        $tableClass = '';
        if (0 < count($arrListing)) {
            $tableClass = "table-justified";
        }
        $tbl = new HtmlElement('table', array('width' => '100%', 'class' => 'table ' . $tableClass));
        $th = $tbl->appendElement('thead')->appendElement('tr');
        foreach ($arr_flds as $key => $val) {
            if ($key == 'listserial') {
                $e = $th->appendElement('th', array('width' => '5%'), $val);
            } elseif ($key == 'splatform_identifier') {
                $e = $th->appendElement('th', array('width' => '25%'), $val);
            } elseif ($key == 'splatform_url') {
                $e = $th->appendElement('th', array('width' => '45%'), $val);
            } elseif ($key == 'splatform_active') {
                $e = $th->appendElement('th', array('width' => '10%'), $val);
            } elseif ($key == 'action') {
                $e = $th->appendElement('th', array('width' => '15%'), $val);
            }
        }

        $sr_no = 0;
        foreach ($arrListing as $sn => $row) {
            $sr_no++;
            $tr = $tbl->appendElement('tr', array('class' => ($row['splatform_active'] != applicationConstants::ACTIVE) ? 'fat-inactive' : ''));
            foreach ($arr_flds as $key => $val) {
                $td = $tr->appendElement('td');
                switch ($key) {
                    case 'listserial':
                        $td->appendElement('plaintext', array(), $sr_no);
                        break;
                    case 'splatform_identifier':
                        if ($row['splatform_title'] != '') {
                            $td->appendElement('plaintext', array(), $row['splatform_title'], true);
                            $td->appendElement('br', array());
                            $td->appendElement('plaintext', array(), '(' . $row[$key] . ')', true);
                        } else {
                            $td->appendElement('plaintext', array(), $row[$key], true);
                        }
                        break;
                    case 'splatform_active':
                        $attributes = (applicationConstants::ACTIVE == $row['splatform_active']) ? "checked" : "";
                        $attributes .= (!$canEdit) ? ' disabled' : '';
                        $attributes .= ' onclick="toggleSocialPlatformStatus(event,this)"';
                        $str = HtmlHelper::configureSwitchForCheckboxStatic('', $row['splatform_id'], $attributes);
                        $td->appendElement('plaintext', array(), $str, true);
                        break;
                    case 'action':
                        $ul = $td->appendElement("ul", array("class" => "actions"));
                        $li = $ul->appendElement("li");
                        $li->appendElement(
                            'a',
                            array('href' => 'javascript:void(0)', 'class' => 'button small green', 'title' => Labels::getLabel('LBL_Edit', $siteLangId), "onclick" => "addForm(" . $row['splatform_id'] . ")"),
                            '<svg class="svg" width="18" height="18">
                            <use
                                xlink:href="' . CONF_WEBROOT_URL . 'images/retina/sprite-actions.svg' . AttachedFile::setTimeParam(RELEASE_DATE) . '#edit">
                            </use>
                        </svg>',
                            true
                        );
                        $li = $ul->appendElement("li");
                        $li->appendElement('a', array('href' => 'javascript:void(0)', 'class' => 'button small green', 'title' => Labels::getLabel('LBL_Delete', $siteLangId), "onclick" => "deleteRecord(" . $row['splatform_id'] . ")"), '<svg class="svg" width="18" height="18">
                            <use
                                xlink:href="' . CONF_WEBROOT_URL . 'images/retina/sprite-actions.svg' . AttachedFile::setTimeParam(RELEASE_DATE) . '#delete">
                            </use>
                        </svg>', true);
                        break;
                    default:
                        $td->appendElement('plaintext', array(), $row[$key], true);
                        break;
                }
            }
        }
        echo $tbl->getHtml();
        if (count($arrListing) == 0) {
            $message = Labels::getLabel('LBL_No_Records_Found', $siteLangId);
            $this->includeTemplate('_partial/no-record-found.php', array('siteLangId' => $siteLangId, 'message' => $message));
        }
        ?>
        </form>
    </div>
</div>