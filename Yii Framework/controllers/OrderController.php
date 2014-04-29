<?php

class OrderController extends CRUDController {

    protected $_pageTitle = 'Заказы';
    protected $_listTitle = 'Заказы';
    protected $_modelName = 'Order';
    protected $_actionName = 'заказ';
    protected $_formView = 'form';
    protected $_listView = 'list';

    public function actionCreateCustomOrder() {
        $criteria = new CDbCriteria();
        $model = $this->getModel();
        $model->status = Order::STATUS_IN_ASSEMBLY;
        if (isset($POST['Recipient']['id'])) {
            $recipient = Recipient::model()->findByPk($POST['Recipient']['id']);
        } else {
            $recipient = new Recipient();
        }

        if (isset($_POST['product_ids'])) {
            $tmp = explode(', ', $_POST['product_ids']);
            $criteria->addInCondition('id', $tmp);
        } else {
            $criteria->addInCondition('id', array(0));
        }

        if (isset($_POST['product_ids']) && isset($_POST['Recipient']) && isset($_POST['Order']['bill_check'])) {
            $recipient->attributes = $_POST['Recipient'];
            if ($recipient->save()) {
                $basket = new BasketDB();
                foreach ($tmp as $product_id) {
                    $basket->addItem($product_id);
                }
                $recipient->createOrder($basket, true, Order::STATUS_IN_ASSEMBLY, $_POST['Order']['bill_check']);
                $this->redirect('/admin/order/createCustomOrder');
            }
        }


        $dataProvider = new CActiveDataProvider('Product', array('criteria' => $criteria));
        $this->render('customOrder', array(
            'dataProvider' => $dataProvider,
            'model' => $model,
            'recipient' => $recipient,
        ));
    }

    public function actionAutoCompleteOrder() {
        if (Yii::app()->request->isAjaxRequest && Yii::app()->getRequest()->getParam('term')) {
            $criteria = new CDbCriteria;
            $criteria->addSearchCondition('name', Yii::app()->getRequest()->getParam('term'), true, 'OR');
            $criteria->addSearchCondition('article', Yii::app()->getRequest()->getParam('term'), true, 'OR');
            $products = Product::model()->findAll($criteria);
            $result = array();
            foreach ($products as $product) {
                $result[] = array('label' => $product->name, 'value' => $product->id);
            }
            echo CJSON::encode($result);
            Yii::app()->end();
        }
    }

    public function actionHistory() {
        $order = Order::model()->findByPk($this->request->getParam('id'));

        if (!$order)
            throw new CHttpException(404, 'The specified post cannot be found.');

        if ($this->request->getParam('Message'))
            $order->createHistory($this->request->getParam('Message'));

        $this->render('history', array('order' => $order));
    }

    public function actionContains() {
        $order = Order::model()->findByPk($this->request->getParam('id'));

        if (!$order)
            throw new CHttpException(404, 'The specified post cannot be found.');

        if ($this->request->getParam('Message'))
            $order->createHistory($this->request->getParam('Message'));

        $this->render('contains', array('order' => $order));
    }

    public function actionChangeStatus() {
        $status = $this->request->getParam('status');
        $order = Order::model()->findByPk($this->request->getParam('id'));
        if (!$status || !$order)
            $this->redirect($this->_indexUrl);

        Smspilot::sendMessage($order->user->username, Config::getConfigValue('sms', 'fromName'), $order->getMessage($status), Config::getConfigValue('sms', 'apiKey'));

        $order->status = $status;
        $order->save();

        $this->redirect($this->_indexUrl);
    }

}
