<?php
class Chenlin_Cronexample_Model_Cron{	
	public function sendReport(){
		//do something
        Mage::log("Start Promo Prod Report Cron");
        include_once( __DIR__."/../Block/Adminhtml/Cronexamplebackend.php");
        $mailObj = new Chenlin_Cronexample_Block_Adminhtml_Cronexamplebackend();
        $mailObj->promoProductionReport();
        Mage::log("Finish Promo Prod Report Cron");
	} 
}
