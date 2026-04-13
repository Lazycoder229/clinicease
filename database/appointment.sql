-- =============================================================
--  ClinicEase — Appointments Module
--  Aligned with: users, user_profiles, patients, doctors schema
-- =============================================================

USE clinicease;

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
--  SEED DATA
-- =============================================================

-- Demo users  (password = "password123" bcrypt)
INSERT IGNORE INTO `users`
  (username, email, password_hash, role, account_status, agreed_terms)
VALUES
  ('juan_patient', 'juan@example.com',  '$2y$12$KIXtU/9R1z4Qe8nPpMvHNeJHHhQ3XB4gBfYD7dOiQkFRiHxW3zXTy', 'patient', 'active', 1),
  ('dr_santos',    'santos@clinic.com', '$2y$12$KIXtU/9R1z4Qe8nPpMvHNeJHHhQ3XB4gBfYD7dOiQkFRiHxW3zXTy', 'doctor',  'active', 1),
  ('dr_reyes',     'reyes@clinic.com',  '$2y$12$KIXtU/9R1z4Qe8nPpMvHNeJHHhQ3XB4gBfYD7dOiQkFRiHxW3zXTy', 'doctor',  'active', 1),
  ('dr_lim',       'lim@clinic.com',    '$2y$12$KIXtU/9R1z4Qe8nPpMvHNeJHHhQ3XB4gBfYD7dOiQkFRiHxW3zXTy', 'doctor',  'active', 1),
  ('admin_main',   'admin@clinic.com',  '$2y$12$KIXtU/9R1z4Qe8nPpMvHNeJHHhQ3XB4gBfYD7dOiQkFRiHxW3zXTy', 'admin',   'active', 1);

-- Demo user_profiles
INSERT IGNORE INTO `user_profiles`
  (user_id, first_name, last_name, date_of_birth, gender, phone, address)
SELECT u.user_id, s.first_name, s.last_name, s.dob, s.gender, s.phone, s.address
FROM `users` u
JOIN (
  SELECT 'juan_patient' AS uname, 'Juan'   AS first_name, 'dela Cruz' AS last_name,
         '1990-05-14'   AS dob,   'Male'   AS gender,
         '09171234567'  AS phone,  'Quezon City, NCR'  AS address
  UNION ALL SELECT 'dr_santos', 'Ana',    'Santos', '1978-03-22', 'Female', '09189876543', 'Makati City, NCR'
  UNION ALL SELECT 'dr_reyes',  'Carlos', 'Reyes',  '1980-07-11', 'Male',   '09201122334', 'Pasig City, NCR'
  UNION ALL SELECT 'dr_lim',    'Grace',  'Lim',    '1985-01-30', 'Female', '09271234000', 'Taguig City, NCR'
) s ON u.username = s.uname;

-- Demo patients
INSERT IGNORE INTO `patients` (user_id, blood_type, civil_status)
SELECT user_id, 'O+', 'Single' FROM `users` WHERE username = 'juan_patient';

-- Demo doctors
INSERT IGNORE INTO `doctors`
  (user_id, prc_license_no, license_expiry, specialization, medical_school, years_experience, consult_days, consult_hours)
SELECT u.user_id, d.lic, d.expiry, d.spec, d.school, d.exp, d.days, d.hours
FROM `users` u
JOIN (
  SELECT 'dr_santos' AS uname, 'PRC-100001' AS lic, '2027-12-31' AS expiry,
         'General Medicine' AS spec, 'UP Manila College of Medicine' AS school,
         15 AS exp, 'Mon, Tue, Thu' AS days, '9:00 AM – 5:00 PM' AS hours
  UNION ALL
  SELECT 'dr_reyes', 'PRC-100002', '2026-06-30', 'Ophthalmology',
         'UST Faculty of Medicine', 12, 'Mon, Wed, Fri', '8:00 AM – 4:00 PM'
  UNION ALL
  SELECT 'dr_lim', 'PRC-100003', '2028-03-15', 'Dermatology',
         'Ateneo School of Medicine', 10, 'Tue, Thu, Sat', '10:00 AM – 6:00 PM'
) d ON u.username = d.uname;

-- Demo appointments
INSERT INTO `appointments`
  (patient_id, doctor_id, type, appointment_date, appointment_time, status, notes)
