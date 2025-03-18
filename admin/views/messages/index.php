<?php defined('SYSTEM_INIT') or die('Invalid Usage.'); ?>
<main class="main">
    <div class="container">
        <?php $this->includeTemplate('_partial/header/header-breadcrumb.php', [], false); ?>
        <?php if (empty($arrListing)) { ?>
            <div class="row">
                <div class="col-md-12">
                    <div class="card card-stretch mb-0">
                        <div class="card-body">
                            <div class="not-found">
                                <img width="100" src="<?php echo CONF_WEBROOT_URL; ?>images/retina/no-data-cuate.svg"
                                    alt="">
                                <h3><?php echo Labels::getLabel('MSG_SORRY,_NO_MATCHING_RESULT_FOUND'); ?></h3>
                                <p><?php echo Labels::getLabel('MSG_TRY_CHECKING_YOUR_SPELLING_OR_USER_MORE_GENERAL_TERMS'); ?>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php } else { ?>
            <div class="communication">
                <div class="communication-nav">
                    <div class="communication-search">
                        <?php
                        $frmSearch->setFormTagAttribute('class', 'form');
                        $frmSearch->setFormTagAttribute('onsubmit', 'searchRecords(this); return(false);');

                        $fld = $frmSearch->getField('message_by');
                        $fld->addFieldtagAttribute('id', 'searchFrmBuyerIdJs');
                        $fld->addFieldtagAttribute('data-dropdownParent-id', 'messageDropMenu');

                        $fld = $frmSearch->getField('keyword');
                        $fld->addFieldtagAttribute('class', 'form-control omni-search');
                        $fld->addFieldtagAttribute('autocomplete', 'off');

                        $fld = $frmSearch->getField('message_to');
                        $fld->addFieldtagAttribute('id', 'searchFrmSellerIdJs');
                        $fld->addFieldtagAttribute('data-dropdownParent-id', 'messageDropMenu');

                        echo $frmSearch->getFormTag();
                        echo $frmSearch->getFieldHtml('page');
                        ?>
                        <div class="d-flex align-items-center">
                            <?php echo $frmSearch->getFieldHtml('keyword'); ?>
                            <div class="dropdown">
                                <button type="button" class="btn dropdown-toggle no-after" data-bs-toggle="dropdown"
                                    data-bs-auto-close="outside">
                                    <span class="icon">
                                        <svg class="svg" width="20" height="20">
                                            <use
                                                xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite.yokart.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#icon-filters">
                                            </use>
                                        </svg>
                                    </span>
                                </button>
                                <div class="dropdown-menu dropDownMenuBlockClose dropdown-menu-right dropdown-menu-anim communication-filter"
                                    id="messageDropMenu">
                                    <div class="form-group">
                                        <label class="label">
                                            <?php
                                            $fld = $frmSearch->getField('message_by');
                                            echo $fld->getCaption();
                                            ;
                                            ?>
                                        </label>
                                        <?php echo $frmSearch->getFieldHtml('message_by'); ?>
                                    </div>
                                    <div class="form-group">
                                        <label class="label">
                                            <?php
                                            $fld = $frmSearch->getField('message_to');
                                            echo $fld->getCaption();
                                            ;
                                            ?>
                                        </label>
                                        <?php echo $frmSearch->getFieldHtml('message_to'); ?>
                                    </div>
                                    <div class="form-group">
                                        <label class="label">
                                            <?php
                                            $fld = $frmSearch->getField('date_from');
                                            echo $fld->getCaption();
                                            ;
                                            ?>
                                        </label>
                                        <?php echo $frmSearch->getFieldHtml('date_from'); ?>
                                    </div>
                                    <div class="form-group">
                                        <label class="label">
                                            <?php
                                            $fld = $frmSearch->getField('date_to');
                                            echo $fld->getCaption();
                                            ;
                                            ?>
                                        </label>
                                        <?php echo $frmSearch->getFieldHtml('date_to'); ?>
                                    </div>
                                    <?php echo $frmSearch->getFieldHtml('btn_submit'); ?>
                                    <?php echo $frmSearch->getFieldHtml('btn_clear'); ?>
                                </div>
                            </div>
                        </div>
                        </form>
                    </div>

                    <?php
                    $activeIndex = 0;
                    require_once (CONF_THEME_PATH . 'messages/search.php');
                    ?>


                </div>
                <?php
                $doNotshowMessages = true;
                $threadListing = [current($arrListing)];
                require_once (CONF_THEME_PATH . 'messages/view-thread.php');
                ?>
            </div>
        <?php } ?>
    </div>
</main>