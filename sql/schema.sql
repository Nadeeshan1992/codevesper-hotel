-- Hotel & Restaurant Management System Database Schema
-- Database Name: hotel_db

-- For local MySQL/XAMPP: uncomment if needed. For Cloud MySQL (Aiven, TiDB, Railway, Supabase): run in your active database.
-- CREATE DATABASE IF NOT EXISTS `hotel_db` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
-- USE `hotel_db`;

-- Drop existing tables if needed in correct foreign key order
SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS `audit_logs`;
DROP TABLE IF EXISTS `payments`;
DROP TABLE IF EXISTS `invoices`;
DROP TABLE IF EXISTS `staff_attendance`;
DROP TABLE IF EXISTS `staff`;
DROP TABLE IF EXISTS `stock_movements`;
DROP TABLE IF EXISTS `stock_items`;
DROP TABLE IF EXISTS `stock_categories`;
DROP TABLE IF EXISTS `order_items`;
DROP TABLE IF EXISTS `orders`;
DROP TABLE IF EXISTS `menu_items`;
DROP TABLE IF EXISTS `menu_categories`;
DROP TABLE IF EXISTS `dining_tables`;
DROP TABLE IF EXISTS `check_ins`;
DROP TABLE IF EXISTS `reservations`;
DROP TABLE IF EXISTS `guests`;
DROP TABLE IF EXISTS `rooms`;
DROP TABLE IF EXISTS `room_types`;
DROP TABLE IF EXISTS `role_permissions`;
DROP TABLE IF EXISTS `permissions`;
DROP TABLE IF EXISTS `users`;
DROP TABLE IF EXISTS `roles`;
DROP TABLE IF EXISTS `settings`;
SET FOREIGN_KEY_CHECKS = 1;

-- 1. Roles Table
CREATE TABLE `roles` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(50) NOT NULL UNIQUE,
    `slug` VARCHAR(50) NOT NULL UNIQUE,
    `description` VARCHAR(255) NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

INSERT INTO `roles` (`id`, `name`, `slug`, `description`) VALUES
(1, 'Administrator', 'admin', 'Full system access, configurations, user management and reports'),
(2, 'Hotel Owner', 'owner', 'Executive business analytics, revenues, P&L, audits and view permissions'),
(3, 'Receptionist', 'reception', 'Front desk, 13 rooms rack, reservations, check-in/out, folio and billing'),
(4, 'Waiter / Steward', 'waiter', 'Tablet POS, table & room service order taking, KOT management');

-- 2. System Permissions Table (For Dynamic Privileges Management)
CREATE TABLE `permissions` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `module` VARCHAR(50) NOT NULL,
    `name` VARCHAR(100) NOT NULL,
    `slug` VARCHAR(100) NOT NULL UNIQUE,
    `description` VARCHAR(255) NULL
) ENGINE=InnoDB;

INSERT INTO `permissions` (`module`, `name`, `slug`, `description`) VALUES
-- Dashboard & Privileges
('Dashboard', 'View Executive Analytics', 'dashboard.executive', 'Access to high-level financial KPIs and charts'),
('Dashboard', 'View Front Desk Dashboard', 'dashboard.frontdesk', 'Access to room status and arrival/departure feeds'),
('Privileges', 'Manage System Roles & Permissions', 'privileges.manage', 'Ability to edit role privileges and module access'),
-- Room & Reservations
('Rooms', 'View 13-Room Status Grid', 'rooms.view', 'View real-time room occupancy and cleaning status'),
('Rooms', 'Manage Room Details & Pricing', 'rooms.manage', 'Add, edit room configurations and price schemes'),
('Reservations', 'Create & Manage Bookings', 'reservations.manage', 'Create reservations, manage guest bookings'),
('Check-In/Out', 'Perform Check-In and Check-Out', 'frontdesk.checkin_checkout', 'Register guests, issue keys and process check-outs'),
('Housekeeping', 'Update Housekeeping Status', 'housekeeping.update', 'Mark rooms clean, dirty, or under maintenance'),
-- Restaurant POS
('Restaurant', 'Tablet POS Order Taking', 'pos.orders', 'Create and modify restaurant and room dining orders'),
('Restaurant', 'Manage Restaurant Tables', 'tables.manage', 'Configure dining tables and table status'),
('Restaurant', 'Manage Menu Categories & Items', 'menu.manage', 'Add, edit or disable food and beverage menu items'),
('Restaurant', 'Kitchen Order Tickets (KOT)', 'pos.kot', 'Generate, view, and update kitchen tickets'),
('Restaurant', 'Charge Order to Room Folio', 'pos.room_charge', 'Post restaurant bills directly to guest room invoices'),
-- Billing & Finance
('Billing', 'Generate Invoices & Print Receipts', 'billing.invoices', 'Generate final checkout folio and tax invoices'),
('Billing', 'Collect Payments & Settle Accounts', 'billing.payments', 'Record cash, card, and online payment settlements'),
('Billing', 'Apply Discounts & Voids', 'billing.discounts', 'Apply special discounts or void bill line items'),
-- Staff
('Staff', 'View Staff Directory', 'staff.view', 'View employee roster and contact directory'),
('Staff', 'Manage Staff Profiles & Salaries', 'staff.manage', 'Add, edit staff, salary structure and designations'),
('Staff', 'Mark Daily Attendance', 'staff.attendance', 'Record daily clock-in/out and leave status'),
-- Stock & Inventory
('Stock', 'View Inventory & Stock Levels', 'stock.view', 'Monitor kitchen, bar and hotel amenity supplies'),
('Stock', 'Manage Purchases & Stock Inward', 'stock.purchases', 'Log supplier purchases and stock additions'),
('Stock', 'Stock Adjustments & Wastage', 'stock.adjustments', 'Record kitchen consumption, amenity usage and wastage'),
-- Reports
('Reports', 'View Financial & Daily Sales Reports', 'reports.financial', 'Access daily revenue, occupancy rate and P&L reports'),
('Reports', 'View Audit & Activity Logs', 'reports.audit', 'Inspect system audit trail and user activities');

