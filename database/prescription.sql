-- =============================================================
--  ClinicEase — Prescriptions Module
--  Aligned with: users, user_profiles, patients, doctors,
--                health_records, appointments schema
-- =============================================================

USE clinicease;

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
--  SEED DATA  — sample prescriptions for juan_patient
-- =============================================================
INSERT INTO `prescriptions`
  (patient_id, doctor_id, prescribed_date, expiry_date,
   medication_name, generic_name, dosage, form, frequency,
   duration_days, quantity, route, instructions, indication,
   refills_allowed, refills_used, status)
SELECT
  (SELECT patient_id FROM patients
    WHERE user_id = (SELECT user_id FROM users WHERE username = 'juan_patient')),
  (SELECT doctor_id FROM doctors
    WHERE user_id = (SELECT user_id FROM users WHERE username = d.doc)),
  d.pdate, d.edate,
  d.med, d.gen, d.dose, d.form, d.freq,
  d.dur, d.qty, d.route, d.instr, d.indication,
  d.refills, d.refills_used, d.status
FROM (
  SELECT
    'dr_santos'                 AS doc,
    '2026-01-15'                AS pdate,
    '2026-04-15'                AS edate,
    'Amlodipine'                AS med,
    'Amlodipine Besylate'       AS gen,
    '5mg'                       AS dose,
    'Tablet'                    AS form,
    'Once daily (morning)'      AS freq,
    90                          AS dur,
    90                          AS qty,
    'Oral'                      AS route,
    'Take with or without food. Do not stop abruptly.' AS instr,
    'Hypertension Stage 1'      AS indication,
    2                           AS refills,
    0                           AS refills_used,
    'Active'                    AS status
  UNION ALL SELECT
    'dr_santos', '2026-01-15', '2026-02-15',
    'Metformin', 'Metformin Hydrochloride', '500mg', 'Tablet',
    'Twice daily with meals', 30, 60, 'Oral',
    'Take with food to reduce GI side effects. Monitor blood sugar regularly.',
    'Pre-diabetes management', 1, 0, 'Active'
  UNION ALL SELECT
    'dr_santos', '2025-11-20', '2025-12-20',
    'Ibuprofen', 'Ibuprofen', '400mg', 'Tablet',
    'Every 8 hours as needed', 7, 21, 'Oral',
    'Take with food. Do not exceed 1200mg per day.',
    'Tension headache', 0, 0, 'Completed'
  UNION ALL SELECT
    'dr_reyes', '2025-12-10', '2026-06-10',
    'Artificial Tears', 'Carboxymethylcellulose Sodium 0.5%', '1-2 drops', 'Drops',
    '4 times daily or as needed', 180, 2, 'Topical',
    'Tilt head back, pull lower eyelid down. Do not touch dropper tip to eye.',
    'Dry eyes / Myopia management', 3, 1, 'Active'
  UNION ALL SELECT
    'dr_santos', '2025-10-01', '2025-11-01',
    'Amoxicillin', 'Amoxicillin Trihydrate', '500mg', 'Capsule',
    'Three times daily', 7, 21, 'Oral',
    'Complete the full course even if feeling better. May cause GI upset.',
    'Bacterial throat infection', 0, 0, 'Completed'
  UNION ALL SELECT
    'dr_lim', '2025-09-15', '2025-10-15',
    'Clindamycin Phosphate Gel', 'Clindamycin Phosphate', '1%', 'Cream',
    'Apply thin layer once at night', 30, 1, 'Topical',
    'Avoid eyes and lips. Use sunscreen during the day.',
    'Mild acne vulgaris', 1, 1, 'Discontinued'
) d;


-- =============================================================
--  USEFUL QUERIES
-- =============================================================

-- 1. All prescriptions for a patient (newest first)
SELECT
  rx.prescription_id,
  rx.medication_name,
  rx.generic_name,
  rx.dosage,
  rx.form,
  rx.frequency,
  rx.duration_days,
  rx.route,
  rx.instructions,
  rx.indication,
  rx.prescribed_date,
  rx.expiry_date,
  rx.refills_allowed,
  rx.refills_used,
  rx.status,
  CONCAT(dp.first_name, ' ', dp.last_name) AS doctor_name,
  d.specialization
FROM  prescriptions  rx
JOIN  doctors        d  ON rx.doctor_id  = d.doctor_id
JOIN  user_profiles  dp ON d.user_id     = dp.user_id
JOIN  patients       p  ON rx.patient_id = p.patient_id
WHERE p.user_id = :user_id
ORDER BY rx.prescribed_date DESC;


-- 2. Active prescriptions only
SELECT
  rx.prescription_id,
  rx.medication_name,
  rx.dosage,
  rx.form,
  rx.frequency,
  rx.expiry_date,
  rx.refills_allowed,
  rx.refills_used,
  (rx.refills_allowed - rx.refills_used) AS refills_remaining,
  CONCAT(dp.first_name, ' ', dp.last_name) AS doctor_name
FROM  prescriptions  rx
JOIN  doctors        d  ON rx.doctor_id  = d.doctor_id
JOIN  user_profiles  dp ON d.user_id     = dp.user_id
JOIN  patients       p  ON rx.patient_id = p.patient_id
WHERE p.user_id   = :user_id
  AND rx.status   = 'Active'
ORDER BY rx.expiry_date ASC;


-- 3. Prescriptions expiring within 30 days (refill reminders)
SELECT
  rx.medication_name,
  rx.dosage,
  rx.expiry_date,
  DATEDIFF(rx.expiry_date, CURDATE()) AS days_until_expiry,
  rx.refills_remaining,
  CONCAT(dp.first_name, ' ', dp.last_name) AS doctor_name
FROM (
  SELECT rx.*,
         (rx.refills_allowed - rx.refills_used) AS refills_remaining
  FROM   prescriptions rx
) rx
JOIN  doctors        d  ON rx.doctor_id  = d.doctor_id
JOIN  user_profiles  dp ON d.user_id     = dp.user_id
JOIN  patients       p  ON rx.patient_id = p.patient_id
WHERE p.user_id          = :user_id
  AND rx.status          = 'Active'
  AND rx.expiry_date     <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)
ORDER BY rx.expiry_date ASC;


-- 4. Request a refill (increment refills_used, guard against exceeding limit)
UPDATE prescriptions
SET    refills_used = refills_used + 1
WHERE  prescription_id = :prescription_id
  AND  patient_id      = :patient_id        -- ownership guard
  AND  status          = 'Active'
  AND  refills_used    < refills_allowed;   -- cannot exceed allowed refills


-- 5. Count prescriptions by status for a patient
SELECT   rx.status, COUNT(*) AS total
FROM     prescriptions rx
JOIN     patients      p ON rx.patient_id = p.patient_id
WHERE    p.user_id = :user_id
GROUP BY rx.status;


-- 6. Auto-expire prescriptions past their expiry date (run as cron job)
UPDATE prescriptions
SET    status = 'Expired'
WHERE  status      = 'Active'
  AND  expiry_date < CURDATE();