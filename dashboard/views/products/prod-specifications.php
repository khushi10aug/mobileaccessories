<?php defined('SYSTEM_INIT') or die('Invalid Usage.');

$arr_flds = array(
    'prodspec_name' => Labels::getLabel('FRM_SPECIFICATION_NAME', $langId),
    'prodspec_value' => Labels::getLabel('FRM_SPECIFICATION_VALUE', $langId),
    'prodspec_group' => Labels::getLabel('FRM_SPECIFICATION_GROUP', $langId),
    'action' => Labels::getLabel('LBL_ACTION_BUTTONS', $langId)
);

$tbl = new HtmlElement('table', array('class' => 'table listingTableJs'));
$th = $tbl->appendElement('thead', ['class' => 'tableHeadJs'])->appendElement('tr');
foreach ($arr_flds as $key => $val) {
    if ($key == 'action') {
        $e = $th->appendElement('th', array('class' => 'align-right', 'width' => '10%'), $val);
    } else {
        $e = $th->appendElement('th', array('width' => '30%'), $val);
    }
}
$tbody = $tbl->appendElement('tbody');
$count = 0;
foreach ($productSpecifications as  $specification) {
    $prodSpecId = $specification['prodspec_id'];
    $tr = $tbody->appendElement('tr', ['data-id' => $prodSpecId]);
    foreach ($arr_flds as $key => $val) {
        $tdAttr = ('action' == $key) ? ['class' => 'align-right'] : ['class' => str_replace(ProdSpecification::DB_TBL_PREFIX, '', $key) . "Js text-break"];
        $td = $tr->appendElement('td', $tdAttr);
        switch ($key) {
            case 'action':
                $ul = new HtmlElement("ul", array('class' => 'actions'));
                $li = $ul->appendElement('li');
                $li->appendElement(
                    'input',
                    [
                        'name' => 'specifications[' . $count . '][id]',
                        'type' => 'hidden',
                        'value' => $specification['prodspec_id'],
                        'data-fatreq' => json_encode(['required' => false]),
                    ]
                );
                $li->appendElement(
                    'a',
                    [
                        'href' => 'javascript:void(0)',
                        'title' => Labels::getLabel('BTN_EDIT', $langId),
                        'onclick' => "editProdSpec(this)"
                    ],
                    '<svg class="svg" width="18" height="18">
                                <use
                                    xlink:href="' . CONF_WEBROOT_URL . 'images/retina/sprite-actions.svg' . AttachedFile::setTimeParam(RELEASE_DATE) . '#edit">
                                </use>
                            </svg>',
                    true
                );

                $li = $ul->appendElement('li');
                $li->appendElement(
                    'a',
                    [
                        'href' => 'javascript:void(0)',
                        'title' => Labels::getLabel('BTN_DELETE', $langId),
                        'onclick' => "deleteProdSpec(this)",
                        'data-id' => $prodSpecId,
                        'data-lang-id' => $langId,
                    ],
                    '<svg class="svg" width="18" height="18">
                                <use
                                    xlink:href="' . CONF_WEBROOT_URL . 'images/retina/sprite-actions.svg' . AttachedFile::setTimeParam(RELEASE_DATE) . '#delete">
                                </use>
                            </svg>',
                    true
                );
                $td->appendElement('plaintext', $tdAttr, $ul->getHtml(), true);

                break;
            default:
                $input = new HtmlElement(
                    'input',
                    [
                        'name' => 'specifications[' . $count . '][' . str_replace(ProdSpecification::DB_TBL_PREFIX, '', $key) . ']',
                        'type' => 'hidden',
                        'value' => html_entity_decode($specification[$key], ENT_QUOTES, 'utf-8'),
                        'data-fatreq' => json_encode(['required' => false]),
                    ]
                );
                $td->appendElement('plaintext', $tdAttr, $specification[$key] . $input->getHtml(), true);
                break;
        }
    }
    $count++;
}
?>
<div class="js-scrollable table-wrap table-responsive">
    <?php
    echo $tbl->getHtml();
    ?>
</div>