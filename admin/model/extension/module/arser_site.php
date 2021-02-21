<?php

class ModelExtensionModuleArserSite extends Model
{
    public function createTables(){
        $this->db->query("CREATE TABLE IF NOT EXISTS `ar_site` (" .
        "`id` int unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID'," .
        "`name` varchar(30) NOT NULL COMMENT 'Наименование'," .
        "`link` varchar(255) NOT NULL COMMENT 'Базовая ссылка'," .
        "`modulname` varchar(255) NOT NULL COMMENT 'Имя модуля обработки'," .
        "`minid` int unsigned NOT NULL COMMENT 'Стартовый id продукта'," .
        "`maxid` int unsigned NOT NULL COMMENT 'Финишный id продукта'," .
        "`mult` decimal(4,2) unsigned NOT NULL COMMENT 'Множитель'," .
        "`status` enum('ok','new','del','price','get','parse') NOT NULL COMMENT 'ok,new,del,price,get,parse'," .
        "`message` varchar(255) NOT NULL," .
        "PRIMARY KEY (`id`)" .
        ") ENGINE=InnoDB AUTO_INCREMENT=24 DEFAULT CHARSET=utf8 COMMENT='Справочник сайтов для парсинга';");


        $this->db->query("INSERT INTO `ar_site` (`id`, `name`, `link`, `modulname`, `minid`, `maxid`, `mult`, `status`, `message`) VALUES " .
        "(1, 'Карлсон', 'https://carlson24.ru', 'carlson', 25000, 29999, 1.00, 'new', '')," .
        "(7, 'Мекко', 'https://mekkomeb.ru/', 'mekko', 15000, 19999, 1.10, '', '')," .
        "(8, 'AltayMebel', 'http://altaimebel22.ru', 'altaymebel', 10000, 14999, 1.10, 'ok', '')," .
        "(9, 'Ваша мебель', 'https://nsk.vmebel24.ru/', 'vmebel', 20000, 24999, 1.10, 'ok', '')," .
        "(10, 'Три кроватки (вручную)', 'http://3krovatki.ru/', 'noModule', 30000, 34999, 1.10, '', '')," .
        "(11, 'Деникс ', 'https://denx.su/', 'denx', 35000, 39999, 1.10, 'ok', '')," .
        "(12, 'Е1 шкафы (вручную) ', 'https://www.e-1.ru/', 'noModule', 40000, 44999, 1.10, '', '')," .
        "(13, 'барнаул мебельный (вручную) ', 'noLink', 'noModule', 45000, 49999, 1.10, '', '')," .
        "(14, 'Пазитивчик пуфики', 'https://pazitif.com/', 'pazitif', 55000, 59999, 1.10, '', '')," .
        "(15, 'Есэндвич', 'https://esandwich.ru', 'esandwich', 60000, 79999, 1.30, 'new', '')," .
        "(16, 'Есэндвич ричи', 'https://esandwich.ru', 'esandwich1', 60000, 79999, 1.10, 'ok', '')," .
        "(17, 'Есэндвич ИЦ', 'https://esandwich.ru', 'esandwich2', 60000, 79999, 1.10, 'ok', '')," .
        "(18, 'Есэндвич DSV', 'https://esandwich.ru', 'esandwich3', 60000, 79999, 1.10, 'ok', '')," .
        "(19, 'Есэндвич  -Ол', 'https://esandwich.ru', 'esandwich4', 60000, 79999, 1.10, 'ok', '')," .
        "(20, 'Есэндвич  г.Волгодонск', 'https://esandwich.ru', 'esandwich5', 60000, 79999, 1.10, 'ok', '')," .
        "(21, 'Есэндвич  Gnt', 'https://esandwich.ru', 'esandwich6', 60000, 79999, 1.10, 'ok', '')," .
        "(22, 'Есэндвич  г.Глазов', 'https://esandwich.ru', 'esandwich7', 60000, 79999, 1.10, 'ok', '')," .
        "(23, 'ОЛ-мекко', 'https://olmeko.ru', 'Olmeko', 90000, 95000, 1.10, 'new', '');");

    }

