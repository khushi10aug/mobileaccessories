<?php defined('SYSTEM_INIT') or die('Invalid Usage.');
$havingOtherTable = [
    MetaTag::META_GROUP_PRODUCT_DETAIL,
    MetaTag::META_GROUP_SHOP_DETAIL,
    MetaTag::META_GROUP_CMS_PAGE,
    MetaTag::META_GROUP_BRAND_DETAIL,
    MetaTag::META_GROUP_CATEGORY_DETAIL,
    MetaTag::META_GROUP_BLOG_CATEGORY,
    MetaTag::META_GROUP_BLOG_POST,
];

require_once(CONF_THEME_PATH . '_partial/listing/listing-column-head.php');

$serialNo = ($page - 1) * $pageSize + 1;
foreach ($arrListing as $sn => $row) {
    $cls = (($serialNo % 2) == 0) ? 'even' : 'odd';
    $tr = $tbody->appendElement('tr', ['class' => $cls, 'data-row' => $serialNo, 'id' => $row['meta_id'] ?? 0]);
    $metaId = FatUtility::int($row['meta_id']);
    $metaRecordId = FatUtility::int($row['meta_record_id']);
    foreach ($fields as $key => $val) {
        $tdAttr = ('action' == $key) ? ['class' => 'align-right'] : [];
        $td = $tr->appendElement('td', $tdAttr);
        switch ($key) {
            case 'listSerial':
                $td->appendElement('plaintext', $tdAttr, $serialNo);
                break;
            case 'action':
                $data = [
                    'siteLangId' => $siteLangId,
                    'recordId' => $metaId
                ];

                if ($canEdit) {
                    $data['otherButtons'] = [
                        [
                            'attr' => [
                                'href' => 'javascript:void(0)',
                                'onclick' => $metaType == MetaTag::META_GROUP_ADVANCED ? "editMetaTagForm(" . $metaId . ",'" . $metaType . "'," . $metaRecordId . ")" : "editMetaTagLangForm(" . $metaId . "," . CommonHelper::getDefaultFormLangId() . ",'" . $metaType . "'," . $metaRecordId . ")",
                                'title' => Labels::getLabel('BTN_EDIT', $siteLangId)
                            ],
                            'label' => '<svg class="svg" width="20" height="20">
                                            <use xlink:href="' . CONF_WEBROOT_URL . 'images/retina/sprite-actions.svg' . AttachedFile::setTimeParam(RELEASE_DATE) . '#edit">
                                            </use>
                                        </svg>'
                        ],
                    ];

                    if ($metaType == MetaTag::META_GROUP_ADVANCED) {
                        $data['deleteButton'] = [];
                    }
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

include(CONF_THEME_PATH . '_partial/listing/no-record-found.php'); ?>

<div id="metaTagsListing" class="card listingTableJs">
    <?php $keyWordFld = $frmSearch->getField('keyword');
    if (1 > $loadRows) {
        $onSubmit = 'searchRecords(this, true); return(false);';
        require_once(CONF_THEME_PATH . '_partial/listing/listing-search-form.php');
    } ?>
    <div class="card-head">
        <div class="card-head-label">
            <h3 class="card-head-title">
                <?php echo CommonHelper::replaceStringData(Labels::getLabel('LBL_{TAB-TITLE}_META_TAGS_LISTING', $siteLangId), ['{TAB-TITLE}' =>  $tabsArr[$metaType]['name']]); ?>
            </h3>
        </div>
        <?php if ($metaType == MetaTag::META_GROUP_ADVANCED) { ?>
            <div class="card-toolbar">
                <?php
                $data = [
                    'canEdit' => $canEdit,
                    'siteLangId' => $siteLangId,
                    'otherButtons' => [
                        [
                            'attr' => [
                                'href' => 'javascript:void(0)',
                                'class' => 'btn btn-icon btn-outline-brand btn-add',
                                'title' => Labels::getLabel('BTN_ADD_META_TAG', $siteLangId),
                                'onclick' => "metaTagForm(0,'" . $metaType . "',0)",
                            ],
                            'label' => '<svg class="svg btn-icon-start" width="18" height="18">
                                            <use 
                                                xlink:href="' . CONF_WEBROOT_URL . 'images/retina/sprite-actions.svg' . AttachedFile::setTimeParam(RELEASE_DATE) . '#add">
                                            </use>
                                        </svg>
                                        <span> ' . Labels::getLabel('BTN_NEW', $siteLangId) . '</span>'
                        ]
                    ]
                ];
                $this->includeTemplate('_partial/listing/action-buttons.php', $data, false);
                ?>
            </div>
        <?php } ?>
    </div>
    <div class="card-table">
        <div class="table-responsive table-scrollable js-scrollable">
            <?php echo $tbl->getHtml(); ?>
        </div>
    </div>
    <?php require_once(CONF_THEME_PATH . '_partial/listing/listing-foot.php'); ?>
</div>