-- 3. Role Permissions Mapping
CREATE TABLE `role_permissions` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `role_id` INT NOT NULL,
    `permission_id` INT NOT NULL,
    `is_granted` TINYINT(1) DEFAULT 1,
    FOREIGN KEY (`role_id`) REFERENCES `roles`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`permission_id`) REFERENCES `permissions`(`id`) ON DELETE CASCADE,
    UNIQUE KEY `unique_role_perm` (`role_id`, `permission_id`)
) ENGINE=InnoDB;

-- Grant Permissions to Admin (All)
INSERT INTO `role_permissions` (`role_id`, `permission_id`, `is_granted`)
SELECT 1, `id`, 1 FROM `permissions`;

-- Grant Permissions to Owner (Executive, Reports, Views, Audits, Billing View)
INSERT INTO `role_permissions` (`role_id`, `permission_id`, `is_granted`)
SELECT 2, `id`, 1 FROM `permissions`
WHERE `slug` IN (
    'dashboard.executive', 'dashboard.frontdesk', 'rooms.view', 'staff.view',
    'stock.view', 'reports.financial', 'reports.audit', 'billing.invoices'
);

-- Grant Permissions to Receptionist
INSERT INTO `role_permissions` (`role_id`, `permission_id`, `is_granted`)
SELECT 3, `id`, 1 FROM `permissions`
WHERE `slug` IN (
    'dashboard.frontdesk', 'rooms.view', 'reservations.manage',
    'frontdesk.checkin_checkout', 'housekeeping.update', 'pos.orders',
    'pos.kot', 'pos.room_charge', 'billing.invoices', 'billing.payments',
    'staff.view', 'staff.attendance', 'stock.view'
);

-- Grant Permissions to Waiter (Tablet POS)
INSERT INTO `role_permissions` (`role_id`, `permission_id`, `is_granted`)
SELECT 4, `id`, 1 FROM `permissions`
WHERE `slug` IN (
    'pos.orders', 'pos.kot', 'pos.room_charge'
);

