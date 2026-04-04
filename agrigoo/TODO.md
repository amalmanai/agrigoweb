# Task: Ajouter des fonctionnalités avancées à /admin/users (Gestion Utilisateurs)

## [x] 0. Plan approuvé par l'utilisateur

## [x] 1. Ajouter recherche/filtre utilisateurs (par nom/email/téléphone)
   - Modifier src/Repository/UserRepository.php : ajouter méthode findByQuery()
   - Modifier src/Controller/UserController.php : gérer paramètre de recherche ?q=
   - Modifier templates/admin/users.html.twig : ajouter barre de recherche

## [ ] 2. Installer librairie PDF
   - composer require dompdf/dompdf

## [ ] 3. Ajouter export PDF
   - Modifier src/Controller/UserController.php : ajouter action exportPdf()
   - Ajouter route dans config/routes.yaml
   - Créer templates/admin/users_pdf.html.twig
   - Ajouter bouton export dans users.html.twig

## [ ] 4. Tests (se connecter admin, /admin/users, tester recherche, PDF)
## [ ] 5. Optionnel : Export CSV, actions bulk

Progress: 0/5 completed
