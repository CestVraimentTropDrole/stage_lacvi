<?php

namespace App\Entity;

use App\Repository\NoticesRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: NoticesRepository::class)]
class Notices
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $legal_notices = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $privacy_policy = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $gcu = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLegalNotices(): ?string
    {
        return $this->legal_notices;
    }

    public function setLegalNotices(string $legal_notices): static
    {
        $this->legal_notices = $legal_notices;

        return $this;
    }

    public function getPrivacyPolicy(): ?string
    {
        return $this->privacy_policy;
    }

    public function setPrivacyPolicy(string $privacy_policy): static
    {
        $this->privacy_policy = $privacy_policy;

        return $this;
    }

    public function getGcu(): ?string
    {
        return $this->gcu;
    }

    public function setGcu(string $gcu): static
    {
        $this->gcu = $gcu;

        return $this;
    }
}