SELECT
  (SELECT patient_id FROM patients WHERE user_id = (SELECT user_id FROM users WHERE username = 'juan_patient')),
  (SELECT doctor_id  FROM doctors  WHERE user_id = (SELECT user_id FROM users WHERE username = d.doc)),
  d.type, d.adate, d.atime, d.status, d.notes
FROM (
  SELECT 'dr_santos' AS doc, 'General Check-up' AS type, '2026-02-22' AS adate, '09:00:00' AS atime, 'Confirmed'  AS status, 'Annual physical check-up'         AS notes
  UNION ALL
  SELECT 'dr_reyes',  'Eye Examination',  '2026-02-25', '14:00:00', 'Pending',   'Left eye blurry since last month'
  UNION ALL
  SELECT 'dr_lim',    'Consultation',     '2026-03-03', '10:30:00', 'Scheduled', 'Skin rash follow-up'
  UNION ALL
  SELECT 'dr_santos', 'Vaccination',      '2026-01-15', '11:00:00', 'Completed', 'Flu shot 2026'
  UNION ALL
  SELECT 'dr_reyes',  'General Check-up', '2026-01-05', '08:30:00', 'Cancelled', 'Rescheduled by patient'
) d;


-- =============================================================
--  USEFUL QUERIES
-- =============================================================

-- 1. Upcoming appointments for a logged-in patient (bind :user_id from $_SESSION)
SELECT
  a.appointment_id,
  a.type,
  a.appointment_date,
  a.appointment_time,
  a.status,
  a.notes,
  CONCAT(dp.first_name, ' ', dp.last_name) AS doctor_name,
  d.specialization
FROM  appointments  a
JOIN  doctors       d  ON a.doctor_id  = d.doctor_id
JOIN  user_profiles dp ON d.user_id    = dp.user_id
JOIN  patients      p  ON a.patient_id = p.patient_id
WHERE p.user_id          = :user_id
  AND a.appointment_date >= CURDATE()
  AND a.status NOT IN ('Cancelled','Completed','No-show')
ORDER BY a.appointment_date, a.appointment_time;


-- 2. All appointments for a patient (full history)
SELECT
  a.appointment_id,
  a.type,
  a.appointment_date,
  a.appointment_time,
  a.status,
  a.notes,
  a.doctor_notes,
  CONCAT(dp.first_name, ' ', dp.last_name) AS doctor_name,
  d.specialization,
  a.created_at
FROM  appointments  a
JOIN  doctors       d  ON a.doctor_id  = d.doctor_id
JOIN  user_profiles dp ON d.user_id    = dp.user_id
JOIN  patients      p  ON a.patient_id = p.patient_id
WHERE p.user_id = :user_id
ORDER BY a.appointment_date DESC, a.appointment_time DESC;


-- 3. Count by status for a patient
SELECT   a.status, COUNT(*) AS total
FROM     appointments a
JOIN     patients     p ON a.patient_id = p.patient_id
WHERE    p.user_id = :user_id
GROUP BY a.status;


-- 4. Doctor's schedule for today
SELECT
  a.appointment_id,
  a.appointment_time,
  a.type,
  a.status,
  CONCAT(pp.first_name, ' ', pp.last_name) AS patient_name,
  pat.blood_type,
  pat.allergies
FROM  appointments  a
JOIN  patients      pat ON a.patient_id  = pat.patient_id
JOIN  user_profiles pp  ON pat.user_id   = pp.user_id
JOIN  doctors       d   ON a.doctor_id   = d.doctor_id
WHERE d.user_id          = :doctor_user_id
  AND a.appointment_date = CURDATE()
ORDER BY a.appointment_time;


-- 5. Admin: appointments per doctor this month
SELECT
  CONCAT(dp.first_name, ' ', dp.last_name) AS doctor_name,
  d.specialization,
  COUNT(*)                    AS total,
  SUM(a.status = 'Completed') AS completed,
  SUM(a.status = 'Cancelled') AS cancelled,
  SUM(a.status = 'No-show')   AS no_show
FROM  appointments  a
JOIN  doctors       d  ON a.doctor_id = d.doctor_id
JOIN  user_profiles dp ON d.user_id   = dp.user_id
WHERE YEAR(a.appointment_date)  = YEAR(CURDATE())
  AND MONTH(a.appointment_date) = MONTH(CURDATE())
GROUP BY a.doctor_id
ORDER BY total DESC;