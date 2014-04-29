<?php

class Order extends CRUDModel {

	const STATUS_NEW = 'new';
	const STATUS_WAIT_PAYMENT = 'wait_payment';
	const STATUS_IN_ASSEMBLY = 'in_assembly';
	const STATUS_MADE = 'made';
	const STATUS_CANCEL = 'cancel';

	const CLASS_RED = 'label-important';
	const CLASS_YELLOW = 'label-warning';
	const CLASS_GREEN = 'label-success';
	const CLASS_BLUE = 'label-info';
	const CLASS_GRAY = '';

	const PAYMENT_UNSELECTED = 'unselected';
	const PAYMENT_ROBOKASSA = 'robokassa';
	const PAYMENT_BANK = 'bank';

	/**
	 * @var string
	 */
	private $_oldStatus;

	/**
	 * @var array
	 */
	public static $paymentMethods = array(
		self::PAYMENT_UNSELECTED => 'Не выбран',
		self::PAYMENT_ROBOKASSA => 'Robokassa',
		self::PAYMENT_BANK => 'Банковский перевод'
	);

	/**
	 * @var array
	 */
	public static $statuses = array(
		self::STATUS_NEW => 'Требует проверки (новый)',
		self::STATUS_WAIT_PAYMENT => 'Ждет оплаты',
		self::STATUS_IN_ASSEMBLY => 'В сборке',
		self::STATUS_MADE => 'Выполнен',
		self::STATUS_CANCEL => 'Отменен',
	);

	/**
	 * @var array
	 */
	public static $relatedStatuses = array(
		self::STATUS_NEW => array(
			array(
				'status' => self::STATUS_WAIT_PAYMENT,
				'name' => 'Подтвердить заказ',
				'class' => 'btn btn-success'
			),
			array(
				'status' => self::STATUS_CANCEL,
				'name' => 'Отменить заказ',
				'class' => 'btn btn-danger'
			)
		),

		self::STATUS_WAIT_PAYMENT => array(
			array(
				'status' => self::STATUS_IN_ASSEMBLY,
				'name' => 'Подтвердить оплату',
				'class' => 'btn btn-success'
			),
			array(
				'status' => self::STATUS_CANCEL,
				'name' => 'Отменить заказ',
				'class' => 'btn btn-danger'
			)
		),

		self::STATUS_IN_ASSEMBLY => array(
			array(
				'status' => self::STATUS_MADE,
				'name' => 'Заказ доставлен',
				'class' => 'btn btn-success'
			),
			array(
				'status' => self::STATUS_CANCEL,
				'name' => 'Заказ отменен',
				'class' => 'btn btn-danger'
			)
		),

		self::STATUS_MADE => array(),
		self::STATUS_CANCEL => array(),
	);

	/**
	 * @param string $className
	 * @return Order
	 */
	public static function model($className = __CLASS__) {
		return parent::model($className);
	}

	/**
	 * void
	 */
	protected function afterFind() {
		$this->_oldStatus = $this->status;
	}

	/**
	 * @return string
	 */
	public function tableName() {
		return 'order';
	}

	/**
	 * @return array
	 */
	public function relations() {
		return array(
			'histories' => array(self::HAS_MANY, 'OrderHistory', 'order_id'),
			'user' => array(self::BELONGS_TO, 'User', 'user_id'),
			'recipient' => array(self::BELONGS_TO, 'Recipient', 'recipient_id'),
		);
	}

	/**
	 * @return array
	 */
	public function rules() {
		return array(
			array('status, payment_method, bill_check', 'safe'),
		);
	}

	/**
	 * @return array
	 */
	public function attributeLabels() {
		return array(
			'id' => 'Номер',
			'user_id' => 'Покупатель',
			'recipient_id' => 'Получатель',
			'structure' => 'Состав заказа',
			'total' => 'Сумма',
			'status' => 'Статус',
			'created_at' => 'Дата',
			'Username' => 'Заказчик',
			'UserPhone' => 'Телефон',
			'TimePassed' => 'Прошло',
			'ContainsLink' => '',
			'HistoryLink' => '',
			'payment_method' => 'Способ оплаты',
			'bill_check' => 'Чек оплаты'
		);
	}

	/**
	 * @return array
	 */
	public function getFields() {
		return array(
			'id' => array('type' => 'string', 'htmlOptions' => array('disabled' => true)),
			'status' => array('type' => 'select', 'options' => self::$statuses, 'htmlOptions' => array('disabled' => true)),
			'payment_method' => array('type' => 'select', 'options' => self::$paymentMethods),
		);
	}

	/**
	 * @return array
	 */
	public function getList() {
		$labels = $this->attributeLabels();
		return array(
			'id' => array('name' => $labels['id']),
			'created_at' => array('name' => $labels['created_at']),
			'Username' => array('name' => $labels['Username']),
			'UserPhone' => array('name' => $labels['UserPhone']),
			'total' => array('name' => $labels['total']),
			'payment_method' => array('name' => $labels['payment_method'], 'translation' => self::$paymentMethods),
			'ColoredStatus' => array('name' => $labels['status']),
			'TimePassed' => array('name' => $labels['TimePassed']),
			'ContainsLink' => array('name' => $labels['ContainsLink']),
			'HistoryLink' => array('name' => $labels['HistoryLink']),
		);
	}

