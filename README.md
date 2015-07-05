# magento_cron_example
An example of sending daily report email by cron on discounted products.

Key Files
==========

local/Chenlin/Cronexample/Block/Adminhtml/Cronexamplebackend.php
The class pulls the order from yesterday and compare the product sold
price with original price to determine if the product is a promotional 
item.

For promotional item, it saves in an array and convert it into text and
sent it out by email, triggered by Cron job.

local/Chenlin/Cronexample/Model/Cron.ph
It is the main cron job file but it actually using the backend class.


