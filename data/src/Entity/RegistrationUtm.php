<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class RegistrationUtm
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $utmSource = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $utmMedium = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $utmCampaign = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $utmTerm = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $utmContent = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $ymUid = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: "SET NULL")]
    private ?User $relatedUser = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUtmSource(): ?string
    {
        return $this->utmSource;
    }

    public function setUtmSource(?string $utmSource): self
    {
        $this->utmSource = $utmSource;
        return $this;
    }

    public function getUtmMedium(): ?string
    {
        return $this->utmMedium;
    }

    public function setUtmMedium(?string $utmMedium): self
    {
        $this->utmMedium = $utmMedium;
        return $this;
    }

    public function getUtmCampaign(): ?string
    {
        return $this->utmCampaign;
    }

    public function setUtmCampaign(?string $utmCampaign): self
    {
        $this->utmCampaign = $utmCampaign;
        return $this;
    }

    public function getUtmTerm(): ?string
    {
        return $this->utmTerm;
    }

    public function setUtmTerm(?string $utmTerm): self
    {
        $this->utmTerm = $utmTerm;
        return $this;
    }

    public function getUtmContent(): ?string
    {
        return $this->utmContent;
    }

    public function setUtmContent(?string $utmContent): self
    {
        $this->utmContent = $utmContent;
        return $this;
    }

    public function getYmUid(): ?string
    {
        return $this->ymUid;
    }

    public function setYmUid(?string $ymUid): self
    {
        $this->ymUid = $ymUid;
        return $this;
    }

    public function getRelatedUser(): ?User
    {
        return $this->relatedUser;
    }

    public function setRelatedUser(?User $user): self
    {
        $this->relatedUser = $user;
        return $this;
    }
}