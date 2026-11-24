-- =============================================================
-- AMOURÉ SALON: full services menu
-- Run AFTER schema.sql and seed.sql (phpMyAdmin: select amoure_salon_db > Import > this file > Go).
-- Safe to run more than once. Old services are deactivated, not deleted, so past appointments keep them.
-- Durations are estimates: change them in Admin > Services if needed.
-- =============================================================

USE amoure_salon_db;

-- 0. Category column (skipped if it already exists).
ALTER TABLE services ADD COLUMN IF NOT EXISTS category VARCHAR(50) NOT NULL DEFAULT 'Other' AFTER name;

-- 0b. Rename older category names (from the first version of this file).
UPDATE services SET category = 'Hair Coloring'         WHERE category = 'Hair Color';
UPDATE services SET category = 'Hair Treatments'       WHERE category = 'Hair Treatment';
UPDATE services SET category = 'Perming & Rebonding'   WHERE category = 'Hair Form';
UPDATE services SET category = 'Hair Styling & Makeup' WHERE category = 'Hair & Make Up';

-- 1. Hide every current service; the ones below are switched back on.
UPDATE services SET is_active = 0;

-- 2. Add or update the full menu (name, price, minutes).
INSERT INTO services (name, category, price, duration_minutes, is_active) VALUES
  -- Haircut
  ('Haircut',                         'Haircut',         450.00,  45, 1),
  ('Haircut - Senior Stylist',        'Haircut',         500.00,  45, 1),

  -- Hair color
  ('Root Color - Deluxe',            'Hair Coloring', 2500.00,  90, 1),
  ('Root Color - Premium',           'Hair Coloring', 3000.00,  90, 1),
  ('Full Color - Deluxe',            'Hair Coloring', 3000.00, 120, 1),
  ('Full Color - Premium',           'Hair Coloring', 3500.00, 120, 1),
  ('Highlights (Foil)',              'Hair Coloring', 3500.00, 150, 1),
  ('Color Conditioning',             'Hair Coloring', 3000.00,  90, 1),

  -- Hair treatment
  ('Milbon Collagen Treatment',      'Hair Treatments', 2500.00,  60, 1),
  ('Milk Shake Make My Day',         'Hair Treatments', 2500.00,  60, 1),
  ('Milk Shake Integrity Repair',    'Hair Treatments', 2500.00,  60, 1),
  ('Loreal Hair Spa',                'Hair Treatments', 2500.00,  60, 1),
  ('Keratin Color Lock & Smooth',    'Hair Treatments', 2500.00,  90, 1),
  ('Keratin Intense Renewal',        'Hair Treatments', 5500.00, 120, 1),
  ('Brazilian Renewal',              'Hair Treatments', 6500.00, 180, 1),
  ('Moroccan Argan MSQ',             'Hair Treatments', 1500.00,  60, 1),

  -- Form (perm, relax, rebond)
  ('Traditional Perm',               'Perming & Rebonding', 2500.00, 150, 1),
  ('Hair Relax - Deluxe',            'Perming & Rebonding', 4500.00, 150, 1),
  ('Hair Relax - Premium',           'Perming & Rebonding', 5000.00, 150, 1),
  ('Hair Rebond - Deluxe',           'Perming & Rebonding', 5000.00, 240, 1),
  ('Hair Rebond - Premium',          'Perming & Rebonding', 6500.00, 240, 1),

  -- Hair & make up
  ('Shampoo & Blow Dry',              'Hair Styling & Makeup', 300.00,  30, 1),
  ('Shampoo Setting',                 'Hair Styling & Makeup', 780.00,  45, 1),
  ('Iron',                            'Hair Styling & Makeup', 780.00,  45, 1),
  ('Eye Make Up',                     'Hair Styling & Makeup', 850.00,  30, 1),
  ('Full Make Up',                   'Hair Styling & Makeup', 1300.00,  60, 1),
  ('Hair Styling & Makeup', 'Hair Styling & Makeup', 1990.00,  90, 1),

  -- Hair removal (waxing)
  ('Eyebrows Wax',                    'Hair Removal',    200.00,  15, 1),
  ('Eyebrows Shave',                  'Hair Removal',    150.00,  15, 1),
  ('Upper Lip Wax',                   'Hair Removal',    255.00,  15, 1),
  ('Lower Lip Wax',                   'Hair Removal',    255.00,  15, 1),
  ('Underarm Wax',                    'Hair Removal',    285.00,  15, 1),
  ('Full Arm Wax',                    'Hair Removal',    550.00,  30, 1),
  ('Half Leg Wax',                    'Hair Removal',    750.00,  30, 1),
  ('Full Leg Wax',                    'Hair Removal',    900.00,  45, 1),
  ('Brazilian Wax',                   'Hair Removal',    600.00,  30, 1),

  -- Nail care
  ('Manicure',                        'Nail Care',       250.00,  30, 1),
  ('Gel Manicure',                    'Nail Care',       520.00,  45, 1),
  ('Pedicure',                        'Nail Care',       280.00,  45, 1),
  ('Gel Pedicure',                    'Nail Care',       550.00,  60, 1),
  ('Gel Polish Removal',              'Nail Care',       150.00,  15, 1),
  ('Foot Spa Regular',                'Nail Care',       400.00,  45, 1),
  ('Foot Spa Premium',                'Nail Care',       750.00,  60, 1),
  ('Clear Overlay with Manicure',     'Nail Care',       780.00,  60, 1),
  ('Colored Overlay with Manicure',   'Nail Care',       880.00,  60, 1),
  ('French Tip Overlay',              'Nail Care',       980.00,  75, 1),
  ('Change Polish Regular',           'Nail Care',       160.00,  15, 1),
  ('Change Polish Gel',               'Nail Care',       250.00,  30, 1),
  ('Hand Paraffin',                   'Nail Care',       450.00,  30, 1),
  ('Foot Paraffin',                   'Nail Care',       450.00,  30, 1)
