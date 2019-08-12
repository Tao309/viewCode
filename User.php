<?php
namespace Utils;
use Utils\Session as SessionUtil;

class User extends Session
{
	private function checkUserExists($login = null, $password)
	{
		
	}
	
	//Перенести в другой класс
	public static function generateHash($count = 8)
	{		
		$arr = array('a','b','c','d','e','f',
                 'g','h','i','j','k','l',
                 'm','n','o','p','r','s',
                 't','u','v','x','y','z',
                 'A','B','C','D','E','F',
                 'G','H','I','J','K','L',
                 'M','N','O','P','R','S',
                 'T','U','V','X','Y','Z',
                 '1','2','3','4','5','6',
                 '7','8','9','0');
				 
		$pass = null;
		for($i = 0; $i < $count; $i++) {
		  $index = rand(0, count($arr) - 1);
		  $pass .= $arr[$index];
		}
		return $pass;
	}
	
	//Выполнение входа, проверка авторизации
	public static function authentificate($login = null, $password = null)
	{
		$session = new SessionUtil();
		$erroCode =  $session->checkLogin($login, $password);

		switch($erroCode)
        {
            case SessionUtil::LOGIN_SUCCESS:
                $message = 'Вы успешло авторизовались';
                break;
            case SessionUtil::LOGIN_FIELDS_EMPTY:
                $message = 'Поля для входа не заполнены';
                break;
            case SessionUtil::LOGIN_FIELDS_NO_CORRECT:
                $message = 'Данные для входа не корретны';
                break;
            case SessionUtil::LOGIN_PROFILE_IS_NOT_READY:
                $message = 'Ваш профиль не найден';
                break;
            case SessionUtil::LOGIN_IS_BLOCKED:
                $message = 'Ваш профиль заблокирован';
                break;
            case SessionUtil::LOGIN_STOP_RETRY:
                $message = 'Вы превысили количество попыток авторизации';
                break;
            default:
                $message = 'Ошибка при авторизации';

        }

        $data = [
            'error' => $erroCode,
            'message' => $message,
            'requiredFields' => $session->getRequiredFields(),
        ];
		return json_encode($data);
	}

	public static function registration($data = [], $json = true)
    {
        $session = new SessionUtil();
        $erroCode =  $session->checkRegister($data);

        switch($erroCode)
        {
            case SessionUtil::REGISTER_SUCCESS:
            case SessionUtil::REGISTER_DONE:
                $message = 'Вы зарегистрированы';
                break;
            case SessionUtil::REGISTER_FIELDS_EMPTY:
                $message = 'Поля для регистрации не заполнены';
                break;
            case SessionUtil::REGISTER_FIELDS_NO_CORRECT:
                $message = 'Данные для регистрации не корректны';
                break;
            case SessionUtil::REGISTER_LOGIN_EXISTS:
                $message = 'Логин занят';
                break;
            case SessionUtil::REGISTER_EMAIL_EXISTS:
                $message = 'E-mail занят';
                break;
            case SessionUtil::REGISTER_CANNOT_SAVE:
                $message = 'Ошибка при создании пользователя';
                break;
            default:
                $message = 'Ошибка при регистрации';

        }

        $data = [
            'error' => $erroCode,
            'message' => $message,
            'requiredFields' => $session->getRequiredFields(),
            //'model' => $session->getCreatedModel(),
        ];

        if($json)
        {
            return json_encode($data);
        }

        return $data;
    }

    //Запускаем, проверяем сессию в самом начале страницы
	public static function enableSession()
    {
        $session = new SessionUtil();
        $session->enableSessionIfExists();
    }

    //Простая проверка авторизации пользователя
    public static function isLogin()
    {
        //Вероятно сделать другую проверку
        return !empty(SessionUtil::getData()) ? true : false;
    }
    //Получаем данные пользователя
    public static function getProfileValue($nameValue)
    {
        if(!self::isLogin())
        {
            return null;
        }
        $names = explode('.', $nameValue);
        $valueType = 'user';
        if(count($names) > 1)
        {
            $valueType = $names[0];
            $valueName = $names[1];
        }
        else
        {
            $valueName = $names[0];
        }

        $sesionData = SessionUtil::getData();

        if(
            !isset($sesionData[$valueType])
            || !isset($sesionData[$valueType][$valueName])
        )
        {
            return null;
        }

        return $sesionData[$valueType][$valueName] ?? null;
    }
    public static function getUserStatus()
    {
        if(!self::isLogin())
        {
            return \Models\User::STATUS_GUEST;
        }

        $userStatus = self::getProfileValue('status');
        if(!isset(\Models\User::$statusNames[$userStatus]))
        {
            return \Models\User::STATUS_GUEST;
        }

        return $userStatus;
    }
    public static function getUserStatusName()
    {
        if(self::getUserStatus() > \Models\User::STATUS_GUEST)
        {
            return \Models\User::$statusNames[self::getUserStatus()];
        }

        return \Models\User::$statusNames[\Models\User::STATUS_GUEST];
    }
    //Получаем url на аватар размер MINI
    public static function getUserAvatarUrl()
    {
        $width = 40;

        if(self::isLogin())
        {
            $url = self::getProfileValue('avatar');
            if($url)
            {
                return ROOT.\Models\ImageCache::generateCacheImage($width, $url);
            }
        }

        return ROOT.\Models\ImageCache::generateNoImage($width, \Models\ImageCache::FILE_NAME_DEFAULT_AVATAR, \Models\ImageCache::FILE_EXT_JPG);
    }

    //Получаем пользователя текущей сессии
    public static function getCurrentUser()
    {
        if(self::isLogin())
        {
            return SessionUtil::currentUser();
        }

        return null;

    }
	
}