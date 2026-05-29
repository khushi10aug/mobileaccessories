<?php defined('SYSTEM_INIT') or die('Invalid Usage.');

/**
 * Apply wholesale theme class to body
 * Include this at the top of pages where you want the wholesale theme
 */

// Add wholesale theme to body classes
if (!isset($bodyClasses)) {
    $bodyClasses = [];
}
$bodyClasses[] = 'wholesale-theme';

// You can also add it via JavaScript if body is already rendered
?>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        if (!document.body.classList.contains('wholesale-theme')) {
            document.body.classList.add('wholesale-theme');
        }
        if (!document.querySelector('.wrapper').classList.contains('wholesale-theme')) {
            document.querySelector('.wrapper').classList.add('wholesale-theme');
        }
    });
</script>
