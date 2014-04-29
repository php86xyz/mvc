<?php

class CRUDController extends AdminController {

	/**
	 * @var string
	 */
	protected $_modelName;

	/**
	 * @var string
	 */
	protected $_listTitle;

	/**
	 * @var string
	 */
	protected $_actionName = '';

	/**
	 * @var string
	 */
	protected $_formView = '/form/view';

	/**
	 * @var string
	 */
	protected $_listView = '/form/list';

	/**
	 * @var string
	 */
	protected $_indexUrl;

	/**
	 * void
	 */
	public function init() {
		if(! $this->_indexUrl)
			$this->_indexUrl = '/admin/'.strtolower($this->_modelName);

		parent::init();
	}

	/**
	 * @return string
	 */
	public function getActionName() {
		return $this->_actionName;
	}

	/**
	 * @return CRUDModel
	 */
	protected function getModel() {
		return new $this->_modelName;
	}

	/**
	 * @return string
	 */
	protected function getFormView() {
		return $this->_formView;
	}

	/**
	 * @return array
	 */
	protected function getFormParams() {
		return Yii::app()->request->getParam($this->_modelName);
	}

	/**
	 * @return string
	 */
	public function getListTitle() {
		return $this->_listTitle;
	}

	/**
	 * void
	 */
	public function actionIndex() {
		$model = $this->getModel();
		$models = $model->findAll();

		$this->render($this->_listView, array('models' => $models, 'list' => $model->getList()));
	}

	/**
	 * void
	 */
	public function actionCreate() {
		if(! $this->allowCreate())
			$this->redirect($this->_indexUrl);

		$this->_pageTitle .= ' добавить';
		$this->_actionName = 'Добавить '.$this->_actionName;
		$model = $this->getModel();

		$this->saveModel($model);
		$this->renderFormView($model);
	}

	/**
	 * @param $id
	 * @throws CHttpException
	 */
	public function actionEdit($id) {
		if(! $this->allowEdit())
			$this->redirect($this->_indexUrl);

		$this->_pageTitle .= ' редактировать';
		$this->_actionName = 'Редактировать '.$this->_actionName;
		$model = $this->getModel();
		$model = $model->findByPk($id);

		if(! $model)
			throw new CHttpException(404, 'The specified post cannot be found.');

		$this->saveModel($model);
		$this->renderFormView($model);
	}

	/**
	 * @param $model CRUDModel
	 */
	protected function saveModel($model) {
		$modelAttributes = $this->getFormParams();

		if($modelAttributes) {
			$model->attributes = $modelAttributes;
			if($model->save())
				$this->redirect($this->_indexUrl);
		}
	}

	/**
	 * @param $model
	 */
	protected function renderFormView($model) {
		$this->render($this->getFormView(), array('model' => $model));
	}

	/**
	 * @param $id
	 */
	public function actionDelete($id) {
		if(! $this->allowDelete())
			$this->redirect($this->_indexUrl);

		$model = $this->getModel();
		$model = $model->findByPk($id);
		if($model)
			$model->delete();
		$this->redirect($this->_indexUrl);
	}

	/**
	 * @return bool
	 */
	public function allowDelete() {
		return true;
	}

	/**
	 * @return bool
	 */
	public function allowEdit() {
		return true;
	}

	/**
	 * @return bool
	 */
	public function allowCreate() {
		return true;
	}
}
