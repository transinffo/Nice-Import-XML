<?php

/**
 * @category   OpenCart
 * @package    Nice Import XML
 * @copyright  © Serge Tkach, 2023, https://sergetkach.com/
 */

// Header
$_['heading_title'] = 'Nice Import XML';

// Text
$_['text_extension'] = 'Modules';
$_['text_success']	 = 'Module settings updated!';
$_['text_edit']			 = 'Module settings';
$_['btn_save']			 = 'Save';
$_['btn_cancel']		 = 'Cancel';

$_['text_copyright'] = '<p>Version: <b>%s</b></p>'
	. '<p>&copy; Nice Import XML. Serge Tkach (<a href="https://sergetkach.com/" target="_blank">https://sergetkach.com/</a>), 2023</p>'
	. '<p>Based on import module <a href="https://dropship-b2b.com.ua/import/opencart" target="_blank">Dropship B2B</a> 1.0.0 beta</p>';

// Error
$_['error_permission'] = 'You do not have permission to manage this module!';


// Settings
$_['text_part_settings'] = 'Module settings';
$_['entry_status']			 = 'Status';
$_['btn_save_settings']	 = 'Save';
$_['btn_add_supplier']	 = 'Add Profile';

// Suppliers list
$_['entry_supplier_list'] = 'Supplier profiles';


// Delete
$_['msg_supplier_delete_success'] = 'Profile deleted';



// supplier form
$_['supplier_modal_title']			 = 'Profile settings';
$_['supplier_fieldset_settings'] = 'XML document settings';
$_['entry_supplier_name']				 = 'Profile name';
$_['entry_supplier_link_price']	 = 'Price Update XML Link';
$_['entry_supplier_link']				 = 'Link to an XML document on the web';
$_['entry_supplier_markup']			 = 'Markup (%)';

$_['supplier_fieldset_attributes']				 = 'Tag attributes in XML document';
$_['entry_attribute_parent_id']						 = 'Attributes to designate the category\'s parent_id';
$_['entry_attribute_parent_id']						 = 'Attributes to designate the category\'s parent_id';
$_['supplier_fieldset_tags']							 = 'XML document tags';
$_['entry_tag_product_name']							 = 'Title tag';
$_['entry_tag_product_description']				 = 'Description tag';
$_['entry_tag_product_model']							 = 'Model tag';
$_['entry_tag_product_sku']								 = 'SKU tag';
$_['entry_tag_product_price_purchasing']	 = 'Purchasing price tag';
$_['entry_tag_product_price_rrp']					 = 'RRP tag';
$_['entry_tag_product_quantity']					 = 'Quantity tag';
$_['entry_tag_product_images']						 = 'Image tags';
$_['entry_tag_product_category']					 = 'Product category tag';
$_['entry_tag_product_manufacturer_name']	 = 'Manufacturer tag';
$_['entry_tag_product_attributes']				 = 'Attribute tags';
$_['btn_supplier_modal_save']							 = 'Save Profile';
$_['msg_supplier_success']								 = 'Profile saved';
$_['msg_supplier_error']									 = 'Error! Check all form fields!';
$_['error_supplier_name_empty']						 = 'Please enter a profile name!';
$_['error_supplier_markup_empty']					 = 'Enter markup!';

$_['error_tag_empty']				 = '%s is required!';
$_['error_attribute_empty']	 = '%s is required!';



// Import
$_['text_select_option'] = 'Import Pages';
$_['text_select_option'] = '-- Select --';

$_['text_part_import']					 = 'Import';
//$_['entry_primary_language'] = ' - select primary language for this import';
$_['entry_language']						 = ' - select primary language for this import';
$_['entry_file']								 = 'Import file(s)';
$_['help_file']									 = 'You can use a link provided by a vendor or select an XML file from your computer';
$_['error_file']								 = 'Select an XML file to import';
$_['error_file_main_not_saved']	 = 'The XML file for the main language was not saved correctly on the site';
$_['entry_xmllink']							 = 'Link to an XML file on the web';

$_['btn_file']							 = 'Select file from computer';
$_['file_not_choosen']			 = 'No file selected';
$_['xor']										 = 'OR';
$_['entry_copy_description'] = 'Copy product and category descriptions to languages that do not have an XML file specified';
$_['entry_copy_attributes']	 = 'Copy product attributes to languages that do not have an XML file specified';
$_['help_copy_attributes']	 = 'Attributes can only be copied if descriptions are copied';
$_['entry_supplier']				 = 'Supplier';
$_['error_supplier']				 = 'Select a Supplier';
$_['btn_import']						 = 'Import';
$_['text_import_options']		 = 'Import options';
$_['entry_delete_all']			 = 'Clear catalog';
$_['help_delete_all']				 = 'All products, categories, attributes and manufacturers will be deleted from the database before the import starts';
$_['entry_update_if_exist']	 = 'Overwrite all data for existing products';
$_['help_update_if_exist']	 = 'This option will override any changes you may have made to product names and descriptions. Without this checkbox, products that are already in the database will not be overwritten.';

$_['error_warning']				 = 'Error submitting form! Examine all fields for errors!';
$_['error_import_fatal']	 = 'The import file contains serious errors';
$_['error_import_no_tags'] = 'The import file does not contain the required tags';


// Import Processing
$_['status_started'] = '<p>Import has started. DO NOT close this page until the import is complete!!</p>';
$_['statistics']		 = '<p>Items processed: <b>%d</b></p>';

$_['statistics_console'] = '<p>Items processed in the current background Request: <b>%1$d</b></p>'
	. '<p>Items processed during the current import <b>%2$d</b></p>';

$_['success_import']	 = 'Import completed successfully';
$_['continued_import'] = 'Import continues...';

$_['import_placeholder_name'] = 'No name';
