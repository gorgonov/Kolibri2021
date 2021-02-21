<?php

class ControllerExtensionModuleArserSite extends Controller
{
    private $error = array();

    public function install()
    {
        $res = $this->db->query(
            "CREATE TABLE IF NOT EXISTS `ar_site` (" .
                "`id` int unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID'," .
                "`name` varchar(30) NOT NULL COMMENT 'Наименование'," .
                "`link` varchar(255) NOT NULL COMMENT 'Базовая ссылка'," .
                "`modulname` varchar(255) NOT NULL COMMENT 'Имя модуля обработки'," .
                "`minid` int unsigned NOT NULL COMMENT 'Стартовый id продукта'," .
                "`maxid` int unsigned NOT NULL COMMENT 'Финишный id продукта'," .
                "`mult` decimal(4,2) unsigned NOT NULL COMMENT 'Множитель'," .
                "`status` enum('ok','new','del','price','get','parse') NOT NULL COMMENT 'ok,new,del,price,get,parse'," .
                "`message` varchar(255) NOT NULL," .
                "`productcount` decimal(10,0) NOT NULL DEFAULT '0' COMMENT 'Количество продуктов'," .
                "PRIMARY KEY (`id`)" .
                ") ENGINE=InnoDB AUTO_INCREMENT=25 DEFAULT CHARSET=utf8 COMMENT='Справочник сайтов для парсинга';"
        );


        $this->db->query(
            "INSERT INTO `ar_site` (`id`, `name`, `link`, `modulname`, `minid`, `maxid`, `mult`, `status`, `message`, `productcount`) VALUES" .
                "(1, 'Карлсон', 'https://carlson24.ru', 'carlson', 25000, 29999, 1.00, 'new', '', 91)," .
                "(7, 'Мекко', 'https://mekkomeb.ru/', 'mekko', 15000, 19999, 1.10, 'ok', '', 0)," .
                "(8, 'AltayMebel', 'http://altaimebel22.ru', 'altaymebel', 10000, 14999, 1.10, 'ok', '', 0)," .
                "(9, 'Ваша мебель', 'https://nsk.vmebel24.ru/', 'vmebel', 20000, 24999, 1.10, 'ok', '', 0)," .
                "(10, 'Три кроватки (вручную)', 'http://3krovatki.ru/', 'noModule', 30000, 34999, 1.10, '', '', 0)," .
                "(11, 'Деникс ', 'https://denx.su/', 'denx', 35000, 39999, 1.10, 'ok', '', 2)," .
                "(12, 'Е1 шкафы (вручную) ', 'https://www.e-1.ru/', 'noModule', 40000, 44999, 1.10, '', '', 0)," .
                "(13, 'барнаул мебельный (вручную) ', 'noLink', 'noModule', 45000, 49999, 1.10, '', '', 0)," .
                "(14, 'Пазитивчик пуфики', 'https://pazitif.com/', 'pazitif', 55000, 59999, 1.10, '', '', 0)," .
                "(15, 'Есэндвич', 'https://esandwich.ru', 'esandwich', 60000, 89999, 1.30, 'ok', '', 157)," .
                "(16, 'Есэндвич ричи', 'https://esandwich.ru', 'esandwich1', 60000, 89999, 1.10, 'ok', '', 32)," .
                "(17, 'Есэндвич ИЦ', 'https://esandwich.ru', 'esandwich2', 60000, 89999, 1.10, 'ok', '', 4)," .
                "(18, 'Есэндвич DSV', 'https://esandwich.ru', 'esandwich3', 60000, 89999, 1.10, 'ok', '', 1386)," .
                "(19, 'Есэндвич  -Ол', 'https://esandwich.ru', 'esandwich4', 60000, 89999, 1.10, 'ok', '', 0)," .
                "(20, 'Есэндвич  г.Волгодонск', 'https://esandwich.ru', 'esandwich5', 60000, 89999, 1.10, 'ok', '', 0)," .
                "(21, 'Есэндвич  Gnt', 'https://esandwich.ru', 'esandwich6', 60000, 89999, 1.10, 'ok', '', 7)," .
                "(22, 'Есэндвич  г.Глазов', 'https://esandwich.ru', 'esandwich7', 60000, 89999, 1.10, 'ok', '', 0)," .
                "(23, 'ОЛ-мекко', 'https://olmeko.ru', 'Olmeko', 90000, 95000, 1.10, 'ok', '', 0)," .
                "(24, 'sitparad', 'http://sitparad.com', 'siteparad', 50000, 54999, 1.10, 'ok', '', 225);"
        );

        $this->db->query(
            "CREATE TABLE IF NOT EXISTS `ar_product` (" .
                "`id` int unsigned NOT NULL AUTO_INCREMENT COMMENT 'id'," .
                "`site_id` int unsigned NOT NULL COMMENT 'id сайта'," .
                "`name` varchar(255) NOT NULL COMMENT 'Наименование продукта'," .
                "`price` int NOT NULL COMMENT 'Цена'," .
                "`product_info` text NOT NULL COMMENT 'Информация о продукте'," .
                "`images_link` text NOT NULL COMMENT 'Ссылки на картинки - список с разделителем \n'," .
                "`status` enum('ok','new','del','price') NOT NULL COMMENT 'Статус: ok|new|del|price'," .
                "PRIMARY KEY (`id`)" .
                ") ENGINE=InnoDB AUTO_INCREMENT=90444 DEFAULT CHARSET=utf8;"
        );
    }

