-- Run once in phpMyAdmin on an existing GreenLoop database.
-- This is safe to run more than once and does not drop existing data.
CREATE TABLE IF NOT EXISTS rewards (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(150) NOT NULL,
    description VARCHAR(500) NOT NULL DEFAULT '',
    points_cost INT NOT NULL,
    stock INT NULL DEFAULT NULL COMMENT 'NULL = unlimited',
    coupon_valid_days SMALLINT UNSIGNED NOT NULL DEFAULT 30,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS coupon_redemptions (
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

CREATE TABLE IF NOT EXISTS point_transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    amount INT NOT NULL,
    description VARCHAR(255) NOT NULL,
    related_type VARCHAR(30) NOT NULL DEFAULT '',
    related_id INT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
);

INSERT INTO point_transactions (user_id, amount, description, related_type)
SELECT u.id, u.points, 'ยอดแต้มตั้งต้นก่อนเริ่มบันทึกประวัติ', 'opening'
FROM users u
WHERE NOT EXISTS (
    SELECT 1 FROM point_transactions pt WHERE pt.user_id = u.id AND pt.related_type = 'opening'
);
