USE petroleum_station_db;

CREATE TABLE IF NOT EXISTS car_wash_service (
    service_id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    price DECIMAL(10,2) NOT NULL,
    estimated_duration_minutes INT DEFAULT 30
);

CREATE TABLE IF NOT EXISTS car_wash_booking (
    booking_id INT PRIMARY KEY AUTO_INCREMENT,
    customer_id INT NOT NULL,
    service_id INT NOT NULL,
    booking_date DATE NOT NULL,
    booking_time TIME NOT NULL,
    vehicle_plate VARCHAR(20) NOT NULL,
    status ENUM('Pending', 'Confirmed', 'Completed', 'Cancelled') DEFAULT 'Pending',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customer(customer_id),
    FOREIGN KEY (service_id) REFERENCES car_wash_service(service_id)
);

-- Insert default services if they don't exist
INSERT INTO car_wash_service (name, description, price, estimated_duration_minutes) 
SELECT * FROM (SELECT 'Basic External Wash', 'Standard exterior wash and dry', 5000.00, 30) AS tmp
WHERE NOT EXISTS (
    SELECT name FROM car_wash_service WHERE name = 'Basic External Wash'
) LIMIT 1;

INSERT INTO car_wash_service (name, description, price, estimated_duration_minutes) 
SELECT * FROM (SELECT 'Full Detail', 'Comprehensive exterior & interior cleaning, waxing, and vacuuming', 15000.00, 120) AS tmp
WHERE NOT EXISTS (
    SELECT name FROM car_wash_service WHERE name = 'Full Detail'
) LIMIT 1;

INSERT INTO car_wash_service (name, description, price, estimated_duration_minutes) 
SELECT * FROM (SELECT 'Interior Only', 'Deep clean of interior seats, mats, and dashboard', 8000.00, 60) AS tmp
WHERE NOT EXISTS (
    SELECT name FROM car_wash_service WHERE name = 'Interior Only'
) LIMIT 1;
