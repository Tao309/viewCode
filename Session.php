<?php
namespace Utils;

class Session {
    // Ошибки при авторизации
    const LOGIN_FIELDS_EMPTY = 10;
    const LOGIN_FIELDS_NO_CORRECT = 20;
    const LOGIN_PROFILE_IS_NOT_READY = 21;
    const LOGIN_IS_BLOCKED = 25;
    const LOGIN_STOP_RETRY = 30;
    const LOGIN_SUCCESS = 200;
    const LOGIN_DENIED = 400;

    const REGISTER_FIELDS_EMPTY = 10;
    const REGISTER_FIELDS_NO_CORRECT = 20;
    const REGISTER_LOGIN_EXISTS = 30;
    const REGISTER_EMAIL_EXISTS = 40;
    const REGISTER_CANNOT_SAVE = 100;
    const REGISTER_SUCCESS = 200;
    const REGISTER_DONE = 210;
    const REGISTER_DENIED = 400;

    //Входящие данные при авторизации
    private $insideLogin;
    private $insidePassword;

    //Входящие данные при регистрации
    private $regLogin;
    private $regEmail;
    private $regPassword;

    //Можно создать сессию
    private $accessCreateSession = false;

    public function __construct($data = [])
    {

    }

    //Одобряем создание сессии для показа на сайте
    private function acceptCreateSession()
    {
        $this->accessCreateSession = true;
    }

    //Проверка одобрения создания сессии
    private function checkAcceptCreateSession()
    {
        if(!$this->accessCreateSession || !$this->insideLogin || !$this->insidePassword)
        {
            echo json_encode([
                'error' => self::LOGIN_DENIED,
                'message' => 'No accept create session',
            ]);
            exit;
        }
    }

    //Создаём новый uid_hash для сессии
    private function createNewUidHash()
    {
		//скрыто
		$hash = 'test';

        return $hash;
    }

    public static function createHashPassword($password)
    {
		//скрыто
        $password = 'test';

        return $password;
    }

    public function checkLogin($login = null, $password = null)
    {
        if(!$login || !$password)
        {
            if(!$login)
            {
                $this->addRequiredFields('login');
            }

            if(!$password)
            {
                $this->addRequiredFields('password');
            }

            return self::LOGIN_FIELDS_EMPTY;
        }

        // @todo Проверка символов, длины

        $this->insideLogin = $login;
        $this->insidePassword = $password;

        return $this->checkLoginData();
    }
    //Поля, которые не заполнены
    private $requiredFields = [];
    //Получаем поля, которые необходимо заполнить
    public function getRequiredFields()
    {
        return $this->requiredFields;
    }
    //Какое поле пустое
    private function addRequiredFields($field)
    {
        $fields = $this->requiredFields;
        $fields[] = 'User['.$field.']';
        //$fields[] = $field;

        $this->requiredFields = array_unique($fields);
    }

    private $createdModel = null;
    //Получаем модель после создания
    public function getCreatedModel()
    {
        return $this->createdModel;
    }

    // Проверка регистрации данных
    public function checkRegister($data = [])
    {
        $login = trim($data['login']);
        $email = trim($data['email']);
        $password = trim($data['password']);

        if(!$login || !$email || !$password)
        {
            if(!$login)
            {
                $this->addRequiredFields('login');
            }
            if(!$email)
            {
                $this->addRequiredFields('email');
            }
            if(!$password)
            {
                $this->addRequiredFields('password');
            }

            return self::REGISTER_FIELDS_EMPTY;
        }

        // @todo Проверка символов, длины
        // @todo Проверка названия логинов на доступность

        $this->regLogin = $login;
        $this->regEmail = $email;
        $this->regPassword = $password;

        return $this->checkRegisterData();
    }
    //Создаём нового пользователя
    private function registerUser()
    {

        $response = \Models\User::saveModel(null, false, true);

        if($response['result'] == \tString::RESPONSE_RESULT_SUCCESS)
        {
            $this->createdModel = $response['model'];
            return self::REGISTER_DONE;
        }

        return self::REGISTER_CANNOT_SAVE;
    }
    private static $sessionEnable = false;//Есть ли сессия авторизованная
    private static $userData = null;//Записанные данные пользователя после проверки сессии
    private static $currentUser = null;//Текущей пользователь сессии
    public static function currentUser()
    {
        return self::$currentUser;
    }

    protected static function getData()
    {
        return self::$userData;
    }
    //Записываем глобальные переменные
    private function setGlobalsData($data = [])
    {
        if(empty($data))
        {
            return false;
        }

        /**
         * @var \Models\User $user
         */
        $user = self::$currentUser;

        self::$sessionEnable = true;

        $userData['user'] = [
            'id' => $data['id'],
            'login' => $data['login'],
            'email' => $data['email'],
            'status' => $data['status'],
        ];

        if($user->hasRelatedModel('avatar'))
        {
            $userData['user']['avatar'] = $user->avatar->getViewPath(\Models\Image::IMAGE_SIZE_MINI);
            $userData['user']['avatar_normal'] = $user->avatar->getViewPath(\Models\Image::IMAGE_SIZE_NORMAL);
            $userData['user']['avatar_full'] = $user->avatar->getViewPath(\Models\Image::IMAGE_SIZE_FULL);
        }
        else
        {
            $userData['user']['avatar'] =
            $userData['user']['avatar_normal'] =
            $userData['user']['avatar_full'] = null;
        }


        self::$userData = $userData;

        return true;
    }

}

?>