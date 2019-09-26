<?php

namespace Models;

/**
 * Class News
 * @package Models
 *
 * @var string $title
 */
class News extends \Unit
{
    public function __toString()
    {
        return $this->title;
    }

    protected function beforeSave()
    {
        $oldTitle = $this->getOldValues()['title'];
        if($oldTitle != $this->title)
        {
            $this->engTitle = \tString::stringToTranslit($this->title, 1);
        }

        $engTitle = (trim($this->engTitle)) ? $this->engTitle : $this->title;

        $this->engTitle = \tString::stringToTranslit($engTitle, 1);

        return parent::beforeSave();
    }

    protected function unitData()
    {
        $data = [
            'details' => [
                'title',
                'engTitle',
                'description',
            ],
            'rules' => [
                'title' => [
                    'type' => self::RULE_TYPE_TEXT,
                    'limit' => 40,
                    'required' => true,
                ],
                'engTitle' => [
                    'type' => self::RULE_TYPE_TEXT,
                    'limit' => 60,
                ],
                'description' => [
                    'type' => self::RULE_TYPE_DESCRIPTION,
                    'limit' => 1000,
                ],
            ],
            'relations' => [
                'cat' => [
                    'type' => self::HAS_ONE_REQUIRED,
                    'model' => self::MODEl_NAME_CATS_ITEM,
                ],
                'cover' => [
                    'type' => self::HAS_ONE,
                    'model' => self::MODEl_NAME_IMAGE,
                ],
                'images' => [
                    'type' => self::HAS_MANY,
                    'model' => self::MODEl_NAME_IMAGE,
                ]
            ],
        ];

        return $data;
    }

    protected function translatedLabels()
    {
        $labels = [];
        $labels['title'] = 'Название';
        $labels['engTitle'] = 'Название транслит';
        $labels['description'] = 'Описание';
        $labels['cover'] = 'Обложка';
        $labels['cat'] = 'Категория';

        return $labels;
    }

    public function getViewUrl($id = null)
    {
        $name = '';
        if($this->engTitle)
        {
            $name .= '-'.$this->engTitle;
        }

        return ROOT.mb_strtolower($this->getModelClassName()).'/'.$this->id.$name.'.html';
    }
}