<?php defined('SYSTEM_INIT') or die('Invalid Usage.');
$status = applicationConstants::ON;
$commonData = array(
    'currencySymbol' => $currencySymbol,
    'totalFavouriteItems' => $totalFavouriteItems ?? 0,
    'totalUnreadMessageCount' => $totalUnreadMessageCount ?? 0,
    'totalUnreadNotificationCount' => $totalUnreadNotificationCount ?? 0,
    'cartItemsCount' => $cartItemsCount ?? 0,
    'offerCheckout' => $_SESSION['offer_checkout'] ?? (object)[],
);
