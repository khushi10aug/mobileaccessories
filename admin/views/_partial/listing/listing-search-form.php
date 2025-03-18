<?php defined('SYSTEM_INIT') or die('Invalid Usage');

$keywordPlaceholder = $keywordPlaceholder ?? Labels::getLabel('FRM_SEARCH', $siteLangId);

$frmSearch->setFormTagAttribute('name', 'frmRecordSearch');

if (!$frmSearch->getFormTagAttribute('onsubmit')) {
    $frmSearch->setFormTagAttribute('onsubmit', 'searchRecords(this); return(false);');
}
$frmSearch->setFormTagAttribute('id', 'frmRecordSearch');
$frmSearch->setFormTagAttribute('class', 'form form-search');

$keyWordFld = $frmSearch->getField('keyword');
if (null != $keyWordFld) {
    $keyWordFld->addFieldtagAttribute('class', 'form-control');
    $keyWordFld->addFieldTagAttribute('autocomplete', 'off');
    $keyWordFld->setFieldTagAttribute('placeholder', $keywordPlaceholder);
}

$frmFields = [
    'hidden' => [],
    'advSrchFlds' => [],
];

$i = $x = 0;
$haveExtraFlds = false;
$firstElement = [];
$extraFldCount = 0;
foreach ($frmSearch->getAllFields() as $key => $frmFld) {
    if ('btn_submit' == $frmFld->getName() || 'keyword' == $frmFld->getName()) {
        continue;
    } else if ('hidden' == $frmFld->fldType) {
        $frmFields['hidden'][] = $frmFld->getName();
    } else {
        if (null == $keyWordFld && empty($firstElement) && 'btn_clear' != strtolower($frmFld->getName())) {
            $firstElement = [
                'name' => $frmFld->getName(),
                'caption' => $frmFld->getCaption(),
            ];
        } else {
            if ('btn_clear' != strtolower($frmFld->getName()) && false === $haveExtraFlds) {
                $haveExtraFlds = true;
            }

            if ('btn_clear' != strtolower($frmFld->getName())) {
                $extraFldCount++;
            }

            $frmFields['advSrchFlds'][$x][] = [
                'name' => $frmFld->getName(),
                'caption' => $frmFld->getCaption()
            ];

            $i++;
            if (3 == $i) {
                $x++;
                $i = 0;
            }
        }
    }
}

$advSrchFldsCount = count($frmFields['advSrchFlds']); /* Any addition field except first fields and submit and clear button */
echo $frmSearch->getFormTag();
foreach ($frmFields['hidden'] as $fldName) {
    echo $frmSearch->getFieldHtml($fldName);
}

