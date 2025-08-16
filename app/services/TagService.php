<?php
/**
 * Tag Service
 * จัดการ Tags สำหรับลูกค้า
 */

require_once __DIR__ . '/../core/Database.php';

class TagService {
    private $db;

    public function __construct() {
        try {
            if (!class_exists('Database')) {
                throw new Exception('Database class not found');
            }
            $this->db = new Database();
        } catch (Exception $e) {
            error_log("TagService constructor error: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * เพิ่ม tag ให้ลูกค้า
     */
    public function addTagToCustomer($customerId, $userId, $tagName, $tagColor = '#007bff') {
        try {
            // ตรวจสอบว่ามี tag นี้แล้วหรือไม่
            $existingTag = $this->getCustomerTag($customerId, $userId, $tagName);
            if ($existingTag) {
                return ['success' => false, 'message' => 'Tag นี้มีอยู่แล้ว'];
            }

            $sql = "INSERT INTO customer_tags (customer_id, user_id, tag_name, tag_color) 
                    VALUES (?, ?, ?, ?)";
            $this->db->execute($sql, [$customerId, $userId, $tagName, $tagColor]);
            
            return ['success' => true, 'message' => 'เพิ่ม Tag สำเร็จ'];
        } catch (Exception $e) {
            error_log('TagService::addTagToCustomer Error: ' . $e->getMessage());
            return ['success' => false, 'message' => 'เกิดข้อผิดพลาดในการเพิ่ม Tag'];
        }
    }

    /**
     * ลบ tag ของลูกค้า
     */
    public function removeTagFromCustomer($customerId, $userId, $tagName) {
        try {
            $sql = "DELETE FROM customer_tags 
                    WHERE customer_id = ? AND user_id = ? AND tag_name = ?";
            $this->db->execute($sql, [$customerId, $userId, $tagName]);
            
            return ['success' => true, 'message' => 'ลบ Tag สำเร็จ'];
        } catch (Exception $e) {
            error_log('TagService::removeTagFromCustomer Error: ' . $e->getMessage());
            return ['success' => false, 'message' => 'เกิดข้อผิดพลาดในการลบ Tag'];
        }
    }

    /**
     * ดึง tags ทั้งหมดของลูกค้า (สำหรับ user คนนั้น)
     */
    public function getCustomerTags($customerId, $userId) {
        try {
            $sql = "SELECT tag_name, tag_color, created_at 
                    FROM customer_tags 
                    WHERE customer_id = ? AND user_id = ? 
                    ORDER BY created_at DESC";
            return $this->db->fetchAll($sql, [$customerId, $userId]);
        } catch (Exception $e) {
            error_log('TagService::getCustomerTags Error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * ตรวจสอบว่าลูกค้ามี tag นี้หรือไม่
     */
    public function getCustomerTag($customerId, $userId, $tagName) {
        try {
            $sql = "SELECT * FROM customer_tags 
                    WHERE customer_id = ? AND user_id = ? AND tag_name = ?";
            return $this->db->fetchOne($sql, [$customerId, $userId, $tagName]);
        } catch (Exception $e) {
            error_log('TagService::getCustomerTag Error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * ดึง tags ทั้งหมดที่ user คนนี้เคยใช้
     */
    public function getUserTags($userId) {
        try {
            $sql = "SELECT DISTINCT tag_name, tag_color, COUNT(*) as usage_count
                    FROM customer_tags 
                    WHERE user_id = ? 
                    GROUP BY tag_name, tag_color 
                    ORDER BY usage_count DESC, tag_name ASC";
            return $this->db->fetchAll($sql, [$userId]);
        } catch (Exception $e) {
            error_log('TagService::getUserTags Error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * ค้นหาลูกค้าตาม tags
     */
    public function getCustomersByTags($userId, $tagNames = [], $additionalFilters = []) {
        try {
            $sql = "SELECT DISTINCT c.*, 
                           GROUP_CONCAT(ct.tag_name) as customer_tags,
                           GROUP_CONCAT(ct.tag_color) as tag_colors
                    FROM customers c 
                    LEFT JOIN customer_tags ct ON c.customer_id = ct.customer_id AND ct.user_id = ?";
            
            $params = [$userId];
            $whereConditions = [];

            // Filter by tags
            if (!empty($tagNames)) {
                $tagPlaceholders = str_repeat('?,', count($tagNames) - 1) . '?';
                $sql .= " INNER JOIN customer_tags ct2 ON c.customer_id = ct2.customer_id 
                         AND ct2.user_id = ? AND ct2.tag_name IN ($tagPlaceholders)";
                $params[] = $userId;
                $params = array_merge($params, $tagNames);
            }

            // Additional filters
            if (!empty($additionalFilters['temperature'])) {
                $whereConditions[] = "c.temperature_status = ?";
                $params[] = $additionalFilters['temperature'];
            }

            if (!empty($additionalFilters['grade'])) {
                $whereConditions[] = "c.customer_grade = ?";
                $params[] = $additionalFilters['grade'];
            }

            if (!empty($additionalFilters['province'])) {
                $whereConditions[] = "c.province = ?";
                $params[] = $additionalFilters['province'];
            }

            if (!empty($additionalFilters['name'])) {
                $whereConditions[] = "(c.first_name LIKE ? OR c.last_name LIKE ?)";
                $searchTerm = '%' . $additionalFilters['name'] . '%';
                $params[] = $searchTerm;
                $params[] = $searchTerm;
            }

            if (!empty($additionalFilters['phone'])) {
                $whereConditions[] = "c.phone LIKE ?";
                $params[] = '%' . $additionalFilters['phone'] . '%';
            }

            // Apply where conditions
            if (!empty($whereConditions)) {
                $sql .= " WHERE " . implode(' AND ', $whereConditions);
            }

            $sql .= " GROUP BY c.customer_id ORDER BY c.created_at DESC";

            return $this->db->fetchAll($sql, $params);
        } catch (Exception $e) {
            error_log('TagService::getCustomersByTags Error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * ดึง predefined tags (tags ที่กำหนดไว้ล่วงหน้า) พร้อม usage_count
     */
    public function getPredefinedTags($userId = null) {
        try {
            $sql = "SELECT 
                        pt.tag_name, 
                        pt.tag_color,
                        COALESCE(COUNT(ct.tag_id), 0) as usage_count
                    FROM predefined_tags pt 
                    LEFT JOIN customer_tags ct ON pt.tag_name = ct.tag_name AND ct.user_id = ?
                    WHERE pt.is_global = 1 OR pt.created_by = ? 
                    GROUP BY pt.tag_name, pt.tag_color
                    ORDER BY pt.is_global DESC, pt.tag_name ASC";
            return $this->db->fetchAll($sql, [$userId, $userId]);
        } catch (Exception $e) {
            error_log('TagService::getPredefinedTags Error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * สร้าง predefined tag ใหม่
     */
    public function createPredefinedTag($tagName, $tagColor, $isGlobal = false, $createdBy = null) {
        try {
            $sql = "INSERT INTO predefined_tags (tag_name, tag_color, is_global, created_by) 
                    VALUES (?, ?, ?, ?)";
            $this->db->execute($sql, [$tagName, $tagColor, $isGlobal, $createdBy]);
            
            return ['success' => true, 'message' => 'สร้าง Predefined Tag สำเร็จ'];
        } catch (Exception $e) {
            error_log('TagService::createPredefinedTag Error: ' . $e->getMessage());
            return ['success' => false, 'message' => 'เกิดข้อผิดพลาดในการสร้าง Predefined Tag'];
        }
    }

    /**
     * ลบ tags หลายอันพร้อมกัน
     */
    public function bulkRemoveTags($customerIds, $userId, $tagNames) {
        try {
            if (empty($customerIds) || empty($tagNames)) {
                return ['success' => false, 'message' => 'ข้อมูลไม่ครบถ้วน'];
            }

            $customerPlaceholders = str_repeat('?,', count($customerIds) - 1) . '?';
            $tagPlaceholders = str_repeat('?,', count($tagNames) - 1) . '?';
            
            $sql = "DELETE FROM customer_tags 
                    WHERE customer_id IN ($customerPlaceholders) 
                    AND user_id = ? 
                    AND tag_name IN ($tagPlaceholders)";
            
            $params = array_merge($customerIds, [$userId], $tagNames);
            $this->db->execute($sql, $params);
            
            return ['success' => true, 'message' => 'ลบ Tags สำเร็จ'];
        } catch (Exception $e) {
            error_log('TagService::bulkRemoveTags Error: ' . $e->getMessage());
            return ['success' => false, 'message' => 'เกิดข้อผิดพลาดในการลบ Tags'];
        }
    }

    /**
     * เพิ่ม tags หลายอันพร้อมกัน
     */
    public function bulkAddTags($customerIds, $userId, $tagName, $tagColor = '#007bff') {
        try {
            if (empty($customerIds) || empty($tagName)) {
                return ['success' => false, 'message' => 'ข้อมูลไม่ครับถ้วน'];
            }

            $values = [];
            $params = [];
            
            foreach ($customerIds as $customerId) {
                // ตรวจสอบว่ามี tag นี้แล้วหรือไม่
                if (!$this->getCustomerTag($customerId, $userId, $tagName)) {
                    $values[] = "(?, ?, ?, ?)";
                    $params = array_merge($params, [$customerId, $userId, $tagName, $tagColor]);
                }
            }

            if (empty($values)) {
                return ['success' => false, 'message' => 'ลูกค้าทั้งหมดมี Tag นี้อยู่แล้ว'];
            }

            $sql = "INSERT INTO customer_tags (customer_id, user_id, tag_name, tag_color) VALUES " . implode(', ', $values);
            $this->db->execute($sql, $params);
            
            return ['success' => true, 'message' => 'เพิ่ม Tags สำเร็จ'];
        } catch (Exception $e) {
            error_log('TagService::bulkAddTags Error: ' . $e->getMessage());
            return ['success' => false, 'message' => 'เกิดข้อผิดพลาดในการเพิ่ม Tags'];
        }
    }
}
