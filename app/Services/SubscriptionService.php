<?php

namespace App\Services;

use App\Repositories\SubscriptionRepository;
use Exception;

class SubscriptionService
{
    protected $subscriptionRepository;

    public function __construct(SubscriptionRepository $subscriptionRepository)
    {
        $this->subscriptionRepository = $subscriptionRepository;
    }

    public function createSubscription($userId, $packageId, $renewalDate = null)
    {
        try {
            return $this->subscriptionRepository->createSubscription($userId, $packageId, $renewalDate);
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    public function finishSubscription($userId)
    {
        try {
            return $this->subscriptionRepository->finishSubscription($userId);
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }
}
