<?php
defined('SYSTEM_INIT') or die('Invalid Usage.');
foreach ($images as $img) {
    ?>
    <li class="uploaded">
        <i class="uploaded-file">
            <img src="<?php echo $img['imageUrl']; ?>">
        </i>
        <div class="file">
            <div class="file_name">
                <?php echo $img['afile_name']; ?>
            </div>           
            <a class="trash" href="javascript:void(0);" onclick="<?php echo $img['removeFunction']; ?>">
                <i class="icn">
                    <svg class="svg" width="18px" height="18px">
                    <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#remove">
                    </use>
                    </svg>
                </i>
            </a>
        </div>
    </li>   
<?php } ?>