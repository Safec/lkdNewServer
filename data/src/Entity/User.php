<?php

/** @noinspection PhpUnused */

namespace App\Entity;

use App\Repository\UserRepository;
use App\Service\PhoneParser;
use App\Validator;
use DateTime;
use DateTimeInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\PersistentCollection;
use InvalidArgumentException;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\Ignore;
use Symfony\Component\Validator\Constraints as Assert;

use function Symfony\Component\Translation\t;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`user`')]
#[ORM\HasLifecycleCallbacks]
#[UniqueEntity(fields: ['phone'], message: 'error.phone_is_not_unique')]
#[UniqueEntity(fields: ['email'], message: 'error.email_is_not_unique')]
#[UniqueEntity(fields: ['login'], message: 'error.login_is_not_unique')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    public const ROLE_BOSS = 'ROLE_BOSS';
    public const ROLE_MANAGER = 'ROLE_MANAGER';
    public const ROLE_MANAGER_OBR = 'ROLE_MANAGER_OBR';
    public const ROLE_DESIGNER = 'ROLE_DESIGNER';
    public const ROLE_USER = 'ROLE_USER';

    public const STATUS_NEW = 'NEW';
    public const STATUS_MODERATE = 'MODERATE';
    public const STATUS_ACTIVE = 'ACTIVE';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['csv'])]
    private ?int $id = null;

    #[ORM\Column(type: Types::SIMPLE_ARRAY)]
    private array $roles = [];

    #[ORM\Column(nullable: true)]
    #[Ignore]
    private ?string $password = null;

    #[ORM\Column(options: ['default' => false])]
    #[Groups(['csv'])]
    private bool $active = false;

    #[ORM\Column(length: 16, options: ['default' => self::STATUS_NEW])]
    private string $status = self::STATUS_NEW;

    #[Validator\Phone]
    #[ORM\Column(length: 16, unique: true, nullable: true)]
    #[Groups(['csv'])]
    private ?string $phone = null;

    #[Assert\Email]
    #[ORM\Column(length: 128, unique: true, nullable: true)]
    #[Groups(['csv'])]
    private ?string $email = null;

    #[ORM\Column(length: 32, unique: true, nullable: true)]
    private ?string $login = null;

    #[ORM\Column(length: 64, nullable: true)]
    private ?string $name = null;

    #[ORM\OneToOne(mappedBy: 'relatedUser', cascade: ['persist', 'remove'])]
    private ?Profile $profile;

    #[ORM\OneToOne(mappedBy: 'relatedUser', cascade: ['persist', 'remove'])]
    private ?Requisites $requisites;

    #[ORM\OneToMany(mappedBy: 'author', targetEntity: Draft::class, cascade: ['persist', 'remove'])]
    private ArrayCollection|PersistentCollection|array $drafts;

    #[ORM\OneToMany(mappedBy: 'author', targetEntity: Project::class, cascade: ['persist', 'remove'])]
    private ArrayCollection|PersistentCollection|array $projects;

    #[ORM\OneToMany(mappedBy: 'relatedUser', targetEntity: Document::class, cascade: ['persist', 'remove'])]
    private ArrayCollection|PersistentCollection|array $documents;

    #[ORM\Column(nullable: true)]
    private ?array $changeLog = [];

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    #[Groups(['csv'])]
    private ?DateTimeInterface $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?DateTimeInterface $updatedAt = null;

    #[ORM\Column(length: 128, nullable: true)]
    #[Groups(['csv'])]
    private ?string $activatedBy = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    #[Groups(['csv'])]
    private ?DateTimeInterface $activatedAt = null;

    #[ORM\Column(length: 128, nullable: true)]
    private ?string $moderateBy = null;

    public function __construct()
    {
        $this->profile = new Profile($this);
        $this->requisites = new Requisites($this);
        $this->drafts = new ArrayCollection();
        $this->projects = new ArrayCollection();
        $this->documents = new ArrayCollection();
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return strval($this->getPhone() ?: $this->getEmail() ?: $this->getLogin());
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = self::ROLE_USER;
        /** @var string[] $roles */
        $roles = array_unique(array_filter($roles, 'is_string'));

        return $roles;
    }

    #[Ignore]
    public function hasRole(string $role): bool
    {
        return in_array($role, $this->getRoles(), true);
    }

    public function setRoles(array $roles): self
    {
        $this->roles = array_diff($roles, [self::ROLE_USER]);

        return $this;
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    #[Ignore]
    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(?string $password): self
    {
        $this->password = $password;

        return $this;
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials(): void
    {
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
    }

    public function isActive(): ?bool
    {
        return $this->active;
    }

    public function setActive(bool $active): self
    {
        $this->active = $active;
        $this->status = $active ? self::STATUS_ACTIVE : self::STATUS_NEW;

        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        if (!in_array($status, [self::STATUS_NEW, self::STATUS_MODERATE, self::STATUS_ACTIVE], true)) {
            throw new InvalidArgumentException('Invalid status.');
        }

        $this->status = $status;
        $this->active = $status === self::STATUS_ACTIVE;

        return $this;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setPhone(?string $phone): self
    {
        $phone = trim((string)$phone);
        $this->phone = $phone === '' ? null : PhoneParser::normalize($phone);

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): self
    {
        $email = trim(strtolower((string)$email));
        $this->email = $email === '' ? null : $email;

        return $this;
    }

    public function getLogin(): ?string
    {
        return $this->login;
    }

    public function setLogin(?string $login): self
    {
        $login = trim((string)$login);
        $this->login = $login === '' ? null : $login;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): self
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @throws InvalidArgumentException
     */
    #[ORM\PreFlush]
    public function validateOrExcept(): void
    {
        if (is_string($this->phone) && !PhoneParser::isValid($this->phone)) {
            throw new InvalidArgumentException(t('Invalid phone number.')->getMessage());
        }

        if (is_string($this->email) && filter_var($this->email, FILTER_VALIDATE_EMAIL) === false) {
            throw new InvalidArgumentException(t('Invalid email.')->getMessage());
        }
    }

    public function getProfile(): Profile
    {
        return $this->profile ?? new Profile($this);
    }

    public function setProfile(Profile $profile): self
    {
        if ($profile->getRelatedUser() !== $this) {
            $profile->setRelatedUser($this);
        }

        $this->profile = $profile;

        return $this;
    }

    public function getRequisites(): Requisites
    {
        return $this->requisites ?? new Requisites($this);
    }

    public function setRequisites(Requisites $requisites): self
    {
        // set the owning side of the relation if necessary
        if ($requisites->getRelatedUser() !== $this) {
            $requisites->setRelatedUser($this);
        }

        $this->requisites = $requisites;

        return $this;
    }

    /**
     * @return ArrayCollection|PersistentCollection|array|Draft[]
     */
    public function getDrafts(): ArrayCollection|PersistentCollection|array
    {
        return $this->drafts;
    }

    /**
     * @return ArrayCollection|PersistentCollection|array|Project[]
     */
    public function getProjects(): ArrayCollection|PersistentCollection|array
    {
        return $this->projects;
    }

    /**
     * @return ArrayCollection|PersistentCollection|array|Document[]
     */
    public function getDocuments(): ArrayCollection|PersistentCollection|array
    {
        return $this->documents;
    }

    public function isFullyRegisteredDesigner(): bool
    {
        return (
            $this->hasRole(self::ROLE_DESIGNER) &&
            $this->isActive() &&
            strlen((string)$this->getRequisites()->getContractNumber()) > 0
        );
    }

    public function isReadyForModerate(): bool
    {
        return (
            count($this->getDocuments()) > 0 ||
            strlen((string)$this->getRequisites()->getContractNumber()) > 0
        );
    }

    #[Ignore]
    public function getChangeLog(): ?array
    {
        return $this->changeLog ?? [];
    }

    public function setChangeLog(?array $changeLog): self
    {
        $this->changeLog = $changeLog ?? [];

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

    #[ORM\PreUpdate]
    #[ORM\PrePersist]
    public function setUpdatedAtNow(): self
    {
        $this->updatedAt = new DateTime();

        return $this;
    }

    public function setUpdatedAt(?DateTimeInterface $updatedAt): self
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function getActivatedBy(): ?string
    {
        return $this->activatedBy;
    }

    public function setActivatedBy(?string $activatedBy): self
    {
        $this->activatedBy = $activatedBy;

        return $this;
    }

    public function getActivatedAt(): ?DateTimeInterface
    {
        return $this->activatedAt;
    }

    public function setActivatedAt(?DateTimeInterface $activatedAt): self
    {
        $this->activatedAt = $activatedAt;

        return $this;
    }

    public function setModerateBy(?string $moderateBy): self
    {
        $this->moderateBy = $moderateBy;

        return $this;
    }

    public function getModerateBy(): ?string
    {
        return $this->moderateBy;
    }
}