-- 4. Users Table
CREATE TABLE `users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `role_id` INT NOT NULL,
    `full_name` VARCHAR(100) NOT NULL,
    `username` VARCHAR(50) NOT NULL UNIQUE,
    `password_hash` VARCHAR(255) NOT NULL,
    `email` VARCHAR(100) NULL,
    `phone` VARCHAR(20) NULL,
    `avatar` VARCHAR(255) DEFAULT 'default_avatar.png',
    `status` ENUM('active', 'inactive') DEFAULT 'active',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`role_id`) REFERENCES `roles`(`id`)
) ENGINE=InnoDB;

-- Seed Default Users (Password for all accounts: password123)
INSERT INTO `users` (`id`, `role_id`, `full_name`, `username`, `password_hash`, `email`, `phone`) VALUES
(1, 1, 'System Administrator', 'admin', '$2y$10$tvYdxzJP2JC6gP7Y3A5.SeI2uqXacwQcpirsG0wTVOXrtV05ZHcP6', 'admin@luxuryhotel.com', '+1 555-0101'),
(2, 2, 'Robert Sterling (Owner)', 'owner', '$2y$10$tvYdxzJP2JC6gP7Y3A5.SeI2uqXacwQcpirsG0wTVOXrtV05ZHcP6', 'owner@luxuryhotel.com', '+1 555-0102'),
(3, 3, 'Sarah Jenkins (Reception)', 'reception', '$2y$10$tvYdxzJP2JC6gP7Y3A5.SeI2uqXacwQcpirsG0wTVOXrtV05ZHcP6', 'reception@luxuryhotel.com', '+1 555-0103'),
(4, 4, 'David Miller (Waiter)', 'waiter', '$2y$10$tvYdxzJP2JC6gP7Y3A5.SeI2uqXacwQcpirsG0wTVOXrtV05ZHcP6', 'waiter@luxuryhotel.com', '+1 555-0104');

-- 5. Room Types & Rooms (Scalable Dynamic Inventory - Starter rooms seeded)
CREATE TABLE `room_types` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(50) NOT NULL,
    `code` VARCHAR(10) NOT NULL UNIQUE,
    `base_price` DECIMAL(10,2) NOT NULL,
    `capacity` INT DEFAULT 2,
    `amenities` TEXT NULL,
    `description` TEXT NULL
) ENGINE=InnoDB;

INSERT INTO `room_types` (`id`, `name`, `code`, `base_price`, `capacity`, `amenities`, `description`) VALUES
(1, 'Deluxe King', 'DLX-K', 18000.00, 2, 'King Bed, AC, Smart TV, Wi-Fi, Mini Bar, Balcony', 'Spacious deluxe room with balcony and luxury king bed'),
(2, 'Deluxe Twin', 'DLX-T', 16000.00, 2, 'Twin Beds, AC, Smart TV, Wi-Fi, Coffee Maker', 'Comfortable twin bedding perfect for friends or colleagues'),
(3, 'Executive Suite', 'EXEC-S', 28000.00, 3, 'King Bed + Sofa, Jacuzzi, Ocean/Garden View, Kitchenette', 'Premium suite with separate seating lounge and panoramic views'),
(4, 'Presidential Penthouse', 'PRES-P', 55000.00, 4, 'Master King, Dining Area, Terrace, Private Jacuzzi', 'Top-tier luxury penthouse on 3rd floor with private terrace');

CREATE TABLE `rooms` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `room_number` VARCHAR(10) NOT NULL UNIQUE,
    `room_type_id` INT NOT NULL,
    `floor` INT NOT NULL,
    `status` ENUM('vacant', 'reserved', 'occupied', 'dirty', 'maintenance') DEFAULT 'vacant',
    `daily_rate` DECIMAL(10,2) NOT NULL,
    `notes` VARCHAR(255) NULL,
    FOREIGN KEY (`room_type_id`) REFERENCES `room_types`(`id`)
) ENGINE=InnoDB;

-- Exact 13 Rooms Configuration (Ground, 1st & 2nd/3rd Floors)
INSERT INTO `rooms` (`id`, `room_number`, `room_type_id`, `floor`, `status`, `daily_rate`, `notes`) VALUES
(1,  'Room 101', 1, 1, 'vacant', 18000.00, 'Ground floor garden view'),
(2,  'Room 102', 1, 1, 'occupied', 18000.00, 'Garden view with easy lobby access'),
(3,  'Room 103', 2, 1, 'vacant', 16000.00, 'Twin beds, quiet wing'),
(4,  'Room 104', 2, 1, 'dirty', 16000.00, 'Checked out this afternoon - needs turnaround'),
(5,  'Room 105', 1, 1, 'reserved', 18000.00, 'Reserved for VIP arrival tonight'),
(6,  'Room 201', 1, 2, 'occupied', 18000.00, 'First floor king suite with balcony'),
(7,  'Room 202', 1, 2, 'vacant', 18000.00, 'First floor king with balcony'),
(8,  'Room 203', 2, 2, 'vacant', 16000.00, 'First floor twin beds'),
(9,  'Room 204', 2, 2, 'maintenance', 16000.00, 'AC servicing in progress'),
(10, 'Room 205', 3, 2, 'occupied', 28000.00, 'Corner Executive Suite with jacuzzi'),
(11, 'Room 301', 3, 3, 'vacant', 28000.00, 'Top floor executive luxury suite'),
(12, 'Room 302', 3, 3, 'vacant', 28000.00, 'Top floor executive luxury suite'),
(13, 'Room 303', 4, 3, 'reserved', 55000.00, 'Presidential Penthouse with panoramic terrace'),
(15, 'Room 106', 2, 1, 'vacant', 16000.00, 'Deluxe Twin with garden view');

-- 6. Guests & Reservations
CREATE TABLE `guests` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `full_name` VARCHAR(100) NOT NULL,
    `phone` VARCHAR(20) NOT NULL,
    `email` VARCHAR(100) NULL,
    `id_type` ENUM('Passport', 'National ID', 'Driving License', 'Other') DEFAULT 'National ID',
    `id_number` VARCHAR(50) NOT NULL,
    `address` TEXT NULL,
    `nationality` VARCHAR(50) DEFAULT 'Domestic',
    `special_requests` TEXT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

INSERT INTO `guests` (`id`, `full_name`, `phone`, `email`, `id_type`, `id_number`, `nationality`) VALUES
(1, 'Alexander Wright', '+1 555-7821', 'alex.wright@gmail.com', 'Passport', 'P987654321', 'United States'),
(2, 'Elena Rostova', '+44 7911-123456', 'elena.rostova@outlook.com', 'Passport', 'GB8823190', 'United Kingdom'),
(3, 'Marcus Vance', '+1 555-4390', 'marcus.v@corporate.com', 'Driving License', 'DL-2023-9912', 'Canada');

CREATE TABLE `reservations` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `booking_ref` VARCHAR(20) NOT NULL UNIQUE,
    `guest_id` INT NOT NULL,
    `room_id` INT NOT NULL,
    `check_in_date` DATE NOT NULL,
    `check_out_date` DATE NOT NULL,
    `adults` INT DEFAULT 2,
    `children` INT DEFAULT 0,
    `total_room_cost` DECIMAL(10,2) NOT NULL,
    `advance_deposit` DECIMAL(10,2) DEFAULT 0.00,
    `status` ENUM('confirmed', 'checked_in', 'checked_out', 'cancelled') DEFAULT 'confirmed',
    `created_by` INT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`guest_id`) REFERENCES `guests`(`id`),
    FOREIGN KEY (`room_id`) REFERENCES `rooms`(`id`),
    FOREIGN KEY (`created_by`) REFERENCES `users`(`id`)
) ENGINE=InnoDB;

INSERT INTO `reservations` (`id`, `booking_ref`, `guest_id`, `room_id`, `check_in_date`, `check_out_date`, `adults`, `children`, `total_room_cost`, `advance_deposit`, `status`, `created_by`) VALUES
(1, 'BK-1001', 1, 2, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 3 DAY), 2, 0, 360.00, 100.00, 'checked_in', 3),
(2, 'BK-1002', 2, 6, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 2 DAY), 1, 0, 240.00, 120.00, 'checked_in', 3),
(3, 'BK-1003', 3, 10, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 4 DAY), 2, 1, 760.00, 300.00, 'checked_in', 3);

CREATE TABLE `check_ins` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `reservation_id` INT NOT NULL,
    `room_id` INT NOT NULL,
    `guest_id` INT NOT NULL,
    `check_in_time` DATETIME NOT NULL,
    `check_out_time` DATETIME NULL,
    `actual_check_out` DATETIME NULL,
    `daily_rate` DECIMAL(10,2) NOT NULL,
    `deposit_paid` DECIMAL(10,2) DEFAULT 0.00,
    `status` ENUM('active', 'completed') DEFAULT 'active',
    `created_by` INT NULL,
    FOREIGN KEY (`reservation_id`) REFERENCES `reservations`(`id`),
    FOREIGN KEY (`room_id`) REFERENCES `rooms`(`id`),
    FOREIGN KEY (`guest_id`) REFERENCES `guests`(`id`)
) ENGINE=InnoDB;

INSERT INTO `check_ins` (`id`, `reservation_id`, `room_id`, `guest_id`, `check_in_time`, `check_out_time`, `daily_rate`, `deposit_paid`, `status`, `created_by`) VALUES
(1, 1, 2, 1, NOW(), DATE_ADD(NOW(), INTERVAL 3 DAY), 120.00, 100.00, 'active', 3),
(2, 2, 6, 2, NOW(), DATE_ADD(NOW(), INTERVAL 2 DAY), 120.00, 120.00, 'active', 3),
(3, 3, 10, 3, NOW(), DATE_ADD(NOW(), INTERVAL 4 DAY), 190.00, 300.00, 'active', 3);

-- 7. Restaurant Dining Tables & Virtual Rooms
CREATE TABLE `dining_tables` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `table_number` VARCHAR(20) NOT NULL UNIQUE,
    `section` VARCHAR(50) DEFAULT 'Main Dining',
    `capacity` INT DEFAULT 4,
    `status` ENUM('available', 'occupied', 'reserved', 'cleaning') DEFAULT 'available'
) ENGINE=InnoDB;

INSERT INTO `dining_tables` (`id`, `table_number`, `section`, `capacity`, `status`) VALUES
(1, 'Table T1', 'Indoor Hall', 2, 'available'),
(2, 'Table T2', 'Indoor Hall', 4, 'occupied'),
(3, 'Table T3', 'Indoor Hall', 4, 'available'),
(4, 'Table T4', 'Indoor Hall', 6, 'available'),
(5, 'Table T5', 'Terrace Garden', 4, 'available'),
(6, 'Table T6', 'Terrace Garden', 4, 'occupied'),
(7, 'Table T7', 'Poolside', 2, 'available'),
(8, 'Table T8', 'Bar Lounge', 2, 'available');

-- 8. Restaurant Menu Categories & Items
CREATE TABLE `menu_categories` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(50) NOT NULL,
    `icon` VARCHAR(50) DEFAULT 'utensils',
    `sort_order` INT DEFAULT 0,
    `status` ENUM('active', 'inactive') DEFAULT 'active'
) ENGINE=InnoDB;

INSERT INTO `menu_categories` (`id`, `name`, `icon`, `sort_order`, `status`) VALUES
(1, 'Appetizers & Starters', 'bowl-food', 1, 'active'),
(2, 'Main Course', 'plate-wheat', 2, 'active'),
(3, 'Grill & Steaks', 'fire', 3, 'active'),
(4, 'Pasta & Italian', 'pizza-slice', 4, 'active'),
(5, 'Desserts', 'ice-cream', 5, 'active'),
(6, 'Beverages & Mocktails', 'glass-water', 6, 'active'),
(7, 'Bar & Cocktails', 'martini-glass-citrus', 7, 'active');

CREATE TABLE `menu_items` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `category_id` INT NOT NULL,
    `name` VARCHAR(100) NOT NULL,
    `description` VARCHAR(255) NULL,
    `price` DECIMAL(10,2) NOT NULL,
    `cost_price` DECIMAL(10,2) DEFAULT 0.00,
    `image` VARCHAR(255) DEFAULT 'default_food.jpg',
    `is_available` TINYINT(1) DEFAULT 1,
    `prep_time_minutes` INT DEFAULT 15,
    FOREIGN KEY (`category_id`) REFERENCES `menu_categories`(`id`)
) ENGINE=InnoDB;

INSERT INTO `menu_items` (`id`, `category_id`, `name`, `description`, `price`, `cost_price`, `prep_time_minutes`, `image`) VALUES
-- Appetizers
(1, 1, 'Crispy Calamari Rings', 'Served with garlic aioli and lemon wedges', 11.50, 4.00, 12, 'calamari.jpg'),
(2, 1, 'Truffle Mushroom Bruschetta', 'Toasted sourdough, wild sautéed mushrooms, truffle oil', 9.50, 3.00, 10, 'bruschetta.jpg'),
(3, 1, 'Creamy Tomato Basil Soup', 'Fresh roasted Roma tomatoes with parmesan croutons', 7.00, 2.00, 8, 'tomato_soup.jpg'),
-- Main Course
(4, 2, 'Grilled Norwegian Salmon', 'Lemon butter emulsion, herb mashed potatoes, asparagus', 24.50, 10.00, 20, 'salmon.jpg'),
(5, 2, 'Roasted Herb Chicken', 'Half free-range chicken roasted with rosemary, root vegetables', 18.00, 6.50, 25, 'roast_chicken.jpg'),
(6, 2, 'Royal Chicken Biryani', 'Fragrant basmati rice slow-cooked with aromatic whole spices', 16.50, 5.00, 18, 'biryani.jpg'),
-- Grill & Steaks
(7, 3, 'Prime Ribeye Steak (300g)', 'Grass-fed beef steak with peppercorn glaze and truffle fries', 32.00, 14.00, 22, 'ribeye_steak.jpg'),
(8, 3, 'Grilled Lamb Chops', 'Marinated with mint garlic chimichurri, grilled baby carrots', 28.50, 12.00, 20, 'lamb_chops.jpg'),
-- Pasta
(9, 4, 'Fettuccine Alfredo with Chicken', 'Rich parmesan cream sauce, grilled chicken breast, parsley', 16.00, 5.00, 15, 'alfredo_pasta.jpg'),
(10, 4, 'Seafood Spaghetti Marinara', 'Prawns, calamari, mussels in rich garlic tomato basil sauce', 21.00, 8.00, 18, 'seafood_pasta.jpg'),
-- Desserts
(11, 5, 'Classic Italian Tiramisu', 'Savoiardi soaked in espresso and Marsala with mascarpone', 8.50, 2.50, 5, 'tiramisu.jpg'),
(12, 5, 'Warm Chocolate Lava Cake', 'Rich molten center served with vanilla bean ice cream', 9.00, 2.80, 10, 'lava_cake.jpg'),
-- Drinks
(13, 6, 'Fresh Tropical Fruit Smoothie', 'Mango, passion fruit and pineapple blended fresh', 6.00, 1.80, 5, 'smoothie.jpg'),
(14, 6, 'Artisan Cappuccino', 'Double shot espresso, velvety steamed milk', 4.50, 1.00, 4, 'cappuccino.jpg'),
(15, 7, 'Signature Smoked Old Fashioned', 'Bourbon, angostura bitters, orange peel, applewood smoke', 14.00, 4.50, 6, 'old_fashioned.jpg'),
(16, 7, 'Classic Mojito', 'White rum, fresh garden mint, lime juice, sparkling soda', 11.00, 3.20, 5, 'mojito.jpg');

-- 9. Orders & KOT (Kitchen Order Tickets)
CREATE TABLE `orders` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `order_number` VARCHAR(20) NOT NULL UNIQUE,
    `order_type` ENUM('dine_in', 'room_service', 'takeaway') DEFAULT 'dine_in',
    `table_id` INT NULL,
    `room_id` INT NULL,
    `guest_id` INT NULL,
    `waiter_id` INT NOT NULL,
    `status` ENUM('pending', 'in_kitchen', 'ready', 'served', 'billed', 'cancelled') DEFAULT 'pending',
    `subtotal` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `tax_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `discount_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `total_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `payment_status` ENUM('unpaid', 'paid', 'billed_to_room') DEFAULT 'unpaid',
    `payment_method` VARCHAR(50) NULL,
    `notes` TEXT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`table_id`) REFERENCES `dining_tables`(`id`),
    FOREIGN KEY (`room_id`) REFERENCES `rooms`(`id`),
    FOREIGN KEY (`guest_id`) REFERENCES `guests`(`id`),
    FOREIGN KEY (`waiter_id`) REFERENCES `users`(`id`)
) ENGINE=InnoDB;

