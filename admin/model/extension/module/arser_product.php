<?php

class ModelExtensionModuleArserProduct extends Model
{

    public function createTables()
    {
        $this->db->query("CREATE TABLE IF NOT EXISTS `ar_product` (" .
                "`id` int unsigned NOT NULL AUTO_INCREMENT COMMENT 'id'," .
                "`site_id` int unsigned NOT NULL COMMENT 'id сайта'," .
                "`name` varchar(255) NOT NULL COMMENT 'Наименование продукта'," .
                "`price` int NOT NULL COMMENT 'Цена'," .
                "`product_info` text NOT NULL COMMENT 'Информация о продукте'," .
                "`images_link` text NOT NULL COMMENT 'Ссылки на картинки - список с разделителем \\n'," .
                "`status` enum('ok','new','del','price') NOT NULL COMMENT 'Статус: ok|new|del|price'," ."PRIMARY KEY (`id`)" .
                ") ENGINE=InnoDB AUTO_INCREMENT=90444 DEFAULT CHARSET=utf8;");
    }

    public function upload($filename, $site_id)
    {
        try {

            // we use the PHPExcel package from https://github.com/PHPOffice/PHPExcel
            $cwd = getcwd();
            $dir = version_compare(VERSION, '3.0', '>=') ? 'library/export_import' : 'PHPExcel';
            chdir(DIR_SYSTEM . $dir);
            require_once('Classes/PHPExcel.php');
            chdir($cwd);

            // parse uploaded spreadsheet file
            $inputFileType = PHPExcel_IOFactory::identify($filename);
            $objReader = PHPExcel_IOFactory::createReader($inputFileType);
            $objReader->setReadDataOnly(true);
            $reader = $objReader->load($filename);

            if (!$this->validateUpload($reader, $site_id)) { // проверим, хорош ли файл?
                return false;
            }

            $this->uploadProducts($reader, $site_id);

            return true;

        } catch (Exception $e) {
            $errstr = $e->getMessage();
            $errline = $e->getLine();
            $errfile = $e->getFile();
            $errno = $e->getCode();
            $this->session->data['export_import_error'] = array('errstr' => $errstr, 'errno' => $errno, 'errfile' => $errfile, 'errline' => $errline);
            if ($this->config->get('config_error_log')) {
                $this->log->write('PHP ' . get_class($e) . ':  ' . $errstr . ' in ' . $errfile . ' on line ' . $errline);
            }
            return false;
        }
    }

    // загрузка в таблицу ar_product данных из открытого XLSX-файла

    protected function validateUpload(&$reader, $site_id)
    {
        $ok = true;

        // проверим имена листов книги
        if (!$this->validateWorksheetNames($reader)) {
            $this->log->write($this->language->get('error_worksheets'));
            $ok = false;
        }

        $this->load->model('extension/module/arser_site');
        $site_info = $this->model_extension_module_arser_site->getSite($this->request->get['id']);

        // worksheets must have correct id
        if (!$this->validateIdProducts($reader, $site_info)) {
            $this->log->write($this->language->get('error_products_header'));
            $ok = false;
        }

        return $ok;
    }

    protected function validateWorksheetNames(&$reader)
    {
        // должно быть 10 листов
        if ($reader->getSheetCount()!=10) return false;

        $allowed_worksheets = array(
            'Products',
            'AdditionalImages',
            'Specials',
            'Discounts',
            'Rewards',
            'ProductOptions',
            'ProductOptionValues',
            'ProductAttributes',
            'ProductFilters',
            'ProductSEOKeywords',
        );
        $all_worksheets_ignored = true;
        $worksheets = $reader->getSheetNames();
        foreach ($worksheets as $worksheet) {
            if (in_array($worksheet, $allowed_worksheets)) {
                $all_worksheets_ignored = false;
                break;
            }
        }
        if ($all_worksheets_ignored) {
            return false;
        }
        return true;
    }

    protected function validateIdProducts(&$reader, $site_info)
    {
        // найти максимальный и минимальный id
        $worksheet = $reader->getSheet(0);
        $lastRow = $worksheet->getHighestRow();
        $maxValue = $worksheet->getCell('A2')->getValue();
        $minValue = $maxValue;
        for ($row = 2; $row <= $lastRow; $row++) {
            $maxValue = max($maxValue, $worksheet->getCell('A' . $row)->getValue());
            $minValue = min($minValue, $worksheet->getCell('A' . $row)->getValue());
        }

        return $maxValue<=$site_info['maxid'] and $minValue>=$site_info['minid'];
    }


