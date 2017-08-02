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

class KF_Customer extends KF_Base{
    
    protected $wrapper = 'custr';
    
    protected $data_fields = array('CustomerID', 'Code', 'Name', 'Contact', 'Telephone', 'Mobile', 'Fax', 'Email', 
            'Address1', 'Address2', 'Address3', 'Address4', 'Postcode', 'Website', 'EC', 'OutsideEC',
            'Notes', 'Source', 'Discount', 'ShowDiscount', 'PaymentTerms', 'ExtraText1', 'ExtraText2', 'ExtraText3',
            'ExtraText4', 'ExtraText5', 'ExtraText6', 'ExtraText7', 'ExtraText8', 'ExtraText9', 'ExtraText10', 'ExtraText11',
            'ExtraText12', 'ExtraText13', 'ExtraText14', 'ExtraText15', 'ExtraText16', 'ExtraText17', 'ExtraText18', 'ExtraText19',
            'ExtraText20', 'CheckBox1', 'CheckBox2', 'CheckBox3', 'CheckBox4', 'CheckBox5', 'CheckBox6', 'CheckBox7', 'CheckBox8',
            'CheckBox9', 'CheckBox10', 'CheckBox11', 'CheckBox12', 'CheckBox13', 'CheckBox14', 'CheckBox15', 'CheckBox16', 'CheckBox17',
            'CheckBox18', 'CheckBox19', 'CheckBox20', 'Created', 'Updated', 'CurrencyID', 'ContactTitle', 'ContactFirstName', 'ContactLastName',
            'CustHasDeliveryAddress', 'DeliveryAddress1', 'DeliveryAddress2', 'DeliveryAddress3', 'DeliveryAddress4', 'DeliveryPostcode', 'VATNumber');

    public function __construct()
    {
    }
}

?>
