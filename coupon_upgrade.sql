-- Run once in phpMyAdmin for installations that already imported the older coupon_setup.sql.
ALTER TABLE rewards
    ADD COLUMN stock INT NULL DEFAULT NULL COMMENT 'NULL = unlimited' AFTER points_cost,
    ADD COLUMN coupon_valid_days SMALLINT UNSIGNED NOT NULL DEFAULT 30 AFTER stock;

ALTER TABLE coupon_redemptions
    MODIFY status ENUM('pending','approved','rejected','used','expired') NOT NULL DEFAULT 'pending',
    ADD COLUMN expires_at DATETIME NULL AFTER coupon_code,
    ADD COLUMN used_at DATETIME NULL AFTER expires_at,
    ADD COLUMN stock_reserved TINYINT(1) NOT NULL DEFAULT 0 AFTER used_at,
    ADD COLUMN recipient_name VARCHAR(100) NULL AFTER used_at,
    ADD COLUMN recipient_phone VARCHAR(20) NULL AFTER recipient_name,
    ADD COLUMN shipping_address VARCHAR(500) NULL AFTER recipient_phone;

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

UPDATE coupon_redemptions SET expires_at = DATE_ADD(COALESCE(reviewed_at, created_at), INTERVAL 30 DAY)
WHERE status = 'approved' AND expires_at IS NULL;

INSERT INTO point_transactions (user_id, amount, description, related_type)
SELECT u.id, u.points, 'ยอดแต้มตั้งต้นก่อนเริ่มบันทึกประวัติ', 'opening'
FROM users u
WHERE NOT EXISTS (
    SELECT 1 FROM point_transactions pt WHERE pt.user_id = u.id AND pt.related_type = 'opening'
);
