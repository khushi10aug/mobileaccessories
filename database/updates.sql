/* RFQ */
CREATE TABLE `tbl_rfq` (
  `rfq_id` int NOT NULL,
  `rfq_number` varchar(15) NOT NULL,
  `rfq_product_id` int NOT NULL,
  `rfq_selprod_id` int NOT NULL,
  `rfq_selprod_code` varchar(100) NOT NULL,
  `rfq_title` varchar(150) NOT NULL,
  `rfq_user_id` int NOT NULL,
  `rfq_type` tinyint NOT NULL,
  `rfq_quantity` int NOT NULL,
  `rfq_quantity_unit` tinyint NOT NULL,
  `rfq_delivery_date` date NOT NULL,
  `rfq_description` text NOT NULL,
  `rfq_addr_id` int NOT NULL COMMENT 'delivery address ID',
  `rfq_lang_id` int NOT NULL,
  `rfq_status` int NOT NULL,
  `rfq_approved` tinyint NOT NULL,
  `rfq_added_on` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `rfq_updated_on` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `rfq_deleted` tinyint NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

ALTER TABLE `tbl_rfq`
  ADD PRIMARY KEY (`rfq_id`),
  ADD UNIQUE KEY `rfq_number` (`rfq_number`);

ALTER TABLE `tbl_rfq`
  MODIFY `rfq_id` int NOT NULL AUTO_INCREMENT;


CREATE TABLE `tbl_rfq_to_sellers` (
  `rfqts_rfq_id` int NOT NULL,
  `rfqts_user_id` int NOT NULL COMMENT 'seller id',
  `rfqts_selprod_id` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

ALTER TABLE `tbl_rfq_to_sellers`
  ADD PRIMARY KEY (`rfqts_rfq_id`,`rfqts_user_id`);

INSERT INTO `tbl_email_templates` (`etpl_code`, `etpl_lang_id`, `etpl_name`, `etpl_subject`, `etpl_body`, `etpl_replacements`, `etpl_status`) VALUES ('NEW_RFQ', '1', 'Request for New Quotation', 'Request for New Quotation', '<table width="600px" cellspacing="0" cellpadding="0" style="margin: 0 auto; table-layout: fixed; background: #ffffff; border-radius: 4px; box-shadow: 0 0 10px rgba(0, 0, 0, 0.04)">
    <tbody>
        <tr>
            <td style="background:#fff;padding:20px 0 10px; text-align:center;">
                <h4
                    style="font-weight:normal; text-transform:uppercase; color:#999;margin:0; padding:10px 0; font-size:18px;">
                </h4>
                <h2 style="margin:0; font-size:34px; padding:0;">Request for New Quotation</h2>
            </td>
        </tr>
        <tr>
            <td style="background:#fff;padding:0 30px; text-align:center; color:#999;vertical-align:top;">
                <table width="100%" border="0" align="center" cellpadding="0" cellspacing="0">
                    <tbody>
                        <tr>
                            <td style="padding:20px 0 30px;"><strong style="font-size:18px;color:#333;">Dear Admin
                                </strong><br />
                                A new request for quotation received on your site <a href="{website_url}">{website_name}</a>
                                <br />
                                Please find the RFQ information below.</td>
                        </tr>
                        <tr>
                            <td style="padding:0 0 30px;">{rfq_table}</td>
                        </tr>
                    </tbody>
                </table>
            </td>
        </tr>
    </tbody>
</table>', '{rfq_table} RFQ information table<br>\r\n{website_name} Name of our website<br>\r\n{website_url} URL of our website<br>\r\n{social_media_icons} <br>\r\n{contact_us_url} <br>\r\n', '1')
ON DUPLICATE KEY UPDATE etpl_subject = VALUES(etpl_subject), etpl_body = VALUES(etpl_body);

INSERT INTO `tbl_sms_templates` (`stpl_code`, `stpl_lang_id`, `stpl_name`, `stpl_body`, `stpl_replacements`, `stpl_status`) VALUES 
('NEW_RFQ',1,'Request for New Quotation','Hello Admin,\r\nA new RFQ is received for {rfq_title} ({rfq_number}) with quantity {qty}.\r\n\r\n{SITE_NAME} Team','[{\"title\":\"RFQ Title\", \"variable\":\"{rfq_title}\"}, {\"title\":\"Quantity\", \"variable\":\"{qty}\"}, {\"title\":\"Website Name\", \"variable\":\"{SITE_NAME}\"}]',1)
ON DUPLICATE KEY UPDATE stpl_body = VALUES(stpl_body), stpl_replacements = VALUES(stpl_replacements);

INSERT INTO `tbl_email_templates` (`etpl_code`, `etpl_lang_id`, `etpl_name`, `etpl_subject`, `etpl_body`, `etpl_replacements`, `etpl_status`) VALUES ('RFQ_APPROVAL', '1', 'Request for Quote Approval', 'Request for Quote Approval', '<table width="600px" cellspacing="0" cellpadding="0" style="margin: 0 auto; table-layout: fixed; background: #ffffff; border-radius: 4px; box-shadow: 0 0 10px rgba(0, 0, 0, 0.04)">
    <tbody>
        <tr>
            <td style="background:#fff;padding:20px 0 10px; text-align:center;">
                <h4
                    style="font-weight:normal; text-transform:uppercase; color:#999;margin:0; padding:10px 0; font-size:18px;">
                </h4>
                <h2 style="margin:0; font-size:34px; padding:0;">Request for Quote Appoval</h2>
            </td>
        </tr>
        <tr>
            <td style="background:#fff;padding:0 30px; text-align:center; color:#999;vertical-align:top;">
                <table width="100%" border="0" align="center" cellpadding="0" cellspacing="0">
                    <tbody>
                        <tr>
                            <td style="padding:20px 0 30px;"><strong style="font-size:18px;color:#333;">Dear {user_name}
                                </strong><br />
                                A request for quote has been {approval_status} by the site <a href="{website_url}">{website_name}</a> Admin.
                                <br />
                                Please find the RFQ information below.</td>
                        </tr>
                        <tr>
                            <td style="padding:0 0 30px;">{rfq_table}</td>
                        </tr>
                    </tbody>
                </table>
            </td>
        </tr>
    </tbody>
</table>', '{user_name} Buyer/Seller Name<br>\r\n{approval_status} RFQ approval status<br>\r\n{rfq_table} RFQ information table<br>\r\n{website_name} Name of our website<br>\r\n{website_url} URL of our website<br>\r\n{social_media_icons} <br>\r\n{contact_us_url} <br>\r\n', '1')
ON DUPLICATE KEY UPDATE etpl_subject = VALUES(etpl_subject), etpl_body = VALUES(etpl_body);

INSERT INTO `tbl_sms_templates` (`stpl_code`, `stpl_lang_id`, `stpl_name`, `stpl_body`, `stpl_replacements`, `stpl_status`) VALUES 
('RFQ_APPROVAL',1,'Request for Quote Approval','Dear {user_name},\r\nRFQ was received for {rfq_title} ({rfq_number}) with quantity {qty} has been {approval_status} by the Admin.\r\n\r\n{SITE_NAME} Team','[{\"title\":\"Buyer/Seller Name\", \"variable\":\"{user_name}\"},{\"title\":\"RFQ Title\", \"variable\":\"{rfq_title}\"}, {\"title\":\"Quantity\", \"variable\":\"{qty}\"}, {\"title\":\"Website Name\", \"variable\":\"{SITE_NAME}\"}]',1)
ON DUPLICATE KEY UPDATE stpl_body = VALUES(stpl_body), stpl_replacements = VALUES(stpl_replacements);

ALTER TABLE `tbl_addresses` ADD `addr_session_id` VARCHAR(150) NOT NULL AFTER `addr_record_id`;

INSERT INTO `tbl_configurations` (`conf_name`, `conf_val`) VALUES 
('CONF_RFQ_MODULE_TYPE', 1),
('CONF_ENABLE_ADMIN_APPROVAL_ON_NEW_RFQ', 1)
ON DUPLICATE KEY UPDATE conf_val = VALUES(conf_val);

CREATE TABLE `tbl_rfq_offers` (
  `offer_id` int NOT NULL,
  `offer_primary_offer_id` INT NOT NULL,
  `offer_rfq_id` int NOT NULL,
  `offer_user_id` int NOT NULL COMMENT 'Primary Seller or Buyer Id',
  `offer_user_type` int NOT NULL,
  `offer_counter_offer_id` int NOT NULL,
  `offer_quantity` int NOT NULL,
  `offer_cost` float(10,2) NOT NULL,
  `offer_price` float(10,2) NOT NULL,
  `offer_shiprate_id` int NOT NULL,
  `offer_negotiable` tinyint NOT NULL,
  `offer_status` int NOT NULL,
  `offer_comments` text NOT NULL,
  `offer_expired_on` datetime NOT NULL,
  `offer_added_on` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `offer_deleted` tinyint(1) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

ALTER TABLE `tbl_rfq_offers`
  ADD PRIMARY KEY (`offer_id`),
  ADD UNIQUE KEY `offer` (`offer_rfq_id`,`offer_user_id`,`offer_counter_offer_id`,`offer_quantity`);

ALTER TABLE `tbl_rfq_offers`
  MODIFY `offer_id` int NOT NULL AUTO_INCREMENT;


CREATE TABLE `tbl_rfq_latest_offers` (
  `rlo_primary_offer_id` int NOT NULL,
  `rlo_rfq_id` int NOT NULL,
  `rlo_seller_user_id` int NOT NULL,
  `rlo_seller_offer_id` int NOT NULL,
  `rlo_buyer_offer_id` int NOT NULL,
  `rlo_selprod_id` int NOT NULL,
  `rlo_shipping_charges` decimal(10,2) NOT NULL,
  `rlo_accepted_offer_id` int NOT NULL,
  `rlo_status` int NOT NULL,
  `rlo_deleted` tinyint(1) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

ALTER TABLE `tbl_rfq_latest_offers`
ADD PRIMARY KEY (`rlo_primary_offer_id`);


CREATE TABLE `tbl_rfq_offer_messages` (
  `rom_id` int NOT NULL,
  `rom_primary_offer_id` int NOT NULL,
  `rom_user_type` tinyint NOT NULL,
  `rom_message` text NOT NULL,
  `rom_buyer_access` tinyint NOT NULL DEFAULT '1',
  `rom_added_on` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

ALTER TABLE `tbl_rfq_offer_messages`
ADD PRIMARY KEY (`rom_id`);

ALTER TABLE `tbl_rfq_offer_messages`
MODIFY `rom_id` int NOT NULL AUTO_INCREMENT;

INSERT INTO `tbl_email_templates` (`etpl_code`, `etpl_lang_id`, `etpl_name`, `etpl_subject`, `etpl_body`, `etpl_replacements`, `etpl_status`) VALUES ('NEW_RFQ_OFFER', '1', 'New RFQ Offer', 'New RFQ Offer', '<table width="600px" cellspacing="0" cellpadding="0" style="margin: 0 auto; table-layout: fixed; background: #ffffff; border-radius: 4px; box-shadow: 0 0 10px rgba(0, 0, 0, 0.04)">
    <tbody>
        <tr>
            <td style="background:#fff;padding:20px 0 10px; text-align:center;">
                <h4
                    style="font-weight:normal; text-transform:uppercase; color:#999;margin:0; padding:10px 0; font-size:18px;">
                </h4>
                <h2 style="margin:0; font-size:34px; padding:0;">New RFQ Offer</h2>
            </td>
        </tr>
        <tr>
            <td style="background:#fff;padding:0 30px; text-align:center; color:#999;vertical-align:top;">
                <table width="100%" border="0" align="center" cellpadding="0" cellspacing="0">
                    <tbody>
                        <tr>
                            <td style="padding:20px 0 30px;"><strong style="font-size:18px;color:#333;">Dear {user_name}
                                </strong><br />
                                A new offer received from {shop_name}.
                                <br />
                                Please find the RFQ offer information below.</td>
                        </tr>
                        <tr>
                            <td style="padding:0 0 30px;">{offer_table}</td>
                        </tr>
                    </tbody>
                </table>
            </td>
        </tr>
    </tbody>
</table>', '{shop_name} Seller`s Shop<br>\r\n{user_name} Buyer Name<br>\r\n{offer_table} Offer Information Table<br>\r\n{rfq_table} RFQ information table<br>\r\n{website_name} Name of our website<br>\r\n{website_url} URL of our website<br>\r\n{social_media_icons} <br>\r\n{contact_us_url} <br>\r\n', '1')
ON DUPLICATE KEY UPDATE etpl_subject = VALUES(etpl_subject), etpl_body = VALUES(etpl_body);

INSERT INTO `tbl_sms_templates` (`stpl_code`, `stpl_lang_id`, `stpl_name`, `stpl_body`, `stpl_replacements`, `stpl_status`) VALUES 
('NEW_RFQ_OFFER',1,'New RFQ Offer','Dear {user_name},\r\nA new offer on {rfq_number} received from {shop_name}.\r\nOffer Amount {offer_price} with Quantity {qty} \r\n\r\n{SITE_NAME} Team','[{\"title\":\"Seller`s Shop\", \"variable\":\"{shop_name}\"},{\"title\":\"Buyer/Seller Name\", \"variable\":\"{user_name}\"},{\"title\":\"Offer Amount\", \"variable\":\"{offer_price}\"}, {\"title\":\"Quantity\", \"variable\":\"{qty}\"}, {\"title\":\"Website Name\", \"variable\":\"{SITE_NAME}\"}]',1)
ON DUPLICATE KEY UPDATE stpl_body = VALUES(stpl_body), stpl_replacements = VALUES(stpl_replacements);

INSERT INTO `tbl_email_templates` (`etpl_code`, `etpl_lang_id`, `etpl_name`, `etpl_subject`, `etpl_body`, `etpl_replacements`, `etpl_status`) VALUES ('COUNTER_RFQ_OFFER_SELLER', '1', 'Counter RFQ Offer From Seller', 'Counter RFQ Offer From Seller', '<table width="600px" cellspacing="0" cellpadding="0" style="margin: 0 auto; table-layout: fixed; background: #ffffff; border-radius: 4px; box-shadow: 0 0 10px rgba(0, 0, 0, 0.04)">
    <tbody>
        <tr>
            <td style="background:#fff;padding:20px 0 10px; text-align:center;">
                <h4
                    style="font-weight:normal; text-transform:uppercase; color:#999;margin:0; padding:10px 0; font-size:18px;">
                </h4>
                <h2 style="margin:0; font-size:34px; padding:0;">Counter RFQ Offer Received From Seller</h2>
            </td>
        </tr>
        <tr>
            <td style="background:#fff;padding:0 30px; text-align:center; color:#999;vertical-align:top;">
                <table width="100%" border="0" align="center" cellpadding="0" cellspacing="0">
                    <tbody>
                        <tr>
                            <td style="padding:20px 0 30px;"><strong style="font-size:18px;color:#333;">Dear {user_name}
                                </strong><br />
                                Counter offer received from {shop_name}.
                                <br />
                                Please find the RFQ offer information below.</td>
                        </tr>
                        <tr>
                            <td style="padding:0 0 30px;">{offer_table}</td>
                        </tr>
                    </tbody>
                </table>
            </td>
        </tr>
    </tbody>
</table>', '{shop_name} Seller`s Shop<br>\r\n{user_name} Buyer Name<br>\r\n{offer_table} Offer Information Table<br>\r\n{rfq_table} RFQ information table<br>\r\n{website_name} Name of our website<br>\r\n{website_url} URL of our website<br>\r\n{social_media_icons} <br>\r\n{contact_us_url} <br>\r\n', '1')
ON DUPLICATE KEY UPDATE etpl_subject = VALUES(etpl_subject), etpl_body = VALUES(etpl_body);

INSERT INTO `tbl_sms_templates` (`stpl_code`, `stpl_lang_id`, `stpl_name`, `stpl_body`, `stpl_replacements`, `stpl_status`) VALUES 
('COUNTER_RFQ_OFFER_SELLER',1,'Counter RFQ Offer From Seller','Dear {user_name},\r\nCounter offer on {rfq_number} received from {shop_name}.\r\nOffer Amount {offer_price} with Quantity {qty} \r\n\r\n{SITE_NAME} Team','[{\"title\":\"Seller`s Shop\", \"variable\":\"{shop_name}\"},{\"title\":\"Buyer Name\", \"variable\":\"{user_name}\"},{\"title\":\"Offer Amount\", \"variable\":\"{offer_price}\"}, {\"title\":\"Quantity\", \"variable\":\"{qty}\"}, {\"title\":\"Website Name\", \"variable\":\"{SITE_NAME}\"}]',1)
ON DUPLICATE KEY UPDATE stpl_body = VALUES(stpl_body), stpl_replacements = VALUES(stpl_replacements);

INSERT INTO `tbl_email_templates` (`etpl_code`, `etpl_lang_id`, `etpl_name`, `etpl_subject`, `etpl_body`, `etpl_replacements`, `etpl_status`) VALUES ('COUNTER_RFQ_OFFER_BUYER', '1', 'Counter RFQ Offer From Buyer', 'Counter RFQ Offer From Buyer', '<table width="600px" cellspacing="0" cellpadding="0" style="margin: 0 auto; table-layout: fixed; background: #ffffff; border-radius: 4px; box-shadow: 0 0 10px rgba(0, 0, 0, 0.04)">
    <tbody>
        <tr>
            <td style="background:#fff;padding:20px 0 10px; text-align:center;">
                <h4
                    style="font-weight:normal; text-transform:uppercase; color:#999;margin:0; padding:10px 0; font-size:18px;">
                </h4>
                <h2 style="margin:0; font-size:34px; padding:0;">Counter RFQ Offer Received From Buyer</h2>
            </td>
        </tr>
        <tr>
            <td style="background:#fff;padding:0 30px; text-align:center; color:#999;vertical-align:top;">
                <table width="100%" border="0" align="center" cellpadding="0" cellspacing="0">
                    <tbody>
                        <tr>
                            <td style="padding:20px 0 30px;"><strong style="font-size:18px;color:#333;">Dear {shop_name} Seller,
                                </strong><br />
                                Counter offer received from {user_name}.
                                <br />
                                Please find the RFQ offer information below.</td>
                        </tr>
                        <tr>
                            <td style="padding:0 0 30px;">{offer_table}</td>
                        </tr>
                    </tbody>
                </table>
            </td>
        </tr>
    </tbody>
</table>', '{shop_name} Seller`s Shop<br>\r\n{user_name} Buyer Name<br>\r\n{offer_table} Offer Information Table<br>\r\n{rfq_table} RFQ information table<br>\r\n{website_name} Name of our website<br>\r\n{website_url} URL of our website<br>\r\n{social_media_icons} <br>\r\n{contact_us_url} <br>\r\n', '1')
ON DUPLICATE KEY UPDATE etpl_subject = VALUES(etpl_subject), etpl_body = VALUES(etpl_body);

INSERT INTO `tbl_sms_templates` (`stpl_code`, `stpl_lang_id`, `stpl_name`, `stpl_body`, `stpl_replacements`, `stpl_status`) VALUES 
('COUNTER_RFQ_OFFER_BUYER',1,'Counter RFQ Offer From Buyer','Dear {shop_name} Seller,\r\nCounter offer on {rfq_number} received from {user_name}.\r\nOffer Amount {offer_price} with Quantity {qty} \r\n\r\n{SITE_NAME} Team','[{\"title\":\"Seller`s Shop\", \"variable\":\"{shop_name}\"},{\"title\":\"Buyer Name\", \"variable\":\"{user_name}\"},{\"title\":\"Offer Amount\", \"variable\":\"{offer_price}\"}, {\"title\":\"Quantity\", \"variable\":\"{qty}\"}, {\"title\":\"Website Name\", \"variable\":\"{SITE_NAME}\"}]',1)
ON DUPLICATE KEY UPDATE stpl_body = VALUES(stpl_body), stpl_replacements = VALUES(stpl_replacements);

INSERT INTO `tbl_email_templates` (`etpl_code`, `etpl_lang_id`, `etpl_name`, `etpl_subject`, `etpl_body`, `etpl_replacements`, `etpl_status`) VALUES ('RFQ_OFFER_ACTION_SELLER', '1', 'RFQ Offer Action By Seller', 'RFQ Offer {offer_status} By Seller', '<table width="600px" cellspacing="0" cellpadding="0" style="margin: 0 auto; table-layout: fixed; background: #ffffff; border-radius: 4px; box-shadow: 0 0 10px rgba(0, 0, 0, 0.04)">
    <tbody>
        <tr>
            <td style="background:#fff;padding:20px 0 10px; text-align:center;">
                <h4
                    style="font-weight:normal; text-transform:uppercase; color:#999;margin:0; padding:10px 0; font-size:18px;">
                </h4>
                <h2 style="margin:0; font-size:34px; padding:0;">RFQ Offer {offer_status} By Seller</h2>
            </td>
        </tr>
        <tr>
            <td style="background:#fff;padding:0 30px; text-align:center; color:#999;vertical-align:top;">
                <table width="100%" border="0" align="center" cellpadding="0" cellspacing="0">
                    <tbody>
                        <tr>
                            <td style="padding:20px 0 30px;"><strong style="font-size:18px;color:#333;">Dear {user_name},
                                </strong><br />
                                RFQ Offer {offer_status} By {shop_name} Seller
                                <br />
                                Please find the RFQ {offer_status} offer information below.</td>
                        </tr>
                        <tr>
                            <td style="padding:0 0 30px;">{offer_table}</td>
                        </tr>
                    </tbody>
                </table>
            </td>
        </tr>
    </tbody>
</table>', '{offer_status} Offer Status<br>\r\n{shop_name} Seller`s Shop<br>\r\n{user_name} Buyer Name<br>\r\n{offer_table} Offer Information Table<br>\r\n{rfq_table} RFQ information table<br>\r\n{website_name} Name of our website<br>\r\n{website_url} URL of our website<br>\r\n{social_media_icons} <br>\r\n{contact_us_url} <br>\r\n', '1')
ON DUPLICATE KEY UPDATE etpl_subject = VALUES(etpl_subject), etpl_body = VALUES(etpl_body);

INSERT INTO `tbl_sms_templates` (`stpl_code`, `stpl_lang_id`, `stpl_name`, `stpl_body`, `stpl_replacements`, `stpl_status`) VALUES 
('RFQ_OFFER_ACTION_SELLER',1,'RFQ Offer Action By Seller','Dear {user_name},\r\n{shop_name} seller has {offer_status} your offer for {rfq_number}.\r\nOffer Amount {offer_price} with Quantity {qty} \r\n\r\n{SITE_NAME} Team','[{\"title\":\"Offer Status\", \"variable\":\"{offer_status}\"},{\"title\":\"Seller`s Shop\", \"variable\":\"{shop_name}\"},{\"title\":\"Buyer Name\", \"variable\":\"{user_name}\"},{\"title\":\"Offer Amount\", \"variable\":\"{offer_price}\"}, {\"title\":\"Quantity\", \"variable\":\"{qty}\"}, {\"title\":\"Website Name\", \"variable\":\"{SITE_NAME}\"}]',1)
ON DUPLICATE KEY UPDATE stpl_body = VALUES(stpl_body), stpl_replacements = VALUES(stpl_replacements);

INSERT INTO `tbl_email_templates` (`etpl_code`, `etpl_lang_id`, `etpl_name`, `etpl_subject`, `etpl_body`, `etpl_replacements`, `etpl_status`) VALUES ('RFQ_OFFER_ACTION_BUYER', '1', 'RFQ Offer Action By Buyer', 'RFQ Offer {offer_status} By Buyer', '<table width="600px" cellspacing="0" cellpadding="0" style="margin: 0 auto; table-layout: fixed; background: #ffffff; border-radius: 4px; box-shadow: 0 0 10px rgba(0, 0, 0, 0.04)">
    <tbody>
        <tr>
            <td style="background:#fff;padding:20px 0 10px; text-align:center;">
                <h4
                    style="font-weight:normal; text-transform:uppercase; color:#999;margin:0; padding:10px 0; font-size:18px;">
                </h4>
                <h2 style="margin:0; font-size:34px; padding:0;">RFQ Offer {offer_status} By Buyer</h2>
            </td>
        </tr>
        <tr>
            <td style="background:#fff;padding:0 30px; text-align:center; color:#999;vertical-align:top;">
                <table width="100%" border="0" align="center" cellpadding="0" cellspacing="0">
                    <tbody>
                        <tr>
                            <td style="padding:20px 0 30px;"><strong style="font-size:18px;color:#333;">Dear {shop_name} Seller,
                                </strong><br />
                                RFQ Offer {offer_status} By {user_name}
                                <br />
                                Please find the RFQ {offer_status} offer information below.</td>
                        </tr>
                        <tr>
                            <td style="padding:0 0 30px;">{offer_table}</td>
                        </tr>
                    </tbody>
                </table>
            </td>
        </tr>
    </tbody>
</table>', '{offer_status} Offer Status<br>\r\n{shop_name} Seller`s Shop<br>\r\n{user_name} Buyer Name<br>\r\n{offer_table} Offer Information Table<br>\r\n{rfq_table} RFQ information table<br>\r\n{website_name} Name of our website<br>\r\n{website_url} URL of our website<br>\r\n{social_media_icons} <br>\r\n{contact_us_url} <br>\r\n', '1')
ON DUPLICATE KEY UPDATE etpl_subject = VALUES(etpl_subject), etpl_body = VALUES(etpl_body);

INSERT INTO `tbl_sms_templates` (`stpl_code`, `stpl_lang_id`, `stpl_name`, `stpl_body`, `stpl_replacements`, `stpl_status`) VALUES 
('RFQ_OFFER_ACTION_BUYER',1,'RFQ Offer Action By Buyer','Dear {shop_name} Seller,\r\n{user_name} has {offer_status} your offer for {rfq_number}.\r\nOffer Amount {offer_price} with Quantity {qty} \r\n\r\n{SITE_NAME} Team','[{\"title\":\"Offer Status\", \"variable\":\"{offer_status}\"},{\"title\":\"Seller`s Shop\", \"variable\":\"{shop_name}\"},{\"title\":\"Buyer Name\", \"variable\":\"{user_name}\"},{\"title\":\"Offer Amount\", \"variable\":\"{offer_price}\"}, {\"title\":\"Quantity\", \"variable\":\"{qty}\"}, {\"title\":\"Website Name\", \"variable\":\"{SITE_NAME}\"}]',1)
ON DUPLICATE KEY UPDATE stpl_body = VALUES(stpl_body), stpl_replacements = VALUES(stpl_replacements);

ALTER TABLE `tbl_order_products` ADD `op_offer_id` INT NOT NULL AFTER `op_order_id`;
ALTER TABLE `tbl_shops` ADD `shop_rfq_enabled` TINYINT NOT NULL AFTER `shop_has_valid_subscription`;

ALTER TABLE `tbl_seller_products` ADD `selprod_rfq_enabled` TINYINT(4) NOT NULL AFTER `selprod_urlrewrite_id`;

INSERT INTO `tbl_email_templates` (`etpl_code`, `etpl_lang_id`, `etpl_name`, `etpl_subject`, `etpl_body`, `etpl_replacements`, `etpl_status`) VALUES ('NEW_RFQ_ASSIGNED', '1', 'Request for New Quotation Assigned', 'Request for New Quotation Assigned', '<table width="600px" cellspacing="0" cellpadding="0" style="margin: 0 auto; table-layout: fixed; background: #ffffff; border-radius: 4px; box-shadow: 0 0 10px rgba(0, 0, 0, 0.04)">
    <tbody>
        <tr>
            <td style="background:#fff;padding:20px 0 10px; text-align:center;">
                <h4
                    style="font-weight:normal; text-transform:uppercase; color:#999;margin:0; padding:10px 0; font-size:18px;">
                </h4>
                <h2 style="margin:0; font-size:34px; padding:0;">Request for New Quotation Assigned</h2>
            </td>
        </tr>
        <tr>
            <td style="background:#fff;padding:0 30px; text-align:center; color:#999;vertical-align:top;">
                <table width="100%" border="0" align="center" cellpadding="0" cellspacing="0">
                    <tbody>
                        <tr>
                            <td style="padding:20px 0 30px;"><strong style="font-size:18px;color:#333;">Dear {shop_user_name}
                                </strong><br />
                                A new request for quotation has been assigned to you
                                <br />
                                Please find the RFQ information below.</td>
                        </tr>
                        <tr>
                            <td style="padding:0 0 30px;">{rfq_table}</td>
                        </tr>
                    </tbody>
                </table>
            </td>
        </tr>
    </tbody>
</table>', '{rfq_table} RFQ information table<br>\r\n{social_media_icons} <br>\r\n{contact_us_url} <br>\r\n', '1')
ON DUPLICATE KEY UPDATE etpl_subject = VALUES(etpl_subject), etpl_body = VALUES(etpl_body);

INSERT INTO `tbl_sms_templates` (`stpl_code`, `stpl_lang_id`, `stpl_name`, `stpl_body`, `stpl_replacements`, `stpl_status`) VALUES 
('NEW_RFQ_ASSIGNED',1,'Request for New Quotation Assigned','Hello {shop_user_name},\r\nA new RFQ is assigned for {rfq_title} ({rfq_number}) with quantity {qty}.\r\n\r\n{SITE_NAME} Team','[{\"title\":\"RFQ Title\", \"variable\":\"{rfq_title}\"}, {\"title\":\"Quantity\", \"variable\":\"{qty}\"}, {\"title\":\"Website Name\", \"variable\":\"{SITE_NAME}\"}]',1)
ON DUPLICATE KEY UPDATE stpl_body = VALUES(stpl_body), stpl_replacements = VALUES(stpl_replacements);

INSERT INTO `tbl_email_templates` (`etpl_code`, `etpl_lang_id`, `etpl_name`, `etpl_subject`, `etpl_body`, `etpl_replacements`, `etpl_status`) VALUES ('RFQ_DELETION', '1', 'Request for Quote Deletion', 'Request for Quote Deletion', '<table width="600px" cellspacing="0" cellpadding="0" style="margin: 0 auto; table-layout: fixed; background: #ffffff; border-radius: 4px; box-shadow: 0 0 10px rgba(0, 0, 0, 0.04)">
    <tbody>
        <tr>
            <td style="background:#fff;padding:20px 0 10px; text-align:center;">
                <h4
                    style="font-weight:normal; text-transform:uppercase; color:#999;margin:0; padding:10px 0; font-size:18px;">
                </h4>
                <h2 style="margin:0; font-size:34px; padding:0;">Request for Quote Deletion</h2>
            </td>
        </tr>
        <tr>
            <td style="background:#fff;padding:0 30px; text-align:center; color:#999;vertical-align:top;">
                <table width="100%" border="0" align="center" cellpadding="0" cellspacing="0">
                    <tbody>
                        <tr>
                            <td style="padding:20px 0 30px;"><strong style="font-size:18px;color:#333;">Dear {user_name}
                                </strong><br />
                                Your request for quote has been deleted by the site <a href="{website_url}">{website_name}</a> Admin.
                                <br />
                                Please find the RFQ information below.</td>
                        </tr>
                        <tr>
                            <td style="padding:0 0 30px;">{rfq_table}</td>
                        </tr>
                    </tbody>
                </table>
            </td>
        </tr>
    </tbody>
</table>', '{user_name} Buyer/Seller Name<br>\r\n{approval_status} RFQ approval status<br>\r\n{rfq_table} RFQ information table<br>\r\n{website_name} Name of our website<br>\r\n{website_url} URL of our website<br>\r\n{social_media_icons} <br>\r\n{contact_us_url} <br>\r\n', '1')
ON DUPLICATE KEY UPDATE etpl_subject = VALUES(etpl_subject), etpl_body = VALUES(etpl_body);

INSERT INTO `tbl_sms_templates` (`stpl_code`, `stpl_lang_id`, `stpl_name`, `stpl_body`, `stpl_replacements`, `stpl_status`) VALUES 
('RFQ_DELETION',1,'Request for Quote Deletion','Dear {user_name},\r\nRFQ was received for {rfq_title} ({rfq_number}) with quantity {qty} has been deleted by the Admin.\r\n\r\n{SITE_NAME} Team','[{\"title\":\"Buyer/Seller Name\", \"variable\":\"{user_name}\"},{\"title\":\"RFQ Title\", \"variable\":\"{rfq_title}\"}, {\"title\":\"Quantity\", \"variable\":\"{qty}\"}, {\"title\":\"Website Name\", \"variable\":\"{SITE_NAME}\"}]',1)
ON DUPLICATE KEY UPDATE stpl_body = VALUES(stpl_body), stpl_replacements = VALUES(stpl_replacements);

ALTER TABLE `tbl_collections` CHANGE `collection_identifier` `collection_identifier` VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL;

INSERT INTO `tbl_collections` (`collection_identifier`, `collection_type`, `collection_criteria`, `collection_primary_records`, `collection_child_records`, `collection_display_order`, `collection_active`, `collection_deleted`, `collection_link_url`, `collection_layout_type`, `collection_display_media_only`, `collection_for_web`, `collection_for_app`) VALUES
('Content Block 1', 11, 0, 0, 0, 3, 1, 0, '', 16, 0, 1, 1),
('Content Block 2', 11, 0, 0, 0, 2, 1, 0, '', 31, 0, 1, 1)
ON DUPLICATE KEY UPDATE collection_identifier = VALUES(collection_identifier);

INSERT INTO `tbl_collections_lang` (`collectionlang_collection_id`, `collectionlang_lang_id`, `collection_name`, `collection_description`, `collection_link_caption`) VALUES
((SELECT collection_id FROM `tbl_collections` WHERE collection_type = 11 AND collection_layout_type  = 16 AND collection_identifier = 'Content Block 1'), 1, 'Content Block 1', '\r\n<section class=\"section security-floor\" data-collection=\"collection-cms\" style=\"background-image:url(\'/images/bg/security.png\')\">    \r\n	<div class=\"container\">        \r\n		<div class=\"section-head\">            \r\n			<div class=\"section-heading\">                \r\n				<h2>Trade confidently, backed by our dedication to top-notch production and purchase safeguards.</h2>            </div>        </div>        \r\n		<div class=\"section-body\">            \r\n			<div class=\"row\">                \r\n				<div class=\"col-lg-6\">                    \r\n					<div class=\"security-floor-card\">                        <span class=\"security-floor-tag\"> Lorem ipsum dolor sit amet.</span>                      <img class=\"security-floor-icon\" src=\"/images/verified-supplier.png\" alt=\"\" width=\"\" height=\"\" />                        \r\n						<p class=\"security-floor-desc\">Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua..</p>                        <a class=\"btn btn-outline-white\" href=\"\">Learn more</a>                    </div>                </div>                \r\n				<div class=\"col-lg-6\">                    \r\n					<div class=\"security-floor-card\">                        <span class=\"security-floor-tag\">Lorem ipsum dolor sit amet,\r\n                        </span>                      <img class=\"security-floor-icon\" src=\"/images/trade-assurance.png\" alt=\"\" width=\"\" height=\"\" />                        \r\n						<p class=\"security-floor-desc\"> Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.\r\n                        </p>                        <a class=\"btn btn-outline-white\" href=\"#\">Learn more</a>                    </div>                </div>            </div>        </div>    </div></section>', ''),
((SELECT collection_id FROM `tbl_collections` WHERE collection_type = 11 AND collection_layout_type  = 31 AND collection_identifier = 'Content Block 2'), 1, 'Content Block 2', '\r\n<section class=\"section\" data-collection=\"collection-cms\">    \r\n	<div class=\"container\">        \r\n		<div class=\"section-head section-head-space\">            \r\n			<div class=\"section-heading\">                \r\n				<h2>                    Discover countless offerings curated to suit your business requirements.</h2>            </div>            \r\n			<div class=\"section-action\">                \r\n				<div class=\"category-number\">                    \r\n					<div class=\"category-number-item\">                        <span class=\"number\">100M+</span>                        \r\n						<p>products</p>                    </div>                    \r\n					<div class=\"category-number-item\"><span class=\"number\">80K+</span>                        \r\n						<p>suppliers</p>                    </div>                    \r\n					<div class=\"category-number-item\"><span class=\"number\">4,250</span>                        \r\n						<p>product categories\r\n                        </p>                    </div>                    \r\n					<div class=\"category-number-item\"><span class=\"number\">100+</span>                        \r\n						<p>countries and regions</p>                    </div>                </div>            </div>        </div>    </div></section>', '')
ON DUPLICATE KEY UPDATE collection_name = VALUES(collection_name), collection_description = VALUES(collection_description);

ALTER TABLE `tbl_rfq_latest_offers` ADD `rlo_seller_acceptance` TINYINT NOT NULL AFTER `rlo_accepted_offer_id`, ADD `rlo_buyer_acceptance` TINYINT NOT NULL AFTER `rlo_seller_acceptance`;

ALTER TABLE `tbl_rfq` ADD `rfq_visibility_type` TINYINT NOT NULL DEFAULT '2' AFTER `rfq_lang_id`;
UPDATE `tbl_rfq` SET `rfq_visibility_type`='1' WHERE `rfq_selprod_id` = 0 AND `rfq_product_id` = 0;
ALTER TABLE `tbl_rfq` ADD `rfq_prodcat_id` INT NOT NULL AFTER `rfq_title`;
ALTER TABLE `tbl_rfq` ADD `rfq_product_type` TINYINT NOT NULL AFTER `rfq_number`;

INSERT INTO `tbl_language_labels` ( `label_key`, `label_lang_id`, `label_caption`, `label_type`) VALUES 
('LBL_DOWNLOAD_RFQ_COPY', 1, 'Download RFQ Copy', 1)
ON DUPLICATE KEY UPDATE label_caption = VALUES(label_caption);


ALTER TABLE `tbl_seller_packages`  ADD `spackage_rfq_offers_allowed` INT NOT NULL  AFTER `spackage_free_trial_days`;
ALTER TABLE `tbl_order_seller_subscriptions`  ADD `ossubs_rfq_offers_allowed` INT NOT NULL  AFTER `ossubs_inventory_allowed`;

ALTER TABLE `tbl_rfq_latest_offers`  ADD `rlo_added_on` DATE NOT NULL  AFTER `rlo_deleted`;

UPDATE `tbl_rfq_latest_offers`
INNER JOIN tbl_rfq_offers ON rlo_primary_offer_id = offer_id
SET rlo_added_on = offer_added_on;

ALTER TABLE `tbl_rfq_offers` CHANGE `offer_quantity` `offer_quantity` INT NULL;

INSERT INTO `tbl_email_templates` (`etpl_code`, `etpl_lang_id`, `etpl_name`, `etpl_subject`, `etpl_body`, `etpl_replacements`, `etpl_status`) VALUES ('RFQ_OFFER_ACCEPTED_BY_BUYER', '1', 'RFQ Offer Action By Buyer', 'RFQ ({rfq_number}) offer from other Seller accepted by Buyer', '<table width="600px" cellspacing="0" cellpadding="0" style="margin: 0 auto; table-layout: fixed; background: #ffffff; border-radius: 4px; box-shadow: 0 0 10px rgba(0, 0, 0, 0.04)">
    <tbody>
        <tr>
            <td style="background:#fff;padding:20px 0 10px; text-align:center;">
                <h4
                    style="font-weight:normal; text-transform:uppercase; color:#999;margin:0; padding:10px 0; font-size:18px;">
                </h4>
                <h2 style="margin:0; font-size:34px; padding:0;">RFQ ({rfq_number}) offer from other seller accepted by Buyer</h2>
            </td>
        </tr>
        <tr>
            <td style="background:#fff;padding:0 30px; text-align:center; color:#999;vertical-align:top;">
                <table width="100%" border="0" align="center" cellpadding="0" cellspacing="0">
                    <tbody>
                        <tr>
                            <td style="padding:20px 0 30px;">
                                <strong style="font-size:18px;color:#333;">Dear {shop_name} Seller,</strong><br />
                                Buyer {user_name} has accepted RFQ ({rfq_number}) offer from other seller.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </td>
        </tr>
    </tbody>
</table>', '{rfq_number} RFQ Number<br>\r\n{shop_name} Seller`s Shop<br>\r\n{user_name} Buyer Name<br>\r\n{website_name} Name of our website<br>\r\n{website_url} URL of our website<br>\r\n{social_media_icons} <br>\r\n{contact_us_url} <br>\r\n', '1')
ON DUPLICATE KEY UPDATE etpl_subject = VALUES(etpl_subject), etpl_body = VALUES(etpl_body), etpl_replacements = VALUES(etpl_replacements);

INSERT INTO `tbl_sms_templates` (`stpl_code`, `stpl_lang_id`, `stpl_name`, `stpl_body`, `stpl_replacements`, `stpl_status`) VALUES 
('RFQ_OFFER_ACCEPTED_BY_BUYER',1,'RFQ offer accepted by Buyer','Dear {shop_name} Seller,\r\n{user_name} has accepted RFQ ({rfq_number}) offer from other seller. \r\n\r\n{SITE_NAME} Team','[{\"title\":\"RFQ Number\", \"variable\":\"{rfq_number}\"},{\"title\":\"Seller`s Shop\", \"variable\":\"{shop_name}\"},{\"title\":\"Buyer Name\", \"variable\":\"{user_name}\"}, {\"title\":\"Website Name\", \"variable\":\"{SITE_NAME}\"}]',1)
ON DUPLICATE KEY UPDATE stpl_body = VALUES(stpl_body), stpl_replacements = VALUES(stpl_replacements);


INSERT INTO `tbl_email_templates` (`etpl_code`, `etpl_lang_id`, `etpl_name`, `etpl_subject`, `etpl_body`, `etpl_replacements`, `etpl_status`) VALUES ('RFQ_OFFER_ACCEPTED_BY_SELLER', '1', 'RFQ Offer Action By Buyer', 'RFQ ({rfq_number}) counter offer from {user_name} accepted by another Seller', '<table width="600px" cellspacing="0" cellpadding="0" style="margin: 0 auto; table-layout: fixed; background: #ffffff; border-radius: 4px; box-shadow: 0 0 10px rgba(0, 0, 0, 0.04)">
    <tbody>
        <tr>
            <td style="background:#fff;padding:0 30px; text-align:center; color:#999;vertical-align:top;">
                <table width="100%" border="0" align="center" cellpadding="0" cellspacing="0">
                    <tbody>
                        <tr>
                            <td style="padding:20px 0 30px;">
                                <strong style="font-size:18px;color:#333;">Dear {shop_name} Seller,</strong><br />
                                Counter offer from {user_name} accepted by another Seller for RFQ ({rfq_number}).
                            </td>
                        </tr>
                    </tbody>
                </table>
            </td>
        </tr>
    </tbody>
</table>', '{rfq_number} RFQ Number<br>\r\n{shop_name} Seller`s Shop<br>\r\n{user_name} Buyer Name<br>\r\n{website_name} Name of our website<br>\r\n{website_url} URL of our website<br>\r\n{social_media_icons} <br>\r\n{contact_us_url} <br>\r\n', '1')
ON DUPLICATE KEY UPDATE etpl_subject = VALUES(etpl_subject), etpl_body = VALUES(etpl_body), etpl_replacements = VALUES(etpl_replacements);

INSERT INTO `tbl_sms_templates` (`stpl_code`, `stpl_lang_id`, `stpl_name`, `stpl_body`, `stpl_replacements`, `stpl_status`) VALUES 
('RFQ_OFFER_ACCEPTED_BY_SELLER',1,'RFQ offer accepted by another Seller','Dear {shop_name} Seller,\r\n{Counter offer from {user_name} accepted by another Seller for RFQ ({rfq_number}). \r\n\r\n{SITE_NAME} Team','[{\"title\":\"RFQ Number\", \"variable\":\"{rfq_number}\"},{\"title\":\"Seller`s Shop\", \"variable\":\"{shop_name}\"},{\"title\":\"Buyer Name\", \"variable\":\"{user_name}\"}, {\"title\":\"Website Name\", \"variable\":\"{SITE_NAME}\"}]',1)
ON DUPLICATE KEY UPDATE stpl_body = VALUES(stpl_body), stpl_replacements = VALUES(stpl_replacements);

UPDATE `tbl_email_templates` SET `etpl_priority` = '5' WHERE `tbl_email_templates`.`etpl_code` IN ('NEW_RFQ', 'RFQ_APPROVAL', 'RFQ_DELETION', 'NEW_RFQ_OFFER', 'NEW_RFQ_ASSIGNED', 'RFQ_OFFER_ACTION_BUYER', 'COUNTER_RFQ_OFFER_BUYER', 'RFQ_OFFER_ACTION_SELLER', 'COUNTER_RFQ_OFFER_SELLER', 'RFQ_OFFER_ACCEPTED_BY_BUYER', 'RFQ_OFFER_ACCEPTED_BY_SELLER');

ALTER TABLE `tbl_rfq_offer_messages`  ADD `rom_read` TINYINT(2) NOT NULL  AFTER `rom_buyer_access`;

ALTER TABLE `tbl_seller_products` DROP `selprod_rfq_enabled`;
ALTER TABLE `tbl_seller_products` ADD `selprod_cart_type` TINYINT NOT NULL AFTER `selprod_fulfillment_type`;


INSERT INTO `tbl_email_templates` (`etpl_code`, `etpl_lang_id`, `etpl_name`, `etpl_subject`, `etpl_body`, `etpl_replacements`, `etpl_status`) VALUES ('RFQ_OFFER_ACCEPTED_BY_SELLER', '1', 'RFQ Offer Action By Seller', 'Final confirmation for the RFQ ({rfq_number}) by another Seller', '<table width="600px" cellspacing="0" cellpadding="0" style="margin: 0 auto; table-layout: fixed; background: #ffffff; border-radius: 4px; box-shadow: 0 0 10px rgba(0, 0, 0, 0.04)">
    <tbody>
        <tr>
            <td style="background:#fff;padding:0 30px; text-align:center; color:#999;vertical-align:top;">
                <table width="100%" border="0" align="center" cellpadding="0" cellspacing="0">
                    <tbody>
                        <tr>
                            <td style="padding:20px 0 30px;">
                                <strong style="font-size:18px;color:#333;">Dear {shop_name} Seller,</strong><br />
                                Another seller shared a final confirmation for the RFQ ({rfq_number}) of {user_name}.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </td>
        </tr>
    </tbody>
</table>', '{rfq_number} RFQ Number<br>\r\n{shop_name} Seller`s Shop<br>\r\n{user_name} Buyer Name<br>\r\n{website_name} Name of our website<br>\r\n{website_url} URL of our website<br>\r\n{social_media_icons} <br>\r\n{contact_us_url} <br>\r\n', '1')
ON DUPLICATE KEY UPDATE etpl_name = VALUES(etpl_name), etpl_subject = VALUES(etpl_subject), etpl_body = VALUES(etpl_body), etpl_replacements = VALUES(etpl_replacements);

INSERT INTO `tbl_sms_templates` (`stpl_code`, `stpl_lang_id`, `stpl_name`, `stpl_body`, `stpl_replacements`, `stpl_status`) VALUES 
('RFQ_OFFER_ACCEPTED_BY_SELLER',1,'Final confirmation for the RFQ by another Seller','Dear {shop_name} Seller,\r\nAnother seller shared a final confirmation for the RFQ ({rfq_number}) of {user_name}. \r\n\r\n{SITE_NAME} Team','[{\"title\":\"RFQ Number\", \"variable\":\"{rfq_number}\"},{\"title\":\"Seller`s Shop\", \"variable\":\"{shop_name}\"},{\"title\":\"Buyer Name\", \"variable\":\"{user_name}\"}, {\"title\":\"Website Name\", \"variable\":\"{SITE_NAME}\"}]',1)
ON DUPLICATE KEY UPDATE stpl_name = VALUES(stpl_name), stpl_body = VALUES(stpl_body), stpl_replacements = VALUES(stpl_replacements);
-- ----------------------TV-10.1.0.20240926------------------------------------


ALTER TABLE `tbl_collections`  ADD `collection_full_width` TINYINT(1) NOT NULL  AFTER `collection_for_app`;

INSERT INTO `tbl_email_templates` (`etpl_code`, `etpl_lang_id`, `etpl_name`, `etpl_subject`, `etpl_body`, `etpl_replacements`, `etpl_priority`, `etpl_status`) VALUES ('admin-gift-card', '1', 'Gift Card Order Placed', 'A new gift card order was placed', '<table width=\"600px\" cellspacing=\"0\" cellpadding=\"0\" style=\"margin: 0 auto; table-layout: fixed; background: #ffffff; border-radius: 4px; box-shadow: 0 0 10px rgba(0, 0, 0, 0.04)\">\r\n    <tbody>\r\n        <tr>\r\n            <td style=\"background:#fff;padding:0 30px; text-align:center; color:#999;vertical-align:top;\">\r\n                <table width=\"100%\" border=\"0\" align=\"center\" cellpadding=\"0\" cellspacing=\"0\">\r\n                    <tbody>\r\n                        <tr>\r\n                            <td style=\"background:#fff;padding:20px 0 10px; text-align:center;\">\r\n                                <h4 style=\"font-weight:normal; text-transform:uppercase; color:#999;margin:0; padding:10px 0; font-size:18px;\">Gift Card</h4>\r\n\r\n                            </td>\r\n                        </tr>\r\n                        <tr>\r\n                            <td style=\"padding:20px 0 30px;\"><strong style=\"font-size:18px;color:#333;\">Dear Admin</strong><br /> A new gift card order was placed. Below are the details::</td>\r\n                        </tr>\r\n                        <tr>\r\n                            <table style=\"border:1px solid #ddd; border-collapse:collapse;\" cellspacing=\"0\" cellpadding=\"0\" border=\"0\">\r\n                                <tbody>\r\n                                    <tr>\r\n                                        <td style=\"padding:10px;font-size:13px;border:1px solid #ddd; color:#333; font-weight:bold;\" width=\"153\">Sender Name</td>\r\n                                        <td style=\"padding:10px;font-size:13px; color:#333;border:1px solid #ddd;\" width=\"620\">{sender_name}</td>\r\n                                    </tr>\r\n                                    <tr>\r\n                                        <td style=\"padding:10px;font-size:13px;border:1px solid #ddd; color:#333; font-weight:bold;\" width=\"153\">Receiver Name<span class=\"Apple-tab-span\" style=\"white-space:pre\"></span></td>\r\n                                        <td style=\"padding:10px;font-size:13px; color:#333;border:1px solid #ddd;\" width=\"620\">{recipient_name}</td>\r\n                                    </tr>\r\n                                    <tr>\r\n                                        <td style=\"padding:10px;font-size:13px;border:1px solid #ddd; color:#333; font-weight:bold;\" width=\"153\">Receiver Email</td>\r\n                                        <td style=\"padding:10px;font-size:13px; color:#333;border:1px solid #ddd;\" width=\"620\">{recipient_email}</td>\r\n                                    </tr>\r\n                                    <tr>\r\n                                        <td style=\"padding:10px;font-size:13px;border:1px solid #ddd; color:#333; font-weight:bold;\" width=\"153\">Amount</td>\r\n                                        <td style=\"padding:10px;font-size:13px; color:#333;border:1px solid #ddd;\" width=\"620\">{giftcard_amount}</td>\r\n                                    </tr>\r\n                                    <tr>\r\n                                        <td style=\"padding:10px;font-size:13px;border:1px solid #ddd; color:#333; font-weight:bold;\" width=\"153\">Code</td>\r\n                                        <td style=\"padding:10px;font-size:13px; color:#333;border:1px solid #ddd;\" width=\"620\">{giftcard_code}</td>\r\n                                    </tr>\r\n                                </tbody>\r\n                            </table>\r\n                        </tr>\r\n\r\n                    </tbody>\r\n                </table>\r\n            </td>\r\n        </tr>\r\n    </tbody>\r\n</table>', '\r\n{recipient_email} Receiver Email<br/> {recipient_name} Receiver Name<br/> {giftcard_code} Redeem Code <br/> {sender_name} Sender Name <br/> {giftcard_amount} Total Amount <br/>', '5', '1');
/* GIFT CARDS */

INSERT IGNORE INTO `tbl_language_labels` ( `label_key`, `label_lang_id`, `label_caption`, `label_type`) VALUES 
('ERR_PACKAGE_SUPPORT_MAXIMUM_UP_TO_{PROD-CNT}_PRODUCTS_AND_{INV-CNT}_INVENTORIES._MARK_ALL_THE_INVENTORIES_INACTIVE', 1, 'This package support maximum up to {PROD-CNT} products and {INV-CNT} inventories. Please mark additional inventories as inactive before buying a plan.', 1)
ON DUPLICATE KEY UPDATE label_caption = VALUES(label_caption);


INSERT INTO `tbl_email_templates` (`etpl_code`, `etpl_lang_id`, `etpl_name`, `etpl_subject`, `etpl_body`, `etpl_replacements`, `etpl_priority`, `etpl_status`) VALUES ('receiver-gift-card', '1', 'Gift Card Order Placed', 'A new gift card order was placed', '<table width="600px" cellspacing="0" cellpadding="0" style="margin: 0 auto; table-layout: fixed; background: #ffffff; border-radius: 4px; box-shadow: 0 0 10px rgba(0, 0, 0, 0.04)">
    <tbody>
        <tr>
            <td style="background:#fff;padding:0 30px; text-align:center; color:#999;vertical-align:top;">
                <table width="100%" border="0" align="center" cellpadding="0" cellspacing="0">
                    <tbody>
                        <tr>
                            <td style="background:#fff;padding:20px 0 10px; text-align:center;">
                                <h4 style="font-weight:normal; text-transform:uppercase; color:#999;margin:0; padding:10px 0; font-size:18px;">Gift Card</h4>
                            </td>
                        </tr>
                        <tr>
                            <td style="padding:20px 0 30px;"><strong style="font-size:18px;color:#333;">Dear {recipient_name}</strong><br /> A new gift card order was placed. Below are the details::</td>
                        </tr>
                        <tr>
                            <table style="border:1px solid #ddd; border-collapse:collapse;" cellspacing="0" cellpadding="0" border="0">
                                <tbody>
                                    <tr>
                                        <td style="padding:10px;font-size:13px;border:1px solid #ddd; color:#333; font-weight:bold;" width="153">Sender</td>
                                        <td style="padding:10px;font-size:13px; color:#333;border:1px solid #ddd;" width="620">{sender_name}<br>({sender_email})</td>
                                    </tr>
                                    <tr>
                                        <td style="padding:10px;font-size:13px;border:1px solid #ddd; color:#333; font-weight:bold;" width="153">Receiver Name<span class="Apple-tab-span" style="white-space:pre"></span></td>
                                        <td style="padding:10px;font-size:13px; color:#333;border:1px solid #ddd;" width="620">{recipient_name}</td>
                                    </tr>
                                    <tr>
                                        <td style="padding:10px;font-size:13px;border:1px solid #ddd; color:#333; font-weight:bold;" width="153">Code</td>
                                        <td style="padding:10px;font-size:13px; color:#333;border:1px solid #ddd;" width="620">{giftcard_code}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </tr>
                    </tbody>
                </table>
            </td>
        </tr>
    </tbody>
</table>', '{recipient_name} Receiver Name<br/> {sender_name} Sender Name <br/> {sender_email} Sender Email <br/> {giftcard_code} Redeem Code <br/>', '5', '1')
ON DUPLICATE KEY UPDATE etpl_subject = VALUES(etpl_subject), etpl_body = VALUES(etpl_body), etpl_replacements = VALUES(etpl_replacements);


INSERT INTO `tbl_email_templates` (`etpl_code`, `etpl_lang_id`, `etpl_name`, `etpl_subject`, `etpl_body`, `etpl_replacements`, `etpl_priority`, `etpl_status`) VALUES ('admin-gift-card', '1', 'Gift Card Order Placed', 'A new gift card order was placed', '<table width="600px" cellspacing="0" cellpadding="0" style="margin: 0 auto; table-layout: fixed; background: #ffffff; border-radius: 4px; box-shadow: 0 0 10px rgba(0, 0, 0, 0.04)">
    <tbody>
        <tr>
            <td style="background:#fff;padding:0 30px; text-align:center; color:#999;vertical-align:top;">
                <table width="100%" border="0" align="center" cellpadding="0" cellspacing="0">
                    <tbody>
                        <tr>
                            <td style="background:#fff;padding:20px 0 10px; text-align:center;">
                                <h4 style="font-weight:normal; text-transform:uppercase; color:#999;margin:0; padding:10px 0; font-size:18px;">Gift Card</h4>
                            </td>
                        </tr>
                        <tr>
                            <td style="padding:20px 0 30px;"><strong style="font-size:18px;color:#333;">Dear Admin</strong><br /> A new gift card order was placed. Below are the details::</td>
                        </tr>
                        <tr>
                            <table style="border:1px solid #ddd; border-collapse:collapse;" cellspacing="0" cellpadding="0" border="0">
                                <tbody>
                                    <tr>
                                        <td style="padding:10px;font-size:13px;border:1px solid #ddd; color:#333; font-weight:bold;" width="153">Sender</td>
                                        <td style="padding:10px;font-size:13px; color:#333;border:1px solid #ddd;" width="620">{sender_name}<br>({sender_email})</td>
                                    </tr>
                                    <tr>
                                        <td style="padding:10px;font-size:13px;border:1px solid #ddd; color:#333; font-weight:bold;" width="153">Receiver<span class="Apple-tab-span" style="white-space:pre"></span></td>
                                        <td style="padding:10px;font-size:13px; color:#333;border:1px solid #ddd;" width="620">{recipient_name}<br>({recipient_email})</td>
                                    </tr>
                                    <tr>
                                        <td style="padding:10px;font-size:13px;border:1px solid #ddd; color:#333; font-weight:bold;" width="153">Amount</td>
                                        <td style="padding:10px;font-size:13px; color:#333;border:1px solid #ddd;" width="620">{giftcard_amount}</td>
                                    </tr>
                                    <tr>
                                        <td style="padding:10px;font-size:13px;border:1px solid #ddd; color:#333; font-weight:bold;" width="153">Code</td>
                                        <td style="padding:10px;font-size:13px; color:#333;border:1px solid #ddd;" width="620">{giftcard_code}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </tr>
                    </tbody>
                </table>
            </td>
        </tr>
    </tbody>
</table>', '\r\n{recipient_email} Receiver Email<br/> {recipient_name} Receiver Name<br/> {giftcard_code} Redeem Code <br/> {sender_name} Sender Name <br/>{sender_email} Sender Email <br/> {giftcard_amount} Total Amount <br/>', '5', '1')
ON DUPLICATE KEY UPDATE etpl_subject = VALUES(etpl_subject), etpl_body = VALUES(etpl_body), etpl_replacements = VALUES(etpl_replacements);

-- ----------------------TV-10.1.0.20240926------------------------------------

UPDATE tbl_shops INNER JOIN tbl_users ON user_id = shop_user_id SET shop_has_valid_subscription = 1 WHERE user_has_valid_subscription = 1;

INSERT INTO `tbl_email_templates` (`etpl_code`, `etpl_lang_id`, `etpl_name`, `etpl_subject`, `etpl_body`, `etpl_replacements`, `etpl_priority`, `etpl_status`) VALUES
('admin_new_user_creation_email', 1, 'New Account Created By Admin', 'Welcome to {website_name}', '\r\n<table width=\"100%\" align=\"center\" cellpadding=\"0\" cellspacing=\"0\">    \r\n</table>\r\n<table width=\"600px\" cellspacing=\"0\" cellpadding=\"0\" style=\"margin: 0 auto; table-layout: fixed; background: #ffffff; border-radius: 4px; box-shadow: 0 0 10px rgba(0, 0, 0, 0.04)\">\r\n	<tbody>\r\n		<tr>                        \r\n			<td style=\"background:#fff;padding:20px 0 10px; text-align:center;\">                            \r\n				<h4 style=\"font-weight:normal; text-transform:uppercase; color:#999;margin:0; padding:10px 0; font-size:18px;\"></h4>                            \r\n				<h2 style=\"margin:0; font-size:34px; padding:0;\">Welcome to {website_name}</h2></td>                    \r\n		</tr> \r\n		<tr>                        \r\n			<td style=\"background:#fff;padding:0 30px; text-align:center; color:#999;vertical-align:top;\">                            \r\n				<table width=\"100%\" border=\"0\" align=\"center\" cellpadding=\"0\" cellspacing=\"0\">                                \r\n					<tbody>                                    \r\n						<tr>                                        \r\n							<td style=\"padding:20px 0 30px;\"><strong style=\"font-size:18px;color:#333;\">Dear {user_full_name} </strong><br />\r\n								<a href=\"{website_url}\">{website_name}</a> admin has created an {account_type} account for you.</td>                                    \r\n						</tr>                                    \r\n						<tr>                                        \r\n							<td style=\"padding:20px 0 30px;\">To access and verify your account please visit the link given below. Your email address will be your username. \r\n								Please note that the link is valid for next {days} days.<br />\r\n								<a href=\"{reset_url}\">{reset_url}</a>.</td>                                    \r\n						</tr>                                \r\n					</tbody>                            \r\n				</table></td>                    \r\n		</tr> \r\n	</tbody>\r\n</table>', '{user_full_name} Name of the email receiver<br>\r\n{user_email} User Email <br>\r\n{account_type} Account Type <br>\r\n{days} Days after which link expire\r\n{website_name} Name of our website<br>\r\n{website_url} URL of our website<br>\r\n{reset_url} URL to reset the password<br>\r\n{social_media_icons} <br>\r\n{contact_us_url} <br>', 5, 1)
ON DUPLICATE KEY UPDATE etpl_subject = VALUES(etpl_subject), etpl_body = VALUES(etpl_body), etpl_replacements = VALUES(etpl_replacements);

ALTER TABLE `tbl_content_pages`  ADD `cpage_hide_header_footer` TINYINT(1) NOT NULL  AFTER `cpage_layout`;
ALTER TABLE `tbl_seller_products`  ADD `selprod_hide_price` TINYINT(1) NOT NULL  AFTER `selprod_price`;

ALTER TABLE `tbl_rfq_latest_offers` CHANGE `rlo_added_on` `rlo_added_on` DATETIME NOT NULL;

UPDATE `tbl_rfq_latest_offers`
INNER JOIN tbl_rfq_offers ON rlo_primary_offer_id = offer_id
SET rlo_added_on = offer_added_on;

INSERT INTO `tbl_language_labels` ( `label_key`, `label_lang_id`, `label_caption`, `label_type`) VALUES
("APP_REQUEST_A_QUOTE", 1, "Request a Quote", 2),
("APP_APPLY", 1, "Apply", 2),
("APP_PROCEED_TO_ADD_SHIPPING_ADDRESS", 1, "Proceed to Add Shipping Address", 2),
("APP_REQUESTED_QUANTITY", 1, "Required Quantity", 2),
("APP_REQUEST_QUANTITY", 1, "Requested Quantity", 2),
("APP_ADD_QUANTITY", 1, "Add Quantity", 2),
("APP_ADD_UNIT", 1, "Add Unit", 2),
("APP_EXPECTED_DELIVERY_DATE", 1, "Expected Delivery Date", 2),
("APP_FILE_NAME", 1, "File Name", 2),
("APP_ADD_COMMENT1", 1, "Add Comment", 2),
("APP_REQUEST_FOR_QUOTE", 1, "Request For Quote", 2),
("APP_SEARCH_BY_RFQ_ID_PRODUCT_NAME", 1, "Search by RFQ ID , Product Name", 2),
("APP_ATTACHMENT", 1, "Attachment", 2),
("APP_CLOSE_RFQ", 1, "Close RFQ", 2),
("APP_OFFERS_LOG", 1, "Offers Log", 2),
("APP_NEGOTIATE", 1, "Negotiate", 2),
("APP_ACCEPT", 1, "Accept", 2),
("APP_BOOK_SERVICES", 1, "Book Services", 2),
("APP_GIFT_CARD", 1, "Gift Cards", 2),
("APP_ADD_GIFT_CARD", 1, "Add Gift Card + ", 2),
("APP_REDEEM_GIFT_CARD", 1, "Redeem Gift Card", 2),
("APP_GIFT_CODE", 1, "Gift Code", 2),
("APP_RECEIVER_NAME", 1, "Receiver Name", 2),
("APP_RECEIVER_EMAIL", 1, "Receiver Email", 2),
("APP_PAYMENT_STATUS", 1, "Payment Status", 2),
("APP_PAID", 1, "Paid", 2),
("APP_USED", 1, "Used", 2),
("APP_UNUSED", 1, "UnUsed", 2),
("APP_PENDING", 1, "Pending", 2),
("APP_ADD_GIFT_CARDS", 1, "Add Gift Cards", 2),
("APP_ADD_COMMENT", 1, "Add Comment", 2),
("APP_SEARCH_BY_RECEIVER_NAME", 1, "Search by receiver name , email or code", 2),
("APP_ENTER_RECEIVER_NAME", 1, "Enter Receiver Name", 2),
("APP_ENTER_RECEIVER_EMAIL", 1, "Enter Receiver Email", 2),
("APP_PLEASE_ENTER_AMOUNT", 1, "Please Enter Amount", 2),
("APP_PLEASE_ENTER_RECEIVER_NAME", 1, "Please Enter Receiver Name", 2),
("APP_PLEASE_ENTER_RECEIVER_EMAIL", 1, "Please Enter Receiver Email", 2),
("APP_ENTER_CODE", 1, "Enter Code", 2),
("APP_PLEASE_ENTER_CODE", 1, "Please Enter Code", 2),
("APP_REDEEM", 1, "Redeem", 2),
("APP_APPLY_FILTERS", 1, "Apply Filters", 2),
("APP_FROM_DATE", 1, "From Date", 2),
("APP_TO_DATE", 1, "To Date", 2),
("APP_CLEAR", 1, "Clear All", 2),
("APP_SELECT_STATUS", 1, "Select Status", 2),
("APP_GIFT_CARD_SUCCESS", 1, "Gift Card has been purchased successfully", 2),
("APP_GRAM", 1, "Gram", 2),
("APP_KILOGRAM", 1, "Kilogram", 2),
("APP_POUND", 1, "Pound", 2),
("APP_LITRE", 1, "Litre", 2),
("APP_PIECES", 1, "Piece", 2),
("APP_SUBMIT_RFQ", 1, "Submit RFQ", 2),
("APP_PLEASE_ENTER_QUANTITY", 1, "Please enter quantity", 2),
("APP_PLEASE_SELECT_UNIT", 1, "Please select unit", 2),
("APP_PLEASE_ENTER_COMMENT", 1, "Please enter comment", 2),
("APP_FILE", 1, "File", 2),
("APP_PRICE_PER", 1, "Price per", 2),
("APP_PRODUCT_NAME", 1, "Product Name", 2),
("APP_VIEW_OFFERS", 1, "View Offers", 2),
("APP_ADD_ATTACHMENT", 1, "Add Attachment", 2),
("APP_LOOKING_FOR", 1, "Looking for", 2),
("APP_PHYSICAL", 1, "Physical", 2),
("APP_TYPE_HERE", 1, "Type Here", 2),
("APP_SELECTED", 1, "Selected", 2),
("APP_FAVOURITE", 1, "Favourite", 2),
("APP_SELECT_SUPPLIERS", 1, "Select Supplier\'s", 2),
("APP_SUPPLIERS", 1, "Supplier\'s", 2),
("APP_PRODUCT_SERVICE_CATEGORY", 1, "Product Service Category", 2),
("APP_DIGITAL", 1, "Digital", 2),
("APP_SERVICE", 1, "Service", 2),
("APP_PUBLIC", 1, "Public", 2),
("APP_RFQ", 1, "RFQ", 2),
("APP_VIEW_RFQ_INFO", 1, "View RFQ info", 2),
("APP_MORE_ACTION", 1, "More Action", 2),
("APP_REPLY", 1, "Reply", 2),
("APP_OFFER_PRICE", 1, "Offer Price", 2),
("APP_AMOUNT", 1, "Amount", 2),
("APP_TARGET_SUPPLIERS", 1, "Target Suppliers", 2),
("APP_PLEASE_SELECT_SELLER", 1, "Please Select Seller", 2),
("APP_SELLER", 1, "Seller", 2),
("APP_SHIPPING_CHARGES", 1, "Shipping Charges", 2),
("APP_REJECT", 1, "Reject", 2),
("APP_TAKE_ACTION", 1, "Take Action", 2),
("APP_SHIPPING_RATES", 1, "Shipping Rates", 2),
("APP_WANT_TO_CLOSE_RFQ", 1, "Are you sure you want to Close Rfq", 2),
("APP_RFQ_CLOSE", 1, "This Rfq has been closed", 2),
("APP_RFQ_COUNTER_FORM", 1, "RFQ Counter Form", 2),
("APP_OFFER_QUANTITY", 1, "Offer Quantity", 2),
("APP_OFFER_PRICE_PER", 1, "Offer price per", 2),
("APP_COMMENTS_FOR_SELLER", 1, "Comments for seller", 2),
("APP_OFFER_QTY_ERROR", 1, "Offer Quantity is mandatory", 2),
("APP_OFFER_PRICE_ERROR", 1, "Offer Price is mandatory", 2),
("APP_BUY_NOW", 1, "Buy Now", 2),
("APP_THIS_OFFER_HAS_BEEN_ACCEPTED_BY_YOU", 1, "This offer has been accepted by you", 2),
("APP_THIS_OFFER_HAS_BEEN_REJECTED_BY_YOU", 1, "This offer has been rejected by you", 2),
("APP_YOUR_OFFER_HAS_BEEN_REJECTED_BY_SELLER", 1, "Your offer has been rejected by seller", 2),
("APP_YOUR_OFFER_HAS_BEEN_ACCEPTED_BY_SELLER", 1, "Your offer has been accepted by seller", 2),
("APP_SELLER_ACCEPTANCE_PENDING_FOR_THIS_OFFER", 1, "Seller Approval \nis Pending", 2),
("APP_ARE_YOU_SURE", 1, "Are you sure", 2),
("APP_THIS_OFFER_HAS_BEEN_REJECTED", 1, "This offer has been rejected", 2),
("APP_THIS_OFFER_HAS_BEEN_ACCEPTED", 1, "This offer has been accepted", 2),
("APP_DELIVERY_ADDRESS", 1, "Delivery Address", 2),
("APP_PRODUCT_TYPE", 1, "Product Type", 2),
("APP_PUBLIC_RFQ", 1, "Public Rfq", 2),
("APP_PRIVATE_RFQ", 1, "Private Rfq", 2),
("APP_SELECT_VISIBILITY", 1, "Select Visibility", 2),
("APP_NO_RECORD_FOUND", 1, "No Record Found", 2),
("APP_MESSAGE_MANDATORY", 1, "Message is Mandatory", 2),
("APP_MESSAGES_ATTACHMENTS", 1, "<![CDATA[Messages & Attachments", 2),
("APP_TOTAL_AMOUNT", 1, "Total Amount", 2),
("APP_PLEASE_ENTER_QUANTITY_UNIT", 1, "<![CDATA[Please enter quantity & select unit", 2),
("APP_EXPIRED", 1, "Expired", 2),
("APP_SELECT_CATEGORY", 1, "Select Category", 2),
("APP_SHIPPING_ADDRESS", 1, "Shipping Address", 2),
("APP_FILTER", 1, "Filters", 2),
("APP_GLOBAL", 1, "Global", 2),
("APP_TO", 1, 'To', 2),
("APP_REQUIRED_QUANTITY", 1, 'Required Quantity', 2),
("APP_ADD_DATE", 1, 'Add Date', 2),
("APP_UNIT", 1, 'Unit', 2),
("APP_PLEASE_ADD_MESSAGE", 1, 'Please add Message', 2),
("APP_COMMENT", 1, 'Comment', 2),
("APP_OFFERS_HISTORY", 1, 'Offers History', 2),
("APP_ARE_YOU_SURE_WANT_TO_BUY_THIS_OFFER", 1, 'Are you sure want to buy this offer', 2),
("APP_ACCEPT_OFFER", 1, 'Accept Offer', 2),
("APP_ARE_YOU_SURE_WANT_TO_ACCEPT_THIS_OFFER", 1, 'Are you sure want to accept this offer', 2),
("APP_REJECT_OFFER", 1, 'Reject offer', 2),
("APP_ARE_YOU_SURE_WANT_TO_REJECT_THIS_OFFER", 1, 'Are you sure want to reject this offer', 2),
("APP_REQUESTED_QUANTITY", 1, 'Requested quantity', 2),
("APP_SHOW_COMMENT", 1, 'Show comment', 2),
("APP_ORDER_STATUS", 1, 'Order status', 2),
("APP_MESSAGES", 1, 'Messages', 2),
("APP_EXPIRE_ON", 1, 'Expire on', 2),
("APP_ACCEPT", 1, 'Accept', 2),
("APP_EDIT", 1, 'Edit', 2),
("APP_QUANTITY", 1, 'Quantity', 2),
("APP_PLEASE_SELECT_QUANTITY", 1, 'Please select quantity', 2),
("APP_PLEASE_ADD_COMMENT", 1, 'Please add comment', 2),
("APP_PLEASE_SELECT_PRODUCT_TITLE", 1, 'Please select product title', 2),
("APP_PLEASE_ADD_ATLEAST_ONE_SELLER", 1, 'Please add atleast one seller', 2),
("APP_PLEASE_ADD_PRICE", 1, 'Please add price', 2),
("APP_OPEN", 1, 'Open', 2),
("APP_COUNTERED", 1, 'Countered', 2),
("APP_ORDER_ID", 1, 'Order Id', 2),
("APP_STATUS", 1, 'Status', 2),
("APP_GIFT_CARDS", 1, 'Gift Cards', 2),
("APP_ENTER_AMOUNT", 1, 'Enter Amount', 2),
("APP_SAVE", 1, 'Save', 2),
("APP_CLEAR_ALL", 1, 'Clear all', 2),
("APP_SELECT_OFFER_STATUS", 1, 'Select offer status', 2),
("APP_GM", 1, 'Gm', 2),
("APP_KG", 1, 'Kg', 2),
("APP_PND", 1, 'Pnd', 2),
("APP_LTR", 1, 'Ltr', 2),
("APP_PC", 1, 'Pc', 2),
("APP_CAMERA", 1, 'Camera', 2),
("APP_GALLERY", 1, 'Gallery', 2),
("APP_VIDEO", 1, 'Video', 2),
("APP_FILE", 1, 'File', 2),
("APP_CANCEL", 1, 'Cancel', 2)
ON DUPLICATE KEY UPDATE label_caption = VALUES(label_caption);

-- -----------089531 - The page language data is missing for "Request for Quotes" section. --------------------- --

INSERT INTO `tbl_pages_language_data`(
    `plang_key`,
    `plang_lang_id`,
    `plang_title`
)
VALUES
('MANAGE_REQUEST_FOR_QUOTE','-1','Request For Quote'),
('MANAGE_RFQ_OFFERS','-1','Offers')
ON DUPLICATE KEY UPDATE plang_key = VALUES(plang_key), plang_lang_id = VALUES(plang_lang_id), plang_title = VALUES(plang_title);

-- -------------------------- 089479 - Ui disruption in email template for order status change. ------------- --

UPDATE
    `tbl_email_templates`
SET
    `etpl_body` = '<table id=\"body\" width=\"100%\"> \r\n <tbody> \r\n <tr> \r\n <td style=\"padding: 30px 10px 50px; text-align: center\"> \r\n <h1 style=\"font-size: 40px; letter-spacing: -0.4px; margin: 0 0 30px 0; font-weight: 700; color: #212529\"> Order Item Status\r\n </h1> \r\n <p style=\"font-size: 16px; line-height: 1.5; letter-spacing: -0.32px; color: #212529; margin: 0 0 10px 0\"> Hello {user_full_name},\r\n </p> \r\n \r\n <p style=\"opacity: 0.6; font-size: 14px; letter-spacing: -0.28px; color: #212529; line-height: 1.71; margin: 0 0 20px 0\"> Your order item status has been changed to {new_order_status} for your Order Invoice Number {invoice_number} on&nbsp;<a href=\"{website_url}\">{website_name}</a>.</p> \r\n <p style=\"opacity: 0.6; font-size: 14px; letter-spacing: -0.28px; color: #212529; line-height: 1.71; margin: 0 0 20px 0\"> {order_items_table_format}\r\n </p> \r\n \r\n <p style=\"opacity: 0.6; font-size: 14px; letter-spacing: -0.28px; color: #212529; line-height: 1.71; margin: 0 0 20px 0\"> {shipment_information}\r\n </p> \r\n <p style=\"opacity: 0.6; font-size: 14px; letter-spacing: -0.28px; color: #212529; line-height: 1.71; margin: 0 0 20px 0\"> {order_admin_comments}\r\n </p> \r\n <p style=\"opacity: 0.6; font-size: 14px; letter-spacing: -0.28px; color: #212529; line-height: 1.71; margin: 0 0 20px 0\"> If you require any assistance in using our site, or have any feedback or suggestions, please email us at\r\n {CONTACT-EMAIL}\r\n </p> \r\n \r\n \r\n <p style=\"font-size: 14px; line-height: 1.71; letter-spacing: -0.28px; color: #212529; margin: 0\"> Thank\r\n You<br />\r\n Team {website_name}\r\n </p> </td> \r\n </tr> \r\n </tbody>\r\n</table>'
WHERE
    `tbl_email_templates`.`etpl_code` = 'child_order_status_change' AND `tbl_email_templates`.`etpl_lang_id` = 1;

INSERT INTO `tbl_language_labels` ( `label_key`, `label_lang_id`, `label_caption`, `label_type`) VALUES 
('LBL_ARE_YOU_SURE?_GLOBAL_RFQ_MODULE_WILL_BE_DISABLED', 1, 'Are you sure you want to turn it OFF? Global RFQ feature will be functional only if Main RFQ feature is ON!', 1),
('MSG_ORDER_#{ORDER_ID}_TXN._HAS_BEEN_{STATUS}', 1, 'Order #{order_id} txn. has been {status}', 1)
ON DUPLICATE KEY UPDATE label_caption = VALUES(label_caption);

DELETE FROM tbl_language_labels WHERE `label_key` = 'LBL_SHIPPING_CHARGED_WERE_NOT_DECLARED.';
INSERT INTO `tbl_language_labels` ( `label_key`, `label_lang_id`, `label_caption`, `label_type`) VALUES
("LBL_DIGITAL_TAG", 1, "DIGITAL", 1),
("LBL_DIGITAL_PRODUCT_TOOLTIP", 1, "Digital product", 1),
("LBL_SERVICE_TAG", 1, "SERVICE", 1),
("LBL_SERVICE_PRODUCT_TOOLTIP", 1, "It's a service", 1)
ON DUPLICATE KEY UPDATE label_caption = VALUES(label_caption);

ALTER TABLE `tbl_product_categories` ADD `prodcat_has_child` TINYINT(1) NOT NULL AFTER `prodcat_featured`;
UPDATE tbl_product_categories c
INNER JOIN (
   SELECT c2.prodcat_parent
    FROM tbl_product_categories c2
    WHERE c2.prodcat_active = 1 
      AND c2.prodcat_deleted = 0
    group by c2.prodcat_parent
) AS sub ON sub.prodcat_parent = c.prodcat_id
SET c.prodcat_has_child = 1;

ALTER TABLE `tbl_brands` ADD INDEX(`brand_active`);
ALTER TABLE `tbl_brands` ADD INDEX(`brand_deleted`);
ALTER TABLE `tbl_user_transactions` ADD INDEX(`utxn_status`);

INSERT INTO `tbl_configurations` (`conf_name`, `conf_val`) VALUES 
('CONF_ANALYTICS_ADVANCE_ECOMMERCE', 0)
ON DUPLICATE KEY UPDATE conf_val = VALUES(conf_val);


-- ------------------------task ----------------------------------- --
CREATE TABLE `tbl_app_release_versions` (
  `arv_id` int(11) NOT NULL,
  `arv_package_name` varchar(255) NOT NULL,
  `arv_app_name` varchar(255) NOT NULL,
  `arv_app_version` varchar(50) NOT NULL,
  `arv_app_type` int(1) NOT NULL COMMENT '1 => Android, 2 => Ios',
  `arv_store_url` varchar(255) NOT NULL,
  `arv_is_critical` int(1) NOT NULL COMMENT '0 => NO, 1 => Yes',
  `arv_description` text NOT NULL,
  `arv_added_on` datetime NOT NULL,
  `arv_added_by` varchar(255) NOT NULL,
  `arv_updated_on` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `tbl_app_release_versions`
  ADD PRIMARY KEY (`arv_id`),
  ADD UNIQUE KEY `arv_package_name` (`arv_package_name`,`arv_app_type`);

ALTER TABLE `tbl_app_release_versions`
  MODIFY `arv_id` int(11) NOT NULL AUTO_INCREMENT;

CREATE TABLE `tbl_app_release_version_logs` (
  `arvlog_id` int(11) NOT NULL,
  `arvlog_arv_id` int(11) NOT NULL,
  `arvlog_app_version` varchar(20) NOT NULL,
  `arvlog_is_critical` tinyint(1) NOT NULL,
  `arvlog_description` text NOT NULL,
  `arvlog_added_on` datetime NOT NULL,
  `arvlog_added_by` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

ALTER TABLE `tbl_app_release_version_logs`
  ADD PRIMARY KEY (`arvlog_id`);
  
ALTER TABLE `tbl_app_release_version_logs`
  MODIFY `arvlog_id` int(11) NOT NULL AUTO_INCREMENT;

-- ---------------------------------task-126067-hns-code-field-addition-on-catalog-form------------------------
ALTER TABLE `tbl_users` ADD `user_gst_number` VARCHAR(254) NULL DEFAULT NULL AFTER `user_deleted`;
ALTER TABLE `tbl_products` ADD `product_hsn_code` VARCHAR(254) NULL DEFAULT NULL AFTER `product_min_selling_price`;
ALTER TABLE `tbl_attached_files`  ADD `afile_attachment_type` TINYINT(2) NOT NULL  AFTER `afile_name`;
ALTER TABLE `tbl_attached_files_temp`  ADD `afile_attachment_type` TINYINT(2) NOT NULL  AFTER `afile_name`;

UPDATE tbl_shops AS s
INNER JOIN (
    select shop_id from tbl_shops s 
    INNER join tbl_countries c on c.country_id = s.shop_country_id
    INNER JOIN tbl_states st on st.state_id = s.shop_state_id
    where shop_supplier_display_status = 1 and (country_active = 0 or state_active = 0)
) t ON t.shop_id = s.shop_id
SET s.shop_supplier_display_status = 0;
ALTER TABLE `tbl_shops` ADD INDEX(`shop_supplier_display_status`);
ALTER TABLE `tbl_users` ADD INDEX(`user_deleted`);
ALTER TABLE `tbl_users` ADD INDEX(`user_is_supplier`);
ALTER TABLE `tbl_plugins` ADD INDEX(`plugin_type`, `plugin_active`);
ALTER TABLE `tbl_user_withdrawal_requests` ADD INDEX(`withdrawal_status`);
ALTER TABLE `tbl_user_withdrawal_requests` ADD INDEX(`withdrawal_user_id`);
ALTER TABLE `tbl_promotions` ADD INDEX(`promotion_user_id`);
ALTER TABLE `tbl_promotions` ADD INDEX(`promotion_active`, `promotion_deleted`);
ALTER TABLE `tbl_order_products` ADD INDEX(`op_order_id`);
ALTER TABLE `tbl_order_products` ADD INDEX(`op_selprod_user_id`);
ALTER TABLE `tbl_promotions_charges` ADD INDEX(`pcharge_promotion_id`);
ALTER TABLE `tbl_promotions_charges` ADD INDEX(`pcharge_end_piclick_id`);
ALTER TABLE `tbl_promotion_item_charges` ADD INDEX(`picharge_pclick_id`);
ALTER TABLE `tbl_promotions_clicks` ADD INDEX(`pclick_promotion_id`);
ALTER TABLE `tbl_order_payments` ADD INDEX(`opayment_txn_status`);
ALTER TABLE `tbl_order_prod_charges_logs` ADD INDEX(`opchargelog_op_id`);
ALTER TABLE `tbl_badges` ADD INDEX(`badge_active`);
ALTER TABLE `tbl_badge_link_conditions` ADD INDEX(`blinkcond_record_type`);
ALTER TABLE `tbl_badge_link_conditions` ADD INDEX(`blinkcond_position`);
ALTER TABLE `tbl_attached_files` ADD INDEX(`afile_display_order`);
ALTER TABLE `tbl_attached_files` ADD INDEX(`afile_screen`);

ALTER TABLE tbl_user_notifications ADD unotification_sent_to_app TINYINT(4) NOT NULL AFTER `unotification_data`;
UPDATE tbl_user_notifications set unotification_sent_to_app = 1;
INSERT INTO `tbl_cron_schedules` (`cron_id`, `cron_name`, `cron_command`, `cron_duration`, `cron_active`) VALUES (NULL, 'APP Push Notifications', 'Notifications/triggerNotification', '2', 1);

ALTER TABLE `tbl_seller_products` CHANGE `selprod_code` `selprod_code` VARCHAR(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT 'generated by product_id and option value ids associated with this product';
