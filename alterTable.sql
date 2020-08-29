ALTER TABLE `business`
ADD `doc_empresa` VARCHAR(15) NOT NULL;

ALTER TABLE `business_locations`
ADD `district` VARCHAR(100) NOT NULL;

-- ALTER TABLE `transactions`
-- ADD `district ` VARCHAR(100) NOT NULL;

ALTER TABLE `transactions`
ADD `estado_sunat` Tinyint(2) NOT NULL;

-- https://es.stackoverflow.com/questions/14139/laravel-no-actualiza-cambios-en-producci%C3%B3n