    /**
     * Check if the table 'customer_online' exists
     * @return boolean TRUE if table exists, FALSE otherwise.
     */
    public function CheckCustomer()
    {
        $res = $this->db->query("SHOW TABLES LIKE 'ar_site'");
        return (bool)$res->num_rows;
    }

    /**
     * вывод основной страницы
     */
    public function index()
    {
        // todo проверить существование таблиц и создать их при необходимости
        //        $q = @$this->db->query('SELECT id FROM ar_site');
        if ($this->CheckCustomer()) {
            //            echo "таблица существует";
        } else {
            //            echo "таблица НЕ существует";
            $this->install();
        }

        $this->load->language('extension/module/arser_site');

        $this->document->setTitle($this->language->get('heading_title'));

        $this->load->model('setting/setting');
        $this->load->model('extension/module/arser_site');

        $this->getList();
    }

    /**
     * вывод основной страницы
     * вспомогательный блок
     */
    protected function getList()
    {
        if (isset($this->request->get['sort'])) {
            $sort = $this->request->get['sort'];
        } else {
            $sort = 'name';
        }

        if (isset($this->request->get['order'])) {
            $order = $this->request->get['order'];
        } else {
            $order = 'ASC';
        }

        if (isset($this->request->get['page'])) {
            $page = $this->request->get['page'];
        } else {
            $page = 1;
        }

        $url = '';

        if (isset($this->request->get['sort'])) {
            $url .= '&sort=' . $this->request->get['sort'];
        }

        if (isset($this->request->get['order'])) {
            $url .= '&order=' . $this->request->get['order'];
        }

        if (isset($this->request->get['page'])) {
            $url .= '&page=' . $this->request->get['page'];
        }

        $data['breadcrumbs'] = array();

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link(
                'extension/module/arser_site',
                'user_token=' . $this->session->data['user_token'] . $url,
                true
            )
        );

        $data['add'] = $this->url->link(
            'extension/module/arser_site/add',
            'user_token=' . $this->session->data['user_token'] . $url,
            true
        );
        $data['repair'] = $this->url->link(
            'extension/module/arser_site/repair',
            'user_token=' . $this->session->data['user_token'] . $url,
            true
        );
        $data['delete'] = $this->url->link(
            'extension/module/arser_site/delete',
            'user_token=' . $this->session->data['user_token'] . $url,
            true
        );
        $data['setting'] = $this->url->link(
            'extension/module/arser_site/setting',
            'user_token=' . $this->session->data['user_token'] . $url,
            true
        );

        $data['modal'] = $this->url->link(
            'extension/module/arser_site/modal',
            'user_token=' . $this->session->data['user_token'] . $url,
            true
        );

        $filter_data = array(
            'sort' => $sort,
            'order' => $order,
            'start' => ($page - 1) * $this->config->get('config_limit_admin'),
            'limit' => $this->config->get('config_limit_admin')
        );

        $this->model_extension_module_arser_site->recalcProducts();

        $site_total = $this->model_extension_module_arser_site->getTotalSites();

        $results = $this->model_extension_module_arser_site->getSites($filter_data);

        $data['sites'] = array();
        foreach ($results as $result) {
            $data['sites'][] = array(
                'id' => $result['id'],
                'name' => $result['name'],
                'link' => $result['link'],
                'modulname' => $result['modulname'],
                'minid' => $result['minid'],
                'maxid' => $result['maxid'],
                'mult' => $result['mult'],
                'status' => (!$result['message'] == '') ? $result['message'] : $result['status'],
                'productcount' => $result['productcount'],
                'edit' => $this->url->link(
                    'extension/module/arser_site/edit',
                    'user_token=' . $this->session->data['user_token'] . '&id=' . $result['id'] . $url,
                    true
                ),
                'export' => $this->url->link(
                    'extension/module/arser_site/export',
                    'user_token=' . $this->session->data['user_token'] . '&id=' . $result['id'] . $url,
                    true
                ),
                'get' => $this->url->link(
                    'extension/module/arser_site/setGetstatus',
                    'user_token=' . $this->session->data['user_token'] . '&id=' . $result['id'] . $url,
                    true
                ),
                //                'import' => $this->url->link('extension/module/arser_site/import', 'user_token=' . $this->session->data['user_token'] . '&id=' . $result['id'] . $url, true),
                'getimage' => $this->url->link(
                    'extension/module/arser_site/getimage',
                    'user_token=' . $this->session->data['user_token'] . '&id=' . $result['id'] . $url,
                    true
                ),
                'delete' => $this->url->link(
                    'extension/module/arser_site/delete',
                    'user_token=' . $this->session->data['user_token'] . '&id=' . $result['id'] . $url,
                    true
                )
            );
        }

        if (isset($this->error['warning'])) {
            $data['error_warning'] = $this->error['warning'];
        } else {
            $data['error_warning'] = '';
        }

        if (isset($this->session->data['success'])) {
            $data['success'] = $this->session->data['success'];

            unset($this->session->data['success']);
        } else {
            $data['success'] = '';
        }

        if (isset($this->request->post['selected'])) {
            $data['selected'] = (array)$this->request->post['selected'];
        } else {
            $data['selected'] = array();
        }