    public function deleteSite($site_id)
    {
        $this->db->query("DELETE FROM ar_site WHERE id = '" . (int)$site_id . "'");
    }
    public function addSite($data)
    {
        $this->db->query("INSERT INTO ar_site SET name = '" . $this->db->escape($data['name'])
            . "', link = '" . $this->db->escape($data['link'])
            . "', modulname = '" . $this->db->escape($data['modulname'])
            . "', minid = " . $this->db->escape($data['minid'])
            . ", maxid = " . $this->db->escape($data['maxid'])
            . ", mult = " . $this->db->escape($data['mult'])
            . ", status = ''"
        );

        $site_id = $this->db->getLastId();

        return $site_id;

    }
    public function editSite($site_id, $data)
    {
        $this->db->query("UPDATE ar_site SET name = '" . $this->db->escape($data['name'])
            . "', link = '" . $this->db->escape($data['link'])
            . "', modulname = '" . $this->db->escape($data['modulname'])
            . "', minid = " . $this->db->escape($data['minid'])
            . ", maxid = " . $this->db->escape($data['maxid'])
            . ", mult = " . $this->db->escape($data['mult'])
            . ", status = '" . $this->db->escape($data['status'])."'"
            . " WHERE id = '" . $site_id . "'");
    }
    public function setMessageSite($site_id, $mes)
    {
        $this->db->query("UPDATE ar_site SET message = '" . $mes . "'"
            . " WHERE id = '" . $site_id . "'");
    }
    public function setStatusSite($site_id, $status)
    {
        $this->db->query("UPDATE ar_site SET status = '" . $status . "'"
            . " WHERE id = '" . $site_id . "'");
    }
    public function getSite($site_id)
    {
        $query = $this->db->query("SELECT DISTINCT * FROM ar_site s WHERE s.id = '" . (int)$site_id . "'");

        return $query->row;
    }
    public function getSites($data = array())
    {
        $sql = "SELECT * FROM ar_site s";

        if (!empty($data['filter_name'])) {
            $sql .= " AND s.name LIKE '" . $this->db->escape($data['filter_name']) . "%'";
        }

        if (isset($data['filter_status']) && $data['filter_status'] !== '') {
            $sql .= " AND s.status = '" . (int)$data['filter_status'] . "'";
        }

//        $sql .= " GROUP BY p.product_id";

        $sort_data = array(
            'name',
            'link',
            'modulname',
            'minid',
            'maxid',
            'mult',
            'status',
            'sort_order'
        );

        if (isset($data['sort']) && in_array($data['sort'], $sort_data)) {
            $sql .= " ORDER BY " . $data['sort'];
        } else {
            $sql .= " ORDER BY s.name";
        }

        if (isset($data['order']) && ($data['order'] == 'DESC')) {
            $sql .= " DESC";
        } else {
            $sql .= " ASC";
        }

        $query = $this->db->query($sql);

        return $query->rows;
    }
    public function getTotalSites()
    {
        $query = $this->db->query("SELECT COUNT(*) AS total FROM ar_site");

        return $query->row['total'];
    }

    // Пересчет количества продуктов и запись в ar_site
    public function recalcProducts()
    {
        $this->db->query("UPDATE ar_site a SET productcount = (SELECT COUNT(p.id) FROM ar_product p WHERE a.id=p.site_id);");
    }


    // Запись настроек в базу данных
    public function SaveSettings()
    {
        $this->load->model('setting/setting');
        $this->model_setting_setting->editSetting('arser', $this->request->post);
//        $this->model_setting_setting->editSetting('arser',
//            [
//                'arser_status' => 1,
//                'arser_import_path' => 'трататата',
//            ]);
//        $setting = $this->request->post;
//        $this->model_setting_setting->editSetting('arser', $setting['arser_status']);
//        $this->model_setting_setting->editSetting('arser', $setting['arser_import_path']);
    }
    // Загрузка настроек из базы данных
    public function LoadSettings()
    {

        return array(
            'arser_status' => $this->config->get('arser_status'),
            'arser_import_path' => $this->config->get('arser_import_path')
        );
    }
}

?>