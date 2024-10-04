<?php

/**
 * @category   OpenCart
 * @package    Nice Import XML
 * @copyright  © Serge Tkach, 2023, https://sergetkach.com/; based on https://dropship-b2b.com.ua/import/opencart
 */

class ModelExtensionModuleNix extends Model {
	public function install() {
		$sql = "CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "nix_suppliers` (
			`supplier_id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
      `name` TEXT NOT NULL,
      `markup` DECIMAL(5,2) NOT NULL,
			`link_price` TEXT NOT NULL,
      `tags` MEDIUMTEXT NOT NULL,
      `attributes` MEDIUMTEXT NOT NULL,
      PRIMARY KEY (`supplier_id`)
    ) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;";

		$this->db->query($sql);
		
		// Updating module table
		$query = $this->db->query("SHOW COLUMNS FROM `" . DB_PREFIX . "nix_suppliers` WHERE `Field` = 'markup'");
		
		if ($query->num_rows < 1) {
			$this->db->query("ALTER TABLE `" . DB_PREFIX . "nix_suppliers` ADD `markup` DECIMAL(5,2) NOT NULL AFTER `name`");
		}		
		
		// Add nix_supplier_id to products to identity imported entities by supplier
		$query = $this->db->query("SHOW COLUMNS FROM `" . DB_PREFIX . "product` WHERE `Field` = 'nix_supplier_id'");
		
		if ($query->num_rows < 1) {
			$this->db->query("ALTER TABLE `" . DB_PREFIX . "product`"
				. " ADD `nix_supplier_id` INT(3) UNSIGNED NOT NULL AFTER `product_id`,"
				. " ADD `nix_supplier_product_id` INT(11) UNSIGNED NOT NULL AFTER `nix_supplier_id`,"
				. " ADD INDEX (`nix_supplier_product_id`);");
		}
		
		// Add price_purchasing
		$query = $this->db->query("SHOW COLUMNS FROM `" . DB_PREFIX . "product` WHERE `Field` = 'price_purchasing'");
		
		if ($query->num_rows < 1) {
			$this->db->query("ALTER TABLE `" . DB_PREFIX . "product` ADD `price_purchasing` DECIMAL(15,4) NOT NULL AFTER `price`");
		}
		
		// Add price_rrp
		$query = $this->db->query("SHOW COLUMNS FROM `" . DB_PREFIX . "product` WHERE `Field` = 'price_rrp'");
		
		if ($query->num_rows < 1) {
			$this->db->query("ALTER TABLE `" . DB_PREFIX . "product` ADD `price_rrp` DECIMAL(15,4) NOT NULL AFTER `price_purchasing`");
		}
		
		// Add nix_supplier_id to products to 
		$query = $this->db->query("SHOW COLUMNS FROM `" . DB_PREFIX . "category` WHERE `Field` = 'nix_supplier_id'");
		
