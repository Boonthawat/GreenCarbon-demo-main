-- GreenLoop: ติดตั้งใหม่ 10 ตาราง + ข้อมูลตัวอย่าง
-- นำเข้าผ่าน phpMyAdmin > Import; คำสั่งนี้ลบฐานข้อมูล greenloop เดิมก่อนสร้างใหม่
DROP DATABASE IF EXISTS greenloop;
CREATE DATABASE greenloop CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE greenloop;
SET NAMES utf8mb4;

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    phone VARCHAR(10) NOT NULL,
    avatar VARCHAR(50) NOT NULL DEFAULT '' COMMENT 'ชื่อไฟล์รูปโปรไฟล์ใน uploads/',
    password VARCHAR(255) NOT NULL,
    question VARCHAR(100) NOT NULL COMMENT 'คำถามกันลืมรหัสผ่านที่สมาชิกเลือก',
    answer VARCHAR(255) NOT NULL COMMENT 'คำตอบกันลืมรหัสผ่าน (เข้ารหัส)',
    role ENUM('user','admin') NOT NULL DEFAULT 'user',
    status ENUM('active','suspended') NOT NULL DEFAULT 'active',
    points INT NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- ค่าสัมประสิทธิ์การปล่อยคาร์บอน: kgCO2e ต่อ 1 หน่วยที่ใช้ต่อเดือน
CREATE TABLE factors (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    unit VARCHAR(30) NOT NULL,
    factor DECIMAL(8,3) NOT NULL
);

-- หมวดขยะอิเล็กทรอนิกส์: kgCO2e ที่ลดได้เมื่อรีไซเคิล 1 กก.
CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    co2_per_kg DECIMAL(6,2) NOT NULL
);

CREATE TABLE footprints (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    year SMALLINT NOT NULL,
    total_kg DECIMAL(10,2) NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE (user_id, year),
    FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
);

CREATE TABLE rewards (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(150) NOT NULL,
    description VARCHAR(500) NOT NULL DEFAULT '',
    points_cost INT NOT NULL,
    stock INT NULL DEFAULT NULL COMMENT 'NULL = unlimited',
    coupon_valid_days SMALLINT UNSIGNED NOT NULL DEFAULT 30,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE recycles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    category_id INT NOT NULL,
    item_name VARCHAR(150) NOT NULL,
    weight_kg DECIMAL(6,2) NOT NULL,
    image VARCHAR(50) NOT NULL DEFAULT '' COMMENT 'ชื่อไฟล์รูปสินค้าใน uploads/',
    status ENUM('pending','done','rejected') NOT NULL DEFAULT 'pending',
    points INT NOT NULL DEFAULT 0,
    co2_kg DECIMAL(8,2) NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES categories (id)
);

CREATE TABLE coupon_redemptions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    reward_id INT NOT NULL,
    points_spent INT NOT NULL,
    status ENUM('pending','approved','rejected','used','expired') NOT NULL DEFAULT 'pending',
    coupon_code VARCHAR(32) NULL UNIQUE,
    expires_at DATETIME NULL,
    used_at DATETIME NULL,
    stock_reserved TINYINT(1) NOT NULL DEFAULT 0,
    recipient_name VARCHAR(100) NULL,
    recipient_phone VARCHAR(20) NULL,
    shipping_address VARCHAR(500) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    reviewed_at DATETIME NULL,
    reviewed_by INT NULL,
    FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
    FOREIGN KEY (reward_id) REFERENCES rewards (id),
    FOREIGN KEY (reviewed_by) REFERENCES users (id) ON DELETE SET NULL
);

CREATE TABLE point_transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    amount INT NOT NULL,
    description VARCHAR(255) NOT NULL,
    related_type VARCHAR(30) NOT NULL DEFAULT '',
    related_id INT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
);

