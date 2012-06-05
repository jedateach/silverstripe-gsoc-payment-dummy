<?php

Director::addRules(100, array(
   Dummy_Payment_Controller::$URLSegment . '/$Action/$ID' => 'Dummy_Payment_Controller',
));

Dummy_Payment_Controller::set_test_mode('cancel');


Director::addRules(50, array(  
  'test/order/$Action/$ID' => 'OrderDemoPage_Controller',
));
