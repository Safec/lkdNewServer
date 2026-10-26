<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: "App\Repository\EventRepository")]
class Event
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;

    #[ORM\Column(type: 'string', length: 255)]
    #[Assert\NotBlank]
    private $title;

    #[ORM\Column(type: 'string', length: 255)]
    #[Assert\NotBlank]
    private $location;

    #[ORM\Column(type: 'datetime')]
    #[Assert\NotBlank]
    #[Assert\Type(\DateTimeInterface::class)]
    private $date;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $coverImage = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $speakerImage = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $speakerName;
    
    #[ORM\Column(type: 'text', nullable: true)]
    private $mainText;

    #[ORM\Column(type: 'text', nullable: true)]
    private $speakerText;

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    private $isRegistrationOpen = false;

    #[ORM\Column(type: 'boolean', options: ['default' => true])]
    private $isDraft = true;

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    private $isPublished = false;

    // Getters and Setters for each field
    // Добавьте свойство status, которое будет управлять isDraft и isPublished
    public function getStatus(): string
    {
        return $this->isPublished ? 'published' : 'draft';
    }

    public function setStatus(string $status): self
    {
        if ($status === 'published') {
            $this->isPublished = true;
            $this->isDraft = false;
        } else {
            $this->isPublished = false;
            $this->isDraft = true;
        }

        return $this;
    }

    public function getId(): ?int
    {
        return $this->id;;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function getLocation(): ?string
    {
        return $this->location;
    }

    public function setLocation(string $location): self
    {
        $this->location = $location;

        return $this;
    }

    public function getDate(): ?\DateTimeInterface
    {
        return $this->date;
    }

    public function setDate(\DateTimeInterface $date): self
    {
        $this->date = $date;

        return $this;
    }

    public function getCoverImage(): ?string
    {
        return $this->coverImage;
    }

    public function setCoverImage(?string $coverImage): self
    {
        $this->coverImage = $coverImage;

        return $this;
    }

    public function getSpeakerImage(): ?string
    {
        return $this->speakerImage;
    }

    public function setSpeakerImage(?string $speakerImage): self
    {
        $this->speakerImage = $speakerImage;

        return $this;
    }

    public function getMainText(): ?string
    {
        return $this->mainText;
    }

    public function setMainText(string $mainText): self
    {
        $this->mainText = $mainText;

        return $this;
    }

    public function getSpeakerName(): ?string
    {
        return $this->speakerName;
    }

    public function setSpeakerName(string $speakerName): self
    {
        $this->speakerName = $speakerName;

        return $this;
    }
    
    public function getSpeakerText(): ?string
    {
        return $this->speakerText;
    }

    public function setSpeakerText(?string $speakerText): self
    {
        $this->speakerText = $speakerText;

        return $this;
    }

    public function getIsRegistrationOpen(): ?bool
    {
        return $this->isRegistrationOpen;
    }

    public function setIsRegistrationOpen(bool $isRegistrationOpen): self
    {
        $this->isRegistrationOpen = $isRegistrationOpen;

        return $this;
    }

    public function getIsDraft(): ?bool
    {
        return $this->isDraft;
    }

    public function setIsDraft(bool $isDraft): self
    {
        $this->isDraft = $isDraft;

        return $this;
    }

    public function getIsPublished(): ?bool
    {
        return $this->isPublished;
    }

    public function setIsPublished(bool $isPublished): self
    {
        $this->isPublished = $isPublished;

        return $this;
    }
}
