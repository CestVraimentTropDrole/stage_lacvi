<?php

namespace App\Entity;

use App\Enum\OrderState;
use App\Repository\OrderRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: OrderRepository::class)]
#[ORM\Table(name: '`order`')]
class Order
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'orders')]
    private ?User $user = null;

    #[ORM\Column]
    private ?\DateTime $date = null;

    #[ORM\Column]
    private ?\DateTime $created = null;

    #[ORM\Column(enumType: OrderState::class)]
    private OrderState $state;

    /**
     * @var Collection<int, OrderItem>
     */
    #[ORM\OneToMany(targetEntity: OrderItem::class, mappedBy: 'orderid')]
    private Collection $orderItems;

    public function __construct()
    {
        // État par défaut
        $this->state = OrderState::BASKET;
        $this->orderItems = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function getDate(): ?\DateTime
    {
        return $this->date;
    }

    public function setDate(\DateTime $date): static
    {
        $this->date = $date;

        return $this;
    }

    public function getCreated(): ?\DateTime
    {
        return $this->created;
    }

    public function setCreated(\DateTime $created): static
    {
        $this->created = $created;

        return $this;
    }

    public function getState(): OrderState
    {
        return $this->state;
    }

    public function setState(OrderState $state): static
    {
        $this->state = $state;
        return $this;
    }

    // Méthodes utiles
    public function isBasket(): bool
    {
        return $this->state === OrderState::BASKET;
    }

    public function isComplete(): bool
    {
        return $this->state === OrderState::COMPLETE;
    }

    public function moveToNextState(): static
    {
        $this->state = match($this->state) {
            OrderState::BASKET => OrderState::CREATED,
            OrderState::CREATED => OrderState::VALIDATE,
            OrderState::VALIDATE => OrderState::COMPLETE,
            OrderState::COMPLETE => OrderState::COMPLETE, // Reste complet
        };
        return $this;
    }

    public function setComplete(): static
    {
        $this->state = OrderState::COMPLETE;
        return $this;
    }

    public function setBasket(): static
    {
        $this->state = OrderState::BASKET;
        return $this;
    }

    /**
     * @return Collection<int, OrderItem>
     */
    public function getOrderItems(): Collection
    {
        return $this->orderItems;
    }

    public function addOrderItem(OrderItem $orderItem): static
    {
        if (!$this->orderItems->contains($orderItem)) {
            $this->orderItems->add($orderItem);
            $orderItem->setOrderid($this);
        }

        return $this;
    }

    public function removeOrderItem(OrderItem $orderItem): static
    {
        if ($this->orderItems->removeElement($orderItem)) {
            // set the owning side to null (unless already changed)
            if ($orderItem->getOrderid() === $this) {
                $orderItem->setOrderid(null);
            }
        }

        return $this;
    }
}
