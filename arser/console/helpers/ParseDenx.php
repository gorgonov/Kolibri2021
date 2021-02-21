<?php
/**
 * Created by PhpStorm.
 * User: papaha
 * Date: 15.10.2019
 * Time: 20:08
 */

namespace console\helpers;

require_once('vendor/autoload.php');

use console\models\ArSite;
use Yii;
use DiDom\Document;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use common\traits\LogPrint;

class ParseDenx
{
    use LogPrint;

    protected $oProductsSheet;
    protected $aProducts = [];
    protected $aGroupProducts = [];
    private $id;
    private $name;
    // объекты
    private $link;
    private $minid;
    private $maxid;    // массив ссылок на продукты
    private $spreadsheet;    // массив ссылок на страницы с продуктами

    /**
     * ParseDenx constructor.
     */
    public function __construct($site)
    {
        $this->site_id = $site["id"];
        $this->name = $site["name"];
        $this->link = $site["link"];
        $this->minid = $site["minid"];
        $this->maxid = $site["maxid"];
        $linksFileName = __DIR__ . '\..\..\..\XLSX\DenxLinks.xlsx';
        $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
        $this->spreadsheet = $reader->load($linksFileName);

        $messageLog = [
            'status' => 'Старт ParseDenx.',
            'post' => $this->name,
        ];

//        Yii::info($messageLog, 'parse_info'); //запись в parse.log
        Yii::trace($messageLog, 'parse_info'); //запись в parse.log

        $this->reprint();
        $this->print("Создался ParseDenx");
    }

    public function run()
    {
        $cntProducts = 0;
        // 1. Обработаем 1 лист - ссылки на группы товаров
        $this->runGroupProducts();
//        echo "aGroupProducts=";
//        print_r($this->aGroupProducts);

        // 2. Обработаем 2 лист - ссылки на товары
        $this->runProducts();
//        echo "\naProducts=";
//        print_r($this->aProducts);
//        die();
        // 3. Добавим товары со страниц с группами товаров
        $this->addProducts();
        // 4. Парсим товары, пишем в БД
        $product_id = $this->minid;
        foreach ($this->aProducts as $el) {
//            $lnk = $this->link . $el['link'];
            $lnk = $el['link'];
            $cat = $el['category'];
            $productInfo = $this->getProductInfo($lnk);

            $productInfo['site_id'] = $this->site_id;
            $productInfo['link'] = $lnk;
            $productInfo['category'] = $cat;
            $productInfo['product_id'] = $product_id++;
            $productInfo['model'] = 'Доставим через 3-7 дней';
            $productInfo['manufacturer'] = 'г.Барнаул';
            $productInfo['subtract'] = true;
//            $productInfo['subtract'] = Если есть в наличии то true если нет то false

//            print_r($productInfo);
            ArSite::addProduct($productInfo);
            $cntProducts++;
        }

        $messageLog = ["Загружено " . $cntProducts . " товаров"];

        Yii::info($messageLog, 'parse_info'); //запись в parse.log

        $this->endprint();
    }

    private function runGroupProducts()
    {
        $worksheet = $this->spreadsheet->setActiveSheetIndex(0);
        $highestRow = $worksheet->getHighestRow();
        for ($row = 2; $row <= $highestRow; $row++) {
            $category = $worksheet->getCell("A" . $row)->getValue();
            if ($category) { // борьба с пустыми строками с таблице

                $category = ParseUtil::dotToComma($category);
                $link = $worksheet->getCell("B" . $row)->getValue();
                $this->print("Добавляем страницу: {$link}");
                $this->aGroupProducts[] = [
                    'category' => $category,
                    'link' => $link
                ];
            }
        }
        $this->aGroupProducts = ParseUtil::unique_multidim_array($this->aGroupProducts, 'link');
//        print_r($this->aGroupProducts);
//        die();
    }


    private function runProducts()
    {
        $worksheet = $this->spreadsheet->setActiveSheetIndex(1);
        $highestRow = $worksheet->getHighestRow();
        for ($row = 2; $row <= $highestRow; $row++) {
            $category = $worksheet->getCell("A" . $row)->getValue();
            $category = implode(",", preg_split("/[.,]/", $category));// поправка, если разделитель - "."
            $link = $worksheet->getCell("B" . $row)->getValue();
            $this->aProducts[] = [
                'category' => $category,
                'link' => $link
            ];
        }
//        print_r($this->aProducts);
    }

    private function addProducts()
    {
        foreach ($this->aGroupProducts as $item) { // на странице ссылки на продукты
            $cat = $item['category'];
            $link = $item['link'];
            $this->getProducts($link, $cat);
        }
    }

    private function getProducts($link, $cat)
    {
        $this->print("Качаем страничку $link.");
        $doc = ParseUtil::tryReadLink($link);

        $aProducts = $doc->find('.product-name>a');

        $countProducts = count($aProducts);
        $this->print("Найдено $countProducts продуктов на странице");

        // на странице есть товары с ценниками
        if ($countProducts > 0) {
            $i = 0;
            foreach ($aProducts as $el) {
                $i++;

                $link = $this->link . $el->attr('href');
                $this->print("Обрабатываем страницу: " . $link, "Категория $cat. Продукт $i/$countProducts");
                $this->aProducts[] = [
                    'category' => $cat,
                    'link' => $link
                ];
            }
        }
    }

