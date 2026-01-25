-- Migration pour lier les users à la newsletter
USE site_escalade;

-- Ajouter un champ newsletter_subscribed à la table users
ALTER TABLE users
ADD COLUMN newsletter_subscribed BOOLEAN DEFAULT FALSE AFTER is_admin;

-- Synchroniser avec la table newsletter existante
-- Marquer comme abonnés les users qui ont leur email dans la table newsletter
UPDATE users u
SET newsletter_subscribed = TRUE
WHERE EXISTS (
    SELECT 1 FROM newsletter n
    WHERE n.email = u.email AND n.actif = TRUE
);
