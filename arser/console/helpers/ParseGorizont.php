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
use phpDocumentor\Reflection\Types\Boolean;
use phpDocumentor\Reflection\Types\Integer;
use Yii;
use DiDom\Document;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use common\traits\LogPrint;

//define('DEBUG', true);

class ParseGorizont
{
    use LogPrint;

    protected $oProductsSheet;
    protected $aItems = []; // ссылки на конечный продукт
    protected $aProducts = []; // продукты без разделения на опции
    protected $aGroupProducts = []; // группы товаров
    private $aSection = [];
    private $id;
    private $name;
    // объекты
    private $link;
    private $minid;
    private $maxid;    // массив ссылок на продукты
    private $cntProducts = 0; // количество продуктов

    /**
     * ParseDenx constructor.
     */
    public function __construct($site)
    {
        $this->start();

        $this->site_id = $site["id"];
        $this->name = $site["name"];
        $this->link = $site["link"];
        $this->minid = $site["minid"];
        $this->maxid = $site["maxid"];
        $linksFileName = 'none';
        $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
        //        $this->spreadsheet = $reader->load($linksFileName);

        $this->reprint();
        $this->print("Создался " . self::class);

        $messageLog = [
            'status' => 'Старт ' . self::class,
            'post' => $this->name,
        ];

        Yii::info($messageLog, 'parse_info'); //запись в лог

    }

    /**
     * Вычисляет новую цену
     * @param Integer $price
     * @return int
     */
    private function newPrice(Integer $price): Integer
    {
        return (int)round($price * 1.1, 10, PHP_ROUND_HALF_UP);
    }

    public function run()
    {
        // 1. Соберем разделы мебели
        $this->runSection();

        // 2. Пробежимся по группам товаров $aGroupProducts, заполним товары
        foreach ($this->aGroupProducts as $group) {
            $this->runGroup($group);
        }

        // print_r($this->aProducts);
        // die;

        // 3. Записываем в базу продукты
        $this->runItems();

        $messageLog = ["Загружено " . $this->cntProducts . " штук товаров"];
        Yii::info($messageLog, 'parse_info'); //запись в лог

        $this->endprint();
    }

    private function runSection()
    {
        $doc = ParseUtil::tryReadLink($this->link);

        $aProducts = $doc->first('.level-1')->find('a'); // найдем меню
        foreach ($aProducts as $el) {
            $link = $el->attr('href');
            $this->aGroupProducts[] = $link;
            $this->print("Добавили ссылку на группу товаров: " . $link);
        }

        return;
    }

    private function runGroup(string $link)
    {
        $this->print("Обрабатываем группу: " . $link);
        $doc = ParseUtil::tryReadLink($this->link . $link . '?page_size=1000');

        $aProducts = $doc->find('div.product-item-content a.h4-like'); // найдем ссылку на товар
        foreach ($aProducts as $el) {
            $link = $this->link . $el->attr('href'); // . '/index.php';
            $this->aProducts[] = $link;
            $this->print("Добавили ссылку на товары: " . $link);
        }

        return;
    }

    // private function runGroupProducts()
    // {
    //     $worksheet = $this->spreadsheet->setActiveSheetIndex(0);
    //     $highestRow = $worksheet->getHighestRow();
    //     for ($row = 2; $row <= $highestRow; $row++) {
    //         $category = $worksheet->getCell("A" . $row)->getValue();
    //         //            $category = implode(",", preg_split("/[.,]/", $category));// поправка, если разделитель - "."
    //         $category = ParseUtil::dotToComma($category);
    //         $link = $worksheet->getCell("B" . $row)->getValue();
    //         echo "Добавляем страницу: {$link}\n";
    //         $this->aGroupProducts[] = [
    //             'category' => $category,
    //             'link' => $link
    //         ];
    //         $this->addPagination($category, $link);
    //     }
    //     $this->aGroupProducts = ParseUtil::unique_multidim_array($this->aGroupProducts, 'link');
    //     print_r($this->aGroupProducts);
    //     //        die();
    // }

    private function runItems()
    {
        $product_id = $this->minid;
        foreach ($this->aProducts as $link) {
            $productInfo = $this->getProductInfo($link);
            $productInfo['site_id'] = $this->site_id;
            $productInfo['category'] = 0;
            $productInfo['product_id'] = $product_id++;
            $productInfo['model'] = '5-6 недель';
            $productInfo['manufacturer'] = 'Горизонт, г.Пенза';
            $productInfo['subtract'] = true;
            echo PHP_EOL . 'productInfo=';
            print_r($productInfo);
            if (count($productInfo['aImgLink']) > 0) {
                ArSite::addProduct($productInfo);
                $this->cntProducts++;
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
        if (defined('DEBUG')) {
            print_r('doc=' . $doc->html());
        }

        $ar = array();
        $aImgLink = array();

        $topic = $doc->first('h1');
        $ar["topic"] = $topic->text();
        $ar["title"] = $ar["topic"];

        $product_teh = $doc->find('.editor');
        if ($product_teh) {
            $s = '';
            foreach ($product_teh as $key => $value) {
                $s .= $value->html();
            }
            $ar["product_teh"] = $s;
        } else {
            $ar["product_teh"] = "Нет описания";
        }

        $re = '/\"price\":([\d.]+)}/m';
        $str = $doc->html();

        echo PHP_EOL;
        if (preg_match_all($re, $str, $matches, PREG_SET_ORDER, 0)) {
            $price = $matches[0][1];
        }

        if ($price) {
            $ar["new_price"] = $price;
        } else {
            $ar["new_price"] = 0;
        }

        // картинки
        $imgs = $doc->find('div.swiper-slide img');

        foreach ($imgs as $img) {
            $aImgLink[] = $img->getAttribute('src');
        }
        $ar["aImgLink"] = $aImgLink;

        $ar["old_price"] = "";
        $ar["link"] = $link;

        print_r($link);
        echo PHP_EOL;

        // атрибуты
        $re = '/Габар[т]*иты[^\d]+([\d]+)[^\d]+([\d]+)[^\d]+([\d]+)/m';
        $str = $ar["product_teh"];

        print_r($str);
        echo PHP_EOL;

        if (preg_match_all($re, $str, $matches, PREG_SET_ORDER, 0)) {
            $ar['attr'] = [
                'Ширина' => $matches[0][1],
                'Высота' => $matches[0][2],
                'Глубина' => $matches[0][3],
            ];
        };
        // Габариты указаны в порядке: Ширина Х Высота Х Глубина
        // Print the entire match result
        var_dump($ar);
        echo PHP_EOL;

        // die;

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
