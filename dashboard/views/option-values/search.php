<?php defined('SYSTEM_INIT') or die('Invalid Usage.'); ?>
<div class="js-scrollable table-wrap table-responsive">
    <?php $arr_flds = array(
        'dragdrop' => '',
        'optionvalue_identifier' => Labels::getLabel('LBL_OPTION_VALUE_NAME', $langId),
        'action'  =>  '',
    );
    $tableClass = '';
    if (0 < count($arrListing)) {
        $tableClass = "table-justified";
    }
    $tbl = new HtmlElement('table', array('width' => '100%', 'class' => 'table sortable--js ' . $tableClass, 'id' => 'optionvalues'));
    $th = $tbl->appendElement('thead')->appendElement('tr');
    foreach ($arr_flds as $val) {
        $e = $th->appendElement('th', array(), $val);
    }

    $sr_no = 0;
    foreach ($arrListing as $sn => $row) {
        $sr_no++;
        $tr = $tbl->appendElement('tr');
        $tr->setAttribute("id", $row['optionvalue_id']);
        foreach ($arr_flds as $key => $val) {
            $td = $tr->appendElement('td');
            switch ($key) {
                case 'dragdrop':
                    $td->appendElement('i', array('class' => 'fas fa-arrows-alt'));
                    $td->setAttribute("class", 'dragHandle handleJs');
                    break;
                case 'optionvalue_identifier':
                    if ($row['optionvalue_name'] != '') {
                        $td->appendElement('plaintext', array(), $row['optionvalue_name'], true);
                    } else {
                        $td->appendElement('plaintext', array(), $row[$key], true);
                    }
                    break;
                case 'action':
                    $ul = $td->appendElement("ul", array("class" => "actions"));

                    $li = $ul->appendElement("li");
                    $li->appendElement('a', array(
                        'href' => 'javascript:void(0)',
                        'class' => 'button small green', 'title' => Labels::getLabel('LBL_EDIT', $langId),
                        "onclick" => "form(" . $row['optionvalue_option_id'] . "," . $row['optionvalue_id'] . ")"
                    ), '<svg class="svg" width="18" height="18">
                            <use
                                xlink:href="' . CONF_WEBROOT_URL . 'images/retina/sprite-actions.svg' . AttachedFile::setTimeParam(RELEASE_DATE) . '#edit">
                            </use>
                        </svg>', true);

                    $li = $ul->appendElement("li");
                    $li->appendElement('a', array(
                        'href' => "javascript:void(0)",
                        'class' => 'button small green', 'title' => Labels::getLabel('LBL_DELETE', $langId), "onclick" => "deleteRecord(" . $row['optionvalue_option_id'] . "," . $row['optionvalue_id'] . ")"
                    ), '<svg class="svg" width="18" height="18">
                            <use
                                xlink:href="' . CONF_WEBROOT_URL . 'images/retina/sprite-actions.svg' . AttachedFile::setTimeParam(RELEASE_DATE) . '#delete">
                            </use>
                        </svg>', true);
                    break;
                default:
                    $td->appendElement('plaintext', array(), $row[$key], true);
                    break;
            }
        }
    }
    echo $tbl->getHtml();
    if (count($arrListing) == 0) {
        $message = Labels::getLabel('LBL_No_Record_found', $siteLangId);
        $this->includeTemplate('_partial/no-record-found.php', array('siteLangId' => $siteLangId, 'message' => $message));
    } ?>
</div>
<script>
    $(document).ready(function() {
        $(".sortable--js tbody").sortable({
            handle: '.handleJs',
            helper: fixWidthHelper,
            start: fixPlaceholderStyle,
            stop: function() {
                var orderStr = '';
                $(this).find('tr').each(function(index, value) {
                    if (0 < index) {
                        orderStr += '&';
                    }
                    orderStr += 'optionvalues[]=' + $(this).attr("id");
                });
                fcom.ajax(fcom.makeUrl('OptionValues', 'setOptionsOrder'), orderStr, function(res) {
                    var ans = $.parseJSON(res);
                    if (ans.status == 1) {
                        fcom.displaySuccessMessage(ans.msg);
                    } else {
                        fcom.displayErrorMessage(ans.msg);
                    }
                });
            }
        });
    });
</script>