USE petroleum_station_db;

CREATE TABLE IF NOT EXISTS bulk_fuel_order (
    order_id INT PRIMARY KEY AUTO_INCREMENT,
    customer_id INT NOT NULL,
    fuel_id INT NOT NULL,
    quantity_liters DECIMAL(10,2) NOT NULL,
    delivery_date DATE NOT NULL,
    delivery_address TEXT NOT NULL,
    total_estimated_cost DECIMAL(10,2) NOT NULL,
    status ENUM('Pending', 'Approved', 'Delivered', 'Cancelled') DEFAULT 'Pending',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customer(customer_id),
    FOREIGN KEY (fuel_id) REFERENCES fuel_type(fuel_id)
);
