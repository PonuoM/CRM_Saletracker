<?php
/**
 * CompanyContext helper
 * Centralizes how current company_id is determined across the app.
 */

require_once __DIR__ . '/../core/Database.php';

class CompanyContext {
    /**
     * Returns the active company_id for current user/session.
     * Priority:
     * 1) $_SESSION['override_company_id'] if present (super_admin only)
     * 2) $_SESSION['company_id'] from login session
     * 3) Lookup user->company_id from DB (last resort)
     */
    public static function getCompanyId(Database $db = null) {
        if (!isset($_SESSION)) { 
            @session_start(); 
        }

        error_log("CompanyContext::getCompanyId() called");
        error_log("Session data: " . json_encode([
            'user_id' => $_SESSION['user_id'] ?? 'null',
            'company_id' => $_SESSION['company_id'] ?? 'null',
            'role_name' => $_SESSION['role_name'] ?? 'null',
            'override_company_id' => $_SESSION['override_company_id'] ?? 'null'
        ]));

        // อนุญาตให้ override เฉพาะ super_admin เท่านั้น และต้องยังเป็น super_admin อยู่
        if (!empty($_SESSION['override_company_id']) && 
            ($_SESSION['role_name'] ?? '') === 'super_admin' && 
            ($_SESSION['user_role'] ?? '') === 'super_admin') {
            $cid = (int)$_SESSION['override_company_id'];
            if ($cid > 0) {
                error_log("Using override_company_id: " . $cid);
                return $cid;
            }
        } else if (!empty($_SESSION['override_company_id']) && ($_SESSION['role_name'] ?? '') !== 'super_admin') {
            // Clear override if user is no longer super_admin
            unset($_SESSION['override_company_id']);
            error_log("Cleared override_company_id - user is not super_admin");
        }

        if (!empty($_SESSION['company_id'])) {
            $companyId = (int)$_SESSION['company_id'];
            if ($companyId > 0) {
                error_log("Using session company_id: " . $companyId);
                return $companyId;
            }
        }

        if ($db && !empty($_SESSION['user_id'])) {
            try {
                $row = $db->fetchOne(
                    "SELECT company_id FROM users WHERE user_id = ? AND is_active = 1",
                    [$_SESSION['user_id']]
                );
                if (!empty($row['company_id'])) {
                    $companyId = (int)$row['company_id'];
                    if ($companyId > 0) {
                        error_log("Using DB company_id: " . $companyId);
                        // Store in session for future use
                        $_SESSION['company_id'] = $companyId;
                        return $companyId;
                    }
                }
            } catch (Exception $e) {
                error_log("Error fetching company_id from DB: " . $e->getMessage());
            }
        }

        // For super_admin without specific company selection, allow viewing all data
        if (($_SESSION['role_name'] ?? '') === 'super_admin') {
            error_log("Super admin with no specific company - allowing all data access");
            return null; // null means no company filter
        }

        error_log("No valid company_id found, returning null");
        return null;
    }
}

?>

