/*  Copyright 2014  Devicesoftware  (email : support@devicesoftware.com) 

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License, version 2, as 
published by the Free Software Foundation.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA    
*/

jQuery(document).ready(function($) {
    
    var dsKashflowInvoiceUrl = dsKashflowVars.ajaxUrl + '?action=ds_kashflow_invoice';
    
    function onDataReceived( data ){
        if( data.success ){
            tb_remove();    
        }
        else{
            $( '#ds_kashflow_error' ).html( 'Error: ' + data.error );
        }
    };

    $('#ds_kashflow_inv_submit').on('click', function() {
        var errorMsg = '';
        var invOrderNumber = $( '#ds_kashflow_inv_order_number').val();
        var invNonce = $( '#ds_kashflow_inv_nonce').val();
        
        var invTo = $( '#ds_kashflow_inv_to').val();
        var emails = invTo.split(',');
        emails.forEach( function( email ){
            if( !validateEmail( email.trim() ) ){
                errorMsg += " Invalid 'To:' email address";
            }                
        });
        var invSubject = $( '#ds_kashflow_inv_subject').val();
        var invBody = $( '#ds_kashflow_inv_body').val();

        var invSenderEmail = $( '#ds_kashflow_inv_sender_email').val();
        if( !validateEmail( invSenderEmail ) )
            errorMsg += " Invalid sender email address:";

        var invSenderName = $( '#ds_kashflow_inv_sender_name').val();
        if( invSenderName.length == 0 )
            errorMsg += " Sender name missing";
            
        //validation failed
        if( errorMsg != '' )
            $( '#ds_kashflow_error' ).html( 'Error:' + errorMsg );
        else{
            $( '#ds_kashflow_error' ).html( '' );
            //post data
            $.ajax({
                url: dsKashflowInvoiceUrl,
                type: "POST",
                data: { invNonce: invNonce, invTo: invTo, invSubject: invSubject, 
                        invBody: invBody, invSenderEmail: invSenderEmail,
                        invSenderName: invSenderName, invOrderNumber: invOrderNumber
                        },
                dataType: "json",
                success: onDataReceived
            });
        }
    });
}); 
   
    
function validateEmail($email) {
    var emailReg = /^([\w-\.]+@([\w-]+\.)+[\w-]{2,4})?$/;
    if( !emailReg.test( $email ) ) {
        return false;
    } else {
        return true;
    }
}