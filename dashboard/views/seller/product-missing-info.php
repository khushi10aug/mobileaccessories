<?php defined('SYSTEM_INIT') or die('Invalid Usage.');
?>

<div class="modal-header">
    <h5 class="modal-title">
        <?php echo  Labels::getLabel('LBL_PRODUCT_MISSING_INFO', $siteLangId) ?>
    </h5>
</div>
<div class="modal-body">
    <div class="loaderContainerJs">
        <?php

        $yesNoArr = applicationConstants::getYesNoArr($siteLangId);
        $arr_flds = array(
            'title' => Labels::getLabel('LBL_FIELDS_TITLE', $siteLangId),
            'currentStatus' => Labels::getLabel('LBL_CURRENT_STATUS', $siteLangId),
            'valid' => Labels::getLabel('LBL_VALID', $siteLangId),
        );    

        $tbl = new HtmlElement('table', array('width' => '100%', 'class' => 'table'));
        $th = $tbl->appendElement('thead')->appendElement('tr');
        foreach ($arr_flds as $key => $val) {          
            $th->appendElement('th', [], $val);
        }  
        foreach ($infoArr as $sn => $row) {           
            $tr = $tbl->appendElement('tr');
            foreach ($arr_flds as $key => $val) {              
                $td = $tr->appendElement('td');
                switch ($key) {  
                    case 'currentStatus':                       
                        $td->appendElement('plaintext', array(), ($yesNoArr[$row[$key]] ?? ''), true);
                    break;    
                    case 'valid':
                        $class = $row[$key] == applicationConstants::YES ? 'badge-success' :'badge-danger';
                        $title = $row[$key] == applicationConstants::NO && isset($row['code']) ? ' <i class="fa fa-info-circle" data-bs-toggle="tooltip" data-placement="right" title=" ' . Labels::getLabel('LBL_PLEASE_DELETE_THIS_INVENTORY_OR_ADD_OPTIONS_FROM_IMPORT/EXPORT_MODULE', $siteLangId) . '"></i>' : '';
                        $html = '<span class="badge badge-inline '.$class.'" >'.$yesNoArr[$row[$key]]. $title .'
                        </span>';
                        $td->appendElement('plaintext', array(), $html, true);
                    break;    
                    default:
                        $td->appendElement('plaintext', array(), $row[$key], true);
                        break;
                }
            }
        }
        ?>
        <div>
            <?php echo $tbl->getHtml(); ?>
        </div>
    </div>
</div>