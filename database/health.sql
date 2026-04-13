-- =============================================================
--  ClinicEase — Health Records Module
--  Aligned with: users, user_profiles, patients, doctors schema
-- =============================================================

USE clinicease;

-- -------------------------------------------------------------
-- 8. HEALTH RECORDS
--    One record per clinical visit / consultation
-- -------------------------------------------------------------
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

-- Sample health records for juan_patient
INSERT INTO `health_records`
  (patient_id, doctor_id, appointment_id, record_date, visit_type,
   chief_complaint, diagnosis, treatment, prescription,
   blood_pressure, heart_rate, temperature, oxygen_saturation,
   weight_kg, height_cm, bmi, status)
SELECT
  (SELECT patient_id FROM patients WHERE user_id = (SELECT user_id FROM users WHERE username = 'juan_patient')),
  (SELECT doctor_id  FROM doctors  WHERE user_id = (SELECT user_id FROM users WHERE username = d.doc)),
  NULL,
  d.rdate, d.vtype, d.complaint, d.diagnosis, d.treatment, d.prescription,
  d.bp, d.hr, d.temp, d.spo2, d.wt, d.ht, d.bmi, 'Final'
FROM (
  SELECT 'dr_santos' AS doc, '2026-01-15' AS rdate, 'General Check-up' AS vtype,
         'Annual physical examination'                         AS complaint,
         'Hypertension Stage 1'                                AS diagnosis,
         'Lifestyle modification, reduced sodium diet'         AS treatment,
         'Amlodipine 5mg once daily'                           AS prescription,
         '130/85' AS bp, 78 AS hr, 36.6 AS temp, 98 AS spo2, 72.5 AS wt, 170.0 AS ht, 25.1 AS bmi
  UNION ALL
  SELECT 'dr_reyes',  '2026-01-05', 'Eye Exam',
         'Blurry vision, left eye',
         'Myopia OS -1.50D',
         'Prescription eyeglasses recommended',
         NULL,
         '120/80', 72, 36.5, 99, 72.0, 170.0, 24.9
  UNION ALL
  SELECT 'dr_santos', '2025-12-10', 'Vaccination',
         'Routine flu vaccination',
         'Healthy — vaccination administered',
         'Influenza vaccine administered (0.5mL IM)',
         NULL,
         '118/76', 70, 36.4, 99, 71.8, 170.0, 24.8
  UNION ALL
  SELECT 'dr_santos', '2025-11-20', 'Follow-up',
         'Persistent mild headache',
         'Tension headache, likely stress-related',
         'Rest, hydration, stress management',
         'Ibuprofen 400mg PRN',
         '122/80', 74, 36.7, 98, 72.0, 170.0, 24.9
) d;

-- Sample health metrics log
INSERT INTO `health_metrics`
  (patient_id, recorded_at, blood_pressure_sys, blood_pressure_dia,
   heart_rate, temperature, oxygen_saturation, blood_sugar, weight_kg, height_cm, bmi)
SELECT
  (SELECT patient_id FROM patients WHERE user_id = (SELECT user_id FROM users WHERE username = 'juan_patient')),
  m.rdate, m.sys, m.dia, m.hr, m.temp, m.spo2, m.sugar, m.wt, 170.0,
  ROUND(m.wt / (1.70 * 1.70), 1)
FROM (
  SELECT '2026-02-18 09:00:00' AS rdate, 120 AS sys, 80 AS dia, 76 AS hr, 36.6 AS temp, 98 AS spo2, 98.0 AS sugar, 71.5 AS wt
  UNION ALL SELECT '2026-01-15 09:30:00', 130, 85, 78, 36.6, 98, 102.0, 72.5
  UNION ALL SELECT '2025-12-10 10:00:00', 118, 76, 70, 36.4, 99,  95.0, 71.8
  UNION ALL SELECT '2025-11-20 14:00:00', 122, 80, 74, 36.7, 98,  97.0, 72.0
  UNION ALL SELECT '2025-10-05 11:00:00', 125, 82, 77, 36.5, 97, 100.0, 72.3
) m;


-- =============================================================
--  USEFUL QUERIES
-- =============================================================

-- 1. Full health record list for a patient (newest first)
SELECT
  hr.record_id,
  hr.record_date,
  hr.visit_type,
  hr.chief_complaint,
  hr.diagnosis,
  hr.treatment,
  hr.prescription,
  hr.blood_pressure,
  hr.heart_rate,
  hr.temperature,
  hr.oxygen_saturation,
  hr.weight_kg,
  hr.bmi,
  hr.status,
  CONCAT(dp.first_name, ' ', dp.last_name) AS doctor_name,
  d.specialization
FROM  health_records hr
JOIN  doctors        d  ON hr.doctor_id  = d.doctor_id
JOIN  user_profiles  dp ON d.user_id     = dp.user_id
JOIN  patients       p  ON hr.patient_id = p.patient_id
WHERE p.user_id = :user_id
ORDER BY hr.record_date DESC;


-- 2. Latest vitals for a patient (most recent metric row)
SELECT *
FROM  health_metrics hm
JOIN  patients       p ON hm.patient_id = p.patient_id
WHERE p.user_id = :user_id
ORDER BY hm.recorded_at DESC
LIMIT 1;


-- 3. Vitals trend for charting (last 6 months)
SELECT
  DATE(recorded_at)      AS date,
  blood_pressure_sys,
  blood_pressure_dia,
  heart_rate,
  blood_sugar,
  weight_kg,
  bmi
FROM  health_metrics hm
JOIN  patients       p ON hm.patient_id = p.patient_id
WHERE p.user_id       = :user_id
  AND recorded_at    >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
ORDER BY recorded_at ASC;


-- 4. Single record detail (for modal/view)
SELECT
  hr.*,
  CONCAT(dp.first_name, ' ', dp.last_name) AS doctor_name,
  d.specialization
FROM  health_records hr
JOIN  doctors        d  ON hr.doctor_id  = d.doctor_id
JOIN  user_profiles  dp ON d.user_id     = dp.user_id
WHERE hr.record_id  = :record_id
  AND hr.patient_id = :patient_id;   -- ownership check