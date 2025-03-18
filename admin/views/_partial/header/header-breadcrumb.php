<?php defined('SYSTEM_INIT') or die('Invalid usage'); ?>
<div class="breadcrumb-wrap">
    <ul class="breadcrumb ">
        <li class="breadcrumb-item">
            <a href="<?php echo UrlHelper::generateUrl('') ?>">
                <?php echo labels::getLabel('LBL_Home', (isset($langId)  && 0 < $langId ? $langId : $siteLangId)); ?>
            </a>
        </li>
        <?php
        if (!empty($this->variables['nodes'])) {
            foreach ($this->variables['nodes'] as $nodes) {
        ?>
                <?php if (!empty($nodes['href'])) { ?>
                    <li class="breadcrumb-item">
                        <a href="<?php echo $nodes['href']; ?>" <?php echo (!empty($nodes['other'])) ? $nodes['other'] : ''; ?>>
                            <?php echo $nodes['title'] ?? ''; ?>
                        </a>
                    </li>
                <?php } else { ?>
                    <li class="breadcrumb-item">
                        <?php echo $nodes['title'] ?? ''; ?>
                    </li>
        <?php
                }
            }
        }
        ?>
    </ul>
    <?php
    $newRecordBtn = $newRecordBtn ?? false;
    $headerHtmlContent = isset($headerHtmlContent) ? $headerHtmlContent : false;

    if ($headerHtmlContent && $newRecordBtn) {
        echo '<div class="btn-group">';
    }


    if ($headerHtmlContent && !$newRecordBtn) {
        echo $headerHtmlContent;
    }

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
        <a href="<?php echo $href; ?>" class="btn btn-icon btn-outline-brand" onclick="<?php echo $onclick; ?>" title="<?php echo $title; ?>" data-bs-toggle='tooltip' data-placement='top'>
            <?php echo $label; ?>
        </a>
    <?php
    }

    if ($headerHtmlContent && $newRecordBtn) {
        echo $headerHtmlContent;
    }
    if ($headerHtmlContent && $newRecordBtn) {
        echo '</div>';
    }
    ?>
</div>