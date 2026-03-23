USE petroleum_station_db;

CREATE TABLE IF NOT EXISTS loyalty_reward (
    reward_id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    points_cost INT NOT NULL
);

CREATE TABLE IF NOT EXISTS loyalty_redemption (
    redemption_id INT PRIMARY KEY AUTO_INCREMENT,
    customer_id INT NOT NULL,
    reward_id INT NOT NULL,
    redeemed_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    status ENUM('Pending', 'Fulfilled') DEFAULT 'Pending',
    FOREIGN KEY (customer_id) REFERENCES customer(customer_id),
    FOREIGN KEY (reward_id) REFERENCES loyalty_reward(reward_id)
);

-- Insert default rewards if they don't exist
INSERT INTO loyalty_reward (name, description, points_cost) 
SELECT * FROM (SELECT 'Free 5 Liters of Fuel', 'Redeem points for 5 free liters of any fuel type.', 500) AS tmp
WHERE NOT EXISTS (
    SELECT name FROM loyalty_reward WHERE name = 'Free 5 Liters of Fuel'
) LIMIT 1;

INSERT INTO loyalty_reward (name, description, points_cost) 
SELECT * FROM (SELECT 'Basic Car Wash', 'One free basic exterior car wash.', 300) AS tmp
WHERE NOT EXISTS (
    SELECT name FROM loyalty_reward WHERE name = 'Basic Car Wash'
) LIMIT 1;

INSERT INTO loyalty_reward (name, description, points_cost) 
SELECT * FROM (SELECT '10% Off Next Servicing', 'Get a 10% discount on your next full car detailing.', 800) AS tmp
WHERE NOT EXISTS (
    SELECT name FROM loyalty_reward WHERE name = '10% Off Next Servicing'
) LIMIT 1;