CREATE TABLE `order_items` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `order_id` INT NOT NULL,
    `menu_item_id` INT NOT NULL,
    `quantity` INT NOT NULL DEFAULT 1,
    `unit_price` DECIMAL(10,2) NOT NULL,
    `subtotal` DECIMAL(10,2) NOT NULL,
    `special_instructions` VARCHAR(255) NULL,
    `kot_status` ENUM('sent', 'cooking', 'ready', 'served') DEFAULT 'sent',
    FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`menu_item_id`) REFERENCES `menu_items`(`id`)
) ENGINE=InnoDB;

-- Sample Restaurant Orders
INSERT INTO `orders` (`id`, `order_number`, `order_type`, `table_id`, `room_id`, `guest_id`, `waiter_id`, `status`, `subtotal`, `tax_amount`, `total_amount`, `payment_status`, `notes`) VALUES
(1, 'ORD-2001', 'dine_in', 2, NULL, NULL, 4, 'in_kitchen', 43.50, 4.35, 47.85, 'unpaid', 'Table T2 dinner order'),
(2, 'ORD-2002', 'room_service', NULL, 2, 1, 4, 'served', 29.50, 2.95, 32.45, 'billed_to_room', 'Room 102 delivery for guest Alexander Wright');

INSERT INTO `order_items` (`order_id`, `menu_item_id`, `quantity`, `unit_price`, `subtotal`, `special_instructions`, `kot_status`) VALUES
(1, 4, 1, 24.50, 24.50, 'Well done salmon', 'cooking'),
(1, 1, 1, 11.50, 11.50, 'Extra lemon slices', 'ready'),
(1, 14, 1, 7.50, 7.50, 'Regular sugar', 'served'),
(2, 5, 1, 18.00, 18.00, 'Deliver with fresh cutlery', 'served'),
(2, 11, 1, 8.50, 8.50, 'Cold dessert', 'served'),
(2, 14, 1, 3.00, 3.00, 'Double shot', 'served');

