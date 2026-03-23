USE petroleum_station_db;

CREATE TABLE IF NOT EXISTS user_permission (
    permission_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    module_name VARCHAR(50) NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_permission (user_id, module_name)
);

-- Note: The admin user automatically bypasses permissions in PHP logic
