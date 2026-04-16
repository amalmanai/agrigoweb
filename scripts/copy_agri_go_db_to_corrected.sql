-- Copie des données agri_go_db -> agri_go_db_corrected (schéma Symfony / contraintes corrigées)
-- Exécuter avec: mysql -u root < scripts/copy_agri_go_db_to_corrected.sql
SET NAMES utf8mb4;
SET SESSION foreign_key_checks = 0;

TRUNCATE TABLE agri_go_db_corrected.alertes_risques;
TRUNCATE TABLE agri_go_db_corrected.historique_cultures;
TRUNCATE TABLE agri_go_db_corrected.historique_irrigation;
TRUNCATE TABLE agri_go_db_corrected.cultures;
TRUNCATE TABLE agri_go_db_corrected.vente;
TRUNCATE TABLE agri_go_db_corrected.mouvement_stock;
TRUNCATE TABLE agri_go_db_corrected.systeme_irrigation;
TRUNCATE TABLE agri_go_db_corrected.recolte;
TRUNCATE TABLE agri_go_db_corrected.parcelles;
TRUNCATE TABLE agri_go_db_corrected.produit;
TRUNCATE TABLE agri_go_db_corrected.tache;
TRUNCATE TABLE agri_go_db_corrected.messenger_messages;
TRUNCATE TABLE agri_go_db_corrected.user;

-- Emails dupliqués: suffixe +id_user avant @ pour respecter UNIQUE (email_user)
INSERT INTO agri_go_db_corrected.user (
  id_user, nom_user, prenom_user, email_user, password, role_user, num_user,
  adresse_user, photo_path, is_active, reset_token, reset_expires
)
SELECT
  id_user,
  nom_user,
  prenom_user,
  CASE
    WHEN rn > 1 THEN CONCAT(
      SUBSTRING_INDEX(email_user, '@', 1), '+', id_user, '@', SUBSTRING_INDEX(email_user, '@', -1)
    )
    ELSE email_user
  END,
  password,
  role_user,
  num_user,
  adresse_user,
  photo_path,
  is_active,
  reset_token,
  reset_expires
FROM (
  SELECT
    u.*,
    ROW_NUMBER() OVER (PARTITION BY email_user ORDER BY id_user) AS rn
  FROM agri_go_db.user u
) t;

INSERT INTO agri_go_db_corrected.parcelles (
  id_parcelle, nom_parcelle, surface, coordonnees_gps, type_sol, owner_id
)
SELECT
  id_parcelle,
  nom_parcelle,
  surface,
  coordonnees_gps,
  type_sol,
  (SELECT MIN(id_user) FROM agri_go_db_corrected.user)
FROM agri_go_db.parcelles;

INSERT INTO agri_go_db_corrected.recolte
SELECT * FROM agri_go_db.recolte;

INSERT INTO agri_go_db_corrected.cultures (
  id_culture, id_parcelle, nom_culture, date_semis, etat_croissance, rendement_prevu, owner_id
)
SELECT
  c.id_culture,
  c.id_parcelle,
  c.nom_culture,
  c.date_semis,
  c.etat_croissance,
  c.rendement_prevu,
  p.owner_id
FROM agri_go_db.cultures c
LEFT JOIN agri_go_db_corrected.parcelles p ON p.id_parcelle = c.id_parcelle;

INSERT INTO agri_go_db_corrected.alertes_risques (
  id_alerte, id_culture, type_alerte, description, date_alerte
)
SELECT id_alerte, id_culture, type_alerte, description, date_alerte
FROM agri_go_db.alertes_risques;

INSERT INTO agri_go_db_corrected.historique_cultures
SELECT * FROM agri_go_db.historique_cultures;

INSERT INTO agri_go_db_corrected.systeme_irrigation
SELECT * FROM agri_go_db.systeme_irrigation;

INSERT INTO agri_go_db_corrected.historique_irrigation
SELECT * FROM agri_go_db.historique_irrigation;

-- Ancien modèle vente (prix, id_user) -> nouveau (price, recolte_id optionnel)
INSERT INTO agri_go_db_corrected.vente (
  id_vente, recolte_id, description, price, sale_date, buyer_name, status
)
SELECT
  id_vente,
  NULL,
  description,
  prix,
  date_vente,
  NULL,
  'Completed'
FROM agri_go_db.vente;

INSERT INTO agri_go_db_corrected.produit
SELECT * FROM agri_go_db.produit;

INSERT INTO agri_go_db_corrected.mouvement_stock
SELECT * FROM agri_go_db.mouvement_stock;

INSERT INTO agri_go_db_corrected.tache
SELECT * FROM agri_go_db.tache;

INSERT INTO agri_go_db_corrected.messenger_messages
SELECT * FROM agri_go_db.messenger_messages;

SET SESSION foreign_key_checks = 1;
