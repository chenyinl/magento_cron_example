<?php  

class Chenlin_Cronexample_Block_Adminhtml_Cronexamplebackend extends Mage_Adminhtml_Block_Template {

    /**
     * The year of the sales
     */
    protected $year;

    /**
     * The day of the sales
     */
    protected $day;

    /**
     * The month of the sales
     */
    protected $month;

    /**
     * The formated sales date
     */
    protected $salesDate;

    /**
     *  Array that save the promotion production and related info 
     */
    protected $resultArray = array();

    /**
     * The text that will be displayed or email out
     */
    protected $returnText = ""; 


    protected function _toHtml()
    {
        $this->promoProductionReport();
        echo $this->returnText;
        return "";
    }

    /**
     * Prepare promotion product sales report and send by email
     */
    public function promoProductionReport()
    {
        /**
         * Set the range for yesterday
         */
        $this->setYesterday();
        
        /**
         * Formate the date for yesterday, email only
         */
        $this->setSalesDate();
        
        /**
         * Prepare the from day with time
         */
        $this->fromDate = $this->convertToUTC ( 0, 0, 0 );
        
        /**
         * Prepare the to (end) day with time
         */
        $this->toDate = $this->convertToUTC ( 23, 59, 59 );

        /**
         * Get the sales order collection 
         */
        $this->setSalesOrderCollection();
        
        /**
         * Prepare the array that has all the info to return
         */
        $this->constructReturnArray();
        
        /**
         * Parepare the text to send to email
         */
        $this->constructReturnText();
        
        /**
         * Send out the email 
         */
        $this->sendReportEmail();
        
    }  

    /**
     * Set the day for yesterday
     */
    protected function setYesterday()
    {
        $this->year = date('Y', strtotime( '-1 days' ));
        $this->day = date('d', strtotime( '-1 days' ));
        $this->month = date('m', strtotime( '-1 days' ));
    }
    
    /**
     * Set the day for sales, this is for email only
     */ 
    protected function setSalesDate()
    {
        $this->salesDate = date(
            'Y-m-d', 
            mktime(
                0, 
                0, 
                0, 
                $this->month,
                $this->day, 
                $this->year
            )
        );
    }
    
    /**
     * Since Mage save date in UTC, needs to convert to UTC
     */
    protected function convertToUTC( $h, $m, $s )
    {
        $returnDate = date(
            'Y-m-d H:i:s', 
            mktime(
                $h, 
                $m, 
                $s, 
                $this->month, 
                $this->day,
                $this->year
            )
        );
        
        /* Yesterday is based on current user timezone */
        $returnDate = new DateTime( 
            $returnDate, 
            new DateTimeZone(
                Mage::getStoreConfig('general/locale/timezone')
            )
        );
        
        /* change to Mage UTC timezone */
        $returnDate -> setTimezone( new DateTimeZone("UTC") );
        $returnDate = $returnDate->format('Y-m-d H:i:s');
        return $returnDate;
    }
    
    /**
     * Get all the sales order from yesterday
     */
    protected function setSalesOrderCollection()
    {
        $this->orderCollection = 
            Mage::getModel('sales/order')
                ->getCollection()
                ->addAttributeToFilter(
                    'main_table.created_at', 
                    array(
                        'from'=>$this->fromDate, 
                        'to'=>$this->toDate
                    )
                );

    }
    
    /**
     * Go through the order and find the promotion productions,
     * Save the qty and production info in an array
     */
    protected function constructReturnArray()
    {
        /* if there is no collection (no sales) */
        if( count($this->orderCollection) ==0 ){
            return;
        }
        
        /* get productino model to look for the original price */
        $productModel = Mage::getModel('catalog/product');
        
        
        foreach($this->orderCollection as $order) {
            foreach ($order->getAllItems() AS $item){
                $sku = ($item->getSku());
                $name = ($item->getName());
                $qty = ($item->qty_ordered);
                $product_id = $productModel->getIdBySku($sku);
                $_product = $productModel->load($product_id);
                
                /**
                 * if the sales price is different from original 
                 * price, set is as a promotional product
                 */
                if($item->getPrice() < $_product->getPrice()){
                    if( isset( $this->resultArray[ $product_id ])){
                        
                        $this->resultArray[ $product_id ]["qty"]+=$qty;
                        
                    } else {
                        
                        $this->resultArray[ $product_id ] = array(
                            "name" => $item->getName(),
                            "qty" => $qty
                        );
                    }
                }
            }
        }
    }
    
    /**
     * Convert the array into readable text
     */
    protected function constructReturnText()
    {
        $text = "Promotional Product Sales Daily Report ".
            $this->salesDate."\n";
        if( count( $this->resultArray ) == 0){
            $text.=" No record found.\n";
            $this->returnText = $text;
        }
        foreach( $this->resultArray AS $key => $value ){
            $text.="Product ID: ".$key."\n";
            $text.="Name: ".$value["name"]."\n";
            $text.="Quantity: ".(INT) $value["qty"]."\n";
            $text.="-------------\n";
        }
        $this->returnText = $text;
    }
    
    /**
     * Send Email out
     */
    protected function sendReportEmail()
    {
        $name="CHEN LIN";
        $email="linchenyin@gmail.com";
        $templateId = "promo_sales_report";
        $storeId = 1;
        $emailTemplate = Mage::getModel('core/email_template')
            ->loadByCode($templateId);
        $vars = array(
            "name" => $name, 
            "email" => $email,
            "comment" => $this->returnText
            
        );
        //$emailTemplate->getProcessedTemplate($vars);
        
        /* set the sender email address */
        $emailTemplate->setSenderEmail(
            Mage::getStoreConfig(
                "trans_email/ident_general/email",
                $storeId
            )
        );
        
        /* set the sender name */
        $emailTemplate->setSenderName(
            Mage::getStoreConfig(
                "trans_email/ident_general/name", 
                $storeId
            )
        );
        
        /* send out email, and log into system.log */
        if( !$emailTemplate->send( $email, $name, $vars)){
            Mage::log("Fail to send out Promotion Sales Report Email.");
        }else{
            Mage::log("Promotion Sales Report Email Sent.");
        }
    }
}