-- 10. Stock & Inventory Management
CREATE TABLE `stock_categories` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(50) NOT NULL,
    `type` ENUM('kitchen', 'bar', 'room_amenities', 'cleaning') DEFAULT 'kitchen'
) ENGINE=InnoDB;

INSERT INTO `stock_categories` (`id`, `name`, `type`) VALUES
(1, 'Meats & Seafood', 'kitchen'),
(2, 'Dairy & Produce', 'kitchen'),
(3, 'Spirits & Beverages', 'bar'),
(4, 'Guest Room Amenities', 'room_amenities'),
(5, 'Housekeeping & Linens', 'cleaning');

CREATE TABLE `stock_items` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `category_id` INT NOT NULL,
    `name` VARCHAR(100) NOT NULL,
    `unit` VARCHAR(20) NOT NULL,
    `current_stock` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `minimum_stock` DECIMAL(10,2) NOT NULL DEFAULT 5.00,
    `cost_per_unit` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `supplier` VARCHAR(100) NULL,
    `last_updated` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`category_id`) REFERENCES `stock_categories`(`id`)
) ENGINE=InnoDB;

INSERT INTO `stock_items` (`id`, `category_id`, `name`, `unit`, `current_stock`, `minimum_stock`, `cost_per_unit`, `supplier`) VALUES
(1, 1, 'Fresh Atlantic Salmon Fillet', 'kg', 14.50, 5.00, 22.00, 'Ocean Fresh Fish Co.'),
(2, 1, 'Prime Ribeye Beef Loins', 'kg', 18.00, 6.00, 26.50, 'Highland Prime Meats'),
(3, 2, 'Fresh Whole Milk', 'liters', 24.00, 10.00, 1.20, 'Green Valley Dairy'),
(4, 2, 'Parmesan Cheese Wheels', 'kg', 8.50, 3.00, 18.00, 'Euro Imports Ltd.'),
(5, 3, 'Bourbon Whiskey (750ml)', 'bottles', 12.00, 4.00, 32.00, 'Vintage Spirits Dist.'),
(6, 4, 'Luxury Shampoo & Shower Gel (50ml)', 'bottles', 180.00, 50.00, 0.85, 'Aroma Hospitality Supplies'),
(7, 4, 'Egyptian Cotton Bath Towels', 'pcs', 45.00, 20.00, 9.50, 'Linen Crafts Co.'),
(8, 4, 'Artisan Coffee Pods', 'boxes', 30.00, 10.00, 7.50, 'Master Roast Coffee'),
(9, 1, 'Jumbo Prawns (Tiger)', 'kg', 3.50, 5.00, 28.00, 'Ocean Fresh Fish Co.'); -- Low stock trigger demo

