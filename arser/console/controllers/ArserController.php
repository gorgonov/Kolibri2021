<?php

namespace console\controllers;

use yii\console\Controller;
use console\models\ArSite;
use yii\debug\panels\DumpPanel;

// usage: yii.bat arser <modulName>

class ArserController extends Controller
{
    /**
     * action default
     *
     * @param string $modul
     * @throws \Throwable
     */
    public function actionIndex(string $modul='get')
    {
        if (is_integer($modul)){
            $site = ArSite::getSiteById($modul);
        }

        if (is_string($modul)){
            if ($modul=='get') {
                $site = ArSite::getSiteToParse();
            } else {
                $site = ArSite::getSiteByName($modul);
            }
        }

        if (!isset($site)){
            echo 'Site ' . $modul . ' not found!';
            die();
        }

        $oName = "console\helpers\Parse" . ucfirst( $site['modulname'] );

        if (class_exists($oName)) {
            $oParse = new $oName($site);
            ArSite::delModulData($site["id"]);
            ArSite::setStatus($site["id"],'parse');
            $oParse->run();
            ArSite::setStatus($site["id"],'new');
        }

        return ;
    }

}
