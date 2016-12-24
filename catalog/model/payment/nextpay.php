<?php 
class ModelPaymentNextpay extends Model {

  	public function getMethod() {
		$this->load->language('payment/nextpay');

		if ($this->config->get('nextpay_status')) {
      		  	$status = TRUE;
      	} else {
			$status = FALSE;
		}
		
		$method_data = array();
	
		if ($status) {  
      		$method_data = array( 
        		'code'         => 'nextpay',
        		'title'      => $this->language->get('text_title'),
				'sort_order' => $this->config->get('nextpay_sort_order')
      		);
    	}
   
    	return $method_data;
  	}
}
?>
