-- Migration script to add role column to existing users table
USE medifinder;

-- Check and add role column (for MySQL 5.7+)
-- If the column exists, this will show a warning but won't fail
SET @dbname = DATABASE();
SET @tablename = "users";
SET @columnname = "role";
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE
      (TABLE_SCHEMA = @dbname)
      AND (TABLE_NAME = @tablename)
      AND (COLUMN_NAME = @columnname)
  ) > 0,
  "SELECT 1",
  CONCAT("ALTER TABLE ", @tablename, " ADD COLUMN ", @columnname, " ENUM('patient', 'pharmacy_owner', 'admin') DEFAULT 'patient' AFTER password_hash")
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- Alternative simpler approach (will show error if column exists, but can be ignored)
-- ALTER TABLE users ADD COLUMN role ENUM('patient', 'pharmacy_owner', 'admin') DEFAULT 'patient' AFTER password_hash;
