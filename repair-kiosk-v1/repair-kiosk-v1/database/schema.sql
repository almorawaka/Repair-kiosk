-- =====================================================================
--  REPAIR WORKSHOP SELF-SERVICE KIOSK
--  Full database schema
--
--  Target : MySQL 5.7.8+ / MariaDB 10.2+  (generated columns + JSON)
--  Engine : InnoDB
--  Charset: utf8mb4
--
--  TESTED: imported clean on MariaDB 10.11 with all constraints verified.
--
--  IMPORT VIA phpMyAdmin
--    1. Open phpMyAdmin WITHOUT selecting a database first
--       (click the phpMyAdmin logo / "Server" home so no DB is highlighted)
--    2. Import tab -> Choose File -> schema.sql -> Go
--    This file creates the database itself, so do not pre-create one.
--
--  IMPORT VIA COMMAND LINE
--    mysql -u root -p < schema.sql
--    WAMP: C:\wamp64\bin\mysql\mysql8.x.x\bin\mysql.exe -u root -p < schema.sql
--
--  WARNING: the DROP DATABASE below destroys any existing repair_kiosk data.
--           Comment out lines 25-26 if you are re-importing and want to keep it.
-- =====================================================================

SET NAMES utf8mb4;
SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';
SET FOREIGN_KEY_CHECKS = 1;

DROP DATABASE IF EXISTS repair_kiosk;
CREATE DATABASE repair_kiosk
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;
USE repair_kiosk;

SET FOREIGN_KEY_CHECKS = 1;
SET time_zone = '+05:30';   -- Sri Lanka; change to match app.php


