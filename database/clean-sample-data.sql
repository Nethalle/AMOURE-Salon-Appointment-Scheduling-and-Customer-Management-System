-- =============================================================
-- AMOURÉ SALON: remove all sample / test data before real use
--
-- BEFORE RUNNING:
--   1. Back up first: phpMyAdmin > amoure_salon_db > Export > Go.
--   2. Create YOUR OWN admin account (Admin > User Accounts > Add User, role Admin)
--      and log in with it once. The sample admin is only deleted if
--      another active admin exists, so you can't lock yourself out.
--
-- KEEPS:   your services, stylists, stylist-service links, settings,
--          and every account that is not a sample (@amoure.test).
-- REMOVES: all appointments, reviews, email logs, test customer profiles,
--          the sample staff/customer accounts, the sample admin,
--          and old inactive "Other" services.
-- This cannot be undone (except from your backup).
-- =============================================================

USE amoure_salon_db;

-- 1. Bookings and everything attached to them.
DELETE FROM email_logs;
DELETE FROM reviews;
DELETE FROM appointments;

-- 2. Customer profiles: walk-in test customers and the sample online customer.
--    (Profiles of real customers who already registered are kept.)
DELETE FROM customers
 WHERE user_id IS NULL
    OR user_id IN (SELECT id FROM (SELECT id FROM users WHERE email LIKE '%@amoure.test') AS sample_users);

-- 3. Sample staff and customer logins.
DELETE FROM users WHERE email LIKE '%@amoure.test' AND role <> 'admin';

-- 4. Sample admin: only if another active admin account exists.
DELETE FROM users
 WHERE email = 'admin@amoure.test'
   AND (SELECT n FROM (SELECT COUNT(*) AS n FROM users
                        WHERE role = 'admin' AND is_active = 1 AND email <> 'admin@amoure.test') AS other_admins) > 0;

-- 5. Old sample services that are turned off and not in any category.
DELETE FROM services WHERE category = 'Other' AND is_active = 0;

-- 6. Bell "last seen" times of deleted accounts.
DELETE FROM settings
 WHERE setting_key LIKE 'notif\_seen\_%'
   AND CAST(SUBSTRING(setting_key, 12) AS UNSIGNED) NOT IN (SELECT id FROM users);

-- 7. Start booking / customer numbers from 1 again.
ALTER TABLE appointments AUTO_INCREMENT = 1;
ALTER TABLE customers    AUTO_INCREMENT = 1;
ALTER TABLE reviews      AUTO_INCREMENT = 1;
ALTER TABLE email_logs   AUTO_INCREMENT = 1;

-- Check what is left.
SELECT 'users' AS what, COUNT(*) AS total FROM users
UNION ALL SELECT 'admins still here', COUNT(*) FROM users WHERE role = 'admin'
UNION ALL SELECT 'sample admin still here (should be 0)', COUNT(*) FROM users WHERE email = 'admin@amoure.test'
UNION ALL SELECT 'customers', COUNT(*) FROM customers
UNION ALL SELECT 'appointments', COUNT(*) FROM appointments
UNION ALL SELECT 'active services', COUNT(*) FROM services WHERE is_active = 1
UNION ALL SELECT 'stylists', COUNT(*) FROM stylists;
