<?php
namespace PHPSC\PagSeguro\Transaction;

use DateTime;
use PHPSC\PagSeguro\Customer\Address;
use PHPSC\PagSeguro\Customer\Phone;
use PHPSC\PagSeguro\Customer\Sender;
use PHPSC\PagSeguro\Customer\Shipping;
use PHPSC\PagSeguro\Item;
use SimpleXMLElement;

class Decoder
{
    /**
     * @param SimpleXMLElement $obj
     *
     * @return Transaction
     */
    public function decode(SimpleXMLElement $obj)
    {
        return new Transaction(
            (string) $obj->code,
            isset($obj->reference) ? (string) $obj->reference : null,
            (int) $obj->type,
            (int) $obj->status,
            new DateTime((string) $obj->date),
            new DateTime((string) $obj->lastEventDate),
            new PaymentMethod(
                (int) $obj->paymentMethod->type,
                (int) $obj->paymentMethod->code
            ),
            (float) $obj->grossAmount,
            (float) $obj->discountAmount,
            (float) $obj->feeAmount,
            (float) $obj->netAmount,
            (float) $obj->extraAmount,
            (int) $obj->installmentCount,
            $this->createItems($obj->items),
            $this->createSender($obj->sender),
            $this->createShipping($obj->shipping),
            isset($obj->escrowEndDate) ? new DateTime((string) $obj->escrowEndDate) : null
        );
    }

    /**
     * @param SimpleXMLElement $itemsNode
     * @return array
     */
    protected function createItems(SimpleXMLElement $itemsNode)
    {
        $items = array();

        foreach ($itemsNode->item as $item) {
            $items[] = new Item(
                (string) $item->id,
                (string) $item->description,
                (float) $item->amount,
                (int) $item->quantity,
                isset($item->shippingCost) ? (float) $item->shippingCost : null,
                isset($item->weight) ? (int) $item->weight : null
            );
        }

        return $items;
    }

    /**
     * @param SimpleXMLElement $sender
     * @return Sender
     */
    protected function createSender(SimpleXMLElement $sender)
    {
        $phone = null;

        if ($sender->phone) {
            $phone = new Phone(
                (string) $sender->phone->areaCode,
                (string) $sender->phone->number
            );
        }

        return new Sender(
            (string) $sender->email,
            isset($sender->name) ? (string) $sender->name : null,
            $phone
        );
    }

    /**
     * @param SimpleXMLElement $shipping
     * @return Shipping
     */
    protected function createShipping(SimpleXMLElement $shipping)
    {
        $address = null;

        if ($shipping->address) {
            $address = new Address(
                (string) $shipping->address->state,
                (string) $shipping->address->city,
                (string) $shipping->address->postalCode,
                (string) $shipping->address->district,
                (string) $shipping->address->street,
                (string) $shipping->address->number,
                (string) $shipping->address->complement,
                (string) $shipping->address->country
            );
        }

        return new Shipping(
            (int) $shipping->type,
            $address,
            isset($shipping->cost) ? (float) $shipping->cost : null
        );
    }
}