-- เนื้อหา: แสดงเฉพาะช่วง start_at ถึง end_at (end_at ว่าง = ไม่มีกำหนด)
CREATE TABLE contents (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(150) NOT NULL,
    body TEXT NOT NULL,
    start_at DATETIME NOT NULL,
    end_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
);

-- การแจ้งเตือน: user_id = สมาชิกผู้รับ, ว่าง (NULL) = ส่งถึงผู้ดูแลระบบ
CREATE TABLE notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    title VARCHAR(150) NOT NULL,
    message VARCHAR(500) NOT NULL,
    link VARCHAR(100) NOT NULL DEFAULT '',
    is_read TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
);

-- ข้อมูลตัวอย่าง: admin = admin1234, สมาชิก = demo1234, คำถามกันลืมทุกบัญชี = ชื่อสัตว์เลี้ยงตัวแรกของคุณ, คำตอบ = greenloop
INSERT INTO users (id, name, email, phone, password, question, answer, role, points, created_at) VALUES
(1, 'ผู้ดูแลระบบ', 'admin@greenloop.local', '0800000000', '$2y$10$Hlspc86w66632ObfEZvRJ.wq7fHdnVnTd6YJFsJAqLV5qfTt78sbC', 'ชื่อสัตว์เลี้ยงตัวแรกของคุณ', '$2y$10$XH5vVtX06Oezhe.zHP.j1.Y0InpB1KEstUe65NM6WLd9cua7wBfaa', 'admin', 0, NOW() - INTERVAL 150 DAY),
(2, 'สมชาย ใจดี', 'demo@greenloop.local', '0811111111', '$2y$10$2otFyK2pF5rcaI5rtbnbvuWkcaJncBeHlcx5LYGHIQnMXmDRKRIrK', 'ชื่อสัตว์เลี้ยงตัวแรกของคุณ', '$2y$10$XH5vVtX06Oezhe.zHP.j1.Y0InpB1KEstUe65NM6WLd9cua7wBfaa', 'user', 114, NOW() - INTERVAL 120 DAY),
(3, 'สมหญิง รักษ์โลก', 'ying@greenloop.local', '0822222222', '$2y$10$2otFyK2pF5rcaI5rtbnbvuWkcaJncBeHlcx5LYGHIQnMXmDRKRIrK', 'ชื่อสัตว์เลี้ยงตัวแรกของคุณ', '$2y$10$XH5vVtX06Oezhe.zHP.j1.Y0InpB1KEstUe65NM6WLd9cua7wBfaa', 'user', 48, NOW() - INTERVAL 80 DAY),
(4, 'วิชัย ประหยัดไฟ', 'wichai@greenloop.local', '0833333333', '$2y$10$2otFyK2pF5rcaI5rtbnbvuWkcaJncBeHlcx5LYGHIQnMXmDRKRIrK', 'ชื่อสัตว์เลี้ยงตัวแรกของคุณ', '$2y$10$XH5vVtX06Oezhe.zHP.j1.Y0InpB1KEstUe65NM6WLd9cua7wBfaa', 'user', 120, NOW() - INTERVAL 40 DAY);

INSERT INTO point_transactions (user_id, amount, description, related_type)
SELECT id, points, 'ยอดแต้มตั้งต้นก่อนเริ่มบันทึกประวัติ', 'opening' FROM users;

INSERT INTO factors (name, unit, factor) VALUES
('ไฟฟ้าที่ใช้ในบ้าน', 'หน่วย (kWh)', 0.5),
('น้ำมันเบนซิน', 'ลิตร', 2.2),
('ก๊าซหุงต้ม', 'กก.', 3.1),
('ขยะทั่วไปที่ทิ้ง', 'กก.', 0.7),
('มื้ออาหารที่มีเนื้อสัตว์', 'มื้อ', 1.5);

INSERT INTO categories (id, name, co2_per_kg) VALUES
(1, 'โทรศัพท์ / แท็บเล็ต', 2.0),
(2, 'คอมพิวเตอร์ / โน้ตบุ๊ก', 1.8),
(3, 'แบตเตอรี่ / สายชาร์จ', 2.5),
(4, 'อุปกรณ์ IT อื่นๆ', 1.5);

