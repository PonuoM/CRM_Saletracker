-- Migration: Add display columns for call_logs to preserve UI-selected labels
-- Date: 2025-09-12

ALTER TABLE `call_logs`
  ADD COLUMN `status_display` VARCHAR(100) DEFAULT NULL AFTER `followup_priority`,
  ADD COLUMN `result_display` VARCHAR(100) DEFAULT NULL AFTER `status_display`;

-- Backfill display labels for existing rows (best-effort)
UPDATE `call_logs`
SET `status_display` = CASE `call_status`
    WHEN 'answered' THEN 'รับสาย'
    WHEN 'no_answer' THEN 'ไม่รับสาย'
    WHEN 'busy' THEN 'สายไม่ว่าง'
    WHEN 'invalid' THEN 'เบอร์ไม่ถูกต้อง'
    ELSE `status_display`
END
WHERE `status_display` IS NULL;

UPDATE `call_logs`
SET `result_display` = CASE `call_result`
    WHEN 'interested' THEN 'สนใจ'
    WHEN 'not_interested' THEN 'ไม่สนใจ'
    WHEN 'callback' THEN 'ติดต่อนัด/โทรกลับ'
    WHEN 'order' THEN 'สั่งซื้อ'
    WHEN 'complaint' THEN 'ร้องเรียน'
    ELSE `result_display`
END
WHERE `result_display` IS NULL;
