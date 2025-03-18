<?php defined('SYSTEM_INIT') or die('Invalid Usage.');
$printData = false;
if (!isset($tbody)) {
    $printData = true;
    $tbody = new HtmlElement('tbody', ['class' => 'listingRecordJs']);
}

$serialNo = ($page - 1) * $pageSize + 1;
foreach ($arrListing as $sn => $row) {
    $cls = (($serialNo % 2) == 0) ? 'even' : 'odd';
    $tr = $tbody->appendElement('tr', ['class' => $cls, 'data-row' => $serialNo, 'id' => $row['sreport_id']]);
    foreach ($fields as $key => $val) {
        $tdAttr = ('action' == $key) ? ['class' => 'align-right'] : (('select_all' == $key) ? ['class' => 'col-check'] : []);
        $td = $tr->appendElement('td', $tdAttr);
        switch ($key) {
            case 'select_all':
                $td->appendElement('plaintext', $tdAttr, '<label class="checkbox"><input class="selectItemJs" type="checkbox" name="records[]" value=' . $row['sreport_id'] . '><i class="input-helper"></i></label>', true);
                break;
            case 'listSerial':
                $td->appendElement('plaintext', $tdAttr, $serialNo);
                break;
            case 'sreport_added_on':
                $td->appendElement('plaintext', $tdAttr, HtmlHelper::formatDateTime($row[$key], true), true);
                break;
            case 'sreport_message':                
                $body = $row[$key];
                if (strlen((string)$body) > 25) {
                    $htm = strlen((string)$body) > 25 ? substr($body, 0, 25) . "..." : $body;
                    $td->appendElement('plaintext', $tdAttr, '<div class="txt-description">' . $htm . ' <button class="btn btn-view" data-bs-toggle="tooltip" data-placement="top"  data-bs-original-title="' . Labels::getLabel('LBL_VIEW_MORE', $siteLangId) . '" onclick="getComment(' . $row['sreport_id'] . ')"><svg class="svg" width="10" height="10"><use xlink:href="' . CONF_WEBROOT_URL . 'images/retina/sprite-actions.svg' . AttachedFile::setTimeParam(RELEASE_DATE) . '#more">
                    </use></svg> </button></div><span class="hide" id="commentId-'.$row['sreport_id'].'">'.nl2br($row[$key]).'</span>', true);
                } else {
                    $td->appendElement('plaintext', $tdAttr, $body, true);
                }
                break;
                $td->appendElement('plaintext', $tdAttr, Transactions::formatTransactionComments($row[$key]), true);
                break;
            default:
                $td->appendElement('plaintext', array(), $row[$key], true);
                break;
        }
    }
    $serialNo++;
}
include (CONF_THEME_PATH . '_partial/listing/no-record-found.php');

if ($printData) {
    echo $tbody->getHtml();
}
