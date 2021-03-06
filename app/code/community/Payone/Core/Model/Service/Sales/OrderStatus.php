<?php
/**
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the GNU General Public License (GPL 3)
 * that is bundled with this package in the file LICENSE.txt
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Payone_Core to newer
 * versions in the future. If you wish to customize Payone_Core for your
 * needs please refer to http://www.payone.de for more information.
 *
 * @category        Payone
 * @package         Payone_Core_Model
 * @subpackage      Service
 * @copyright       Copyright (c) 2012 <info@noovias.com> - www.noovias.com
 * @author          Matthias Walter <info@noovias.com>
 * @license         <http://www.gnu.org/licenses/> GNU General Public License (GPL 3)
 * @link            http://www.noovias.com
 */

/**
 *
 * @category        Payone
 * @package         Payone_Core_Model
 * @subpackage      Service
 * @copyright       Copyright (c) 2012 <info@noovias.com> - www.noovias.com
 * @license         <http://www.gnu.org/licenses/> GNU General Public License (GPL 3)
 * @link            http://www.noovias.com
 */
class Payone_Core_Model_Service_Sales_OrderStatus extends Payone_Core_Model_Service_Abstract
{
    /**
     * States where order should not move further
     *
     * @var array
     */
    protected $orderFinalStates = array (Mage_Sales_Model_Order::STATE_CANCELED);

    /**
     * @param Mage_Sales_Model_Order $order
     * @param Payone_Core_Model_Domain_Protocol_TransactionStatus $transactionStatus
     * @return void
     */
    public function updateByTransactionStatus(
        Mage_Sales_Model_Order $order, Payone_Core_Model_Domain_Protocol_TransactionStatus $transactionStatus
    )
    {
        // Update Status of Transaction
        $order->setPayoneTransactionStatus($transactionStatus->getTxaction());

        // Update dunning status
        if ($transactionStatus->getReminderlevel()) {
            $order->setPayoneDunningStatus($transactionStatus->getReminderlevel());
        }

        // If order reached final stage, it should not be updated
        if (in_array($order->getState(), $this->orderFinalStates)) {
            return;
        }

        // Status mapping of Order by Transaction Status
        $statusMapping = $this->getConfigStore()->getGeneral()->getStatusMapping();

        $txAction = $transactionStatus->getTxaction();
        /**
         * @var $paymentMethod Payone_Core_Model_Payment_Method_Abstract
         */
        $paymentMethod = $order->getPayment()->getMethodInstance();
        $type = $paymentMethod->getMethodType();

        // Mapping
        $mapping = $statusMapping->getByType($type);
        if (!is_array($mapping) or !array_key_exists($txAction, $mapping)) {
            return;
        }

        // Check for valid Mapping
        $mappingOrderState = $mapping[$txAction];
        if (!is_array($mappingOrderState)
                or !array_key_exists('status', $mappingOrderState)
                or !array_key_exists('state', $mappingOrderState)
        ) {
            return;
        }

        // Get State / Status and set to Order
        $newOrderState = $mappingOrderState['state'];
        $newOrderStatus = $mappingOrderState['status'];

        if($newOrderState != '') {
            $order->setState($newOrderState, $newOrderStatus);
        }
        else {
            $order->setStatus($newOrderStatus);
        }
    }

    /**
     * @param Mage_Sales_Model_Order $order
     * @param Payone_Api_Response_Interface $response
     */
    public function updateByApiResponse(
        Mage_Sales_Model_Order $order, Payone_Api_Response_Interface $response
    )
    {
        $order->setPayoneTransactionStatus($response->getStatus());
    }

}