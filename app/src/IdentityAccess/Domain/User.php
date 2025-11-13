<?php

namespace App\IdentityAccess\Domain;

use App\IdentityAccess\Infrastructure\Persistence\DoctrineUserRepository;
use App\Property\Domain\House;
use App\ServiceManagement\Domain\ServiceRequest;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: DoctrineUserRepository::class)]
#[ORM\Table(name: '`users`')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180, unique: true)]
    private ?string $email = null;

    #[ORM\Column]
    private array $roles = [];

    #[ORM\Column]
    private ?string $password = null;

    #[ORM\Column(length: 100)]
    private ?string $firstName = null;

    #[ORM\Column(length: 100)]
    private ?string $lastName = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $phone = null;

    #[ORM\Column]
    private bool $isActive = true;

    #[ORM\OneToMany(mappedBy: 'owner', targetEntity: House::class)]
    private Collection $ownedHouses;

    #[ORM\OneToMany(mappedBy: 'assignedCleaner', targetEntity: ServiceRequest::class)]
    private Collection $assignedServiceRequests;

    #[ORM\OneToMany(mappedBy: 'createdBy', targetEntity: ServiceRequest::class)]
    private Collection $createdServiceRequests;

    public function __construct()
    {
        $this->ownedHouses = new ArrayCollection();
        $this->assignedServiceRequests = new ArrayCollection();
        $this->createdServiceRequests = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;
        return $this;
    }

    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    public function getRoles(): array
    {
        $roles = $this->roles;
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    public function setRoles(array $roles): static
    {
        $this->roles = $roles;
        return $this;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;
        return $this;
    }

    public function eraseCredentials(): void
    {
        // TODO: clear sensitive data on the user if any
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function setFirstName(string $firstName): static
    {
        $this->firstName = $firstName;
        return $this;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function setLastName(string $lastName): static
    {
        $this->lastName = $lastName;
        return $this;
    }

    public function getFullName(): string
    {
        return $this->firstName . ' ' . $this->lastName;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setPhone(?string $phone): static
    {
        $this->phone = $phone;
        return $this;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): static
    {
        $this->isActive = $isActive;
        return $this;
    }

    /**
     * @return Collection<int, House>
     */
    public function getOwnedHouses(): Collection
    {
        return $this->ownedHouses;
    }

    public function addOwnedHouse(House $ownedHouse): static
    {
        if (!$this->ownedHouses->contains($ownedHouse)) {
            $this->ownedHouses->add($ownedHouse);
            $ownedHouse->setOwner($this);
        }

        return $this;
    }

    public function removeOwnedHouse(House $ownedHouse): static
    {
        if ($this->ownedHouses->removeElement($ownedHouse)) {
            if ($ownedHouse->getOwner() === $this) {
                $ownedHouse->setOwner(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, ServiceRequest>
     */
    public function getAssignedServiceRequests(): Collection
    {
        return $this->assignedServiceRequests;
    }

    public function addAssignedServiceRequest(ServiceRequest $assignedServiceRequest): static
    {
        if (!$this->assignedServiceRequests->contains($assignedServiceRequest)) {
            $this->assignedServiceRequests->add($assignedServiceRequest);
            $assignedServiceRequest->setAssignedCleaner($this);
        }

        return $this;
    }

    public function removeAssignedServiceRequest(ServiceRequest $assignedServiceRequest): static
    {
        if ($this->assignedServiceRequests->removeElement($assignedServiceRequest)) {
            if ($assignedServiceRequest->getAssignedCleaner() === $this) {
                $assignedServiceRequest->setAssignedCleaner(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, ServiceRequest>
     */
    public function getCreatedServiceRequests(): Collection
    {
        return $this->createdServiceRequests;
    }

    public function addCreatedServiceRequest(ServiceRequest $createdServiceRequest): static
    {
        if (!$this->createdServiceRequests->contains($createdServiceRequest)) {
            $this->createdServiceRequests->add($createdServiceRequest);
            $createdServiceRequest->setCreatedBy($this);
        }

        return $this;
    }

    public function removeCreatedServiceRequest(ServiceRequest $createdServiceRequest): static
    {
        if ($this->createdServiceRequests->removeElement($createdServiceRequest)) {
            if ($createdServiceRequest->getCreatedBy() === $this) {
                $createdServiceRequest->setCreatedBy(null);
            }
        }

        return $this;
    }
}