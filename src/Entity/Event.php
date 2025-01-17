<?php

namespace App\Entity;

use App\Repository\EventRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Validator\Constraints as Assert;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: EventRepository::class)]
class Event
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['event:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    #[Groups(['event:read', 'event:write'])]
    private ?string $name = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Assert\NotBlank]
    #[Assert\Type("\DateTimeInterface")]
    #[Groups(['event:read', 'event:write'])]
    private ?\DateTimeInterface $date = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Groups(['event:read', 'event:write'])]
    private ?string $description = null;

    #[ORM\Column]
    #[Assert\Positive]
    #[Groups(['event:read', 'event:write'])]
    private ?int $maxParticipants = null;

    #[ORM\ManyToOne(inversedBy: 'events')]
    #[Groups(['event:read'])]
    private ?User $organizer = null;

    #[ORM\ManyToMany(targetEntity: User::class, inversedBy: 'events')]
    #[Groups(['event:read'])]
    private Collection $participants;

    #[ORM\ManyToMany(targetEntity: Game::class, inversedBy: 'events')]
    #[Groups(['event:read', 'event:write'])]
    private Collection $games;

    public function __construct()
    {
        $this->participants = new ArrayCollection();
        $this->games = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getDate(): ?\DateTimeInterface
    {
        return $this->date;
    }

    public function setDate(\DateTimeInterface $date): static
    {
        $this->date = $date;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getMaxParticipants(): ?int
    {
        return $this->maxParticipants;
    }

    public function setMaxParticipants(int $maxParticipants): static
    {
        $this->maxParticipants = $maxParticipants;

        return $this;
    }

    public function getOrganizer(): ?User
    {
        return $this->organizer;
    }

    public function setOrganizer(?User $organizer): static
    {
        $this->organizer = $organizer;

        return $this;
    }

    /**
     * @return Collection<int, User>
     */
    public function getParticipants(): Collection
    {
        return $this->participants;
    }

    public function addParticipant(User $participant): static
    {
        if (!$this->participants->contains($participant)) {
            $this->participants->add($participant);
        }

        return $this;
    }

    public function removeParticipant(User $participant): static
    {
        $this->participants->removeElement($participant);

        return $this;
    }

    /**
     * @return Collection<int, Game>
     */
    public function getGames(): Collection
    {
        return $this->games;
    }

    public function addGame(Game $game): static
    {
        if (!$this->games->contains($game)) {
            $this->games->add($game);
        }

        return $this;
    }

    public function removeGame(Game $game): static
    {
        $this->games->removeElement($game);

        return $this;
    }

    /**
     * Vérifie si l'événement est complet (nombre maximum de participants atteint)
     */
    public function isFull(): bool
    {
        return $this->participants->count() >= $this->maxParticipants;
    }
}
