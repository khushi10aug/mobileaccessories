<?php defined('SYSTEM_INIT') or die('Invalid Usage.'); ?>
<?php if (!empty($zones)) { ?>
    <ul class="list-group list-shipping">
        <?php foreach ($zones as $zone) {
            $zoneId = $zone['shipzone_id'];
            $locationData = (isset($zoneLocations[$zoneId])) ? $zoneLocations[$zoneId] : array();
            $countryNames = array_column($locationData, 'country_name');
            $countryNames = array_unique($countryNames);
            $zoneIds = array_column($locationData, 'shiploc_zone_id');
            if (in_array(-1, $zoneIds)) {
                $countryNames = array(Labels::getLabel("LBL_REST_OF_THE_WORLD", $siteLangId));
            }
            $shipProZoneId = $zone['shipprozone_id'];
            $shipRates = (isset($shipRatesData[$shipProZoneId])) ? $shipRatesData[$shipProZoneId] : array(); ?>
            <li class="list-group-item zoneRates-js">
                <div class="row justify-content-between">
                    <div class="col">
                        <div class="shipping-states">
                            <span class="box-icon"><i class="fa fa-globe"> </i></span>
                            <div class="detail">
                                <h6><?php echo $zone['shipzone_name'] ?></h6>
                                <p><span><?php echo implode(', ', $countryNames); ?></span></p>
                            </div>
                        </div>
                        <div class="row no-gutters">
                            <div class="col"></div>
                        </div>
                    </div>
                    <div class="col-auto">
                        <?php if ($canEdit) { ?>
                            <ul class="actions">
                                <li>
                                    <a href="javascript:void(0);" onclick="zoneForm(<?php echo $profile_id; ?>, <?php echo $zone['shipzone_id'] ?>)" title="<?php echo Labels::getLabel("LBL_Edit_Zone", $siteLangId); ?>"><i class="icn">
                                            <svg class="svg" width="18" height="18">
                                                <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite-actions.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#edit">
                                                </use>
                                            </svg>
                                        </i>

                                    </a>
                                </li>
                                <li>
                                    <a href="javascript:void(0);" onclick="deleteZone(<?php echo $shipProZoneId ?>)" title="<?php echo Labels::getLabel("LBL_Delete_Zone", $siteLangId); ?>"><i class="icn">
                                            <svg class="svg" width="18" height="18">
                                                <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite-actions.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#delete">
                                                </use>
                                            </svg>
                                        </i></a>
                                </li>
                                <li>
                                    <a href="javascript:void(0)" title="<?php echo Labels::getLabel("LBL_Add_Rates", $siteLangId); ?>" onclick="addEditShipRates(<?php echo $shipProZoneId; ?>, 0);"><i class="icn">
                                            <svg class="svg" width="18" height="18">
                                                <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite-actions.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#add">
                                                </use>
                                            </svg>
                                        </i></a>
                                </li>
                            </ul>
                        <?php } ?>
                    </div>
                </div>
                <?php if (!empty($shipRates)) { ?>
                    <div class="js-scrollable table-wrap table-responsive">
                        <table class="table table-justified table-rates mt-3">
                            <thead>
                                <tr>
                                    <th><?php echo Labels::getLabel("LBL_Rate_Name", $siteLangId); ?>
                                    </th>
                                    <th><?php echo Labels::getLabel("LBL_Conditions", $siteLangId); ?>
                                    </th>
                                    <th><?php echo Labels::getLabel("LBL_Cost", $siteLangId); ?>
                                    </th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($shipRates as $rate) { ?>
                                    <tr>
                                        <td><?php echo $rate['shiprate_rate_name']; ?>
                                        </td>
                                        <td>
                                            <?php if ($rate['shiprate_condition_type'] > 0) {
                                                if ($rate['shiprate_condition_type'] == ShippingRate::CONDITION_TYPE_PRICE) {
                                                    echo CommonHelper::displayMoneyFormat($rate['shiprate_min_val']) . ' - ' . CommonHelper::displayMoneyFormat($rate['shiprate_max_val']);
                                                } else {
                                                    echo $rate['shiprate_min_val'] . ' - ' . $rate['shiprate_max_val'];
                                                }
                                            } else {
                                                echo '—';
                                            }
                                            ?>
                                        </td>
                                        <td><?php echo CommonHelper::displayMoneyFormat($rate['shiprate_cost']); ?>
                                        </td>
                                        <td>
                                            <?php if ($canEdit) { ?>
                                                <ul class="actions">
                                                    <li>
                                                        <a href="javascript:void(0);" onclick="addEditShipRates(<?php echo $rate['shiprate_shipprozone_id'] ?>, <?php echo $rate['shiprate_id'] ?>);" title="<?php echo Labels::getLabel("LBL_Edit", $siteLangId); ?>"><i class="icn">
                                                                <svg class="svg" width="18" height="18">
                                                                    <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite-actions.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#edit">
                                                                    </use>
                                                                </svg>
                                                            </i></a>
                                                    </li>
                                                    <li>
                                                        <a href="javascript:void(0);" onclick="deleteRate(<?php echo $rate['shiprate_id'] ?>)" title="<?php echo Labels::getLabel("LBL_Delete", $siteLangId); ?>"><i class="icn">
                                                                <svg class="svg" width="18" height="18">
                                                                    <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite-actions.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#delete">
                                                                    </use>
                                                                </svg>
                                                            </i></a>
                                                    </li>
                                                </ul>
                                            <?php } ?>
                                        </td>
                                    </tr>
                                <?php } ?>
                            </tbody>
                        </table>
                    </div>
                <?php } else {
                    $message = Labels::getLabel('LBL_No_Records_Found', $siteLangId);
                    $this->includeTemplate('_partial/no-record-found.php', array('siteLangId' => $siteLangId, 'message' => $message));
                } ?>
            </li>
        <?php } ?>
    </ul>
<?php } else {
    $message = Labels::getLabel('LBL_No_Records_Found', $siteLangId);
    $this->includeTemplate('_partial/no-record-found.php', array('siteLangId' => $siteLangId, 'message' => $message));
}