ON DUPLICATE KEY UPDATE
  category         = VALUES(category),
  price            = VALUES(price),
  duration_minutes = VALUES(duration_minutes),
  is_active        = 1;

-- 3. Stylist specialties.
UPDATE stylists SET specialty = 'Haircut, perm and rebond'  WHERE id = 1;
UPDATE stylists SET specialty = 'Hair color and treatment'  WHERE id = 2;
UPDATE stylists SET specialty = 'Nails and waxing'          WHERE id = 3;
UPDATE stylists SET specialty = 'Hair and make up'          WHERE id = 4;

-- 4. Which stylist does which service (only adds links; existing ones stay).
-- Stylist One: haircut, form, blow dry / setting / iron
INSERT IGNORE INTO stylist_services (stylist_id, service_id)
SELECT 1, id FROM services WHERE name IN (
  'Haircut', 'Haircut - Senior Stylist',
  'Traditional Perm', 'Hair Relax - Deluxe', 'Hair Relax - Premium', 'Hair Rebond - Deluxe', 'Hair Rebond - Premium',
  'Shampoo & Blow Dry', 'Shampoo Setting', 'Iron');

-- Stylist Two: color, treatment, form
INSERT IGNORE INTO stylist_services (stylist_id, service_id)
SELECT 2, id FROM services WHERE name IN (
  'Root Color - Deluxe', 'Root Color - Premium', 'Full Color - Deluxe', 'Full Color - Premium', 'Highlights (Foil)', 'Color Conditioning',
  'Milbon Collagen Treatment', 'Milk Shake Make My Day', 'Milk Shake Integrity Repair', 'Loreal Hair Spa',
  'Keratin Color Lock & Smooth', 'Keratin Intense Renewal', 'Brazilian Renewal', 'Moroccan Argan MSQ',
  'Traditional Perm', 'Hair Relax - Deluxe', 'Hair Relax - Premium', 'Hair Rebond - Deluxe', 'Hair Rebond - Premium',
  'Shampoo & Blow Dry');

-- Stylist Three: nail care, waxing
INSERT IGNORE INTO stylist_services (stylist_id, service_id)
SELECT 3, id FROM services WHERE name IN (
  'Manicure', 'Gel Manicure', 'Pedicure', 'Gel Pedicure', 'Gel Polish Removal', 'Foot Spa Regular', 'Foot Spa Premium',
  'Clear Overlay with Manicure', 'Colored Overlay with Manicure', 'French Tip Overlay',
  'Change Polish Regular', 'Change Polish Gel', 'Hand Paraffin', 'Foot Paraffin',
  'Eyebrows Wax', 'Eyebrows Shave', 'Upper Lip Wax', 'Lower Lip Wax', 'Underarm Wax',
  'Full Arm Wax', 'Half Leg Wax', 'Full Leg Wax', 'Brazilian Wax');

-- Stylist Four: hair & make up, eyebrows
INSERT IGNORE INTO stylist_services (stylist_id, service_id)
SELECT 4, id FROM services WHERE name IN (
  'Shampoo & Blow Dry', 'Shampoo Setting', 'Iron', 'Eye Make Up', 'Full Make Up', 'Hair Styling & Makeup', 'Eyebrows Wax', 'Eyebrows Shave');

-- Check: every active service should have at least one stylist (this should return no rows).
SELECT s.name AS service_without_stylist
  FROM services s
  LEFT JOIN stylist_services ss ON ss.service_id = s.id
 WHERE s.is_active = 1 AND ss.service_id IS NULL;
