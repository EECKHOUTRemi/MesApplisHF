<?php

namespace App\Entity\monpoids;

use App\Entity\User;
use App\Repository\monpoids\MeasurementRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: MeasurementRepository::class)]
class Measurement
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\Column(nullable: true)]
    private ?float $chest = null;

    #[ORM\Column(nullable: true)]
    private ?float $hips = null;

    #[ORM\Column(nullable: true)]
    private ?float $thigh = null;

    #[ORM\Column(nullable: true)]
    private ?float $waist = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getuser(): ?User
    {
        return $this->user;
    }

    public function setuser(?User $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function getChest(): ?float
    {
        return $this->chest;
    }

    public function setChest(?float $chest): static
    {
        $this->chest = $chest;

        return $this;
    }

    public function getHips(): ?float
    {
        return $this->hips;
    }

    public function setHips(?float $hips): static
    {
        $this->hips = $hips;

        return $this;
    }

    public function getThigh(): ?float
    {
        return $this->thigh;
    }

    public function setThigh(?float $thigh): static
    {
        $this->thigh = $thigh;

        return $this;
    }

    public function getWaist(): ?float
    {
        return $this->waist;
    }

    public function setWaist(?float $waist): static
    {
        $this->waist = $waist;

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
}
