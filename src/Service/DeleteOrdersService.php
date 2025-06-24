<?php

namespace App\Service;

use App\Entity\Order;
use App\Repository\OrderItemRepository;
use Doctrine\ORM\EntityManagerInterface;

class DeleteOrdersService
{
    private $orderItemRepository;
    private $entityManager;

    public function __construct(OrderItemRepository $orderItemRepository, EntityManagerInterface $entityManager)
    {
        $this->orderItemRepository = $orderItemRepository;
        $this->entityManager = $entityManager;
    }

    public function dropOrder(Order $order): static
    {
        $orderitems = $this->orderItemRepository->findBy([
            'orderid' => $order,
        ]);

        foreach ($orderitems as $orderitem) {
            $this->entityManager->remove($orderitem);
            $this->entityManager->flush();
        }
        $this->entityManager->remove($order);
        $this->entityManager->flush();

        return $this;
    }

}

?>
