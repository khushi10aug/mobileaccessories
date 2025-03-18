<?php if (0 < count($arrListing)) { ?>

    <ul class="tax-rules">
        <?php foreach ($arrListing as $sn => $row) {
            $ruleSpecificCombinedData = $combinedData[$row['taxrule_id']] ?? [];

        ?> <li>
                <div class="d-flex justify-content-between">

                    <ul class="list-stats">
                        <li class="list-stats-item">
                            <span class="lable"><?php echo Labels::getLabel('LBL_Rule', $siteLangId); ?>:</span>
                            <span class="value"> <?php echo $row['taxrule_name']; ?></span>
                        </li>
                        <li class="list-stats-item">
                            <span class="lable">
                                <?php echo Labels::getLabel('LBL_Tax_Rate(%)', $siteLangId); ?>:</span>
                            <span class="value"> <?php echo !empty($row['user_rule_rate']) ? "<del>" . CommonHelper::numberFormat($row['trr_rate']) . "</del>" : CommonHelper::numberFormat($row['trr_rate']); ?></del>&nbsp; <?php echo (!empty($row['user_rule_rate']) ? CommonHelper::numberFormat($row['user_rule_rate']) : ''); ?></span>
                        </li>
                    </ul>

                    <button class="btn btn-outline-gray btn-icon" type="button" onclick="editRule(<?php echo $row['taxrule_id']; ?>)" title="Edit">
                        <svg class="svg btn-icon-start" width="14" height="14">
                            <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite-actions.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#edit">
                            </use>
                        </svg>
                        <span><?php echo Labels::getLabel('LBL_Edit', $siteLangId); ?></span>

                    </button>

                </div>
                <div class="divider"></div>
                <ul class="list-stats list-stats-double">
                    <?php if (!empty($ruleSpecificCombinedData) && $row['taxstr_is_combined'] > 0) { ?>
                        <li class="list-stats-item">
                            <span class="lable"><?php echo Labels::getLabel('LBL_TAX_STRUCTURE_NAME', $siteLangId); ?>: </span>
                            <span class="value"><?php echo $row['taxstr_name']; ?></span>
                        </li>
                        <?php
                        foreach ($ruleSpecificCombinedData as $comData) { ?>
                            <li class="list-stats-item">
                                <span class="lable"><?php echo $comData['taxstr_name']; ?>: </span>
                                <span class="value"> <?php echo !empty($comData['user_rate']) ? "<del>" . $comData['taxruledet_rate'] . "</del>" : $comData['taxruledet_rate']; ?></del>&nbsp; <?php echo $comData['user_rate']; ?></span>

                            </li>
                        <?php }
                    } else {
                        ?>
                        <li class="list-stats-item">
                            <span class="lable"><?php echo Labels::getLabel('LBL_TAX_STRUCTURE_NAME', $siteLangId); ?>: </span>
                            <span class="value"><?php echo $row['taxstr_name']; ?></span>

                        </li>
                    <?php } ?>
                    <li class="list-stats-item">

                        <span class="lable"><?php echo Labels::getLabel('LBL_FROM_COUNTRY', $siteLangId); ?>:
                        </span>
                        <span class="value"><?php echo $row['from_country'] ?? Labels::getLabel('LBL_Rest_of_the_world', $siteLangId); ?></span>


                    </li>
                    <li class="list-stats-item">
                        <span class="lable"><?php echo Labels::getLabel('LBL_FROM_STATES', $siteLangId); ?>:
                        </span>
                        <span class="value"> <?php echo $row['from_state'] ?? Labels::getLabel('LBL_ALL_STATES', $siteLangId); ?></span>


                    </li>
                    <li class="list-stats-item">
                        <span class="lable"><?php echo Labels::getLabel('LBL_TO_COUNTRY', $siteLangId); ?>:
                        </span>
                        <span class="value"><?php echo $row['to_country'] ?? Labels::getLabel('LBL_Rest_of_the_world', $siteLangId); ?></span>


                    </li>
                    <li class="list-stats-item">
                        <span class="lable"><?php echo Labels::getLabel('LBL_TO_STATES', $siteLangId); ?>:
                        </span>
                        <span class="value"><?php echo TaxRule::getTypeOptions($siteLangId)[$row['taxruleloc_type']]; ?><br>
                            <?php echo $row['to_state'] ?>
                        </span>


                    </li>
                </ul>
            </li>
        <?php } ?>
    </ul>
<?php
} else {
    $message = Labels::getLabel('LBL_No_Records_Found', $siteLangId);
    $this->includeTemplate('_partial/no-record-found.php', array('siteLangId' => $siteLangId, 'message' => $message));
}

$postedData['page'] = $page;
echo FatUtility::createHiddenFormFromData($postedData, array('name' => 'frmSearchPaging'));

$pagingArr = array('pageCount' => $pageCount, 'page' => $page, 'recordCount' => $recordCount);
$this->includeTemplate('_partial/pagination.php', $pagingArr, false);