CREATE TABLE `stock_movements` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `stock_item_id` INT NOT NULL,
    `movement_type` ENUM('purchase_in', 'kitchen_out', 'room_out', 'wastage', 'adjustment') NOT NULL,
    `quantity` DECIMAL(10,2) NOT NULL,
    `unit_price` DECIMAL(10,2) DEFAULT 0.00,
    `reference_note` VARCHAR(255) NULL,
    `created_by` INT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`stock_item_id`) REFERENCES `stock_items`(`id`),
    FOREIGN KEY (`created_by`) REFERENCES `users`(`id`)
) ENGINE=InnoDB;

-- 11. Staff Directory & Attendance
CREATE TABLE `staff` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NULL,
    `staff_code` VARCHAR(20) NOT NULL UNIQUE,
    `first_name` VARCHAR(50) NOT NULL,
    `last_name` VARCHAR(50) NOT NULL,
    `designation` VARCHAR(50) NOT NULL,
    `department` ENUM('Management', 'Front Office', 'F&B Service', 'Kitchen', 'Housekeeping', 'Maintenance') NOT NULL,
    `phone` VARCHAR(20) NOT NULL,
    `email` VARCHAR(100) NULL,
    `salary` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `join_date` DATE NOT NULL,
    `status` ENUM('active', 'on_leave', 'terminated') DEFAULT 'active',
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB;

