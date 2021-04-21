The source files in current folder are templatized with place holders and they can NOT be installed into PrestaShop website directly!

To generate different distributions for different brands, we MUST use another SCRIPT project - 'plugin-distribution' project.
Here are the detailed instructions:
1. Download the SCRIPT project - 'plugin-distribution' project
2. Place the SCRIPT project and the currect project in the same directory, i.e
        | -- yourDir
		        | --- PrestaShop
				| --- plugin-distribution 
				
3. Open up the terminal and change directory to yourDir\plugin-distribution\prestashop, then execute the command in the form of: 
    php .\package.php BRANDNAME
	
   Here are the example commands for eService and EVOPayments:
	php  .\package.php eService
	or
	php .\package.php EVOPayments 

4. Once the above command runs successfully, the zip file are generated in the output folder:
	./output/eservice.zip
	or
	./output/evopayments.zip

Note: please refer to the README.md file in SCRIPT project plugin-distribution for more details if you have any issues.

Note: here are the PLACE HOLDER in use in currect project:
Place holder explanation:
{[$]} -> plugin name
{[$small]} -> lower case plugin name
{[$C]} -> first letter of plugin name is capital, for example: eService -> EService
{[$url_sandbox_token]} -> configuration token url for sandbox
{[$url_sandbox_payment]} -> configuration payment url for sandbox
{[$url_sandbox_cashier]} -> configuration cashier url for sandbox
{[$url_sandbox_js]} -> configuration js url for sandbox
{[$url_live_token]} -> configuration token url for live
{[$url_live_payment]} -> configuration payment url for live
{[$url_live_cashier]} -> configuration cashier url for live
{[$url_live_js]} -> configuration js url for live
{[$showUrlFields4Sandbox]} -> configuration sandbox url field visibility
{[$showUrlFields4Live]} -> configuration live url field visibility
{[$integration_show_iframe]} -> configuration to show payment mode iframe or not
{[$integration_show_redirect]} -> configuration to show payment mode redirect or not
{[$integration_show_hostedpay]} -> configuration to show payment mode hostedpay or not
{[$integration_default_payment]} -> default payment mode
{[$accepts_payments_by_brand]} -> language translation with md5(Accepts payments by BRAND)
{[$brand_connection_settings]} -> language translation with md5(BRAND Connection settings)
{[$pay_with_brand]} -> language translation with md5(Pay with BRAND)