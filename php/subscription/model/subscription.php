<?php
class ModelAccountSubscription extends Model {
	

	public function addSubscription($data) {
			
		$this->db->query("
		INSERT INTO 
			`" . DB_PREFIX . "subscription` 
		SET 
		
			`customer_id` = '" . (int)$data['customer_id'] . "',
			`order_id` = '" . (int)$data['order_id'] . "',
			`order_product_id` = '" . (int)$data['order_product_id'] . "',
			`subscription_plan_id` = '" . (int)$data['subscription_plan_id'] . "',
			`customer_payment_id` = '" . (int)$data['customer_payment_id'] . "',
			`name` = '" . $this->db->escape($data['name']) . "',
			`description` = '" . $this->db->escape($data['description']) . "',
			`price` = '" . (float)$data['price'] . "',
			`currency_id` = '" . (int)$data['currency_id'] . "',
			`currency_code` = '" .  $this->db->escape($data['currency_code']) . "',
			`currency_value` = '" . (float)$data['currency_value'] . "',
			`frequency` = '" . $this->db->escape($data['frequency']) . "',
			`cycle` = '" . (int)$data['cycle'] . "',
			`duration` = '" . (int)$data['duration'] . "',
			`remaining` = '" . (int)$data['remaining'] . "',
			`date_next` = '" . $this->db->escape($data['date_next']) . "',
			`subscription_status_id` = '" . (int)$data['subscription_status_id'] . "',
			`date_added` = '" . $this->db->escape($data['date_start']) . "',
			`date_modified` = '" . $this->db->escape($data['date_start']) . "',
			`date_start` = '" . $this->db->escape($data['date_start']) . "',
			`date_finish` = '" . $this->db->escape($data['date_finish']) . "'
		
		");	
		
		$subscription_id = $this->db->getLastId();
		
		return $subscription_id;
		
	}


	public function addSubscriptionHistory($subscription_id, $subscription_status_id, $data=array()) {

		//Add History 
		$this->db->query("
		INSERT INTO 
			`" . DB_PREFIX . "subscription_history` 
		SET 
		
			`subscription_id` = '" . (int)$subscription_id  . "',
			`subscription_status_id` = '" . $subscription_status_id . "',
			`subscription_plan_id` = '" . (int)$data['subscription_plan_id'] . "',
			`customer_payment_id` = '" . (int)$data['customer_payment_id'] . "',
			`notify` = 0,
			`comment` = '',
			`frequency` = '" . $this->db->escape($data['frequency']) . "',
			`price`  = '" . (float)$data['price'] . "',
			`date_added` = '" . $this->db->escape($data['date_start']) . "',
			`date_start` = '" . $this->db->escape($data['date_start']) . "',
			`date_finish` = '" . $this->db->escape($data['date_finish']) . "'
		");	

		return true;
	}


	public function addSubscriptionTransaction($subscription_id, $data=array()) {

		//Add Transaction 
		$this->db->query("
		INSERT INTO 
			`" . DB_PREFIX . "subscription_transaction` 
		SET 
		
			`subscription_id` = '" . (int)$subscription_id  . "',
			`order_id` = '" . (int)$data['order_id'] . "',
			`transaction_id` = '" . (int)$data['transaction_id'] . "',
			`description` = '" . $this->db->escape($data['transaction_description']) . "',
			`amount` = '" . (float)$data['price'] . "',
			`payment_method` = '" . $this->db->escape($data['payment_method']) . "',
			`payment_code` = '" .  $this->db->escape($data['payment_code']) . "',
			`payment_reference` = '" . $this->db->escape($data['payment_reference']). "',
			`date_added` = NOW()
		
		");	

		return true;
	}


	public function getSubscription($subscription_id) {

		$subscription_query = $this->db->query("
			SELECT 
				s.`subscription_id` AS subscription_id,
				s.`order_id` AS order_id,
				s.`date_added` AS date_added,
				s.`date_start` AS date_start,
				s.`date_finish` AS date_finish,
				s.`price` AS price,
				s.`currency_id` AS currency_id,
				s.`currency_code` AS currency_code,
				s.`currency_value` AS currency_value,
				s.`frequency` AS frequency,
				spd.`name` AS subscription_plan,
				ss.`name` AS status
			FROM 
				`" . DB_PREFIX . "subscription` s
				LEFT JOIN `" . DB_PREFIX . "subscription_plan_description` spd ON s.`subscription_plan_id` = spd.`subscription_plan_id`
				LEFT JOIN `" . DB_PREFIX . "subscription_status` ss ON s.`subscription_status_id` = ss.`subscription_status_id` 
			WHERE 
				s.`subscription_id` = '" . (int)$subscription_id . "' AND 
				s.`customer_id` = '" . (int)$this->customer->getId() . "' AND 
				s.`customer_id` != '0' AND 
				s.`subscription_status_id` > '0' AND 
				ss.`language_id` = '" . (int)$this->config->get('config_language_id') . "' AND
				spd.`language_id` = '" . (int)$this->config->get('config_language_id') . "'
				
			");

		if ($subscription_query->num_rows) {

			return array(
				'subscription_id'		=> $subscription_query->row['subscription_id'],
				'order_id'            	=> $subscription_query->row['order_id'],
				'date_added'            => $subscription_query->row['date_added'],
				'date_start'          	=> $subscription_query->row['date_start'],
				'date_finish'           => $subscription_query->row['date_finish'],
				'price'              	=> $subscription_query->row['price'],
				'frequency'             => $subscription_query->row['frequency'],
				'subscription_plan'     => $subscription_query->row['subscription_plan'],
				'status'               	=> $subscription_query->row['status'],
				'currency_id'           => $subscription_query->row['currency_id'],
				'currency_code'         => $subscription_query->row['currency_code'],
				'currency_value'        => $subscription_query->row['currency_value']
				
			);
		} else {
			return false;
		}
	}
	
	public function getSubscriptionDateFinish ($subscription_id) {
		$subscription_query = $this->db->query("
			SELECT 
				s.`date_finish` AS date_finish
			FROM 
				`" . DB_PREFIX . "subscription` s
			WHERE 
				s.`subscription_id` = '" . (int)$subscription_id . "'
				
			");

		if ($subscription_query->num_rows) {

			return $subscription_query->row['date_finish'];
			
		} else {
			return false;
		}
	}
	
	public function getPendingSubscriptionId () {

		$query = $this->db->query("
			SELECT subscription_id 
			FROM 
				" . DB_PREFIX . "subscription 
			WHERE 
				customer_id = '" . (int)$this->customer->getId() . "' AND
				subscription_status_id = 1
			ORDER BY 
				date_added DESC
			LIMIT 1");//subscription_status_id = 2 Active

		if (isset($query->row['subscription_id'])) {
			return (int)$query->row['subscription_id'];
		} else {
			return 0;
		}
		
	}	
	
	
	//      SkyTowN 
	//  Subscription 2022
	public function getActiveCustomerSubscription ($customer_id) {

		$query = $this->db->query("
			SELECT subscription_id 
			FROM 
				" . DB_PREFIX . "subscription 
			WHERE 
				customer_id = '" . (int)$customer_id. "' AND
				subscription_status_id = 2  AND
				date_finish > NOW()
			ORDER BY 
				date_finish DESC
			LIMIT 1");//subscription_status_id = 2 Active

		if (isset($query->row['subscription_id'])) {
			return (int)$query->row['subscription_id'];
		} else {
			return 0;
		}
		
	}
	
	
	public function addSubscriptionCustomerActivity ($data = array()) {

		$query = $this->db->query("
			INSERT INTO " . DB_PREFIX . "subscription_customer_activity SET 
				subscription_id = '" . (int)$data['subscription_id'] . "', 
				customer_id = '" . (int)$data['customer_id'] . "', 
				customer_data = '" . $this->db->escape($data['customer_data']) . "',
				ip = '" . $this->db->escape($data['ip']) . "',
				token = '" . $this->db->escape($data['token']) . "',
				date_finish = '" . $this->db->escape($data['date_finish']) . "',
				date_added = NOW()
			");
			
		//return true;
		
	}	
	
	public function addSubscriptionCustomerActivityDemo ($data = array()) {

		$query = $this->db->query("
			INSERT INTO " . DB_PREFIX . "subscription_customer_activity_demo SET 
				subscription_id = 0, 
				customer_id = '" . (int)$data['customer_id'] . "', 
				customer_data = '" . $this->db->escape($data['customer_data']) . "',
				ip = '" . $this->db->escape($data['ip']) . "',
				token = '" . $this->db->escape($data['token']) . "',
				date_finish =  '" . $this->db->escape($data['date_finish']) . "',
				date_added = NOW()
			");
			
		//return true;
		
	}	
	
	public function getSubscriptionDateFinishCustomerActivity ($token) {

		$query = $this->db->query("
			SELECT date_finish 
			FROM 
				" . DB_PREFIX . "subscription_customer_activity 
			WHERE 
				token = '" . $this->db->escape($token) . "' AND
				date_finish > NOW()
			ORDER BY `subscription_customer_activity_id` DESC
			LIMIT 1
			");
			
		if (isset($query->row['date_finish'])) {
			return $query->row['date_finish'];
		} else {
			return false;
		}
		
	}
	
	public function getSubscriptionDateFinishCustomerActivityDemo ($token) {

		$query = $this->db->query("
			SELECT date_finish 
			FROM 
				" . DB_PREFIX . "subscription_customer_activity_demo 
			WHERE 
				token = '" . $this->db->escape($token) . "' AND
				date_finish > NOW()
			ORDER BY `subscription_customer_activity_id` DESC
			LIMIT 1
			");
			
		if (isset($query->row['date_finish'])) {
			return $query->row['date_finish'];
		} else {
			return false;
		}
		
	}
	
	
	public function getOrder($order_id) {
		$order_query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "order` WHERE order_id = '" . (int)$order_id . "' AND customer_id = '" . (int)$this->customer->getId() . "' AND customer_id != '0' AND order_status_id > '0'");

		if ($order_query->num_rows) {
			$country_query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "country` WHERE country_id = '" . (int)$order_query->row['payment_country_id'] . "'");

			if ($country_query->num_rows) {
				$payment_iso_code_2 = $country_query->row['iso_code_2'];
				$payment_iso_code_3 = $country_query->row['iso_code_3'];
			} else {
				$payment_iso_code_2 = '';
				$payment_iso_code_3 = '';
			}

			$zone_query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "zone` WHERE zone_id = '" . (int)$order_query->row['payment_zone_id'] . "'");

			if ($zone_query->num_rows) {
				$payment_zone_code = $zone_query->row['code'];
			} else {
				$payment_zone_code = '';
			}

			$country_query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "country` WHERE country_id = '" . (int)$order_query->row['shipping_country_id'] . "'");

			if ($country_query->num_rows) {
				$shipping_iso_code_2 = $country_query->row['iso_code_2'];
				$shipping_iso_code_3 = $country_query->row['iso_code_3'];
			} else {
				$shipping_iso_code_2 = '';
				$shipping_iso_code_3 = '';
			}

			$zone_query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "zone` WHERE zone_id = '" . (int)$order_query->row['shipping_zone_id'] . "'");

			if ($zone_query->num_rows) {
				$shipping_zone_code = $zone_query->row['code'];
			} else {
				$shipping_zone_code = '';
			}

			return array(
				'order_id'                => $order_query->row['order_id'],
				'invoice_no'              => $order_query->row['invoice_no'],
				'invoice_prefix'          => $order_query->row['invoice_prefix'],
				'store_id'                => $order_query->row['store_id'],
				'store_name'              => $order_query->row['store_name'],
				'store_url'               => $order_query->row['store_url'],
				'customer_id'             => $order_query->row['customer_id'],
				'firstname'               => $order_query->row['firstname'],
				'lastname'                => $order_query->row['lastname'],
				'telephone'               => $order_query->row['telephone'],
				'email'                   => $order_query->row['email'],
				'payment_firstname'       => $order_query->row['payment_firstname'],
				'payment_lastname'        => $order_query->row['payment_lastname'],
				'payment_company'         => $order_query->row['payment_company'],
				'payment_address_1'       => $order_query->row['payment_address_1'],
				'payment_address_2'       => $order_query->row['payment_address_2'],
				'payment_postcode'        => $order_query->row['payment_postcode'],
				'payment_city'            => $order_query->row['payment_city'],
				'payment_zone_id'         => $order_query->row['payment_zone_id'],
				'payment_zone'            => $order_query->row['payment_zone'],
				'payment_zone_code'       => $payment_zone_code,
				'payment_country_id'      => $order_query->row['payment_country_id'],
				'payment_country'         => $order_query->row['payment_country'],
				'payment_iso_code_2'      => $payment_iso_code_2,
				'payment_iso_code_3'      => $payment_iso_code_3,
				'payment_address_format'  => $order_query->row['payment_address_format'],
				'payment_method'          => $order_query->row['payment_method'],
				'shipping_firstname'      => $order_query->row['shipping_firstname'],
				'shipping_lastname'       => $order_query->row['shipping_lastname'],
				'shipping_company'        => $order_query->row['shipping_company'],
				'shipping_address_1'      => $order_query->row['shipping_address_1'],
				'shipping_address_2'      => $order_query->row['shipping_address_2'],
				'shipping_postcode'       => $order_query->row['shipping_postcode'],
				'shipping_city'           => $order_query->row['shipping_city'],
				'shipping_zone_id'        => $order_query->row['shipping_zone_id'],
				'shipping_zone'           => $order_query->row['shipping_zone'],
				'shipping_zone_code'      => $shipping_zone_code,
				'shipping_country_id'     => $order_query->row['shipping_country_id'],
				'shipping_country'        => $order_query->row['shipping_country'],
				'shipping_iso_code_2'     => $shipping_iso_code_2,
				'shipping_iso_code_3'     => $shipping_iso_code_3,
				'shipping_address_format' => $order_query->row['shipping_address_format'],
				'shipping_method'         => $order_query->row['shipping_method'],
				'comment'                 => $order_query->row['comment'],
				'total'                   => $order_query->row['total'],
				'order_status_id'         => $order_query->row['order_status_id'],
				'language_id'             => $order_query->row['language_id'],
				'currency_id'             => $order_query->row['currency_id'],
				'currency_code'           => $order_query->row['currency_code'],
				'currency_value'          => $order_query->row['currency_value'],
				'date_modified'           => $order_query->row['date_modified'],
				'date_added'              => $order_query->row['date_added'],
				'ip'                      => $order_query->row['ip']
			);
		} else {
			return false;
		}
	}

	public function getOrders($start = 0, $limit = 20) {
		if ($start < 0) {
			$start = 0;
		}

		if ($limit < 1) {
			$limit = 1;
		}

		$query = $this->db->query("SELECT o.order_id, o.firstname, o.lastname, os.name as status, o.date_added, o.total, o.currency_code, o.currency_value FROM `" . DB_PREFIX . "order` o LEFT JOIN " . DB_PREFIX . "order_status os ON (o.order_status_id = os.order_status_id) WHERE o.customer_id = '" . (int)$this->customer->getId() . "' AND o.order_status_id > '0' AND o.store_id = '" . (int)$this->config->get('config_store_id') . "' AND os.language_id = '" . (int)$this->config->get('config_language_id') . "' ORDER BY o.order_id DESC LIMIT " . (int)$start . "," . (int)$limit);

		return $query->rows;
	}

	public function getOrderProduct($order_id, $order_product_id) {
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "order_product WHERE order_id = '" . (int)$order_id . "' AND order_product_id = '" . (int)$order_product_id . "'");

		return $query->row;
	}

	public function getOrderProducts($order_id) {
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "order_product WHERE order_id = '" . (int)$order_id . "'");

		return $query->rows;
	}

	public function getOrderOptions($order_id, $order_product_id) {
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "order_option WHERE order_id = '" . (int)$order_id . "' AND order_product_id = '" . (int)$order_product_id . "'");

		return $query->rows;
	}

	public function getOrderVouchers($order_id) {
		$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "order_voucher` WHERE order_id = '" . (int)$order_id . "'");

		return $query->rows;
	}

	public function getOrderTotals($order_id) {
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "order_total WHERE order_id = '" . (int)$order_id . "' ORDER BY sort_order");

		return $query->rows;
	}

	public function getOrderHistories($order_id) {
		$query = $this->db->query("SELECT date_added, os.name AS status, oh.comment, oh.notify FROM " . DB_PREFIX . "order_history oh LEFT JOIN " . DB_PREFIX . "order_status os ON oh.order_status_id = os.order_status_id WHERE oh.order_id = '" . (int)$order_id . "' AND os.language_id = '" . (int)$this->config->get('config_language_id') . "' ORDER BY oh.date_added");

		return $query->rows;
	}

	public function getTotalOrders() {
		$query = $this->db->query("SELECT COUNT(*) AS total FROM `" . DB_PREFIX . "order` o WHERE customer_id = '" . (int)$this->customer->getId() . "' AND o.order_status_id > '0' AND o.store_id = '" . (int)$this->config->get('config_store_id') . "'");

		return $query->row['total'];
	}

	public function getTotalOrderProductsByOrderId($order_id) {
		$query = $this->db->query("SELECT COUNT(*) AS total FROM " . DB_PREFIX . "order_product WHERE order_id = '" . (int)$order_id . "'");

		return $query->row['total'];
	}

	public function getTotalOrderVouchersByOrderId($order_id) {
		$query = $this->db->query("SELECT COUNT(*) AS total FROM `" . DB_PREFIX . "order_voucher` WHERE order_id = '" . (int)$order_id . "'");

		return $query->row['total'];
	}
}