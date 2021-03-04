var cashier = null;
try{
	cashier = com.myriadpayments.api.cashier();
	cashier.init(
		{baseUrl: evomodule['baseUrl']}
	);
}catch(e){
console.log(e);
}

function handleResult(data) {
    var status = JSON.stringify(data);
    $.ajax({
        type: "POST",
        url: evomodule['baseUri'] + "index.php?fc=module&module=universalpay&controller=ajax",
        data: "typerequest=payment&status=" + status + "&token=" + evomodule['evotoken'] + "&cart=" + evomodule['cartId'] + "&retry=" + evomodule['retry'] + "",
        cache: false,
        success: function (data) {
            if (data == 'error_status') {
                alert('Problem with status payment!');
            } else {
                if (data != '') {
                    $('#statusPayment').val(status.replace(/"/g, ""));
                    $('#evoPayment').val(data.replace(/"/g, ""));
                    $('#submitpayment').submit();
                } else {
                    alert('Error');
                }
            }
        },
        error: function () {
            alert('Error');
        }
    });
}

function pay() {
    var token = evomodule['token'];
    var merchantId = evomodule['merchantId'];
    cashier.show(
        {
            containerId: "payMethods",
            merchantId: merchantId,
            token: token,
            successCallback: handleResult,
            failureCallback: handleResult,
            cancelCallback: handleResult
        }
    );
}

function submitStandalone() {
	$('#btnSubmit').attr("disabled", true);
    $.ajax({
        type: "POST",
        url: "index.php?fc=module&module=universalpay&controller=ajax",
        data: "typerequest=redirect_payment&merchantTxId=" + evomodule['evotoken'] + "&cart=" + evomodule['cartId'] ,
        cache: false,
        success: function (data) {
        	console.log(data);
        	console.log(data === 1);
        	if(data == 1){
        		console.log('dd');
        		$('#submitpayment').submit();
        	}else{
        		alert('Problem with status payment!');
        	}
        },
        error: function () {
			$('#btnSubmit').removeAttr("disabled");
            alert('Error');
        }
    });
}


$(document).ready(function () {
    if (typeof evomodule['paymentType'] != 'undefined' && evomodule['paymentType'] == '1') {
        pay();
    }
	
	$('#btnSubmit').on('click',function(){
    	submitStandalone(); 
    });
});