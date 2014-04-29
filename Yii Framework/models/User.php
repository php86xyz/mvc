<?php

class User extends ActiveRecord {

	const SCENARIO_UPDATE_PASSWORD = 'updatePassword';

	const ROLE_GUEST = 'guest';
	const ROLE_USER = 'user';
	const ROLE_ADMIN = 'admin';
        const ROLE_SELLER = 'seller';

	/**
	 * @var
	 */
	private $_identity;

	/**
	 * @var string
	 */
	public $password_confirmation;

	/**
	 * @var array
	 */
	public $roles = array(
		self::ROLE_GUEST => 'Гость',
		self::ROLE_USER => 'Пользователь',
		self::ROLE_ADMIN => 'Администратор',
		self::ROLE_SELLER => 'Продавец',
	);

	/**
	 * @param string $className
	 * @return User
	 */
	public static function model($className = __CLASS__) {
		return parent::model($className);
	}

	/**
	 * @return string
	 */
	public function tableName() {
		return 'user';
	}

	/**
	 * @return array
	 */
	public function rules() {
		return array(
			array('username', 'unique', 'message' => 'Пользователь с таким телефонным номером уже существует'),
			array('password, username', 'required', 'message' => parent::$requiredMessage),
			array('username, password, first_name, middle_name, last_name, address, role', 'safe'),
		);
	}

	/**
	 * @return array
	 */
	public function relations() {
		return array(
			'recipients' => array(self::HAS_MANY, 'Recipient', 'user_id'),
			'orders' => array(self::HAS_MANY, 'Order', 'user_id'),
		);
	}

	/**
	 * @return array
	 */
	public function attributeLabels() {
		return array(
			'first_name' => 'Имя',
			'middle_name' => 'Отчество',
			'last_name' => 'Фамилия',
			'username' => 'Телефон',
			'address' => 'Адрес',
			'role' => 'Роль',
			'password' => 'Пароль'
		);
	}

	/**
	 * @return array
	 */
	public function getFields() {
		return array(
			'username' => array('type' => 'string', 'htmlOptions' => array()),
			'role' => array('type' => 'select', 'options' => $this->roles),
			'last_name' => array('type' => 'string'),
			'first_name' => array('type' => 'string'),
			'middle_name' => array('type' => 'string'),
			'address' => array('type' => 'string'),
		);
	}

	/**
	 * @return array
	 */
	public function getList() {
		$labels = $this->attributeLabels();
		return array(
			'username' => array('name' => $labels['username']),
			'last_name' => array('name' => $labels['last_name']),
			'first_name' => array('name' => $labels['first_name']),
			'middle_name' => array('name' => $labels['middle_name']),
			'address' => array('name' => $labels['address']),
		);
	}

	/**
	 * @param $password string
	 * @return bool
	 */
	public function validatePassword($password) {
		return $this->hashPassword($password, $this->salt) === $this->password;
	}

	/**
	 * @param $password string
	 * @param $salt string
	 * @return string
	 */
	public function hashPassword($password, $salt) {
		return md5($salt . $password);
	}

	/**
	 * @return string
	 */
	public function generateSalt() {
		return uniqid('', true);
	}

	/**
	 * @return bool
	 */
	public function beforeValidate() {
		if (! $this->salt && $this->password != '') {
			$this->salt = $this->generateSalt();
			$this->password = $this->hashPassword($this->password, $this->salt);
		}
		return parent::beforeValidate();
	}

	/**
	 * @return bool
	 */
	protected function beforeSave() {
		if ($this->getScenario() == self::SCENARIO_UPDATE_PASSWORD)
			$this->password = $this->hashPassword($this->password, $this->salt);

		return parent::beforeSave();
	}

	/**
	 * @param bool $rememberMe
	 * @return bool
	 */
	public function login($rememberMe = false) {
		if ($this->_identity === null) {
			$this->_identity = new UserIdentity($this->username, $this->password);
			$this->_identity->authenticate();
		}
		if ($this->_identity->errorCode === UserIdentity::ERROR_NONE) {
			$duration = $rememberMe ? 3600*24*30 : 0;

			Yii::app()->user->login($this->_identity, $duration);
			return true;
		} else
			return false;
	}

	/**
	 * @return string
	 */
	public function getFullName() {
		return $this->last_name.' '.$this->first_name.' '.$this->middle_name;
	}
	
	public function getFullInfo() {
		return $this->last_name.' '.$this->first_name.' '.$this->middle_name. ', '.$this->username;
	}
}