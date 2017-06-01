(function( $ ) {
    var xemPayment = {
        init: function () {

            $.initialize("#xem-qr", function() {
                //Get form
                if ( $( '#xem-form' ).length ) {
                    this.form = $('#xem-form');
                    this.email = this.form.data('email');
                    this.amount = this.form.data('amount');
                    this.currency = this.form.data('currency');
                    this.xemAddress = this.form.data('xem-address');
                    this.xemAmount = this.form.data('xem-amount');
                    this.xemRef = this.form.data('xem-ref');
                    this.infoWrapper = '#xem-info';
                    this.amountWrapper = '#xem-amount-wrapper';
                    this.process = '#xem-process';
                }

                //Prepare NEM qr code data
                // Invoice model for QR
                this.paymentData = {
                    "v": wc_xem_params.test ? 1 : 2,
                    "type": 2,
                    "data": {
                        "addr": this.xemAddress.toUpperCase().replace(/-/g, ''),
                        "amount": this.xemAmount * 1000000,
                        "msg": this.xemRef,
                        "name": "XEM payment to " + wc_xem_params.store
                    }
                };
                //Generate the QR code with address
                new QRCode("xem-qr", {
                    text: JSON.stringify(this.paymentData),
                    size: 256,
                    fill: '#000',
                    quiet: 0,
                    ratio: 2
                });

                /*Add copy functinality to amount, ref and nem address*/
                if(Clipboard.isSupported()){
                    new Clipboard('#xem-amount-wrapper');
                    new Clipboard('#xem-address-wrapper');
                    new Clipboard('#xem-ref-wrapper');
                }

                //Set payment button to disabled if whole chech is updated.
                if($( 'div.payment_box.payment_method_xem' ).is(':visible')){
                    $( '#place_order' ).attr( 'disabled', true);
                }else{
                    $( '#place_order' ).attr( 'disabled', false)
                }

                /*Set pay button to disabled and start waiting for payments*/
                $('.wc_payment_methods  > li').on( 'click', 'input[name="payment_method"]',function () {
                    if ( $( this ).is( '#payment_method_xem' ) ) {
                        $( '#place_order' ).attr( 'disabled', true);
                    }else{
                        $( '#place_order' ).attr( 'disabled', false)
                    }
                });

                var options = {
                    classname: 'nanobar-xem',
                    id: 'xem-nanobar',
                    target: document.getElementById('xem-process')
                };

                xemPayment.nanobar = new Nanobar( options );
            });


        },
        updateXemAmount: function () {
            this.ajaxGetXemAmount().done(function (res) {
                console.log(res);

                if(res.success === true && res.data.amount > 0){
                    $(this.amountWrapper).text(res.data.amount)
                }
            });

        },
        checkForXemPayment: function () {
            this.nanobar.go(25);
            $.ajax({
                url: wc_xem_params.wc_ajax_url,
                type: 'post',
                data: {
                    action: 'woocommerce_check_for_payment',
                    nounce: wc_xem_params.nounce
                }
            }).done(function (res) {
                $('#xem-check').html('<p id="xem-check">Checking..</p>');
                //console.log(res);
                //console.log("Match: " + res.data.match);
                if(res.success === true && res.data.match === true){
                    $( '#place_order' ).attr( 'disabled', false);
                    $( '#place_order' ).trigger( 'click');
                }
                setTimeout(function() {
                    xemPayment.checkForXemPayment();
                }, 5000);
            });
            this.nanobar.go(100);
        },

        ajaxGetXemAmount: function () {
            return $.ajax({
                url: wc_xem_params.wc_ajax_url,
                type: 'post',
                data: {
                    action: 'woocommerce_get_xem_amount',
                    nounce: wc_xem_params.nounce
                }
            })
        }
    };

    xemPayment.init();
    setTimeout(function() {
        xemPayment.checkForXemPayment();
    }, 5000);

})( jQuery );