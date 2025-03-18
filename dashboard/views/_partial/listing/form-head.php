<?php defined('SYSTEM_INIT') or die('Invalid Usage.');

$formBackButtonAttr = $formBackButtonAttr ?? false;
$activeGentab = !empty($activeGentab) ? 'active' : '';
$activeLangtab = !empty($activeLangtab) ? 'active' : '';
$disabled = !empty($disabled) ? ' disabled' : '';
$formTitle = !empty($formTitle) ? $formTitle : Labels::getLabel('LBL_SETUP', $siteLangId);
$formSubTitle = !empty($formSubTitle) ? $formSubTitle : '';
$includeTabs = $includeTabs ?? true;
$displayLangTab = $displayLangTab ?? true;
$languages = $languages ?? [];
unset($languages[CommonHelper::getDefaultFormLangId()]); ?>
<div class="modal-header">
    <h5 class="modal-title">
        <?php if (false !== $formBackButtonAttr) {
            $onclick = $formBackButtonAttr['onclick'] ?? '';
        ?>
            <a class="btn btn-back" href="javascript:void(0);" onclick="<?php echo $onclick; ?>">
                <svg class="svg" width="24" height="24">
                    <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite-actions.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#back">
                    </use>
                </svg>
            </a>
        <?php } ?>
        <?php echo $formTitle; ?>
        <?php if (!empty($formSubTitle)) { ?>
            <span class="text-muted"><?php echo $formSubTitle; ?></span>
        <?php } ?>
    </h5>
</div>
<div class="modal-body form-edit">
    <!-- Closing tag must be added inside the files who include this file. -->
    <?php
    if ($includeTabs && (0 < count($languages) || isset($otherButtons))) { ?>
        <div class="form-edit-head">
            <nav class="nav nav-tabs navTabsJs">
                <?php
                if (!isset($generalTab)) {
                    $generalTab = [
                        'attr' => [
                            'href' => 'javascript:void(0);',
                            'onclick' => "editRecord(" . $recordId . ");",
                            'title' => Labels::getLabel('LBL_GENERAL', $siteLangId)
                        ],
                        'label' => Labels::getLabel('LBL_GENERAL', $siteLangId),
                        'isActive' => $activeGentab
                    ];
                }
                $generalTabAttr = $generalTab['attr'] ?? [];
                $label = $generalTab['label'] ?? Labels::getLabel('LBL_GENERAL', $siteLangId);
                $isActive = $generalTab['isActive'] ?? $activeGentab;
                $active = $isActive ? 'active' : '';

                $href = $generalTabAttr['href'] ?? 'javascript:void(0);';
                $onclick = $generalTabAttr['onclick'] ?? '';
                $title = $generalTabAttr['title'] ?? Labels::getLabel('LBL_GENERAL', $siteLangId);

                ?>
                <a class="nav-link <?php echo $active; ?>" href="<?php echo $href; ?>" <?php echo !empty($onclick) ? "onclick='" . $onclick . "'" : ""; ?> title="<?php echo $title; ?>">
                    <?php echo $label; ?>
                </a>
                <?php if (0 < count($languages) && true === $displayLangTab) { ?>
                    <a class="nav-link <?php echo $activeLangtab . $disabled; ?>" href="javascript:void(0);" <?php echo (0 < $recordId) ? "onclick='editLangData(" . $recordId . "," . array_key_first($languages) . ");'" : ""; ?> title="<?php echo Labels::getLabel('LBL_LANGUAGE_DATA', $siteLangId); ?>">
                        <?php echo Labels::getLabel('LBL_LANGUAGE_DATA', $siteLangId); ?>
                    </a>
                <?php } ?>
                <?php
                /* 
                *    EXAMPLE : $otherButtons = [
                *        [
                *           'attr' => [
                *                'href' => 'javascript:void(0)',
                *                'onclick' => '',
                *                'title' => 'TITLE'
                *            ],
                *            'label' => 'LABEL',
                *            'isActive' => true/false
                *        ]
                *    ] 
                */
                if (isset($otherButtons) && is_array($otherButtons)) {
                    foreach ($otherButtons as $link) {
                        $attr = isset($link['attr']) ? $link['attr'] : [];
                        $label = isset($link['label']) ? $link['label'] : '';
                        $isActive = isset($link['isActive']) ? $link['isActive'] : false;
                        $active = $isActive ? 'active' : '';
                        $othetBtnsDisabled = (isset($link['isDisabled']) && false === $link['isDisabled']) || true === $isActive ? '' : $disabled;

                        $href = !empty($attr) ? $attr['href'] : 'javascript:void(0);';
                        $onclick = !empty($attr) ? $attr['onclick'] : '';
                        $title = !empty($attr) ? $attr['title'] : '';
                ?>
                        <a class="nav-link <?php echo $active . $othetBtnsDisabled; ?>" href="<?php echo $href; ?>" <?php echo !empty($onclick) ? "onclick='" . $onclick . "'" : ""; ?> title="<?php echo $title; ?>">
                            <?php echo $label; ?>
                        </a>
                <?php }
                }
                ?>
            </nav>
        </div>
    <?php } ?>
    <!-- Todo need to refine logic [override lisiting page variable name {controllerName}so we can able to save/edit popup of other controller like brands]  -->
    <script>
        var controllerName = '<?php echo LibHelper::getControllerName(); ?>';
    </script>