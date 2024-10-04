<?php

/**
 * @category   OpenCart
 * @package    Nice Import XML
 * @copyright  © Serge Tkach, 2023, https://sergetkach.com/; based on https://dropship-b2b.com.ua/import/opencart
 */

ini_set('error_reporting', E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

define('NIX_VERSION', '1.2.0');

class ControllerExtensionModuleNix extends Controller {

	public $errors = [];
	public $tags = [];
	public $request_time;
	public $languages = [];
	public $stores = []; // for OC 3 SEO URL
	public $xml = [];
	public $offers_prepared = [];
	public $correlation = [];
	public $categories = [];
	public $hierarchy = [];
	private $stdelog;

	function __construct($registry) {
		parent::__construct($registry);
		
		$this->request_time = time(); // for helperHaveTime()
		
		// StdE Require
		$this->stde = new StdE($registry);
		$this->registry->set('stde', $this->stde);
		$this->stde->setCode('nix');
		$this->stde->setType('extension_monolithic');
		
		// StdeLog require
		$this->stdelog = new StdeLog('nix');		
		$this->registry->set('stdelog', $this->stdelog);
		$this->stdelog->setDebug(4);
		
		// !A  Note-5
		// Каждый импорт выделяю в отдельный лог-файл - но это надо и в конструторе еще проследить, чтобы при создании экземпляра класса сразу присваивалась правильная метка
		// Здесь только те переменные сессии, которые нужны для лог-файла
		// Остальные - в processingImportAjax()
		
		if (isset($this->request->post['nix_new_submit'])) {
			// При успешном завершении импорта, $this->session->data['nix']['processing_start_time'] обнуляется и так
			// Но в случае ошибки, необходимо обнулить принудительно
			if (isset($this->session->data['nix']['processing_start_time'])) {
				unset($this->session->data['nix']['processing_start_time']);
			}
			
			$this->session->data['nix']['processing_start_time'] = time(); // IT IS NOT required to be time!
			$this->stdelog->write(3, '__construct() :: NEW SESSION');
			
			$this->stdelog->write(2, '__construct() :: SEND LOGS TO FILE `nix_' . date("Y-m-d") . '_' . $this->session->data['nix']['processing_start_time'] . '.log`');
		}
		
		if (isset($this->session->data['nix']['processing_start_time'])) {
			$this->stdelog->setMarker($this->session->data['nix']['processing_start_time']);
		}
	}
	
	public function install() {
		$this->load->model('user/user_group');
		$this->model_user_user_group->addPermission($this->user->getGroupId(), 'access', 'extension/module/nix');
		$this->model_user_user_group->addPermission($this->user->getGroupId(), 'modify', 'extension/module/nix');
		
		$this->load->model('extension/module/nix');
		$this->model_extension_module_nix->install();
	}
	
	public function index() {
		foreach ($this->load->language('extension/module/nix') as $key => $value) {
			$data[$key] = $value;
		}

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('extension/module/nix');
		$this->load->model('setting/setting');
		
		$data['text_copyright'] = sprintf($this->language->get('text_copyright'), NIX_VERSION);
		
		if ($this->request->server['REQUEST_METHOD'] == 'POST' && $this->validateSettings()) {
			$data['text_success'] = $this->language->get('text_success'); // no redirects if success
			
			$this->model_setting_setting->editSetting('nix', $this->request->post);
		}
		
		if (isset($this->errors)) {
			$data['errors'] = $this->errors;
		}
		
		$data['user_token'] = $this->session->data['user_token']; // user_token need in js ajax
		
		// Breadcrumbps & Links
		$data['breadcrumbs'] = $this->stde->breadcrumbs();

		$data['action'] = $this->stde->link('action');
		$data['cancel'] = $this->stde->link('cancel');

		$data['link_part_settings'] = $this->stde->link('index'); // A!
		$data['link_part_import'] = $this->stde->link('partImport');
		
		$data['supplier_list'] = $this->model_extension_module_nix->supplierList();

		$data['header']			 = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer']			 = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/module/nix_setting', $data));
	}
	
	public function supplierForm() {
		foreach ($this->load->language('extension/module/nix') as $key => $value) {
			$data[$key] = $value;
		}
		
		$data['user_token'] = $this->session->data['user_token']; // user_token need in js ajax
		
		$this->load->model('extension/module/nix');
		
		// default tags
		$data['supplier']['tags'] = [
			'name'							 => 'name',
			'model'							 => 'model',
			'sku'								 => 'vendorCode',
			'description'				 => 'description',
			'price_purchasing'	 => 'optPrice',
			'price_rrp'					 => 'price',
			'images'						 => 'picture',
			'category'					 => 'categoryId',
			'manufacturer_name'	 => 'vendor',
			'attributes'				 => 'param',
		];

		// default attributes
		$data['supplier']['attributes'] =  [
			'parent_id'	=> 'parent_id',
		];

		$this->response->setOutput($this->load->view('extension/module/nix_supplier', $data));
	}
	
	public function supplierEdit() {
		foreach ($this->load->language('extension/module/nix') as $key => $value) {
			$data[$key] = $value;
		}
		
		$this->load->model('extension/module/nix');		
		
		$data['user_token'] = $this->session->data['user_token']; // user_token need in js ajax
		
		$data['supplier_id'] = $this->request->get['supplier_id']; // for form hidden field
		
		$data['supplier'] = $this->model_extension_module_nix->supplierGet($data['supplier_id']);

		$this->response->setOutput($this->load->view('extension/module/nix_supplier', $data));
	}
	
	public function supplierSave() {
		$this->load->language('extension/module/nix');
		$this->load->model('extension/module/nix');
		
		$json = [
			'status' => 'Error',
			'msg' => $this->language->get('msg_supplier_error'),
		];
		
		if ($this->request->server['REQUEST_METHOD'] == 'POST') {
			$this->request->post['name'] = trim($this->request->post['name']);
			if (!$this->request->post['name']) {
				$json['errors']['name'] = $this->language->get('error_supplier_name_empty');
			}
			
			if (!$this->request->post['markup']) {
				$json['errors']['markup'] = $this->language->get('error_supplier_markup_empty');
			}
			
			foreach ($this->request->post['tags'] as $key => $value) {
				$this->request->post['tags'][$key] = trim($value);
			}
			
			$tags_required = [
				'name', 'model', 
				'description',
				'price_purchasing',
				'category', 'manufacturer_name',
				'attributes',
			];

			foreach ($this->request->post['tags'] as $key => $value) {
				if (!$value && in_array($key, $tags_required)) {
					$json['errors']['tag-' . str_replace('_', '-', $key)] = sprintf($this->language->get('error_tag_empty'), $this->language->get('entry_tag_product_' . $key));
				}
			}
			
			$attributes_required = [
				'parent_id',
			];

			foreach ($this->request->post['attributes'] as $key => $value) {
				if (!$value && in_array($key, $attributes_required)) {
					$json['errors']['attribute-' . str_replace('_', '-', $key)] = sprintf($this->language->get('error_attribute_empty'), $this->language->get('entry_attribute_' . $key));
				}
			}
		}
		
		if (!isset($json['errors'])) {
			
			if (isset($this->request->post['supplier_id'])) {
				$json['result'] = $this->model_extension_module_nix->supplierEdit($this->request->post);
			} else {
				$json['supplier_id'] = $this->model_extension_module_nix->supplierAdd($this->request->post);
			}
			
			$json['status'] = 'OK';			
			$json['msg'] = $this->language->get('msg_supplier_success');
			
		}
		
		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}
	
	public function supplierDelete() {
		$this->load->language('extension/module/nix');
		$this->load->model('extension/module/nix');
		
		$json = [
			'status' => 'Error',
			'msg' => $this->language->get('msg_supplier_error'),
		];
		
		if ($this->request->server['REQUEST_METHOD'] == 'POST') {
			$json['result'] = $this->model_extension_module_nix->supplierDelete($this->request->post['supplier_id']);
		}
		
		if (!isset($json['errors'])) {
			
			$json['status'] = 'OK';			
			$json['msg'] = $this->language->get('msg_supplier_delete_success');
			
		}
		
		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}
	
	public function partImport() {
		$this->stdelog->write(2, 'partImport() is called');
		
		foreach ($this->load->language('extension/module/nix') as $key => $value) {
			$data[$key] = $value;
		}

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('extension/module/nix');
		$this->load->model('catalog/manufacturer');
		
		$data['text_copyright'] = sprintf($this->language->get('text_copyright'), NIX_VERSION);
		
		$this->load->model('localisation/language');

		$this->languages = $data['languages'] = $this->stde->languages($this->model_localisation_language->getLanguages());
		
		if (count($data['languages']) > 1) {
			$data['is_multilingual'] = true;
		} else {
			$data['is_multilingual'] = false;
		}
		
		$data['config_language_id'] = $this->config->get('config_language_id');

		//$data['lang'] = $this->language->get('lang');
	
		
		if (isset($this->errors)) {
			$data['errors'] = $this->errors;
		}
		
		$data['language_id'] = $this->request->post['language_id'] ?? '*';
//		$data['primary_language'] = $this->request->post['primary_language'] ?? 0;
		
		$data['copy_description'] = $this->request->post['copy_description'] ?? '';
		$data['copy_attributes'] = $this->request->post['copy_attributes'] ?? '';
		
		$data['delete_all'] = $this->request->post['delete_all'] ?? '';
		$data['update_if_exist'] = $this->request->post['update_if_exist'] ?? '';
		
		$data['supplier_list'] = $this->model_extension_module_nix->supplierList();
		$data['supplier_id'] = (isset($this->request->post['supplier_id'])) ? $this->request->post['supplier_id'] : 0;
		
		$data['user_token'] = $this->session->data['user_token']; // user_token need in js ajax
		
		// Breadcrumbps & Links
		$data['breadcrumbs'] = $this->stde->breadcrumbs();

		$data['action'] = $this->stde->link('partImport');
		$data['cancel'] = $this->stde->link('cancel');

		$data['link_part_settings'] = $this->stde->link('index'); // A!
		$data['link_part_import'] = $this->stde->link('partImport');
		
		$data['header']			 = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer']			 = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/module/nix_import', $data));
	}
	
	public function processingImportAjax() {
		$this->stdelog->write(2, 'processingImportAjax() is called');		
		$this->stdelog->write(3, $this->request->post, 'processingImportAjax() :: $this->request->post');
		$this->stdelog->write(3, $_FILES, 'processingImportAjax() :: $_FILES');
		
		$this->load->language('extension/module/nix');
		
		$this->load->model('extension/module/nix');
		$this->load->model('catalog/manufacturer');
		$this->load->model('localisation/language');

		$this->languages = $data['languages'] = $this->stde->languages($this->model_localisation_language->getLanguages());

		$data['is_multilingual'] = (count($data['languages']) > 1) ? true : false;
		
		$data['config_language_id'] = $this->config->get('config_language_id');
				
		define('FILE_PATH_BASE', DIR_LOGS . 'tmp-');
		
		$json = [
			'status' => 'Error',
			'msg' => $this->language->get('error_warning'),
		];
		
		if (!$this->validateImport()) {
			$json['errors'] = $this->errors;
			
			goto nix_processing_end;
		}		
			
		$this->supplier = $this->model_extension_module_nix->supplierGet($this->request->post['supplier_id']);
		$this->correlation = [];
		
		// Stores - for OC 3 SEO URL
		$this->load->model('setting/store');

		$this->stores[] = array(
			'store_id' => 0,
			'name'     => $this->language->get('text_default')
		);

		$stores = $this->model_setting_store->getStores();

		foreach ($stores as $store) {
			$this->stores[] = array(
				'store_id' => $store['store_id'],
				'name'     => $store['name']
			);
		}
		
		// XML tags
		$tag_name = $this->supplier['tags']['name'];
		$tag_model = $this->supplier['tags']['model'];	
		$tag_sku = $this->supplier['tags']['sku'];
		
		// SESSION DATA
		$this->session->data['nix']['products_processed_in_this_request'] = 0;
		
		if (isset($this->request->post['nix_new_submit'])) {
			$this->session->data['nix']['processing_warnings'] = [];
			$this->session->data['nix']['last_product_item_was'] = false;
			$this->session->data['nix']['products_processed'] = 0;
		} else {
			$this->stdelog->write(3, 'processingImportAjax() :: OLD SESSION');
		}
		
		// check if fields exist
		$this->session->data['nix']['exist_field_meta_h1'] = $this->model_extension_module_nix->helperExistFieldMetaH1(); // ocStore
		$this->session->data['nix']['exist_field_h1'] = $this->model_extension_module_nix->helperExistFieldH1(); // OpenCart + My Tag H1
		$this->session->data['nix']['exist_field_main_category'] = $this->model_extension_module_nix->helperExistFieldMainCategory(); // SeoPro
		$this->session->data['nix']['exist_table_manufacturer_description'] = $this->model_extension_module_nix->helperExistTableManufacturerDescription(); // ocStore has
		
		$this->session->data['nix']['markup'] = $this->model_extension_module_nix->supplierMarkup($this->request->post['supplier_id']);
		
		$this->stdelog->write(3, $this->session->data, 'processingImportAjax() :: $this->session->data');
		
		// A! Note-4
		// In first request we save file in the filesystem
		// In loopQueries() we not send files again!
		
		// A! Note-4:A	
		if (isset($this->request->post['nix_new_submit'])) {
			foreach ($this->languages as $language) {
				if ($_FILES['xmlfile']['tmp_name'][$language['language_id']]) {
					
					$this->stdelog->write(4, FILE_PATH_BASE . $language['language_id'] . '.xml', 'processingImportAjax() :: try to record file');
					
					file_put_contents(FILE_PATH_BASE . $language['language_id'] . '.xml', file_get_contents($_FILES['xmlfile']['tmp_name'][$language['language_id']]));
					
					if (!is_file(FILE_PATH_BASE . $language['language_id'] . '.xml')) {
						$this->stdelog->write(1, 'processingImportAjax() :: ERROR - cannot write file ' . FILE_PATH_BASE . $language['language_id'] . '.xml');
					} else {
						$this->stdelog->write(3, 'processingImportAjax() :: File writed ' . FILE_PATH_BASE . $language['language_id'] . '.xml');
					}			
				}
			}
		}	else {
			if (!is_file(FILE_PATH_BASE . $this->request->post['language_id'] . '.xml')) {
				$json['msg'] = $this->language->get('error_file_main_not_saved');
				$json['errors']['main_file'] = $this->language->get('error_file_main_not_saved');

				$this->stdelog->write(1, 'processingImportAjax() :: error_file_main_not_saved GOTO nix_processing_end');				
				goto nix_processing_end;
			}
		} 		
		
		foreach ($this->languages as $language) {
			if (is_file(FILE_PATH_BASE . $language['language_id'] . '.xml')) {
				$use_errors = libxml_use_internal_errors(true);
				
				$this->xml[$language['language_id']] = simplexml_load_file(FILE_PATH_BASE . $language['language_id'] . '.xml');
				
				$xml_errors = libxml_get_errors(); // Получаем список ошибок XML
				
				// Обрабатываем ошибки
				if (count($xml_errors) > 0) {
					$this->stdelog->write(1, 'processingImportAjax() :: Errors in XML file ' . FILE_PATH_BASE . $language['language_id'] . '.xml');
				
					foreach ($xml_errors as $error) {
						// Ваш код обработки ошибок
						// Например, можно выводить сообщения об ошибках или записывать их в лог
						$this->stdelog->write(4, 'XML Error: ' . $error->message . ' on line ' . $error->line);
					}
				}

				libxml_clear_errors();

				libxml_use_internal_errors($use_errors);
			}
		}
		
		// Link product data in multiple languages by offer_id
		$this->helperPrepareOffers();

		if (false === $this->xml[$this->request->post['language_id']]) {
			$json['errors'] = $this->language->get('error_import_fatal');
			$this->stdelog->write(1, 'processingImportAjax() :: error_import_fatal GOTO nix_processing_end');
			
			goto nix_processing_end;
		}
		
		// TODO...
		// Если опция удаления товара включена
		
		// Удалять только при первом запросе
		if (isset($this->request->post['delete_all']) && isset($this->request->post['nix_new_submit'])) {
			$this->model_extension_module_nix->clearAll();
		}


		// Check required tags of products 
		// 
		// A! Note-1:A
		// Not all vendors have MODEL in XML price
		// Some from them use SKU as the primary product code...

		$main_xml = $this->xml[$this->request->post['language_id']];

		$this->stdelog->write(4, $main_xml->shop->offers->offer[0], 'processingImportAjax() :: $main_xml->shop->offers->offer[0]');

		if (isset($main_xml->shop->offers->offer[0]->$tag_model)) {
			$test = (string)$main_xml->shop->offers->offer[0]->$tag_model;
		} elseif (isset($main_xml->shop->offers->offer[0]->$tag_sku)) {
			$test = (string)$main_xml->shop->offers->offer[0]->$tag_sku;
		} else {
			$this->stdelog->write(4, 'processingImportAjax() :: NOT ISSET $main_xml->shop->offers->offer[0]->$tag_model & $main_xml->shop->offers->offer[0]->$tag_sku');
			$json['errors'] = $this->language->get('error_import_no_tags');
			$this->stdelog->write(1, 'processingImportAjax() :: error_import_no_tags');
			
			$this->stdelog->write(2, 'processingImportAjax() :: GOTO nix_processing_end');
			
			goto nix_processing_end;
		}

		// Если есть данные о категориях
		if (isset($main_xml->shop->categories)) {
			//$result = $this->recordCategory($main_xml->shop->categories);
			$result = $this->recordCategory($main_xml->xpath('shop/categories/category'));
			
			if ('Error' == $result['status']) {
				$json['errors'] = $result['errors'];
			}
		}

		// Если есть данные о товаре
		if (isset($main_xml->shop->offers)) {					
			$result = $this->recordProduct($this->offers_prepared[$this->request->post['language_id']]);
			
			$this->stdelog->write(3, $result, 'processingImportAjax() :: has $this->recordProduct() $result');
			
			if ('Error' == $result['status']) {
				$json['errors'] = $result['errors'];
			} else {
				$json['last_product_id'] = $this->session->data['nix']['last_product_id'];
				
				$json['statistics'] = sprintf($this->language->get('statistics'), $this->session->data['nix']['products_processed']);
				
				$json['statistics_console'] = sprintf(
					$this->language->get('statistics_console'), 
					$this->session->data['nix']['products_processed_in_this_request'],
					$this->session->data['nix']['products_processed']
				);
			}
		}
		
		nix_processing_end:
			
		if (isset($json['errors'])) {
			
			// Errors in the submit form and initital XML-file validation
			$this->stdelog->write(1, $json['errors'], 'processingImportAjax() :: $json["errors"]');
			
			$this->cleanUp();
			
		} elseif ('Finish' == $result['status']) {
			
			$json['status'] = 'Finish';
			$json['msg'] = $this->language->get('success_import');

			$json['warnings'] = false;

			if (count($this->session->data['nix']['processing_warnings']) > 0) {
				foreach ($this->session->data['nix']['processing_warnings'] as $value) {
					$json['warnings'][] = $value;
				}
			}

			$this->cleanUp();
			
		} else {
			
			$json['status'] = 'Continue';
			$json['msg'] = $this->language->get('continued_import');
			
		}
		
		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}
	
	protected function cleanUp() {
		unset($this->session->data['nix']);
		
		// Unlink XML Files
		foreach ($this->languages as $language) {
			if (is_file(FILE_PATH_BASE . $language['language_id'] . '.xml')) {
				unlink(FILE_PATH_BASE . $language['language_id'] . '.xml');
			}
		}
	}
	
	protected function unlinkXMLFiles() {
		foreach ($this->languages as $language) {
			if (is_file(FILE_PATH_BASE . $language['language_id'] . '.xml')) {
				unlink(FILE_PATH_BASE . $language['language_id'] . '.xml');
			}
		}
	}

	protected function validateSettings() {
		if (!$this->user->hasPermission('modify', 'extension/module/nix')) {
			$this->errors['warning'] = $this->language->get('error_permission');
		}
		
		// If are any errors : common warning
		if ($this->errors && !isset($this->errors['warning'])) {
			$this->errors['warning'] = $this->language->get('error_warning');
		}

		return !$this->errors;
	}
	
	protected function validateImport() {
		$this->stdelog->write(2, 'validateImport() is called');
		
		$this->stdelog->write(3, $this->request->post, 'validateImport() :: $this->request->post');
		
		if (!$this->user->hasPermission('modify', 'extension/module/nix')) {
			$this->errors['warning'] = $this->language->get('error_permission');
		}

		if ('*' == $this->request->post['supplier_id']) {
			$this->errors['supplier_id'] = $this->language->get('error_supplier');
		}
		
//		if ('*' == $this->request->post['language_id']) {
//			$this->errors['language_id'] = $this->language->get('error_language');
//		}

		// A! Note-4:B
		// it is present in time of form submit but not is in loopQueries()
		if (isset($_FILES['xmlfile'])) {
			if ($_FILES['xmlfile']['tmp_name'][$this->request->post['language_id']] == '') {
				$this->errors['xmlfile'][$this->request->post['language_id']] = $this->language->get('error_file');
			}
		}
		
		
		// If is any errors : common warning
		if ($this->errors && !isset($this->errors['warning'])) {
			$this->errors['warning'] = $this->language->get('error_warning');
		}
		
		$this->stdelog->write(3, $this->errors, 'validateImport() :: $this->errors');

		return !$this->errors;
	}
	
	/*
	 * In category we didn't have error with different languages
	 */
	private function recordCategory($categories) {
		$result = [];
		
		$this->stdelog->write(2, 'recordCategory() is called');
		
		$this->stdelog->write(4, $categories, 'recordCategory() :: $categories');
					
		//Разбераем массив categories
		foreach ($categories as $node_index => $category) {
			$supplier_category_id = (int)$category['id'];
			
			$name	= trim($category);
			
			$this->stdelog->write(4, $name, 'recordCategory() :: $name for category');
			
			//если parent отсутствует значит это главная категория
			$this->stdelog->write(4, $category[$this->supplier['attributes']['parent_id']], 'recordCategory() :: $category[$this->supplier["attributes"]["parent_id"]]');
			
			if (isset($category[$this->supplier['attributes']['parent_id']]) === false) {
				$parent_id = 0;
				$top = 1;
			} else {
				$parent_id = $this->correlation[(int)$category[$this->supplier['attributes']['parent_id']]] ?? 0;
				$top = 0;
				
				$this->hierarchy[$supplier_category_id][] = (int)$category[$this->supplier['attributes']['parent_id']];
			}

			//проверяем существование категории
			// todo...
			// test by nix_suplier_id
			$test_category = $this->model_extension_module_nix->getCategory($name);

			//Если категория не найдена то создаем новую категорию
			if ($test_category == []) {
				$category_description = $this->prepareCategoryDescription($node_index);
				
				// SEO URL For OC 3 
				$category_seo_url = [];

				foreach ($this->stores as $store) {
					foreach ($category_description as $language_id => $value) {
						$category_seo_url[$store['store_id']][$language_id] = $this->helperTranslitUniversal($value['name']);
					}
				}
				
				$data = [
					'nix_supplier_id'			 => $this->request->post['supplier_id'],
					'nix_supplier_category_id' => $supplier_category_id,
					'parent_id'						 => $parent_id,
					'top'									 => $top,
					'column'							 => 0,
					'sort_order'					 => 0,
					'status'							 => 1,
					'category_store'			 => [0],
					'category_description' => $category_description,
					'category_seo_url'		 => $category_seo_url,
				];
				
				$this->stdelog->write(4, $data, 'model->recordCategory() :: $data');

				$category_id = $this->model_extension_module_nix->addCategory($data);
				
				$this->stdelog->write(4, $category_id, 'model->recordCategory() :: $this->model_extension_module_nix->addCategory return');

				$this->correlation[$supplier_category_id] = $category_id;
				$this->categories[$category_id] = $name;
				
			} else {
				$this->correlation[$supplier_category_id] = $test_category['category_id'];				
				$this->categories[$test_category['category_id']] = $name;

				if (isset($this->request->post['update_if_exist'])) {
					$category_description = $this->prepareCategoryDescription($node_index);
					
					// SEO URL For OC 3 
					$category_seo_url = [];

					foreach ($this->stores as $store) {
						foreach ($category_description as $language_id => $value) {
							$category_seo_url[$store['store_id']][$language_id] = $this->helperTranslitUniversal($value['name']);
						}
					}
					
					$data = [
						'nix_supplier_id'			 => $this->request->post['supplier_id'],
						'nix_supplier_category_id' => $supplier_category_id,
						'parent_id'						 => $this->correlation[$parent_id] ?? 0,
						'top'									 => $top,
						'column'							 => 0,
						'sort_order'					 => 0,
						'status'							 => 1,
						'category_store'			 => [0],
						'category_description' => $category_description,
						'category_seo_url'		 => $category_seo_url,
					];

					$this->model_extension_module_nix->editCategory($test_category['category_id'], $data);
				}
			}
		}
		
		$this->stdelog->write(4, $this->correlation, 'recordCategory() :: $this->correlation');
		$this->stdelog->write(4, $this->categories, 'recordCategory() :: $this->categories');
		$this->stdelog->write(4, $this->hierarchy, 'recordCategory() :: $this->hierarchy');
		
		if (!isset($result['errors'])) {
			$result['status'] = 'OK';
		} else {
			$result['status'] = 'Error';
		}
		
		return $result;
	}
	
	/*
	 * In products we had errors with different languages when we were using $node_index
	 */
	private function recordProduct($offers) {
		$this->stdelog->write(2, 'recordProduct() is called');

		$result = [];
		
		$tag_name							 = $this->supplier['tags']['name'];
		$tag_price_purchasing	 = $this->supplier['tags']['price_purchasing'];
		$tag_price_rrp				 = $this->supplier['tags']['price_rrp'];
		$tag_quantity					 = $this->supplier['tags']['quantity'];
		$tag_images						 = $this->supplier['tags']['images'];
		$tag_model						 = $this->supplier['tags']['model'];
		$tag_sku							 = $this->supplier['tags']['sku'];
		$tag_manufacturer_name = $this->supplier['tags']['manufacturer_name'];
		$tag_description			 = $this->supplier['tags']['description'];
		$tag_category					 = $this->supplier['tags']['category'];
		$tag_attributes				 = $this->supplier['tags']['attributes'];

		$skip = false;
		
		if ($this->session->data['nix']['last_product_item_was']) {
			$skip = true;
		}
		
		foreach ($offers as $offer_id => $offer) {
			$this->stdelog->write(2, (string)$offer['id'], "\r\n\r\n\r\n>>>>>>>>>>>>>>>>>>>>>>>>\r\n" . 'recordProduct() :: NEW ITTERATION $offer with $offer["id"]');
			
			$this->stdelog->write(3, $offer_id, '$offer_id in main thread');
			
			if ($this->session->data['nix']['last_product_item_was'] == (string)$offer['id']) {
				$skip = false;
				continue;
			}
			
			if ($skip) {
				$this->stdelog->write(3, (string)$offer['id'], 'recordProduct() :: SKIP OFFER');
				continue;
			}
			
			// Check required tags & attributes in the XML
			if (!isset($offer['id'])) {
				$this->stdelog->write(1, $offer, 'recordProduct() :: absent attribute `id` for offer');
				$this->session->data['nix']['processing_warnings'][] = 'WARNING: absent attribute `id` for offer';
				
				continue;
			}
			
			if (!isset($offer->$tag_name)) {
				$this->stdelog->write(1, (string)$offer['id'], 'recordProduct() :: absent tag `' . $tag_name . '` for offer item ');
				$this->session->data['nix']['processing_warnings'][] = 'WARNING: offer with id ' . (string)$offer['id'] . ' -- absent tag `' . $tag_name . '`';				
				continue;
			}
			

			/* 
			 * A! Note-2
			 * 
			 * Fatal error: Uncaught exception 'Exception' with message 'Serialization of 'SimpleXMLElement' is not allowed' in [no active file]:0 Stack trace: #0 {main} thrown in [no active file] on line 0
			 *					 
			 * https://www.php.net/manual/en/function.unserialize.php
			 * ... If you store such an object in $_SESSION, you will get a post-execution error ...
			 */	
			
			$this->session->data['nix']['last_product_item_was'] = (string)$offer['id']; // Convert to String! // A! Note-2
			
			$filter = [
//					'name' => trim($offer->$tag_name),
//					'model' => trim($offer->$tag_model),
//					'sku' => trim($offer->$tag_sku),
					'nix_supplier_id' => $this->request->post['supplier_id'],
					'nix_supplier_product_id' => $offer['id'],
				];
			
			$test_product = $this->model_extension_module_nix->getProduct($filter);
			
			$this->stdelog->write(4, $test_product, 'recordProduct() :: $test_product');
			
			
			
			
			/*
			 * Обновление остатков
			 * Это другой XML-файл, в котором меньше тегов
			 * 
			 * Q?
			 * Не проще ли просто поставить какой-то флаг для этого??
			 * Или отдельную вкладку, где нету возможности добавлять разные языковые файлы?
			 */
			if (!isset($offer->$tag_manufacturer_name) && !isset($offer->$tag_attributes) && $test_product !== []) {
				
				$this->stdelog->write(
					3, [
						'product_id' => $test_product['product_id'],
						'$offer["id]"' => (string)$offer['id']
					], 'recordProduct() UPDATE STOCK for'
				);
				
				$this->updateProductStock($offer, $test_product, $filter);
				
				$this->stdelog->write(2, 'recordProduct() :: $this->updateProductStock() called. + Continue');
				
				continue;
			}
			
			
			
			
			/*
			 * Полноценный импорт
			 */
			
			// Если товара в базе нету, но и тегов в файле нету, то это и не импорт, и не обновление остатков.
			if ($test_product == [] 
				&& !isset($offer->$tag_manufacturer_name) 
				&& !isset($offer->$tag_attributes)
				&& (!isset($offer->$tag_model) && !isset($offer->$tag_sku))
			) {
				$this->stdelog->write(1, $offer, 'recordProduct() :: absent tags reuired for offer' . (string)$offer['id']);
				$this->session->data['nix']['processing_warnings'][] = 'WARNING: absent tags reuired for offer ' . (string)$offer['id'];
				
				$this->stdelog->write(2, 'recordProduct() :: NO XML-attributes. Break itteration');
				
				break;
			}
			
			// получаем производителя
			$manufacturer_id = 0;
			
			if (isset($offer->$tag_manufacturer_name)) {
				$manufacturer_id = $this->recordManufacturer((string)$offer->$tag_manufacturer_name);
			}

			$available = 'true';
			
			if (isset($offer['available'])) {
				$available = $offer['available'];
			}
			
			if ($available == 'true') {
				$remains = mt_rand(23, 105);
			} else {
				$remains = 0;
			}
			
			if ($tag_quantity && isset($offer->$tag_quantity)) {
				$remains = $offer->$tag_price;
			}
			
			$price_purchasing = 0;
			
			if (isset($offer->$tag_price_purchasing)) {
				$price_purchasing = $offer->$tag_price_purchasing;
			}
			
			$price_rrp = 0;
			
			if ($tag_price_rrp && isset($offer->$tag_price_rrp)) {
				$price_rrp = $offer->$tag_price_rrp;
			}		
			
			$price = 0;
			
			if ($this->session->data['nix']['markup'] && $price_purchasing) {
				$price = $price_purchasing + ($price_purchasing * ($this->session->data['nix']['markup'] / 100));
			} else {
				$price = $price_rrp;
			}
			
			$status = 1;
			
			if (0 == $price || 0 == $remains) {
				$status = 0;
			}
			
			$model = '';
			
			if (isset($offer->$tag_model)) {
				$model = $offer->$tag_model;
			}
			
			$sku = '';
			
			if (isset($offer->$tag_sku)) {
				$sku = $offer->$tag_sku;
			}
			
			// A! Note-1:B
			if ('' == $model) {
				$model = $sku;
			}
				
			$description = '';
			
			if (isset($offer->$tag_description)) {
				$description = $offer->$tag_description;
			}
						
			$categories = [];
			$category_name_for_images = 'uncategorized';
			
			//$parent_id = $this->correlation[(int)$category[$this->supplier['attributes']['parent_id']]] ?? 0;
			if (isset($offer->$tag_category)) {		
				$categories[0] = $main_category_id = $this->correlation[(int)$offer->$tag_category];
				
				$category_name_for_images = $this->categories[$main_category_id];
				
				// todo...
				// Каждая из родительской категории может иметь еще одну родительскую категорию...
				// А в моем случае рассматирвается только 1 уровень вложенности...
				
				if (isset($this->hierarchy[(int)$offer->$tag_category])) {
					foreach ($this->hierarchy[(int)$offer->$tag_category] as $parent_id) {
						$categories[] = $this->correlation[$parent_id];
					}
				}
			} else {
				$this->stdelog->write(4, $offer->$tag_category, 'recordProduct() :: $offer->$tag_category is absent!');
			}
			
			$this->stdelog->write(4, $categories, 'recordProduct() :: $categories');
			
			// Images
			$image = '';
			
			$product_images = [];

			if (isset($offer->$tag_images)) {
				$images = (array) $offer->$tag_images;
				
				$image = array_shift($images);

				$image = $this->helperGetImage($image, 0, 'products', $category_name_for_images);
				
				if (isset($images) && count($images) > 0) {
					foreach ($images as $i => $item) {
						$product_images[$i] = [
							'image' => $this->helperGetImage((string)$item, ($i + 1), 'products', $category_name_for_images), 'sort_order' => $i,
						];
						
					}
				}
			}
					
			
			// Check if $offer_id is present in all uploaded xml-files
			foreach ($this->offers_prepared as $language_id => $offers) {
				if (!isset($offers[$offer_id])) {
					$this->stdelog->write(1, $language_id, 'offer_id is absent for language');

					// Write to main log also
					$this->log->write('NIX:: ERROR -- offer_id `' . $offer_id . '` is absent for language ' . $language_id);
				}
			}

			//Атрибуты			
			$product_attribute = [];
			
			if (isset($offer->$tag_attributes)) {
				$this->stdelog->write(3, 'recordProduct() :: going to call $this->prepareAttributes()');
				
				$product_attribute = $this->prepareAttributes($offer_id);
			}

			if ($test_product == []) {
				
				$this->stdelog->write(3, 'recordProduct() :: going to call $this->prepareProductDescription()');
				
				$product_description = $this->prepareProductDescription($offer_id);
				
				// SEO URL For OC 3 
				$product_seo_url = [];
				
				foreach ($this->stores as $store) {
					foreach ($product_description as $language_id => $value) {
						$product_seo_url[$store['store_id']][$language_id] = $this->helperTranslitUniversal($value['name']);
					}
				}
				
				//создаем товар
				$data = [
					'nix_supplier_id'					 => $this->request->post['supplier_id'],
					'nix_supplier_product_id'	 => $offer['id'],
					'image'										 => $image,
					'product_image'						 => $product_images,
					'model'										 => $model,
					'sku'											 => $sku,
					'upc'											 => '',
					'ean'											 => '',
					'jan'											 => '',
					'isbn'										 => '',
					'mpn'											 => '',
					'location'								 => '',
					'quantity'								 => $remains,
					'minimum'									 => 1,
					'subtract'								 => 0,
					'stock_status_id'					 => 5,
					'date_available'					 => date("Y-m-d"),
					'manufacturer_id'					 => $manufacturer_id,
					'shipping'								 => 1,
					'price'										 => $price,
					'price_purchasing'				 => $price_purchasing,
					'price_rrp'								 => $price_rrp,
					'points'									 => 0,
					'weight'									 => 0,
					'weight_class_id'					 => 1,
					'length'									 => 0,
					'width'										 => 0,
					'height'									 => 0,
					'length_class_id'					 => 0,
					'status'									 => $status,
					'tax_class_id'						 => 0,
					'sort_order'							 => 0,
					'product_category'				 => $categories,
					'main_category_id'				 => $main_category_id ?? 0,
					'product_attribute'				 => $product_attribute,
					'product_description'			 => $product_description,
					'product_seo_url'					 => $product_seo_url,
				];

				$this->stdelog->write(4, $data, 'recordProduct() :: NEW PRODUCT DATA');
				
				$product_id = $this->model_extension_module_nix->addProduct($data);
				
				$this->stdelog->write(3, $product_id, 'recordProduct() ADD :: $product_id');
				
			} else {
				// обновляем товар
				// Q?
				// А нужно ли обновлять товар???
				// А если человек уже прописал мета-теги?
				// А если поставщик изменил название товара?
				// А если в магазине назание уже отредактировано и так надо?
				// А если фотки случайно удалил?
				
				$this->stdelog->write(2, $this->request->post['update_if_exist'] ?? 0, 'recordProduct() :: $this->request->post["update_if_exist"]');
				
				if (isset($this->request->post['update_if_exist'])) {
					$this->stdelog->write(3, 'recordProduct() :: going to call $this->prepareProductDescription()');
					
					$product_description = $this->prepareProductDescription($offer_id);
					
					// SEO URL For OC 3 
					$product_seo_url = [];

					foreach ($this->stores as $store) {
						foreach ($product_description as $language_id => $value) {
							$product_seo_url[$store['store_id']][$language_id] = $this->helperTranslitUniversal($value['name']);
						}
					}
					
					$data = [
						'image'								 => $image,
						'product_image'				 => $product_images,
						'model'								 => $model,
						'sku'									 => $sku,
						'upc'									 => '',
						'ean'									 => '',
						'jan'									 => '',
						'isbn'								 => '',
						'mpn'									 => '',
						'location'						 => '',
						'quantity'						 => $remains,
						'minimum'							 => 1,
						'subtract'						 => 0,
						'stock_status_id'			 => 5,
						'date_available'			 => date("Y-m-d"),
						'manufacturer_id'			 => $manufacturer_id,
						'shipping'						 => 1,
						'price'								 => $price,
						'price_purchasing'		 => $price_purchasing,
						'price_rrp'						 => $price_rrp,
						'points'							 => 0,
						'weight'							 => 0,
						'weight_class_id'			 => 1,
						'length'							 => 0,
						'width'								 => 0,
						'height'							 => 0,
						'length_class_id'			 => 0,
						'status'							 => $status,
						'tax_class_id'				 => 0,
						'sort_order'					 => 0,
						'product_category'		 => $categories,
						'product_attribute'		 => $product_attribute,
						'product_description'	 => $product_description,
						'product_seo_url'			 => $product_seo_url,
					];

					$this->stdelog->write(3, $data, 'recordProduct() :: PRODUCT UPDATE DATA');

					$this->model_extension_module_nix->editProduct($test_product['product_id'], $data);
				} else {
					$this->stdelog->write(3, 'recordProduct() :: PRODUCT ALREADY EXIST -- SKIP');
				}
			}
			
			// Statistics
			$this->session->data['nix']['products_processed']++;	
			$this->session->data['nix']['products_processed_in_this_request']++;			
			$this->session->data['nix']['last_product_id'] = $product_id ?? $test_product['product_id'];
			
			if (!$this->helperHaveTime()) {
				$result['status'] = 'Continue';
				
				$this->stdelog->write(2, 'recordProduct() :: time is running out. Break operation');
				
				break;
			}
		}
		
		if (!isset($result['status'])) {
			$result['status'] = 'Finish';
		}
		
		$this->stdelog->write(4, $result, 'recordProduct() ::return $result');
		
		return $result;
	}
	
	private function updateProductStock($offer, $test_product, $filter) {
		$this->stdelog->write(3, 'updateProductStock() is called');
		
		$tag_price_purchasing	 = $this->supplier['tags']['price_purchasing'];
		$tag_price_rrp				 = $this->supplier['tags']['price_rrp'];
		$tag_quantity					 = $this->supplier['tags']['quantity'];

		if ($test_product !== []) {
			$available = 'true';
			
			if (isset($offer['available'])) {
				$available = $offer['available'];
			}
			
			if ($available == 'true') {
				$remains = mt_rand(23, 105);
			} else {
				$remains = 0;
			}
			
			if ($tag_quantity && isset($offer->$tag_quantity)) {
				$remains = $offer->$tag_price;
			}
			
			$price_purchasing = 0;
			
			if (isset($offer->$tag_price_purchasing)) {
				$price_purchasing = $offer->$tag_price_purchasing;
			}
			
			$price_rrp = 0;
			
			if ($tag_price_rrp && isset($offer->$tag_price_rrp)) {
				$price_rrp = $offer->$tag_price_rrp;
			}		
			
			$price = 0;
			
			if ($this->session->data['nix']['markup'] && $price_purchasing) {
				$price = $price_purchasing + ($price_purchasing * ($this->session->data['nix']['markup'] / 100));
			} else {
				$price = $price_rrp;
			}
			
			$status = 1;
			
			if (0 == $price || 0 == $remains) {
				$status = 0;
			}

			$data_update = [
				'nix_supplier_product_id'	 => $offer['id'],
				'price'										 => $price,
				'remains'									 => $remains,
				'status'									 => $status,
			];

			$this->model_extension_module_nix->updateProductStock($data_update);

			$this->stdelog->write(4, 'recordProduct() :: $this->model_extension_module_nix->updateProduct($data_update);');

			// Q?
			// Is it necessary to add any report?

		}
	}
	
	private function prepareCategoryDescription($node_index) {
		$this->stdelog->write(4, $node_index, 'getCategoryDescription() called with');
		
		$tag_name	= $this->supplier['tags']['name'];
		$tag_description = $this->supplier['tags']['description'];
		
		$category_description = [];
		
		foreach ($this->xml as $language_id => $xml) {
			$this_lang_node = $xml->shop->categories->category[$node_index];
			
			$category_description[$language_id]['name'] = $this->language->get('import_placeholder_name');
			$category_description[$language_id]['description'] = '';
			
			//'tag' => '',
			//'meta_title' => $name,
			//'meta_h1' => $name,
			//'meta_description' => '',
			//'meta_keyword' => '',
			
			$category_description[$language_id]['name'] = trim((string)$this_lang_node);
			
			if (isset($this_lang_node->$tag_description)) {
				$category_description[$language_id]['description'] = trim((string)$this_lang_node->$tag_description);
			}
		}
		
		// Copy description to other language - if it is choosen
		foreach ($this->languages as $language) {
			if (!isset($category_description[$language['language_id']]) && isset($this->request->post['copy_description'])) {
				$category_description[$language['language_id']] = $category_description[$this->request->post['language_id']];
			}
		}
		
		return $category_description;
	}
	
	private function prepareProductDescription($offer_id) {
		$this->stdelog->write(3, $offer_id, 'getProductDescription() called with $offer_id');
		
		$tag_name	= $this->supplier['tags']['name'];
		$tag_description = $this->supplier['tags']['description'];
		
		$product_description = [];
		
		// A! Other way tnan in attributes
		$languages_used = [];
		
		foreach ($this->offers_prepared as $language_id => $offers) {
			$languages_used[$language_id] = 1; // $dummy_value
		}
		
		foreach ($languages_used as $language_id => $dummy_value) {
			$offer = (isset($this->offers_prepared[$language_id][$offer_id])) ? $this->offers_prepared[$language_id][$offer_id] : $this->offers_prepared[$this->request->post['language_id']][$offer_id];
			$this->stdelog->write(3, $offer, 'prepareProductDescription() :: $offer for $language_id `' . $language_id . '`');
			
			$product_description[$language_id]['name'] = $this->language->get('import_placeholder_name');
			$product_description[$language_id]['description'] = '';
			
			//'tag' => '',
			//'meta_title' => $name,
			//'meta_h1' => $name,
			//'meta_description' => '',
			//'meta_keyword' => '',
			
			if (isset($offer->$tag_name)) {
				$product_description[$language_id]['name'] = trim((string)$offer->$tag_name);
			}
			
			if (isset($offer->$tag_description)) {
				$product_description[$language_id]['description'] = trim((string)$offer->$tag_description);
			}
		}
		
		// Copy description to other language - if it is choosen
		foreach ($this->languages as $language) {
			if (!isset($product_description[$language['language_id']]) && isset($this->request->post['copy_description'])) {
				$product_description[$language['language_id']] = $product_description[$this->request->post['language_id']];
			}
		}
		
		return $product_description;
	}
	
	private function recordManufacturer($name) {
		$manufacturer_id = 0;
			
		$test_manufacturer = $this->model_extension_module_nix->getManufacturer($name);
		
		if ($test_manufacturer === []) {
			//создаем
			$data_new_manufacturer = [
				'name'							 => $name,
				'description'				 => '',
				'meta_keyword'			 => '',
				'sort_order'				 => 0,
				'manufacturer_store' => [0 => 0],
				'keyword'						 => $this->helperTranslitUniversal($name),
			];
			$manufacturer_id = $this->model_extension_module_nix->addManufacturer($data_new_manufacturer);
		} else {
			$manufacturer_id = $test_manufacturer['manufacturer_id'];
		}
			
		return $manufacturer_id;
	}
	
	private function prepareAttributes($offer_id) {
		$this->stdelog->write(3, $offer_id, 'prepareAttributes() called with $offer_id');
		
		$product_attribute = [];
		
		$languages_used = [];

		// for languages uploaded files itteration - not for getting offer
		foreach ($this->offers_prepared as $language_id => $offers) {
			$languages_used[$language_id] = 1; // $dummy_value
			
			// A! Note-6
			// If other languages file are present but offer_id is absent
			${'params_' . $language_id} = [];
			
			if (isset($offers[$offer_id]) && isset($offers[$offer_id]->{$this->supplier['tags']['attributes']})) {
				${'params_' . $language_id} = $offers[$offer_id]->{$this->supplier['tags']['attributes']};
			} else {
				${'params_' . $language_id} = $this->offers_prepared[$this->request->post['language_id']][$offer_id]->{$this->supplier['tags']['attributes']};
				
				$this->stdelog->write(2, 'prepareAttributes() :: attributes not found in $language_id `' . $language_id . '`, so we get attributes from main lanuage `' . $this->request->post['language_id'] . '`');
			}

			$this->stdelog->write(4, ${'params_' . $language_id}, 'prepareAttributes() :: ${"params_" . $language_id} for language ' . $language_id);
			
		}

		// Copy attributes to other language - if it is choosen but without copy_description it non sense!!		
		foreach ($this->languages as $language) {
			if (!isset(${'params_' . $language['language_id']}) && isset($this->request->post['copy_description']) && isset($this->request->post['copy_attributes'])) {
				${'params_' . $language['language_id']} = ${'params_' . $this->request->post['language_id']};
				
				$languages_used[$language['language_id']] = 1; // $dummy_value
			}
		}

		// Attribute Group
		$attribute_group_name	 = 'Default';
		$test_attribute_group	 = $this->model_extension_module_nix->getAttributeGroup($attribute_group_name);

		if ($test_attribute_group == []) {
			// Create Attribute Group "Default"
			$attribute_group_data = [];
			foreach ($languages_used as $language_id => $dummy_value) {
				$attribute_group_data['attribute_group_description'][$language_id]['name'] = $attribute_group_name;
			}
			
			$this->stdelog->write(4, $attribute_group_data, 'prepareAttributes() :: $attribute_group_data for offer_id ' . $offer_id);
			
			$attribute_group_id = $this->model_extension_module_nix->addAttributeGroup($attribute_group_data);
		} else {
			$attribute_group_id = $test_attribute_group['attribute_group_id'];
		}

		$this->stdelog->write(4, $attribute_group_id, 'prepareAttributes() :: $attribute_group_id');

		
		// Attribute		
		$i = 0;
				
		// endless cycle was...
		//foreach ($all_langs_params[$this->request->post['language_id']] as $attribute) { 
		//foreach ($main_langs_params as $attribute) {
		//foreach (${'params_' . $this->request->post['language_id']} as $attribute) {
		
		// Was by $node_index
		//foreach ($this->xml[$this->request->post['language_id']]->shop->offers->offer[$offer_id]->{$this->supplier['tags']['attributes']} as $attribute) {
		
		foreach ($this->offers_prepared[$this->request->post['language_id']][$offer_id]->{$this->supplier['tags']['attributes']} as $attribute) {			
			$this->stdelog->write(4, $i, 'prepareAttributes() :: itteration $i');
			
			$attribute_name = $attribute['name'];
			
			$this->stdelog->write(4, $attribute_name, 'prepareAttributes() :: main language $attribute_name');

			$test_attribute = $this->model_extension_module_nix->getAttribute($attribute_name);

			if ($test_attribute !== []) {
				$attribute_id = $test_attribute['attribute_id'];
				
			} else {
				// Create attribute
				$dta['attribute_group_id'] = $attribute_group_id;
				
				$dta['attribute_description'] = [];
				
				foreach ($languages_used as $language_id => $dummy_value) {
					$param_item = ${'params_' . $language_id};
					
					// Q?
					// А разве уже не предупреждено в A! Note-6?
//					if (!isset($param_item[$i]['name'])) {
//						$param_item	= ${'params_' . $this->request->post['language_id']};
//					}

					$dta['attribute_description'][$language_id]['name'] = (string)$param_item[$i]['name'];
				}
				
				$this->stdelog->write(4, $dta, 'prepareAttributes() :: $dta');
				
				$attribute_id = $this->model_extension_module_nix->addAttribute($dta);
			}
			
			$this->stdelog->write(4, $attribute_id, 'prepareAttributes() :: $attribute_id');
			
			// Attribute values for product			
			$product_attribute[$i] = [
				'attribute_id' => $attribute_id,
			];
			
			foreach ($languages_used as $language_id => $dummy_value) {
				$param_item = ${'params_' . $language_id};
				
				// Q?
				// А разве уже не предупреждено в A! Note-6?
//				if (!isset($param_item[$i]['name'])) {
//					$param_item	= ${'params_' . $this->request->post['language_id']};
//				}
				
				$product_attribute[$i]['product_attribute_description'][$language_id]['text']	 = (string)$param_item[$i];
			}

			$i++;
		}
		
		$this->stdelog->write(4, $product_attribute, 'prepareAttributes() :: return $product_attribute');
		
		return $product_attribute;
	}

	
	public function helperGetImage($url, $index, $essense = 'products', $dirname) {
		$this->stdelog->write(4, $url, 'helperGetImage() :: $url');
		
		$url = trim($url);
		
		$filename = $this->helperPrepareFilename($url, $index);
		
		$dirs = 'catalog/' . $essense . '/' . $this->helperTranslitUniversal(mb_strtolower($dirname));
		
		$image = $dirs . '/' . $filename;
		
		$filepath = DIR_IMAGE . $image;

		if (!is_dir(DIR_IMAGE . $dirs)) {
			$this->helperCreateDir(DIR_IMAGE . $dirs);
		}

		if (!is_file($filepath)) {
			
			$img = file_get_contents($url);
			
			file_put_contents($filepath, $img);
		}

		return $image;
	}

	/*
	 * Create array of objects with offer_id index (!)
	 * Sometimes it is happen errors with ordering of elemens by $node_index
	 * So we have to use good identifier of offer - it is offer_id
	 */
	public function helperPrepareOffers() {
		foreach ($this->xml as $language_id => $xml) {
			$offers_with_nodes = $xml->xpath('shop/offers/offer');
			
			$offers = [];
			
			foreach ($offers_with_nodes as $offer) {
				$offerId = (string)$offer['id'];
				if (!isset($data[$offerId])) {
					$offers[$offerId] = $offer;
				}
			}
			
			$this->offers_prepared[$language_id] = $offers;
		}
	}
	
	public function helperHaveTime() {
		$time = time();
		
		$diff = $time - $this->request_time;
		
		$this->stdelog->write(3, $diff, 'helperHaveTime() :: $diff');

		if ($diff > 25) {
			return false;
		}
		return true;
	}
	
	public function helperCreateDir($path) {
		mkdir($path, 0755, true);
	}
	
	/*
	 * Based on Foreign Characters class and convert_accented_characters() from CodeIgniter 3
	 */
	public function helperTranslitUniversal($string) {
		$foreign_characters = array(
			'/ä|æ|ǽ/'																											 => 'ae',
			'/ö|œ/'																												 => 'oe',
			'/ü/'																													 => 'ue',
			'/Ä/'																													 => 'Ae',
			'/Ü/'																													 => 'Ue',
			'/Ö/'																													 => 'Oe',
			'/À|Á|Â|Ã|Ä|Å|Ǻ|Ā|Ă|Ą|Ǎ|Α|Ά|Ả|Ạ|Ầ|Ẫ|Ẩ|Ậ|Ằ|Ắ|Ẵ|Ẳ|Ặ|А/'					 => 'A',
			'/à|á|â|ã|å|ǻ|ā|ă|ą|ǎ|ª|α|ά|ả|ạ|ầ|ấ|ẫ|ẩ|ậ|ằ|ắ|ẵ|ẳ|ặ|а/'				 => 'a',
			'/Б/'																													 => 'B',
			'/б/'																													 => 'b',
			'/Ç|Ć|Ĉ|Ċ|Č/'																									 => 'C',
			'/ç|ć|ĉ|ċ|č/'																									 => 'c',
			'/Д|Δ/'																												 => 'D',
			'/д|δ/'																												 => 'd',
			'/Ð|Ď|Đ/'																											 => 'Dj',
			'/ð|ď|đ/'																											 => 'dj',
			'/È|É|Ê|Ë|Ē|Ĕ|Ė|Ę|Ě|Ε|Έ|Ẽ|Ẻ|Ẹ|Ề|Ế|Ễ|Ể|Ệ|Е|Э/'									 => 'E',
			'/è|é|ê|ë|ē|ĕ|ė|ę|ě|έ|ε|ẽ|ẻ|ẹ|ề|ế|ễ|ể|ệ|е|э/'									 => 'e',
			'/Ф/'																													 => 'F',
			'/ф/'																													 => 'f',
			'/Ĝ|Ğ|Ġ|Ģ|Γ|Г|Ґ/'																							 => 'G',
			'/ĝ|ğ|ġ|ģ|γ|г|ґ/'																							 => 'g',
			'/Ĥ|Ħ/'																												 => 'H',
			'/ĥ|ħ/'																												 => 'h',
			'/Ì|Í|Î|Ï|Ĩ|Ī|Ĭ|Ǐ|Į|İ|Η|Ή|Ί|Ι|Ϊ|Ỉ|Ị|И|Ы/'											 => 'I',
			'/ì|í|î|ï|ĩ|ī|ĭ|ǐ|į|ı|η|ή|ί|ι|ϊ|ỉ|ị|и|ы|ї/'										 => 'i',
			'/І/' => 'I', // Customized ukr
			'/і/' => 'i', // Customized ukr
			'/Ĵ/'																													 => 'J',
			'/ĵ/'																													 => 'j',
			'/Θ/'																													 => 'TH',
			'/θ/'																													 => 'th',
			'/Ķ|Κ|К/'																											 => 'K',
			'/ķ|κ|к/'																											 => 'k',
			'/Ĺ|Ļ|Ľ|Ŀ|Ł|Λ|Л/'																							 => 'L',
			'/ĺ|ļ|ľ|ŀ|ł|λ|л/'																							 => 'l',
			'/М/'																													 => 'M',
			'/м/'																													 => 'm',
			'/Ñ|Ń|Ņ|Ň|Ν|Н/'																								 => 'N',
			'/ñ|ń|ņ|ň|ŉ|ν|н/'																							 => 'n',
			'/Ò|Ó|Ô|Õ|Ō|Ŏ|Ǒ|Ő|Ơ|Ø|Ǿ|Ο|Ό|Ω|Ώ|Ỏ|Ọ|Ồ|Ố|Ỗ|Ổ|Ộ|Ờ|Ớ|Ỡ|Ở|Ợ|О/'		 => 'O',
			'/ò|ó|ô|õ|ō|ŏ|ǒ|ő|ơ|ø|ǿ|º|ο|ό|ω|ώ|ỏ|ọ|ồ|ố|ỗ|ổ|ộ|ờ|ớ|ỡ|ở|ợ|о/'	 => 'o',
			'/П/'																													 => 'P',
			'/п/'																													 => 'p',
			'/Ŕ|Ŗ|Ř|Ρ|Р/'																									 => 'R',
			'/ŕ|ŗ|ř|ρ|р/'																									 => 'r',
			'/Ś|Ŝ|Ş|Ș|Š|Σ|С/'																							 => 'S',
			'/ś|ŝ|ş|ș|š|ſ|σ|ς|с/'																					 => 's',
			'/Ț|Ţ|Ť|Ŧ|Τ|Т/'																								 => 'T',
			'/ț|ţ|ť|ŧ|τ|т/'																								 => 't',
			'/Þ|þ/'																												 => 'th',
			'/Ù|Ú|Û|Ũ|Ū|Ŭ|Ů|Ű|Ų|Ư|Ǔ|Ǖ|Ǘ|Ǚ|Ǜ|Ũ|Ủ|Ụ|Ừ|Ứ|Ữ|Ử|Ự|У/'						 => 'U',
			'/ù|ú|û|ũ|ū|ŭ|ů|ű|ų|ư|ǔ|ǖ|ǘ|ǚ|ǜ|υ|ύ|ϋ|ủ|ụ|ừ|ứ|ữ|ử|ự|у/'				 => 'u',
			'/Ƴ|Ɏ|Ỵ|Ẏ|Ӳ|Ӯ|Ў|Ý|Ÿ|Ŷ|Υ|Ύ|Ϋ|Ỳ|Ỹ|Ỷ|Ỵ|Й/'												 => 'Y',
			'/ẙ|ʏ|ƴ|ɏ|ỵ|ẏ|ӳ|ӯ|ў|ý|ÿ|ŷ|ỳ|ỹ|ỷ|ỵ|й/'													 => 'y',
			'/В/'																													 => 'V',
			'/в/'																													 => 'v',
			'/Ŵ/'																													 => 'W',
			'/ŵ/'																													 => 'w',
			'/Φ/'																													 => 'F',
			'/φ/'																													 => 'f',
			'/Χ/'																													 => 'CH',
			'/χ/'																													 => 'ch',
			'/Ź|Ż|Ž|Ζ|З/'																									 => 'Z',
			'/ź|ż|ž|ζ|з/'																									 => 'z',
			'/Æ|Ǽ/'																												 => 'AE',
			'/ß/'																													 => 'ss',
			'/Ĳ/'																													 => 'IJ',
			'/ĳ/'																													 => 'ij',
			'/Œ/'																													 => 'OE',
			'/ƒ/'																													 => 'f',
			'/Ξ/'																													 => 'KS',
			'/ξ/'																													 => 'ks',
			'/Π/'																													 => 'P',
			'/π/'																													 => 'p',
			'/Β/'																													 => 'V',
			'/β/'																													 => 'v',
			'/Μ/'																													 => 'M',
			'/μ/'																													 => 'm',
			'/Ψ/'																													 => 'PS',
			'/ψ/'																													 => 'ps',
			'/Ё/'																													 => 'Yo',
			'/ё/'																													 => 'yo',
			'/Є/'																													 => 'Ye',
			'/є/'																													 => 'ye',
			'/Ї/'																													 => 'Yi',
			'/Ж/'																													 => 'Zh',
			'/ж/'																													 => 'zh',
			'/Х/'																													 => 'Kh',
			'/х/'																													 => 'kh',
			'/Ц/'																													 => 'Ts',
			'/ц/'																													 => 'ts',
			'/Ч/'																													 => 'Ch',
			'/ч/'																													 => 'ch',
			'/Ш/'																													 => 'Sh',
			'/ш/'																													 => 'sh',
			'/Щ/'																													 => 'Shch',
			'/щ/'																													 => 'shch',
			'/Ъ|ъ|Ь|ь/'																										 => '',
			'/Ю/'																													 => 'Yu',
			'/ю/'																													 => 'yu',
			'/Я/'																													 => 'Ya',
			'/я/'																													 => 'ya'
		);

		$array_from	= array_keys($foreign_characters);
		$array_to		= array_values($foreign_characters);

		$string = preg_replace($array_from, $array_to, $string);
		
		$string = preg_replace('/[^a-zA-Z0-9\-_]/', ' ', $string);
		
		$string = preg_replace('/\s+/', '-', $string);
		$string = preg_replace('|-+|', '-', $string);
		$string = preg_replace('|_+|', '-', $string);
		$string = trim($string, '-');

		return $string;
	}
	
	public function helperPrepareFilename($url, $index = 0) {
		$path_parts = pathinfo($url);
		
		$string = $this->helperTranslitUniversal($path_parts['filename']);
		
		if ($index) {
			$string .= '_' . $index;
		}
		
		$string .= '.' . $path_parts['extension'];

		return $string;
	}

}
