<?php defined('SYSTEM_INIT') or die('Invalid Usage.');
?>

<div class="modal-header">
    <h5 class="modal-title">
        <?php echo  Labels::getLabel('LBL_SHOP_MISSING_INFO', $siteLangId) ?>
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
                        $msg = $yesNoArr[$row[$key]];
                        $status = $row[$key] == applicationConstants::YES ? HtmlHelper::SUCCESS:HtmlHelper::DANGER;
                        $td->appendElement('plaintext', array(), HtmlHelper::getStatusHtml($status, $msg), true);
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