<?php

defined('SYSTEM_INIT') or die('Invalid Usage.');
$arr_flds = array(
    'listserial' => '#',
    'plugin_identifier' => Labels::getLabel('LBL_PLUGIN', $siteLangId),
    'pu_active' => Labels::getLabel('LBL_Status', $siteLangId),
);
if ($canEdit) {
    $arr_flds = array_merge($arr_flds, array('action' => ''));
}

$tableClass = '';
if (0 < count($arrListing)) {
    $tableClass = "table-justified";
}

$tbl = new HtmlElement(
    'table',
    array('width' => '100%', 'class' => 'table ' . $tableClass, 'id' => 'options')
);

$th = $tbl->appendElement('thead')->appendElement('tr');
foreach ($arr_flds as $key => $val) {
    if ('select_all' == $key) {
        $th->appendElement('th')->appendElement('plaintext', array(), '<label class="checkbox"><input type="checkbox" onclick="selectAll( $(this) )" class="selectAll-js">' . $val . '</label>', true);
    } else {
        $th->appendElement('th', array(), $val);
    }
}

$sr_no = 0;
foreach ($arrListing as $sn => $row) {
    $sr_no++;
    $tr = $tbl->appendElement('tr');
    $tr->setAttribute("id", $row['plugin_id']);

    foreach ($arr_flds as $key => $val) {
        $td = $tr->appendElement('td');
        switch ($key) {
            case 'listserial':
                $td->appendElement('plaintext', array(), $sr_no);
                break;
            case 'plugin_identifier':
                $htm = '';
                if (!empty($defaultPluginId) && $row['plugin_id'] == $defaultPluginId) {
                    $htm = ' <span class="badge badge-brand badge-inline badge-pill">' . Labels::getLabel('LBL_DEFAULT', $siteLangId) . '</span>';
                }

                if (!empty($row['plugin_name'])) {
                    $td->appendElement('plaintext', array(), $row['plugin_name'] . $htm, true);
                    $td->appendElement('br', array());
                    $td->appendElement('plaintext', array(), '(' . $row[$key] . ')', true);
                } else {
                    $td->appendElement('plaintext', array(), $row[$key] . $htm, true);
                }
                break;
            case 'pu_active':
                $attributes = (applicationConstants::ACTIVE == $row['pu_active']) ? "checked" : "";
                $attributes .= ' onclick="toggleStatus(this,' . ($row['pu_active'] > 0 ? 0 : 1) . ')"';
                $str = HtmlHelper::configureSwitchForCheckboxStatic('', $row['plugin_id'], $attributes);
                $td->appendElement('plaintext', array(), $str, true);
                break;
            case 'action':
                $ul = $td->appendElement("ul", array("class" => "actions"));
                if (applicationConstants::ACTIVE == $row['pu_active'] && Plugin::TYPE_SHIPPING_SERVICES == $row['plugin_type']) {
                    $li = $ul->appendElement("li");
                    $li->appendElement(
                        'a',
                        array(
                            'href' => 'javascript:void(0)',
                            'data-bs-toggle' => 'tooltip',
                            'title' => Labels::getLabel('LBL_SYNC_PLUGIN_CARRIERS', $siteLangId),
                            'onclick' => "syncCarriers(" . UserAuthentication::getLoggedUserId() . ")",
                        ),
                        '<i class="icn">
                                    <svg class="svg" width="18" height="18">
                                        <use
                                            xlink:href="' . CONF_WEBROOT_URL . 'images/retina/sprite.yokart.svg' . AttachedFile::setTimeParam(RELEASE_DATE) . '#sync">
                                        </use>
                                    </svg>
                                </i>',
                        true
                    );
                }

                $li = $ul->appendElement("li");
                $li->appendElement(
                    'a',
                    array(
                        'href' => 'javascript:void(0)',
                        'data-bs-toggle' => 'tooltip',
                        'title' => Labels::getLabel('LBL_Edit', $siteLangId),
                        "onclick" => "editSettingForm('" . $row['plugin_code'] . "')"
                    ),
                    '<i class="icn">
                                <svg class="svg" width="18" height="18">
                                    <use
                                        xlink:href="' . CONF_WEBROOT_URL . 'images/retina/sprite-actions.svg' . AttachedFile::setTimeParam(RELEASE_DATE) . '#edit">
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
?>
<div class="js-scrollable table-wrap table-responsive">
    <?php
    echo $tbl->getHtml();
    if (count($arrListing) == 0) {
        $message = Labels::getLabel('LBL_No_Records_Found', $siteLangId);
        $this->includeTemplate('_partial/no-record-found.php', array('siteLangId' => $siteLangId, 'message' => $message));
    }
    ?>
</div>