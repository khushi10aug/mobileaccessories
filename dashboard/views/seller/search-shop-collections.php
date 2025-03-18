<?php defined('SYSTEM_INIT') or die('Invalid Usage.'); ?>
<div class="card-head border-0 py-4">
    <div class="card-head-label">
        <h5 class="card-title"><?php echo Labels::getLabel('LBL_Shop_Collections', $siteLangId); ?></h5>
    </div> <?php
            if ($canEdit) {
                $btnData = [
                    'statusButtons' => true,
                    'deleteButton' => true,
                    'siteLangId' => $siteLangId,
                    'canEdit' => $canEdit
                ];

                if (count($arrListing) > 0) {
                    $btnData['listTopButtons'] = [
                        [
                            'attr' => [
                                'class' => 'btn btn-outline-gray btn-icon',
                                'onclick' => 'getShopCollectionGeneralForm(0)',
                                'title' => Labels::getLabel('BTN_NEW_RECORD', $siteLangId)
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
    <div class="table-wrap">
        <?php
        $arr_flds = array(
            'listserial' => '#',
            'scollection_identifier' => Labels::getLabel('LBL_Collection_Name', $siteLangId),
            'scollection_active' => Labels::getLabel('LBL_Status', $siteLangId),
            'action' => '',
        );
        if (count($arrListing) > 0) {
            $arr_flds = array_merge(
                array('select_all' => ''),
                $arr_flds
            );
        }
        $tableClass = '';
        if (0 < count($arrListing) && $canEdit) {
            $tableClass = "table-justified";
        }
        $tbl = new HtmlElement(
            'table',
            array('width' => '100%', 'class' => 'table ' . $tableClass, 'id' => 'options')
        );

        $th = $tbl->appendElement('thead')->appendElement('tr');
        foreach ($arr_flds as $key => $val) {
            if ('select_all' == $key && $canEdit) {
                $th->appendElement('th')->appendElement('plaintext', array(), '<label class="checkbox"><input type="checkbox" onclick="selectAll( $(this) )" class="selectAll-js">' . $val . '</label>', true);
            } else {
                $th->appendElement('th', array(), $val);
            }
        }
        $sr_no = 0;
        foreach ($arrListing as $sn => $row) {
            $sr_no++;
            $tr = $tbl->appendElement('tr');
            $tr->setAttribute("id", $row['scollection_id']);

            foreach ($arr_flds as $key => $val) {
                $td = $tr->appendElement('td');
                switch ($key) {
                    case 'select_all':
                        $td->appendElement('plaintext', array(), '<label class="checkbox"><input class="selectItem--js" type="checkbox" name="scollection_ids[]" value=' . $row['scollection_id'] . '></label>', true);
                        break;
                    case 'listserial':
                        $td->appendElement('plaintext', array(), $sr_no);
                        break;
                    case 'scollection_identifier':
                        $td->appendElement('plaintext', array(), $row[$key], true);
                        break;

                    case 'scollection_active':
                        $attributes = (applicationConstants::ACTIVE == $row['scollection_active']) ? "checked" : "";
                        $attributes .= (!$canEdit) ? ' disabled' : '';
                        $attributes .= ' onclick="toggleShopCollectionStatus(event,this)"';
                        $str = HtmlHelper::configureSwitchForCheckboxStatic('', $row['scollection_id'], $attributes);

                        $td->appendElement('plaintext', array(), $str, true);
                        break;

                    case 'action':
                        $ul = $td->appendElement("ul", array("class" => "actions"));
                        if ($canEdit) {
                            $li = $ul->appendElement("li");
                            $li->appendElement(
                                'a',
                                array(
                                    'href' => 'javascript:void(0)',
                                    'class' => 'button small green', 'title' => Labels::getLabel('LBL_Edit', $siteLangId),
                                    "onclick" => "getShopCollectionGeneralForm(" . $row['scollection_id'] . ")"
                                ),
                                '<svg class="svg" width="18" height="18">
                                    <use
                                        xlink:href="' . CONF_WEBROOT_URL . 'images/retina/sprite-actions.svg' . AttachedFile::setTimeParam(RELEASE_DATE) . '#edit">
                                    </use>
                                </svg>',
                                true
                            );

                            $li = $ul->appendElement("li");
                            $li->appendElement(
                                'a',
                                array(
                                    'href' => "javascript:void(0)", 'class' => 'button small green',
                                    'title' => Labels::getLabel('LBL_Delete', $siteLangId), "onclick" => "deleteShopCollection(" . $row['scollection_id'] . ")"
                                ),
                                '<svg class="svg" width="18" height="18">
                                    <use
                                        xlink:href="' . CONF_WEBROOT_URL . 'images/retina/sprite-actions.svg' . AttachedFile::setTimeParam(RELEASE_DATE) . '#delete">
                                    </use>
                                </svg>',
                                true
                            );
                        }
                        break;
                    default:
                        $td->appendElement('plaintext', array(), $row[$key], true);
                        break;
                }
            }
        }

        $frm = new Form('frmCollectionsListing', array('id' => 'frmCollectionsListing'));
        $frm->setFormTagAttribute('class', 'form');
        $frm->setFormTagAttribute('onsubmit', 'formAction(this, shopCollections ); return(false);');
        $frm->setFormTagAttribute('action', UrlHelper::generateUrl('Seller', 'toggleBulkCollectionStatuses'));
        $frm->addHiddenField('', 'collection_status', '');

        echo $frm->getFormTag();
        echo $frm->getFieldHtml('collection_status');
        echo $tbl->getHtml();
        if (count($arrListing) == 0) {
            $message = Labels::getLabel('LBL_No_Collection_found', $siteLangId);
            $linkArr = array(
                0 => array(
                    'href' => 'javascript:void(0);',
                    'label' => Labels::getLabel('LBL_Add_Collection', $siteLangId),
                    'onclick' => "getShopCollectionGeneralForm(0)",
                )
            );
            $this->includeTemplate('_partial/no-record-found.php', array('siteLangId' => $siteLangId, 'linkArr' => $linkArr, 'message' => $message));
        } ?>
        </form>
    </div>
</div>