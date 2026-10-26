<?php

/** @noinspection PhpUnused */

namespace App\Entity;

use App\Repository\ProfileRepository;
use DateTime;
use DateTimeInterface;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: ProfileRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Profile
{
    public const LEGAL_ENTITY_LTD = 'ООО';
    public const LEGAL_ENTITY_INDIVIDUAL = 'ИП';
    public const LEGAL_ENTITY_SELF_EMPLOYED = 'Самозанятый';

    public const PROFESSION_DESIGNER = 'Дизайнер';
    public const PROFESSION_ARCHITECT = 'Архитектор';
    public const PROFESSION_INTERIOR_STYLIST = 'Интерьерный стилист';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\OneToOne(inversedBy: 'profile', cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $relatedUser;

    #[ORM\Column(length: 128, nullable: true)]
    #[Groups(['csv'])]
    private ?string $fullName = null;

    #[ORM\Column(length: 64, nullable: true)]
    #[Groups(['csv'])]
    private ?string $city = null;

    #[ORM\Column(length: 64, nullable: true)]
    #[Groups(['csv'])]
    private ?string $telegram = null;

    #[ORM\Column(length: 32, nullable: true)]
    #[Groups(['csv'])]
    private ?string $legalEntity = null;

    #[ORM\Column(length: 32, nullable: true)]
    #[Groups(['csv'])]
    private ?string $profession = null;

    #[ORM\Column(type: Types::SIMPLE_ARRAY, nullable: true)]
    #[Groups(['csv'])]
    private array $styles = [];

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['csv'])]
    private ?string $info = null;

    #[ORM\Column(type: Types::SIMPLE_ARRAY, nullable: true)]
    #[Groups(['csv'])]
    private array $sites = [];

    #[ORM\Column(type: Types::SIMPLE_ARRAY, nullable: true)]
    #[Groups(['csv'])]
    private array $socialMedias = [];

    #[ORM\Column(type: Types::BOOLEAN, nullable: false, options: ['default' => false])]
    private bool $userAgreementAccepted = false;

    #[ORM\Column(type: Types::BOOLEAN, nullable: false, options: ['default' => false])]
    private bool $userAgreementAcceptedReklama = false;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $avatar = null;

    #[ORM\Column(length: 32, nullable: true)]
    private ?string $position = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?DateTimeInterface $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?DateTimeInterface $updatedAt = null;

    public function __construct(User $user)
    {
        $this->relatedUser = $user;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getRelatedUser(): ?User
    {
        return $this->relatedUser;
    }

    public function setRelatedUser(User $relatedUser): self
    {
        $this->relatedUser = $relatedUser;

        return $this;
    }

    public function getFullName(): ?string
    {
        return $this->fullName;
    }

    public function setFullName(?string $fullName): self
    {
        $this->fullName = $fullName;

        return $this;
    }

    public function getPhone(): ?string
    {
        return $this->relatedUser?->getPhone();
    }

    public function setPhone(?string $phone): self
    {
        $this->relatedUser?->setPhone($phone);

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->relatedUser?->getEmail();
    }

    public function setEmail(?string $email): self
    {
        $this->relatedUser?->setEmail($email);

        return $this;
    }

    public function getCity(): ?string
    {
        return $this->city;
    }

    public function setCity(?string $city): self
    {
        $this->city = $city;

        return $this;
    }

    public function getTelegram(): ?string
    {
        return $this->telegram;
    }

    public function setTelegram(?string $telegram): self
    {
        $this->telegram = $telegram;

        return $this;
    }

    public function getLegalEntity(): ?string
    {
        return $this->legalEntity;
    }

    public function setLegalEntity(?string $legalEntity): self
    {
        $this->legalEntity = $legalEntity;

        return $this;
    }

    public function getProfession(): ?string
    {
        return $this->profession;
    }

    public function setProfession(?string $profession): self
    {
        $this->profession = $profession;

        return $this;
    }

    public function getStyles(): array
    {
        return array_filter($this->styles);
    }

    public function setStyles(array $styles): self
    {
        $this->styles = array_filter($styles);

        return $this;
    }

    public function getInfo(): ?string
    {
        return $this->info;
    }

    public function setInfo(?string $info): self
    {
        $this->info = $info;

        return $this;
    }

    public function getSites(): array
    {
        return array_filter($this->sites);
    }

    public function setSites(array $sites): self
    {
        $this->sites = array_filter($sites);

        return $this;
    }

    public function getSocialMedias(): array
    {
        return array_filter($this->socialMedias);
    }

    public function setSocialMedias(array $socialMedias): self
    {
        $this->socialMedias = array_filter($socialMedias);

        return $this;
    }

    public function isUserAgreementAccepted(): ?bool
    {
        return $this->userAgreementAccepted;
    }

    public function setUserAgreementAccepted(bool $userAgreementAccepted): self
    {
        $this->userAgreementAccepted = $userAgreementAccepted;

        return $this;
    }

    public function isUserAgreementAcceptedReklama(): ?bool
    {
        return $this->userAgreementAcceptedReklama;
    }

    public function setUserAgreementAcceptedReklama(bool $userAgreementAcceptedReklama): self
    {
        $this->userAgreementAcceptedReklama = $userAgreementAcceptedReklama;

        return $this;
    }

    public function getAvatar(): ?string
    {
        return $this->avatar;
    }

    public function setAvatar(?string $avatar): self
    {
        $this->avatar = $avatar;

        return $this;
    }

    public function getPosition(): ?string
    {
        return $this->position;
    }

    public function setPosition(?string $position): self
    {
        $this->position = $position;

        return $this;
    }

    public function getCreatedAt(): ?DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(?DateTimeInterface $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    #[ORM\PrePersist]
    public function setCreatedAtNow(): self
    {
        $this->createdAt = new DateTime();

        return $this;
    }

    public function getUpdatedAt(): ?DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?DateTimeInterface $updatedAt): self
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    #[ORM\PreUpdate]
    #[ORM\PrePersist]
    public function setUpdatedAtNow(): self
    {
        $this->updatedAt = new DateTime();

        return $this;
    }
}
