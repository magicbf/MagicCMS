-- The top role is called "Superadmin" everywhere
UPDATE {roles} SET name = 'Superadmin' WHERE slug = 'superadmin' AND name = 'Superadministrator';
