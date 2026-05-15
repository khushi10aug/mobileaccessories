<?php defined('SYSTEM_INIT') or die('Invalid Usage.'); 
$activeGentab = !empty($activeGentab) ? 'active' : '';
$activeLangtab = !empty($activeLangtab) ? 'active' : '';
$disabled = !empty($disabled) ? ' disabled' : '';
$formTitle = !empty($formTitle) ? $formTitle : Labels::getLabel('LBL_SETUP', $siteLangId);
$formSubTitle = !empty($formSubTitle) ? $formSubTitle : '';
?>

<div class="modal-header">
    <h5 class="modal-title">
        <?php echo $formTitle; ?>
        <?php if (!empty($formSubTitle)) { ?>
            <span class="text-muted"><?php echo $formSubTitle; ?></span>
        <?php } ?>
    </h5>
</div>
<div class="modal-body form-edit"> <!-- Closing tag must be added inside the files who include this file. -->
    <?php if (0 < count($languages) && $metaType == MetaTag::META_GROUP_ADVANCED) { ?>
        <div class="form-edit-head">
            <nav class="nav nav-tabs navTabsJs">
                <a class="nav-link <?php echo $activeGentab; ?>" href="javascript:void(0)" onclick="editMetaTagForm(<?php echo $metaId; ?>, '<?php echo $metaType; ?>', <?php echo $metaTagRecordId; ?>)" title="<?php echo Labels::getLabel('LBL_GENERAL', $siteLangId); ?>">
                    <?php echo Labels::getLabel('LBL_GENERAL', $siteLangId); ?>
                </a>
                <?php if (0 < count($languages)) { 
                    $onclick = (0 < $metaId) ? "editMetaTagLangForm(" . $metaId . ", " . array_key_first($languages) . ", '" . $metaType . "', " . $metaTagRecordId . ")" : 'return false;';
                    ?>
                    <a class="nav-link <?php echo $activeLangtab . $disabled; ?>" href="javascript:void(0);" onclick="<?php echo $onclick; ?>" title="<?php echo Labels::getLabel('LBL_LANGUAGE_DATA', $siteLangId); ?>">
                        <?php echo Labels::getLabel('LBL_LANGUAGE_DATA', $siteLangId); ?>
                    </a>
                <?php } ?>
            </nav>
        </div>
    <?php } elseif (!empty($sellerInventorySelprodId)) { ?>
        <div class="form-edit-head">
            <nav class="nav nav-tabs navTabsJs">
                <a class="nav-link" href="javascript:void(0);" onclick="editSellerProductInventory(<?php echo (int) $sellerInventorySelprodId; ?>);" title="<?php echo Labels::getLabel('LBL_GENERAL', $siteLangId); ?>">
                    <?php echo Labels::getLabel('LBL_GENERAL', $siteLangId); ?>
                </a>
                <a class="nav-link active" href="javascript:void(0);" title="<?php echo Labels::getLabel('LBL_META_TAG_SETUP', $siteLangId); ?>">
                    <?php echo Labels::getLabel('LBL_META_TAG_SETUP', $siteLangId); ?>
                </a>
            </nav>
        </div>
    <?php } ?>