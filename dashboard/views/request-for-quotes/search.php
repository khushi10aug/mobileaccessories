<?php defined('SYSTEM_INIT') or die('Invalid Usage.'); ?>
<div class="card-table">
    <div class="js-scrollable table-wrap table-responsive">
        <?php
        $labelArr = RequestForQuote::getSellerLinkingTypeArr($siteLangId);
        $tbl = new HtmlElement('table', array('class' => 'table table-justified'));
        $th = $tbl->appendElement('thead')->appendElement('tr', array('class' => ''));
        foreach ($headerCols as $val) {
            $e = $th->appendElement('th', array(), $val);
        }

        $sr_no = ($page > 1) ? $recordCount - (($page - 1) * $pageSize) : $recordCount;
        foreach ($arrListing as $sn => $row) {
            $tr = $tbl->appendElement('tr', array('class' => ''));

            foreach ($headerCols as $key => $val) {
                $td = $tr->appendElement('td');
                switch ($key) {
                    case 'listSerial':
                        $td->appendElement('plaintext', array(), $sr_no);
                        break;
                    case 'rfq_title':
                        $url = 0 < $row['rfq_selprod_id'] ? UrlHelper::generateUrl('Products', 'view', array($row['rfq_selprod_id']), CONF_WEBROOT_FRONTEND) : 'javascript:void(0)';
                        $title = '<a href="' . $url . '">' . Labels::getLabel('LBL_TITLE') . ': ' . $row[$key] . '</a>';
                        $global = '';
                        $class = '';
                        if (RequestForQuote::VISIBILITY_TYPE_OPEN == $row['rfq_visibility_type']) {
                            $global = HtmlHelper::getStatusHtml(HtmlHelper::INFO, $labelArr[$row['rfq_visibility_type']]);
                        }

                        if (1 > $row['rfq_selprod_id']) {
                            $title = Labels::getLabel('LBL_TITLE') . ': ' . $row[$key];
                            $class = 'fw-normal';
                        }

                        $htm = '<div class="product-profile">
                                    <div class="product-profile__description mw-350">
                                        <div class="product-profile__title ' . $class . '">' . $title . '</div>
                                        ' . $global . '
                                        <div class="product-profile__options">' . Labels::getLabel('LBL_QTY') . ': ' . $row['rfq_quantity'] . ' ' . applicationConstants::getWeightUnitName($siteLangId, $row['rfq_quantity_unit'], true) . '</div>
                                    </div>
                                </div>';
                        $td->appendElement('plaintext', array('width'=> '20%'), $htm, true);
                        break;
                    case 'rfq_number':
                        $td->appendElement('div', ["class" => 'text-nowrap'], $row['rfq_number'], true);
                        break;
                    case 'acceptedOffers':
                    case 'rejectedOffers':
                        $offerCount = ($row['totalOffers'] > 0) ? $row[$key] . '/' . $row['totalOffers'] : 0;
                        $td->appendElement('plaintext', [], $offerCount, true);
                        break;
                    case 'rfq_approved':
                        $html = '<span class="' . RequestForQuote::getBadgeClass($row[$key]) . '">' . $approvalStatusArr[$row[$key]] . '</span>';
                        $td->appendElement('plaintext', array(), $html, true);
                        break;
                    case 'rfq_status':
                        $html = RequestForQuote::getStatusHtml($row['rfq_status'], $siteLangId);
                        $td->appendElement('plaintext', array(), $html, true);
                        break;
                    case 'rfq_added_on':
                        $td->appendElement('plaintext', array(), HtmlHelper::formatDateTime($row[$key]), true);
                        break;
                    case 'action':
                        $ul = $td->appendElement("ul", array("class" => "actions"), '', true);
                        $li = $ul->appendElement("li", ['class' => 'actions-item']);
                        $li->appendElement(
                            'a',
                            array(
                                'class' => 'actions-link',
                                'href' => 'javascript:void(0)',
                                'onclick' => 'viewRfq("' . $row['rfq_id'] . '", ' . $visibilityType . ');',
                                'data-bs-toggle' => 'tooltip',
                                'title' => Labels::getLabel('LBL_VIEW', $siteLangId)
                            ),
                            '<svg class="svg" width="18" height="18">
                                <use
                                    xlink:href="' . CONF_WEBROOT_URL . 'images/retina/sprite-actions.svg' . AttachedFile::setTimeParam(RELEASE_DATE) . '#view">
                                </use>
                            </svg>',
                            true
                        );

                        if ($row['rfq_approved'] == RequestForQuote::APPROVED) {
                            if ($isBuyer || (RequestForQuote::isAssignedToSeller($row['rfq_id'], $userParentId) && $canEdit)) {
                                $action = RequestForQuote::VISIBILITY_TYPE_OPEN == $visibilityType ? 'globalListing' : 'listing';
                                $li = $ul->appendElement("li", ['class' => 'actions-item']);
                                $li->appendElement(
                                    'a',
                                    array(
                                        'class' => 'actions-link',
                                        'data-bs-toggle' => 'tooltip',
                                        'href' => UrlHelper::generateUrl(($isSeller ? 'Seller' : '') . 'RfqOffers', $action, [$row['rfq_id']]),
                                        'title' => Labels::getLabel('LBL_OFFERS', $siteLangId)
                                    ),
                                    '<svg class="svg" width="18" height="18">

                                    <use
                                        xlink:href="' . CONF_WEBROOT_URL . 'images/retina/sprite-actions.svg' . AttachedFile::setTimeParam(RELEASE_DATE) . '#list">
                                    </use>
                                </svg>',
                                    true
                                );
                            } else if ($isSeller && in_array($row['rfq_status'], [RequestForQuote::STATUS_OPEN, RequestForQuote::STATUS_OFFERED]) && $canEdit) {
                                $li = $ul->appendElement("li", ['class' => 'actions-item']);
                                $li->appendElement(
                                    'a',
                                    array(
                                        'class' => 'actions-link',
                                        'href' => 'javascript:void(0)',
                                        'onclick' => 'assignToMe("' . $row['rfq_id'] . '");',
                                        'title' => Labels::getLabel('LBL_ASSIGN_TO_ME', $siteLangId)
                                    ),
                                    '<svg class="svg" width="18" height="18">
                                        <use
                                            xlink:href="' . CONF_WEBROOT_URL . 'images/retina/sprite-actions.svg' . AttachedFile::setTimeParam(RELEASE_DATE) . '#add">
                                        </use>
                                    </svg>',
                                    true
                                );
                            }
                        }

                        if ($isBuyer && 0 < $row['acceptedOffers']) {
                            $li = $ul->appendElement("li", ['class' => 'actions-item']);
                            $li->appendElement(
                                'a',
                                array(
                                    'class' => 'actions-link',
                                    'data-bs-toggle' => 'tooltip',
                                    'href' => UrlHelper::generateUrl('RequestForQuotes', 'downloadRfqCopy', [$row['rfq_id']]),
                                    'title' => Labels::getLabel('LBL_DOWNLOAD_RFQ_COPY', $siteLangId)
                                ),
                                '<svg class="svg" width="18" height="18">
                                    <use
                                        xlink:href="' . CONF_WEBROOT_URL . 'images/retina/sprite-actions.svg' . AttachedFile::setTimeParam(RELEASE_DATE) . '#download">
                                    </use>
                                </svg>',
                                true
                            );
                        }
                        break;
                    default:
                        $td->appendElement('plaintext', array(), $row[$key], true);
                        break;
                }
            }

            $sr_no--;
        }
        echo $tbl->getHtml();
        if (count($arrListing) == 0) {
            $this->includeTemplate('_partial/no-record-found.php', array('siteLangId' => $siteLangId));
        } ?>
    </div>
</div>
<?php $postedData['page'] = $page;
echo FatUtility::createHiddenFormFromData($postedData, array('name' => 'frmSrchPaging'));
$pagingArr = array('pageCount' => $pageCount, 'page' => $page, 'pageSize' => $pagesize, 'recordCount' => $recordCount, 'callBackJsFunc' => 'goToSearchPage');
$this->includeTemplate('_partial/pagination.php', $pagingArr, false);
