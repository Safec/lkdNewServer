<?php

/** @noinspection PhpUnused */

namespace App\Entity;

use App\Repository\RequisitesRepository;
use App\Service\PhoneParser;
use App\Validator;
use DateTime;
use DateTimeInterface;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use InvalidArgumentException;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\Ignore;
use Symfony\Component\Validator\Constraints as Assert;

use function Symfony\Component\Translation\t;

#[ORM\Entity(repositoryClass: RequisitesRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Requisites
{
    public const LEGAL_ENTITY_LTD = 'ООО';
    public const LEGAL_ENTITY_INDIVIDUAL = 'ИП';
    public const LEGAL_ENTITY_SELF_EMPLOYED = 'Самозанятый';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\OneToOne(inversedBy: 'requisites', cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(nullable: false)]
    #[Ignore]
    private ?User $relatedUser;

    #[ORM\Column(length: 16, nullable: true)]
    #[Groups(['csv'])]
    private ?string $legalEntity = null;

    #[ORM\Column(length: 16, nullable: true)]
    #[Groups(['csv'])]
    private ?string $taxSystem = null;

    #[ORM\Column(length: 16, nullable: true)]
    #[Groups(['csv'])]
    private ?string $vatRate = null;

    #[ORM\Column(length: 128, nullable: true)]
    #[Groups(['csv'])]
    private ?string $fullName = null;

    #[Validator\Phone]
    #[ORM\Column(length: 16, nullable: true)]
    #[Groups(['csv'])]
    private ?string $phone = null;

    #[Assert\Email]
    #[ORM\Column(length: 128, nullable: true)]
    #[Groups(['csv'])]
    private ?string $email = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['csv'])]
    private ?string $address = null;

    #[ORM\Column(length: 16, nullable: true)]
    #[Groups(['csv'])]
    private ?string $INN = null;

    #[ORM\Column(length: 16, nullable: true)]
    #[Groups(['csv'])]
    private ?string $KPP = null;

    #[ORM\Column(length: 16, nullable: true)]
    #[Groups(['csv'])]
    private ?string $OGRN = null;

    #[ORM\Column(length: 16, nullable: true)]
    #[Groups(['csv'])]
    private ?string $OGRNIP = null;

    #[ORM\Column(length: 16, nullable: true)]
    #[Groups(['csv'])]
    private ?string $SNILS = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['csv'])]
    private ?string $passport = null;

    #[ORM\Column(length: 32, nullable: true)]
    #[Groups(['csv'])]
    private ?string $bankAccountNumber = null;

    #[ORM\Column(length: 32, nullable: true)]
    #[Groups(['csv'])]
    private ?string $bankCorrespondentAccount = null;

    #[ORM\Column(length: 64, nullable: true)]
    #[Groups(['csv'])]
    private ?string $bank = null;

    #[ORM\Column(length: 16, nullable: true)]
    #[Groups(['csv'])]
    private ?string $BIK = null;

    #[ORM\Column(length: 64, nullable: true)]
    #[Groups(['csv'])]
    private ?string $company = null;

    #[ORM\Column(length: 64, nullable: true)]
    #[Groups(['csv'])]
    private ?string $charter = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['csv'])]
    private ?string $legalAddress = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['csv'])]
    private ?string $mailingAddress = null;

    #[ORM\Column(length: 32, nullable: true)]
    #[Groups(['csv'])]
    private ?string $contractNumber = null;

    #[ORM\Column(length: 32, nullable: true)]
    #[Groups(['csv'])]
    private ?string $edoNumber = null;

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

    public function getTaxSystem(): ?string
    {
        return $this->taxSystem;
    }

    public function setTaxSystem(?string $taxSystem): self
    {
        $this->taxSystem = $taxSystem;

        return $this;
    }

    public function getVatRate(): ?string
    {
        return $this->vatRate;
    }

    public function setVatRate(?string $vatRate): self
    {
        $this->vatRate = $vatRate;

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

    public function getAddress(): ?string
    {
        return $this->address;
    }

    public function setAddress(?string $address): self
    {
        $this->address = $address;

        return $this;
    }

    public function getINN(): ?string
    {
        return $this->INN;
    }

    public function setINN(?string $INN): self
    {
        $this->INN = $INN;

        return $this;
    }

    public function getKPP(): ?string
    {
        return $this->KPP;
    }

    public function setKPP(?string $KPP): self
    {
        $this->KPP = $KPP;

        return $this;
    }

    public function getOGRN(): ?string
    {
        return $this->OGRN;
    }

    public function setOGRN(?string $OGRN): self
    {
        $this->OGRN = $OGRN;

        return $this;
    }

    public function getOGRNIP(): ?string
    {
        return $this->OGRNIP;
    }

    public function setOGRNIP(?string $OGRNIP): self
    {
        $this->OGRNIP = $OGRNIP;

        return $this;
    }

    public function getSNILS(): ?string
    {
        return $this->SNILS;
    }

    public function setSNILS(?string $SNILS): self
    {
        $this->SNILS = $SNILS;

        return $this;
    }

    public function getPassport(): ?string
    {
        return $this->passport;
    }

    public function setPassport(?string $passport): self
    {
        $this->passport = $passport;

        return $this;
    }

    public function getBankAccountNumber(): ?string
    {
        return $this->bankAccountNumber;
    }

    public function setBankAccountNumber(?string $bankAccountNumber): self
    {
        $this->bankAccountNumber = $bankAccountNumber;

        return $this;
    }

    public function getBankCorrespondentAccount(): ?string
    {
        return $this->bankCorrespondentAccount;
    }

    public function setBankCorrespondentAccount(?string $bankCorrespondentAccount): self
    {
        $this->bankCorrespondentAccount = $bankCorrespondentAccount;

        return $this;
    }

    public function getBank(): ?string
    {
        return $this->bank;
    }

    public function setBank(?string $bank): self
    {
        $this->bank = $bank;

        return $this;
    }

    public function getBIK(): ?string
    {
        return $this->BIK;
    }

    public function setBIK(?string $BIK): self
    {
        $this->BIK = $BIK;

        return $this;
    }

    public function getCompany(): ?string
    {
        return $this->company;
    }

    public function setCompany(?string $company): self
    {
        $this->company = $company;

        return $this;
    }

    public function getCharter(): ?string
    {
        return $this->charter;
    }

    public function setCharter(?string $charter): self
    {
        $this->charter = $charter;

        return $this;
    }

    public function getLegalAddress(): ?string
    {
        return $this->legalAddress;
    }

    public function setLegalAddress(?string $legalAddress): self
    {
        $this->legalAddress = $legalAddress;

        return $this;
    }

    public function getMailingAddress(): ?string
    {
        return $this->mailingAddress;
    }

    public function setMailingAddress(?string $mailingAddress): self
    {
        $this->mailingAddress = $mailingAddress;

        return $this;
    }

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

    public function getContractNumber(): ?string
    {
        return $this->contractNumber;
    }

    public function setContractNumber(?string $contractNumber): self
    {
        $this->contractNumber = $contractNumber;

        return $this;
    }

    public function getEdoNumber(): ?string
    {
        return $this->edoNumber;
    }

    public function setEdoNumber(?string $edoNumber): self
    {
        $this->edoNumber = $edoNumber;

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
