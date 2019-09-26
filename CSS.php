<?php
/*
 * Проверка, защита доступа к файлам
 */

class CSS extends Obfuscate {
    const TYPE_ELEMENTS = 'elements';
    const TYPE_PLUGINS = 'plugins';
    const TYPE_ADMIN = 'admin';
    const TYPE_TEMPLATE = 'template';

    //Куда сохранять сжатые файлы
    const COMPRESS_PATH = 'app/assets/css/';

    private static $appendFiles = [];

	public static function generateHeaderStyles($extract = false, $adminView = false)
	{
		$input = null;
		
		$array = [];

		$rootPath = 'app/assets/css/';

        $array[self::TYPE_ELEMENTS] = [
            $rootPath.'fonts',
            $rootPath.'elements',
            $rootPath.'modalElements',
            $rootPath.'pageBody',
        ];

        $array[self::TYPE_PLUGINS] = [
            'app/assets/css/plugins/testCompress',//test
            'app/assets/css/plugins/jquery-ui.min',
            'app/assets/css/plugins/jquery-ui.structure',
            'app/assets/css/plugins/tGallery',
        ];

        //@todo делать проверку шаблона
		$template = 'default';

		if($adminView)
        {
            $array[self::TYPE_ADMIN] = [
                'app/assets/css/admin/pageBody',
            ];
        }
        else
        {
            //Стили шаблона
            $array[self::TYPE_TEMPLATE] = [
                'app/assets/css/'.$template.'/pageBody',
                'app/assets/css/'.$template.'/elements',
            ];
        }
		
		if($extract)
		{
			$input .= '<style>'.PAGE_EOL;
		}

		//Время жизни файла в минутах
        $defaultLifePeriod = 60*24*7;//Неделя
		$lifePeriod = [
		    self::TYPE_ELEMENTS => $defaultLifePeriod,
            self::TYPE_PLUGINS => $defaultLifePeriod,
            self::TYPE_ADMIN => $defaultLifePeriod,
            self::TYPE_TEMPLATE => $defaultLifePeriod,
        ];
		
		foreach($array as $type => $files)
		{
            if(in_array($type, [
                    self::TYPE_ELEMENTS,
                    self::TYPE_PLUGINS,
                    self::TYPE_TEMPLATE,
                ]))
            {
                $fileNames = [
                    self::COMPRESS_PATH,
                    'min_'.$type.'.'.\Cache::FILE_EXT_CSS,
                ];

                //Проверяем если файл был изменен недавно
                //@todo Продумать этот пункт, а то постоянно пересоздаёт. Сделать только локально?
                $periodDay = 60*2;
                if(tFile::file_exists(zROOT.implode('', $fileNames)) && !tFile::checkFilePeriod($fileNames[0], $fileNames[1], $periodDay))
                {
                    tFile::deleteFile($fileNames[0], $fileNames[1]);
                }

                $fullFileName = ROOT.implode('', $fileNames).'?time=';

                $lifePeriod = $lifePeriod[$type] ?? $defaultLifePeriod;
                $period = 100*$lifePeriod;

                if(tFile::file_exists(zROOT.implode('', $fileNames)) && !tFile::checkFilePeriod($fileNames[0], $fileNames[1], $period))
                {
                    $filetime = tFile::filemtime(zROOT.implode('', $fileNames));
                    $input .= self::renderRow($fullFileName.$filetime).PAGE_EOL;
                    continue;
                }

                //Выставлять по умолчанию
                $constants = [
                    'color' => [],
                    'background' => [],
                ];

                for($i = 1; $i < 501; $i++)
                {
                    $constants['color'][$i] = TFL::source()->getDataOptionValue(Models\Option::NAME_DESIGN_COLOR, 'color_'.$i);
                    $constants['background'][$i] = TFL::source()->getDataOptionValue(Models\Option::NAME_DESIGN_COLOR, 'background_'.$i);
                }

                $content = self::joinFilesContent($files, \Cache::FILE_EXT_CSS);
                $content = self::compressCSS($content);
                $content = self::replaceConstants($content, $constants);
                $content = self::replaceCSSproperties($content);

                \tFile::writeFile($fileNames[0], $fileNames[1], $content, false);

                $filetime = tFile::filemtime(zROOT.implode('', $fileNames));

                $input .= self::renderRow($fullFileName.$filetime).PAGE_EOL;

                continue;
            }

		    foreach($files as $file)
            {
                $file .= '.'.\Cache::FILE_EXT_CSS;
                if(tFile::file_exists(zROOT.$file))
                {
                    if($extract)
                    {
                        $input .= self::compressCSS(tFile::file_get_contents(zROOT.$file));
                    }
                    else
                    {
                        $input .= self::renderRow(ROOT.$file).PAGE_EOL;
                    }
                }
            }
		}
		
		if($extract)
		{
			$input .= PAGE_EOL.'</style>';
		}

        if(!empty(self::$appendFiles))
        {
            foreach(self::$appendFiles as $index => $file)
            {
                $file .= '.'.\Cache::FILE_EXT_CSS;
                $input .= self::renderRow(ROOT.$rootPath.$file).PAGE_EOL;
            }
        }
		
		return $input;
	}

    //$path без ".css" добавлять
    //@todo Сделать CSS обработчик, для кроссбраузерности
    public static function appendFile($path = null, $type = 'header')
    {
        self::$appendFiles[] = $path;
    }
}