if (null != $keyWordFld || $haveExtraFlds || !empty($firstElement)) {
?>
    <div class="card-head">
        <div class="card-head-label">
            <div class="row">
                <?php if (0 == $extraFldCount) { ?>
                    <div class="col-md-12">
                        <div class="input-group">
                            <?php if (null != $keyWordFld) {
                                $fld = $frmSearch->getField('keyword');
                                $fld->setFieldtagAttribute('autocomplete', 'off');
                                $fld->setFieldtagAttribute('title', $fld->getFieldtagAttribute('placeholder'));
                                echo $frmSearch->getFieldHtml('keyword');
                            } else {
                                $fld = $frmSearch->getField($firstElement['name']);
                                $fld->setFieldtagAttribute('autocomplete', 'off');

                                $class = (string) $fld->getFieldtagAttribute('class');
                                $class .= (false === strpos($class, 'form-control') ? ' form-control' : '');
                                $class = ltrim($class, ' ');
                                $fld->setFieldtagAttribute('class', $class);

                                if (!$fld->getFieldtagAttribute('placeholder')) {
                                    $fld->setFieldtagAttribute('placeholder', $firstElement['caption']);
                                }
                                $fld->setFieldtagAttribute('title', $fld->getFieldtagAttribute('placeholder'));
                                echo $frmSearch->getFieldHtml($firstElement['name']);
                            }
                            ?>
                            <div class="input-group-append">
                                <?php echo $frmSearch->getFieldHtml('btn_submit'); ?>
                            </div>
                        </div>
                    </div>
                <?php } else if (1 == $extraFldCount) { ?>
                    <div class="col-md-4">
                        <div class="form-group">
                            <?php if (null != $keyWordFld) {
                                $fld = $frmSearch->getField('keyword');
                                $fld->setFieldtagAttribute('autocomplete', 'off');
                                $fld->setFieldtagAttribute('title', $fld->getFieldtagAttribute('placeholder'));
                                echo $frmSearch->getFieldHtml('keyword');
                            } else {
                                $fld = $frmSearch->getField($firstElement['name']);
                                $fld->setFieldtagAttribute('autocomplete', 'off');

                                $class = (string) $fld->getFieldtagAttribute('class');
                                $class .= (false === strpos($class, 'form-control') ? ' form-control' : '');
                                $class = ltrim($class, ' ');
                                $fld->setFieldtagAttribute('class', $class);
                                if (!$fld->getFieldtagAttribute('placeholder')) {
                                    $fld->setFieldtagAttribute('placeholder', $firstElement['caption']);
                                }
                                $fld->setFieldtagAttribute('title', $fld->getFieldtagAttribute('placeholder'));
                                echo $frmSearch->getFieldHtml($firstElement['name']);
                            }
                            ?>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <?php
                            $flds = current($frmFields['advSrchFlds'][0]);
                            $fld = $frmSearch->getField($flds['name']);
                            $class = (string) $fld->getFieldtagAttribute('class');
                            $class .= (false === strpos($class, 'form-control') ? ' form-control' : '');
                            $class = ltrim($class, ' ');
                            $fld->setFieldtagAttribute('class', $class);

                            if (!$fld->getFieldtagAttribute('placeholder')) {
                                $fld->setFieldtagAttribute('placeholder', $flds['caption']);
                            }
                            $fld->setFieldtagAttribute('title', $fld->getFieldtagAttribute('placeholder'));
                            echo $frmSearch->getFieldHtml($flds['name']); ?>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <div class="btn-group">
                                <?php echo $frmSearch->getFieldHtml('btn_submit'); ?>
                                <?php echo $frmSearch->getFieldHtml('btn_clear'); ?>
                            </div>
                        </div>
                    </div>
                <?php } else { ?>
                    <div class="col-md-12">
                        <div class="input-group">
                            <?php if (null != $keyWordFld) {
                                $fld = $frmSearch->getField('keyword');
                                $fld->setFieldtagAttribute('autocomplete', 'off');
                                $fld->setFieldtagAttribute('title', $fld->getFieldtagAttribute('placeholder'));
                                echo $frmSearch->getFieldHtml('keyword');
                            } else {
                                $fld = $frmSearch->getField($firstElement['name']);
                                $fld->setFieldtagAttribute('autocomplete', 'off');
                                $class = (string) $fld->getFieldtagAttribute('class');
                                $class .= (false === strpos($class, 'form-control') ? ' form-control' : '');
                                $class = ltrim($class, ' ');
                                $fld->setFieldtagAttribute('class', $class);

                                if (!$fld->getFieldtagAttribute('placeholder')) {
                                    $fld->setFieldtagAttribute('placeholder', $firstElement['caption']);
                                }
                                $fld->setFieldtagAttribute('title', $fld->getFieldtagAttribute('placeholder'));
                                echo $frmSearch->getFieldHtml($firstElement['name']);
                            }
                            ?>
                            <?php if ($haveExtraFlds && $extraFldCount > 1) { ?>
                                <a class="btn advanced-trigger ms-2 collapsed advSrchToggleJs" data-bs-toggle="collapse" href="#collapseKeyword" aria-expanded="true" aria-controls="collapseKeyword">
                                    <svg class="svg" width="22" height="22">
                                        <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite-actions.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#double-arrow">
                                        </use>
                                    </svg>
                                </a>
                            <?php } ?>
                            <div class="input-group-append advSrchBtnJs">
                                <?php echo $frmSearch->getFieldHtml('btn_submit'); ?>
                            </div>
                        </div>
                    </div>
                <?php } ?>

            </div>
        </div>
        <?php require_once(CONF_THEME_PATH . '_partial/listing/listing-head.php'); ?>
    </div>
    <?php if ($haveExtraFlds && $extraFldCount > 1) { ?>
        <div class="advanced-search collapse advancedSearchJs" id="collapseKeyword">
            <?php
            foreach ($frmFields['advSrchFlds'] as $itr => $fldsGroup) { ?>
                <div class="row">
                    <?php foreach ($fldsGroup as $frmFld) {
                        $fld = $frmSearch->getField($frmFld['name']);
                        if ('btn_clear' == strtolower($frmFld['name'])) {
                            $clearBtn = $fld;
                            $fld = $frmSearch->getField('btn_submit');
                            $fld->attachField($clearBtn);
                        }

                        $class = (string) $fld->getFieldtagAttribute('class');
                        $class .= (false === strpos($class, 'form-control') ? ' form-control' : '');
                        $class = ltrim($class, ' ');

                        $fld->setFieldTagAttribute('class', $class);
                    ?>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="label"><?php echo $fld->getCaption(); ?></label>
                                <?php echo $fld->getHTML(); ?>
                            </div>
                        </div>
                    <?php } ?>
                </div>
            <?php } ?>
            <div class="separator separator-dashed my-4"></div>
        </div>
<?php }
} ?>
</form>
<?php echo $frmSearch->getExternalJS(); ?>