	/**
	 * @return bool
	 */
	public function beforeSave() {
		if($this->isNewRecord) {
			$this->user_id = Yii::app()->user->getId();
			$this->payment_method = self::PAYMENT_UNSELECTED;
		}

		if($this->_oldStatus != $this->status) {
			if($this->status == Order::STATUS_IN_ASSEMBLY)
				$this->create1CDocument();
			$this->createHistory('Статус изменен на "'.self::$statuses[$this->status].'"');
		}

		return parent::beforeSave();
	}

	/**
	 * void
	 */
	public function afterSave() {
		if($this->getIsNewRecord())
			$this->createHistory('Создан. Ждет оплаты');
	}

	/**
	 * @param $description string
	 */
	public function createHistory($description) {
		$history = new OrderHistory();
		$history->setAttributes(array(
			'description' => $description,
			'order_id' => $this->id,
			'user_id' => Yii::app()->user->getId(),
		));
		$history->save();
	}

	/**
	 * @return string
	 */
	public function getUsername() {
		return $this->user->getFullName();
	}

	/**
	 * @return string
	 */
	public function getUserPhone() {
		return $this->user->username;
	}

	/**
	 * @return string
	 */
	public function getTimePassed() {
		return DateHelper::getTimePassed($this->created_at);
	}

	/**
	 * @return string
	 */
	public function getHistoryLink() {
		return CHtml::link('<i class="halflings-icon envelope halflings-icon"></i>', 'order/history/'.$this->id, array('class' => 'btn', 'data-original-title' => $this->getLastHistory()->description));
	}

	/**
	 * @return string
	 */
	public function getContainsLink() {
		return CHtml::link('<i class="halflings-icon search halflings-icon"></i>', 'order/contains/'.$this->id, array('class' => 'btn', 'data-original-title' => $this->getLastHistory()->description));
	}

	/**
	 * @return OrderHistory
	 */
	public function getLastHistory() {
		if($this->histories)
			return $this->histories[count($this->histories) - 1];
	}

	/**
	 * @return string
	 */
	public function getColoredStatus(){
		return '<span status="'.$this->status.'" class="label '.$this->getStatusColor().'">'.self::$statuses[$this->status].'</span>';
	}

	/**
	 * @return string
	 */
	private function getStatusColor() {
		switch ($this->status) {
			case self::STATUS_NEW:
				return self::CLASS_RED;
			case self::STATUS_WAIT_PAYMENT:
				if((time() - strtotime($this->updated_at)) > DateHelper::DATE_DAY * 3)
					return self::CLASS_RED;
				return self::CLASS_YELLOW;
			case self::STATUS_IN_ASSEMBLY:
				if((time() - strtotime($this->updated_at)) > DateHelper::DATE_DAY * 2)
					return self::CLASS_RED;
				return self::CLASS_BLUE;
			case self::STATUS_MADE:
				return self::CLASS_GREEN;
			case self::STATUS_CANCEL:
				return self::CLASS_GRAY;
		}
	}

	/**
	 * @param $status string
	 * @return string
	 */
	public function getMessage($status) {
		if($this->status == self::STATUS_NEW) {
			if($status == self::STATUS_WAIT_PAYMENT)
				return 'Ваш заказ №'.$this->id.' прошел проверку, готов к оплате.';
			if($status == self::STATUS_CANCEL)
				return 'Ваш заказ №'.$this->id.' отменен, комментарий на сайте.';
		}
		if($this->status == self::STATUS_WAIT_PAYMENT) {
			if($status == self::STATUS_IN_ASSEMBLY)
				return 'Ваш заказ №'.$this->id.' успешно оплачен.';
			if($status == self::STATUS_CANCEL)
				return 'Ваш заказ №'.$this->id.' отменен, комментарий на сайте.';
		}
		if($this->status == self::STATUS_IN_ASSEMBLY) {
			if($status == self::STATUS_MADE)
				return 'Ваш заказ №'.$this->id.' успешно доставлен.';
			if($status == self::STATUS_CANCEL)
				return 'Ваш заказ №'.$this->id.' отменен, комментарий на сайте.';
		}
		return 'непонятно';
	}

	/**
	 * void
	 */
	public function create1CDocument() {
		file_put_contents(Yii::getPathOfAlias('webroot').'/data/'.'order'.$this->id.'.txt', $this->get1CData());
	}

	/**
	 * @return string
	 */
	private function get1CData() {
		$data = '';
		$data .= date('YmdHis', strtotime($this->created_at)).';';
		$data .= $this->id.';';
		$data .= 'Покупатель: '. $this->user->getFullInfo().';';
		$data .= 'Получатель: '. $this->recipient->getFullInfo().';';
		$data .= $this->total.';'."\n";
		foreach(unserialize($this->structure) as $product) {
                        $data .= $product['article'].';';
                        $data .= $product['name'].';';
                        $productModel = Product::model()->findByPk($product['id']);
                        $data .= $productModel->category->name.';';
			$data .= $product['cost'].';';
			$data .= $product['quantity'].';';
			$data .= ($product['cost']*$product['quantity']).';'."\n";
		}
                
		return $data;
	}
}