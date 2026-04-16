-- Feature Automation: Loyalty & Tiers

DELIMITER //

-- 1. Trigger to generate points after each sale
-- Points: 1 point per 1000 RWF spent
CREATE TRIGGER trg_loyalty_update
AFTER INSERT ON sale
FOR EACH ROW
BEGIN
    IF NEW.customer_id IS NOT NULL THEN
        UPDATE customer 
        SET points = points + FLOOR(NEW.total_amount / 1000)
        WHERE customer_id = NEW.customer_id;
    END IF;
END //

-- 2. Trigger to update loyalty tiers based on lifetime liters (quantity)
-- Bronze (0L), Silver (500L), Gold (2000L), Platinum (5000L), Diamond (10000L)
CREATE TRIGGER trg_tier_check
AFTER INSERT ON sale
FOR EACH ROW
BEGIN
    DECLARE total_liters DECIMAL(15,2);
    
    IF NEW.customer_id IS NOT NULL THEN
        -- Calculate total lifetime liters for this customer
        SELECT SUM(quantity) INTO total_liters FROM sale WHERE customer_id = NEW.customer_id;
        UPDATE customer
        SET tier = CASE
            WHEN total_liters >= 10000 THEN 'Diamond'
            WHEN total_liters >= 5000 THEN 'Platinum'
            WHEN total_liters >= 2000 THEN 'Gold'
            WHEN total_liters >= 500 THEN 'Silver'
            ELSE 'Bronze'
        END
        WHERE customer_id = NEW.customer_id;
    END IF;
END //

-- 3. Views for Advanced Analytics
CREATE OR REPLACE VIEW vw_daily_sales AS
SELECT 
    DATE(sale_date) as date,
    COUNT(sale_id) as transaction_count,
    SUM(quantity) as total_liters,
    SUM(total_amount) as gross_revenue,
    SUM(discount_amount) as total_discounts,
    SUM(total_amount - discount_amount) as net_revenue
FROM sale
GROUP BY DATE(sale_date);

CREATE OR REPLACE VIEW vw_customer_ranking AS
SELECT 
    c.customer_id,
    c.name,
    c.tier,
    c.points,
    COUNT(s.sale_id) as total_visits,
    SUM(s.quantity) as lifetime_liters,
    SUM(s.total_amount) as total_spent
FROM customer c
LEFT JOIN sale s ON c.customer_id = s.customer_id
GROUP BY c.customer_id, c.name, c.tier, c.points
ORDER BY total_spent DESC;

CREATE OR REPLACE VIEW vw_inventory_status AS
SELECT 
    f.fuel_name,
    t.tank_id,
    t.capacity,
    t.current_stock,
    (t.current_stock / t.capacity * 100) as stock_percentage,
    CASE 
        WHEN (t.current_stock / t.capacity * 100) < 20 THEN 'CRITICAL'
        WHEN (t.current_stock / t.capacity * 100) < 50 THEN 'LOW'
        ELSE 'HEALTHY'
    END as status
FROM fuel_type f
JOIN tank t ON f.fuel_id = t.fuel_id;

DELIMITER ;
