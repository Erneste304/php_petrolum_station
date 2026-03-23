USE petroleum_station_db;

CREATE TABLE IF NOT EXISTS fuel_request (
    request_id INT PRIMARY KEY AUTO_INCREMENT,
    customer_id INT NOT NULL,
    fuel_id INT NOT NULL,
    requested_quantity DECIMAL(10,2) NOT NULL,
    payment_method VARCHAR(50) NOT NULL,
    status ENUM('Pending', 'Approved', 'Completed', 'Cancelled') DEFAULT 'Pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customer(customer_id),
    FOREIGN KEY (fuel_id) REFERENCES fuel_type(fuel_id)
);
