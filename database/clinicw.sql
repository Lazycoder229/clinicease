-- =============================================================
--  ClinicEase — MySQL Database Schema

-- =============================================================
CREATE DATABASE clinic;
USE clinic;
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


ALTER TABLE `patients`
ADD COLUMN `health_matrix` JSON 
DEFAULT NULL 
COMMENT 'Stores health metrics like blood pressure, heart rate, etc.';

-- =============================================================
-- 7. AUDIT LOG  (optional but recommended)
-- =============================================================
-- 
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
--  ClinicEase — Appointments Module
--  Aligned with: users, user_profiles, patients, doctors schema
-- =============================================================

USE clinic;

-- -------------------------------------------------------------
-- 7. APPOINTMENTS
--    patient_id → patients.patient_id
--    doctor_id  → doctors.doctor_id
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `appointments` (
  `appointment_id`    INT          NOT NULL AUTO_INCREMENT,
  `patient_id`        INT          NOT NULL COMMENT 'FK → patients.patient_id',
  `doctor_id`         INT          NOT NULL COMMENT 'FK → doctors.doctor_id',

  `type`              ENUM(
      'General Check-up','Dental Cleaning','Eye Examination',
      'Vaccination','Consultation','Follow-up','Laboratory','Other'
  ) NOT NULL,

  `appointment_date`  DATE         NOT NULL,
  `appointment_time`  TIME         NOT NULL,

  `status`            ENUM(
      'Pending','Confirmed','Scheduled','Completed','Cancelled','No-show'
  ) NOT NULL DEFAULT 'Pending',

  `notes`             TEXT         DEFAULT NULL COMMENT 'Patient notes at booking',
  `doctor_notes`      TEXT         DEFAULT NULL COMMENT 'Doctor remarks post-consult',

  `created_at`        DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`        DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP
                                   ON UPDATE CURRENT_TIMESTAMP,

  PRIMARY KEY (`appointment_id`),

  CONSTRAINT `fk_appt_patient`
    FOREIGN KEY (`patient_id`)
    REFERENCES `patients` (`patient_id`)
    ON DELETE CASCADE ON UPDATE CASCADE,

  CONSTRAINT `fk_appt_doctor`
    FOREIGN KEY (`doctor_id`)
    REFERENCES `doctors` (`doctor_id`)
    ON DELETE RESTRICT ON UPDATE CASCADE,

  INDEX `idx_appt_patient` (`patient_id`),
  INDEX `idx_appt_doctor`  (`doctor_id`),
  INDEX `idx_appt_date`    (`appointment_date`),
  INDEX `idx_appt_status`  (`status`)

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Patient appointments linked to patients and doctors tables';


-- =============================================================
--  ClinicEase — Health Records Module
--  Aligned with: users, user_profiles, patients, doctors schema
-- =============================================================

USE clinic;
CREATE TABLE IF NOT EXISTS `health_records` (
  `record_id`          INT          NOT NULL AUTO_INCREMENT,
  `patient_id`         INT          NOT NULL COMMENT 'FK → patients.patient_id',
  `doctor_id`          INT          NOT NULL COMMENT 'FK → doctors.doctor_id',
  `appointment_id`     INT          DEFAULT NULL COMMENT 'FK → appointments.appointment_id (optional link)',

  -- Visit details
  `record_date`        DATE         NOT NULL,
  `visit_type`         ENUM(
      'General Check-up','Follow-up','Emergency','Vaccination',
      'Laboratory','Consultation','Procedure','Dental','Eye Exam','Other'
  ) NOT NULL DEFAULT 'General Check-up',

  -- Clinical findings
  `chief_complaint`    TEXT         DEFAULT NULL COMMENT 'Patient reported symptoms',
  `diagnosis`          TEXT         DEFAULT NULL,
  `treatment`          TEXT         DEFAULT NULL,
  `prescription`       TEXT         DEFAULT NULL,
  `doctor_notes`       TEXT         DEFAULT NULL,

  -- Vital signs (snapshot at time of visit)
  `blood_pressure`     VARCHAR(20)  DEFAULT NULL COMMENT 'e.g. 120/80',
  `heart_rate`         TINYINT UNSIGNED DEFAULT NULL COMMENT 'bpm',
  `temperature`        DECIMAL(4,1) DEFAULT NULL COMMENT 'Celsius',
  `oxygen_saturation`  TINYINT UNSIGNED DEFAULT NULL COMMENT 'SpO2 %',
  `weight_kg`          DECIMAL(5,1) DEFAULT NULL,
  `height_cm`          DECIMAL(5,1) DEFAULT NULL,
  `bmi`                DECIMAL(4,1) DEFAULT NULL COMMENT 'Computed or stored',

  -- Lab / attachments
  `lab_results`        TEXT         DEFAULT NULL COMMENT 'Summary or JSON of lab values',
  `attachments`        TEXT         DEFAULT NULL COMMENT 'Comma-separated file paths',

  -- Status
  `status`             ENUM('Draft','Final','Archived') NOT NULL DEFAULT 'Final',

  `created_at`         DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`         DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP
                                    ON UPDATE CURRENT_TIMESTAMP,

  PRIMARY KEY (`record_id`),

  CONSTRAINT `fk_hr_patient`
    FOREIGN KEY (`patient_id`)
    REFERENCES `patients` (`patient_id`)
    ON DELETE CASCADE ON UPDATE CASCADE,

  CONSTRAINT `fk_hr_doctor`
    FOREIGN KEY (`doctor_id`)
    REFERENCES `doctors` (`doctor_id`)
    ON DELETE RESTRICT ON UPDATE CASCADE,

  CONSTRAINT `fk_hr_appointment`
    FOREIGN KEY (`appointment_id`)
    REFERENCES `appointments` (`appointment_id`)
    ON DELETE SET NULL ON UPDATE CASCADE,

  INDEX `idx_hr_patient`     (`patient_id`),
  INDEX `idx_hr_doctor`      (`doctor_id`),
  INDEX `idx_hr_date`        (`record_date`),
  INDEX `idx_hr_visit_type`  (`visit_type`)

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Clinical visit records with vitals, diagnosis, and treatment';


-- -------------------------------------------------------------
-- 9. HEALTH METRICS LOG
--    Time-series vitals (from health_matrix JSON in patients table
--    but normalized here for querying/charting)
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `health_metrics` (
  `metric_id`           INT          NOT NULL AUTO_INCREMENT,
  `patient_id`          INT          NOT NULL COMMENT 'FK → patients.patient_id',
  `record_id`           INT          DEFAULT NULL COMMENT 'Linked visit record (optional)',

  `recorded_at`         DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `recorded_by`         INT          DEFAULT NULL COMMENT 'FK → users.user_id (nurse/doctor)',

  -- Vitals
  `blood_pressure_sys`  SMALLINT UNSIGNED DEFAULT NULL COMMENT 'Systolic mmHg',
  `blood_pressure_dia`  SMALLINT UNSIGNED DEFAULT NULL COMMENT 'Diastolic mmHg',
  `heart_rate`          TINYINT UNSIGNED  DEFAULT NULL COMMENT 'bpm',
  `temperature`         DECIMAL(4,1)      DEFAULT NULL COMMENT 'Celsius',
  `oxygen_saturation`   TINYINT UNSIGNED  DEFAULT NULL COMMENT 'SpO2 %',
  `blood_sugar`         DECIMAL(5,1)      DEFAULT NULL COMMENT 'mg/dL',
  `weight_kg`           DECIMAL(5,1)      DEFAULT NULL,
  `height_cm`           DECIMAL(5,1)      DEFAULT NULL,
  `bmi`                 DECIMAL(4,1)      DEFAULT NULL,

  `notes`               TEXT         DEFAULT NULL,

  PRIMARY KEY (`metric_id`),

  CONSTRAINT `fk_hm_patient`
    FOREIGN KEY (`patient_id`)
    REFERENCES `patients` (`patient_id`)
    ON DELETE CASCADE ON UPDATE CASCADE,

  CONSTRAINT `fk_hm_record`
    FOREIGN KEY (`record_id`)
    REFERENCES `health_records` (`record_id`)
    ON DELETE SET NULL ON UPDATE CASCADE,

  INDEX `idx_hm_patient`  (`patient_id`),
  INDEX `idx_hm_recorded` (`recorded_at`)

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Time-series vitals log per patient for charting and tracking';


-- =============================================================
--  SEED DATA
-- =============================================================


-- =============================================================
--  ClinicEase — Prescriptions Module
--  Aligned with: users, user_profiles, patients, doctors,
--                health_records, appointments schema
-- =============================================================


-- -------------------------------------------------------------
-- 10. PRESCRIPTIONS
--     One row per prescribed medication per visit.
--     Linked to the health_record and optionally the appointment.
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `prescriptions` (
  `prescription_id`   INT          NOT NULL AUTO_INCREMENT,
  `patient_id`        INT          NOT NULL COMMENT 'FK → patients.patient_id',
  `doctor_id`         INT          NOT NULL COMMENT 'FK → doctors.doctor_id',
  `record_id`         INT          DEFAULT NULL COMMENT 'FK → health_records.record_id',
  `appointment_id`    INT          DEFAULT NULL COMMENT 'FK → appointments.appointment_id',

  -- Drug details
  `medication_name`   VARCHAR(200) NOT NULL,
  `generic_name`      VARCHAR(200) DEFAULT NULL,
  `dosage`            VARCHAR(80)  NOT NULL   COMMENT 'e.g. 500mg, 5mL',
  `form`              ENUM(
      'Tablet','Capsule','Syrup','Drops','Injection',
      'Inhaler','Patch','Cream','Ointment','Other'
  ) NOT NULL DEFAULT 'Tablet',
  `frequency`         VARCHAR(100) NOT NULL   COMMENT 'e.g. Twice daily, Every 8 hours',
  `duration_days`     TINYINT UNSIGNED DEFAULT NULL COMMENT 'How many days to take',
  `quantity`          SMALLINT UNSIGNED DEFAULT NULL COMMENT 'Total units dispensed',
  `route`             ENUM(
      'Oral','Topical','Intravenous','Intramuscular',
      'Subcutaneous','Inhalation','Sublingual','Other'
  ) NOT NULL DEFAULT 'Oral',

  -- Instructions
  `instructions`      TEXT         DEFAULT NULL COMMENT 'Special instructions / warnings',
  `indication`        VARCHAR(255) DEFAULT NULL COMMENT 'Why it was prescribed',

  -- Dates
  `prescribed_date`   DATE         NOT NULL,
  `expiry_date`       DATE         DEFAULT NULL COMMENT 'Prescription valid until',
  `refills_allowed`   TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `refills_used`      TINYINT UNSIGNED NOT NULL DEFAULT 0,

  -- Status
  `status`            ENUM('Active','Completed','Discontinued','Expired') NOT NULL DEFAULT 'Active',

  `created_at`        DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`        DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP
                                   ON UPDATE CURRENT_TIMESTAMP,

  PRIMARY KEY (`prescription_id`),

  CONSTRAINT `fk_rx_patient`
    FOREIGN KEY (`patient_id`)     REFERENCES `patients`      (`patient_id`)
    ON DELETE CASCADE  ON UPDATE CASCADE,

  CONSTRAINT `fk_rx_doctor`
    FOREIGN KEY (`doctor_id`)      REFERENCES `doctors`       (`doctor_id`)
    ON DELETE RESTRICT ON UPDATE CASCADE,

  CONSTRAINT `fk_rx_record`
    FOREIGN KEY (`record_id`)      REFERENCES `health_records` (`record_id`)
    ON DELETE SET NULL ON UPDATE CASCADE,

  CONSTRAINT `fk_rx_appointment`
    FOREIGN KEY (`appointment_id`) REFERENCES `appointments`  (`appointment_id`)
    ON DELETE SET NULL ON UPDATE CASCADE,

  INDEX `idx_rx_patient`        (`patient_id`),
  INDEX `idx_rx_doctor`         (`doctor_id`),
  INDEX `idx_rx_status`         (`status`),
  INDEX `idx_rx_prescribed_date`(`prescribed_date`)

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Patient prescriptions linked to records and doctors';


-- =============================================================
-- 11. MESSAGES
--     Secure messaging system between users
-- =============================================================
CREATE TABLE IF NOT EXISTS `messages` (
  `message_id`      INT          NOT NULL AUTO_INCREMENT,
  `sender_id`       INT          NOT NULL COMMENT 'FK → users.user_id',
  `recipient_id`    INT          NOT NULL COMMENT 'FK → users.user_id',
  `conversation_id` INT          DEFAULT NULL COMMENT 'Groups related messages',

  -- Message content
  `subject`         VARCHAR(255) DEFAULT NULL,
  `content`         TEXT         NOT NULL,
  `message_type`    ENUM(
      'Appointment','Prescription','Lab Result','General Inquiry',
      'Follow-up','Urgent','System Notification','Other'
  ) NOT NULL DEFAULT 'General Inquiry',

  -- Related entities
  `appointment_id`  INT          DEFAULT NULL COMMENT 'FK → appointments.appointment_id',
  `prescription_id` INT          DEFAULT NULL COMMENT 'FK → prescriptions.prescription_id',
  `record_id`       INT          DEFAULT NULL COMMENT 'FK → health_records.record_id',

  -- Status tracking
  `is_read`         TINYINT(1)   NOT NULL DEFAULT 0,
  `read_at`         DATETIME     DEFAULT NULL,
  `is_archived`     TINYINT(1)   NOT NULL DEFAULT 0,
  `archived_at`     DATETIME     DEFAULT NULL,

  -- Timestamps
  `created_at`      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  PRIMARY KEY (`message_id`),

  CONSTRAINT `fk_msg_sender`
    FOREIGN KEY (`sender_id`)
    REFERENCES `users` (`user_id`)
    ON DELETE CASCADE ON UPDATE CASCADE,

  CONSTRAINT `fk_msg_recipient`
    FOREIGN KEY (`recipient_id`)
    REFERENCES `users` (`user_id`)
    ON DELETE CASCADE ON UPDATE CASCADE,

  CONSTRAINT `fk_msg_appointment`
    FOREIGN KEY (`appointment_id`)
    REFERENCES `appointments` (`appointment_id`)
    ON DELETE SET NULL ON UPDATE CASCADE,

  CONSTRAINT `fk_msg_prescription`
    FOREIGN KEY (`prescription_id`)
    REFERENCES `prescriptions` (`prescription_id`)
    ON DELETE SET NULL ON UPDATE CASCADE,

  CONSTRAINT `fk_msg_record`
    FOREIGN KEY (`record_id`)
    REFERENCES `health_records` (`record_id`)
    ON DELETE SET NULL ON UPDATE CASCADE,

  INDEX `idx_msg_recipient` (`recipient_id`),
  INDEX `idx_msg_sender` (`sender_id`),
  INDEX `idx_msg_conversation` (`conversation_id`),
  INDEX `idx_msg_created` (`created_at`),
  INDEX `idx_msg_read` (`is_read`)

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Secure messaging between clinic users';


-- =============================================================
--  END OF SCHEMA
-- =============================================================