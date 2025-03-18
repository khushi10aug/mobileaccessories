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
            case 'utxn_id':
                $td->appendElement('html', $tdAttr, '<strong>' . Transactions::formatTransactionNumber($row[$key]) . '</strong>', true);
                break;
            case 'user_name':
                $href = "javascript:void(0)";
                $onclick = ($canViewUsers ? 'redirectUser(' . $row['user_id'] . ')' : '');
                $str = $this->includeTemplate('_partial/user/user-info-card.php', [
                    'user' => $row,
                    'extraClass' => 'user-profile-sm',
                    'siteLangId' => $siteLangId,
                    'href' => $href,
                    'onclick' => $onclick,
                    'userTitleClass' => 'text-muted'
                ], false, true);
                $td->appendElement('plaintext', $tdAttr, $str, true);
                break;
            case 'utxn_date':
                $td->appendElement('html', $tdAttr, HtmlHelper::formatDateTime($row[$key], true), true);
                break;
            case 'utxn_credit':
            case 'utxn_debit':
            case 'balance':
                $td->appendElement('plaintext', $tdAttr, CommonHelper::displayMoneyFormat($row[$key]));
                break;
            case 'utxn_comments':
                $body = $row[$key];
                if (strlen((string)$body) > 25) {
                    $htm = strlen((string)$body) > 25 ? substr($body, 0, 25) . "..." : $body;
                    $td->appendElement('plaintext', $tdAttr, '<div class="txt-description">' . $htm . ' <button class="btn btn-view" data-bs-toggle="tooltip" data-placement="top"  data-bs-original-title="' . Labels::getLabel('LBL_VIEW_MORE', $siteLangId) . '" onclick="getDescription(' . $row['utxn_id'] . ')"><svg class="svg" width="10" height="10"><use xlink:href="' . CONF_WEBROOT_URL . 'images/retina/sprite-actions.svg' . AttachedFile::setTimeParam(RELEASE_DATE) . '#more">
                    </use></svg> </button></div>', true);
                } else {
                    $td->appendElement('plaintext', $tdAttr, $body, true);
                }
                break;


                $td->appendElement('plaintext', $tdAttr, Transactions::formatTransactionComments($row[$key]), true);
                break;
            case 'utxn_status':
                $statusHtm = Transactions::getStatusHtml($siteLangId, $row[$key]);
                $td->appendElement('plaintext', $tdAttr, $statusHtm, true);
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
