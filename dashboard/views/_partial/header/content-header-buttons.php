<?php
$canEdit = $canEdit ?? true;
$action = $action ?? '';
$canViewProducts = $canViewProducts ?? true;
$canEditProducts = $canEditProducts ?? true;
if ($canEdit) { ?>
    <div class="content-header-toolbar" id="headerToolbar">
        <ul>
            <?php if (User::canAddCustomProduct() && $action == 'products') {
                if ($canEditProducts) { ?>
                    <li>
                        <a href="<?php echo UrlHelper::generateUrl('products', 'form'); ?>" class="btn btn-outline-gray btn-icon">
                            <svg class="svg btn-icon-start" width="18" height="18">
                                <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite-actions.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#add">
                                </use>
                            </svg><?php echo Labels::getLabel('LBL_NEW_PRODUCT', $siteLangId); ?>
                        </a>
                    </li>
                <?php }
                if ($canViewProducts) { ?>
                    <li>
                        <a href="<?php echo UrlHelper::generateUrl('seller', 'catalog'); ?>" class="btn btn-outline-gray btn-icon">
                            <svg class="svg btn-icon-start" width="18" height="18">
                                <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite-actions.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#inventories">
                                </use>
                            </svg><?php echo Labels::getLabel('LBL_MY_PRODUCTS', $siteLangId); ?>
                        </a>
                    </li>
            <?php }
            } ?>
            <?php if (isset($adminCatalogs) && $adminCatalogs > 0 && !FatApp::getConfig('CONF_WITHOUT_PROD_VARIANTS', FatUtility::VAR_INT, 0)) { ?>
                <li>
                    <a href="<?php echo UrlHelper::generateUrl('seller', 'catalog', [0]); ?>" class="btn btn-outline-gray btn-icon">
                        <svg class="svg btn-icon-start" width="18" height="18">
                            <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite-actions.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#inventories">
                            </use>
                        </svg><?php echo Labels::getLabel('LBL_MARKETPLACE_PRODUCTS', $siteLangId); ?>
                    </a>
                </li>
            <?php } ?>
            <?php if (User::canAddCustomProduct() && $action == 'catalog' && $type == 1) { ?>
                <li>
                    <a href="<?php echo UrlHelper::generateUrl('products', 'form'); ?>" class="btn btn-outline-gray btn-icon">
                        <svg class="svg btn-icon-start" width="18" height="18">
                            <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite-actions.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#add">
                            </use>
                        </svg><?php echo Labels::getLabel('LBL_NEW_CATALOG', $siteLangId); ?>
                    </a>
                </li>
            <?php } ?>

            <?php if ((isset($canAddCustomProduct) && $canAddCustomProduct == false) && (isset($canRequestProduct) && $canRequestProduct === true)) { ?>
                <li>
                    <a href="<?php echo UrlHelper::generateUrl('Seller', 'requestedCatalog'); ?>" class="btn btn-outline-gray btn-icon">
                        <svg class="svg btn-icon-start" width="18" height="18">
                            <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite-actions.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#inventories">
                            </use>
                        </svg><?php echo Labels::getLabel('LBL_REQUEST_A_PRODUCT', $siteLangId); ?>
                    </a>
                </li>
            <?php } ?>

            <?php if (User::canAddCustomProduct() && ($action == 'catalog') && !FatApp::getConfig('CONF_WITHOUT_PROD_VARIANTS', FatUtility::VAR_INT, 0)) { 
                $prodUrl = UrlHelper::generateUrl('seller', 'products');                ?>
                <li>
                    <a href="<?php echo $prodUrl; ?>" class="btn btn-outline-gray btn-icon">
                        <svg class="svg btn-icon-start" width="18" height="18">
                            <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite-actions.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#back">
                            </use>
                        </svg><?php echo Labels::getLabel('LBL_BACK_TO_INVENTORY', $siteLangId); ?>
                    </a>
                </li>
            <?php } ?>


            <?php if (isset($otherButtons) && is_array($otherButtons) && !empty($otherButtons)) {
                $class = 'btn ';
                if (count($otherButtons) == count($otherButtons, COUNT_RECURSIVE)) {
                    if (array_key_exists('html', $otherButtons)) {
                        echo $otherButtons['html'];
                    } else {
                        $class = isset($otherButtons['class']) ? $class . $otherButtons['class'] : $class . 'btn btn-outline-gray btn-icon';
                        $title = isset($otherButtons['title']) ? $otherButtons['title'] : '';
                        $href = isset($otherButtons['href']) ? $otherButtons['href'] : 'javascript:void(0);';
                        $onclick = isset($otherButtons['onclick']) ? 'onclick = ' . $otherButtons['onclick'] : '';
                        $icon = isset($otherButtons['icon']) ? $otherButtons['icon'] : '';
                        $label = isset($otherButtons['label']) ? $otherButtons['label'] : ''; ?>
                        <li>
                            <a href="<?php echo $href; ?>" class="<?php echo $class; ?>" <?php echo $onclick; ?> title="<?php echo $title; ?>" data-bs-toggle='tooltip' data-placement='top'>
                                <?php echo $icon . $label; ?>
                            </a>
                        </li>
                        <?php }
                } else {
                    foreach ($otherButtons as $attr) {
                        if (array_key_exists('html', $attr)) {
                            echo $attr['html'];
                        } else {
                            $btnClass = isset($attr['attr']['class']) ? $class . $attr['attr']['class'] : $class . 'btn btn-outline-gray btn-icon';
                            $title = isset($attr['attr']['title']) ? $attr['attr']['title'] : '';
                            $href = isset($attr['attr']['href']) ? $attr['attr']['href'] : 'javascript:void(0);';
                            $onclick = isset($attr['attr']['onclick']) ? 'onclick = ' . $attr['attr']['onclick'] : '';
                            $icon = isset($attr['icon']) ? $attr['icon'] : '';
                            $label = isset($attr['label']) ? $attr['label'] : ''; ?>
                            <li>
                                <a href="<?php echo $href; ?>" class="<?php echo $btnClass; ?>" <?php echo $onclick; ?> title="<?php echo $title; ?>" data-bs-toggle='tooltip' data-placement='top'>
                                    <?php echo $icon . $label; ?>
                                </a>
                            </li>
            <?php }
                    }
                }
            } ?>

            <?php $newRecordBtn = $newRecordBtn ?? false;
            $newRecordBtnAttrs = $newRecordBtnAttrs ?? [];
            if (isset($newRecordBtn) && true === $newRecordBtn && $canEdit) {
                $href = "javascript:void(0)";
                $onclick = "addNew()";
                $title = Labels::getLabel('BTN_NEW_RECORD', $siteLangId);
                $icon = '<svg class="svg btn-icon-start" width="18" height="18">
                            <use xlink:href="' . CONF_WEBROOT_URL . 'images/retina/sprite-actions.svg' . AttachedFile::setTimeParam(RELEASE_DATE) . '#add">
                            </use>
                        </svg>';
                $label =  $icon . '<span>' . Labels::getLabel('BTN_NEW', $siteLangId) . '</span>';
                if (isset($newRecordBtnAttrs) && 0 < count($newRecordBtnAttrs)) {
                    $href = $newRecordBtnAttrs['attr']['href'] ?? $href;
                    $onclick = $newRecordBtnAttrs['attr']['onclick'] ?? $onclick;
                    $title = $newRecordBtnAttrs['attr']['title'] ?? $title;
                    $label = $newRecordBtnAttrs['label'] ?? $label;
                }
            ?>
                <li>
                    <a href="<?php echo $href; ?>" class="btn btn-outline-gray btn-icon" onclick="<?php echo $onclick; ?>" title="<?php echo $title; ?>" data-bs-toggle='tooltip' data-placement='top'>
                        <?php echo html_entity_decode($label); ?>
                    </a>
                </li>
            <?php } ?>
        </ul>
    </div>
<?php } ?>