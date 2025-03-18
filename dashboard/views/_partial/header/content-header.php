<?php defined('SYSTEM_INIT') or die('Invalid Usage.');

$headingBackButton = $headingBackButton ?? false;
?>

<div class="content-header">
    <div class="content-header-title">
        <?php
        if (isset($headingLabel)) { ?>
            <h2>
                <?php if (false !== $headingBackButton) {
                    $href = $headingBackButton['href'] ?? 'javascript:void(0);';
                    $onclick = $headingBackButton['onclick'] ?? 'history.back()';
                ?>
                    <a class="btn btn-back" href="<?php echo $href; ?>" onclick="<?php echo $onclick; ?>">
                        <svg class="svg" width="24" height="24">
                            <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite-actions.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#back">
                            </use>
                        </svg>
                    </a>
                <?php } ?>

                <?php echo $headingLabel; ?>
            </h2>
        <?php }

        $this->includeTemplate('_partial/header/header-breadcrumb.php', $this->variables, false); ?>
    </div>
    <?php $this->includeTemplate('_partial/header/content-header-buttons.php', $this->variables, false); ?>
</div>