    protected function getProductInfo($link)
    {
        $this->print("Обрабатываем страницу: $link");
        $doc = ParseUtil::tryReadLink($link);
        if (!$doc) {
            return false;
        }

        $ar = array();

        $ar["topic"] = $this->normalText($doc->first('.site-main__inner h1')->innerHtml()); // Заголовок товара

        // артикул будет рассчитан при записи в таблицу

        if ($doc->has('.product-price>.price-old')) {
            $ar["new_price"] = parseUtil::normalSum($doc->first('.price-old')->text()); // Цена старая
        } else {
            $ar["new_price"] = parseUtil::normalSum($doc->first('.product-price>.price-current')->text()); // Цена новая
        }

        $aImgLink = array();
        $aImg = $doc->find('.light_gallery2'); // список картинок для карусели
        if (!$aImg) {
            $aImg = $doc->find('.product-image>a');
        }
        foreach ($aImg as $el) {
            $href = $el->attr('href');

            if ($href <> '') {
                $aImgLink [] = $this->link . $href;
            }
        }
        $ar["aImgLink"] = $aImgLink;

        $ar["title"] = $this->normalText($doc->first('title')->text()); // title страницы

        // формируем таблицу с характеристиками продукта c разметкой
        $tbl = $doc->first('.shop2-product-options')->html();
        $description = $doc->first('#shop2-tabs-1') ? trim($doc->first('#shop2-tabs-1')->text()) : '';

        // 1. найдем размеры в формате ШхВхГ
        $re = '/(\d+)х(\d+)х(\d+)\s*мм/m';
        $str = $tbl . $description;

        if (preg_match_all($re, $str, $matches, PREG_SET_ORDER, 0)) {
            $aTmp["Ширина"] = $matches[0][1];
            $aTmp["Высота"] = $matches[0][2];
            $aTmp["Глубина"] = $matches[0][3];
        }
        // 2. Найдем размеры в формате Ширина:840 мм; Высота:736 мм; Глубина:500 мм.
        if (!isset($aTmp)) {
            $re = '/Ширина\s*[:-]\s*(\d+)[м;\s]*Высота\s*[:-]\s*(\d+)[м;\s]*Глубина\s*[:-]\s*(\d+)[м;\s]*/m';
            if (preg_match_all($re, $str, $matches, PREG_SET_ORDER, 0)) {
                $aTmp["Ширина"] = $matches[0][1];
                $aTmp["Высота"] = $matches[0][2];
                $aTmp["Глубина"] = $matches[0][3];
            }
        }

        if (isset($aTmp)) {
            $ar["attr"] = $aTmp;
        }

        $aTmp = $this->getAttr($tbl, ['Описание:', 'Характеристики:']);
        $aTmp[] = $description;
        $ar['product_teh'] = implode("<p>", $aTmp);

        return $ar;
    }

    protected function normalText($s)
    {
        // удалим из названия текст "Esandwich"
        $s = trim($s);
        // TODO: убрать?
        $b[] = 'Esandwich.ru';
        $b[] = 'Esandwich';
        $b[] = 'барнаул';
        $b[] = 'есэндвич';

        $s = ParseUtil::utf8_replace($b, '', $s, true);

        return $s;
    }

    /**
     * возвращает строку - описание продукта из таблицы
     *
     * @param $table - элемент table, с описанием продукта
     * <table>
     * <tbody>
     * <tr>
     * <th>имя характеристики</th>;
     * <td>значение характеристики</td>;
     * </tr>
     * ...
     * </tbody>
     * </table>
     * @param $attrList - список атрибутов, значения которых надо найти (в первой колонке th)
     * @return array - атрибуты продукта
     */
    protected function getAttr($table, $attrList)
    {
        if (is_string($table)) {
            $table = new Document($table);
        }

        if (is_array($attrList)) {
            $aTmp = array();
            foreach ($attrList as $str) {
                $th = $table->first("tr>th:contains('" . $str . "')");
                if ($th) {
                    $td = $th->nextSibling("td"); // $td->parent()->find('td')[1]->text();
                    $aTmp[] = $td->text(); // $td->parent()->find('td')[1]->text();
                } else {
                }
            }
            return $aTmp;
        } else {
            $th = $table->first("tr>th:contains('" . $attrList . "')");
            if ($th) {
                return $th->nextSibling()->text();
            } else {
                return "";
            }
        }
    }

    protected function getSize($sTmp)
    {
        // убираем неразрывные пробелы
        $sTmp = str_replace(array(" ", chr(0xC2) . chr(0xA0)), ' ', $sTmp);
        print_r("sTmp=" . $sTmp . "\n");
        $aTmp = explode(" ", $sTmp);
//    echo '<pre>';
//    var_dump($aTmp);
//    echo '</pre>';
        $aSize = array();
        foreach ($aTmp as $i => $el) {
            if ((int)$el > 0) {
                $key = mb_convert_case($aTmp[$i - 1], 2); // первый символ - заглавный
                $key = preg_replace("/:/i", "", $key);
                $aSize[$key] = $el;
            }
        }
        return $aSize;
    }

}