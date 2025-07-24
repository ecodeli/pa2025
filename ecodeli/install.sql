    -- Création de la base
    CREATE DATABASE IF NOT EXISTS `ecodeli_db`
      DEFAULT CHARACTER SET utf8mb4
      COLLATE utf8mb4_unicode_ci;
    USE `ecodeli_db`;

    CREATE TABLE `users` (
                             `user_id`           INT AUTO_INCREMENT PRIMARY KEY,
                             `type`              ENUM('client','merchant','courier','service_provider','admin') NOT NULL,
                             `name`              VARCHAR(100) NOT NULL,
                             `email`             VARCHAR(100) NOT NULL UNIQUE,
                             `password`          VARCHAR(255) NOT NULL,
                             `phone`             VARCHAR(20),
                             `address`           TEXT,
                             `registration_date` DATETIME DEFAULT CURRENT_TIMESTAMP,
                             `avatar_url`        VARCHAR(255),
                             `is_verified`       TINYINT(1) DEFAULT 0,
                             `is_banned`         TINYINT(1) DEFAULT 0,
                             `nfc_tag`           VARCHAR(255) DEFAULT 0,
                             `identity_verified` TINYINT(1) DEFAULT 0,
                             `domicile_verified` TINYINT(1) DEFAULT 0,
                             INDEX `idx_user_type` (`type`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

    CREATE TABLE `documents` (
                                 `document_id` INT AUTO_INCREMENT PRIMARY KEY NOT NULL,
                                 `user_id` INT NOT NULL,
                                 `document_type` enum('identite','domicile','permis') NOT NULL,
                                 `file_path` varchar(255) NOT NULL,
                                 `is_verified` tinyint(1) DEFAULT 0,
                                 `upload_date` datetime DEFAULT current_timestamp(),
                                 FOREIGN KEY (`user_id`) REFERENCES `users`(`user_id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


    CREATE TABLE `warehouses` (
                                  `warehouse_id` INT AUTO_INCREMENT PRIMARY KEY,
                                  `city`         VARCHAR(100) NOT NULL,
                                  `address`      TEXT NOT NULL,
                                   `name` VARCHAR(50),
                                  `max_capacity` INT NOT NULL,
                                  INDEX `idx_warehouse_city` (`city`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

    CREATE TABLE `emails` (
                              `email_id` INT AUTO_INCREMENT PRIMARY KEY,
                              `recipient` VARCHAR(100) NOT NULL,
                              `subject`   VARCHAR(255) NOT NULL,
                              `content`   TEXT NOT NULL,
                              `send_date` DATETIME DEFAULT CURRENT_TIMESTAMP,
                              `status`    ENUM('sent','failed') DEFAULT 'sent',
                              INDEX `idx_email_status` (`status`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

    CREATE TABLE `sessions` (
                                `id`            VARCHAR(255) PRIMARY KEY,
                                `user_id`       INT,
                                `ip_address`    VARCHAR(45),
                                `user_agent`    TEXT,
                                `payload`       LONGTEXT NOT NULL,
                                `last_activity` INT NOT NULL,
                                FOREIGN KEY (`user_id`) REFERENCES `users`(`user_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

    CREATE TABLE `personal_access_tokens` (
                                              `id`             BIGINT AUTO_INCREMENT PRIMARY KEY,
                                              `tokenable_type` VARCHAR(255) NOT NULL,
                                              `tokenable_id`   BIGINT UNSIGNED NOT NULL,
                                              `name`           VARCHAR(255) NOT NULL,
                                              `token`          VARCHAR(64) NOT NULL UNIQUE,
                                              `abilities`      TEXT,
                                              `last_used_at`   TIMESTAMP NULL,
                                              `expires_at`     TIMESTAMP NULL,
                                              `created_at`     TIMESTAMP NULL,
                                              `updated_at`     TIMESTAMP NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

    CREATE TABLE `subscriptions` (
                                     `subscription_id` INT AUTO_INCREMENT PRIMARY KEY,
                                     `type`            ENUM('free','starter','premium') NOT NULL,
                                     `start_date`      DATE NOT NULL,
                                     `end_date`        DATE,
                                     `status`          ENUM('active','expired') DEFAULT 'active',
                                     `user_id`         INT NOT NULL,
                                     FOREIGN KEY (`user_id`) REFERENCES `users`(`user_id`) ON DELETE CASCADE,
                                     INDEX `idx_subscription_status` (`status`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

    CREATE TABLE `storage_boxes` (
                                     `box_id`     INT AUTO_INCREMENT PRIMARY KEY,
                                     `start_date` DATETIME NOT NULL,
                                     `end_date`   DATETIME,
                                     `status`     ENUM('reserved','free') DEFAULT 'free',
                                     `warehouse_id` INT NOT NULL,
                                     `user_id`      INT NOT NULL,
                                     FOREIGN KEY (`warehouse_id`) REFERENCES `warehouses`(`warehouse_id`) ON DELETE CASCADE,
                                     FOREIGN KEY (`user_id`)      REFERENCES `users`     (`user_id`)      ON DELETE CASCADE,
                                     INDEX `idx_box_status` (`status`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

    CREATE TABLE `listings` (
                                `listing_id`     INT AUTO_INCREMENT PRIMARY KEY,
                                `description`    TEXT,
                                `departure_city` VARCHAR(100) NOT NULL,
                                `arrival_city`   VARCHAR(100) NOT NULL,
                                `deadline_date`  DATE,
                                `price`          DECIMAL(10,2) NOT NULL,
                                `status`         ENUM('pending','accepted','delivered','canceled') DEFAULT 'pending',
                                `user_id`        INT NOT NULL,
                                `type`           ENUM('colis','service'),
                                `annonce_title`  VARCHAR(255),
                                `category`       VARCHAR(100),
                                `details`        TEXT,
                                `departure_lat`  DECIMAL(10,8),
                                `departure_lng`  DECIMAL(11,8),
                                `arrival_lat`    DECIMAL(10,8),
                                `arrival_lng`    DECIMAL(11,8),
                                `service_radius` INT DEFAULT 0,
                                `is_archived`    TINYINT(1) DEFAULT 0,
                                `verification_code` VARCHAR(6) NOT NULL ,
                                `delivery_fees` DECIMAL(4,2) NOT NULL,
                                `livraison_directe` TINYINT(1) DEFAULT 0,
                                `delivery_address` VARCHAR(255),
                                `departure_address` VARCHAR(255),
                                FOREIGN KEY (`user_id`) REFERENCES `users`(`user_id`) ON DELETE CASCADE,
                                INDEX `idx_listing_cities` (`departure_city`,`arrival_city`),
                                UNIQUE KEY `uk_listing_verification_code` (`verification_code`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

    CREATE TABLE `listing_objects` (
                                       `object_id`   INT AUTO_INCREMENT PRIMARY KEY,
                                       `listing_id`  INT NOT NULL,
                                       `quantity`    INT DEFAULT 1,
                                       `object_name` VARCHAR(255) NOT NULL,
                                       `format`      VARCHAR(50),
                                       `poids`       VARCHAR(50),
                                       FOREIGN KEY (`listing_id`) REFERENCES `listings`(`listing_id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

    CREATE TABLE `listing_photos` (
                                      `photo_id`   INT AUTO_INCREMENT PRIMARY KEY,
                                      `listing_id` INT NOT NULL,
                                      `photo_path` VARCHAR(255) NOT NULL,
                                      FOREIGN KEY (`listing_id`) REFERENCES `listings`(`listing_id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

    CREATE TABLE `liked` (
                             `user_id`    INT NOT NULL,
                             `listing_id` INT NOT NULL,
                             PRIMARY KEY (`user_id`,`listing_id`),
                             FOREIGN KEY (`user_id`)    REFERENCES `users`   (`user_id`),
                             FOREIGN KEY (`listing_id`) REFERENCES `listings`(`listing_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

    CREATE TABLE `deliveries` (
                                  `delivery_id`     INT AUTO_INCREMENT PRIMARY KEY,
                                  `departure_date`  DATETIME,
                                  `arrival_date`    DATETIME,
                                  `status`          ENUM('in_progress','delivered','issue','canceled') DEFAULT 'in_progress',
                                  `courier_id`      INT NOT NULL,
                                  `listing_id`      INT NOT NULL,
                                  `verification_code` VARCHAR(6),
                                  FOREIGN KEY (`courier_id`) REFERENCES `users`   (`user_id`)   ON DELETE CASCADE,
                                  FOREIGN KEY (`listing_id`) REFERENCES `listings`(`listing_id`) ON DELETE CASCADE,
                                  FOREIGN KEY (`verification_code`) REFERENCES `listings`(`verification_code`),
                                  INDEX `idx_delivery_status` (`status`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

    CREATE TABLE `delivery_routes` (
                                       `route_id`        INT AUTO_INCREMENT PRIMARY KEY,
                                       `courier_id`      INT NOT NULL,
                                       `start_city`      VARCHAR(100),
                                       `end_city`        VARCHAR(100),
                                       `start_lat`       DECIMAL(10,8),
                                       `start_lng`       DECIMAL(11,8),
                                       `end_lat`         DECIMAL(10,8),
                                       `end_lng`         DECIMAL(11,8),
                                       `departure_date`  DATETIME,
                                       `available_seats` INT DEFAULT 1,
                                       `vehicle_type`    VARCHAR(100),
                                       `comments`        TEXT,
                                       FOREIGN KEY (`courier_id`) REFERENCES `users`(`user_id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

    CREATE TABLE `delivery_lines` (
                                      `line_id`              INT AUTO_INCREMENT PRIMARY KEY,
                                      `route_id`             INT NOT NULL,
                                      `listing_id`           INT NOT NULL,
                                      `delivery_id`          INT,
                                      `custom_start_address` TEXT,
                                      `status` ENUM('en_cours', 'livré', 'en_pause', 'terminé') DEFAULT 'en_cours',
                                      `lastest_step` ENUM('picked_up', 'in_transit', 'arrived', 'completed') DEFAULT NULL,

                                            `current_lat` DECIMAL(10,8) DEFAULT NULL,
                                      `current_lng` DECIMAL(11,8) DEFAULT NULL,

    FOREIGN KEY (`route_id`)   REFERENCES `delivery_routes`(`route_id`)   ON DELETE CASCADE,
                                      FOREIGN KEY (`listing_id`) REFERENCES `listings`        (`listing_id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

    CREATE TABLE `delivery_notes` (
                                      `note_id`   INT AUTO_INCREMENT PRIMARY KEY,
                                      `listing_id` INT NOT NULL,
                                      `user_id`    INT NOT NULL,
                                      `note`       DECIMAL(3,2) NOT NULL,
                                      `comment`    TEXT,
                                      `created_at` TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
                                      FOREIGN KEY (`listing_id`) REFERENCES `listings`(`listing_id`),
                                      FOREIGN KEY (`user_id`)    REFERENCES `users`   (`user_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

    CREATE TABLE `notifications` (
                                     `notification_id` INT AUTO_INCREMENT PRIMARY KEY,
                                     `message`         TEXT NOT NULL,
                                     `send_date`       DATETIME DEFAULT CURRENT_TIMESTAMP,
                                     `is_read`         TINYINT(1) DEFAULT 0,
                                     `user_id`         INT NOT NULL,
                                     FOREIGN KEY (`user_id`) REFERENCES `users`(`user_id`) ON DELETE CASCADE,
                                     INDEX `idx_notification_read` (`is_read`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

    CREATE TABLE `messages` (
                                `message_id`  INT AUTO_INCREMENT PRIMARY KEY,
                                `content`     TEXT NOT NULL,
                                `send_date`   DATETIME DEFAULT CURRENT_TIMESTAMP,
                                `status`      ENUM('sent','read','archived') DEFAULT 'sent',
                                `sender_id`   INT NOT NULL,
                                `receiver_id` INT NOT NULL,
                                FOREIGN KEY (`sender_id`)   REFERENCES `users`(`user_id`) ON DELETE CASCADE,
                                FOREIGN KEY (`receiver_id`) REFERENCES `users`(`user_id`) ON DELETE CASCADE,
                                INDEX `idx_message_status` (`status`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

    CREATE TABLE `payments` (
                                `payment_id`    INT AUTO_INCREMENT PRIMARY KEY,
                                `amount`        DECIMAL(10,2) NOT NULL,
                                `payment_date`  DATETIME     DEFAULT CURRENT_TIMESTAMP,
                                `status`        ENUM('pending','successful','failed') DEFAULT 'pending',
                                `transaction_id` VARCHAR(255) NOT NULL UNIQUE,
                                `user_id`       INT NOT NULL,
                                FOREIGN KEY (`user_id`) REFERENCES `users`(`user_id`) ON DELETE CASCADE,
                                INDEX `idx_payment_status` (`status`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

    CREATE TABLE `invoices` (
                                `invoice_id`  INT AUTO_INCREMENT PRIMARY KEY,
                                `amount`      DECIMAL(10,2) NOT NULL,
                                `invoice_date` DATETIME    DEFAULT CURRENT_TIMESTAMP,
                                `invoice_file` VARCHAR(255),
                                `user_id`     INT NOT NULL,
                                `courier_id` INT NOT NULL,
                                FOREIGN KEY (`user_id`) REFERENCES `users`(`user_id`) ON DELETE CASCADE,
                                FOREIGN KEY (`courier_id`) REFERENCES `users`(`user_id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

    CREATE TABLE `invoice_items` (
                                     `item_id`    INT AUTO_INCREMENT PRIMARY KEY,
                                     `invoice_id` INT NOT NULL,
                                     `label`      VARCHAR(255),
                                     `amount`     DECIMAL(10,2),
                                     FOREIGN KEY (`invoice_id`) REFERENCES `invoices`(`invoice_id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

    CREATE TABLE `prestations` (
                                   `id`              INT AUTO_INCREMENT PRIMARY KEY,
                                   `title`           VARCHAR(255),
                                   `description`     TEXT,
                                   `price`           DECIMAL(10,2),
                                   `provider_id`     INT,
                                   `category`        VARCHAR(100),
                                   `duration_minutes` INT,
                                   FOREIGN KEY (`provider_id`) REFERENCES `users`(`user_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

    CREATE TABLE `reservations` (
                                    `id`               INT AUTO_INCREMENT PRIMARY KEY,
                                    `prestation_id`    INT,
                                    `client_id`        INT,
                                    `reservation_date` DATE,
                                    `status`           ENUM('en attente','validée','réalisée','annulée') DEFAULT 'en attente',
                                    `note`             INT,
                                    `feedback`         TEXT,
                                    FOREIGN KEY (`prestation_id`) REFERENCES `prestations`(`id`),
                                    FOREIGN KEY (`client_id`)     REFERENCES `users`   (`user_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

    CREATE TABLE `ratings` (
                               `rating_id`     INT AUTO_INCREMENT PRIMARY KEY,
                               `score`         TINYINT UNSIGNED NOT NULL,
                               `comment`       TEXT,
                               `rating_date`   DATETIME DEFAULT CURRENT_TIMESTAMP,
                               `rater_id`      INT NOT NULL,
                               `rated_user_id` INT NOT NULL,
                               FOREIGN KEY (`rater_id`)      REFERENCES `users`(`user_id`) ON DELETE CASCADE,
                               FOREIGN KEY (`rated_user_id`) REFERENCES `users`(`user_id`) ON DELETE CASCADE,
                               INDEX `idx_rating_score` (`score`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

    CREATE TABLE `services` (
                                `service_id` INT AUTO_INCREMENT PRIMARY KEY,
                                `type`       VARCHAR(100) NOT NULL,
                                `description` TEXT,
                                `price`      DECIMAL(10,2) NOT NULL,
                                `provider_id` INT NOT NULL,
                                FOREIGN KEY (`provider_id`) REFERENCES `users`(`user_id`),
                                INDEX `idx_service_type` (`type`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

    CREATE TABLE `wallet` (
                              `wallet_id`   INT AUTO_INCREMENT PRIMARY KEY,
                              `user_id`     INT NOT NULL,
                              `balance`     DECIMAL(10,2) DEFAULT 0.00,
                              `last_update` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                              FOREIGN KEY (`user_id`) REFERENCES `users`(`user_id`) ON DELETE CASCADE,
                              INDEX `idx_wallet_user` (`user_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

    CREATE TABLE `wallet_transactions` (
                                           `transaction_id` INT AUTO_INCREMENT PRIMARY KEY,
                                           `sender_id`      INT,
                                           `receiver_id`      INT,
                                           `wallet_id`      INT NOT NULL,
                                           `amount`         DECIMAL(10,2) NOT NULL,
                                           `type`           ENUM('credit','debit') NOT NULL,
                                           `description`    VARCHAR(255),
                                           `created_at`     DATETIME DEFAULT CURRENT_TIMESTAMP,
                                           FOREIGN KEY (`wallet_id`) REFERENCES `wallet`(`wallet_id`) ON DELETE CASCADE,
                                           FOREIGN KEY (`sender_id`) REFERENCES `users`(`user_id`) ON DELETE CASCADE,
                                           FOREIGN KEY (`receiver_id`) REFERENCES `users`(`user_id`) ON DELETE CASCADE,
                                           INDEX `idx_wallet_txn` (`wallet_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

    CREATE TABLE `delivery_status_history` (
                                               `id`                INT AUTO_INCREMENT PRIMARY KEY,
                                               `delivery_line_id`  INT,
                                               `status`            ENUM('pris_en_charge','entrepot','livre') DEFAULT NULL,
                                               `timestamp`         DATETIME DEFAULT CURRENT_TIMESTAMP,
                                               `location`          TEXT,
                                               FOREIGN KEY (`delivery_line_id`) REFERENCES `delivery_lines`(`line_id`) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

    CREATE TABLE `availabilities` (
                                      `id` INT(11) NOT NULL AUTO_INCREMENT,
                                      `provider_id` INT(11) DEFAULT NULL,
                                      `date` DATE DEFAULT NULL,
                                      `start_time` TIME DEFAULT NULL,
                                      `end_time` TIME DEFAULT NULL,
                                      `listing_id` INT(11) DEFAULT 0,
                                      `reserved` TINYINT(1) DEFAULT NULL,
                                      PRIMARY KEY (`id`),
                                      KEY `fk_provider_id` (`provider_id`),
                                      KEY `fk_listing_id` (`listing_id`),
                                      CONSTRAINT `fk_provider_id` FOREIGN KEY (`provider_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL,
                                      CONSTRAINT `fk_listing_id` FOREIGN KEY (`listing_id`) REFERENCES `listings` (`listing_id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


    CREATE TABLE `bookings` (
                                `booking_id` INT(11) NOT NULL AUTO_INCREMENT,
                                `client_id` INT(11) DEFAULT NULL,
                                `provider_id` INT(11) DEFAULT NULL,
                                `listing_id` INT(11) DEFAULT NULL,
                                `availability_id` INT(11) DEFAULT NULL,
                                `status` ENUM('pending', 'validée', 'réalisée', 'annulée')
                                                 CHARACTER SET utf8mb4
                                                 COLLATE utf8mb4_general_ci
                                    DEFAULT 'pending',
                                `booked_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
                                PRIMARY KEY (`booking_id`),
                                KEY `idx_booking_client_id` (`client_id`),
                                KEY `idx_booking_provider_id` (`provider_id`),
                                KEY `idx_booking_listing_id` (`listing_id`),
                                KEY `idx_booking_availability_id` (`availability_id`),
                                CONSTRAINT `fk_bookings_client_id` FOREIGN KEY (`client_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL,
                                CONSTRAINT `fk_bookings_provider_id` FOREIGN KEY (`provider_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL,
                                CONSTRAINT `fk_bookings_listing_id` FOREIGN KEY (`listing_id`) REFERENCES `listings` (`listing_id`) ON DELETE SET NULL,
                                CONSTRAINT `fk_bookings_availability_id` FOREIGN KEY (`availability_id`) REFERENCES `availabilities` (`id`) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


    CREATE TABLE `reviews` (
                               `review_id` INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
                               `listing_id` INT(11) NOT NULL,
                               `courier_id` INT(11) NOT NULL,
                               `user_id` INT(11) NOT NULL,
                               `note` TINYINT(4) NOT NULL CHECK (`note` BETWEEN 1 AND 5),
                               `commentaire` TEXT DEFAULT NULL,
                               `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
                               `auteur_id` INT(11) NOT NULL,
                               CONSTRAINT `fk_reviews_listing_id` FOREIGN KEY (`listing_id`) REFERENCES `listings` (`listing_id`) ON DELETE CASCADE,
                               CONSTRAINT `fk_reviews_courier_id` FOREIGN KEY (`courier_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
                               CONSTRAINT `fk_reviews_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


    CREATE TABLE delivery_progress_steps (
                                             id INT AUTO_INCREMENT PRIMARY KEY,
                                             line_id INT NOT NULL,
                                             step ENUM('picked_up', 'in_transit', 'arrived', 'completed') NOT NULL,
                                             location TEXT,
                                             timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
                                             latest_step ENUM('picked_up', 'in_transit', 'arrived', 'completed') NOT NULL,
                                             CONSTRAINT fk_line FOREIGN KEY (line_id) REFERENCES delivery_lines(line_id) ON DELETE CASCADE
    );
