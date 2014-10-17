<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
class util {
    
    /**
     * @param integer $id
     * @return boolean
     */
    public function isPaymillOrder($id){
        $db = Db::getInstance();
        $orderId = (int)$id;
        $result = $db->executeS('SELECT COUNT(*) AS "count" FROM `'._DB_PREFIX_.'pigmbh_paymill_transactiondata` WHERE `id`='. $db->_escape($orderId),true);
        $returnValue = false;
        if(is_array($result) && isset($result[0]) && isset($result[0]['count'])){
            $returnValue = (boolean)$result[0]['count'];    
        }
        return $returnValue;
    }
    
}
