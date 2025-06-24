<?php

namespace App\Service;

use App\Entity\Order;
use App\Enum\OrderState;
use App\Repository\OrderItemRepository;
use App\Repository\ArticleRepository;
use Doctrine\ORM\EntityManagerInterface;

class OrderInfosService
{
    private $orderItemRepository;
    private $articleRepository;
    private $entityManager;

    public function __construct(OrderItemRepository $orderItemRepository, ArticleRepository $articleRepository, EntityManagerInterface $entityManager)
    {
        $this->orderItemRepository = $orderItemRepository;
        $this->articleRepository = $articleRepository;
        $this->entityManager = $entityManager;
    }

    public function getTotalPrice(int $orderid): float
    {
        $items = $this->orderItemRepository->findByOrder($orderid);
        $total = 0;

        foreach ($items as $item) {
            $id = $item->getArticleid();
            $article = $this->articleRepository->findOneById($id);

            $total = $total + ($article->getPrice() * $item->getQuantity());
        }

        return $total;
    }

    public function isModifiable(Order $order): bool
    {
        if ($order->getState() == OrderState::BASKET) {
            return true;
        } elseif ($order->getState() == OrderState::COMPLETE) {
            return false;
        }

        $orderdate = $order->getDate();
        $today = new \DateTime();
        $interval = $today->diff($orderdate);

        return ($interval->days > 7);
    }

    public function testState(Order $order)
    {
        if ($order->getState() != OrderState::COMPLETE) {
            $orderdate = $order->getDate();
            $today = new \DateTime();

            if ($orderdate < $today) {
                $order->setComplete();
                $this->entityManager->flush();
            }
        }
    }

}

?>
