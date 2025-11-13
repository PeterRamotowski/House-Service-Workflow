<?php

namespace App\ServiceManagement\Domain;

use App\IdentityAccess\Domain\User;
use App\Property\Domain\House;
use App\ServiceManagement\Infrastructure\Persistence\DoctrineServiceRequestRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: DoctrineServiceRequestRepository::class)]
#[ORM\Table(name: 'service_requests')]
class ServiceRequest
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'serviceRequests')]
    #[ORM\JoinColumn(nullable: false)]
    private ?House $house = null;

    #[ORM\Column(length: 50)]
    private ?string $serviceType = null; // cleaning, maintenance, inspection, etc.

    #[ORM\Column(length: 50)]
    private string $currentPlace = 'draft'; // Workflow state

    #[ORM\Column]
    private ?\DateTimeImmutable $scheduledDate = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $completedDate = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $notes = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    private ?string $estimatedDuration = null; // in hours

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    private ?string $actualDuration = null; // in hours

    #[ORM\ManyToOne(inversedBy: 'createdServiceRequests')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $createdBy = null;

    #[ORM\ManyToOne(inversedBy: 'assignedServiceRequests')]
    private ?User $assignedCleaner = null;

    #[ORM\Column(length: 20)]
    private string $priority = 'normal'; // low, normal, high, urgent

    #[ORM\OneToMany(mappedBy: 'serviceRequest', targetEntity: ServiceTask::class, cascade: ['persist', 'remove'])]
    private Collection $tasks;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $workflowHistory = [];

    public function __construct()
    {
        $this->tasks = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
        $this->workflowHistory = [];
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getHouse(): ?House
    {
        return $this->house;
    }

    public function setHouse(?House $house): static
    {
        $this->house = $house;

        return $this;
    }

    public function getServiceType(): ?string
    {
        return $this->serviceType;
    }

    public function setServiceType(string $serviceType): static
    {
        $this->serviceType = $serviceType;

        return $this;
    }

    public function getCurrentPlace(): string
    {
        return $this->currentPlace;
    }

    public function setCurrentPlace(string $currentPlace): static
    {
        $this->currentPlace = $currentPlace;

        return $this;
    }

    public function getScheduledDate(): ?\DateTimeImmutable
    {
        return $this->scheduledDate;
    }

    public function setScheduledDate(\DateTimeImmutable $scheduledDate): static
    {
        $this->scheduledDate = $scheduledDate;

        return $this;
    }

    public function getCompletedDate(): ?\DateTimeImmutable
    {
        return $this->completedDate;
    }

    public function setCompletedDate(?\DateTimeImmutable $completedDate): static
    {
        $this->completedDate = $completedDate;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function setNotes(?string $notes): static
    {
        $this->notes = $notes;

        return $this;
    }

    public function getEstimatedDuration(): ?string
    {
        return $this->estimatedDuration;
    }

    public function setEstimatedDuration(?string $estimatedDuration): static
    {
        $this->estimatedDuration = $estimatedDuration;

        return $this;
    }

    public function getActualDuration(): ?string
    {
        return $this->actualDuration;
    }

    public function setActualDuration(?string $actualDuration): static
    {
        $this->actualDuration = $actualDuration;

        return $this;
    }

    public function getCreatedBy(): ?User
    {
        return $this->createdBy;
    }

    public function setCreatedBy(?User $createdBy): static
    {
        $this->createdBy = $createdBy;

        return $this;
    }

    public function getAssignedCleaner(): ?User
    {
        return $this->assignedCleaner;
    }

    public function setAssignedCleaner(?User $assignedCleaner): static
    {
        $this->assignedCleaner = $assignedCleaner;

        return $this;
    }

    public function getPriority(): string
    {
        return $this->priority;
    }

    public function setPriority(string $priority): static
    {
        $this->priority = $priority;

        return $this;
    }

    /**
     * @return Collection<int, ServiceTask>
     */
    public function getTasks(): Collection
    {
        return $this->tasks;
    }

    public function addTask(ServiceTask $task): static
    {
        if (!$this->tasks->contains($task)) {
            $this->tasks->add($task);
            $task->setServiceRequest($this);
        }

        return $this;
    }

    public function removeTask(ServiceTask $task): static
    {
        if ($this->tasks->removeElement($task)) {
            if ($task->getServiceRequest() === $this) {
                $task->setServiceRequest(null);
            }
        }

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function getWorkflowHistory(): ?array
    {
        return $this->workflowHistory;
    }

    public function setWorkflowHistory(?array $workflowHistory): static
    {
        $this->workflowHistory = $workflowHistory;

        return $this;
    }

    public function addWorkflowHistoryEntry(string $from, string $to, string $transition): void
    {
        $this->workflowHistory[] = [
            'from' => $from,
            'to' => $to,
            'transition' => $transition,
            'timestamp' => (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
        ];
    }

    #[ORM\PreUpdate]
    public function updateTimestamp(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }
}