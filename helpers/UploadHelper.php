<?php

namespace gromovfjodor\imagemanager\helpers;

use Yii;
use yii\helpers\BaseFileHelper;
use yii\imagine\Image;
use yii\web\Response;
use gromovfjodor\imagemanager\models\ImageManager;

class UploadHelper
{

    const UPLOAD_NAME = 'imagemanagerFiles';
    const RETURN_MODE_NAMES = 0;
    const RETURN_MODE_IDS = 1;

    /**
     * @param string $uploadNames
     * @param int    $returnMode
     * @param bool $
     *
     * @return array
     * @throws \yii\base\Exception
     * @throws \yii\db\Exception
     */
    public static function upload(string $uploadNames = self::UPLOAD_NAME, int $returnMode = self::RETURN_MODE_NAMES, bool $checkRights = true)
    {
        // Check if the user is allowed to upload the image
        if ($checkRights && Yii::$app->controller->module->canUploadImage == false) {
            // Return the response array to prevent from the action being executed any further
            return [];
        }

        // Create the transaction and set the success variable
        $transaction = Yii::$app->db->beginTransaction();
        $bSuccess    = false;

        //disable Csrf
        Yii::$app->controller->enableCsrfValidation = false;

        //return default
        if ($returnMode == self::RETURN_MODE_NAMES) {
            $return = $_FILES;
        } else {
            $return = [];
        }

        //set media path
        $sMediaPath = \Yii::$app->imagemanager->mediaPath;

        //create the folder
        BaseFileHelper::createDirectory($sMediaPath);

        //check file isset
        if (isset($_FILES[$uploadNames]['tmp_name'])) {

            //loop through each uploaded file
            foreach ($_FILES[$uploadNames]['tmp_name'] AS $key => $sTempFile) {
                //collect variables
                $sFileName      = $_FILES[$uploadNames]['name'][$key];
                $sFileExtension = pathinfo($sFileName, PATHINFO_EXTENSION);
                $iErrorCode     = $_FILES[$uploadNames]['error'][$key];
                //if uploaded file has no error code  than continue;
                if ($iErrorCode == 0) {
                    //create a file record
                    $model           = new ImageManager();
                    $model->fileName = str_replace("_", "-", $sFileName);
                    $model->fileHash = Yii::$app->getSecurity()->generateRandomString(32);
                    //if file is saved add record
                    if ($model->save()) {
                        //move file to dir
                        $sSaveFileName = $model->id . "_" . $model->fileHash . "." . $sFileExtension;
                        //move_uploaded_file($sTempFile, $sMediaPath."/".$sFileName);
                        //save with Imagine class
                        Image::getImagine()->open($sTempFile)->save($sMediaPath . "/" . $sSaveFileName);
                        $bSuccess = true;
                        if ($returnMode == self::RETURN_MODE_IDS) {
                            $return[] = $model->id;
                        }
                    }
                }
            }
        }

        if ($bSuccess) {
            // The upload action went successful, save the transaction
            $transaction->commit();
        } else {
            // There where problems during the upload, kill the transaction
            $transaction->rollBack();
            $return = [];
        }

        // return array
        return $return;
    }
}
