<?php defined('SYSTEM_INIT') or die('Invalid Usage.');
$printData = false;
if (!isset($tbody)) {
    $printData = true;
    $tbody = new HtmlElement('tbody', ['class' => 'listingRecordJs']);
}

$serialNo = ($page - 1) * $pageSize + 1;
foreach ($arrListing as $sn => $row) {
    $cls = (($serialNo % 2) == 0) ? 'even' : 'odd';
    $tr = $tbody->appendElement('tr', ['class' => $cls, 'data-row' => $serialNo]);
    foreach ($fields as $key => $val) {
        $tdAttr = ('action' == $key) ? ['class' => 'align-right'] : [];
        $td = $tr->appendElement('td', $tdAttr);
        switch ($key) {
            case 'listSerial':
                $td->appendElement('plaintext', $tdAttr, $serialNo);
                break;
            case 'user_name':
                $href = "javascript:void(0)";
                $onclick = ($canViewUsers ? 'redirectUser(' . $row['user_id'] . ')' : '');
                $str = $this->includeTemplate('_partial/user/user-info-card.php', [
                    'user' => $row,
                    'siteLangId' => $siteLangId,
                    'href' => $href,
                    'onclick' => $onclick,
                ], false, true);
                $td->appendElement('plaintext', $tdAttr, '<div class="user-profile">' . $str . '</div>', true);
                break;
            case 'urp_date_added':
                $td->appendElement('plaintext', $tdAttr, HtmlHelper::formatDateTime($row[$key], true), true);
                break;
            case 'urp_date_expiry':
                $td->appendElement('plaintext', $tdAttr, ($row[$key] != '0000-00-00'  ? HtmlHelper::formatDateTime($row[$key]) : Labels::getLabel("LBL_NA", $siteLangId)), true);
                break;
            case 'action':
                $data = [
                    'siteLangId' => $siteLangId,
                    'recordId' => $row['urp_id'],
                ];

                $data['otherButtons'][] = [
                    'attr' => [
                        'href' => 'javascript:void(0);',
                        'title' => Labels::getLabel('MSG_CLICK_TO_VIEW_COMMENTS', $siteLangId),
                        'onclick' => 'getComments(' . $row['urp_id'] . ')'
                    ],
                    'label' => '<svg class="svg" width="18" height="18">
                                    <use xlink:href="' . CONF_WEBROOT_URL . 'images/retina/sprite-actions.svg' . AttachedFile::setTimeParam(RELEASE_DATE) . '#comment">
                                    </use>
                                </svg>',
                ];

                if ($canEdit && 0 < $row['urp_points'] && (0 == $row['urp_date_expiry'] ||  strtotime('now') < strtotime($row['urp_date_expiry'])) && !UserRewards::isRewardPointUsed($row['urp_id'])) {
                    $data['otherButtons'][] = [
                        'attr' => [
                            'href' => 'javascript:void(0);',
                            'title' => Labels::getLabel('LBL_REVERT_REWARD', $siteLangId),
                            'onclick' => 'deleteRecord(' . $row['urp_id'] . ')'
                        ],
                        'label' => '<svg class="svg" width="18" height="18">
                                        <use xlink:href="' . CONF_WEBROOT_URL . 'images/retina/sprite-actions.svg' . AttachedFile::setTimeParam(RELEASE_DATE) . '#counterclockwise">
                                        </use>
                                    </svg>',
                    ];
                }

                $actionItems = $this->includeTemplate('_partial/listing/listing-action-buttons.php', $data, false, true);
                $td->appendElement('plaintext', $tdAttr, $actionItems, true);
                break;
            default:
                $td->appendElement('plaintext', $tdAttr, $row[$key], true);
                break;
        }
    }
    $serialNo++;
}

include(CONF_THEME_PATH . '_partial/listing/no-record-found.php');

if ($printData) {
    echo $tbody->getHtml();
}
?>

<script>
    var confirmRewardRevertLbl = '<?php echo Labels::getLabel('LBL_DO_YOU_WANT_TO_REVERSE', $siteLangId); ?>';
</script>