<?php

/**
 * creditcard
 *
 * @category   PayIntelligent
 * @package    Expression package is undefined on line 6, column 18 in Templates/Scripting/PHPClass.php.
 * @copyright  Copyright (c) 2011 PayIntelligent GmbH (http://payintelligent.de)
 */
class PigmbhPaymillCreditcard extends ModuleFrontController
{
    public function initContent()
    {
        parent::initContent();
        $this->setTemplate('payment_creditcard.tpl');
    }
}
