<?php
class ControllerPaymentNextpay extends Controller {
	protected function index() {
		$this->language->load('payment/nextpay');
    		$this->data['button_confirm'] = $this->language->get('button_confirm');
		
		$this->data['text_wait'] = $this->language->get('text_wait');
		$this->data['text_ersal'] = $this->language->get('text_ersal');
		if (file_exists(DIR_TEMPLATE . $this->config->get('config_template') . '/template/payment/nextpay.tpl')) {
			$this->template = $this->config->get('config_template') . '/template/payment/nextpay.tpl';
		} else {
			$this->template = 'default/template/payment/nextpay.tpl';
		}
		
		$this->render();		
	}
	public function confirm() {
		
		$this->load->model('checkout/order');
		$order_info = $this->model_checkout_order->getOrder($this->session->data['order_id']);

		$this->data['Amount'] = $this->currency->format($order_info['total'], $order_info['currency_code'], $order_info['currency_value'], false);
		$this->data['PIN']=$this->config->get('nextpay_PIN');
		$this->data['Order_ID'] = $this->session->data['order_id'];
		$this->data['return'] = $this->url->link('checkout/success', '', 'SSL');
		$this->data['cancel_return'] = $this->url->link('checkout/payment', '', 'SSL');
		$this->data['back'] = $this->url->link('checkout/payment', '', 'SSL');

		$client = new SoapClient('https://api.nextpay.org/gateway/token.wsdl', array('encoding' => 'UTF-8'));
		
		if ((!$client)) {
			$json = array();
			$json['error']= "Can not connect to Nextpay.<br>";
		
			$this->response->setOutput(json_encode($json));
		}
	
		$amount = intval($this->data['Amount'])/$order_info['currency_value'];
		if ($this->currency->getCode()=='RLS') {
			$amount = $amount / 10;
		}

		$this->data['order_id'] = $this->session->data['order_id'];
		$callbackUrl  =  $this->url->link('payment/nextpay/callback&order_id=' . $this->data['order_id']);
		
		$result = $client->TokenGenerator(array(
			'api_key' 	=> $this->data['PIN'],
			'amount' 	=> $amount,
			'order_id'  => $order_info['order_id'],
			'callback_uri' 	=> $callbackUrl
		));

		$result = $result->TokenGeneratorResult;

		if(intval($result->code) == -1)
		{
			$this->data['action'] = 'https://api.nextpay.org/gateway/payment/'. $result->trans_id;
			$json = array();
			$json['success']= $this->data['action'];
			$this->response->setOutput(json_encode($json));
		}else{
			$json = array();
			$json['error']= $result->code;
			$this->response->setOutput(json_encode($json));
		}
	}


	function verify_payment($trans_id,$order_id, $amount){
		if ($trans_id AND $order_id) {
			$client = new SoapClient('https://api.nextpay.org/gateway/verify.wsdl', array('encoding' => 'UTF-8'));
			if ((!$client)) {
				echo  "Error: can not connect to Nextpay.<br>";
				return false;
			} else {
					
				if ($this->currency->getCode()=='RLS') {
					$amount = $amount / 10;
				}
				$result = $client->PaymentVerification(
					array(
						'api_key'	 => $this->config->get('nextpay_PIN'),
						'trans_id' 	 => $trans_id,
						'order_id' 	 => $order_id,
						'amount'	 => $amount
					)
				);
				$result = $result->PaymentVerificationResult;
				//print_r($result ); exit;

				if(intval($result->code) == 0){
					return true;
				} else {
					return false;
				}
			}
		} else {
			return false;
		}

	}

	public function callback() {
		$trans_id = $this->request->post['trans_id'];
		$order_id = $this->request->post['order_id'];
		$debugmod = false;
		
		$this->load->model('checkout/order');
		$order_info = $this->model_checkout_order->getOrder($order_id);
		
		$amount = $this->currency->format($order_info['total'], $order_info['currency_code'], $order_info['currency_value'], false);		//echo $this->data['Amount'];
		if ($order_info) {
			if (($this->verify_payment($trans_id,$order_id, $amount)) or ($debugmod==true)) {
				$this->model_checkout_order->confirm($order_id, $this->config->get('nextpay_order_status_id'),'شماره رسيد ديجيتالي; Transaction ID: '.$trans_id);
				
				$this->response->setOutput('<html><head><meta http-equiv="refresh" CONTENT="2; url=' . $this->url->link('checkout/success') . '"></head><body><table border="0" width="100%"><tr><td>&nbsp;</td><td style="border: 1px solid gray; font-family: tahoma; font-size: 14px; direction: rtl; text-align: right;">با تشکر پرداخت تکمیل شد.لطفا چند لحظه صبر کنید و یا  <a href="' . $this->url->link('checkout/success') . '"><b>اینجا کلیک نمایید</b></a></td><td>&nbsp;</td></tr></table></body></html>');
			
			} else {
				$this->response->setOutput('<html><body><table border="0" width="100%"><tr><td>&nbsp;</td><td style="border: 1px solid gray; font-family: tahoma; font-size: 14px; direction: rtl; text-align: right;">پرداخت موفقيت آميز نبود.1<br /><br /><a href="' . $this->url->link('checkout/cart').  '"><b>بازگشت به فروشگاه</b></a></td><td>&nbsp;</td></tr></table></body></html>');
			}
		}
	}
	
}
?>
