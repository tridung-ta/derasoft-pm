<?php
include_once(ROOT_PATH . 'classes/dao/editorialsubscriptions.class.php');

class EditorialEntitlement
{
    var $subscriptions;

    function __construct($store_id = 1, $database = '', $subscriptions = null)
    {
        $this->subscriptions = $subscriptions ?: new EditorialSubscriptions($store_id, $database);
    }

    function isPremiumArticle($article)
    {
        if (!$article || !method_exists($article, 'getProperty')) return false;
        return strtolower(trim((string)$article->getProperty('custom_editorial_access'))) === 'premium';
    }

    function getActiveSubscription($customerId, $at = '')
    {
        return $this->subscriptions->getActiveForCustomer((int)$customerId, $at);
    }

    function hasPremiumAccess($customerId, $at = '')
    {
        return (bool)$this->getActiveSubscription($customerId, $at);
    }

    function canReadArticle($customerId, $article, $at = '')
    {
        if (!$this->isPremiumArticle($article)) return true;
        return $this->hasPremiumAccess($customerId, $at);
    }
}
?>
