<?php
/*  Copyright 2013  Devicesoftware  (email : support@devicesoftware.com) 

    Author: swicks@devicesoftware.com

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

class KF_Api{    
    
    private $last_error;
    
    public function __construct()
    {
        
    }   
    public function get_last_error()
    {
        return $this->last_error;
    }
    
    private function call_api($name, $headers, $xml)
    {
        // standard headers
        $std_headers = array(
            'Content-Type'=> 'text/xml; charset=utf-8',
            'Accept' => 'text/xml',
            'Host' => 'securedwebapp.com'
        );
        // merge headers
        $headers = array_merge($std_headers, $headers);
        
            $params = array( 
                'body' => $xml,
                'method' => 'POST',
                'headers' => $headers,
                'sslverify' => false
            );   
            
            $result = wp_remote_post(DS_KASHFLOW_SOAP_URL, $params);
           
           if( is_wp_error( $result ) )
           {
               foreach( $result->errors as $error_key => $error_values )
               {
                   foreach( $error_values as $error_value)
                   {
                        echo $error_key . ": " . $error_value;
                   }
               }
               return;
           }            
            if($result['response']['message']== "OK")
            {
                $soap_body = preg_replace("/(<\/?)(\w+):([^>]*>)/", "$1$2$3", $result['body']);
                $sxml = new SimpleXMLElement($soap_body, LIBXML_NOWARNING);
                $status = (string)$sxml->soapBody->{$name . "Response"}->Status[0];
                if($status == "NO")
                {
                    $this->last_error = (string)$sxml->soapBody->{$name . "Response"}->StatusDetail[0];
                    return false;
                }
                return (array)$sxml->soapBody->{$name . "Response"}->{$name . "Result"};
            }
            else
            {
                $this->last_error = __("Failed to connect to KashFlow server.", "ds-kashflow");
                return false;
            }
                
            
            //$sxml = new SimpleXMLElement($response['body']);
            return $response;
    }   
    private function call_soap_function($name, $xml = '')
    {
        $username = get_option('ds_kashflow_username');
        $password = get_option('ds_kashflow_api_password');
        
        
        $soap_xml  = '<?xml version="1.0" encoding="utf-8"?>';
        $soap_xml .= '<soap12:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:soap12="http://www.w3.org/2003/05/soap-envelope">';
        $soap_xml .= '<soap12:Body>';
        
        $soap_xml .= '<' . $name . ' xmlns="KashFlow">';
        $soap_xml .= '<UserName>' . $username . '</UserName>';
        $soap_xml .= '<Password>' . $password . '</Password>';
        $soap_xml .= $xml;
        $soap_xml .= '</' . $name . '></soap12:Body></soap12:Envelope>';
        
        $headers = array(
            'Content-Length' => strlen($soap_xml),
            'SOAPAction' => '"KashFlow/' . $name . '"'
        );
        
        return $this->call_api($name, $headers, $soap_xml);
    }
    
    // API Calls
    
    public function get_company_details()
    {        
        return $this->call_soap_function("GetCompanyDetails");
    }
    
    public function get_customer_by_email( $email )
    {
        $xml = '<CustomerEmail>' . $email . '</CustomerEmail>';
        $res = $this->call_soap_function('GetCustomerByEmail', $xml);
        if($res)
        {
            $customer = new KF_Customer();
            $customer->populate($res);
            return $customer;
        }
        return false;        
    }
    
    public function insert_customer($customer)
    {
        //create new customer
        $res = $this->call_soap_function('InsertCustomer', $customer);
        if(is_array($res)) $res = $res[0];
        return $res;
    }
    
    public function update_customer($customer)
    {
        $res = $this->call_soap_function('UpdateCustomer', $customer);
        return $res;
    }
    
    public function insert_quote($quote, $lines = array())
    {
        $xml = '<Quote>';
        $xml .= (string)$quote;
        
        if(count($lines)>0)
        {
            $xml .= '<Lines>';
            foreach($lines as $line)
            {
                $xml .= '<anyType xsi:type="InvoiceLine">';
                $xml .= (string)$line;
                $xml .= '</anyType>';
            }
            $xml .= '</Lines>';
        }
        $xml .= '</Quote>';
        $res = $this->call_soap_function('InsertQuote', $xml);
        if(is_array($res)) $res = $res[0];
        return $res;        
    }
    
    public function insert_quote_line($quote_id, $quote_line)
    {
        $xml = '<QuoteID>' . $invoice_id . '</QuoteID>';
        $xml .= (string)$quote_line;
        $res = $this->call_soap_function('InsertQuoteLine', $xml);
        if(is_array($res)) $res = $res[0];
        return $res;
    }    
    
    public function insert_invoice($invoice, $lines = array())
    {
        $xml = '<Inv>';
        $xml .= (string)$invoice;
        
        if(count($lines)>0)
        {
            $xml .= '<Lines>';
            foreach($lines as $line)
            {
                $xml .= '<anyType xsi:type="InvoiceLine">';
                $xml .= (string)$line;
                $xml .= '</anyType>';
            }
            $xml .= '</Lines>';
        }
        $xml .= '</Inv>';
        $res = $this->call_soap_function('InsertInvoice', $xml);
        if(is_array($res)) $res = $res[0];
        return $res;
    }
    public function insert_invoice_line($invoice_id, $invoice_line)
    {
        $xml = '<InvoiceID>' . $invoice_id . '</InvoiceID>';
        $xml .= (string)$invoice_line;
        $res = $this->call_soap_function('InsertInvoiceLine', $xml);
        if(is_array($res)) $res = $res[0];
        return $res;
    }
    /**
    * Take Payment for an invoice
    * 
    * @param KFInvoicePayment $invoice_payment
    */
    public function insert_invoice_payment($invoice_payment)
    {
        $res = $this->call_soap_function('InsertInvoicePayment', $invoice_payment);        
        if(is_array($res)) $res = $res[0];
        return $res;        
    }
    public function get_inv_pay_methods()
    {
        $res = $this->call_soap_function('GetInvPayMethods');
        return $res;
    }
    public function email_invoice( $invoice_number, $from_email = "", $from_name = "", $subject_line = "", $body ="", $recipient_email = "" ){
        $xml = '<InvoiceNumber>' . $invoice_number . '</InvoiceNumber>';
        $xml .= '<FromEmail>' . $from_email . '</FromEmail>';
        $xml .= '<FromName>' . $from_name . '</FromName>';
        $xml .= '<SubjectLine>' . $subject_line . '</SubjectLine>';
        $xml .= '<Body>' . $body . '</Body>';        
        $xml .= '<RecipientEmail>' . $recipient_email . '</RecipientEmail>'; 
        
        $res = $this->call_soap_function('EmailInvoice', $xml);
        if(is_array($res)) $res = $res[0];
        return $res;        
    }
    /**
    * get the current nominal codes from KashFlow
    * 
    */
    public function get_nominal_codes()
    {
        return $this->call_soap_function('GetNominalCodes');
    }
    
    /**
    * get the current sales types from KashFlow
    * 
    */
    public function get_sales_types()
    {
        $sales_types = array();
        $types =  $this->call_soap_function('GetProducts');
        if(is_array($types))
        {
            if(is_array($types['Product']))
            {
                foreach($types['Product'] as $product)
                {
                    $sales_types[] = array(
                                        "ID" => (int)$product->ProductID, 
                                        'Name' => (string)$product->ProductName, 
                                        'Code' => (string)$product->ProductCode 
                    );
                }    
            }
        }
        return $sales_types;
    }
    
    /**
    * get the bank accounts from KashFlow
    * 
    */
    public function get_bank_accounts()
    {
        $bank_accounts = array();
        $accounts =  $this->call_soap_function('GetBankAccounts');
        if(is_array($accounts))
        {
            if(is_array($accounts['BankAccount']))
            {
                foreach($accounts['BankAccount'] as $bank_account)
                {
                    $bank_accounts[] = array(
                                        "ID" => (int)$bank_account->AccountID, 
                                        'Name' => (string)$bank_account->AccountName, 
                                        'Code' => (string)$bank_account->AccountCode 
                    );
                }    
            }
        }
        return $bank_accounts;
    }    
    
    public function get_customer_sources()
    {
        $customer_sources = array();
        $types =  $this->call_soap_function('GetCustomerSources');
        if(is_array($types))
        {
            if(is_array($types['BasicDataset']))
            {
                foreach($types['BasicDataset'] as $basic_dataset)
                {
                    $customer_sources[] = array(
                                        "ID" => (int)$basic_dataset->ID, 
                                        'Name' => (string)$basic_dataset->Name, 
                                        'Value' => (int)$basic_dataset->Value 
                    );
                }    
            }
            elseif(is_object($types['BasicDataset']))
            {
                $basic_dataset = $types['BasicDataset'];
                $customer_sources[] = array(
                                    "ID" => (int)$basic_dataset->ID, 
                                    'Name' => (string)$basic_dataset->Name, 
                                    'Value' => (int)$basic_dataset->Value 
                );
            }
        }
        return $customer_sources;
    }

    

    public function get_sub_products($nominal_id)
    {
        $xml = '<NominalID>' . $nominal_id . '</NominalID>';
        $res = $this->call_soap_function('GetSubProducts', $xml);
        return $res;
    }
    public function get_sub_product_by_code($code = '')
    {
        // return false if no valid code
        if( empty( $code ) ) 
            return false;
        
        $xml = '<ProductCode>' . $code . '</ProductCode>';
        $res = $this->call_soap_function('GetSubProductByCode', $xml);
        return $res;
    }
    public function update_sub_product_by_code($sub_product)
    {
        if($res = $this->get_sub_product_by_code($sub_product->Code))
        {
            $id = $res->id;
            $customer->data = array_merge($res->data, $sub_product->data );
            //update existing sub product
            $res = $this->call_soap_function('AddOrUpdateSubProduct', $sub_product);
            $res = $id;
        }
        else
        {
            //create new sub product
            $sub_product->id = 0;
            $res = $this->call_soap_function('AddOrUpdateSubProduct', $sub_product);
            if(is_array($res)) $res = $res[0];            
        }
        return $res;        
    }  
}

?>
