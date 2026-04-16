USE petroleum_station_db;

-- 1. Create Permission Table
CREATE TABLE IF NOT EXISTS permission (
    permission_id INT PRIMARY KEY AUTO_INCREMENT,
    permission_name VARCHAR(50) UNIQUE NOT NULL,
    module VARCHAR(50),
    description VARCHAR(200),
    INDEX idx_permission_module (module)
);

-- 2. Create Role Permission Mapping Table
CREATE TABLE IF NOT EXISTS role_permission (
    role VARCHAR(50) NOT NULL,
    permission_id INT NOT NULL,
    PRIMARY KEY (role, permission_id),
    FOREIGN KEY (permission_id) REFERENCES permission(permission_id) ON DELETE CASCADE,
    INDEX idx_role_permission_role (role)
);

-- 3. Register All Existing Modules as Permissions
INSERT IGNORE INTO permission (permission_name, module, description) VALUES
('stations', 'Core', 'Manage and view stations'),
('employees', 'Core', 'Manage personnel'),
('customers', 'Core', 'Manage client database'),
('fuel', 'Inventory', 'Manage fuel types and pricing'),
('sales', 'Transactions', 'Process and view sales'),
('fuel_delivery', 'Supply', 'Manage bulk fuel deliveries'),
('car_wash', 'Services', 'Access Car Wash module'),
('loyalty', 'Marketing', 'Manage loyalty and rewards'),
('reports', 'Analysis', 'View system reports'),
('users', 'Security', 'Manage system access'),
('shares', 'Partners', 'Manage partner shares'),
('approve_services', 'Services', 'Admin-level approval of new services'),
('manage_bookings', 'Services', 'Update service booking statuses'),
('manage_services', 'Services', 'Create and edit service offerings'),
('view_any_loyalty', 'Marketing', 'View other customers’ loyalty profiles'),
('accounting', 'Finance', 'Access accountant dashboard and payments'),
('manage_reports', 'Analysis', 'Full access to financial and operational reports'),
('reception', 'Front Desk', 'Access the operational staff and receptionist dashboard');

-- 4. Assign Default Permissions to Roles

DELETE FROM role_permission;

-- ADMIN (All privileges)
INSERT INTO role_permission (role, permission_id)
SELECT 'admin', permission_id FROM permission;

-- ACCOUNTANT (Financial focus)
INSERT INTO role_permission (role, permission_id)
SELECT 'accountant', permission_id FROM permission 
WHERE permission_name IN ('reports', 'sales', 'fuel', 'shares', 'accounting');

-- RECEPTIONIST (Operations focus)
INSERT INTO role_permission (role, permission_id)
SELECT 'receptionist', permission_id FROM permission 
WHERE permission_name IN ('sales', 'customers', 'car_wash', 'loyalty', 'fuel', 'manage_bookings', 'reception');

-- STAFF (General operations)
INSERT INTO role_permission (role, permission_id)
SELECT 'staff', permission_id FROM permission 
WHERE permission_name IN ('sales', 'customers', 'fuel', 'manage_bookings');

-- CUSTOMER (Restricted to services)
INSERT INTO role_permission (role, permission_id)
SELECT 'customer', permission_id FROM permission 
WHERE permission_name IN ('car_wash', 'loyalty');

-- PARTNER (Investment focus)
INSERT INTO role_permission (role, permission_id)
SELECT 'partner', permission_id FROM permission 
WHERE permission_name IN ('shares', 'reports');

-- 5. Verification
SELECT r.role, p.permission_name 
FROM role_permission r 
JOIN permission p ON r.permission_id = p.permission_id 
ORDER BY r.role;