		if ($query->num_rows < 1) {
			$this->db->query("ALTER TABLE `" . DB_PREFIX . "category`"
				. " ADD `nix_supplier_id` INT(3) UNSIGNED NOT NULL AFTER `category_id`,"
				. " ADD `nix_supplier_category_id` INT(11) UNSIGNED NOT NULL AFTER `nix_supplier_id`,"
				. " ADD INDEX (`nix_supplier_category_id`);");
		}
		
	}
	
	public function supplierAdd($data) {
		$sql = "INSERT INTO `" . DB_PREFIX . "nix_suppliers` SET"
      . " `name` = '" . $this->db->escape($data['name']) . "',"
      . " `markup` = '" . (float)$data['markup'] . "',"
      . " `link_price` = '" . $this->db->escape($data['link_price']) . "',"
      . " `tags` = '" . $this->db->escape(json_encode($data['tags'])) . "',"
			. " `attributes` = '" . $this->db->escape(json_encode($data['attributes'])) . "'";

		$this->db->query($sql);
		
		$supplier_id = $this->db->getLastId();
		
		if ($supplier_id) {
			return $supplier_id;
		}
		
		return false;
	}
	
	public function supplierEdit($data) {
		$sql = "UPDATE `" . DB_PREFIX . "nix_suppliers` SET"
      . " `name` = '" . $this->db->escape($data['name']) . "',"
			. " `markup` = '" . (float)$data['markup'] . "',"
      . " `link_price` = '" . $this->db->escape($data['link_price']) . "',"
      . " `tags` = '" . $this->db->escape(json_encode($data['tags'])) . "',"
      . " `attributes` = '" . $this->db->escape(json_encode($data['attributes'])) . "'"
			. " WHERE `supplier_id` = '" . (int)$data['supplier_id'] . "'";

		$query = $this->db->query($sql);

		return $query;
	}
	
	public function supplierMarkup($supplier_id) {
		$sql = "SELECT `markup` FROM `" . DB_PREFIX . "nix_suppliers` WHERE `supplier_id` = '" . (int)$supplier_id . "'";

		$query = $this->db->query($sql);	
		
		if ($query->row) {
			return (float)$query->row['markup'];
		}
		
		return false;
	}
	
	public function supplierList() {
		$sql = "SELECT * FROM `" . DB_PREFIX . "nix_suppliers` ORDER BY `supplier_id` ASC";

		$query = $this->db->query($sql);
		
		$suppliers = [];
		
		if ($query->num_rows > 0) {
			foreach ($query->rows as $row) {
				$suppliers[$row['supplier_id']] = $row;
				$suppliers[$row['supplier_id']]['tags'] = json_decode($row['tags'], true);
				$suppliers[$row['supplier_id']]['attributes'] = json_decode($row['attributes'], true);
			}
		}

		return $suppliers;
	}
	
	public function supplierGet($supplier_id) {
		$res = [];
		
		$sql = "SELECT * FROM `" . DB_PREFIX . "nix_suppliers` WHERE `supplier_id` = '" . (int)$supplier_id . "'";

		$query = $this->db->query($sql);
		
		
		if ($query->row) {
			$res = $query->row;
			$res['tags'] = json_decode($query->row['tags'], true);
			$res['attributes'] = json_decode($query->row['attributes'], true);
		}
		
		return $res;
	}
	
	public function supplierDelete($supplier_id) {
		$sql = "DELETE FROM `" . DB_PREFIX . "nix_suppliers` WHERE `supplier_id` = '" . (int)$supplier_id . "'";

		$query = $this->db->query($sql);
		
		return $query;
	}
	
	
	
	
	
	/* ------------------------------------------------------------------------
	  IMPORT
	------------------------------------------------------------------------- */

	/** Создаем новую категорию
	 *
	 */
	public function addCategory($data) {
		$this->stdelog->write(2, 'model->addCategory() is called');

		$language_id = $this->request->post['language_id'];

		//пишем категорию
		$this->db->query("INSERT INTO " . DB_PREFIX . "category SET nix_supplier_id = " . (int)$data['nix_supplier_id'] . ", nix_supplier_category_id = " . (int)$data['nix_supplier_category_id'] . ", parent_id = '" . (int)$data['parent_id'] . "', `top` = '" . (isset($data['top']) ? (int)$data['top'] : 0) . "', `column` = '" . (int)$data['column'] . "', sort_order = '" . (int)$data['sort_order'] . "', status = '" . (int)$data['status'] . "', date_modified = NOW(), date_added = NOW()");

		$category_id = $this->db->getLastId();

		if (isset($data['image'])) {
			$this->db->query("UPDATE " . DB_PREFIX . "category SET image = '" . $this->db->escape($data['image']) . "' WHERE category_id = '" . (int)$category_id . "'");
		}
		
		foreach ($data['category_description'] as $language_id => $value) {
			$sql = "INSERT INTO " . DB_PREFIX . "category_description SET category_id = '" . (int)$category_id . "', language_id = '" . (int)$language_id . "'";
			
			if (isset($value['name'])) $sql .= ", name = '" . $this->db->escape($value['name']) . "'";			
			if (isset($value['description'])) $sql .= ", description = '" . $this->db->escape($value['description']) . "'";			
			if (isset($value['meta_title'])) $sql .= ", meta_title = '" . $this->db->escape($value['meta_title']) . "'";			
			if (isset($value['meta_h1']) || isset($value['h1'])) {
				if ($this->session->data['nix']['exist_field_meta_h1']) {
					$sql .= ", meta_h1 = '" . $this->db->escape($value['meta_h1']) . "'";
				} elseif($this->session->data['nix']['exist_field_h1']) {
					$sql .= ", h1 = '" . $this->db->escape($value['h1']) . "'";
				}
			}
			if (isset($value['meta_description'])) $sql .= ", meta_description = '" . $this->db->escape($value['meta_description']) . "'";			
			if (isset($value['meta_keyword'])) $sql .= ", meta_keyword = '" . $this->db->escape($value['meta_keyword']) . "'";
	
			$this->db->query($sql);
		}
		
		// MySQL Hierarchical Data Closure Table Pattern
		$level = 0;

		$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "category_path` WHERE category_id = '" . (int)$data['parent_id'] . "' ORDER BY `level` ASC");

		foreach ($query->rows as $result) {
			$this->db->query("INSERT INTO `" . DB_PREFIX . "category_path` SET `category_id` = '" . (int)$category_id . "', `path_id` = '" . (int)$result['path_id'] . "', `level` = '" . (int)$level . "'");

			$level++;
		}

		$this->db->query("INSERT INTO `" . DB_PREFIX . "category_path` SET `category_id` = '" . (int)$category_id . "', `path_id` = '" . (int)$category_id . "', `level` = '" . (int)$level . "'");

		if (isset($data['category_filter'])) {
			foreach ($data['category_filter'] as $filter_id) {
				$this->db->query("INSERT INTO " . DB_PREFIX . "category_filter SET category_id = '" . (int)$category_id . "', filter_id = '" . (int)$filter_id . "'");
			}
		}

		if (isset($data['category_store'])) {
			foreach ($data['category_store'] as $store_id) {
				$this->db->query("INSERT INTO " . DB_PREFIX . "category_to_store SET category_id = '" . (int)$category_id . "', store_id = '" . (int)$store_id . "'");
			}
		}

		// Set which layout to use with this category
		if (isset($data['category_layout'])) {
			foreach ($data['category_layout'] as $store_id => $layout_id) {
				$this->db->query("INSERT INTO " . DB_PREFIX . "category_to_layout SET category_id = '" . (int)$category_id . "', store_id = '" . (int)$store_id . "', layout_id = '" . (int)$layout_id . "'");
			}
		}

		if (isset($data['category_seo_url'])) {
			foreach ($data['category_seo_url'] as $store_id => $language) {
				foreach ($language as $language_id => $keyword) {
					if (!empty($keyword)) {						
						$this->db->query("INSERT INTO " . DB_PREFIX . "seo_url SET store_id = '" . (int)$store_id . "', language_id = '" . (int)$language_id . "', query = 'category_id=" . (int)$category_id . "', keyword = '" . $this->db->escape(trim($keyword)) . "'");
					}
				}
			}
		}

		$this->cache->delete('category');
		
		if($this->config->get('config_seo_pro')){		
			$this->cache->delete('seopro');
		}

		return $category_id;
	}

	/** Меняем категорию
	 *
	 */
	public function editCategory($category_id, $data) {
		$language_id = $this->request->post['language_id'];

		$this->db->query("UPDATE " . DB_PREFIX . "category SET parent_id = '" . (int)$data['parent_id'] . "', `top` = '" . (isset($data['top']) ? (int)$data['top'] : 0) . "', `column` = '" . (int)$data['column'] . "', sort_order = '" . (int)$data['sort_order'] . "', status = '" . (int)$data['status'] . "', date_modified = NOW() WHERE category_id = '" . (int)$category_id . "'");

		if (isset($data['image'])) {
			$this->db->query("UPDATE " . DB_PREFIX . "category SET image = '" . $this->db->escape($data['image']) . "' WHERE category_id = '" . (int)$category_id . "'");
		}

		$this->db->query("DELETE FROM " . DB_PREFIX . "category_description WHERE category_id = '" . (int)$category_id . "'");
		
		foreach ($data['category_description'] as $language_id => $value) {
			$sql = "INSERT INTO " . DB_PREFIX . "category_description SET category_id = '" . (int)$category_id . "', language_id = '" . (int)$language_id . "'";
			
			if (isset($value['name'])) $sql .= ", name = '" . $this->db->escape($value['name']) . "'";			
			if (isset($value['description'])) $sql .= ", description = '" . $this->db->escape($value['description']) . "'";			
			if (isset($value['meta_title'])) $sql .= ", meta_title = '" . $this->db->escape($value['meta_title']) . "'";			
			if (isset($value['meta_h1']) || isset($value['h1'])) {
				if ($this->session->data['nix']['exist_field_meta_h1']) {
					$sql .= ", meta_h1 = '" . $this->db->escape($value['meta_h1']) . "'";
				} elseif($this->session->data['nix']['exist_field_h1']) {
					$sql .= ", h1 = '" . $this->db->escape($value['h1']) . "'";
				}
			}	
			if (isset($value['meta_description'])) $sql .= ", meta_description = '" . $this->db->escape($value['meta_description']) . "'";			
			if (isset($value['meta_keyword'])) $sql .= ", meta_keyword = '" . $this->db->escape($value['meta_keyword']) . "'";
	
			$this->db->query($sql);
		}

		// MySQL Hierarchical Data Closure Table Pattern
		$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "category_path` WHERE path_id = '" . (int)$category_id . "' ORDER BY level ASC");

		if ($query->rows) {
			foreach ($query->rows as $category_path) {
				// Delete the path below the current one
				$this->db->query("DELETE FROM `" . DB_PREFIX . "category_path` WHERE category_id = '" . (int)$category_path['category_id'] . "' AND level < '" . (int)$category_path['level'] . "'");

				$path = array();

				// Get the nodes new parents
				$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "category_path` WHERE category_id = '" . (int)$data['parent_id'] . "' ORDER BY level ASC");

				foreach ($query->rows as $result) {
					$path[] = $result['path_id'];
				}

				// Get whats left of the nodes current path
				$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "category_path` WHERE category_id = '" . (int)$category_path['category_id'] . "' ORDER BY level ASC");

				foreach ($query->rows as $result) {
					$path[] = $result['path_id'];
				}

				// Combine the paths with a new level
				$level = 0;

				foreach ($path as $path_id) {
					$this->db->query("REPLACE INTO `" . DB_PREFIX . "category_path` SET category_id = '" . (int)$category_path['category_id'] . "', `path_id` = '" . (int)$path_id . "', level = '" . (int)$level . "'");

					$level++;
				}
			}
		} else {
			// Delete the path below the current one
			$this->db->query("DELETE FROM `" . DB_PREFIX . "category_path` WHERE category_id = '" . (int)$category_id . "'");

			// Fix for records with no paths
			$level = 0;

			$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "category_path` WHERE category_id = '" . (int)$data['parent_id'] . "' ORDER BY level ASC");

			foreach ($query->rows as $result) {
				$this->db->query("INSERT INTO `" . DB_PREFIX . "category_path` SET category_id = '" . (int)$category_id . "', `path_id` = '" . (int)$result['path_id'] . "', level = '" . (int)$level . "'");

				$level++;
			}

			$this->db->query("REPLACE INTO `" . DB_PREFIX . "category_path` SET category_id = '" . (int)$category_id . "', `path_id` = '" . (int)$category_id . "', level = '" . (int)$level . "'");
		}

		$this->db->query("DELETE FROM " . DB_PREFIX . "category_filter WHERE category_id = '" . (int)$category_id . "'");

		if (isset($data['category_filter'])) {
			foreach ($data['category_filter'] as $filter_id) {
				$this->db->query("INSERT INTO " . DB_PREFIX . "category_filter SET category_id = '" . (int)$category_id . "', filter_id = '" . (int)$filter_id . "'");
			}
		}

		$this->db->query("DELETE FROM " . DB_PREFIX . "category_to_store WHERE category_id = '" . (int)$category_id . "'");

		if (isset($data['category_store'])) {
			foreach ($data['category_store'] as $store_id) {
				$this->db->query("INSERT INTO " . DB_PREFIX . "category_to_store SET category_id = '" . (int)$category_id . "', store_id = '" . (int)$store_id . "'");
			}
		}

		$this->db->query("DELETE FROM " . DB_PREFIX . "category_to_layout WHERE category_id = '" . (int)$category_id . "'");

		if (isset($data['category_layout'])) {
			foreach ($data['category_layout'] as $store_id => $layout_id) {
				$this->db->query("INSERT INTO " . DB_PREFIX . "category_to_layout SET category_id = '" . (int)$category_id . "', store_id = '" . (int)$store_id . "', layout_id = '" . (int)$layout_id . "'");
			}
		}
		
		if (isset($data['category_seo_url'])) {
			
			$this->db->query("DELETE FROM `" . DB_PREFIX . "seo_url` WHERE query = 'category_id=" . (int)$category_id . "'");
			
			foreach ($data['category_seo_url'] as $store_id => $language) {
				foreach ($language as $language_id => $keyword) {
					if (!empty($keyword)) {
						$this->db->query("INSERT INTO " . DB_PREFIX . "seo_url SET store_id = '" . (int)$store_id . "', language_id = '" . (int)$language_id . "', query = 'category_id=" . (int)$category_id . "', keyword = '" . $this->db->escape(trim($keyword)) . "'");
					}
				}
			}
		}

		$this->cache->delete('category');
		
		if($this->config->get('config_seo_pro')){		
			$this->cache->delete('seopro');
		}
	}

	/** Получаем категорию по названию
	 * @param $name
	 * @return mixed
	 */
	public function getCategory($name) {
		$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "category` `c` "
			. " LEFT JOIN `" . DB_PREFIX . "category_description` `cd` ON (c.category_id = cd.category_id)"
			. "WHERE `cd`.`language_id` = '" . (int)$this->request->post['language_id'] . "' "
			. "AND `c`.`nix_supplier_id` = '" . (int)$this->request->post['supplier_id'] . "'"
			. "AND `cd`.`name` = '" . $this->db->escape($name) . "'");
		
		return $query->row;
	}

	// PRODUCT
	//------------------------------------------------------------------------------------------------------------------
	//------------------------------------------------------------------------------------------------------------------

	/** Получаем товар по названию
	 * @param $filter
	 * @return mixed
	 */
	public function getProduct($filter) {
		$this->stdelog->write(3, 'model::getProduct() is called');
		
//		$query = $this->db->query("SELECT DISTINCT *, (SELECT keyword FROM " . DB_PREFIX . "url_alias WHERE query = 'product_id=" . (int)$product_id . "' LIMIT 1) AS keyword FROM " . DB_PREFIX . "product p LEFT JOIN " . DB_PREFIX . "product_description pd ON (p.product_id = pd.product_id) WHERE p.product_id = '" . (int)$product_id . "' AND pd.language_id = '" . (int)$this->config->get('config_language_id') . "'");
		
		$sql = "SELECT DISTINCT * FROM " . DB_PREFIX . "product p"
			. " LEFT JOIN " . DB_PREFIX . "product_description pd ON (p.product_id = pd.product_id)"
			. " WHERE"
//			. " pd.name = '" . $this->db->escape($filter['name']) . "'"
//			. " AND pd.language_id = '" . (int)$this->request->post['language_id'] . "'"
//			. " AND p.model = '" . $this->db->escape($filter['model']) . "'"
//			. " AND p.sku = '" . $this->db->escape($filter['sku']) . "'"
			. " p.nix_supplier_id = '" . (int)$filter['nix_supplier_id'] . "'"
			. " AND p.nix_supplier_product_id = '" . (int)$filter['nix_supplier_product_id'] . "'";
		
		$this->stdelog->write(4, $sql, 'model::getProduct() :: $sql');
		
		$query = $this->db->query($sql);
		
		return $query->row;
	}

	/** Создаем новый товар
	 * @param $data
	 * @return mixed
	 */
	public function addProduct($data) {
		$this->stdelog->write(3, 'model::addProduct() is called');
		
		$language_id = $this->request->post['language_id'];

		$sql = "INSERT INTO " . DB_PREFIX . "product SET"
			. " nix_supplier_id = '" . (int)$data['nix_supplier_id'] . "',"
			. " nix_supplier_product_id = '" . (int)$data['nix_supplier_product_id'] . "',"
			. " model = '" . $this->db->escape($data['model']) . "',"
			. " sku = '" . $this->db->escape($data['sku']) . "',"
			. " upc = '" . $this->db->escape($data['upc']) . "',"
			. " ean = '" . $this->db->escape($data['ean']) . "',"
			. " jan = '" . $this->db->escape($data['jan']) . "',"
			. " isbn = '" . $this->db->escape($data['isbn']) . "',"
			. " mpn = '" . $this->db->escape($data['mpn']) . "',"
			. " location = '" . $this->db->escape($data['location']) . "',"
			. " quantity = '" . (int)$data['quantity'] . "',"
			. " minimum = '" . (int)$data['minimum'] . "',"
			. " subtract = '" . (int)$data['subtract'] . "',"
			. " stock_status_id = '" . (int)$data['stock_status_id'] . "',"
			. " date_available = '" . $this->db->escape($data['date_available']) . "',"
			. " manufacturer_id = '" . (int)$data['manufacturer_id'] . "',"
			. " shipping = '" . (int)$data['shipping'] . "',"
			. " price = '" . (float)$data['price'] . "'," 
			. (isset($data['price_purchasing']) ? " price_purchasing = '" . (float)$data['price_purchasing'] . "', " : '') 
			. (isset($data['price_rrp']) ? " price_rrp = '" . (float)$data['price_rrp'] . "', " : '')
			. " points = '" . (int)$data['points'] . "',"
			. " weight = '" . (float) $data['weight'] . "',"
			. " weight_class_id = '" . (int)$data['weight_class_id'] . "',"
			. " length = '" . (float) $data['length'] . "',"
			. " width = '" . (float) $data['width'] . "',"
			. " height = '" . (float) $data['height'] . "',"
			. " length_class_id = '" . (int)$data['length_class_id'] . "',"
			. " status = '" . (int)$data['status'] . "',"
			. " tax_class_id = '" . (int)$data['tax_class_id'] . "',"
			. " sort_order = '" . (int)$data['sort_order'] . "',"
			. " date_added = NOW()";
		
		$this->db->query($sql);
		
		$this->stdelog->write(4, $sql, 'model::addProduct() :: $sql');

		$product_id = $this->db->getLastId();

		if (isset($data['image'])) {
			$this->db->query("UPDATE " . DB_PREFIX . "product SET image = '" . $this->db->escape($data['image']) . "' WHERE product_id = '" . (int)$product_id . "'");
		}

		// $this->db->query("INSERT INTO " . DB_PREFIX . "product_description SET product_id = '" . (int)$product_id . "', language_id = '" . (int)$language_id . "', name = '" . $this->db->escape($data['name']) . "', description = '" . $this->db->escape($data['description']) . "', tag = '" . $this->db->escape($data['tag']) . "', meta_title = '" . $this->db->escape($data['meta_title']) . "', meta_h1 = '" . $this->db->escape($data['meta_h1']) . "', meta_description = '" . $this->db->escape($data['meta_description']) . "', meta_keyword = '" . $this->db->escape($data['meta_keyword']) . "'");
		
		foreach ($data['product_description'] as $language_id => $value) {
			$sql = "INSERT INTO " . DB_PREFIX . "product_description SET product_id = '" . (int)$product_id . "', language_id = '" . (int)$language_id . "'";
			
			if (isset($value['name'])) $sql .= ", name = '" . $this->db->escape($value['name']) . "'";			
			if (isset($value['description'])) $sql .= ", description = '" . $this->db->escape($value['description']) . "'";			
			if (isset($value['tag'])) $sql .= ", tag = '" . $this->db->escape($value['tag']) . "'";			
			if (isset($value['meta_title'])) $sql .= ", meta_title = '" . $this->db->escape($value['meta_title']) . "'";	
			if (isset($value['meta_h1']) || isset($value['h1'])) {
				if ($this->session->data['nix']['exist_field_meta_h1']) {
					$sql .= ", meta_h1 = '" . $this->db->escape($value['meta_h1']) . "'";
				} elseif($this->session->data['nix']['exist_field_h1']) {
					$sql .= ", h1 = '" . $this->db->escape($value['h1']) . "'";
				}
			}
			if (isset($value['meta_description'])) $sql .= ", meta_description = '" . $this->db->escape($value['meta_description']) . "'";			
			if (isset($value['meta_keyword'])) $sql .= ", meta_keyword = '" . $this->db->escape($value['meta_keyword']) . "'";
	
			$this->db->query($sql);
		}

		$this->db->query("INSERT INTO " . DB_PREFIX . "product_to_store SET product_id = '" . (int)$product_id . "', store_id = '0'");

		if (isset($data['product_attribute'])) {
			foreach ($data['product_attribute'] as $product_attribute) {
				if ($product_attribute['attribute_id']) {
					foreach ($product_attribute['product_attribute_description'] as $language_id => $product_attribute_description) {
						$this->db->query("DELETE FROM " . DB_PREFIX . "product_attribute WHERE product_id = '" . (int)$product_id . "' AND attribute_id = '" . (int)$product_attribute['attribute_id'] . "' AND language_id = '" . (int)$language_id . "'");

						$this->db->query("INSERT INTO " . DB_PREFIX . "product_attribute SET product_id = '" . (int)$product_id . "', attribute_id = '" . (int)$product_attribute['attribute_id'] . "', language_id = '" . (int)$language_id . "', text = '" .  $this->db->escape($product_attribute_description['text']) . "'");
					}
				}
			}
		}

		if (isset($data['product_option'])) {
			foreach ($data['product_option'] as $product_option) {
				if ($product_option['type'] == 'select' || $product_option['type'] == 'radio' || $product_option['type'] == 'checkbox' || $product_option['type'] == 'image') {
					if (isset($product_option['product_option_value'])) {
						$this->db->query("INSERT INTO " . DB_PREFIX . "product_option SET product_id = '" . (int)$product_id . "', option_id = '" . (int)$product_option['option_id'] . "', required = '" . (int)$product_option['required'] . "'");

						$product_option_id = $this->db->getLastId();

						foreach ($product_option['product_option_value'] as $product_option_value) {
							$this->db->query("INSERT INTO " . DB_PREFIX . "product_option_value SET product_option_id = '" . (int)$product_option_id . "', product_id = '" . (int)$product_id . "', option_id = '" . (int)$product_option['option_id'] . "', option_value_id = '" . (int)$product_option_value['option_value_id'] . "', quantity = '" . (int)$product_option_value['quantity'] . "', subtract = '" . (int)$product_option_value['subtract'] . "', price = '" . (float) $product_option_value['price'] . "', price_prefix = '" . $this->db->escape($product_option_value['price_prefix']) . "', points = '" . (int)$product_option_value['points'] . "', points_prefix = '" . $this->db->escape($product_option_value['points_prefix']) . "', weight = '" . (float) $product_option_value['weight'] . "', weight_prefix = '" . $this->db->escape($product_option_value['weight_prefix']) . "'");
						}
					}
				} else {
					$this->db->query("INSERT INTO " . DB_PREFIX . "product_option SET product_id = '" . (int)$product_id . "', option_id = '" . (int)$product_option['option_id'] . "', value = '" . $this->db->escape($product_option['value']) . "', required = '" . (int)$product_option['required'] . "'");
				}
			}
		}

		if (isset($data['product_discount'])) {
			foreach ($data['product_discount'] as $product_discount) {
				$this->db->query("INSERT INTO " . DB_PREFIX . "product_discount SET product_id = '" . (int)$product_id . "', customer_group_id = '" . (int)$product_discount['customer_group_id'] . "', quantity = '" . (int)$product_discount['quantity'] . "', priority = '" . (int)$product_discount['priority'] . "', price = '" . (float) $product_discount['price'] . "', date_start = '" . $this->db->escape($product_discount['date_start']) . "', date_end = '" . $this->db->escape($product_discount['date_end']) . "'");
			}
		}

		if (isset($data['product_special'])) {
			foreach ($data['product_special'] as $product_special) {
				$this->db->query("INSERT INTO " . DB_PREFIX . "product_special SET product_id = '" . (int)$product_id . "', customer_group_id = '" . (int)$product_special['customer_group_id'] . "', priority = '" . (int)$product_special['priority'] . "', price = '" . (float) $product_special['price'] . "', date_start = '" . $this->db->escape($product_special['date_start']) . "', date_end = '" . $this->db->escape($product_special['date_end']) . "'");
			}
		}

		if (isset($data['product_image'])) {
			foreach ($data['product_image'] as $product_image) {
				$this->db->query("INSERT INTO " . DB_PREFIX . "product_image SET product_id = '" . (int)$product_id . "', image = '" . $this->db->escape($product_image['image']) . "', sort_order = '" . (int)$product_image['sort_order'] . "'");
			}
		}

		if (isset($data['product_download'])) {
			foreach ($data['product_download'] as $download_id) {
				$this->db->query("INSERT INTO " . DB_PREFIX . "product_to_download SET product_id = '" . (int)$product_id . "', download_id = '" . (int)$download_id . "'");
			}
		}

		if (isset($data['product_category'])) {
			foreach ($data['product_category'] as $category_id) {
				$this->db->query("INSERT INTO " . DB_PREFIX . "product_to_category SET product_id = '" . (int)$product_id . "', category_id = '" . (int)$category_id . "'");
			}
		}

		if ($this->session->data['nix']['exist_field_main_category']) {
			if (isset($data['main_category_id']) && $data['main_category_id'] > 0) {
				$this->db->query("DELETE FROM " . DB_PREFIX . "product_to_category WHERE product_id = '" . (int)$product_id . "' AND category_id = '" . (int)$data['main_category_id'] . "'");
				$this->db->query("INSERT INTO " . DB_PREFIX . "product_to_category SET product_id = '" . (int)$product_id . "', category_id = '" . (int)$data['main_category_id'] . "', main_category = 1");
			} elseif (isset($data['product_category'][0])) {
				$this->db->query("UPDATE " . DB_PREFIX . "product_to_category SET main_category = 1 WHERE product_id = '" . (int)$product_id . "' AND category_id = '" . (int)$data['product_category'][0] . "'");
			}
		}

		if (isset($data['product_filter'])) {
			foreach ($data['product_filter'] as $filter_id) {
				$this->db->query("INSERT INTO " . DB_PREFIX . "product_filter SET product_id = '" . (int)$product_id . "', filter_id = '" . (int)$filter_id . "'");
			}
		}

		if (isset($data['product_related'])) {
			foreach ($data['product_related'] as $related_id) {
				$this->db->query("DELETE FROM " . DB_PREFIX . "product_related WHERE product_id = '" . (int)$product_id . "' AND related_id = '" . (int)$related_id . "'");
				$this->db->query("INSERT INTO " . DB_PREFIX . "product_related SET product_id = '" . (int)$product_id . "', related_id = '" . (int)$related_id . "'");
				$this->db->query("DELETE FROM " . DB_PREFIX . "product_related WHERE product_id = '" . (int)$related_id . "' AND related_id = '" . (int)$product_id . "'");
				$this->db->query("INSERT INTO " . DB_PREFIX . "product_related SET product_id = '" . (int)$related_id . "', related_id = '" . (int)$product_id . "'");
			}
		}

		if (isset($data['product_reward'])) {
			foreach ($data['product_reward'] as $customer_group_id => $product_reward) {
				if ((int)$product_reward['points'] > 0) {
					$this->db->query("INSERT INTO " . DB_PREFIX . "product_reward SET product_id = '" . (int)$product_id . "', customer_group_id = '" . (int)$customer_group_id . "', points = '" . (int)$product_reward['points'] . "'");
				}
			}
		}

		if (isset($data['product_layout'])) {
			foreach ($data['product_layout'] as $store_id => $layout_id) {
				$this->db->query("INSERT INTO " . DB_PREFIX . "product_to_layout SET product_id = '" . (int)$product_id . "', store_id = '" . (int)$store_id . "', layout_id = '" . (int)$layout_id . "'");
			}
		}
		
		$this->db->query("DELETE FROM " . DB_PREFIX . "seo_url WHERE query = 'product_id=" . (int)$product_id . "'");
		
		if (isset($data['product_seo_url'])) {
			foreach ($data['product_seo_url']as $store_id => $language) {
				foreach ($language as $language_id => $keyword) {
					if (!empty($keyword)) {
						$keyword = $product_id . '-' . $keyword;
						$this->db->query("INSERT INTO " . DB_PREFIX . "seo_url SET store_id = '" . (int)$store_id . "', language_id = '" . (int)$language_id . "', query = 'product_id=" . (int)$product_id . "', keyword = '" . $this->db->escape(trim($keyword)) . "'");
					}
				}
			}
		}

		if (isset($data['product_seo_url'])) {
			
			$this->db->query("DELETE FROM " . DB_PREFIX . "seo_url WHERE query = 'product_id=" . (int)$product_id . "'");
			
			foreach ($data['product_seo_url']as $store_id => $language) {
				foreach ($language as $language_id => $keyword) {
					if (!empty($keyword)) {
						$keyword = $product_id . '-' . $keyword;
						$this->db->query("INSERT INTO " . DB_PREFIX . "seo_url SET store_id = '" . (int)$store_id . "', language_id = '" . (int)$language_id . "', query = 'product_id=" . (int)$product_id . "', keyword = '" . $this->db->escape(trim($keyword)) . "'");
					}
				}
			}
		}

		if (isset($data['product_recurring'])) {
			foreach ($data['product_recurring'] as $recurring) {
				$this->db->query("INSERT INTO `" . DB_PREFIX . "product_recurring` SET `product_id` = " . (int)$product_id . ", customer_group_id = " . (int)$recurring['customer_group_id'] . ", `recurring_id` = " . (int)$recurring['recurring_id']);
			}
		}

		$this->cache->delete('product');

		if ($this->config->get('config_seo_pro')) {
			$this->cache->delete('seopro');
		}

		return $product_id;
	}

	/** Обновляем товар
	 * @param $product_id
	 * @param $data
	 * @return string
	 */
	public function editProduct($product_id, $data) {
		$this->stdelog->write(3, 'model::editProduct() is called');
		
		$language_id = $this->request->post['language_id'];

		$sql = "UPDATE " . DB_PREFIX . "product SET"
			. " model = '" . $this->db->escape($data['model']) . "',"
			. " sku = '" . $this->db->escape($data['sku']) . "',"
			. " upc = '" . $this->db->escape($data['upc']) . "',"
			. " ean = '" . $this->db->escape($data['ean']) . "',"
			. " jan = '" . $this->db->escape($data['jan']) . "',"
			. " isbn = '" . $this->db->escape($data['isbn']) . "',"
			. " mpn = '" . $this->db->escape($data['mpn']) . "',"
			. " location = '" . $this->db->escape($data['location']) . "',"
			. " quantity = '" . (int)$data['quantity'] . "',"
			. " minimum = '" . (int)$data['minimum'] . "',"
			. " subtract = '" . (int)$data['subtract'] . "',"
			. " stock_status_id = '" . (int)$data['stock_status_id'] . "',"
			. " date_available = '" . $this->db->escape($data['date_available']) . "',"
			. " manufacturer_id = '" . (int)$data['manufacturer_id'] . "',"
			. " shipping = '" . (int)$data['shipping'] . "',"
			. " price = '" . (float)$data['price'] . "'," 
			. (isset($data['price_purchasing']) ? " price_purchasing = '" . (float)$data['price_purchasing'] . "', " : '') 
			. (isset($data['price_rrp']) ? " price_rrp = '" . (float)$data['price_rrp'] . "', " : '') 
			. " points = '" . (int)$data['points'] . "',"
			. " weight = '" . (float) $data['weight'] . "',"
			. " weight_class_id = '" . (int)$data['weight_class_id'] . "',"
			. " length = '" . (float) $data['length'] . "',"
			. " width = '" . (float) $data['width'] . "',"
			. " height = '" . (float) $data['height'] . "',"
			. " length_class_id = '" . (int)$data['length_class_id'] . "',"
			. " status = '" . (int)$data['status'] . "',"
			. " tax_class_id = '" . (int)$data['tax_class_id'] . "',"
			. " sort_order = '" . (int)$data['sort_order'] . "',"
			. " date_modified = NOW()"
			. " WHERE product_id = '" . (int)$product_id . "'";
		
		$this->stdelog->write(4, $sql, 'model::editProduct() :: $sql');
		
		$this->db->query($sql);		

		if (isset($data['image'])) {
			$this->db->query("UPDATE " . DB_PREFIX . "product SET image = '" . $this->db->escape($data['image']) . "' WHERE product_id = '" . (int)$product_id . "'");
		}

		$this->db->query("DELETE FROM " . DB_PREFIX . "product_description WHERE product_id = '" . (int)$product_id . "'");
		

		//$this->db->query("INSERT INTO " . DB_PREFIX . "product_description SET product_id = '" . (int)$product_id . "', language_id = '" . (int)$language_id . "', name = '" . $this->db->escape($data['name']) . "', description = '" . $this->db->escape($data['description']) . "', tag = '" . $this->db->escape($data['tag']) . "', meta_title = '" . $this->db->escape($data['meta_title']) . "', meta_h1 = '" . $this->db->escape($data['meta_h1']) . "', meta_description = '" . $this->db->escape($data['meta_description']) . "', meta_keyword = '" . $this->db->escape($data['meta_keyword']) . "'");
		
		foreach ($data['product_description'] as $language_id => $value) {	
			$sql = "INSERT INTO " . DB_PREFIX . "product_description SET product_id = '" . (int)$product_id . "', language_id = '" . (int)$language_id . "'";
			
			if (isset($value['name'])) $sql .= ", name = '" . $this->db->escape($value['name']) . "'";			
			if (isset($value['description'])) $sql .= ", description = '" . $this->db->escape($value['description']) . "'";			
			if (isset($value['tag'])) $sql .= ", tag = '" . $this->db->escape($value['tag']) . "'";			
			if (isset($value['meta_title'])) $sql .= ", meta_title = '" . $this->db->escape($value['meta_title']) . "'";
			if (isset($value['meta_h1']) || isset($value['h1'])) {
				if ($this->session->data['nix']['exist_field_meta_h1']) {
					$sql .= ", meta_h1 = '" . $this->db->escape($value['meta_h1']) . "'";
				} elseif($this->session->data['nix']['exist_field_h1']) {
					$sql .= ", h1 = '" . $this->db->escape($value['h1']) . "'";
				}
			}
			if (isset($value['meta_description'])) $sql .= ", meta_description = '" . $this->db->escape($value['meta_description']) . "'";			
			if (isset($value['meta_keyword'])) $sql .= ", meta_keyword = '" . $this->db->escape($value['meta_keyword']) . "'";
	
			$this->db->query($sql);
		}

		$this->db->query("DELETE FROM " . DB_PREFIX . "product_to_store WHERE product_id = '" . (int)$product_id . "'");

		$this->db->query("INSERT INTO " . DB_PREFIX . "product_to_store SET product_id = '" . (int)$product_id . "', store_id = '0'");

		$this->db->query("DELETE FROM " . DB_PREFIX . "product_attribute WHERE product_id = '" . (int)$product_id . "'");

		if (isset($data['product_attribute'])) {
			foreach ($data['product_attribute'] as $product_attribute) {
				if ($product_attribute['attribute_id']) {
					foreach ($product_attribute['product_attribute_description'] as $language_id => $product_attribute_description) {
						$this->db->query("DELETE FROM " . DB_PREFIX . "product_attribute WHERE product_id = '" . (int)$product_id . "' AND attribute_id = '" . (int)$product_attribute['attribute_id'] . "' AND language_id = '" . (int)$language_id . "'");

						$this->db->query("INSERT INTO " . DB_PREFIX . "product_attribute SET product_id = '" . (int)$product_id . "', attribute_id = '" . (int)$product_attribute['attribute_id'] . "', language_id = '" . (int)$language_id . "', text = '" .  $this->db->escape($product_attribute_description['text']) . "'");
					}
				}
			}
		}

		$this->db->query("DELETE FROM " . DB_PREFIX . "product_option WHERE product_id = '" . (int)$product_id . "'");
		$this->db->query("DELETE FROM " . DB_PREFIX . "product_option_value WHERE product_id = '" . (int)$product_id . "'");

		if (isset($data['product_option'])) {
			foreach ($data['product_option'] as $product_option) {
				if ($product_option['type'] == 'select' || $product_option['type'] == 'radio' || $product_option['type'] == 'checkbox' || $product_option['type'] == 'image') {
					if (isset($product_option['product_option_value'])) {
						$this->db->query("INSERT INTO " . DB_PREFIX . "product_option SET product_option_id = '" . (int)$product_option['product_option_id'] . "', product_id = '" . (int)$product_id . "', option_id = '" . (int)$product_option['option_id'] . "', required = '" . (int)$product_option['required'] . "'");

						$product_option_id = $this->db->getLastId();

						foreach ($product_option['product_option_value'] as $product_option_value) {
							$this->db->query("INSERT INTO " . DB_PREFIX . "product_option_value SET product_option_value_id = '" . (int)$product_option_value['product_option_value_id'] . "', product_option_id = '" . (int)$product_option_id . "', product_id = '" . (int)$product_id . "', option_id = '" . (int)$product_option['option_id'] . "', option_value_id = '" . (int)$product_option_value['option_value_id'] . "', quantity = '" . (int)$product_option_value['quantity'] . "', subtract = '" . (int)$product_option_value['subtract'] . "', price = '" . (float) $product_option_value['price'] . "', price_prefix = '" . $this->db->escape($product_option_value['price_prefix']) . "', points = '" . (int)$product_option_value['points'] . "', points_prefix = '" . $this->db->escape($product_option_value['points_prefix']) . "', weight = '" . (float) $product_option_value['weight'] . "', weight_prefix = '" . $this->db->escape($product_option_value['weight_prefix']) . "'");
						}
					}
				} else {
					$this->db->query("INSERT INTO " . DB_PREFIX . "product_option SET product_option_id = '" . (int)$product_option['product_option_id'] . "', product_id = '" . (int)$product_id . "', option_id = '" . (int)$product_option['option_id'] . "', value = '" . $this->db->escape($product_option['value']) . "', required = '" . (int)$product_option['required'] . "'");
				}
			}
		}

		$this->db->query("DELETE FROM " . DB_PREFIX . "product_discount WHERE product_id = '" . (int)$product_id . "'");

		if (isset($data['product_discount'])) {
			foreach ($data['product_discount'] as $product_discount) {
				$this->db->query("INSERT INTO " . DB_PREFIX . "product_discount SET product_id = '" . (int)$product_id . "', customer_group_id = '" . (int)$product_discount['customer_group_id'] . "', quantity = '" . (int)$product_discount['quantity'] . "', priority = '" . (int)$product_discount['priority'] . "', price = '" . (float) $product_discount['price'] . "', date_start = '" . $this->db->escape($product_discount['date_start']) . "', date_end = '" . $this->db->escape($product_discount['date_end']) . "'");
			}
		}

		$this->db->query("DELETE FROM " . DB_PREFIX . "product_special WHERE product_id = '" . (int)$product_id . "'");

		if (isset($data['product_special'])) {
			foreach ($data['product_special'] as $product_special) {
				$this->db->query("INSERT INTO " . DB_PREFIX . "product_special SET product_id = '" . (int)$product_id . "', customer_group_id = '" . (int)$product_special['customer_group_id'] . "', priority = '" . (int)$product_special['priority'] . "', price = '" . (float) $product_special['price'] . "', date_start = '" . $this->db->escape($product_special['date_start']) . "', date_end = '" . $this->db->escape($product_special['date_end']) . "'");
			}
		}

		$this->db->query("DELETE FROM " . DB_PREFIX . "product_image WHERE product_id = '" . (int)$product_id . "'");

		if (isset($data['product_image'])) {
			foreach ($data['product_image'] as $product_image) {
				$this->db->query("INSERT INTO " . DB_PREFIX . "product_image SET product_id = '" . (int)$product_id . "', image = '" . $this->db->escape($product_image['image']) . "', sort_order = '" . (int)$product_image['sort_order'] . "'");
			}
		}


		$this->db->query("DELETE FROM " . DB_PREFIX . "product_to_download WHERE product_id = '" . (int)$product_id . "'");

		if (isset($data['product_download'])) {
			foreach ($data['product_download'] as $download_id) {
				$this->db->query("INSERT INTO " . DB_PREFIX . "product_to_download SET product_id = '" . (int)$product_id . "', download_id = '" . (int)$download_id . "'");
			}
		}

		$this->db->query("DELETE FROM " . DB_PREFIX . "product_to_category WHERE product_id = '" . (int)$product_id . "'");

		//--------------------------------------------------
		if (isset($data['product_category'])) {
			foreach ($data['product_category'] as $category_id) {
				$this->db->query("INSERT INTO " . DB_PREFIX . "product_to_category SET product_id = '" . (int)$product_id . "', category_id = '" . (int)$category_id . "'");
			}
		}

		if ($this->session->data['nix']['exist_field_main_category']) {
			if (isset($data['main_category_id']) && $data['main_category_id'] > 0) {
				$this->db->query("DELETE FROM " . DB_PREFIX . "product_to_category WHERE product_id = '" . (int)$product_id . "' AND category_id = '" . (int)$data['main_category_id'] . "'");
				$this->db->query("INSERT INTO " . DB_PREFIX . "product_to_category SET product_id = '" . (int)$product_id . "', category_id = '" . (int)$data['main_category_id'] . "', main_category = 1");
			} elseif (isset($data['product_category'][0])) {
				$this->db->query("UPDATE " . DB_PREFIX . "product_to_category SET main_category = 1 WHERE product_id = '" . (int)$product_id . "' AND category_id = '" . (int)$data['product_category'][0] . "'");
			}
		}

		$this->db->query("DELETE FROM " . DB_PREFIX . "product_filter WHERE product_id = '" . (int)$product_id . "'");

		if (isset($data['product_filter'])) {
			foreach ($data['product_filter'] as $filter_id) {
				$this->db->query("INSERT INTO " . DB_PREFIX . "product_filter SET product_id = '" . (int)$product_id . "', filter_id = '" . (int)$filter_id . "'");
			}
		}

		$this->db->query("DELETE FROM " . DB_PREFIX . "product_related WHERE product_id = '" . (int)$product_id . "'");
		$this->db->query("DELETE FROM " . DB_PREFIX . "product_related WHERE related_id = '" . (int)$product_id . "'");

		if (isset($data['product_related'])) {
			foreach ($data['product_related'] as $related_id) {
				$this->db->query("DELETE FROM " . DB_PREFIX . "product_related WHERE product_id = '" . (int)$product_id . "' AND related_id = '" . (int)$related_id . "'");
				$this->db->query("INSERT INTO " . DB_PREFIX . "product_related SET product_id = '" . (int)$product_id . "', related_id = '" . (int)$related_id . "'");
				$this->db->query("DELETE FROM " . DB_PREFIX . "product_related WHERE product_id = '" . (int)$related_id . "' AND related_id = '" . (int)$product_id . "'");
				$this->db->query("INSERT INTO " . DB_PREFIX . "product_related SET product_id = '" . (int)$related_id . "', related_id = '" . (int)$product_id . "'");
			}
		}

		$this->db->query("DELETE FROM " . DB_PREFIX . "product_reward WHERE product_id = '" . (int)$product_id . "'");

		if (isset($data['product_reward'])) {
			foreach ($data['product_reward'] as $customer_group_id => $value) {
				if ((int)$value['points'] > 0) {
					$this->db->query("INSERT INTO " . DB_PREFIX . "product_reward SET product_id = '" . (int)$product_id . "', customer_group_id = '" . (int)$customer_group_id . "', points = '" . (int)$value['points'] . "'");
				}
			}
		}

		$this->db->query("DELETE FROM " . DB_PREFIX . "product_to_layout WHERE product_id = '" . (int)$product_id . "'");

		if (isset($data['product_layout'])) {
			foreach ($data['product_layout'] as $store_id => $layout_id) {
				$this->db->query("INSERT INTO " . DB_PREFIX . "product_to_layout SET product_id = '" . (int)$product_id . "', store_id = '" . (int)$store_id . "', layout_id = '" . (int)$layout_id . "'");
			}
		}

		if (isset($data['product_seo_url'])) {
			
			$this->db->query("DELETE FROM " . DB_PREFIX . "seo_url WHERE query = 'product_id=" . (int)$product_id . "'");
			
			foreach ($data['product_seo_url']as $store_id => $language) {
				foreach ($language as $language_id => $keyword) {
					if (!empty($keyword)) {
						$keyword = $product_id . '-' . $keyword;
						$this->db->query("INSERT INTO " . DB_PREFIX . "seo_url SET store_id = '" . (int)$store_id . "', language_id = '" . (int)$language_id . "', query = 'product_id=" . (int)$product_id . "', keyword = '" . $this->db->escape(trim($keyword)) . "'");
					}
				}
			}
		}

		$this->db->query("DELETE FROM `" . DB_PREFIX . "product_recurring` WHERE product_id = " . (int)$product_id);

		if (isset($data['product_recurring'])) {
			foreach ($data['product_recurring'] as $product_recurring) {
				$this->db->query("INSERT INTO `" . DB_PREFIX . "product_recurring` SET `product_id` = " . (int)$product_id . ", customer_group_id = " . (int)$product_recurring['customer_group_id'] . ", `recurring_id` = " . (int)$product_recurring['recurring_id']);
			}
		}

		$this->cache->delete('product');

		if ($this->config->get('config_seo_pro')) {
			$this->cache->delete('seopro');
		}
	}

	public function updateProductStock($data) {
		$this->db->query("UPDATE " . DB_PREFIX . "product SET"
			. " quantity = '" . (int)$data['remains'] . "',"
			. " price = '" . (float) $data['price'] . "',"
			. " price_purchase = '" . (float) $data['price_purchase'] . "',"
			. " status = '" . (int)$data['status'] . "',"
			. " date_modified = NOW()"
			. " WHERE nix_supplier_product_id = '" . (int)$data['nix_supplier_product_id'] . "'");
	}

	/**
	 * Удаляем товар
	 * @param $product_id
	 */
	public function clearAll() {
		//TRUNCATE `oc_zone`
		$this->db->query("TRUNCATE " . DB_PREFIX . "category");
		$this->db->query("TRUNCATE " . DB_PREFIX . "category_description");
		$this->db->query("TRUNCATE " . DB_PREFIX . "category_filter");
		$this->db->query("TRUNCATE " . DB_PREFIX . "category_path");
		$this->db->query("TRUNCATE " . DB_PREFIX . "category_to_layout");
		$this->db->query("TRUNCATE " . DB_PREFIX . "category_to_store");

		$this->db->query("TRUNCATE " . DB_PREFIX . "product");
		$this->db->query("TRUNCATE " . DB_PREFIX . "product_attribute");
		$this->db->query("TRUNCATE " . DB_PREFIX . "product_description");
		$this->db->query("TRUNCATE " . DB_PREFIX . "product_discount");
		$this->db->query("TRUNCATE " . DB_PREFIX . "product_filter");
		$this->db->query("TRUNCATE " . DB_PREFIX . "product_image");
		$this->db->query("TRUNCATE " . DB_PREFIX . "product_option");
		$this->db->query("TRUNCATE " . DB_PREFIX . "product_option_value");
		$this->db->query("TRUNCATE " . DB_PREFIX . "product_related");
		$this->db->query("TRUNCATE " . DB_PREFIX . "product_reward");
		$this->db->query("TRUNCATE " . DB_PREFIX . "product_special");
		$this->db->query("TRUNCATE " . DB_PREFIX . "product_to_category");
		$this->db->query("TRUNCATE " . DB_PREFIX . "product_to_download");
		$this->db->query("TRUNCATE " . DB_PREFIX . "product_to_layout");
		$this->db->query("TRUNCATE " . DB_PREFIX . "product_to_store");
		$this->db->query("TRUNCATE " . DB_PREFIX . "product_recurring");
		$this->db->query("TRUNCATE " . DB_PREFIX . "seo_url");
		$this->db->query("TRUNCATE " . DB_PREFIX . "coupon_product");

		$this->db->query("TRUNCATE " . DB_PREFIX . "attribute");
		$this->db->query("TRUNCATE " . DB_PREFIX . "attribute_description");
		$this->db->query("TRUNCATE " . DB_PREFIX . "attribute_group");
		$this->db->query("TRUNCATE " . DB_PREFIX . "attribute_group_description");

		$this->db->query("TRUNCATE " . DB_PREFIX . "manufacturer");
		if ($this->session->data['nix']['exist_table_manufacturer_description']) $this->db->query("TRUNCATE " . DB_PREFIX . "manufacturer_description");
		$this->db->query("TRUNCATE " . DB_PREFIX . "manufacturer_to_store");
		
		$this->cache->delete('product');
	}

	// Производитель
	//------------------------------------------------------------------------------------------------------------------
	//------------------------------------------------------------------------------------------------------------------

	public function getManufacturer($name) {
		$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "manufacturer` WHERE name = '" . $this->db->escape($name) . "'");
		return $query->row;
	}

	public function addManufacturer($data) {
		$language_id = $this->request->post['language_id'];

		$this->db->query("INSERT INTO " . DB_PREFIX . "manufacturer SET name = '" . $this->db->escape($data['name']) . "', sort_order = '" . (int)$data['sort_order'] . "'");

		$manufacturer_id = $this->db->getLastId();

		if (isset($data['image'])) {
			$this->db->query("UPDATE " . DB_PREFIX . "manufacturer SET image = '" . $this->db->escape($data['image']) . "' WHERE manufacturer_id = '" . (int)$manufacturer_id . "'");
		}

		// !A
		// ocStore 2.3 ONLY manufacturer_description.name
		if ($this->session->data['nix']['exist_table_manufacturer_description']) {
			$this->db->query("INSERT INTO " . DB_PREFIX . "manufacturer_description SET manufacturer_id = '" . (int)$manufacturer_id . "', language_id = '" . (int)$language_id . "', description = '" . $this->db->escape($data['description']) . "', meta_title = '" . $this->db->escape($data['name']) . "', meta_h1 = '" . $this->db->escape($data['name']) . "', meta_description = '" . $this->db->escape($data['description']) . "', meta_keyword = '" . $this->db->escape($data['meta_keyword']) . "'");
		}

		if (isset($data['manufacturer_store'])) {
			foreach ($data['manufacturer_store'] as $store_id) {
				$this->db->query("INSERT INTO " . DB_PREFIX . "manufacturer_to_store SET manufacturer_id = '" . (int)$manufacturer_id . "', store_id = '" . (int)$store_id . "'");
			}
		}

		if (isset($data['manufacturer_seo_url'])) {
			foreach ($data['manufacturer_seo_url'] as $store_id => $language) {
				foreach ($language as $language_id => $keyword) {
					if (!empty($keyword)) {
						$this->db->query("INSERT INTO " . DB_PREFIX . "seo_url SET store_id = '" . (int)$store_id . "', language_id = '" . (int)$language_id . "', query = 'manufacturer_id=" . (int)$manufacturer_id . "', keyword = '" . $this->db->escape(trim($keyword)) . "'");
					}
				}
			}
		}

		$this->cache->delete('manufacturer');

		return $manufacturer_id;
	}

	// Атрибуты
	//------------------------------------------------------------------------------------------------------------------
	//------------------------------------------------------------------------------------------------------------------

	public function getAttributeGroup($name) {
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "attribute_group_description WHERE name = '" . $name . "' AND language_id = '" . (int)$this->request->post['language_id']. "'");

		return $query->row;
	}

	public function getAttribute($name) {
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "attribute_description WHERE name = '" . $name . "' AND language_id = '" . (int)$this->request->post['language_id']. "'");

		return $query->row;
	}

	public function addAttributeGroup($data) {
		$this->db->query("INSERT INTO " . DB_PREFIX . "attribute_group SET sort_order = 0");

		$attribute_group_id = $this->db->getLastId();

		$this->db->query("DELETE FROM " . DB_PREFIX . "attribute_group_description WHERE attribute_group_id = '" . (int)$attribute_group_id . "'");
		
		foreach ($data['attribute_group_description'] as $language_id => $value) {
			$this->db->query("INSERT INTO " . DB_PREFIX . "attribute_group_description SET attribute_group_id = '" . (int)$attribute_group_id . "', language_id = '" . (int)$language_id . "', name = '" . $this->db->escape($value['name']) . "'");
		}
		
		return $attribute_group_id;
	}

	public function addAttribute($data) {
		$this->db->query("INSERT INTO " . DB_PREFIX . "attribute SET attribute_group_id = '" . (int)$data['attribute_group_id'] . "'");

		$attribute_id = $this->db->getLastId();

		foreach ($data['attribute_description'] as $language_id => $value) {
			$this->db->query("INSERT INTO " . DB_PREFIX . "attribute_description SET attribute_id = '" . (int)$attribute_id . "', language_id = '" . (int)$language_id . "', name = '" . $this->db->escape($value['name']) . "'");
		}

		return $attribute_id;
	}

	public function helperExistFieldMetaH1() {
		$query = $this->db->query("SHOW COLUMNS FROM `" . DB_PREFIX . "product` WHERE `Field` = 'meta_h1'");
		
		return ($query->num_rows < 1 ? false : true);
	}
	
	public function helperExistFieldH1() {
		$query = $this->db->query("SHOW COLUMNS FROM `" . DB_PREFIX . "product` WHERE `Field` = 'h1'");
		
		return ($query->num_rows < 1 ? false : true);
	}
	
	public function helperExistFieldMainCategory() {
		$query = $this->db->query("SHOW COLUMNS FROM `" . DB_PREFIX . "product_to_category` WHERE `Field` = 'main_category'");
		
		return ($query->num_rows < 1 ? false : true);
	}
	
	public function helperExistTableManufacturerDescription() {
		$query = $this->db->query("SHOW TABLES LIKE '" . DB_PREFIX . "oc_manufacturer_description'");
		
		return ($query->num_rows < 1 ? false : true);
	}
	
}