        $url = '';

        if ($order == 'ASC') {
            $url .= '&order=DESC';
        } else {
            $url .= '&order=ASC';
        }

        if (isset($this->request->get['page'])) {
            $url .= '&page=' . $this->request->get['page'];
        }

        $data['sort_name'] = $this->url->link(
            'extension/module/arser_site',
            'user_token=' . $this->session->data['user_token'] . '&sort=name' . $url,
            true
        );
        $data['sort_link'] = $this->url->link(
            'extension/module/arser_site',
            'user_token=' . $this->session->data['user_token'] . '&sort=link' . $url,
            true
        );
        $data['sort_modulname'] = $this->url->link(
            'extension/module/arser_site',
            'user_token=' . $this->session->data['user_token'] . '&sort=modulname' . $url,
            true
        );
        $data['sort_minid'] = $this->url->link(
            'extension/module/arser_site',
            'user_token=' . $this->session->data['user_token'] . '&sort=minid' . $url,
            true
        );
        $data['sort_maxid'] = $this->url->link(
            'extension/module/arser_site',
            'user_token=' . $this->session->data['user_token'] . '&sort=maxid' . $url,
            true
        );
        $data['sort_mult'] = $this->url->link(
            'extension/module/arser_site',
            'user_token=' . $this->session->data['user_token'] . '&sort=mult' . $url,
            true
        );
        $data['sort_status'] = $this->url->link(
            'extension/module/arser_site',
            'user_token=' . $this->session->data['user_token'] . '&sort=status' . $url,
            true
        );

        $url = '';

        if (isset($this->request->get['sort'])) {
            $url .= '&sort=' . $this->request->get['sort'];
        }

        if (isset($this->request->get['order'])) {
            $url .= '&order=' . $this->request->get['order'];
        }

        $pagination = new Pagination();
        $pagination->total = $site_total;
        $pagination->page = $page;
        $pagination->limit = $this->config->get('config_limit_admin');
        $pagination->url = $this->url->link(
            'extension/module/arser_site',
            'user_token=' . $this->session->data['user_token'] . $url . '&page={page}',
            true
        );

        $data['pagination'] = $pagination->render();

        $data['results'] = sprintf(
            $this->language->get('text_pagination'),
            ($site_total) ? (($page - 1) * $this->config->get('config_limit_admin')) + 1 : 0,
            ((($page - 1) * $this->config->get(
                'config_limit_admin'
            )) > ($site_total - $this->config->get(
                'config_limit_admin'
            ))) ? $site_total : ((($page - 1) * $this->config->get(
                'config_limit_admin'
            )) + $this->config->get('config_limit_admin')),
            $site_total,
            ceil($site_total / $this->config->get('config_limit_admin'))
        );

