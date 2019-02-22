<?php

namespace Sleepz\KlarnaExternalPayment\Controller\Banktransfer;

class PlaceOrder extends AbstractBanktransfer
{
    protected $_methodCode = \Magento\OfflinePayments\Model\Banktransfer::PAYMENT_METHOD_BANKTRANSFER_CODE;

    /**
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Magento\Sales\Model\OrderFactory $orderFactory
     * @param \Magento\Framework\Url\Helper\Data $urlHelper
     * @param \Magento\Customer\Model\Url $customerUrl
     * @param \Magento\Quote\Api\CartManagementInterface $cartManagement
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        \Magento\Framework\Url\Helper\Data $urlHelper,
        \Magento\Customer\Model\Url $customerUrl,
        \Magento\Quote\Api\CartManagementInterface $cartManagement,
        \Magento\Checkout\Helper\Data $checkoutHelper,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository
    ) {
        parent::__construct(
            $context,
            $customerSession,
            $checkoutSession,
            $orderFactory,
            $urlHelper,
            $customerUrl,
            $cartManagement,
            $checkoutHelper,
            $customerRepository
        );
    }

    /**
     * Execute view action
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        try {
            $quote = $this->_getQuote();
            $quote->setPaymentMethod($this->_methodCode);

            // Set Sales Order Payment
            $quote->getPayment()->importData(['method' => $this->_methodCode]);
            $quote->save();

            // Collect Totals
            $quote->collectTotals();

            $isNewCustomer = false;
            switch ($this->getCheckoutMethod()) {
                case \Magento\Checkout\Model\Type\Onepage::METHOD_GUEST:
                    $this->_prepareGuestQuote();
                    break;
                /*case \Magento\Checkout\Model\Type\Onepage::METHOD_REGISTER:
                    //$this->_prepareNewCustomerQuote();
                    $isNewCustomer = true;
                    break;*/
                default:
                    $this->_prepareCustomerQuote();
                    break;
            }

            //$this->_cartManagement->placeOrder($quote->getId());
            $order = $this->_cartManagement->submit($quote);

            $quoteId = $quote->getId();
            $this->_checkoutSession->setLastQuoteId($quoteId)->setLastSuccessQuoteId($quoteId);

            // add order information to the session
            $this->_checkoutSession
                ->setLastOrderId($order->getId())
                ->setRedirectUrl('checkout/onepage/success')
                ->setLastRealOrderId($order->getIncrementId())
                ->setLastOrderStatus($order->getStatus());
        } catch(\Exception $e) {
            $this->messageManager->addExceptionMessage($e, __('Something went wrong while processing your order. Please try again later.'));
            $this->_redirect('*/*/review');
        }

        $this->_redirect('checkout/onepage/success');
    }
}