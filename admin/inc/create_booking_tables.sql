-- Create bookings table
CREATE TABLE IF NOT EXISTS `bookings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `room_id` int(11) NOT NULL,
  `check_in` date NOT NULL,
  `check_out` date NOT NULL,
  `order_id` varchar(100) NOT NULL,
  `room_name` varchar(100) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `total_pay` decimal(10,2) NOT NULL,
  `booking_status` varchar(50) NOT NULL DEFAULT 'pending',
  `payment_status` varchar(50) NOT NULL DEFAULT 'pending',
  `user_name` varchar(100) NOT NULL,
  `phonenum` varchar(20) NOT NULL,
  `address` text NOT NULL,
  `booking_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `datentime` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `room_id` (`room_id`),
  KEY `order_id` (`order_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create payments table
CREATE TABLE IF NOT EXISTS `payments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `booking_id` int(11) NOT NULL,
  `order_id` varchar(100) NOT NULL,
  `stripe_payment_intent_id` varchar(255) DEFAULT NULL,
  `stripe_session_id` varchar(255) DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL,
  `currency` varchar(10) NOT NULL DEFAULT 'usd',
  `payment_status` varchar(50) NOT NULL DEFAULT 'pending',
  `payment_method` varchar(50) DEFAULT 'stripe',
  `transaction_id` varchar(255) DEFAULT NULL,
  `payment_date` datetime DEFAULT NULL,
  `datentime` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `booking_id` (`booking_id`),
  KEY `order_id` (`order_id`),
  KEY `stripe_payment_intent_id` (`stripe_payment_intent_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