    /**
     * @param PHPExcel_IOFactory::reader $reader
     * @param integer $site_id
     * @throws PHPExcel_Exception
     */
      protected function uploadProducts(&$reader, $site_id)
    {
        // get worksheet, if not there return immediately
        $data = $reader->getSheetByName('Products');
        if ($data == null) {
            return;
        }

        // load the worksheet cells and store them to the database
        $first_row = array();
        $ar = array();

        $k = $data->getHighestRow();
        for ($i = 0; $i < $k; $i += 1) {
            $product_id = $data->getCellByColumnAndRow(1, 8)->getValue();

            $ar["topic"] = $this->normalText($product->first('h4')->text()); // Заголовок товара
// TODO: с этого места продолжить код
            //product_id	name(ru-ru)	categories	sku	upc	ean	jan	isbn	mpn	location	quantity	model	manufacturer	image_name	shipping	price	points	date_added	date_modified	date_available	weight	weight_unit	length	width	height	length_unit	status	tax_class_id	description(ru-ru)	meta_title(ru-ru)	meta_description(ru-ru)	meta_keywords(ru-ru)	stock_status_id	store_ids	layout	related_ids	tags(ru-ru)	sort_order	subtract	minimum	price_original	link

            if ($i == 0) {
                $max_col = PHPExcel_Cell::columnIndexFromString($data->getHighestColumn());
                for ($j = 1; $j <= $max_col; $j += 1) {
                    $first_row[] = $this->getCell($data, $i, $j);
                }
                continue;
            }
            $j = 1;
            $product_id = trim($this->getCell($data, $i, $j++));
            if ($product_id == "") {
                continue;
            }
            $names = array();
            while ($this->startsWith($first_row[$j - 1], "name(")) {
                $language_code = substr($first_row[$j - 1], strlen("name("), strlen($first_row[$j - 1]) - strlen("name(") - 1);
                $name = $this->getCell($data, $i, $j++);
                $name = htmlspecialchars($name);
                $names[$language_code] = $name;
            }
            $categories = $this->getCell($data, $i, $j++);
            $sku = $this->getCell($data, $i, $j++, '');
            $upc = $this->getCell($data, $i, $j++, '');
            if (in_array('ean', $product_fields)) {
                $ean = $this->getCell($data, $i, $j++, '');
            }
            if (in_array('jan', $product_fields)) {
                $jan = $this->getCell($data, $i, $j++, '');
            }
            if (in_array('isbn', $product_fields)) {
                $isbn = $this->getCell($data, $i, $j++, '');
            }
            if (in_array('mpn', $product_fields)) {
                $mpn = $this->getCell($data, $i, $j++, '');
            }
            $location = $this->getCell($data, $i, $j++, '');
            $quantity = $this->getCell($data, $i, $j++, '0');
            $model = $this->getCell($data, $i, $j++, '   ');
            $manufacturer_name = $this->getCell($data, $i, $j++);
            $image_name = $this->getCell($data, $i, $j++);
            $shipping = $this->getCell($data, $i, $j++, 'yes');
            $price = $this->getCell($data, $i, $j++, '0.00');
            $points = $this->getCell($data, $i, $j++, '0');
            $date_added = $this->getCell($data, $i, $j++);
            $date_added = ((is_string($date_added)) && (strlen($date_added) > 0)) ? $date_added : "NOW()";
            $date_modified = $this->getCell($data, $i, $j++);
            $date_modified = ((is_string($date_modified)) && (strlen($date_modified) > 0)) ? $date_modified : "NOW()";
            $date_available = $this->getCell($data, $i, $j++);
            $date_available = ((is_string($date_available)) && (strlen($date_available) > 0)) ? $date_available : "NOW()";
            $weight = $this->getCell($data, $i, $j++, '0');
            $weight_unit = $this->getCell($data, $i, $j++, $default_weight_unit);
            $length = $this->getCell($data, $i, $j++, '0');
            $width = $this->getCell($data, $i, $j++, '0');
            $height = $this->getCell($data, $i, $j++, '0');
            $measurement_unit = $this->getCell($data, $i, $j++, $default_measurement_unit);
            $status = $this->getCell($data, $i, $j++, 'true');
            $tax_class_id = $this->getCell($data, $i, $j++, '0');
            if (!$this->use_table_seo_url) {
                $keyword = $this->getCell($data, $i, $j++);
            }
            $descriptions = array();
            while ($this->startsWith($first_row[$j - 1], "description(")) {
                $language_code = substr($first_row[$j - 1], strlen("description("), strlen($first_row[$j - 1]) - strlen("description(") - 1);
                $description = $this->getCell($data, $i, $j++);
                $description = htmlspecialchars($description);
                $descriptions[$language_code] = $description;
            }
            if ($exist_meta_title) {
                $meta_titles = array();
                while ($this->startsWith($first_row[$j - 1], "meta_title(")) {
                    $language_code = substr($first_row[$j - 1], strlen("meta_title("), strlen($first_row[$j - 1]) - strlen("meta_title(") - 1);
                    $meta_title = $this->getCell($data, $i, $j++);
                    $meta_title = htmlspecialchars($meta_title);
                    $meta_titles[$language_code] = $meta_title;
                }
            }
            $meta_descriptions = array();
            while ($this->startsWith($first_row[$j - 1], "meta_description(")) {
                $language_code = substr($first_row[$j - 1], strlen("meta_description("), strlen($first_row[$j - 1]) - strlen("meta_description(") - 1);
                $meta_description = $this->getCell($data, $i, $j++);
                $meta_description = htmlspecialchars($meta_description);
                $meta_descriptions[$language_code] = $meta_description;
            }
            $meta_keywords = array();
            while ($this->startsWith($first_row[$j - 1], "meta_keywords(")) {
                $language_code = substr($first_row[$j - 1], strlen("meta_keywords("), strlen($first_row[$j - 1]) - strlen("meta_keywords(") - 1);
                $meta_keyword = $this->getCell($data, $i, $j++);
                $meta_keyword = htmlspecialchars($meta_keyword);
                $meta_keywords[$language_code] = $meta_keyword;
            }
            $stock_status_id = $this->getCell($data, $i, $j++, $default_stock_status_id);
            $store_ids = $this->getCell($data, $i, $j++);
            $layout = $this->getCell($data, $i, $j++);
            $related = $this->getCell($data, $i, $j++);
            $tags = array();
            while ($this->startsWith($first_row[$j - 1], "tags(")) {
                $language_code = substr($first_row[$j - 1], strlen("tags("), strlen($first_row[$j - 1]) - strlen("tags(") - 1);
                $tag = $this->getCell($data, $i, $j++);
                $tag = htmlspecialchars($tag);
                $tags[$language_code] = $tag;
            }
            $sort_order = $this->getCell($data, $i, $j++, '0');
            $subtract = $this->getCell($data, $i, $j++, 'true');
            $minimum = $this->getCell($data, $i, $j++, '1');
            $product = array();
            $product['product_id'] = $product_id;
            $product['names'] = $names;
            $categories = trim($this->clean($categories, false));
            $product['categories'] = ($categories == "") ? array() : explode(",", $categories);
            if ($product['categories'] === false) {
                $product['categories'] = array();
            }
            $product['quantity'] = $quantity;
            $product['model'] = $model;
            $product['manufacturer_name'] = $manufacturer_name;
            $product['image'] = $image_name;
            $product['shipping'] = $shipping;
            $product['price'] = $price;
            $product['points'] = $points;
            $product['date_added'] = $date_added;
            $product['date_modified'] = $date_modified;
            $product['date_available'] = $date_available;
            $product['weight'] = $weight;
            $product['weight_unit'] = $weight_unit;
            $product['status'] = $status;
            $product['tax_class_id'] = $tax_class_id;
            $product['viewed'] = isset($view_counts[$product_id]) ? $view_counts[$product_id] : 0;
            $product['descriptions'] = $descriptions;
            $product['stock_status_id'] = $stock_status_id;
            if ($exist_meta_title) {
                $product['meta_titles'] = $meta_titles;
            }
            $product['meta_descriptions'] = $meta_descriptions;
            $product['length'] = $length;
            $product['width'] = $width;
            $product['height'] = $height;
            if (!$this->use_table_seo_url) {
                $product['seo_keyword'] = $keyword;
            }
            $product['measurement_unit'] = $measurement_unit;
            $product['sku'] = $sku;
            $product['upc'] = $upc;
            if (in_array('ean', $product_fields)) {
                $product['ean'] = $ean;
            }
            if (in_array('jan', $product_fields)) {
                $product['jan'] = $jan;
            }
            if (in_array('isbn', $product_fields)) {
                $product['isbn'] = $isbn;
            }
            if (in_array('mpn', $product_fields)) {
                $product['mpn'] = $mpn;
            }
            $product['location'] = $location;
            $store_ids = trim($this->clean($store_ids, false));
            $product['store_ids'] = ($store_ids == "") ? array() : explode(",", $store_ids);
            if ($product['store_ids'] === false) {
                $product['store_ids'] = array();
            }
            $product['related_ids'] = ($related == "") ? array() : explode(",", $related);
            if ($product['related_ids'] === false) {
                $product['related_ids'] = array();
            }
            $product['layout'] = ($layout == "") ? array() : explode(",", $layout);
            if ($product['layout'] === false) {
                $product['layout'] = array();
            }
            $product['subtract'] = $subtract;
            $product['minimum'] = $minimum;
            $product['meta_keywords'] = $meta_keywords;
            $product['tags'] = $tags;
            $product['sort_order'] = $sort_order;
            if ($incremental) {
                $this->deleteProduct($product_id, $exist_table_product_tag);
            }
            $available_product_ids[$product_id] = $product_id;
            $this->moreProductCells($i, $j, $data, $product);
            $this->storeProductIntoDatabase($product, $languages, $product_fields, $exist_table_product_tag, $exist_meta_title, $layout_ids, $available_store_ids, $manufacturers, $weight_class_ids, $length_class_ids, $url_alias_ids);
        }
    }

    public function deleteProduct($site_id)
    {
        $this->db->query("DELETE FROM ar_product WHERE site_id = '" . (int)$site_id . "'");
    }

    public function getProducts($site_id)
    {
        $query = $this->db->query("SELECT DISTINCT * FROM ar_product s WHERE s.site_id = '" . (int)$site_id . "'");

        return $query->rows;
    }

}