-- =====================================================================
--  1. USERS  (staff: technicians and admins)
-- =====================================================================
CREATE TABLE users (
    id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
    username        VARCHAR(50)  NOT NULL,
    password_hash   VARCHAR(255) NOT NULL,          -- password_hash(), bcrypt
    full_name       VARCHAR(120) NOT NULL,
    email           VARCHAR(150) DEFAULT NULL,
    phone           VARCHAR(30)  DEFAULT NULL,
    role            ENUM('admin','technician') NOT NULL DEFAULT 'technician',
    is_active       TINYINT(1)   NOT NULL DEFAULT 1,
    last_login_at   DATETIME     DEFAULT NULL,
    failed_attempts TINYINT UNSIGNED NOT NULL DEFAULT 0,
    locked_until    DATETIME     DEFAULT NULL,
    created_at      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP
                                 ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_users_username (username),
    UNIQUE KEY uq_users_email    (email),
    KEY idx_users_role_active    (role, is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- =====================================================================
--  2. EQUIPMENT  (the asset register — asset_tag is the scanned barcode)
-- =====================================================================
CREATE TABLE equipment (
    id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
    asset_tag       VARCHAR(64)  NOT NULL,          -- printed barcode value
    serial_number   VARCHAR(120) DEFAULT NULL,
    name            VARCHAR(150) NOT NULL,          -- "Dell Latitude 5420"
    category        VARCHAR(80)  DEFAULT NULL,      -- Laptop / Projector / ...
    brand           VARCHAR(80)  DEFAULT NULL,
    model           VARCHAR(80)  DEFAULT NULL,
    owner_department VARCHAR(120) DEFAULT NULL,
    home_location   VARCHAR(120) DEFAULT NULL,      -- where it lives normally
    purchase_date   DATE         DEFAULT NULL,
    warranty_expiry DATE         DEFAULT NULL,
    condition_notes TEXT         DEFAULT NULL,
    status          ENUM('available','on_repair','retired')
                                 NOT NULL DEFAULT 'available',
    is_active       TINYINT(1)   NOT NULL DEFAULT 1,
    created_at      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP
                                 ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_equipment_asset_tag (asset_tag),
    KEY idx_equipment_serial   (serial_number),
    KEY idx_equipment_status   (status),
    KEY idx_equipment_category (category)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- =====================================================================
--  3. BORROWERS  (borrower_code is their scannable ID card barcode)
-- =====================================================================
CREATE TABLE borrowers (
    id             INT UNSIGNED NOT NULL AUTO_INCREMENT,
    borrower_code  VARCHAR(64)  NOT NULL,           -- student / staff ID
    full_name      VARCHAR(150) NOT NULL,
    email          VARCHAR(150) DEFAULT NULL,
    phone          VARCHAR(30)  DEFAULT NULL,
    department     VARCHAR(120) DEFAULT NULL,
    borrower_type  ENUM('student','staff','external') NOT NULL DEFAULT 'student',
    is_active      TINYINT(1)   NOT NULL DEFAULT 1,
    created_at     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP
                                ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_borrowers_code (borrower_code),
    KEY idx_borrowers_name  (full_name),
    KEY idx_borrowers_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- =====================================================================
--  4. REPAIR JOBS  (the core table)
--
--  active_equipment_lock is a generated column that equals equipment_id
--  while the job is open and NULL once it is closed. The UNIQUE key on it
--  makes it physically impossible to have two open jobs for one asset —
--  MySQL has no partial indexes, so this is the workaround.
-- =====================================================================
CREATE TABLE repair_jobs (
    id                    INT UNSIGNED NOT NULL AUTO_INCREMENT,
    job_number            VARCHAR(24)  NOT NULL,    -- RJ-2026-000123
    public_token          CHAR(32)     NOT NULL,    -- bin2hex(random_bytes(16))
    equipment_id          INT UNSIGNED NOT NULL,
    borrower_id           INT UNSIGNED DEFAULT NULL,

    -- fallback if a walk-in has no borrower record yet
    walkin_name           VARCHAR(150) DEFAULT NULL,
    walkin_contact        VARCHAR(120) DEFAULT NULL,

    fault_description     TEXT         NOT NULL,
    fault_category        VARCHAR(80)  DEFAULT NULL,
    status                ENUM(
                              'awaiting_assessment',
                              'assessing',
                              'awaiting_parts',
                              'in_repair',
                              'ready_for_collection',
                              'collected',
                              'unrepairable',
                              'cancelled'
                          ) NOT NULL DEFAULT 'awaiting_assessment',
    priority              ENUM('low','normal','high','urgent')
                              NOT NULL DEFAULT 'normal',
    assigned_to           INT UNSIGNED DEFAULT NULL,

    estimated_ready_date  DATE         DEFAULT NULL,
    dropped_off_at        DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    assessed_at           DATETIME     DEFAULT NULL,
    ready_at              DATETIME     DEFAULT NULL,
    collected_at          DATETIME     DEFAULT NULL,

    is_under_warranty     TINYINT(1)   NOT NULL DEFAULT 0,
    labour_cost           DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    parts_cost            DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    total_cost            DECIMAL(10,2) AS (labour_cost + parts_cost) STORED,

    dropoff_signature     VARCHAR(255) DEFAULT NULL,  -- storage/ relative path
    collection_signature  VARCHAR(255) DEFAULT NULL,
    collected_by_name     VARCHAR(150) DEFAULT NULL,  -- if proxy collection
    collection_verified_by INT UNSIGNED DEFAULT NULL, -- staff override

    created_at            DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at            DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
                                   ON UPDATE CURRENT_TIMESTAMP,

    active_equipment_lock INT UNSIGNED AS (
        CASE WHEN status IN ('collected','cancelled')
             THEN NULL ELSE equipment_id END
    ) STORED,

    PRIMARY KEY (id),
    UNIQUE KEY uq_jobs_number       (job_number),
    UNIQUE KEY uq_jobs_token        (public_token),
    UNIQUE KEY uq_jobs_active_asset (active_equipment_lock),

    KEY idx_jobs_status       (status),
    KEY idx_jobs_equipment    (equipment_id),
    KEY idx_jobs_borrower     (borrower_id),
    KEY idx_jobs_assigned     (assigned_to),
    KEY idx_jobs_dropped_off  (dropped_off_at),
    KEY idx_jobs_status_date  (status, dropped_off_at),

    -- NOTE: this FK must be ON UPDATE RESTRICT, not CASCADE.
    -- Both MySQL and MariaDB refuse to create a stored generated column
    -- (active_equipment_lock) that references a column carrying
    -- ON UPDATE CASCADE. equipment.id is a surrogate key that never
    -- changes, so RESTRICT costs nothing here.
    CONSTRAINT fk_jobs_equipment FOREIGN KEY (equipment_id)
        REFERENCES equipment (id) ON UPDATE RESTRICT ON DELETE RESTRICT,
    CONSTRAINT fk_jobs_borrower FOREIGN KEY (borrower_id)
        REFERENCES borrowers (id) ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT fk_jobs_assigned FOREIGN KEY (assigned_to)
        REFERENCES users (id) ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT fk_jobs_verifier FOREIGN KEY (collection_verified_by)
        REFERENCES users (id) ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- =====================================================================
--  5. JOB STATUS HISTORY  (the timeline the borrower sees on the QR page)
-- =====================================================================
CREATE TABLE job_status_history (
    id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    job_id          INT UNSIGNED NOT NULL,
    from_status     VARCHAR(40)  DEFAULT NULL,      -- NULL on job creation
    to_status       VARCHAR(40)  NOT NULL,
    source          ENUM('kiosk','staff','system') NOT NULL DEFAULT 'staff',
    changed_by      INT UNSIGNED DEFAULT NULL,      -- NULL when kiosk/system
    note            VARCHAR(500) DEFAULT NULL,
    is_public       TINYINT(1)   NOT NULL DEFAULT 1, -- show on track page?
    created_at      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_history_job  (job_id, created_at),
    CONSTRAINT fk_history_job FOREIGN KEY (job_id)
        REFERENCES repair_jobs (id) ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_history_user FOREIGN KEY (changed_by)
        REFERENCES users (id) ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- =====================================================================
--  6. ACCESSORIES  (lookup) and what came in with each job
-- =====================================================================
CREATE TABLE accessories (
    id         SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT,
    name       VARCHAR(80) NOT NULL,
    sort_order SMALLINT    NOT NULL DEFAULT 0,
    is_active  TINYINT(1)  NOT NULL DEFAULT 1,
    PRIMARY KEY (id),
    UNIQUE KEY uq_accessories_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE job_accessories (
    id             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    job_id         INT UNSIGNED NOT NULL,
    accessory_id   SMALLINT UNSIGNED NOT NULL,
    quantity       SMALLINT UNSIGNED NOT NULL DEFAULT 1,
    received_at_dropoff  TINYINT(1) NOT NULL DEFAULT 1,
    returned_at_collection TINYINT(1) NOT NULL DEFAULT 0,
    remarks        VARCHAR(255) DEFAULT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_job_accessory (job_id, accessory_id),
    KEY idx_job_accessories_job (job_id),
    CONSTRAINT fk_jobacc_job FOREIGN KEY (job_id)
        REFERENCES repair_jobs (id) ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_jobacc_accessory FOREIGN KEY (accessory_id)
        REFERENCES accessories (id) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- =====================================================================
--  7. JOB PHOTOS  (stored outside webroot, served via media.php)
-- =====================================================================
CREATE TABLE job_photos (
    id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    job_id      INT UNSIGNED NOT NULL,
    file_path   VARCHAR(255) NOT NULL,              -- storage/uploads/jobs/12/x.jpg
    thumb_path  VARCHAR(255) DEFAULT NULL,
    phase       ENUM('dropoff','repair','collection') NOT NULL DEFAULT 'dropoff',
    caption     VARCHAR(255) DEFAULT NULL,
    file_size   INT UNSIGNED DEFAULT NULL,
    mime_type   VARCHAR(60)  DEFAULT NULL,
    uploaded_by INT UNSIGNED DEFAULT NULL,          -- NULL = kiosk
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_photos_job (job_id, phase),
    CONSTRAINT fk_photos_job FOREIGN KEY (job_id)
        REFERENCES repair_jobs (id) ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_photos_user FOREIGN KEY (uploaded_by)
        REFERENCES users (id) ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- =====================================================================
--  8. JOB NOTES  (diagnosis, parts used, internal chatter)
--     note_type = 'borrower_visible' is the only kind shown on track page
-- =====================================================================
CREATE TABLE job_notes (
    id         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    job_id     INT UNSIGNED NOT NULL,
    user_id    INT UNSIGNED DEFAULT NULL,
    note_type  ENUM('diagnosis','part_used','internal','borrower_visible')
                            NOT NULL DEFAULT 'internal',
    body       TEXT         NOT NULL,
    part_name  VARCHAR(150) DEFAULT NULL,
    part_qty   SMALLINT UNSIGNED DEFAULT NULL,
    part_cost  DECIMAL(10,2) DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_notes_job  (job_id, created_at),
    KEY idx_notes_type (note_type),
    CONSTRAINT fk_notes_job FOREIGN KEY (job_id)
        REFERENCES repair_jobs (id) ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_notes_user FOREIGN KEY (user_id)
        REFERENCES users (id) ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- =====================================================================
--  9. JOB NUMBER COUNTER
--     Atomic per-year sequence. Do NOT derive job numbers from MAX()+1 —
--     two kiosk drop-offs a second apart will collide.
--     Use: UPDATE job_counters SET last_number = LAST_INSERT_ID(last_number+1)
--          WHERE year_key = YEAR(NOW());  then SELECT LAST_INSERT_ID();
-- =====================================================================
CREATE TABLE job_counters (
    year_key    SMALLINT UNSIGNED NOT NULL,
    last_number INT UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (year_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- =====================================================================
-- 10. AUDIT LOG
-- =====================================================================
CREATE TABLE audit_log (
    id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id     INT UNSIGNED DEFAULT NULL,
    action      VARCHAR(80)  NOT NULL,              -- job.created, user.login
    entity_type VARCHAR(60)  DEFAULT NULL,
    entity_id   INT UNSIGNED DEFAULT NULL,
    ip_address  VARCHAR(45)  DEFAULT NULL,          -- IPv6-safe
    user_agent  VARCHAR(255) DEFAULT NULL,
    details     JSON         DEFAULT NULL,
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_audit_entity (entity_type, entity_id),
    KEY idx_audit_user   (user_id, created_at),
    KEY idx_audit_action (action),
    CONSTRAINT fk_audit_user FOREIGN KEY (user_id)
        REFERENCES users (id) ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- =====================================================================
-- 11. SETTINGS  (workshop name, idle timeout, SLA days, printer text)
-- =====================================================================
CREATE TABLE settings (
    setting_key   VARCHAR(80)  NOT NULL,
    setting_value TEXT         DEFAULT NULL,
    description   VARCHAR(255) DEFAULT NULL,
    updated_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
                           ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (setting_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- =====================================================================
--  VIEWS
-- =====================================================================

-- Everything the staff job list needs, in one read
CREATE OR REPLACE VIEW v_active_jobs AS
SELECT
    j.id,
    j.job_number,
    j.public_token,
    j.status,
    j.priority,
    j.dropped_off_at,
    j.estimated_ready_date,
    TIMESTAMPDIFF(DAY, j.dropped_off_at, NOW())       AS days_open,
    e.asset_tag,
    e.name        AS equipment_name,
    e.category    AS equipment_category,
    COALESCE(b.full_name, j.walkin_name)   AS borrower_name,
    COALESCE(b.borrower_code, '-')         AS borrower_code,
    b.email       AS borrower_email,
    u.full_name   AS technician_name,
    j.total_cost
FROM repair_jobs j
JOIN equipment  e ON e.id = j.equipment_id
LEFT JOIN borrowers b ON b.id = j.borrower_id
LEFT JOIN users     u ON u.id = j.assigned_to
WHERE j.status NOT IN ('collected','cancelled');

-- Turnaround stats for the reports page
CREATE OR REPLACE VIEW v_job_turnaround AS
SELECT
    j.id,
    j.job_number,
    e.category,
    j.status,
    j.dropped_off_at,
    j.ready_at,
    j.collected_at,
    TIMESTAMPDIFF(HOUR, j.dropped_off_at, j.ready_at)    AS hours_to_ready,
    TIMESTAMPDIFF(HOUR, j.ready_at, j.collected_at)      AS hours_on_shelf,
    TIMESTAMPDIFF(HOUR, j.dropped_off_at, j.collected_at) AS hours_total
FROM repair_jobs j
JOIN equipment e ON e.id = j.equipment_id
WHERE j.collected_at IS NOT NULL;


-- =====================================================================
--  SEED DATA
-- =====================================================================

-- Default admin — username: admin   password: Admin@123
-- CHANGE THIS IMMEDIATELY AFTER FIRST LOGIN.
INSERT INTO users (username, password_hash, full_name, email, role) VALUES
('admin',
 '$2y$10$NY5rIN.BywGdULQRJaTUNOjkjP48J6XojeR/UslkrqOTg6zskc26S',
 'System Administrator', 'admin@workshop.local', 'admin');

INSERT INTO accessories (name, sort_order) VALUES
('Power adapter / charger', 10),
('Power cable',             20),
('Carry bag / case',        30),
('Mouse',                   40),
('Remote control',          50),
('HDMI / VGA cable',        60),
('Battery',                 70),
('Stylus / pen',            80),
('Manual / documentation',  90),
('Other',                  100);

INSERT INTO settings (setting_key, setting_value, description) VALUES
('workshop_name',      'Central Repair Workshop', 'Shown on kiosk and slips'),
('kiosk_idle_seconds', '90',   'Auto-reset kiosk to home after inactivity'),
('default_sla_days',   '5',    'Default estimated ready date offset'),
('slip_footer_text',   'Keep this slip. It is required for collection.', 'Printed on drop-off slip'),
('track_base_url',     'https://repair.example.lk/track', 'QR code target prefix'),
('allow_proxy_collection', '1', 'Allow someone else to collect with the slip');

INSERT INTO job_counters (year_key, last_number) VALUES (YEAR(NOW()), 0);

-- Sample equipment for testing the scanner
INSERT INTO equipment (asset_tag, serial_number, name, category, brand, model, owner_department, status) VALUES
('AST-000001', 'SN-DL-88213', 'Dell Latitude 5420',     'Laptop',    'Dell',    'Latitude 5420', 'IT Department',  'available'),
('AST-000002', 'SN-EP-44120', 'Epson EB-X51 Projector', 'Projector', 'Epson',   'EB-X51',        'Media Unit',     'available'),
('AST-000003', 'SN-HP-77510', 'HP LaserJet M404dn',     'Printer',   'HP',      'M404dn',        'Admin Office',   'available'),
('AST-000004', 'SN-LN-31009', 'Lenovo ThinkPad T14',    'Laptop',    'Lenovo',  'T14 Gen2',      'Engineering',    'available'),
('AST-000005', 'SN-SM-90233', 'Samsung 24" Monitor',    'Monitor',   'Samsung', 'S24R350',       'Library',        'available');

INSERT INTO borrowers (borrower_code, full_name, email, phone, department, borrower_type) VALUES
('STU-2023-1145', 'Nimal Perera',     'nimal@example.lk',  '0771234567', 'Engineering',   'student'),
('STF-000217',    'Kumari Silva',     'kumari@example.lk', '0719876543', 'Admin Office',  'staff'),
('STU-2024-0088', 'Ahmed Raza',       'ahmed@example.lk',  '0761122334', 'Media Unit',    'student');


-- =====================================================================
--  DONE
--
--  Next: create a least-privilege app user rather than using root.
--
--  CREATE USER 'kiosk_app'@'localhost' IDENTIFIED BY 'change-me';
--  GRANT SELECT, INSERT, UPDATE, DELETE ON repair_kiosk.*
--        TO 'kiosk_app'@'localhost';
--  FLUSH PRIVILEGES;
-- =====================================================================
