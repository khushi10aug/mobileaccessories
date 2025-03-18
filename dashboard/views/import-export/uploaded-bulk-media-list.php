<?php defined('SYSTEM_INIT') or die('Invalid Usage.'); ?>
<div class="js-scrollable table-wrap table-responsive">
    <?php
    $arr_flds = array(
        'listserial' => Labels::getLabel('LBL_#', $siteLangId),
        'afile_physical_path' => Labels::getLabel('LBL_FILE_LOCATION', $siteLangId),
        'afile_name'    => Labels::getLabel('LBL_File_Name', $siteLangId),
        'files'    => Labels::getLabel('LBL_Files_Inside', $siteLangId),
        'action'    => '',
    );
    $tableClass = '';
    if (0 < count($arrListing)) {
        $tableClass = "table-justified";
    }
    $tbl = new HtmlElement('table', array('width' => '100%', 'class' => 'table ' . $tableClass));
    $th = $tbl->appendElement('thead')->appendElement('tr');
    foreach ($arr_flds as $val) {
        $e = $th->appendElement('th', array(), $val);
    }

    $sr_no = ($page > 1) ? $recordCount - (($page - 1) * $pageSize) : $recordCount;
    foreach ($arrListing as $sn => $row) {
        $tr = $tbl->appendElement('tr', array());

        foreach ($arr_flds as $key => $val) {
            $td = $tr->appendElement('td');
            switch ($key) {
                case 'listserial':
                    $td->appendElement('plaintext', array(), $sr_no);
                    break;
                case 'afile_physical_path':
                    $path = AttachedFile::FILETYPE_BULK_IMAGES_PATH . $row['afile_physical_path'];
                    $td->appendElement('plaintext', array(), $path, true);
                    break;
                case 'files':
                    $fullPath = CONF_UPLOADS_PATH . AttachedFile::FILETYPE_BULK_IMAGES_PATH . $row['afile_physical_path'];
                    $count = Labels::getLabel('LBL_NA', $siteLangId);
                    if (file_exists($fullPath)) {
                        $allFiles = scandir($fullPath);
                        $files_count = array_diff($allFiles, array('..', '.'));
                        $count = count($files_count);
                    }

                    $td->appendElement('plaintext', array(), $count);
                    break;
                case 'action':
                    $ul = $td->appendElement("ul", array("class" => "actions actions--centered"));

                    $li = $ul->appendElement("li");
                    $li->appendElement(
                        'a',
                        array(
                            'href' => 'javascript:void(0)', 'class' => 'button small green',
                            'title' => Labels::getLabel('LBL_Delete', $siteLangId), "onclick" => "removeDir('" . base64_encode($row['afile_physical_path']) . "')"
                        ),
                        '<i class="icn">
                    <svg class="svg" width="18" height="18">
                        <use
                            xlink:href="' . CONF_WEBROOT_URL . 'images/retina/sprite-actions.svg' . AttachedFile::setTimeParam(RELEASE_DATE) . '#delete">
                        </use>
                    </svg>
                </i>',
                        true
                    );
                    $li = $ul->appendElement("li");
                    $li->appendElement('a', array('href' => 'javascript:void(0)', 'class' => 'button small green', 'title' => Labels::getLabel('LBL_Download', $siteLangId), "onclick" => "downloadPathsFile('" . base64_encode($fullPath) . "')"), '<i class="icn">
                <svg class="svg" width="18" height="18">
                    <use
                        xlink:href="' . CONF_WEBROOT_URL . 'images/retina/sprite-actions.svg' . AttachedFile::setTimeParam(RELEASE_DATE) . '#icon-download">
                    </use>
                </svg>
            </i>', true);
                    break;
                default:
                    $td->appendElement('plaintext', array(), $row[$key], true);
                    break;
            }
        }
        $sr_no--;
    }
    if (count($arrListing) == 0) {
        $tbl->appendElement('tr')->appendElement('td', array('colspan' => count($arr_flds)), Labels::getLabel('LBL_No_Records_Found', $siteLangId));
    }
    echo $tbl->getHtml(); ?>
</div>
<?php $postedData['page'] = $page;
echo FatUtility::createHiddenFormFromData($postedData, array(
    'name' => 'frmSearchPaging'
));
$pagingArr = array('pageCount' => $pageCount, 'page' => $page, 'pageSize' => $pageSize, 'recordCount' => $recordCount, 'adminLangId' => $siteLangId);
$this->includeTemplate('_partial/pagination.php', $pagingArr, false);
