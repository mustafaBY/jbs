CREATE DEFINER = CURRENT_USER TRIGGER `WorksCompliteOnInsert` BEFORE INSERT ON `WorksComplite`
  FOR EACH ROW BEGIN
    SET NEW.`CreateDate` = UNIX_TIMESTAMP();
  END;
