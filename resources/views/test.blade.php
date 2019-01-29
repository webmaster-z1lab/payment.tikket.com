<!DOCTYPE html>
<html>
<head>
    <title>Page Title</title>
</head>
<body>

<form action="/api/v1/transactions" method="POST">
    <input type="hidden" name="hash" value="" required>
    <input type="hidden" name="ip" value="127.0.0.1">
    <input type="hidden" name="amount" value="10000">
    <input type="hidden" name="costumer[user_id]" value="40cc2803-b6a8-4456-b77a-0bf9403559ea">
    <input type="hidden" name="costumer[name]" value="Teste teste">
    <input type="hidden" name="costumer[email]" value="teste@sandbox.pagseguro.com.br">
    <input type="hidden" name="costumer[document]" value="06771783600">
    <input type="hidden" name="costumer[phone][area_code]" value="31">
    <input type="hidden" name="costumer[phone][phone]" value="996225834">
    <input type="hidden" name="items[0][item_id]" value="dfdfdsafsfsda">
    <input type="hidden" name="items[0][description]" value="teste">
    <input type="hidden" name="items[0][quantity]" value="1">
    <input type="hidden" name="items[0][amount]" value="10000">
    <input type="hidden" name="type" value="boleto">
    <button type="submit">Send</button>
</form>

<script type="text/javascript" src="https://stc.sandbox.pagseguro.uol.com.br/pagseguro/api/v2/checkout/pagseguro.directpayment.js"></script>
<script>
    function init() {
        let xhttp = new XMLHttpRequest();
        xhttp.onreadystatechange = function() {
            if (this.readyState === 4 && this.status === 200) {
                let response = JSON.parse(this.responseText);
                PagSeguroDirectPayment.setSessionId(response.session);

                PagSeguroDirectPayment.onSenderHashReady(function(response){
                    if(response.status === 'error') {
                        console.log(response.message);
                        return false;
                    }

                    console.log(response.senderHash);
                    document.querySelector('input[name=hash]').value = response.senderHash;
                });
            }
        };
        xhttp.open("GET", "/api/session", true);
        xhttp.send();
    }

    window.onload = init();
</script>

</body>
</html>