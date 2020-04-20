<?php
/**
 * Платежный шлюз Сбербанка
 * Документация https://securepayments.sberbank.ru/wiki/doku.php/integration:api:rest:start
 *
 * @link   https://github.com/alexfedosienko/Sberbank-acquiring
 * @version 1.0
 * @author Alexander Fedosienko <alexfedosienko@gmail.com>
 */

namespace AFedosienko;

class SBRF_API
{
	private $developmentURI = 'https://3dsec.sberbank.ru/';
	private $productionURI = 'https://securepayments.sberbank.ru/';
	private $requestURI = '';

	private $registerOrderURI = 'payment/rest/register.do';
	private $orderStatusExtendedURI = "payment/rest/getOrderStatusExtended.do";
	private $refundOrderURI = "payment/rest/refund.do";
	private $reverseOrderURI = "payment/rest/reverse.do";

	private $requestData = array();

	function __construct($userName, $password, $returnSuccessURL, $returnFailURL, $development = false)
	{
		$this->requestURI = ($development) ? $this->developmentURI : $this->productionURI;
		$this->requestData['userName'] = $userName;
		$this->requestData['password'] = $password;
		$this->requestData['returnUrl'] = $returnSuccessURL;
		$this->requestData['failUrl'] = $returnFailURL;
	}

	public function isExistOrder($orderNumber) {
		$response = $this->getOrderInfo('', $orderNumber);
		// Заказ не найден
		if ($response->errorCode == 6) return false;
		// Заказ зарегистрирован
		if ($response->errorCode == 0) return true;
	}

	public function registerOrder($orderSum, $orderNumber, $description)
	{
		$requestData = array();
		$requestData['orderNumber'] = $orderNumber;
		$requestData['amount'] = $orderSum*100;
		$requestData['description'] = $description;
		$response = $this->request($requestData, $this->registerOrderURI);
		// Если такой заказ уже обработан
		if ($response->errorCode && $response->errorCode == "1") {
			$response = $this->getOrderInfo('', $orderNumber);
			if ($response->errorCode == 0) {
				if ($response->actionCode == -100) { // Не было попыток оплаты.
					$resp = array(
						'orderId' => $response->attributes[0]->value,
						'formUrl' => $this->requestURI.'payment/merchants/sbersafe_id/payment_ru.html?mdOrder='.$response->attributes[0]->value
					);
					return (object)$resp;
				} elseif ($response->actionCode == -2007) { // Истёк срок ожидания ввода данных.
					$requestData['orderNumber'] = $requestData['orderNumber']."_".date('dmyHis', strtotime('now'));
					$response = $this->request($requestData, $this->registerOrderURI);
					return $response;
				} elseif ($response->actionCode == 0) { // Успешно
					if ($response->paymentAmountInfo->paymentState == "DEPOSITED") { 
						// Заказ уже оплачен
						return $response;
					} else {
						return false;
					}
				} else {
					return $response;
				}
			} else {
				// Заказ не найден
				return false;
			}
		} else {
			return $response;
		}
	}

	public function getOrderInfo($orderId, $orderNumber)
	{
		$requestData = array();
		$requestData['orderId'] = $orderId;
		$requestData['orderNumber'] = $orderNumber;
		$response = $this->request($requestData, $this->orderStatusExtendedURI);
		return $response;
	}

	public function reverseOrderSum($orderId)
	{
		$requestData = array();
		$requestData['orderId'] = $orderId;
		$response = $this->request($requestData, $this->reverseOrderURI);
		return $response;
	}

	public function refudOrderSum($orderId, $amount)
	{
		$requestData = array();
		$requestData['orderId'] = $orderId;
		$requestData['amount'] = $amount*100;
		$response = $this->request($requestData, $this->refundOrderURI);
		return $response;
	}

	private function request($requestData, $requestURI)
	{
		$requestURI = $this->requestURI.$requestURI;
		$requestData = array_merge($requestData, $this->requestData);
		$requestData = \http_build_query($requestData, '', '&');
		if ($curl = curl_init()) {
			$headers = array('Cache-Control: no-cache', 'Content-Type:  application/x-www-form-urlencoded');
			curl_setopt($curl, CURLOPT_URL, $requestURI);
			curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($curl, CURLOPT_POST, true);
			curl_setopt($curl, CURLOPT_POSTFIELDS, $requestData);
			curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
			$response = curl_exec($curl);
			curl_close($curl);
			$response = json_decode($response);
			return $response;
		} else {
			return false;
		}
	}
}