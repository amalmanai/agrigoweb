-- À exécuter dans phpMyAdmin sur la base agri_go_db (ou la base utilisée par l’app)
-- Corrige : Unknown column 'bad_word_comment_strikes' sur la table user

ALTER TABLE `user`
  ADD COLUMN `bad_word_comment_strikes` INT NOT NULL DEFAULT 0;
