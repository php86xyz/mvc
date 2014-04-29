<?php
/**
 * Created by JetBrains PhpStorm.
 * User: UnderDark
 * Date: 22.07.13
 * Time: 16:05
 * To change this template use File | Settings | File Templates.
 */

class RecipientController extends CabinetBaseController {

	public function actionCreate() {
		$this->currentMenuItem = self::MY_RECIPIENT;
		$recipient = new Recipient();
		$this->processRecipient($recipient);
	}

	public function actionUpdate() {
		$this->currentMenuItem = self::MY_RECIPIENT;
		$recipient = $this->getItem('Recipient');

		if($recipient->user_id != $this->user->id)
			throw new CHttpException(404, 'The specified post cannot be found.');

		$this->processRecipient($recipient);
	}

	/**
	 * @param $recipient Recipient
	 */
	private function processRecipient($recipient) {
		$recipientAttributes = $this->request->getParam('Recipient');

		if($recipientAttributes) {
			$recipient->attributes = $recipientAttributes;
			$recipient->save();
			if($recipient->save())
				$this->redirect('/cabinet/recipient');
		}

		$this->render('//front/recipient/container', array('recipient' => $recipient));
	}

}