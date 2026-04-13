-- =============================================================
--  ClinicEase — MySQL Database Schema

-- =============================================================

SET FOREIGN_KEY_CHECKS = 0;

-- -------------------------------------------------------------
-- 1. USERS  (core account table — all roles share this)
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `users` (
  `user_id`           INT      NOT NULL AUTO_INCREMENT,
  `username`          VARCHAR(60)       NOT NULL,
  `email`             VARCHAR(180)      NOT NULL,
  `password_hash`     VARCHAR(255)      NOT NULL COMMENT 'bcrypt/argon2 hash',
  `role`              ENUM('patient','doctor','admin','nurse') NOT NULL,
  `security_question` VARCHAR(255)      DEFAULT NULL,
  `security_answer`   VARCHAR(255)      DEFAULT NULL COMMENT 'hashed answer',
  `account_status`    ENUM('pending','active','suspended','rejected') NOT NULL DEFAULT 'pending',
  `agreed_terms`      TINYINT(1)        NOT NULL DEFAULT 0,
  `email_verified_at` DATETIME          DEFAULT NULL,
  `last_login_at`     DATETIME          DEFAULT NULL,
  `created_at`        DATETIME          NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`        DATETIME          NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  PRIMARY KEY (`user_id`),
  UNIQUE KEY `uq_username` (`username`),
  UNIQUE KEY `uq_email`    (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Core account credentials for every role';


-- -------------------------------------------------------------
-- 2. PERSONAL INFO  (shared by all roles)
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `user_profiles` (
  `profile_id`    INT NOT NULL AUTO_INCREMENT,
  `user_id`       INT        NOT NULL,
  `first_name`    VARCHAR(80)   NOT NULL,
  `last_name`     VARCHAR(80)   NOT NULL,
  `date_of_birth` DATE          NOT NULL,
  `gender`        ENUM('Male','Female','Non-binary','Prefer not to say') NOT NULL,
  `phone`         VARCHAR(30)   NOT NULL,
  `nationality`   VARCHAR(80)   DEFAULT NULL,
  `address`       VARCHAR(500)  NOT NULL,
  `created_at`    DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`    DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  PRIMARY KEY (`profile_id`),
  UNIQUE KEY `uq_user_profile` (`user_id`),
  CONSTRAINT `fk_profile_user`
    FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Basic personal information, common to all roles';


-- -------------------------------------------------------------
-- 3. PATIENT INFO
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `patients` (
  `patient_id`         INT NOT NULL AUTO_INCREMENT,
  `user_id`            INT NOT NULL,

  -- Medical
  `blood_type`         ENUM('A+','A-','B+','B-','AB+','AB-','O+','O-') DEFAULT NULL,
  `civil_status`       ENUM('Single','Married','Widowed','Separated')   DEFAULT NULL,
  `height_cm`          DECIMAL(5,1)  DEFAULT NULL,
  `weight_kg`          DECIMAL(5,1)  DEFAULT NULL,
  `allergies`          TEXT          DEFAULT NULL,
  `medical_conditions` TEXT          DEFAULT NULL,

  -- Emergency contact
  `emergency_name`     VARCHAR(160)  DEFAULT NULL,
  `emergency_relation` ENUM('Parent','Spouse','Sibling','Child','Relative','Friend') DEFAULT NULL,
  `emergency_phone`    VARCHAR(30)   DEFAULT NULL,

  -- Insurance
  `insurance_number`   VARCHAR(80)   DEFAULT NULL,
  `insurance_provider` VARCHAR(120)  DEFAULT NULL,

  `created_at`         DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`         DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  PRIMARY KEY (`patient_id`),
  UNIQUE KEY `uq_patient_user` (`user_id`),
  CONSTRAINT `fk_patient_user`
    FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Patient-specific medical and insurance information';


-- -------------------------------------------------------------
-- 4. DOCTOR INFO
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `doctors` (
  `doctor_id`        INT NOT NULL AUTO_INCREMENT,
  `user_id`          INT NOT NULL,

  -- License
  `prc_license_no`   VARCHAR(40)   NOT NULL,
  `license_expiry`   DATE          NOT NULL,

  -- Specialization
  `specialization`   ENUM(
      'General Medicine','Pediatrics','Cardiology','Dermatology',
      'Orthopedics','Neurology','OB-GYN','Psychiatry','Surgery',
      'ENT','Ophthalmology','Radiology','Other'
  ) NOT NULL,
  `fellowship`       VARCHAR(160)  DEFAULT NULL COMMENT 'Sub-specialty or fellowship',

  -- Background
  `medical_school`   VARCHAR(200)  NOT NULL,
  `years_experience` TINYINT UNSIGNED DEFAULT NULL,
  `affiliation`      VARCHAR(200)  DEFAULT NULL COMMENT 'Clinic or hospital affiliation',
  `bio`              TEXT          DEFAULT NULL,

  -- Schedule
  `consult_days`     VARCHAR(100)  DEFAULT NULL COMMENT 'e.g. Mon, Wed, Fri',
  `consult_hours`    VARCHAR(80)   DEFAULT NULL COMMENT 'e.g. 9:00 AM – 5:00 PM',

  `created_at`       DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`       DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  PRIMARY KEY (`doctor_id`),
  UNIQUE KEY `uq_doctor_user`    (`user_id`),
  UNIQUE KEY `uq_prc_license`    (`prc_license_no`),
  CONSTRAINT `fk_doctor_user`
    FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Doctor professional credentials and schedule';


-- -------------------------------------------------------------
-- 5. ADMIN INFO
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `admins` (
  `admin_id`       INT NOT NULL AUTO_INCREMENT,
  `user_id`        INT NOT NULL,

  `employee_id`    VARCHAR(40)   NOT NULL,
  `department`     ENUM(
      'General Administration','HR & Payroll','Finance',
      'Medical Records','IT & Systems','Billing & Insurance','Operations'
  ) NOT NULL,
  `job_title`      VARCHAR(100)  NOT NULL,
  `access_level`   TINYINT UNSIGNED NOT NULL DEFAULT 1
                   COMMENT '1=View Only, 2=Edit Records, 3=Full Admin, 4=Super Admin',
  `hire_date`      DATE          DEFAULT NULL,
  `supervisor`     VARCHAR(120)  DEFAULT NULL,
  `auth_code_hash` VARCHAR(255)  DEFAULT NULL COMMENT 'Hashed authorization code',

  `created_at`     DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`     DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  PRIMARY KEY (`admin_id`),
  UNIQUE KEY `uq_admin_user`     (`user_id`),
  UNIQUE KEY `uq_admin_emp_id`   (`employee_id`),
  CONSTRAINT `fk_admin_user`
    FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Administrator staff and access-level information';


-- -------------------------------------------------------------
-- 6. NURSE INFO
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `nurses` (
  `nurse_id`          INT NOT NULL AUTO_INCREMENT,
  `user_id`           INT NOT NULL,

  -- License
  `prc_license_no`    VARCHAR(40)   NOT NULL,
  `license_expiry`    DATE          NOT NULL,

  -- Assignment
  `years_experience`  TINYINT UNSIGNED DEFAULT NULL,
  `shift_preference`  ENUM(
      'Morning (6AM–2PM)','Afternoon (2PM–10PM)',
      'Night (10PM–6AM)','Rotating'
  ) DEFAULT NULL,
  `ward_department`   ENUM(
      'Emergency','ICU/CCU','Pediatrics','OB-GYN',
      'Surgery','Oncology','General Ward','Out-Patient'
  ) DEFAULT NULL,
  `employee_id`       VARCHAR(40)   DEFAULT NULL,
  `supervisor`        VARCHAR(120)  DEFAULT NULL,
  `certifications`    VARCHAR(255)  DEFAULT NULL COMMENT 'e.g. BLS, ACLS, PALS',

  `created_at`        DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`        DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  PRIMARY KEY (`nurse_id`),
  UNIQUE KEY `uq_nurse_user`        (`user_id`),
  UNIQUE KEY `uq_nurse_prc_license` (`prc_license_no`),
  CONSTRAINT `fk_nurse_user`
    FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Nurse credentials, shift, and ward assignment';

------------------------------------------------
-- Additional table for Patient health Matrix records
------------------------------------------------
ALTER TABLE `patients`
ADD COLUMN `health_matrix` JSON 
DEFAULT NULL 
COMMENT 'Stores health metrics like blood pressure, heart rate, etc.';

---------------
-APPOINTMENTS TABLE
---------------

-- -------------------------------------------------------------
-- 7. AUDIT LOG  (optional but recommended)
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `registration_audit` (
  `audit_id`    INT NOT NULL AUTO_INCREMENT,
  `user_id`     INT NOT NULL,
  `action`      VARCHAR(80)  NOT NULL COMMENT 'e.g. registered, approved, rejected',
  `performed_by` INT DEFAULT NULL COMMENT 'admin user_id, NULL = self',
  `notes`       TEXT         DEFAULT NULL,
  `created_at`  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,

  PRIMARY KEY (`audit_id`),
  KEY `idx_audit_user` (`user_id`),
  CONSTRAINT `fk_audit_user`
    FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Tracks registration actions and admin approvals';


SET FOREIGN_KEY_CHECKS = 1;

-- =============================================================
--  Useful views
-- =============================================================

-- Full patient overview
CREATE OR REPLACE VIEW `v_patients` AS
SELECT
  u.user_id, u.username, u.email, u.account_status,
  p.first_name, p.last_name, p.date_of_birth, p.gender, p.phone, p.address,
  pt.blood_type, pt.civil_status, pt.height_cm, pt.weight_kg,
  pt.allergies, pt.medical_conditions,
  pt.emergency_name, pt.emergency_relation, pt.emergency_phone,
  pt.insurance_number, pt.insurance_provider
FROM users u
JOIN user_profiles p  ON p.user_id  = u.user_id
JOIN patients pt      ON pt.user_id = u.user_id
WHERE u.role = 'patient';

-- Full doctor overview
CREATE OR REPLACE VIEW `v_doctors` AS
SELECT
  u.user_id, u.username, u.email, u.account_status,
  p.first_name, p.last_name, p.phone,
  d.prc_license_no, d.license_expiry, d.specialization,
  d.fellowship, d.medical_school, d.years_experience,
  d.consult_days, d.consult_hours, d.affiliation, d.bio
FROM users u
JOIN user_profiles p ON p.user_id = u.user_id
JOIN doctors d       ON d.user_id = u.user_id
WHERE u.role = 'doctor';

-- Full nurse overview
CREATE OR REPLACE VIEW `v_nurses` AS
SELECT
  u.user_id, u.username, u.email, u.account_status,
  p.first_name, p.last_name, p.phone,
  n.prc_license_no, n.license_expiry, n.shift_preference,
  n.ward_department, n.years_experience, n.certifications, n.supervisor
FROM users u
JOIN user_profiles p ON p.user_id = u.user_id
JOIN nurses n        ON n.user_id = u.user_id
WHERE u.role = 'nurse';

-- Full admin overview
CREATE OR REPLACE VIEW `v_admins` AS
SELECT
  u.user_id, u.username, u.email, u.account_status,
  p.first_name, p.last_name, p.phone,
  a.employee_id, a.department, a.job_title, a.access_level,
  a.hire_date, a.supervisor
FROM users u
JOIN user_profiles p ON p.user_id = u.user_id
JOIN admins a        ON a.user_id = u.user_id
WHERE u.role = 'admin';

-- =============================================================
--  END OF SCHEMA
-- =============================================================