INSERT INTO `staff` (`id`, `user_id`, `staff_code`, `first_name`, `last_name`, `designation`, `department`, `phone`, `email`, `salary`, `join_date`, `status`) VALUES
(1, 1, 'STF-001', 'System', 'Admin', 'IT & Systems Manager', 'Management', '+1 555-0101', 'admin@luxuryhotel.com', 2800.00, '2023-01-15', 'active'),
(2, 3, 'STF-002', 'Sarah', 'Jenkins', 'Senior Receptionist', 'Front Office', '+1 555-0103', 'sarah.j@luxuryhotel.com', 1900.00, '2023-03-01', 'active'),
(3, 4, 'STF-003', 'David', 'Miller', 'Head Waiter', 'F&B Service', '+1 555-0104', 'david.m@luxuryhotel.com', 1500.00, '2023-05-10', 'active'),
(4, NULL, 'STF-004', 'Chef Antonio', 'Rossi', 'Executive Head Chef', 'Kitchen', '+1 555-0188', 'antonio.chef@luxuryhotel.com', 3200.00, '2022-11-01', 'active'),
(5, NULL, 'STF-005', 'Maria', 'Santos', 'Housekeeping Supervisor', 'Housekeeping', '+1 555-0192', 'maria.s@luxuryhotel.com', 1450.00, '2023-02-15', 'active');

