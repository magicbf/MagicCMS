-- Security hardening: remember when a password last changed so older sessions can be ended
ALTER TABLE {users} ADD COLUMN pw_changed_at DATETIME NULL AFTER password_hash;
