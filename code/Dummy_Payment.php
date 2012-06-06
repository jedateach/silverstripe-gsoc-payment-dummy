<?php


/**
 * Object representing a dummy payment gateway
 */
class Dummy_Payment extends Payment {

  public function getControllerClassName() {
    //This is where we can specify the type of controller that we need
    //Check environment, add a hook so this can be decorated, overloaded etc.

    $className = 'Dummy_Payment_Controller';
    if (!class_exists($className)) {
      user_error("Payment gateway class is not defined", E_USER_ERROR);
    }

    return $className;
  }
  
  public function getFormFields() {
    parent::getFormFields();

    $this->formFields->push(new TextField('PaymentMethod', 'Payment Method', get_class($this)));
    $this->formFields->push(new NumericField('Amount', 'Amount', '10.00'));
    $this->formFields->push(new TextField('Currency', 'Currency', 'NZD'));

    return $this->formFields;
  }
}

class Dummy_Payment_MerchantHosted extends Dummy_Payment {

  public function getFormFields() {
    parent::getFormFields();

    $fields = new FieldList(
        new TextField('CardHolderName', 'Credit Card Holder Name :'),
        new CreditCardField('CardNumber', 'Credit Card Number :'),
        new TextField('DateExpiry', 'Credit Card Expiry : (MMYY)', '', 4)
    );

    return $fields;
  }
}

class Dummy_Payment_GatewayHosted extends Dummy_Payment {

}

/**
 * Controller for DummyPayment
 */
class Dummy_Payment_Controller extends Payment_Controller {
  
  static $URLSegment = 'dummy';
  
  protected static $test_mode = 'success';

  static function set_test_mode($test_mode) {
    if ($test_mode == 'success' || $test_mode == 'cancel') {
      self::$test_mode = $test_mode;
    } else {
      user_error('Test mode not supported', E_USER_ERROR);
    }
  }

  public function processRequest($data) {

    parent::processRequest($data);

    // Redirect to the return url
    switch (self::$test_mode) {
      case 'success':
        $this->payment->Status = 'Success';
        $this->payment->write();
        break;
      case 'cancel':
        $this->payment->Status = 'Failure';
        $this->payment->write();
        break;
    } 

    //TODO should we return payment or some alternative like Payment_Result class?
    return $this->payment;  
  }

  public function processResponse($response) {
    // Nothing to do here...
  }

  
  /**
   * Override to add payment id to the link
   * @see Payment_Controller::complete_link()
   */
  public function complete_link() {
    return self::$URLSegment . '/complete/' . $this->payment->ID;
  }
  
  /**
   * @see Payment_Controller::cancel_link()
   */
  public function cancel_link() {
    return self::$URLSegment . '/cancel/' . $this->payment->ID;
  }
  
  /**
   * Payment complete handler. 
   * This function should be persistent accross all payment gateways.
   * Additional processing of payment response can be done in processResponse(). 
   */
  public function complete($request) {


    // Additional processing
    $this->processResponse($request);        
    // Update payment status    
    $payment = $this->updatePaymentStatus($request, 'Success');
    
    return $payment->renderWith($payment->class . "_complete");
  }
  
  /**
   * Payment cancel handler
   */
  public function cancel($request) {
    // Additional processing
    $this->processResponse($request);
    // Update payment status
    $payment = $this->updatePaymentStatus($request, 'Incomplete');
    
    return $payment->renderWith($payment->class . "_cancel");
  }
  
  public function getPaymentID($response) {
    return $response->param('ID');
  }
}