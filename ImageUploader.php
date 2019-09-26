<?php

namespace Utils;

class ImageUploader {
    //Данные загружаемого файла
    private $file;
    private $tempFile;
    private $extension;
    private $fileName;
    private $size;

    private $initialModel;//Загружаемая модель
    private $type;//COVER или SCREEN

    public function __construct($file, \Unit $initialModel, $type)
    {
        $this->file =  $file;
        $this->initialModel = $initialModel;
        $this->type = $type;

        $this->setImageData();
    }

    //Получаем расширение файла
    private function setImageData()
    {
        //@todo Ошибку обработать
        //$this->file['error']

        $fileName = preg_replace('!(.*?)\.(.*?)$!si', '$1', $this->file['name']);
        $this->fileName = $fileName;

        $this->extension = self::getExtType($this->file['type']);
        $this->size = $this->file['size'];
        $this->tempFile = $this->file['tmp_name'];
    }

    //Получаем расширение из mime типа файла 'image/jpeg' => 'jpg'
    //@todo Проверка допустимых значений для 'IMAGE/jpeg'. Загружать только картинки
    //@todo Список разрешённых для загрузки форматов
    public static function getExtType($type)
    {
        $types = explode('/', $type);

        $type = $types[1];

        if(in_array($type, [
            'jpeg',
            'jpg',
        ]))
        {
            $type = 'jpg';
        }

        return $type;
    }

    private $errors = [];
    private function addError($message)
    {
        $errors = $this->errors;
        $errors[] = $message;

        $this->errors = $errors;
    }
    public function getErrors()
    {
        return implode(PAGE_EOL, $this->errors);
    }

    private $uploadResult = [];
    public function getUploadResult()
    {
        return $this->uploadResult;
    }

    public function upload()
    {
        $imageModel = new \Models\Image;
        $imageModel->title = '';
        $imageModel->type = $this->type;

        $imageModel->setInitialModel($this->initialModel);

        $imageModel->fileName = $this->fileName.'.'.$this->extension;

        if(!$imageModel->save())
        {
            $this->addError($imageModel->getSaveErrors());
            return false;
        }

        $isCover = false;
        if($imageModel->type === \Models\Image::TYPE_IMAGE_COVER)
        {
            $isCover = true;
        }

        //Записываем в БД, получаем ID
        $fileName = $imageModel->id.'_'.time().'.'.$this->extension;
        $imageModel->fileName = $fileName;

        if(!$imageModel->save())
        {
            $this->errors = $imageModel->getSaveErrors();
            return false;
        }

        switch($this->extension) {
            case 'jpg':
            case 'jpeg':
                $image = @imagecreatefromjpeg($this->tempFile);
                break;
            case 'png':
                $image = @imagecreatefrompng($this->tempFile);
            break;
            case 'gif':
                $image = @imagecreatefromgif($this->tempFile);
                break;
            default:
                $this->addError('Ошибка формата загружаемого файла ('.$this->extension.')');
                return false;
        }

        if(!$image)
        {
            $this->addError('Загружаемый файл повреждён');
            return false;
        }

        list($width_orig, $height_orig, $mime, $attr) = \tFile::getimagesize($this->tempFile);

        $imageModel->checkPathExists();

        if($isCover)
        {
            $paramsFull = [360,360];
        }
        else
        {
            $paramsFull = [600,600];
        }

        $pathList = [
            $imageModel->getUploadPath(\Models\Image::IMAGE_SIZE_MINI) => [120,120],
            $imageModel->getUploadPath(\Models\Image::IMAGE_SIZE_NORMAL) => [360,360],
            $imageModel->getUploadPath(\Models\Image::IMAGE_SIZE_FULL) => $paramsFull,
        ];

        //@todo Соблюдать соотношение сторон
        $useWM = false;

        foreach($pathList as $path => $data)
        {
            list($width, $height) =  self::getCropParams([$data[0], $data[1]],[$width_orig, $height_orig]);

            $imageTemp = imagecreatetruecolor($width, $height);

            if(!$useWM)
            {
                imagealphablending($imageTemp, false);
                imagesavealpha($imageTemp, true);
            }

            imagecopyresampled($imageTemp, $image, 0, 0, 0, 0, $width, $height, $width_orig, $height_orig);

            switch($this->extension) {
                case 'jpg':
                case 'jpeg':
                    imagejpeg($imageTemp, $path.$fileName, 100);
                    break;
                case 'png':
                    imagepng($imageTemp, $path.$fileName,PNG_NO_FILTER);
                    break;
                case 'gif':
                    imagegif($imageTemp, $path.$fileName);
                    break;
            }

            imagedestroy($imageTemp);
        }

        $this->uploadResult = [
            'mini' => $imageModel->getViewPath(\Models\Image::IMAGE_SIZE_MINI),
            'normal' => $imageModel->getViewPath(\Models\Image::IMAGE_SIZE_NORMAL),
            'full' => $imageModel->getViewPath(\Models\Image::IMAGE_SIZE_FULL),
            //'model_name' => $imageModel->model_name,
            //'model_id' => $imageModel->model_id,
            'htmlElement' => $imageModel->generateOneScreen($isCover, \ViewList::TYPE_VIEW_EDIT),
            'imageModel' => $imageModel,
        ];

        return true;
    }

    private static function getCropParams($needParams, $origParams)
    {
        //@todo Протестировть загрузку различных картинок
        $widthScale = ($origParams[0]/$needParams[0]);
        $heightScale = ($origParams[1]/$needParams[1]);

        //Проверка сторон need

        //Проверка сторон оригинала

        //Сравнение длин и высот

        $minScale = min([$widthScale, $heightScale]);
        $maxScale = max([$widthScale, $heightScale]);

        return [
            (int)($origParams[0] / $maxScale),
            (int)($origParams[1] / $maxScale),
        ];
    }

    //Получаем путь без расширения и расширение
    public static function getImageInfoByPath($path)
    {
        /*
         Array
        (
            [dirname] => upload/user/cover/mini
            [basename] => 51_1552739739.png
            [extension] => png
            [filename] => 51_1552739739
        )
         */
        $data = pathinfo($path);
        $data['dirname'] = str_ireplace('upload/','',$data['dirname']).'/';

        return $data;
    }

}