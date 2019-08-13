<?php

$installer = $this;
$installer->startSetup();

$installer->run("

ALTER TABLE {$this->getTable('recommender/product_link')} ADD COLUMN `category_id` INT(10) NULL AFTER `product_id`;
ALTER TABLE {$this->getTable('recommender/product_link')} DROP INDEX `link_type_id`;
ALTER TABLE {$this->getTable('recommender/product_link')} ADD UNIQUE INDEX `link_type_id` (`link_type_id`,`product_id`,`category_id`,`position`);
");