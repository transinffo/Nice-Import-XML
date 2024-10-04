<?php

/**
 * @category   OpenCart
 * @package    Nice Import XML
 * @copyright  © Serge Tkach, 2023, https://sergetkach.com/
 */

// Heading
$_['heading_title'] = 'Nice Import XML';

// Text
$_['text_extension'] = 'Модулі';
$_['text_success']	 = 'Налаштування модуля оновлено!';
$_['text_edit']			 = 'Налаштування модуля';
$_['btn_save']			 = 'Зберегти';
$_['btn_cancel']		 = 'Скасування';

$_['text_copyright'] = '<p>Версія: <b>%s</b></p>'
	. '<p>&copy; Nice Import XML. Serge Tkach (<a href="https://sergetkach.com/" target="_blank">https://sergetkach.com/</a>), 2023</p>'
	. '<p>Засновано на модулі імпорту <a href="https://dropship-b2b.com.ua/import/opencart" target="_blank">Dropship B2B</a> 1.0.0 beta</p>';

// Error
$_['error_permission'] = 'У вас немає прав для керування цим модулем!';


// Settings
$_['text_part_settings'] = 'Налаштування модуля';
$_['entry_status']			 = 'Статус';
$_['btn_save_settings']	 = 'Зберегти';
$_['btn_add_supplier']	 = 'Додати профіль';

// Suppliers list
$_['entry_supplier_list'] = 'Профілі постачальників';


// Delete
$_['msg_supplier_delete_success'] = 'Профіль видалено';



// Supplier form
$_['supplier_modal_title']			 = 'Налаштування профілю';
$_['supplier_fieldset_settings'] = 'Налаштування документа XML';
$_['entry_supplier_name']				 = 'Назва профілю';
$_['entry_supplier_link_price']	 = 'Посилання на XML для оновлення цін';
$_['entry_supplier_link']				 = 'Посилання на XML-документ в інтернеті';
$_['entry_supplier_markup']			 = 'Націнка (%)';

$_['supplier_fieldset_attributes']				 = 'Атрибути тегів у XML-документі';
$_['entry_attribute_parent_id']						 = 'Атрибути для позначення parent_id категорії';
$_['entry_attribute_parent_id']						 = 'Атрибути для позначення parent_id категорії';
$_['supplier_fieldset_tags']							 = 'Теги XML-документа';
$_['entry_tag_product_name']							 = 'Тег назви';
$_['entry_tag_product_description']				 = 'Тег опису';
$_['entry_tag_product_model']							 = 'Тег моделі';
$_['entry_tag_product_sku']								 = 'Тег SKU (артикула)';
$_['entry_tag_product_price_purchasing']	 = 'Тег закупівельної ціни';
$_['entry_tag_product_price_rrp']					 = 'Тег РРЦ';
$_['entry_tag_product_quantity']					 = 'Тег кількоcті товару';
$_['entry_tag_product_images']						 = 'Теги зображень';
$_['entry_tag_product_category']					 = 'Тег з категорією товару';
$_['entry_tag_product_manufacturer_name']	 = 'Тег з назвою виробника';
$_['entry_tag_product_attributes']				 = 'Теги атрибутів';
$_['btn_supplier_modal_save']							 = 'Зберегти профіль';
$_['msg_supplier_success']								 = 'Профіль збережено';
$_['msg_supplier_error']									 = 'Помилка! Перевірте всі поля форми!';
$_['error_supplier_name_empty']						 = 'Вкажіть назву профілю!';
$_['error_supplier_markup_empty']					 = 'Вкажіть націнку!';

$_['error_tag_empty']				 = '%s є обов\'язковим для заповнення!';
$_['error_attribute_empty']	 = '%s є обов\'язковим для заполонення!';



//Import
$_['text_edit_import']	 = 'Сторінка імпорту';
$_['text_select_option'] = '-- Вибрати --';

$_['text_part_import']					 = 'Імпорт';
//$_['entry_primary_language'] = ' - вибрати основним для цього імпорту';
$_['entry_language']						 = ' - вибрати основним для цього імпорту';
$_['entry_file']								 = 'Файл(и) імпорту';
$_['help_file']									 = 'Ви можете вказати посилання, отримане від постачальника або вибрати файл XML з комп\'ютера';
$_['error_file']								 = 'Виберіть XML-файл для імпорту';
$_['error_file_main_not_saved']	 = 'Файл XML для головної мови не був коректно збережений на сайті';
$_['entry_xmllink']							 = 'Посилання на XML-файл в Інтернеті';

$_['btn_file']							 = 'Вибрати файл із комп\'ютера';
$_['file_not_choosen']			 = 'Файл не вибраний';
$_['xor']										 = 'АБО';
$_['entry_copy_description'] = 'Копіювати описи товарів та категорій у мови, для яких не вказано XML-файл';
$_['entry_copy_attributes']	 = 'Копіювати атрубути товарів у мови, для яких не вказано XML-файл';
$_['help_copy_attributes']	 = 'Копіювати атрибути можна лише в тому випадку, якщо копіюються описи';
$_['entry_supplier']				 = 'Постачальник';
$_['error_supplier']				 = 'Виберіть Постачальника';
$_['btn_import']						 = 'Імпортувати';
$_['text_import_options']		 = 'Опції імпорту';
$_['entry_delete_all']			 = 'Очистити каталог';
$_['help_delete_all']				 = 'Перед початком імпорту з бази даних будуть видалені всі товари, категорії, атрибути та виробники';
$_['entry_update_if_exist']	 = 'Перезаписати всі дані для існуючих товарів';
$_['help_update_if_exist']	 = 'Дана опція зачепить будь-які зміни в назві товарів та їх описах, які Ви могли зробити. Без цієї галочки, товари, які вже є в базі, не перезаписуватимуться.';

$_['error_warning']				 = 'Помилка при надсиланні форми! Вивчіть усі поля щодо помилок!';
$_['error_import_fatal']	 = 'Файл імпорту містить серйозні помилки';
$_['error_import_no_tags'] = 'Файл імпорту не містить потрібних тегів';


// Import Processing
$_['status_started'] = '<p>Імпорт розпочався. НЕ закривайте цю сторінку до закінчення імпорту!!</p>';
$_['statistics']		 = '<p>Оброблено товарів: <b>%d</b></p>';

$_['statistics_console'] = '<p>Оброблено товарів у поточному фоновому запиті: <b>%1$d</b></p>'
	. '<p>Оброблено товарів за час поточного імпорту <b>%2$d</b></p>';

$_['success_import']	 = 'Імпорт успішно завершено';
$_['continued_import'] = 'Імпорт продовжується...';

$_['import_placeholder_name'] = 'Немає назви';
