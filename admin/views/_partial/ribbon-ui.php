<?php defined('SYSTEM_INIT') or die('Invalid Usage');

$ribbon = "";
if (is_array($ribbRow) && !empty($ribbRow)) {
    $type = $ribbRow['badge_shape_type'];
    $textColor = $ribbRow['badge_text_color'];
    $color = $ribbRow['badge_color'];
    $text = $title = $ribbRow['badge_name'];
    if (applicationConstants::NO == $ribbRow['badge_display_inside']) {
        $text = "";
    }
    $class = '';
    if (array_key_exists('blinkcond_position', $ribbRow)) {
        $class = (Badge::RIBB_POS_TLEFT == $ribbRow['blinkcond_position'])  ? 'badges-left' : 'badges-right';
    }

    switch ($type) {
        case Badge::SHAPE_RECTANGLE:
            $ribbon = '<div class="badge badges-' . $type . ' ' . $class . '" style="background:' . $color . '; color:' . $textColor . '" title="' . $title . '">' . $text . '</div>';
            break;
        case Badge::SHAPE_STRIP:
        case Badge::SHAPE_STAR:
        case Badge::SHAPE_TRIANGLE:
        case Badge::SHAPE_CIRCLE:
            $ribbon = '<div class="badge badges-' . $type . ' ' . $class . '" title="' . $title . '">
                        <svg class="svg" style="fill:' . $color . '">
                            <use xlink:href="' . CONF_WEBROOT_FRONT_URL . 'images/retina/badges/sprite.svg' . AttachedFile::setTimeParam(RELEASE_DATE) . '#badges-' . $type . '"></use>
                        </svg>
                        <span class="text" style="color:' . $textColor . '">' . $text . '</span>
                    </div>';
            break;
    }
}
echo $ribbon;
