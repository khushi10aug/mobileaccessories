<?php defined('SYSTEM_INIT') or die('Invalid Usage.');

$allPlugins = $arrListing;
$pluginType = (!empty($allPlugins)) ? (array_shift($allPlugins))['plugin_type'] : '';

if (!in_array($pluginType, Plugin::getSeparateIconTypeArr())) {
    unset($fields['plugin_icon']);
}

$isKingPinType = in_array($pluginType, Plugin::getKingpinTypeArr());

if (!$canEdit || 2 > count($arrListing) || $isKingPinType) {
    unset($fields['dragdrop'], $fields['select_all']);
}

$tableId = "pluginsJs";
require_once(CONF_THEME_PATH . '_partial/listing/listing-column-head.php');

$aspectRatioArr = AttachedFile::getRatioTypeArray($siteLangId);
$msg = '';

$serialNo = 0;
foreach ($arrListing as $sn => $row) {
    $serialNo++;
    $cls = (($serialNo % 2) == 0) ? 'even' : 'odd';
    $tr = $tbody->appendElement('tr', ['class' => $cls, 'data-row' => $serialNo, 'id' => $row['plugin_id']]);
    foreach ($fields as $key => $val) {
        $tdAttr = ('action' == $key) ? ['class' => 'align-right'] : (('select_all' == $key) ? ['class' => 'col-check'] : []);
        $td = $tr->appendElement('td', $tdAttr);
        switch ($key) {
            case 'dragdrop':
                $div = $td->appendElement('div', ['class' => 'handleJs']);
                $div->appendElement('plaintext', $tdAttr, '<svg class="svg" width="18" height="18">
                                                            <use
                                                                xlink:href="' . CONF_WEBROOT_URL . 'images/retina/sprite-actions.svg' . AttachedFile::setTimeParam(RELEASE_DATE) . '#drag">
                                                            </use>
                                                        </svg>', true);
                break;
            case 'select_all':
                $td->appendElement('plaintext', $tdAttr, '<label class="checkbox"><input class="selectItemJs" type="checkbox" name="plugin_ids[]" value=' . $row['plugin_id'] . '><i class="input-helper"></i></label>', true);
                break;
            case 'listSerial':
                $td->appendElement('plaintext', $tdAttr, $serialNo);
                break;
            case 'plugin_icon':
                $fileData = AttachedFile::getAttachment(AttachedFile::FILETYPE_PLUGIN_LOGO, $row['plugin_id']);
                $uploadedTime = '';
                $aspectRatio = '';
                if (!empty($fileData)) {
                    $uploadedTime = AttachedFile::setTimeParam($fileData['afile_updated_at']);
                    $aspectRatio = ($fileData['afile_aspect_ratio'] > 0 && isset($aspectRatioArr[$fileData['afile_aspect_ratio']])) ? $aspectRatioArr[$fileData['afile_aspect_ratio']] : '';
                }
                $imagePluginDimensions = ImageDimension::getData(ImageDimension::TYPE_PLUGIN_IMAGE, ImageDimension::VIEW_ICON);
                $imageUrl = UrlHelper::getCachedUrl(UrlHelper::generateFileUrl('Image', 'plugin', array($row['plugin_id'], ImageDimension::VIEW_ICON), CONF_WEBROOT_FRONT_URL) . $uploadedTime, CONF_IMG_CACHE_TIME, '.jpg');
                $imgHtm = '<img data-aspect-ratio = "' . $imagePluginDimensions[ImageDimension::VIEW_ICON]['aspectRatio'] . '" src="' . $imageUrl . '" data-ratio="' . $aspectRatio . '">';
                $td->appendElement('plaintext', $tdAttr, $imgHtm, true);
                break;
            case 'plugin_name':
                $defaultCurrConvAPI = FatApp::getConfig('CONF_DEFAULT_PLUGIN_' . $row['plugin_type'], FatUtility::VAR_INT, 0);
                $htm = '';
                if (in_array($row['plugin_code'], Plugin::PAY_LATER)) {
                    $htm .= ' <span class="badge badge-warning">'  . Labels::getLabel('LBL_PAY_LATER', $siteLangId) . '</span>';
                }
                $td->appendElement('plaintext', $tdAttr, $row[$key] . $htm, true);
                break;
            case 'plugin_active':
                $fn = 'reloadList()';
                if (in_array($row['plugin_type'], [Plugin::TYPE_SHIPMENT_TRACKING, Plugin::TYPE_SHIPPING_SERVICES]) && ('AfterShipShipment' == $row['plugin_code'] || 'ShipStationShipping' == $row['plugin_code']) && Plugin::INACTIVE == $row[$key]) {
                    $isShipStationActive = Plugin::isActive('ShipStationShipping');
                    $isAfterShipActive = Plugin::isActive('AfterShipShipment');
                    if ('AfterShipShipment' == $row['plugin_code'] && true === $isShipStationActive) {
                        $fn = 'redirectToTrackingCodeRelation()';
                    } else if ('ShipStationShipping' == $row['plugin_code'] && true === $isAfterShipActive) {
                        $fn = 'redirectToTrackingCodeRelation()';
                    }
                }
                
                $statusAct = ($canEdit) ? 'updateStatus(event, this, ' . $row['plugin_id'] . ', ' . ((int) !$row[$key]) . ', \'' . $fn . '\')' : 'return false;';
                if (!empty($otherPluginTypes) && $canEdit) {
                    if (empty($msg)) {
                        $msg = Labels::getLabel("MSG_TURNING_ON_{PLUGIN-TYPE}_WILL_TURN_OFF_{OTHER-PLUGIN-TYPE}_PLUGINS._DO_YOU_WANT_TO_CONTINUE_?", $siteLangId);
                        $msg = CommonHelper::replaceStringData($msg, ['{PLUGIN-TYPE}' => $pluginTypes[$row['plugin_type']], '{OTHER-PLUGIN-TYPE}' => $otherPluginTypes]);
                    }
                    $statusAct = "changeStatusEitherPluginTypes(this, " . ($row['plugin_active'] > 0 ? 0 : 1) . ", '" . $msg . "')";
                }

                $otherAttribute = '';
                if ($row['plugin_type'] == Plugin::TYPE_TAX_SERVICES) {
                    $otherAttribute = 'data-function="' . $statusAct . '"';
                    $statusAct = 'return confirmTaxPluginActivation(this, \'' . Labels::getLabel('LBL_PLEASE_RE-BIND_THE_PRODUCT_TAX_CATEGORY.') . '\');';
                }

                $statusClass = ($canEdit) ? '' : 'disabled';
                $checked = applicationConstants::ACTIVE == $row[$key] ? 'checked' : '';

                $htm = '<span class="switch switch-sm switch-icon">
                                    <label>
                                        <input type="checkbox" id="' . $row['plugin_id'] . '" data-old-status="' . $row[$key] . '" value="' . $row['plugin_id'] . '" ' . $checked . ' onclick="' . $statusAct . '" ' . $statusClass . ' ' . $otherAttribute . '>
                                        <span class="input-helper"></span>
                                    </label>
                                </span>';
                $td->appendElement('plaintext', $tdAttr, $htm, true);
                break;
            case 'action':
                $data = [
                    'siteLangId' => $siteLangId,
                    'recordId' => $row['plugin_id']
                ];

                if ($canEdit) {
                    $data['editButton'] = [];
                    $data['otherButtons'] = [
                        [
                            'attr' => [
                                'href' => 'javascript:void(0)',
                                'onclick' => "editSettingForm('" . $row['plugin_code'] . "')",
                                'title' => Labels::getLabel('LBL_SETTINGS', $siteLangId)
                            ],
                            'label' => '<svg class="svg" width="20" height="20">
                                            <use xlink:href="' . CONF_WEBROOT_URL . 'images/retina/sprite.yokart.svg' . AttachedFile::setTimeParam(RELEASE_DATE) . '#icon-setting">
                                            </use>
                                        </svg>'
                        ],
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

include(CONF_THEME_PATH . '_partial/listing/no-record-found.php');

$formAction = isset($formAction) ? $formAction : 'toggleBulkStatuses';
$attr = [
    'class' => 'actionButtonsJs',
    'onsubmit' => 'formAction(this, reloadList); return(false);',
    'action' => UrlHelper::generateUrl('Plugins', $formAction),
];
$frm = new Form('listingForm', $attr);
$frm->addHiddenField('', 'plugin_type', $pluginType);
$frm->addHiddenField('', 'status'); ?>

<div class="card-head">
    <div class="card-head-label">
        <h3 class="card-head-title">
            <?php echo CommonHelper::replaceStringData(Labels::getLabel('LBL_{PLUGIN-NAME}_PLUGINS', $siteLangId), ['{PLUGIN-NAME}' =>  $pluginTypes[$type]]); ?>
        </h3>
    </div>
    <div class="card-toolbar">
        <?php
        $data = [
            'canEdit' => $canEdit,
            'siteLangId' => $siteLangId,
            'statusButtons' => (1 < count($arrListing) && $canEdit && !$isKingPinType),
            'otherButtons' => $otherButtons ?? [],
        ];

        if ($data['statusButtons'] && ($pluginType == Plugin::TYPE_SPLIT_PAYMENT_METHOD || $pluginType == Plugin::TYPE_REGULAR_PAYMENT_METHOD) && !empty($otherPluginTypes)) {
            $msg = Labels::getLabel("MSG_TURNING_ON_{PLUGIN-TYPE}_WILL_TURN_OFF_{OTHER-PLUGIN-TYPE}_PLUGINS._DO_YOU_WANT_TO_CONTINUE_?", $siteLangId);
            $msg = CommonHelper::replaceStringData($msg, ['{PLUGIN-TYPE}' => $pluginTypes[$row['plugin_type']], '{OTHER-PLUGIN-TYPE}' => $otherPluginTypes]);
            $data['msg'] = $msg;
        } 
        
        if ($pluginType == Plugin::TYPE_TAX_SERVICES) {
            $plugin = new Plugin();
            if ($plugin->getDefaultPluginData(Plugin::TYPE_TAX_SERVICES, 'plugin_id')) {
                $data['otherButtons'] = [
                    [
                        'attr' => [
                            'href' => 'javascript:void(0)',
                            'class' => 'btn btn-outline-brand btn-icon',
                            'onclick' => "syncCategories()",
                            'title' => Labels::getLabel('LBL_SYNC_TAX_CATEGORIES', $siteLangId)
                        ],
                        'label' => '<svg class="svg" width="18" height="18">
                                        <use
                                            xlink:href="' . CONF_WEBROOT_URL . 'images/retina/sprite.yokart.svg' . AttachedFile::setTimeParam(RELEASE_DATE) . '#sync-currency">
                                        </use>
                                    </svg><span>' . Labels::getLabel('BTN_SYNC', $siteLangId) . '</span>',
                    ]
                ];
            }
        }

        $this->includeTemplate('_partial/listing/action-buttons.php', $data, false);
        ?>
    </div>
</div>
<div class="card-table">
    <div class="table-responsive table-scrollable js-scrollable listingTableJs">
        <?php
        echo $frm->getFormTag();
        echo $frm->getFieldHtml('plugin_type');
        echo $frm->getFieldHtml('status');
        echo $tbl->getHtml();
        echo '</form>';
        ?>
    </div>
</div>