        $data['sort'] = $sort;
        $data['order'] = $order;

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('extension/module/arser_site', $data));
    }

    /**
     * Настройка
     */
    public function setting()
    {
        $this->load->language('extension/module/arser_site');

        $this->document->setTitle($this->language->get('heading_setting'));

        $this->load->model('setting/setting');
        $this->load->model('extension/module/arser_site');

        if (($this->request->server['REQUEST_METHOD'] == 'POST')) {
            // Вызываем метод "модели" для сохранения настроек
            $this->model_extension_module_arser_site->SaveSettings();

            // Выходим из настроек с выводом сообщения
            $this->session->data['success'] = 'Настройки сохранены';
            $this->response->redirect(
                $this->url->link(
                    'extension/module/arser_site',
                    'user_token=' . $this->session->data['user_token'] . '&type=module',
                    true
                )
            );
        }

        // Загружаем настройки через метод "модели"
        $setting = $this->model_extension_module_arser_site->LoadSettings();

        $data = array();
        $data['text_form'] = $this->language->get('heading_setting');
        $data['arser_status'] = $setting['arser_status'] ?? 1;
        $data['arser_import_path'] = $setting['arser_import_path'] ?? '';

        // Загружаем "хлебные крошки"
        $url = '';
        $data['breadcrumbs'] = array();
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        );
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link(
                'extension/module/arser_site',
                'user_token=' . $this->session->data['user_token'] . $url,
                true
            )
        );
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_setting'),
            'href' => $this->url->link(
                'extension/module/arser_site/setting',
                'user_token=' . $this->session->data['user_token'] . $url,
                true
            )
        );

        // Кнопки действий
        $data['action'] = $this->url->link(
            'extension/module/arser_site/setting',
            'user_token=' . $this->session->data['user_token'] . $url,
            true
        );
        $data['cancel'] = $this->url->link(
            'extension/module/arser_site',
            'user_token=' . $this->session->data['user_token'] . $url,
            true
        );

        // Загрузка шаблонов для шапки, колонки слева и футера
        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        // Выводим в браузер шаблон
        $this->response->setOutput($this->load->view('extension/module/site_setting', $data));
    }


    /**
     * Добавление сайта для парсинга
     */
    public function add()
    {
        $this->load->language('extension/module/arser_site');

        $this->document->setTitle($this->language->get('heading_title'));

        $this->load->model('extension/module/arser_site');

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateForm()) {
            $this->model_extension_module_arser_site->addSite($this->request->post);

            $this->session->data['success'] = $this->language->get('text_success');

            $url = '';

            if (isset($this->request->get['sort'])) {
                $url .= '&sort=' . $this->request->get['sort'];
            }

            if (isset($this->request->get['order'])) {
                $url .= '&order=' . $this->request->get['order'];
            }

            if (isset($this->request->get['page'])) {
                $url .= '&page=' . $this->request->get['page'];
            }

            $this->response->redirect(
                $this->url->link(
                    'extension/module/arser_site',
                    'user_token=' . $this->session->data['user_token'] . $url,
                    true
                )
            );
        }

        $this->getForm();
    }

    /**
     * валидация полей формы перед сохранением
     * @return bool
     */
    protected function validateForm()
    {
        if (!$this->user->hasPermission('modify', 'extension/module/arser_site')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        if ((utf8_strlen($this->request->post['name']) < 3) || (utf8_strlen($this->request->post['name']) > 30)) {
            $this->error['name'] = $this->language->get('error_name');
        }

        if ((utf8_strlen($this->request->post['link']) < 3)) {
            $this->error['link'] = $this->language->get('error_empty');
        }

        if ((utf8_strlen($this->request->post['modulname']) < 3)) {
            $this->error['modulname'] = $this->language->get('error_empty');
        }

        if ((int)($this->request->post['minid']) == 0) {
            $this->error['minid'] = $this->language->get('error_empty');
        }

        if (!($this->request->post['mult'])) {
            $this->error['mult'] = $this->language->get('error_empty');
        }

        //        if (!in_array($this->request->post['status'], array(0, 1))) {
        //            $this->error['status'] = $this->language->get('error_status');
        //        }

        if ($this->error && !isset($this->error['warning'])) {
            $this->error['warning'] = $this->language->get('error_warning');
        }

        return !$this->error;
    }

    /**
     * форма для добавления/изменения
     */
    protected function getForm()
    {
        $data['text_form'] = !isset($this->request->get['id'])
            ? $this->language->get('text_add')
            : $this->language->get('text_edit');

        if (isset($this->error['warning'])) {
            $data['error_warning'] = $this->error['warning'];
        } else {
            $data['error_warning'] = '';
        }

        if (isset($this->error['name'])) {
            $data['error_name'] = $this->error['name'];
        } else {
            $data['error_name'] = array();
        }

        if (isset($this->error['link'])) {
            $data['error_link'] = $this->error['link'];
        } else {
            $data['error_link'] = array();
        }

        if (isset($this->error['modulname'])) {
            $data['error_modulname'] = $this->error['modulname'];
        } else {
            $data['error_modulname'] = array();
        }

        if (isset($this->error['minid'])) {
            $data['error_minid'] = $this->error['minid'];
        } else {
            $data['error_minid'] = array();
        }

        if (isset($this->error['maxid'])) {
            $data['error_maxid'] = $this->error['maxid'];
        } else {
            $data['error_maxid'] = array();
        }

        if (isset($this->error['mult'])) {
            $data['error_mult'] = $this->error['mult'];
        } else {
            $data['error_mult'] = array();
        }

        if (isset($this->error['status'])) {
            $data['error_status'] = $this->error['status'];
        } else {
            $data['error_status'] = '';
        }

        $url = '';

        if (isset($this->request->get['sort'])) {
            $url .= ' & sort = ' . $this->request->get['sort'];
        }

        if (isset($this->request->get['order'])) {
            $url .= ' & order = ' . $this->request->get['order'];
        }

        if (isset($this->request->get['page'])) {
            $url .= ' & page = ' . $this->request->get['page'];
        }

        $data['breadcrumbs'] = array();

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link(
                'extension/module/arser_site',
                'user_token=' . $this->session->data['user_token'] . $url,
                true
            )
        );

        if (!isset($this->request->get['id'])) {
            $data['action'] = $this->url->link(
                'extension/module/arser_site/add',
                'user_token=' . $this->session->data['user_token'] . $url,
                true
            );
        } else {
            $data['action'] = $this->url->link(
                'extension/module/arser_site/edit',
                'user_token=' . $this->session->data['user_token'] . '&id=' . $this->request->get['id'] . $url,
                true
            );
        }

        $data['cancel'] = $this->url->link(
            'extension/module/arser_site',
            'user_token=' . $this->session->data['user_token'] . $url,
            true
        );

        if (isset($this->request->get['id']) && ($this->request->server['REQUEST_METHOD'] != 'POST')) {
            $site_info = $this->model_extension_module_arser_site->getSite($this->request->get['id']);
        }

        $data['user_token'] = $this->session->data['user_token'];

        $this->load->model('localisation / language');

        $data['languages'] = $this->model_localisation_language->getLanguages();

        if (isset($this->request->post['name'])) {
            $data['name'] = $this->request->post['name'];
        } elseif (!empty($site_info)) {
            $data['name'] = $site_info['name'];
        } else {
            $data['name'] = '';
        }

        if (isset($this->request->post['link'])) {
            $data['link'] = $this->request->post['link'];
        } elseif (!empty($site_info)) {
            $data['link'] = $site_info['link'];
        } else {
            $data['link'] = '';
        }

        if (isset($this->request->post['modulname'])) {
            $data['modulname'] = $this->request->post['modulname'];
        } elseif (!empty($site_info)) {
            $data['modulname'] = $site_info['modulname'];
        } else {
            $data['modulname'] = '';
        }

        if (isset($this->request->post['minid'])) {
            $data['minid'] = $this->request->post['minid'];
        } elseif (!empty($site_info)) {
            $data['minid'] = $site_info['minid'];
        } else {
            $data['minid'] = '';
        }

        if (isset($this->request->post['maxid'])) {
            $data['maxid'] = $this->request->post['maxid'];
        } elseif (!empty($site_info)) {
            $data['maxid'] = $site_info['maxid'];
        } else {
            $data['maxid'] = '';
        }

        if (isset($this->request->post['mult'])) {
            $data['mult'] = $this->request->post['mult'];
        } elseif (!empty($site_info)) {
            $data['mult'] = $site_info['mult'];
        } else {
            $data['mult'] = '';
        }

        if (isset($this->request->post['status'])) {
            $data['status'] = $this->request->post['status'];
        } elseif (!empty($site_info)) {
            $data['status'] = $site_info['status'];
        } else {
            $data['status'] = '';
        }

        $data['header'] = $this->load->controller('common / header');
        $data['column_left'] = $this->load->controller('common / column_left');
        $data['footer'] = $this->load->controller('common / footer');

        $this->response->setOutput($this->load->view('extension / module / site_form', $data));
    }

    /**
     * изменение
     */
    public function edit()
    {
        $this->load->language('extension/module/arser_site');

        $this->document->setTitle($this->language->get('heading_title'));

        $this->load->model('extension/module/arser_site');

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateForm()) {
            $this->model_extension_module_arser_site->editSite($this->request->get['id'], $this->request->post);

            $this->session->data['success'] = $this->language->get('text_success');

            $url = '';

            if (isset($this->request->get['sort'])) {
                $url .= ' & sort = ' . $this->request->get['sort'];
            }

            if (isset($this->request->get['order'])) {
                $url .= ' & order = ' . $this->request->get['order'];
            }

            if (isset($this->request->get['page'])) {
                $url .= ' & page = ' . $this->request->get['page'];
            }

            $this->response->redirect(
                $this->url->link(
                    'extension/module/arser_site',
                    'user_token=' . $this->session->data['user_token'] . $url,
                    true
                )
            );
        }

        $this->getForm();
    }


    /**
     * загрузить файл
     */
    public function getFile()
    {
    }

    /**
     * установить признак Status='get' для CRON'a (старт парсинга) по этому сайту
     */
    public function setGetstatus()
    {
        if (isset($this->request->get['id'])) {
            $site_id = $this->request->get['id'];
        } else {
            $this->error['warning'] = 'Не указан id сайта для парсинга.';
            return !$this->error;
        }

        $this->load->model('extension/module/arser_site');
        // Вызываем метод "модели" для сохранения настроек
        $this->model_extension_module_arser_site->setStatusSite($site_id, 'get');

        //        $json = array(
        //            'status' => 'ok',
        //            'message' => 'Признак get установлен',
        //        );
        //
        //        $this->response->addHeader('Content-Type: application/json');
        //        $this->response->setOutput(json_encode($json));
        //
        //        return;
        $this->response->redirect(
            $this->url->link(
                'extension/module/arser_site',
                'user_token=' . $this->session->data['user_token'] . '&type=module',
                true
            )
        );
        //        $this->getList();
    }

    public function import()
    {
        $this->load->language('extension/module/arser_site');
        $this->document->setTitle($this->language->get('heading_import'));
        $this->load->model('extension/module/arser_site');

        if ($this->request->server['REQUEST_METHOD'] == 'POST') {
            // если файл выбран для импорта
            // импортируем

            // выводим сообщение об успехе
            $this->session->data['success'] = $this->language->get('text_success');
            // просто надо закрыть модальное окно
            $url = '';
            if (isset($this->request->get['sort'])) {
                $url .= ' & sort = ' . $this->request->get['sort'];
            }
            if (isset($this->request->get['order'])) {
                $url .= ' & order = ' . $this->request->get['order'];
            }
            if (isset($this->request->get['page'])) {
                $url .= ' & page = ' . $this->request->get['page'];
            }
            $this->response->redirect(
                $this->url->link(
                    'extension/module/arser_site',
                    'user_token=' . $this->session->data['user_token'] . $url,
                    true
                )
            );
        }

        if (isset($this->request->get['id']) && ($this->request->server['REQUEST_METHOD'] != 'POST')) {
            $site_info = $this->model_extension_module_arser_site->getSite($this->request->get['id']);
        }

        $url = '';
        $data['modulname'] = $site_info['modulname'] ?? 'Не указано имя модуля';
        //        $data['action'] = $this->url->link('extension/module/arser_site/import', 'user_token=' . $this->session->data['user_token'] . $url, true);
        $data['user_token'] = $this->session->data['user_token'];
        $data['id'] = $this->request->get['id'];

        $this->response->setOutput($this->load->view('extension/module/site_import', $data));
    }

    public function delete()
    {
        $this->load->language('extension/module/arser_site');

        $this->document->setTitle($this->language->get('heading_title'));

        $this->load->model('extension/module/arser_site');

        if (isset($this->request->post['selected']) && $this->validateDelete()) {
            foreach ($this->request->post['selected'] as $id) {
                $this->model_extension_module_arser_site->deleteSite($id);
            }

            $this->session->data['success'] = $this->language->get('text_success');

            $url = '';

            if (isset($this->request->get['sort'])) {
                $url .= ' & sort = ' . $this->request->get['sort'];
            }

            if (isset($this->request->get['order'])) {
                $url .= ' & order = ' . $this->request->get['order'];
            }

            if (isset($this->request->get['page'])) {
                $url .= ' & page = ' . $this->request->get['page'];
            }

            $this->response->redirect(
                $this->url->link(
                    'extension/module/arser_site',
                    'user_token=' . $this->session->data['user_token'] . $url,
                    true
                )
            );
        }

        $this->getList();
    }

    protected function validateDelete()
    {
        if (!$this->user->hasPermission('modify', 'extension/module/arser_site')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        return !$this->error;
    }

    public function repair()
    {
        $this->load->language('extension/module/arser_site');

        $this->document->setTitle($this->language->get('heading_title'));

        $this->load->model('extension/module/arser_site');

        if ($this->validateRepair()) {
            $this->model_extension_module_arser_site->repairCategories();

            $this->session->data['success'] = $this->language->get('text_success');

            $url = '';

            if (isset($this->request->get['sort'])) {
                $url .= ' & sort = ' . $this->request->get['sort'];
            }

            if (isset($this->request->get['order'])) {
                $url .= ' & order = ' . $this->request->get['order'];
            }

            if (isset($this->request->get['page'])) {
                $url .= ' & page = ' . $this->request->get['page'];
            }

            $this->response->redirect(
                $this->url->link(
                    'extension/module/arser_site',
                    'user_token=' . $this->session->data['user_token'] . $url,
                    true
                )
            );
        }

        $this->getList();
    }

    protected function validateRepair()
    {
        if (!$this->user->hasPermission('modify', 'extension/module/arser_site')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        return !$this->error;
    }

    public function autocomplete()
    {
        $json = array();

        if (isset($this->request->get['filter_name'])) {
            $this->load->model('catalog / category');

            $filter_data = array(
                'filter_name' => $this->request->get['filter_name'],
                'sort' => 'name',
                'order' => 'ASC',
                'start' => 0,
                'limit' => 5
            );

            $results = $this->model_extension_module_arser_site->getCategories($filter_data);

            foreach ($results as $result) {
                $json[] = array(
                    'category_id' => $result['category_id'],
                    'name' => strip_tags(html_entity_decode($result['name'], ENT_QUOTES, 'UTF - 8'))
                );
            }
        }

        $sort_order = array();

        foreach ($json as $key => $value) {
            $sort_order[$key] = $value['name'];
        }

        array_multisort($sort_order, SORT_ASC, $json);

        $this->response->addHeader('Content - Type: application / json');
        $this->response->setOutput(json_encode($json));
    }

    public function export()
    {
        if (isset($this->request->get['id'])) {
            $site_id = $this->request->get['id'];
        } else {
            $this->error['warning'] = 'Не указан id сайта для экспорта.';
            return !$this->error;
        }

        try {
            // we use the PHPExcel package from https://github.com/PHPOffice/PHPExcel
            $cwd = getcwd();
            $dir = version_compare(VERSION, '3.0', '>=') ? 'library/export_import' : 'PHPExcel';
            chdir(DIR_SYSTEM . $dir);
            require_once('Classes/PHPExcel.php');
            chdir($cwd);
        } catch (Exception $e) {
            $errstr = $e->getMessage();
            $errline = $e->getLine();
            $errfile = $e->getFile();
            $errno = $e->getCode();

            // этот блок надо убрать?
            $this->session->data['export_import_error'] = array(
                'errstr' => $errstr,
                'errno' => $errno,
                'errfile' => $errfile,
                'errline' => $errline
            );
            if ($this->config->get('config_error_log')) {
                $this->log->write(
                    'PHP ' . get_class($e) . ':  ' . $errstr . ' in ' . $errfile . ' on line ' . $errline
                );
            }
            $this->error['warning'] = 'Предупреждаю!!! Ошибка при создании класса PHPExcel';
            $data['error_warning'] = 'Какая-то ошибка при создании класса PHPExcel';
            $this->response->redirect(
                $this->url->link(
                    'extension/module/arser_site',
                    'user_token=' . $this->session->data['user_token'],
                    true
                )
            );
            return false;
        }

        $this->load->model('extension/module/arser_site');
        $site = $this->model_extension_module_arser_site->getSite($site_id);
        $xlsName = $site['modulname'] . '.xlsx'; // этот файл вернем пользователю
        $mult = $site['mult'];

        $templateName = DIR_APPLICATION . '../XLSX/template.xls';
        $objPHPExcel = PHPExcel_IOFactory::load($templateName);

        // инициализация счетчиков строк на листах
        $i = array(
            'Products' => 2,
            'AdditionalImages' => 2,
            'ProductOptions' => 2,
            'ProductOptionValues' => 2,
            'Specials' => 2,
            'ProductAttributes' => 2
        );
        // Заполнение листов
        $this->load->model('extension/module/arser_product');
        $rows = $this->model_extension_module_arser_product->getProducts($site_id);
        $aImg = []; // список всех картинок (URL)
        foreach ($rows as $row) {
            $pInfo = unserialize($row['product_info']);
            $imgs = unserialize($row['images_link']);

            $img = $imgs[0];

            if (($imgname = array_search($img, $aImg)) === false) { // не нашел, добавим ссылку на картинку
                $imgname = $this->genImgName($img, $row['id'], 0);
                $aImg[$imgname] = $img;
            }

            //Указывая номера ячеек, заполняем страницу данными
            $objPHPExcel->getSheetByName('Products')
                ->setCellValue('A' . $i['Products'], $row['id'])
                ->setCellValue('B' . $i['Products'], $row['name'])
                ->setCellValue('C' . $i['Products'], $pInfo['category'])
                ->setCellValue('E' . $i['Products'], 'БЕСПЛАТНО!')
                ->setCellValue('K' . $i['Products'], 999)
                ->setCellValue('L' . $i['Products'], $pInfo['model'])
                ->setCellValue('M' . $i['Products'], $pInfo['manufacturer'])
                ->setCellValue('N' . $i['Products'], $imgname)
                ->setCellValue('O' . $i['Products'], 'yes')
                ->setCellValue('P' . $i['Products'], round($row['price'] / 10 * $mult) * 10)
                ->setCellValue('Q' . $i['Products'], 0)
                ->setCellValue('AA' . $i['Products'], 'true')
                ->setCellValue('AB' . $i['Products'], 9)
                ->setCellValue('AC' . $i['Products'], $pInfo['product_teh'])
                //                ->setCellValue('AD' . $i['Products'], $pInfo['title'] ?? $row['name'])
                ->setCellValue('AG' . $i['Products'], 7)
                ->setCellValue('AH' . $i['Products'], 0)
                ->setCellValue('AI' . $i['Products'], 0)
                ->setCellValue('AL' . $i['Products'], 0)
                ->setCellValue('AM' . $i['Products'], 'true')
                ->setCellValue('AN' . $i['Products'], 1)
                ->setCellValue('AO' . $i['Products'], $row['price'])
                ->setCellValue('AP' . $i['Products'], $pInfo['link'] ?? 'не установлено');
            $i['Products']++;

            foreach ($imgs as $key => $img) {
                if (($imgname = array_search($img, $aImg)) === false) { // не нашел, добавим ссылку на картинку
                    $imgname = $this->genImgName($img, $row['id'], $key);
                    $aImg[$imgname] = $img;
                } else {
                }

                if ($key > 0) {
                    $objPHPExcel->getSheetByName('AdditionalImages')
                        ->setCellValue('A' . $i['AdditionalImages'], $row['id'])
                        ->setCellValue(
                            'B' . $i['AdditionalImages'],
                            $imgname
                        ) // $this->genImgName($img, $row['id'], $key))
                        ->setCellValue('C' . $i['AdditionalImages'], 0);
                    $i['AdditionalImages']++;
                }
            }

            if (isset($pInfo['ProductOptions'])) {
                if ($pInfo['ProductOptions'] == 'Цвет') {
                    $objPHPExcel->getSheetByName('ProductOptions')
                        ->setCellValue('A' . $i['ProductOptions'], $row['id'])
                        ->setCellValue('B' . $i['ProductOptions'], 'Цвет')
                        ->setCellValue('D' . $i['ProductOptions'], 'true');
                    $i['ProductOptions']++;


                    foreach ($pInfo['colors'] as $key => $color) {
                        $objPHPExcel->getSheetByName('ProductOptionValues')
                            ->setCellValue('A' . $i['ProductOptionValues'], $row['id'])
                            ->setCellValue('B' . $i['ProductOptionValues'], 'Цвет')
                            ->setCellValue('C' . $i['ProductOptionValues'], $color)
                            ->setCellValue('D' . $i['ProductOptionValues'], 10000)
                            ->setCellValue('E' . $i['ProductOptionValues'], 'false')
                            ->setCellValue('F' . $i['ProductOptionValues'], 0)
                            ->setCellValue('G' . $i['ProductOptionValues'], '+')
                            ->setCellValue('H' . $i['ProductOptionValues'], 0)
                            ->setCellValue('I' . $i['ProductOptionValues'], '+')
                            ->setCellValue('J' . $i['ProductOptionValues'], 0)
                            ->setCellValue('K' . $i['ProductOptionValues'], '+');
                        $i['ProductOptionValues']++;
                    }
                }
            }

            if (isset($pInfo['special'])) {
                $objPHPExcel->getSheetByName('Specials')
                    ->setCellValue('A' . $i['Specials'], $row['id'])
                    ->setCellValue('B' . $i['Specials'], 'Default')
                    ->setCellValue('C' . $i['Specials'], 0)
                    ->setCellValue('D' . $i['Specials'], $pInfo['special']);
                $i['Specials']++;
            }

            if (isset($pInfo['attr'])) {
                foreach ($pInfo['attr'] as $key => $item) {
                    $objPHPExcel->getSheetByName('ProductAttributes')
                        ->setCellValue('A' . $i['ProductAttributes'], $row['id'])
                        ->setCellValue('B' . $i['ProductAttributes'], 'Фильтры')
                        ->setCellValue('C' . $i['ProductAttributes'], $key)
                        ->setCellValue('D' . $i['ProductAttributes'], $item);
                    $i['ProductAttributes']++;
                }
            }
        }

        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="' . $xlsName . '"');
        header('Cache-Control: max-age=0');
        $objWriter->save('php://output');

        $this->load->model('extension/module/arser_site');

        $this->model_extension_module_arser_site->setStatusSite($site_id, 'ok');
        //        $this->response->redirect($this->url->link('extension/module/arser_site', 'user_token=' . $this->session->data['user_token'], true));

    }

    protected function genImgName($filename, $product_id, $i)
    {
        $tmp = explode(".", $filename);
        $result = "/catalog/";
        $result .= $product_id;
        $result .= "_";
        $result .= $i;
        $result .= ".";
        $result .= end($tmp);
        return $result;
    }

    // TODO: не используется?
    public function getimagestatus()
    {
        if (isset($this->request->get['id'])) {
            $site_id = $this->request->get['id'];
        } else {
            echo "Не указан id сайта для экспорта.";
            $this->error['warning'] = 'Не указан id сайта для экспорта.';
            return !$this->error;
        }

        $this->load->model('extension/module/arser_site');
        $site_info = $this->model_extension_module_arser_site->getSite($this->request->get['id']);
        $result = $site_info["message"];

        echo $result;
        //Get the progress
        //        session_start();
        //
        //        print("Processed " . $_SESSION['progress'] . " of " . $_SESSION['goal'] . " rows.");

    }

    /**
     * load image from site to path
     *
     * @return bool
     */
    public function getimage()
    {
        if (isset($this->request->get['id'])) {
            $site_id = $this->request->get['id'];
        } else {
            $this->error['warning'] = 'Не указан id сайта для экспорта.';
            return !$this->error;
        }

        set_time_limit(0);

        $this->load->model('extension/module/arser_site');
        $site_info = $this->model_extension_module_arser_site->getSite($this->request->get['id']);
        $this->model_extension_module_arser_site->setMessageSite($site_id, $site_info['name'] . ' Загрузка картинок');

        $this->load->model('extension/module/arser_product');
        $rows = $this->model_extension_module_arser_product->getProducts($site_id);
        $i = 0;
        $all = count($rows);
        $aImg = [];
        foreach ($rows as $row) {
            //            session_start();  //Reopen session to continue updating the progress
            //            if(!isset($_SESSION['progress'])) {
            //                $_SESSION['progress'] = $i;
            //                $_SESSION['goal'] = $all;
            //            }

            //            $pInfo = unserialize($row['product_info']);
            $imgs = unserialize($row['images_link']);
            foreach ($imgs as $key => $img) {
                if (($imgname = array_search($img, $aImg)) === false) { // не нашел, добавим ссылку на картинку
                    $imgname = $this->genImgName($img, $row['id'], $key);
                    $aImg[$imgname] = $img;
                } else {
                }

                $url = $img;
                //                $path = DIR_APPLICATION . '../image/' . $this->genImgName($img, $row['id'], $key);
                $path = DIR_APPLICATION . '../image/' . $imgname;

                if (file_exists($path)) {
                    echo "<p>" . $row['id'] . $path . ' существует, пропускаем.';
                    //                    $this->toLog("      " . $path . "- существует, пропускаем", "Минуточку...");
                } else {
                    //                    $this->toLog("      " . $path . "- качаем", "Минуточку...");
                    //                    echo "<p>{$url}->{$path}";
                    $img = @file_get_contents($url);
                    if (!$img) {
                        $img = @file_get_contents($this->myUrlEncode($url));
                    }
                    if (!$img) {
                        $img = @file_get_contents($this->сyrillicUrl($url));
                    }
                    if ($img) {
                        file_put_contents($path, $img);
                        echo "<p>" . $row['id'] . ' Сохранен ' . $path;
                    } else {
                        echo "<p>" . $row['id'] . "Ошибка загрузки файла {$url}!\n";
                        //                        die();
                    }
                }
            }
            $i++;

            $this->model_extension_module_arser_site->setMessageSite(
                $site_id,
                $site_info['name'] . " Загрузка картинок ($i/$all)"
            );
            //
            //            $_SESSION['progress'] = $i;
            //            session_write_close();  //Close session to gain access to progress from update script

        }
        $this->model_extension_module_arser_site->setMessageSite($site_id, '');
    }

    protected function myUrlEncode($string)
    {
        $entities = array(
            '%21',
            '%2A',
            '%27',
            '%28',
            '%29',
            '%3B',
            '%3A',
            '%40',
            '%26',
            '%3D',
            '%2B',
            '%24',
            '%2C',
            '%2F',
            '%3F',
            '%25',
            '%23',
            '%5B',
            '%5D'
        );
        $replacements = array(
            '!',
            '*',
            "'",
            "(",
            ")",
            ";",
            ":",
            "@",
            "&",
            "=",
            "+",
            "$",
            ",",
            "/",
            "?",
            "%",
            "#",
            "[",
            "]"
        );
        return str_replace($entities, $replacements, urlencode($string));
    }

    protected function сyrillicUrl($url)
    {
        if (preg_match('#^([\w\d]+://)([^/]+)(.*)$#iu', $url, $m)) {
            $url = $m[1] . idn_to_ascii($m[2], IDNA_DEFAULT, INTL_IDNA_VARIANT_UTS46) . $m[3];
        }
        $url = urldecode($url);
        $url = rawurlencode($url);
        $url = str_replace(array('%3A', '%2F'), array(':', '/'), $url);
        return $url;
    }
}
