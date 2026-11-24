-- =============================================================
-- AMOURÉ SALON: sample data
-- Import AFTER schema.sql.
-- Sample logins (change these passwords after your first login):
--   admin@amoure.test     / Admin@123
--   staff@amoure.test     / Staff@123
--   customer@amoure.test  / Customer@123
-- Prices and names are sample values: replace them with the salon's real ones.
-- =============================================================

USE amoure_salon_db;

-- ---------- settings ----------
INSERT INTO settings (setting_key, setting_value) VALUES
  ('daily_appointment_limit', '20'),
  ('booking_emails_enabled',  '1'),
  ('reminders_enabled',       '1'),
  ('reminder_hours_before',   '24'),
  ('opening_time',            '09:00'),
  ('closing_time',            '19:00'),
  ('slot_interval_minutes',   '30'),
  ('booking_days_ahead',      '30');

-- ---------- users ----------
INSERT INTO users (id, name, email, password_hash, role) VALUES
  (1, 'Salon Admin',     'admin@amoure.test',    '$2y$10$sFR7Tzp/ZYhUaHuU2qDIKuMuMONu5CpCQcYQfifvXWH5GdkXemn36', 'admin'),
  (2, 'Front Desk',      'staff@amoure.test',    '$2y$10$Xv83y8mtngt9Hv3wD8P0ku2rYVMLcznzASyxRpo3jzw03yBzZpTJG', 'staff'),
  (3, 'Sample Customer', 'customer@amoure.test', '$2y$10$ebxW77GfEkHbn8sJH04pje7ERytCc3diIGefwQ7W6Y3jqaoWqz76e', 'customer');

-- ---------- customers ----------
INSERT INTO customers (id, user_id, full_name, phone, email, notes) VALUES
  (1, 3,    'Sample Customer', '09170000001', 'customer@amoure.test', NULL),
  (2, NULL, 'Walk-in Customer', '09170000002', NULL, 'Walk-in, no account, no email');

-- ---------- stylists ----------
INSERT INTO stylists (id, full_name, specialty) VALUES
  (1, 'Sofia Reyes',      'Haircut, Perm & Rebond'),
  (2, 'Isabella Torres',  'Hair Color & Treatment'),
  (3, 'Marco Villanueva', 'Nails & Waxing'),
  (4, 'Ethan Cruz',       'Hair Styling & Makeup');

-- ---------- services ----------
INSERT INTO services (id, name, category, price, duration_minutes) VALUES
  (1, 'Haircut',            'Haircut',         450.00,  45),
  (2, 'Shampoo & Blow Dry', 'Hair Styling & Makeup', 300.00,  30),
  (3, 'Root Color - Deluxe','Hair Coloring', 2500.00,  90),
  (4, 'Loreal Hair Spa',    'Hair Treatments', 2500.00,  60),
  (5, 'Manicure',           'Nail Care',       250.00,  30),
  (6, 'Pedicure',           'Nail Care',       280.00,  45);
-- For the full salon menu (50 services), also import services-update.sql.

-- ---------- which stylist does which service ----------
INSERT INTO stylist_services (stylist_id, service_id) VALUES
  (1, 1), (1, 2),
  (2, 2), (2, 3), (2, 4),
  (3, 5), (3, 6),
  (4, 2), (4, 4);

-- ---------- sample appointments (dates relative to the import day) ----------
INSERT INTO appointments
  (id, customer_id, stylist_id, service_id, appt_date, start_time, end_time, status, source, created_by) VALUES
  -- completed last week (has an approved review below)
  (1, 1, 1, 1, DATE_SUB(CURDATE(), INTERVAL 7 DAY), '10:00:00', '10:45:00', 'completed', 'online',  3),
  -- confirmed for tomorrow (use this to test the reminder job)
  (2, 1, 2, 4, DATE_ADD(CURDATE(), INTERVAL 1 DAY), '14:00:00', '15:00:00', 'confirmed', 'online',  3),
  -- pending online request in 3 days (use this to test confirming a booking)
  (3, 1, 3, 5, DATE_ADD(CURDATE(), INTERVAL 3 DAY), '11:00:00', '11:45:00', 'pending',   'online',  3),
  -- walk-in today
  (4, 2, 1, 2, CURDATE(),                          '16:00:00', '16:30:00', 'confirmed', 'walk_in', 2);

-- ---------- sample review ----------
INSERT INTO reviews (appointment_id, rating, comment, status) VALUES
  (1, 5, 'Sample review: replace or delete before going live.', 'approved');
