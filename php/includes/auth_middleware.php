<?php
session_start();


if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}


function isAdmin()
{
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}


function isStaff()
{
    return isset($_SESSION['role']) && $_SESSION['role'] === 'staff';
}


function isPartner()
{
    return isset($_SESSION['role']) && $_SESSION['role'] === 'partner';
}


function isCustomer()
{
    return isset($_SESSION['role']) && $_SESSION['role'] === 'customer';
}


function isAccountant()
{
    return isset($_SESSION['role']) && $_SESSION['role'] === 'accountant';
}


function isReceptionist()
{
    return isset($_SESSION['role']) && $_SESSION['role'] === 'receptionist';
}


/**
 * Check if the Accountant has active permission for a specific financial action
 */
function isFinancePermitted($action)
{
    global $pdo;
    if (isAdmin()) return true;
    if (!isAccountant()) return false;
    
    try {
        $stmt = $pdo->prepare("
            SELECT 1 FROM finance_permission_request 
            WHERE accountant_id = ? AND module_name = ? AND status = 'Granted' 
            AND (expiry IS NULL OR expiry > NOW())
        ");
        $stmt->execute([$_SESSION['user_id'], $action]);
        return $stmt->fetch() !== false;
    } catch (Exception $e) {
        return false;
    }
}


function requireAdmin()
{
    if (!isAdmin()) {
        $_SESSION['error'] = 'Access denied. Administrator privileges required.';
        header('Location: ../index.php');
        exit;
    }
}


function hasPermission($module_name)
{
    global $pdo;
    
    
    if (isAdmin()) {
        return true;
    }
    
    
    try {
        if (!isset($_SESSION['user_id']) || !isset($pdo)) {
            return false;
        }
        
        $stmt = $pdo->prepare("SELECT 1 FROM user_permission WHERE user_id = ? AND module_name = ?");
        $stmt->execute([$_SESSION['user_id'], $module_name]);
        return $stmt->fetch() !== false;
    } catch (Exception $e) {
        return false;
    }
}


function requirePermission($module_name)
{
    // Explicit Restriction: Partners cannot manage employees or users
    if (isPartner() && in_array($module_name, ['employees', 'users'])) {
        $_SESSION['error'] = "Access denied. Partners are restricted from managing personnel.";
        header('Location: ../index.php');
        exit;
    }

    if (!hasPermission($module_name)) {
        $_SESSION['error'] = "Access denied. You do not have permission to view the {$module_name} dashboard.";
        header('Location: ../index.php');
        exit;
    }
}
?>
