<?php

/**
 * @category   OpenCart
 * @package    Nice Import XML
 * @copyright  © Serge Tkach, 2023, https://sergetkach.com/
 */

// Heading
$_['heading_title'] = 'Nice Import XML';

// Text
$_['text_extension'] = 'Module';
$_['text_success']	 = 'Setările modulului au fost actualizate!';
$_['text_edit']			 = 'Setările modulului';
$_['btn_save']			 = 'Salvare';
$_['btn_cancel']		 = 'Anulează';

$_['text_copyright'] = '<p>Versiune: <b>%s</b></p>'
	. '<p>&copy; Nimic Import XML. Serge Tkach (<a href="https://sergetkach.com/" target="_blank">https://sergetkach.com/</a>), 2023</p>'
	. '<p>Pe baza modulului de import <a href="https://dropship-b2b.com.ua/import/opencart" target="_blank">Dropship B2B</a> 1.0.0 beta</p>';

// eroare
$_['error_permission'] = 'Nu aveți permisiunea de a gestiona acest modul!';


// Setări
$_['text_part_settings'] = 'Setări module';
$_['entry_status']			 = 'Stare';
$_['btn_save_settings']	 = 'Salvare';
$_['btn_add_supplier']	 = 'Adaugă profil';

// Lista furnizorilor
$_['entry_supplier_list'] = 'Profiluri de furnizori';


// Șterge
$_['msg_supplier_delete_success'] = 'Profil șters';



// formular furnizor
$_['supplier_modal_title']			 = 'Setări profil';
$_['supplier_fieldset_settings'] = 'Setări document XML';
$_['entry_supplier_name']				 = 'Numele profilului';
$_['entry_supplier_link_price']	 = 'Legătură XML de actualizare a prețului';
$_['entry_supplier_link']				 = 'Legătură către un document XML de pe web';
$_['entry_supplier_markup']			 = 'Adaos comercial (%)';

$_['supplier_fieldset_attributes']				 = 'Atributele etichetei în documentul XML';
$_['entry_attribute_parent_id']						 = 'Atribute pentru a desemna parent_id-ul categoriei';
$_['entry_attribute_parent_id']						 = 'Atribute pentru a desemna parent_id-ul categoriei';
$_['supplier_fieldset_tags']							 = 'Etichete document XML';
$_['entry_tag_product_name']							 = 'Etichetă de titlu';
$_['entry_tag_product_description']				 = 'Etichetă de descriere';
$_['entry_tag_product_model']							 = 'Etichetă de model';
$_['entry_tag_product_sku']								 = 'Etichetă SKU';
$_['entry_tag_product_price_purchasing']	 = 'Etichetă de preț de achiziție';
$_['entry_tag_product_price_rrp']					 = 'Etichetă de RRP';
$_['entry_tag_product_quantity']					 = 'Etichetă de cantitate';
$_['entry_tag_product_images']						 = 'Etichete de imagine';
$_['entry_tag_product_category']					 = 'Etichetă categorie de produs';
$_['entry_tag_product_manufacturer_name']	 = 'Etichetă de producător';
$_['entry_tag_product_attributes']				 = 'Etichete de atribut';
$_['btn_supplier_modal_save']							 = 'Salvează profilul';
$_['msg_supplier_success']								 = 'Profil salvat';
$_['msg_supplier_error']									 = 'Eroare! Verificați toate câmpurile formularului!';
$_['error_supplier_name_empty']						 = 'Vă rugăm să introduceți un nume de profil!';
$_['error_supplier_markup_empty']					 = 'Іntroduceți adaos comercial!';

$_['error_tag_empty']											 = '%s este necesar!';
$_['error_attribute_empty'] = '%s este necesar!';



// Import
$_['text_select_option'] = 'Pagina de Import';
$_['text_select_option'] = '-- Selectați --';

$_['text_part_import']					 = 'Importă';
//$_['entry_primary_language'] = '- selectați limba principală pentru acest import';
$_['entry_language']						 = '- selectați limba principală pentru acest import';
$_['entry_file']								 = 'Importă fișier(e)';
$_['help_file']									 = 'Puteți folosi un link furnizat de un furnizor sau puteți selecta un fișier XML de pe computer';
$_['error_file']								 = 'Selectați un fișier XML de importat';
$_['error_file_main_not_saved']	 = 'Fișierul XML pentru limba principală nu a fost salvat corect pe site';
$_['entry_xmllink']							 = 'Legătură către un fișier XML de pe web';

$_['btn_file']							 = 'Selectați fișierul de pe computer';
$_['file_not_choosen']			 = 'Niciun fișier selectat';
$_['xor']										 = 'SAU';
$_['entry_copy_description'] = 'Copiați descrierile de produse și categorii în limbi care nu au specificat un fișier XML';
$_['entry_copy_attributes']	 = 'Copiați atributele produsului în limbi care nu au un fișier XML specificat';
$_['help_copy_attributes']	 = 'Atributele pot fi copiate numai dacă descrierile sunt copiate';
$_['entry_supplier']				 = 'Furnizor';
$_['error_supplier']				 = 'Selectați un furnizor';
$_['btn_import']						 = 'Importă';
$_['text_import_options']		 = 'Opțiuni de import';
$_['entry_delete_all']			 = 'Șterge directorul';
$_['help_delete_all']				 = 'Toate produsele, categoriile, atributele și producătorii vor fi șterse din baza de date înainte de începerea importului';
$_['entry_update_if_exist']	 = 'Suprascrie toate datele pentru produsele existente';
$_['help_update_if_exist']	 = 'Această opțiune va suprascrie orice modificări pe care le-ați făcut la numele și descrierile produselor. Fără această casetă de selectare, produsele care sunt deja în baza de date nu vor fi suprascrise.';

$_['error_warning']				 = 'Eroare la trimiterea formularului! Examinați toate câmpurile pentru erori!';
$_['error_import_fatal']	 = 'Fișierul de import conține erori grave';
$_['error_import_no_tags'] = 'Fișierul de import nu conține etichetele necesare';


// Procesare de import
$_['status_started'] = '<p>Importul a început. NU închideți această pagină până când importul este complet!!</p>';
$_['statistics']		 = '<p>Elemente procesate: <b>%d</b></p>';

$_['statistics_console'] = '<p>Elemente procesate în fundal curent Solicitare: <b>%1$d</b></p>'
	. '<p>Articole procesate în timpul importului curent <b>%2$d</b></p>';

$_['success_import']	 = 'Importul a fost finalizat cu succes';
$_['continued_import'] = 'Importul continuă...';

$_['import_placeholder_name'] = 'Fără nume';
