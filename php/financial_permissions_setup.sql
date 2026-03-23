USE petroleum_station_db;

CREATE TABLE IF NOT EXISTS finance_permission_request (
    permission_id INT PRIMARY KEY AUTO_INCREMENT,
    accountant_id INT NOT NULL,
    admin_id INT,
    module_name VARCHAR(50) NOT NULL, -- e.g., 'Salaries', 'SharePurchases'
    status ENUM('Pending', 'Granted', 'Closed', 'Denied') DEFAULT 'Pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expiry DATETIME,
    FOREIGN KEY (accountant_id) REFERENCES users(user_id),
    FOREIGN KEY (admin_id) REFERENCES users(user_id)
);
