-- Compte utilisateur standard (mot de passe : UserAgri2026!)
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
  'Utilisateur',
  'Test',
  'user@agrigoweb.local',
  '$2y$13$9iAzPfBWMlBl3JaUp/Ej6.GUsDml0XUNEq84bt68Od9s1NQjgHCdi',
  'ROLE_USER',
  87654321,
  'Local',
  1
);
