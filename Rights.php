<?php
namespace Utils;

class Rights {
    public static function canView(\UnitAction $model, $userId = null)
    {
        if(!$userId)
        {
            $userId = User::getProfileValue('id');
        }

        return true;
    }
    public static function canEdit(\UnitAction $model, $userId = null)
    {
        if(!$userId)
        {
            $userId = User::getProfileValue('id');
        }

        //Кнопка сохранить для настроек
        if(
            $model instanceof \Models\Option
            || $model instanceof \Models\Cats
            || $model instanceof \Models\CatsItem
        )
        {
            if(User::getProfileValue('status') >= \Models\User::STATUS_ADMIN)
            {
                return true;
            }

            return false;
        }

        if(!self::canVIew($model, $userId ))
        {
            return false;
        }

        if(!self::isOwner($model, $userId))
        {
            return false;
        }

        //Проверка на owner

        return true;
    }
    public static function canDelete(\UnitAction $model, $userId = null)
    {
        if(!$userId)
        {
            $userId = User::getProfileValue('id');
        }

        if(!self::canEdit($model, $userId))
        {
            return false;
        }

        $currentUserId = User::getProfileValue('id');
        $currentUserStatus = User::getProfileValue('status');

        //@todo Проверка выданного права тут и других функций тут
        //@todo Удалять пока может только супер-админ пользователей других
        if($model->getModelClassName() == 'User'
            && $currentUserStatus != \Models\User::STATUS_SUPERADMIN
        )
        {
            return false;
        }

        return true;
    }

    //@todo Добавить в код везде: кнопка и POST
    public static function canAdd(\UnitAction $model)
    {
        $currentUserStatus = User::getProfileValue('status');

        if($model->getModelClassName() == 'User')
        {
            return in_array($currentUserStatus, [
               \Models\User::STATUS_ADMIN,
               \Models\User::STATUS_SUPERADMIN,
            ]);
        }

        return $currentUserStatus >= \Models\User::STATUS_PUBLISHER && $currentUserStatus <= \Models\User::STATUS_SUPERADMIN;
    }

    public static function isOwner(\UnitAction $model, $userId = null)
    {
        if(!$userId)
        {
            $userId = User::getProfileValue('id');
        }

        if(!$model->hasRelatedModel('owner'))
        {
            return false;
        }

        $currentUserId = User::getProfileValue('id');
        $currentUserStatus = User::getProfileValue('status');

        //Просмотр своего профиля
        if($currentUserStatus)
        {
            if($model->getModelClassName() == 'User' && $model->id == $currentUserId)
            {
                return true;
            }
        }

        $checkStatus = $model->owner->status;
        if($model->getModelClassName() == 'User')
        {
            $checkStatus = $model->status;
        }

        if(!$checkStatus)
        {
            return false;
        }

        //Проверка текущего статуса
        switch($currentUserStatus)
        {
            case \Models\User::STATUS_PUBLISHER:
            case \Models\User::STATUS_MODERATOR:
                return $checkStatus < $currentUserStatus;
                break;
            case \Models\User::STATUS_ADMIN:
                return $checkStatus <= $currentUserStatus;
                break;
            case \Models\User::STATUS_SUPERADMIN:
                return true;
                break;
        }

        return ($model->owner->id == $userId);
    }
}