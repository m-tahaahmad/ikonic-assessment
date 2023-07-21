<?php

namespace App\Services;

use App\Models\Affiliate;
use App\Models\Merchant;
use App\Models\Order;
use App\Models\User;

class OrderService
{
    public function __construct(
        protected AffiliateService $affiliateService,
        protected MerchantService $merchantService
    ) {
    }

    /**
     * Process an order and log any commissions.
     * This should create a new affiliate if the customer_email is not already associated with one.
     * This method should also ignore duplicates based on order_id.
     *
     * @param  array{order_id: string, subtotal_price: float, merchant_domain: string, discount_code: string, customer_email: string, customer_name: string} $data
     * @return void
     */
    public function processOrder(array $data)
    {
        // TODO: Complete this method
        $order = Order::where('external_order_id', $data['order_id'])->first();
        if (!$order) {
            $merchant = $this->merchantService->register([
                'domain' => $data['merchant_domain'],
                'name' => $data['customer_name'],
                'email' => $data['customer_email'],
                'api_key' => null
            ]);

            $affiliate = $this->affiliateService->register(
                $merchant,
                $data['customer_email'],
                $data['customer_name'],
                0.1
            );

            Order::create([
                'merchant_id' => $merchant->id ?? Merchant::where('domain', $data['merchant_domain'])->first()->id,
                'affiliate_id' => $affiliate->id ?? null,
                'subtotal' => $data['subtotal_price'],
                'commission_owed' => $data['subtotal_price'] * $affiliate->commission_rate,
                'external_order_id' => $data['order_id']
            ]);
        }
    }
}