INSERT INTO footprints (user_id, year, total_kg, created_at) VALUES
(2, YEAR(NOW()) - 1, 3700, NOW() - INTERVAL 118 DAY),
(2, YEAR(NOW()), 3040, NOW() - INTERVAL 30 DAY),
(3, YEAR(NOW()), 2310, NOW() - INTERVAL 70 DAY),
(4, YEAR(NOW()), 4400, NOW() - INTERVAL 38 DAY);

INSERT INTO recycles (user_id, category_id, item_name, weight_kg, status, points, co2_kg, created_at) VALUES
(2, 1, 'โทรศัพท์เก่าเปิดไม่ติด', 0.4, 'done', 8, 0.80, NOW() - INTERVAL 97 DAY),
(2, 2, 'โน้ตบุ๊กจอแตก', 2.3, 'done', 46, 4.14, NOW() - INTERVAL 55 DAY),
(3, 3, 'แบตเตอรี่สำรองบวม', 0.9, 'done', 18, 2.25, NOW() - INTERVAL 43 DAY),
(4, 4, 'เครื่องพิมพ์เสีย', 4.5, 'done', 90, 6.75, NOW() - INTERVAL 15 DAY),
(2, 4, 'เมาส์พัง', 0.2, 'rejected', 0, 0, NOW() - INTERVAL 9 DAY),
(3, 1, 'แท็บเล็ตแบตเสื่อม', 0.5, 'pending', 0, 0, NOW() - INTERVAL 3 DAY),
(4, 3, 'สายชาร์จชำรุดหลายเส้น', 0.3, 'pending', 0, 0, NOW() - INTERVAL 1 DAY);

INSERT INTO contents (user_id, title, body, start_at, end_at) VALUES
(1, 'E-Waste คืออะไร ทำไมต้องรีไซเคิล', 'ขยะอิเล็กทรอนิกส์มีทั้งโลหะมีค่าและสารอันตราย การส่งรีไซเคิลช่วยนำวัสดุกลับมาใช้ใหม่และลดก๊าซเรือนกระจก', NOW() - INTERVAL 25 DAY, NULL),
(1, '5 วิธีลดคาร์บอนฟุตพริ้นท์ในบ้าน', 'ตั้งแอร์ 26 องศา ถอดปลั๊กเมื่อไม่ใช้ แยกขยะ ใช้ขนส่งสาธารณะ และลดอาหารเหลือทิ้ง', NOW() - INTERVAL 10 DAY, NULL),
(1, 'เปิดจุดรับ E-Waste แห่งใหม่', 'ข่าวนี้ตั้งเวลาเผยแพร่ล่วงหน้า', NOW() + INTERVAL 3 DAY, NOW() + INTERVAL 30 DAY),
(1, 'สรุปกิจกรรม Big Cleaning Day', 'ข่าวนี้หมดเวลาแสดงผลแล้ว', NOW() - INTERVAL 60 DAY, NOW() - INTERVAL 40 DAY);

INSERT INTO notifications (user_id, title, message, link, is_read, created_at) VALUES
(NULL, 'สมาชิกใหม่', 'วิชัย ประหยัดไฟ (wichai@greenloop.local)', 'admin/users.php', 1, NOW() - INTERVAL 40 DAY),
(NULL, 'คำขอรีไซเคิลใหม่', 'วิชัย ประหยัดไฟ: สายชาร์จชำรุดหลายเส้น', 'admin/recycles.php?status=pending', 0, NOW() - INTERVAL 1 DAY),
(2, 'รีไซเคิลสำเร็จ รับ 46 แต้ม', 'โน้ตบุ๊กจอแตก ลดคาร์บอนได้ 4.14 kgCO₂e', 'points.php', 1, NOW() - INTERVAL 55 DAY);