CREATE TABLE `staff_attendance` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `staff_id` INT NOT NULL,
    `attendance_date` DATE NOT NULL,
    `clock_in` TIME NULL,
    `clock_out` TIME NULL,
    `status` ENUM('present', 'late', 'absent', 'half_day', 'on_leave') DEFAULT 'present',
    `notes` VARCHAR(255) NULL,
    FOREIGN KEY (`staff_id`) REFERENCES `staff`(`id`),
    UNIQUE KEY `unique_daily_attendance` (`staff_id`, `attendance_date`)
) ENGINE=InnoDB;

INSERT INTO `staff_attendance` (`staff_id`, `attendance_date`, `clock_in`, `clock_out`, `status`, `notes`) VALUES
(1, CURDATE(), '08:00:00', NULL, 'present', 'On duty'),
(2, CURDATE(), '07:45:00', NULL, 'present', 'Morning shift front desk'),
(3, CURDATE(), '10:00:00', NULL, 'present', 'Restaurant lunch/dinner shift'),
(4, CURDATE(), '09:30:00', NULL, 'present', 'Kitchen prep'),
(5, CURDATE(), '08:15:00', NULL, 'present', 'Room inspection rounds');

-- 12. Invoices & Billing (Consolidated Room & Restaurant Folio)
CREATE TABLE `invoices` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `invoice_number` VARCHAR(30) NOT NULL UNIQUE,
    `guest_id` INT NOT NULL,
    `room_id` INT NULL,
    `reservation_id` INT NULL,
    `room_charges` DECIMAL(10,2) DEFAULT 0.00,
    `restaurant_charges` DECIMAL(10,2) DEFAULT 0.00,
    `amenity_charges` DECIMAL(10,2) DEFAULT 0.00,
    `tax_rate` DECIMAL(5,2) DEFAULT 10.00,
    `tax_amount` DECIMAL(10,2) DEFAULT 0.00,
    `discount_amount` DECIMAL(10,2) DEFAULT 0.00,
    `total_amount` DECIMAL(10,2) NOT NULL,
    `paid_amount` DECIMAL(10,2) DEFAULT 0.00,
    `balance_due` DECIMAL(10,2) NOT NULL,
    `payment_status` ENUM('unpaid', 'partially_paid', 'paid') DEFAULT 'unpaid',
    `generated_by` INT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`guest_id`) REFERENCES `guests`(`id`),
    FOREIGN KEY (`room_id`) REFERENCES `rooms`(`id`),
    FOREIGN KEY (`reservation_id`) REFERENCES `reservations`(`id`)
) ENGINE=InnoDB;

CREATE TABLE `payments` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `invoice_id` INT NULL,
    `order_id` INT NULL,
    `amount` DECIMAL(10,2) NOT NULL,
    `payment_method` ENUM('Cash', 'Credit Card', 'Debit Card', 'UPI / QR', 'Bank Transfer') NOT NULL,
    `transaction_ref` VARCHAR(100) NULL,
    `notes` VARCHAR(255) NULL,
    `recorded_by` INT NULL,
    `payment_date` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`invoice_id`) REFERENCES `invoices`(`id`),
    FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`),
    FOREIGN KEY (`recorded_by`) REFERENCES `users`(`id`)
) ENGINE=InnoDB;

-- 13. Audit & Activity Logs
CREATE TABLE `audit_logs` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NULL,
    `module` VARCHAR(50) NOT NULL,
    `action` VARCHAR(100) NOT NULL,
    `details` TEXT NULL,
    `ip_address` VARCHAR(45) NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`)
) ENGINE=InnoDB;

INSERT INTO `audit_logs` (`user_id`, `module`, `action`, `details`) VALUES
(1, 'System', 'Database Initialization', 'Hotel management system initialized with 13 rooms, 4 roles and default catalog.');
