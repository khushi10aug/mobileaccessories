 
<?php

defined('SYSTEM_INIT') or die('Invalid Usage.');
$printData = false;
if (!isset($tbody)) {
    $printData = true;
    $tbody = new HtmlElement('tbody', ['class' => 'listingRecordJs']);
}

$serialNo = ($page - 1) * $pageSize + 1;

foreach ($arrListing as $sn => $row) {
    $cls = (($serialNo % 2) == 0) ? 'even' : 'odd';
    $tr = $tbody->appendElement('tr', ['class' => $cls, 'data-row' => $serialNo, 'id' => $row['shipprofile_id']]);
    foreach ($fields as $key => $val) {
        $tdAttr = ('action' == $key) ? ['class' => 'align-right'] : [];
        $td = $tr->appendElement('td', $tdAttr);
        switch ($key) {
            case 'listSerial':
                $td->appendElement('plaintext', $tdAttr, $serialNo);
                break;
            case 'shipprofile_name':
                $badge = '';
                if ($row['shipprofile_default'] == 1) {
                    $badge = ' <span class="badge badge-brand badge-inline badge-pill">' . Labels::getLabel('LBL_DEFAULT', $siteLangId) . '</span>';
                }
                $td->appendElement('plaintext', array(), $row[$key] . $badge, true);
                break;
            case 'rates':
                $str = '';
                $profileId = $row['shipprofile_id'];
                $zoneData = (isset($zones[$profileId])) ? $zones[$profileId] : array();
                if (!empty($zoneData)) {
                    $str = '<ul class="list-tags">';
                    foreach ($zoneData as $data) {
                        $str .= '<li><span>' . $data['shipzone_name'] . '</span></li>';
                    }
                    $str .= '</ul>';
                }
                $td->appendElement('plaintext', array(), $str, true);
                break;
            case 'action':
                $data = [
                    'siteLangId' => $siteLangId,
                    'recordId' => $row['shipprofile_id']
                ];

                if ($canEdit) {
                    $data['editButton'] = false;
                }

                $data['otherButtons'] = [
                    [
                        'attr' => [
                            'href' => UrlHelper::generateUrl('ShippingProfile', 'form', [$row['shipprofile_id']]),
                            'title' => Labels::getLabel('LBL_EDIT', $siteLangId)
                        ],
                        'label' => '<svg class="svg" width="18" height="18">
        <use
            xlink:href="' . CONF_WEBROOT_URL . 'images/retina/sprite-actions.svg' . AttachedFile::setTimeParam(RELEASE_DATE) . '#edit">
        </use>
    </svg>'
                    ]
                ];
                if (!$row['shipprofile_default']) {
                    array_push($data['otherButtons'],[
                        'attr' => [
                            'href' => 'Javascript:void(0)',
                            'onclick' => 'deleteRecord(' . $row['shipprofile_id'] . ')',
                            'title' => Labels::getLabel('LBL_Delete', $siteLangId)
                        ],
                        'label' => '<svg class="svg" width="18" height="18">
                                            <use xlink:href="' . CONF_WEBROOT_URL . 'images/retina/sprite-actions.svg' . AttachedFile::setTimeParam(RELEASE_DATE) . '#delete">
                                            </use>
                                        </svg>'
                    ]);
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

include (CONF_THEME_PATH . '_partial/listing/no-record-found.php');

if ($printData) {
    echo $tbody->getHtml();
}