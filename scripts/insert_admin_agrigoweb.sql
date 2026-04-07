-- Compte admin local (mot de passe : AdminAgri2026!)
-- Exécuter une seule fois : mysql -u root agri_go_db_corrected < scripts/insert_admin_agrigoweb.sql
INSERT INTO `user` (
  nom_user,
  prenom_user,
  email_user,
  password,
  role_user,
  num_user,
  adresse_user,
  is_active
) VALUES (
  'Admin',
  'AgriGo',
  'admin@agrigoweb.local',
  '$2y$13$.AdYpxGnj9zIR0xYJLR4GOEFb/9VWk.bOZMxwCCDCwqswkJVUozGO',
  'ROLE_ADMIN',
  12345678,
  'Local',
  1
);
