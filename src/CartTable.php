<?php

namespace Drupal\basiccart;

use Drupal\basiccart\CartInterface;
use Drupal\basiccart\Settings;

/**
 * Class CartTable.
 */
class CartTable implements CartInterface {	

     
  protected $user;
  protected $user_id;
  private $cart_storage;
  
	public function __construct(CartStorage $cart_storage ,$user) {
     $this->user = $user;
     $this->user_id = $user->id();
     $this->cart_storage = $cart_storage;
 	}

	/**
	 * Function for shopping cart retrieval.
	 *
	 * @param int $nid
	 *   We are using the node id to store the node in the shopping cart
	 *
	 * @return mixed
	 *   Returning the shopping cart contents.
	 *   An empty array if there is nothing in the cart
	 */


	public function get_cart($nid = NULL) {
	  if (isset($nid)) {
	    return array("cart" => $_SESSION['basiccart']['cart'][$nid], "cart_quantity" => $_SESSION['basiccart']['cart_quantity'][$nid]);
	  }
	  if (isset($_SESSION['basiccart']['cart'])) {
	    return array("cart" => $_SESSION['basiccart']['cart'], "cart_quantity" => $_SESSION['basiccart']['cart_quantity']);
	  }
	  // Empty cart.
	  return array("cart" => array(),"cart_quantity" => array());
	}


	/**
	 * Callback function for cart/remove/.
	 *
	 * @param int $nid
	 *   We are using the node id to remove the node in the shopping cart
	 */
	public function remove_from_cart($nid) {
	  $nid = (int) $nid;
   
	  if ($nid > 0) {
	    $param['id'] = $nid;
    	$param['uid'] = $this->user_id;
			$entity = $_SESSION['basiccart']['cart'][$nid];
    	$param['entitytype'] = $entity->getEntityTypeId();  
    	$this->cart_storage->delete($param);
	    unset($_SESSION['basiccart']['cart'][$nid]);
	    unset($_SESSION['basiccart']['cart_quantity'][$nid]);
	  }

	}

	/**
	 * Shopping cart reset.
	 */
	public function empty_cart() {
		$param['uid'] = $this->user_id;  
		$this->cart_storage->delete($param);	
		unset($_SESSION['basiccart']['cart']);
		unset($_SESSION['basiccart']['cart_quantity']);
	}


  public  function add_to_cart($id, $params = array()) {
    $config = Settings::cart_settings();
    if(!empty($params)) {
      $quantity = $params['quantity'];
      $entitytype = $params['entitytype'];
      $quantity = $params['quantity'];
        
      if ($id > 0 && $quantity > 0) {
						  $param['uid'] = $this->user_id;
							$param['id'] = $id;
              $param['entitytype'] = $params['entitytype'];
            // If a node is added more times, just update the quantity.
            $cart = self::get_cart();
            if ($config->get('quantity_status') && !empty($cart['cart']) && in_array($id, array_keys($cart['cart']))) {
              // Clicked 2 times on add to cart button. Increment quantity.
              $_SESSION['basiccart']['cart_quantity'][$id] += $quantity;
			  $param['quantity'] = $_SESSION['basiccart']['cart_quantity'][$id];
              $this->cart_storage->update($param);  
            }
            else {
               $entity = \Drupal::entityTypeManager()->getStorage($entitytype)->load($id);
               $_SESSION['basiccart']['cart'][$id] = $entity;
               $_SESSION['basiccart']['cart_quantity'][$id] = $quantity;
			   $param['quantity'] = $_SESSION['basiccart']['cart_quantity'][$id];
               $this->cart_storage->insert($param);	
            }
      }
      Settings::cart_updated_message();
    }  
  }

	public function loggedinactioncart() {
		if(isset($_SESSION['basiccart']['cart']) && isset($_SESSION['basiccart']['cart_quantity'])) {
			foreach($_SESSION['basiccart']['cart'] as $id => $value) {
				$param = array();
				$param['uid'] = $this->user_id;
				$param['id'] = $id;
				$param['entitytype'] = $value->getEntityTypeId();
				$table_data = $this->cart_storage->load($param);
				if($table_data) {
					$param['quantity'] = $_SESSION['basiccart']['cart_quantity'][$id];
					$table_data = $this->cart_storage->update($param);   
				}else{
					$param['quantity'] = $_SESSION['basiccart']['cart_quantity'][$id];	
					$this->cart_storage->insert($param);
				} 
			}
		}
		$param = array();
		$param['uid'] = $this->user_id;
        $exist_data = $this->cart_storage->load($param);
        foreach($exist_data as $key) {
          if(!isset($_SESSION['basiccart']['cart'][$key->id])) {
            $_SESSION['basiccart']['cart_quantity'][$key->id] = $key->quantity;
            $_SESSION['basiccart']['cart'][$key->id] =  \Drupal::entityTypeManager()->getStorage($key->entitytype)->load($key->id);
          }
        } 
	}
}	
