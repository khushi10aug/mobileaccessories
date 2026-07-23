<?php defined('SYSTEM_INIT') or die('Invalid Usage.'); ?>
<div class="modal-header">
    <h5 class="modal-title">
        <?php echo Labels::getLabel('LBL_LOGIN_DETAILS', $siteLangId); ?>
    </h5>
</div>
<div class="modal-body form-edit">
    <div class="form-edit-body">
        <div class="timeline-v4 appendRowsJs">
            <div class="rowJs">
                <div class="timeline-v4__item-date">
                    <span class="tag">
                        <?php echo HtmlHelper::getTheDay($detail['alh_logged_at'], $siteLangId); ?>
                    </span>
                </div>
                <ul class="timeline-v4__items">
                    <li class="timeline-v4__item">
                        <span class="timeline-v4__item-time"><?php echo date('H:i', strtotime($detail['alh_logged_at'])); ?></span>
                        <div class="timeline-v4__item-desc">
                            <ul class="list-stats list-stats-double">
                                <li class="list-stats-item">
                                    <span class="lable"><?php echo Labels::getLabel('LBL_NAME', $siteLangId); ?></span>
                                    <span class="value"><?php echo htmlspecialchars($detail['alh_admin_name']); ?></span>
                                </li>
                                <li class="list-stats-item">
                                    <span class="lable"><?php echo Labels::getLabel('LBL_USERNAME', $siteLangId); ?></span>
                                    <span class="value"><?php echo htmlspecialchars($detail['alh_admin_username']); ?></span>
                                </li>
                                <li class="list-stats-item">
                                    <span class="lable"><?php echo Labels::getLabel('LBL_EMAIL', $siteLangId); ?></span>
                                    <span class="value"><?php echo htmlspecialchars($detail['alh_admin_email']); ?></span>
                                </li>
                                <li class="list-stats-item">
                                    <span class="lable"><?php echo Labels::getLabel('LBL_IP_ADDRESS', $siteLangId); ?></span>
                                    <span class="value"><?php echo htmlspecialchars($detail['alh_ip']); ?></span>
                                </li>
                                <li class="list-stats-item">
                                    <span class="lable"><?php echo Labels::getLabel('LBL_LOCATIONS', $siteLangId); ?></span>
                                    <span class="value"><?php echo htmlspecialchars($detail['alh_location'] ?: '-'); ?></span>
                                </li>
                                <li class="list-stats-item">
                                    <span class="lable"><?php echo Labels::getLabel('LBL_CITY', $siteLangId); ?></span>
                                    <span class="value"><?php echo htmlspecialchars($detail['alh_city'] ?: '-'); ?></span>
                                </li>
                                <li class="list-stats-item">
                                    <span class="lable"><?php echo Labels::getLabel('LBL_REGION', $siteLangId); ?></span>
                                    <span class="value"><?php echo htmlspecialchars($detail['alh_region'] ?: '-'); ?></span>
                                </li>
                                <li class="list-stats-item">
                                    <span class="lable"><?php echo Labels::getLabel('LBL_COUNTRY', $siteLangId); ?></span>
                                    <span class="value">
                                        <?php
                                        $countryDisplay = trim(($detail['alh_country'] ?? '') . (!empty($detail['alh_country_code']) ? ' (' . $detail['alh_country_code'] . ')' : ''));
                                        echo htmlspecialchars($countryDisplay !== '' ? $countryDisplay : '-');
                                        ?>
                                    </span>
                                </li>
                                <li class="list-stats-item">
                                    <span class="lable"><?php echo Labels::getLabel('LBL_ZIP', $siteLangId); ?></span>
                                    <span class="value"><?php echo htmlspecialchars($detail['alh_zip'] ?: '-'); ?></span>
                                </li>
                                <li class="list-stats-item">
                                    <span class="lable"><?php echo Labels::getLabel('LBL_TIMEZONE', $siteLangId); ?></span>
                                    <span class="value"><?php echo htmlspecialchars($detail['alh_timezone'] ?: '-'); ?></span>
                                </li>
                                <li class="list-stats-item">
                                    <span class="lable"><?php echo Labels::getLabel('LBL_ISP', $siteLangId); ?></span>
                                    <span class="value"><?php echo htmlspecialchars($detail['alh_isp'] ?: '-'); ?></span>
                                </li>
                                <?php if (!empty($detail['alh_latitude']) || !empty($detail['alh_longitude'])) { ?>
                                    <li class="list-stats-item">
                                        <span class="lable"><?php echo Labels::getLabel('LBL_LATITUDE', $siteLangId); ?></span>
                                        <span class="value"><?php echo htmlspecialchars($detail['alh_latitude'] ?: '-'); ?></span>
                                    </li>
                                    <li class="list-stats-item">
                                        <span class="lable"><?php echo Labels::getLabel('LBL_LONGITUDE', $siteLangId); ?></span>
                                        <span class="value"><?php echo htmlspecialchars($detail['alh_longitude'] ?: '-'); ?></span>
                                    </li>
                                <?php } ?>
                                <li class="list-stats-item">
                                    <span class="lable"><?php echo Labels::getLabel('LBL_BROWSER', $siteLangId); ?></span>
                                    <span class="value"><?php echo htmlspecialchars($detail['alh_browser']); ?></span>
                                </li>
                                <li class="list-stats-item">
                                    <span class="lable"><?php echo Labels::getLabel('LBL_PLATFORM', $siteLangId); ?></span>
                                    <span class="value"><?php echo htmlspecialchars($detail['alh_platform']); ?></span>
                                </li>
                                <li class="list-stats-item">
                                    <span class="lable"><?php echo Labels::getLabel('LBL_DEVICE', $siteLangId); ?></span>
                                    <span class="value"><?php echo htmlspecialchars($detail['alh_device']); ?></span>
                                </li>
                                <li class="list-stats-item">
                                    <span class="lable"><?php echo Labels::getLabel('LBL_LOGIN_TYPE', $siteLangId); ?></span>
                                    <span class="value"><?php echo $loginTypes[$detail['alh_login_type']] ?? '-'; ?></span>
                                </li>
                                <li class="list-stats-item">
                                    <span class="lable"><?php echo Labels::getLabel('LBL_LOGGED_AT', $siteLangId); ?></span>
                                    <span class="value"><?php echo FatDate::format($detail['alh_logged_at'], true); ?></span>
                                </li>
                                <li class="list-stats-item">
                                    <span class="lable"><?php echo Labels::getLabel('LBL_LOGOUT_AT', $siteLangId); ?></span>
                                    <span class="value"><?php echo !empty($detail['alh_logout_at']) ? FatDate::format($detail['alh_logout_at'], true) : '-'; ?></span>
                                </li>
                                <li class="list-stats-item">
                                    <span class="lable"><?php echo Labels::getLabel('LBL_TIME_SPENT', $siteLangId); ?></span>
                                    <span class="value"><?php echo htmlspecialchars(AdminLoginHistory::formatSessionDuration($detail, $siteLangId)); ?></span>
                                </li>
                                <?php if (!empty($detail['alh_referer'])) { ?>
                                    <li class="list-stats-item list-stats-item-full">
                                        <span class="lable"><?php echo Labels::getLabel('LBL_REFERER', $siteLangId); ?></span>
                                        <span class="value"><?php echo htmlspecialchars($detail['alh_referer']); ?></span>
                                    </li>
                                <?php } ?>
                                <?php if (!empty($detail['alh_user_agent'])) { ?>
                                    <li class="list-stats-item list-stats-item-full">
                                        <span class="lable"><?php echo Labels::getLabel('LBL_USER_AGENT', $siteLangId); ?></span>
                                        <span class="value"><?php echo htmlspecialchars($detail['alh_user_agent']); ?></span>
                                    </li>
                                <?php } ?>
                                <?php if (!empty($detail['alh_session_id'])) { ?>
                                    <li class="list-stats-item list-stats-item-full">
                                        <span class="lable"><?php echo Labels::getLabel('LBL_SESSION_ID', $siteLangId); ?></span>
                                        <span class="value"><?php echo htmlspecialchars($detail['alh_session_id']); ?></span>
                                    </li>
                                <?php } ?>
                            </ul>
                        </div>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>
