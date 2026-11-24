-- AMOURÉ SALON: real stylist names (run once in phpMyAdmin > amoure_salon_db > Import)
USE amoure_salon_db;

UPDATE stylists SET full_name = 'Sofia Reyes',       specialty = 'Haircut, Perm & Rebond'  WHERE id = 1;
UPDATE stylists SET full_name = 'Isabella Torres',   specialty = 'Hair Color & Treatment'  WHERE id = 2;
UPDATE stylists SET full_name = 'Marco Villanueva',  specialty = 'Nails & Waxing'          WHERE id = 3;
UPDATE stylists SET full_name = 'Ethan Cruz',        specialty = 'Hair Styling & Makeup'   WHERE id = 4;

SELECT id, full_name, specialty FROM stylists ORDER BY id;
