<?php

/**
 * @category   OpenCart
 * @package    Nice Import XML
 * @copyright  © Serge Tkach, 2023, https://sergetkach.com/
 */

// Heading
$_['heading_title']    = 'Nice Import XML';

// Text
$_['text_extension'] = 'Модули';
$_['text_success']	 = 'Настройки модуля обновлены!';
$_['text_edit']			 = 'Настройки модуля';
$_['btn_save']			 = 'Сохранить';
$_['btn_cancel']		 = 'Отмена';

$_['text_copyright']  = '<p>Версия: <b>%s</b></p>'
	. '<p>&copy; Nice Import XML. Serge Tkach (<a href="https://sergetkach.com/" target="_blank">https://sergetkach.com/</a>), 2023</p>'
	. '<p>Основано на модуле импорта <a href="https://dropship-b2b.com.ua/import/opencart" target="_blank">Dropship B2B</a> 1.0.0 beta</p>';

// Error
$_['error_permission'] = 'У вас нет прав для управления этим модулем!';


// Settings
$_['text_part_settings'] = 'Настройки модуля';
$_['entry_status']			 = 'Статус';
$_['btn_save_settings']	 = 'Сохранить';
$_['btn_add_supplier']	 = 'Добавить профиль';

// Suppliers list
$_['entry_supplier_list']	= 'Профили поставщиков';


// Delete
$_['msg_supplier_delete_success'] = 'Профиль удален';



// Supplier form
$_['supplier_modal_title']			 = 'Настройки профиля';
$_['supplier_fieldset_settings'] = 'Настройки XML-документа';
$_['entry_supplier_name']				 = 'Название профиля';
$_['entry_supplier_link_price']	 = 'Ссылка на XML для обновления цен';
$_['entry_supplier_link']				 = 'Ссылка на XML-документ в интернете';
$_['entry_supplier_markup']			 = 'Наценка (%)';

$_['supplier_fieldset_attributes']				 = 'Атрибуты тегов в XML-документе';
$_['entry_attribute_parent_id']						 = 'Атрибуты для обозначения parent_id категории';
$_['entry_attribute_parent_id']						 = 'Атрибуты для обозначения parent_id категории';
$_['supplier_fieldset_tags']							 = 'Теги XML-документа';
$_['entry_tag_product_name']							 = 'Тег названия';
$_['entry_tag_product_description']				 = 'Тег описания';
$_['entry_tag_product_model']							 = 'Тег модели';
$_['entry_tag_product_sku']								 = 'Тег SKU (артикула)';
$_['entry_tag_product_price_purchasing']	 = 'Тег закупочной цены';
$_['entry_tag_product_price_rrp']					 = 'Тег РРЦ';
$_['entry_tag_product_quantity']					 = 'Тег количества товара';
$_['entry_tag_product_images']						 = 'Теги изображений';
$_['entry_tag_product_category']					 = 'Тег с категорией товара';
$_['entry_tag_product_manufacturer_name']	 = 'Тег с названием производителя';
$_['entry_tag_product_attributes']				 = 'Теги атрибутов';
$_['btn_supplier_modal_save']							 = 'Сохранить профиль';
$_['msg_supplier_success']								 = 'Профиль сохранен';
$_['msg_supplier_error']									 = 'Ошибка! Проверьте все поля формы!';
$_['error_supplier_name_empty']						 = 'Укажите название профиля!';
$_['error_tag_empty']											 = '%s обязателен для заполнения!';

$_['error_supplier_markup_empty']	 = 'Укажите наценку!';
$_['error_attribute_empty']				 = '%s обязателен для заполенения!';



// Import
$_['text_edit_import']	 = 'Страница импорта';
$_['text_select_option'] = '-- Выбрать --';

$_['text_part_import']				 = 'Импорт';
//$_['entry_primary_language']	 = ' — выбрать основным для этого импорта';
$_['entry_language']	 = ' — выбрать основным для этого импорта';
$_['entry_file']							 = 'Файл(ы) импорта';
$_['help_file']								 = 'Вы можете указать ссылку, полученную от поставщика или выбрать XML-файл с компьютера';
$_['error_file']							 = 'Выберите XML-файл для импорта';
$_['error_file_main_not_saved']= 'XML-файл для главного языка не был корректно сохранен на сайте';
$_['entry_xmllink']						 = 'Ссылка на XML-файл в Интернете';

$_['btn_file']								 = 'Выбрать файл с компьютера';
$_['file_not_choosen']				 = 'Файл не выбран';
$_['xor']											 = 'ИЛИ';
$_['entry_copy_description']	 = 'Копировать описания товаров и категорий в языки, для которых не указан XML-файл';
$_['entry_copy_attributes']    = 'Копировать атрубуты товаров в языки, для которых не указан XML-файл';
$_['help_copy_attributes']     = 'Копировать атрибуты можно только в том случае, если копируются описания';
$_['entry_supplier']					 = 'Поставщик';
$_['error_supplier']					 = 'Выберите Поставщика';
$_['btn_import']							 = 'Импортировать';
$_['text_import_options']			 = 'Опции импорта';
$_['entry_delete_all']				 = 'Очистить каталог';
$_['help_delete_all']					 = 'Перед началом импорта из базы данных будут удалены все товары, категории, атрибуты и производители';
$_['entry_update_if_exist']    = 'Перезаписать все данные для существующих товаров';
$_['help_update_if_exist']	   = 'Данная опция затрет любые изменения в названии товаров и их описаниях, которые Вы могли сделать. Без этой галочки, товары, которые уже есть в базе, не будут перезаписываться.';

$_['error_warning']				 = 'Ошибка при отправке формы! Изучите все поля на предмет ошибок!';
$_['error_import_fatal']	 = 'Фаил импорта содержит серьезные ошибки';
$_['error_import_no_tags'] = 'Файл импорта не содержит необходимых тегов';


// Import Processing
$_['status_started'] = '<p>Импорт начался. НЕ закрывайте эту страницу до окончания импорта!!</p>';
$_['statistics']		 = '<p>Обработано товаров: <b>%d</b></p>';

$_['statistics_console'] = '<p>Обработано товаров в текущем фоновом запросе: <b>%1$d</b></p>'
	. '<p>Обработано товаров за время текущего импорта <b>%2$d</b></p>';

$_['success_import']  = 'Импорт успешно завершен';
$_['continued_import'] = 'Импорт продолжается...';

$_['import_placeholder_name']  = 'Нет названия';

